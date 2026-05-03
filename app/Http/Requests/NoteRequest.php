<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\Note;

class NoteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Basic content
            'title' => ['nullable', 'string', 'max:255'],
            'content' => ['required_without:content_json', 'string', 'max:10000'],
            'content_json' => ['required_without:content', 'array'],
            'is_rich_text' => ['boolean'],
            
            // Article association
            'article_id' => ['required', 'integer', 'exists:articles,id'],
            
            // Note anchoring
            'paragraph_index' => ['nullable', 'integer', 'min:0'],
            'paragraph_id' => ['nullable', 'string', 'max:255'],
            'start_offset' => ['nullable', 'integer', 'min:0'],
            'end_offset' => ['nullable', 'integer', 'min:0', 'gte:start_offset'],
            
            // Categories and tags
            'type' => ['nullable', 'in:' . implode(',', [Note::TYPE_PERSONAL, Note::TYPE_RESEARCH, Note::TYPE_DRAFT])],
            'category' => ['nullable', 'string', 'max:100'],
            'tags' => ['nullable', 'array', 'max:10'],
            'tags.*' => ['string', 'max:50'],
            
            // Privacy and sync
            'is_private' => ['boolean'],
            'device_id' => ['nullable', 'string', 'max:255'],
            'sync_status' => ['nullable', 'in:' . implode(',', [Note::SYNC_SYNCED, Note::SYNC_PENDING, Note::SYNC_CONFLICT, Note::SYNC_ERROR])],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.max' => 'Title cannot exceed 255 characters.',
            'content.required_without' => 'Either content or rich text content is required.',
            'content.max' => 'Note content cannot exceed 10000 characters.',
            'content_json.required_without' => 'Either content or rich text content is required.',
            'content_json.array' => 'Rich text content must be a valid JSON array.',
            'article_id.required' => 'Article ID is required.',
            'article_id.exists' => 'The specified article does not exist.',
            'paragraph_index.integer' => 'Paragraph index must be a valid integer.',
            'paragraph_index.min' => 'Paragraph index must be a positive number.',
            'paragraph_id.string' => 'Paragraph ID must be a string.',
            'start_offset.integer' => 'Start offset must be a valid integer.',
            'start_offset.min' => 'Start offset must be a positive number.',
            'end_offset.integer' => 'End offset must be a valid integer.',
            'end_offset.min' => 'End offset must be a positive number.',
            'end_offset.gte' => 'End offset must be greater than or equal to start offset.',
            'type.in' => 'Type must be one of: personal, research, draft.',
            'category.max' => 'Category cannot exceed 100 characters.',
            'tags.array' => 'Tags must be an array.',
            'tags.max' => 'Maximum 10 tags allowed.',
            'tags.*.string' => 'Each tag must be a string.',
            'tags.*.max' => 'Each tag cannot exceed 50 characters.',
            'is_private.boolean' => 'Privacy setting must be a boolean.',
            'device_id.max' => 'Device ID cannot exceed 255 characters.',
            'sync_status.in' => 'Sync status must be one of: synced, pending, conflict, error.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Validate rich text content if provided
            if ($this->input('is_rich_text') && $this->input('content_json')) {
                $this->validateRichTextContent($validator);
            }
            
            // Validate paragraph anchoring consistency
            $this->validateParagraphAnchoring($validator);
            
            // Additional security checks for note content
            $this->validateContentSecurity($validator);
        });
    }
    
    /**
     * Validate rich text content structure
     */
    protected function validateRichTextContent($validator)
    {
        $contentJson = $this->input('content_json');
        
        if (!is_array($contentJson)) {
            $validator->errors()->add('content_json', 'Rich text content must be a valid array.');
            return;
        }
        
        // Basic validation for common rich text formats
        if (isset($contentJson['ops']) && is_array($contentJson['ops'])) {
            // Quill.js format validation
            foreach ($contentJson['ops'] as $index => $op) {
                if (!isset($op['insert'])) {
                    $validator->errors()->add('content_json', "Rich text operation {$index} is missing insert property.");
                }
            }
        }
    }
    
    /**
     * Validate paragraph anchoring consistency
     */
    protected function validateParagraphAnchoring($validator)
    {
        $paragraphIndex = $this->input('paragraph_index');
        $paragraphId = $this->input('paragraph_id');
        $startOffset = $this->input('start_offset');
        $endOffset = $this->input('end_offset');
        
        // If any anchoring field is provided, validate consistency
        if (!is_null($paragraphIndex) || !is_null($paragraphId)) {
            if (is_null($paragraphIndex) || is_null($paragraphId)) {
                $validator->errors()->add('paragraph_index', 'Both paragraph index and paragraph ID are required for anchoring.');
            }
            
            if (!is_null($startOffset) && is_null($endOffset)) {
                $validator->errors()->add('end_offset', 'End offset is required when start offset is provided.');
            }
            
            if (!is_null($endOffset) && is_null($startOffset)) {
                $validator->errors()->add('start_offset', 'Start offset is required when end offset is provided.');
            }
        }
    }
    
    /**
     * Validate content security
     */
    protected function validateContentSecurity($validator)
    {
        $content = $this->input('content');
        $contentJson = $this->input('content_json');
        
        $contentToCheck = $content ?? json_encode($contentJson);
        
        if ($contentToCheck) {
            // Check for potentially dangerous content
            $dangerousPatterns = [
                '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/is',
                '/javascript:/i',
                '/vbscript:/i',
                '/on\w+\s*=/i',
                '/data:text\/html/i',
                '/<iframe\b[^<]*(?:(?!<\/iframe>)<[^<]*)*<\/iframe>/is',
                '/<object\b[^<]*(?:(?!<\/object>)<[^<]*)*<\/object>/is',
                '/<embed\b[^<]*(?:(?!<\/embed>)<[^<]*)*<\/embed>/is',
                '/<form\b[^<]*(?:(?!<\/form>)<[^<]*)*<\/form>/is',
            ];
            
            foreach ($dangerousPatterns as $pattern) {
                if (preg_match($pattern, $contentToCheck)) {
                    $validator->errors()->add('content', 'The note content contains potentially dangerous HTML or JavaScript.');
                    break;
                }
            }
            
            // Check for excessive special characters that might indicate injection attempts
            if (substr_count($contentToCheck, '<') > 100 || substr_count($contentToCheck, '>') > 100) {
                $validator->errors()->add('content', 'The note content contains too many HTML tags.');
            }
        }
    }
}