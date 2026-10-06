<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\ContactRequest;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Team;
use App\Models\Testimonial;
use App\Models\User;
use App\Models\VpnCredential;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Jeux de données de démonstration pour les captures d'écran et le mémoire.
 *
 * - N'altère jamais le compte administrateur réel (mohamed.belkacemi2004@gmail.com).
 * - Idempotent : les comptes de démonstration utilisent le domaine @demo.nexusvpn.dz
 *   et sont recréés proprement à chaque exécution (`php artisan db:seed --class=DemoDataSeeder`).
 */
class DemoDataSeeder extends Seeder
{
    private const DEMO_DOMAIN = '@demo.nexusvpn.dz';

    public function run(): void
    {
        $this->clearPreviousDemoData();

        $essential = Plan::where('slug', 'essential')->first() ?? Plan::where('monthly_price', '>', 0)->orderBy('monthly_price')->first();
        $standard = Plan::where('slug', 'standard')->first() ?? $essential;
        $team = Plan::where('slug', 'team')->first();

        // 1. Utilisateur payant (abonnement actif mensuel) + VPN actif + paiement réussi.
        $yacine = $this->user('Yacine Benali', 'yacine.benali', true, 'user', now()->subMonths(4));
        $subA = $this->subscription($yacine, $essential, 'active', [
            'billing_period' => 'monthly',
            'started_at' => now()->subMonth(),
            'current_period_end' => now()->addWeeks(2),
        ]);
        $this->payment($yacine, $subA, $essential->monthly_price, 'succeeded', now()->subMonth());
        $this->issueVpn($yacine, $subA);

        // 2. Utilisatrice en période d'essai + VPN actif.
        $amina = $this->user('Amina Cherif', 'amina.cherif', true, 'user', now()->subDays(2));
        $subB = $this->subscription($amina, $standard, 'trialing', [
            'started_at' => now()->subDays(2),
            'trial_ends_at' => now()->addDays(5),
            'current_period_end' => now()->addDays(5),
        ]);
        $this->issueVpn($amina, $subB);

        // 3. Abonnement expiré + VPN révoqué.
        $karim = $this->user('Karim Boumediene', 'karim.boumediene', true, 'user', now()->subMonths(6));
        $subC = $this->subscription($karim, $essential, 'expired', [
            'billing_period' => 'monthly',
            'started_at' => now()->subMonths(2),
            'current_period_end' => now()->subDays(10),
        ]);
        $this->payment($karim, $subC, $essential->monthly_price, 'succeeded', now()->subMonths(2));
        $cred = $this->issueVpn($karim, $subC);
        $cred?->update(['status' => 'revoked', 'revoked_at' => now()->subDays(10)]);

        // 4. Abonnement annuel actif + paiement réussi.
        $sofiane = $this->user('Sofiane Haddad', 'sofiane.haddad', true, 'user', now()->subMonths(3));
        $subD = $this->subscription($sofiane, $standard, 'active', [
            'billing_period' => 'yearly',
            'started_at' => now()->subMonths(3),
            'current_period_end' => now()->addMonths(9),
        ]);
        $this->payment($sofiane, $subD, $standard->yearly_total, 'succeeded', now()->subMonths(3));
        $this->issueVpn($sofiane, $subD);

        // 5. Abonnement annulé (accès conservé jusqu'à la fin de période) + paiement remboursé.
        $nadia = $this->user('Nadia Mansouri', 'nadia.mansouri', true, 'user', now()->subMonths(2));
        $subE = $this->subscription($nadia, $essential, 'canceled', [
            'billing_period' => 'monthly',
            'started_at' => now()->subMonths(2),
            'canceled_at' => now()->subDays(3),
            'current_period_end' => now()->addDays(12),
        ]);
        $this->payment($nadia, $subE, $essential->monthly_price, 'refunded', now()->subMonths(2), [
            'refunded_at' => now()->subDays(3),
            'refund_reason' => 'Remboursement à la demande du client.',
        ]);

        // 6. Paiement échoué (sans abonnement activé).
        $walid = $this->user('Walid Toumi', 'walid.toumi', true, 'user', now()->subWeeks(2));
        $this->payment($walid, null, $standard->monthly_price, 'failed', null, [
            'failure_reason' => 'Carte refusée par la banque émettrice.',
            'error_code' => 'checkout_failed',
        ]);

        // 7. Comptes sans abonnement (prospects / e-mail non vérifié).
        $this->user('Imane Belkadi', 'imane.belkadi', false, 'user', now()->subDays(1));
        $this->user('Bilal Ferhat', 'bilal.ferhat', true, 'user', now()->subDays(5));

        // 8. Équipe (offre vente assistée) : propriétaire + membres.
        if ($team) {
            $rachid = $this->user('Rachid Zidane', 'rachid.zidane', true, 'user', now()->subMonths(5));
            $teamSub = $this->subscription($rachid, $team, 'active', [
                'seats' => 10,
                'billing_period' => 'yearly',
                'started_at' => now()->subMonths(5),
                'current_period_end' => now()->addMonths(7),
            ]);
            $teamModel = Team::create(['name' => 'Sonatrach IT', 'owner_id' => $rachid->id, 'subscription_id' => $teamSub->id]);
            $this->payment($rachid, $teamSub, ($team->monthly_price ?: 9000) * 12, 'succeeded', now()->subMonths(5));
            $this->issueVpn($rachid, $teamSub);

            foreach ([['Leila Saidi', 'leila.saidi'], ['Omar Khelifi', 'omar.khelifi']] as [$name, $handle]) {
                $member = $this->user($name, $handle, true, 'user', now()->subMonths(4));
                $teamModel->members()->syncWithoutDetaching([$member->id => ['role' => 'member', 'created_at' => now()->subMonths(4)]]);
                $this->issueVpn($member, $teamSub);
            }
        }

        // 9. Témoignages (approuvés et en attente de modération).
        $this->testimonial($yacine, 'Depuis Oran, la connexion WireGuard est stable et rapide. Installation en deux minutes.', 'approved');
        $this->testimonial($sofiane, 'Enfin un VPN qui accepte le paiement en dinars via Edahabia. Service client réactif.', 'approved');
        $this->testimonial($amina, 'Je teste l\'offre gratuite, très bonne première impression.', 'pending');

        // 10. Demandes commerciales (offre Team).
        ContactRequest::create([
            'name' => 'Fatima Zohra Belhadj', 'email' => 'f.belhadj@entreprise-dz.com', 'company' => 'Cevital',
            'seats' => 50, 'message' => 'Nous souhaitons équiper notre équipe IT à Béjaïa.', 'status' => 'new',
            'ip_address' => '41.100.12.34', 'created_at' => now()->subDays(2), 'updated_at' => now()->subDays(2),
        ]);
        $contactHandled = ContactRequest::create([
            'name' => 'Mehdi Ouali', 'email' => 'mehdi.ouali@startup.dz', 'company' => 'Yassir',
            'seats' => 20, 'message' => 'Besoin de 20 accès pour nos développeurs distants.', 'status' => 'handled',
            'handled_at' => now()->subDay(), 'ip_address' => '105.235.4.10',
            'created_at' => now()->subDays(4), 'updated_at' => now()->subDay(),
        ]);

        // 11. Journal d'audit représentatif.
        $this->audit('user.registered', $yacine, $yacine, now()->subMonths(4));
        $this->audit('subscription.trial_started', $amina, $amina, now()->subDays(2), ['plan' => $standard->slug]);
        $this->audit('payment.succeeded', $sofiane, $sofiane, now()->subMonths(3), ['amount' => $standard->yearly_total, 'currency' => 'DZD']);
        $this->audit('vpn.credential_issued', $yacine, $yacine, now()->subMonth());
        $this->audit('subscription.expired', $karim, null, now()->subDays(10));
        $this->audit('payment.refunded', $nadia, null, now()->subDays(3), ['reason' => 'customer_request']);
        $this->audit('payment.failed', $walid, $walid, now()->subWeeks(2), ['error_code' => 'checkout_failed']);
        if (isset($rachid)) {
            $this->audit('admin.team_subscription_created', $rachid, null, now()->subMonths(5), ['seats' => 10]);
        }

        $this->command?->info('DemoDataSeeder: '.User::where('email', 'like', '%'.self::DEMO_DOMAIN)->count().' comptes de démonstration prêts (admin réel préservé).');
    }

