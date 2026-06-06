# Gestión de turnos y empleados

Aplicación web para gestionar los turnos de trabajo de una empresa, con tres niveles de
acceso: **administrador** (visión y gestión global), **responsable de departamento** (gestiona
solo su equipo) y **empleado** (consulta sus propios turnos). El punto diferencial es el
**aislamiento por departamento**: un responsable no ve ni toca nada fuera del suyo.

Incluye API REST propia en JSON, autenticación por sesiones con token CSRF, validación de
reglas de negocio en servidor (solapamiento de turnos, máquina de estados, sustituciones) y un
frontend Bootstrap + jQuery donde **todas** las operaciones se hacen por AJAX sin recarga.

## Stack

| Capa | Tecnología |
|------|------------|
| Frontend | HTML5, CSS3, Bootstrap 5, JavaScript |
| Interactividad / AJAX | jQuery |
| Backend | PHP 8 **sin frameworks** (PDO + prepared statements en el 100% de las consultas) |
| Base de datos | MySQL 8 / MariaDB 10.4+ |
| API | REST JSON propia (front controller + router en `/api`) |
| Autenticación | Sesiones PHP + token CSRF en las mutaciones |
| Tests | Runner PHP propio (unitarios) + bash/curl (integración) + Playwright (E2E, opcional) |

Estructura: `public/` (único directorio expuesto), `api/` (router y controladores),
`includes/` (PDO, auth, permisos, validación, repositorios), `sql/` (schema + seed),
`tests/`, `docker/`.

## Instalación

Requisitos: PHP 8.x con la extensión PDO MySQL, MySQL 8 / MariaDB 10.4+ y, opcionalmente,
Apache 2.4 con `mod_rewrite`.

### 1. Base de datos (con usuario dedicado)

Se crean dos bases: la de la aplicación y una aislada para los tests de integración (que se
recrea en cada ejecución). Se usa un usuario dedicado en lugar de `root`, por convención:

```sql
CREATE DATABASE employee_manager      CHARACTER SET utf8mb4;
CREATE DATABASE employee_manager_test CHARACTER SET utf8mb4;

CREATE USER 'turnos_user'@'localhost' IDENTIFIED BY 'turnos_pass';
GRANT ALL PRIVILEGES ON employee_manager.*      TO 'turnos_user'@'localhost';
GRANT ALL PRIVILEGES ON employee_manager_test.* TO 'turnos_user'@'localhost';
FLUSH PRIVILEGES;
```

Carga del esquema y los datos de prueba:

```bash
mysql -uturnos_user -pturnos_pass employee_manager      < sql/schema.sql
mysql -uturnos_user -pturnos_pass employee_manager      < sql/seed.sql
# (opcional, para tests) la de pruebas también:
mysql -uturnos_user -pturnos_pass employee_manager_test < sql/schema.sql
mysql -uturnos_user -pturnos_pass employee_manager_test < sql/seed.sql
```

> El seed es **dinámico**: las fechas de los turnos se calculan con `DATE_ADD` desde el lunes
> de la semana actual (`CURDATE()`), cubriendo esta semana y la siguiente. Así los datos de
> prueba siempre caen en "la semana actual" se ejecute cuando se ejecute.

### 2. Configuración

```bash
cp includes/config.example.php includes/config.php
```

`config.php` lee variables de entorno (`DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`) con valores
por defecto. Ajusta los defaults o expórtalas para usar el usuario dedicado:

```php
define('DB_USER', getenv('DB_USER') ?: 'turnos_user');
define('DB_PASS', getenv('DB_PASS') ?: 'turnos_pass');
```

El `config.php` real está en `.gitignore`; lo versionado es `config.example.php`.

### 3a. Arranque rápido — servidor embebido (evaluación)

```bash
php -S localhost:8000 -t public public/router.php
```

Abrir <http://localhost:8000/login>. `public/router.php` sirve los ficheros estáticos y manda
el resto al front controller (necesario porque el servidor embebido ignora `.htaccess`).

### 3b. Apache (recomendado en producción)

```bash
sudo a2enmod rewrite
```

Virtual host — el `DocumentRoot` apunta **siempre** a `/public`, de modo que `includes/`,
`api/` y `sql/` quedan fuera del alcance web:

