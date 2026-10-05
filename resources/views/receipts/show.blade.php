<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Recibo {{ $receipt->id }} — {{ $receipt->company->name }}</title>
    <style>
        body{margin:0;background:#f4f7f6;color:#172033;font-family:Inter,ui-sans-serif,system-ui,sans-serif}
        .sheet{width:min(720px,calc(100% - 32px));margin:32px auto;background:#fff;border:1px solid #d7e2e0;border-radius:16px;padding:32px}
        h1{margin:0;font-size:1.6rem;letter-spacing:-.04em}
        .muted{color:#607089}
        table{width:100%;border-collapse:collapse;margin-top:24px}
        th,td{padding:10px 0;border-bottom:1px solid #e6eeec;text-align:left}
        td.num,th.num{text-align:right}
        .total{display:flex;justify-content:space-between;margin-top:18px;font-size:1.15rem;font-weight:800}
        button{margin-top:24px;border:0;border-radius:9px;background:#0f766e;color:#fff;padding:12px 16px;font:inherit;font-weight:700;cursor:pointer}
        @media print{body{background:#fff}.sheet{margin:0;width:auto;border:0}button{display:none}}
    </style>
</head>
<body>
    @php
        $order = $receipt->serviceOrder;
        $money = fn (mixed $value): string => 'R$ '.number_format((float) $value, 2, ',', '.');
    @endphp
    <article class="sheet">
        <p class="muted">Recibo interno</p>
        <h1>{{ $receipt->company->name }}</h1>
        <p>Recibo {{ $receipt->id }} · ordem {{ $order->id }} · {{ $receipt->issued_on->format('d/m/Y') }}</p>
        <p><strong>{{ $order->customer->name }}</strong><br>{{ $order->vehicle->label() }}</p>
        <table>
            <thead>
                <tr><th>Item</th><th class="num">Qtd</th><th class="num">Unitário</th><th class="num">Total</th></tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                    <tr>
                        <td>{{ $item->description }}</td>
                        <td class="num">{{ number_format((float) $item->quantity, 2, ',', '.') }}</td>
                        <td class="num">{{ $money($item->unit_price) }}</td>
                        <td class="num">{{ $money($item->lineTotal()) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="total"><span>Valor do recibo</span><span>{{ $money($receipt->amount) }}</span></div>
        <p>{{ $receipt->payment_method->label() }} · {{ $receipt->status->label() }}@if($receipt->paid_on) · pago em {{ $receipt->paid_on->format('d/m/Y') }}@endif</p>
        <button type="button" onclick="window.print()">Imprimir</button>
    </article>
</body>
</html>
