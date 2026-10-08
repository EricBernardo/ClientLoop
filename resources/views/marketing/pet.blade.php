@extends('layouts.marketing')

@section('description', 'ClientLoop organiza pet shops de banho e tosa. Agenda, confirmações, pacotes e o link do tutor.')
@section('title', 'ClientLoop — Pet shop')

@section('body')
    <div class="wrap">
        <header>
            <a class="brand" href="{{ route('home') }}"><img class="brand-mark" src="{{ asset('images/clientloop-symbol.png') }}" alt="">Client<span>Loop</span></a>
            <nav>
                <a class="text-link" href="#recursos">Recursos</a>
                <a class="text-link" href="#how-it-works">Como funciona</a>
                <a class="text-link niche-switch" href="{{ route('landing.automotive') }}">Oficina</a>
                <a class="text-link" href="{{ url('/admin/login') }}">Entrar</a>
                <a class="button primary" href="{{ route('register') }}">Criar minha conta</a>
            </nav>
        </header>
        <main>
            <section class="hero">
                <div>
                    <div class="eyebrow">Feito para pet shops de banho e tosa</div>
                    <h1>Sua agenda, seus pets e seus pacotes no mesmo lugar.</h1>
                    <p>Marque banhos sem conflito, confirme pelo WhatsApp ou por link, dê ao tutor um link de agendamento, acompanhe pacotes e retornos — e não deixe ninguém sumir da fila. O sino avisa a equipe quando alguém confirma, cancela ou quando a cota do mês acaba.</p>
                    <div class="actions">
                        <a class="button primary" href="{{ route('register') }}">Começar gratuitamente</a>
                        <a class="button secondary" href="#recursos">Ver o que inclui</a>
                    </div>
                    <p class="fine">14 dias de teste. Sem cartão nesta etapa.</p>
                </div>
                <aside class="preview" aria-label="Exemplo do painel ClientLoop">
                    <div class="preview-head"><strong>Rotina de hoje</strong><span class="badge">Agenda · Fila · Pacotes</span></div>
                    <div class="metric-grid">
                        <div class="metric"><b>4</b><span>Pets na agenda</span></div>
                        <div class="metric"><b>3</b><span>Contatos pendentes</span></div>
                        <div class="metric"><b>1</b><span>Na lista de espera</span></div>
                        <div class="metric"><b>2</b><span>Pacotes com pouco saldo</span></div>
                    </div>
                    <div class="task"><span>Thor · Banho · Ana</span><em>10:00</em></div>
                    <div class="task"><span>Confirmar Mel · WhatsApp</span><em>Pendente</em></div>
                    <div class="task"><span>Link do tutor · horário solicitado</span><em>Novo</em></div>
                    <div class="task"><span>Remarcar falta do Bob</span><em>Retorno</em></div>
                    <div class="task"><span>Pacote do Thor acabou · renovar</span><em>Fila</em></div>
                    <div class="task"><span>Sino · Nina confirmou presença</span><em>Agora</em></div>
                </aside>
            </section>
        </main>
    </div>

    <section class="section white" id="recursos">
        <div class="wrap">
            <div class="eyebrow">O que o ClientLoop cobre</div>
            <h2>Do horário marcado à próxima conversa.</h2>
            <p class="lead">Tudo o que a loja precisa para lembrar o próximo banho e a próxima mensagem — sem virar clínica, hotel ou caixa.</p>
            <div class="cards">
                <article class="card">
                    <div class="icon">◷</div>
                    <h3>Agenda com regras reais</h3>
                    <p>Expediente, intervalos de 15/30/60 min, almoço bloqueado, recorrência semanal e vários tosadores no mesmo horário. No serviço, o retorno previsto em meses marca a próxima visita.</p>
                </article>
                <article class="card">
                    <div class="icon">▣</div>
                    <h3>Pacotes em sequência</h3>
                    <p>Venda “4 banhos”, baixe crédito só ao concluir, transfira saldo entre pets e desfaça a conclusão se errou. O último crédito abre um contato de renovação na fila.</p>
                </article>
                <article class="card">
                    <div class="icon">✓</div>
                    <h3>Fila de WhatsApp</h3>
                    <p>Confirmação, retorno e reativação com mensagem pronta. Sem resposta tenta de novo; falta e cancelamento geram follow-up para remarcar.</p>
                </article>
                <article class="card">
                    <div class="icon">◎</div>
                    <h3>Link para o tutor</h3>
                    <p>Página pública de agendamento e link de confirmar ou cancelar a presença. O pedido do tutor entra na agenda e a confirmação segue na fila de WhatsApp.</p>
                </article>
                <article class="card">
                    <div class="icon">☰</div>
                    <h3>Lista de espera e campanhas</h3>
                    <p>Quando a agenda enche, entre na fila. Dispare retornos e reativações em lote no dia certo.</p>
                </article>
                <article class="card">
                    <div class="icon">◉</div>
                    <h3>Equipe e visão da loja</h3>
                    <p>Separe tosadores de quem usa o painel. Convide atendentes, consulte o histórico de ações e exporte os dados do responsável.</p>
                </article>
                <article class="card">
                    <div class="icon">1</div>
                    <h3>Importação da planilha</h3>
                    <p>Baixe o modelo, preencha responsáveis e pets e envie o CSV. O painel mostra o que entrou e a linha que falhou.</p>
                </article>
                <article class="card">
                    <div class="icon">⚑</div>
                    <h3>Avisos para a equipe</h3>
                    <p>O sino avisa quando o tutor pede ou altera um horário pelo link, quando confirma ou cancela, quando uma campanha ou importação termina, quando a cota acaba, e quando o período de teste encerra.</p>
                </article>
                <article class="card">
                    <div class="icon">%</div>
                    <h3>Relatórios do mês</h3>
                    <p>Confirmação, falta, ocupação de hoje, pacotes vendidos e contatos ainda pendentes — na mesma linguagem da agenda e da fila.</p>
                </article>
            </div>
        </div>
    </section>

    <section class="section" id="how-it-works">
        <div class="wrap">
            <div class="eyebrow">Uma rotina clara</div>
            <h2>Do primeiro cadastro ao próximo banho.</h2>
            <p class="lead">O ClientLoop organiza o próximo passo; a conversa continua sendo da loja — ou do tutor, quando ele usa o link.</p>
            <div class="steps">
                <div class="step">
                    <div class="step-num">01 — PREPARE</div>
                    <h3>Serviços, equipe e horários</h3>
                    <p>Cadastre horários, serviços, responsáveis e pets. Tosadores entram na agenda; atendentes entram no painel. A planilha em CSV traz a lista que já existe.</p>
                </div>
                <div class="step">
                    <div class="step-num">02 — AGENDE</div>
                    <h3>Pela loja ou pelo tutor</h3>
                    <p>Marque na agenda, use lista de espera ou compartilhe o link público de agendamento.</p>
                </div>
                <div class="step">
                    <div class="step-num">03 — CONFIRME</div>
                    <h3>WhatsApp ou link</h3>
                    <p>Abra o wa.me com texto pronto, registre o resultado, ou envie o link de confirmação.</p>
                </div>
                <div class="step">
                    <div class="step-num">04 — CONTINUE</div>
                    <h3>Conclua e retorne</h3>
                    <p>Baixe o pacote, marque a próxima etapa, recupere faltas e reative quem sumiu.</p>
                </div>
            </div>
        </div>
    </section>

    <div class="wrap section">
        <section class="cta">
            <div>
                <div class="eyebrow" style="color:#74cbbd">Comece agora</div>
                <h2>Deixe a rotina do pet shop mais leve a partir do próximo banho.</h2>
                <p>Crie a conta e abra o painel. A agenda, a fila, o link do tutor e os relatórios ficam no mesmo lugar.</p>
            </div>
            <a class="button primary" href="{{ route('register') }}">Criar conta</a>
        </section>
        <footer>
            <span>© {{ now()->year }} ClientLoop</span>
            <span>Já possui uma conta? <a href="{{ url('/admin/login') }}">Entrar no sistema</a></span>
        </footer>
    </div>
@endsection
