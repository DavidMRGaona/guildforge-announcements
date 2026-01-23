<?php

declare(strict_types=1);

return [
    // Navigation
    'navigation' => 'Anuncios',
    'navigation_group' => 'Comunicación',

    // Model labels
    'model' => [
        'singular' => 'Anuncio',
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
    ],

    // Fields
    'fields' => [
        'title' => 'Título',
        'content' => 'Contenido',
        'visibility' => 'Visibilidad',
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
    ],

    // Visibility options
    'visibility' => [
        'public' => 'Público',
        'authenticated' => 'Usuarios autenticados',
        'members' => 'Solo miembros',
    ],

    // Position options
    'position' => [
        'before_header' => 'Antes del header',
        'after_header' => 'Después del header',
        'before_content' => 'Antes del contenido',
        'after_content' => 'Después del contenido',
        'before_footer' => 'Antes del footer',
        'after_footer' => 'Después del footer',
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
        'view' => 'Ver anuncios',
        'create' => 'Crear anuncios',
        'update' => 'Editar anuncios',
        'delete' => 'Eliminar anuncios',
    ],

    // Settings
    'settings' => [
        'display' => 'Visualización',
        'display_description' => 'Configuración de cómo se muestran los anuncios en el sitio.',
        'show_banner' => 'Mostrar banner',
        'show_banner_help' => 'Muestra los anuncios como un banner en la parte superior del sitio.',
        'banner_position' => 'Posición del banner',
        'position_top' => 'Arriba',
        'position_bottom' => 'Abajo',
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
    ],

    // Placeholders
    'placeholders' => [
        'title' => 'Título del anuncio',
        'content' => 'Escribe el contenido del anuncio...',
        'no_date' => 'Sin fecha',
    ],
];
