---
id: announcements.panel.create-announcement
title: Crear un anuncio
summary: Escribe un anuncio, elige quién lo ve, en qué parte de la página sale y durante cuánto tiempo.
category: content
audience: panel
order: 81
keywords: [crear anuncio, nuevo anuncio, publicar anuncio, poner un aviso, avisar, banner, banda, comunicado, visibilidad, posición, prioridad, color, fecha de inicio, fecha de fin, programar, solo socios, cerrar anuncio]
related: [announcements.panel.overview, announcements.panel.manage-announcements]
conditions:
  permissions:
    all: ['announcements:announcements.create']
---
Un anuncio nuevo sale en la web en cuanto lo creas, si está activo y no tiene fecha de inicio. Si quieres prepararlo antes, ponle una **Fecha de inicio** o desmarca **Activo**.

## Crear el anuncio

1. En el menú lateral, abre **Comunicación** y elige **Anuncios**.
2. Pulsa **Crear anuncio**.
3. En **Contenido**, escribe el **Título** y el **Contenido**. En la banda de la web solo sale el título y el principio del contenido, en una línea y sin formato: pon lo importante al principio.
4. En **Visualización**, revisa los interruptores:
   - **Activo**: marcado, el anuncio puede salir en la web.
   - **Permite cerrar**: marcado, cada persona puede cerrarlo con la ✕ y no lo vuelve a ver en ese navegador.
5. Elige la **Visibilidad**: **Público**, **Usuarios autenticados** o **Solo socios** (quien tiene el rol {{association_role}}).
6. Elige la **Posición**, la parte de la página donde sale. Por defecto, **Antes del header**, arriba del todo.
7. Pon la **Prioridad**, de 1 a 10. Por defecto, 5. Con más prioridad sale antes que los demás de su posición, y su color cambia: de 8 a 10 usa el color de error del tema, de 5 a 7 el de aviso y de 1 a 4 el de información.
8. Si quieres otros colores, abre **Estilo personalizado** y elige el **Color de fondo** y el **Color de texto**. Déjalos vacíos para usar los de la prioridad.
9. Si el anuncio tiene plazo, abre **Programación** y elige la **Fecha de inicio**, la **Fecha de fin** o las dos.
10. Pulsa **Crear**, o **Crear y crear otro** si vas a escribir varios.

Vuelves a la lista de anuncios. La gente lo ve en cuanto carga otra página.

> [!TIP]
> Si cambias los colores, elige los dos. Con uno solo, el otro deja de salir de la prioridad y el texto puede no leerse bien sobre el fondo.

## Si algo no sale bien

- **La fecha de fin no se acepta**: tiene que ser posterior a la **Fecha de inicio**.
- **El anuncio no sale en la web**: comprueba que está **Activo**, que estás dentro de sus fechas y que tu cuenta entra en su **Visibilidad**. Si lo cerraste tú antes con la ✕, en tu navegador ya no sale. Comprueba también que **Mostrar banner** está activado en los ajustes del módulo.
- **No lo ve alguien con «Solo socios»**: esa persona tiene que haber iniciado sesión y tener el rol {{association_role}}.

[[action:filament.admin.resources.announcements.create|Crear un anuncio]]
