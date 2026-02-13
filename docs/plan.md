# Rumibot - Plataforma SaaS Multi-Tenant de Chatbot IA por WhatsApp

## Misión

Rumibot automatiza la comunicación por WhatsApp de negocios latinoamericanos en dos funciones clave:

### 1. Gestión de Prospectos (Pre-venta)
Un prospecto ve el anuncio de la empresa en Facebook, Instagram u otro medio y hace clic en "Contactar por WhatsApp". El bot del canal de **ventas** lo recibe y:

- Responde todas sus dudas sobre el producto o servicio de forma natural y amigable
- Envía características, precios, comparativas y beneficios usando la información pre-cargada del negocio
- Comparte imágenes del producto, enlaces a videos demostrativos, PDFs con catálogos o fichas técnicas
- Envía botones interactivos para elegir opciones o agendar una demo
- Captura los datos del prospecto (nombre, país, email, intereses) durante la conversación
- Califica al prospecto y, cuando detecta alta intención de compra, escala a un humano del equipo de ventas

### 2. Soporte al Cliente (Post-venta)
Una vez que el prospecto compra el producto o servicio, un humano del equipo le indica que guarde el número de **Soporte al Cliente** para futuras consultas. El bot del canal de **soporte**:

- Enseña a usar el producto o servicio paso a paso, como un asistente experto
- Envía imágenes instructivas, capturas de pantalla y enlaces a videos tutoriales pre-cargados
- Resuelve preguntas frecuentes de forma inmediata (funciona como un FAQ inteligente que entiende contexto)
- Guía al cliente con instrucciones detalladas para completar acciones específicas
- Escala a un humano del equipo de soporte cuando la consulta excede la capacidad del bot

### Separación por número de WhatsApp
Cada función opera con su propio número de WhatsApp (canal), cada uno con su personalidad, conocimiento y comportamiento diferenciado:

| Canal | Número WhatsApp | Personalidad | Knowledge Base |
|-------|----------------|-------------|----------------|
| **Ventas** (`sales`) | Número 1 | Amigable, persuasivo, orientado a cerrar | Catálogos, fichas de producto, precios, promos, videos demo |
| **Soporte** (`support`) | Número 2 | Paciente, didáctico, orientado a resolver | Manuales, guías paso a paso, FAQs, videos tutoriales |

La transición de Ventas a Soporte es manual: cuando el prospecto se convierte en cliente, un humano del equipo le comunica que para soporte utilice el segundo número. Cada canal tiene su propio `system_prompt_override` que define el tono, las instrucciones y el comportamiento del bot.

---

## Contexto

Rumibot es una plataforma SaaS multi-tenant multilenguaje donde negocios latinoamericanos configuran sus chatbots de IA para WhatsApp. Cada tenant (negocio) sube la información de su empresa, configura sus canales de Ventas y Soporte con números de WhatsApp independientes, y el bot atiende a prospectos y clientes automáticamente. La plataforma soporta tres idiomas: español latinoamericano (es), inglés (en) y portugués brasileño (pt_BR).

**RumiStar E.I.R.L.** (RUC: 20611921820) es el primer tenant (`is_platform_owner: true`). Desarrolladora de iTrade 3.x, plataforma SaaS de gestión inmobiliaria con 176+ empresas activas en Latinoamérica. Canal de Ventas: responde consultas de inmobiliarias interesadas en iTrade. Canal de Soporte: ayuda a los 176+ clientes activos a usar la plataforma.

**Modelo de negocio:** Tres ciclos de facturación: trimestral, semestral y anual. Tres planes de suscripción con límites de uso diferenciados. MercadoPago como procesador de pagos principal (soporte nativo para Perú y LATAM).

**Estado actual:** Laravel 12 con Livewire 4, Flux UI Free, Fortify (auth + 2FA), PostgreSQL. Solo el modelo `User` y migraciones base.

---

## Stack Tecnológico

**Instalado:**
- Laravel 12 + Livewire 4 Starter Kit
- Flux UI Free v2 (componentes oficiales de Livewire)
- Laravel Fortify v1 (auth headless + 2FA)
- PHP 8.3 / PostgreSQL + pgvector
- Pest v4 (testing)
- Tailwind CSS v4 + Vite

**Por instalar (por fase):**

```bash
# Fase 0 - Fundación
composer require laravel/ai                        # Motor IA: Agents, Tools, Embeddings, RAG
composer require spatie/laravel-permission          # Roles y permisos con teams/multi-tenant
composer require spatie/laravel-activitylog         # Auditoría de cambios en modelos
composer require laravel/pennant                    # Feature flags por plan de suscripción
composer require laravel-lang/common                # Traducciones oficiales (Framework, Fortify, etc.)
composer require smalot/pdfparser                  # Extracción de texto de PDFs

# Fase 6 - API de automatización
composer require laravel/sanctum                    # Tokens API para integraciones externas
composer require spatie/laravel-query-builder       # Filtros y sorts en API REST

# Fase 7 - Billing
composer require mercadopago/dx-php                 # SDK oficial MercadoPago

# Fase 9 - Producción y escalamiento
composer require maatwebsite/excel                  # Exportaciones Excel/CSV
composer require spatie/laravel-backup              # Backups automáticos a S3
composer require laravel/pulse                      # Monitoreo de performance
composer require laravel/horizon                    # Monitoreo de colas (cuando se migre a Redis)
```

---

## Integraciones Externas

### 1. Laravel AI SDK (`laravel/ai`) — Corazón del sistema
**Docs:** https://laravel.com/docs/12.x/ai-sdk

SDK oficial de Laravel para IA. Capacidades que usaremos:

- **Agents como clases PHP**: Encapsulan instrucciones, historial, tools y schema en una clase testeable
- **Multi-provider con failover**: OpenAI, Anthropic, Gemini, Mistral, Ollama, xAI, DeepSeek, Groq, Azure
- **Tools**: El agente ejecuta acciones (buscar knowledge base, capturar leads, escalar a humano)
- **Structured Output**: Respuestas tipadas para extraer datos del prospecto
- **Embeddings + pgvector**: Búsqueda semántica nativa con PostgreSQL
- **SimilaritySearch**: Tool RAG built-in que busca en modelos Eloquent por similitud vectorial
- **File Attachments**: Pasar archivos directamente al LLM
- **Streaming**: SSE y Vercel AI Protocol
- **Queue**: Encolar prompts con `.queue()` y callbacks `.then()` / `.catch()`
- **Agent Middleware**: Para logging y tracking de costos
- **Testing**: `Agent::fake()`, `Agent::assertPrompted()`, `Embeddings::fake()`

### 2. Spatie Laravel Permission v7 (`spatie/laravel-permission`)
**Docs:** https://spatie.be/docs/laravel-permission/v7/introduction

Manejo de roles y permisos con soporte **Teams** para multi-tenant:

- **Teams habilitado**: `'teams' => true` en config, con `team_foreign_key => 'tenant_id'`
- Los roles y permisos se scopean automáticamente por `tenant_id`
- `setPermissionsTeamId($tenantId)` en middleware para establecer el contexto del tenant
- Middleware `role:admin` y `permission:manage-channels` para proteger rutas
- Directivas Blade: `@role('tenant_owner')`, `@can('manage-channels')`
- Super-admin opera vía `Gate::before()` y columna `is_super_admin` en `users`
- Tablas automáticas: `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions`

**Roles del sistema:**
| Rol | Scope | Permisos |
|-----|-------|----------|
| `tenant_owner` | Per-tenant | Gestión completa del tenant: canales, prompts, knowledge, equipo, billing, analytics |
| `tenant_admin` | Per-tenant | Similar a owner, sin poder eliminar tenant ni transferir ownership |
| `tenant_member` | Per-tenant | Ver conversaciones, ver leads, ver analytics |

**Nota**: `super_admin` se gestiona con `is_super_admin` boolean en `users` + `Gate::before()`, fuera del scope de teams de Spatie.

**Permisos granulares:**
- `manage-channels`, `manage-prompts`, `manage-knowledge`, `manage-team`, `manage-integrations`
- `view-conversations`, `view-leads`, `view-analytics`, `manage-escalations`
- `export-data`, `manage-billing`, `manage-subscription`

### 3. Spatie Activity Log v4 (`spatie/laravel-activitylog`)
**Docs:** https://spatie.be/docs/laravel-activitylog/v4/introduction

Auditoría automática de acciones y cambios en modelos:

