<?php

namespace Codenteq\Iyzico\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class VerifyWebhookSignature
{
    /**
     * Handle the incoming request.
     *
     *
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $signature = $request->header('X-IYZ-SIGNATURE-V3');

        if (! $signature) {
            throw new AccessDeniedHttpException('Missing webhook signature.');
        }

        if (! $this->verify($request, $signature)) {
            throw new AccessDeniedHttpException('Invalid webhook signature.');
        }

        return $next($request);
    }

    /**
     * Verify the webhook signature.
     */
    protected function verify(Request $request, string $signature): bool
    {
        $payload = $request->all();
        $secretKey = config('cashier.iyzico.secret_key');

        if (isset($payload['token'])) {
            return $this->verifyHppFormat($payload, $secretKey, $signature);
        }

        return $this->verifyDirectFormat($payload, $secretKey, $signature);
    }

    /**
     * Verify Direct Format webhook signature.
     */
    protected function verifyDirectFormat(array $payload, string $secretKey, string $signature): bool
    {
        $iyziEventType = $payload['iyziEventType'] ?? '';
        $paymentId = $payload['paymentId'] ?? '';
        $paymentConversationId = $payload['paymentConversationId'] ?? '';
        $status = $payload['status'] ?? '';

        $key = $secretKey.$iyziEventType.$paymentId.$paymentConversationId.$status;

        $expectedSignature = hash_hmac('sha256', $key, $secretKey);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Verify HPP Format webhook signature.
     */
    protected function verifyHppFormat(array $payload, string $secretKey, string $signature): bool
    {
        $iyziEventType = $payload['iyziEventType'] ?? '';
        $iyziPaymentId = $payload['iyziPaymentId'] ?? '';
        $token = $payload['token'] ?? '';
        $paymentConversationId = $payload['paymentConversationId'] ?? '';
        $status = $payload['status'] ?? '';

        $key = $secretKey.$iyziEventType.$iyziPaymentId.$token.$paymentConversationId.$status;

        $expectedSignature = hash_hmac('sha256', $key, $secretKey);

        return hash_equals($expectedSignature, $signature);
    }
}
