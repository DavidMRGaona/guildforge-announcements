# GuildForge - Módulo de anuncios

Anuncios para la web pública de GuildForge. Se crean en el panel de administración y se muestran como banner en la zona de la página elegida en cada anuncio, solo a quien puede verlos y solo mientras están vigentes.

## Características

- Gestión de anuncios desde el panel (grupo **Comunicación** → **Anuncios**): crear, editar, eliminar, filtrar por visibilidad, posición y estado
- Tres niveles de visibilidad: público, usuarios autenticados y solo socios
- Seis posiciones en el layout público (antes y después de la cabecera, el contenido y el pie)
- Prioridad de 1 a 10: ordena los anuncios y fija el color por defecto del banner
- Colores de fondo y de texto propios por anuncio
- Fechas de inicio y fin opcionales, además del interruptor de activo
- Anuncios que el visitante puede cerrar (o no), a elección de cada anuncio
- Rutas JSON de solo lectura (`/anuncios`)
- Traducciones en español e inglés

## Requisitos

- GuildForge core `^3.22` (rol de socio configurable, fechas en UTC)
- PHP >= 8.5, Laravel >= 13, Filament 5

El core rechaza el módulo si no se cumplen (`module.json` → `requires`).

Las fechas de vigencia se guardan en la zona de almacenamiento del sitio (UTC cuando el core lo indica) y el panel y la API las muestran en la zona de visualización (Europe/Madrid). El módulo declara `"storesUtc": true`.

## Instalación

1. Descarga `announcements-X.Y.Z.zip` de la última release de `DavidMRGaona/guildforge-announcements` en GitHub.
2. En el panel, activa el modo mantenimiento (**Configuración del sitio** → pestaña **Mantenimiento**).
3. En **Módulos**, pulsa **Instalar / actualizar** y sube el ZIP.
4. Pulsa **Habilitar** en el módulo: la primera vez ejecuta sus migraciones.
5. Desactiva el modo mantenimiento.

Las versiones nuevas se aplican desde **Actualizaciones de módulos** (también con el modo mantenimiento activo) o con `php artisan module:update announcements`.

### En desarrollo

Con el repositorio clonado en `src/modules/announcements`:

```bash
php artisan module:discover
php artisan module:enable announcements
```

Los componentes Vue se compilan con las herramientas del core, desde `src/`:

```bash
docker exec guildforge_node npm run build:module -- announcements
```

En las releases los assets los compila el workflow y van dentro del ZIP.

## Uso

### Anuncios

Cada anuncio tiene:

| Campo | Descripción |
|-------|-------------|
| Título y contenido | El banner muestra el título y una línea del contenido como texto plano |
| Activo | Solo los anuncios activos se muestran |
| Permite cerrar | Si el visitante puede cerrarlo |
| Visibilidad | Quién lo ve (ver abajo) |
| Posición | En qué zona del layout aparece |
| Prioridad | De 1 (baja) a 10 (alta) |
| Color de fondo y de texto | Opcionales; sin ellos el color sale de la prioridad: 8-10 color de error del tema, 5-7 de aviso, 1-4 de información |
| Fecha de inicio y de fin | Opcionales; fuera de ese intervalo el anuncio no se muestra |

#### Visibilidad

| Valor | Quién lo ve |
|-------|-------------|
| `public` | Todos los visitantes |
| `authenticated` | Usuarios con sesión iniciada |
| `members` | Socios: usuarios con el rol de socio del core |

«Solo socios» se refiere a los socios de la asociación, no a los usuarios registrados: el rol `member`, que tiene cualquier usuario registrado, no basta. El rol de socio se elige en Configuración del sitio → Autenticación (`socio` por defecto) y el módulo se lo pregunta al core, así que un cambio en ese ajuste se aplica sin tocar el módulo. Los administradores no tienen excepción: sin el rol de socio no ven los anuncios para socios. Si el rol de socio configurado no existe o no es válido (uno de los roles base `admin`, `editor` o `member`), nadie ve los anuncios para socios.

