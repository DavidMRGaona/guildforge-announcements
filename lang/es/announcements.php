<?php

declare(strict_types=1);

return [
    // Navigation
    'navigation' => 'Anuncios',

    // Model labels
    'model' => [
        'singular' => 'anuncio',
        'plural' => 'Anuncios',
    ],

    // Sections
    'sections' => [
        'content' => 'Contenido',
        'display' => 'Visualización',
        'display_description' => 'Configuración de dónde y cómo se muestra el anuncio.',
        'styling' => 'Estilo personalizado',
        'styling_description' => 'Deja vacío para usar los colores predeterminados según la prioridad.',
        'scheduling' => 'Programación',
        'popup' => 'Ventana emergente',
        'popup_description' => 'Cómo es la ventana. Se puede cerrar siempre, con la ✕ o con la tecla Esc.',
        'popup_triggers' => 'Cuándo se abre',
        'popup_triggers_description' => 'Se abre con el primero que se cumpla. Sin ninguno, nada más cargar la página.',
        'popup_where' => 'Dónde se abre',
    ],

    // Fields
    'fields' => [
        'title' => 'Título',
        'content' => 'Contenido',
        'visibility' => 'Visibilidad',
        'visibility_help' => '«Solo socios» lo ven únicamente los usuarios con el rol de socio elegido en Configuración del sitio → Autenticación. «Solo visitas sin sesión», quien no ha iniciado sesión.',
        'position' => 'Posición',
        'priority' => 'Prioridad',
        'priority_help' => '1 (baja) a 10 (alta). Afecta el orden de visualización y el color predeterminado.',
        'background_color' => 'Color de fondo',
        'background_color_help' => 'Color de fondo personalizado para el banner.',
        'text_color' => 'Color de texto',
        'text_color_help' => 'Color del texto personalizado para el banner.',
        'starts_at' => 'Fecha de inicio',
        'ends_at' => 'Fecha de fin',
        'is_active' => 'Activo',
        'is_dismissible' => 'Permite cerrar',
        'is_dismissible_help' => 'Si el usuario puede cerrar el anuncio. Una vez cerrado, no volverá a aparecer.',
        'created_at' => 'Creado',
        'updated_at' => 'Actualizado',
        'format' => 'Formato',
        'format_help' => 'Un banner sale como una franja en la posición elegida; una ventana emergente se abre encima de la página.',
    ],

    // Visibility options
    'visibility' => [
        'public' => 'Público',
        'authenticated' => 'Usuarios autenticados',
        'members' => 'Solo socios',
        'guests' => 'Solo visitas sin sesión',
    ],

    // Position options
    'position' => [
        'before_header' => 'Antes de la cabecera',
        'after_header' => 'Después de la cabecera',
        'before_content' => 'Antes del contenido',
        'after_content' => 'Después del contenido',
        'before_footer' => 'Antes del pie de página',
        'after_footer' => 'Después del pie de página',
    ],

    // Actions
    'actions' => [
        'activate' => 'Activar',
        'deactivate' => 'Desactivar',
        'activated' => 'Anuncio activado',
        'deactivated' => 'Anuncio desactivado',
    ],

    // Messages
    'messages' => [
        'created' => 'Anuncio creado correctamente.',
        'updated' => 'Anuncio actualizado correctamente.',
        'deleted' => 'Anuncio eliminado correctamente.',
        'not_found' => 'Anuncio no encontrado.',
    ],

    // Permissions
    'permissions' => [
        'view_any' => 'Ver listado de anuncios',
        'create' => 'Crear anuncios',
        'update' => 'Editar anuncios',
        'delete' => 'Eliminar anuncios',
        'settings_manage' => 'Cambiar los ajustes del banner',
    ],

    // Settings
    'settings' => [
        'navigation' => 'Anuncios',
        'title' => 'Ajustes de anuncios',
        'saved' => 'Ajustes guardados.',
        'save' => 'Guardar cambios',
        'display' => 'Visualización',
        'display_description' => 'Configuración de cómo se muestran los anuncios en el sitio.',
        'show_banner' => 'Mostrar banner',
        'show_banner_help' => 'Muestra los anuncios activos con formato banner en la posición elegida en cada anuncio. Las ventanas emergentes no dependen de este ajuste.',
        'auto_rotate' => 'Rotación automática',
        'auto_rotate_help' => 'Rota automáticamente entre los anuncios activos.',
        'rotate_interval' => 'Intervalo de rotación',
        'rotate_interval_help' => 'Tiempo entre cambios de anuncio (en milisegundos).',
    ],

    // Filters
    'filters' => [
        'visibility' => 'Visibilidad',
        'position' => 'Posición',
        'is_active' => 'Estado',
        'active' => 'Activos',
        'inactive' => 'Inactivos',
        'format' => 'Formato',
    ],

    // Placeholders
    'placeholders' => [
        'title' => 'Título del anuncio',
        'content' => 'Escribe el contenido del anuncio...',
        'no_date' => 'Sin fecha',
    ],

    // Media library (core#482)
    'media' => [
        'source' => 'Anuncios',
        'role_in_text' => 'En el texto',
        'role_popup_image' => 'Imagen de la ventana',
    ],

    // Formats (announcements#67)
    'format' => [
        'banner' => 'Banner',
        'popup' => 'Ventana emergente',
    ],

    // Popup windows (announcements#67)
    'popup' => [
        'shape' => [
            'label' => 'Forma',
            'help' => 'La centrada tapa la página hasta que se cierra; la tarjeta sale abajo y deja seguir leyendo.',
            'centered' => 'Centrada',
            'corner' => 'Tarjeta en la esquina inferior',
        ],
        'image' => 'Imagen',
        'image_help' => 'Opcional. Sale encima del título.',
        'image_alt' => 'Texto alternativo de la imagen',
        'image_alt_help' => 'Qué muestra la imagen, para quien no puede verla. Déjalo vacío si es solo decoración.',
        'action_label' => 'Texto del botón',
        'action_label_help' => 'Opcional. Sin texto y enlace no sale el botón.',
        'action_url' => 'Enlace del botón',
        'action_url_help' => 'Una dirección https o una ruta de la web que empiece por «/», como /eventos.',
        'action_url_invalid' => 'El enlace tiene que ser una dirección https o una ruta de la web que empiece por «/».',
        'trigger_delay' => 'Al cargar la página, tras unos segundos',
        'trigger_delay_help' => 'De 0 a 60 segundos. Con 0 se abre nada más cargar.',
        'trigger_scroll' => 'Al bajar por la página (%)',
        'trigger_scroll_help' => 'De 10 a 100. Solo cuenta al bajar: en una página que no se puede bajar, no se abre por esto.',
        'trigger_exit_intent' => 'Al ir a salir',
        'trigger_exit_intent_help' => 'Cuando el ratón sale por arriba de la página. Solo en escritorio: en móviles se usa el retardo, o 10 segundos si no hay.',
        'trigger_page_views' => 'Tras ver varias páginas',
        'trigger_page_views_help' => 'De 2 a 20 páginas en la misma visita.',
        'seconds' => 's',
        'pages' => [
            'label' => 'Páginas',
            'help' => 'Nunca se abre en el panel, al entrar o registrarse, en las páginas legales, en mantenimiento, en /newsletter ni en las páginas de error.',
            'all' => 'Todo el sitio',
            'home' => 'Solo la portada',
            'paths' => 'Solo estas rutas',
        ],
        'paths' => 'Rutas',
        'paths_help' => 'Pulsa Intro tras cada una. Termina en * para incluir lo que hay debajo: /eventos* vale para /eventos y para cada evento.',
        'paths_invalid' => 'Cada ruta empieza por «/», sin dominio ni «?», y solo puede llevar un «*» al final.',
        'device' => [
            'label' => 'Dispositivo',
            'help' => 'Un móvil es una pantalla de menos de 768 px de ancho.',
            'all' => 'Todos',
            'mobile' => 'Solo móviles',
            'desktop' => 'Solo escritorio',
        ],
    ],
];
