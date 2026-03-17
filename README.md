# Backend - Plataforma de Servicios

Backend modular desarrollado con **Node.js + TypeScript** y **Prisma** como ORM. Implementa una arquitectura basada en módulos (feature-based) y capas (controller, service, repository), pensada para una plataforma tipo **LinkedIn + GitHub + Marketplace**.

---

## Descripción

Este backend proporciona la API para una aplicación que combina:

- **Networking profesional** (estilo LinkedIn)
- **Portafolio y proyectos** (estilo GitHub)
- **Marketplace de servicios** (estilo Freelancer)

Características técnicas:

- **Node.js + TypeScript** para tipado fuerte y mantenibilidad
- **Prisma** como ORM para acceso a base de datos
- **Arquitectura modular** por dominio de negocio (auth, users, companies, etc.)
- **Capas definidas** (controller → service → repository) para separación de responsabilidades

---

## Instalación

### Requisitos

- **Node.js** (>= 18)
- **pnpm** (gestor de paquetes recomendado)

### Instalación de pnpm

```bash
npm install -g pnpm
```

### Instalar dependencias del proyecto

```bash
pnpm install
```

---

## Configuración de Prisma

### Generar cliente Prisma

Antes de ejecutar el proyecto, generar el cliente de Prisma:

```bash
pnpm prisma:generate
```

### Ejecutar migraciones (si aplica)

Para aplicar migraciones en desarrollo:

```bash
pnpm prisma:migrate
```

### Ubicación del schema

El esquema de la base de datos se define en:

```
prisma/schema.prisma
```

---

## Ejecución del proyecto

### Modo desarrollo

```bash
pnpm run dev
```

Servidor en modo desarrollo con hot reload.

---

## Estructura del proyecto

```text
prisma/
  schema.prisma

src/
  auth/
  calendar/
  cases/
  companies/
  documents/
  notifications/
  procedures/
  settings/
  users/
  db/
  shared/
  config/
  routes/
  app.ts
  server.ts
```

- **`prisma/`**: definición del esquema y migraciones
- **`src/`**: código fuente del backend
- **`app.ts`**: configuración de la aplicación (middlewares, rutas)
- **`server.ts`**: punto de entrada, arranque del servidor

---

## Arquitectura por módulos

Cada módulo de negocio (`auth`, `users`, `calendar`, `cases`, `companies`, `documents`, `notifications`, `procedures`, `settings`) sigue la misma estructura de capas:

```text
src/<modulo>/
  controllers/
  services/
  repositories/
  dto/
  types/
  routes/
```

| Capa | Carpeta | Responsabilidad |
|------|---------|-----------------|
| **HTTP** | `controllers/` | Recibe requests, valida entrada, delega al service y devuelve la respuesta HTTP |
| **Negocio** | `services/` | Lógica de negocio, reglas de dominio, orquestación |
| **Datos** | `repositories/` | Acceso a base de datos vía Prisma, queries y transacciones |
| **Validación** | `dto/` | Objetos de transferencia y validación de entrada/salida |
| **Tipos** | `types/` | Interfaces y tipos TypeScript del módulo |
| **Rutas** | `routes/` | Definición de endpoints y mapeo a controllers |

---

## Carpeta `db/`

- **`prismaClient.ts`**: instancia única del cliente Prisma (singleton)
- **`migrations/`**: migraciones de base de datos generadas por Prisma

---

## Carpeta `shared/`

Recursos compartidos entre todos los módulos:

- **`middlewares/`**: middleware de autenticación (JWT), manejo de errores global, logging
- **`utils/`**: funciones auxiliares reutilizables
- **`constants/`**: constantes globales del proyecto
- **`types/`**: tipos e interfaces compartidos entre módulos

---

## Carpeta `config/`

- **`env.ts`**: carga y validación de variables de entorno
- **`index.ts`**: configuración general exportada (entorno, puerto, base de datos, etc.)

---

## Flujo del backend

El flujo de cada request sigue una cadena clara de responsabilidades:

```text
Request → Controller → Service → Repository → Database
            ↓            ↓            ↓
         HTTP/JSON   Lógica      Prisma
         respuestas  negocio     queries
```

1. **Controller**: recibe el request, extrae parámetros/body, llama al service
2. **Service**: aplica la lógica de negocio, usa el repository
3. **Repository**: ejecuta consultas a la base de datos con Prisma
4. **Response**: el resultado viaja de vuelta (repository → service → controller → cliente)

---

## Autenticación

- **JWT (JSON Web Token)** como mecanismo de autenticación
- Header: `Authorization: Bearer <token>`
- Middleware en `shared/middlewares/` para validar el token y adjuntar el usuario al request

---

## Reglas del proyecto

- **No acceder directamente a la base de datos desde controllers**; toda consulta debe pasar por un repository
- **Toda lógica de negocio debe estar en services**; los controllers solo manejan HTTP
- **Prisma solo se usa en repositories**; el cliente Prisma no se importa en controllers ni services
- **Código modular y escalable**: cada módulo es independiente y sigue la misma estructura de capas

---

## Convención de commits

Se recomienda [Conventional Commits](https://www.conventionalcommits.org/):

- **`feat`**: nueva funcionalidad
- **`fix`**: corrección de bug
- **`chore`**: tareas de mantenimiento (dependencias, scripts, etc.)
- **`refactor`**: refactorización sin cambiar el comportamiento
- **`docs`**: cambios en documentación
- **`test`**: añadir o modificar pruebas
- **`style`**: formato de código (espacios, comas, etc.)

---

## Filosofía del proyecto

- **Separación de responsabilidades**: cada capa tiene un propósito claro
- **Escalabilidad**: estructura que permite añadir módulos y funcionalidades sin degradar el código
- **Código limpio**: nombres claros, funciones pequeñas, tipado fuerte con TypeScript
- **Arquitectura mantenible**: fácil de entender, testear y evolucionar
