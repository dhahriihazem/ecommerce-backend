<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBidRequest;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class BidController extends Controller
{
    /**
     * @OA\Post(
     *      path="/api/products/{product}/bids",
     *      operationId="placeBidOnProduct",
     *      tags={"Bidding"},
     *      summary="Place a bid on an auction product",
     *      description="Allows an authenticated user to place a bid on a product of type 'auction'. The bid must be higher than the current highest bid, and the auction must not have ended.",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(
     *          name="product",
     *          description="The ID of the auction product",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="The amount for the bid",
     *          @OA\JsonContent(
     *              required={"bid_amount"},
     *              @OA\Property(property="bid_amount", type="number", format="float", example=99.99, description="Must be higher than the current highest bid.")
     *          )
     *      ),
     *      @OA\Response(
     *          response=201,
     *          description="Bid placed successfully",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Bid placed successfully."),
     *              @OA\Property(property="bid", ref="#/components/schemas/Bid")
     *          )
     *      ),
     *      @OA\Response(response=401, description="Unauthenticated"),
     *      @OA\Response(response=404, description="Product not found"),
     *      @OA\Response(response=422, description="Validation Error (e.g., bid is too low, auction has ended, or product is not an auction item)")
     * )
     * Store a newly created bid in storage.
     *
     * @param  \App\Http\Requests\StoreBidRequest  $request
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreBidRequest $request, Product $product): JsonResponse
    {
        $validated = $request->validated();

        // Use a database transaction to ensure atomicity.
        // If any operation fails, all are rolled back.
        $bid = DB::transaction(function () use ($product, $request, $validated) {
            // Create the new bid record
            $newBid = $product->bids()->create([
                'user_id' => $request->user()->id,
                'bid_amount' => $validated['bid_amount'],
            ]);

            // Update the product's highest bid
            $product->update(['current_highest_bid' => $validated['bid_amount']]);

            return $newBid;
        });

        return response()->json(['message' => 'Bid placed successfully.', 'bid' => $bid], 201);
    }
}