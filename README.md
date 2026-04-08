##
-Comandos a usar para levantar el backend
-Tener Composer Instalado
*tener pgadmin4 instalado*
-crar un database con el nombre de ----->Kawi-Tis
-usar estos comandos para levantarlo
php artisan config:clear
php artisan migrate:status
php artisan migrate
php artisan serve
si hay un error con el comando "php artisan server" usar el comando php -S 127.0.0.1:9200 -t public
eso es depende a la ip que esta configurado en tu maquina 
cualquier duda al 77417175 o al 65315925

estamos aca para servirlos, muchas gracias por confiar en nosotros :D
##


## Backend - Plataforma de Servicios

Backend desarrollado con **PHP 8.3+** usando el framework **Laravel 13**.  
Este proyecto expone una API para una plataforma tipo **LinkedIn + GitHub**, con enfoque en perfiles profesionales, proyectos y portafolios.

---

## Descripción

- **Framework**: Laravel (PHP)
- **Arquitectura**: basada en módulos de negocio (por ejemplo: `Users`, `Companies`, etc.)
- **Objetivo**: servir como backend para una plataforma profesional que combina:
  - Perfiles y networking (estilo LinkedIn)
  - Portafolios y proyectos (estilo GitHub)

El proyecto parte de la estructura estándar de Laravel y puede evolucionar hacia una organización por módulos dentro de `app/`.

---

## Requisitos

- **PHP** >= 8.3
- **Composer** (gestor de dependencias PHP)
- **Extensiones PHP** recomendadas por Laravel (mbstring, openssl, pdo, etc.)
- **Base de datos** (MySQL/MariaDB, PostgreSQL, SQLite u otra soportada por Laravel)

---

## Instalación

1. Clonar el repositorio (o copiar el proyecto a tu entorno de trabajo).
2. Desde la carpeta del backend (`Dev_Profile_UMSS_BE`), instalar dependencias:

```bash
composer install
```

3. Crear el archivo de entorno:

```bash
cp .env.example .env
```

En Windows (PowerShell):

```powershell
Copy-Item .env.example .env
```

4. Generar la clave de la aplicación:

```bash
php artisan key:generate
```

5. Configurar la base de datos en el archivo `.env`:

- `DB_CONNECTION`
- `DB_HOST`
- `DB_PORT`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`

6. Ejecutar migraciones (opcional, si ya tienes modelos/migraciones definidas):

```bash
php artisan migrate
```

---

## Ejecución en desarrollo

Desde la carpeta del backend:

```bash
php artisan serve
```

Por defecto, la aplicación estará disponible en:

- `http://127.0.0.1:8000`

Si se definen rutas de API en `routes/api.php`, estarán bajo el prefijo:

- `http://127.0.0.1:8000/api/...`

---

## Scripts útiles (Composer)

En `composer.json` vienen definidos algunos scripts que puedes usar, por ejemplo:

- **Entorno de desarrollo completo (Laravel + cola + Vite)**:

```bash
composer run dev
```

- **Pruebas**:

```bash
composer test
```

> Revisa la sección `scripts` de `composer.json` para ver el detalle y cualquier script adicional.

---

## Estructura principal del proyecto

Estructura base (simplificada) de Laravel:

```text
app/
  Console/
  Exceptions/
  Http/
    Controllers/
    Middleware/
  Models/

bootstrap/
config/
database/
  factories/
  migrations/
  seeders/

public/
resources/
routes/
  api.php
  web.php
storage/
tests/
```

### Carpetas clave

- **`app/Http/Controllers/`**: controladores HTTP (puntos de entrada de las peticiones).
- **`app/Models/`**: modelos de Eloquent (mapeo ORM a tablas de base de datos).
- **`database/migrations/`**: migraciones de base de datos.
- **`routes/web.php`**: rutas web (HTML, vistas).
- **`routes/api.php`**: rutas de API (endpoints JSON).
- **`config/`**: configuración de la aplicación (DB, cache, mail, etc.).
- **`public/`**: punto de entrada público (`index.php`) para el servidor web.

---

## Organización por módulos (sugerida)

Para alinear el backend con una arquitectura por módulos de negocio, se recomienda ir organizando el código en algo similar a:

```text
app/
  Modules/
    Users/
      Http/
        Controllers/
      Services/
      Repositories/
      Dto/
      Resources/
    Companies/
      Http/
        Controllers/
      Services/
      Repositories/
      Dto/
      Resources/
    ...
```

### Capas sugeridas por módulo

- **Controllers** (`Http/Controllers/` o `Modules/*/Http/Controllers/`):
  - Reciben la request HTTP.
  - Validan la entrada (Requests/DTOs).
  - Delegan la lógica al **Service** correspondiente.
  - Devuelven responses (JSON, recursos, etc.).

- **Services**:
  - Contienen la **lógica de negocio**.
  - Orquestan llamadas a repositories, otros servicios, eventos, etc.

- **Repositories**:
  - Encapsulan el acceso a datos (consultas Eloquent/Query Builder).
  - Evitan que los services dependan directamente de modelos concretos.

- **Dto / Requests**:
  - Validan y normalizan la data de entrada/salida.

---

## Flujo típico de una request

```text
Cliente → Ruta (routes/api.php) → Controller → Service → Repository → Base de datos
                                                   ↓
                                              Respuesta JSON
```

1. La ruta define el endpoint y apunta a un método de un controller.
2. El controller valida la request y llama al service.
3. El service aplica reglas de negocio y usa repositories/modelos.
4. El repository interactúa con la base de datos (Eloquent).
5. La respuesta viaja de vuelta al cliente (controller → cliente).

---

## Autenticación (sugerida)

Laravel soporta múltiples estrategias de autenticación. Para una API tipo plataforma de servicios suele usarse:

- **Tokens de acceso** (por ejemplo, Laravel Sanctum o Passport).
- Header: `Authorization: Bearer <token>`.
- Middleware de autenticación aplicado a las rutas protegidas en `routes/api.php`.

La implementación concreta puede variar según los requisitos del proyecto.

---

## Reglas del proyecto (recomendadas)

- No acceder directamente a la base de datos desde los controllers.
- Centralizar la lógica de negocio en **services**.
- Mantener el acceso a datos encapsulado en **repositories** o modelos bien definidos.
- Mantener una organización por módulos de negocio (`Modules/*`) a medida que el proyecto crece.
- Respetar las convenciones de formato (Laravel Pint / PHP-CS-Fixer) para mantener un código limpio.

---

## Convención de commits

Se recomienda seguir una convención tipo **Conventional Commits**:

- `feat`: nueva funcionalidad
- `fix`: corrección de bug
- `chore`: tareas de mantenimiento (deps, scripts, etc.)
- `refactor`: cambios internos sin modificar comportamiento observable
- `docs`: cambios en documentación
- `test`: añadir o modificar pruebas
- `style`: cambios de estilo/formato (sin cambios lógicos)

---

## Filosofía del proyecto

- **Separación de responsabilidades**: cada capa / módulo tiene un rol claro.
- **Escalabilidad**: estructura preparada para crecer en features y equipos.
- **Código limpio**: legible, coherente y fácil de mantener.
- **Alineado con buenas prácticas de Laravel**: aprovechando su ecosistema (migraciones, colas, eventos, etc.).