#### Posiciones

| Valor | Zona |
|-------|------|
| `before-header` | Antes de la cabecera |
| `after-header` | Después de la cabecera |
| `before-content` | Antes del contenido principal |
| `after-content` | Después del contenido principal |
| `before-footer` | Antes del pie de página |
| `after-footer` | Después del pie de página |

### Banner en la web pública

El módulo registra el banner en las seis posiciones del layout; no hay que añadirlo a mano. Cada banner muestra los anuncios de su posición, ordenados por prioridad. Con varios anuncios en la misma posición aparecen flechas y puntos para pasar de uno a otro, y la rotación automática si está activada en los ajustes.

Al cerrar un anuncio se guarda en el navegador (`localStorage`): deja de verse en ese navegador, no en los demás.

Los anuncios visibles para el usuario actual se comparten con todas las páginas como prop de Inertia `announcements`. El componente `AnnouncementCard` (`:announcement`) está disponible para mostrar un anuncio en otras vistas.

### Ajustes

En **Módulos**, acción **Configuración** del módulo:

| Ajuste | Descripción |
|--------|-------------|
| Mostrar banner | Muestra u oculta el banner en todas las posiciones |
| Rotación automática | Pasa solo de un anuncio a otro cuando hay varios en la misma posición |
| Intervalo de rotación | Milisegundos entre cambios (1000-30000); solo con la rotación automática activada |

Por defecto el banner está activado, con rotación automática cada 5000 ms.

### Rutas

| Método | Ruta | Descripción |
|--------|------|-------------|
| `GET` | `/anuncios` | Anuncios vigentes visibles para el usuario actual (JSON) |
| `GET` | `/anuncios/{id}` | Un anuncio (JSON); 404 si no está vigente o el usuario no puede verlo |

## Permisos

| Permiso | Descripción | Editor |
|---------|-------------|--------|
| `announcements.view_any` | Ver el listado de anuncios (y el menú) | Sí |
| `announcements.view` | Ver un anuncio | Sí |
| `announcements.create` | Crear anuncios | Sí |
| `announcements.update` | Editar anuncios | Sí |
| `announcements.delete` | Eliminar anuncios | No |

Los administradores tienen todos los permisos. Se asignan a otros roles desde el editor de roles del panel.

## Estructura

```
announcements/
├── config/
│   ├── module.php          # Configuración del módulo
│   └── settings.php        # Valores por defecto de los ajustes
├── database/
│   └── migrations/
├── lang/
│   ├── en/
│   └── es/
├── resources/
│   ├── css/                # Hoja de estilos propia del módulo
│   └── js/
│       ├── components/     # AnnouncementBanner, AnnouncementCard
│       └── types/
├── routes/
│   └── web.php             # /anuncios
├── src/
│   ├── Application/        # DTOs y servicios
│   ├── Domain/             # Entidades, enums, value objects, repositorios
│   ├── Filament/           # Recurso del panel
│   ├── Http/               # Controlador y requests
│   ├── Infrastructure/     # Persistencia Eloquent
│   └── Policies/
├── tests/
│   ├── Feature/
│   ├── Integration/
│   └── Unit/
└── module.json
```

## Tests

Desde `src/` (o dentro del contenedor `guildforge_app`):

```bash
vendor/bin/phpunit -c modules/announcements/phpunit.xml
vendor/bin/phpunit -c modules/announcements/phpunit.xml --testsuite=Unit
```

`php artisan test` no ejecuta los tests de los módulos.

## Publicar una versión

1. Sube `version` en `module.json`.
2. Crea y empuja la etiqueta `vX.Y.Z` (debe coincidir con `version`).
3. El workflow `release.yml` ejecuta los tests contra el core (`main`, o la variable `GUILDFORGE_HOST_REF`), compila los assets y publica `announcements-X.Y.Z.zip` con su `.sha256` en la release.

## Licencia

Propietario - GuildForge
