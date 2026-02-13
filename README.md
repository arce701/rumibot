# Rumibot

Plataforma SaaS multi-tenant de chatbots de IA para WhatsApp, diseñada para negocios latinoamericanos.

## Funcionalidades

- **Chatbot IA por WhatsApp** - Agente conversacional con RAG (Retrieval-Augmented Generation) que responde usando la base de conocimiento del negocio
- **Canales Sales & Support** - Cada tenant configura canales de ventas (persuasivo) y soporte (didáctico) con personalidad independiente
- **Captura de Leads** - El bot extrae datos del prospecto durante la conversación y los guarda automáticamente
- **Base de Conocimiento** - Upload de PDFs y documentos, procesados en chunks con embeddings (pgvector) para búsqueda semántica
- **Escalación a Humano** - Detección automática de cuándo transferir a un agente humano
- **Integraciones Externas** - Webhooks outbound a n8n, Zapier, Make con firma HMAC-SHA256
- **API REST** - Endpoints autenticados con Sanctum para automatización externa
- **Billing** - Suscripciones con MercadoPago (trimestral, semestral, anual) y pagos manuales
- **Multi-idioma** - Español, inglés y portugués brasileño
- **Panel Super-Admin** - Gestión global de tenants, planes y billing de la plataforma
- **Feature Flags** - Control de funcionalidades por plan de suscripción via Laravel Pennant
- **Exportaciones** - Leads y conversaciones a Excel/CSV
- **Monitoreo** - Laravel Pulse para performance, backups automáticos a S3

## Stack

| Componente | Tecnología |
|-----------|------------|
| Framework | Laravel 12 |
| Frontend | Livewire 4 + Flux UI Free v2 + Tailwind CSS v4 |
| Base de Datos | PostgreSQL + pgvector |
| IA | Laravel AI SDK (OpenAI, Anthropic, Gemini) |
| Auth | Laravel Fortify + Sanctum |
| WhatsApp | YCloud (Business API) |
| Pagos | MercadoPago |
| Testing | Pest 4 |
| Colas | Database driver (Redis + Horizon planificado) |

## Requisitos

- PHP 8.4+
- PostgreSQL 15+ con extensión pgvector
- Node.js 20+
- Composer 2+

## Instalación

```bash
# Clonar repositorio
git clone <repo-url> rumibot
cd rumibot

# Instalar dependencias
composer install
npm install

# Configurar entorno
cp .env.example .env
php artisan key:generate

# Configurar base de datos PostgreSQL en .env, luego:
php artisan migrate --seed

# Compilar assets
npm run build

# Iniciar worker de colas
php artisan queue:work --queue=high,default,low
```

## Desarrollo

```bash
# Servidor de desarrollo (Herd o Valet sirven automáticamente en rumibot.test)
npm run dev

# Ejecutar tests
php artisan test --compact

# Formatear código
vendor/bin/pint

# Procesar colas en desarrollo
php artisan queue:work --queue=high,default,low
```

## Estructura del Proyecto

```
app/
├── Ai/              # Agente IA, tools (CaptureLead, EscalateToHuman, SendMedia)
├── Exports/         # Exportaciones Excel (Leads, Conversations)
├── Http/
│   ├── Controllers/ # Webhook controllers (WhatsApp, Payments, API)
│   └── Middleware/   # Tenant, auth, locale, logging
├── Jobs/            # ProcessIncomingMessage, SendWhatsApp, ProcessDocument, DispatchIntegration
├── Livewire/        # Componentes UI (Dashboard, Channels, Conversations, Leads, Billing, Platform)
├── Models/          # 16 modelos con enums, traits (BelongsToTenant), scopes (TenantScope)
├── Services/        # Tenant, WhatsApp, Document, Billing, Discord
├── Events/          # Eventos de negocio (ConversationStarted, LeadCaptured, etc.)
└── Listeners/       # Dispatch de eventos a integraciones externas
docs/
├── plan.md              # Especificación completa del proyecto
└── architecture-map.md  # Mapa de arquitectura para referencia
```

## Documentación

- **[docs/plan.md](docs/plan.md)** - Especificación completa: misión, arquitectura, esquema de DB, fases de implementación
- **[docs/architecture-map.md](docs/architecture-map.md)** - Mapa de arquitectura: índice de archivos, patrones, operaciones comunes

## Tests

323+ tests cubriendo:

- Autenticación y 2FA
- Aislamiento de tenants (11 modelos)
- WhatsApp webhooks y messaging
- Agente IA y tools
- Knowledge base (RAG)
- Billing y suscripciones
- Rate limiting y seguridad
- Exportaciones
- Panel super-admin

```bash
php artisan test --compact
```

## Licencia

Propietario. Todos los derechos reservados.