- Trait `LogsActivity` en modelos clave: `Tenant`, `Channel`, `KnowledgeDocument`, `Lead`, `Plan`, `Subscription`
- Registra automáticamente: `created`, `updated`, `deleted` con valores old/new
- Cada log incluye: quién (causer), qué modelo (subject), qué cambió (properties)
- `activity()->causedBy($user)->performedOn($tenant)->log('Prompt actualizado')`
- Ideal para: cambios en system_prompt, config de canales, gestión de equipo, cambios en leads, cambios de plan
- La tabla `activity_log` se filtra por `causer_id` + tenant context
- `LogOptions::defaults()->logOnly(['system_prompt', 'name', 'settings'])->logOnlyDirty()`

### 4. Colas: Database driver → Redis + Horizon (progresivo)

**Inicio con `database` driver:**
- Laravel ya incluye la tabla `jobs` para colas basadas en base de datos
- `QUEUE_CONNECTION=database` en `.env`
- Worker: `php artisan queue:work --queue=high,default,low`
- Tres colas por prioridad: `high` (mensajes WhatsApp), `default` (IA, webhooks), `low` (procesamiento de documentos)
- `failed_jobs` table para jobs fallidos + `php artisan queue:retry`
- Suficiente para etapa inicial con pocos tenants

**Migración a Redis + Horizon (cuando la carga lo requiera):**
- Instalar `laravel/horizon` y configurar Redis: `QUEUE_CONNECTION=redis`
- Dashboard visual para monitorear jobs, throughput, tiempos, fallos
- Auto-scaling de workers basado en carga
- Configuración de workers por cola vía código
- El cambio es transparente: solo cambia el driver, los jobs son los mismos

### 5. Laravel Pennant (`laravel/pennant`)
**Docs:** https://laravel.com/docs/12.x/pennant

Feature flags para control de funcionalidades por plan de suscripción:

```php
Feature::define('knowledge-base', fn (Tenant $tenant) => $tenant->hasActiveFeature('knowledge_base'));
Feature::define('webhook-integrations', fn (Tenant $tenant) => $tenant->hasActiveFeature('integrations'));
Feature::define('multi-channel', fn (Tenant $tenant) => $tenant->hasActiveFeature('multi_channel'));
Feature::define('analytics-dashboard', fn (Tenant $tenant) => $tenant->hasActiveFeature('analytics'));
Feature::define('data-export', fn (Tenant $tenant) => $tenant->hasActiveFeature('data_export'));
```

- Gating centralizado: `@feature('knowledge-base')` en Blade
- `Feature::active('multi-channel')` en PHP
- Útil para rollout gradual de features nuevas
- Driver `database` para persistencia

### 6. Laravel Sanctum (`laravel/sanctum`)
**Docs:** https://laravel.com/docs/12.x/sanctum

Autenticación por tokens para la API de automatización:

- Cada tenant genera API tokens con habilidades específicas: `['send-messages', 'update-leads', 'close-conversations']`
- Los tokens se scopean al tenant que los creó
- n8n, Zapier, Make usan estos tokens para ejecutar acciones vía API REST
- Dashboard de gestión de tokens en el panel del tenant

### 7. Laravel Lang (`laravel-lang/common`)
**Docs:** https://laravel-lang.com/introduction.html

Paquete oficial de traducciones para el ecosistema Laravel. Provee localización completa para Framework, Fortify, Breeze, Cashier, Jetstream, Nova y UI:

- **Traducciones pre-hechas**: Validaciones, mensajes de auth, paginación, passwords, HTTP statuses para 100+ idiomas
- **Tres idiomas iniciales**: `es` (español latinoamericano), `en` (inglés), `pt_BR` (portugués brasileño)
- **`php artisan lang:update`**: Sincroniza traducciones con cada actualización de dependencias
- **Detección de locale**: Por URL, header `Accept-Language`, sesión, cookie o modelo de usuario
- **Config `config/localization.php`**: Aliases de locale, smart punctuation por idioma, inline mode
- **Automático en composer**: `"post-update-cmd": ["@php artisan lang:update"]` mantiene traducciones sincronizadas

**Estrategia multilenguaje:**

| Capa | Idioma | Mecanismo |
|------|--------|-----------|
| Panel admin (UI) | Preferencia del usuario (`users.locale`) | Laravel Lang + `__()` / `trans()` en Blade |
| Bot WhatsApp (respuestas IA) | Configurado por tenant (`tenants.locale`) | Instrucción en el system_prompt del Agent |
| Emails y notificaciones | Locale del usuario destinatario | Laravel Notifications con `locale()` |
| Validaciones y errores | Locale de la sesión activa | Laravel Lang traducciones automáticas |

Cada usuario elige su idioma preferido para el panel. Cada tenant configura el idioma de su bot (el Agent recibe la instrucción de responder en ese idioma). Los textos de la UI usan `__('messages.key')` con archivos en `lang/{locale}/`.

### 8. YCloud - Proveedor WhatsApp Business API
**Docs:** https://www.ycloud.com | https://docs.ycloud.com

BSP (Business Solution Provider) oficial de Meta para WhatsApp:

- **API REST**: `POST https://api.ycloud.com/v2/whatsapp/messages/sendDirectly`
- **Auth**: Header `X-API-Key` con API key del canal
- **Webhooks**: Evento `whatsapp.inbound_message.received` con payload completo
- **Tipos de mensaje**: texto, imagen, video, documento, audio, interactive, templates, ubicación, contactos
- **Coexistencia**: WhatsApp Business App y API simultáneamente
- **Zero markup** en mensajes WhatsApp (pass-through de precios de Meta)
- **Plan Free**: 1 usuario, 2 canales, sin tarjeta

**Pricing YCloud (plataforma):**
| Plan | Precio | Usuarios | Canales |
|------|--------|----------|---------|
| Free | $0 | 1 | 2 |
| Growth | $39/mes | 2 | 3 |
| Pro | $89/mes | 6 | 8 |
| Enterprise | $399/mes | 40 | 30 |

La interfaz `WhatsAppProvider` abstrae YCloud, permitiendo agregar otros proveedores (Meta Cloud API, Twilio) en el futuro.

### 9. n8n - Capa de Automatización
**Docs:** https://n8n.io | https://docs.n8n.io

Plataforma de automatización open-source (self-hosted o cloud). Los tenants la conectan opcionalmente para extender su chatbot con workflows personalizados.

**Patrón de integración: Event-Driven Webhooks**
```
Rumibot (eventos) → Webhook outbound → n8n (automatización) → API rumibot (acciones)
```

**Eventos que rumibot emite:**
- `conversation.started` - Nueva conversación iniciada
- `message.received` - Mensaje del usuario recibido
- `lead.captured` - Lead capturado con datos
- `escalation.triggered` - Escalación a humano detectada
- `conversation.closed` - Conversación cerrada

**Acciones que n8n ejecuta vía API de rumibot (autenticadas con Sanctum):**
- Enviar mensaje por WhatsApp a un contacto
- Actualizar estado de un lead
- Cerrar una conversación
- Agregar nota a una escalación

**Tabla `tenant_integrations`**: Cada tenant registra integraciones externas (n8n, Zapier, Make, custom) con estado gestionable: registrar, marcar como principal, suspender o dar de baja.

**Casos de uso por tenant:**
- Prospecto capturado → crear contacto en CRM (HubSpot, Salesforce)
- Escalación → notificar en Slack/Teams/Discord
- Lead convertido → enviar email de bienvenida
- Resumen diario de conversaciones → enviar por email

### 10. MercadoPago - Procesador de Pagos para LATAM
**Docs:** https://www.mercadopago.com/developers

Procesador de pagos con soporte nativo en Perú y toda Latinoamérica:

- **Cobertura**: Argentina, Brasil, Chile, Colombia, México, Perú, Uruguay
- **Métodos de pago en Perú**: Visa, Mastercard, Yape, PagoEfectivo, BCP
- **API de suscripciones**: Planes con `frequency` y `frequency_type` configurables (trimestral, semestral, anual)
- **SDK PHP oficial**: `mercadopago/dx-php`
- **Webhooks**: Notificaciones de pago exitoso, fallido, reembolso

La interfaz `PaymentProvider` abstrae MercadoPago, permitiendo agregar Stripe u otros procesadores para mercados específicos.

### 11. Paquetes de Producción

**Spatie Laravel Query Builder** (`spatie/laravel-query-builder`)
- Filtros, sorts e includes en endpoints API REST: `?filter[status]=new&sort=-created_at&include=conversation`
- Estandariza la API de automatización

**Maatwebsite Excel** (`maatwebsite/excel`)
- Exportaciones de leads, conversaciones y analytics en Excel/CSV
- Exports en cola para datasets grandes

**Spatie Laravel Backup** (`spatie/laravel-backup`)
- Backups automáticos de base de datos y archivos a S3
- Rotación y limpieza de backups antiguos
- Notificaciones en caso de fallos

