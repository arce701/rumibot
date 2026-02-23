# Sesiones de desarrollo con Claude

Registro de todas las sesiones de trabajo. Cargar este archivo al inicio de una nueva sesion para dar contexto.

---

## Sesion 1 — Fases 0-8: Construccion completa de la plataforma

**Fecha:** Anterior a v1.0.0

**Resumen:** Se construyo la plataforma Rumibot desde cero a lo largo de multiples sesiones cubriendo las fases 0 a 8 del plan de desarrollo.

**Fases completadas:**
- **Fase 0:** Scaffolding — Laravel 12, multi-tenancy (`BelongsToTenant` + `TenantScope`), roles/permisos con Spatie
- **Fase 1:** WhatsApp — Integracion WhatsApp (Meta Cloud API), webhooks, verificacion HMAC, modelo `Channel`
- **Fase 2:** AI Agent — Pipeline conversacional con OpenAI/Anthropic/Gemini, system prompts configurables
- **Fase 3:** RAG — Knowledge base con embeddings, `ProcessDocument` job, busqueda semantica
- **Fase 4:** Leads — Extraccion automatica de datos de contacto, scoring, modelo `Lead`
- **Fase 5:** Escalaciones — Cola de escalacion humana, notificaciones Discord
- **Fase 6:** Integraciones — Webhooks salientes a servicios externos, `DispatchIntegrationEvent` job
- **Fase 7:** Billing — Suscripciones con MercadoPago, planes, feature flags con Pennant
- **Fase 8:** Analytics — Dashboard con metricas, graficos de conversaciones/mensajes/tokens

**Resultado:** 323+ tests pasando, plataforma funcional completa.

---

## Sesion 2 — Fase 9: Hardening para produccion

**Fecha:** Anterior a v1.0.0

**Resumen:** Endurecimiento de la aplicacion para produccion.

**Cambios implementados:**
- Rate limiting por tenant, canal y endpoint (AppServiceProvider + routes/api.php)
- Encriptacion de `TenantIntegration.secret` con cast `encrypted`
- Logging por tenant con `SetLogContext` middleware + `Context::add()` en jobs
- Retry/backoff en `ProcessIncomingMessage` (3 intentos, backoff exponencial)
- HTTP retry en `MetaCloudProvider`
- Backups automaticos a S3 con `spatie/laravel-backup`
- Exportaciones Excel/CSV con `maatwebsite/excel` (Leads + Conversations)
- Laravel Pulse dashboard restringido a super-admins
- 8 archivos de tests de seguridad, exports, logging, backup, pulse

**Resultado:** 323+ tests pasando, hardening completo.

---

## Sesion 3 — Documentacion, primer commit, seeders, fixes UI

**Fecha:** Sesion actual (pre-v1.0.0 a v1.0.0+)

### Tarea 1: Architecture Map
- Creado `docs/architecture-map.md` — referencia AI-readable (~29KB, 18 secciones)
- Movido `plan.md` de raiz a `docs/plan.md`

### Tarea 2: Primer commit v1.0.0
- Creado `README.md` con descripcion de la plataforma, stack, requisitos, instalacion
- Actualizado `.gitignore` (+.mcp.json, .DS_Store, .claude/settings.local.json, archivos editor)
- Actualizado `.env.example` con config Rumibot (PostgreSQL, AI providers, MercadoPago, Discord)
- Inicializado git, commit inicial, tag anotado `v1.0.0`
- **Sin co-autor** (preferencia del usuario)

### Tarea 3: Database seeders
- `PlansSeeder.php` — Cambiado de 3 planes en PEN a 1 plan "Rumibot" en USD:
  - Trimestral: $30, Semestral: $55, Anual: $110
  - Todas las features unlimited (usuarios pagan sus propios LLMs)
- `DatabaseSeeder.php` — Super admin: `rumibot8@gmail.com` / `Rumi2026$`
  - Tenant "Rumibot", suscripcion al plan anual
- Verificado con `migrate:fresh --seed` y tinker, 323 tests pasan

### Tarea 4: Instrucciones de uso
- Guia paso a paso para login, configuracion WhatsApp, prompts, knowledge base, AI, queue worker

### Tarea 5: Fixes UI (reportados via screenshot)

**Problemas encontrados:**
1. **Branding:** "Laravel Starter Kit" en lugar de "Rumibot"
2. **Billing crash:** `__('messages')` retorna array (translation group gotcha)
3. **Activity Log crash:** PostgreSQL type mismatch varchar vs bigint en query polimorfica
4. **Faltaba:** Selector de idioma en menu de usuario

