# Skill 01: Project Setup

## Overview
Scaffold the Atelier SaaS platform with Laravel 11 backend (API mode) and Nuxt 3 frontend (Vue 3 + TypeScript).

## Prerequisites
- PHP 8.3+
- Composer 2.8+
- Node.js v24+
- Laragon (local dev server)
- MySQL 8.0+ (via Laragon)

---

## Step 1: Create Laravel Backend

```bash
cd C:\laragon\www\atelier
composer create-project laravel/laravel backend --prefer-dist
cd backend
```

### Install Dependencies

```bash
# Authentication
composer require laravel/sanctum

# Permissions & Roles
composer require spatie/laravel-permission

# Image Processing
composer require intervention/image

# PDF Generation
composer require barryvdh/laravel-dompdf

# Payments
composer require laravel/cashier

# Filesystem (S3/R2)
composer require league/flysystem-aws-s3-v3

# Queue Jobs
composer require laravel/horizon

# Activity Log (optional)
composer require spatie/laravel-activitylog
```

### Publish Configs

```bash
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan vendor:publish --provider="Barryvdh\DomPDF\DomPDFServiceProvider"
```

---

## Step 2: Create Nuxt Frontend

```bash
cd C:\laragon\www\atelier
npx nuxi@latest init frontend
cd frontend
```

### Install Dependencies

```bash
# UI Framework
npm install -D tailwindcss @tailwindcss/vite
npm install @nuxtjs/google-fonts

# State Management
npm install @pinia/nuxt pinia

# Auth
npm install @sidebase/nuxt-auth

# HTTP Client (auto-imported)
npm install ofetch

# Image Optimization
npm install @nuxt/image

# Charts (for Grisaille histogram)
npm install chart.js vue-chartjs

# Icons
npm install @nuxt/icon

# Toast Notifications
npm install @nuxtjs/toast

# Carousel (featured painters)
npm install @nuxtjs/embla-carousel

# Masonry Layout (gallery)
npm install vue-masonry-wall
```

---

## Step 3: Environment Configuration

### Backend `.env`

```env
APP_NAME=Atelier
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://atelier.test

FRONTEND_URL=http://localhost:3000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=atelier
DB_USERNAME=root
DB_PASSWORD=

SANCTUM_STATEFUL_DOMAINS=localhost:3000,localhost:8000
SESSION_DOMAIN=.atelier.test

# S3 / Cloudflare R2
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=auto
AWS_BUCKET=atelier-media
AWS_URL=
AWS_ENDPOINT=

# Stripe
STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...

# Stripe Connect (Marketplace)
STRIPE_CONNECT_CLIENT_ID=ca_...

# Mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailgun.org
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@atelier.com
MAIL_FROM_NAME=Atelier

# Queue
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

### Frontend `.env`

```env
NUXT_PUBLIC_API_URL=http://atelier.test/api
NUXT_PUBLIC_APP_URL=http://localhost:3000
NUXT_STRIPE_KEY=pk_test_...
```

---

## Step 4: Folder Structure

### Backend Structure

```
backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Api/
│   │   │       ├── AuthController.php
│   │   │       ├── PainterController.php
│   │   │       ├── PaintingController.php
│   │   │       ├── VideoController.php
│   │   │       ├── OrderController.php
│   │   │       ├── GrisailleController.php
│   │   │       └── AdminController.php
│   │   ├── Middleware/
│   │   │   ├── EnsurePainter.php
│   │   │   └── EnsureAdmin.php
│   │   └── Requests/
│   ├── Models/
│   │   ├── User.php
│   │   ├── PainterProfile.php
│   │   ├── Painting.php
│   │   ├── PaintingImage.php
│   │   ├── Video.php
│   │   ├── Order.php
│   │   ├── GrisailleAnalysis.php
│   │   └── Project.php
│   ├── Services/
│   │   ├── GrisailleService.php
│   │   ├── StripeConnectService.php
│   │   ├── PaymentService.php
│   │   ├── PaletteService.php
│   │   └── GlazingService.php
│   ├── Policies/
│   ├── Jobs/
│   │   ├── GenerateGrisaillePdf.php
│   │   ├── ProcessVideoThumbnail.php
│   │   └── SendPurchaseReceipt.php
│   └── Notifications/
├── config/
│   ├── services.php (Stripe keys)
│   └── permission.php
├── database/
│   └── migrations/
├── routes/
│   ├── api.php
│   └── web.php
└── storage/
    └── app/
        └── paintings/
