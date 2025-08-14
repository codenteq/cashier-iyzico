<?php

namespace Codenteq\Iyzico\Exceptions;

use Exception;

class SubscriptionException extends Exception
{
    public function __construct(string $message = '', int $code = 0, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'error' => 'Subscription Error',
            'message' => $this->getMessage(),
        ], 400);
    }
}
