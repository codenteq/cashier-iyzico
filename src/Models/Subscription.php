<?php

namespace Codenteq\Iyzico\Models;

use Carbon\Carbon;
use Codenteq\Iyzico\Cashier;
use Codenteq\Iyzico\Enums\SubscriptionStatusEnum;
use Codenteq\Iyzico\Enums\UpgradePeriodEnum;
use Codenteq\Iyzico\Services\SubscriptionService;
use Codenteq\Iyzico\Exceptions\SubscriptionException;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    private SubscriptionService $subscriptionService;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->subscriptionService = new SubscriptionService();
    }

    /**
     * İlişkiler
     */
    public function user(): BelongsTo
    {
        return $this->owner();
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Cashier::$model, 'user_id');
    }

    /**
     * Durum kontrolleri
     */
    public function valid(): bool
    {
        return $this->active() || $this->onTrial() || $this->onGracePeriod();
    }

    public function active(): bool
    {
        return $this->isNotExpired()
            && $this->isTrialValid()
            && $this->isIyzicoStatusActive();
    }

    public function cancelled(): bool
    {
        return !is_null($this->ends_at);
    }

    public function onTrial(): bool
    {
        return $this->trial_ends_at?->isFuture() ?? false;
    }

    public function onGracePeriod(): bool
    {
        return $this->ends_at?->isFuture() ?? false;
    }

    public function hasPlan(string $plan): bool
    {
        return $this->iyzico_plan === $plan;
    }

    /**
     * Aksiyon metodları
     */
    public function cancel(): self
    {
        try {
            $nextPaymentDate = $this->getNextPaymentDate();

            $this->subscriptionService->cancel($this->iyzico_id);

            $this->update([
                'iyzico_status' => SubscriptionStatusEnum::CANCELED->value,
                'ends_at' => $this->calculateEndDate($nextPaymentDate)
            ]);

            return $this;
        } catch (\Exception $e) {
            throw new SubscriptionException("Subscription cancel failed: " . $e->getMessage());
        }
    }

    public function resume(): self
    {
        $this->update(['ends_at' => null]);
        return $this;
    }

    public function retry(): bool
    {
        return $this->executeIyzicoAction(
            fn() => $this->subscriptionService->retry($this->iyzico_id)
        );
    }

    public function activate(): bool
    {
        return $this->executeIyzicoAction(
            fn() => $this->subscriptionService->activate($this->iyzico_id)
        );
    }

    public function upgrade(bool $resetRecurrenceCount, bool $useTrial, string $newPricingPlanReferenceCode, UpgradePeriodEnum $upgradePeriod): bool
    {
        return $this->executeIyzicoAction(
            fn() => $this->subscriptionService->upgrade($this->iyzico_id, $resetRecurrenceCount, $useTrial, $newPricingPlanReferenceCode, $upgradePeriod)
        );
    }

    public function detail()
    {
        try {
            $response = $this->subscriptionService->detail($this->iyzico_id);

            if ($response->getStatus() !== 'success') {
                throw new SubscriptionException('Subscription detail fetch failed');
            }

            return $response;
        } catch (\Exception $e) {
            throw new SubscriptionException("Detail fetch failed: " . $e->getMessage());
        }
    }

    /**
     * Yardımcı metodlar (Private)
     */
    private function isNotExpired(): bool
    {
        return is_null($this->ends_at) || $this->onGracePeriod();
    }

    private function isTrialValid(): bool
    {
        return !$this->onTrial() || $this->trial_ends_at->isFuture();
    }

    private function isIyzicoStatusActive(): bool
    {
        return $this->iyzico_status === SubscriptionStatusEnum::ACTIVE->value;
    }

    private function getNextPaymentDate(): int
    {
        $detail = $this->detail();
        return $detail->getOrders()[0]->startPeriod ?? now()->timestamp * 1000;
    }

    private function calculateEndDate(int $nextPaymentPeriod): Carbon
    {
        if ($this->onTrial()) {
            return $this->trial_ends_at;
        }

        return Carbon::createFromTimestampMs($nextPaymentPeriod, 'UTC')->startOfDay();
    }

    private function executeIyzicoAction(callable $action): bool
    {
        try {
            $response = $action();

            if ($response->getStatus() === 'success') {
                $this->update(['iyzico_status' => SubscriptionStatusEnum::ACTIVE->value]);
                return true;
            }

            return false;
        } catch (\Exception $e) {
            throw new SubscriptionException("Iyzico action failed: " . $e->getMessage());
        }
    }

    /**
     * Scope'lar
     */
    public function scopeActive($query)
    {
        return $query->where('iyzico_status', SubscriptionStatusEnum::ACTIVE->value);
    }

    public function scopeOnTrial($query)
    {
        return $query->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '>', now());
    }

    public function scopeCancelled($query)
    {
        return $query->whereNotNull('ends_at');
    }

    public function scopeForPlan($query, string $plan)
    {
        return $query->where('iyzico_plan', $plan);
    }
}
