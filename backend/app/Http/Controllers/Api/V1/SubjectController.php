<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\SubjectResource;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Fächer und ihre Themen für die App. Sichtbar ist nur, was veröffentlicht und aktiv ist (E-014, E-017).
 */
class SubjectController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $subjects = Subject::visible()
            ->withCount(['topics' => fn (Builder $topics) => $topics->published()])
            ->orderBy('order_index')
            ->get();

        return SubjectResource::collection($subjects);
    }

    public function show(string $subject): SubjectResource
    {
        $subject = Subject::visible()
            ->where('slug', $subject)
            ->with(['topics' => fn ($topics) => $topics->published()
                ->withCount(['questions' => fn (Builder $questions) => $questions->published()])])
            ->firstOrFail();

        return new SubjectResource($subject);
    }
}
