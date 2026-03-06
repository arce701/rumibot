# Rumibot — Manual de Usuario

Manual paso a paso para configurar y operar la plataforma Rumibot.

---

## Tabla de Contenidos

1. [Registro e ingreso](#1-registro-e-ingreso)
2. [Navegacion general](#2-navegacion-general)
3. [Configuracion inicial (primeros pasos)](#3-configuracion-inicial-primeros-pasos)
4. [Canales WhatsApp](#4-canales-whatsapp)
5. [Configuracion AI](#5-configuracion-ai)
6. [Prompts del bot](#6-prompts-del-bot)
7. [Knowledge Base (base de conocimiento)](#7-knowledge-base-base-de-conocimiento)
8. [Agent Playground (pruebas)](#8-agent-playground-pruebas)
9. [Conversaciones](#9-conversaciones)
10. [Leads (prospectos)](#10-leads-prospectos)
11. [Escalaciones](#11-escalaciones)
12. [Integraciones externas](#12-integraciones-externas)
13. [Equipo](#13-equipo)
14. [Billing (facturacion)](#14-billing-facturacion)
15. [Activity Log](#15-activity-log)
16. [Panel de Plataforma (super-admin)](#16-panel-de-plataforma-super-admin)
17. [Iniciar el worker de colas](#17-iniciar-el-worker-de-colas)
18. [Monitoreo](#18-monitoreo)
19. [Flujo completo del bot](#19-flujo-completo-del-bot)

---

## 1. Registro e ingreso

### Registrar una nueva empresa (tenant)

1. Abre `/register`
2. Completa el formulario:
   - **Nombre de empresa** — se convierte en el nombre del tenant
   - **Nombre** — tu nombre personal
   - **Email** y **Contraseña**
3. Al registrarte se crea automaticamente tu tenant (empresa), quedas como owner con todos los permisos

### Ingresar al sistema

1. Abre `/login`
2. Ingresa con tu email y contraseña
3. Si tienes 2FA activado, se te pedira el codigo TOTP

### Credenciales de desarrollo (super-admin)

- **Email:** rumibot8@gmail.com
- **Password:** Rumi2026$
- Este usuario tiene acceso al panel de plataforma y puede entrar en el contexto de cualquier tenant

---

## 2. Navegacion general

El sidebar izquierdo organiza todas las secciones. El orden sigue el flujo de trabajo recomendado para configurar tu bot:

**Panel del tenant:**

| Seccion | Descripcion |
|---------|-------------|
| Dashboard | Metricas y resumen de tu empresa |
| Canales | Crear y gestionar canales WhatsApp |
| Configuracion AI | Credenciales LLM y configuracion del modelo |
| Prompts | System prompt del tenant y overrides por canal |
| Knowledge Base | Subir documentos para que el bot los consulte |
| Agent Playground | Probar el agente AI sin afectar produccion |
| Conversaciones | Ver todas las conversaciones con contactos |
| Leads | Prospectos capturados por el bot |
| Escalaciones | Cola de escalaciones a agentes humanos |
| Integraciones | Webhooks salientes y API tokens |
| Equipo | Invitar miembros y asignar roles |
| Billing | Suscripcion, plan y pagos |
| Activity Log | Registro de cambios en el sistema |

**Panel de plataforma** (solo super-admins): Plataforma, Tenants, Planes, Platform Billing.

---

## 3. Configuracion inicial (primeros pasos)

Cuando creas un nuevo tenant, sigue estos pasos en orden para que el bot funcione:

1. **Configurar credencial AI** — sin esto el bot no puede responder
2. **Crear un canal WhatsApp** — necesitas un Access Token de Meta
3. **Escribir el prompt del tenant** — describe tu negocio, productos, tono
4. **Subir documentos a Knowledge Base** — catalogos, precios, FAQ
5. **Probar en el Playground** — verifica que el agente responde correctamente
6. **Configurar el webhook en Meta** — para recibir mensajes reales
7. **Iniciar el worker de colas** — para que los mensajes se procesen

---

## 4. Canales WhatsApp

Un canal conecta un numero de WhatsApp con el bot. Cada canal tiene su propio tipo (ventas o soporte) y puede tener un prompt personalizado.

### Crear un canal

1. Ve a **Canales** en el sidebar
2. Haz clic en crear nuevo canal
3. Completa los campos:
   - **Nombre** — ej. "Ventas iTrade" (el slug se genera automaticamente)
   - **Tipo** — `Sales` (ventas, bot persuasivo) o `Support` (soporte, bot paciente)
   - **Access Token** — token de acceso de la Meta Cloud API (WhatsApp Business)
   - **Phone Number ID** — el ID del numero de WhatsApp en Meta Business
4. Guarda el canal

### Configurar el webhook en Meta Business

Despues de crear el canal, configura el webhook en tu app de Meta Business:

- **URL del webhook:** `https://{tu-dominio}/api/webhooks/whatsapp/{tenant-uuid}`
- **Verify Token:** se genera automaticamente — copialo desde la interfaz del canal
- **Eventos a suscribir:** `messages`

El UUID del tenant lo puedes ver en la URL del panel o en el panel de plataforma.

### Activar / desactivar un canal

Desde la lista de canales puedes activar o desactivar un canal. Un canal desactivado no recibira ni procesara mensajes.

### Eliminar un canal

Los canales se eliminan con soft delete — se pueden recuperar si es necesario.

---

## 5. Configuracion AI

**IMPORTANTE:** No hay proveedor AI por defecto. Cada tenant debe configurar su propia credencial LLM antes de que el bot pueda responder mensajes.

### Crear una credencial LLM

1. Ve a **Configuracion AI** en el sidebar
2. En la seccion **Credenciales LLM**, crea una nueva:
   - **Nombre** — ej. "Production OpenAI"
   - **Provider** — OpenAI, Anthropic, Gemini, Groq, DeepSeek, Mistral, xAI, OpenRouter
   - **API Key** — tu clave de API del proveedor (se almacena encriptada)
3. La primera credencial se marca como default automaticamente

### Configurar el modelo

1. En la seccion **Configuracion del Modelo**:
   - **Credencial** — selecciona cual usar
   - **Modelo** — la lista se actualiza segun el provider (ej. gpt-4o-mini, claude-sonnet-4-20250514)
   - **Temperature** — creatividad de las respuestas (0.0 a 1.0)
   - **Max Tokens** — limite de tokens por respuesta
   - **Context Window** — ventana de contexto del modelo
2. Guarda la configuracion

Sin credencial configurada, el bot no respondera mensajes y el Playground mostrara un aviso.

---

## 6. Prompts del bot

Los prompts definen la personalidad y comportamiento del bot. Funcionan en capas:

```
Instrucciones base (del sistema) + Prompt del tenant + Override del canal
```

### Prompt del tenant

1. Ve a **Prompts** en el sidebar
2. Edita el **System Prompt** del tenant
3. Aqui describes: tu negocio, productos/servicios, precios, tono de comunicacion, reglas generales

Este prompt se aplica a TODOS los canales del tenant.

### Override por canal

Debajo del prompt del tenant veras cada canal con su propio campo de override. Usalo para ajustar la personalidad segun el tipo:

- **Canal de ventas** — tono persuasivo, captura datos, ofrece productos
- **Canal de soporte** — tono paciente, guia paso a paso, resuelve problemas

Si el override esta vacio, el canal usa solo el prompt del tenant.

---

## 7. Knowledge Base (base de conocimiento)

La Knowledge Base permite que el bot responda preguntas basandose en tus documentos reales (RAG — Retrieval-Augmented Generation).

### Subir documentos

1. Ve a **Knowledge Base** en el sidebar
2. Sube archivos PDF o documentos de texto
3. El sistema los procesa automaticamente:
   - Extrae el texto del documento
   - Lo divide en fragmentos (chunks) de ~500 tokens
   - Genera embeddings (representaciones vectoriales) para busqueda semantica
4. El estado del documento cambia: Pendiente → Procesando → Listo

### Channel Scope

Al subir un documento puedes restringirlo a canales especificos. Por ejemplo: un catalogo de productos solo para el canal de ventas, un manual tecnico solo para soporte. Si no seleccionas canales, el documento estara disponible para todos.

### Que subir

- Catalogos de productos con precios
- Manuales de uso
- FAQ y respuestas frecuentes
- Politicas de garantia, envios, devoluciones
- Informacion de la empresa

---

## 8. Agent Playground (pruebas)

El Playground permite probar el agente AI sin afectar produccion.

### Como usar

1. Ve a **Agent Playground** en el sidebar
2. Selecciona el canal que quieres probar
3. Revisa los documentos disponibles y las herramientas activas
4. Envia mensajes de prueba como si fueras un cliente

### Diferencias con produccion

- Los mensajes **no se persisten** en la base de datos
- Las herramientas con side effects **no se ejecutan** (no captura leads, no escala, no envia WhatsApp)
- Solo la herramienta de busqueda semantica (SimilaritySearch) esta activa
- Usa el mismo prompt y Knowledge Base que produccion

Si no tienes credencial AI configurada, el Playground mostrara un mensaje de aviso en lugar del chat.

---

## 9. Conversaciones

Todas las conversaciones entre el bot y los contactos de WhatsApp se registran aqui.

1. Ve a **Conversaciones** en el sidebar
2. Usa los filtros para buscar: por canal, por estado (activa, cerrada, escalada), por texto
3. Haz clic en una conversacion para ver el historial completo de mensajes
4. Las conversaciones muestran: nombre del contacto, numero formateado (ej. `+502 3485 0199`), canal, estado, ultimo mensaje
5. El pais del contacto se detecta automaticamente del codigo de pais del numero de telefono y se guarda en la conversacion

### Intervencion humana (responder desde la web)

Desde el detalle de una conversacion activa, puedes responder directamente al contacto de WhatsApp:

1. Abre una conversacion activa
2. Escribe tu respuesta en el campo de texto debajo de los mensajes
3. Opcionalmente, adjunta un archivo haciendo clic en el icono de clip (📎):
   - **Imagenes:** JPEG, PNG, WebP (max 5 MB) — se muestran como foto en WhatsApp
   - **Documentos:** PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, TXT, CSV (max 100 MB) — se envian como archivo adjunto
   - Puedes enviar un archivo con o sin texto (el texto se usa como caption)
   - Para quitar el adjunto antes de enviar, haz clic en el boton **Quitar adjunto**
4. Haz clic en **Enviar** — el mensaje (y el adjunto si hay) se envia al contacto por WhatsApp
5. La IA se pausa automaticamente por 24 horas para esa conversacion
6. Los mensajes enviados por un operador humano se muestran con la etiqueta **Operador**
7. Las imagenes enviadas se muestran como preview en el chat, los documentos como enlace de descarga

En el sidebar de la conversacion veras el **Estado de IA**:
- Si la IA esta pausada, se muestra la fecha/hora hasta cuando esta pausada y un boton **Reanudar IA** para reactivarla antes de que expire
- Si la IA esta activa, el contacto recibe respuestas automaticas del bot

### Exportar conversaciones

Si tu plan incluye exportacion de datos, puedes descargar las conversaciones en formato Excel (.xlsx).

---

## 10. Leads (prospectos)

El bot captura automaticamente datos de contacto de los prospectos durante conversaciones en canales de tipo **Sales**. El pais del prospecto se detecta automaticamente del numero de telefono si la IA no lo provee explicitamente.

### Ver y gestionar leads

1. Ve a **Leads** en el sidebar
2. Filtra por estado: Nuevo, Contactado, Convertido, Perdido
3. Edita un lead para actualizar su estado, agregar notas o ajustar el puntaje de calificacion (score)

### Exportar leads

Si tu plan incluye exportacion de datos, puedes descargar los leads en formato Excel (.xlsx).

---

## 11. Escalaciones

Cuando el bot detecta que necesita intervencion humana (problema complejo, cliente insatisfecho), crea una escalacion.

1. Ve a **Escalaciones** en el sidebar
2. Veras la cola de escalaciones pendientes
3. Toma una escalacion, revisala, y marcala como resuelta con una nota de resolucion

Las escalaciones generan notificaciones via Discord (si esta configurado).

---

## 12. Integraciones externas

### Webhooks salientes

Configura webhooks para notificar a sistemas externos cuando ocurran eventos:

1. Ve a **Integraciones** en el sidebar
2. Crea una nueva integracion:
   - **Nombre** y **URL** del webhook destino
   - **Provider** — n8n, Zapier, Make, o Custom
   - **Eventos** a suscribir:
     - Conversacion iniciada
     - Mensaje recibido
     - Lead capturado
     - Escalacion activada
     - Conversacion cerrada
3. Los payloads se firman con HMAC-SHA256 usando un secret encriptado

### API Tokens (Sanctum)

Para que servicios externos interactuen con Rumibot (enviar mensajes, consultar leads, cerrar conversaciones):

1. En la seccion de **API Tokens**, genera un nuevo token
2. Usa el token en el header `Authorization: Bearer {token}`
3. Endpoints disponibles: `GET /api/v1/conversations`, `GET /api/v1/leads`, y mas

---

## 13. Equipo

Gestiona los miembros de tu empresa y sus permisos.

### Roles disponibles

| Rol | Permisos |
|-----|----------|
| Owner | Todos los permisos (canales, billing, equipo, etc.) |
| Admin | Igual que owner excepto eliminar tenant y transferir ownership |
| Member | Solo lectura (ver conversaciones, leads, analytics) |

### Invitar miembros

1. Ve a **Equipo** en el sidebar
2. Ingresa el email del nuevo miembro y selecciona su rol
3. Si el email no esta registrado, se crea una cuenta automaticamente
4. El miembro queda asociado a tu tenant

### Eliminar miembros

Desde la lista de equipo puedes eliminar a un miembro. Esto lo desvincula del tenant.

---

## 14. Billing (facturacion)

### Plan

Rumibot tiene un unico plan con todas las funcionalidades incluidas. La unica diferencia entre las opciones de pago es el periodo de renovacion:

| Intervalo | Precio (USD) |
|-----------|-------------|
| Trimestral | $30 |
| Semestral | $55 |
| Anual | $110 |

Todas las features estan desbloqueadas: canales, mensajes, documentos, miembros, integraciones, analytics y exportacion ilimitados.

### Ver suscripcion y pagos

1. Ve a **Billing** en el sidebar
2. Veras tu plan actual, fecha de renovacion y uso
3. En **Pagos** puedes ver el historial de pagos

### Metodos de pago

- **MercadoPago** — pago online (principal para LATAM)
- **Manual** — transferencia bancaria

**Nota:** El tenant propietario de la plataforma (`is_platform_owner: true`) no paga suscripcion.

---

## 15. Activity Log

Registro de auditoría de todos los cambios realizados en el sistema.

1. Ve a **Activity Log** en el sidebar
2. Veras un listado de cambios con: quien hizo el cambio, que cambio, y cuando

Los modelos que registran actividad: Tenant, Channel, KnowledgeDocument, Lead, Plan, Subscription.

---

## 16. Panel de Plataforma (super-admin)

Solo accesible para usuarios con `is_super_admin: true`.

### Secciones

| Seccion | Ruta | Descripcion |
|---------|------|-------------|
| Plataforma | `/platform` | Metricas globales (tenants activos, revenue, mensajes, costos AI) |
| Tenants | `/platform/tenants` | Listar, crear, suspender tenants. Entrar en contexto de cualquier tenant |
| Planes | `/platform/plans` | Gestionar planes, precios e intervalos de facturacion |
| Platform Billing | `/platform/billing` | Vision global de facturacion de la plataforma |

### Entrar como tenant

Desde el detalle de un tenant, el super-admin puede "entrar" en su contexto. Esto cambia el `current_tenant_id` del super-admin y le permite ver y operar como si fuera el owner de ese tenant.

---

## 17. Iniciar el worker de colas

Los mensajes de WhatsApp se procesan en background mediante jobs. El worker **debe estar corriendo** para que el bot funcione.

```bash
php artisan queue:work --queue=high,default,low
```

### Colas

| Cola | Que procesa |
|------|-------------|
| `high` | Mensajes entrantes (ProcessIncomingMessage) y envio de respuestas (SendWhatsAppMessage) |
| `default` | Despacho de eventos a integraciones externas (DispatchIntegrationEvent) |
| `low` | Procesamiento de documentos (ProcessDocument) |

### Reintentos y Rate Limiting

El job `ProcessIncomingMessage` maneja automaticamente los limites de peticiones (rate limits) de los proveedores de IA:

- Si el proveedor rechaza la peticion por rate limit, el job se **vuelve a encolar** con un retraso inteligente segun el proveedor
- El sistema intenta leer el header `Retry-After` de la respuesta del proveedor para usar el tiempo exacto de espera; si no esta disponible, usa el cooldown por defecto del proveedor (ej: 60s para Gemini, OpenAI, Anthropic; 30s para DeepSeek)
- El mensaje del usuario **nunca se pierde ni se duplica** — se guarda una sola vez y se reutiliza en los reintentos
- Maximo **5 intentos** totales, maximo **3 excepciones** reales (las liberaciones por rate limit no cuentan como excepcion)
- Si el job falla permanentemente, se registra en los logs para su revision
- Cada **2 horas**, un comando programado (`app:retry-unanswered`) busca conversaciones que quedaron sin respuesta y genera la respuesta automaticamente

### En desarrollo

Para desarrollo local, puedes usar:

```bash
composer run dev
```

Esto levanta el servidor de Vite y el worker simultaneamente.

---

## 18. Monitoreo

### Laravel Pulse

- **URL:** `/pulse` (solo super-admins)
- Performance, queries lentas, jobs fallidos, excepciones, cache

### Logs

- **Ubicacion:** `storage/logs/laravel-*.log`
- Cada entrada incluye `tenant_id` y `user_id` para facilitar el debug por tenant

---

## 19. Flujo completo del bot

```
1. Usuario envia mensaje por WhatsApp
2. Meta Cloud API reenvia el mensaje como webhook POST a Rumibot
3. WhatsAppWebhookController valida y parsea el payload
4. Se despacha el job ProcessIncomingMessage (cola high)
5. El job carga la credencial LLM del tenant
6. El AI Agent construye el contexto: historial + prompt + Knowledge Base (RAG) + pais del contacto
7. Se envia la consulta al proveedor AI (OpenAI, Anthropic, etc.)
8. El AI responde (puede usar herramientas: buscar documentos, capturar lead, escalar)
9. Se despacha el job SendWhatsAppMessage (cola high)
10. Rumibot envia la respuesta via Meta Cloud API
11. El usuario recibe la respuesta en WhatsApp
```
