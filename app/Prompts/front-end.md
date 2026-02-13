Crea un chatbot conversacional para la aplicación e-commerce que permita a los usuarios autenticados hacer preguntas sobre la base de datos de la tienda (productos, pedidos, categorías y clientes) usando el Laravel AI SDK (`laravel/ai`) con Anthropic como proveedor.

## Contexto del proyecto

El proyecto ya tiene los siguientes modelos Eloquent con sus relaciones:

- **User** — name, email, password. Relación: hasMany(Order).
- **Category** — name. Relación: hasMany(Product).
- **Product** — category_id (FK), name, description, price, stock. Relaciones: belongsTo(Category), belongsToMany(Order) con pivote (quantity, unit_price).
- **Order** — user_id (FK), status (enum OrderStatus: pending, processing, shipped, delivered, cancelled), total. Relaciones: belongsTo(User), belongsToMany(Product) con pivote (quantity, unit_price).

El panel Filament ya está configurado en `/admin`. El chatbot es una página independiente accesible desde el sidebar de la aplicación.

## 1. Agente AI (DatabaseQueryAgent)

Crear `app/Ai/Agents/DatabaseQueryAgent.php`:

- Implementar `Agent`, `Conversational`, `HasTools`.
- Usar traits `Promptable`, `RemembersConversations`.
- Atributos: `#[Provider('anthropic')]`, `#[UseCheapestModel]`, `#[MaxSteps(10)]`, `#[Temperature(0.3)]`.
- Instrucciones del agente:
  - Es un asistente de base de datos para una tienda e-commerce.
  - Es READ-ONLY — nunca sugiere ni intenta modificar datos.
  - Usa las herramientas disponibles para consultar datos, no fabrica información.
  - Presenta datos con formato claro (listas o tablas).
  - Valores monetarios con formato de moneda (e.g., $19.99).
  - Si no puede responder con las herramientas disponibles, lo dice amablemente.
  - Respuestas concisas y relevantes.
  - Cuando el usuario pregunta por "customers" o "clients", se refiere a usuarios con órdenes.
- Incluir el esquema de base de datos en las instrucciones para contexto del agente.

## 2. Tools (Herramientas de consulta)

Crear 5 tools en `app/Ai/Tools/`:

### QueryProducts
- Buscar productos con filtros opcionales: nombre (búsqueda parcial), categoría (por ID), rango de precio (min/max), estado de stock (in_stock, low_stock, out_of_stock).
- Incluir la categoría relacionada en los resultados.
- Limitar a 20 resultados.

### QueryOrders
- Buscar órdenes con filtros opcionales: status (enum), user_id, rango de fechas (from/to), rango de total (min/max).
- Incluir relaciones: user (nombre), products (con pivot quantity y unit_price).
- Limitar a 20 resultados, ordenados por fecha descendente.

### QueryCategories
- Listar categorías con conteo de productos.
- Filtro opcional por nombre (búsqueda parcial).

### QueryUsers
- Buscar usuarios con filtros opcionales: nombre o email (búsqueda parcial), solo clientes con órdenes.
- Incluir conteo de órdenes y total gastado.
- Limitar a 20 resultados.

### QueryStatistics
- Generar estadísticas agregadas por tipo: revenue (ingresos por status), orders (conteo por status y periodo), products (más vendidos, con poco stock, por categoría), customers (mejores clientes por gasto).
- Parámetro requerido: `type` (revenue, orders, products, customers).
- Parámetro opcional: `period` (today, week, month, year, all).

## 3. Interfaz del chatbot (Livewire + Flux UI)

Crear la vista en `resources/views/pages/⚡chatbot.blade.php` como componente Livewire de página completa:

### Layout
- Usar `Layout('layouts.app')` con título "Chatbot".
- Altura completa: `h-[calc(100vh-4rem)]` en mobile, `h-screen` en desktop.
- Dos paneles: sidebar de conversaciones + área principal de chat.

### Sidebar de conversaciones (desktop)
- Ancho fijo de 72 (w-72), oculto en mobile.
- Header con título "Conversations" y botón `flux:button` para nueva conversación (icono plus).
- Lista scrollable de conversaciones del usuario, ordenadas por `updated_at` desc, límite 50.
- Conversación activa resaltada con fondo diferente.
- Estado vacío: "No conversations yet."

### Header mobile
- Visible solo en pantallas pequeñas (`lg:hidden`).
- `flux:dropdown` con menú de conversaciones y opción de nueva conversación.
- Botón de nueva conversación.

### Área de mensajes
- Scrollable con auto-scroll al fondo (`x-effect="$el.scrollTop = $el.scrollHeight"`).
- **Estado vacío** (sin mensajes): Icono centrado, título "Store Assistant", subtítulo descriptivo, y grid de 4 preguntas sugeridas clickeables:
  - "What are the top selling products?"
  - "How many orders are pending?"
  - "Which products are low on stock?"
  - "Show me revenue by order status"
