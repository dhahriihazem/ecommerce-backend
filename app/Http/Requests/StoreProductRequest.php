<?php

namespace App\Http\Requests;

use App\Enums\ProductType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
   
    public function authorize(): bool
    {
        // Set to true to allow anyone to attempt to create a product.
        // Authorization can be handled by a policy or in the controller if needed.
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'type' => ['required', Rule::enum(ProductType::class)],

            // Rules for 'fixed_price' products
            'price' => ['required_if:type,'.ProductType::FixedPrice->value, 'numeric', 'min:0', 'nullable'],
            'stock_quantity' => ['required_if:type,'.ProductType::FixedPrice->value, 'integer', 'min:0', 'nullable'],

            // Rules for 'auction' products
            'starting_price' => ['required_if:type,'.ProductType::Auction->value, 'numeric', 'min:0', 'nullable'],
            'auction_end_time' => ['required_if:type,'.ProductType::Auction->value, 'date', 'after:now', 'nullable'],
        ];
    }
}