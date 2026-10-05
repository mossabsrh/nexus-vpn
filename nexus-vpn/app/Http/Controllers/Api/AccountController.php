<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\VpnCredential;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AccountController extends Controller
{
    public function subscriptions(Request $request): JsonResponse
    {
        $subscriptions = $request->user()->subscriptions()->with('plan')->latest('id')->paginate(10);

        return response()->json([
            'subscriptions' => $subscriptions->getCollection()->map(fn (Subscription $subscription) => $subscription->toApiArray()),
            'meta' => $this->meta($subscriptions),
        ]);
    }

    public function payments(Request $request): JsonResponse
    {
        $payments = $request->user()->payments()->latest('id')->paginate(10);

        return response()->json([
            'payments' => $payments->getCollection()->map(fn (Payment $payment) => $payment->toApiArray()),
            'meta' => $this->meta($payments),
        ]);
    }

    /**
     * Receipt details for one of the user's own payments.
     */
    public function payment(Request $request, Payment $payment): JsonResponse
    {
        abort_unless($payment->user_id === $request->user()->id, 404);

        return response()->json([
            'payment' => $payment->toApiArray(),
            'customer' => ['name' => $request->user()->name, 'email' => $request->user()->email],
        ]);
    }

    public function vpnStatus(Request $request): JsonResponse
    {
        $user = $request->user();
        $credential = $user->vpnCredentials()->where('status', 'active')->latest('id')->first();

        return response()->json([
            'hasAccess' => $user->hasVpnAccess(),
            'emailVerified' => $user->hasVerifiedEmail(),
            'serverConfigured' => VpnCredential::serverIsConfigured(),
            'credential' => $credential ? [
                'publicKey' => $credential->public_key,
                'address' => $credential->address,
                'issuedAt' => $credential->created_at?->toISOString(),
            ] : null,
        ]);
    }

    /**
     * Download the WireGuard client config. Issues a peer on first download for users with access.
     */
    public function vpnConfig(Request $request): Response|JsonResponse
    {
        $user = $request->user();
        $subscription = $user->accessSubscription();

        if (! $subscription) {
            return response()->json(['message' => 'An active trial or subscription is required.'], 403);
        }

        if (! $user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Please verify your email address first.', 'code' => 'email_unverified'], 403);
        }

        if (! VpnCredential::serverIsConfigured()) {
            return response()->json(['message' => 'VPN servers are not available yet. Please try again later.'], 503);
        }

        $credential = VpnCredential::issueFor($user, $subscription);
        AuditLog::record('vpn.config_downloaded', $credential, [], $user);

        return response($credential->toWireGuardConfig(), 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="nexusvpn.conf"',
            'Cache-Control' => 'no-store',
        ]);
    }

    /**
     * @return array<string, int>
     */
    private function meta($paginator): array
    {
        return [
            'currentPage' => $paginator->currentPage(),
            'lastPage' => $paginator->lastPage(),
            'perPage' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }
}
