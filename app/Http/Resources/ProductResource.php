<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="ProductResource",
 *     title="Product Resource",
 *     description="Product resource model",
 *     @OA\Property(property="id", type="integer", readOnly=true, example=1),
 *     @OA\Property(property="name", type="string", example="Vintage T-Shirt"),
 *     @OA\Property(property="description", type="string", example="A cool vintage t-shirt from the 90s."),
 *     @OA\Property(property="type", type="string", enum={"fixed_price", "auction"}, example="fixed_price"),
 *     @OA\Property(property="price", type="number", format="float", example=29.99, description="Present only for 'fixed_price' products."),
 *     @OA\Property(property="stock_quantity", type="integer", example=100, description="Present only for 'fixed_price' products."),
 *     @OA\Property(property="starting_price", type="number", format="float", example=50.00, description="Present only for 'auction' products."),
 *     @OA\Property(property="current_highest_bid", type="number", format="float", example=75.50, description="Present only for 'auction' products."),
 *     @OA\Property(property="auction_end_time", type="string", format="date-time", example="2024-12-31T23:59:59.000000Z", description="Present only for 'auction' products."),
 *     @OA\Property(property="created_at", type="string", format="date-time", readOnly=true),
 *     @OA\Property(property="updated_at", type="string", format="date-time", readOnly=true),
 * )
 */
class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type,

            // Conditionally include fixed-price fields
            $this->mergeWhen($this->isFixedPrice(), [
                'price' => $this->price,
                'stock_quantity' => $this->stock_quantity,
            ]),

            // Conditionally include auction fields
            $this->mergeWhen($this->isAuction(), [
                'starting_price' => $this->starting_price,
                'current_highest_bid' => $this->current_highest_bid,
                'auction_end_time' => $this->auction_end_time,
            ]),
        ];
    }
}

