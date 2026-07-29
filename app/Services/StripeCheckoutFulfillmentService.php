<?php

namespace App\Services;

use App\Support\StripeSessionData;

class StripeCheckoutFulfillmentService
{
    public function __construct(
        protected PointPurchasePaymentService $pointPurchases,
        protected AdPromotionPaymentService $adPromotions,
        protected IdentityVerificationPaymentService $identityVerifications,
        protected ServiceOrderWorkflowService $serviceOrders,
        protected ProviderSubscriptionService $subscriptions,
    ) {}

    public function fulfill(object|array $session): string
    {
        $metadata = StripeSessionData::metadata($session);
        $type = (string) ($metadata['type'] ?? '');

        if ($type === 'service_order') {
            return $this->serviceOrders->markPaidFromCheckoutSession($session) ? 'processed' : 'ignored';
        }

        if (in_array($type, ProviderSubscriptionService::CHECKOUT_TYPES, true)) {
            $this->subscriptions->completeCheckoutSession($session);

            return 'processed';
        }

        if ($type === 'points' || isset($metadata['product_key'])) {
            return $this->pointPurchases->fulfill($session)['status'];
        }

        if (in_array($type, ['ad_boost', 'refresh', 'urgent'], true) || isset($metadata['package'])) {
            return $this->adPromotions->fulfill($session)['status'];
        }

        if ($type === 'identity_verification'
            || in_array($type, ['profile_verification', 'service_provider'], true)
            || isset($metadata['verification_id'])) {
            return $this->identityVerifications->fulfill($session)['status'];
        }

        return 'ignored';
    }
}
