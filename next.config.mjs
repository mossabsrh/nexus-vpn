/** @type {import('next').NextConfig} */
const configuredApiUrl = process.env.NEXT_PUBLIC_API_URL?.replace(/\/$/, '')

if (process.env.VERCEL && !configuredApiUrl) {
  throw new Error('Set NEXT_PUBLIC_API_URL to the deployed Laravel service URL in Vercel.')
}

const backendUrl = configuredApiUrl || 'http://127.0.0.1:8000'
const isDev = process.env.NODE_ENV === 'development'

// No nonces: the app has no third-party scripts, and all API calls go through the same-origin rewrite.
const contentSecurityPolicy = [
  "default-src 'self'",
  `script-src 'self' 'unsafe-inline'${isDev ? " 'unsafe-eval'" : ''}`,
  "style-src 'self' 'unsafe-inline'",
  "img-src 'self' blob: data:",
  "font-src 'self' data:",
  "connect-src 'self'",
  "object-src 'none'",
  "base-uri 'self'",
  "form-action 'self'",
  "frame-ancestors 'none'",
].join('; ')

const nextConfig = {
  images: {
    unoptimized: true,
  },
  async headers() {
    return [
      {
        source: '/(.*)',
        headers: [
          { key: 'Content-Security-Policy', value: contentSecurityPolicy },
          { key: 'X-Frame-Options', value: 'DENY' },
          { key: 'X-Content-Type-Options', value: 'nosniff' },
          { key: 'Referrer-Policy', value: 'strict-origin-when-cross-origin' },
          { key: 'Permissions-Policy', value: 'camera=(), microphone=(), geolocation=()' },
        ],
      },
    ]
  },
  async rewrites() {
    return [
      {
        source: '/backend-api/:path*',
        destination: `${backendUrl}/api/:path*`,
      },
    ]
  },
}

export default nextConfig
