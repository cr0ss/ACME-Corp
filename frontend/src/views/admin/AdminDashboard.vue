<template>
  <div class="space-y-6">
    <!-- Admin Navigation -->
    <div class="border-b border-gray-200">
      <nav class="flex space-x-8">
        <router-link 
          :to="{ name: 'admin' }" 
          exact-active-class="border-blue-500 text-blue-600"
          class="py-4 px-1 border-b-2 border-transparent font-medium text-sm hover:text-gray-700 hover:border-gray-300"
        >
          Dashboard
        </router-link>
        <router-link 
          :to="{ name: 'admin-campaigns' }" 
          active-class="border-blue-500 text-blue-600"
          class="py-4 px-1 border-b-2 border-transparent font-medium text-sm hover:text-gray-700 hover:border-gray-300"
        >
          Campaigns
        </router-link>
        <router-link 
          :to="{ name: 'admin-users' }" 
          active-class="border-blue-500 text-blue-600"
          class="py-4 px-1 border-b-2 border-transparent font-medium text-sm hover:text-gray-700 hover:border-gray-300"
        >
          Users
        </router-link>
        <router-link 
          :to="{ name: 'admin-reports' }" 
          active-class="border-blue-500 text-blue-600"
          class="py-4 px-1 border-b-2 border-transparent font-medium text-sm hover:text-gray-700 hover:border-gray-300"
        >
          Reports
        </router-link>
      </nav>
    </div>

    <!-- Content Area -->
    <div>
      <!-- Dashboard content (when on main admin route) -->
      <div v-if="$route.name === 'admin'">
        <div class="flex justify-between items-center mb-6">
          <h1 class="text-3xl font-bold text-gray-900">Admin Dashboard</h1>
        </div>

        <!-- Loading State -->
        <div v-if="isLoading" class="text-center py-8">
          <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
          <p class="text-gray-600 mt-2">Loading dashboard data...</p>
        </div>

        <!-- Stats Cards -->
        <div v-else class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
          <div class="card text-center">
            <div class="text-3xl font-bold text-blue-600 mb-2">{{ formattedStats.activeCampaigns }}</div>
            <div class="text-gray-600">Active Campaigns</div>
          </div>
          <div class="card text-center">
            <div class="text-3xl font-bold text-green-600 mb-2">{{ formattedStats.totalDonations }}</div>
            <div class="text-gray-600">Total Donations</div>
          </div>
          <div class="card text-center">
            <div class="text-3xl font-bold text-purple-600 mb-2">{{ formattedStats.totalRaised }}</div>
            <div class="text-gray-600">Total Raised</div>
          </div>
          <div class="card text-center">
            <div class="text-3xl font-bold text-orange-600 mb-2">{{ formattedStats.averageDonation }}</div>
            <div class="text-gray-600">Avg Donation</div>
          </div>
        </div>

        <!-- Quick Actions -->
        <div class="card mb-6">
          <h2 class="text-xl font-bold text-gray-900 mb-4">Quick Actions</h2>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <router-link to="/admin/campaigns" class="btn-primary text-center">
              Manage Campaigns
            </router-link>
            <router-link to="/admin/users" class="btn-secondary text-center">
              Manage Users
            </router-link>
            <router-link to="/admin/reports" class="btn-outline text-center">
              View Reports
            </router-link>
          </div>
        </div>

        <!-- Recent Activity -->
        <div class="card">
          <h2 class="text-xl font-bold text-gray-900 mb-4">Recent Activity</h2>
          <p class="text-gray-600">Recent activity tracking will be implemented here...</p>
        </div>
      </div>

      <!-- Child route content -->
      <router-view v-else />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useCampaignsStore } from '@/stores/campaigns'
import { donationsApi, campaignsApi } from '@/services/api'
import { formatNumber, formatSmartCurrency, formatCurrency } from '@/utils/formatters'

const campaignsStore = useCampaignsStore()

// State
const isLoading = ref(false)
const totalDonationsCount = ref(0)
const totalRaised = ref(0)

// Computed stats
const stats = computed(() => {
  return {
    activeCampaigns: campaignsStore.activeCampaignsCount,
    totalDonations: totalDonationsCount.value,
    totalRaised: totalRaised.value
  }
})

// Computed formatted values
const formattedStats = computed(() => {
  return {
    activeCampaigns: formatNumber(stats.value.activeCampaigns),
    totalDonations: formatNumber(stats.value.totalDonations),
    totalRaised: formatSmartCurrency(stats.value.totalRaised),
    averageDonation: stats.value.totalDonations > 0 
      ? formatCurrency(stats.value.totalRaised / stats.value.totalDonations)
      : formatCurrency(0)
  }
})

// Fetch data on mount
async function fetchDashboardData() {
  isLoading.value = true
  try {
    // Fetch all dashboard data in parallel
    const [campaignStatsResult, donationsResult, totalRaisedResult] = await Promise.allSettled([
      campaignsStore.fetchCampaignStats(),
      donationsApi.getAll(),
      campaignsApi.getTotalRaised()
    ])
    
    // Handle donations count
    if (donationsResult.status === 'fulfilled') {
      totalDonationsCount.value = donationsResult.value.total || donationsResult.value.data.length
    } else {
      console.error('Failed to fetch donations data:', donationsResult.reason)
      totalDonationsCount.value = 0
    }
    
    // Handle total raised
    if (totalRaisedResult.status === 'fulfilled') {
      totalRaised.value = parseFloat(totalRaisedResult.value.total_raised) || 0
    } else {
      console.error('Failed to fetch total raised data:', totalRaisedResult.reason)
      totalRaised.value = 0
    }
    
    // Handle campaign stats error
    if (campaignStatsResult.status === 'rejected') {
      console.error('Failed to fetch campaign stats:', campaignStatsResult.reason)
    }
  } catch (error) {
    console.error('Failed to fetch dashboard data:', error)
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  fetchDashboardData()
})
</script>
