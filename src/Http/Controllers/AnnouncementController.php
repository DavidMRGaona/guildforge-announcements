<?php

declare(strict_types=1);

namespace Modules\Announcements\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Announcements\Application\Services\AnnouncementServiceInterface;

final class AnnouncementController extends Controller
{
    public function __construct(
        private readonly AnnouncementServiceInterface $announcementService,
    ) {}

    /**
     * Get active announcements for the current user.
     */
    public function index(Request $request): JsonResponse
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
     * Get a specific announcement.
     */
    public function show(string $id): JsonResponse
    {
        $announcement = $this->announcementService->find($id);

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
