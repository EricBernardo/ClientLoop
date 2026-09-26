<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Agendar — {{ $company->name }}</title>
    <style>
        body{font-family:Georgia,serif;background:#f0fdfa;color:#134e4a;margin:0;padding:2rem}
        .card{max-width:32rem;margin:0 auto;background:#fff;border:1px solid #99f6e4;border-radius:1rem;padding:1.5rem}
        label{display:block;margin:.8rem 0 .3rem;font-weight:700;font-size:.9rem}
        input,select{width:100%;padding:.7rem;border:1px solid #d0d5dd;border-radius:.5rem;box-sizing:border-box}
        button{margin-top:1.2rem;width:100%;padding:.85rem;border:0;border-radius:.6rem;background:#0f766e;color:#fff;font-weight:800;cursor:pointer}
        .status{padding:.75rem;border-radius:.5rem;background:#ecfdf5;margin-bottom:1rem}
        .error{color:#b91c1c;font-size:.85rem}
    </style>
</head>
<body>
<div class="card">
    <h1>{{ $company->name }}</h1>
    <p>Escolha um horário disponível. A loja confirma pelo WhatsApp.</p>
    @if(session('status'))<div class="status">{{ session('status') }}</div>@endif
    <form method="post" action="{{ route('booking.store', $company->public_booking_token) }}">
        @csrf
        <label>Seu nome</label>
        <input name="customer_name" value="{{ old('customer_name') }}" required>
        @error('customer_name')<div class="error">{{ $message }}</div>@enderror
        <label>WhatsApp</label>
        <input name="customer_phone" value="{{ old('customer_phone') }}" required placeholder="11999999999">
        @error('customer_phone')<div class="error">{{ $message }}</div>@enderror
        <label>Nome do pet</label>
        <input name="pet_name" value="{{ old('pet_name') }}" required>
        @error('pet_name')<div class="error">{{ $message }}</div>@enderror
        <label>Serviço</label>
        <select name="service_id" required>
            @foreach($services as $service)
                <option value="{{ $service->id }}" @selected(old('service_id') == $service->id)>{{ $service->name }} ({{ $service->duration_minutes }} min)</option>
            @endforeach
        </select>
        @error('service_id')<div class="error">{{ $message }}</div>@enderror
        <label>Data e horário</label>
        <input type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at') }}" required>
        @error('scheduled_at')<div class="error">{{ $message }}</div>@enderror
        <button type="submit">Solicitar horário</button>
    </form>
</div>
</body>
</html>
