<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="ClientLoop organiza pet shops de banho e tosa e oficinas de serviços automotivos. Agenda e pacotes, ou ordem de serviço, recibo e fluxo de caixa.">
    <title>ClientLoop — Pet shop e serviços automotivos</title>
    <link rel="icon" type="image/png" href="{{ asset('images/clientloop-symbol.png') }}">
    <style>
        :root{--ink:#172033;--muted:#607089;--brand:#0f766e;--dark:#123d39;--paper:#f7faf9;--line:#dce8e5}*{box-sizing:border-box}body{margin:0;background:var(--paper);color:var(--ink);font-family:Inter,ui-sans-serif,system-ui,-apple-system,"Segoe UI",sans-serif;line-height:1.5}.wrap{width:min(1120px,calc(100% - 40px));margin:auto}header{padding:20px 0;display:flex;align-items:center;justify-content:space-between}.brand{display:inline-flex;align-items:center;gap:.55rem;font-weight:800;font-size:1.3rem;letter-spacing:-.04em;text-decoration:none;color:var(--ink)}.brand-mark{width:2rem;height:2rem}.brand span{color:var(--brand)}nav,.actions{display:flex;gap:14px;align-items:center}.text-link{color:var(--muted);font-size:.94rem;text-decoration:none}.button{display:inline-flex;align-items:center;justify-content:center;border-radius:10px;padding:12px 18px;font-weight:700;text-decoration:none;transition:.2s}.primary{color:white;background:var(--brand);box-shadow:0 10px 22px #0f766e2b}.primary:hover{background:#115e59;transform:translateY(-1px)}.secondary{color:#115e59;border:1px solid #9dcfc7;background:#fff}.hero{padding:72px 0 86px;display:grid;grid-template-columns:1.1fr .9fr;gap:64px;align-items:center}.eyebrow{color:var(--brand);font-weight:800;font-size:.82rem;letter-spacing:.1em;text-transform:uppercase}.hero h1{margin:14px 0 20px;font-size:clamp(2.6rem,5vw,4.6rem);line-height:1.04;letter-spacing:-.055em}.hero p{font-size:1.15rem;color:var(--muted);max-width:580px}.actions{margin-top:30px;flex-wrap:wrap}.fine{font-size:.86rem;color:var(--muted);margin-top:14px}.preview{padding:24px;border-radius:22px;background:#fff;box-shadow:0 20px 55px #132c2a17;border:1px solid var(--line)}.preview-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:22px}.badge{font-size:.76rem;padding:5px 9px;border-radius:99px;background:#daf1ea;color:#115e59;font-weight:700}.metric-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}.metric{border:1px solid var(--line);padding:16px;border-radius:13px}.metric b{display:block;font-size:1.55rem;letter-spacing:-.04em}.metric span,.task{font-size:.82rem;color:var(--muted)}.task{display:flex;justify-content:space-between;gap:12px;padding:14px 0;border-bottom:1px solid var(--line)}.task:last-child{border:0}.task em{font-style:normal;color:#b45309;background:#fff2d6;border-radius:99px;padding:3px 8px;font-size:.74rem;white-space:nowrap}.section{padding:76px 0}.white{background:#fff}.section h2{font-size:clamp(2rem,3vw,2.8rem);letter-spacing:-.045em;line-height:1.1;margin:0}.lead{color:var(--muted);max-width:680px;margin:14px 0 38px}.cards{display:grid;grid-template-columns:repeat(3,1fr);gap:18px}.cards.four{grid-template-columns:repeat(2,1fr)}.card{background:#fff;border:1px solid var(--line);border-radius:16px;padding:25px}.white .card{background:var(--paper)}.icon{font-size:1.5rem}.card h3{margin:15px 0 8px;font-size:1.08rem}.card p,.step p{margin:0;color:var(--muted);font-size:.95rem}.steps{display:grid;grid-template-columns:repeat(4,1fr);gap:18px}.step-num{color:var(--brand);font-weight:800;font-size:.85rem}.step h3{margin:8px 0}.cta{background:var(--dark);color:white;border-radius:24px;padding:52px;display:flex;gap:28px;align-items:center;justify-content:space-between}.cta h2{max-width:580px}.cta p{color:#bdd8d4;max-width:540px}footer{padding:30px 0;color:var(--muted);font-size:.88rem;display:flex;justify-content:space-between}@media(max-width:900px){.steps{grid-template-columns:1fr 1fr}}@media(max-width:760px){header nav .text-link{display:none}.hero{grid-template-columns:1fr;padding:44px 0 64px;gap:38px}.cards,.cards.four,.steps{grid-template-columns:1fr}.section{padding:56px 0}.cta{padding:32px;display:block}.cta .button{margin-top:15px}footer{display:block}}
    </style>
</head>
<body>
    <div class="wrap">
        <header>
            <a class="brand" href="{{ route('home') }}"><img class="brand-mark" src="{{ asset('images/clientloop-symbol.png') }}" alt="">Client<span>Loop</span></a>
            <nav>
                <a class="text-link" href="#recursos">Pet shop</a>
                <a class="text-link" href="#automotivo">Oficina</a>
                <a class="text-link" href="#how-it-works">Como funciona</a>
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

    <section class="section white" id="automotivo">
        <div class="wrap">
            <div class="eyebrow">Serviços automotivos</div>
            <h2>Oficina com ordem de serviço, recibo e caixa.</h2>
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
</body>
</html>
