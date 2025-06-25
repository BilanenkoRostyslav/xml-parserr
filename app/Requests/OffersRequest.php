<?php

namespace App\Requests;

use App\Enums\Order;
use App\Enums\OrderAttribute;
use App\Requests\Abstracts\BaseApiRequest;
use Illuminate\Validation\Rule;

class OffersRequest extends BaseApiRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'page' => $this->input('page', 1),
            'limit' => $this->input('limit', 10),
            'sortAttribute' => $this->input('sortAttribute', 'id'),
            'sortBy' => $this->input('sortBy', 'asc'),
            'filters' => $this->input('filters', []),
        ]);
    }

    public function rules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1',],
            'limit' => ['nullable', 'integer', 'min:1',],
            'sortAttribute' => ['nullable', 'string', Rule::in(OrderAttribute::values())],
            'sortBy' => ['nullable', 'string', Rule::in(Order::values())],
            'filters' => ['nullable', 'array',],
            'filters.*' => ['required', 'string'],
        ];
    }
}