---
id: announcements.panel.manage-announcements
title: Programar, cambiar y retirar anuncios
summary: Encuentra un anuncio, cambia su texto o sus fechas, retíralo de la web sin borrarlo o bórralo del todo.
category: content
audience: panel
order: 82
keywords: [editar anuncio, cambiar anuncio, modificar aviso, programar, fecha de inicio, fecha de fin, caducar, retirar anuncio, quitar aviso, ocultar, desactivar, activar, borrar anuncio, eliminar anuncio, filtrar, inactivos]
related: [announcements.panel.overview, announcements.panel.create-announcement]
conditions:
  permissions:
    any: ['announcements:announcements.update', 'announcements:announcements.delete']
---
Un anuncio se puede cambiar en cualquier momento, y los cambios se ven en la web en cuanto la gente carga otra página.

## Encontrar un anuncio

En el menú lateral, abre **Comunicación** y elige **Anuncios**. La lista sale ordenada por **Prioridad**, de más a menos, y muestra si cada uno está **Activo**.

Puedes filtrarla por **Visibilidad**, por **Posición** y por **Estado** (**Activos** o **Inactivos**). Las columnas de **Posición**, **Color de fondo**, las fechas y **Creado** están ocultas: puedes mostrarlas desde el selector de columnas de la tabla.

## Cambiar un anuncio

1. Pulsa **Editar** en su fila.
2. Cambia lo que necesites. Las secciones **Estilo personalizado** y **Programación** están plegadas: ábrelas para ver sus campos.
3. Pulsa **Guardar cambios**.

> [!IMPORTANT]
> Quien ya cerró un anuncio con la ✕ no lo vuelve a ver en su navegador, aunque lo cambies o desmarques **Permite cerrar**. Si tienes algo nuevo que contar, crea otro anuncio.

## Programarlo

En **Programación**:

- **Fecha de inicio**: el anuncio no sale hasta ese momento.
- **Fecha de fin**: deja de salir después de ese momento. Tiene que ser posterior a la de inicio.

Las fechas van a la hora del sitio. Cuando pasa la fecha de fin, el anuncio sigue en la lista marcado como **Activo**, aunque ya no sale en la web.

## Retirarlo sin borrarlo

Abre el anuncio, desmarca **Activo** y pulsa **Guardar cambios**. Deja de salir en la web enseguida y lo encuentras con el filtro **Inactivos** para usarlo otra vez.

## Borrarlo

Pulsa **Borrar** en su fila, o dentro del anuncio, y confirma. Para borrar varios a la vez, marca sus casillas y usa **Borrar seleccionados** en las acciones de la tabla.

> [!WARNING]
> Borrar un anuncio no se puede deshacer. Si puede que lo necesites otra vez, desactívalo en lugar de borrarlo.

Solo ves **Borrar** si tienes el permiso **Eliminar anuncios**.

[[action:filament.admin.resources.announcements.index|Ver los anuncios]]
