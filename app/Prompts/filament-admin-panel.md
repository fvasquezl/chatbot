Crea un panel de administración premium con Filament 5 para gestionar el sistema E-Commerce existente.

## Contexto del proyecto

El proyecto ya tiene los siguientes modelos Eloquent con sus relaciones:

- **User** — name, email, password. Relación: hasMany(Order).
- **Category** — name. Relación: hasMany(Product).
- **Product** — category_id (FK), name, description, price, stock. Relaciones: belongsTo(Category), belongsToMany(Order) con pivote (quantity, unit_price).
- **Order** — user_id (FK), status (enum OrderStatus: pending, processing, shipped, delivered, cancelled), total. Relaciones: belongsTo(User), belongsToMany(Product) con pivote (quantity, unit_price).
- **OrderStatus** — enum string-backed en `App\Enums\OrderStatus`.

El panel Filament ya está instalado en `app/Providers/Filament/AdminPanelProvider.php` con ruta `/admin`.

## 1. Configuración premium del panel (AdminPanelProvider)

Personalizar el panel con aspecto premium:

- Esquema de colores: primary → Indigo, gray → Slate, danger → Rose, warning → Amber, success → Emerald.
- Activar SPA mode para navegación instantánea.
- Activar dark mode como predeterminado con toggle disponible.
- Registrar un custom branding: brandName "ShopAdmin", favicon si existe.
- Sidebar collapsible en desktop.
- Breadcrumbs habilitados.
- Global search activado.
- Activar databaseNotifications.
- Registrar las páginas y widgets personalizados que se creen.

## 2. Dashboard personalizado (página principal)

Reemplazar el dashboard por defecto con widgets premium:

### Widgets de estadísticas (StatsOverviewWidget)
- **Total Revenue** — suma de `orders.total` donde status = delivered. Icono: currency-dollar. Color: success. Con description mostrando % de cambio vs mes anterior.
- **Total Orders** — conteo de órdenes. Icono: shopping-bag. Color: primary. Con description del conteo de hoy.
- **Total Products** — conteo de productos. Icono: cube. Color: warning. Con description de productos con stock = 0.
- **Total Customers** — conteo de usuarios con al menos 1 orden. Icono: users. Color: info.

### Widget de gráfica de órdenes (ChartWidget)
- Gráfica de líneas mostrando cantidad de órdenes por día en los últimos 30 días.
- Título: "Orders Trend".
- Color: primary.

### Widget de órdenes recientes (TableWidget)
- Tabla con las últimas 5 órdenes.
- Columnas: ID, Customer (user.name), Status (badge con colores), Total (formateado como moneda), Date.
- Link a la vista de la orden.

### Widget de productos más vendidos (TableWidget)
- Top 5 productos por cantidad total vendida (suma de pivot quantity).
- Columnas: Product Name, Category, Units Sold, Revenue.

## 3. Resource: CategoryResource

### Tabla (index)
- Columnas: ID, Name (searchable, sortable), Products Count (counts badge), Created At (date, sortable, toggleable).
- Filtros: Created At (date range).
- Acciones: Edit, Delete (con confirmación, bloqueado si tiene productos — mostrar notification de error).
- Header action: Create.
- Default sort: name asc.
- Paginación: 10, 25, 50.

### Formulario (create/edit)
- Name — TextInput, required, max 255, unique (ignorando el actual en edit), placeholder "e.g. Electronics", autofocus.
- Layout limpio con Section card.

### Vista (view)
- Infolist con el nombre y timestamps.
- Relation manager para Products asociados (tabla inline con acciones de crear, editar, eliminar producto dentro de la categoría).

## 4. Resource: ProductResource

### Tabla (index)
- Columnas: ID, Name (searchable, sortable), Category (badge, searchable), Price (money formatted, sortable), Stock (badge: verde si > 10, amarillo si 1-10, rojo si 0, sortable), Created At (date, toggleable).
- Filtros: Category (select relationship), Stock Status (select: All, In Stock, Low Stock < 10, Out of Stock = 0), Price Range (min/max).
- Acciones: View, Edit, Delete (bloqueado si tiene órdenes — notification).
- Header action: Create.
- Default sort: name asc.
- Paginación: 10, 25, 50.
- Búsqueda global habilitada.

### Formulario (create/edit)
- Layout en 2 columnas usando Grid.
- Columna principal (span 2):
  - Section "Product Information":
    - Name — TextInput, required, max 255.
    - Description — RichEditor o Textarea, required, rows 4.
- Sidebar (span 1):
  - Section "Pricing & Inventory":
    - Price — TextInput numeric, required, prefix "$", min 0.01.
    - Stock — TextInput numeric, required, min 0, integer.
  - Section "Organization":
    - Category — Select relationship, required, searchable, createOptionForm inline (para crear categoría rápida desde aquí).

### Vista (view)
- Infolist con layout premium: secciones con iconos.
- Mostrar Product Info, Pricing, Category.
- Relation manager: Orders que contienen este producto (tabla con order ID, customer, quantity del pivote, unit_price del pivote, order status).

