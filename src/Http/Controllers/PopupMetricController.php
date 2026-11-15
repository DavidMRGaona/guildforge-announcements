<?php

declare(strict_types=1);

namespace Modules\Announcements\Http\Controllers;

use App\Application\Impersonation\Services\ImpersonationContextInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Announcements\Application\Services\PopupMetricServiceInterface;
use Modules\Announcements\Domain\Enums\PopupMetric;

/**
 * The browser counts what happened to a window (announcements#69). Open to visitors without a
 * session; only the window id and the kind of event travel, and nothing about who sent it is
 * kept. Always answers without content: the page does nothing with the answer.
 */
final class PopupMetricController extends Controller
{
    public function __construct(
        private readonly PopupMetricServiceInterface $metrics,
        private readonly ImpersonationContextInterface $impersonation,
    ) {}

    public function store(Request $request, string $id, string $metric): Response|JsonResponse
    {
        $kind = PopupMetric::tryFrom($metric);

        if (! $kind instanceof PopupMetric) {
            abort(404);
        }

        // Looking at the site as someone else is not a visit: nothing is counted
        if ($this->impersonation->isActive()) {
            return response()->noContent();
        }

        if (! $this->metrics->record($request->user(), $id, $kind)) {
            return response()->json(['message' => __('announcements::announcements.messages.not_found')], 404);
        }

        return response()->noContent();
    }
}
