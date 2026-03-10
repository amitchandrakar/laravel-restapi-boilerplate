<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CartUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'id' => 'required|exists:oj_cart_items',
            'product_id' => 'required|exists:oj_products,id',
            'product_variant_id' => 'required|exists:oj_product_variants,id',
            'quantity' => 'required|numeric',
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
            'id.required' => 'Cart Item Id is required!',
            'id.exists' => 'Cart Item does not exists!',
            'product_id.required' => 'Product is required!',
            'product_id.exists' => 'Product does not exists!',
            'product_variant_id.required' => 'Product Variant is required!',
            'product_variant_id.exists' => 'Package Type does not exists!',
            'quantity.required' => 'Quantity is required!',
            'quantity.numeric' => 'Quantity is not a number!',
        ];
    }
}