## 5. Resource: OrderResource

### Tabla (index)
- Columnas: ID (sortable), Customer (user.name, searchable), Status (SelectColumn con los valores del enum OrderStatus y colores: pending→gray, processing→warning, shipped→info, delivered→success, cancelled→danger), Products Count (counts badge), Total (money formatted, sortable), Created At (date, sortable).
- Filtros: Status (select del enum), Customer (select relationship), Date Range (created_at), Total Range (min/max).
- Acciones: View, Delete (con cascade automático por la FK, confirmar con modal).
- Header action: Create.
- Default sort: created_at desc.
- Bulk actions: Delete selected, Export (si Filament lo soporta).
- Paginación: 10, 25, 50.
- Búsqueda global habilitada.

### Formulario (create/edit)
- Layout con Wizard (steps) para crear órdenes:
  - Step 1 "Customer": Select user_id, searchable, required.
  - Step 2 "Products": Repeater para agregar productos a la orden:
    - Cada item: Select product_id (searchable), quantity (numeric, min 1, required), unit_price (numeric, auto-filled al seleccionar producto pero editable).
    - Botón de agregar producto.
    - Mostrar subtotal por línea (quantity × unit_price) usando Placeholder reactive.
  - Step 3 "Review": Placeholder mostrando resumen de la orden con total calculado.
- Status — Select con los valores del enum OrderStatus, default "pending".
- Total — Hidden, calculado automáticamente desde los items del repeater.

### Vista (view)
- Infolist premium:
  - Section "Order Information": ID, Status (badge con color), Created At, Updated At.
  - Section "Customer": Name, Email.
  - Section "Order Summary": Total formateado.
- Relation manager: Products en esta orden con columnas (Product Name, Quantity, Unit Price, Subtotal calculado).

## 6. Resource: UserResource (solo lectura + órdenes)

### Tabla (index)
- Columnas: ID, Name (searchable, sortable), Email (searchable), Orders Count (counts badge, sortable), Total Spent (suma de orders.total, sortable), Created At (date, toggleable).
- Filtros: Has Orders (boolean), Registration Date (date range).
- Acciones: View (no edit, no delete — los usuarios se gestionan desde el flujo de auth).
- Default sort: name asc.
- Paginación: 10, 25, 50.

### Vista (view)
- Infolist: Name, Email, Member Since.
- Relation manager: Orders del usuario (tabla con ID, Status badge, Total, Date, link a order view).

## 7. Colores del enum OrderStatus para badges

Mapear colores consistentes en toda la aplicación:
- Pending → gray
- Processing → warning (amber)
- Shipped → info (blue)
- Delivered → success (green)
- Cancelled → danger (red)

Considerar agregar un método `getColor(): string` y `getLabel(): string` al enum `OrderStatus` para que Filament los use automáticamente con `HasLabel` y `HasColor`.

## 8. Testing

Crear tests Pest para el panel de administración:

### tests/Feature/Filament/CategoryResourceTest.php
- Puede renderizar la página index de categorías.
- Puede renderizar la página de creación.
- Puede crear una categoría.
- Puede renderizar la página de edición.
- Puede actualizar una categoría.
- Puede eliminar una categoría sin productos.
- No puede eliminar una categoría con productos (muestra notification).
- La tabla lista las categorías existentes.

### tests/Feature/Filament/ProductResourceTest.php
- Puede renderizar la página index.
- Puede crear un producto con categoría.
- Puede actualizar un producto.
- Puede eliminar un producto sin órdenes.
- No puede eliminar un producto con órdenes.
- Filtra productos por categoría correctamente.
- Filtra productos por stock status correctamente.

### tests/Feature/Filament/OrderResourceTest.php
- Puede renderizar la página index.
- Puede crear una orden con productos (wizard completo).
- El total se calcula correctamente desde los items.
- Puede cambiar el status de una orden desde la tabla.
- Puede eliminar una orden (cascade elimina pivot).
- Filtra órdenes por status.
- Filtra órdenes por cliente.

### tests/Feature/Filament/DashboardTest.php
- Puede renderizar el dashboard.
- Muestra las estadísticas correctas (revenue, orders, products, customers).
- El widget de órdenes recientes muestra datos.

## 9. Requisitos técnicos

- Usar Filament 5 — buscar la documentación con `search-docs` antes de implementar para asegurar compatibilidad con v5.
- Todo el código debe seguir las convenciones del proyecto (ver CLAUDE.md).
- Ejecutar `vendor/bin/sail bin pint --dirty --format agent` al finalizar.
- Ejecutar todos los tests y asegurar que pasen: `vendor/bin/sail artisan test --compact`.
- Los Resources deben usar `getEloquentQuery()` con eager loading para evitar N+1.
- Navigation: agrupar recursos bajo iconos lógicos (Shop: Products, Categories; Sales: Orders; People: Users).
- Navigation badges: mostrar conteos en los items del menú.
