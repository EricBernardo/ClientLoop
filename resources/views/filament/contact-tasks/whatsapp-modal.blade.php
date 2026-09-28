@php
    $appointment = $task->appointment;
    $petName = $appointment?->pet?->name;
    $serviceName = $appointment?->service?->name;
    $visitAt = $appointment?->scheduled_at
        ? $appointment->scheduled_at->format('d/m/Y').' às '.$appointment->scheduled_at->format('H:i')
        : null;
    $context = collect([$petName, $serviceName, $visitAt])->filter()->implode(' · ');
@endphp

<div style="display:flex;flex-direction:column;gap:1rem;">
    <div style="display:flex;flex-direction:column;gap:.25rem;padding:.85rem 1rem;border:1px solid rgba(15,118,110,.18);border-radius:.85rem;background:rgba(15,118,110,.06);">
        <p style="margin:0;font-size:1rem;font-weight:700;">{{ $task->customer?->name }}</p>
        <p style="margin:0;font-size:.875rem;opacity:.8;">
            {{ \App\Support\InterfaceLabels::contactType($task->type) }}
            @if ($task->customer?->phone)
                · {{ $task->customer->phone }}
            @endif
        </p>
        @if ($context !== '')
            <p style="margin:0;font-size:.875rem;opacity:.8;">{{ $context }}</p>
        @endif
    </div>

    <div style="display:flex;flex-direction:column;gap:.4rem;">
        <p style="margin:0;font-size:.75rem;font-weight:700;letter-spacing:.04em;text-transform:uppercase;opacity:.6;">Mensagem</p>
        <div style="max-height:12rem;overflow:auto;padding:.85rem 1rem;border-radius:.85rem;background:rgba(15,118,110,.1);font-size:.9rem;line-height:1.45;white-space:pre-wrap;">{{ filled($task->rendered_message) ? $task->rendered_message : 'Esta tarefa não tem mensagem pronta.' }}</div>
    </div>

    <a
        href="{{ $url }}"
        target="_blank"
        rel="noopener"
        style="display:flex;width:100%;align-items:center;justify-content:center;padding:.8rem 1rem;border-radius:.85rem;background:#0f766e;color:#fff;font-size:.9rem;font-weight:700;text-decoration:none;"
    >
        Abrir WhatsApp
    </a>

    @if ($apiConfigured && $sent)
        <p style="margin:0;padding:.75rem 1rem;border-radius:.85rem;background:rgba(5,150,105,.12);font-size:.875rem;">
            Mensagem enviada automaticamente pela API do WhatsApp.
        </p>
    @elseif ($apiConfigured && filled($error))
        <p style="margin:0;padding:.75rem 1rem;border-radius:.85rem;background:rgba(217,119,6,.12);font-size:.875rem;">
            API configurada, mas o envio automático falhou: {{ $error }}. Use o botão acima.
        </p>
    @endif

    <p style="margin:0;font-size:.875rem;opacity:.7;">Depois volte aqui e registre o resultado.</p>
</div>