    private function clearPreviousDemoData(): void
    {
        $demoIds = User::where('email', 'like', '%'.self::DEMO_DOMAIN)->pluck('id');

        if ($demoIds->isEmpty()) {
            return;
        }

        Team::whereIn('owner_id', $demoIds)->get()->each(function (Team $t): void {
            $t->members()->detach();
            $t->delete();
        });
        VpnCredential::whereIn('user_id', $demoIds)->delete();
        Payment::whereIn('user_id', $demoIds)->forceDelete();
        Testimonial::whereIn('user_id', $demoIds)->delete();
        Subscription::whereIn('user_id', $demoIds)->forceDelete();
        AuditLog::whereIn('actor_id', $demoIds)->delete();
        ContactRequest::whereIn('email', ['f.belhadj@entreprise-dz.com', 'mehdi.ouali@startup.dz'])->delete();
        User::whereIn('id', $demoIds)->forceDelete();
    }

    private function user(string $name, string $handle, bool $verified, string $role, Carbon $createdAt): User
    {
        $user = User::create([
            'name' => $name,
            'email' => $handle.self::DEMO_DOMAIN,
            'password' => Hash::make('password'),
            'role' => $role,
        ]);
        $user->forceFill([
            'email_verified_at' => $verified ? $createdAt->copy()->addMinutes(5) : null,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ])->save();

        return $user;
    }

