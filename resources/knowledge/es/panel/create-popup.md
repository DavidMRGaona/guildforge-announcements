---
id: announcements.panel.create-popup
title: Crear una ventana emergente
summary: Convierte un anuncio en una ventana que se abre encima de la página, y elige cuándo, dónde y para quién sale.
category: content
audience: panel
order: 84
keywords: [bloque, bloque dentro de la ventana, suscripción al boletín, boletín, ocultar la ventana si el bloque no está disponible, bloque no disponible, frecuencia, una sola vez, una vez por sesión, cada cierto número de días, cada cuánto sale, volver a salir, ventana emergente, ventana, emergente, popup, pop-up, modal, ventana modal, tarjeta, aviso destacado, captación de socios, hazte socio, botón, llamada a la acción, imagen, disparador, retardo, segundos, desplazamiento, bajar, al ir a salir, intención de salida, páginas vistas, portada, rutas, móvil, escritorio, dispositivo, solo visitas sin sesión, banner de cookies, vista previa, previsualizar, métricas, estadísticas, resultados, tasa de clic, clics, cuántas veces se ha visto]
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
5. Rellena **Ventana emergente**, **Bloque** (opcional), **Cuándo se abre** (que incluye la **Frecuencia**) y **Dónde se abre**, como se explica abajo.
6. Pulsa **Crear**.

## Cómo es la ventana

- **Forma**: **Centrada** tapa la página con un fondo oscuro hasta que se cierra. **Tarjeta en la esquina inferior** sale abajo, a la derecha en escritorio y a lo ancho en el móvil, y deja seguir leyendo.
- **Imagen**: opcional. Sale encima del título. Puedes subir una o pulsar **Elegir de la biblioteca** para usar una que ya esté en la web.
- **Texto alternativo de la imagen**: qué muestra la imagen, para quien usa un lector de pantalla o no la ve cargar. Déjalo vacío si la imagen es solo decoración.
- **Texto del botón** y **Enlace del botón**: opcionales, pero van juntos. El enlace puede ser una dirección que empiece por `https://` o una página de la web que empiece por `/`, como `/eventos`. No se aceptan direcciones `http://` sin cifrar ni nada que no sea un enlace.

La ventana siempre se puede cerrar, con su ✕, con la tecla Esc o, en la centrada, pulsando fuera de ella. La tarjeta se cierra con Esc cuando se está usando, para no cerrarse al pulsar Esc en otra cosa de la página. Cerrarla o pulsar su botón cuenta como haberla visto: cuándo vuelve a salir lo decide la **Frecuencia**.

## Un bloque dentro de la ventana

El **Bloque** es opcional: una pieza que aporta la propia web u otro módulo y que se dibuja dentro de la ventana, entre el contenido y el botón. Por ejemplo, el formulario de suscripción al boletín si ese módulo está instalado. Esta sección no sabe qué módulos hay: lista lo que la web ofrece para las ventanas.

1. En **Bloque**, elige uno de la lista. **Sin bloque** deja la ventana como siempre.
2. Debajo salen los ajustes de ese bloque (un título, un texto, un enlace…). Rellénalos: los comprueba el propio bloque.
3. Decide con **Ocultar la ventana si el bloque no está disponible**, marcada por defecto:
   - **Marcada**: mientras el bloque no se pueda mostrar (su módulo está desactivado, su proveedor no responde o no tiene nada que mostrar a esa persona), la ventana no sale a quien no pueda verlo. Es lo que conviene si sin el bloque la ventana no tiene sentido.
   - **Sin marcar**: la ventana sale igualmente, sin el bloque. También sale sin él si el bloque no llega a pintarse en el navegador.

En la lista solo salen los bloques que se pueden poner en una ventana y están disponibles ahora. Si una ventana guardada tiene un bloque que ahora no está disponible, sigue en la lista y se conserva con sus ajustes al guardar, para no perderlo por una caída pasajera.

Lo que haga el bloque (apuntar a alguien a una lista, por ejemplo) es cosa suya: los **Resultados** de la ventana cuentan lo mismo de siempre y no cuentan el envío del bloque. El bloque se decide para cada persona: dos visitas pueden ver la misma ventana con el bloque distinto, o una con él y otra sin él.

