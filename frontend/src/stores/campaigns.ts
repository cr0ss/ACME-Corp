import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import {
  campaignsApi,
  categoriesApi,
  apiService,
  type Campaign,
  type CampaignCategory,
  type CreateCampaign,
  type UpdateCampaign,
} from '@/services/api'

export const useCampaignsStore = defineStore('campaigns', () => {
  // State
  const campaigns = ref<Campaign[]>([])
  const categories = ref<CampaignCategory[]>([])
  const currentCampaign = ref<Campaign | null>(null)
  const trendingCampaigns = ref<Campaign[]>([])
  const endingSoonCampaigns = ref<Campaign[]>([])
  const isLoading = ref(false)
  const error = ref<string | null>(null)
  const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 15,
    total: 0,
  })

  // Campaign stats by status
  const campaignStats = ref({
    active: 0,
    completed: 0,
    cancelled: 0,
    draft: 0,
    total: 0,
  })

  // Getters
  const activeCampaigns = computed(() =>
    campaigns.value.filter((campaign) => campaign.status === 'active'),
  )

  const featuredCampaigns = computed(() =>
    campaigns.value.filter((campaign) => campaign.featured && campaign.status === 'active'),
  )

  const activeCampaignsCount = computed(() => {
    return campaignStats.value.active || 0
  })

  // Actions
  async function fetchCampaigns(params?: Record<string, unknown>) {
    isLoading.value = true
    error.value = null

    try {
      const response = await campaignsApi.getAll(params)

      // All endpoints now return consistent format with data and meta
      campaigns.value = response.data
      pagination.value = {
        current_page: response.current_page || 1,
        last_page: response.last_page || 1,
        per_page: response.per_page || response.data.length,
        total: response.total || response.data.length,
      }
    } catch (err: unknown) {
      const errorMessage = err instanceof Error ? err.message : 'Failed to fetch campaigns'
      error.value = errorMessage
    } finally {
      isLoading.value = false
    }
  }

  async function fetchCampaign(id: number) {
    isLoading.value = true
    error.value = null

    try {
      const response = await campaignsApi.getById(id)
      currentCampaign.value = response.campaign
      return response
    } catch (err: unknown) {
      const errorMessage = err instanceof Error ? err.message : 'Failed to fetch campaign'
      error.value = errorMessage
      throw err
    } finally {
      isLoading.value = false
    }
  }

  async function createCampaign(data: CreateCampaign) {
    isLoading.value = true
    error.value = null

    try {
      const response = await campaignsApi.create(data)
      campaigns.value.unshift(response.campaign)
      return response
    } catch (err: unknown) {
      const errorMessage = err instanceof Error ? err.message : 'Failed to create campaign'
      error.value = errorMessage
      throw err
    } finally {
      isLoading.value = false
    }
  }

  async function updateCampaign(id: number, data: UpdateCampaign) {
    isLoading.value = true
    error.value = null

    try {
      const response = await campaignsApi.update(id, data)

      // Update in campaigns list
      const index = campaigns.value.findIndex((c) => c.id === id)
      if (index !== -1) {
        campaigns.value[index] = response.campaign
      }

      // Update current campaign if it matches
      if (currentCampaign.value?.id === id) {
        currentCampaign.value = response.campaign
      }

      return response
    } catch (err: unknown) {
      const errorMessage = err instanceof Error ? err.message : 'Failed to update campaign'
      error.value = errorMessage
      throw err
    } finally {
      isLoading.value = false
    }
  }

  async function deleteCampaign(id: number) {
    isLoading.value = true
    error.value = null

    try {
      await campaignsApi.delete(id)

      // Remove from campaigns list
      campaigns.value = campaigns.value.filter((c) => c.id !== id)

      // Clear current campaign if it matches
      if (currentCampaign.value?.id === id) {
        currentCampaign.value = null
      }
    } catch (err: unknown) {
      const errorMessage = err instanceof Error ? err.message : 'Failed to delete campaign'
      error.value = errorMessage
      throw err
    } finally {
      isLoading.value = false
    }
  }

  async function fetchFeaturedCampaigns() {
    try {
      const response = await campaignsApi.getFeatured()
      campaigns.value = response.data // Store featured campaigns in main campaigns array
    } catch (err: unknown) {
      console.warn('Failed to fetch featured campaigns:', err)
    }
  }

  async function fetchTrendingCampaigns() {
    try {
      const response = await campaignsApi.getTrending()
      trendingCampaigns.value = response.data
    } catch (err: unknown) {
      console.warn('Failed to fetch trending campaigns:', err)
    }
  }

  async function fetchEndingSoonCampaigns() {
    try {
      const response = await campaignsApi.getEndingSoon()
      endingSoonCampaigns.value = response.data
    } catch (err: unknown) {
      console.warn('Failed to fetch ending soon campaigns:', err)
    }
  }

  async function fetchCategories() {
    try {
      categories.value = await categoriesApi.getAll()
    } catch (err: unknown) {
      console.warn('Failed to fetch categories:', err)
    }
  }

  async function fetchCampaignStats() {
    try {
      const response = await apiService.get<{
        active: number
        completed: number
        cancelled: number
        draft: number
        total: number
      }>('/campaigns/stats')
      campaignStats.value = response
    } catch (err: unknown) {
      console.warn('Failed to fetch campaign stats:', err)
    }
  }

  function clearError() {
    error.value = null
  }

  function clearCurrentCampaign() {
    currentCampaign.value = null
  }

  // Search and filter helpers
  function searchCampaigns(query: string) {
    return campaigns.value.filter(
      (campaign) =>
        campaign.title.toLowerCase().includes(query.toLowerCase()) ||
        campaign.description.toLowerCase().includes(query.toLowerCase()),
    )
  }

  function filterCampaignsByCategory(categoryId: number) {
    return campaigns.value.filter((campaign) => campaign.category.id === categoryId)
  }

  function filterCampaignsByStatus(status: Campaign['status']) {
    return campaigns.value.filter((campaign) => campaign.status === status)
  }

  return {
    // State
    campaigns,
    categories,
    currentCampaign,
    trendingCampaigns,
    endingSoonCampaigns,
    isLoading,
    error,
    pagination,
    campaignStats,

    // Getters
    activeCampaigns,
    featuredCampaigns,
    activeCampaignsCount,

    // Actions
    fetchCampaigns,
    fetchCampaign,
    createCampaign,
    updateCampaign,
    deleteCampaign,
    fetchFeaturedCampaigns,
    fetchTrendingCampaigns,
    fetchEndingSoonCampaigns,
    fetchCategories,
    fetchCampaignStats,
    clearError,
    clearCurrentCampaign,
    searchCampaigns,
    filterCampaignsByCategory,
    filterCampaignsByStatus,
  }
})
