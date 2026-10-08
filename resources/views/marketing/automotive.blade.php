@extends('layouts.marketing')

@section('description', 'ClientLoop organiza oficinas de serviços automotivos. Ordem de serviço, recibo e fluxo de caixa.')
@section('title', 'ClientLoop — Serviços automotivos')

@section('body')
    <div class="wrap">
        <header>
            <a class="brand" href="{{ route('home') }}"><img class="brand-mark" src="{{ asset('images/clientloop-symbol.png') }}" alt="">Client<span>Loop</span></a>
            <nav>
                <a class="text-link" href="#recursos">Recursos</a>
                <a class="text-link" href="#how-it-works">Como funciona</a>
                <a class="text-link niche-switch" href="{{ route('landing.pet') }}">Pet shop</a>
                <a class="text-link" href="{{ url('/admin/login') }}">Entrar</a>
                <a class="button primary" href="{{ route('register') }}">Criar minha conta</a>
            </nav>
        </header>
        <main>
            <section class="hero">
                <div>
                    <div class="eyebrow">Serviços automotivos</div>
                    <h1>Oficina com ordem de serviço, recibo e caixa.</h1>
                    <p>Cadastre o cliente e o veículo, abra a ordem de serviço, lance o recibo interno e acompanhe o que entrou e saiu no período.</p>
                    <div class="actions">
                        <a class="button primary" href="{{ route('register') }}">Começar gratuitamente</a>
                        <a class="button secondary" href="#recursos">Ver o que inclui</a>
                    </div>
                    <p class="fine">14 dias de teste. Sem cartão nesta etapa.</p>
                </div>
                <aside class="preview" aria-label="Exemplo do painel da oficina">
                    <div class="preview-head"><strong>Rotina de hoje</strong><span class="badge">Ordem · Recibo · Caixa</span></div>
                    <div class="metric-grid">
                        <div class="metric"><b>3</b><span>Ordens abertas</span></div>
                        <div class="metric"><b>1</b><span>Recibo pendente</span></div>
                        <div class="metric"><b>2</b><span>Recibos pagos</span></div>
                        <div class="metric"><b>1</b><span>Saída lançada</span></div>
                    </div>
                    <div class="task"><span>Civic · cliente e veículo</span><em>Aberto</em></div>
                    <div class="task"><span>Ordem de serviço · itens e total</span><em>Em serviço</em></div>
                    <div class="task"><span>Recibo interno · Pix</span><em>Pago</em></div>
                    <div class="task"><span>Fluxo de caixa · peças</span><em>Saída</em></div>
                </aside>
            </section>
        </main>
    </div>

    <section class="section white" id="recursos">
        <div class="wrap">
            <div class="eyebrow">O que o ClientLoop cobre</div>
            <h2>Da ordem de serviço ao saldo do período.</h2>
            <p class="lead">Cadastre o cliente e o veículo, abra a ordem de serviço, lance o recibo interno e acompanhe o que entrou e saiu no período.</p>
            <div class="cards">
                <article class="card">
                    <div class="icon">1</div>
                    <h3>Ordem de serviço</h3>
                    <p>Cliente, veículo, situação e itens com quantidade e valor. O total sai da soma das linhas.</p>
                </article>
                <article class="card">
                    <div class="icon">2</div>
                    <h3>Recibo interno</h3>
                    <p>Um recibo por ordem: valor, Pix, dinheiro, cartão ou a prazo, pago ou pendente. Dá para imprimir.</p>
                </article>
                <article class="card">
                    <div class="icon">3</div>
                    <h3>Fluxo de caixa</h3>
                    <p>O recibo pago entra sozinho. Aluguel, peças e outras saídas você lança na mão. O saldo é do período.</p>
                </article>
            </div>
        </div>
    </section>

    <section class="section" id="how-it-works">
        <div class="wrap">
            <div class="eyebrow">Uma rotina clara</div>
            <h2>Do cliente na porta ao recibo no caixa.</h2>
            <p class="lead">O ClientLoop guarda a ordem, o recibo e o que entrou e saiu. A conversa com o cliente continua sendo da oficina.</p>
            <div class="steps">
                <div class="step">
                    <div class="step-num">01 — CADASTRE</div>
                    <h3>Cliente e veículo</h3>
                    <p>Cadastre o cliente e o veículo antes de abrir o serviço.</p>
                </div>
                <div class="step">
                    <div class="step-num">02 — ABRA</div>
                    <h3>Ordem de serviço</h3>
                    <p>Cliente, veículo, situação e itens com quantidade e valor. O total sai da soma das linhas.</p>
                </div>
                <div class="step">
                    <div class="step-num">03 — LANCE</div>
                    <h3>Recibo interno</h3>
                    <p>Um recibo por ordem: valor, Pix, dinheiro, cartão ou a prazo, pago ou pendente. Dá para imprimir.</p>
                </div>
                <div class="step">
                    <div class="step-num">04 — ACOMPANHE</div>
                    <h3>Fluxo de caixa</h3>
                    <p>O recibo pago entra sozinho. Aluguel, peças e outras saídas você lança na mão. O saldo é do período.</p>
                </div>
            </div>
        </div>
    </section>

    <div class="wrap section">
        <section class="cta">
            <div>
                <div class="eyebrow" style="color:#74cbbd">Comece agora</div>
                <h2>Deixe a rotina da oficina no mesmo lugar.</h2>
                <p>Crie a conta e abra o painel. A ordem de serviço, o recibo e o fluxo de caixa ficam juntos.</p>
            </div>
            <a class="button primary" href="{{ route('register') }}">Criar conta</a>
        </section>
        <footer>
            <span>© {{ now()->year }} ClientLoop</span>
            <span>Já possui uma conta? <a href="{{ url('/admin/login') }}">Entrar no sistema</a></span>
        </footer>
    </div>
@endsection
