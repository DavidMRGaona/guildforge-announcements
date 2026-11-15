# GuildForge - Módulo de anuncios

Anuncios para la web pública de GuildForge. Se crean en el panel de administración y se muestran como banner en la zona de la página elegida en cada anuncio, o como ventana emergente encima de la página, solo a quien puede verlos y solo mientras están vigentes.

## Características

- Gestión de anuncios desde el panel (grupo **Comunicación** → **Anuncios**): crear, editar, eliminar, filtrar por visibilidad, posición y estado
- Dos formatos: banner y ventana emergente (centrada o tarjeta en la esquina, con imagen y botón opcionales, disparadores, páginas y dispositivo)
- Cuatro niveles de visibilidad: público, usuarios autenticados, solo socios y solo visitas sin sesión
- Seis posiciones en el layout público para los banners (antes y después de la cabecera, el contenido y el pie)
- Prioridad de 1 a 10: ordena los anuncios y fija el color por defecto del banner
- Colores de fondo y de texto propios por anuncio
- Fechas de inicio y fin opcionales, además del interruptor de activo
- Anuncios que el visitante puede cerrar (o no), a elección de cada anuncio
- Rutas JSON de solo lectura (`/anuncios`)
- Imágenes del texto y de las ventanas en Cloudinary, con fuentes propias en la biblioteca de medios del panel
- Guías de ayuda para la web y el panel (base de conocimiento del core), con el botón de ayuda en las páginas del recurso
- Traducciones en español e inglés

## Requisitos

- GuildForge core `^3.75` (contrato de consentimiento con `whenConsentDecided()`, ajustes en el clúster «Configuración», avisos en la campana, imágenes del editor, biblioteca de medios, base de conocimiento, rol de socio configurable, fechas en UTC)
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
| Título y contenido | El banner muestra el título y una línea del contenido como texto plano; la ventana, el contenido entero |
| Formato | `banner` (por defecto) o `popup` (ventana emergente, ver abajo) |
| Activo | Solo los anuncios activos se muestran |
| Permite cerrar | Si el visitante puede cerrarlo. Solo banners: una ventana siempre se puede cerrar |
| Visibilidad | Quién lo ve (ver abajo) |
| Posición | En qué zona del layout aparece. Solo banners |
| Prioridad | De 1 (baja) a 10 (alta) |
| Color de fondo y de texto | Opcionales; sin ellos el color sale de la prioridad: 8-10 color de error del tema, 5-7 de aviso, 1-4 de información |
| Fecha de inicio y de fin | Opcionales; fuera de ese intervalo el anuncio no se muestra |

#### Visibilidad

| Valor | Quién lo ve |
|-------|-------------|
| `public` | Todos los visitantes |
| `authenticated` | Usuarios con sesión iniciada |
| `members` | Socios: usuarios con el rol de socio del core |
| `guests` | Solo visitas sin sesión: deja de verse al iniciar sesión |

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

Los banners visibles para el usuario actual se comparten con todas las páginas como prop de Inertia `announcements` (las ventanas van aparte, en `announcementPopups`). El componente `AnnouncementCard` (`:announcement`) está disponible para mostrar un anuncio en otras vistas.

### Ventanas emergentes

Un anuncio con formato `popup` se abre encima de la página. El servidor decide qué ventanas se ofrecen a cada persona (activas, vigentes y de su visibilidad) y las comparte con todas las páginas como prop de Inertia `announcementPopups`, por prioridad y, a igual prioridad, por inicio más reciente (`starts_at` o, sin él, `created_at`). El componente `AnnouncementPopup`, registrado una vez en el hueco `after-footer`, decide en el navegador los disparadores, las páginas y el dispositivo.

| Campo | Columna | Valores |
|-------|---------|---------|
| Forma | `popup_shape` | `centered` (modal) o `corner` (tarjeta abajo, a la derecha en escritorio) |
| Imagen | `popup_image_public_id` | Opcional. Cloudinary, en `announcements/popups/<año>/<mes>`, o elegida de la biblioteca de medios |
| Texto alternativo | `popup_image_alt` | Opcional (máx. 255). Vacío, la imagen es decorativa (`alt=""`) |
| Botón | `popup_action_label`, `popup_action_url` | Opcional, los dos juntos. Enlace `https://` o ruta de la web que empieza por `/` (nunca `//`, `http:` ni `javascript:`) |
| Al cargar, tras unos segundos | `popup_trigger_delay` | 0–60 |
| Al bajar por la página | `popup_trigger_scroll` | 10–100 %. Solo cuenta al bajar (ni una página que no se puede bajar ni subir desde una posición restaurada); 1 px del final cuenta como el final |
| Al ir a salir | `popup_trigger_exit_intent` | Ratón que sale por arriba, solo en escritorio con ratón. Sin él se usa el retardo, o 10 s si no hay |
| Tras ver varias páginas | `popup_trigger_page_views` | 2–20 páginas de la misma visita (`sessionStorage`) |
| Páginas | `popup_pages`, `popup_paths` | `all`, `home` o `paths` (hasta 30 rutas; `*` final para «y lo que hay debajo») |
| Dispositivo | `popup_device` | `all`, `mobile` (menos de 768 px de ancho) o `desktop` |
| Frecuencia | `popup_frequency`, `popup_frequency_days` | `once` (por defecto, también en las ventanas que ya existían), `session` o `days` con de 1 a 365 días |

