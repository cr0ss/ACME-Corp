<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
      <div>
      <h1 class="text-3xl font-bold text-gray-900">Campaign Management</h1>
        <p class="text-gray-600 mt-1">Manage all campaigns across the platform</p>
      </div>
      <div class="flex space-x-3">
        <button @click="exportCampaigns" class="btn-secondary">
          Export Data
        </button>
        <button @click="refreshData" class="btn-primary" :disabled="isLoading">
          <span v-if="isLoading">Refreshing...</span>
          <span v-else>Refresh</span>
        </button>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
      <div class="card text-center">
        <div class="text-3xl font-bold text-blue-600">{{ totalCampaigns }}</div>
        <div class="text-sm text-gray-500">Total Campaigns</div>
      </div>
      <div class="card text-center">
        <div class="text-3xl font-bold text-green-600">{{ activeCampaigns }}</div>
        <div class="text-sm text-gray-500">Active Campaigns</div>
      </div>
      <div class="card text-center">
        <div class="text-3xl font-bold text-yellow-600">{{ draftCampaigns }}</div>
        <div class="text-sm text-gray-500">Draft Campaigns</div>
      </div>
      <div class="card text-center">
        <div class="text-3xl font-bold text-gray-600">{{ featuredCount }}</div>
        <div class="text-sm text-gray-500">Featured Campaigns</div>
      </div>
    </div>

    <!-- Filters and Search -->
    <div class="card">
      <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
          <input
            v-model="filters.search"
            type="text"
            placeholder="Search campaigns..."
            class="input-field"
            @input="debouncedSearch"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
          <select v-model="filters.status" class="input-field" @change="applyFilters">
            <option value="">All Status</option>
            <option value="draft">Draft</option>
            <option value="active">Active</option>
            <option value="completed">Completed</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
          <select v-model="filters.category" class="input-field" @change="applyFilters">
            <option value="">All Categories</option>
            <option v-for="category in categories" :key="category.id" :value="category.id">
              {{ category.icon }} {{ category.name }}
            </option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Featured</label>
          <select v-model="filters.featured" class="input-field" @change="applyFilters">
            <option value="">All</option>
            <option value="true">Featured Only</option>
            <option value="false">Not Featured</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Sort By</label>
          <select v-model="filters.sortBy" class="input-field" @change="applyFilters">
            <option value="created_at_desc">Newest First</option>
            <option value="created_at_asc">Oldest First</option>
            <option value="target_amount_desc">Highest Target</option>
            <option value="current_amount_desc">Most Raised</option>
            <option value="title_asc">Title A-Z</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Campaign Table -->
    <div class="bg-white shadow-sm rounded-lg border border-gray-200 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider w-2/5">Campaign & Creator</th>
              <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider w-1/5">Category & Metrics</th>
              <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider w-1/5">Progress</th>
              <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider w-1/5">Actions</th>
            </tr>
          </thead>
          <tbody class="bg-white">
            <tr v-if="isLoading">
              <td colspan="4" class="px-6 py-12 text-center">
                <div class="flex flex-col items-center">
                  <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mb-3"></div>
                  <p class="text-gray-600 font-medium">Loading campaigns...</p>
                </div>
              </td>
            </tr>
            <tr v-else-if="campaigns.length === 0">
              <td colspan="4" class="px-6 py-12 text-center">
                <div class="flex flex-col items-center">
                  <div class="text-6xl mb-4">📊</div>
                  <h3 class="text-lg font-medium text-gray-900 mb-2">No campaigns found</h3>
                  <p class="text-gray-500">Try adjusting your filters or create your first campaign.</p>
                </div>
              </td>
            </tr>
            <template v-else v-for="campaign in paginatedCampaigns" :key="campaign.id">
              <!-- Row 1: Main Campaign Info -->
              <tr class="border-b border-gray-100 hover:bg-gray-50/50 transition-colors duration-150">
                <!-- Campaign & Creator -->
                <td class="px-6 py-4">
                  <div class="flex items-start space-x-4">
                    <!-- Campaign Avatar -->
                    <div class="flex-shrink-0">
                      <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-bold text-lg">
                        {{ campaign.title.charAt(0) }}
                      </div>
                    </div>
                    <!-- Campaign Details -->
                    <div class="flex-1 min-w-0">
                      <div class="mb-1">
                        <h3 class="text-lg font-semibold text-gray-900 truncate">
                          <span v-if="campaign.featured" class="text-yellow-500 mr-1">⭐</span>{{ campaign.title }}
                        </h3>
                      </div>
                      <p class="text-sm text-gray-600 line-clamp-2 mb-3">{{ campaign.description.substring(0, 120) }}...</p>
                      
                      <!-- Creator Info (inline) -->
                      <div class="flex items-center space-x-3">
                        <div class="w-6 h-6 rounded-full bg-gray-300 flex items-center justify-center text-gray-600 font-medium text-xs">
                          {{ campaign.user.name.charAt(0) }}
                        </div>
                        <div class="text-sm">
                          <span class="font-medium text-gray-900">{{ campaign.user.name }}</span>
                          <span class="text-gray-500 ml-2">{{ campaign.user.email }}</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </td>

                <!-- Category & Metrics -->
                <td class="px-6 py-4">
                  <!-- Category -->
                  <div class="mb-3">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                      <span class="mr-1.5">{{ campaign.category.icon }}</span>
                      {{ campaign.category.name }}
                    </span>
                  </div>
                  
                  <!-- Target & Raised -->
                  <div class="grid grid-cols-1 gap-2">
                    <div>
                      <div class="text-xs text-gray-500">Target</div>
                      <div class="text-lg font-bold text-gray-900">${{ campaign.target_amount.toLocaleString() }}</div>
                    </div>
                    <div>
                      <div class="text-xs text-gray-500">Raised</div>
                      <div class="text-lg font-bold text-green-600">${{ campaign.current_amount.toLocaleString() }}</div>
                    </div>
                  </div>
                </td>

                <!-- Progress -->
                <td class="px-6 py-4">
                  <div class="space-y-2">
                    <div class="flex justify-between text-sm">
                      <span class="font-medium text-gray-900">{{ Math.round(((campaign.current_amount || 0) / (campaign.target_amount || 1)) * 100) }}%</span>
                      <span class="text-gray-500">{{ campaign.donations_count || 0 }} donations</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-3">
                      <div
                        class="h-3 rounded-full transition-all duration-300"
                        :class="((campaign.current_amount || 0) / (campaign.target_amount || 1)) >= 1 ? 'bg-green-500' : 'bg-blue-500'"
                        :style="{ width: Math.min(100, ((campaign.current_amount || 0) / (campaign.target_amount || 1)) * 100) + '%' }"
                      ></div>
                    </div>
                  </div>
                </td>

                <!-- Primary Actions -->
                <td class="px-6 py-4">
                  <div class="flex flex-col space-y-2">
                    <button
                      @click="viewCampaign(campaign)"
                      class="w-full inline-flex items-center justify-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                      <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                      </svg>
                      View
                    </button>
                    <button
                      @click="editCampaign(campaign)"
                      class="w-full inline-flex items-center justify-center px-3 py-2 border border-blue-300 text-sm font-medium rounded-md text-blue-700 bg-blue-50 hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                      <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                      </svg>
                      Edit
                    </button>
                    <button
                      @click="toggleFeatured(campaign)"
                      class="w-full inline-flex items-center justify-center px-3 py-2 border text-sm font-medium rounded-md focus:outline-none focus:ring-2"
                      :class="campaign.featured 
                        ? 'border-yellow-300 text-yellow-700 bg-yellow-50 hover:bg-yellow-100 focus:ring-yellow-500' 
                        : 'border-gray-300 text-gray-600 bg-white hover:bg-gray-50 focus:ring-gray-500'"
                      :disabled="isUpdating === campaign.id"
                    >
                      <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                      </svg>
                      {{ campaign.featured ? 'Unfeature' : 'Feature' }}
                    </button>
                    <button
                      @click="deleteCampaign(campaign)"
                      class="w-full inline-flex items-center justify-center px-3 py-2 border border-red-300 text-sm font-medium rounded-md text-red-700 bg-red-50 hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-500"
                      :disabled="campaign.donations_count && campaign.donations_count > 0"
                      :title="campaign.donations_count && campaign.donations_count > 0 ? 'Cannot delete campaign with donations' : 'Delete campaign'"
                    >
                      <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                      </svg>
                      Delete
                    </button>
                  </div>
                </td>
              </tr>

              <!-- Row 2: Status & Management Actions -->
              <tr class="border-b-2 border-gray-200 bg-gray-50/30">
                <!-- Metadata -->
                <td class="px-6 py-3">
                  <div class="flex items-center space-x-4 text-xs text-gray-500">
                    <span>ID: {{ campaign.id }}</span>
                    <span>•</span>
                    <span>Created: {{ formatDate(campaign.created_at) }}</span>
                  </div>
                </td>

                <!-- Status Controls -->
                <td class="px-6 py-3">
                  <div class="flex items-center space-x-3">
                    <!-- Current Status Badge -->
                    <span
                      class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                      :class="getStatusClass(campaign.status)"
                    >
                      <span class="w-1.5 h-1.5 rounded-full mr-1" :class="getStatusDotClass(campaign.status)"></span>
                      {{ campaign.status.charAt(0).toUpperCase() + campaign.status.slice(1) }}
                    </span>
                    
                    <!-- Quick Status Change -->
                    <select
                      :value="campaign.status"
                      @change="quickStatusChange(campaign, $event)"
                      :disabled="isUpdating === campaign.id"
                      class="text-xs border border-gray-300 rounded-md px-2 py-1 bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"
                    >
                      <option value="draft">Draft</option>
                      <option value="active">Active</option>
                      <option value="completed">Completed</option>
                    </select>
                  </div>
                </td>

                <!-- Additional Info -->
                <td class="px-6 py-3">
                  <!-- Available for future information if needed -->
                </td>

                <!-- Extra Space / Future Actions -->
                <td class="px-6 py-3">
                  <!-- Reserved for additional actions if needed -->
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="totalPages > 1" class="flex flex-col sm:flex-row justify-between items-center mt-8 pt-6 border-t border-gray-200 bg-gray-50 rounded-lg px-6 py-4">
        <div class="text-sm text-gray-600 mb-4 sm:mb-0">
          Showing <span class="font-medium text-gray-900">{{ serverPagination.from }}</span>-<span class="font-medium text-gray-900">{{ serverPagination.to }}</span> 
          of <span class="font-medium text-gray-900">{{ serverPagination.total }}</span> campaigns
        </div>
        <nav class="flex items-center space-x-1">
          <button
            @click="goToPage(Math.max(1, currentPage - 1))"
            :disabled="currentPage === 1"
            class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-l-md hover:bg-gray-50 focus:z-10 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Previous
          </button>
          <div class="flex items-center space-x-1 mx-2">
            <button
              v-for="page in visiblePages"
              :key="page"
              @click="goToPage(page)"
              class="relative inline-flex items-center px-4 py-2 text-sm font-medium border focus:z-10 focus:ring-2 focus:ring-blue-500"
              :class="page === currentPage
                ? 'z-10 bg-blue-600 border-blue-600 text-white'
                : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50'"
            >
              {{ page }}
            </button>
          </div>
          <button
            @click="goToPage(Math.min(totalPages, currentPage + 1))"
            :disabled="currentPage === totalPages"
            class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-r-md hover:bg-gray-50 focus:z-10 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Next
          </button>
        </nav>
      </div>
    </div>


  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useCampaignsStore } from '@/stores/campaigns'