**Laravel Pulse** (`laravel/pulse`)
- Monitoreo de performance: queries lentas, requests lentos, excepciones, uso de caché
- Complementa el monitoreo de colas con visibilidad de toda la aplicación

---

## Arquitectura

### 1. Multi-tenancy: Single DB con `tenant_id` + Global Scopes
- Trait `BelongsToTenant` con `TenantScope` global en todos los modelos tenant-scoped
- Auto-asignación de `tenant_id` al crear registros vía el trait
- Middleware `SetCurrentTenant` para rutas web (lee del usuario autenticado)
- Dentro del middleware: `setPermissionsTeamId($tenantId)` para scopear Spatie Permission
- Webhooks resuelven tenant desde UUID en la URL (stateless, sin auth)
- `SoftDeletes` en todas las tablas donde se requiera dar de baja registros (tenants, users, channels, conversations, leads, escalations, knowledge_documents, tenant_integrations, plans, subscriptions)

### 2. Super-Admin: Separación Plataforma vs Tenant
El super-admin (dueño de la plataforma) opera en dos contextos:

**Contexto plataforma** (`current_tenant_id = null`):
- Rutas bajo `/platform/*` protegidas con middleware `EnsureSuperAdmin`
- Dashboard de plataforma: listar tenants, métricas globales, gestionar planes, billing
- `setPermissionsTeamId(null)` — sin scope de tenant

**Contexto tenant** (`current_tenant_id = {uuid}`):
- El super-admin "entra" a cualquier tenant vía switch
- Opera como si fuera `tenant_owner` gracias a `Gate::before()`
- Puede volver al contexto plataforma con "Salir al Panel de Plataforma"

```php
// app/Models/User.php
public function isSuperAdmin(): bool
{
    return $this->is_super_admin;
}

// app/Providers/AppServiceProvider.php
Gate::before(function (User $user, string $ability) {
    if ($user->isSuperAdmin()) {
        return true;
    }
});
```

**RumiStar E.I.R.L.** es un tenant regular con `is_platform_owner: true` (exento de billing). El super-admin es `tenant_owner` de RumiStar y además tiene `is_super_admin: true`.

### 3. Webhooks WhatsApp: UUID del tenant en la URL
```
GET/POST /api/webhooks/whatsapp/{tenant_uuid}/{channel_slug}
```
- Un solo controller; el `channel.type` determina el comportamiento
- Cada canal tiene su `webhook_verify_token` para validación de YCloud/Meta

### 4. IA: Laravel AI SDK Agents

```php
// app/Ai/Agents/TenantChatAgent.php
class TenantChatAgent implements Agent, Conversational, HasTools, HasMiddleware
{
    use Promptable;

    public function __construct(
        public Tenant $tenant,
        public Channel $channel,
        public Conversation $conversation,
    ) {}

    public function instructions(): string
    {
        return implode("\n\n", array_filter([
            $this->baseInstructions(),
            $this->tenant->system_prompt,
            $this->channel->system_prompt_override,
        ]));
    }

    public function messages(): iterable
    {
        return $this->conversation->messages()
            ->latest()->limit(50)->get()->reverse()
            ->map(fn ($msg) => new Message($msg->role, $msg->content));
    }

    public function tools(): iterable
    {
        return [
            SimilaritySearch::usingModel(
                model: KnowledgeChunk::class,
                column: 'embedding',
                query: fn ($q) => $q->where('tenant_id', $this->tenant->id)
                    ->whereJsonContainsOrEmpty('channel_scope', $this->channel->id),
            ),
            new SendMedia($this->channel),         // Enviar imágenes, docs, videos por WhatsApp
            new CaptureLead($this->conversation),   // Solo activo en canales 'sales'
            new EscalateToHuman($this->conversation),
        ];
    }

    public function middleware(): array
    {
        return [new TrackTokenUsage($this->conversation)];
    }
}
```

**El Agent pattern integra todo:**
- `instructions()` = prompts multi-capa (tenant + canal). El `system_prompt_override` del canal define si el bot es vendedor o soporte
- `messages()` = historial desde nuestra tabla messages
- `tools()` = SimilaritySearch filtra por channel_scope (cada canal accede solo a su knowledge base) + SendMedia (imágenes, docs, videos) + CaptureLead (solo en sales) + EscalateToHuman
- `middleware()` = tracking de tokens y costos
- `.prompt()` / `.stream()` / `.queue()` = llamada al LLM
- Failover multi-provider: `provider: [Lab::OpenAI, Lab::Anthropic]`

**Comportamiento por tipo de canal:**
| Canal | Bot actúa como | Tools activos | Knowledge Base |
|-------|---------------|---------------|----------------|
| `sales` | Asesor comercial amigable y persuasivo | SimilaritySearch, SendMedia, CaptureLead, EscalateToHuman | Catálogos, fichas, precios, videos demo |
| `support` | Asistente de soporte paciente y didáctico | SimilaritySearch, SendMedia, EscalateToHuman | Manuales, guías, FAQs, videos tutoriales |

### 5. Knowledge Base: Archivos → Chunks → Embeddings → Búsqueda Semántica
```
Upload → Validar → S3 → Job en cola → Extraer texto → Chunking → Generar embeddings → Guardar en DB
```
- **pgvector** en PostgreSQL para embeddings (1536 dimensiones)
- `Embeddings::for($chunks)->generate()` del Laravel AI SDK para generar vectores
- `SimilaritySearch::usingModel(KnowledgeChunk::class, 'embedding')` como tool del Agent
- `$table->vector('embedding', dimensions: 1536)->index()` para índice HNSW
- Búsqueda semántica desde el día 1

### 6. WhatsApp: Interfaz abstracta con YCloud como primer provider
```php
interface WhatsAppProvider
{
    public function sendText(string $to, string $text): MessageResponse;
    public function sendImage(string $to, string $url, ?string $caption): MessageResponse;
    public function sendDocument(string $to, string $url, string $filename): MessageResponse;
    public function sendInteractive(string $to, array $buttons, string $body): MessageResponse;
    public function validateWebhook(Request $request): bool;
    public function parseInboundMessage(array $payload): InboundMessage;
}
```
- `YCloudProvider` implementa esta interfaz usando `api.ycloud.com/v2`
- Cada canal almacena `provider_type` y credenciales encriptadas

### 7. Billing: Sistema propio con PaymentProvider abstracto
```php
interface PaymentProvider
{
    public function createCustomer(Tenant $tenant): string;
    public function createSubscription(Tenant $tenant, PlanPrice $planPrice): SubscriptionResult;
    public function cancelSubscription(string $externalSubscriptionId): bool;
    public function handleWebhook(Request $request): WebhookPayload;
}
```
- `MercadoPagoProvider` como primera implementación (Perú + LATAM)
- Preparado para `StripeProvider` cuando se expanda a mercados con soporte Stripe
- `ManualPaymentProvider` para transferencias bancarias y pagos manuales
- Feature gating vía `PlanFeatureGate` service + Laravel Pennant
- Tenants con `is_platform_owner: true` están exentos de billing

**Planes de suscripción:**
| Feature | Basico | Profesional | Empresa |
|---------|--------|-------------|---------|
| Canales WhatsApp | 1 | 3 | 10 |
| Mensajes/mes | 500 | 2,000 | ilimitado |
| Documentos knowledge | 3 | 15 | ilimitado |
| Miembros del equipo | 1 | 5 | 20 |
| Integraciones (n8n, etc.) | — | 3 | ilimitado |
| Analytics dashboard | — | incluido | incluido |
| Exportación de datos | — | — | incluido |
| Modelo IA | gpt-4o-mini | gpt-4o-mini / gpt-4o | todos |

**Ciclos de facturación:**
| Plan | Trimestral | Semestral (10% desc.) | Anual (20% desc.) |
|------|------------|----------------------|-------------------|
| Basico | S/ 150 | S/ 270 | S/ 480 |
| Profesional | S/ 450 | S/ 810 | S/ 1,440 |
| Empresa | S/ 1,200 | S/ 2,160 | S/ 3,840 |

### 8. Multilenguaje: Laravel Lang + locale por usuario y tenant
- Tres idiomas soportados: `es` (español latinoamericano), `en` (inglés), `pt_BR` (portugués brasileño)
- `users.locale` determina el idioma del panel admin para cada usuario
- `tenants.locale` determina el idioma por defecto del bot en WhatsApp
- Middleware `SetLocale` lee `auth()->user()->locale` y aplica `App::setLocale()`
- Todas las vistas Blade usan `__('key')` y `trans('messages.key')` para textos traducibles
- Archivos de traducción en `lang/es/`, `lang/en/`, `lang/pt_BR/` para textos custom de la app
- Laravel Lang provee traducciones automáticas de Framework, Fortify, validaciones, HTTP statuses
- `php artisan lang:update` sincroniza traducciones del ecosistema
- Selector de idioma en el perfil del usuario y en la barra de navegación
- Notificaciones y emails se envían en el locale del usuario destinatario

