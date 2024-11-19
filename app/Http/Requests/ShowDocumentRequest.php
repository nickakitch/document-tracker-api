<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShowDocumentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // As an improvement, I'd want to put this into a policy instead of having the check inline in the request
        return $this->user()->id === $this->route('document')->owner_id;
    }
}