import { useAuthStore } from '@/stores/auth'
import { apiService } from '@/services/api'
import type { Campaign, CampaignCategory } from '@/services/api'

const campaignsStore = useCampaignsStore()
const authStore = useAuthStore()

// State
const campaigns = ref<Campaign[]>([])
const categories = ref<CampaignCategory[]>([])
const isLoading = ref(false)
const isUpdating = ref<number | null>(null)
const currentPage = ref(1)
const pageSize = ref(10)

// Server pagination state
const serverPagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 10,
  total: 0,
  from: 1,
  to: 10
})

// Campaign stats state
const campaignStats = ref({
  active: 0,
  completed: 0,
  cancelled: 0,
  draft: 0,
  total: 0,
  featured: 0
})

// Filters
const filters = ref({
  search: '',
  status: '',
  category: '',
  featured: '',
  sortBy: 'created_at_desc'
})



// Computed - use real stats instead of current page data
const totalCampaigns = computed(() => campaignStats.value.total || serverPagination.value.total || campaigns.value.length)
const activeCampaigns = computed(() => campaignStats.value.active)
const draftCampaigns = computed(() => campaignStats.value.draft)
const featuredCount = computed(() => campaignStats.value.featured)

// Use campaigns directly since filtering and pagination are now done server-side
const paginatedCampaigns = computed(() => campaigns.value)

