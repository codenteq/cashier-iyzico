<?php

namespace Codenteq\Iyzico\Concerns;

use Carbon\Carbon;
use Codenteq\Iyzico\Cashier;
use Codenteq\Iyzico\Models\Subscription;
use Codenteq\Iyzico\Services\SubscriptionBuilder;
use Codenteq\Iyzico\Exceptions\SubscriptionException;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Collection;

trait ManagesSubscriptions
{
    /**
     * Begin creating a new subscription.
     */
    public function newSubscription(string $name, string $plan): SubscriptionBuilder
    {
        return SubscriptionBuilder::make($this, $name, $plan);
    }

    /**
     * Get all of the subscriptions for the billable model.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Cashier::$subscriptionModel)->latest();
    }

    /**
     * Get active subscriptions for the billable model.
     */
    public function activeSubscriptions(): HasMany
    {
        return $this->subscriptions()->where(function ($query) {
            $query->whereNull('ends_at')
                ->orWhere('ends_at', '>', now());
        });
    }

    /**
     * Get a subscription instance by name.
     */
    public function subscription(string $name = 'default'): ?Subscription
    {
        return $this->subscriptions()
            ->where('name', $name)
            ->first();
    }

    /**
     * Get all subscriptions by name.
     */
    public function subscriptionsByName(string $name): Collection
    {
        return $this->subscriptions()
            ->where('name', $name)
            ->get();
    }

    /**
     * Determine if the billable model is actively subscribed to one of the given plans.
     */
    public function subscribed(string $name = 'default', ?string $plan = null): bool
    {
        $subscription = $this->subscription($name);

        if (!$subscription || !$subscription->valid()) {
            return false;
        }

        return $plan ? $subscription->hasPlan($plan) : true;
    }

    /**
     * Determine if the billable model is subscribed to any of the given plans.
     */
    public function subscribedToAnyPlan(array $plans, string $name = 'default'): bool
    {
        $subscription = $this->subscription($name);

        if (!$subscription || !$subscription->valid()) {
            return false;
        }

        foreach ($plans as $plan) {
            if ($subscription->hasPlan($plan)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if the billable model is on trial.
     */
    public function onTrial(string $name = 'default', ?string $plan = null): bool
    {
        if (func_num_args() === 0 && $this->onGenericTrial()) {
            return true;
        }

        $subscription = $this->subscription($name);

        if (!$subscription || !$subscription->onTrial()) {
            return false;
        }

        return !$plan || $subscription->hasPlan($plan);
    }

    /**
     * Determine if the billable model has a generic trial applied.
     */
    public function onGenericTrial(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    /**
     * Get the ending date of the trial.
     */
    public function trialEndsAt(string $name = 'default'): ?Carbon
    {
        if ($name === 'default' && $this->onGenericTrial()) {
            return $this->trial_ends_at;
        }

        $subscription = $this->subscription($name);

        return $subscription?->trial_ends_at;
    }

    /**
     * Cancel a subscription by name.
     */
    public function cancelSubscription(string $name = 'default'): bool
    {
        $subscription = $this->subscription($name);

        if ($subscription && $subscription->valid()) {
            $subscription->cancel();
            return true;
        }

        return false;
    }

    /**
     * Cancel all active subscriptions.
     */
    public function cancelAllSubscriptions(): int
    {
        $cancelledCount = 0;

        foreach ($this->activeSubscriptions as $subscription) {
            try {
                $subscription->cancel();
                $cancelledCount++;
            } catch (SubscriptionException $e) {
                // Log error but continue with other subscriptions
                logger()->error('Failed to cancel subscription', [
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $cancelledCount;
    }

    /**
     * Resume a subscription by name.
     */
    public function resumeSubscription(string $name = 'default'): bool
    {
        $subscription = $this->subscription($name);

        if ($subscription && $subscription->cancelled()) {
            $subscription->resume();
            return true;
        }

        return false;
    }

    /**
     * Swap the subscription to a new plan.
     */
    public function swapSubscriptionPlan(string $newPlan, string $name = 'default'): bool
    {
        $subscription = $this->subscription($name);

        if ($subscription && $subscription->valid()) {
            return $subscription->upgrade($newPlan);
        }

        return false;
    }

    /**
     * Determine if the billable model has any active subscription.
     */
    public function hasActiveSubscription(): bool
    {
        return $this->activeSubscriptions()->exists();
    }

    /**
     * Get the most recent subscription.
     */
    public function latestSubscription(): ?Subscription
    {
        return $this->subscriptions()->first();
    }

    /**
     * Find subscription by Iyzico ID.
     */
    public function findSubscriptionByIyzicoId(string $iyzicoId): ?Subscription
    {
        return $this->subscriptions()
            ->where('iyzico_id', $iyzicoId)
            ->first();
    }

    /**
     * Get subscriptions that are currently on trial.
     */
    public function trialSubscriptions(): Collection
    {
        return $this->subscriptions()
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '>', now())
            ->get();
    }

    /**
     * Get subscriptions that are cancelled but still in grace period.
     */
    public function gracePeriodSubscriptions(): Collection
    {
        return $this->subscriptions()
            ->whereNotNull('ends_at')
            ->where('ends_at', '>', now())
            ->get();
    }

    /**
     * Get all valid subscriptions (active, trial, or grace period).
     */
    public function validSubscriptions(): Collection
    {
        return $this->subscriptions()
            ->where(function ($query) {
                $query->where(function ($q) {
                    // Active subscriptions
                    $q->whereNull('ends_at')
                        ->orWhere('ends_at', '>', now());
                })->orWhere(function ($q) {
                    // Trial subscriptions
                    $q->whereNotNull('trial_ends_at')
                        ->where('trial_ends_at', '>', now());
                });
            })
            ->get();
    }

    /**
     * Determine if the billable model has ever subscribed to the given plan.
     */
    public function hasEverSubscribedTo(string $plan): bool
    {
        return $this->subscriptions()
            ->where('iyzico_plan', $plan)
            ->exists();
    }
}
