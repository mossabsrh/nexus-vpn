<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Team;
use App\Models\User;
use App\Models\VpnCredential;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Team owners manage seat holders. Team subscriptions themselves are created by admins
 * (custom per-seat pricing agreed with sales).
 */
class TeamController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $team = $request->user()->teams()->with('members', 'subscription.plan')->first();

        if (! $team) {
            return response()->json(['team' => null]);
        }

        $payload = $team->toApiArray();

        // Members only see the team name and their own seat; the roster is for the owner.
        if ($team->owner_id !== $request->user()->id) {
            unset($payload['members']);
        }

        return response()->json(['team' => $payload]);
    }

    public function update(Request $request): JsonResponse
    {
        $team = $this->ownedTeam($request);
        $validated = $request->validate(['name' => ['required', 'string', 'max:150']]);
        $team->update($validated);

        return response()->json(['team' => $team->fresh()->toApiArray()]);
    }

    public function addMember(Request $request): JsonResponse
    {
        $team = $this->ownedTeam($request);
        $request->merge(['email' => strtolower(trim((string) $request->input('email')))]);
        $validated = $request->validate(['email' => ['required', 'email']]);

        if (! $team->subscription?->grantsAccess()) {
            return response()->json(['message' => 'The team subscription is not active.'], 409);
        }

        $member = User::query()->where('email', $validated['email'])->first();

        if (! $member) {
            return response()->json(['message' => 'No NexusVPN account uses that email. Ask them to sign up first.'], 404);
        }

        $result = DB::transaction(function () use ($team, $member): ?string {
            $locked = Team::query()->whereKey($team->id)->lockForUpdate()->first();

            if ($locked->members()->whereKey($member->id)->exists()) {
                return 'That user is already on the team.';
            }

            if ($locked->members()->count() >= $team->seatLimit()) {
                return 'All '.$team->seatLimit().' seats are in use.';
            }

            $locked->members()->attach($member->id, ['role' => 'member', 'created_at' => now()]);

            return null;
        });

        if ($result !== null) {
            return response()->json(['message' => $result], 409);
        }

        AuditLog::record('team.member_added', $team, ['member_id' => $member->id]);

        return response()->json(['team' => $team->fresh()->toApiArray()], 201);
    }

    public function removeMember(Request $request, User $member): JsonResponse
    {
        $team = $this->ownedTeam($request);

        if ($member->id === $team->owner_id) {
            return response()->json(['message' => 'The team owner cannot be removed.'], 422);
        }

        if ($team->members()->detach($member->id) === 0) {
            return response()->json(['message' => 'That user is not on the team.'], 404);
        }

        AuditLog::record('team.member_removed', $team, ['member_id' => $member->id]);

        if (! $member->hasVpnAccess()) {
            VpnCredential::revokeFor($member, 'removed_from_team');
        }

        return response()->json(['team' => $team->fresh()->toApiArray()]);
    }

    private function ownedTeam(Request $request): Team
    {
        $team = $request->user()->ownedTeams()->latest('id')->first();
        abort_unless($team, 403, 'Only the team owner can manage the team.');

        return $team;
    }
}
