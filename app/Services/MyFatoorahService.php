<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MyFatoorahService
{
    protected string $baseUrl;
    protected string $apiKey;

    public function __construct()
    {
        $this->baseUrl = config('services.myfatoorah.base_url', 'https://apitest.myfatoorah.com');
        $this->apiKey = config('services.myfatoorah.api_key');
    }

    /**
     * Initiates a payment session with MyFatoorah.
     *
     * @param Order $order
     * @param float $totalAmount
     * @param string $currency
     * @return array{payment_url: string, invoice_id: string}
     * @throws \Exception
     */
    public function initiatePayment(Order $order, float $totalAmount, string $currency = 'KWD'): array
    {
        // Verify amount matches order total (using float comparison instead of bccomp)
        if (abs($order->fresh()->total_amount - $totalAmount) > 0.01) {
            throw new \RuntimeException('Order amount mismatch. Payment initiation aborted.');
        }

        $user = $order->user;

        $payload = [
            'CustomerName' => $user->name,
            'CustomerEmail' => $user->email,
            'CustomerMobile' => $user->phone ?? '0000000000',
            'InvoiceValue' => $totalAmount,
            'DisplayCurrencyIso' => $currency,
            'CallBackUrl' => route('payment.callback', ['order' => $order->id]),
            'ErrorUrl' => route('payment.error', ['order' => $order->id]),
            'Language' => 'en',
            'CustomerReference' => $order->id,
            'NotificationOption' => 'EML', // Required by MyFatoorah to send payment link via email
            'UserDefinedField' => $order->id, // Additional reference
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json'
            ])->post($this->baseUrl . '/v2/SendPayment', $payload);

            $data = $response->json();

            if (!$response->successful() || !($data['IsSuccess'] ?? false) || !isset($data['Data']['InvoiceURL'])) {
                Log::error('MyFatoorah API Error', [
                    'order_id' => $order->id,
                    'response' => $data,
                    'status' => $response->status()
                ]);
                throw new \RuntimeException($data['Message'] ?? 'Failed to get payment URL from MyFatoorah.');
            }

            return [
                'payment_url' => $data['Data']['InvoiceURL'],
                'invoice_id' => $data['Data']['InvoiceId'],
            ];

        } catch (\Throwable $e) {
            Log::error('MyFatoorah Service Error', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            // Chain the original exception for better debugging
            throw new \RuntimeException('Payment service unavailable. Please try again later.', 0, $e);
        }
    }

    /**
     * Verify payment status
     */
    public function verifyPayment(string $paymentId): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json'
            ])->post($this->baseUrl . '/v2/getPaymentStatus', [
                'Key' => $paymentId,
                'KeyType' => 'PaymentId'
            ]);

            return $response->json();

        } catch (\Throwable $e) {
            Log::error('MyFatoorah Verification Failed', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}