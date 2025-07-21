<?php

namespace App\Http\Controllers\Api;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\MyFatoorahService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Tag(
 *     name="Payment Callbacks",
 *     description="Endpoints for handling payment gateway callbacks. These are not intended for direct use by API clients."
 * )
 */
class PaymentController extends Controller
{
    /**
     * @param MyFatoorahService $myFatoorahService
     */
    public function __construct(private MyFatoorahService $myFatoorahService)
    {
    }

    /**
     * @OA\Get(
     *     path="/payment/callback/{order}",
     *     operationId="handlePaymentCallback",
     *     tags={"Payment Callbacks"},
     *     summary="Handle successful payment callback",
     *     description="This is the callback URL for the MyFatoorah payment gateway to notify the system of a successful payment. It verifies the payment status and updates the order accordingly.",
     *     @OA\Parameter(
     *         name="order",
     *         in="path",
     *         required=true,
     *         description="The ID of the order being paid.",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="paymentId",
     *         in="query",
     *         required=true,
     *         description="The payment ID provided by MyFatoorah.",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Payment successful. Your order has been confirmed."),
     *             @OA\Property(property="order_id", type="integer", example=123)
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid callback (e.g., missing paymentId)",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Invalid payment callback."))
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Payment verification failed (e.g., payment not 'Paid')",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Payment was not successful."))
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error during verification",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="An unexpected error occurred during payment verification. Please contact support."))
     *     )
     * )
     *
     * Handle the payment callback from MyFatoorah.
     *
     * @param Request $request
     * @param Order $order
     * @return JsonResponse
     */
    public function callback(Request $request, Order $order): JsonResponse
    {
        $paymentId = $request->query('paymentId');

        if (!$paymentId) {
            Log::warning('Payment callback received without paymentId.', ['order_id' => $order->id]);
            return response()->json(['message' => 'Invalid payment callback.'], 400);
        }

        try {
            $paymentData = $this->myFatoorahService->verifyPayment($paymentId);

            // Check the payment status from MyFatoorah
            if (isset($paymentData['Data']['InvoiceStatus']) && $paymentData['Data']['InvoiceStatus'] === 'Paid') {
                // Payment is successful
                $order->update([
                    'status' => OrderStatus::Paid,
                    'transaction_id' => $paymentData['Data']['InvoiceId'] ?? null,
                ]);

                Log::info('Payment successful for order.', ['order_id' => $order->id, 'payment_id' => $paymentId]);

                // In a real web application, you would redirect to a success page.
                // For this API-centric app, we return a JSON response.
                return response()->json([
                    'message' => 'Payment successful. Your order has been confirmed.',
                    'order_id' => $order->id,
                ]);
            }

            // Payment failed or has another status
            $order->update(['status' => OrderStatus::Failed]);
            $errorMessage = $paymentData['Message'] ?? 'Payment was not successful.';
            Log::error('Payment verification failed for order.', [
                'order_id' => $order->id,
                'payment_id' => $paymentId,
                'response' => $paymentData
            ]);

            return response()->json(['message' => $errorMessage, 'order_id' => $order->id], 422);
        } catch (\Throwable $e) {
            Log::critical('Error during payment verification.', ['order_id' => $order->id, 'payment_id' => $paymentId, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'An unexpected error occurred during payment verification. Please contact support.'], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/payment/error/{order}",
     *     operationId="handlePaymentError",
     *     tags={"Payment Callbacks"},
     *     summary="Handle failed or cancelled payment callback",
     *     description="This is the error URL for the MyFatoorah payment gateway. It is called when a payment fails, is cancelled by the user, or encounters an error.",
     *     @OA\Parameter(
     *         name="order",
     *         in="path",
     *         required=true,
     *         description="The ID of the order that failed.",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="paymentId",
     *         in="query",
     *         required=false,
     *         description="The payment ID provided by MyFatoorah, if available.",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Payment failed or was cancelled",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Payment was cancelled or failed. Please try again."))
     *     )
     * )
     * Handle the payment error URL from MyFatoorah.
     *
     * @param Request $request
     * @param Order $order
     * @return JsonResponse
     */
    public function handleError(Request $request, Order $order): JsonResponse
    {
        $order->update(['status' => OrderStatus::Failed]);
        Log::error('Payment failed or was cancelled by user.', ['order_id' => $order->id, 'paymentId' => $request->query('paymentId')]);
        return response()->json(['message' => 'Payment was cancelled or failed. Please try again.'], 400);
    }
}