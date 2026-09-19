# ClientLoop

SaaS para empresas de serviços organizarem agenda, retornos, reativação e oportunidades. O envio por WhatsApp é manual nesta versão: o painel abre uma conversa `wa.me` e o atendente registra o resultado.

## Início rápido

1. Copie `.env.example` para `.env` e preencha `APP_KEY`.
2. Inicie o Docker Desktop e execute `docker compose up --build`.
3. Em outro terminal, execute `docker compose exec app php artisan migrate --seed`.
4. Abra `http://localhost:8000/register`, crie a empresa e confirme o e-mail no Mailpit (`http://localhost:8025`).

O painel da empresa fica em `/app` e o painel reservado a superadmins fica em `/super`. Para promover uma conta existente, atualize `users.is_super_admin` para `true` diretamente no banco durante a fase inicial.

## Operação

- `php artisan clientloop:generate-tasks` gera confirmações, recalls e follow-ups; o scheduler Docker o executa de hora em hora.
- Cada plano limita clientes e tarefas mensais. A geração é idempotente e opt-out cancela qualquer tarefa pendente.
- Execute `php artisan test` e `vendor/bin/pint --test` antes de publicar alterações.