## Cuándo se abre

Puedes combinar varios disparadores. La ventana se abre con el primero que se cumpla:

- **Al cargar la página, tras unos segundos**: de 0 a 60. Con 0 se abre nada más cargar.
- **Al bajar por la página (%)**: de 10 a 100. Solo cuenta al bajar: en una página demasiado corta para bajar, este disparador no la abre.
- **Al ir a salir**: cuando el ratón sale por arriba de la página, hacia las pestañas o la barra de direcciones. Solo funciona en escritorio. En el móvil se usa el retardo de arriba, o 10 segundos si no has puesto ninguno.
- **Tras ver varias páginas**: de 2 a 20 páginas en la misma visita. Se abre al llegar a esa página.

Si no rellenas ninguno, se abre nada más cargar la página.

> [!TIP]
> Un retardo de 0 gana siempre a los demás disparadores. Si quieres que la ventana espere a que alguien baje por la página o vaya a salir, deja el retardo vacío.

## Cada cuánto vuelve: la frecuencia

La **Frecuencia** decide cada cuánto vuelve a salir la ventana a la misma persona. Abrirla y cerrarla cuentan como haberla visto.

- **Una sola vez**: no vuelve a salir una vez vista. Es lo que tienen las ventanas nuevas.
- **Una vez por sesión**: la sesión es la de la pestaña del navegador, no la de la persona. Vuelve a salir al abrir una pestaña o ventana nueva, o tras cerrar la pestaña y abrirla de nuevo, aunque sea el mismo navegador. No usa cookies.
- **Cada cierto número de días**: vuelve a salir pasados de 1 a 365 días desde la última vez que se mostró. Cerrarla no retrasa la vuelta.

Cómo se recuerda:

- **Visitas sin sesión**: el recuerdo está en el navegador. Si borran los datos del navegador, o entran desde otro, la ventana vuelve a salir.
- **Quien ha iniciado sesión**: el recuerdo está también en su cuenta, así que no vuelve a ver la ventana en otro dispositivo o navegador. Con la frecuencia **Una vez por sesión**, la cuenta no decide nada: cada pestaña nueva la vuelve a mostrar.
- Aunque la frecuencia deje volver a una ventana, nunca sale más de una vez por visita.
- Al editar una ventana no se borra lo que ya se ha visto: si cambias la frecuencia, se aplica sobre cuándo la vio cada persona por última vez. Para que vuelva a salir a todo el mundo, crea una ventana nueva.
- Si el navegador no deja guardar datos, la ventana sale una vez por cada carga de página.
- Cuando un administrador mira la web como otra persona (**Impersonar**), no se guarda nada: ni en la cuenta de esa persona ni en el navegador del administrador. La ventana sale en cada carga de página.

## Dónde se abre

- **Páginas**: **Todo el sitio**, **Solo la portada** o **Solo estas rutas**. Con rutas, escribe cada una y pulsa Intro: `/eventos` es solo esa página y `/eventos*` es esa página y todas las que empiezan igual, como cada evento.
- **Dispositivo**: **Todos**, **Solo móviles** (pantallas de menos de 768 px de ancho) o **Solo escritorio**.

Nunca se abre en el panel, en las páginas de entrar y registrarse, en las páginas legales, durante el mantenimiento, en `/newsletter` ni en las páginas de error, aunque las rutas digan lo contrario.

## Para quién: la visibilidad

Además de **Público**, **Usuarios autenticados** y **Solo socios** (quien tiene el rol {{association_role}}), puedes elegir **Solo visitas sin sesión**: la ventana sale solo a quien no ha iniciado sesión. Viene bien para invitar a registrarse o a hacerse socio sin molestar a quien ya lo es.

La imagen de una ventana **Solo socios** sale oculta en la biblioteca de medios y no se ofrece en **Elegir de la biblioteca**, como las imágenes de su contenido.

## Ver cómo queda: la vista previa

