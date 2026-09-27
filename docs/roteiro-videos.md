# Roteiro dos vídeos do ClientLoop

Série para a página de vídeos. Cada vídeo mostra um trecho do uso do sistema. A tela é gravada em silêncio. O áudio é gerado por IA a partir do bloco **Texto para a narração**.

Grave com uma loja de demonstração já preparada: um responsável, um pet, o serviço Banho e um pacote pago. Em cada clique, pare cerca de um segundo. Cole na IA só o texto da narração. Peça voz em português do Brasil, tom calmo, ritmo de explicação, sem música.

São oito vídeos, na ordem em que a pessoa usa o ClientLoop. Cada um fica entre um minuto e um minuto e meio. A página do painel é **Vídeos** (`/admin/videos`). Salve cada arquivo em `storage/app/public/videos/` com o nome abaixo. Se o player não abrir, rode `php artisan storage:link`.

| Vídeo | Narração | Gravação da tela |
| --- | --- | --- |
| 1. O que o ClientLoop faz | `01-o-que-o-clientloop-faz.mp3` | `01-o-que-o-clientloop-faz.mp4` |
| 2. Horários e serviços | `02-horarios-e-servicos.mp3` | `02-horarios-e-servicos.mp4` |
| 3. Responsável, pet e lista pronta | `03-responsavel-pet-e-lista.mp3` | `03-responsavel-pet-e-lista.mp4` |
| 4. Pacotes | `04-pacotes.mp3` | `04-pacotes.mp4` |
| 5. Marcar um horário | `05-marcar-um-horario.mp3` | `05-marcar-um-horario.mp4` |
| 6. Confirmar pelo WhatsApp | `06-confirmar-pelo-whatsapp.mp3` | `06-confirmar-pelo-whatsapp.mp4` |
| 7. Concluir, faltar, cancelar e marcar a próxima visita | `07-concluir-e-proxima-visita.mp3` | `07-concluir-e-proxima-visita.mp4` |
| 8. O que o painel acompanha depois | `08-o-que-o-painel-acompanha.mp3` | `08-o-que-o-painel-acompanha.mp4` |

## Vídeo 1 — O que o ClientLoop faz

**Na tela:** página inicial do painel, devagar. Passe pela agenda de hoje e pelos cards de tarefas, pacotes e uso do plano. Termine no menu, mostrando Agenda, Responsáveis, Pets, Pacotes e Fila de contatos, sem abrir cada um.

**Texto para a narração:**

O ClientLoop organiza o dia do pet shop em um só lugar. Aqui ficam a agenda, os pets, os responsáveis e as mensagens de WhatsApp. O painel mostra o que precisa de atenção agora: os horários de hoje, quem ainda não confirmou e os pacotes que estão acabando. Você configura a loja uma vez. Depois, a rotina é marcar o horário, confirmar com o tutor e concluir o atendimento quando o banho terminar.

## Vídeo 2 — Horários e serviços

**Na tela:** abra Horários e regras. Mostre os dias, o expediente, o intervalo da agenda e o horário de almoço bloqueado. Salve. Vá em Serviços, crie Banho com 60 minutos e Tosa com 120 minutos. Se o campo de retorno em meses aparecer no Banho, preencha com 1 e salve.

**Texto para a narração:**

Comece pelos horários da loja. Defina os dias de funcionamento, o expediente e o intervalo da agenda. Pode ser de 15, 30 ou 60 minutos. O almoço bloqueado fica fora dos horários livres. Salve. Agora cadastre o que você vende. Crie o serviço Banho, com duração de 60 minutos. A tosa pode levar 120. Se preencher o retorno em meses, ao concluir um atendimento o sistema calcula a data prevista da próxima visita e grava essa data no responsável.

## Vídeo 3 — Responsável, pet e lista pronta

**Na tela:** crie um responsável só com nome e telefone. Em seguida crie o pet desse responsável. Depois abra Importar e mostre o modelo de CSV, sem precisar concluir um envio grande. Volte à lista de responsáveis para mostrar o cadastro pronto.

**Texto para a narração:**

O responsável é a pessoa que recebe o WhatsApp. Nome e telefone já bastam. Salve. O pet é quem entra na agenda. Cadastre o animal ligado a esse responsável. Se a sua lista já está numa planilha, use Importar. Baixe o modelo, preencha o nome do responsável, o telefone e o nome do pet, e envie o arquivo. O sistema cria os dois juntos. A partir daqui, cada banho fica ligado à pessoa certa e ao animal certo.

## Vídeo 4 — Pacotes

**Na tela:** em Modelos de pacote, crie “4 banhos”. Monte a sequência: três vezes Banho e, por último, um serviço maior, como banho com higiênico e hidratação. Salve. Em Pacotes, venda esse modelo para o pet, informe o valor e marque como pago. Abra o pacote e mostre a próxima etapa.

**Texto para a narração:**