### 9. Integraciones externas: Interfaz gestionable por tenant
Cada tenant gestiona sus integraciones externas (n8n, Zapier, Make, custom) con un ciclo de vida completo:

- **Registrar**: Crear nueva integración con URL, secreto y eventos suscritos
- **Marcar como principal**: Una integración por tenant es la `is_primary` (recibe todos los eventos por defecto)
- **Suspender**: Pausa temporal, deja de recibir eventos (`status: suspended`)
- **Dar de baja**: Soft delete, se puede reactivar (`deleted_at`)

```php
enum IntegrationStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
}
```

### 10. Automatización: Event-Driven Outbound Webhooks
- Los tenants registran integraciones en tabla `tenant_integrations`
- Cada evento de negocio dispara un Laravel Event
- Un listener envía el payload a las integraciones activas del tenant vía job en cola
- API REST autenticada con Sanctum para que n8n/Zapier ejecute acciones de vuelta

### 11. Convenciones de Código
- **Idioma del código**: Todo en inglés — variables, funciones, clases, migraciones, comentarios
- **Nombres autodescriptivos**: Expresan claramente su intención (`$activeSubscription`, `canAccessFeature()`, `dispatchIntegrationEvents()`)
- **Escalabilidad y mantenibilidad**: Interfaces para servicios externos, enums para estados, traits para comportamiento compartido
- **Convenciones Laravel estrictas**: Tablas en inglés plural snake_case, modelos en singular PascalCase, foreign keys como `{model}_id`, timestamps con `$table->timestamps()`, soft deletes con `$table->softDeletes()`
- **Modelos**: En `app/Models/` directamente
- **Agents IA**: En `app/Ai/Agents/`, Tools en `app/Ai/Tools/`, Middleware en `app/Ai/Middleware/`
- **Servicios**: En `app/Services/` organizados por dominio
- **Enums PHP 8**: Para estados y tipos, keys en TitleCase
- **SoftDeletes**: En tablas que requieren dar de baja registros
- **Factories y seeders**: Para cada modelo, con states útiles para testing

---

## Esquema de Base de Datos

### Tablas de paquetes (automáticas)
- `agent_conversations`, `agent_conversation_messages` — Laravel AI SDK
- `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions` — Spatie Permission (con `tenant_id` como team key)
- `activity_log` — Spatie Activity Log
- `features`, `feature_values` — Laravel Pennant (driver database)
- `personal_access_tokens` — Laravel Sanctum
- `jobs`, `job_batches`, `failed_jobs` — Laravel Queue (ya existen)

### Tablas nuevas

**`tenants`** — SoftDeletes
| Columna | Tipo | Descripción |
|---|---|---|
| id | uuid (PK, uuidv7) | |
| name | string(200) | Nombre del negocio |
| slug | string(100), unique | URL-friendly |
| system_prompt | text | **Prompt personalizado grande** - el tenant describe su negocio, tono, reglas, productos, precios |
| default_ai_provider | string(20), default 'openai' | Provider IA preferido |
| default_ai_model | string(100), nullable | Modelo IA preferido |
| timezone | string(50), default 'America/Lima' | |
| locale | string(10), default 'es' | |
| is_active | boolean, default true | |
| is_platform_owner | boolean, default false | Exento de billing, solo para el tenant del dueño de la plataforma |
| settings | jsonb | Config flexible: temperatura IA, max tokens, reglas de escalación, notificaciones |
| timestamps | | |
| soft_deletes | | |

**`channels`** — Canales WhatsApp del tenant (cada canal = un número de WhatsApp con su función) — SoftDeletes
| Columna | Tipo | Descripción |
|---|---|---|
| id | uuid (PK, uuidv7) | |
| tenant_id | uuid (FK → tenants) | |
| name | string(100) | "Ventas iTrade", "Soporte iTrade", etc. |
| slug | string(100) | Unique per tenant |
| type | string(20) | **'sales'** (gestión de prospectos) \| **'support'** (soporte al cliente) |
| provider_type | string(20), default 'ycloud' | 'ycloud' \| 'meta_cloud' |
| provider_api_key | text | **Encriptado** - API key del proveedor WhatsApp |
| provider_phone_number_id | string(50) | Phone number ID en el proveedor |
| provider_business_account_id | string(50), nullable | Business Account ID |
| provider_webhook_verify_token | string(100) | Token para verificar webhooks |
| system_prompt_override | text, nullable | **Personalidad del canal**: ventas (persuasivo, amigable) o soporte (paciente, didáctico). Instrucciones de tono, qué enviar, cómo responder |
| ai_model_override | string(100), nullable | Override del modelo IA |
| ai_temperature | decimal(3,2), nullable | |
| is_active | boolean, default true | |
| settings | jsonb | Config del canal |
| timestamps | | |
| soft_deletes | | |
| UNIQUE(tenant_id, slug) | | |

**`knowledge_documents`** — Archivos y recursos subidos por el tenant (catálogos, manuales, guías, imágenes de producto, etc.) — SoftDeletes
| Columna | Tipo | Descripción |
|---|---|---|
| id | uuid (PK, uuidv7) | |
| tenant_id | uuid (FK → tenants) | |
| title | string(200) | "Catálogo de productos", "Manual de usuario", "FAQ soporte", etc. |
| file_name | string(255) | Nombre original |
| file_path | string(500) | Ruta en S3: `tenants/{uuid}/documents/{uuid}/{filename}` |
| file_size | integer | Bytes |
| mime_type | string(100) | |
| status | string(20) | 'pending' \| 'processing' \| 'ready' \| 'failed' |
| error_message | text, nullable | |
| total_chunks | integer, default 0 | |
| channel_scope | jsonb, default '[]' | Vacío = todos los canales. Permite asignar docs solo a ventas, solo a soporte, o ambos |
| metadata | jsonb | |
| timestamps | | |
| soft_deletes | | |

**`knowledge_chunks`** — Fragmentos con embeddings para búsqueda semántica
| Columna | Tipo | Descripción |
|---|---|---|
| id | bigint (PK) | |
| document_id | uuid (FK → knowledge_documents) | ON DELETE CASCADE |
| tenant_id | uuid (FK → tenants) | Denormalizado para performance |
| chunk_index | integer | Orden dentro del documento |
| content | text | Texto extraído (~500 tokens por chunk) |
| token_count | integer | Pre-calculado |
| embedding | vector(1536) | **pgvector** - embedding para búsqueda semántica, índice HNSW |
| metadata | jsonb | Nro de página, sección, etc. |
| created_at | timestamp | |

**`conversations`** — SoftDeletes
| Columna | Tipo | Descripción |
|---|---|---|
| id | uuid (PK, uuidv7) | |
| tenant_id | uuid (FK → tenants) | |
| channel_id | uuid (FK → channels) | |
| contact_phone | string(20) | Teléfono del usuario final |
| contact_name | string(100), nullable | |
| status | string(20) | 'active' \| 'closed' \| 'escalated' |
| current_intent | string(50), nullable | |
| metadata | jsonb | País, intereses, info extra |
| messages_count | integer, default 0 | |
| total_input_tokens | integer, default 0 | |
| total_output_tokens | integer, default 0 | |
| last_message_at | timestamp, nullable | |
| timestamps | | |
| soft_deletes | | |

**`messages`** — Registro inmutable de mensajes
| Columna | Tipo | Descripción |
|---|---|---|
| id | bigint (PK) | |
| conversation_id | uuid (FK → conversations) | |
| tenant_id | uuid (FK → tenants) | Denormalizado |
| role | string(20) | 'user' \| 'assistant' \| 'system' |
| content | text | |
| tokens_input | integer, nullable | |
| tokens_output | integer, nullable | |
| model_used | string(100), nullable | |
| response_time_ms | integer, nullable | |
| metadata | jsonb | |
| created_at | timestamp | |

**`leads`** — Prospectos capturados por el bot — SoftDeletes
| Columna | Tipo | Descripción |
|---|---|---|
| id | bigint (PK) | |
| tenant_id | uuid (FK → tenants) | |
| conversation_id | uuid (FK → conversations) | |
| full_name | string(100), nullable | |
| country | string(50), nullable | |
| phone | string(20) | |
| email | string(100), nullable | |
| company_name | string(150), nullable | |
| interests | jsonb, default '[]' | |
| qualification_score | integer, nullable (1-10) | |
| status | string(20) | 'new' \| 'contacted' \| 'converted' \| 'lost' |
| notes | text, nullable | |
| converted_at | timestamp, nullable | |
| timestamps | | |
| soft_deletes | | |

