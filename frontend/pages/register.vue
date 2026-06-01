<template>
  <div class="min-h-screen flex items-center justify-center bg-canvas-50 pt-16">
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
          <input v-model="form.name" type="text" required class="input" />
        </div>

        <div>
          <label class="block text-sm font-medium text-canvas-700 mb-2">Email</label>
          <input v-model="form.email" type="email" required class="input" />
        </div>

        <div>
          <label class="block text-sm font-medium text-canvas-700 mb-2">Password</label>
          <input v-model="form.password" type="password" required minlength="8" class="input" />
        </div>

        <div>
          <label class="block text-sm font-medium text-canvas-700 mb-2">Confirm Password</label>
          <input v-model="form.password_confirmation" type="password" required class="input" />
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
    console.error('Registration failed:', error)
  } finally {
    loading.value = false
  }
}
</script>
