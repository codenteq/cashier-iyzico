<?php

namespace Codenteq\Iyzico\Exceptions;

use Exception;

class CashierException extends Exception
{
    /**
     * Create a new Cashier exception instance.
     */
    public function __construct(string $message = '', int $code = 0, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Render the exception as an HTTP response.
     */
    public function render()
    {
        return response()->json([
            'error' => 'Cashier Configuration Error',
            'message' => $this->getMessage(),
        ], 500);
    }

    /**
     * Report the exception.
     */
    public function report(): void
    {
        logger()->error('Cashier Exception: ' . $this->getMessage(), [
            'exception' => $this,
            'trace' => $this->getTraceAsString(),
        ]);
    }
}