**`escalations`** — Escalamientos a humano — SoftDeletes
| Columna | Tipo | Descripción |
|---|---|---|
| id | bigint (PK) | |
| tenant_id | uuid (FK → tenants) | |
| conversation_id | uuid (FK → conversations) | |
| reason | string(50) | |
| note | text, nullable | |
| assigned_to_user_id | bigint, nullable (FK → users) | |
| resolved_at | timestamp, nullable | |
| resolution_note | text, nullable | |
| timestamps | | |
| soft_deletes | | |

**`tenant_integrations`** — Integraciones externas del tenant (n8n, Zapier, etc.) — SoftDeletes
| Columna | Tipo | Descripción |
|---|---|---|
| id | bigint (PK) | |
| tenant_id | uuid (FK → tenants) | |
| name | string(100) | "n8n Principal", "Zapier CRM", etc. |
| provider | string(50) | 'n8n' \| 'zapier' \| 'make' \| 'custom' |
| url | string(500) | URL del webhook externo |
| secret | string(64), nullable | Secreto para firmar payloads (HMAC-SHA256) |
| events | jsonb | Array de eventos suscritos |
| status | string(20) | 'active' \| 'suspended' |
| is_primary | boolean, default false | Integración principal del tenant |
| last_triggered_at | timestamp, nullable | |
| failure_count | integer, default 0 | Para circuit breaker |
| metadata | jsonb | Configuración adicional del provider |
| timestamps | | |
| soft_deletes | | |

**`plans`** — Planes de suscripción de la plataforma — SoftDeletes
| Columna | Tipo | Descripción |
|---|---|---|
| id | bigint (PK) | |
| name | string(100) | "Basico", "Profesional", "Empresa" |
| slug | string(50), unique | "basico", "profesional", "empresa" |
| description | text, nullable | Descripción del plan |
| is_active | boolean, default true | |
| sort_order | integer, default 0 | Orden de presentación |
| metadata | jsonb | Config flexible del plan |
| timestamps | | |
| soft_deletes | | |

**`plan_prices`** — Precios por ciclo de facturación
| Columna | Tipo | Descripción |
|---|---|---|
| id | bigint (PK) | |
| plan_id | bigint (FK → plans) | |
| billing_interval | string(20) | 'quarterly' \| 'semi_annual' \| 'annual' |
| price_amount | integer | Monto en céntimos (15000 = S/ 150.00) |
| currency | string(3), default 'PEN' | Código ISO de moneda |
| external_price_id | string(100), nullable | ID del plan en MercadoPago/Stripe |
| is_active | boolean, default true | |
| timestamps | | |
| UNIQUE(plan_id, billing_interval, currency) | | |

**`plan_features`** — Límites y features por plan
| Columna | Tipo | Descripción |
|---|---|---|
| id | bigint (PK) | |
| plan_id | bigint (FK → plans) | |
| feature_slug | string(50) | 'max_channels', 'max_messages_month', 'max_knowledge_docs', etc. |
| value | string(100) | "2", "1000", "5", "unlimited" |
| timestamps | | |
| UNIQUE(plan_id, feature_slug) | | |

**`subscriptions`** — Suscripciones activas de tenants — SoftDeletes
| Columna | Tipo | Descripción |
|---|---|---|
| id | bigint (PK) | |
| tenant_id | uuid (FK → tenants) | |
| plan_id | bigint (FK → plans) | |
| plan_price_id | bigint (FK → plan_prices) | |
| status | string(20) | 'active' \| 'trialing' \| 'past_due' \| 'canceled' \| 'expired' |
| payment_provider | string(20) | 'mercadopago' \| 'stripe' \| 'manual' |
| external_subscription_id | string(100), nullable | ID en el sistema de pagos externo |
| trial_starts_at | timestamp, nullable | |
| trial_ends_at | timestamp, nullable | |
| current_period_starts_at | timestamp | |
| current_period_ends_at | timestamp | |
| canceled_at | timestamp, nullable | |
| grace_period_ends_at | timestamp, nullable | Activo hasta esta fecha después de cancelar |
| timestamps | | |
| soft_deletes | | |

**`subscription_usages`** — Tracking de uso por período de facturación
| Columna | Tipo | Descripción |
|---|---|---|
| id | bigint (PK) | |
| subscription_id | bigint (FK → subscriptions) | |
| tenant_id | uuid (FK → tenants) | |
| feature_slug | string(50) | 'messages_sent', 'conversations_created', 'knowledge_docs' |
| used | integer, default 0 | Uso acumulado en el período |
| period_starts_at | timestamp | Inicio del período |
| period_ends_at | timestamp | Fin del período |
| timestamps | | |
| UNIQUE(subscription_id, feature_slug, period_starts_at) | | |

**`payment_history`** — Historial de pagos (auditoría)
| Columna | Tipo | Descripción |
|---|---|---|
| id | bigint (PK) | |
| tenant_id | uuid (FK → tenants) | |
| subscription_id | bigint, nullable (FK → subscriptions) | |
| payment_provider | string(20) | 'mercadopago' \| 'stripe' \| 'manual' |
| external_payment_id | string(100), nullable | ID en el sistema de pagos externo |
| amount | integer | Monto en céntimos |
| currency | string(3) | 'PEN', 'USD', etc. |
| status | string(20) | 'pending' \| 'completed' \| 'failed' \| 'refunded' |
| description | string(200) | |
| paid_at | timestamp, nullable | |
| metadata | jsonb | Datos específicos del provider |
| timestamps | | |

### Modificaciones a tablas existentes

**`users`** — nueva migración — SoftDeletes:
- `is_super_admin` boolean, default false — identifica al dueño de la plataforma
- `current_tenant_id` uuid, nullable (FK → tenants) — tenant activo en sesión (null = contexto plataforma)
- `locale` string(10), default 'es' — idioma preferido del usuario para el panel (`es`, `en`, `pt_BR`)
- `deleted_at` timestamp, nullable — soft delete
- Agregar trait `HasRoles` de Spatie Permission
- Agregar trait `SoftDeletes`

---

## Estructura de Archivos

