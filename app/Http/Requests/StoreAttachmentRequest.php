<?php

namespace App\Http\Requests;

use App\Models\Todo;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class StoreAttachmentRequest extends FormRequest
{
    public const MAX_FILE_KILOBYTES = 10 * 1024;

    public function authorize(): bool
    {
        $todo = $this->route('todo');

        return $todo instanceof Todo
            && ($this->user()?->can('update', $todo) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => [
                'bail',
                'required',
                'file',
                'max:'.self::MAX_FILE_KILOBYTES,
                'mimetypes:image/jpeg,image/png,image/webp,application/pdf,text/plain,text/csv,application/csv,application/vnd.ms-excel,application/json',
                'extensions:jpg,jpeg,png,webp,pdf,txt,csv,json',
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'file.max' => __('data_transfer.attachment.file_too_large'),
            'file.mimetypes' => __('data_transfer.attachment.content_type'),
            'file.extensions' => __('data_transfer.attachment.extension'),
        ];
    }

    public function uploadedFile(): UploadedFile
    {
        $file = $this->file('file');

        if (! $file instanceof UploadedFile) {
            throw ValidationException::withMessages([
                'file' => __('validation.uploaded', ['attribute' => 'file']),
            ]);
        }

        return $file;
    }
}
