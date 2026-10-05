<?php

namespace App\Console\Commands;

use App\Models\VpnCredential;
use Illuminate\Console\Command;

/**
 * Prints the [Peer] sections for every active credential, for the WireGuard gateway:
 *   php artisan vpn:peers > peers.conf && wg syncconf wg0 <(cat server-interface.conf peers.conf)
 */
class ExportVpnPeers extends Command
{
    protected $signature = 'vpn:peers';

    protected $description = 'Export active VPN peers in WireGuard [Peer] format (public keys and addresses only)';

    public function handle(): int
    {
        VpnCredential::query()->where('status', 'active')->orderBy('id')->chunkById(500, function ($credentials): void {
            foreach ($credentials as $credential) {
                $this->line('[Peer]');
                $this->line('# credential '.$credential->id.', user '.$credential->user_id);
                $this->line('PublicKey = '.$credential->public_key);
                $this->line('AllowedIPs = '.$credential->address.'/32');
                $this->line('');
            }
        });

        return self::SUCCESS;
    }
}
