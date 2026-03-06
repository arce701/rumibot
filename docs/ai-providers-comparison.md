# Proveedores de IA — Comparativa para Rumibot

> Ultima actualizacion: 2026-03-05
> Proposito: Ayudar a los tenants de Rumibot a elegir el mejor proveedor de IA segun su volumen y presupuesto.

## Contexto de Rumibot

Rumibot es una plataforma SaaS multi-tenant de chatbots para WhatsApp. Cada tenant (cliente) configura su propia API key de un proveedor de IA. La plataforma procesa mensajes entrantes de WhatsApp, genera respuestas con IA, y las envia de vuelta — todo de forma automatica.

**Requisitos clave para elegir proveedor:**

- **Velocidad de respuesta** — Los usuarios de WhatsApp esperan respuestas rapidas (< 5 segundos). Proveedores lentos causan mala experiencia.
- **RPD suficiente** — Un bot tipico de ventas/soporte maneja ~30 conversaciones/dia con ~5 mensajes cada una = ~150 llamadas API/dia. Bots mas activos pueden llegar a 500+.
- **Sin tarjeta de credito** — Muchos clientes son PyMEs latinoamericanas que quieren probar antes de pagar. El free tier NO debe requerir tarjeta.
- **Calidad minima: Buena** — Los modelos deben seguir instrucciones del system prompt, mantener tono conversacional natural y manejar bien espanol/portugues.
- **API compatible con OpenAI** — Rumibot usa `laravel/ai` que soporta la especificacion OpenAI Chat Completions. Proveedores con API compatible se integran de forma nativa.

**Proveedores ya integrados en Rumibot:** OpenAI, Anthropic, Gemini, Groq, DeepSeek, Mistral, xAI, OpenRouter.
**Proveedores pendientes de integracion:** Cerebras, NVIDIA NIM (API compatible con OpenAI, integracion sencilla).

---

## Free Tier Recomendado (ordenado por calidad y luego limites)

Solo se incluyen proveedores que cumplen: calidad Buena o superior, sin tarjeta de credito requerida, y limites utiles para un chatbot en produccion.

