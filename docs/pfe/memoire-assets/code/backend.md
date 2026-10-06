# Extraits de code — Backend (Laravel 12)

> Extraits commentés, prêts à insérer dans le mémoire. Chaque bloc est accompagné d'une explication en français.
> Les chemins sont relatifs à `nexus-vpn/`.

## 1. Sécurité du webhook de paiement (HMAC) — `app/Services/ChargilyGateway.php`

```php
public function verifySignature(string $rawBody, string $signature): bool
{
    $secretKey = $this->secretKey();
    if ($secretKey === '') {
        Log::critical('Chargily webhook received but CHARGILY_SECRET_KEY is not configured.');
        return false;
    }
    return hash_equals(hash_hmac('sha256', $rawBody, $secretKey), $signature);
}
```

**Explication.** Le webhook de Chargily est authentifié par une signature **HMAC-SHA256** calculée sur le **corps brut** de la requête avec la clé secrète. La comparaison utilise `hash_equals` (temps constant) pour empêcher les attaques temporelles. Sans cette vérification, n'importe qui pourrait simuler un paiement.

## 2. Activation idempotente et contrôle montant/devise — `ChargilyGateway::applyCheckoutStatus()`

```php
if ($status === 'paid') {
    $mismatch = match (true) {
        ! is_numeric($checkout['amount'] ?? null)
            || (int) $checkout['amount'] !== (int) $payment->amount => 'amount_mismatch',
        strtolower((string) ($checkout['currency'] ?? '')) !== strtolower((string) $payment->currency) => 'currency_mismatch',
        default => null,
    };
    if ($mismatch !== null) {
        Log::critical('Chargily paid checkout does not match the payment record; not activating.', [...]);
        $payment->update(['error_code' => $mismatch]);
        return;
    }
    if (! $payment->markSucceeded($checkout)) {
        $this->flagPaidAfterFailure($payment->refresh());
    }
    return;
}
```

**Explication.** Un statut `paid` n'active l'abonnement que si le **montant** (présent et égal) **et la devise** concordent avec l'enregistrement local (risque R-10). `markSucceeded()` est idempotent (verrou + mise à jour conditionnelle), donc un webhook rejoué n'active jamais deux fois. Si Chargily paie un paiement déjà en échec, l'incident est tracé pour l'administrateur (`paid_after_failure`).

## 3. Réception du webhook — `app/Http/Controllers/Api/PaymentController.php`

```php
public function webhook(Request $request): JsonResponse
{
    $signature = (string) $request->header('signature', '');
    $rawBody = $request->getContent();
    if ($signature === '') {
        return response()->json(['message' => 'Missing signature'], 400);
    }
    if (! $this->chargily->verifySignature($rawBody, $signature)) {
        return response()->json(['message' => 'Invalid signature'], 403);
    }
    // ... décodage, recherche du paiement par provider_reference ...
    $this->chargily->applyCheckoutStatus($payment, $event === 'checkout.paid' ? 'paid' : Str::after($event, 'checkout.'), $checkout);
    return response()->json(['status' => 'success']);
}
```

**Explication.** La route `POST /api/webhooks/chargily` est **hors du groupe web** (pas de session ni de CSRF) : elle est authentifiée uniquement par la signature HMAC. Un checkout inconnu renvoie 404 pour que Chargily réessaie.

## 4. Fermeture de compte (anonymisation) — `app/Models/User.php`

```php
public function closeAccount(?User $actor = null): void
{
    $previousEmail = $this->email;
    DB::transaction(function () use ($actor, $previousEmail): void {
        $this->currentSubscription()?->cancel('account_deleted');
        VpnCredential::revokeFor($this, 'account_deleted');
        $this->teams()->detach();
        $this->forceFill([
            'name' => 'Deleted account',
            'email' => 'deleted+'.$this->id.'@deleted.invalid',
            'email_verified_at' => null,
            'password' => Hash::make(Str::random(64)),
            'remember_token' => null,
        ])->save();
        AuditLog::record('account.deleted', null, [
            'user_id' => $this->id,
            'previous_domain' => Str::after($previousEmail, '@'),
            'by' => ($actor && ! $actor->is($this)) ? 'admin' : 'self',
        ], $actor);
        $this->delete();
    });
    DB::table('password_resets')->where('email', $previousEmail)->delete();
    DB::table('sessions')->where('user_id', $this->id)->delete();
}
```

