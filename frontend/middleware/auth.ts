export default defineNuxtRouteMiddleware(() => {
  const { isAuthenticated, loading } = useAuth()

  if (!loading && !isAuthenticated.value) {
    return navigateTo('/login')
  }
})
