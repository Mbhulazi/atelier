# Skill 02: Authentication System

## Overview
Implement full authentication with Laravel Sanctum (cookie-based SPA auth) and role-based access (Painter/Collector/Admin).

## Dependencies
- Laravel Sanctum (installed in Skill 01)
- Spatie Laravel Permission (installed in Skill 01)

---

## Step 1: User Model Update

### `app/Models/User.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'bio',
        'slug',
        'role',
        'stripe_account_id',
        'stripe_mode',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            if (!$user->slug) {
                $user->slug = self::generateSlug($user->name);
            }
        });
    }

    public static function generateSlug($name, $excludeId = null)
    {
        $slug = \Str::slug($name);
        $original = $slug;
        $count = 1;

        $query = static::where('slug', $slug);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        while ($query->exists()) {
            $slug = $original . '-' . $count;
            $query = static::where('slug', $slug);
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }
            $count++;
        }

        return $slug;
    }

    public function painterProfile()
    {
        return $this->hasOne(PainterProfile::class);
    }

    public function paintings()
    {
        return $this->hasMany(Painting::class);
    }

    public function videos()
    {
        return $this->hasMany(Video::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'buyer_id');
    }

    public function sales()
    {
        return $this->hasMany(Order::class, 'painter_id');
    }

    public function grisailleAnalyses()
    {
        return $this->hasMany(GrisailleAnalysis::class);
    }

    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    public function getPublicUrlAttribute()
    {
        return "/painter/{$this->slug}";
    }
}
```

---

## Step 2: Auth Controller

### `app/Http/Controllers/Api/AuthController.php`

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => ['required', 'confirmed', Password::min(8)],
            'role' => 'required|in:collector,painter',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
        ]);

        $user->assignRole($validated['role']);

        if ($validated['role'] === 'painter') {
            $user->painterProfile()->create([
                'slug' => $user->slug,
            ]);
        }

        Auth::login($user);

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'user' => $user->load('roles'),
            'token' => $token,
        ], 201);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            return response()->json([
                'message' => 'Invalid credentials',
            ], 401);
        }

        $user = Auth::user();
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'user' => $user->load('roles'),
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        Auth::logout();

        return response()->json(['message' => 'Logged out']);
    }

    public function user(Request $request)
    {
        return response()->json([
            'user' => $request->user()->load('roles', 'painterProfile'),
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'bio' => 'sometimes|string|max:1000',
            'avatar' => 'sometimes|image|max:2048',
        ]);

        if ($request->hasFile('avatar')) {
            $validated['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $user->update($validated);

        return response()->json(['user' => $user]);
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        if (!Hash::check($request->current_password, $request->user()->password)) {
            return response()->json(['message' => 'Current password is incorrect'], 422);
        }

        $request->user()->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json(['message' => 'Password updated']);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = \Password::sendResetLink(
            $request->only('email')
        );

        return $status === \Password::RESET_LINK_SENT
            ? response()->json(['message' => 'Reset link sent'])
            : response()->json(['message' => 'Unable to send reset link'], 500);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $status = \Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();
            }
        );

        return $status === \Password::PASSWORD_RESET
            ? response()->json(['message' => 'Password reset successfully'])
            : response()->json(['message' => 'Invalid reset token'], 422);
    }
}
```

---

## Step 3: Middleware

### `app/Http/Middleware/EnsurePainter.php`

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsurePainter
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->user() || !$request->user()->hasRole('painter')) {
            return response()->json(['message' => 'Painter access required'], 403);
        }

        return $next($request);
    }
}
```

### `app/Http/Middleware/EnsureAdmin.php`

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureAdmin
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->user() || !$request->user()->hasRole('admin')) {
            return response()->json(['message' => 'Admin access required'], 403);
        }

        return $next($request);
    }
}
```

### `app/Http/Middleware/VerifyStripeWebhook.php`

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Stripe\Webhook;

class VerifyStripeWebhook
{
    public function handle(Request $request, Closure $next)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');

