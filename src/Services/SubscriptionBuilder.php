<?php

namespace Codenteq\Iyzico\Services;

use Carbon\Carbon;
use Codenteq\Iyzico\Models\Subscription;
use Codenteq\Iyzico\Exceptions\SubscriptionException;
use Illuminate\Database\Eloquent\Model;
use Iyzipay\Model\Subscription\SubscriptionCreate;

class SubscriptionBuilder
{
    private Model $owner;
    private string $name;
    private string $plan;
    private int $trialDays = 0;
    private bool $skipTrial = false;
    private array $metadata = [];
    private SubscriptionService $subscriptionService;

    /**
     * Create a new subscription builder instance.
     */
    public function __construct(Model $owner, string $name, string $plan)
    {
        $this->owner = $owner;
        $this->name = $name;
        $this->plan = $plan;
        $this->subscriptionService = new SubscriptionService();
    }

    /**
     * Set the trial period in days.
     */
    public function trialDays(int $trialDays): self
    {
        $this->validateTrialDays($trialDays);
        $this->trialDays = $trialDays;

        return $this;
    }

    /**
     * Skip the trial period.
     */
    public function skipTrial(): self
    {
        $this->skipTrial = true;

        return $this;
    }

    /**
     * Set metadata for the subscription.
     */
    public function withMetadata(array $metadata): self
    {
        $this->metadata = array_merge($this->metadata, $metadata);

        return $this;
    }

    /**
     * Add single metadata item.
     */
    public function addMetadata(string $key, mixed $value): self
    {
        $this->metadata[$key] = $value;

        return $this;
    }

    /**
     * Create the subscription.
     *
     * @throws SubscriptionException
     */
    public function create(array $data = []): Subscription
    {
        $this->validateCreateData($data);

        try {
            $response = $this->subscriptionService->create($data);

            if ($response->getStatus() === 'failure') {
                throw new SubscriptionException(
                    'Subscription creation failed: ' . $response->getRawResult()
                );
            }

            return $this->createSubscriptionRecord($response, $data);

        } catch (\Exception $e) {
            if ($e instanceof SubscriptionException) {
                throw $e;
            }

            throw new SubscriptionException(
                'Subscription creation error: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Create subscription with immediate activation.
     */
    public function createAndActivate(array $data = []): Subscription
    {
        $subscription = $this->create($data);

        if (!$subscription->active()) {
            $subscription->activate();
        }

        return $subscription;
    }

    /**
     * Preview subscription details without creating.
     */
    public function preview(): array
    {
        return [
            'owner_id' => $this->owner->getKey(),
            'name' => $this->name,
            'plan' => $this->plan,
            'trial_days' => $this->trialDays,
            'skip_trial' => $this->skipTrial,
            'trial_ends_at' => $this->getTrialExpiration(),
            'metadata' => $this->metadata,
        ];
    }

    /**
     * Validate trial days input.
     *
     * @throws SubscriptionException
     */
    private function validateTrialDays(int $trialDays): void
    {
        if ($trialDays < 0) {
            throw new SubscriptionException('Trial days cannot be negative');
        }

        if ($trialDays > 365) {
            throw new SubscriptionException('Trial days cannot exceed 365 days');
        }
    }

    /**
     * Validate create data.
     *
     * @throws SubscriptionException
     */
    private function validateCreateData(array $data): void
    {
        $requiredFields = ['price'];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                throw new SubscriptionException("Required field '{$field}' is missing");
            }
        }

        if (isset($data['price']) && (!is_numeric($data['price']) || $data['price'] <= 0)) {
            throw new SubscriptionException('Price must be a positive number');
        }

        // Check if user already has an active subscription with the same name
        if ($this->hasActiveSubscription()) {
            throw new SubscriptionException(
                "User already has an active subscription with name '{$this->name}'"
            );
        }
    }

    /**
     * Check if user has active subscription with same name.
     */
    private function hasActiveSubscription(): bool
    {
        return $this->owner->subscriptions()
            ->where('name', $this->name)
            ->where(function ($query) {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>', now());
            })
            ->exists();
    }

    /**
     * Create subscription database record.
     */
    private function createSubscriptionRecord(SubscriptionCreate $response, array $data): Subscription
    {
        $subscriptionData = [
            'name' => $this->name,
            'iyzico_id' => $response->getReferenceCode(),
            'iyzico_status' => $response->getSubscriptionStatus(),
            'iyzico_plan' => $this->plan,
            'iyzico_price' => $data['price'],
            'trial_ends_at' => $this->getTrialExpiration(),
            'ends_at' => null,
        ];

        // Add metadata if provided
        if (!empty($this->metadata)) {
            $subscriptionData['metadata'] = json_encode($this->metadata);
        }

        $this->owner->iyzico_id = $subscriptionData['iyzico_id'];
        $this->owner->pm_last_four = "0000";
        $this->owner->trial_ends_at = $subscriptionData['trial_ends_at'];

        $this->owner->save();

        return $this->owner->subscriptions()->create($subscriptionData);
    }

    /**
     * Calculate the trial expiration date.
     */
    private function getTrialExpiration(): ?Carbon
    {
        if ($this->skipTrial || $this->trialDays === 0) {
            return null;
        }

        return now()->addDays($this->trialDays);
    }

    /**
     * Static factory method for cleaner instantiation.
     */
    public static function make(Model $owner, string $name, string $plan): self
    {
        return new static($owner, $name, $plan);
    }

    /**
     * Get current configuration as array.
     */
    public function toArray(): array
    {
        return [
            'owner_type' => get_class($this->owner),
            'owner_id' => $this->owner->getKey(),
            'name' => $this->name,
            'plan' => $this->plan,
            'trial_days' => $this->trialDays,
            'skip_trial' => $this->skipTrial,
            'metadata' => $this->metadata,
        ];
    }
}
