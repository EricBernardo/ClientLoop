# ClientLoop

SaaS para pet shops de pequeno porte organizarem agenda, pets, responsáveis, pacotes e confirmações. O envio por WhatsApp é manual nesta versão: o painel abre uma conversa `wa.me` e o atendente registra o resultado. Há também link público de agendamento e confirmação para o tutor.

## Início rápido

1. Copie `.env.example` para `.env` e preencha `APP_KEY`.
2. Inicie o Docker Desktop e execute `docker compose up --build`.
3. Em outro terminal, execute `docker compose exec app php artisan migrate --seed`.
4. Abra `http://localhost:8000/admin/login` com a demo, ou `http://localhost:8000/register` para criar empresa nova (e-mail no Mailpit: `http://localhost:8025`).

Contas do seed:

| Papel | URL | E-mail | Senha |
|---|---|---|---|
| Dona da loja | `/admin` | `demo@clientloop.test` | `clientloop123` |
| Atendente | `/admin` | `atendente@clientloop.test` | `clientloop123` |
| Superadmin | `/platform` | `admin@clientloop.test` | `clientloop123` |

Link público da demo: `/agendar/patinhas-demo-booking`.

O painel da empresa fica em `/admin` e o painel reservado a superadmins fica em `/platform`. Também é possível promover um usuário em **Empresas** no `/platform`.

## Operação

- `php artisan clientloop:generate-tasks` gera confirmações, retornos e reativações; o scheduler Docker o executa de hora em hora.
- `php artisan clientloop:launch-campaigns` ativa campanhas com `starts_at` vencido.
- `php artisan clientloop:expire-trials` suspende trials vencidos.
- Cada plano limita clientes e tarefas mensais. A geração é idempotente e opt-out cancela qualquer tarefa pendente.
- Execute `php artisan test` e `vendor/bin/pint --test` antes de publicar alterações.
