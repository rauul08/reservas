# ReservaDemo

Este repo contiene una API REST minimalista para gestionar reservas de habitaciones (Users, Rooms, Reservations) usando una organización basada en DDD (Domain/Application/Infrastructure) sobre Slim 4 y PDO/MySQL.

---

**Índice rápido**

- 1) Descripción general
- 2) Tecnologías
- 3) Estructura del proyecto
- 4) Endpoints (API)
- 5) Lógica clave
- 6) Cómo ejecutar el proyecto
- 7) Datos de prueba
- 8) Errores comunes (JSON)
- 9) Roadmap

---

**1. Descripción general del proyecto**

ReservaDemo es una API para crear, listar, modificar y cancelar reservas de habitaciones. Su propósito es servir como backend para un front-end de reservas o como ejemplo de arquitectura DDD + Slim + PDO.

Organización breve:
- Los modelos de dominio, casos de uso y repositorios siguen la separación Domain / Application / Infrastructure dentro de `src/`.
- Las rutas HTTP están en `app/routes.php`.

---

**2. Tecnologías utilizadas**

- PHP 7.2+ / 8.x compatible
- MySQL (XAMPP usado en desarrollo)
- Slim 4 (routing y middleware)
- PHP-DI (contenedor)
- Composer para autoload y dependencias

Librerías importantes en `composer.json`:
- `slim/slim`, `php-di/php-di`, `monolog/monolog`, entre otras.

Requerimientos del servidor:
- Apache/Nginx (o usar el servidor embebido de PHP para desarrollo)
- Extensiones PHP: `pdo`, `pdo_mysql`, `json`

---

**3. Estructura del proyecto**

Arbol superior relevante:

- `app/` : bootstrap, rutas y bindings (ej. `app/routes.php`, `app/dependencies.php`)
- `public/` : punto de entrada público (`index.php`)
- `src/` : código fuente organizado por módulos
	- `src/Reservations/Domain` : entidades, value objects, repositorios (interfaces)
	- `src/Reservations/Application` : casos de uso (CreateReservation, UpdateReservation, CancelReservation)
	- `src/Reservations/Infrastructure` : repositorios MySQL, mappers y controladores HTTP
	- `src/Users/...`, `src/Rooms/...` : módulos Users/Rooms con mismo layout
- `tests/` : pruebas unitarias (si las hay)

Dónde está cada cosa:
- Controladores HTTP: `src/*/Infrastructure/Controllers`
- Repositorios MySQL: `src/*/Infrastructure/Repository`
- Entidades / Value Objects: `src/*/Domain/Entities` y `src/*/Domain/ValueObjects`
- Mappers (DB <-> Entity): `src/*/Infrastructure/Mappers`
- Configuración: `app/settings.php`
- Rutas: `app/routes.php`
- Punto público: `public/index.php`

---

**4. Endpoints disponibles (documentación API)**

Todos los endpoints devuelven JSON y esperan cabecera `Accept: application/json`. Usan códigos HTTP semánticos.

Users
- GET `/users`
- GET `/users/{id}`

Rooms
- GET `/rooms`
- GET `/rooms/{id}`

Reservations
- GET `/reservations` (listado con filtros y paginación)
	- Query params:
		- `page` (int, default 1)
		- `per_page` (int, default 10, max 100)
		- `user_id` (int)
		- `room_id` (int)
		- `status` (string: `pending`|`confirmed`|`cancelled`)
		- `from` (datetime string `YYYY-MM-DD HH:MM:SS`) — fecha inicio rango
		- `to` (datetime string) — fecha fin rango
	- Ejemplo:
		- Request: `GET /reservations?page=1&per_page=10&user_id=2&from=2025-12-01%2000:00:00&to=2025-12-10%2023:59:59`
		- Response (200):
			```json
			{
				"data": [ { /* reservation DTO */ } ],
				"meta": { "total": 42, "page": 1, "per_page": 10 }
			}
			```

- GET `/reservations/{id}`
	- Path param: `id` (int)
	- Response (200): reservation DTO ejemplo:
		```json
		{
			"id": 1,
			"status": "confirmed",
			"check_in": "2025-12-01 14:00:00",
			"check_out": "2025-12-05 11:00:00",
			"total_price": 8000,
			"user": { "id": 1, "full_name": "Juan Pérez", "email": "juan@example.com", "phone": "555-111-2222" },
			"room": { "id": 2, "number": "202", "type": "double", "description": "...", "price_per_night": 2000 }
		}
		```

- POST `/reservations`
	- Body JSON: `check_in`, `check_out`, `user_id`, `room_id` (ejemplo):
		```json
		{
			"check_in": "2025-12-10 14:00:00",
			"check_out": "2025-12-15 11:00:00",
			"user_id": 1,
			"room_id": 2
		}
		```
	- Response: 201 Created con el DTO completo (igual formato al GET/{id}).

