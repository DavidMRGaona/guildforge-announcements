# GuildForge - Módulo de anuncios

Sistema de anuncios y comunicaciones para la plataforma GuildForge. Permite crear, gestionar y mostrar anuncios a los miembros de la asociación.

## Características

- Gestión completa de anuncios (CRUD) desde el panel de administración
- Múltiples niveles de visibilidad (público, autenticados, miembros)
- Posicionamiento flexible en diferentes zonas de la página
- Sistema de prioridades para ordenar anuncios
- Fechas de publicación y expiración programables
- Anuncios descartables por el usuario
- Componentes Vue reutilizables (banner, card)
- API REST para integración
- Soporte multiidioma (español, inglés)

## Requisitos

- PHP >= 8.2
- Laravel >= 12.0
- GuildForge (aplicación principal)

## Instalación

1. Copia el módulo en el directorio `modules/`:

```bash
cp -r announcements /path/to/guildforge/modules/
```

2. Descubre el módulo:

```bash
php artisan module:discover
```

3. Habilita el módulo:

```bash
php artisan module:enable announcements
```

4. Ejecuta las migraciones:

```bash
php artisan module:migrate announcements
```

## Estructura

```
announcements/
├── config/
│   ├── module.php          # Configuración del módulo
│   └── settings.php        # Configuración de ajustes
├── database/
│   └── migrations/         # Migraciones de base de datos
├── lang/
│   ├── en/                 # Traducciones en inglés
│   └── es/                 # Traducciones en español
├── resources/
│   └── js/
│       └── components/     # Componentes Vue
├── routes/
│   ├── api.php             # Rutas API
│   └── web.php             # Rutas web
├── src/
│   ├── Application/
│   │   ├── DTOs/           # Data Transfer Objects
│   │   └── Services/       # Servicios de aplicación
│   ├── Domain/
│   │   ├── Entities/       # Entidades de dominio
│   │   ├── Enums/          # Enumeraciones
│   │   ├── Exceptions/     # Excepciones de dominio
│   │   ├── Repositories/   # Interfaces de repositorio
│   │   └── ValueObjects/   # Objetos de valor
│   ├── Filament/
│   │   └── Resources/      # Recursos de Filament
│   ├── Http/
│   │   └── Controllers/    # Controladores HTTP
│   ├── Infrastructure/
│   │   └── Persistence/    # Implementación de persistencia
│   └── Policies/           # Políticas de autorización
├── tests/
│   ├── Feature/            # Tests de integración
│   └── Unit/               # Tests unitarios
└── module.json             # Manifiesto del módulo
```

## Uso

### Panel de administración

Una vez habilitado, aparecerá un nuevo grupo "Comunicación" en el menú lateral del panel de administración con acceso a la gestión de anuncios.

### API

```
GET    /api/announcements           # Listar anuncios activos
GET    /api/announcements/{id}      # Obtener un anuncio
POST   /api/announcements/{id}/dismiss  # Descartar anuncio
```

### Componentes Vue

```vue
<template>
  <AnnouncementBanner :announcement="announcement" />
  <AnnouncementCard :announcement="announcement" />
</template>
```

## Configuración

### Visibilidad

| Valor | Descripción |
|-------|-------------|
| `public` | Visible para todos los visitantes |
| `authenticated` | Solo usuarios autenticados |
| `members` | Solo miembros de la asociación |

### Posiciones

| Valor | Descripción |
|-------|-------------|
| `before-header` | Antes del encabezado |
| `after-header` | Después del encabezado |
| `before-content` | Antes del contenido principal |
| `after-content` | Después del contenido principal |
| `before-footer` | Antes del pie de página |
| `after-footer` | Después del pie de página |

### Prioridad

Valores de 1 a 10, donde 10 es la máxima prioridad.

## Permisos

| Permiso | Descripción |
|---------|-------------|
| `announcements.view` | Ver anuncios en el panel |
| `announcements.create` | Crear nuevos anuncios |
| `announcements.update` | Editar anuncios existentes |
| `announcements.delete` | Eliminar anuncios |

## Tests

```bash
# Ejecutar todos los tests del módulo
php artisan test modules/announcements/tests

# Solo tests unitarios
php artisan test modules/announcements/tests/Unit

# Solo tests de integración
php artisan test modules/announcements/tests/Feature
```

## Licencia

Propietario - GuildForge