- **Mensajes**: Burbujas de chat diferenciadas por rol:
  - Usuario: alineado a la derecha, fondo oscuro (zinc-900/zinc-100 dark).
  - Asistente: alineado a la izquierda, fondo claro (zinc-100/zinc-800 dark), contenido renderizado como Markdown con `str()->markdown()` y clases `prose`.
- **Streaming**: Mientras procesa, mostrar burbuja del asistente con "Thinking..." animado (animate-pulse) y `wire:stream="response"` para streaming en tiempo real.

### Input de mensaje
- Borde superior separador.
- Formulario con `flux:input` y `flux:button` (icono paper-airplane, variant primary).
- Placeholder: "Ask about products, orders, categories..."
- Deshabilitado durante procesamiento.
- Validación: required, string, max 1000.

### Lógica del componente
- Propiedades: `$message` (string), `$conversationId` (locked, nullable), `$isProcessing` (locked, bool), `$messages` (array de role/content).
- `mount()`: Acepta parámetro opcional `?conversation` para cargar una conversación existente.
- `sendMessage()`: Valida, agrega mensaje del usuario al array, limpia input, activa processing, y llama a `processMessage()` vía `$this->js()`.
- `processMessage()`: Instancia `DatabaseQueryAgent`, continúa conversación existente o crea nueva para el usuario autenticado, usa `stream()` con `$this->stream(to: 'response')`, guarda respuesta y `conversationId`.
- `selectConversation()`: Carga conversación por ID (verificando que pertenezca al usuario), restaura mensajes desde `agent_conversation_messages`.
- `startNewConversation()`: Resetea todo el estado.
- `conversations` (Computed): Query a `agent_conversations` del usuario, ordenadas por `updated_at` desc, límite 50.
- `askSuggestion()`: Asigna la pregunta sugerida al `$message` y llama a `sendMessage()`.

## 4. Ruta

Agregar la ruta en `routes/web.php`:

- Ruta `/chatbot` que apunte a la vista `pages.⚡chatbot`.
- Protegida con middleware `auth`.
- Nombre: `chatbot`.

## 5. Navegación

Agregar enlace al chatbot en el sidebar de la aplicación (`resources/views/layouts/app/sidebar.blade.php`).

## 6. Testing

Crear tests Pest en `tests/Feature/Chatbot/`:

### ChatbotRouteTest.php
- Usuarios no autenticados son redirigidos al login.
- Usuarios autenticados pueden acceder a la página.

### ChatbotComponentTest.php
- Puede renderizar el componente.
- Muestra el estado vacío cuando no hay mensajes.
- Muestra las preguntas sugeridas.
- Valida que el mensaje es requerido.
- Valida longitud máxima del mensaje (1000).
- Puede cargar una conversación existente.
- No carga conversaciones de otros usuarios.
- Puede iniciar nueva conversación (resetea estado).
- La propiedad computed `conversations` retorna solo conversaciones del usuario autenticado.

### DatabaseQueryAgentTest.php
- El agente tiene las instrucciones correctas.
- El agente registra las 5 herramientas.
- El agente implementa las interfaces requeridas (Agent, Conversational, HasTools).

### QueryProductsToolTest.php
- Retorna todos los productos cuando no hay filtros.
- Filtra por nombre (búsqueda parcial).
- Filtra por categoría.
- Filtra por rango de precio.
- Filtra por estado de stock.
- Limita resultados a 20.

### QueryOrdersToolTest.php
- Retorna órdenes con relaciones cargadas.
- Filtra por status.
- Filtra por usuario.
- Filtra por rango de fechas.
- Filtra por rango de total.
- Limita a 20 resultados, ordenados por fecha desc.

### QueryCategoriesToolTest.php
- Lista categorías con conteo de productos.
- Filtra por nombre.

### QueryUsersToolTest.php
- Retorna usuarios con conteo de órdenes y total gastado.
- Filtra por nombre o email.
- Filtra solo clientes con órdenes.
- Limita a 20 resultados.

### QueryStatisticsToolTest.php
- Genera estadísticas de revenue por status.
- Genera estadísticas de órdenes por periodo.
- Genera estadísticas de productos más vendidos.
- Genera estadísticas de mejores clientes.

## 7. Requisitos técnicos

- Usar Laravel AI SDK (`laravel/ai`) — buscar documentación con `search-docs` antes de implementar.
- Proveedor: Anthropic con `#[UseCheapestModel]`.
- Todo el código debe seguir las convenciones del proyecto (ver CLAUDE.md).
- Ejecutar `vendor/bin/sail bin pint --dirty --format agent` al finalizar.
- Ejecutar todos los tests y asegurar que pasen: `vendor/bin/sail artisan test --compact`.
- Usar Flux UI para los componentes de interfaz (inputs, buttons, dropdowns).
- Soporte de dark mode en toda la interfaz.
- Diseño responsive (mobile + desktop).
