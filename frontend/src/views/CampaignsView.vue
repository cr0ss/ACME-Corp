<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">Campaigns</h1>
        <p class="text-gray-600 mt-1">Browse and support meaningful causes in our community</p>
      </div>
      <router-link v-if="authStore.isAuthenticated" to="/campaigns/create" class="btn-primary">
        Create Campaign
      </router-link>
    </div>

    <!-- Search and Filters -->
    <div class="card">
      <!-- Basic Filters Row -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
          <input
            v-model="filters.search"
            type="text"
            placeholder="Search campaigns, descriptions, creators..."
            class="input-field"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
          <select v-model="filters.category" class="input-field">
            <option value="">All Categories</option>
            <option
              v-for="category in campaignsStore.categories"
              :key="category.id"
              :value="category.id"
            >
              {{ category.icon }} {{ category.name }}
            </option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
          <select v-model="filters.status" class="input-field">
            <option value="">All Status</option>
            <option value="active">Active</option>
            <option value="completed">Completed</option>
            <option value="cancelled">Cancelled</option>
            <option value="draft">Draft</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Sort By</label>
          <select v-model="filters.sortBy" class="input-field">
            <option value="created_at_desc">Newest First</option>
            <option value="created_at_asc">Oldest First</option>
            <option value="target_amount_desc">Highest Target</option>
            <option value="target_amount_asc">Lowest Target</option>
            <option value="progress_desc">Most Progress</option>
            <option value="progress_asc">Least Progress</option>
            <option value="title_asc">Title A-Z</option>
            <option value="title_desc">Title Z-A</option>
          </select>
        </div>
      </div>

      <!-- Advanced Filters (Collapsible) -->
      <div class="border-t border-gray-200 pt-4">
        <button
          @click="showAdvancedFilters = !showAdvancedFilters"
          class="flex items-center text-sm font-medium text-gray-700 hover:text-gray-900 mb-3"
        >
          <svg
            class="w-4 h-4 mr-1 transition-transform"
            :class="{ 'rotate-90': showAdvancedFilters }"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M9 5l7 7-7 7"
            />
          </svg>
          Advanced Filters
        </button>

        <div v-if="showAdvancedFilters" class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <!-- Target Amount Range -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Target Amount Range</label>
            <div class="grid grid-cols-2 gap-2">
              <input
                v-model.number="filters.minAmount"
                type="number"
                placeholder="Min $"
                class="input-field text-sm"
                min="0"
              />
              <input
                v-model.number="filters.maxAmount"
                type="number"
                placeholder="Max $"
                class="input-field text-sm"
                min="0"
              />
            </div>
          </div>

          <!-- Date Range -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Campaign Period</label>
            <div class="grid grid-cols-2 gap-2">
              <input v-model="filters.startDate" type="date" class="input-field text-sm" />
              <input v-model="filters.endDate" type="date" class="input-field text-sm" />
            </div>
          </div>

          <!-- Progress Range -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Progress Range</label>
            <div class="grid grid-cols-2 gap-2">
              <input
                v-model.number="filters.minProgress"
                type="number"
                placeholder="Min %"
                class="input-field text-sm"
                min="0"
                max="100"
              />
              <input
                v-model.number="filters.maxProgress"
                type="number"
                placeholder="Max %"
                class="input-field text-sm"
                min="0"
                max="100"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- Filter Actions -->
      <div class="flex justify-between items-center pt-4 border-t border-gray-200 mt-4">
        <div class="flex items-center space-x-2">
          <button @click="clearFilters" class="btn-secondary text-sm">Clear All Filters</button>
          <button @click="saveFilters" class="btn-secondary text-sm">Save Filters</button>
          <button v-if="hasSavedFilters" @click="loadFilters" class="btn-secondary text-sm">
            Load Saved
          </button>
        </div>
        <div class="text-sm text-gray-500">
          {{ campaignsStore.campaigns.length }} of {{ campaignsStore.pagination.total }} campaigns
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="campaignsStore.isLoading" class="text-center py-8">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      <p class="mt-2 text-gray-600">Loading campaigns...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="campaignsStore.error" class="card bg-red-50 border-red-200">
      <p class="text-red-600">{{ campaignsStore.error }}</p>
    </div>

    <!-- Campaigns Grid -->
    <div
      v-else-if="campaignsStore.campaigns.length > 0"
      class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"
    >
      <CampaignCard
        v-for="campaign in campaignsStore.campaigns"
        :key="campaign.id"
        :campaign="campaign"
        @donate="handleDonate"
      />
    </div>

    <!-- Empty State -->
    <div v-else class="text-center py-12">
      <div class="text-6xl mb-4">🎯</div>
      <h3 class="text-lg font-medium text-gray-900 mb-2">No campaigns found</h3>
      <p class="text-gray-600 mb-4">
        Try adjusting your filters or check back later for new campaigns.
      </p>
      <router-link v-if="authStore.isAuthenticated" to="/campaigns/create" class="btn-primary">
        Create the First Campaign
      </router-link>
    </div>

    <!-- Pagination -->
    <div
      v-if="campaignsStore.pagination && campaignsStore.pagination.last_page > 1"
      class="flex justify-center"
    >
      <nav class="flex items-center space-x-2">
        <!-- Previous button -->
        <button
          v-if="campaignsStore.pagination.current_page > 1"
          @click="goToPage(campaignsStore.pagination.current_page - 1)"
          class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-100 transition duration-200"
        >
          Previous
        </button>

        <!-- Page numbers -->
        <button
          v-for="page in visiblePages"
          :key="page"
          @click="goToPage(page)"
          class="px-3 py-2 rounded-md text-sm font-medium transition duration-200"
          :class="
            page === campaignsStore.pagination.current_page
              ? 'bg-blue-600 text-white'
              : 'text-gray-700 hover:bg-gray-100'
          "
        >
          {{ page }}
        </button>

        <!-- Next button -->
        <button
          v-if="campaignsStore.pagination.current_page < campaignsStore.pagination.last_page"
          @click="goToPage(campaignsStore.pagination.current_page + 1)"
          class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-100 transition duration-200"
        >
          Next
        </button>
      </nav>
    </div>

    <!-- Donation Modal -->
    <DonationModal
      v-if="showDonateModal"
      :campaign="selectedCampaign"
      @close="showDonateModal = false"
      @success="handleDonationSuccess"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useCampaignsStore } from '@/stores/campaigns'
