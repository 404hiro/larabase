<?php

use App\Services\Stripe\CreateMessageCheckoutSession;

test('calculates platform fee and creator payout from the support amount', function () {
    $service = new CreateMessageCheckoutSession;

    $result = $service->calculateFees(1500);

    expect($result)->toMatchArray([
        'amount' => 1500,
        'platform_fee' => 150,
        'creator_payout' => 1350,
    ]);
});
