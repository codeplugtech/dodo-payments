<?php

namespace Codeplugtech\DodoPayments\Tests\Unit;

use Codeplugtech\DodoPayments\DodoPayments;

it('it can format amount 1000 into $10.00', function () {
    $this->assertSame('$10', DodoPayments::formatAmount(1000, 'USD'));
});

it('it can format amount 1020 into $10.20', function () {
    $this->assertSame('$10.20', DodoPayments::formatAmount(1020, 'USD'));
});

it('it can format amount Indian Rupee', function () {
    $this->assertSame('₹10.20', DodoPayments::formatAmount(1020, 'INR'));
});
