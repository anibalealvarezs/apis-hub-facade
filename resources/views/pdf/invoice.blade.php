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
        @php
            $numeroFactura = $invoice->invoice_number ?? str_pad($invoice->id ?? 1, 8, '0', STR_PAD_LEFT);
            $numeroControl = $invoice->control_number ?? '00-' . $numeroFactura;
            $isVe = ($invoice->tax_rate > 0 || strtoupper($profile->country_code) === 'VE');
        @endphp
        <table cellpadding="0" cellspacing="0">
            <tr class="header">
                <td>
                    <span class="title">FACTURA</span><br><br>
                    <strong>Factura N°:</strong> {{ $numeroFactura }}<br>
                    <strong>N° de Control:</strong> {{ $numeroControl }}<br>
                    <strong>Fecha de Emisión:</strong> {{ $invoice->created_at->format('d/m/Y') }}<br>
                    <strong>Fecha de Pago:</strong> {{ $invoice->paid_at ? $invoice->paid_at->format('d/m/Y') : 'Pendiente' }}
                </td>
                <td class="company-details">
                    <strong>ANIBAL ENRIQUE ALVAREZ SIFONTES</strong><br>
                    <strong>RIF:</strong> V-16224613-1<br>
                    CALLE 14 EDIF LOMA NORTE PISO 3 APT 36<br>
                    URB LOMAS DEL AVILA CARACAS (PETARE)<br>
                    MIRANDA ZONA POSTAL 1073<br>
                    support@apis-hub.cloud
                </td>
            </tr>
        </table>
        
        <table cellpadding="0" cellspacing="0" style="margin-top: 30px; margin-bottom: 30px;">
            <tr>
                <td style="width: 50%;">
                    <div class="section-title">Datos del Adquiriente:</div>
                    <strong>{{ $profile->name }}</strong><br>
                    @if($profile->tax_id) <strong>RIF / Tax ID:</strong> {{ $profile->tax_id }}<br> @endif
                    @if($profile->address_line_1) {{ $profile->address_line_1 }}<br> @endif
                    @if($profile->city) {{ $profile->city }}, {{ $profile->state }} {{ $profile->postal_code }}<br> @endif
                    @if($profile->country_code) {{ $profile->country_code }}<br> @endif
                </td>
                <td style="width: 50%;"></td>
            </tr>
        </table>

        <table class="items-table">
            <thead>
                <tr>
                    <th>Cant.</th>
                    <th>Descripción</th>
                    <th class="text-right">Precio Unitario (USD)</th>
                    <th class="text-right">Monto Neto (USD)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>1</td>
                    <td>
                        Suscripción Mensual al Plan {{ ucfirst($profile->tier?->value ?? $profile->tier) }} - SaaS
                    </td>
                    <td class="text-right">${{ number_format($invoice->subtotal ?? $invoice->amount, 2) }}</td>
                    <td class="text-right">${{ number_format($invoice->subtotal ?? $invoice->amount, 2) }}</td>
                </tr>
                <tr>
                    <td colspan="2"></td>
                    <td class="text-right" style="padding-top: 20px;">Subtotal (USD):</td>
                    <td class="text-right" style="padding-top: 20px;">${{ number_format($invoice->subtotal ?? $invoice->amount, 2) }}</td>
                </tr>
                <tr>
                    <td colspan="2"></td>
                    <td class="text-right">IVA ({{ number_format($invoice->tax_rate ?? 0, 0) }}%):</td>
                    <td class="text-right">${{ number_format($invoice->tax_amount ?? 0, 2) }}</td>
                </tr>
                <tr class="total">
                    <td colspan="2"></td>
                    <td class="text-right">Total (USD):</td>
                    <td class="text-right">${{ number_format($invoice->amount, 2) }}</td>
                </tr>
            </tbody>
        </table>

        @if($invoice->local_currency === 'VES' || $isVe)
        <div style="margin-top: 30px; border: 1px solid #ddd; padding: 15px; background: #f9f9f9;">
            <div class="section-title" style="margin-top:0;">Equivalente Legal en Bolívares (VES)</div>
            <p style="font-size: 12px; margin-bottom: 10px;">
                Tasa de cambio de referencia según el Banco Central de Venezuela (BCV) de fecha {{ $invoice->paid_at ? $invoice->paid_at->format('d/m/Y') : $invoice->created_at->format('d/m/Y') }}: <strong>{{ $invoice->exchange_rate ? number_format($invoice->exchange_rate, 4, ',', '.') . ' Bs./USD' : '[Tasa Pendiente]' }}</strong>.
            </p>
            <table cellpadding="0" cellspacing="0" style="width: 100%; font-size: 13px;">
                <tr>
                    <td style="width: 50%;"></td>
                    <td class="text-right"><strong>Subtotal:</strong> {{ $invoice->local_subtotal ? number_format($invoice->local_subtotal, 2, ',', '.') . ' Bs' : '[Pendiente]' }}</td>
                </tr>
                <tr>
                    <td style="width: 50%;"></td>
                    <td class="text-right"><strong>IVA ({{ number_format($invoice->tax_rate ?? 0, 0) }}%):</strong> {{ $invoice->local_tax_amount ? number_format($invoice->local_tax_amount, 2, ',', '.') . ' Bs' : '[Pendiente]' }}</td>
                </tr>
                <tr>
                    <td style="width: 50%;"></td>
                    <td class="text-right" style="font-size: 15px;"><strong>Total:</strong> {{ $invoice->local_total ? number_format($invoice->local_total, 2, ',', '.') . ' Bs' : '[Pendiente]' }}</td>
                </tr>
            </table>
        </div>
        @endif

        <div class="footer" style="text-align: justify; border: none;">
            @if($isVe)
                <strong>Nota Fiscal:</strong> Operación comercial en moneda extranjera pactada y pagada en divisas según Convenio Cambiario N° 1. Montos en Bolívares reflejados a los fines del cumplimiento tributario según Providencia 0071.
            @else
                <strong>Nota Fiscal:</strong> Operación de exportación de servicios intangibles gravada con alícuota del 0% de IVA, según lo establecido en el Artículo 13 de la Ley que Establece el Impuesto al Valor Agregado.
            @endif
            <br><br>
            <div style="text-align: center;">Pago procesado vía {{ ucfirst($invoice->gateway) }} (ID: {{ $invoice->gateway_invoice_id }}). Gracias por su confianza.</div>
        </div>
    </div>
</body>
</html>
