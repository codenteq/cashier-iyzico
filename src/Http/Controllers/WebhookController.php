<?php

namespace Codenteq\Iyzico\Http\Controllers;

use Codenteq\Iyzico\Events\WebhookHandled;
use Codenteq\Iyzico\Events\WebhookReceived;
use Codenteq\Iyzico\Http\Middleware\VerifyWebhookSignature;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class WebhookController extends Controller
{
    /**
     * Create a new WebhookController instance.
     */
    public function __construct()
    {
        $this->middleware(VerifyWebhookSignature::class);
    }

    /**
     * Handle an İyzico webhook call.
     */
    public function handleWebhook(Request $request): Response
    {
        $payload = $request->all();
        $method = 'handle'.str_replace('_', '', ucwords($request->iyziEventType, '_'));

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
    protected function handleSubscriptionOrderSuccess(array $payload): Response
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
    protected function handleSubscriptionOrderFailure(array $payload): Response
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
     * Get the billable entity instance by payment conversation ID.
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    protected function getUserByPaymentConversationId(string $paymentConversationId): ?\Illuminate\Database\Eloquent\Model
    {
        $model = 'App\\Models\\User';

        return $model::where('iyzico_id', $paymentConversationId)->first();
    }

}
