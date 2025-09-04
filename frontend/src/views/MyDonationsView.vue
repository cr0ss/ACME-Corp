<template>
  <div class="space-y-6">
    <div class="flex justify-between items-center">
      <h1 class="text-3xl font-bold text-gray-900">My Donations</h1>
      <div class="text-sm text-gray-600">Total: {{ donations.length }} donations</div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div class="card text-center">
        <div v-if="isLoadingStats" class="animate-pulse">
          <div class="h-8 bg-gray-200 rounded mb-1"></div>
          <div class="text-gray-600">Total Donated</div>
        </div>
        <div v-else>
          <div class="text-2xl font-bold text-blue-600 mb-1">${{ formatNumber(totalDonated) }}</div>
          <div class="text-gray-600">Total Donated</div>
        </div>
      </div>
      <div class="card text-center">
        <div v-if="isLoadingStats" class="animate-pulse">
          <div class="h-8 bg-gray-200 rounded mb-1"></div>
          <div class="text-gray-600">Total Donations</div>
        </div>
        <div v-else>
          <div class="text-2xl font-bold text-green-600 mb-1">{{ totalDonations }}</div>
          <div class="text-gray-600">Total Donations</div>
        </div>
      </div>
      <div class="card text-center">
        <div v-if="isLoadingStats" class="animate-pulse">
          <div class="h-8 bg-gray-200 rounded mb-1"></div>
          <div class="text-gray-600">Campaigns Supported</div>
        </div>
        <div v-else>
          <div class="text-2xl font-bold text-purple-600 mb-1">{{ uniqueCampaigns }}</div>
          <div class="text-gray-600">Campaigns Supported</div>
        </div>
      </div>
    </div>

    <!-- Stats Error -->
    <div v-if="statsError" class="bg-orange-50 border border-orange-200 rounded-md p-3">
      <p class="text-orange-600 text-sm">
        <span class="font-medium">Stats Error:</span> {{ statsError }}
      </p>
    </div>

    <!-- Donations List -->
    <div class="card">
      <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold text-gray-900">Donation History</h2>
        <button @click="refreshDonations" :disabled="isLoading" class="btn-secondary inline-flex items-center">
          <svg
            v-if="isLoading"
            class="animate-spin h-4 w-4 mr-2"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
          >
            <circle
              class="opacity-25"
              cx="12"
              cy="12"
              r="10"
              stroke="currentColor"
              stroke-width="4"
            ></circle>
            <path
              class="opacity-75"
              fill="currentColor"
              d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
            ></path>
          </svg>
          <span>Refresh</span>
        </button>
      </div>

      <!-- Loading State -->
      <div v-if="isLoading && donations.length === 0" class="text-center py-8">
        <div
          class="animate-spin mx-auto h-8 w-8 border-4 border-blue-600 border-t-transparent rounded-full"
        ></div>
        <p class="mt-2 text-gray-600">Loading your donations...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-md p-4">
        <p class="text-red-600">{{ error }}</p>
        <button @click="refreshDonations" class="mt-2 btn-primary">Try Again</button>
      </div>

      <!-- Empty State -->
      <div v-else-if="donations.length === 0" class="text-center py-12">
        <svg
          class="mx-auto h-12 w-12 text-gray-400"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"
          />
        </svg>
        <h3 class="mt-2 text-lg font-medium text-gray-900">No donations yet</h3>
        <p class="mt-1 text-gray-500">
          You haven't made any donations. Start supporting campaigns today!
        </p>
        <router-link to="/campaigns" class="mt-4 inline-block btn-primary">
          Browse Campaigns
        </router-link>
      </div>

      <!-- Donations Table -->
      <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
              >
                Campaign
              </th>
              <th
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
              >
                Amount
              </th>
              <th
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
              >
                Date
              </th>
              <th
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
              >
                Status
              </th>
              <th
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
              >
                Actions
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr
              v-for="(donation, index) in donations"
              :key="donation.id || `donation-${index}`"
              class="hover:bg-gray-50"
            >
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div>
                    <div class="text-sm font-medium text-gray-900">
                      {{ donation.campaign?.title }}
                    </div>
                    <div class="text-sm text-gray-500">
                      {{ donation.campaign?.category?.name }}
                    </div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">
                  ${{ formatNumber(donation.amount || 0) }}
                </div>
                <div v-if="donation?.anonymous" class="text-xs text-gray-500">Anonymous</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ donation.created_at ? formatDate(donation.created_at) : 'N/A' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  :class="getStatusClass(donation.status || 'unknown')"
                  class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                >
                  {{ getStatusText(donation.status || 'unknown') }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <div class="flex flex-col space-y-2">
                  <router-link
                    v-if="donation.campaign?.id"
                    :to="`/campaigns/${donation.campaign.id}`"
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
                    View Campaign
                  </router-link>
                  <button
                    v-if="donation.status === 'completed' && donation.id"
                    @click="downloadReceipt(donation.id)"
                    class="w-full inline-flex items-center justify-center px-3 py-2 border border-green-300 text-sm font-medium rounded-md text-green-700 bg-green-50 hover:bg-green-100 focus:outline-none focus:ring-2 focus:ring-green-500"
                  >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                      ></path>
                    </svg>
                    Download Receipt
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div
        v-if="pagination && pagination.last_page > 1"
        class="mt-6 flex items-center justify-between"
      >
        <div class="text-sm text-gray-700">
          Showing {{ ((pagination?.current_page || 1) - 1) * (pagination?.per_page || 15) + 1 }} to
          {{
            Math.min(
              (pagination?.current_page || 1) * (pagination?.per_page || 15),
              pagination?.total || 0,
            )
          }}
          of {{ pagination?.total || 0 }} results
        </div>
        <div class="flex space-x-2">
          <button
            @click="changePage((pagination?.current_page || 1) - 1)"
            :disabled="(pagination?.current_page || 1) <= 1"
            class="px-3 py-1 text-sm border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Previous
          </button>
          <button
            @click="changePage((pagination?.current_page || 1) + 1)"
            :disabled="(pagination?.current_page || 1) >= (pagination?.last_page || 1)"
            class="px-3 py-1 text-sm border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Next
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useDonationsStore } from '@/stores/donations'
import { formatNumber } from '@/utils/formatters'