const totalPages = computed(() => serverPagination.value.last_page)

const visiblePages = computed(() => {
  const current = currentPage.value
  const total = totalPages.value
  const pages = []

  for (let i = Math.max(1, current - 2); i <= Math.min(total, current + 2); i++) {
    pages.push(i)
  }

  return pages
})

// Methods
async function fetchCampaigns() {
  isLoading.value = true
  try {
    // Build query parameters
    const params: any = {
      page: currentPage.value,
      per_page: pageSize.value,
    }

    // Add filters
    if (filters.value.search) {
      params.search = filters.value.search
    }
    if (filters.value.status) {
      params.status = filters.value.status
    }
    if (filters.value.category) {
      params.category_id = filters.value.category
    }
    if (filters.value.featured && filters.value.featured !== '') {
      params.featured = filters.value.featured === 'true'
    }

    // Add sorting
    if (filters.value.sortBy) {
      const parts = filters.value.sortBy.split('_')
      const order = parts[parts.length - 1] // Last part is the order (asc/desc)
      const field = parts.slice(0, -1).join('_') // Everything else is the field name
      
      // Map frontend field names to backend field names
      let sortField = field
      if (field === 'amount') {
        sortField = 'target_amount'
      } else if (field === 'created') {
        sortField = 'created_at'
      }
      
      params.sort_by = sortField
      params.sort_order = order || 'desc'
    }

    // Fetch campaigns with server-side pagination and filtering
    const response = await apiService.get('/admin/campaigns', { params })
    
    if (response.data) {
      campaigns.value = response.data
      // Update server pagination state
      serverPagination.value = {
        current_page: response.current_page || 1,
        last_page: response.last_page || 1,
        per_page: response.per_page || pageSize.value,
        total: response.total || 0,
        from: response.from || 1,
        to: response.to || 0
      }
    } else if (Array.isArray(response)) {
      campaigns.value = response
    } else {
      campaigns.value = []
    }
  } catch (error) {
    console.error('Failed to fetch campaigns:', error)
    campaigns.value = []
  } finally {
    isLoading.value = false
  }
}

