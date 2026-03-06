# Rumibot - Architecture Map

> AI-readable reference for maintaining, debugging, and extending the platform.

---

## Mission

**EN:** Empower Latin American businesses to automate their WhatsApp sales and customer support through AI-powered chatbots — enabling them to capture more leads, resolve customer issues instantly, and scale their communication without growing their team. Each business brings its own AI provider and knowledge base, keeping full control over costs and data.

**ES:** Empoderar a las empresas latinoamericanas para automatizar sus ventas y atención al cliente por WhatsApp mediante chatbots impulsados por inteligencia artificial — permitiéndoles capturar más prospectos, resolver consultas al instante y escalar su comunicación sin necesidad de ampliar su equipo. Cada empresa trae su propio proveedor de IA y base de conocimiento, manteniendo control total sobre costos y datos.

### Core Objectives

**1. Sales Prospecting / Prospección comercial**

**EN:** Automate lead generation through an AI-powered sales agent on WhatsApp. The bot answers product questions, shares catalogs and media, identifies purchase intent, captures prospect data, and escalates high-value leads to human sales representatives — turning every conversation into a potential sale.

**ES:** Automatizar la generación de prospectos mediante un agente de ventas con IA en WhatsApp. El bot responde consultas sobre productos, comparte catálogos y material multimedia, identifica intención de compra, captura datos de contacto y escala los prospectos de alto valor a representantes de ventas humanos — convirtiendo cada conversación en una oportunidad de venta.

**2. Technical Support / Soporte técnico**

**EN:** Provide instant, 24/7 technical support for products and services that require post-sale assistance. The bot guides customers step by step through troubleshooting, shares instructional media, resolves frequently asked questions, and escalates complex issues to human support agents — reducing response times and improving customer satisfaction.

**ES:** Brindar soporte técnico inmediato y disponible 24/7 para productos y servicios que requieren asistencia postventa. El bot guía a los clientes paso a paso en la resolución de problemas, comparte material instructivo, resuelve preguntas frecuentes y escala los casos complejos a agentes de soporte humanos — reduciendo tiempos de respuesta y mejorando la satisfacción del cliente.

---

## What Is Rumibot?

A **multi-tenant SaaS** platform where Latin American businesses configure AI chatbots for WhatsApp. Each tenant (business) uploads their company info, configures Sales and Support WhatsApp channels, and the AI bot handles prospects and customers automatically.

**Stack:** Laravel 12, Livewire 4, Flux UI Free v2, Pest 4, PostgreSQL + pgvector, Tailwind CSS v4.

---

## Table of Contents

