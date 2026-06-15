<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $invoice->gateway_invoice_id }}</title>
    <style>
        body { font-family: 'Helvetica Neue', 'Helvetica', Arial, sans-serif; color: #333; line-height: 1.5; font-size: 14px; }
        .invoice-box { max-width: 800px; margin: auto; padding: 30px; border: 1px solid #eee; box-shadow: 0 0 10px rgba(0, 0, 0, 0.15); }
        table { width: 100%; line-height: inherit; text-align: left; border-collapse: collapse; }
        .header td { padding: 5px; vertical-align: top; }
        .title { font-size: 36px; font-weight: bold; color: #000; }
        .company-details { text-align: right; }
        .section-title { font-weight: bold; background: #f5f5f5; padding: 8px; border-bottom: 1px solid #ddd; margin-bottom: 10px; }
        .items-table th { background: #eee; border-bottom: 1px solid #ddd; font-weight: bold; padding: 10px; text-align: left; }
        .items-table td { padding: 10px; border-bottom: 1px solid #eee; }
        .items-table .total td { font-weight: bold; font-size: 16px; border-top: 2px solid #333; }
        .text-right { text-align: right; }
        .footer { margin-top: 50px; text-align: center; color: #777; font-size: 12px; border-top: 1px solid #eee; padding-top: 20px; }
    </style>
</head>
<body>
    <div class="invoice-box">
        <table cellpadding="0" cellspacing="0">
            <tr class="header">
                <td>
                    <span class="title">INVOICE</span><br><br>
                    <strong>Invoice #:</strong> {{ $invoice->gateway_invoice_id }}<br>
                    <strong>Created:</strong> {{ $invoice->created_at->format('F d, Y') }}<br>
                    <strong>Paid:</strong> {{ $invoice->paid_at ? $invoice->paid_at->format('F d, Y') : 'Pending' }}
                </td>
                <td class="company-details">
                    <strong>APIs Hub</strong><br>
                    [Your Company Name Here]<br>
                    [Your Address Line 1]<br>
                    [City, State, Zip]<br>
                    VAT: [Your VAT/Tax ID]<br>
                    support@apishub.com
                </td>
            </tr>
        </table>
        
        <table cellpadding="0" cellspacing="0" style="margin-top: 30px; margin-bottom: 30px;">
            <tr>
                <td style="width: 50%;">
                    <div class="section-title">Billed To:</div>
                    <strong>{{ $profile->name }}</strong><br>
                    @if($profile->address_line_1) {{ $profile->address_line_1 }}<br> @endif
                    @if($profile->city) {{ $profile->city }}, {{ $profile->state }} {{ $profile->postal_code }}<br> @endif
                    @if($profile->country_code) {{ $profile->country_code }}<br> @endif
                    @if($profile->tax_id) <strong>Tax ID:</strong> {{ $profile->tax_id }}<br> @endif
                </td>
                <td style="width: 50%;"></td>
            </tr>
        </table>

        <table class="items-table">
            <thead>
                <tr>
                    <th>Item Description</th>
                    <th class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        {{ ucfirst($profile->tier?->value ?? $profile->tier) }} Tier Subscription
                        @if($subscription)
                            <br><small>Cycle: {{ ucfirst($subscription->billing_cycle ?? 'Monthly') }}</small>
                        @endif
                    </td>
                    <td class="text-right">${{ number_format($invoice->amount, 2) }} {{ strtoupper($invoice->currency) }}</td>
                </tr>
                <tr>
                    <td class="text-right" style="padding-top: 20px;">Subtotal:</td>
                    <td class="text-right" style="padding-top: 20px;">${{ number_format($invoice->amount, 2) }}</td>
                </tr>
                <tr>
                    <td class="text-right">Tax (0%):</td>
                    <td class="text-right">$0.00</td>
                </tr>
                <tr class="total">
                    <td class="text-right">Total Paid:</td>
                    <td class="text-right">${{ number_format($invoice->amount, 2) }} {{ strtoupper($invoice->currency) }}</td>
                </tr>
            </tbody>
        </table>

        <div class="footer">
            Payment processed via {{ ucfirst($invoice->gateway) }}.<br>
            Thank you for your business!
        </div>
    </div>
</body>
</html>
