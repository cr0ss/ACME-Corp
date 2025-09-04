import { defineStore } from 'pinia'
import { ref } from 'vue'
import { donationsApi, type Donation } from '@/services/api'

export const useDonationsStore = defineStore('donations', () => {
  // State
  const donations = ref<Donation[]>([])
  const currentDonation = ref<Donation | null>(null)
  const isLoading = ref(false)
  const error = ref<string | null>(null)
  const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 15,
    total: 0,
  })

  // Actions
  async function fetchMyDonations(params?: any) {
    isLoading.value = true
    error.value = null

    try {
      const response = await donationsApi.getMyDonations(params)
      donations.value = response.data
      pagination.value = response.meta
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to fetch donations'
    } finally {
      isLoading.value = false
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
      return response
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to process donation'
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
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to fetch donation'
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
      return receipt
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to download receipt'
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
      .filter(d => d.status === 'completed')
      .reduce((sum, d) => sum + d.amount, 0)
  }

  function getDonationsByStatus(status: Donation['status']) {
    return donations.value.filter(d => d.status === status)
  }

  function getDonationStats() {
    const completed = donations.value.filter(d => d.status === 'completed')
    const total = completed.reduce((sum, d) => sum + d.amount, 0)
    const average = completed.length > 0 ? total / completed.length : 0
    
    return {
      total_donations: completed.length,
      total_amount: total,
      average_amount: average,
      campaigns_supported: new Set(completed.map(d => d.campaign.id)).size,
    }
  }

  return {
    // State
    donations,
    currentDonation,
    isLoading,
    error,
    pagination,
    
    // Actions
    fetchMyDonations,
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
