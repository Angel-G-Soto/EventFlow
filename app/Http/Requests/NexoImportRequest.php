<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NexoImportRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'source_id' => ['nullable', 'string', 'max:191'],
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.name' => ['required', 'string', 'max:255'],
            'rows.*.email' => ['required', 'email', 'max:255'],
            'rows.*.assoc_id' => ['required', 'integer'], // external association id from Nexo
            'rows.*.association_name' => ['required', 'string', 'max:255'],
            'rows.*.counselor' => ['nullable', 'string', 'max:255'],
            'rows.*.email_counselor' => ['nullable', 'email', 'max:255'],
        ];
    }

    /*
    public function prepareForValidation()
    {
        // normalize keys if Nexo uses different names; e.g. accept 'id' as assoc id
        if ($this->has('rows')) {
            $rows = collect($this->input('rows'))->map(function ($r) {
                if (isset($r['id']) && !isset($r['assoc_id'])) {
                    $r['assoc_id'] = $r['id'];
                }
                return $r;
            })->toArray();
            $this->merge(['rows' => $rows]);
        }
    }
    */
}

