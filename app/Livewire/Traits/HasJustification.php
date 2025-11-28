<?php

namespace App\Livewire\Traits;

trait HasJustification
{
    /**
     * Base validation rules for justification text.
     *
     * @param bool $required When true, justification must be present.
     * @return array<int,string>
     */
    protected function justificationRules(bool $required = true): array
    {
        $base = ['string', 'min:10', 'max:200', 'not_regex:/^\s*$/'];

        if ($required) {
            array_unshift($base, 'required');
        } else {
            array_unshift($base, 'nullable');
        }

        return $base;
    }

    /**
     * Validate only the justification field using the shared rules.
     *
     * @param bool $required When true, justification must be present.
     */
    protected function validateJustificationField(bool $required = true): void
    {
        $this->validateOnly('justification', [
            'justification' => $this->justificationRules($required),
        ]);
    }
}

