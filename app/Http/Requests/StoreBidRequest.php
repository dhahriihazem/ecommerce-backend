<?php

namespace App\Http\Requests;

use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreBidRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // The route middleware already ensures the user is authenticated.
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Product $product */
        $product = $this->route('product');
 
        // Only apply bid_amount rules if the product is an auction item.
        if ($product->isAuction()) {
            // The highest bid is either the current highest bid or the starting price if no bids have been made.
            $minBid = $product->current_highest_bid ?? $product->starting_price;
 
            return [
                'bid_amount' => [
                    'required',
                    'numeric',
                    'gt:' . $minBid,
                ],
            ];
        }
 
        return [];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            /** @var Product $product */
            $product = $this->route('product');

            // Rule 1: Ensure the product is an auction item.
            if (!$product->isAuction()) {
                $validator->errors()->add('product', 'This product is not available for auction.');
                // No need to check other auction-specific rules if it's not an auction.
                return;
            }

            // Rule 2: Ensure the auction has not ended.
            if ($product->auction_end_time && Carbon::now()->gt($product->auction_end_time)) {
                $validator->errors()->add('product', 'The auction for this product has ended.');
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        /** @var Product|null $product */
        $product = $this->route('product');

        if ($product && $product->isAuction()) {
            // Check if there are any actual bids on the product to provide a more specific message.
            if ($product->bids()->doesntExist()) {
                return [
                    'bid_amount.gt' => 'Your bid must be higher than the starting price of :value.',
                ];
            }
        }

        return [
            'bid_amount.gt' => 'Your bid must be higher than the current highest bid of :value.',
        ];
    }
}
