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