```

### Frontend Structure

```
frontend/
├── components/
│   ├── auth/
│   │   ├── LoginForm.vue
│   │   ├── RegisterForm.vue
│   │   └── ForgotPassword.vue
│   ├── grisaille/
│   │   ├── ImageUploader.vue
│   │   ├── ValuePreview.vue
│   │   ├── ValueHistogram.vue
│   │   ├── PaletteRecommendation.vue
│   │   ├── GlazingSuggestions.vue
│   │   └── PdfDownload.vue
│   ├── gallery/
│   │   ├── PaintingGrid.vue
│   │   ├── PaintingCard.vue
│   │   ├── PaintingLightbox.vue
│   │   └── PaintingFilters.vue
│   ├── shop/
│   │   ├── VideoCard.vue
│   │   ├── VideoPlayer.vue
│   │   └── PurchaseButton.vue
│   ├── painter/
│   │   ├── PainterProfile.vue
│   │   ├── PainterGallery.vue
│   │   └── PainterProjects.vue
│   └── layout/
│       ├── Navbar.vue
│       ├── Footer.vue
│       └── Sidebar.vue
├── composables/
│   ├── useAuth.ts
│   ├── useApi.ts
│   ├── useGrisaille.ts
│   └── useStripe.ts
├── middleware/
│   ├── auth.ts
│   ├── painter.ts
│   └── admin.ts
├── pages/
│   ├── index.vue
│   ├── login.vue
│   ├── register.vue
│   ├── grisaille.vue
│   ├── dashboard/
│   │   ├── index.vue
│   │   ├── paintings.vue
│   │   ├── videos.vue
│   │   ├── projects.vue
│   │   ├── orders.vue
│   │   └── settings.vue
│   ├── painter/
│   │   └── [slug]/
│   │       ├── index.vue
│   │       ├── gallery.vue
│   │       ├── videos.vue
│   │       └── projects.vue
│   ├── shop/
│   │   ├── index.vue
│   │   └── [id].vue
│   └── admin/
│       ├── index.vue
│       ├── users.vue
│       └── paintings.vue
├── plugins/
│   ├── stripe.client.ts
│   └── toast.ts
├── stores/
│   ├── auth.ts
│   ├── painter.ts
│   └── cart.ts
├── assets/
│   └── css/
│       └── main.css
├── app.vue
├── nuxt.config.ts
└── tailwind.config.ts
```

---

## Step 5: CORS Configuration

### Backend `config/cors.php`

```php
<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [env('FRONTEND_URL', 'http://localhost:3000')],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
```

---

## Step 6: Sanctum SPA Configuration

### Backend `config/sanctum.php`

```php
<?php

return [
    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', sprintf(
        '%s%s',
        'localhost,localhost:3000,localhost:8000,127.0.0.1,127.0.0.1:8000,::1',
        env('APP_URL') ? ','.parse_url(env('APP_URL'), PHP_URL_HOST) : ''
    ))),
    'guard' => ['web'],
    'expiration' => null,
    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', ''),
    'middleware' => [
        'authenticate_session' => Laravel\Sanctum\Http\Middleware\AuthenticateSession::class,
        'encrypt_cookies' => Illuminate\Cookie\Middleware\EncryptCookies::class,
        'validate_csrf_token' => Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
    ],
];
```

---

## Step 7: Nuxt Config

### Frontend `nuxt.config.ts`

```typescript
export default defineNuxtConfig({
  devtools: { enabled: true },

  modules: [
    '@nuxtjs/tailwindcss',
    '@pinia/nuxt',
    '@sidebase/nuxt-auth',
    '@nuxtjs/google-fonts',
    '@nuxt/icon',
    '@nuxtjs/toast',
    '@nuxtjs/embla-carousel',
    '@nuxt/image',
  ],

  runtimeConfig: {
    public: {
      apiUrl: process.env.NUXT_PUBLIC_API_URL || 'http://atelier.test/api',
      appUrl: process.env.NUXT_PUBLIC_APP_URL || 'http://localhost:3000',
      stripeKey: process.env.NUXT_STRIPE_KEY,
    },
  },

  auth: {
    provider: {
      type: 'laravel',
      endpoints: {
        signIn: { path: '/login', method: 'post' },
        signOut: { path: '/logout', method: 'post' },
        getSession: { path: '/user', method: 'get' },
      },
      token: {
        signInResponseKeyPointer: ['/token'],
        maxAgeInSeconds: 60 * 60 * 24 * 7, // 7 days
      },
    },
  },

  tailwindcss: {
    config: {
      theme: {
        extend: {
          colors: {
            primary: {
              50: '#fdf8f0',
              100: '#f9eddb',
              200: '#f2d7b0',
              300: '#e9bb7c',
              400: '#df9a46',
              500: '#d68222',
              600: '#c46a18',
              700: '#a35116',
              800: '#844119',
              900: '#6c3618',
            },
            canvas: {
              50: '#fafaf7',
              100: '#f5f4ed',
              200: '#e8e5d8',
              300: '#d6d0bb',
              400: '#c1b799',
              500: '#b0a480',
              600: '#9a8d6a',
              700: '#817457',
              800: '#6a5f4a',
              900: '#574f3f',
            },
          },
          fontFamily: {
            serif: ['Playfair Display', 'Georgia', 'serif'],
            sans: ['Inter', 'system-ui', 'sans-serif'],
          },
        },
      },
    },
  },

  googleFonts: {
    families: {
      'Playfair Display': [400, 500, 600, 700],
      'Inter': [300, 400, 500, 600, 700],
    },
  },

  compatibilityDate: '2025-01-01',
})
```

---

## Step 8: Local Development

### Start Backend (Laragon)
```bash
cd C:\laragon\www\atelier\backend
php artisan serve
# Runs on http://atelier.test (Laragon auto-virtualizes)
```

### Start Frontend
```bash
cd C:\laragon\www\atelier\frontend
npm run dev
# Runs on http://localhost:3000
```

### Database Setup
```bash
# Create database via Laragon MySQL
cd C:\laragon\www\atelier\backend
php artisan migrate
php artisan db:seed
```

---

## Verification Checklist

- [ ] Laravel serves on `http://atelier.test`
- [ ] Nuxt serves on `http://localhost:3000`
- [ ] Database connection works
- [ ] Sanctum CSRF cookie loads
- [ ] CORS allows frontend to backend requests
- [ ] Tailwind CSS compiles correctly
- [ ] All modules load without errors

---

## Next Steps

Proceed to [Skill 02: Auth System](./02-auth-system.md) to implement authentication.
