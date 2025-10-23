<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NexoImportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * We will handle authorization with middleware, so we can set this to true.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'source_id' => 'nullable|string|max:191',
            'rows' => 'required|array|min:1',
            'rows.*.name' => 'required|string|max:255',
            'rows.*.email' => 'required|email|max:255',
            'rows.*.assoc_id' => 'required|integer', // external association id from Nexo
            'rows.*.association_name' => 'required|string|max:255',
            'rows.*.counselor' => 'nullable|string|max:255',
            'rows.*.email_counselor' => 'nullable|email|max:255',
        ];
    }
}

