<?php

namespace Codenteq\Iyzico\Enums;

enum UpgradePeriodEnum: string
{
    case NOW = 'NOW';
    case NEXT_PERIOD = 'NEXT_PERIOD';

    public function toString(): string
    {
        return match($this) {
            self::NOW => 'NOW',
            self::NEXT_PERIOD => 'NEXT_PERIOD'
        };
    }
}
