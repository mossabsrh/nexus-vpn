export const apiBaseUrl = '/backend-api'

let csrfPromise: Promise<void> | null = null

const SAFE_METHODS = new Set(['GET', 'HEAD', 'OPTIONS'])

function readXsrfToken() {
	const value = document.cookie
		.split('; ')
		.find((cookie) => cookie.startsWith('XSRF-TOKEN='))
		?.split('=')[1]

	return value ? decodeURIComponent(value) : null
}

async function ensureCsrfCookie(force = false) {
	if (!force && readXsrfToken()) return

	if (!csrfPromise) {
		csrfPromise = fetch(`${apiBaseUrl}/csrf-cookie`, {
			credentials: 'include',
		}).then((response) => {
			if (!response.ok) {
				throw new Error('Unable to initialize secure session.')
			}
		}).finally(() => {
			csrfPromise = null
		})
	}

	return csrfPromise
}

/**
 * Fetch from the Laravel API through the Next.js rewrite (same origin, session cookie).
 * Every state-changing request carries the XSRF token; an expired token (419) is refreshed once.
 */
export async function apiFetch(path: string, init: RequestInit = {}, timeoutMs = 10000): Promise<Response> {
	const method = init.method?.toUpperCase() ?? 'GET'
	const needsCsrf = !SAFE_METHODS.has(method)

	const send = async (forceCsrf: boolean) => {
		if (needsCsrf) {
			await ensureCsrfCookie(forceCsrf)
		}

		const headers = new Headers(init.headers)
		headers.set('Accept', 'application/json')
		if (init.body && !headers.has('Content-Type')) {
			headers.set('Content-Type', 'application/json')
		}

		const xsrfToken = readXsrfToken()
		if (xsrfToken) {
			headers.set('X-XSRF-TOKEN', xsrfToken)
		}

		const controller = new AbortController()
		const timeout = window.setTimeout(() => controller.abort(), timeoutMs)

		try {
			return await fetch(`${apiBaseUrl}${path}`, {
				...init,
				headers,
				credentials: 'include',
				signal: init.signal ?? controller.signal,
			})
		} finally {
			window.clearTimeout(timeout)
		}
	}

	const response = await send(false)

	if (response.status === 419 && needsCsrf) {
		return send(true)
	}

	return response
}

/**
 * Parse a JSON error body into a single user-facing message.
 */
export async function apiErrorMessage(response: Response, fallback: string) {
	try {
		const data = await response.json()
		const firstValidationError = data.errors ? Object.values(data.errors as Record<string, string[]>)[0]?.[0] : null

		return firstValidationError || data.message || data.error || fallback
	} catch {
		return fallback
	}
}
