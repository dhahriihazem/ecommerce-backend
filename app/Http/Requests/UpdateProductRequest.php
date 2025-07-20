<?php

namespace App\Http\Requests;

use App\Enums\ProductType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Set to true to allow anyone to attempt to update a product.
        // You should ideally use a Policy to check if the authenticated user owns the product.
        // Example: return $this->user()->can('update', $this->route('product'));
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
            'name' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            // Note: Changing a product's type can have side effects.
            // This validation allows it, but your application logic should handle the transition.
            'type' => ['sometimes', 'required', Rule::enum(ProductType::class)],

            // Rules for 'fixed_price' products
            'price' => ['sometimes', 'required_if:type,'.ProductType::FixedPrice->value, 'numeric', 'min:0', 'nullable'],
            'stock_quantity' => ['sometimes', 'required_if:type,'.ProductType::FixedPrice->value, 'integer', 'min:0', 'nullable'],

            // Rules for 'auction' products
            'starting_price' => ['sometimes', 'required_if:type,'.ProductType::Auction->value, 'numeric', 'min:0', 'nullable'],
            'auction_end_time' => ['sometimes', 'required_if:type,'.ProductType::Auction->value, 'date', 'after:now', 'nullable'],
        ];
    }
}