// Legacy function for compatibility
async function fetchAllCampaigns() {
  await fetchCampaigns()
}

async function fetchCampaignStats() {
  try {
    const response = await apiService.get('/campaigns/stats')
    campaignStats.value = response
  } catch (error) {
    console.error('Failed to fetch campaign stats:', error)
  }
}

async function fetchCategories() {
  try {
    await campaignsStore.fetchCategories()
    categories.value = campaignsStore.categories
  } catch (error) {
    console.error('Failed to fetch categories:', error)
  }
}

function getStatusClass(status: string) {
  switch (status) {
    case 'active':
      return 'bg-green-100 text-green-800'
    case 'draft':
      return 'bg-gray-100 text-gray-800'
    case 'completed':
      return 'bg-blue-100 text-blue-800'
    default:
      return 'bg-gray-100 text-gray-800'
  }
}

function getStatusDotClass(status: string) {
  switch (status) {
    case 'active':
      return 'bg-green-500'
    case 'draft':
      return 'bg-gray-400'
    case 'completed':
      return 'bg-blue-500'
    default:
      return 'bg-gray-400'
  }
}

function formatDate(dateString: string) {
  return new Date(dateString).toLocaleDateString()
}

function viewCampaign(campaign: Campaign) {
  window.open(`/campaigns/${campaign.id}`, '_blank')
}

