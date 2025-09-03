<template>
  <div class="space-y-6">
    <!-- Loading State -->
    <div v-if="campaignsStore.isLoading" class="text-center py-8">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      <p class="mt-2 text-gray-600">Loading campaign...</p>
    </div>

    <!-- Campaign Details -->
    <div v-else-if="campaign" class="space-y-6">
      <!-- Back Button -->
      <button @click="$router.go(-1)" class="flex items-center text-gray-600 hover:text-gray-900">
        ← Back to Campaigns
      </button>

      <!-- Campaign Header -->
      <div class="card">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
          <!-- Campaign Image/Icon -->
          <div class="lg:col-span-1">
            <div
              class="w-full h-64 bg-gradient-to-br from-blue-100 to-blue-200 rounded-lg flex items-center justify-center"
            >
              <span class="text-6xl">{{ campaign.category.icon || '🎯' }}</span>
            </div>
          </div>

          <!-- Campaign Info -->
          <div class="lg:col-span-2 space-y-4">
            <div class="flex items-start justify-between">
              <div>
                <div class="flex items-center gap-2 mb-2">
                  <span class="badge badge-primary">{{ campaign.category.name }}</span>
                  <span v-if="campaign.featured" class="badge badge-warning">Featured</span>
                  <span class="badge" :class="getStatusClass(campaign.status)">
                    {{ getStatusText(campaign.status) }}
                  </span>
                </div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ campaign.title }}</h1>
                <div class="flex items-center text-sm text-gray-600">
                  <span
                    class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center text-xs font-medium mr-2"
                  >
                    {{ campaign.user.name.charAt(0).toUpperCase() }}
                  </span>
                  <span>Created by {{ campaign.user.name }} • {{ campaign.user.department }}</span>
                </div>
              </div>
            </div>

            <!-- Progress -->
            <div class="space-y-3">
              <div class="flex justify-between text-lg font-semibold">
                <span>${{ formatNumber(campaign.current_amount || 0) }} raised</span>
                <span>{{ formatProgressPercentage() }}</span>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-4 overflow-hidden">
                <div
                  class="bg-blue-600 h-4 rounded-full transition-all duration-300"
                  :style="{ width: `${calculateProgressPercentage()}%` }"
                ></div>
              </div>
              <div class="flex justify-between text-sm text-gray-600">
                <span>Goal: ${{ formatNumber(campaign.target_amount || 0) }}</span>
                <span v-if="campaign.status === 'active'">
                  {{ getDaysRemaining(campaign.end_date) }}
                </span>
                <span v-else-if="campaign.status === 'completed' && calculateProgressPercentage() >= 100">
                  Goal reached!
                </span>
                <span v-else-if="campaign.status === 'completed' && calculateProgressPercentage() < 100">
                  Campaign ended ({{ formatProgressPercentage() }} of goal)
                </span>
                <span v-else-if="campaign.status === 'draft'">
                  Draft campaign
                </span>
                <span v-else-if="campaign.status === 'cancelled'">
                  Cancelled
                </span>
              </div>
              

            </div>

            <!-- Donate Button -->
            <div v-if="campaign.status === 'active' && authStore.isAuthenticated">
              <button @click="showDonateModal = true" class="btn-primary w-full">Donate Now</button>
            </div>
            <div v-else-if="!authStore.isAuthenticated" class="p-4 bg-blue-50 rounded-lg">
              <p class="text-blue-800 text-center">
                <router-link to="/login" class="font-medium underline">Sign in</router-link>
                to support this campaign
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Campaign Description -->
      <div class="card">
        <h2 class="text-xl font-bold text-gray-900 mb-4">About This Campaign</h2>
        <div class="prose max-w-none">
          <p class="text-gray-700 leading-relaxed">{{ campaign.description }}</p>
        </div>
      </div>

      <!-- Campaign Stats -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="card text-center">
          <div class="text-2xl font-bold text-blue-600 mb-1">{{ stats?.total_donations || 0 }}</div>
          <div class="text-gray-600">Donations</div>
        </div>
        <div class="card text-center">
          <div class="text-2xl font-bold text-green-600 mb-1">
            ${{ formatNumber(stats?.total_donated || 0) }}
          </div>
          <div class="text-gray-600">Total Donated</div>
        </div>
        <div class="card text-center">
          <div class="text-2xl font-bold text-purple-600 mb-1">
            {{ stats?.days_remaining || 0 }}
          </div>
          <div class="text-gray-600">Days Remaining</div>
        </div>
      </div>
    </div>

    <!-- Error State -->
    <div v-else class="card bg-red-50 border-red-200">
      <p class="text-red-600">Campaign not found or failed to load.</p>
    </div>

    <!-- Donation Modal -->
    <DonationModal
      v-if="showDonateModal"
      :campaign="campaign"
      @close="showDonateModal = false"
      @success="handleDonationSuccess"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useCampaignsStore } from '@/stores/campaigns'
