<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Testimonial;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TestimonialController extends Controller
{
    private const MAX_PENDING_PER_USER = 1;

    /**
     * Approved testimonials only, paginated.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->integer('per_page', 12), 1), 50);
        $testimonials = Testimonial::query()
            ->approved()
            ->with('user:id,name')
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'testimonials' => $testimonials->getCollection()->map(fn (Testimonial $testimonial) => $this->present($testimonial)),
            'meta' => [
                'currentPage' => $testimonials->currentPage(),
                'lastPage' => $testimonials->lastPage(),
                'total' => $testimonials->total(),
            ],
        ]);
    }

    /**
     * New testimonials wait for admin approval. One pending testimonial per user at a time,
     * plus a route-level hourly throttle.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'content' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        $user = $request->user();

        if ($user->testimonials()->where('status', 'pending')->count() >= self::MAX_PENDING_PER_USER) {
            return response()->json([
                'message' => 'You already have a comment waiting for review.',
            ], 429);
        }

        $testimonial = $user->testimonials()->create([
            'content' => trim($validated['content']),
            'status' => 'pending',
        ]);
        $testimonial->load('user:id,name');

        return response()->json([
            'testimonial' => $this->present($testimonial),
            'message' => 'Thanks! Your comment will appear once it has been reviewed.',
        ], 201);
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Testimonial $testimonial): array
    {
        return [
            'id' => $testimonial->id,
            'content' => $testimonial->content,
            'name' => $testimonial->user?->name ?? 'NexusVPN user',
            'status' => $testimonial->status,
            'createdAt' => $testimonial->created_at->toISOString(),
        ];
    }
}