**Archivos modificados (10):**
| Archivo | Cambio |
|---------|--------|
| `resources/views/components/app-logo.blade.php` | "Laravel Starter Kit" → "Rumibot" |
| `app/Livewire/Billing/SubscriptionManager.php` | Labels explicitos en vez de `__()` en translation groups |
| `resources/views/livewire/billing/subscription-manager.blade.php` | S/ → USD $ dinamico |
| `app/Livewire/ActivityLog/ActivityLogViewer.php` | Query por causer_id en vez de whereHas polimorfico |
| `resources/views/components/desktop-user-menu.blade.php` | Agregado selector de idioma (ES/EN/PT) |
| `routes/web.php` | Ruta `locale/{locale}` para cambiar idioma |
| `resources/views/layouts/app/sidebar.blade.php` | Grupo "Platform" → "Panel", removidos links externos |
| `database/seeders/DatabaseSeeder.php` | Super admin rumibot8@gmail.com |
| `database/seeders/PlansSeeder.php` | Plan unico Rumibot en USD |
| `docs/uso.md` | Instrucciones de uso |

**Commit:** `3731a15` — sin co-autor

### Lecciones aprendidas
- `__('messages')` y `__('channels')` retornan arrays porque coinciden con archivos en `lang/*/`
- PostgreSQL no hace cast implicito en joins polimorficos (varchar ≠ bigint)
- Hardcodear `S/` como moneda causa problemas al cambiar de PEN a USD

---

## Sesion 4 — Landing page con branding Rumibot

**Commits:** `157a9ae`, `ee3c305`, `c1db3a3`

**Cambios:**
- Landing page publica en `/` con soporte i18n (ES/EN/PT_BR)
- Branding Rumibot: logo sparkle star reemplaza logo Laravel
- Secciones: hero, features, pricing, CTA, footer
- Menu mobile responsive
- Mejoras visuales generales

---

## Sesion 5 — Traducciones i18n: JSON custom keys + enums traducibles

**Commits:** `519e61b`, `7c9a056`, `f7d87c4`

### Traducciones JSON
- ~150 translation keys custom agregadas a `lang/{en,es,pt_BR}.json`
- Investigacion confirmo que `lang:update` preserva keys custom (soft merge) — solo `lang:reset` las destruye

### Enums traducibles
- Creados `lang/{en,es,pt_BR}/enums.php` con labels para los 12 enums
- Agregado metodo `label(): string` a los 12 enums en `app/Models/Enums/`
- Patron: `__('enums.{group}.' . $this->value)` → devuelve label traducido
- Reemplazadas 18 ocurrencias de `ucfirst($enum->value)` por `$enum->label()` en 11 vistas blade

### Limpieza
- Eliminado `lang/vendor/backup/` (traducciones publicadas de spatie/backup, innecesarias)
- Eliminado `docs/plan.md` (redundante con `docs/architecture-map.md`)
- Actualizado `docs/architecture-map.md` con seccion i18n actualizada, tabla de enums, vision del producto y roadmap

**Tests:** 323 pasando, 1 skipped (preexistente)

---

## Sesion 6 — LLM Credentials, AI Config, Agent Playground, Registration fix, Channel simplification

**Fecha:** 2026-02-21

### Tarea 1: Fix registro de nuevos tenants (403)
- Nuevo usuario "LIMA FASHION" se registraba pero recibia 403 "No active tenant context"
- `CreateNewUser` no creaba Tenant, ni pivot, ni seteaba `current_tenant_id`
- **Solucion:** Reescribir `CreateNewUser` para crear Tenant + User + pivot + role en una transaccion DB
- Agregado campo "Company name" en el formulario de registro
- Auto-generacion de slug unico a partir del nombre de empresa
- Fix del usuario existente LIMA FASHION via tinker

### Tarea 2: Modelo LlmCredential + migracion + enum AiProvider
- Creado modelo `LlmCredential` con UUID, `api_key` encrypted, `provider` (AiProvider enum), soft deletes
- Creado enum `AiProvider` con 8 providers (OpenAi, Anthropic, Gemini, Groq, DeepSeek, Mistral, XAi, OpenRouter)
- Cada provider tiene metodo `models(): array` con lista estatica de modelos disponibles
- Migracion: columnas AI en tenants (`default_llm_credential_id`, `default_ai_model`, `ai_temperature`, `ai_max_tokens`, `ai_context_window`, `ai_streaming`)

### Tarea 3: Pagina AI Configuration (AiConfigManager)
- CRUD de credenciales LLM por tenant
- Configuracion de modelo: seleccionar credencial, elegir modelo (lista dinamica segun provider), ajustar temperature/tokens/context
- Permisos: `ai-config.view`, `ai-config.update` (owner y admin, no member)
- Primera credencial se marca como default automaticamente

### Tarea 4: Agent Playground
- Componente `AgentPlayground` con chat in-memory (no persistido)
- Usa `PlaygroundChatAgent` (solo SimilaritySearch, sin side effects)
- Selector de canal, muestra documentos y herramientas disponibles
- Requiere credencial LLM configurada para funcionar

