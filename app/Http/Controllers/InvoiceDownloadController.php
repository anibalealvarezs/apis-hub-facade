<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class InvoiceDownloadController extends Controller
{
    public function __invoke(Request $request, Invoice $invoice)
    {
        // Ensure the authenticated user owns the invoice
        $profile = $invoice->billingProfile;
        
        if (!$profile || $profile->user_id !== auth()->id()) {
            abort(403, 'Unauthorized access to this invoice.');
        }

        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
            'profile' => $profile,
            'subscription' => $invoice->subscription,
        ]);

        return $pdf->download("invoice-{$invoice->gateway_invoice_id}.pdf");
    }
}
