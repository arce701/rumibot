# Rumibot

Multi-tenant SaaS platform where Latin American businesses configure AI chatbots for WhatsApp. Each tenant uploads their company info, sets up Sales and Support channels with independent WhatsApp numbers, and the AI bot handles prospects and customers automatically.

## What It Does

- **Sales Channel** — Persuasive bot that answers product questions, shares catalogs/media, captures lead data, qualifies prospects, and escalates high-intent leads to human agents
- **Support Channel** — Patient bot that teaches product usage step-by-step, resolves FAQs, shares instructional media, and escalates complex issues to humans
- **Knowledge Base (RAG)** — Upload PDFs/docs, auto-processed into chunks with pgvector embeddings for semantic search
- **Integrations** — Outbound webhooks (n8n, Zapier, Make) with HMAC-SHA256 signing + REST API with Sanctum tokens
- **Billing** — Subscriptions via MercadoPago (quarterly, semi-annual, annual) + manual payments
- **Platform Admin** — Super-admin panel for global tenant/plan/billing management
- **i18n** — Full Spanish, English, and Brazilian Portuguese support (UI + enum labels)

## Stack

| Component | Technology |
|-----------|------------|
| Framework | Laravel 12 |
| Frontend | Livewire 4 + Flux UI Free v2 + Tailwind CSS v4 |
| Database | PostgreSQL + pgvector |
| AI | Laravel AI SDK (OpenAI, Anthropic, Gemini) |
| Auth | Laravel Fortify (headless + 2FA) + Sanctum |
| WhatsApp | YCloud (Business API) |
| Payments | MercadoPago |
| Testing | Pest 4 (323+ tests) |
| Queues | Database driver (Redis + Horizon planned) |
| Feature Flags | Laravel Pennant |
| Monitoring | Laravel Pulse |
| Exports | Maatwebsite Excel |
| Backups | Spatie Laravel Backup (S3) |

## Requirements

- PHP 8.4+
- PostgreSQL 15+ with pgvector extension
- Node.js 20+
- Composer 2+

## Installation

```bash
git clone <repo-url> rumibot
cd rumibot

composer install
npm install

cp .env.example .env
php artisan key:generate

# Configure PostgreSQL + AI provider keys in .env, then:
php artisan migrate --seed
npm run build

# Start queue worker (required for WhatsApp message processing)
php artisan queue:work --queue=high,default,low
```

## Development

```bash
# Dev server (Herd/Valet auto-serves at rumibot.test)
npm run dev

# Run tests
php artisan test --compact

# Format code
vendor/bin/pint

# Queue worker
php artisan queue:work --queue=high,default,low
```

## Project Structure

```
app/
├── Ai/              # TenantChatAgent, tools (CaptureLead, EscalateToHuman, SendMedia)
├── Exports/         # Excel exports (Leads, Conversations)
├── Http/
│   ├── Controllers/ # Webhook controllers (WhatsApp, Payments, Automation API)
│   └── Middleware/   # Tenant, auth, locale, logging
├── Jobs/            # ProcessIncomingMessage, SendWhatsApp, ProcessDocument, DispatchIntegration
├── Livewire/        # UI components (20 pages: Dashboard, Channels, Conversations, Leads, Billing, Platform...)
├── Models/          # 16 models + 12 enums with label() translations
├── Services/        # Tenant, WhatsApp, Document, Billing, Discord
├── Events/          # Business events (ConversationStarted, LeadCaptured, etc.)
└── Listeners/       # Dispatch events to tenant integrations
lang/
├── {en,es,pt_BR}.json    # Custom UI translation keys (~150 keys)
└── {en,es,pt_BR}/enums.php  # Translated enum labels (12 groups)
docs/
├── architecture-map.md  # Technical reference (architecture, file index, patterns, how to extend)
├── session-claude.md    # Development session log (context for AI-assisted development)
└── uso.md               # Quick start guide
```

## Documentation

- **[docs/architecture-map.md](docs/architecture-map.md)** — Complete technical reference: architecture, file index, patterns, common operations, and how to extend the platform
- **[docs/session-claude.md](docs/session-claude.md)** — Development session history for continuity across AI-assisted sessions
- **[docs/uso.md](docs/uso.md)** — Quick start guide for using the platform

## Tests

323+ tests covering tenant isolation (11 models), WhatsApp webhooks, AI agent + tools, RAG pipeline, billing/subscriptions, rate limiting, security, exports, auth/2FA, and super-admin panel.

```bash
php artisan test --compact
```

## License

Proprietary. All rights reserved.
