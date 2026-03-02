# Guia: Configurar Meta Cloud API para WhatsApp Business

Manual paso a paso para obtener el **Access Token** y **Phone Number ID** necesarios para conectar un canal de WhatsApp en Rumibot.

> **Ultima actualizacion:** Febrero 2026

---

## Tabla de Contenidos

1. [Requisitos previos](#1-requisitos-previos)
2. [Crear cuenta de Meta Business](#2-crear-cuenta-de-meta-business)
3. [Crear cuenta de Meta Developers](#3-crear-cuenta-de-meta-developers)
4. [Crear una App en Meta Developers](#4-crear-una-app-en-meta-developers)
5. [Agregar el producto WhatsApp a la App](#5-agregar-el-producto-whatsapp-a-la-app)
6. [Obtener el Phone Number ID](#6-obtener-el-phone-number-id)
7. [Generar un Access Token permanente (System User)](#7-generar-un-access-token-permanente-system-user)
8. [Configurar el canal en Rumibot](#8-configurar-el-canal-en-rumibot)
9. [Configurar el webhook en Meta](#9-configurar-el-webhook-en-meta)
10. [Probar en local con ngrok](#10-probar-en-local-con-ngrok)
11. [Enviar un mensaje de prueba](#11-enviar-un-mensaje-de-prueba)
12. [Pasar a produccion](#12-pasar-a-produccion)
13. [Errores comunes](#13-errores-comunes)
14. [Referencias](#14-referencias)

---

## 1. Requisitos previos

Antes de empezar necesitas:

| Requisito | Detalle |
|-----------|---------|
| **Cuenta de Facebook** | Cuenta personal activa (no se publica nada) |
| **Meta Business Manager** | Cuenta de negocio verificada en [business.facebook.com](https://business.facebook.com) |
| **Numero de telefono** | Un numero que **NO** este registrado en WhatsApp personal ni WhatsApp Business App. Puede ser un numero nuevo o uno que hayas desvinculado previamente |
| **Documento de negocio** | Para la verificacion de negocio: RUC/ficha SUNAT (Peru), RFC (Mexico), CNPJ (Brasil), o equivalente segun tu pais |
| **Metodo de pago** | Tarjeta de credito/debito para habilitar el envio de mensajes (Meta cobra por conversaciones iniciadas por el negocio) |

> **Nota Peru/LATAM:** La verificacion de negocio en Meta suele requerir RUC, ficha SUNAT y un documento con direccion comercial. El proceso toma ~24-48 horas.

---

## 2. Crear cuenta de Meta Business

Si ya tienes una cuenta de Meta Business Manager, salta al paso 3.

1. Ve a [business.facebook.com](https://business.facebook.com)
2. Haz clic en **Crear cuenta**
3. Completa:
   - **Nombre del negocio** — el nombre legal de tu empresa
   - **Tu nombre** — el administrador de la cuenta
   - **Email de trabajo** — email corporativo
4. Verifica el email que te envian
5. **Verificar el negocio** (obligatorio para WhatsApp API):
   - Ve a **Configuracion del negocio** → **Centro de seguridad** → **Verificacion del negocio**
   - Sube los documentos solicitados (RUC, ficha SUNAT, etc.)
   - Espera la aprobacion (~24-48 horas)

> **Importante:** Sin verificacion de negocio solo podras enviar mensajes a 5 numeros de prueba. Con verificacion se desbloquean los limites de envio.

---

## 3. Crear cuenta de Meta Developers

1. Ve a [developers.facebook.com](https://developers.facebook.com)
2. Inicia sesion con tu cuenta de Facebook
3. Si es tu primera vez, acepta los terminos de servicio
4. Tu cuenta de desarrollador queda vinculada a tu Facebook personal

---

## 4. Crear una App en Meta Developers

1. En [developers.facebook.com/apps](https://developers.facebook.com/apps), haz clic en **Crear app**
2. Selecciona **Otro** como caso de uso
3. Selecciona **Business** como tipo de app
4. Completa:
   - **Nombre de la app** — ej. "Rumibot WhatsApp" o "Mi Empresa WhatsApp"
   - **Email de contacto** — tu email
   - **Cuenta de Business Manager** — selecciona tu cuenta de negocio del dropdown
5. Haz clic en **Crear app**

---

## 5. Agregar el producto WhatsApp a la App

1. En el dashboard de tu app, busca **Agregar productos a tu app** en el panel izquierdo
2. Encuentra **WhatsApp** y haz clic en **Configurar**
3. Selecciona tu **cuenta de Meta Business** cuando lo solicite
4. Esto crea automaticamente una **WhatsApp Business Account (WABA)** vinculada a tu app

---

## 6. Obtener el Phone Number ID

### Opcion A: Usar el numero de prueba (para desarrollo)

Meta proporciona un numero de prueba gratuito para que hagas tests:

1. En tu app, ve a **WhatsApp** → **Configuracion de la API** (API Setup)
2. Veras una seccion **Desde** (From) con un numero de prueba pre-asignado
3. El **Phone Number ID** aparece justo debajo del numero — es un numero largo como `123456789012345`
4. Copia este ID

> **Limitacion del numero de prueba:** Solo puedes enviar mensajes a **5 numeros** que registres manualmente como receptores de prueba.

### Opcion B: Registrar tu propio numero (para produccion)

1. En **WhatsApp** → **Configuracion de la API** → seccion **Numeros de telefono**
2. Haz clic en **Agregar numero de telefono**
3. Completa:
   - **Nombre visible del negocio** — como aparecera en WhatsApp
   - **Categoria del negocio**
   - **Descripcion** (opcional)
4. Ingresa tu numero de telefono con codigo de pais (ej. `+51 987 654 321`)
5. Elige el metodo de verificacion: **SMS** o **Llamada**
6. Ingresa el codigo OTP que recibas
7. Una vez verificado, tu numero aparecera en la lista con su **Phone Number ID**
8. Copia este ID

> **Antes de registrar:** Asegurate de desinstalar WhatsApp personal o WhatsApp Business App de ese numero. No puede estar activo en ambos servicios a la vez.

---

## 7. Generar un Access Token permanente (System User)

El token temporal que muestra la pagina de API Setup expira en **24 horas**. Para produccion necesitas un token permanente creado via System User.

### Paso 1: Ir a System Users

1. Ve a [business.facebook.com/settings](https://business.facebook.com/settings)
2. En el menu izquierdo: **Usuarios** → **System Users** (Usuarios del sistema)

### Paso 2: Crear un System User

1. Haz clic en **Agregar**
2. Completa:
   - **Nombre** — ej. "Rumibot API" o "WhatsApp Bot"
   - **Rol** — selecciona **Admin**
3. Haz clic en **Crear usuario del sistema**

### Paso 3: Asignar activos al System User (CRITICO)

Este paso se omite frecuentemente y causa errores. Sin esto, el token no tendra acceso.

1. Selecciona el System User que acabas de crear
2. Haz clic en **Asignar activos** (Add Assets)
3. En la pestana **Apps**:
   - Busca tu app de WhatsApp (ej. "Rumibot WhatsApp")
   - Activa **Control total** (Full Control)
   - Haz clic en **Guardar cambios**
4. En la pestana **WhatsApp Accounts**:
   - Busca tu WhatsApp Business Account
   - Activa **Control total** (Full Control)
   - Haz clic en **Guardar cambios**

### Paso 4: Generar el token permanente

1. Regresa a la pagina del System User
2. Haz clic en **Generar nuevo token** (Generate New Token)
3. Selecciona tu **app** del dropdown
4. En **Expiracion del token** selecciona **Nunca** (Never)
5. Selecciona estos **permisos** (minimo obligatorio):
   - `whatsapp_business_messaging` — para enviar y recibir mensajes
   - `whatsapp_business_management` — para gestionar la cuenta
6. Haz clic en **Generar token**
7. **COPIA EL TOKEN INMEDIATAMENTE** — no se volvera a mostrar

> **Seguridad:**
> - Este token **nunca expira** a menos que lo revoques manualmente
> - Guardalo en un lugar seguro (gestor de contraseñas, `.env`, etc.)
> - NUNCA lo expongas en frontend, repositorios publicos, o logs
> - En Rumibot se almacena **encriptado** en la base de datos

---

## 8. Configurar el canal en Rumibot

Con el **Access Token** y el **Phone Number ID** en mano:

1. Inicia sesion en Rumibot
2. Ve a **Canales** en el sidebar
3. Crea un nuevo canal:
   - **Nombre** — ej. "Ventas WhatsApp" (el slug se genera automaticamente)
   - **Tipo** — `Sales` (ventas) o `Support` (soporte)
   - **Access Token** — pega el token permanente del System User
   - **Phone Number ID** — pega el ID del paso 6
4. Guarda el canal

Rumibot encripta el Access Token automaticamente al guardarlo.

---

## 9. Configurar el webhook en Meta

El webhook es la URL donde Meta envia los mensajes que recibe tu numero de WhatsApp. Rumibot genera la URL y el Verify Token automaticamente.

### Datos que necesitas de Rumibot

| Dato | Donde encontrarlo |
|------|-------------------|
| **Webhook URL** | `https://{tu-dominio}/api/webhooks/whatsapp/{tenant-uuid}` — visible en la interfaz del canal |
| **Verify Token** | Se genera automaticamente — copialo desde la interfaz del canal |

### Configurar en Meta

1. Ve a tu app en [developers.facebook.com](https://developers.facebook.com)
2. En el menu izquierdo: **WhatsApp** → **Configuracion** (Configuration)
3. En la seccion **Webhook**:
   - **URL de devolucion de llamada (Callback URL):** pega la URL del webhook de Rumibot
   - **Token de verificacion (Verify Token):** pega el verify token de Rumibot
4. Haz clic en **Verificar y guardar**
5. Meta enviara un GET a tu URL para verificar — si responde correctamente, se guardara
6. En **Campos del webhook**, suscribete a: **messages**

> **Importante:** La URL del webhook DEBE ser HTTPS y estar accesible publicamente. Para desarrollo local, usa ngrok (ver seccion 10).

---

## 10. Probar en local con ngrok

**SI, puedes probar los mensajes de WhatsApp en local** sin necesidad de desplegar en la nube. Para esto se usa **ngrok**, que crea un tunel HTTPS publico hacia tu maquina local.

### Instalar ngrok

```bash
# macOS con Homebrew
brew install ngrok

# O descarga desde https://ngrok.com/download
```

### Crear una cuenta gratuita

1. Registrate en [ngrok.com](https://ngrok.com)
2. Copia tu **authtoken** desde el dashboard
3. Configura ngrok:

```bash
ngrok config add-authtoken TU_AUTH_TOKEN
```

### Iniciar el tunel

```bash
# Rumibot corre por defecto en el puerto 80 (Laravel Herd)
# Ajusta el puerto segun tu configuracion
ngrok http 80 --host-header=rumibot.test
```

> **Con Laravel Herd:** Tu sitio esta en `rumibot.test`. Usa `--host-header=rumibot.test` para que ngrok reenvie las peticiones con el host correcto.

ngrok mostrara algo como:

```
Forwarding  https://abc123.ngrok-free.app -> http://localhost:80
```

### Configurar el webhook con la URL de ngrok

1. Copia la URL HTTPS de ngrok (ej. `https://abc123.ngrok-free.app`)
2. En Meta → tu app → WhatsApp → Configuracion → Webhook:
   - **Callback URL:** `https://abc123.ngrok-free.app/api/webhooks/whatsapp/{tenant-uuid}`
   - **Verify Token:** el verify token de tu canal en Rumibot
3. Verifica y guarda

### Flujo de prueba completo

```
1. ngrok corriendo → tunel HTTPS activo
2. Rumibot corriendo (php artisan serve o Herd)
3. Queue worker corriendo (php artisan queue:work --queue=high,default,low)
4. Envia mensaje desde WhatsApp al numero configurado
5. Meta reenvia el webhook a tu ngrok → llega a Rumibot local
6. ProcessIncomingMessage procesa → AI responde → SendWhatsAppMessage envia
7. Recibes la respuesta en WhatsApp
```

### Limitaciones de ngrok en desarrollo

| Limitacion | Detalle |
|------------|---------|
| URL cambia al reiniciar | Cada vez que reinicias ngrok la URL cambia (plan gratuito). Debes actualizar el webhook en Meta |
| Dominio fijo (plan de pago) | Con ngrok Pro puedes reservar un dominio fijo para no cambiar la URL |
| Latencia | Hay una pequeña latencia adicional vs produccion (~100-200ms) |
| Inspector de trafico | Abre `http://127.0.0.1:4040` para ver todas las peticiones que llegan a tu tunel |

---

## 11. Enviar un mensaje de prueba

### Desde la consola de Meta (sin webhook)

**Enviar mensajes NO requiere webhook configurado.** Puedes probar el envio de mensajes directamente:

1. En tu app → **WhatsApp** → **Configuracion de la API** (API Setup)
2. Selecciona tu numero en **Desde** (From)
3. Agrega un numero receptor en **Hasta** (To) — maximo 5 numeros de prueba
4. Haz clic en **Enviar mensaje** — enviara un template "hello_world"

### Desde curl (verificar que el token funciona)

```bash
curl -X POST \
  'https://graph.facebook.com/v21.0/TU_PHONE_NUMBER_ID/messages' \
  -H 'Authorization: Bearer TU_ACCESS_TOKEN' \
  -H 'Content-Type: application/json' \
  -d '{
    "messaging_product": "whatsapp",
    "to": "NUMERO_DESTINO_CON_CODIGO_PAIS",
    "type": "template",
    "template": {
      "name": "hello_world",
      "language": { "code": "en_US" }
    }
  }'
```

Si recibes una respuesta con `"messages": [{"id": "wamid.xxx"}]`, el token y Phone Number ID funcionan correctamente.

### Desde Rumibot (flujo completo)

1. Asegurate de tener:
   - Canal creado con Access Token y Phone Number ID
   - Credencial LLM configurada (AI Configuration)
   - Prompt del tenant escrito
   - Queue worker corriendo
   - Webhook configurado en Meta (con ngrok o dominio real)
2. Envia un mensaje desde WhatsApp al numero configurado
3. El bot deberia responder automaticamente

---

## 12. Pasar a produccion

Cuando estes listo para produccion necesitas:

| Paso | Detalle |
|------|---------|
| **Dominio con SSL** | URL HTTPS publica (ej. `api.tuempresa.com`) |
| **Actualizar webhook** | Cambiar la URL de ngrok por tu dominio real en Meta |
| **Verificar negocio** | Necesario para enviar mensajes a numeros que no estan en tu lista de prueba |
| **Agregar metodo de pago** | En Meta Business → Configuracion de pago. Meta cobra por conversaciones |
| **Numero propio** | Registrar tu numero real (no el de prueba de Meta) |
| **Template de mensajes** | Crear y aprobar templates si el bot inicia conversaciones (mensajes proactivos) |
| **Escalar limites** | Despues de verificar el negocio, puedes solicitar aumento de limites de mensajes |

### Precios de Meta (referencia)

Meta cobra por **conversacion** (ventana de 24 horas), no por mensaje individual:

| Tipo | Descripcion |
|------|-------------|
| **Service conversations** | Iniciadas por el usuario → **gratuitas** (1,000/mes incluidas) |
| **Marketing/Utility/Auth** | Iniciadas por el negocio → varian segun pais (~$0.01-0.08 USD) |

> Las primeras 1,000 conversaciones de servicio al mes son gratuitas. Para la mayoria de negocios pequeños y medianos, el costo de Meta es minimo ya que el bot responde mensajes entrantes (service conversations).

---

## 13. Errores comunes

| Error | Causa | Solucion |
|-------|-------|----------|
| Token expira en 24h | Estas usando el token temporal de API Setup | Genera un token permanente via System User (seccion 7) |
| "Unauthorized" al enviar mensajes | System User no tiene assets asignados | Asigna la app Y la WABA al System User con Full Control |
| Webhook no se verifica | URL no es HTTPS o no responde al GET de verificacion | Verifica que ngrok/dominio este activo y que Rumibot este corriendo |
| Mensajes no llegan a Rumibot | No suscribiste el campo "messages" en el webhook | En Meta → WhatsApp → Configuracion → Webhook → Suscribir a "messages" |
| Bot no responde | Queue worker no esta corriendo | Ejecuta `php artisan queue:work --queue=high,default,low` |
| Bot no responde | No hay credencial LLM configurada | Configura una en AI Configuration |
| "Phone number not verified" | El numero no paso la verificacion OTP | Vuelve a intentar la verificacion con SMS o llamada |
| Numero ya registrado en WhatsApp | El numero esta activo en WhatsApp personal/Business App | Elimina la cuenta de WhatsApp personal de ese numero y espera unos minutos |

---

## 14. Referencias

- [Meta for Developers — WhatsApp Cloud API](https://developers.facebook.com/docs/whatsapp/cloud-api)
- [WhatsApp Cloud API — Permanent Access Token (System User)](https://anjoktechnologies.in/blog/-whatsapp-cloud-api-permanent-access-token-step-by-step-system-user-2026-complete-correct-guide-by-anjok-technologies)
- [Setup WhatsApp Cloud API Step-by-Step](https://anjoktechnologies.in/blog/how-to-set-up-whatsapp-cloud-api-step-by-step-in-meta-developer-business-manager)
- [How to Create a Permanent WhatsApp Token](https://digitalinspiration.com/docs/document-studio/apps/whatsapp/token)
- [Testing WhatsApp Webhooks with ngrok](https://ngrok.com/docs/integrations/webhooks/whatsapp-webhooks)
- [WhatsApp Cloud API Postman Collection](https://www.postman.com/meta/whatsapp-business-platform/documentation/wlk6lh4/whatsapp-cloud-api)
- [Precios de WhatsApp Business Platform](https://developers.facebook.com/docs/whatsapp/pricing)
