<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">My Campaigns</h1>
        <p class="text-gray-600 mt-1">Manage your fundraising campaigns</p>
      </div>
      <router-link to="/campaigns/create" class="btn-primary"> Create New Campaign </router-link>
    </div>

    <!-- Stats Overview -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
      <div class="card text-center">
        <div class="text-3xl font-bold text-blue-600">{{ stats.total }}</div>
        <div class="text-sm font-medium text-gray-500">Total Campaigns</div>
      </div>
      <div class="card text-center">
        <div class="text-3xl font-bold text-green-600">{{ stats.active }}</div>
        <div class="text-sm font-medium text-gray-500">Active</div>
      </div>
      <div class="card text-center">
        <div class="text-3xl font-bold text-yellow-600">
          ${{ stats.totalRaised.toLocaleString() }}
        </div>
        <div class="text-sm font-medium text-gray-500">Total Raised</div>
      </div>
      <div class="card text-center">
        <div class="text-3xl font-bold text-purple-600">{{ stats.totalDonations }}</div>
        <div class="text-sm font-medium text-gray-500">Total Donations</div>
      </div>
    </div>

    <!-- Filters -->
    <div class="card">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
          <input
            v-model="filters.search"
            type="text"
            placeholder="Search your campaigns..."
            class="input-field"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
          <select v-model="filters.status" class="input-field">
            <option value="">All Status</option>
            <option value="draft">Draft</option>
            <option value="active">Active</option>
            <option value="completed">Completed</option>
          </select>
        </div>
        <div class="flex items-end">
          <button @click="clearFilters" class="btn-secondary w-full">Clear Filters</button>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="text-center py-8">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      <p class="text-gray-600 mt-2">Loading your campaigns...</p>
    </div>

    <!-- Empty State -->
    <div v-else-if="filteredCampaigns.length === 0 && !isLoading" class="text-center py-12">
      <svg
        class="mx-auto h-12 w-12 text-gray-400"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"
        />
      </svg>
      <h3 class="text-lg font-medium text-gray-900 mt-2">No campaigns found</h3>
      <p class="text-gray-600 mt-1">
        {{
          filters.search || filters.status
            ? 'Try adjusting your filters'
            : 'Start by creating your first campaign'
        }}
      </p>
      <router-link
        v-if="!filters.search && !filters.status"
        to="/campaigns/create"
        class="btn-primary mt-4 inline-flex"
      >
        Create Your First Campaign
      </router-link>
    </div>

    <!-- Campaigns List -->
    <div v-else class="space-y-4">
      <div
        v-for="campaign in filteredCampaigns"
        :key="campaign.id"
        class="card hover:shadow-md transition-shadow"
      >
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
          <!-- Campaign Info -->
          <div class="flex-1">
            <div class="flex items-center space-x-3 mb-2">
              <h3 class="text-lg font-semibold text-gray-900">
                {{ campaign.title }}
              </h3>
              <span
                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                :class="getStatusClass(campaign.status)"
              >
                {{ campaign.status.charAt(0).toUpperCase() + campaign.status.slice(1) }}
              </span>
              <span
                v-if="campaign.featured"
                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800"
              >
                Featured
              </span>
            </div>

            <p class="text-gray-600 text-sm mb-3 line-clamp-2">{{ campaign.description }}</p>

            <!-- Progress -->
            <div class="mb-3">
              <div class="flex justify-between text-sm text-gray-600 mb-1">
                <span>${{ Number(campaign.current_amount || 0).toLocaleString() }} raised</span>
                <span
                  >{{
                    Math.round(
                      (Number(campaign.current_amount || 0) / Number(campaign.target_amount || 1)) *
                        100,
                    )
                  }}% of ${{ Number(campaign.target_amount || 0).toLocaleString() }}</span
                >
              </div>
              <div class="w-full bg-gray-200 rounded-full h-2">
                <div
                  class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                  :style="{
                    width: `${Math.min(Math.round((Number(campaign.current_amount || 0) / Number(campaign.target_amount || 1)) * 100), 100)}%`,
                  }"
                ></div>
              </div>
            </div>

            <!-- Campaign Meta -->
            <div class="flex flex-wrap items-center gap-4 text-sm text-gray-500">
              <span class="flex items-center">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                  />
                </svg>
                {{ formatDate(campaign.start_date) }} - {{ formatDate(campaign.end_date) }}
              </span>
              <span class="flex items-center">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"
                  />
                </svg>
                {{
                  campaign.donations_count !== undefined
                    ? campaign.donations_count
                    : Math.floor(Number(campaign.current_amount || 0) / 50)
                }}
                donations
              </span>
              <span class="flex items-center">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a2 2 0 012-2z"
                  />
                </svg>
                {{ campaign.category.name }}
              </span>
            </div>
          </div>

          <!-- Actions -->
          <div class="mt-4 lg:mt-0 lg:ml-6 flex flex-col space-y-2">
            <!-- View Details -->
            <router-link
              :to="`/campaigns/${campaign.id}`"
              class="w-full inline-flex items-center justify-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                ></path>
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                ></path>
              </svg>
              View Details
            </router-link>

            <!-- Edit Campaign -->
            <router-link
              :to="`/campaigns/${campaign.id}/edit`"
              class="w-full inline-flex items-center justify-center px-3 py-2 border border-blue-300 text-sm font-medium rounded-md text-blue-700 bg-blue-50 hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                ></path>
              </svg>
              Edit
            </router-link>

            <!-- Quick Actions - Only for admins -->
            <div v-if="authStore.user?.is_admin" class="relative">
              <button
                @click="toggleActionsMenu(campaign.id)"
                class="btn-secondary flex items-center"
              >
                Actions
                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M19 9l-7 7-7-7"
                  />
                </svg>
              </button>

              <!-- Actions Menu -->
              <div
                v-if="activeActionsMenu === campaign.id"
                v-click-outside="() => (activeActionsMenu = null)"
                class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 z-10"
              >
                <div class="py-1">
                  <!-- Status actions only for admins -->
                  <template v-if="authStore.user?.is_admin">
                    <button
                      v-if="campaign.status === 'draft'"
                      @click="activateCampaign(campaign)"
                      class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                    >
                      Activate Campaign
                    </button>
                  </template>

                  <!-- Info for regular users about draft status -->
                  <div
                    v-else-if="campaign.status === 'draft'"
                    class="px-4 py-2 text-sm text-gray-500"
                  >
                    Campaign pending admin approval
                  </div>
                  <button
                    @click="duplicateCampaign(campaign)"
                    class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                  >
                    Duplicate Campaign
                  </button>
                  <hr class="my-1" />
                  <button
                    @click="confirmDelete(campaign)"
                    class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50"
                  >
                    Delete Campaign
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div
      v-if="campaignToDelete"
      class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
    >
      <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
          <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
            <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"
              />
            </svg>
          </div>
          <h3 class="text-lg leading-6 font-medium text-gray-900 mt-4">Delete Campaign</h3>
          <div class="mt-2 px-7 py-3">
            <p class="text-sm text-gray-500">
              Are you sure you want to delete "{{ campaignToDelete.title }}"? This action cannot be
              undone.
            </p>
          </div>
          <div class="items-center px-4 py-3 space-x-3">
            <button @click="campaignToDelete = null" class="btn-secondary">Cancel</button>
            <button
              @click="deleteCampaign(campaignToDelete)"
              class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md text-sm font-medium"
            >
              Delete
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useCampaignsStore } from '@/stores/campaigns'
import { useAuthStore } from '@/stores/auth'
import { campaignsApi } from '@/services/api'
import type { Campaign } from '@/services/api'

