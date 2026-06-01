<template>
  <div class="min-h-screen flex items-center justify-center bg-canvas-50 pt-16">
    <div class="w-full max-w-md p-8 bg-white rounded-2xl shadow-lg">
      <h1 class="text-3xl font-serif text-center mb-2">Join Atelier</h1>
      <p class="text-center text-canvas-500 mb-8">Create your free account</p>

      <div v-if="error" class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm">
        {{ error }}
      </div>

      <form @submit.prevent="handleRegister" class="space-y-6">
        <div>
          <label class="block text-sm font-medium text-canvas-700 mb-2">I am a...</label>
          <div class="grid grid-cols-2 gap-3">
            <button
              type="button"
              @click="form.role = 'painter'"
              :class="[
                'py-3 px-4 rounded-xl border-2 transition font-medium',
                form.role === 'painter'
                  ? 'border-primary-500 bg-primary-50 text-primary-700'
                  : 'border-canvas-200 text-canvas-600 hover:border-canvas-300'
              ]"
            >
              Painter
            </button>
            <button
              type="button"
              @click="form.role = 'collector'"
              :class="[
                'py-3 px-4 rounded-xl border-2 transition font-medium',
                form.role === 'collector'
                  ? 'border-primary-500 bg-primary-50 text-primary-700'
                  : 'border-canvas-200 text-canvas-600 hover:border-canvas-300'
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
            class="w-full px-4 py-2.5 rounded-xl border border-canvas-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition"
            placeholder="Your full name"
          />
        </div>

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
            minlength="8"
            class="w-full px-4 py-2.5 rounded-xl border border-canvas-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition"
            placeholder="At least 8 characters"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-canvas-700 mb-2">Confirm Password</label>
          <input
            v-model="form.password_confirmation"
            type="password"
            required
            class="w-full px-4 py-2.5 rounded-xl border border-canvas-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition"
            placeholder="Repeat your password"
          />
        </div>

        <button
          type="submit"
          :disabled="loading"
          class="w-full py-3 bg-primary-500 hover:bg-primary-600 disabled:opacity-50 text-white font-semibold rounded-xl transition"
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
const error = ref('')

const form = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  role: 'painter' as 'painter' | 'collector',
})

const handleRegister = async () => {
  if (form.password !== form.password_confirmation) {
    error.value = 'Passwords do not match'
    return
  }
  loading.value = true
  error.value = ''
  try {
    await register(form)
    navigateTo('/')
  } catch (e: any) {
    const errors = e?.data?.errors
    if (errors) {
      error.value = Object.values(errors).flat().join(' ')
    } else {
      error.value = e?.data?.message || e?.message || 'Registration failed. Please try again.'
    }
  } finally {
    loading.value = false
  }
}
</script>