```apache
<VirtualHost *:80>
    ServerName turnos.local
    DocumentRoot /ruta/al/proyecto/public
    <Directory /ruta/al/proyecto/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Añade `127.0.0.1 turnos.local` a `/etc/hosts`. El `public/.htaccess` reescribe todo lo que no
sea fichero/directorio existente al front controller.

### 3c. Docker (alternativa)

Incluido en el repositorio: `docker-compose.yml` + `docker/Dockerfile` (PHP 8.2 + Apache con
`pdo_mysql` y `mod_rewrite`, `DocumentRoot` a `/public`) + `docker/vhost.conf`.

```bash
docker compose up -d --build --wait
# App:    http://localhost:8080/login
# MySQL:  localhost:3307  (app: turnos_user / turnos_pass · root: turnos_root)
docker compose down        # parar (añade -v para borrar también el volumen de datos)
```

El servicio `db` (MariaDB 11) carga `sql/schema.sql` y `sql/seed.sql` automáticamente vía
`docker-entrypoint-initdb.d` en la primera inicialización. Las credenciales y `DB_HOST=db` se
inyectan como `environment` del contenedor `web`; el código las lee con `getenv()`, por lo que
no hace falta `config.php` dentro del contenedor (cae a `config.example.php` y el entorno manda).

## Credenciales de demo

| Rol           | Correo                      | Contraseña |
|---------------|-----------------------------|------------|
| Administrador | admin@turnos.local          | Admin1234  |
| Responsable   | resp.soporte@turnos.local   | Resp1234   |
| Empleado      | emp.soporte@turnos.local    | Empl1234   |

También existen `resp.ventas@turnos.local` / `resp.oper@turnos.local` y
`emp.ventas@turnos.local` / `emp.oper@turnos.local`, con las mismas contraseñas por rol
(`Resp1234` / `Empl1234`).

## Tests

La forma más rápida es el runner único, que ejecuta los unitarios y toda la integración contra
la **base de pruebas aislada** (`employee_manager_test`), sin tocar la de desarrollo:

```bash
bash tests/run-all.sh
```

Si tu base usa un usuario distinto al de los defaults, pásalo por entorno:

```bash
DB_USER=turnos_user DB_PASS=turnos_pass bash tests/run-all.sh
```

Qué cubre:

- **Unitarios** (`php tests/unit.php`) — funciones puras: solapamiento horario (incluyendo que
  la adyacencia exacta no es solape, contención y solapes parciales), orden de horas y la
  matriz completa de transiciones de estado por rol.
- **Integración** (`tests/[0-9]*.sh`, vía curl) — auth/sesión/CSRF, CRUD de empleados y turnos
  con scope por rol, filtros combinables, reglas de negocio (solapamiento, sustitución,
  máquina de estados, coherencia al borrar/desactivar) y un checklist de aceptación que mapea
  el mapa de códigos HTTP (200/201/401/403/404/409/422).

### End-to-end de la UI (opcional, Playwright)

Cubre los flujos AJAX en un navegador real (usa el **Chrome del sistema**, no descarga
Chromium): redirección por rol, toast de login inválido, alta de turno con aviso de
solapamiento en vivo, edición y reapertura de turnos, asignación de sustituto (con el ausente
excluido del desplegable) y escapado de XSS. Recrea `employee_manager_test` antes de cada test.

```bash
cd tests/browser && npm install && npx playwright test
```

## Decisiones de diseño

- **`departamento` como `VARCHAR` con FK a un catálogo.** El enunciado define `departamento`
  como `VARCHAR` en `empleados`; se conserva ese tipo y nombre, pero es **clave foránea** a una
  tabla catálogo `departamentos(nombre)` con `ON UPDATE CASCADE`. Aporta integridad referencial
  (el aislamiento de permisos depende de comparar el departamento) y alimenta los desplegables,
  sin alterar el contrato del PDF.
- **Columna `contrasena` (sin ñ).** Se nombra `contrasena` por robustez de herramientas y
  encoding; el hash es **bcrypt** vía `password_hash` / `password_verify`.
- **Reapertura a `programado` solo del administrador, con limpieza.** Devolver un turno a
  `programado` (desde `confirmado`/`ausente`/`cubierto`) es exclusivo del administrador. Al
  hacerlo se **limpian `sustituto_id` y `motivo_ausencia`**, para que un turno `programado` no
  arrastre datos de una ausencia o sustitución anteriores.
- **Coherencia al borrar/desactivar sustitutos.** Al borrar un empleado o ponerlo
  `activo = false`, los turnos donde figuraba como **sustituto y estaban `cubierto`** vuelven a
  `ausente` con `sustituto_id` NULL (dentro de la misma transacción), evitando turnos
  `cubierto` sin sustituto.
- **Seguridad transversal.** Prepared statements en el 100% de las consultas; escapado de toda
  salida HTML; token CSRF en cada mutación AJAX; `session_regenerate_id` en el login; el scope
  por rol/departamento se deriva de la sesión y se añade con `AND` a las consultas (un parámetro
  solo puede estrechar, nunca ampliar); `motivo_ausencia` no se expone al rol empleado. Acceso
  a un recurso fuera de scope → **404** (no se filtra su existencia entre departamentos).

## Limitaciones y mejoras

- **Editar turno no permite cambiar el titular.** `PUT /api/shifts/{id}` no admite reasignar
  `empleado_id` por diseño; el modal de edición deja el selector de empleado bloqueado y solo
  envía fecha/horas/tipo. Permitir reasignar titular requeriría ampliar la lógica del backend.
- **El nombre del sustituto se muestra a todos los roles.** No se considera sensible (a
  diferencia de `motivo_ausencia`, que sí se oculta al empleado); restringirlo sería un cambio
  de una línea en el serializador.
- **`docker-compose` orientado a desarrollo.** Las credenciales viajan en el `compose`; para
  producción convendría gestionarlas como secretos.
- **Sin tests unitarios de JavaScript.** La lógica de cliente se valida con smoke + E2E de
  Playwright, no con pruebas unitarias de JS.
- **Posibles mejoras:** paginación/orden en los listados, endpoint dedicado de departamentos
  (hoy el catálogo del frontend se deriva de `/api/employees`), borrado lógico de empleados,
  internacionalización de textos y un pipeline de CI que ejecute las tres capas de tests.