| # | Proveedor | Modelo | Calidad | RPM | RPD | Velocidad | Integrado | Registro |
|---|-----------|--------|---------|-----|-----|-----------|-----------|----------|
| 1 | **Mistral** | Mistral Large (plan Experiment) | Excelente | 2 | ~2,880 | Media | Si | [console.mistral.ai](https://console.mistral.ai) |
| 2 | **DeepSeek** | deepseek-chat (V3) | Excelente | Dinamico | Sin limite fijo | Lenta en pico | Si | [platform.deepseek.com](https://platform.deepseek.com) |
| 3 | **Cerebras** | Qwen3 32B / 235B | Muy buena | 30 | ~1,000* | Ultra-rapida (3,000+ tok/s) | No | [cloud.cerebras.ai](https://cloud.cerebras.ai) |
| 4 | **Groq** | Llama 3.3 70B | Buena | 30 | ~1,000 | Ultra-rapida | Si | [console.groq.com](https://console.groq.com) |
| 5 | **Cerebras** | Llama 3.3 70B | Buena | 30 | ~1,000* | Ultra-rapida (3,000+ tok/s) | No | [cloud.cerebras.ai](https://cloud.cerebras.ai) |
| 6 | **NVIDIA NIM** | Llama 3.3 70B y otros | Buena | 40 | Sin limite fijo | Rapida | No | [build.nvidia.com](https://build.nvidia.com) |

\* Cerebras mide en tokens/dia (1M TPD), no RPD. Con mensajes tipicos de ~1,000 tokens, equivale a ~1,000 peticiones/dia.

### Por que este orden

1. **Mistral** — La mejor calidad gratis del mercado. Acceso a Mistral Large (modelo flagship) sin pagar. El limite de 2 RPM es su unica debilidad: si 3 usuarios mandan mensaje al mismo tiempo, uno espera. Para un bot con pocas conversaciones concurrentes, es imbatible.

2. **DeepSeek** — Calidad de razonamiento excelente, precios imbatibles cuando se acaban los 5M tokens gratis (30 dias). Sin rate limits duros. Su debilidad: lento en horas pico de China (UTC+8), pero los horarios de Latinoamerica (UTC-3 a UTC-6) coinciden con horas no-pico de DeepSeek, lo que beneficia a nuestros tenants.

3. **Cerebras Qwen3** — Modelos Qwen3 tienen muy buena calidad, especialmente el 235B. 30 RPM y 1M tokens/dia es generoso. La velocidad de 3,000+ tok/s es ideal para WhatsApp. Requiere integracion en Rumibot (API compatible con OpenAI, esfuerzo bajo). Contexto limitado a 8,192 tokens en free tier.

4. **Groq Llama 3.3 70B** — Ya integrado en Rumibot, lo que lo hace la opcion mas practica hoy. 30 RPM y ~1,000 RPD cubren un bot activo. Inferencia ultra-rapida. Sin tarjeta de credito. Es la recomendacion por defecto para tenants nuevos.

5. **Cerebras Llama 3.3 70B** — Mismo modelo que Groq pero en hardware Cerebras (aun mas rapido). Requiere integracion. Si ya integramos Cerebras para Qwen3, este viene de regalo.

6. **NVIDIA NIM** — 40 RPM es generoso, acceso a multiples modelos. Pero es una plataforma de prototipado sin SLA, la disponibilidad puede fallar en modelos populares. Util como respaldo, no como opcion principal.

### Nota sobre OpenRouter

OpenRouter sigue integrado en Rumibot pero no se incluye en la tabla porque la calidad de sus modelos `:free` es variable e impredecible — depende de que modelos esten disponibles ese dia. Ademas, sin creditos cargados el limite es de solo 50 RPD. Es util como herramienta de experimentacion, no como recomendacion para produccion.

---

## Proveedores descartados del Free Tier

| Proveedor | Modelo | Razon |
|-----------|--------|-------|
| **Gemini** | 2.5 Flash | Limites reales sin billing: 5 RPM, 20 RPD (captura verificada). Inutilizable para produccion. Google empuja a configurar facturacion para limites decentes. |
| **Gemini** | 2.5 Flash Lite | Calidad pesima. No sigue instrucciones del system prompt de forma consistente. |
| **Gemini** | 2.5 Pro | Requiere configurar facturacion. Sin billing muestra 0 RPM / 0 RPD (captura verificada). |
| **xAI** | Grok 3 | Requiere tarjeta de credito ($5 minimo) y compromiso permanente de data sharing. |
| **Groq** | Llama 3.1 8B | Calidad insuficiente. Respuestas genericas, mal seguimiento de instrucciones complejas. |
| **SambaNova** | Varios | Solo $5 en creditos que expiran en 30-90 dias. No es un free tier sostenible. |
| **Together AI** | Varios | Requiere compra minima de $5 para acceder a la API. |
| **Fireworks AI** | Varios | Solo $1 en creditos gratis. No es significativo. |
| **Hugging Face** | Varios | Limites vagos, enfocado en experimentacion, no produccion de chatbots. |
| **Cloudflare Workers AI** | Varios | Usa "Neurons" como unidad (no tokens). Integracion diferente, no compatible con OpenAI API. |
| **Cohere** | Command R | 1,000 llamadas/mes gratis pero solo para pruebas (prohibe uso en produccion con key gratuita). |

---

## Comparativa Planes de Pago (menos de $20/mes)

Costo estimado para un bot tipico: **~30 conversaciones/dia**, ~5 mensajes por conversacion = **~4,500 llamadas API/mes** (~4.5M tokens entrada, ~1.35M tokens salida).

| Proveedor | Modelo | Input $/M | Output $/M | Costo estimado/mes | Limite de gasto | Calidad | Registro |
|-----------|--------|-----------|------------|---------------------|-----------------|---------|----------|
| **OpenAI** | GPT-4.1-nano | $0.02 | $0.15 | **~$0.29** | Si (por proyecto) | Buena | [platform.openai.com](https://platform.openai.com) |
| **Groq** | Llama 3.3 70B | $0.59 | $0.79 | **~$3.73** | Si ($500 default) | Buena | [console.groq.com](https://console.groq.com) |
| **Cerebras** | Llama 3.3 70B | $0.60 | $0.60 | **~$3.51** | No documentado | Buena | [cloud.cerebras.ai](https://cloud.cerebras.ai) |
| **Mistral** | Mistral Small | $0.10 | $0.30 | **~$0.86** | No documentado | Buena | [console.mistral.ai](https://console.mistral.ai) |
| **OpenAI** | GPT-4o-mini | $0.15 | $0.60 | **~$1.49** | Si (por proyecto) | Muy buena | [platform.openai.com](https://platform.openai.com) |
| **DeepSeek** | deepseek-chat | $0.28 | $0.42 | **~$1.83** | No documentado | Excelente | [platform.deepseek.com](https://platform.deepseek.com) |
| **Anthropic** | Claude Haiku 4.5 | $1.00 | $5.00 | **~$11.25** | Si (por tier) | Excelente | [console.anthropic.com](https://console.anthropic.com) |

### Modelos descartados (superan $20/mes con uso tipico)

| Proveedor | Modelo | Costo estimado/mes | Razon |
|-----------|--------|--------------------|-------|
| OpenAI | GPT-4.1 | ~$18 | Borderline, sube rapido con mas volumen |
| Anthropic | Claude Sonnet 4.5 | ~$27 | Demasiado caro para bot basico |
| xAI | Grok 3 | ~$34 | Output muy caro ($15/M) |
| Anthropic | Claude Opus 4.6 | ~$63 | Premium, solo para casos excepcionales |

### Proteccion contra gastos descontrolados

> **Riesgo real en Rumibot:** Cada tenant configura su propia API key. Si un empleado la comparte por error o un atacante la obtiene, las llamadas API se disparan.

| Proveedor | Limite configurable | Como configurarlo |
|-----------|---------------------|-------------------|
| **OpenRouter** | Si — por saldo prepago | Saldo cargado. Cuando se acaba, se detiene. No hay deuda. |
| **OpenAI** | Si — por proyecto | Dashboard → Limits → Budget Limit. Bloquea al tope. Alerta al 80%. |
| **Groq** | Si — por organizacion | Settings → Spend Limits. Default $500/mes. Bloquea API al limite. |
| **Anthropic** | Si — automatico por tier | Tope automatico por tier. No se puede exceder. |
| **Cerebras** | No documentado | Sin mecanismo oficial conocido. |
| **DeepSeek** | No documentado | Riesgo bajo: precios ultra-baratos. |
| **Mistral** | No documentado | Plan Experiment tiene limites naturales. |

---

## Recomendaciones por caso de uso en Rumibot

### Tenant que quiere probar gratis

| Escenario | Proveedor | Por que |
|-----------|-----------|---------|
| Primeras pruebas, pocas conversaciones | **Groq + Llama 3.3 70B** | Ya integrado, ~1,000 RPD, ultra-rapido, sin tarjeta. Cubre ~200 conv/dia. |
| Quiere la mejor calidad gratis | **Mistral (Experiment)** | Mistral Large gratis. 2 RPM limita concurrencia pero ~2,880 RPD. |
| Bot activo en horario latinoamericano | **DeepSeek** | 5M tokens gratis. Rapido en horario LATAM (fuera del pico chino). |

### Tenant dispuesto a pagar poco (< $5/mes)

| Escenario | Proveedor | Costo | Por que |
|-----------|-----------|-------|---------|
| Lo mas barato posible | **OpenAI GPT-4.1-nano** | ~$0.29/mes | Lo mas barato + limite de gasto configurable. |
| Mejor balance calidad-precio-velocidad | **Groq + Llama 3.3 70B** | ~$3.73/mes | Ultra-rapido, buena calidad, limite de gasto. |
| Calidad premium barata | **DeepSeek** | ~$1.83/mes | Excelente razonamiento, ultra-barato. |

### Tenant con volumen alto (100+ conversaciones/dia)

| Escenario | Proveedor | Costo (15K calls) | Por que |
|-----------|-----------|-------------------|---------|
| Alto volumen, costo minimo | **OpenAI GPT-4.1-nano** | ~$0.90/mes | Sin limites practicos, ultra-barato, limite de gasto. |
| Alto volumen, buena calidad | **OpenAI GPT-4o-mini** | ~$4.50/mes | El modelo mas usado en chatbots de soporte en 2026. |
| Alto volumen, maxima calidad | **Anthropic Haiku 4.5** | ~$34/mes | Excelente en atencion al cliente, tono natural. |

### Estrategia de crecimiento recomendada

1. **Empezar con Groq gratis** — 0 costo, probar el bot, ajustar el prompt. Ya integrado.
2. **Escalar a OpenAI GPT-4.1-nano** — Cuando necesite mas volumen o consistencia (~$0.29/mes).
3. **Subir a GPT-4o-mini** — Cuando la calidad sea prioridad (~$1.49/mes).
4. **Para tenants premium** — Anthropic Haiku 4.5 para la mejor experiencia de atencion al cliente.

---

## Proveedores pendientes de integracion

### Cerebras (Prioridad: Alta)

- **Por que:** Free tier muy competitivo (30 RPM, 1M tokens/dia), inferencia ultra-rapida (3,000+ tok/s), Qwen3 235B con calidad muy buena.
- **Limitaciones:** Contexto 8,192 tokens en free tier. Sin limite de gasto documentado.
- **Esfuerzo:** Bajo — API compatible con OpenAI, solo agregar caso en `AiProvider` + endpoint `https://api.cerebras.ai/v1`.

### NVIDIA NIM (Prioridad: Baja)

- **Por que:** 40 RPM, muchos modelos, API compatible con OpenAI.
- **Limitaciones:** Sin SLA, disponibilidad variable, enfocado en prototipado.
- **Esfuerzo:** Bajo — endpoint `https://integrate.api.nvidia.com/v1`.

---

## Detalle por proveedor

### Mistral

- **Que es:** Empresa francesa de IA. Desarrolla modelos propios (Mistral Large, Small, Nemo). Enfoque en GDPR.
- **Consola:** [console.mistral.ai](https://console.mistral.ai)
- **Documentacion:** [docs.mistral.ai](https://docs.mistral.ai)
- **Precios:** [mistral.ai/pricing](https://mistral.ai/pricing)
- **Tarjeta de credito:** No
- **Plan gratuito:** "Experiment" — acceso a todos los modelos, 2 RPM, 500K TPM, 1B tokens/mes
- **Comportamiento 429:** HTTP 429 estandar con headers de reintento
- **Fortalezas:** Acceso a todos los modelos gratis incluyendo Mistral Large, proveedor europeo (GDPR)
- **Debilidades:** 2 RPM limita concurrencia, datos pueden usarse para entrenar modelos de Mistral
- **Relevancia para Rumibot:** Mejor calidad gratis. Ideal para tenants con pocas conversaciones concurrentes.

### DeepSeek

- **Que es:** Empresa china de IA. Modelos propios con excelente razonamiento. Precios ultra-competitivos.
- **Consola:** [platform.deepseek.com](https://platform.deepseek.com)
- **Documentacion:** [api-docs.deepseek.com](https://api-docs.deepseek.com)
- **Precios:** [api-docs.deepseek.com/quick_start/pricing](https://api-docs.deepseek.com/quick_start/pricing)
- **Tarjeta de credito:** No
- **Creditos gratis:** ~5M tokens al registrarse (validos 30 dias)
- **Rate limits:** Sin limites fijos — throttling dinamico. Descuentos hasta 75% en horas no-pico (16:30–00:30 GMT).
- **Fortalezas:** Precios mas bajos del mercado, sin rate limits duros, excelente razonamiento
- **Debilidades:** Lento en horas pico chino (UTC+8), throttling impredecible
- **Relevancia para Rumibot:** Excelente para tenants LATAM — horas laborales de Latinoamerica (UTC-3 a -6) coinciden con horas no-pico de DeepSeek.

### Cerebras

- **Que es:** Empresa de hardware con chip Wafer-Scale Engine (WSE-3), procesador monolitico de 4 billones de transistores. Ejecuta modelos open-source a velocidades record.
- **Consola:** [cloud.cerebras.ai](https://cloud.cerebras.ai)
- **Documentacion:** [inference-docs.cerebras.ai](https://inference-docs.cerebras.ai)
- **Precios:** [cerebras.ai/pricing](https://cerebras.ai/pricing)
- **Tarjeta de credito:** No
- **Free tier:** 30 RPM, 1M tokens/dia, contexto 8,192 tokens
- **Modelos:** Llama 3.3 70B, Qwen3 32B, Qwen3 235B, GPT-OSS 120B
- **API:** Compatible con OpenAI Chat Completions
- **Fortalezas:** Inferencia mas rapida del mercado (3,000+ tok/s), free tier generoso, API estandar
- **Debilidades:** Contexto limitado en free tier (8K), proveedor relativamente nuevo
- **Relevancia para Rumibot:** 8K de contexto es suficiente para conversaciones de WhatsApp. Velocidad ideal para UX. Requiere integracion.

### Groq

- **Que es:** Empresa de hardware con chip LPU (Language Processing Unit). Ejecuta modelos open-source (Llama, Gemma, Mixtral).
- **Consola:** [console.groq.com](https://console.groq.com)
- **Documentacion:** [console.groq.com/docs](https://console.groq.com/docs)
- **Precios:** [groq.com/pricing](https://groq.com/pricing)
- **Tarjeta de credito:** No (free tier), Si (paid tier)
- **Free tier:** 30 RPM, ~1,000 RPD (Llama 3.3 70B)
- **Comportamiento 429:** Header `Retry-After` con segundos de espera
- **Fortalezas:** Ultra-rapido, free tier generoso, ya integrado en Rumibot
- **Debilidades:** Limitado a modelos open-source
- **Relevancia para Rumibot:** Recomendacion por defecto para tenants nuevos. Ya integrado, sin friccion.

### NVIDIA NIM

- **Que es:** Plataforma de inferencia de NVIDIA sobre hardware DGX Cloud.
- **Consola:** [build.nvidia.com](https://build.nvidia.com)
- **Tarjeta de credito:** No
- **Free tier:** 40 RPM, gratis para prototipado
- **Modelos:** Llama, Mistral, Kimi K2.5, y mas
- **API:** Compatible con OpenAI Chat Completions
- **Fortalezas:** 40 RPM generoso, variedad de modelos
- **Debilidades:** Sin SLA, disponibilidad variable, modelos populares pueden sobrecargarse
- **Relevancia para Rumibot:** Alternativa de respaldo, no opcion principal.

### OpenAI (solo pago)

- **Que es:** Creadores de ChatGPT. El proveedor mas confiable del mercado.
- **Consola:** [platform.openai.com](https://platform.openai.com)
- **Documentacion:** [developers.openai.com](https://developers.openai.com)
- **Precios:** [openai.com/api/pricing](https://openai.com/api/pricing)
- **Tarjeta de credito:** Si (requerida)
- **Free tier:** No tiene
- **Comportamiento 429:** Headers `x-ratelimit-reset-requests`, `Retry-After`
- **Fortalezas:** Mejor ecosistema, mas confiable, limite de gasto configurable, modelos ultra-baratos (nano)
- **Debilidades:** Requiere tarjeta de credito
- **Relevancia para Rumibot:** La opcion recomendada cuando el tenant esta listo para pagar. GPT-4.1-nano a $0.29/mes es practicamente gratis.

### Anthropic (solo pago)

- **Que es:** Empresa fundada por ex-investigadores de OpenAI. Desarrolla Claude.
- **Consola:** [console.anthropic.com](https://console.anthropic.com)
- **Documentacion:** [docs.anthropic.com](https://docs.anthropic.com)
- **Precios:** [anthropic.com/pricing](https://anthropic.com/pricing)
- **Tarjeta de credito:** Si (requerida)
- **Free tier:** $5 en creditos al registrarse (requiere tarjeta)
- **Comportamiento 429:** Algoritmo token bucket, header `retry-after`
- **Fortalezas:** Excelente calidad, fuerte en seguridad, ideal para atencion al cliente, tono natural
- **Debilidades:** Requiere tarjeta, mas caro que alternativas
- **Relevancia para Rumibot:** Para tenants premium que priorizan calidad en atencion al cliente.

---

## Cooldowns de Rate Limit en Rumibot

Configurados en `AiProvider::rateLimitCooldownSeconds()`, usados por `ProcessIncomingMessage` cuando el header `Retry-After` no esta disponible:

| Proveedor | Cooldown (segundos) |
|-----------|---------------------|
| Gemini | 60 |
| OpenAI | 60 |
| Anthropic | 60 |
| Groq | 60 |
| DeepSeek | 30 |
| Mistral | 60 |
| xAI | 60 |
| OpenRouter | 60 |

---

## Fuentes

- [Groq Rate Limits](https://console.groq.com/docs/rate-limits)
- [Gemini Rate Limits](https://ai.google.dev/gemini-api/docs/rate-limits) — Limites reales verificados con captura de aistudio.google.com
- [Mistral Rate Limits & Tiers](https://docs.mistral.ai/deployment/ai-studio/tier)
- [DeepSeek Rate Limits](https://api-docs.deepseek.com/quick_start/rate_limit)
- [Cerebras Pricing](https://www.cerebras.ai/pricing)
- [Cerebras Rate Limits](https://inference-docs.cerebras.ai/support/rate-limits)
- [OpenRouter Free Models](https://openrouter.ai/collections/free-models)
- [NVIDIA NIM](https://developer.nvidia.com/nim)
- [Free AI APIs 2026 - Complete Guide](https://awesomeagents.ai/tools/free-ai-inference-providers-2026/)
- [Free LLM API Resources (GitHub)](https://github.com/cheahjs/free-llm-api-resources)
- [Best LLM for Chatbots 2026](https://www.heltar.com/blogs/what-is-the-best-llm-to-build-chatbots-in-2026)
