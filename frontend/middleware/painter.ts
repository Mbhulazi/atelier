export default defineNuxtRouteMiddleware(() => {
  const { isAuthenticated, isPainter, loading } = useAuth()

  if (!loading && !isAuthenticated.value) {
    return navigateTo('/login')
  }

  if (!loading && !isPainter.value) {
    return navigateTo('/dashboard')
  }
})
