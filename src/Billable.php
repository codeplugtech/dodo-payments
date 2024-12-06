<?php

namespace Codeplugtech\DodoPayments;

use Codeplugtech\DodoPayments\Concerns\ManageSubscriptions;
use Codeplugtech\DodoPayments\Concerns\ManageTransactions;

trait Billable
{
    use ManageSubscriptions;
    use ManageTransactions;
}
