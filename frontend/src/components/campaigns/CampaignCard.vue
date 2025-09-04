<template>
  <div class="card hover:shadow-md transition-shadow duration-200">
    <!-- Campaign Image/Icon -->
    <div class="mb-4">
      <div class="w-full h-48 bg-gradient-to-br from-blue-100 to-blue-200 rounded-lg flex items-center justify-center">
        <span class="text-4xl">{{ campaign.category.icon || '🎯' }}</span>
      </div>
    </div>

    <!-- Campaign Info -->
    <div class="space-y-3">
      <!-- Title and Category -->
      <div>
        <div class="flex items-center justify-between mb-1">
          <span class="badge badge-primary">{{ campaign.category.name }}</span>
          <span v-if="campaign.featured" class="badge badge-warning">Featured</span>
        </div>
        <h3 class="text-lg font-semibold text-gray-900 line-clamp-2">
          {{ campaign.title }}
        </h3>
      </div>

      <!-- Description -->
      <p class="text-sm text-gray-600 line-clamp-3">
        {{ campaign.description }}
      </p>

      <!-- Progress -->
      <div class="space-y-2">
        <div class="flex justify-between text-sm">
          <span class="text-gray-600">Progress</span>
          <span class="font-medium">{{ campaign.progress_percentage }}%</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-2">
          <div
            class="bg-blue-600 h-2 rounded-full transition-all duration-300"
            :style="{ width: `${Math.min(campaign.progress_percentage, 100)}%` }"
          ></div>
        </div>
        <div class="flex justify-between text-sm">
          <span class="text-gray-600">
            ${{ formatNumber(campaign.current_amount) }} raised
          </span>
          <span class="text-gray-600">
            ${{ formatNumber(campaign.target_amount) }} goal
          </span>
        </div>
      </div>

      <!-- Status and Timing -->
      <div class="flex items-center justify-between text-xs text-gray-500">
        <span class="badge" :class="getStatusClass(campaign.status)">
          {{ getStatusText(campaign.status) }}
        </span>
        <span v-if="campaign.status === 'active'">
          {{ getDaysRemaining(campaign.end_date) }}
        </span>
      </div>

      <!-- Creator -->
      <div class="flex items-center text-sm text-gray-600">
        <span class="w-6 h-6 bg-gray-300 rounded-full flex items-center justify-center text-xs font-medium mr-2">
          {{ campaign.user.name.charAt(0).toUpperCase() }}
        </span>
        <span>by {{ campaign.user.name }}</span>
      </div>
    </div>

    <!-- Actions -->
    <div class="mt-4 pt-4 border-t border-gray-200 flex gap-2">
      <router-link
        :to="`/campaigns/${campaign.id}`"
        class="btn-primary flex-1 text-center"
      >
        View Details
      </router-link>
      <button
        v-if="campaign.status === 'active' && authStore.isAuthenticated"
        @click="$emit('donate', campaign)"
        class="btn-outline px-3"
      >
        Donate
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { Campaign } from '@/services/api'
import { useAuthStore } from '@/stores/auth'

interface Props {
  campaign: Campaign
}

const props = defineProps<Props>()

const authStore = useAuthStore()

// Emits
defineEmits<{
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