import CampaignCard from '@/components/campaigns/CampaignCard.vue'
import DonationModal from '@/components/donations/DonationModal.vue'
import type { Campaign } from '@/services/api'

const authStore = useAuthStore()
const campaignsStore = useCampaignsStore()

// UI State
const showAdvancedFilters = ref(false)
const showDonateModal = ref(false)
const selectedCampaign = ref<Campaign | null>(null)

// Enhanced Filters
const filters = ref({
  search: '',
  category: '',
  status: 'active', // Default to showing only active campaigns
  sortBy: 'created_at_desc',
  minAmount: null as number | null,
  maxAmount: null as number | null,
  startDate: '',
  endDate: '',
  minProgress: null as number | null,
  maxProgress: null as number | null,
})

// Saved filters in localStorage
const SAVED_FILTERS_KEY = 'campaign_filters'

// Computed properties
// Note: All filtering is now done server-side for proper pagination

const hasSavedFilters = computed(() => {
  return localStorage.getItem(SAVED_FILTERS_KEY) !== null
})

const visiblePages = computed(() => {
  const current = campaignsStore.pagination?.current_page || 1
  const last = campaignsStore.pagination?.last_page || 1
  const pages = []

  for (let i = Math.max(1, current - 2); i <= Math.min(last, current + 2); i++) {
    pages.push(i)
  }

  return pages
})

// Methods
async function fetchCampaigns(page = 1) {
  // Server-side filters and pagination
  const params: Record<string, unknown> = {
    page,
    per_page: 15,
  }

  // Basic filters
  if (filters.value.search) {
    params.search = filters.value.search
  }

  if (filters.value.category) {
    params.category_id = filters.value.category
  }

  if (filters.value.status) {
    params.status = filters.value.status
  }

  // Sorting - convert our format to backend format
  if (filters.value.sortBy) {
    const [field, direction] = filters.value.sortBy.split('_')
    switch (field) {
      case 'created':
        params.sort_by = 'created_at'
        break
      case 'target':
        params.sort_by = 'target_amount'
        break
      case 'title':
        params.sort_by = 'title'
        break
      default:
        params.sort_by = 'created_at'
    }
    params.sort_order = direction === 'desc' ? 'desc' : 'asc'
  }

  await campaignsStore.fetchCampaigns(params)
}

function clearFilters() {
  filters.value = {
    search: '',
    category: '',
    status: '',
    sortBy: 'created_at_desc',
    minAmount: null,
    maxAmount: null,
    startDate: '',
    endDate: '',
    minProgress: null,
    maxProgress: null,
  }
  showAdvancedFilters.value = false
}

function saveFilters() {
  try {
    localStorage.setItem(SAVED_FILTERS_KEY, JSON.stringify(filters.value))
    // You could add a toast notification here
    console.log('Filters saved successfully')
  } catch (error) {
    console.error('Failed to save filters:', error)
  }
}

function loadFilters() {
  try {
    const savedFilters = localStorage.getItem(SAVED_FILTERS_KEY)
    if (savedFilters) {
      const parsed = JSON.parse(savedFilters)
      filters.value = { ...filters.value, ...parsed }
      // Show advanced filters if any advanced filter is set
      showAdvancedFilters.value = !!(
        parsed.minAmount ||
        parsed.maxAmount ||
        parsed.startDate ||
        parsed.endDate ||
        parsed.minProgress ||
        parsed.maxProgress
      )
      console.log('Filters loaded successfully')
    }
  } catch (error) {
    console.error('Failed to load filters:', error)
  }
}

function goToPage(page: number) {
  fetchCampaigns(page)
}

function handleDonate(campaign: Campaign) {
  selectedCampaign.value = campaign
  showDonateModal.value = true
}

function handleDonationSuccess() {
  // Reload campaigns to reflect updated progress
  fetchCampaigns(1)
}

// Watchers - debounced for better performance
let searchTimeout: ReturnType<typeof setTimeout>
watch(
  () => filters.value.search,
  () => {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
      fetchCampaigns(1) // Reset to page 1 when search changes
    }, 300) // 300ms debounce
  },
)

// Watch other filters without debounce
watch(
  () => [filters.value.category, filters.value.status, filters.value.sortBy],
  () => {
    fetchCampaigns(1) // Reset to page 1 when filters change
  },
)

// Lifecycle
onMounted(async () => {
  await campaignsStore.fetchCategories()
  await fetchCampaigns(1)

  // Optionally auto-load saved filters
  // if (hasSavedFilters.value) {
  //   loadFilters()
  // }
})
</script>
