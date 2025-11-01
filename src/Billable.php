<?php

namespace Codenteq\Iyzico;

use Codenteq\Iyzico\Concerns\ManagesInvoices;
use Codenteq\Iyzico\Concerns\ManagesSubscriptions;

trait Billable
{
    use ManagesSubscriptions, ManagesInvoices;
}
