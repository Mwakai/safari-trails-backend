<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:102400',
                'mimes:jpg,jpeg,png,gif,webp,mp4,avi,wmv,webm,pdf,gpx,svg,pptx',
            ],
            'alt_text' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'A file is required',
            'file.file' => 'The upload must be a valid file',
            'file.max' => 'File size must not exceed 100MB',
            'file.mimes' => 'File type is not supported. Allowed: jpg, jpeg, png, gif, webp, mp4, avi, wmv, webm, pdf, gpx, svg, pptx',
            'alt_text.max' => 'Alt text must not exceed 255 characters',
        ];
    }
}
