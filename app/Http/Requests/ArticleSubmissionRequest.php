<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ArticleSubmissionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'url' => [
                'required',
                'string',
                'max:2048',
                'url',
                'active_url',
                // Custom rule to prevent SSRF
                function ($attribute, $value, $fail) {
                    $ssrfService = app(\App\Services\SsrfProtectionService::class);
                    if (!$ssrfService->validateUrl($value)) {
                        $fail('The ' . $attribute . ' is not accessible or contains invalid content.');
                    }
                },
            ],
            'title' => ['nullable', 'string', 'max:255'],
            'tags' => ['nullable', 'array', 'max:10'],
            'tags.*' => ['string', 'max:50', 'regex:/^[a-zA-Z0-9\s-]+$/'],
            'notes' => ['nullable', 'string', 'max:1000'],
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
            'url.required' => 'Please provide a valid URL to process.',
            'url.url' => 'The provided URL format is invalid.',
            'url.max' => 'The URL is too long (maximum 2048 characters).',
            'tags.*.regex' => 'Tags can only contain letters, numbers, spaces, and hyphens.',
            'notes.max' => 'Notes cannot exceed 1000 characters.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Additional security checks
            $url = $this->input('url');
            
            if ($url) {
                // Check for suspicious patterns
                $suspiciousPatterns = [
                    '/\.exe$/i',
                    '/\.sh$/i',
                    '/\.bat$/i',
                    '/\.cmd$/i',
                    '/javascript:/i',
                    '/data:text\/html/i',
                    '/file:\/\//i',
                    '/localhost/i',
                    '/127\.0\.0\.1/i',
                    '/192\.168\./i',
                    '/10\./i',
                    '/172\.(1[6-9]|2[0-9]|3[01])\./i',
                ];
                
                foreach ($suspiciousPatterns as $pattern) {
                    if (preg_match($pattern, $url)) {
                        $validator->errors()->add('url', 'The URL contains potentially dangerous content.');
                        break;
                    }
                }
            }
        });
    }
}