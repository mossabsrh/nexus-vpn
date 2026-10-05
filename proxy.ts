import { NextResponse } from 'next/server'
import type { NextRequest } from 'next/server'

const backendUrl = (process.env.NEXT_PUBLIC_API_URL || 'http://127.0.0.1:8000').replace(/\/$/, '')

/**
 * Server-side gate for account pages: the session cookie is checked against Laravel before the
 * page is sent, so non-admins never receive the admin UI. Laravel still authorizes every API call.
 */
export async function proxy(request: NextRequest) {
  const isAdminArea = request.nextUrl.pathname.startsWith('/admin')
  const cookie = request.headers.get('cookie')

  if (!cookie) {
    return NextResponse.redirect(new URL('/', request.url))
  }

  try {
    const response = await fetch(`${backendUrl}/api/me`, {
      headers: { cookie, accept: 'application/json' },
      cache: 'no-store',
      signal: AbortSignal.timeout(4000),
    })

    if (response.status === 401 || response.status === 419) {
      return NextResponse.redirect(new URL('/', request.url))
    }

    if (!response.ok) {
      // Backend trouble: admin fails closed; the dashboard renders and shows its own error state.
      return isAdminArea ? NextResponse.redirect(new URL('/', request.url)) : NextResponse.next()
    }

    if (isAdminArea) {
      const { user } = await response.json()
      if (user?.role !== 'admin') {
        return NextResponse.redirect(new URL('/', request.url))
      }
    }

    return NextResponse.next()
  } catch {
    return isAdminArea ? NextResponse.redirect(new URL('/', request.url)) : NextResponse.next()
  }
}

export const config = {
  matcher: ['/admin/:path*', '/dashboard/:path*'],
}
