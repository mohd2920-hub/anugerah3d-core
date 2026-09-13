<?php

namespace App\Support;

use App\Models\AgentDiscountSetting;

class AgentOrderDiscount
{
    public const BELOW_RM20_THRESHOLD_CENTS = 2000;

    public const BELOW_RM100_THRESHOLD_CENTS = 10000;

    public const BELOW_RM20_PERCENTAGE = 10.0;

    public const BELOW_RM100_PERCENTAGE = 25.0;

    public const AT_LEAST_RM100_PERCENTAGE = 25.0;

    public const DELIVERY_FEE_CENTS = 300;

    public static function resolvePercentage(int $subtotalCents, ?float $agentDiscountPercentage = null): float
    {
        $setting = AgentDiscountSetting::current();

        if ($subtotalCents < self::BELOW_RM20_THRESHOLD_CENTS) {
            return $setting->below_rm20;
        }

        if ($subtotalCents < self::BELOW_RM100_THRESHOLD_CENTS) {
            return $setting->below_rm100;
        }

        return max($setting->at_least_rm100, max(0, (float) $agentDiscountPercentage));
    }

    public static function frontendConfig(?float $agentDiscountPercentage = null): array
    {
        $setting = AgentDiscountSetting::current();

        return [
            'belowRm20ThresholdCents' => self::BELOW_RM20_THRESHOLD_CENTS,
            'belowRm100ThresholdCents' => self::BELOW_RM100_THRESHOLD_CENTS,
            'belowRm20Percentage' => $setting->below_rm20,
            'belowRm100Percentage' => $setting->below_rm100,
            'aboveRm100Percentage' => max($setting->at_least_rm100, max(0, (float) $agentDiscountPercentage)),
            'deliveryFeeCents' => self::DELIVERY_FEE_CENTS,
        ];
    }
}
