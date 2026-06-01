<template>
  <div class="min-h-screen bg-canvas-50 pt-20">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <h1 class="text-3xl font-serif font-bold text-canvas-900 mb-8">Account Settings</h1>

      <div class="space-y-8">
        <div class="bg-white rounded-2xl p-6 shadow-sm">
          <h2 class="text-lg font-semibold text-canvas-900 mb-4">Profile</h2>
          <form @submit.prevent="updateProfile" class="space-y-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
              <div>
                <label class="block text-sm font-medium text-canvas-700 mb-1">Name</label>
                <input
                  v-model="profileForm.name"
                  type="text"
                  class="w-full px-4 py-2.5 rounded-xl border border-canvas-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-canvas-700 mb-1">Email</label>
                <input
                  :value="user?.email"
                  type="email"
                  disabled
                  class="w-full px-4 py-2.5 rounded-xl border border-canvas-200 bg-canvas-50 text-canvas-500"
                />
              </div>
            </div>
            <div>
              <label class="block text-sm font-medium text-canvas-700 mb-1">Bio</label>
              <textarea
                v-model="profileForm.bio"
                rows="3"
                class="w-full px-4 py-2.5 rounded-xl border border-canvas-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition resize-none"
                placeholder="Tell us about yourself and your art..."
              />
            </div>

            <div v-if="isPainter">
              <label class="block text-sm font-medium text-canvas-700 mb-1">Specialties</label>
              <div class="flex flex-wrap gap-2 mb-2">
                <span
                  v-for="(spec, idx) in profileForm.specialties"
                  :key="idx"
                  class="inline-flex items-center gap-1 px-3 py-1 bg-primary-50 text-primary-700 rounded-full text-sm"
                >
                  {{ spec }}
                  <button type="button" @click="profileForm.specialties.splice(idx, 1)" class="hover:text-red-500">
                    <Icon name="mdi:close" class="w-3 h-3" />
                  </button>
                </span>
              </div>
              <input
                v-model="newSpecialty"
                @keydown.enter.prevent="addSpecialty"
                type="text"
                class="w-full px-4 py-2.5 rounded-xl border border-canvas-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition"
                placeholder="e.g., Oil Painting, Portraiture (press Enter)"
              />
            </div>

            <div v-if="isPainter" class="grid grid-cols-1 sm:grid-cols-2 gap-5">
              <div>
                <label class="block text-sm font-medium text-canvas-700 mb-1">Location</label>
                <input
                  v-model="profileForm.location"
                  type="text"
                  class="w-full px-4 py-2.5 rounded-xl border border-canvas-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition"
                  placeholder="City, Country"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-canvas-700 mb-1">Website</label>
                <input
                  v-model="profileForm.website"
                  type="url"
                  class="w-full px-4 py-2.5 rounded-xl border border-canvas-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition"
                  placeholder="https://yoursite.com"
                />
              </div>
            </div>

            <div v-if="isPainter">
              <label class="block text-sm font-medium text-canvas-700 mb-1">Artistic Style</label>
              <input
                v-model="profileForm.style"
                type="text"
                class="w-full px-4 py-2.5 rounded-xl border border-canvas-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition"
                placeholder="e.g., Contemporary Realism, Abstract Expressionism"
              />
            </div>

            <div class="flex items-center gap-4">
              <button
                type="submit"
                :disabled="savingProfile"
                class="px-6 py-2.5 bg-primary-500 hover:bg-primary-600 disabled:opacity-50 text-white font-medium rounded-xl transition"
              >
                {{ savingProfile ? 'Saving...' : 'Save Profile' }}
              </button>
              <span v-if="profileSuccess" class="text-green-600 text-sm">Profile saved!</span>
            </div>
          </form>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm">
          <h2 class="text-lg font-semibold text-canvas-900 mb-4">Change Password</h2>
          <form @submit.prevent="changePassword" class="space-y-5">
            <div>
              <label class="block text-sm font-medium text-canvas-700 mb-1">Current Password</label>
              <input
                v-model="passwordForm.current_password"
                type="password"
                required
                class="w-full px-4 py-2.5 rounded-xl border border-canvas-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition"
              />
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
              <div>
                <label class="block text-sm font-medium text-canvas-700 mb-1">New Password</label>
                <input
                  v-model="passwordForm.password"
                  type="password"
                  required
                  minlength="8"
                  class="w-full px-4 py-2.5 rounded-xl border border-canvas-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-canvas-700 mb-1">Confirm New Password</label>
                <input
                  v-model="passwordForm.password_confirmation"
                  type="password"
                  required
                  class="w-full px-4 py-2.5 rounded-xl border border-canvas-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition"
                />
              </div>
            </div>
            <div class="flex items-center gap-4">
              <button
                type="submit"
                :disabled="savingPassword"
                class="px-6 py-2.5 bg-primary-500 hover:bg-primary-600 disabled:opacity-50 text-white font-medium rounded-xl transition"
              >
                {{ savingPassword ? 'Saving...' : 'Update Password' }}
              </button>
              <span v-if="passwordSuccess" class="text-green-600 text-sm">Password updated!</span>
            </div>
          </form>
        </div>

        <div v-if="isPainter" class="bg-white rounded-2xl p-6 shadow-sm">
          <h2 class="text-lg font-semibold text-canvas-900 mb-4">Stripe Payments</h2>
          <div class="flex items-center gap-4">
            <div :class="[
              'w-10 h-10 rounded-full flex items-center justify-center',
              stripeConnected ? 'bg-green-100' : 'bg-canvas-100'
            ]">
              <Icon
                :name="stripeConnected ? 'mdi:check-circle' : 'mdi:credit-card-outline'"
                :class="['w-5 h-5', stripeConnected ? 'text-green-600' : 'text-canvas-400']"
              />
            </div>
            <div class="flex-1">
              <p class="font-medium text-canvas-900">
                {{ stripeConnected ? 'Stripe Connected' : 'Stripe Not Connected' }}
              </p>
              <p class="text-sm text-canvas-500">
                {{ stripeConnected ? 'Your account is ready to receive payments.' : 'Connect your Stripe account to start selling.' }}
              </p>
            </div>
            <button
              v-if="!stripeConnected"
              @click="connectStripe"
              :disabled="connectingStripe"
              class="px-5 py-2 bg-primary-500 hover:bg-primary-600 disabled:opacity-50 text-white font-medium rounded-xl transition text-sm"
            >
              {{ connectingStripe ? 'Connecting...' : 'Connect' }}
            </button>
          </div>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm border border-red-100">
          <h2 class="text-lg font-semibold text-red-700 mb-4">Danger Zone</h2>
          <p class="text-sm text-canvas-600 mb-4">Permanently delete your account and all associated data. This action cannot be undone.</p>
          <button
            @click="deleteAccount"
            class="px-5 py-2 bg-red-500 hover:bg-red-600 text-white font-medium rounded-xl transition text-sm"
          >
            Delete Account
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({ middleware: 'auth' })

