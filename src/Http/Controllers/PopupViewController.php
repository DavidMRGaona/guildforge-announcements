<?php

declare(strict_types=1);

namespace Modules\Announcements\Http\Controllers;

use App\Application\Impersonation\Services\ImpersonationContextInterface;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Announcements\Application\Services\PopupViewServiceInterface;

/**
 * The browser reports that it showed or closed a window (announcements#68) so the account does
 * not get it again on another device. Only the id travels. It always answers without content,
 * as the page does nothing with the answer.
 */
final class PopupViewController extends Controller
{
    public function __construct(
        private readonly PopupViewServiceInterface $views,
        private readonly ImpersonationContextInterface $impersonation,
    ) {}

    public function shown(Request $request, string $id): Response|JsonResponse
    {
        return $this->remember($request, $id, dismissed: false);
    }

    public function dismissed(Request $request, string $id): Response|JsonResponse
    {
        return $this->remember($request, $id, dismissed: true);
    }

    private function remember(Request $request, string $id, bool $dismissed): Response|JsonResponse
    {
        $user = $request->user();

        if (! $user instanceof Authenticatable) {
            abort(401);
        }

        // Nothing is written while an administrator looks at the site as someone else: the net
        // would undo it, but the answer is the same, so the page notices nothing
        if ($this->impersonation->isActive()) {
            return response()->noContent();
        }

        $recorded = $dismissed
            ? $this->views->recordDismissed($user, $id)
            : $this->views->recordShown($user, $id);

        if (! $recorded) {
            return response()->json(['message' => __('announcements::announcements.messages.not_found')], 404);
        }

        return response()->noContent();
    }
}