const donationsStore = useDonationsStore()

// Computed properties
const donations = computed(() => (donationsStore.donations || []).filter((d) => d != null))
const isLoading = computed(() => donationsStore.isLoading)
const isLoadingStats = computed(() => donationsStore.isLoadingStats)
const error = computed(() => donationsStore.error)
const statsError = computed(() => donationsStore.statsError)
const stats = computed(() => donationsStore.stats)
const pagination = computed(
  () =>
    donationsStore.pagination || {
      current_page: 1,
      last_page: 1,
      per_page: 15,
      total: 0,
    },
)

// Use real stats from API instead of calculating from paginated data
const totalDonated = computed(() => stats.value?.total_donated || 0)
const totalDonations = computed(() => stats.value?.total_donations || 0)
const uniqueCampaigns = computed(() => stats.value?.campaigns_supported || 0)

// Methods
const refreshDonations = () => {
  donationsStore.fetchMyDonations()
  donationsStore.fetchStats()
}

const changePage = (page: number) => {
  donationsStore.fetchMyDonations({ page })
}

const downloadReceipt = async (donationId: number | undefined) => {
  if (!donationId) {
    alert('Invalid donation ID')
    return
  }

  try {
    await donationsStore.downloadReceipt(donationId)
  } catch {
    alert('Failed to download receipt. Please try again.')
  }
}

const formatDate = (dateString: string | undefined) => {
  if (!dateString) return 'N/A'
  try {
    return new Date(dateString).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    })
  } catch {
    return 'Invalid Date'
  }
}

const getStatusClass = (status: string | undefined) => {
  if (!status) return 'bg-gray-100 text-gray-800'

  switch (status.toLowerCase()) {
    case 'completed':
      return 'bg-green-100 text-green-800'
    case 'pending':
      return 'bg-yellow-100 text-yellow-800'
    case 'failed':
      return 'bg-red-100 text-red-800'
    default:
      return 'bg-gray-100 text-gray-800'
  }
}

const getStatusText = (status: string | undefined) => {
  if (!status) return 'Unknown'

  switch (status.toLowerCase()) {
    case 'completed':
      return 'Completed'
    case 'pending':
      return 'Processing'
    case 'failed':
      return 'Failed'
    default:
      return 'Unknown'
  }
}

// Load donations and stats when component mounts
onMounted(() => {
  refreshDonations()
})
</script>
