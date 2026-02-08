Crea un sistema de e-commerce en Laravel con el siguiente modelo relacional.                                                  

  ## Entidades                    

  ### users

  - id (PK)

  - name

  - email (unique)

  - password

  - timestamps



  ### categories

  - id (PK)

  - name

  - timestamps



  ### products

  - id (PK)

  - category_id (FK → categories.id, RESTRICT ON DELETE)

  - name

  - description

  - price

  - stock

  - timestamps



  ### orders

  - id (PK)

  - user_id (FK → users.id, RESTRICT ON DELETE)

  - status (enum: pending, processing, shipped, delivered, cancelled)

  - total

  - timestamps



  ### order_product (tabla pivote)

  - id (PK)

  - order_id (FK → orders.id, CASCADE ON DELETE)

  - product_id (FK → products.id, RESTRICT ON DELETE)

  - quantity

  - unit_price

  - timestamps



  ## Relaciones

  1. User → Order (1:N): Un usuario puede tener muchas órdenes. Una orden pertenece a un solo usuario. RESTRICT ON DELETE — no se puede borrar un usuario si tiene órdenes.



  2. Category → Product (1:N): Una categoría agrupa muchos productos. Un producto pertenece a una sola categoría. RESTRICT ON DELETE — no se puede borrar una categoría si tiene productos asociados.



  3. Order ↔ Product (N:M): Una orden puede tener muchos productos y un producto puede aparecer en muchas órdenes. Se resuelve con la tabla pivote order_product que almacena quantity y unit_price

  (precio al momento de la compra).



  ## Lógica de borrado

  - Borrar usuario → Bloqueado si tiene órdenes (RESTRICT).

  - Borrar categoría → Bloqueado si tiene productos (RESTRICT).

  - Borrar producto → Bloqueado si aparece en alguna orden (RESTRICT).

  - Borrar orden → Se eliminan automáticamente sus registros en order_product (CASCADE), porque una línea de detalle no existe sin su orden.



  ## Requisitos técnicos

  - Crear migraciones para todas las tablas con sus foreign keys y estrategias de borrado.

  - Crear modelos Eloquent con sus relaciones (hasMany, belongsTo, belongsToMany).

  - La relación belongsToMany entre Order y Product debe incluir los campos pivote quantity y unit_price con withPivot() y withTimestamps().

  - Crear factories y seeders para todas las entidades con datos de prueba.



  ## Testing

  - Corrige la base de datos de testing en phpunit.xml.

  - Crea una suite de test todas las acciones que se realizan y ejecutala.
