<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
/**
 * @OA\Schema(
 *     schema="StoreOrderRequest",
 *     type="object",
 *     title="Store Order Request",
 *     required={"items"},
 *     @OA\Property(
 *         property="items",
 *         type="array",
 *         description="An array of products to include in the order.",
 *         @OA\Items(
 *             required={"product_id", "quantity"},
 *             @OA\Property(property="product_id", type="integer", description="The ID of the product.", example=1),
 *             @OA\Property(property="quantity", type="integer", description="The quantity of the product.", example=2)
 *         )
 *     ),
 *     @OA\Property(
 *         property="idempotency_key",
 *         type="string",
 *         nullable=true,
 *         description="A unique key to prevent duplicate order creation on network retries.",
 *         example="unique-key-for-this-purchase-123"
 *     )
 * )
 */
class StoreOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization is handled by the 'auth:sanctum' middleware on the route.
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
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'idempotency_key' => 'sometimes|string|max:255|unique:orders,idempotency_key',
        ];
    }
}