const router = useRouter()
const campaignsStore = useCampaignsStore()
const authStore = useAuthStore()

// State
const myCampaigns = ref<Campaign[]>([])
const isLoading = ref(false)
const activeActionsMenu = ref<number | null>(null)
const campaignToDelete = ref<Campaign | null>(null)

// Filters
const filters = ref({
  search: '',
  status: '',
})

// Computed
const filteredCampaigns = computed(() => {
  let filtered = myCampaigns.value

  if (filters.value.search) {
    const search = filters.value.search.toLowerCase()
    filtered = filtered.filter(
      (campaign) =>
        campaign.title.toLowerCase().includes(search) ||
        campaign.description.toLowerCase().includes(search),
    )
  }

  if (filters.value.status) {
    filtered = filtered.filter((campaign) => campaign.status === filters.value.status)
  }

  return filtered.sort(
    (a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime(),
  )
})

const stats = computed(() => {
  return {
    total: myCampaigns.value.length,
    active: myCampaigns.value.filter((c) => c.status === 'active').length,
    totalRaised: myCampaigns.value.reduce((sum, c) => sum + Number(c.current_amount || 0), 0),
    totalDonations: myCampaigns.value.reduce((sum, c) => {
      // If donations_count is available, use it; otherwise estimate based on current_amount
      if (c.donations_count !== undefined && c.donations_count !== null) {
        return sum + Number(c.donations_count)
      }
      // Fallback: estimate donations (assume $50 average donation)
      const estimatedDonations = Math.floor(Number(c.current_amount || 0) / 50)
      return sum + estimatedDonations
    }, 0),
  }
})

// Methods
async function fetchMyCampaigns() {
  if (!authStore.isAuthenticated) return

  isLoading.value = true
  try {
    // Use dedicated API endpoint for user's campaigns (includes drafts)
    const response = await campaignsApi.getMyCampaigns({
      per_page: 100, // Get all user campaigns
    })

    if (response.data) {
      myCampaigns.value = response.data
    } else if (Array.isArray(response)) {
      myCampaigns.value = response
    } else {
      myCampaigns.value = []
    }
  } catch (error) {
    console.error('Failed to fetch my campaigns:', error)
  } finally {
    isLoading.value = false
  }
}

function clearFilters() {
  filters.value.search = ''
  filters.value.status = ''
}

function toggleActionsMenu(campaignId: number) {
  activeActionsMenu.value = activeActionsMenu.value === campaignId ? null : campaignId
}

function getStatusClass(status: string) {
  const classes = {
    draft: 'bg-gray-100 text-gray-800',
    active: 'bg-green-100 text-green-800',
    completed: 'bg-blue-100 text-blue-800',
  }
  return classes[status as keyof typeof classes] || 'bg-gray-100 text-gray-800'
}

function formatDate(dateString: string) {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}

// Campaign Actions
async function activateCampaign(campaign: Campaign) {
  try {
    await campaignsStore.updateCampaign(campaign.id, { status: 'active' })
    await fetchMyCampaigns() // Refresh the list
    activeActionsMenu.value = null
  } catch (error) {
    console.error('Failed to activate campaign:', error)
  }
}

function duplicateCampaign(campaign: Campaign) {
  // Navigate to create form with pre-filled data
  router.push({
    path: '/campaigns/create',
    query: {
      duplicate: campaign.id.toString(),
    },
  })
}

function confirmDelete(campaign: Campaign) {
  campaignToDelete.value = campaign
  activeActionsMenu.value = null
}

async function deleteCampaign(campaign: Campaign) {
  try {
    await campaignsStore.deleteCampaign(campaign.id)
    await fetchMyCampaigns()
    campaignToDelete.value = null
  } catch (error) {
    console.error('Failed to delete campaign:', error)
  }
}

// Click outside directive (simplified)
const vClickOutside = {
  beforeMount(el: HTMLElement & { clickOutsideEvent?: (event: Event) => void }, binding: { value: () => void }) {
    const clickOutsideEvent = (event: Event) => {
      if (!(el === event.target || el.contains(event.target as Node))) {
        binding.value()
      }
    }
    el.clickOutsideEvent = clickOutsideEvent
    document.addEventListener('click', clickOutsideEvent)
  },
  unmounted(el: HTMLElement & { clickOutsideEvent?: (event: Event) => void }) {
    const clickOutsideEvent = el.clickOutsideEvent
    if (clickOutsideEvent) {
      document.removeEventListener('click', clickOutsideEvent)
    }
  },
}

// Initialize
onMounted(() => {
  fetchMyCampaigns()
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