### Tarea 5: Eliminacion total de env defaults para LLM
- Eliminado `default_provider`, `default_model`, `fallback_providers`, `temperature`, `max_tokens`, `max_conversation_messages` de `config/rumibot.php`
- Solo queda `ai.timeout` en la config
- `ProcessIncomingMessage` retorna early con log warning si no hay credencial
- `AgentPlayground` muestra mensaje amigable si no hay credencial

### Tarea 6: Simplificacion del formulario de canales
- Eliminados campos: slug (auto-generado), `provider_business_account_id`, `system_prompt_override` (esta en Prompts), `ai_model_override` (esta en AI Config), `ai_temperature` (esta en AI Config)
- Formulario simplificado: Nombre, Tipo, Provider, API Key, Phone Number ID, Webhook Token, Activo

### Tarea 7: Limpieza de base de datos
- Eliminadas columnas sin usar: `tenants.default_ai_provider`, `channels.provider_business_account_id`, `channels.ai_temperature`, `llm_credentials.is_default`
- Consolidacion de migraciones: editados archivos `create_*` directamente, eliminada migracion `add_ai_settings_to_tenants_table`
- Ejecutado `migrate:fresh --seed`

### Tarea 8: Sidebar reorganizado
Nuevo orden logico siguiendo el flujo de trabajo:
Dashboard → Channels → AI Configuration → Prompts → Knowledge Base → Agent Playground → Conversations → Leads → Escalations → Integrations → Team → Billing → Activity Log

### Tarea 9: Actualizacion de documentacion
- Actualizado `architecture-map.md` con todos los cambios
- Actualizado `session-claude.md` con Sesion 6
- Actualizado `uso.md` con flujo actualizado

**Archivos modificados (30+):**
- Modelos: Tenant, Channel, LlmCredential (nuevo)
- Livewire: AiConfigManager (nuevo), AgentPlayground (nuevo), ChannelManager
- Agentes: PlaygroundChatAgent (nuevo), TenantChatAgent
- Jobs: ProcessIncomingMessage
- Auth: CreateNewUser, register.blade.php
- Config: rumibot.php
- Migraciones: 3 editadas, 1 eliminada, 1 nueva
- Factories: 3 actualizadas, 1 nueva
- Seeders: DatabaseSeeder, RolesAndPermissionsSeeder
- Tests: 6 actualizados, 4 nuevos
- Vistas: sidebar, ai-config (nueva), playground (nueva), channel-manager

**Resultado:** 362 tests pasando, 1 skipped (preexistente)

---

## Sesion 7 — Limpieza post-refactorizacion YCloud → Meta Cloud API

**Fecha:** 2026-02-23

**Resumen:** Eliminacion de peso muerto tras la migracion de YCloud a Meta Cloud API directo.

**Cambios implementados:**
- Eliminadas columnas muertas `provider_type` y `provider_webhook_verify_token` de la migracion `create_channels_table`
- Consolidado partial unique index `channels_tenant_phone_unique` en la migracion original
- Eliminada migracion transitional `update_channels_default_provider_to_meta_cloud`
- Eliminada referencia a `provider_type` en ChannelFactory y tests
- Actualizada documentacion: `uso.md`, `architecture-map.md`, `README.md`, `session-claude.md` — todas las referencias a YCloud reemplazadas por Meta Cloud API
- Eliminada entrada completada "WhatsApp BSP migration" del roadmap

**Archivos modificados (9):**
| Accion | Archivo |
|--------|---------|
| EDIT | `database/migrations/2026_02_13_164521_create_channels_table.php` |
| DELETE | `database/migrations/2026_02_23_044334_update_channels_default_provider_to_meta_cloud.php` |
| EDIT | `database/factories/ChannelFactory.php` |
| EDIT | `tests/Feature/WhatsApp/ChannelTest.php` |
| EDIT | `tests/Feature/Panel/TenantPanelCrudTest.php` |
| EDIT | `docs/uso.md` |
| EDIT | `docs/architecture-map.md` |
| EDIT | `docs/session-claude.md` |
| EDIT | `README.md` |

---

## Estado actual del proyecto

**Tests:** 361 pasando, 1 skipped
**Branch:** main
**Super admin:** rumibot8@gmail.com / Rumi2026$
**Plan:** Rumibot (unico) en USD — $30/trim, $55/sem, $110/anual
**Fases completadas:** 0-9 (todas) + post-fase (LLM credentials, AI config, playground, registration fix, Meta Cloud API migration)
**Modelos:** 17 (12 tenant-scoped) | **Enums:** 12 | **Livewire:** 22 | **Migraciones:** 27
**Docs:** `architecture-map.md` (referencia tecnica), `session-claude.md` (log de sesiones), `user-manual.md` (manual de usuario)
**Pendiente:** Deployment, dominio produccion, configuracion real de Meta Cloud API/MercadoPago/AI providers, streaming AI, analytics dashboard
