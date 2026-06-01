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
