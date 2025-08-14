<?php

namespace Codenteq\Iyzico\Http\Controllers;

use Codenteq\Iyzico\Events\WebhookHandled;
use Codenteq\Iyzico\Events\WebhookReceived;
use Codenteq\Iyzico\Http\Middleware\VerifyWebhookSignature;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class WebhookController extends Controller
{
    /**
     * Create a new WebhookController instance.
     */
    /*public function __construct()
    {
        $this->middleware(VerifyWebhookSignature::class);
    }*/

    /**
     * Handle an İyzico webhook call.
     */
    public function handleWebhook(Request $request): SymfonyResponse
    {
        $payload = $request->all();
        $method = $this->eventToMethod($payload['iyziEventType'] ?? '');

        WebhookReceived::dispatch($payload);

        if (method_exists($this, $method)) {
            $response = $this->{$method}($payload);

            WebhookHandled::dispatch($payload);

            return $response;
        }

        return $this->missingMethod($payload);
    }

    /**
     * Handle successful subscription payment.
     */
    protected function handleSubscriptionPaymentSucceeded(array $payload): SymfonyResponse
    {
        if ($user = $this->getUserByPaymentConversationId($payload['paymentConversationId'])) {
            $subscription = $user->subscription();

            if ($subscription) {
                $subscription->update([
                    'iyzico_status' => 'ACTIVE',
                    'ends_at' => null,
                ]);
            }
        }

        return new Response('Webhook Handled', 200);
    }

    /**
     * Handle failed subscription payment.
     */
    protected function handleSubscriptionPaymentFailed(array $payload): SymfonyResponse
    {
        $user = $this->getUserByPaymentConversationId($payload['paymentConversationId']);

        if ($user) {
            $subscription = $user->subscription();

            if ($subscription) {
                $subscription->update([
                    'iyzico_status' => 'UNPAID',
                ]);

                // Log the failed payment
                Log::warning('İyzico subscription payment failed', [
                    'user_id' => $user->id,
                    'payment_conversation_id' => $payload['paymentConversationId'],
                    'iyzico_payment_id' => $payload['iyziPaymentId'] ?? $payload['paymentId'],
                ]);
            }
        }

        return new Response('Webhook Handled', 200);
    }

    /**
     * Handle payment API webhook.
     */
    protected function handlePaymentApi(array $payload): SymfonyResponse
    {
        $status = $payload['status'] ?? '';

        switch ($status) {
            case 'SUCCESS':
                return $this->handleSubscriptionPaymentSucceeded($payload);
            case 'FAILURE':
                return $this->handleSubscriptionPaymentFailed($payload);
            default:
                return new Response('Webhook Handled', 200);
        }
    }

    /**
     * Handle API auth webhook.
     */
    protected function handleApiAuth(array $payload): SymfonyResponse
    {
        return $this->handlePaymentApi($payload);
    }

    /**
     * Handle 3DS auth webhook.
     */
    protected function handleThreeDsAuth(array $payload): SymfonyResponse
    {
        return $this->handlePaymentApi($payload);
    }

    /**
     * Handle 3DS callback webhook.
     */
    protected function handleThreeDsCallback(array $payload): SymfonyResponse
    {
        return $this->handlePaymentApi($payload);
    }

    /**
     * Handle refund retry success webhook.
     */
    protected function handleRefundRetrySuccess(array $payload): SymfonyResponse
    {
        if ($user = $this->getUserByPaymentConversationId($payload['paymentConversationId'])) {
            // Handle successful refund
            Log::info('İyzico refund succeeded', [
                'user_id' => $user->id,
                'payment_conversation_id' => $payload['paymentConversationId'],
                'iyzico_payment_id' => $payload['iyziPaymentId'] ?? $payload['paymentId'],
            ]);
        }

        return new Response('Webhook Handled', 200);
    }

    /**
     * Handle refund retry failure webhook.
     */
    protected function handleRefundRetryFailure(array $payload): SymfonyResponse
    {
        if ($user = $this->getUserByPaymentConversationId($payload['paymentConversationId'])) {
            // Handle failed refund
            Log::warning('İyzico refund failed', [
                'user_id' => $user->id,
                'payment_conversation_id' => $payload['paymentConversationId'],
                'iyzico_payment_id' => $payload['iyziPaymentId'] ?? $payload['paymentId'],
            ]);
        }

        return new Response('Webhook Handled', 200);
    }

    /**
     * Handle a webhook call for a missing method.
     */
    protected function missingMethod(array $payload): SymfonyResponse
    {
        Log::info('İyzico webhook received but no handler found', [
            'event_type' => $payload['iyziEventType'] ?? 'unknown',
            'payload' => $payload,
        ]);

        return new Response('Webhook Handled', 200);
    }

    /**
     * Convert the event type to a method name.
     */
    protected function eventToMethod(string $eventType): string
    {
        return 'handle'.str_replace('_', '', ucwords($eventType, '_'));
    }

    /**
     * Get the billable entity instance by payment conversation ID.
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    protected function getUserByPaymentConversationId(string $paymentConversationId)
    {
        $model = 'App\\Models\\User';

        return $model::where('iyzico_id', $paymentConversationId)->first();
    }

    /**
     * Get amount from payload.
     */
    protected function getAmountFromPayload(array $payload): int
    {
        // This would need to be retrieved from İyzico API
        // as webhook doesn't include amount information
        return 0;
    }
}