Los disparadores se combinan: abre el primero que se cumpla. Sin ninguno, la ventana se abre al cargar.

Reglas fijas:

- Como mucho una ventana por página: la primera de la lista que encaje en la página y la pantalla. Cerrar una no abre otra en esa página, tampoco tras otra visita de Inertia a la misma ruta (paginación, filtros, un formulario que vuelve): solo cuenta un cambio de ruta o del conjunto de ventanas ofrecidas.
- Nunca antes de que se responda al banner de cookies (`whenConsentDecided()` del contrato `@/contracts/cookieConsent`, cancelado con un `AbortSignal` al cambiar de página o desmontar). Si `hasCookieConsentDecided()` vuelve a ser falso mientras un disparador espera o con la ventana abierta, la ventana se retira y vuelve a esperar la respuesta, con los disparadores desde cero. Hace falta una versión de cookie-consent que implemente `hasDecided()` (cookie-consent#98, posterior a la 2.14.0): con la 2.14.0 o anteriores el contrato da al visitante por decidido y la ventana puede salir con el banner abierto.
- Nunca en `/admin*`, las páginas de acceso (`Auth/*`), las legales (`Legal/*`), mantenimiento, `/newsletter` y lo que cuelga de ella, las de error ni las de los enlaces de los correos (`Mail/*`).
- Siempre se puede cerrar, aunque el anuncio tenga «Permite cerrar» desmarcado (la respuesta dice `is_dismissible: true`): ✕, Esc y, en la centrada, pulsar fuera. La centrada es `role="dialog"` con `aria-modal="true"`, atrapa el foco, bloquea el desplazamiento de la página y devuelve el foco al cerrarse. La tarjeta es un `role="dialog"` sin `aria-modal`: no roba el foco ni lo atrapa, y Esc la cierra solo con el foco dentro de ella. La animación de entrada solo se ve sin `prefers-reduced-motion`.
- Frecuencia (announcements#68): abrir la ventana y cerrarla (✕, Esc, fondo o botón) cuentan como haberla visto. `once` no vuelve nunca; `session` vuelve en otra sesión de la pestaña del navegador (`sessionStorage`, por pestaña, sin cookies); `days` vuelve cuando pasan N días (de 24 h, sobre el instante, sin mirar el horario de verano) desde la última vez que se **mostró**, y cerrar no los mueve. En cualquier caso, una ventana sale como mucho una vez por visita.
- Memoria del navegador (`popupMemory.ts`): `localStorage` `guildforge_popup_views` guarda por ventana `{ shown, dismissed }` en milisegundos (las de más de 400 días se borran), y `sessionStorage` `guildforge_shown_popups` las abiertas en la visita. Todo va en `try/catch`: lo que el almacenamiento no puede guardar se queda en la memoria de la página (una vez por carga). Una marca de tiempo del futuro (reloj atrasado) no se tiene en cuenta. Las marcas de `guildforge_dismissed_popups` de versiones anteriores a esta no se leen.
- Memoria de la cuenta: tabla `announcements_popup_views` (`user_id`, `announcement_id`, `last_shown_at`, `dismissed_at`; única por cuenta y anuncio; se borra con el anuncio, con la cuenta y, al anonimizar, con el evento `UserAnonymized`). Al compartir `announcementPopups` con una cuenta con sesión, el servidor marca con `seen: true` las ventanas que su memoria dice que no tocan (`PopupView::isDue()`) y no las quita, para que los ids no cambien al abrirse una ventana y no se cierre la que está abierta; el navegador no abre las marcadas. `session` lo decide el navegador, que es quien conoce sus pestañas. Una sola consulta por petición y solo si hay ventanas. Las visitas sin sesión se quedan solo con el navegador.
- El navegador avisa al servidor al mostrar y al cerrar con `POST /anuncios/ventanas/{id}/mostrada` y `/cerrada` (`announcements.popups.shown|dismissed`; `auth`, `email.verified-if-required`, `throttle:60,1`, CSRF; solo viaja el id). Responde `204`, o `404` si la ventana no existe o la cuenta no la puede ver. Con una impersonación activa (`ImpersonationContextInterface::isActive()`) responde `204` sin escribir nada, y el navegador ni llama ni toca su almacenamiento (la memoria pasa a ser solo la de la página).
- El layout de Inertia es persistente: cada ruta nueva es otra página. Los disparadores pendientes se cancelan, y una ventana abierta sigue solo si encaja en la página nueva. Si cambia la lista (al iniciar o cerrar sesión), una ventana que ya no se ofrece se cierra.
- Una ventana cuyos ajustes guardados no se pueden leer (una fila editada a mano) no se muestra nunca y deja un aviso en el log. Lo mismo cualquier anuncio con un formato, visibilidad, posición o prioridad desconocidos: se deja fuera en vez de romper todas las páginas.
- Banners y ventanas se leen con una sola consulta por petición y se reparten entre las dos props.
- Al cambiar el formato a `banner` se conservan los ajustes de la ventana. El ajuste «Mostrar banner» no afecta a las ventanas.

### Métricas y vista previa de las ventanas

Contadores anónimos (announcements#69): tabla `announcements_popup_metrics` (`id`, `announcement_id`, `day`, `metric`, `count`; única por anuncio, día y métrica; se borra con el anuncio). Sin cuenta, sesión, dirección ni agente de usuario: solo el número de veces. `metric` es `shown` (al abrirse), `dismissed` (✕, Esc o fondo) o `action_clicked` (el botón; no cuenta además como `dismissed`). El día es el de la zona de visualización (`TimezoneServiceInterface::today()`); el incremento es un único `upsert` atómico.

- Ruta pública `POST /anuncios/ventanas/{id}/metrica/{metric}` (`announcements.popups.metric`; también para visitas sin sesión, grupo `web` con CSRF, `throttle:120,1`). Solo acepta el id de una ventana (formato `popup`) activa, vigente y visible para quien llama, y una de las tres métricas; lo demás da `404` y no cuenta. Responde `204`; durante una impersonación responde `204` sin contar, y el navegador tampoco llama.
- `announcements:prune-metrics [--days=]` (diario en el planificador, `announcements.metrics_retention_days`, 400 por defecto) borra los días anteriores al límite.
- El resumen de la ficha (`PopupMetricsWidget`, arriba en la página de edición de una ventana) suma los últimos 30 días de visualización, hoy incluido: mostrada, cerrada, clics en el botón y tasa de clic (clics por cada 100 mostradas; un guion hasta la primera vez). Solo con el formato `popup` y para quien puede editar el anuncio.
- «Vista previa» (`PopupPreviewAction`, cabecera de crear y editar, solo con el formato `popup`): un modal del panel con la forma, la imagen, el título, el contenido (saneado con el mismo saneador que el guardado) y el botón (solo si el enlace es válido) del formulario tal cual está, guardado o no. Es un dibujo hecho por el panel, no el componente Vue del sitio: no ejecuta disparadores ni frecuencia, no cuenta ni guarda memoria.

### Avisos en la campana

Cuando un anuncio empieza a salir en la web por primera vez, cada cuenta que puede verlo recibe un aviso en la campana del core: «Nuevo anuncio: <título>», con el texto del anuncio y un enlace a la portada, donde sale la banda. Nunca se manda por correo.

- **Qué anuncios**: solo los banners para cuentas. Ni las ventanas emergentes ni los anuncios `guests` avisan.
- **Cuándo**: al crearlo activo, al activarlo o al cambiar su fecha de inicio. Si tiene una fecha de inicio futura, el aviso se encola hasta esa fecha. Se avisa una sola vez por anuncio (tabla `announcements_notices`): cambiar el texto, desactivarlo y volver a activarlo o moverlo de fecha no avisa otra vez.
- **A quién**: a todas las cuentas activas salvo a quien lo guarda. Al entregarlo se comprueba con la misma regla que la banda: si el anuncio ya no está activo, ha caducado o la cuenta no entra en su visibilidad (por ejemplo, «Solo socios»), no se guarda nada.
- **Preferencia**: categoría `announcements.new-announcements` («Anuncios nuevos»), opcional, solo para la web y activada por defecto. Cada persona la apaga en sus preferencias de avisos.
- **Agrupado**: mientras no se lean, los avisos de anuncios de una persona se juntan en una fila («3 anuncios nuevos»).
- **Al borrar** un anuncio se borran sus avisos.
- El envío va en lotes de 100 cuentas por la cola y directo al canal `site` del core, sin pasar por la fachada `Notification`, así que un `Notification::fake()` en los tests de otros módulos no lo ve.

### Ajustes

En **Sistema → Configuración → Anuncios** (permiso `announcements:settings.manage`, que un administrador da a mano en el editor de roles; el botón **Configuración** de **Módulos** lleva a la misma página a quien lo tiene y además puede entrar en **Módulos**). Los valores se guardan en la fila `module_settings:announcements`:

| Ajuste | Descripción |
|--------|-------------|
| Mostrar banner | Muestra u oculta el banner en todas las posiciones (no afecta a las ventanas emergentes) |
| Rotación automática | Pasa solo de un anuncio a otro cuando hay varios en la misma posición |
| Intervalo de rotación | Milisegundos entre cambios (1000-30000); solo con la rotación automática activada |

Por defecto el banner está activado, con rotación automática cada 5000 ms.

### Rutas

| Método | Ruta | Descripción |
|--------|------|-------------|
| `GET` | `/anuncios` | Banners vigentes visibles para el usuario actual (JSON); las ventanas emergentes no se listan |
| `GET` | `/anuncios/{id}` | Un banner (JSON); 404 si no está vigente, el usuario no puede verlo o es una ventana emergente |
| `POST` | `/anuncios/ventanas/{id}/mostrada`, `/cerrada` | Memoria de la cuenta (con sesión) |
| `POST` | `/anuncios/ventanas/{id}/metrica/{metric}` | Contador anónimo: `shown`, `dismissed` o `action_clicked` |

### Imagen de la ventana

La imagen de una ventana se sube a Cloudinary, a `announcements/popups/<año>/<mes>`, con un nombre UUID (hasta 2 MB), o se elige de la biblioteca de medios. La fuente `announcements.popup-image` reclama `announcements/popups/` y la biblioteca la muestra con el origen «Anuncios» y el rol «Imagen de la ventana». Al cambiarla o borrar el anuncio, la anterior se libera a través del core, que solo la borra si nadie más la usa.

### Imágenes en el texto

Las imágenes que se pegan o arrastran al texto de un anuncio se suben a Cloudinary, a `announcements/content/<año>/<mes>`, con un nombre UUID. Solo se aceptan JPEG, PNG, WebP y GIF de hasta 2 MB, como en los editores del core. El HTML guarda la URL completa de entrega.

La biblioteca de medios del panel (**Contenido** → **Biblioteca de medios**) muestra esas imágenes con el origen «Anuncios», el rol «En el texto», el título del aviso y el enlace para editarlo, si quien mira puede hacerlo. La fuente (`announcements.content`) reclama la carpeta `announcements/content/` y cuenta como uso cualquier URL de entrega del sitio que aparezca en el texto, esté en la carpeta que esté. Crear un aviso, cambiar su título o su texto y borrarlo actualiza los usos al momento. Los avisos borrados no se pueden recuperar, así que dejan de contar.

Las imágenes que se pegaron antes de la versión 2.7.0 están en el almacenamiento local. Las mueve a Cloudinary el comando del core, que lee los textos de los avisos como fuente `announcements.content`:

```bash
php artisan content:move-editor-images-to-cloudinary --source=announcements.content --dry-run
php artisan content:move-editor-images-to-cloudinary --source=announcements.content
```

Sin `--source` mueve las imágenes de todos los textos del sitio, también los de los avisos. La copia local solo se borra cuando ningún texto la nombra, sea del módulo que sea. El comando escribe el texto directamente en la tabla, así que la biblioteca de medios ve las URL nuevas en su siguiente sincronización diaria (`media:sync`). Las opciones y el informe se explican en la guía del core `docs/modules/editor-images.md`.

## Permisos

| Permiso | Descripción | Editor |
|---------|-------------|--------|
| `announcements.view_any` | Ver el listado de anuncios (y el menú) | Sí |
| `announcements.create` | Crear anuncios | Sí |
| `announcements.update` | Editar anuncios | Sí |
| `announcements.delete` | Eliminar anuncios | No |
| `settings.manage` | Cambiar los ajustes del banner | No |

Los administradores tienen todos los permisos. Se asignan a otros roles desde el editor de roles del panel.

`announcements.view` («Ver detalle de anuncio») se retiró porque no lo pedía ninguna página: ver un anuncio exige `announcements.view_any`. El core nunca borra permisos, así que en los sitios que lo concedieron la fila y la concesión siguen en la base de datos, pero ya no hacen nada, no salen en el editor de roles y guardar un rol las conserva.

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
│       ├── components/     # AnnouncementBanner, AnnouncementCard, AnnouncementPopup
│       ├── popup/          # PopupDialog (la ventana en sí)
│       ├── types/
│       └── utils/          # Páginas, disparadores y memoria de las ventanas
├── routes/
│   └── web.php             # /anuncios
├── src/
│   ├── Application/        # DTOs y servicios
│   ├── Domain/             # Entidades, enums, value objects, repositorios
│   ├── Filament/           # Recurso del panel, página de ajustes y fuentes de medios
│   ├── Http/               # Controlador y requests
│   ├── Infrastructure/     # Persistencia Eloquent, textos del editor y envío de avisos de la campana
│   ├── Notifications/      # Aviso de anuncio nuevo y su categoría
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
