<?php

namespace Codeplugtech\DodoPayments\Enum;

enum SubscriptionStatusEnum:string
{
    case ACTIVE = 'active';
    case PENDING = 'pending';
    case CANCELLED = 'cancelled';
    case FAILED = 'failed';
    case PAUSED = 'paused';
    case ON_HOLD = 'on_hold';
    case EXPIRED = 'expired';
}
