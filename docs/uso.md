# Guia de uso - Rumibot

---

## 1. Registro / Ingreso

### Registro de nuevo tenant
1. Abre http://rumibot.test/register
2. Completa: Nombre de empresa, Nombre, Email, Contraseña
3. Al registrarte se crea automaticamente tu tenant (empresa) y quedas como owner

### Ingreso como super-admin (desarrollo)
1. Abre http://rumibot.test/login
2. Credenciales:
   - Email: rumibot8@gmail.com
   - Password: Rumi2026$

Al ingresar llegaras al Dashboard del tenant RumiStar.

---

## 2. Navegacion

Como super-admin tienes acceso a dos contextos:

- **Panel Tenant** (sidebar izquierdo) — Dashboard, Canales, Configuracion AI, Prompts, Knowledge Base, Agent Playground, Conversaciones, Leads, Escalaciones, Integraciones, Equipo, Billing, Activity Log
- **Panel Plataforma** (/platform) — Gestion global de tenants, planes, billing de la plataforma

El sidebar sigue un flujo de trabajo logico: primero configuras el canal, luego el AI, luego el prompt, luego la knowledge base, y finalmente pruebas en el playground.

---

## 3. Crear canal WhatsApp

1. Ve a **Canales** en el sidebar
2. Crea un canal nuevo con:
   - Nombre: ej. "Ventas iTrade" (el slug se genera automaticamente)
   - Tipo: Sales (para ventas) o Support (para soporte)
   - Provider: YCloud
   - API Key del provider: tu API key de YCloud
   - Phone Number ID: el ID de tu numero de WhatsApp en YCloud
   - Webhook Verify Token: un token aleatorio que configuraras tambien en YCloud
3. Guarda el canal

La URL del webhook para configurar en YCloud sera:
`https://rumibot.test/api/webhooks/whatsapp/{tenant-uuid}/{channel-slug}`
(El UUID del tenant lo ves en la URL o en el panel de plataforma)

---

## 4. Configurar proveedor AI (obligatorio)

**IMPORTANTE:** No hay proveedor AI por defecto. Debes configurar una credencial LLM antes de que el bot pueda responder mensajes.

1. Ve a **Configuracion AI** en el sidebar
2. En la seccion **Credenciales LLM**, crea una nueva credencial:
   - Nombre: ej. "Production OpenAI"
   - Provider: OpenAI, Anthropic, Gemini, Groq, DeepSeek, Mistral, xAI, OpenRouter
   - API Key: tu clave de API del proveedor
3. La primera credencial se marca como default automaticamente
4. En la seccion **Configuracion del Modelo**:
   - Selecciona la credencial
   - Elige el modelo (la lista se actualiza segun el provider seleccionado)
   - Ajusta Temperature, Max Tokens, Context Window segun necesites
5. Guarda la configuracion

Sin esta configuracion, el bot no respondera mensajes y el playground mostrara un aviso.

---

## 5. Configurar el prompt del bot

1. Ve a **Prompts** en el sidebar
2. Edita el System Prompt del tenant — aqui describes tu negocio, productos, precios, tono
3. Opcionalmente edita el System Prompt Override del canal para ajustar la personalidad (vendedor vs soporte)

---

## 6. Subir Knowledge Base

1. Ve a **Knowledge Base** en el sidebar
2. Sube PDFs, documentos de tu negocio (catalogos, manuales, precios, FAQ)
3. El sistema los procesa automaticamente: extrae texto, crea chunks y genera embeddings
4. El bot usara esta informacion para responder preguntas

---

## 7. Probar el agente (Agent Playground)

1. Ve a **Agent Playground** en el sidebar
2. Selecciona el canal que quieres probar
3. Revisa los documentos disponibles y las herramientas activas
4. Envia mensajes de prueba para verificar que el agente responde correctamente
5. El agente usa el mismo prompt y Knowledge Base que el bot de produccion
6. No se persisten mensajes ni se consumen herramientas con side effects (como capturar leads o escalar)

---

## 8. Conectar integraciones

1. Ve a **Integraciones** en el sidebar
2. Configura webhooks para notificar a sistemas externos (n8n, Zapier, Make) cuando ocurran eventos:
   - Conversacion iniciada
   - Mensaje recibido
   - Lead capturado
   - Escalacion activada
   - Conversacion cerrada

---

## 9. Iniciar el worker de colas

Los mensajes se procesan en background:

```bash
php artisan queue:work --queue=high,default,low
```

Esto debe estar corriendo para que el bot responda mensajes de WhatsApp.

---

## 10. Panel de Plataforma (super-admin)

Visita http://rumibot.test/platform para:

- Ver metricas globales
- Gestionar tenants (crear, suspender, entrar como tenant)
- Administrar planes y precios
- Ver billing global

---

## 11. Monitoreo

- **Pulse:** http://rumibot.test/pulse (performance, queries lentas, jobs, excepciones)
- **Logs:** storage/logs/laravel-*.log (con tenant_id y user_id en cada entrada)

---

## Flujo completo del bot

```
Usuario envia WhatsApp → YCloud webhook → Rumibot recibe →
Job ProcessIncomingMessage → Carga credencial LLM del tenant →
AI Agent consulta Knowledge Base (RAG) →
Genera respuesta → Job SendWhatsAppMessage → YCloud envia → Usuario recibe
```