    /** @param array<string,mixed> $attributes */
    private function subscription(User $user, Plan $plan, string $status, array $attributes): Subscription
    {
        return $user->subscriptions()->create(array_merge([
            'plan_id' => $plan->id,
            'seats' => 1,
            'billing_period' => 'monthly',
            'status' => $status,
        ], $attributes));
    }

    /** @param array<string,mixed> $extra */
    private function payment(User $user, ?Subscription $sub, int $amount, string $status, ?Carbon $paidAt, array $extra = []): Payment
    {
        return Payment::create(array_merge([
            'user_id' => $user->id,
            'subscription_id' => $sub?->id,
            'amount' => $amount,
            'currency' => 'DZD',
            'status' => $status,
            'provider' => 'chargily',
            'provider_reference' => 'demo_'.Str::lower(Str::random(10)),
            'metadata' => ['payment_id' => 'pay_demo_'.Str::lower(Str::random(8)), 'user_id' => $user->id],
            'paid_at' => $paidAt,
            'created_at' => $paidAt ?? now(),
            'updated_at' => $paidAt ?? now(),
        ], $extra));
    }

    private function issueVpn(User $user, Subscription $sub): ?VpnCredential
    {
        try {
            return VpnCredential::issueFor($user, $sub);
        } catch (\Throwable) {
            // Fallback if the sodium/key generation is unavailable in this environment.
            return null;
        }
    }

    private function testimonial(User $user, string $content, string $status): Testimonial
    {
        return Testimonial::create([
            'user_id' => $user->id,
            'content' => $content,
            'status' => $status,
            'moderated_at' => $status === 'approved' ? now()->subDays(1) : null,
        ]);
    }

    /** @param array<string,mixed> $meta */
    private function audit(string $action, ?User $subject, ?User $actor, Carbon $at, array $meta = []): void
    {
        // audit_logs has only created_at (AuditLog::UPDATED_AT === null), so set it via forceFill.
        (new AuditLog())->forceFill([
            'actor_id' => $actor?->id,
            'action' => $action,
            'subject_type' => $subject ? 'User' : null,
            'subject_id' => $subject?->id,
            'metadata' => $meta,
            'ip_address' => '41.'.random_int(96, 110).'.'.random_int(0, 255).'.'.random_int(1, 254),
            'created_at' => $at,
        ])->save();
    }
}
