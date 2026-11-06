---
id: announcements.panel.banner-settings
title: Ajustes del banner de anuncios
summary: Muestra u oculta todos los anuncios de golpe y decide si la banda pasa sola de uno a otro y cada cuánto.
category: content
audience: panel
order: 83
keywords: [ajustes, configuración, configurar anuncios, banner, banda, mostrar banner, ocultar anuncios, apagar anuncios, rotación automática, carrusel, pasar anuncios, intervalo, velocidad, milisegundos]
related: [announcements.panel.overview, core.panel.modules]
conditions:
  permissions:
    all: [modules.configure]
---
Estos ajustes valen para todos los anuncios a la vez. Lo de cada anuncio (texto, visibilidad, posición, fechas) se cambia en el propio anuncio.

## Abrir los ajustes

1. En el menú lateral, abre **Administración** y elige **Módulos**.
2. En la tarjeta de **Anuncios**, pulsa **Configuración**.
3. Cambia los ajustes de **Visualización**.
4. Pulsa **Guardar configuración**.

## Qué decide cada ajuste

- **Mostrar banner**: desactivado, no sale ningún anuncio en la web, en ninguna posición. Los anuncios no se borran ni se desactivan: vuelven a salir al activarlo.
- **Rotación automática**: activado, cuando hay varios anuncios en la misma posición la banda pasa sola de uno a otro. Desactivado, se quedan quietos y la gente pasa de uno a otro con las flechas o los puntos.
- **Intervalo de rotación**: cuánto tarda en pasar al siguiente, en milisegundos (1000 son 1 segundo), entre 1000 y 30000. Solo sale con la **Rotación automática** activada.

Por defecto, el banner sale y pasa de un anuncio a otro cada 5000 milisegundos (5 segundos).

> [!TIP]
> Si los anuncios tienen textos largos, sube el intervalo para que dé tiempo a leerlos.

[[action:filament.admin.pages.modules-page|Ver los módulos]]
