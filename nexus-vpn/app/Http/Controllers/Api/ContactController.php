<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\ContactRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Sales inquiries for the Team plan. Admins follow up and provision the team subscription.
 */
class ContactController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->merge(['email' => strtolower(trim((string) $request->input('email')))]);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:255'],
            'company' => ['nullable', 'string', 'max:150'],
            'seats' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'message' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        ContactRequest::query()->create($validated + [
            'user_id' => $request->user()?->id,
            'ip_address' => $request->ip(),
        ]);

        return response()->json(['message' => 'Thanks! Our team will reply by email within one business day.'], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $request->validate(['status' => ['nullable', Rule::in(['new', 'handled'])]]);

        $requests = ContactRequest::query()
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->latest('id')
            ->paginate(20);

        return response()->json([
            'requests' => $requests->getCollection()->map(fn (ContactRequest $contact) => $contact->toApiArray()),
            'meta' => AdminController::meta($requests),
            'newCount' => ContactRequest::query()->where('status', 'new')->count(),
        ]);
    }

    public function update(Request $request, ContactRequest $contactRequest): JsonResponse
    {
        $validated = $request->validate(['status' => ['required', Rule::in(['new', 'handled'])]]);

        $contactRequest->update([
            'status' => $validated['status'],
            'handled_by' => $validated['status'] === 'handled' ? $request->user()->id : null,
            'handled_at' => $validated['status'] === 'handled' ? now() : null,
        ]);
        AuditLog::record('admin.contact_request_'.$validated['status'], $contactRequest);

        return response()->json(['request' => $contactRequest->toApiArray()]);
    }
}
