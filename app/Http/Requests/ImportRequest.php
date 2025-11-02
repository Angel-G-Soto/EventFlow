<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class ImportRequest extends FormRequest
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
     * Validation rules for the import payload.
     * - 'source_id' must be one of the configured allowed sources.
     * - 'payload' is a SINGLE associative array (object), not an array of rows.
     *   (Hence 'payload.name' and not 'payload.*.name')
     */
    public function rules(): array
    {
        $allowed = config('sources.allowed_sources', []);

        return [
            'source_id' => ['required', 'string', 'max:191', Rule::in($allowed)],
            'payload' => 'required|array',
            'payload.name' => 'required|string|max:255',
            'payload.email' => 'required|email|max:255',
            'payload.assoc_id' => 'required|integer',
            'payload.association_name' => 'required|string|max:255',
            'payload.counselor' => 'nullable|string|max:255',
            'payload.email_counselor' => 'nullable|email|max:255',
        ];
    }
}

