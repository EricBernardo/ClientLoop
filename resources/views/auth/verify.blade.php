<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Confirme seu e-mail — ClientLoop</title>
    <link rel="icon" type="image/png" href="{{ asset('images/clientloop-symbol.png') }}">
    <style>
        :root{--ink:#172033;--muted:#607089;--brand:#0f766e;--line:#d7e2e0;--paper:#f2f8f7}
        *{box-sizing:border-box}
        body{margin:0;min-height:100vh;background:var(--paper);color:var(--ink);font-family:Inter,ui-sans-serif,system-ui,-apple-system,"Segoe UI",sans-serif;display:grid;place-items:center;padding:32px 20px}
        .card{width:min(440px,100%);background:#fff;border:1px solid var(--line);border-radius:16px;padding:36px 32px;box-shadow:0 12px 32px #132c2a0f}
        .brand{display:inline-flex;align-items:center;gap:.55rem;color:var(--ink);text-decoration:none;font-size:1.25rem;font-weight:800;letter-spacing:-.04em}
        .brand-mark{width:2rem;height:2rem}
        .brand span{color:var(--brand)}
        h1{font-size:1.75rem;letter-spacing:-.04em;margin:28px 0 10px;line-height:1.15}
        p{color:var(--muted);margin:0 0 12px;line-height:1.55;font-size:.98rem}
        .hint{font-size:.88rem;background:#f0fdfa;border:1px solid #b7e4d7;border-radius:10px;padding:12px 14px;color:#115e59;margin:18px 0 22px}
        button{width:100%;border:0;border-radius:9px;background:var(--brand);color:#fff;padding:13px;font:inherit;font-weight:800;cursor:pointer}
        button:hover{background:#115e59}
        .secondary{display:block;text-align:center;margin-top:18px;color:var(--brand);font-weight:700;text-decoration:none;font-size:.92rem}
        .secondary:hover{text-decoration:underline}
        .status{background:#ecfdf5;color:#047857;padding:11px 13px;border-radius:8px;font-size:.9rem;margin-bottom:16px}
        .error{background:#fff0f0;color:#b42318;padding:11px 13px;border-radius:8px;font-size:.9rem;margin-bottom:16px}
    </style>
</head>
<body>
    <main class="card">
        <a class="brand" href="{{ route('home') }}"><img class="brand-mark" src="{{ asset('images/clientloop-symbol.png') }}" alt="">Client<span>Loop</span></a>
        <h1>Confirme seu e-mail</h1>
        <p>Enviamos um link de confirmação para <strong>{{ auth()->user()->email }}</strong>. Depois de abrir o link, você entra no painel da sua pet shop.</p>
        <div class="hint">Não encontrou? Confira a pasta de spam ou o atraso da caixa de entrada. Em ambientes de demo, o e-mail pode aparecer no Mailpit.</div>
        @if (session('status') === 'verification-link-sent')
            <p class="status">Link reenviado. Verifique seu e-mail.</p>
        @endif
        @if ($errors->any())
            <p class="error">{{ $errors->first() }}</p>
        @endif
        <form method="post" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit">Reenviar link</button>
        </form>
        <a class="secondary" href="{{ url('/admin') }}">Já confirmei — ir ao painel</a>
    </main>
</body>
</html>
