<template>
  <div class="card hover:shadow-lg transition-shadow duration-200">
    <!-- Campaign Image -->
    <div class="relative">
              <div class="w-full h-48 bg-gradient-to-br from-blue-100 to-blue-200 rounded-t-lg flex items-center justify-center">
          <span class="text-4xl">{{ campaign.category?.icon || '🎯' }}</span>
        </div>
      <div class="absolute top-3 left-3">
        <span :class="getStatusClass(campaign.status)" class="badge">
          {{ getStatusText(campaign.status) }}
        </span>
      </div>
      <div class="absolute top-3 right-3">
        <span class="badge badge-primary">{{ campaign.category?.name || 'Uncategorized' }}</span>
      </div>
    </div>

    <!-- Campaign Content -->
    <div class="p-4">
      <h3 class="text-lg font-semibold text-gray-900 mb-2 line-clamp-2">
        {{ campaign.title }}
      </h3>
      <p class="text-gray-600 text-sm mb-4 line-clamp-3">{{ campaign.description }}</p>

      <!-- Progress Bar -->
      <div class="mb-4">
        <div class="flex justify-between text-sm text-gray-600 mb-1">
          <span>Progress</span>
          <span>{{ Math.round((campaign.current_amount / campaign.target_amount) * 100) }}%</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-2">
          <div
            class="bg-blue-600 h-2 rounded-full transition-all duration-300"
            :style="{
              width: `${Math.min((campaign.current_amount / campaign.target_amount) * 100, 100)}%`,
            }"
          ></div>
        </div>
        <div class="flex justify-between text-sm text-gray-600 mt-1">
          <span>${{ formatNumber(campaign.current_amount) }}</span>
          <span>${{ formatNumber(campaign.target_amount) }}</span>
        </div>
      </div>

      <!-- Campaign Stats -->
      <div class="grid grid-cols-2 gap-4 mb-4 text-sm">
        <div class="text-center">
          <div class="text-lg font-semibold text-blue-600">{{ campaign.donations_count || 0 }}</div>
          <div class="text-gray-600">Donations</div>
        </div>
        <div class="text-center">
          <div class="text-lg font-semibold text-green-600">
            {{ getDaysRemaining(campaign.end_date) }}
          </div>
          <div class="text-gray-600">Remaining</div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="mt-4 pt-4 border-t border-gray-200 flex gap-2">
        <router-link :to="`/campaigns/${campaign.id}`" class="btn-primary flex-1 text-center">
          View Details
        </router-link>
        <button
          v-if="campaign.status === 'active' && authStore.isAuthenticated"
          @click="handleDonateClick"
          class="btn-outline px-3"
        >
          Donate
        </button>
      </div>
    </div>


  </div>
</template>

<script setup lang="ts">
import type { Campaign } from '@/services/api'
import { useAuthStore } from '@/stores/auth'

interface Props {
  campaign: Campaign
}

const props = defineProps<Props>()

const authStore = useAuthStore()

// Handle donate button click
const handleDonateClick = () => {
  // Emit the donate event to parent component to open the modal
  emit('donate', props.campaign)
}

// Emits
const emit = defineEmits<{
  donate: [campaign: Campaign]
}>()

// Format number with commas
function formatNumber(num: number): string {
  return new Intl.NumberFormat().format(num)
}

// Get status badge class
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

// Get status text
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

// Get days remaining
function getDaysRemaining(endDate: string): string {
  const end = new Date(endDate)
  const now = new Date()
  const diffTime = end.getTime() - now.getTime()
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))

  if (diffDays < 0) {
    return 'Ended'
  } else if (diffDays === 0) {
    return 'Ends today'
  } else if (diffDays === 1) {
    return '1 day left'
  } else {
    return `${diffDays} days left`
  }
}
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.line-clamp-3 {
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.badge-primary {
  @apply bg-blue-100 text-blue-800;
}

.badge-secondary {
  @apply bg-gray-100 text-gray-800;
}
</style>
