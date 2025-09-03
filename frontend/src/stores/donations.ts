import { defineStore } from 'pinia'
import { ref } from 'vue'
import { donationsApi, type Donation, type DonationStats } from '@/services/api'

export const useDonationsStore = defineStore('donations', () => {
  // State
  const donations = ref<Donation[]>([])
  const currentDonation = ref<Donation | null>(null)
  const stats = ref<DonationStats | null>(null)
  const isLoading = ref(false)
  const isLoadingStats = ref(false)
  const error = ref<string | null>(null)
  const statsError = ref<string | null>(null)
  const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 15,
    total: 0,
  })

  // Actions
  async function fetchMyDonations(params?: Record<string, unknown>) {
    isLoading.value = true
    error.value = null

    try {
      const response = await donationsApi.getMyDonations(params)
      donations.value = response.data || []
      pagination.value = {
        current_page: response.current_page || 1,
        last_page: response.last_page || 1,
        per_page: response.per_page || 15,
        total: response.total || 0,
      }
    } catch (err: unknown) {
      const errorMessage = err instanceof Error ? err.message : 'Failed to fetch donations'
      error.value = errorMessage
      // Reset to default values on error
      donations.value = []
      pagination.value = {
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 0,
      }
    } finally {
      isLoading.value = false
    }
  }

  async function fetchStats() {
    isLoadingStats.value = true
    statsError.value = null

    try {
      stats.value = await donationsApi.getStats()
    } catch (err: unknown) {
      const errorMessage =
        err instanceof Error ? err.message : 'Failed to fetch donation statistics'
      statsError.value = errorMessage
      // Reset to default values on error
      stats.value = {
        total_donated: 0,
        total_donations: 0,
        campaigns_supported: 0,
        avg_donation: 0,
        pending_donations: 0,
        failed_donations: 0,
      }
    } finally {
      isLoadingStats.value = false
    }
  }

  async function createDonation(data: {
    campaign_id: number
    amount: number
    payment_method: string
    anonymous?: boolean
    message?: string
  }) {
    isLoading.value = true
    error.value = null

    try {
      const response = await donationsApi.create(data)
      donations.value.unshift(response.donation)

      // Refresh stats after successful donation
      fetchStats()

      return response
    } catch (err: unknown) {
      const errorMessage = err instanceof Error ? err.message : 'Failed to process donation'
      error.value = errorMessage
      throw err
    } finally {
      isLoading.value = false
    }
  }

  async function fetchDonation(id: number) {
    isLoading.value = true
    error.value = null

    try {
      const donation = await donationsApi.getById(id)
      currentDonation.value = donation
      return donation
    } catch (err: unknown) {
      const errorMessage = err instanceof Error ? err.message : 'Failed to fetch donation'
      error.value = errorMessage
      throw err
    } finally {
      isLoading.value = false
    }
  }

  async function downloadReceipt(id: number) {
    isLoading.value = true
    error.value = null

    try {
      const receipt = await donationsApi.getReceipt(id)
      
      // Create download link
      const url = window.URL.createObjectURL(receipt.data)
      const link = document.createElement('a')
      link.href = url
      link.download = receipt.filename || `receipt-${id}.pdf`
      link.click()
      
      // Clean up
      window.URL.revokeObjectURL(url)
      
      return receipt
    } catch (err: unknown) {
      const errorMessage = err instanceof Error ? err.message : 'Failed to download receipt'
      error.value = errorMessage
      console.error('Error downloading receipt:', err)
      throw err
    } finally {
      isLoading.value = false
    }
  }

  function clearError() {
    error.value = null
  }

  function clearCurrentDonation() {
    currentDonation.value = null
  }

  // Helper functions
  function getTotalDonated() {
    return donations.value
      .filter((d) => d.status === 'completed')
      .reduce((sum, d) => sum + d.amount, 0)
  }

  function getDonationsByStatus(status: Donation['status']) {
    return donations.value.filter((d) => d.status === status)
  }

  function getDonationStats() {
    const completed = donations.value.filter((d) => d.status === 'completed')
    const total = completed.reduce((sum, d) => sum + d.amount, 0)
    const average = completed.length > 0 ? total / completed.length : 0

    return {
      total_donations: completed.length,
      total_amount: total,
      average_amount: average,
      campaigns_supported: new Set(completed.map((d) => d.campaign.id)).size,
    }
  }

  return {
    // State
    donations,
    currentDonation,
    stats,
    isLoading,
    isLoadingStats,
    error,
    statsError,
    pagination,

    // Actions
    fetchMyDonations,
    fetchStats,
    createDonation,
    fetchDonation,
    downloadReceipt,
    clearError,
    clearCurrentDonation,

    // Helpers
    getTotalDonated,
    getDonationsByStatus,
    getDonationStats,
  }
})