import DonationModal from '@/components/donations/DonationModal.vue'
import { formatNumber, formatPercentage } from '@/utils/formatters'

const route = useRoute()
const authStore = useAuthStore()
const campaignsStore = useCampaignsStore()

const showDonateModal = ref(false)

// Handle successful donation
const handleDonationSuccess = (donation: { amount: number }) => {
  showDonateModal.value = false
  // Refresh campaign data to show updated totals
  const campaignId = parseInt(route.params.id as string)
  campaignsStore.fetchCampaign(campaignId)

  // Show success message (you could use a toast notification here)
  alert(`Thank you for your donation of $${donation.amount}!`)
}

// Computed
const campaign = computed(() => campaignsStore.currentCampaign)
const stats = computed(() => {
  // This would come from the API response
  return {
    total_donations: 25,
    total_donated: campaign.value?.current_amount || 0,
    days_remaining: getDaysRemaining(campaign.value?.end_date || ''),
  }
})

// Methods

function getStatusClass(status: string): string {
  switch (status) {
    case 'active':
      return 'badge-success'
    case 'completed':
      return 'badge-primary'
    case 'draft':
      return 'badge-warning'
    case 'cancelled':
      return 'badge-danger'
    default:
      return 'badge-secondary'
  }
}

function getStatusText(status: string): string {
  switch (status) {
    case 'active':
      return 'Active'
    case 'completed':
      return 'Completed'
    case 'draft':
      return 'Draft'
    case 'cancelled':
      return 'Cancelled'
    default:
      return status
  }
}

function getDaysRemaining(endDate: string): string {
  const end = new Date(endDate)
  const now = new Date()
  const diffTime = end.getTime() - now.getTime()
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))

  if (diffDays < 0) return 'Ended'
  if (diffDays === 0) return 'Ends today'
  if (diffDays === 1) return '1 day left'
  return `${diffDays} days left`
}

function calculateProgressPercentage(): number {
  const current = Number(campaign.value?.current_amount) || 0
  const target = Number(campaign.value?.target_amount) || 1
  
  if (target <= 0) return 0
  
  const percentage = (current / target) * 100
  return Math.min(Math.max(percentage, 0), 100) // Clamp between 0 and 100
}

function formatProgressPercentage(): string {
  const percentage = calculateProgressPercentage()
  return formatPercentage(percentage / 100) // formatPercentage expects decimal (0.25 for 25%)
}

// Lifecycle
onMounted(async () => {
  const campaignId = parseInt(route.params.id as string)
  if (campaignId) {
    await campaignsStore.fetchCampaign(campaignId)
  }
})
</script>

<style scoped>
.badge-primary {
  @apply bg-blue-100 text-blue-800;
}
.badge-secondary {
  @apply bg-gray-100 text-gray-800;
}
.badge-success {
  @apply bg-green-100 text-green-800;
}
.badge-warning {
  @apply bg-yellow-100 text-yellow-800;
}
.badge-danger {
  @apply bg-red-100 text-red-800;
}
</style>
