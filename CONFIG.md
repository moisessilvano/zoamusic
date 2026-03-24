# LOUVOR.NET — Guia de Configuração

## Pré-requisitos

- PHP 8.2+
- MySQL 8.0+
- Extensões PHP: `pdo_mysql`, `curl`, `mbstring`, `openssl`
- Servidor web: Apache/Nginx ou `php -S localhost:8080`

---

## 1. Banco de Dados

As credenciais já estão configuradas em `config.php`:

| Parâmetro | Valor |
|-----------|-------|
| Host | `108.181.92.73` |
| Database | `louvor-db` |
| User | `louvor-user` |
| Password | `aG2vH9c@2CtkDZ68` |

**Executar o schema:**
```bash
mysql -h 108.181.92.73 -u louvor-user -p'aG2vH9c@2CtkDZ68' 'louvor-db' < db/schema.sql
```

---

## 2. Anthropic (Claude) — Letra da Música

1. Crie sua conta em [console.anthropic.com](https://console.anthropic.com)
2. Gere uma API Key em **API Keys**
3. Em `config.php`, substitua:

```php
define('ANTHROPIC_API_KEY', 'sk-ant-SUBSTITUA_AQUI');
```

**Modelo utilizado:** `claude-sonnet-4-6` (configurável via `ANTHROPIC_MODEL`)

---

## 3. PiAPI — Geração de Áudio (Suno)

1. Acesse [piapi.ai](https://piapi.ai) e crie uma conta
2. Obtenha sua API Key no dashboard
3. Em `config.php`, substitua:

```php
define('PIAPI_KEY', 'SUBSTITUA_AQUI');
```

**Endpoint usado:** `https://api.piapi.ai/api/suno/v1/music`

> **Nota:** A PiAPI leva entre 1–5 minutos para gerar o áudio. O sistema faz polling automático a cada 5 segundos por até 5 minutos.

---

## 4. Asaas — Pagamento PIX

1. Crie uma conta sandbox em [sandbox.asaas.com](https://sandbox.asaas.com)
2. Acesse **Integrações > Chaves de API**
3. Em `config.php`, substitua:

```php
define('ASAAS_API_KEY', '$aact_SUBSTITUA_AQUI');
define('ASAAS_ENV', 'sandbox'); // mude para 'production' ao ir ao ar
```

**Para produção:** Altere `ASAAS_ENV` para `'production'` e use a chave de produção do Asaas.

---

## 5. URL Base da Aplicação

Ajuste `BASE_URL` para o endereço real do servidor:

```php
// Desenvolvimento local
define('BASE_URL', 'http://localhost:8080');

// Produção
define('BASE_URL', 'https://louvor.net');
```

---

## 6. Rodar Localmente

```bash
cd /caminho/para/sass-gospel
php -S localhost:8080
```

Acesse: [http://localhost:8080](http://localhost:8080)

---

## 7. Estrutura de Arquivos

```
sass-gospel/
├── index.php              # Landing Page (Tela 1)
├── checkout.php           # Pagamento PIX (Tela 2)
├── confirmar_pagamento.php # Processa confirmação (POST)
├── processando.php        # Loading + polling (Tela 3)
├── ouvir.php              # Player final (Tela 4)
├── config.php             # ⚙ Configurações e credenciais
├── db.php                 # Conexão PDO + helper UUID
├── api/
│   ├── check_status.php   # Endpoint de polling (JS)
│   └── gerar_musica.php   # Worker background (Claude + PiAPI)
├── includes/
│   ├── claude.php         # Integração Anthropic
│   ├── piapi.php          # Integração PiAPI/Suno
│   └── asaas.php          # Integração Asaas PIX
└── db/
    └── schema.sql         # Schema MySQL
```

---

## 8. Fluxo Completo

```
Usuário preenche inspiração (index.php)
    ↓
Registro criado no DB (status: aguardando_pagamento)
    ↓
QR Code PIX gerado via Asaas (checkout.php)
    ↓
Usuário clica "Já Paguei" → POST confirmar_pagamento.php
    ↓
Status → 'processando', redirect → processando.php
    ↓
Worker gerar_musica.php (background):
    1. Claude gera letra (salva no DB)
    2. PiAPI dispara geração de áudio (salva task_id)
    3. Polling interno até audio_url disponível
    ↓
JS polling (check_status.php) detecta status='concluido'
    ↓
Redirect automático → ouvir.php?uid={uuid}
```

---

## 9. Segurança — Checklist para Produção

- [ ] Mover `config.php` para fora do webroot ou proteger com `.htaccess`
- [ ] Ativar HTTPS (Let's Encrypt)
- [ ] Configurar webhook Asaas para confirmação automática de pagamento
- [ ] Implementar rate limiting no `index.php`
- [ ] Adicionar CPF/email real do comprador no fluxo de checkout
- [ ] Revisar `ASAAS_ENV` → `'production'`
