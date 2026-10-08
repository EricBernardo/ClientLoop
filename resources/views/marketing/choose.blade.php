@extends('layouts.marketing')

@section('description', 'ClientLoop para pet shops de banho e tosa e oficinas de serviços automotivos.')
@section('title', 'ClientLoop — Escolha o seu nicho')

@section('body')
    <div class="wrap">
        <header>
            <a class="brand" href="{{ route('home') }}"><img class="brand-mark" src="{{ asset('images/clientloop-symbol.png') }}" alt="">Client<span>Loop</span></a>
            <nav>
                <a class="text-link" href="{{ url('/admin/login') }}">Entrar</a>
            </nav>
        </header>
        <main>
            <section class="hero">
                <div>
                    <div class="eyebrow">ClientLoop</div>
                    <h1>Para qual negócio é o ClientLoop?</h1>
                    <p>Escolha o nicho. A página e o cadastro seguem essa escolha.</p>
                </div>
                <div class="cards four">
                    <a class="card choice" href="{{ route('landing.pet') }}">
                        <div class="eyebrow">Pet shop</div>
                        <h3>Banho, tosa e pacotes</h3>
                        <p>Agenda, confirmações, pacotes e o link do tutor.</p>
                    </a>
                    <a class="card choice" href="{{ route('landing.automotive') }}">
                        <div class="eyebrow">Serviços automotivos</div>
                        <h3>Oficina com ordem de serviço</h3>
                        <p>Ordem de serviço, recibo e fluxo de caixa.</p>
                    </a>
                </div>
            </section>
        </main>
        <footer>
            <span>© {{ now()->year }} ClientLoop</span>
            <span>Já possui uma conta? <a href="{{ url('/admin/login') }}">Entrar no sistema</a></span>
        </footer>
    </div>
@endsection
