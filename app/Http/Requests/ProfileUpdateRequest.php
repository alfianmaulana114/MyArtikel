<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProfileUpdateRequest extends FormRequest
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
        $userId = auth()->id();

        return [
            'name' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z\s]+$/'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$userId],
            'bio' => ['nullable', 'string', 'max:500'],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
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
            'name.required' => 'Name is required.',
            'name.regex' => 'Name can only contain letters and spaces.',
            'name.max' => 'Name cannot exceed 255 characters.',
            'email.required' => 'Email is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email is already taken.',
            'bio.max' => 'Bio cannot exceed 500 characters.',
            'avatar.image' => 'Avatar must be an image file.',
            'avatar.mimes' => 'Avatar must be a JPEG, PNG, JPG, or GIF file.',
            'avatar.max' => 'Avatar size cannot exceed 2MB.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $name = $this->input('name');
            $bio = $this->input('bio');

            if ($name) {
                // Check for suspicious patterns in name
                $suspiciousPatterns = [
                    '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/is',
                    '/javascript:/i',
                    '/on\w+\s*=/i',
                    '/<[^>]+>/',
                ];

                foreach ($suspiciousPatterns as $pattern) {
                    if (preg_match($pattern, $name)) {
                        $validator->errors()->add('name', 'Name contains invalid characters.');
                        break;
                    }
                }
            }

            if ($bio) {
                // Check for suspicious patterns in bio
                $dangerousPatterns = [
                    '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/is',
                    '/javascript:/i',
                    '/vbscript:/i',
                    '/on\w+\s*=/i',
                    '/data:text\/html/i',
                ];

                foreach ($dangerousPatterns as $pattern) {
                    if (preg_match($pattern, $bio)) {
                        $validator->errors()->add('bio', 'Bio contains potentially dangerous content.');
                        break;
                    }
                }
            }
        });
    }
}
