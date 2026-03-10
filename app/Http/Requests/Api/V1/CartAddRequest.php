<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CartAddRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'product_id' => 'required',
            'product_variant_id' => 'required',
            'quantity' => 'required',
        ];
    }

    /**
     * Custom message for validation
     *
     * @return array
     */
    public function messages()
    {
        return [
            'product_id.required' => 'Product is required!',
            'product_variant_id.required' => 'Product Variant is required!',
            'quantity.required' => 'Quantity is required!',
        ];
    }
}
