<?php

namespace App\Http\Requests;

use App\Models\Workspace;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ImportWorkspaceRequest extends FormRequest
{
    public const MAX_FILE_KILOBYTES = 5 * 1024;

    public function authorize(): bool
    {
        $workspace = $this->route('workspace');

        return $workspace instanceof Workspace
            && ($this->user()?->can('update', $workspace) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $fileTypeRules = match ($this->input('format')) {
            'json' => ['mimetypes:application/json,text/plain', 'extensions:json'],
            'csv' => [
                'mimetypes:text/csv,text/plain,application/csv,application/vnd.ms-excel',
                'extensions:csv',
            ],
            default => [],
        };

        return [
            'format' => ['required', 'string', Rule::in(['json', 'csv'])],
            'file' => [
                'bail',
                'required',
                'file',
                'max:'.self::MAX_FILE_KILOBYTES,
                ...$fileTypeRules,
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'file.max' => __('data_transfer.import.file_too_large'),
            'file.mimetypes' => __('data_transfer.import.content_type'),
            'file.extensions' => __('data_transfer.import.extension'),
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

    public function importFormat(): string
    {
        return (string) $this->validated('format');
    }
}
