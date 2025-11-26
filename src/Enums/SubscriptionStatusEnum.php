<?php

namespace Codenteq\Iyzico\Enums;

enum SubscriptionStatusEnum: string
{
    case ACTIVE = 'ACTIVE';
    case PENDING = 'PENDING';
    case UNPAID = 'UNPAID';
    case UPGRADED = 'UPGRADED';
    case CANCELED = 'CANCELED';
    case EXPIRED = 'EXPIRED';
}
