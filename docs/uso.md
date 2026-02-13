1. Ingresar

1. Abre http://rumibot.test/login
2. Credenciales:
   - Email: rumibot8@gmail.com
   - Password: Rumi2026$

Al ingresar llegaras al Dashboard del tenant RumiStar.

  ---
2. Navegacion

Como super-admin tienes acceso a dos contextos:

- Panel Tenant (sidebar izquierdo) - Dashboard, Canales, Conversaciones, Leads, Escalaciones, Knowledge Base, Prompts,
  Integraciones, Billing, Equipo, Activity Log
- Panel Plataforma (/platform) - Gestion global de tenants, planes, billing de la plataforma

  ---
3. Configurar tu primer canal WhatsApp

1. Ve a Canales en el sidebar
2. Crea un canal nuevo con:
   - Nombre: ej. "Ventas iTrade"
   - Tipo: Sales (para ventas) o Support (para soporte)
   - Provider: YCloud
   - API Key: tu API key de YCloud
   - Phone Number ID: el ID de tu numero de WhatsApp en YCloud
   - Webhook Verify Token: un token aleatorio que configuraras tambien en YCloud
3. Guarda el canal

La URL del webhook para configurar en YCloud sera:
https://rumibot.test/api/webhooks/whatsapp/{tenant-uuid}/{channel-slug}
(El UUID del tenant lo ves en la URL o en el panel de plataforma)

  ---
4. Configurar el prompt del bot

1. Ve a Prompts en el sidebar
2. Edita el System Prompt del tenant - aqui describes tu negocio, productos, precios, tono
3. Opcionalmente edita el System Prompt Override del canal para ajustar la personalidad (vendedor vs soporte)

  ---
5. Subir Knowledge Base

1. Ve a Knowledge Base en el sidebar
2. Sube PDFs, documentos de tu negocio (catalogos, manuales, precios, FAQ)
3. El sistema los procesa automaticamente: extrae texto, crea chunks y genera embeddings
4. El bot usara esta informacion para responder preguntas

  ---
6. Configurar IA

En tu archivo .env configura la API key del proveedor de IA:

OPENAI_API_KEY=sk-...
# o
ANTHROPIC_API_KEY=sk-ant-...

El modelo por defecto es gpt-4o-mini. Puedes cambiarlo por tenant en la configuracion.

  ---
7. Iniciar el worker de colas

Los mensajes se procesan en background:

php artisan queue:work --queue=high,default,low

Esto debe estar corriendo para que el bot responda mensajes de WhatsApp.

  ---
8. Panel de Plataforma (super-admin)

Visita http://rumibot.test/platform para:

- Ver metricas globales
- Gestionar tenants (crear, suspender, entrar como tenant)
- Administrar planes y precios
- Ver billing global

  ---
9. Monitoreo

- Pulse: http://rumibot.test/pulse (performance, queries lentas, jobs, excepciones)
- Logs: storage/logs/laravel-*.log (con tenant_id y user_id en cada entrada)

  ---
Flujo completo del bot

Usuario envia WhatsApp → YCloud webhook → Rumibot recibe →
Job ProcessIncomingMessage → AI Agent consulta Knowledge Base →
Genera respuesta → Job SendWhatsAppMessage → YCloud envia → Usuario recibe
