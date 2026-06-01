<template>
  <div class="min-h-screen flex items-center justify-center bg-canvas-50 pt-16">
    <div class="w-full max-w-md p-8 bg-white rounded-2xl shadow-lg">
      <h1 class="text-3xl font-serif text-center mb-8">Welcome Back</h1>

      <form @submit.prevent="handleLogin" class="space-y-6">
        <div>
          <label class="block text-sm font-medium text-canvas-700 mb-2">Email</label>
          <input
            v-model="form.email"
            type="email"
            required
            class="input"
            placeholder="you@example.com"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-canvas-700 mb-2">Password</label>
          <input
            v-model="form.password"
            type="password"
            required
            class="input"
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
    console.error('Login failed:', error)
  } finally {
    loading.value = false
  }
}
</script>
