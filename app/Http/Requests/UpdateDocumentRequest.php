<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDocumentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // As an improvement, I'd want to put this into a policy instead of having the check inline in the request
        return $this->user()->id === $this->route('document')->owner_id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // another improvement I'd make is having a parent request for store and for update (StoreOrUpdateDocumentRequest)
        // that would have the common rules for both store and update requests

        // I'd also add in custom messages so that this is a bit more user-friendly, instead of something lie "The archived at must be at most 1732056981."
        return [
            'archived_at' => ['nullable', 'int', 'min:0', 'max:' . now()->timestamp],
        ];
    }
}
