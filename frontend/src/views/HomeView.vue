<template>
  <div class="space-y-8">
    <!-- Hero Section -->
    <section class="bg-gradient-to-r from-blue-600 to-blue-800 text-white rounded-lg p-8">
      <div class="max-w-4xl mx-auto text-center">
        <h1 class="text-4xl font-bold mb-4">
          ACME Corporate Social Responsibility
        </h1>
        <p class="text-xl mb-6">
          Together, we make a difference in our community through meaningful charitable initiatives.
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
          <router-link
            to="/campaigns"
            class="btn-primary bg-white text-blue-600 hover:bg-gray-100"
          >
            Browse Campaigns
          </router-link>
          <router-link
            v-if="authStore.isAuthenticated"
            to="/campaigns/create"
            class="btn-outline border-white text-white hover:bg-white hover:text-blue-600"
          >
            Create Campaign
          </router-link>
        </div>
      </div>
    </section>

    <!-- Stats Section -->
    <section class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div class="card text-center">
        <div class="text-3xl font-bold text-blue-600 mb-2">{{ formatSmartCurrency(totalRaised) }}</div>
        <div class="text-gray-600">Total Raised</div>
      </div>
      <div class="card text-center">
        <div class="text-3xl font-bold text-green-600 mb-2">{{ formatNumber(activeCampaignsCount) }}</div>
        <div class="text-gray-600">Active Campaigns</div>
      </div>
      <div class="card text-center">
        <div class="text-3xl font-bold text-purple-600 mb-2">{{ formatNumber(totalDonationsCount) }}</div>
        <div class="text-gray-600">Total Donations</div>
      </div>
    </section>

    <!-- Featured Campaigns -->
    <section v-if="campaignsStore.featuredCampaigns.length > 0">
      <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-gray-900">Featured Campaigns</h2>
        <router-link
          to="/campaigns"
          class="text-blue-600 hover:text-blue-800 font-medium"
        >
          View all →
        </router-link>
      </div>
      
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <CampaignCard
          v-for="campaign in campaignsStore.featuredCampaigns.slice(0, 3)"
          :key="campaign.id"
          :campaign="campaign"
        />
      </div>
    </section>

    <!-- Trending Campaigns -->
    <section v-if="campaignsStore.trendingCampaigns.length > 0">
      <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-gray-900">Trending This Week</h2>
        <router-link
          to="/campaigns"
          class="text-blue-600 hover:text-blue-800 font-medium"
        >
          View all →
        </router-link>
      </div>
      
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <CampaignCard
          v-for="campaign in campaignsStore.trendingCampaigns.slice(0, 3)"
          :key="campaign.id"
          :campaign="campaign"
        />
      </div>
    </section>

    <!-- How It Works -->
    <section class="bg-gray-50 rounded-lg p-8">
      <h2 class="text-2xl font-bold text-center text-gray-900 mb-8">
        How It Works
      </h2>
      
      <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <div class="text-center">
          <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <span class="text-2xl">🎯</span>
          </div>
          <h3 class="text-lg font-semibold mb-2">Create or Find</h3>
          <p class="text-gray-600">
            Create a campaign for a cause you care about or browse existing campaigns to support.
          </p>
        </div>
        
        <div class="text-center">
          <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <span class="text-2xl">💝</span>
          </div>
          <h3 class="text-lg font-semibold mb-2">Donate</h3>
          <p class="text-gray-600">
            Make secure donations to campaigns that align with your values and interests.
          </p>
        </div>
        
        <div class="text-center">
          <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <span class="text-2xl">🌟</span>
          </div>
          <h3 class="text-lg font-semibold mb-2">Make Impact</h3>
          <p class="text-gray-600">
            Track progress and see the real-world impact of your contributions to the community.
          </p>
        </div>
      </div>
    </section>

    <!-- Call to Action -->
    <section v-if="!authStore.isAuthenticated" class="text-center bg-blue-50 rounded-lg p-8">
      <h2 class="text-2xl font-bold text-gray-900 mb-4">
        Ready to Make a Difference?
      </h2>
      <p class="text-gray-600 mb-6">
        Join your colleagues in supporting meaningful causes and making a positive impact in our community.
      </p>
      <router-link
        to="/login"
        class="btn-primary"
      >
        Sign In to Get Started
      </router-link>
    </section>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useCampaignsStore } from '@/stores/campaigns'
import { campaignsApi } from '@/services/api'
import CampaignCard from '@/components/campaigns/CampaignCard.vue'
import { formatSmartCurrency, formatNumber } from '@/utils/formatters'

const authStore = useAuthStore()
const campaignsStore = useCampaignsStore()

// Reactive state
const totalRaisedData = ref({
  total_raised: '0.00',
  total_donations: 0,
  average_donation: '0.00'
})

const campaignStatsData = ref({
  active: 0,
  completed: 0,
  cancelled: 0,
  draft: 0,
  total: 0
})

// Computed properties
const activeCampaignsCount = computed(() => {
  return campaignStatsData.value.active
})

const totalRaised = computed(() => {
  return parseFloat(totalRaisedData.value.total_raised)
})

const totalDonationsCount = computed(() => {
  return totalRaisedData.value.total_donations
})

onMounted(async () => {
  // Fetch campaign stats and featured/trending campaigns
  await Promise.all([
    campaignsStore.fetchTrendingCampaigns(),
  ])
  
  // Fetch total raised data and campaign stats from new endpoints
  try {
    const [totalRaisedResponse, campaignStatsResponse] = await Promise.all([
      campaignsApi.getTotalRaised(),
      campaignsApi.getStats()
    ])
    
    totalRaisedData.value = totalRaisedResponse
    campaignStatsData.value = campaignStatsResponse
  } catch (error) {
    console.error('Failed to fetch dashboard data:', error)
    // Keep default values on error
  }
})
</script>
