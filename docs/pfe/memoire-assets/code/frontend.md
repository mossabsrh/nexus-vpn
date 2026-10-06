# Extraits de code — Frontend (Next.js 16 + React)

> Extraits commentés, prêts à insérer dans le mémoire. Chemins relatifs à la racine du dépôt.

## 1. Client API et gestion du CSRF — `lib/api.ts`

```ts
export const apiBaseUrl = '/backend-api'
const SAFE_METHODS = new Set(['GET', 'HEAD', 'OPTIONS'])

function readXsrfToken() {
  const value = document.cookie.split('; ')
    .find((c) => c.startsWith('XSRF-TOKEN='))?.split('=')[1]
  return value ? decodeURIComponent(value) : null
}

export async function apiFetch(path: string, init: RequestInit = {}, timeoutMs = 10000): Promise<Response> {
  const method = init.method?.toUpperCase() ?? 'GET'
  const needsCsrf = !SAFE_METHODS.has(method)
  const send = async (forceCsrf: boolean) => {
    if (needsCsrf) await ensureCsrfCookie(forceCsrf)
    const headers = new Headers(init.headers)
    headers.set('Accept', 'application/json')
    const xsrfToken = readXsrfToken()
    if (xsrfToken) headers.set('X-XSRF-TOKEN', xsrfToken)
    return fetch(`${apiBaseUrl}${path}`, { ...init, headers, credentials: 'include' })
  }
  // ... rejoue une fois en cas de 419 (jeton CSRF expiré) ...
}
```

**Explication.** Toutes les requêtes passent par la **réécriture `/backend-api`** (même origine → cookie de session partagé). Les requêtes modifiantes récupèrent le cookie `XSRF-TOKEN` et le renvoient dans l'en-tête `X-XSRF-TOKEN`. En cas de **419** (jeton expiré), la requête est **rejouée une fois** après rafraîchissement du jeton.

## 2. Authentification — `components/auth-provider.tsx`

```tsx
const login = async (email: string, password: string) => {
  const response = await apiFetch('/auth/login', {
    method: 'POST', body: JSON.stringify({ email, password }),
  })
  if (!response.ok) return { success: false, error: await apiErrorMessage(response, 'Invalid email or password.') }
  applyUser((await response.json()).user)
  return { success: true }
}

const deleteAccount = async (currentPassword: string) => {
  const response = await apiFetch('/account', {
    method: 'DELETE', body: JSON.stringify({ current_password: currentPassword }),
  })
  if (!response.ok) return { success: false, error: await apiErrorMessage(response, 'Failed to delete your account.') }
  applyUser(null) // le compte est supprimé côté serveur : on efface la session locale
  return { success: true, message: (await response.json()).message }
}
```

**Explication.** Le `AuthProvider` (contexte React) centralise l'état d'authentification et expose `login`, `signup`, `updateEmail`, `updatePassword`, `deleteAccount`, `logout`, etc. Chaque méthode renvoie `{ success, error? }` pour un affichage uniforme des erreurs.

## 3. Zone de danger : suppression de compte — `components/account-menu.tsx`

```tsx
const deleteConfirmed =
  deletePassword.length > 0 &&
  deleteEmailConfirm.trim().toLowerCase() === (auth.user?.email ?? '').toLowerCase()

const handleDeleteAccount = async () => {
  if (!deleteConfirmed) { setErrorMessage('Enter your password and type your email to confirm.'); return }
  const result = await auth.deleteAccount(deletePassword)
  if (!result.success) { setErrorMessage(result.error ?? '...'); return }
  setSettingsOpen(false)
  router.push('/'); router.refresh()
}
```

**Explication.** La suppression exige **le mot de passe actuel ET la saisie exacte de l'e-mail** : le bouton « Permanently delete » reste désactivé tant que les deux ne concordent pas. C'est une protection contre les suppressions accidentelles.

## 4. Suppression d'un utilisateur côté admin — `components/admin/accounts.tsx`

```tsx
const confirmDelete = async () => {
  if (!pendingDelete) return
  const result = await adminRequest(`/admin/users/${pendingDelete.id}`, 'DELETE')
  if (!result.ok) { setActionError(result.error); setPendingDelete(null); return }
  const email = pendingDelete.email
  setPendingDelete(null)
  await list.reload()           // recharge la liste depuis l'API
  setNotice(`${email} has been permanently deleted.`)
}
```

```tsx
<button type="button" disabled={isSelf} onClick={onDelete}
  title={isSelf ? 'You cannot delete your own account here' : undefined}
  className="rounded-md border border-destructive/40 ... text-destructive">
  Delete
</button>
```

**Explication.** Chaque ligne du tableau d'administration a un bouton **Supprimer** (désactivé sur sa propre ligne). Un encart de confirmation rappelle les conséquences, puis la liste est **rechargée** après suppression. Les erreurs renvoyées par l'API (dernier admin, propriétaire d'équipe…) sont affichées telles quelles.

## 5. Contrôle d'accès des pages — `proxy.ts`

```ts
// Rewrite /backend-api/* -> API Laravel ; protège /admin et /dashboard selon la session et le rôle.
// /admin : fail-closed (redirige si la vérification échoue) ; /dashboard : accès vérifié via /api/me.
```

**Explication.** Le `proxy.ts` (middleware Next.js) fait deux choses : il **réécrit** les appels `/backend-api/*` vers le serveur Laravel (même origine) et il **garde** les pages privées `/admin` (fail-closed) et `/dashboard` en s'appuyant sur la session et le rôle renvoyés par `/api/me`.
