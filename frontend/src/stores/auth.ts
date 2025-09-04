import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authApi, type User, type LoginCredentials } from '@/services/api'

export const useAuthStore = defineStore('auth', () => {
  // State
  const user = ref<User | null>(null)
  const token = ref<string | null>(localStorage.getItem('auth_token'))
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  // Getters
  const isAuthenticated = computed(() => !!token.value && !!user.value)
  const isAdmin = computed(() => user.value?.is_admin || false)

  // Actions
  async function login(credentials: LoginCredentials) {
    isLoading.value = true
    error.value = null

    try {
      const response = await authApi.login(credentials)

      user.value = response.user
      token.value = response.token

      // Store token in localStorage
      localStorage.setItem('auth_token', response.token)

      return response
    } catch (err: unknown) {
      const errorMessage = err instanceof Error ? err.message : 'Login failed'
      error.value = errorMessage
      throw err
    } finally {
      isLoading.value = false
    }
  }

  async function logout() {
    isLoading.value = true

    try {
      if (token.value) {
        await authApi.logout()
      }
    } catch {
      console.warn('Logout API call failed, but continuing with local logout')
    } finally {
      // Clear state regardless of API call success
      user.value = null
      token.value = null
      localStorage.removeItem('auth_token')
      isLoading.value = false
    }
  }

  async function fetchUser() {
    if (!token.value) return

    isLoading.value = true
    error.value = null

    try {
      const response = await authApi.getUser()
      user.value = response.user
    } catch (err: unknown) {
      const errorMessage = err instanceof Error ? err.message : 'Failed to fetch user'
      error.value = errorMessage
      // If token is invalid, clear it
      if (err && typeof err === 'object' && 'response' in err && err.response && typeof err.response === 'object' && 'status' in err.response && err.response.status === 401) {
        logout()
      }
    } finally {
      isLoading.value = false
    }
  }

  async function updateProfile(data: Partial<User>) {
    isLoading.value = true
    error.value = null

    try {
      const response = await authApi.updateProfile(data)
      user.value = response.user
      return response
    } catch (err: unknown) {
      const errorMessage = err instanceof Error ? err.message : 'Failed to update profile'
      error.value = errorMessage
      throw err
    } finally {
      isLoading.value = false
    }
  }

  async function updatePassword(data: {
    current_password: string
    password: string
    password_confirmation: string
  }) {
    isLoading.value = true
    error.value = null

    try {
      const response = await authApi.updatePassword(data)
      return response
    } catch (err: unknown) {
      const errorMessage = err instanceof Error ? err.message : 'Failed to update password'
      error.value = errorMessage
      throw err
    } finally {
      isLoading.value = false
    }
  }

  function clearError() {
    error.value = null
  }

  // Initialize auth state on store creation
  function initialize() {
    if (token.value) {
      fetchUser()
    }
  }

  return {
    // State
    user,
    token,
    isLoading,
    error,

    // Getters
    isAuthenticated,
    isAdmin,

    // Actions
    login,
    logout,
    fetchUser,
    updateProfile,
    updatePassword,
    clearError,
    initialize,
  }
})
