<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class TagRequest extends FormRequest
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
        $tagId = $this->route('tag');

        return [
            'name' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-zA-Z0-9\s-]+$/',
                'unique:tags,name'.($tagId ? ','.$tagId : '').',id,user_id,'.auth()->id(),
            ],
            'color' => ['nullable', 'string', 'max:7', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'description' => ['nullable', 'string', 'max:200'],
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
            'name.required' => 'Tag name is required.',
            'name.max' => 'Tag name cannot exceed 50 characters.',
            'name.regex' => 'Tag name can only contain letters, numbers, spaces, and hyphens.',
            'name.unique' => 'You already have a tag with this name.',
            'color.regex' => 'Color must be a valid hex color code (e.g., #FF0000).',
            'color.max' => 'Color code cannot exceed 7 characters.',
            'description.max' => 'Description cannot exceed 200 characters.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $name = $this->input('name');

            if ($name) {
                // Check for reserved/suspicious tag names
                $reservedNames = [
                    'admin', 'administrator', 'root', 'superuser', 'system',
                    'javascript', 'script', 'eval', 'alert', 'prompt',
                    'xss', 'sql', 'injection', 'hack', 'exploit',
                ];

                if (in_array(strtolower($name), $reservedNames)) {
                    $validator->errors()->add('name', 'This tag name is reserved and cannot be used.');
                }

                // Check for excessive length variations
                if (strlen($name) < 2) {
                    $validator->errors()->add('name', 'Tag name must be at least 2 characters long.');
                }
            }
        });
    }
}
