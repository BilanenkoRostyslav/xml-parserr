<?php

namespace App\Requests;

use App\Requests\Abstracts\BaseApiRequest;

class FilterRequest extends BaseApiRequest
{
    public function prepareForValidation(): void
    {
        $this->merge([
            'filters' => $this->input('filters', []),
        ]);
    }

    public function rules(): array
    {
        return [
            'filters' => ['nullable', 'array',],
            'filters.*' => ['required', 'string'],
        ];
    }
}