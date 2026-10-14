<?php

declare(strict_types=1);

namespace Modules\Announcements\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Announcements\Application\Services\AnnouncementServiceInterface;
use Modules\Announcements\Http\Requests\GetAnnouncementsRequest;

final class AnnouncementController extends Controller
{
    public function __construct(
        private readonly AnnouncementServiceInterface $announcementService,
    ) {}

    /**
     * Get active announcements for the current user.
     */
    public function index(GetAnnouncementsRequest $request): JsonResponse
    {
        $user = $request->user();
        $announcements = $this->announcementService->getActiveForUser($user);

        return response()->json([
            'data' => array_map(
                fn ($announcement) => $announcement->toArray(),
                $announcements
            ),
        ]);
    }

    /**
     * Get a specific announcement, only if the current user may see it in the list.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $announcement = $this->announcementService->findVisibleForUser($id, $request->user());

        if ($announcement === null) {
            return response()->json([
                'message' => __('announcements::announcements.messages.not_found'),
            ], 404);
        }

        return response()->json([
            'data' => $announcement->toArray(),
        ]);
    }
}