function editCampaign(campaign: Campaign) {
  // Navigate to campaign edit page
  window.open(`/campaigns/${campaign.id}/edit`, '_blank')
}

async function quickStatusChange(campaign: Campaign, event: Event) {
  const target = event.target as HTMLSelectElement
  const newStatus = target.value as Campaign['status']
  
  if (newStatus === campaign.status) {
    return // No change
  }

  isUpdating.value = campaign.id
  try {
    await campaignsStore.updateCampaign(campaign.id, {
      status: newStatus
    })
    
    // Update local state
    campaign.status = newStatus
    
    // Refresh the data to ensure consistency
    await fetchAllCampaigns()
  } catch (error) {
    console.error('Failed to update campaign status:', error)
    // Reset the dropdown to original value on error
    target.value = campaign.status
  } finally {
    isUpdating.value = null
  }
}

async function toggleFeatured(campaign: Campaign) {
  isUpdating.value = campaign.id
  try {
    await apiService.put(`/admin/campaigns/${campaign.id}/featured`, {
      featured: !campaign.featured
    })
    
    // Update local state
    campaign.featured = !campaign.featured
    
    // Refresh campaign stats to update the featured count in stats cards
    await fetchCampaignStats()
  } catch (error) {
    console.error('Failed to toggle featured status:', error)
  } finally {
    isUpdating.value = null
  }
}



async function deleteCampaign(campaign: Campaign) {
  if (!confirm(`Are you sure you want to delete "${campaign.title}"? This action cannot be undone.`)) {
    return
  }

  try {
    await campaignsStore.deleteCampaign(campaign.id)
    campaigns.value = campaigns.value.filter(c => c.id !== campaign.id)
  } catch (error) {
    console.error('Failed to delete campaign:', error)
  }
}

function exportCampaigns() {
  const csvContent = [
    ['ID', 'Title', 'Creator', 'Category', 'Target Amount', 'Current Amount', 'Progress %', 'Status', 'Featured', 'Created At'].join(','),
    ...campaigns.value.map(campaign => [
      campaign.id,
      `"${campaign.title}"`,
      `"${campaign.user.name}"`,
      `"${campaign.category.name}"`,
      campaign.target_amount,
      campaign.current_amount,
      Math.round(((campaign.current_amount || 0) / (campaign.target_amount || 1)) * 100) + '%',
      campaign.status,
      campaign.featured,
      campaign.created_at
    ].join(','))
  ].join('\n')

  const blob = new Blob([csvContent], { type: 'text/csv' })
  const url = window.URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = `campaigns-export-${new Date().toISOString().split('T')[0]}.csv`
  link.click()
  window.URL.revokeObjectURL(url)
}

async function refreshData() {
  await Promise.all([
    fetchCampaigns(),
    fetchCategories(),
    fetchCampaignStats()
  ])
}

function applyFilters() {
  currentPage.value = 1
  fetchCampaigns()
}

function goToPage(page: number) {
  currentPage.value = page
  fetchCampaigns()
}

// Debounced search
let searchTimeout: NodeJS.Timeout
function debouncedSearch() {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    applyFilters()
  }, 300)
}

// Watch for filter changes to reset pagination and fetch new data
watch(() => [filters.value.status, filters.value.category, filters.value.featured, filters.value.sortBy], () => {
  currentPage.value = 1
  fetchCampaigns()
})

// Lifecycle
onMounted(async () => {
  await refreshData()
})
</script>

<style scoped>
.line-clamp-2 {
  overflow: hidden;
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
}
</style>
