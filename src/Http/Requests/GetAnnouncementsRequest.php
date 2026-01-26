<?php

declare(strict_types=1);

namespace Modules\Announcements\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class GetAnnouncementsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Public endpoint
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return []; // No parameters needed for index
    }
}
