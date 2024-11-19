<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class StoreDocumentRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'expires_at' => [
                'nullable',
                'date',
                'after:' . now()->addWeek()->startOfDay()->toDateTimeString(),
                'before:' . now()->addYears(5)->endOfDay()->toDateTimeString()
            ],
            'file' => ['required', File::types(['pdf'])->max(10 * 1024)],
        ];
    }
}
