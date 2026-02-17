# 🧪 Sistema de Gestão de Ocorrências — Corpo de Bombeiros
Este repositório contém a implementação do desafio técnico de **Sistema de Gestão de Ocorrências**, inspirado em um cenário real de corporação pública crítica.


A solução foi construída com:

* **Backend:** Laravel (PHP 8+)
* **Banco de Dados:** PostgreSQL
* **Fila:** Redis (processamento assíncrono obrigatório)
* **Frontend:** React
* **Orquestração:** Docker + Docker Compose
---
# 🚀 Como rodar backend e frontend
## ✅ Pré-requisitos

* Docker
* Docker Compose

Não é necessário instalar PHP, Node ou Redis localmente.

Clonar os repositórios: 
```bash
git clone https://github.com/BCPHERO11/OlhoDeThundera_frontend.git
```
```bash
git clone https://github.com/BCPHERO11/OlhoDeThundera_backend.git
```
⚠️ **Lembre-se de duplicar a env.example para .env no back** ⚠️

---
## 🐳 Subindo o ambiente
```bash
docker compose up -d --build
```
Isso tanto no repositório de front como de backend irá subir:

* `app` → Laravel (API)
* `db` → PostgreSQL
* `redis` → Redis
* `frontend` → React

## 🔧 Backend

Acessar o container:
```bash
docker exec -it back_thundera bash
```

Instalar as dependências do backend:
```bash
composer install
```

Rodar migrations:
```bash
php artisan migrate
```

Iniciar o worker:

```bash
php artisan queue:work
```

## Acessos
A aplicação React estará disponível em:

```
http://localhost:5173
```

A API estará disponível em:

```
http://localhost:8070/api
```


---
# 📦 Desenho de arquitetura

![Desenho da Arquitetura](./OlhoDeThundera.drawio.png)

A arquitetura foi projetada para atender explicitamente aos requisitos de:

* Processamento assíncrono (`202 Accepted`)
* Idempotência forte com `Idempotency-Key`
* Controle de concorrência
* Auditoria completa
* Separação clara entre aceitação e execução

A API apenas **aceita** o comando e o registra.
O processamento real ocorre em background via **worker**.
---

# 📡 Estratégia de integração externa


Endpoint:

```
POST /api/integrations/occurrences
```

Headers obrigatórios:

```
X-API-Key
Idempotency-Key
```

Fluxo:

1. Valida autenticação
2. Valida payload
3. Persiste registro na tabela `command_inbox` com status `pending`
4. Enfileira Job

Resposta HTTP:

```
202 Accepted
```

A decisão de usar `202` está alinhada com o significado formal: requisição aceita, mas processamento não concluído.

---


## Estratégia de idempotência
## Estratégia de concorrência
## Pontos de falha e recuperação
## O que ficou de fora
## Como o sistema poderia evoluir na corporação


Rodar testes:

```bash
docker compose exec app php artisan test
```

#  Estratégia de Integração Externa
# 🔁 Estratégia de Idempotência

A idempotência é garantida via tabela **Command/Event Inbox**.

Escopo de unicidade:

```
idempotency_key + type + external_id
```

## Como funciona:

* Cada requisição externa gera um registro na inbox.
* Existe constraint única no banco para evitar duplicação.
* O payload é armazenado integralmente.
* Um hash/fingerprint do payload é comparado caso a mesma key reapareça.

## Cenários tratados:

| Situação                            | Comportamento           |
| ----------------------------------- | ----------------------- |
| Retry com mesma key e mesmo payload | Retorna mesmo commandId |
| Mesma key com payload diferente     | 422 Unprocessable       |
| Mesma key em processamento          | 409 Conflict            |

## Armazenamento da chave

* Persistida no banco
* Retenção indefinida (pode ser evoluído para política de expiração)
* Serve como trilha auditável

---

# 🔒 Estratégia de Concorrência

O sistema se protege contra:

* Eventos simultâneos
* Transições inválidas de estado

## Medidas adotadas

### 1️⃣ Constraint de unicidade

`external_id` possui índice único no banco.

Isso impede duplicidade sob concorrência real.

---

### 2️⃣ Transações com lock por linha

Durante mudança de status:

```sql
SELECT ... FOR UPDATE
```

Isso serializa alterações na mesma ocorrência.

---

### 3️⃣ Jobs sem sobreposição

Utilização de:

* Middleware `WithoutOverlapping`
* Locks distribuídos via Redis

Evita que dois workers processem o mesmo agregado simultaneamente.

---

### 4️⃣ Máquina de estados

Transições válidas:

Occurrence:

* reported → in_progress
* in_progress → resolved
* qualquer → cancelled (exceto resolved)

Dispatch:

* assigned → en_route → on_site → closed

Transições inválidas geram erro e não alteram estado.

---

# 📝 Estratégia de Auditoria

Toda mudança de status em:

* Occurrence
* Dispatch

Gera registro na tabela `audit_logs` contendo:

* before
* after
* action
* origem
* correlation_id

Isso garante rastreabilidade completa.

---

# 📊 Observabilidade

Cada comando possui:

* `commandId`
* `source`
* `status`
* `processed_at`
* `error`

Logs estruturados incluem:

* commandId
* occurrenceId
* idempotencyKey

Possível evolução futura: integração com OpenTelemetry.

# 🖥 Frontend

Interface React com:

* Lista de ocorrências
* Filtro por status e tipo
* Detalhe da ocorrência
* Histórico de dispatches
* Status atual

Fluxo com `202 Accepted`:

1. Ação dispara POST
2. Recebe `commandId`
3. UI atualiza para "processando"
4. Polling atualiza estado após processamento

---

# 🧪 Testes Automatizados

Cobertura mínima implementada:

1. ✅ Idempotência da integração
2. ✅ Transição válida/inválida
3. ✅ Geração de audit log
4. ✅ Concorrência simulada

Executar:

```bash
docker compose exec app php artisan test
```

---

# ⚠️ Pontos de Falha e Recuperação

| Falha               | Mitigação             |
| ------------------- | --------------------- |
| Worker cai          | Job permanece na fila |
| Banco indisponível  | Retry com backoff     |
| Payload inválido    | Status failed + log   |
| Duplicidade externa | Idempotência          |

---

# 🚧 O que Ficou de Fora

* Autenticação com OAuth/JWT
* Sistema de permissões por perfil
* Observabilidade completa (tracing distribuído)
* Dashboard operacional avançado
* Cache para leitura

---

# 🔮 Evolução na Corporação

Possíveis evoluções:

* Integração com sistemas estaduais
* API pública de consulta
* Métricas operacionais (SLA, tempo resposta)
* Georreferenciamento
* Painel em tempo real
* Multi-tenancy para batalhões
* Event streaming (Kafka)

---

# 🧠 Decisões Arquiteturais

O sistema foi projetado para:

* Ser resiliente a retries
* Operar com múltiplos workers
* Garantir integridade sob concorrência
* Fornecer trilha auditável completa
* Permitir escalabilidade horizontal

---

# 📌 Conclusão

Esta implementação atende aos requisitos obrigatórios:

* ✔ Processamento assíncrono real
* ✔ Idempotência forte
* ✔ Proteção contra concorrência
* ✔ Auditoria completa
* ✔ Frontend funcional
* ✔ Testes automatizados
* ✔ Ambiente totalmente dockerizado

O projeto foi pensado para refletir desafios reais de sistemas públicos críticos.

---

🚒🔥 Obrigado pela oportunidade de participar deste desafio técnico.