1. [Multi-Tenancy](#1-multi-tenancy)
2. [Authentication & Authorization](#2-authentication--authorization)
3. [WhatsApp Integration](#3-whatsapp-integration)
4. [AI Agent System](#4-ai-agent-system)
5. [Knowledge Base (RAG)](#5-knowledge-base-rag)
6. [Leads & Escalations](#6-leads--escalations)
7. [External Integrations & API](#7-external-integrations--api)
8. [Billing & Subscriptions](#8-billing--subscriptions)
9. [Super-Admin Platform](#9-super-admin-platform)
10. [Feature Flags](#10-feature-flags)
11. [Exports](#11-exports)
12. [Logging & Monitoring](#12-logging--monitoring)
13. [Security & Hardening](#13-security--hardening)
14. [Background Jobs](#14-background-jobs)
15. [Internationalization](#15-internationalization)
16. [File Index](#16-file-index)
17. [Testing](#17-testing)
18. [Common Operations](#18-common-operations)

---

## 1. Multi-Tenancy

**Pattern:** Single database, `tenant_id` column on all tenant-scoped tables, enforced via a global scope.

### Key Files

| File | Purpose |
|------|---------|
| `app/Models/Concerns/BelongsToTenant.php` | Trait applied to all tenant-scoped models. Registers `TenantScope` and auto-assigns `tenant_id` on create. |
| `app/Models/Scopes/TenantScope.php` | Global scope that adds `WHERE tenant_id = ?` to all queries. |
| `app/Services/Tenant/TenantContext.php` | Singleton service. Holds the current tenant. Called by middleware to set context. Also calls `setPermissionsTeamId()` for Spatie Permission. |
| `app/Http/Middleware/SetCurrentTenant.php` | Web middleware. Reads `auth()->user()->current_tenant_id`, resolves tenant, calls `TenantContext::set()`. |
| `app/Http/Middleware/EnsureTenantContext.php` | Aborts 403 if no tenant context is set (guards tenant-only routes). |
| `app/Http/Middleware/SetTenantFromToken.php` | API middleware. Resolves tenant from Sanctum token's user. |

### Tenant-Scoped Models (12 models)

All use the `BelongsToTenant` trait and are filtered by `TenantScope`:

`Channel`, `Conversation`, `Message`, `Lead`, `Escalation`, `KnowledgeDocument`, `KnowledgeChunk`, `TenantIntegration`, `Subscription`, `SubscriptionUsage`, `PaymentHistory`, `LlmCredential`

### Cross-Tenant Queries

Use `::withoutGlobalScopes()` when you need to query across tenants (e.g., super-admin panel, webhooks, scheduled commands).

### Important: Webhook Routes Are Stateless

WhatsApp webhook routes (`/api/webhooks/whatsapp/{tenantUuid}`) resolve the tenant from the URL UUID and the channel from the `phone_number_id` in the Meta payload, not from auth. See `WhatsAppWebhookController`.

---

## 2. Authentication & Authorization

### Auth Stack

| Component | Implementation |
|-----------|---------------|
| Auth backend | Laravel Fortify (headless) |
| 2FA | Fortify TOTP (`FortifyServiceProvider`) |
| Registration | `App\Actions\Fortify\CreateNewUser` — creates Tenant + User + pivot + role in a DB transaction. Includes `company_name` field. |
| Password reset | `App\Actions\Fortify\ResetUserPassword` |
| API auth | Laravel Sanctum (token-based) |

### Roles & Permissions

**Package:** Spatie Laravel Permission v7 with **teams enabled** (`team_foreign_key => 'tenant_id'`).

| Role | Scope | Key Permissions |
|------|-------|-----------------|
| `tenant_owner` | Per-tenant | All permissions (manage-channels, manage-billing, manage-team, etc.) |
| `tenant_admin` | Per-tenant | Same as owner except cannot delete tenant or transfer ownership |
| `tenant_member` | Per-tenant | View-only (view-conversations, view-leads, view-analytics) |

**Super-admin** is NOT a Spatie role. It uses `users.is_super_admin` boolean + `Gate::before()` in `AppServiceProvider`:
```php
Gate::before(fn (User $user) => $user->isSuperAdmin() ? true : null);
```

**Seeder:** `database/seeders/RolesAndPermissionsSeeder.php` creates all roles and permissions.

### Key Pattern: Setting Team ID

Before any `assignRole()` or permission check, you MUST set the team ID:
```php
app()[PermissionRegistrar::class]->setPermissionsTeamId($tenant->id);
```
This is done automatically by `SetCurrentTenant` middleware. In tests, you must do it manually.

---

## 3. WhatsApp Integration

### Message Flow (End-to-End)

```
┌─────────────┐     ┌──────────────────┐     ┌──────────────────────┐     ┌────────────────────┐
│  Prospect    │     │  Meta Cloud API  │     │      Rumibot         │     │    AI Provider     │
│  (WhatsApp)  │     │  (graph.facebook │     │   (Laravel App)      │     │  (OpenAI/etc.)     │
│              │     │   .com/v21.0)    │     │                      │     │                    │
└──────┬───────┘     └────────┬─────────┘     └──────────┬───────────┘     └─────────┬──────────┘
       │                      │                          │                           │
       │  1. Sends message    │                          │                           │
       │ ──────────────────►  │                          │                           │
       │                      │  2. Webhook POST         │                           │
       │                      │ ──────────────────────►  │                           │
       │                      │                          │  3. Validate & parse      │
       │                      │                          │  (WhatsAppWebhookHandler) │
       │                      │                          │                           │
       │                      │                          │  4. Queue job             │
       │                      │                          │  (ProcessIncomingMessage) │
       │                      │                          │                           │
       │                      │                          │  5. Build context         │
       │                      │                          │  (conversation history +  │
       │                      │                          │   system prompt + RAG)    │
       │                      │                          │                           │
       │                      │                          │  6. Send to AI            │
       │                      │                          │ ──────────────────────►   │
       │                      │                          │                           │
       │                      │                          │  7. AI response           │
       │                      │                          │ ◄────────────────────────  │
       │                      │                          │                           │
       │                      │  8. Send reply via API   │                           │
       │                      │ ◄────────────────────────│                           │
       │                      │  (SendWhatsAppMessage)   │                           │
       │  9. Delivers reply   │                          │                           │
       │ ◄────────────────────│                          │                           │
       │                      │                          │                           │
```

**Summary:** Prospect sends WhatsApp message → Meta delivers webhook to Rumibot → AI generates response → Rumibot sends reply via Meta Cloud API → Prospect receives answer on WhatsApp.

**Human Intervention:** Operators can reply to conversations directly from the web UI. When a human sends a reply, the AI is automatically paused for 24 hours (`ai_paused_until` on `conversations`). Incoming messages are still stored but no AI response is generated while paused. The operator can resume AI at any time via the "Resume AI" button.

### WhatsApp Provider (Meta Cloud API Direct)

The system connects directly to Meta's Cloud API (`graph.facebook.com/v21.0`) — no intermediary BSP. Each tenant provides their own Meta WhatsApp Business access token and phone number IDs.

- **Webhook:** One URL per tenant: `POST /api/webhooks/whatsapp/{tenantUuid}`
- **Verify token:** Deterministic `hash_hmac('sha256', tenantUuid, APP_KEY)` — no DB column needed
- **Channel resolution:** By `phone_number_id` from the Meta webhook payload metadata
- **Auth:** `Authorization: Bearer {accessToken}` per channel

**For Peru/LATAM:** Meta verification typically requires RUC, ficha SUNAT, and a business address document. Processing takes ~24-48 hours.

### Key Files

| File | Purpose |
|------|---------|
| `app/Services/WhatsApp/Contracts/WhatsAppProvider.php` | Interface: `sendText()`, `sendImage()`, `sendDocument()`, `sendInteractive()`, `parseInboundMessage()` |
| `app/Services/WhatsApp/MetaCloudProvider.php` | Meta Cloud API implementation. Uses `graph.facebook.com/v21.0`. Has HTTP retry (`->retry(2, 100, throw: false)`). |
| `app/Services/WhatsApp/WhatsAppWebhookHandler.php` | Resolves channel by `phone_number_id`, validates and parses incoming Meta webhook payloads |
| `app/Services/WhatsApp/InboundMessage.php` | DTO for normalized inbound messages |
| `app/Http/Controllers/WhatsAppWebhookController.php` | Handles GET (verify) and POST (receive) webhook requests |
| `app/Models/Channel.php` | WhatsApp channel config. `provider_api_key` is encrypted via cast. Slug auto-generated from name. |
| `app/Models/Conversation.php` | Tracks conversations with contacts |
| `app/Models/Message.php` | Immutable message log (role: user/assistant/system) |

### Webhook URL Format
```
GET/POST /api/webhooks/whatsapp/{tenantUuid}
```
Channel is resolved by `phone_number_id` in the Meta payload metadata, not by URL parameter.

### Channel Types

Defined in `app/Models/Enums/ChannelType.php`:
- `Sales` - Pre-sale prospect management (persuasive bot)
- `Support` - Post-sale customer support (patient, didactic bot)

---

## 4. AI Agent System

**Package:** `laravel/ai` (Laravel AI SDK)

### Key Files

| File | Purpose |
|------|---------|
| `app/Ai/Agents/TenantChatAgent.php` | Main production agent. Implements `Agent`, `Conversational`, `HasTools`, `HasMiddleware`. |
| `app/Ai/Agents/PlaygroundChatAgent.php` | Testing agent for Agent Playground. Same prompts as TenantChatAgent but only `SimilaritySearch` tool (no side effects). No `HasMiddleware` (no token tracking). |
| `app/Ai/Tools/CaptureLead.php` | Tool: extracts prospect data, saves as Lead. Only active on `sales` channels. |
| `app/Ai/Tools/EscalateToHuman.php` | Tool: triggers human escalation |
| `app/Ai/Tools/SendMedia.php` | Tool: sends images, documents, video links via WhatsApp |
| `app/Ai/Middleware/TrackTokenUsage.php` | Agent middleware: records token counts on the conversation |
| `app/Models/LlmCredential.php` | Stores encrypted API keys per provider per tenant. Uses `BelongsToTenant` trait. |

### LLM Credential System (Mandatory)

**There are NO env-level default providers or API keys.** Every tenant MUST configure an LLM credential before using AI features. The system will NOT fall back to any environment variable.

| Concept | Implementation |
|---------|---------------|
| `LlmCredential` | Stores `provider` (AiProvider enum), `api_key` (encrypted), `name` per tenant |
| Default credential | `Tenant.default_llm_credential_id` → FK to `llm_credentials` |
| Default model | `Tenant.default_ai_model` (string, e.g. "gpt-4o-mini") |
| AI settings | `Tenant.ai_temperature`, `ai_max_tokens`, `ai_context_window`, `ai_streaming` |
| Runtime config | Before calling `prompt()`, the API key is set via `config()->set("ai.providers.{provider}.key", $apiKey)` |
| No credential → no AI | `ProcessIncomingMessage` returns early with log warning. `AgentPlayground` shows friendly message. |

### Agent Instructions (Multi-Layer Prompts)

```
Base instructions + Tenant.system_prompt + Channel.system_prompt_override + Country context
```

The `system_prompt_override` on the channel defines whether the bot acts as a salesperson or support agent.

**Country context** is auto-appended when the prospect's country can be detected from their phone number (E.164 prefix matching). This tells the AI the prospect's country so it doesn't ask redundantly, and helps with country-specific pricing/payment methods.

### Phone Number Formatting

WhatsApp `wa_id` numbers arrive as raw digits (e.g. `50234850199`). The `PhoneHelper` class (`app/Support/PhoneHelper.php`) handles:
- **Format:** `50234850199` → `+502 3485 0199` (Guatemala) — country-specific masks per country
- **Country detection:** `50234850199` → ISO `GT`, name `Guatemala`
- **Flag emoji:** `50234850199` → flag emoji
- Prefix matching: 4-digit NANP Caribbean (1809 DR, 1787 PR) → 3-digit LATAM (502-509, 591-598) → 2-digit (51-58) → 1-digit (US/Canada)
- Special handling for Mexico mobile (`521...` → `+52 1 ...`)
- Country names in Spanish with proper accents (Peru, Mexico, Panama, etc.)
- 27+ countries supported (all Hispanic LATAM + Brazil + common international)
- Global helper functions `format_phone()` and `phone_flag()` available in Blade views and exports
- Data stored as `public const COUNTRIES` (not `config()`) for unit test compatibility without framework boot

| File | Purpose |
|------|---------|
| `app/Support/PhoneHelper.php` | Core class: format, detect country, flags |
| `app/Support/helpers.php` | Global `format_phone()` and `phone_flag()` functions |
| `config/phone.php` | References `PhoneHelper::COUNTRIES` constant |

### Agent Tools

| Tool | Purpose | Active On |
|------|---------|-----------|
| `SimilaritySearch` | RAG search on `KnowledgeChunk` embeddings, filtered by `channel_scope` | All channels (production + playground) |
| `SendMedia` | Send images/docs/videos via WhatsApp | Production only |
| `CaptureLead` | Extract and save prospect info | Production, sales channels only |
| `EscalateToHuman` | Trigger escalation to human agent | Production only |

---

## 5. Knowledge Base (RAG)

### Pipeline

```
File upload → S3 storage → ProcessDocument job → TextExtractor → TextChunker → Embeddings::generate() → DB
```

### Key Files

| File | Purpose |
|------|---------|
| `app/Services/Document/DocumentProcessor.php` | Orchestrates: extract → chunk → embed → save |
| `app/Services/Document/TextExtractor.php` | Extracts text from PDFs (via `smalot/pdfparser`) and other formats |
| `app/Services/Document/TextChunker.php` | Splits text into ~500-token chunks |
| `app/Jobs/ProcessDocument.php` | Queued job for async document processing |
| `app/Models/KnowledgeDocument.php` | Document metadata, status tracking |
| `app/Models/KnowledgeChunk.php` | Individual chunks with `vector(1536)` embedding column |
| `app/Livewire/Knowledge/KnowledgeManager.php` | UI: upload files, view chunks, processing status |

### Embeddings

- **Engine:** pgvector extension on PostgreSQL
- **Dimensions:** 1536 (text-embedding-3-small)
- **Index:** HNSW on `knowledge_chunks.embedding`
- **Search:** `SimilaritySearch::usingModel(KnowledgeChunk::class, 'embedding')` as agent tool

### Channel Scope

Documents have a `channel_scope` JSONB field. Empty = all channels. Allows restricting docs to specific channels (e.g., product catalogs only for sales channel).

---

## 6. Leads & Escalations

### Key Files

| File | Purpose |
|------|---------|
| `app/Models/Lead.php` | Prospect data captured by AI. Statuses: `new`, `contacted`, `converted`, `lost`. |
| `app/Models/Escalation.php` | Human escalation requests. Has `assigned_to_user_id` and `resolved_at`. |
| `app/Livewire/Leads/LeadsList.php` | UI: filterable list with export capability |
| `app/Livewire/Escalations/EscalationQueue.php` | UI: escalation queue management |
| `app/Services/Discord/DiscordNotifier.php` | Sends Discord webhook notifications for escalations |

### Events Triggered

| Event | When |
|-------|------|
| `LeadCaptured` | AI agent captures prospect data |
| `EscalationTriggered` | AI agent triggers human escalation |

---

## 7. External Integrations & API

### Outbound Webhooks (Event-Driven)

```
Business event → Laravel Event → DispatchTenantIntegrationEvents listener → DispatchIntegrationEvent job → HTTP POST to integration URL
```

### Key Files

| File | Purpose |
|------|---------|
| `app/Models/TenantIntegration.php` | Integration config. `secret` is encrypted (cast). Supports n8n, Zapier, Make, custom. |
| `app/Events/` | 5 events: `ConversationStarted`, `MessageReceived`, `LeadCaptured`, `EscalationTriggered`, `ConversationClosed` |
| `app/Listeners/DispatchTenantIntegrationEvents.php` | Listens to events, dispatches to active integrations |
| `app/Jobs/DispatchIntegrationEvent.php` | Sends payload to integration URL with HMAC-SHA256 signature |
| `app/Livewire/Integrations/IntegrationManager.php` | UI: CRUD integrations (register, set primary, suspend, deactivate) |
| `app/Livewire/Integrations/ApiTokenManager.php` | UI: manage Sanctum API tokens |

### Inbound API (Sanctum-Authenticated)

| File | Purpose |
|------|---------|
| `app/Http/Controllers/Api/AutomationApiController.php` | REST endpoints for external tools (n8n/Zapier) to send messages, update leads, close conversations |
| `routes/api.php` | API v1 routes with `auth:sanctum` + `throttle:tenant-api` middleware |

### HMAC Signing

Outbound webhook payloads are signed with `hash_hmac('sha256', $payload, $integration->secret)`. The `secret` field is encrypted at rest via Laravel's `encrypted` cast.

---

## 8. Billing & Subscriptions

### Architecture

```
Tenant selects plan → SubscriptionManager → PaymentProvider (MercadoPago/Manual) → Subscription created
MercadoPago webhook → PaymentWebhookController → Update subscription status + PaymentHistory
```

### Key Files

| File | Purpose |
|------|---------|
| `app/Services/Billing/Contracts/PaymentProvider.php` | Interface: `createCustomer()`, `createSubscription()`, `cancelSubscription()`, `handleWebhook()` |
| `app/Services/Billing/MercadoPagoProvider.php` | MercadoPago implementation (LATAM) |
| `app/Services/Billing/ManualPaymentProvider.php` | Manual payments (bank transfer) |
| `app/Services/Billing/SubscriptionManager.php` | Lifecycle management (create, change plan, cancel, renew) |
| `app/Services/Billing/PlanFeatureGate.php` | Checks feature access based on active plan |
| `app/Http/Controllers/Api/PaymentWebhookController.php` | Receives MercadoPago/Stripe webhooks |
| `app/Models/Plan.php` | Plan definition (single plan "Rumibot" with 3 billing intervals) |
| `app/Models/PlanPrice.php` | Prices per billing interval (quarterly, semi-annual, annual) |
| `app/Models/PlanFeature.php` | Feature limits per plan (max_channels, max_messages_month, etc.) |
| `app/Models/Subscription.php` | Active tenant subscriptions |
| `app/Models/SubscriptionUsage.php` | Usage tracking per billing period |
| `app/Models/PaymentHistory.php` | Immutable payment audit log |
| `app/Livewire/Billing/SubscriptionManager.php` | UI: view/change plan, see usage |
| `app/Livewire/Billing/PaymentHistory.php` | UI: payment history |

### Plans & Pricing

Single plan: **Rumibot** — all features unlimited, no tiered restrictions. The only difference between billing options is the renewal period and discount:

| Interval | Price (USD) | Discount |
|----------|-------------|----------|
| Quarterly | $30 | — |
| Semi-annual | $55 | ~8% off |
| Annual | $110 | ~8% off |

All features included for every tenant: unlimited channels, messages, documents, team members, integrations, analytics, and data export. Tenants bring and pay for their own AI API keys.

**Platform owner exemption:** The first tenant (id=1, `is_platform_owner: true`) is the platform operator (RumiStar E.I.R.L.) and bypasses all billing. All other tenants must subscribe to one of the three billing intervals.

**Seeder:** `database/seeders/PlansSeeder.php`

---

## 9. Super-Admin Platform

### Routes

All under `/platform/*` with middleware `['auth', 'verified', 'super-admin']`. **No tenant context** (operates globally).

### Key Files

| File | Purpose |
|------|---------|
| `app/Http/Middleware/EnsureSuperAdmin.php` | Guards platform routes, requires `is_super_admin` |
| `app/Livewire/Platform/PlatformDashboard.php` | Global metrics (active tenants, revenue, messages, AI costs) |
| `app/Livewire/Platform/TenantIndex.php` | List all tenants with filters, suspend/reactivate |
| `app/Livewire/Platform/TenantDetail.php` | Tenant detail + "Enter as this tenant" (context switch) |
| `app/Livewire/Platform/PlanManager.php` | CRUD plans, prices, features |
| `app/Livewire/Platform/PlatformBilling.php` | Global billing overview |

### Context Switch

Super-admin can enter any tenant by setting `current_tenant_id` on their User model. While in tenant context, `Gate::before()` grants all permissions.

---

## 10. Feature Flags

**Package:** Laravel Pennant

### Defined Features

| Flag | Gated By | Controls |
|------|----------|----------|
| `knowledge-base` | `Tenant::hasActiveFeature('knowledge_base')` | Knowledge base access |
| `webhook-integrations` | `Tenant::hasActiveFeature('integrations')` | External integrations |
| `multi-channel` | `Tenant::hasActiveFeature('multi_channel')` | Multiple WhatsApp channels |
| `analytics-dashboard` | `Tenant::hasActiveFeature('analytics')` | Analytics access |
| `data-export` | `Tenant::hasActiveFeature('data_export')` | Excel/CSV exports |

**Registration:** `app/Providers/AppServiceProvider.php` in `boot()`.

**Usage in Blade:** `@feature('data-export')` ... `@endfeature`

**Usage in PHP:** `Feature::active('data-export')`

---

## 11. Exports

**Package:** Maatwebsite Excel 3.1

### Key Files

| File | Purpose |
|------|---------|
| `app/Exports/LeadsExport.php` | Exports leads to XLSX. Implements `FromQuery`, `WithHeadings`, `WithMapping`, `ShouldAutoSize`. Filters: status, search. |
| `app/Exports/ConversationsExport.php` | Exports conversations to XLSX. Same pattern. Filters: channel, status, search. |

### Triggering Exports

Exports are triggered from Livewire components and gated by the `data-export` feature flag:

- `LeadsList::exportLeads()` → downloads `leads-{date}.xlsx`
- `ConversationList::exportConversations()` → downloads `conversations-{date}.xlsx`

If the feature is not active, a flash error is shown: "Export not available on your plan."

---

## 12. Logging & Monitoring

### Tenant Context in Logs

| File | Purpose |
|------|---------|
| `app/Http/Middleware/SetLogContext.php` | Adds `tenant_id` and `user_id` to Laravel's `Context` facade. Applied to both web and API middleware. |
| `bootstrap/app.php` | Exception context enrichment via `$exceptions->context()` — adds `tenant_id` and `user_id` to all exception reports. |

### Job Logging

All 4 jobs add `Context::add('tenant_id', ...)` at the start of `handle()`:
- `ProcessIncomingMessage` → `$this->channel->tenant_id`
- `SendWhatsAppMessage` → `$this->conversation->tenant_id`
- `DispatchIntegrationEvent` → `$this->integration->tenant_id`
- `ProcessDocument` → `$this->document->tenant_id`

### Log Configuration

- Default stack channel uses `daily` rotation (`config/logging.php`)
- `LOG_STACK` env variable can override (default: `daily`)

### Activity Log

**Package:** Spatie Activity Log v4

Models with `LogsActivity` trait: `Tenant`, `Channel`, `KnowledgeDocument`, `Lead`, `Plan`, `Subscription`.

**Viewer:** `app/Livewire/ActivityLog/ActivityLogViewer.php`

### Pulse (Performance Monitoring)

**Package:** Laravel Pulse v1.5

- **Access:** `/pulse` route, restricted to super-admins via `Gate::define('viewPulse', ...)`
- **Config:** `config/pulse.php` — Recorders: Requests, SlowQueries, SlowJobs, Exceptions, Queues, Cache
- **Authorization:** `app/Providers/AppServiceProvider.php` → `configurePulse()` method

---

## 13. Security & Hardening

### Encrypted Fields

| Model | Field | Cast |
|-------|-------|------|
| `Channel` | `provider_api_key` | `encrypted` |
| `TenantIntegration` | `secret` | `encrypted` |
| `LlmCredential` | `api_key` | `encrypted` |

### Rate Limiting

Defined in `AppServiceProvider::configureRateLimiting()`:

| Limiter | Rate | Key |
|---------|------|-----|
| `tenant-api` | 60/min (auth) or 10/min (guest) | `tenant_id` or IP |
| `webhook-whatsapp` | 120/min | `tenantUuid` |
| `webhook-payments` | 60/min | IP |

Applied in `routes/api.php` as `throttle:{limiter-name}`.

### Tenant Isolation Audit

11 of 12 tenant-scoped models are tested for proper isolation in `tests/Feature/Security/TenantIsolationAuditTest.php` (`LlmCredential` is not yet included).

### Backups

**Package:** Spatie Laravel Backup v9

- **Destination:** S3
- **Databases:** PostgreSQL
- **Files included:** `.env`
- **Schedule:** `backup:clean` at 01:00, `backup:run` at 02:00 (daily)
- **Retention:** 7 days all backups, 30 days daily, 8 weeks weekly, 4 months monthly
- **Config:** `config/backup.php`
- **Schedule:** `routes/console.php`

### HMAC Webhook Signing

Outbound integration webhooks are signed with `hash_hmac('sha256', $jsonPayload, $decryptedSecret)`.

---

## 14. Background Jobs

| Job | Queue | Retry | Purpose |
|-----|-------|-------|---------|
| `ProcessIncomingMessage` | `high` | 5x, max 3 exceptions, 180s timeout. Catches `RateLimitedException` and releases with provider-aware delay (extracts `Retry-After` header or uses `AiProvider::rateLimitCooldownSeconds()`). Idempotent message storage via `whatsapp_message_id`. | Receives message → Agent → AI response → send reply |
| `SendWhatsAppMessage` | `high` | 3x | Sends message via WhatsApp provider |
| `DispatchIntegrationEvent` | `default` | 3x | Sends event payload to external integrations |
| `ProcessDocument` | `low` | 3x | Processes uploaded document (extract → chunk → embed) |

### Rate Limiting (ProcessIncomingMessage)

When the AI provider returns a rate limit error:
1. Job catches `RateLimitedException`
2. Extracts `Retry-After` header from the underlying HTTP response (if available)
3. Falls back to `AiProvider::rateLimitCooldownSeconds()` (60s for most providers, 30s for DeepSeek)
4. Releases the job with `$this->release($delay)` — does NOT count as an exception
5. User message is stored idempotently (checked by `whatsapp_message_id`) — never duplicated on retry

### Scheduled Commands

| Command | Schedule | Purpose |
|---------|----------|---------|
| `backup:clean` | Daily 01:00 | Clean old backups |
| `backup:run` | Daily 02:00 | Run backup to S3 |
| `app:retry-unanswered` | Every 2 hours | Find active conversations with unanswered user messages and generate AI responses |

The `app:retry-unanswered` command (`app/Console/Commands/RetryUnansweredConversations.php`) finds conversations where the last message is from the user with no subsequent assistant reply. For each, it loads the tenant's LLM credential, generates a response via `TenantChatAgent`, and dispatches `SendWhatsAppMessage`. Handles rate limits gracefully (logs warning and skips).

**Queue driver:** `database` (migration to Redis + Horizon planned "on demand").

**Worker command:** `php artisan queue:work --queue=high,default,low`

---

## 15. Internationalization

**Package:** `laravel-lang/common`

### Languages

| Code | Language |
|------|----------|
| `es` | Spanish (Latin America) — default |
| `en` | English |
| `pt_BR` | Brazilian Portuguese |

### How It Works

- `users.locale` → UI language for the admin panel
- `tenants.locale` → Bot language (injected into agent's system prompt)
- `SetLocale` middleware reads `auth()->user()->locale` and calls `App::setLocale()`

### Translation File Architecture

| File type | Purpose | Safe from `lang:update`? |
|-----------|---------|--------------------------|
| `lang/{locale}.json` | Custom UI strings (`__('Channels')`, `__('Save')`, etc.) | Yes — `lang:update` does soft merge, preserves custom keys |
| `lang/{locale}/enums.php` | Translated labels for all enums | Yes — `laravel-lang` never touches custom PHP files |
| `lang/{locale}/*.php` (auto-generated) | Framework/Fortify/validation translations | Managed by `laravel-lang` |

**Only `lang:reset` (destructive) would overwrite custom JSON keys. Normal `lang:update` is safe.**

### Translatable Enums

All enums in `app/Models/Enums/` have a `label()` method that returns a translated string:

```php
// Example: ChannelType.php
public function label(): string
{
    return __('enums.channel_type.' . $this->value);
}
```

Translation keys live in `lang/{locale}/enums.php` organized by group:

```php
// lang/es/enums.php
return [
    'channel_type' => ['sales' => 'Ventas', 'support' => 'Soporte'],
    'conversation_status' => ['active' => 'Activa', 'closed' => 'Cerrada', ...],
    // ... 12 groups total
];
```

**In Blade views, always use `$enum->label()` instead of `ucfirst($enum->value)`.**

### Translation Gotcha

`__('Messages')` returns the entire `lang/*/messages.php` array because Laravel resolves it as a translation group. Use `__('messages.key')` or keys that don't match filename basenames.

---

## 16. File Index

### Models (17)

| Model | Traits | Notes |
|-------|--------|-------|
| `User` | `HasRoles`, `SoftDeletes` | `is_super_admin`, `current_tenant_id`, `locale` |
| `Tenant` | `SoftDeletes`, `LogsActivity` | `is_platform_owner`, `system_prompt`, `default_llm_credential_id`, `default_ai_model`, AI settings |
| `LlmCredential` | `BelongsToTenant`, `SoftDeletes` | `api_key` encrypted, `provider` (AiProvider enum) |
| `Channel` | `BelongsToTenant`, `SoftDeletes`, `LogsActivity` | `provider_api_key` encrypted, slug auto-generated |
| `Conversation` | `BelongsToTenant`, `SoftDeletes` | Token tracking, `contact_country` (ISO 2-letter, auto-detected from phone) |
| `Message` | `BelongsToTenant` | Immutable, no soft delete |
| `Lead` | `BelongsToTenant`, `SoftDeletes`, `LogsActivity` | Captured by AI |
| `Escalation` | `BelongsToTenant`, `SoftDeletes` | Human escalation requests |
| `KnowledgeDocument` | `BelongsToTenant`, `SoftDeletes`, `LogsActivity` | Document metadata |
| `KnowledgeChunk` | `BelongsToTenant` | `vector(1536)` embedding |
| `TenantIntegration` | `BelongsToTenant`, `SoftDeletes` | `secret` encrypted |
| `Plan` | `SoftDeletes`, `LogsActivity` | Platform plans |
| `PlanPrice` | — | Per billing interval |
| `PlanFeature` | — | Feature limits |
| `Subscription` | `BelongsToTenant`, `SoftDeletes`, `LogsActivity` | Active subscriptions |
| `SubscriptionUsage` | `BelongsToTenant` | Usage tracking |
| `PaymentHistory` | `BelongsToTenant` | Immutable audit log |

### Enums (12)

All in `app/Models/Enums/`. Each enum has a `label(): string` method returning translated labels via `__('enums.{group}.{value}')`.

| Enum | Translation group | Values |
|------|-------------------|--------|
| `AiProvider` | `enums.ai_provider` | OpenAi, Anthropic, Gemini, Groq, DeepSeek, Mistral, XAi, OpenRouter. Has `models(): array` and `rateLimitCooldownSeconds(): int` methods. |
| `ChannelType` | `enums.channel_type` | Sales, Support |
| `ConversationStatus` | `enums.conversation_status` | Active, Closed, Escalated |
| `LeadStatus` | `enums.lead_status` | New, Contacted, Converted, Lost |
| `DocumentStatus` | `enums.document_status` | Pending, Processing, Ready, Failed |
| `IntegrationStatus` | `enums.integration_status` | Active, Suspended |
| `IntegrationProvider` | `enums.integration_provider` | N8n, Zapier, Make, Custom |
| `BillingInterval` | `enums.billing_interval` | Quarterly, SemiAnnual, Annual |
| `SubscriptionStatus` | `enums.subscription_status` | Active, Trialing, PastDue, Canceled, Expired |
| `PaymentStatus` | `enums.payment_status` | Pending, Completed, Failed, Refunded |
| `PaymentProviderType` | `enums.payment_provider` | MercadoPago, Stripe, Manual |
| `WebhookEvent` | `enums.webhook_event` | ConversationStarted, MessageReceived, LeadCaptured, EscalationTriggered, ConversationClosed |

### Livewire Components (22)

| Component | Path | Purpose |
|-----------|------|---------|
| `Dashboard` | `app/Livewire/Dashboard.php` | Tenant dashboard |
| `ChannelManager` | `app/Livewire/Channels/ChannelManager.php` | WhatsApp channel CRUD (simplified form, auto-slug) |
| `AiConfigManager` | `app/Livewire/AiConfig/AiConfigManager.php` | LLM credentials CRUD + model configuration |
| `PromptEditor` | `app/Livewire/Prompts/PromptEditor.php` | System prompt editing |
| `KnowledgeManager` | `app/Livewire/Knowledge/KnowledgeManager.php` | Document upload/management |
| `AgentPlayground` | `app/Livewire/Playground/AgentPlayground.php` | Test AI agent with RAG (in-memory chat, no persistence) |
| `ConversationList` | `app/Livewire/Conversations/ConversationList.php` | Filterable list + export |
| `ConversationDetail` | `app/Livewire/Conversations/ConversationDetail.php` | Chat view |
| `LeadsList` | `app/Livewire/Leads/LeadsList.php` | Lead list + export |
| `EscalationQueue` | `app/Livewire/Escalations/EscalationQueue.php` | Escalation management |
| `IntegrationManager` | `app/Livewire/Integrations/IntegrationManager.php` | Integration CRUD |
| `ApiTokenManager` | `app/Livewire/Integrations/ApiTokenManager.php` | Sanctum token management |
| `TeamManager` | `app/Livewire/Team/TeamManager.php` | Team + role management |
| `SubscriptionManager` | `app/Livewire/Billing/SubscriptionManager.php` | Plan management |
| `PaymentHistory` | `app/Livewire/Billing/PaymentHistory.php` | Payment log |
| `ActivityLogViewer` | `app/Livewire/ActivityLog/ActivityLogViewer.php` | Audit log viewer |
| `PlatformDashboard` | `app/Livewire/Platform/PlatformDashboard.php` | Global metrics |
| `TenantIndex` | `app/Livewire/Platform/TenantIndex.php` | All tenants list |
| `TenantDetail` | `app/Livewire/Platform/TenantDetail.php` | Tenant detail + context switch |
| `PlanManager` | `app/Livewire/Platform/PlanManager.php` | Plan CRUD |
| `PlatformBilling` | `app/Livewire/Platform/PlatformBilling.php` | Global billing |
| `Logout` | `app/Livewire/Actions/Logout.php` | Logout action |

### Middleware (6)

| Middleware | Applied To | Purpose |
|-----------|------------|---------|
| `SetCurrentTenant` | Web | Resolves tenant from authenticated user |
| `EnsureTenantContext` | Tenant routes | Requires active tenant |
| `EnsureSuperAdmin` | `/platform/*` | Requires `is_super_admin` |
| `SetLocale` | Web | Sets app locale from user preference |
| `SetTenantFromToken` | API | Resolves tenant from Sanctum token |
| `SetLogContext` | Web + API | Adds tenant_id/user_id to log context |

### Console Commands (1)

| Command | Signature | Purpose |
|---------|-----------|---------|
| `RetryUnansweredConversations` | `app:retry-unanswered` | Finds active conversations with unanswered user messages, generates AI responses, sends via WhatsApp |

### Routes (4 files)

| File | Purpose |
|------|---------|
| `routes/web.php` | Tenant routes (dashboard, channels, ai-config, prompts, knowledge, playground, conversations, leads, escalations, integrations, billing, team, activity) + Platform routes (`/platform/*`) |
| `routes/api.php` | WhatsApp webhooks, Payment webhooks, API v1 (Sanctum) |
| `routes/settings.php` | User profile, password, 2FA settings |
| `routes/console.php` | Scheduled commands (backup:clean, backup:run, app:retry-unanswered) |

### Config Files (21)

Key custom configs: `config/ai.php` (provider definitions), `config/rumibot.php` (only `ai.timeout` — no default provider/model), `config/phone.php` (country code map for phone formatting), `config/permission.php`, `config/backup.php`, `config/pulse.php`, `config/excel.php`

### Database (29 migrations, 3 seeders)

**Seeders:**
- `RolesAndPermissionsSeeder` — Roles + permissions for Spatie Permission
- `PlansSeeder` — Plans, prices, features
- `DatabaseSeeder` — Orchestrates all seeders + creates platform owner tenant + super-admin user

---

## 17. Testing

**Framework:** Pest 4 | **Database:** PostgreSQL (same as production)

### Test Suite (49 files, 432+ tests)

| Category | Files | What They Test |
|----------|-------|----------------|
| Auth | 6 | Login, registration (with tenant creation), password reset, email verification, 2FA |
| Settings | 3 | Profile update, password update, 2FA management |
| WhatsApp | 3 | Webhook validation, messaging, channel config |
| AI | 4 | Agent tools (incl. country auto-detect), ProcessIncomingMessage (incl. rate limiting, idempotency), TenantChatAgent (incl. country context), PlaygroundChatAgent |
| Knowledge | 2 | Document processing, agent knowledge search |
| Billing | 3 | Payment webhooks, plan feature gating, subscription lifecycle |
| Panel | 2 | Tenant panel access, CRUD operations |
| Platform | 2 | Super-admin access, plan management |
| Integrations | 3 | Automation API, integration event dispatch, event triggers |
| Security | 3 | Credential encryption, rate limiting, tenant isolation audit (12 models) |
| Hardening | 2 | Backup config, Pulse access |
| Exports | 2 | Leads export, conversations export |
| Logging | 1 | Tenant context in logs |
| Models | 1 | LlmCredential (UUID, encryption, tenant scope, soft deletes) |
| AiConfig | 1 | AiConfigManager (CRUD credentials, model settings, permissions) |
| Playground | 1 | AgentPlayground (chat, channel selection, permissions) |
| Conversations | 1 | ConversationDetail (human reply, AI pause/resume) |
| Other | 4 | Dashboard, tenant scoping, escalation notifications |
| Unit | 3 | TextChunker, PhoneHelper (48 tests: format, detect, flags, prefix priority, all LATAM countries), RetryUnansweredConversations |

### Running Tests

```bash
# All tests
php artisan test --compact

# Specific file
php artisan test --compact tests/Feature/Security/TenantIsolationAuditTest.php

# Filter by name
php artisan test --compact --filter="tenant scope"
```

### Key Test Patterns

```php
// Standard test setup for tenant-scoped tests
beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->tenant = Tenant::factory()->create();
    $this->user = User::factory()->create(['current_tenant_id' => $this->tenant->id]);
    $this->tenant->users()->attach($this->user->id, ['role' => 'tenant_owner', 'is_default' => true]);
    app()[PermissionRegistrar::class]->setPermissionsTeamId($this->tenant->id);
    $this->user->assignRole('tenant_owner');
});
```

---

## 18. Common Operations

### Adding a New Tenant-Scoped Model

1. Create model with `php artisan make:model ModelName -mf`
2. Add `BelongsToTenant` trait to the model
3. Add `tenant_id` UUID foreign key in migration
4. Create factory with `tenant_id` field
5. Add to `tenantScopedModels()` in `TenantIsolationAuditTest.php`

### Adding a New Livewire Page

1. Create component: `php artisan make:livewire Section/ComponentName --class`
2. Add `#[Layout('layouts::app')]` and `#[Title('Page Title')]` attributes
3. Add `AuthorizesRequests` trait and call `$this->authorize('permission')` if needed
4. Add route in `routes/web.php` within appropriate middleware group
5. Add navigation link in sidebar

### Adding a New Feature Flag

1. Define in `AppServiceProvider::boot()`:
   ```php
   Feature::define('feature-name', fn (Tenant $tenant) => $tenant->hasActiveFeature('feature_slug'));
   ```
2. Add `feature_slug` to `PlanFeature` entries via seeder
3. Gate in Blade: `@feature('feature-name')` ... `@endfeature`
4. Gate in PHP: `Feature::active('feature-name')`

### Adding a New Export

1. Create export class in `app/Exports/` implementing `FromQuery`, `WithHeadings`, `WithMapping`, `ShouldAutoSize`
2. Add export method in Livewire component, gated by `Feature::active('data-export')`
3. Add export button in Blade view within `@feature('data-export')`

### Adding a New External Integration Event

1. Create event in `app/Events/` extending base event
2. Register in `DispatchTenantIntegrationEvents` listener
3. Add to `WebhookEvent` enum
4. Dispatch event at the appropriate place in application logic

### Formatting & Linting

```bash
vendor/bin/pint --dirty --format agent
```

---

## Product Vision

Rumibot automates WhatsApp communication for Latin American businesses in two key functions:

1. **Prospect Management (Pre-sale):** Sales channel bot answers product questions, shares media/catalogs, captures lead data, qualifies prospects, and escalates high-intent leads to human sales agents.
2. **Customer Support (Post-sale):** Support channel bot teaches product usage step-by-step, shares instructional media, resolves FAQs instantly, and escalates complex issues to human support.

Each function operates on a separate WhatsApp number (channel) with its own personality, knowledge base, and system prompt.

**Business model:** Single plan ("Rumibot") in USD with quarterly ($30), semi-annual ($55), and annual ($110) billing. Tenants bring their own AI API keys.

**First tenant:** RumiStar E.I.R.L. (`is_platform_owner: true`), developer of iTrade 3.x (real estate SaaS with 176+ active companies in LATAM).

---

## Roadmap

All 10 implementation phases (0-9) are complete. Post-phase additions: LLM credential management, AI Configuration page, Agent Playground, registration with tenant auto-creation, channel form simplification, Meta Cloud API direct integration (replacing YCloud BSP), phone formatting & country detection, AI rate limiting, retry unanswered conversations command.

| Item | Description | Priority |
|------|-------------|----------|
| Redis + Horizon | Migrate from `database` queue driver to Redis. Install `laravel/horizon`, change `QUEUE_CONNECTION=redis`. Transparent switch — same jobs, different driver. | On demand |
| Production deployment | Domain, SSL, server setup, real Meta/MercadoPago/AI provider keys | Next |
| Landing page | Branded public landing at `/` with i18n support (ES/EN/PT_BR) — partially done | In progress |
| Analytics dashboard | `Livewire/Analytics/AnalyticsDashboard.php` — metrics and charts per tenant | Pending |
| Streaming responses | `Tenant.ai_streaming` column exists but not yet wired. Pending Laravel AI SDK support for streaming in agents. | Pending |
