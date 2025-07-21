<?php

namespace App\Http\Controllers\Api;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Models\Order;
use App\Models\Product;
use App\Services\MyFatoorahService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Tag(
 *     name="Orders",
 *     description="Order processing operations"
 * )
 *
 * @OA\Schema(
 *     schema="OrderItem",
 *     required={"product_id", "quantity"},
 *     @OA\Property(property="product_id", type="integer", example=1),
 *     @OA\Property(property="quantity", type="integer", example=2)
 * )
 *
 * @OA\Schema(
 *     schema="CreateOrder",
 *     @OA\Property(
 *         property="items",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/OrderItem")
 *     ),
 *     @OA\Property(
 *         property="idempotency_key",
 *         type="string",
 *         example="unique-key-123",
 *         description="Optional client-generated idempotency key"
 *     )
 * )
 */
class OrderController extends Controller
{
    public function __construct(private MyFatoorahService $myFatoorahService)
    {
    }

    /**
     * @OA\Post(
     *     path="/api/orders",
     *     operationId="createOrder",
     *     tags={"Orders"},
     *     summary="Create a new order with multiple products",
     *     description="Creates an order with one or more fixed-price products and initiates payment via MyFatoorah. Supports idempotency to prevent duplicate orders.",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Order payload",
     *         @OA\JsonContent(ref="#/components/schemas/CreateOrder")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Order created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Order created successfully. Please proceed to payment."),
     *             @OA\Property(property="order_id", type="integer", example=456),
     *             @OA\Property(property="payment_url", type="string", example="https://demo.myfatoorah.com/payment/pay?invoiceId=inv_789")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Existing order retrieved (idempotency)",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Order already exists. Please proceed to payment."),
     *             @OA\Property(property="order_id", type="integer", example=456),
     *             @OA\Property(property="payment_url", type="string", example="https://demo.myfatoorah.com/payment/pay?invoiceId=inv_789")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation or business rule failure",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Not enough stock available for one or more products.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error when creating order or initiating payment",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Failed to create order. Please try again.")
     *         )
     *     )
     * )
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $items = $validated['items'];
        $idempotencyKey = $validated['idempotency_key'] ?? null;
        $user = $request->user();
 
        // Check for an existing order with the same idempotency key
        if ($idempotencyKey) {
            $existingOrder = Order::where('idempotency_key', $idempotencyKey)->first();
            if ($existingOrder && $existingOrder->payment_id && $existingOrder->status === OrderStatus::PendingPayment) {
                // Assuming the payment URL can be reconstructed or is long-lived.
                // A real implementation might need to query the payment gateway for a new URL.
                $paymentUrl = 'https://demo.myfatoorah.com/payment/pay?invoiceId=' . $existingOrder->payment_id;
                return response()->json([
                    'message' => 'Order already exists. Please proceed to payment.',
                    'order_id' => $existingOrder->id,
                    'payment_url' => $paymentUrl,
                ], 200);
            }
        }
 
        $productIds = array_column($items, 'product_id');
        // Eager load products to avoid N+1 query problem.
        // keyBy('id') makes it easy to access products by their ID.
        $products = Product::whereIn('id', $productIds)->get()->keyBy('id');
 
        // Verify that all requested products were found in the database.
        if (count($products) !== count(array_unique($productIds))) {
            return response()->json(['message' => 'One or more products could not be found.'], 404);
        }
 
        $totalAmount = 0;
        $productsToAttach = [];
 
        // Single loop for validation and calculation.
        foreach ($items as $item) {
            $product = $products->get($item['product_id']);
            $quantity = $item['quantity'];
 
            if (!$product->isFixedPrice()) {
                return response()->json(['message' => "Product '{$product->name}' is not a fixed-price item and cannot be purchased this way."], 422);
            }
 
            if ($product->stock_quantity < $quantity) {
                return response()->json(['message' => "Not enough stock for product '{$product->name}'. Requested: {$quantity}, Available: {$product->stock_quantity}."], 422);
            }
 
            $totalAmount += $product->price * $quantity;
            $productsToAttach[$product->id] = [
                'quantity' => $quantity,
                'price' => $product->price,
            ];
        }
 
        $order = null;
        try {
            $order = DB::transaction(function () use ($user, $totalAmount, $productsToAttach, $idempotencyKey) {
                $order = Order::create([
                    'user_id' => $user->id,
                    'total_amount' => $totalAmount,
                    'status' => OrderStatus::PendingPayment,
                    'idempotency_key' => $idempotencyKey,
                ]);
 
                // Attach all products and their details in a single operation.
                $order->products()->attach($productsToAttach);
 
                return $order;
            });
        } catch (\Throwable $e) {
            Log::error('Order creation failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['message' => 'Failed to create order. Please try again.'], 500);
        }
 
        try {
            $paymentResponse = $this->myFatoorahService->initiatePayment($order, $totalAmount, 'SAR');
            $order->update([
                'payment_gateway' => 'myfatoorah',
                'payment_id' => $paymentResponse['invoice_id'],
            ]);
            return response()->json([
                'message' => 'Order created successfully. Please proceed to payment.',
                'order_id' => $order->id,
                'payment_url' => $paymentResponse['payment_url'],
            ], 201);
        } catch (\Throwable $e) {
            Log::error('Payment initiation failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Could not initiate payment. Please contact support.'], 500);
        }
    }
}
