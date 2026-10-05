<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\UniqueConstraintViolationException;
use RuntimeException;

class VpnCredential extends Model
{
    protected $fillable = [
        'user_id',
        'subscription_id',
        'public_key',
        'private_key',
        'address',
        'status',
        'revoked_at',
    ];

    protected $hidden = [
        'private_key',
    ];

    protected function casts(): array
    {
        return [
            'private_key' => 'encrypted',
            'revoked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class)->withTrashed();
    }

    public static function serverIsConfigured(): bool
    {
        return filled(config('services.vpn.server_public_key')) && filled(config('services.vpn.endpoint'));
    }

    /**
     * Return the user's active WireGuard peer, creating one (new keypair + tunnel address) if needed.
     */
    public static function issueFor(User $user, Subscription $subscription): self
    {
        $existing = $user->vpnCredentials()->where('status', 'active')->latest('id')->first();

        if ($existing) {
            if ($existing->subscription_id !== $subscription->id) {
                $existing->update(['subscription_id' => $subscription->id]);
            }

            return $existing;
        }

        for ($attempt = 0; $attempt < 5; $attempt++) {
            [$privateKey, $publicKey] = self::generateKeyPair();

            try {
                $credential = $user->vpnCredentials()->create([
                    'subscription_id' => $subscription->id,
                    'public_key' => $publicKey,
                    'private_key' => $privateKey,
                    'address' => self::nextAddress(),
                    'status' => 'active',
                ]);

                AuditLog::record('vpn.credential_issued', $credential, ['user_id' => $user->id], $user);

                return $credential;
            } catch (UniqueConstraintViolationException) {
                // Another request took the same tunnel address; pick the next one.
            }
        }

        throw new RuntimeException('Unable to allocate a VPN tunnel address.');
    }

    /**
     * Revoke every active peer of the user. Returns the number revoked.
     */
    public static function revokeFor(User $user, string $reason): int
    {
        $credentials = $user->vpnCredentials()->where('status', 'active')->get();

        foreach ($credentials as $credential) {
            $credential->update(['status' => 'revoked', 'revoked_at' => now()]);
            AuditLog::record('vpn.credential_revoked', $credential, ['user_id' => $user->id, 'reason' => $reason]);
        }

        return $credentials->count();
    }

    public function toWireGuardConfig(): string
    {
        if (! self::serverIsConfigured()) {
            throw new RuntimeException('VPN server is not configured.');
        }

        $prefix = (int) config('services.vpn.client_prefix', 32);

        return implode("\n", [
            '[Interface]',
            'PrivateKey = '.$this->private_key,
            'Address = '.$this->address.'/'.$prefix,
            'DNS = '.config('services.vpn.dns'),
            '',
            '[Peer]',
            'PublicKey = '.config('services.vpn.server_public_key'),
            'Endpoint = '.config('services.vpn.endpoint'),
            'AllowedIPs = '.config('services.vpn.allowed_ips'),
            'PersistentKeepalive = 25',
            '',
        ]);
    }

    /**
     * WireGuard keys are Curve25519: a clamped random scalar and its base-point multiple, base64-encoded.
     *
     * @return array{0: string, 1: string}
     */
    private static function generateKeyPair(): array
    {
        $secret = random_bytes(32);
        $secret[0] = chr(ord($secret[0]) & 248);
        $secret[31] = chr((ord($secret[31]) & 127) | 64);

        return [base64_encode($secret), base64_encode(sodium_crypto_scalarmult_base($secret))];
    }

    private static function nextAddress(): string
    {
        [$network, $bits] = explode('/', (string) config('services.vpn.address_pool', '10.66.0.0/16'));
        $base = ip2long($network);
        $size = 2 ** (32 - (int) $bits);
        $last = self::query()->latest('id')->value('address');

        // .1 is reserved for the server; addresses are never reused so revoked peers can't collide.
        $offset = $last ? ip2long($last) - $base + 1 : 2;

        if ($offset >= $size - 1) {
            throw new RuntimeException('VPN address pool is exhausted.');
        }

        return long2ip($base + $offset);
    }
}
