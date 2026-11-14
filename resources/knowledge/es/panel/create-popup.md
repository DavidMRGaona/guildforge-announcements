---
id: announcements.panel.create-popup
title: Crear una ventana emergente
summary: Convierte un anuncio en una ventana que se abre encima de la página, y elige cuándo, dónde y para quién sale.
category: content
audience: panel
order: 84
keywords: [ventana emergente, ventana, emergente, popup, pop-up, modal, ventana modal, tarjeta, aviso destacado, captación de socios, hazte socio, botón, llamada a la acción, imagen, disparador, retardo, segundos, desplazamiento, bajar, al ir a salir, intención de salida, páginas vistas, portada, rutas, móvil, escritorio, dispositivo, solo visitas sin sesión, banner de cookies]
related: [announcements.panel.create-announcement, announcements.panel.overview, announcements.panel.manage-announcements]
conditions:
  permissions:
    all: ['announcements:announcements.create']
---
Una ventana emergente es un anuncio que se abre encima de la página en lugar de salir como una banda. Sirve para lo que no puede pasar desapercibido: una inscripción que cierra, una campaña de socios o una invitación a apuntarse. Usa las mismas piezas que cualquier anuncio: título, contenido, visibilidad, prioridad y fechas.

## Crear la ventana

1. En el menú lateral, abre **Comunicación** y elige **Anuncios**.
2. Pulsa **Crear anuncio** y escribe el **Título** y el **Contenido**. En la ventana el contenido sale entero, con su formato: negritas, listas y enlaces.
3. En **Visualización**, elige el **Formato** **Ventana emergente**. Desaparecen la **Posición**, **Permite cerrar** y el **Estilo personalizado**, que son solo de las bandas, y salen las secciones de la ventana.
4. Elige la **Visibilidad** y la **Prioridad**, como en cualquier anuncio.
5. Rellena **Ventana emergente**, **Cuándo se abre** y **Dónde se abre**, como se explica abajo.
6. Pulsa **Crear**.

## Cómo es la ventana

- **Forma**: **Centrada** tapa la página con un fondo oscuro hasta que se cierra. **Tarjeta en la esquina inferior** sale abajo, a la derecha en escritorio y a lo ancho en el móvil, y deja seguir leyendo.
- **Imagen**: opcional. Sale encima del título. Puedes subir una o pulsar **Elegir de la biblioteca** para usar una que ya esté en la web.
- **Texto del botón** y **Enlace del botón**: opcionales, pero van juntos. El enlace puede ser una dirección que empiece por `https://` o una página de la web que empiece por `/`, como `/eventos`. No se aceptan direcciones `http://` sin cifrar ni nada que no sea un enlace.

La ventana siempre se puede cerrar, con su ✕, con la tecla Esc o, en la centrada, pulsando fuera de ella. Quien la cierra o pulsa su botón no la vuelve a ver en ese navegador.

## Cuándo se abre

Puedes combinar varios disparadores. La ventana se abre con el primero que se cumpla:

- **Al cargar la página, tras unos segundos**: de 0 a 60. Con 0 se abre nada más cargar.
- **Al bajar por la página (%)**: de 10 a 100. Solo cuenta al bajar: en una página demasiado corta para bajar, este disparador no la abre.
- **Al ir a salir**: cuando el ratón sale por arriba de la página, hacia las pestañas o la barra de direcciones. Solo funciona en escritorio. En el móvil se usa el retardo de arriba, o 10 segundos si no has puesto ninguno.
- **Tras ver varias páginas**: de 2 a 20 páginas en la misma visita. Se abre al llegar a esa página.

Si no rellenas ninguno, se abre nada más cargar la página.

> [!TIP]
> Un retardo de 0 gana siempre a los demás disparadores. Si quieres que la ventana espere a que alguien baje por la página o vaya a salir, deja el retardo vacío.

## Dónde se abre

- **Páginas**: **Todo el sitio**, **Solo la portada** o **Solo estas rutas**. Con rutas, escribe cada una y pulsa Intro: `/eventos` es solo esa página y `/eventos*` es esa página y todas las que empiezan igual, como cada evento.
- **Dispositivo**: **Todos**, **Solo móviles** (pantallas de menos de 768 px de ancho) o **Solo escritorio**.

Nunca se abre en el panel, en las páginas de entrar y registrarse, en las páginas legales, durante el mantenimiento, en `/newsletter` ni en las páginas de error, aunque las rutas digan lo contrario.

## Para quién: la visibilidad

Además de **Público**, **Usuarios autenticados** y **Solo socios** (quien tiene el rol {{association_role}}), puedes elegir **Solo visitas sin sesión**: la ventana sale solo a quien no ha iniciado sesión. Viene bien para invitar a registrarse o a hacerse socio sin molestar a quien ya lo es.

## Reglas que no cambian

- **Una ventana por página como mucho.** Si a la vez pueden salir varias, sale la de más **Prioridad**. Con la misma prioridad, la que empezó más tarde (su **Fecha de inicio** o, sin ella, cuándo se creó). Cada ventana sale como mucho una vez por visita.
- **Nunca encima del banner de cookies.** La ventana espera a que la persona responda al banner. Si vuelve a abrir el banner, la ventana se cierra y espera a la nueva respuesta.
- **No avisa en la campana.** Las ventanas no mandan el aviso «Nuevo anuncio».

> [!WARNING]
> Para que la ventana espere al banner de cookies, el módulo **Banner de cookies** tiene que estar en una versión posterior a la 2.14.0. Con la 2.14.0 o anteriores, la ventana puede salir mientras el banner está abierto.

## Si algo no sale bien

- **La ventana no sale**: comprueba que está **Activo**, que estás dentro de sus fechas, que tu cuenta entra en su **Visibilidad** y que la página y el dispositivo encajan. Si la cerraste antes, en tu navegador ya no sale; si ya salió en esta visita, no vuelve hasta la próxima. Prueba en una ventana privada del navegador.
- **Sale otra ventana en lugar de la mía**: otra con más prioridad, o con la misma y más reciente, encaja en esa página.
- **El enlace del botón no se acepta**: tiene que empezar por `https://` o por `/`.
- **Una ruta no se acepta**: empieza por `/`, sin el nombre de la web delante ni `?`, y solo puede llevar un `*` al final.

[[action:filament.admin.resources.announcements.create|Crear un anuncio]]