Pulsa **Vista previa** arriba, junto al título de la página, para ver la ventana con lo que hay ahora en el formulario, sin guardar. Enseña la forma, la imagen, el título, el contenido y el botón (que no es un enlace: debajo dice a dónde iría). Del bloque solo pone un marcador, «Aquí irá el bloque…», con su nombre: no lo ejecuta, así que no ves lo que dibujaría ni se envía nada. Es un dibujo de la ventana dentro del panel: no aplica los disparadores, las páginas ni la frecuencia, y no cuenta como una vista ni deja ningún recuerdo. El botón «Vista previa» solo sale con el formato **Ventana emergente**.

## Ver si funciona: los resultados

Al editar una ventana ya guardada, arriba salen sus **Resultados de los últimos 30 días**:

- **Mostrada**: cuántas veces se abrió.
- **Cerrada**: cuántas veces se cerró con el ✕, la tecla Esc o pulsando fuera.
- **Clics en el botón**: cuántas veces se pulsó el botón. Pulsarlo no cuenta como cerrarla.
- **Tasa de clic**: los clics por cada 100 veces que se mostró. Nunca pasa del 100 %. Hasta que se muestra por primera vez, sale un guion.

Los días son los de la web, no los del servidor. Son contadores anónimos: guardan cuántas veces ocurrió cada cosa cada día, nunca quién la vio, ni su cuenta, sesión o dirección. Los cuentan también las visitas sin sesión, pero no la vista previa ni quien mira la web como otra persona (**Impersonar**). Los contadores de más de 400 días se borran solos, y los de una ventana se borran con ella. Solo los ve quien puede editar anuncios.

> [!IMPORTANT]
> Una ventana que se retira porque el banner de cookies vuelve a preguntar y se abre de nuevo en la misma página cuenta como mostrada una sola vez. Los números son orientativos: pueden quedarse algo por debajo de la realidad (bloqueadores, sin conexión) o ser inflados a propósito, así que sirven para comparar ventanas, no como cifra exacta.

## Reglas que no cambian

- **Una ventana por página como mucho.** Si a la vez pueden salir varias, sale la de más **Prioridad**. Con la misma prioridad, la que empezó más tarde (su **Fecha de inicio** o, sin ella, cuándo se creó). Cada ventana sale como mucho una vez por visita.
- **Nunca encima del banner de cookies.** La ventana espera a que la persona responda al banner. Si lo vuelve a abrir, la ventana se retira (o no llega a salir) y espera a la nueva respuesta para volver a contar sus disparadores.
- **No avisa en la campana.** Las ventanas no mandan el aviso «Nuevo anuncio».
- **No sale en la lista de anuncios.** Las ventanas se abren en las páginas, no como tarjetas de anuncio.

> [!WARNING]
> Para que la ventana espere al banner de cookies, el módulo **Banner de cookies** tiene que estar en una versión posterior a la 2.14.0. Con la 2.14.0 o anteriores, la ventana puede salir mientras el banner está abierto.

## Si algo no sale bien

- **La ventana no sale**: si lleva un **Bloque** y tiene marcado **Ocultar la ventana si el bloque no está disponible**, mira si el módulo del bloque está activado y funcionando. Después, comprueba que está **Activo**, que estás dentro de sus fechas, que tu cuenta entra en su **Visibilidad** y que la página y el dispositivo encajan. Si ya la viste, la **Frecuencia** decide cuándo vuelve: con **Una sola vez** ya no sale, y con los otros modos, no hasta la próxima sesión o hasta pasados los días. Si ya salió en esta visita, no vuelve hasta la próxima. Para probarla, usa una ventana privada del navegador y, si has iniciado sesión, ciérrala o prueba con otra cuenta, porque tu cuenta también la recuerda.
- **Sale otra ventana en lugar de la mía**: otra con más prioridad, o con la misma y más reciente, encaja en esa página.
- **El enlace del botón no se acepta**: tiene que empezar por `https://` o por `/`.
- **Una ruta no se acepta**: empieza por `/`, sin el nombre de la web delante ni `?`, y solo puede llevar un `*` al final.

[[action:filament.admin.resources.announcements.create|Crear un anuncio]]
