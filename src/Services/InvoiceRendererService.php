<?php

namespace Codenteq\Iyzico\Services;

use Codenteq\Iyzico\Contracts\InvoiceRenderer;
use Codenteq\Iyzico\Invoice;
use Dompdf\Dompdf;
use Dompdf\Options;

class InvoiceRendererService implements InvoiceRenderer
{

    /**
     * @throws \Throwable
     */
    public function render(Invoice $invoice, array $data): string
    {
        if (! defined('DOMPDF_ENABLE_AUTOLOAD')) {
            define('DOMPDF_ENABLE_AUTOLOAD', false);
        }

        $dompdfOptions = new Options;
        $dompdfOptions->setIsRemoteEnabled(false);
        $dompdfOptions->setChroot(base_path());

        $dompdf =  new Dompdf($dompdfOptions);
        $dompdf->loadHtml($invoice->view($data)->render());
        $dompdf->render();

        return $dompdf->output();
    }
}