Um pacote é uma sequência de visitas compradas antes. Primeiro crie o modelo. Por exemplo, 4 banhos. A ordem importa. As três primeiras visitas são banho. A quarta é o banho com higiênico e hidratação. Salve o modelo. Para vender, escolha o pet, o modelo e o valor. O pacote só entra na agenda depois de marcado como pago. Na ficha do pacote, a próxima etapa mostra qual serviço deve ser feito na visita seguinte. O saldo mostra quantas visitas ainda faltam.

## Vídeo 5 — Marcar um horário

**Na tela:** abra a Agenda no dia de hoje. Clique num horário livre. No formulário, escolha o responsável, o pet, o serviço Banho e o pacote. Mostre que o campo Pacote traz o nome e a próxima etapa, não um número. Salve. Mostre o horário ocupado na agenda, com a duração do serviço. Se houver dois tosadores, mostre dois horários no mesmo minuto, um para cada um.

**Texto para a narração:**

Abra a agenda e clique num horário livre. Escolha o responsável, depois o pet, depois o serviço. Se esse pet tiver pacote pago e a próxima etapa for este serviço, selecione o pacote. O campo mostra o nome do pacote e a próxima etapa. O crédito ainda não é baixado aqui. Ele só baixa quando você concluir o atendimento. Salve. A agenda ocupa o tempo do serviço e não deixa dois atendimentos do mesmo tosador se sobreporem. Dois tosadores diferentes podem atender no mesmo horário.

## Vídeo 6 — Confirmar pelo WhatsApp

**Na tela:** abra Modelos de mensagem e mostre um texto de confirmação com as variáveis na tela, sem precisar ler cada uma. Depois abra a Fila de contatos, na tarefa de confirmação. Clique em WhatsApp e registrar. Mostre a mensagem pronta. Feche sem encerrar a tarefa. Aponte o resultado Sem resposta e explique, sem precisar clicar duas vezes se a tarefa sumir.

**Texto para a narração:**

A confirmação aparece na fila de contatos cerca de um dia antes do horário. A mensagem já vem pronta. O nome do responsável, o nome do pet, a data e os links entram sozinhos. Um link leva o tutor à página para pedir horário. O outro confirma ou cancela aquele atendimento. Na fila, use WhatsApp e registrar. O WhatsApp abre com o texto, e na mesma tela você marca o que aconteceu. Se ninguém responder, escolha Sem resposta. Na primeira vez, a tarefa continua na fila para você tentar de novo. Na segunda, a tarefa encerra e o horário continua agendado.

## Vídeo 7 — Concluir, faltar, cancelar e marcar a próxima visita

**Na tela:** abra o agendamento. Clique em Concluir atendimento e mostre a janela de confirmação antes de confirmar. Depois de concluído, mostre Agendar próxima etapa, com o próximo serviço e a data uma semana depois. Em outro horário ainda agendado, mostre os botões Falta, Cancelar e Reagendar, e Desfazer conclusão num atendimento já concluído. Não precisa executar falta e cancelar de verdade se quiser manter a demo. Passe o mouse ou abra o menu o suficiente para o nome do botão aparecer.

**Texto para a narração:**

Quando o banho terminar, abra o agendamento e clique em Concluir atendimento. Uma janela pede confirmação. Só depois disso o status muda, o crédito do pacote é baixado e, se o serviço tiver retorno em meses, a data prevista vai para o responsável. Se concluiu por engano, use Desfazer conclusão. O horário volta e o crédito do pacote é restaurado. Se o tutor não veio, use Falta. Se desmarcou, use Cancelar. Nos dois casos o horário sai da agenda e o crédito do pacote não é baixado. Para mudar o dia, use Reagendar. Para a visita seguinte do pacote, use Agendar próxima etapa. O sistema já traz o próximo serviço, para o mesmo horário, sete dias depois.

## Vídeo 8 — O que o painel acompanha depois

**Na tela:** volte ao painel e passe pelos cards. Abra o sino no topo. Abra Relatórios. Mostre a Lista de espera, uma Campanha e a diferença entre Tosadores e Equipe no menu. Feche no Histórico de ações. No responsável, mostre Bloquear contato e Exportar LGPD, sem executar o bloqueio.

**Texto para a narração:**

Com a rotina andando, o painel acompanha o resto. O sino avisa quando o tutor confirma ou cancela, quando uma importação ou campanha termina e quando a cota de tarefas do mês acaba. Os relatórios mostram, no mês, confirmações, faltas, ocupação, pacotes vendidos e contatos ainda pendentes. Se o horário pedido não cabe, anote a pessoa na lista de espera. Campanhas de retorno e reativação criam várias tarefas de uma vez, e elas caem na mesma fila de WhatsApp. Tosador é quem aparece na agenda. Equipe é quem entra no painel: dono ou atendente. O histórico guarda conclusões, faltas e mudanças. No responsável, Bloquear contato interrompe as mensagens. Exportar LGPD entrega os dados daquela pessoa.
