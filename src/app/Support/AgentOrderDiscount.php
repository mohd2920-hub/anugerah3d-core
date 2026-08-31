<?php

namespace App\Support;

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
        if ($subtotalCents < self::BELOW_RM20_THRESHOLD_CENTS) {
            return self::BELOW_RM20_PERCENTAGE;
        }

        if ($subtotalCents < self::BELOW_RM100_THRESHOLD_CENTS) {
            return self::BELOW_RM100_PERCENTAGE;
        }

        return max(self::AT_LEAST_RM100_PERCENTAGE, max(0, (float) $agentDiscountPercentage));
    }

    public static function frontendConfig(?float $agentDiscountPercentage = null): array
    {
        return [
            'belowRm20ThresholdCents' => self::BELOW_RM20_THRESHOLD_CENTS,
            'belowRm100ThresholdCents' => self::BELOW_RM100_THRESHOLD_CENTS,
            'belowRm20Percentage' => self::BELOW_RM20_PERCENTAGE,
            'belowRm100Percentage' => self::BELOW_RM100_PERCENTAGE,
            'aboveRm100Percentage' => max(self::AT_LEAST_RM100_PERCENTAGE, max(0, (float) $agentDiscountPercentage)),
            'deliveryFeeCents' => self::DELIVERY_FEE_CENTS,
        ];
    }
}
