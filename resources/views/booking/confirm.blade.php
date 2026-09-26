<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Confirmar — {{ $appointment->company->name ?? 'ClientLoop' }}</title>
    <style>
        body{font-family:Georgia,serif;background:#f0fdfa;color:#134e4a;margin:0;padding:2rem}
        .card{max-width:28rem;margin:0 auto;background:#fff;border:1px solid #99f6e4;border-radius:1rem;padding:1.5rem}
        .actions{display:flex;gap:.75rem;margin-top:1.25rem}
        button,.btn{flex:1;padding:.85rem;border:0;border-radius:.6rem;font-weight:800;cursor:pointer;text-align:center;text-decoration:none}
        .confirm{background:#0f766e;color:#fff}
        .cancel{background:#fee2e2;color:#991b1b}
        .status{padding:.75rem;border-radius:.5rem;background:#ecfdf5;margin-bottom:1rem}
    </style>
</head>
<body>
<div class="card">
    <h1>Confirmação de horário</h1>
    @if(session('status'))<div class="status">{{ session('status') }}</div>@endif
    <p><strong>{{ $appointment->pet?->name }}</strong> · {{ $appointment->service?->name }}</p>
    <p>{{ $appointment->scheduled_at?->format('d/m/Y H:i') }}</p>
    <p>Situação: {{ \App\Support\InterfaceLabels::appointmentStatus($appointment->status) }}</p>
    @if(in_array($appointment->status, ['scheduled', 'confirmed', 'reschedule_requested'], true))
        <div class="actions">
            <form method="post" action="{{ route('appointment.confirm.submit', $appointment->confirmation_token) }}">@csrf<button class="confirm" type="submit">Confirmar</button></form>
            <form method="post" action="{{ route('appointment.confirm.cancel', $appointment->confirmation_token) }}">@csrf<button class="cancel" type="submit">Cancelar</button></form>
        </div>
    @endif
</div>
</body>
</html>