        try {
            Webhook::constructEvent(
                $payload,
                $sigHeader,
                config('services.stripe.webhook_secret')
            );
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        return $next($request);
    }
}
```

---

## Step 4: Routes

### `routes/api.php`

```php
<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PainterController;
use App\Http\Controllers\Api\PaintingController;
use App\Http\Controllers\Api\VideoController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\GrisailleController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// Public painter profiles
Route::get('/painters', [PainterController::class, 'index']);
Route::get('/painters/{slug}', [PainterController::class, 'show']);
Route::get('/painters/{slug}/paintings', [PainterController::class, 'paintings']);
Route::get('/painters/{slug}/videos', [PainterController::class, 'videos']);

// Public paintings
Route::get('/paintings', [PaintingController::class, 'index']);
Route::get('/paintings/{id}', [PaintingController::class, 'show']);

// Public videos (shop)
Route::get('/videos', [VideoController::class, 'index']);
Route::get('/videos/{id}', [VideoController::class, 'show']);

// CSRF cookie for Sanctum
Route::get('/sanctum/csrf-cookie', fn () => response()->json(['status' => 'ok']));

// Authenticated routes
Route::middleware('auth:sanctum')->group(function () {
    // User
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::put('/password', [AuthController::class, 'changePassword']);

    // Grisaille Tool
    Route::post('/grisaille/analyze', [GrisailleController::class, 'analyze']);
    Route::get('/grisaille/history', [GrisailleController::class, 'history']);
    Route::get('/grisaille/{id}/pdf', [GrisailleController::class, 'downloadPdf']);

    // Painter Dashboard (painter role only)
    Route::middleware('role:painter')->prefix('dashboard')->group(function () {
        Route::get('/paintings', [PaintingController::class, 'dashboardIndex']);
        Route::post('/paintings', [PaintingController::class, 'store']);
        Route::put('/paintings/{id}', [PaintingController::class, 'update']);
        Route::delete('/paintings/{id}', [PaintingController::class, 'destroy']);

        Route::get('/videos', [VideoController::class, 'dashboardIndex']);
        Route::post('/videos', [VideoController::class, 'store']);
        Route::put('/videos/{id}', [VideoController::class, 'update']);
        Route::delete('/videos/{id}', [VideoController::class, 'destroy']);

        Route::get('/orders', [OrderController::class, 'painterOrders']);
        Route::get('/earnings', [OrderController::class, 'earnings']);
    });

    // Orders & Payments
    Route::post('/orders/checkout', [OrderController::class, 'checkout']);
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);

    // Stripe Connect
    Route::post('/stripe/connect/onboard', [PainterController::class, 'stripeOnboard']);
    Route::get('/stripe/connect/status', [PainterController::class, 'stripeStatus']);
    Route::post('/stripe/connect/refresh', [PainterController::class, 'stripeRefresh']);
});

// Stripe Webhooks
Route::post('/webhooks/stripe', [OrderController::class, 'webhook'])
    ->middleware(\App\Http\Middleware\VerifyStripeWebhook::class);

// Admin routes
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/users', [\App\Http\Controllers\Api\AdminController::class, 'users']);
    Route::put('/users/{id}/role', [\App\Http\Controllers\Api\AdminController::class, 'updateUserRole']);
    Route::delete('/users/{id}', [\App\Http\Controllers\Api\AdminController::class, 'deleteUser']);
    Route::get('/stats', [\App\Http\Controllers\Api\AdminController::class, 'stats']);
});
```

---

## Step 5: Nuxt Auth Integration

### `composables/useAuth.ts`

```typescript
import { useAuthStore } from '~/stores/auth'