```
app/
├── Ai/
│   ├── Agents/
│   │   └── TenantChatAgent.php             # Agente principal - instrucciones dinámicas por tenant/canal
│   ├── Tools/
│   │   ├── SendMedia.php                   # Tool: envía imágenes, documentos, enlaces de video por WhatsApp
│   │   ├── CaptureLead.php                 # Tool: extrae y guarda info del prospecto (activo en canales sales)
│   │   └── EscalateToHuman.php             # Tool: detecta y activa escalación a humano
│   └── Middleware/
│       └── TrackTokenUsage.php             # Middleware: registra tokens y costos por conversación
│
├── Models/
│   ├── User.php                            # Modificar: HasRoles, SoftDeletes, isSuperAdmin()
│   ├── Tenant.php                          # SoftDeletes, LogsActivity
│   ├── Channel.php                         # BelongsToTenant, SoftDeletes, LogsActivity
│   ├── Conversation.php                    # BelongsToTenant, SoftDeletes
│   ├── Message.php                         # BelongsToTenant (sin soft delete, registro inmutable)
│   ├── Lead.php                            # BelongsToTenant, SoftDeletes, LogsActivity
│   ├── Escalation.php                      # BelongsToTenant, SoftDeletes
│   ├── KnowledgeDocument.php               # BelongsToTenant, SoftDeletes, LogsActivity
│   ├── KnowledgeChunk.php                  # BelongsToTenant (cascade delete con documento)
│   ├── TenantIntegration.php               # BelongsToTenant, SoftDeletes
│   ├── Plan.php                            # SoftDeletes, LogsActivity
│   ├── PlanPrice.php                       # Pertenece a Plan
│   ├── PlanFeature.php                     # Pertenece a Plan
│   ├── Subscription.php                    # BelongsToTenant, SoftDeletes, LogsActivity
│   ├── SubscriptionUsage.php               # Pertenece a Subscription
│   ├── PaymentHistory.php                  # BelongsToTenant (registro inmutable de auditoría)
│   ├── Concerns/
│   │   └── BelongsToTenant.php             # Trait: global scope + auto-assign tenant_id
│   ├── Scopes/
│   │   └── TenantScope.php                 # Global scope que filtra por tenant_id
│   └── Enums/
│       ├── ConversationStatus.php          # Active, Closed, Escalated
│       ├── ChannelType.php                 # Sales (gestión de prospectos), Support (soporte al cliente)
│       ├── WhatsAppProviderType.php        # YCloud, MetaCloud
│       ├── DocumentStatus.php              # Pending, Processing, Ready, Failed
│       ├── LeadStatus.php                  # New, Contacted, Converted, Lost
│       ├── IntegrationStatus.php           # Active, Suspended
│       ├── IntegrationProvider.php         # N8n, Zapier, Make, Custom
│       ├── WebhookEvent.php                # ConversationStarted, MessageReceived, LeadCaptured, etc.
│       ├── BillingInterval.php             # Quarterly, SemiAnnual, Annual
│       ├── SubscriptionStatus.php          # Active, Trialing, PastDue, Canceled, Expired
│       ├── PaymentProviderType.php         # MercadoPago, Stripe, Manual
│       └── PaymentStatus.php              # Pending, Completed, Failed, Refunded
│
├── Services/
│   ├── Tenant/
│   │   └── TenantContext.php               # Singleton: tenant actual + setPermissionsTeamId()
│   ├── WhatsApp/
│   │   ├── Contracts/
│   │   │   └── WhatsAppProvider.php        # Interfaz abstracta
│   │   ├── YCloudProvider.php              # Implementación YCloud (api.ycloud.com/v2)
│   │   ├── WhatsAppWebhookHandler.php      # Valida y parsea webhooks entrantes
│   │   └── InboundMessage.php              # DTO para mensaje entrante normalizado
│   ├── Document/
│   │   ├── DocumentProcessor.php           # Orquesta: extraer → chunk → embeddings → guardar
│   │   ├── TextExtractor.php               # Extrae texto (PDF via smalot/pdfparser)
│   │   └── TextChunker.php                 # Divide texto en chunks de ~500 tokens
│   ├── Billing/
│   │   ├── Contracts/
│   │   │   └── PaymentProvider.php         # Interfaz abstracta de pagos
│   │   ├── MercadoPagoProvider.php         # Implementación MercadoPago
│   │   ├── ManualPaymentProvider.php       # Pagos manuales (transferencia bancaria)
│   │   ├── PlanFeatureGate.php             # Verifica acceso a features según plan
│   │   └── SubscriptionManager.php         # Gestiona ciclo de vida de suscripciones
│   └── Automation/
│       └── IntegrationEventDispatcher.php  # Envía eventos a integraciones externas del tenant
│
├── Events/
│   ├── ConversationStarted.php
│   ├── MessageReceived.php
│   ├── LeadCaptured.php
│   ├── EscalationTriggered.php
│   └── ConversationClosed.php
│
├── Listeners/
│   └── DispatchTenantIntegrationEvents.php # Envía eventos a integraciones del tenant
│
├── Http/
│   ├── Controllers/
│   │   ├── WhatsAppWebhookController.php       # Recibe webhooks de YCloud/Meta
│   │   └── Api/
│   │       ├── AutomationApiController.php     # API para n8n/Zapier (autenticada con Sanctum)
│   │       └── PaymentWebhookController.php    # Recibe webhooks de MercadoPago/Stripe
│   └── Middleware/
│       ├── SetCurrentTenant.php            # Resuelve tenant + setPermissionsTeamId()
│       ├── EnsureTenantContext.php          # Requiere current_tenant_id activo
│       ├── EnsureSuperAdmin.php            # Protege rutas de plataforma
│       └── SetLocale.php                   # Aplica App::setLocale() según user.locale
│
├── Jobs/
│   ├── ProcessIncomingMessage.php          # Cola: mensaje → Agent → respuesta → enviar
│   ├── SendWhatsAppMessage.php             # Cola: enviar mensaje vía provider
│   ├── ProcessDocument.php                 # Cola: archivo → chunks → embeddings
│   └── DispatchIntegrationEvent.php        # Cola: enviar evento a integración externa
│
└── Livewire/
    ├── Dashboard.php                       # Dashboard principal del tenant
    ├── Channels/
    │   └── ChannelManager.php              # CRUD de canales WhatsApp + config provider
    ├── Conversations/
    │   ├── ConversationList.php            # Lista con filtros
    │   └── ConversationDetail.php          # Vista tipo chat
    ├── Knowledge/
    │   └── KnowledgeManager.php            # Upload de archivos, ver chunks, estado de procesamiento
    ├── Prompts/
    │   └── PromptEditor.php                # Editor grande de prompt del tenant + overrides por canal
    ├── Leads/
    │   └── LeadsList.php                   # Lista de prospectos
    ├── Escalations/
    │   └── EscalationQueue.php             # Cola de escalaciones
    ├── Integrations/
    │   └── IntegrationManager.php          # CRUD de integraciones: registrar, principal, suspender, dar de baja
    ├── Team/
    │   └── TeamManager.php                 # Invitar usuarios, asignar roles Spatie
    ├── Billing/
    │   ├── SubscriptionManager.php         # Ver plan actual, cambiar plan, ver uso
    │   └── PaymentHistory.php              # Historial de pagos
    ├── Analytics/
    │   └── AnalyticsDashboard.php          # Métricas y gráficos
    └── Platform/                           # Solo super-admin (contexto plataforma)
        ├── PlatformDashboard.php           # Métricas globales de la plataforma
        ├── TenantIndex.php                 # Listar todos los tenants
        ├── TenantDetail.php                # Detalle de un tenant + switch context
        ├── PlanManager.php                 # CRUD de planes y precios
        └── PlatformBilling.php             # Billing global: suscripciones, pagos, métricas

lang/                                           # Traducciones multilenguaje
├── es/                                         # Español latinoamericano (idioma por defecto)
│   ├── messages.php                            # Textos generales de la app
│   ├── dashboard.php                           # Textos del dashboard
│   ├── channels.php                            # Textos de gestión de canales
│   ├── knowledge.php                           # Textos de base de conocimiento
│   ├── billing.php                             # Textos de facturación y planes
│   └── platform.php                            # Textos del panel super-admin
├── en/                                         # Inglés
│   └── (mismos archivos que es/)
├── pt_BR/                                      # Portugués brasileño
│   └── (mismos archivos que es/)
└── (archivos auto-generados por Laravel Lang)  # Validaciones, auth, pagination, passwords
```

---

## Archivos Existentes a Modificar

| Archivo | Cambio |
|---|---|
| `app/Models/User.php` | Agregar traits `HasRoles` y `SoftDeletes`, `isSuperAdmin()`, `currentTenant()`, `is_super_admin`, `current_tenant_id`, `locale` |
| `app/Providers/AppServiceProvider.php` | Registrar `TenantContext` singleton, bind `WhatsAppProvider`, bind `PaymentProvider`, `Gate::before()` para super_admin, registrar Pennant features |
| `bootstrap/app.php` | Agregar API routing, registrar middlewares: `SetCurrentTenant`, `EnsureTenantContext`, `EnsureSuperAdmin`, `SetLocale` |
| `config/ai.php` | Configurar providers IA |
| `config/permission.php` | Habilitar teams: `'teams' => true`, `'team_foreign_key' => 'tenant_id'` |
| `config/activitylog.php` | Configurar log name default, causer model |
| `config/queue.php` | Configurar conexión `database`, definir colas `high`, `default`, `low` |
| `config/localization.php` | Configurar locales soportados: `es`, `en`, `pt_BR`; detección por sesión/modelo |
| `config/app.php` | `locale => 'es'`, `fallback_locale => 'en'`, `available_locales => ['es', 'en', 'pt_BR']` |
| `database/factories/UserFactory.php` | Agregar states: `superAdmin()`, `withTenant()` |
| `database/seeders/DatabaseSeeder.php` | Seed: tenant RumiStar (is_platform_owner), roles + permisos, usuario super_admin + tenant_owner, planes + precios + features |
| `resources/views/layouts/app/sidebar.blade.php` | Navegación dual: plataforma vs tenant, con `@can` directives |
| `routes/web.php` | Rutas tenant con `EnsureTenantContext` + rutas platform con `EnsureSuperAdmin` |
| `routes/api.php` | Rutas webhook WhatsApp + API automatización (Sanctum) + webhooks de pago |

---

## Fases de Implementación

