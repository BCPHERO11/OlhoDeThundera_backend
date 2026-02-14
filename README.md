# Prova-CBMSE-2026
---
## Como rodar backend e frontend
---
### Endpoint para teste da integração externa

Com a aplicação Laravel em execução local, o POST para criação da ocorrência externa deve ser feito em:

`POST http://localhost:8000/api/integrations/external-occurrences`

Headers obrigatórios:

- `X-Api-Key: <INTEGRATION_API_KEY>`
- `Idempotency-Key: <chave-unica-por-requisicao>`

Exemplo de payload:

```json
{
  "externalId": "f7f7e6fb-39ff-4b03-bfe6-5dd58f2cabf4",
  "description": "Ocorrência de teste",
  "type": "1",
  "reportedAt": "2026-02-14 10:00:00"
}
```

## Desenho de arquitetura
---
## Estratégia de integração externa
---
## Estratégia de idempotência
---
## Estratégia de concorrência
---
## Pontos de falha e recuperação
---
## O que ficou de fora
---
## Como o sistema poderia evoluir na corporação
---
