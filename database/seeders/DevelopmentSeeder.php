<?php

declare(strict_types=1);

namespace Modules\Announcements\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Announcements\Domain\Enums\AnnouncementPosition;
use Modules\Announcements\Domain\Enums\AnnouncementVisibility;
use Modules\Announcements\Infrastructure\Persistence\Eloquent\Models\AnnouncementModel;

final class DevelopmentSeeder extends Seeder
{
    public function run(): void
    {
        // Demo data: the core runs every module seeder on install and update, production included
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        // Enabling the module in tests runs this seeder too, and the suites expect no announcements
        if (! app()->environment('local')) {
            return;
        }

        $this->seedAnnouncements();
    }

    /**
     * Only the first one is active: the others are switched off (or not due yet) so the site
     * does not show a banner for each of them, and can be activated from the panel to try
     * every priority, visibility and position. The priority sets the default colour:
     * 1-4 informative, 5-7 warning, 8-10 urgent.
     */
    private function seedAnnouncements(): void
    {
        $announcements = [
            [
                'title' => 'Bienvenida a la temporada de otoño',
                'content' => '<p>Ya está abierto el calendario de <strong>otoño</strong>: torneos, partidas de rol y talleres de pintura todas las semanas.</p>',
                'visibility' => AnnouncementVisibility::Public,
                'position' => AnnouncementPosition::BeforeHeader,
                'priority' => 3,
                'is_active' => true,
                'is_dismissible' => true,
            ],
            [
                'title' => 'Cambio de horario de la sala',
                'content' => '<p>Este mes la sala <strong>cierra a las 21:00</strong> los miércoles por obras en el edificio. Gracias por vuestra paciencia.</p>',
                'visibility' => AnnouncementVisibility::Public,
                'position' => AnnouncementPosition::AfterHeader,
                'priority' => 6,
                'is_active' => false,
                'is_dismissible' => true,
            ],
            [
                'title' => 'Local cerrado por una incidencia',
                'content' => '<p><strong>Atención:</strong> el local permanece cerrado hasta nuevo aviso por una avería en el suministro eléctrico.</p>',
                'visibility' => AnnouncementVisibility::Public,
                'position' => AnnouncementPosition::BeforeHeader,
                'priority' => 9,
                'is_active' => false,
                'is_dismissible' => false,
            ],
            [
                'title' => 'Asamblea general de socios',
                'content' => '<p>Los socios están convocados a la asamblea general. Consulta el orden del día en el área de socios.</p>',
                'visibility' => AnnouncementVisibility::Members,
                'position' => AnnouncementPosition::BeforeContent,
                'priority' => 7,
                'is_active' => false,
                'is_dismissible' => true,
            ],
            [
                'title' => 'Novedades para usuarios registrados',
                'content' => '<p>Ya puedes gestionar tus reservas e inscripciones desde tu perfil.</p>',
                'visibility' => AnnouncementVisibility::Authenticated,
                'position' => AnnouncementPosition::AfterContent,
                'priority' => 4,
                'is_active' => false,
                'is_dismissible' => true,
            ],
            [
                'title' => 'Próxima jornada de puertas abiertas',
                'content' => '<p>Dentro de poco abrimos las puertas a quien quiera conocer la asociación. ¡Trae a tus amigos!</p>',
                'visibility' => AnnouncementVisibility::Public,
                'position' => AnnouncementPosition::BeforeFooter,
                'priority' => 5,
                'is_active' => true,
                'is_dismissible' => true,
                'starts_at' => now()->addDays(10)->startOfDay(),
                'ends_at' => now()->addDays(17)->endOfDay(),
            ],
            [
                'title' => 'Aviso con colores personalizados',
                'content' => '<p>Ejemplo de un aviso con <strong>colores propios</strong> de fondo y de texto.</p>',
                'visibility' => AnnouncementVisibility::Public,
                'position' => AnnouncementPosition::AfterFooter,
                'priority' => 4,
                'background_color' => '#4c1d95',
                'text_color' => '#ffffff',
                'is_active' => false,
                'is_dismissible' => true,
            ],
        ];

        foreach ($announcements as $data) {
            AnnouncementModel::firstOrCreate(
                ['title' => $data['title']],
                $data + ['starts_at' => null, 'ends_at' => null, 'background_color' => null, 'text_color' => null],
            );
        }
    }
}
