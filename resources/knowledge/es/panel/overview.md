---
id: announcements.panel.overview
title: 'Anuncios: cómo funcionan'
summary: Qué es un anuncio, dónde sale en la web, quién lo ve, cómo deciden su orden y su color, y quién puede hacer cada cosa.
category: content
audience: panel
order: 80
featured: true
keywords: [anuncios, anuncio, aviso, avisos, banner, banda, franja, cartel, comunicado, comunicación, cómo funciona, introducción, conceptos, visibilidad, posición, prioridad, colores, vigencia, socios, permisos, anuncio importante]
related: [announcements.panel.create-announcement, announcements.panel.manage-announcements, announcements.panel.banner-settings, core.panel.association-role]
conditions:
  permissions:
    all: ['announcements:announcements.view_any']
---
Los anuncios son avisos cortos que salen como una banda en la web de {{site_name}}: un cierre por vacaciones, un cambio de local o el plazo de una cuota. Se crean en el panel y se muestran solos, sin tocar ninguna página. Esta guía explica las piezas. Las demás guías de anuncios entran en cada tarea.

## Qué se ve en la web

Cada anuncio sale como una banda a lo ancho de la página, con su **Título** en negrita y, debajo, el principio del **Contenido** en una sola línea. En la banda el contenido sale como texto sencillo: sin negritas, listas ni enlaces. Pon lo importante en el título y en la primera frase.

La banda sale en todas las páginas de la web, salvo en las de entrar, registrarse, recuperar la contraseña y verificar el email, y en la página de mantenimiento.

Si hay varios anuncios en la misma posición, la banda los va pasando, el de más prioridad primero. Salen flechas a los lados y unos puntos debajo para ir de uno a otro. Mientras alguien deja el ratón encima, la banda se para.

## Quién lo ve: la visibilidad

| **Visibilidad** | Quién lo ve |
|---|---|
| **Público** | Cualquiera que visite la web, con cuenta o sin ella. |
| **Usuarios autenticados** | Quien ha iniciado sesión. |
| **Solo socios** | Quien ha iniciado sesión y tiene el rol {{association_role}}. |

**Solo socios** se refiere a los miembros de la asociación, no a cualquier cuenta registrada. El rol que cuenta es el elegido en **Configuración del sitio → Autenticación**. No hay excepción para la administración: sin ese rol, tampoco ve estos anuncios.

## Dónde sale: la posición

La **Posición** elige en qué parte de la página aparece la banda:

| **Posición** | Dónde |
|---|---|
| **Antes del header** | Arriba del todo, encima de la cabecera con el menú. |
| **Después del header** | Justo debajo de la cabecera. |
| **Antes del contenido** | Al principio de la parte central de la página. |
| **Después del contenido** | Al final de la parte central. |
| **Antes del footer** | Justo encima del pie de página. |
| **Después del footer** | Abajo del todo, debajo del pie. |

Cada posición tiene su propia banda: dos anuncios en posiciones distintas salen a la vez, cada uno en su sitio.

## Orden y color: la prioridad

La **Prioridad** va de 1 (baja) a 10 (alta). Decide dos cosas:

- **El orden**: en cada posición sale primero el de más prioridad.
- **El color**, si no eliges uno propio: de 8 a 10, el color de error del tema; de 5 a 7, el de aviso; de 1 a 4, el de información.

En **Estilo personalizado** puedes dar al anuncio su **Color de fondo** y su **Color de texto**, que mandan sobre el color de la prioridad.

## Cuándo sale: la vigencia

Un anuncio sale en la web cuando se cumplen las dos cosas:

- Tiene marcado **Activo**.
- Estamos entre su **Fecha de inicio** y su **Fecha de fin**. Las dos son opcionales: sin fecha de inicio sale desde ya, y sin fecha de fin no se retira solo.

Un anuncio cuya fecha de fin ya ha pasado sigue marcado como **Activo** en la lista, pero ya no sale.

## Cerrar un anuncio

Con **Permite cerrar**, la banda tiene una ✕ para cerrarlo. Lo que se cierra se recuerda en el navegador de esa persona: no lo vuelve a ver ahí, aunque cambies el anuncio, pero sí en otro navegador o en otro dispositivo. Sin **Permite cerrar**, el anuncio no se puede quitar de la vista y sale hasta que deja de estar vigente.

## Quién hace qué

| Permiso | Qué permite |
|---|---|
| **Ver listado de anuncios** | Ver **Anuncios** en el menú y la lista |
| **Crear anuncios** | Crear anuncios nuevos |
| **Editar anuncios** | Cambiar los anuncios, también activarlos y desactivarlos |
| **Eliminar anuncios** | Borrar anuncios |
| **Configurar módulos** | Cambiar los ajustes del banner, como los de los demás módulos |

Ninguno de estos permisos se concede solo: se dan a cada rol en **Roles**.

## Dónde está cada cosa

- En el menú lateral, el grupo **Comunicación** tiene **Anuncios**.
- Los ajustes que valen para todos los anuncios (mostrar el banner y la rotación) están en **Módulos**, en la tarjeta de **Anuncios**.

[[action:filament.admin.resources.announcements.index|Ver los anuncios]]
