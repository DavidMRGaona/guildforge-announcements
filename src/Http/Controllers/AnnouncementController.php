<?php

declare(strict_types=1);

namespace Modules\Announcements\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Announcements\Application\DTOs\AnnouncementResponseDTO;
use Modules\Announcements\Application\Services\AnnouncementServiceInterface;
use Modules\Announcements\Domain\Enums\AnnouncementFormat;
use Modules\Announcements\Http\Requests\GetAnnouncementsRequest;

final class AnnouncementController extends Controller
{
    public function __construct(
        private readonly AnnouncementServiceInterface $announcementService,
    ) {}

    /**
     * Get the active banners for the current user. Windows are not cards: they open on the pages.
     */
    public function index(GetAnnouncementsRequest $request): JsonResponse
    {
        $user = $request->user();
        $announcements = $this->announcementService->getActiveForUser($user, AnnouncementFormat::Banner);

        return response()->json([
            'data' => array_map(
                fn ($announcement) => $announcement->toArray(),
                $announcements
            ),
        ]);
    }

    /**
     * Get a specific banner, only if the current user may see it in the list.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $announcement = $this->announcementService->findVisibleForUser($id, $request->user());

        if (! $announcement instanceof AnnouncementResponseDTO || $announcement->format !== AnnouncementFormat::Banner->value) {
            return response()->json([
                'message' => __('announcements::announcements.messages.not_found'),
            ], 404);
        }

        return response()->json([
            'data' => $announcement->toArray(),
        ]);
    }
}