export const useAuth = () => {
  const store = useAuthStore()
  const config = useRuntimeConfig()

  const login = async (email: string, password: string, remember = false) => {
    await $fetch(`${config.public.apiUrl}/login`, {
      method: 'POST',
      body: { email, password, remember },
      withCredentials: true,
    })
    await store.fetchUser()
  }

  const register = async (data: {
    name: string
    email: string
    password: string
    password_confirmation: string
    role: 'painter' | 'collector'
  }) => {
    await $fetch(`${config.public.apiUrl}/register`, {
      method: 'POST',
      body: data,
      withCredentials: true,
    })
    await store.fetchUser()
  }

  const logout = async () => {
    await $fetch(`${config.public.apiUrl}/logout`, {
      method: 'POST',
      withCredentials: true,
    })
    store.clearUser()
    navigateTo('/login')
  }

  const fetchUser = async () => {
    await store.fetchUser()
  }

  return {
    login,
    register,
    logout,
    fetchUser,
    user: computed(() => store.user),
    isAuthenticated: computed(() => store.isAuthenticated),
    isPainter: computed(() => store.user?.role === 'painter'),
    isAdmin: computed(() => store.user?.role === 'admin'),
  }
}
```

### `stores/auth.ts`

```typescript
import { defineStore } from 'pinia'

interface User {
  id: number
  name: string
  email: string
  avatar: string | null
  bio: string | null
  slug: string
  role: 'collector' | 'painter' | 'admin'
  roles: Array<{ id: number; name: string }>
  painterProfile?: any
}

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null as User | null,
    loading: false,
  }),

  getters: {
    isAuthenticated: (state) => !!state.user,
    isPainter: (state) => state.user?.role === 'painter',
    isAdmin: (state) => state.user?.role === 'admin',
  },

  actions: {
    async fetchUser() {
      this.loading = true
      try {
        const config = useRuntimeConfig()
        const data = await $fetch<{ user: User }>(`${config.public.apiUrl}/user`, {
          withCredentials: true,
        })
        this.user = data.user
      } catch {
        this.user = null
      } finally {
        this.loading = false
      }
    },

    clearUser() {
      this.user = null
    },
  },
})
```

---

## Step 6: Auth Pages

### `pages/login.vue`

```vue
<template>
  <div class="min-h-screen flex items-center justify-center bg-canvas-50">
    <div class="w-full max-w-md p-8 bg-white rounded-2xl shadow-lg">
      <h1 class="text-3xl font-serif text-center mb-8">Welcome Back</h1>

      <form @submit.prevent="handleLogin" class="space-y-6">
        <div>
          <label class="block text-sm font-medium text-canvas-700 mb-2">Email</label>
          <input
            v-model="form.email"
            type="email"
            required
            class="w-full px-4 py-3 border border-canvas-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
            placeholder="you@example.com"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-canvas-700 mb-2">Password</label>
          <input
            v-model="form.password"
            type="password"
            required
            class="w-full px-4 py-3 border border-canvas-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
            placeholder="••••••••"
          />
        </div>

        <div class="flex items-center justify-between">
          <label class="flex items-center">
            <input v-model="form.remember" type="checkbox" class="rounded border-canvas-300" />
            <span class="ml-2 text-sm text-canvas-600">Remember me</span>
          </label>
          <NuxtLink to="/forgot-password" class="text-sm text-primary-600 hover:text-primary-700">
            Forgot password?
          </NuxtLink>
        </div>

        <button
          type="submit"
          :disabled="loading"
          class="w-full py-3 bg-primary-600 text-white rounded-lg font-medium hover:bg-primary-700 transition disabled:opacity-50"
        >
          {{ loading ? 'Signing in...' : 'Sign In' }}
        </button>
      </form>

      <p class="mt-6 text-center text-canvas-600">
        Don't have an account?
        <NuxtLink to="/register" class="text-primary-600 font-medium hover:text-primary-700">
          Create one
        </NuxtLink>
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({ layout: false })

const { login } = useAuth()
const loading = ref(false)

const form = reactive({
  email: '',
  password: '',
  remember: false,
})

