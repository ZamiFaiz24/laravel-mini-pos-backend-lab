<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $supplier = $this->route('supplier');

        return [
            'name' => ['required', 'string', 'max:255'],
            'contact_info' => [
                'required',
                'string',
                'max:255',
                Rule::unique('suppliers', 'contact_info')
                    ->ignore($supplier->id),
            ],
        ];
    }
}
