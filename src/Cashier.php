<?php

namespace Codenteq\Iyzico;

use Codenteq\Iyzico\Models\Subscription;
use Codenteq\Iyzico\Exceptions\CashierException;
use Iyzipay\Options;
use InvalidArgumentException;

class Cashier
{
    /**
     * The Cashier library version.
     */
    public const VERSION = '0.1.0-beta';

    /**
     * The Iyzico API version.
     *
     * @var string
     */
    const IYZICO_VERSION = '2.0.59';

    /**
     * The default currency.
     */
    public const DEFAULT_CURRENCY = 'TRY';

    /**
     * The billable model class name.
     */
    public static string $model = 'App\\Models\\User';

    /**
     * The subscription model class name.
     */
    public static string $subscriptionModel = Subscription::class;

    /**
     * The default currency used by Cashier.
     */
    public static string $currency = self::DEFAULT_CURRENCY;

    /**
     * The currency locale.
     */
    public static string $currencyLocale = 'tr_TR';

    /**
     * Indicates if Cashier migrations will be run.
     */
    public static bool $runsMigrations = true;

    /**
     * Indicates if Cashier routes will be registered.
     */
    public static bool $registersRoutes = true;

    /**
     * The custom currency formatter.
     */
    public static $formatCurrencyUsing;

    /**
     * Set the billable model class name.
     *
     * @throws InvalidArgumentException
     */
    public static function useUserModel(string $model): void
    {
        if (!class_exists($model)) {
            throw new InvalidArgumentException("Model class [{$model}] does not exist.");
        }

        static::$model = $model;
    }

    /**
     * Set the subscription model class name.
     *
     * @throws InvalidArgumentException
     */
    public static function useSubscriptionModel(string $model): void
    {
        if (!class_exists($model)) {
            throw new InvalidArgumentException("Subscription model class [{$model}] does not exist.");
        }

        static::$subscriptionModel = $model;
    }

    /**
     * Set the currency to be used when billing users.
     */
    public static function useCurrency(string $currency, ?string $locale = null): void
    {
        static::$currency = strtoupper($currency);

        if ($locale) {
            static::useCurrencyLocale($locale);
        }
    }

    /**
     * Set the currency locale to be used when formatting currency.
     */
    public static function useCurrencyLocale(string $locale): void
    {
        static::$currencyLocale = $locale;
    }

    /**
     * Set the custom currency formatter.
     */
    public static function formatCurrencyUsing(callable $callback): void
    {
        static::$formatCurrencyUsing = $callback;
    }

    /**
     * Configure Cashier to not register its migrations.
     */
    public static function ignoreMigrations(): void
    {
        static::$runsMigrations = false;
    }

    /**
     * Configure Cashier to not register its routes.
     */
    public static function ignoreRoutes(): void
    {
        static::$registersRoutes = false;
    }

    /**
     * Get configured Iyzico API options.
     *
     * @throws CashierException
     */
    public static function iyzicoOptions(): Options
    {
        $apiKey = config('cashier.iyzico.api_key');
        $secretKey = config('cashier.iyzico.secret_key');
        $baseUrl = config('cashier.iyzico.base_url');

        if (!$apiKey || !$secretKey) {
            throw new CashierException('Iyzico API credentials are not configured properly.');
        }

        if (!$baseUrl) {
            throw new CashierException('Iyzico base URL is not configured.');
        }

        $options = new Options();
        $options->setApiKey($apiKey);
        $options->setSecretKey($secretKey);
        $options->setBaseUrl($baseUrl);

        return $options;
    }

    /**
     * Format the given amount into a displayable currency.
     */
    public static function formatAmount(int $amount, ?string $currency = null): string
    {
        if (static::$formatCurrencyUsing) {
            return call_user_func(static::$formatCurrencyUsing, $amount, $currency ?: static::$currency);
        }

        $currency = $currency ?: static::$currency;
        $amount = $amount / 100;

        $formatter = new \NumberFormatter(static::$currencyLocale, \NumberFormatter::CURRENCY);

        return $formatter->formatCurrency($amount, $currency);
    }

    /**
     * Convert amount to cents/kuruş.
     */
    public static function convertToCents(float $amount): int
    {
        return (int) round($amount * 100);
    }

    /**
     * Convert amount from cents/kuruş to decimal.
     */
    public static function convertFromCents(int $amount): float
    {
        return $amount / 100;
    }

    /**
     * Get the billable entity instance by ID.
     */
    public static function findBillable($id)
    {
        return (new static::$model)->find($id);
    }

    /**
     * Get the subscription instance by ID.
     */
    public static function findSubscription($id): ?Subscription
    {
        return (new static::$subscriptionModel)->find($id);
    }

    /**
     * Get supported currencies for Iyzico.
     */
    public static function supportedCurrencies(): array
    {
        return [
            'TRY' => 'Turkish Lira',
            'USD' => 'US Dollar',
            'EUR' => 'Euro',
            'GBP' => 'British Pound',
            'IRR' => 'Iranian Rial',
        ];
    }

    /**
     * Determine if the given currency is supported.
     */
    public static function supportsCurrency(string $currency): bool
    {
        return array_key_exists(strtoupper($currency), static::supportedCurrencies());
    }

    /**
     * Get the current Iyzico environment (sandbox or live).
     */
    public static function environment(): string
    {
        $baseUrl = config('cashier.iyzico.base_url');

        if (str_contains($baseUrl, 'sandbox')) {
            return 'sandbox';
        }

        return 'live';
    }

    /**
     * Determine if Cashier is running in sandbox mode.
     */
    public static function isSandbox(): bool
    {
        return static::environment() === 'sandbox';
    }

    /**
     * Get configuration value with fallback.
     */
    public static function config(string $key, $default = null)
    {
        return config("cashier.{$key}", $default);
    }

    /**
     * Validate required configuration values.
     *
     * @throws CashierException
     */
    public static function validateConfiguration(): void
    {
        $requiredConfigs = [
            'iyzico.api_key' => 'Iyzico API key',
            'iyzico.secret_key' => 'Iyzico secret key',
            'iyzico.base_url' => 'Iyzico base URL',
        ];

        foreach ($requiredConfigs as $config => $name) {
            if (!static::config($config)) {
                throw new CashierException("{$name} is not configured.");
            }
        }

        $currency = static::$currency;
        if (!static::supportsCurrency($currency)) {
            throw new CashierException("Currency [{$currency}] is not supported by Iyzico.");
        }
    }

    /**
     * Get package information.
     */
    public static function packageInfo(): array
    {
        return [
            'name' => 'Codenteq Cashier Iyzico',
            'version' => static::VERSION,
            'iyzico_version' => static::IYZICO_VERSION,
            'environment' => static::environment(),
            'currency' => static::$currency,
            'locale' => static::$currencyLocale,
            'models' => [
                'user' => static::$model,
                'subscription' => static::$subscriptionModel,
            ],
        ];
    }

    /**
     * Generate a unique reference code for transactions.
     */
    public static function generateReferenceCode(string $prefix = 'CASHIER'): string
    {
        return $prefix . '_' . strtoupper(uniqid()) . '_' . time();
    }

    /**
     * Mask sensitive data for logging.
     */
    public static function maskSensitiveData(string $data, int $visibleChars = 4): string
    {
        if (strlen($data) <= $visibleChars) {
            return str_repeat('*', strlen($data));
        }

        return str_repeat('*', strlen($data) - $visibleChars) . substr($data, -$visibleChars);
    }
}