const handleLogin = async () => {
  loading.value = true
  try {
    await login(form.email, form.password, form.remember)
    navigateTo('/dashboard')
  } catch (error: any) {
    // Handle error
  } finally {
    loading.value = false
  }
}
</script>
```

### `pages/register.vue`

```vue
<template>
  <div class="min-h-screen flex items-center justify-center bg-canvas-50">
    <div class="w-full max-w-md p-8 bg-white rounded-2xl shadow-lg">
      <h1 class="text-3xl font-serif text-center mb-8">Join Atelier</h1>

      <form @submit.prevent="handleRegister" class="space-y-6">
        <div>
          <label class="block text-sm font-medium text-canvas-700 mb-2">I am a...</label>
          <div class="grid grid-cols-2 gap-3">
            <button
              type="button"
              @click="form.role = 'painter'"
              :class="[
                'py-3 px-4 rounded-lg border-2 transition font-medium',
                form.role === 'painter'
                  ? 'border-primary-600 bg-primary-50 text-primary-700'
                  : 'border-canvas-300 text-canvas-600 hover:border-canvas-400'
              ]"
            >
              Painter
            </button>
            <button
              type="button"
              @click="form.role = 'collector'"
              :class="[
                'py-3 px-4 rounded-lg border-2 transition font-medium',
                form.role === 'collector'
                  ? 'border-primary-600 bg-primary-50 text-primary-700'
                  : 'border-canvas-300 text-canvas-600 hover:border-canvas-400'
              ]"
            >
              Collector
            </button>
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-canvas-700 mb-2">Full Name</label>
          <input
            v-model="form.name"
            type="text"
            required
            class="w-full px-4 py-3 border border-canvas-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-canvas-700 mb-2">Email</label>
          <input
            v-model="form.email"
            type="email"
            required
            class="w-full px-4 py-3 border border-canvas-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-canvas-700 mb-2">Password</label>
          <input
            v-model="form.password"
            type="password"
            required
            minlength="8"
            class="w-full px-4 py-3 border border-canvas-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-canvas-700 mb-2">Confirm Password</label>
          <input
            v-model="form.password_confirmation"
            type="password"
            required
            class="w-full px-4 py-3 border border-canvas-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
          />
        </div>

        <button
          type="submit"
          :disabled="loading"
          class="w-full py-3 bg-primary-600 text-white rounded-lg font-medium hover:bg-primary-700 transition disabled:opacity-50"
        >
          {{ loading ? 'Creating account...' : 'Create Account' }}
        </button>
      </form>

      <p class="mt-6 text-center text-canvas-600">
        Already have an account?
        <NuxtLink to="/login" class="text-primary-600 font-medium hover:text-primary-700">
          Sign in
        </NuxtLink>
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({ layout: false })

const { register } = useAuth()
const loading = ref(false)

const form = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  role: 'painter' as 'painter' | 'collector',
})

const handleRegister = async () => {
  loading.value = true
  try {
    await register(form)
    navigateTo(form.role === 'painter' ? '/dashboard' : '/explore')
  } catch (error: any) {
    // Handle error
  } finally {
    loading.value = false
  }
}
</script>
```

---

## Step 7: Middleware Protection

### `middleware/auth.ts`

```typescript
export default defineNuxtRouteMiddleware(() => {
  const { isAuthenticated, loading } = useAuth()

  if (!loading && !isAuthenticated.value) {
    return navigateTo('/login')
  }
})
```

### `middleware/painter.ts`

```typescript
export default defineNuxtRouteMiddleware(() => {
  const { isAuthenticated, isPainter, loading } = useAuth()

  if (!loading && !isAuthenticated.value) {
    return navigateTo('/login')
  }

  if (!loading && !isPainter.value) {
    return navigateTo('/dashboard')
  }
})
```

---

## Verification Checklist

- [ ] Registration creates user with role
- [ ] Login returns user and sets cookie
- [ ] `/user` endpoint returns authenticated user
- [ ] Logout clears session
- [ ] Painter role required for dashboard routes
- [ ] Admin role required for admin routes
- [ ] Password reset flow works
- [ ] Slug generated automatically for painters

---

## Next Steps

Proceed to [Skill 03: Database Schema](./03-database-schema.md) for remaining migrations.