### Fase 0: Fundación Multi-Tenant + Paquetes Base
- Instalar `laravel/ai`, publicar config, migrar tablas del SDK
- Instalar `spatie/laravel-permission`, publicar config, habilitar `teams => true` con `team_foreign_key => 'tenant_id'`, migrar
- Instalar `spatie/laravel-activitylog`, publicar migraciones, migrar
- Instalar `laravel/pennant`, publicar migraciones, migrar
- Configurar `QUEUE_CONNECTION=database` y colas por prioridad: `high`, `default`, `low`
- Instalar `laravel-lang/common`, ejecutar `php artisan lang:update`, agregar locales `es`, `en`, `pt_BR`
- Instalar `smalot/pdfparser`
- Habilitar extensión `pgvector` en PostgreSQL: `CREATE EXTENSION IF NOT EXISTS vector`
- Crear modelo `Tenant` con migración (SoftDeletes, `is_platform_owner`), factory, seeder (con trait `LogsActivity`)
- Crear migración para modificar `users`: add `is_super_admin`, `current_tenant_id`, `locale`, `deleted_at`
- Agregar traits `HasRoles` y `SoftDeletes` al modelo `User`
- Crear trait `BelongsToTenant` y `TenantScope`
- Crear servicio `TenantContext` (singleton que llama `setPermissionsTeamId()`)
- Crear middleware `SetCurrentTenant`, `EnsureTenantContext`, `EnsureSuperAdmin` y `SetLocale`
- Registrar middleware en `bootstrap/app.php`
- Crear `routes/api.php` y registrarlo en `bootstrap/app.php`
- Crear todos los Enums
- Crear seeder de roles y permisos: `tenant_owner`, `tenant_admin`, `tenant_member` con sus permisos granulares
- Seed del primer tenant: RumiStar E.I.R.L. con `is_platform_owner: true`, `system_prompt` sobre iTrade, dos canales de ejemplo: "Ventas iTrade" (type: sales) y "Soporte iTrade" (type: support)
- Seed del usuario super-admin: `is_super_admin: true` + rol `tenant_owner` en tenant RumiStar
- Configurar `Gate::before()` en AppServiceProvider para super_admin
- Configurar route groups: `/platform/*` y tenant-scoped
- Configurar `config/localization.php`: locales `es`, `en`, `pt_BR`; detección por sesión/modelo
- Configurar `config/app.php`: `locale => 'es'`, `fallback_locale => 'en'`
- Crear archivos de traducción base en `lang/es/`, `lang/en/`, `lang/pt_BR/` para textos custom de la app
- Agregar `"@php artisan lang:update"` al `post-update-cmd` en `composer.json`
- Selector de idioma en perfil de usuario (Livewire)
- **Tests**: tenant scoping, auto-assign tenant_id, isolation entre tenants, roles por tenant, super-admin acceso global, soft deletes, cambio de locale

### Fase 1: Canales + WhatsApp vía YCloud
- Crear interfaz `WhatsAppProvider` con métodos para enviar/recibir
- Crear `YCloudProvider` implementando la interfaz (usando `api.ycloud.com/v2`)
- Crear modelo `Channel` con migración (SoftDeletes), factory (trait `BelongsToTenant`, `LogsActivity`)
- Crear rutas webhook: `GET/POST /api/webhooks/whatsapp/{tenant_uuid}/{channel_slug}`
- Crear `WhatsAppWebhookHandler` (validación, parsing de payload YCloud)
- Crear `WhatsAppWebhookController` (maneja verify GET + receive POST)
- Crear DTO `InboundMessage` para normalizar payloads
- Crear modelos `Conversation` (SoftDeletes) y `Message` con migraciones, factories
- Crear jobs `ProcessIncomingMessage` y `SendWhatsAppMessage`
- Rate limiting en rutas webhook
- **Tests**: validación webhook, creación de conversación, envío mockeado

### Fase 2: Agente IA con Laravel AI SDK
- Crear `TenantChatAgent` (implements Agent, Conversational, HasTools, HasMiddleware)
- Implementar `instructions()`: base prompt + tenant.system_prompt + channel.system_prompt_override (el override define si el bot es vendedor o soporte)
- Implementar `messages()`: cargar historial desde tabla messages
- Crear tool `SendMedia`: el LLM decide cuándo enviar una imagen, documento o enlace de video por WhatsApp
- Crear tool `CaptureLead`: el LLM extrae datos del prospecto y los guarda (solo activo en canales `sales`)
- Crear tool `EscalateToHuman`: el LLM detecta cuándo escalar y activa el proceso
- Crear middleware `TrackTokenUsage`: registra tokens y costos en la conversación
- SimilaritySearch filtra knowledge chunks por `channel_scope` (cada canal accede solo a su base de conocimiento)
- Integrar Agent en job `ProcessIncomingMessage`: recibir → Agent.prompt() → guardar → enviar
- Configurar failover: `provider: [Lab::OpenAI, Lab::Anthropic]`
- **Tests**: `TenantChatAgent::fake()`, `assertPrompted()`, respuestas mockeadas, verificar que sales captura leads y support no

### Fase 3: Base de Conocimiento con Embeddings (RAG)
- Crear modelos `KnowledgeDocument` (SoftDeletes) y `KnowledgeChunk` con migraciones
- Migración KnowledgeChunk: `$table->vector('embedding', dimensions: 1536)->index()` (HNSW)
- Crear `DocumentProcessor`, `TextExtractor`, `TextChunker`
- Crear job `ProcessDocument`: extraer texto → chunks → `Embeddings::for($chunks)->generate()` → guardar
- Agregar `SimilaritySearch::usingModel(KnowledgeChunk::class, 'embedding')` al Agent tools
- Livewire: componente de upload con estado de procesamiento
- **Tests**: pipeline document → chunks → embeddings, `Embeddings::fake()`, `SimilaritySearch` mockeado

### Fase 4: Panel Admin del Tenant (Livewire + Flux UI)
- **Dashboard**: stats de conversaciones, chats activos, escalaciones, costos IA
- **Gestión de canales**: CRUD con soft delete, selección de provider (YCloud), config de credenciales (`@can('manage-channels')`)
- **Editor de prompts**: textarea grande para system_prompt del tenant + override por canal (`@can('manage-prompts')`)
- **Lista de conversaciones**: filtros por canal, status, fecha, búsqueda (`@can('view-conversations')`)
- **Detalle de conversación**: vista tipo chat con mensajes, metadata, tokens usados
- **Base de conocimiento**: upload de archivos, progreso, ver chunks, eliminar con soft delete (`@can('manage-knowledge')`)
- **Gestión de equipo**: invitar usuarios, asignar roles Spatie por tenant (`@can('manage-team')`)
- **Activity Log viewer**: historial de cambios importantes del tenant
- Todo scoped por `BelongsToTenant` + permisos Spatie
- Sidebar navigation con `@can` directives

### Fase 5: Leads, Escalaciones y Notificaciones
- Crear modelos `Lead` (SoftDeletes, `LogsActivity`) y `Escalation` (SoftDeletes) con migraciones
- El tool `CaptureLead` del Agent extrae datos → crear/actualizar Lead
- El tool `EscalateToHuman` detecta → crear Escalation + notificación
- Notificaciones Discord webhook para escalaciones
- Livewire: lista de leads (`@can('view-leads')`), cola de escalaciones (`@can('manage-escalations')`)
- **Tests**

### Fase 6: Integraciones y API de Automatización
- Instalar `laravel/sanctum` y `spatie/laravel-query-builder`
- Crear modelo `TenantIntegration` (SoftDeletes) con migración y factory
- Livewire `IntegrationManager`: registrar integración, marcar como principal (`is_primary`), suspender, dar de baja (soft delete), reactivar (`@can('manage-integrations')`)
- Crear Laravel Events: ConversationStarted, MessageReceived, LeadCaptured, EscalationTriggered, ConversationClosed
- Crear listener `DispatchTenantIntegrationEvents`
- Crear job `DispatchIntegrationEvent` (con retry y circuit breaker)
- Crear `AutomationApiController` con endpoints autenticados por Sanctum
- Gestión de API tokens por tenant en el panel
- Registrar Pennant features para gating de integraciones por plan
- **Tests**: dispatch de eventos, payload format, API endpoints, Sanctum auth

### Fase 7: Billing y Suscripciones
- Instalar `mercadopago/dx-php`
- Crear modelos: `Plan` (SoftDeletes), `PlanPrice`, `PlanFeature`, `Subscription` (SoftDeletes), `SubscriptionUsage`, `PaymentHistory` con migraciones y factories
- Crear interfaz `PaymentProvider` con `MercadoPagoProvider` y `ManualPaymentProvider`
- Crear `PlanFeatureGate` service: verifica acceso a features según plan activo
- Crear `SubscriptionManager` service: gestiona ciclo de vida (crear, cambiar plan, cancelar, renovar)
- Crear `PaymentWebhookController` para recibir notificaciones de MercadoPago
- Integrar Pennant features con `PlanFeatureGate`
- Livewire: `SubscriptionManager` (ver plan, cambiar, ver uso) y `PaymentHistory` (historial de pagos)
- Seeder de planes: Basico, Profesional, Empresa con precios trimestrales, semestrales y anuales
- Tenants con `is_platform_owner: true` bypasean todo billing
- **Tests**: feature gating, ciclo de suscripción, webhooks de pago, usage tracking