**Explication.** La suppression de compte **anonymise** les données personnelles (nom, e-mail, mot de passe), libère l'e-mail d'origine, puis effectue un **soft delete**. Les lignes `payments`/`subscriptions` sont conservées pour l'historique de facturation. Cette logique est partagée par la suppression en libre-service (`DELETE /api/account`) et par l'administrateur (`DELETE /api/admin/users/{user}`).

## 5. Provisionnement et révocation VPN — `app/Models/VpnCredential.php`

```php
public static function revokeFor(User $user, string $reason): int
{
    $credentials = $user->vpnCredentials()->where('status', 'active')->get();
    foreach ($credentials as $credential) {
        $credential->update(['status' => 'revoked', 'revoked_at' => now()]);
        AuditLog::record('vpn.credential_revoked', $credential, ['user_id' => $user->id, 'reason' => $reason]);
    }
    return $credentials->count();
}
```

**Explication.** `issueFor()` réutilise l'identifiant actif de l'utilisateur ou génère une **paire de clés Curve25519** (`sodium`) et attribue la prochaine adresse libre du pool `10.66.0.0/16`. La clé privée est stockée **chiffrée**. `revokeFor()` passe les identifiants en `revoked` lorsqu'il ne reste aucun accès (annulation, expiration, suppression).

## 6. Contrôle d'accès administrateur — `app/Http/Middleware/EnsureUserIsAdmin.php`

```php
public function handle(Request $request, Closure $next): Response
{
    abort_unless($request->user()?->isAdmin(), 403);
    return $next($request);
}
```

**Explication.** Middleware `admin` appliqué à tout le groupe `/api/admin/*`. Il renvoie **403** si l'utilisateur n'est pas administrateur. Côté frontend, `proxy.ts` protège aussi la page `/admin` (fail-closed).

## 7. Suppression d'un utilisateur par l'admin — `app/Http/Controllers/Api/AdminController.php`

```php
public function destroyUser(Request $request, User $user): JsonResponse
{
    if ($user->isAdmin() && User::query()->where('role', 'admin')->count() <= 1) {
        return response()->json(['message' => 'You cannot delete the last administrator.'], 422);
    }
    if ($user->is($request->user())) {
        return response()->json(['message' => 'You cannot delete your own account from the admin panel.'], 422);
    }
    if ($user->ownedTeams()->exists()) {
        return response()->json(['message' => 'This user owns a team subscription...'], 422);
    }
    $user->closeAccount($request->user());
    return response()->json(['message' => 'User account deleted.']);
}
```

**Explication.** L'administrateur ne peut pas supprimer **le dernier administrateur**, **lui-même**, ni **un propriétaire d'équipe** (sa suppression couperait l'accès de toute l'équipe). L'action réutilise `closeAccount()` et trace l'administrateur comme acteur dans le journal d'audit.

## 8. Définition des routes API — `routes/api.php` (extrait)

```php
Route::post('webhooks/chargily', [PaymentController::class, 'webhook'])->middleware('throttle:webhooks');

Route::middleware('auth')->group(function () {
    Route::put('account/email', [AuthController::class, 'updateEmail'])->middleware('throttle:account');
    Route::put('account/password', [AuthController::class, 'updatePassword'])->middleware('throttle:account');
    Route::delete('account', [AuthController::class, 'deleteAccount'])->middleware('throttle:account');
    // ...
    Route::prefix('admin')->middleware(['auth', 'admin'])->group(function () {
        Route::get('users', [AdminController::class, 'users']);
        Route::patch('users/{user}', [AdminController::class, 'updateUser']);
        Route::delete('users/{user}', [AdminController::class, 'destroyUser']);
        // plans, testimonials, audit-logs, ...
    });
});
```

**Explication.** Les routes sont protégées par couches : `auth` (session), `admin` (rôle), et des **limiteurs de débit** nommés (`throttle:account`, `throttle:webhooks`…). Le webhook est volontairement hors `auth`.
