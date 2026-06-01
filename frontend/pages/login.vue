<template>
  <div class="min-h-screen flex items-center justify-center bg-canvas-50 pt-16">
    <div class="w-full max-w-md p-8 bg-white rounded-2xl shadow-lg">
      <h1 class="text-3xl font-serif text-center mb-2">Welcome Back</h1>
      <p class="text-center text-canvas-500 mb-8">Sign in to your Atelier account</p>

      <div v-if="error" class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm">
        {{ error }}
      </div>

      <form @submit.prevent="handleLogin" class="space-y-6">
        <div>
          <label class="block text-sm font-medium text-canvas-700 mb-2">Email</label>
          <input
            v-model="form.email"
            type="email"
            required
            class="w-full px-4 py-2.5 rounded-xl border border-canvas-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition"
            placeholder="you@example.com"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-canvas-700 mb-2">Password</label>
          <input
            v-model="form.password"
            type="password"
            required
            class="w-full px-4 py-2.5 rounded-xl border border-canvas-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition"
            placeholder="Enter your password"
          />
        </div>

        <div class="flex items-center justify-between">
          <label class="flex items-center">
            <input v-model="form.remember" type="checkbox" class="w-4 h-4 rounded border-canvas-300 text-primary-500 focus:ring-primary-500" />
            <span class="ml-2 text-sm text-canvas-600">Remember me</span>
          </label>
        </div>

        <button
          type="submit"
          :disabled="loading"
          class="w-full py-3 bg-primary-500 hover:bg-primary-600 disabled:opacity-50 text-white font-semibold rounded-xl transition"
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

      <div class="mt-6 pt-6 border-t border-canvas-100 text-center">
        <p class="text-xs text-canvas-400">Test: maria@atelier.com / password</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({ layout: false })

const { login } = useAuth()
const loading = ref(false)
const error = ref('')

const form = reactive({
  email: '',
  password: '',
  remember: false,
})

const handleLogin = async () => {
  loading.value = true
  error.value = ''
  try {
    await login(form.email, form.password, form.remember)
    navigateTo('/')
  } catch (e: any) {
    error.value = e?.data?.message || e?.message || 'Login failed. Please check your credentials.'
  } finally {
    loading.value = false
  }
}
</script>