const config = useRuntimeConfig()
const apiUrl = config.public.apiUrl
const { user, isPainter, fetchUser } = useAuth()

const newSpecialty = ref('')
const savingProfile = ref(false)
const profileSuccess = ref(false)
const savingPassword = ref(false)
const passwordSuccess = ref(false)
const connectingStripe = ref(false)
const stripeConnected = ref(false)

const profileForm = reactive({
  name: user.value?.name || '',
  bio: user.value?.bio || '',
  specialties: user.value?.painter_profile?.specialties || [],
  location: user.value?.painter_profile?.location || '',
  website: user.value?.painter_profile?.website || '',
  style: user.value?.painter_profile?.style?.join(', ') || '',
})

const passwordForm = reactive({
  current_password: '',
  password: '',
  password_confirmation: '',
})

onMounted(async () => {
  if (isPainter.value) {
    try {
      const res = await $fetch<{ connected: boolean }>(`${apiUrl}/stripe/connect/status`, {
        credentials: 'include',
      })
      stripeConnected.value = res.connected
    } catch {}
  }
})

const addSpecialty = () => {
  const spec = newSpecialty.value.trim()
  if (spec && !profileForm.specialties.includes(spec)) {
    profileForm.specialties.push(spec)
  }
  newSpecialty.value = ''
}

const updateProfile = async () => {
  savingProfile.value = true
  profileSuccess.value = false
  try {
    await $fetch(`${apiUrl}/profile`, {
      method: 'PUT',
      body: {
        name: profileForm.name,
        bio: profileForm.bio,
        specialties: profileForm.specialties,
        location: profileForm.location,
        website: profileForm.website,
        style: profileForm.style ? profileForm.style.split(',').map(s => s.trim()).filter(Boolean) : [],
      },
      credentials: 'include',
    })
    await fetchUser()
    profileSuccess.value = true
    setTimeout(() => profileSuccess.value = false, 3000)
  } catch (e: any) {
    alert(e.data?.message || 'Failed to update profile')
  } finally {
    savingProfile.value = false
  }
}

const changePassword = async () => {
  if (passwordForm.password !== passwordForm.password_confirmation) {
    alert('Passwords do not match')
    return
  }
  savingPassword.value = true
  passwordSuccess.value = false
  try {
    await $fetch(`${apiUrl}/password`, {
      method: 'PUT',
      body: passwordForm,
      credentials: 'include',
    })
    passwordForm.current_password = ''
    passwordForm.password = ''
    passwordForm.password_confirmation = ''
    passwordSuccess.value = true
    setTimeout(() => passwordSuccess.value = false, 3000)
  } catch (e: any) {
    alert(e.data?.message || 'Failed to update password')
  } finally {
    savingPassword.value = false
  }
}

const connectStripe = async () => {
  connectingStripe.value = true
  try {
    const res = await $fetch<{ url: string }>(`${apiUrl}/stripe/connect/onboard`, {
      method: 'POST',
      credentials: 'include',
    })
    window.location.href = res.url
  } catch (e: any) {
    alert(e.data?.message || 'Failed to connect Stripe')
  } finally {
    connectingStripe.value = false
  }
}

const deleteAccount = async () => {
  if (!confirm('Are you absolutely sure? This cannot be undone.')) return
  if (!confirm('Type DELETE to confirm account deletion.')) return
  // Would call delete endpoint
  alert('Account deletion is not yet implemented. Please contact support.')
}

useHead({ title: 'Settings — Atelier' })
</script>
