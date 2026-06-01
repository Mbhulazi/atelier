export const useApi = () => {
  const config = useRuntimeConfig()
  const apiUrl = config.public.apiUrl

  const authFetch = async <T = any>(url: string, options: any = {}): Promise<T> => {
    const token = import.meta.client ? localStorage.getItem('auth_token') : null
    const headers: Record<string, string> = {
      ...options.headers,
    }
    if (token) {
      headers['Authorization'] = `Bearer ${token}`
    }
    if (options.body instanceof FormData) {
      delete headers['Content-Type']
    } else if (!options.body || typeof options.body === 'string') {
      headers['Content-Type'] = 'application/json'
      headers['Accept'] = 'application/json'
    }

    return $fetch<T>(url.startsWith('http') ? url : `${apiUrl}${url}`, {
      ...options,
      headers,
    })
  }

  return { authFetch, apiUrl }
}
