<?php

namespace Codeplugtech\DodoPayments\Enum;

enum PaymentStatusEnum:string
{
    case PENDING = 'pending';
    case SUCCEEDED = 'succeeded';

    case FAILED = 'failed';
    case CANCELED = 'canceled';
    case PROCESSING = 'processing';

    case PAUSED = 'paused';
}
