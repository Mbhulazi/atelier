import { useAuthStore } from '~/stores/auth'

export const useAuth = () => {
  const store = useAuthStore()
  const config = useRuntimeConfig()

  const login = async (email: string, password: string, remember = false) => {
    const data = await $fetch<{ user: any; token: string }>(`${config.public.apiUrl}/login`, {
      method: 'POST',
      body: { email, password, remember },
      withCredentials: true,
    })
    if (data.token) {
      localStorage.setItem('auth_token', data.token)
    }
    store.user = data.user
  }

  const register = async (data: {
    name: string
    email: string
    password: string
    password_confirmation: string
    role: 'painter' | 'collector'
  }) => {
    const res = await $fetch<{ user: any; token: string }>(`${config.public.apiUrl}/register`, {
      method: 'POST',
      body: data,
      withCredentials: true,
    })
    if (res.token) {
      localStorage.setItem('auth_token', res.token)
    }
    store.user = res.user
  }

  const logout = async () => {
    await $fetch(`${config.public.apiUrl}/logout`, {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${localStorage.getItem('auth_token')}`,
      },
    })
    localStorage.removeItem('auth_token')
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