### Fase 8: Panel Super-Admin (Plataforma)
- Livewire pages bajo `/platform/*`:
  - `PlatformDashboard`: métricas globales (tenants activos, revenue, mensajes procesados, costos IA)
  - `TenantIndex`: listar todos los tenants con filtros, suspender/reactivar (soft delete)
  - `TenantDetail`: detalle del tenant + botón "Entrar como este tenant" (switch context)
  - `PlanManager`: CRUD de planes, precios y features
  - `PlatformBilling`: resumen de suscripciones, pagos, morosidad
- Switch de contexto: super-admin entra/sale de tenants con actualización de `current_tenant_id`
- Analytics a nivel plataforma
- Protegido con middleware `EnsureSuperAdmin`

### Fase 9: Hardening para Producción
- Instalar `maatwebsite/excel`, `spatie/laravel-backup`, `laravel/pulse`
- Rate limiting por tenant, canal, y endpoint
- Auditoría de aislamiento de tenants (verificar cada query path)
- Manejo de errores y retry para APIs externas (YCloud, OpenAI, MercadoPago)
- Logging por tenant con contexto
- Encriptación de credenciales con `Crypt::encryptString()`
- Backups automáticos a S3 con rotación
- Exportaciones Excel/CSV para leads, conversaciones, analytics
- Dashboard Pulse para monitoreo de performance
- **Migración a Redis + Horizon** (según demanda): instalar `laravel/horizon`, cambiar `QUEUE_CONNECTION=redis`, configurar workers por cola
- Tests end-to-end

---

## Seguridad

- Toda query tenant-scoped pasa por el global scope `TenantScope`
- Credenciales de providers encriptadas con `Crypt::encryptString()`
- Validación de webhook en cada request entrante
- Rate limiting por tenant por canal
- Spatie Permission + teams para autorización granular por tenant
- Super-admin con `is_super_admin` boolean + `Gate::before()` (fuera del scope de teams)
- Spatie Activity Log para auditoría de cambios críticos
- Sanitizar inputs del usuario antes de enviar al LLM
- Logs auditables de todas las conversaciones
- Archivos en S3 organizados por tenant UUID
- Integraciones outbound firmadas con HMAC-SHA256
- Sanctum tokens scoped por tenant para API de automatización
- SoftDeletes en tablas críticas para recuperación de datos
- Webhooks de pago validados con firma del provider

---

## Verificación

1. **Tenant isolation**: Crear 2 tenants, verificar que datos de uno son invisibles para el otro
2. **Soft deletes**: Eliminar tenant → verificar que datos quedan accesibles con `withTrashed()`, inaccesibles sin él
3. **Super-admin flow**: Login → panel plataforma → switch a tenant → operar → salir a plataforma
4. **Roles y permisos**: Verificar que `tenant_member` solo ve, `tenant_owner` gestiona todo, `super_admin` accede a todo
5. **Activity Log**: Cambiar system_prompt → verificar log con old/new values y causer
6. **Agent flow (sales)**: Crear Agent en canal ventas → verificar respuesta persuasiva, captura de lead, envío de imágenes/videos del producto
7. **Agent flow (support)**: Crear Agent en canal soporte → verificar respuesta didáctica, paso a paso, envío de guías instructivas
8. **Webhook flow**: Simular payload YCloud → conversación creada → Agent responde → mensaje enviado
9. **Knowledge RAG**: Subir PDF → chunks + embeddings → Agent usa SimilaritySearch. Verificar channel_scope (docs de ventas solo en canal ventas)
10. **Integraciones**: Registrar integración → marcar como principal → suspender → dar de baja → reactivar
11. **Billing flow**: Crear suscripción → verificar feature gating → uso incrementa → cambiar plan → cancelar con grace period
12. **Pennant features**: Tenant sin plan → features bloqueadas. Tenant con plan Profesional → features del plan activas
13. **n8n integration**: Lead capturado → evento → integración → n8n → API de vuelta (Sanctum)
14. **Multilenguaje**: Cambiar locale del usuario → verificar que UI cambia de idioma. Verificar traducciones en es, en, pt_BR
15. **Tests**: `php artisan test --compact` en cada fase
16. **Pint**: `vendor/bin/pint --dirty --format agent` antes de cada commit

---

## Variables de Entorno

```env
# Laravel AI SDK - Providers
OPENAI_API_KEY=
ANTHROPIC_API_KEY=
GEMINI_API_KEY=

# Rumibot Platform Config
RUMIBOT_DEFAULT_AI_PROVIDER=openai
RUMIBOT_DEFAULT_AI_MODEL=gpt-4o-mini
RUMIBOT_EMBEDDING_MODEL=text-embedding-3-small
RUMIBOT_EMBEDDING_DIMENSIONS=1536

# Colas (database driver inicial, migrar a redis cuando sea necesario)
QUEUE_CONNECTION=database

# S3 para documentos de tenants
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=
AWS_BUCKET=

# MercadoPago
MERCADOPAGO_ACCESS_TOKEN=
MERCADOPAGO_PUBLIC_KEY=
MERCADOPAGO_WEBHOOK_SECRET=

# Discord (escalaciones de la plataforma)
DISCORD_WEBHOOK_URL=
```

Las API keys de YCloud, credenciales de WhatsApp y tokens de Sanctum se almacenan **por canal/tenant** en sus respectivas tablas, encriptadas.

---

## Dependencias

```bash
# Fase 0 - Fundación
composer require laravel/ai                        # Motor IA: Agents, Tools, Embeddings, RAG
composer require spatie/laravel-permission          # Roles y permisos con teams/multi-tenant
composer require spatie/laravel-activitylog         # Auditoría de cambios en modelos
composer require laravel/pennant                    # Feature flags por plan
composer require laravel-lang/common                # Traducciones oficiales (Framework, Fortify, etc.)
composer require smalot/pdfparser                  # Extracción de texto de PDFs

# Fase 6 - API
composer require laravel/sanctum                    # Tokens API para integraciones
composer require spatie/laravel-query-builder       # Filtros API REST

# Fase 7 - Billing
composer require mercadopago/dx-php                 # SDK MercadoPago para LATAM

# Fase 9 - Producción y escalamiento
composer require maatwebsite/excel                  # Exportaciones Excel/CSV
composer require spatie/laravel-backup              # Backups automáticos
composer require laravel/pulse                      # Monitoreo de performance
composer require laravel/horizon                    # Monitoreo de colas (cuando se migre a Redis)
```

---

## Fuentes

- [Laravel AI SDK](https://laravel.com/docs/12.x/ai-sdk)
- [Laravel Queues](https://laravel.com/docs/12.x/queues)
- [Laravel Horizon](https://laravel.com/docs/12.x/horizon) (para migración a Redis)
- [Laravel Pennant](https://laravel.com/docs/12.x/pennant)
- [Laravel Sanctum](https://laravel.com/docs/12.x/sanctum)
- [Laravel Pulse](https://laravel.com/docs/12.x/pulse)
- [Laravel Lang](https://laravel-lang.com/introduction.html) | [Configuration](https://laravel-lang.com/configuration.html)
- [Laravel Cashier / Billing](https://laravel.com/docs/12.x/billing)
- [Spatie Permission v7](https://spatie.be/docs/laravel-permission/v7/introduction) | [Teams](https://spatie.be/docs/laravel-permission/v7/basic-usage/teams-permissions)
- [Spatie Activity Log v4](https://spatie.be/docs/laravel-activitylog/v4/introduction)
- [Spatie Query Builder v6](https://spatie.be/docs/laravel-query-builder/v6/introduction)
- [Spatie Laravel Backup v9](https://spatie.be/docs/laravel-backup/v9/introduction)
- [Maatwebsite Excel](https://docs.laravel-excel.com/3.1/getting-started/)
- [MercadoPago API](https://www.mercadopago.com/developers/en/docs/subscriptions/integration-configuration/subscription-associated-plan)
- [MercadoPago PHP SDK](https://github.com/mercadopago/sdk-php)
- [YCloud WhatsApp API](https://docs.ycloud.com/reference/whatsapp-messaging-examples)
- [YCloud Webhooks](https://docs.ycloud.com/reference/whatsapp-inbound-message-webhook-examples)
- [YCloud Pricing](https://www.ycloud.com/pricing)
- [n8n Webhook Node](https://docs.n8n.io/integrations/builtin/core-nodes/n8n-nodes-base.webhook/)
- [n8n WhatsApp Integration](https://n8n.io/integrations/webhook/and/whatsapp-business-cloud/)
