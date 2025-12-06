<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreHoldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        return [
            'product_id' => 'required|integer|exists:products,id',
            'qty'        => 'required|integer|min:1',
        ];
    }
}