- PUT `/reservations/{id}`
	- Body JSON: `check_in`, `check_out`, opcional `room_id` para cambiar habitación
	- Validaciones:
		- `check_out` > `check_in`
		- Si `room_id` cambia, valida existencia y disponibilidad en el nuevo rango
		- No permitir editar si `status == 'cancelled'`
	- Response: 200 OK con DTO actualizado.

- DELETE `/reservations/{id}`
	- Marca la reserva como `cancelled` (no borra físicamente).
	- Validaciones:
		- No permitir cancelar si `check_out` ya pasó (reservas vencidas)
		- No permitir cancelar si ya está `cancelled`
	- Response: 200 OK con DTO actualizado (status = `cancelled`).

Errores relevantes (códigos):
- 400 Bad Request: campo faltante, fechas inválidas, room not found, room not available
- 404 Not Found: reservation/user/room not found
- 500 Internal Server Error: error inesperado

---

**5. Lógica clave del proyecto**

- `total_price`: calculado como `nights * room.price`.
	- `nights` = diferencia en días entre `check_out` y `check_in` (mínimo 1).
- Disponibilidad: una habitación está ocupada si existe una reserva para esa habitación donde las fechas se solapan. Condición usada: NOT (existing.check_out < new.from OR existing.check_in > new.to).
- Estados de reserva:
	- `pending`: creada pero sin confirmación de pago/operación.
	- `confirmed`: confirmada (p. ej. pago recibido).
	- `cancelled`: anulada — no puede modificarse ni volver a cancelar.
- Reglas de `check_in`/`check_out`:
	- `check_out` debe ser mayor que `check_in`.
	- Validaciones en creación y edición.
- Cambio de `room_id` en edición:
	- Si el cliente envía `room_id` en PUT, el sistema intentará usar la nueva habitación, validará existencia y disponibilidad, y recalculará `total_price` según el precio por noche de la nueva habitación.

---

**6. Cómo ejecutar el proyecto (entorno de desarrollo)**

Pasos rápidos:

1. Clonar repo:
	 ```bash
	 git clone <repo> && cd reservademo
	 ```
2. Instalar dependencias:
	 ```bash
	 composer install
	 ```
3. Configurar base de datos (MySQL / XAMPP):
	 - Crear base de datos `reservademo`.
	 - Importar el SQL de esquema (si existe `schema.sql`):
		 ```bash
		 mysql -u root -p reservademo < sql/schema.sql
		 ```
	 - Ajustar `app/settings.php` con las credenciales DSN/user/pass.
4. Configurar virtual host o usar PHP embebido para pruebas:
	 ```powershell
	 # desde la carpeta public
	 php -S localhost:8000 -t public
	 ```
5. Regenerar autoload si cambias clases:
	 ```bash
	 composer dump-autoload -o
	 ```

---

**7. Datos de prueba**

Agregar algunos inserts mínimos (ejemplo):

SQL de ejemplo (simplificado):
```sql
INSERT INTO users (id, first_name, last_name, email, phone) VALUES
(1,'Juan','Pérez','juan@example.com','555-111-2222');

INSERT INTO rooms (id, room_number, room_type, capacity, price, description, status) VALUES
(1,'101','single',1,1000,'Individual económica','available'),
(2,'202','double',2,2000,'Habitación doble amplia','available');

-- Reservación de ejemplo
INSERT INTO reservations (user_id, room_id, check_in, check_out, total_price, status) VALUES
(1,2,'2025-12-01 14:00:00','2025-12-05 11:00:00',8000,'confirmed');
```

Ejemplos funcionales de `POST`/`PUT` ya están en la sección de endpoints.

---

**8. Posibles errores (y sus respuestas JSON)**

- `Room not found` (400)
	```json
	{ "error": "Invalid data", "details": "Room with id \"999\" not found" }
	```
- `User not found` (400)
	```json
	{ "error": "Invalid data", "details": "User with id \"5\" not found" }
	```
- `Invalid date range` (400)
	```json
	{ "error": "Invalid data", "details": "check_out must be greater than check_in" }
	```
- `Room not available` (400)
	```json
	{ "error": "Invalid data", "details": "Room is occupied in the requested date range" }
	```
- `Reservation not found` (404)
	```json
	{ "error": "Invalid data", "details": "Reservation not found" }
	```
- `Invalid JSON` (400)
	```json
	{ "error": "Malformed JSON", "details": "Syntax error" }
	```

---

**9. Roadmap (qué sigue)**

- Autenticación y permisos
- Roles de usuario (admin, receptionist, guest)
- Tests automatizados e integración continua
- Filtros y ordenamientos avanzados, cache para listados
- Pagos / integración con pasarela y status real de `confirmed`
