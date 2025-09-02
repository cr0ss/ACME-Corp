<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">Edit Campaign</h1>
        <p class="text-gray-600 mt-1">Update your fundraising campaign details</p>
      </div>
      <div class="flex space-x-3">
        <router-link :to="`/campaigns/${campaignId}`" class="btn-secondary">
          View Campaign
        </router-link>
        <router-link to="/my-campaigns" class="btn-secondary">
          Back to My Campaigns
        </router-link>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="isLoadingCampaign" class="text-center py-8">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      <p class="text-gray-600 mt-2">Loading campaign details...</p>
    </div>

    <!-- Access Denied -->
    <div v-else-if="accessDenied" class="card text-center py-8">
      <svg class="mx-auto h-12 w-12 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
      </svg>
      <h3 class="text-lg font-medium text-gray-900 mt-2">Access Denied</h3>
      <p class="text-gray-600 mt-1">You can only edit campaigns that you created.</p>
      <router-link to="/my-campaigns" class="btn-primary mt-4 inline-flex">
        Go to My Campaigns
      </router-link>
    </div>

    <!-- Campaign Edit Form -->
    <div v-else class="card">
      <form @submit.prevent="handleSubmit" class="space-y-6">
        <!-- Basic Information -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="md:col-span-2">
            <label for="title" class="block text-sm font-medium text-gray-700 mb-1">
              Campaign Title *
            </label>
            <input
              id="title"
              v-model="form.title"
              type="text"
              required
              class="input-field"
              :class="{ 'border-red-500': errors.title }"
              placeholder="Enter a compelling campaign title"
            />
            <p v-if="errors.title" class="text-red-500 text-sm mt-1">{{ errors.title }}</p>
          </div>

          <div>
            <label for="category" class="block text-sm font-medium text-gray-700 mb-1">
              Category *
            </label>
            <select
              id="category"
              v-model="form.category_id"
              required
              class="input-field"
              :class="{ 'border-red-500': errors.category_id }"
            >
              <option value="">Select a category</option>
              <option v-for="category in campaignsStore.categories" :key="category.id" :value="category.id">
                {{ category.icon }} {{ category.name }}
              </option>
            </select>
            <p v-if="errors.category_id" class="text-red-500 text-sm mt-1">{{ errors.category_id }}</p>
          </div>

          <div>
            <label for="target_amount" class="block text-sm font-medium text-gray-700 mb-1">
              Target Amount ($) *
            </label>
            <input
              id="target_amount"
              v-model.number="form.target_amount"
              type="number"
              min="1"
              step="0.01"
              required
              class="input-field"
              :class="{ 'border-red-500': errors.target_amount }"
              placeholder="0.00"
            />
            <p v-if="errors.target_amount" class="text-red-500 text-sm mt-1">{{ errors.target_amount }}</p>
            <p v-if="form.target_amount && campaign && form.target_amount < campaign.current_amount" class="text-amber-600 text-sm mt-1">
              Warning: Target amount is less than current raised amount (${{ campaign.current_amount.toLocaleString() }})
            </p>
          </div>
        </div>

        <!-- Description -->
        <div>
          <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
            Description *
          </label>
          <textarea
            id="description"
            v-model="form.description"
            rows="6"
            required
            class="input-field"
            :class="{ 'border-red-500': errors.description }"
            placeholder="Describe your campaign, why it matters, and how the funds will be used..."
          ></textarea>
          <p v-if="errors.description" class="text-red-500 text-sm mt-1">{{ errors.description }}</p>
          <p class="text-sm text-gray-500 mt-1">{{ form.description.length }}/1000 characters</p>
        </div>

        <!-- Campaign Duration -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">
              Start Date *
            </label>
            <input
              id="start_date"
              v-model="form.start_date"
              type="date"
              required
              class="input-field"
              :class="{ 'border-red-500': errors.start_date }"
              :disabled="campaign?.status === 'active' && isPastDate(campaign.start_date)"
            />
            <p v-if="errors.start_date" class="text-red-500 text-sm mt-1">{{ errors.start_date }}</p>
            <p v-if="campaign?.status === 'active' && isPastDate(campaign.start_date)" class="text-gray-500 text-sm mt-1">
              Cannot modify start date of active campaign that has already started
            </p>
          </div>

          <div>
            <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">
              End Date *
            </label>
            <input
              id="end_date"
              v-model="form.end_date"
              type="date"
              required
              class="input-field"
              :class="{ 'border-red-500': errors.end_date }"
              :min="minEndDate"
            />
            <p v-if="errors.end_date" class="text-red-500 text-sm mt-1">{{ errors.end_date }}</p>
          </div>
        </div>

        <!-- Status (Admin Only) -->
        <div v-if="authStore.user?.is_admin">
          <label for="status" class="block text-sm font-medium text-gray-700 mb-1">
            Campaign Status (Admin Only)
          </label>
          <select
            id="status"
            v-model="form.status"
            class="input-field"
          >
            <option value="draft">Draft</option>
            <option value="active">Active</option>
            <option value="completed" :disabled="!isEligibleForCompletion">Completed</option>
          </select>
          <p class="text-sm text-gray-500 mt-1">
            <span v-if="form.status === 'draft'">Draft campaigns are not visible to other users</span>
            <span v-else-if="form.status === 'active'">Active campaigns are live and accepting donations</span>
            <span v-else-if="form.status === 'completed'">Completed campaigns have reached their goal or ended</span>
          </p>
        </div>

        <!-- Status Display for Regular Users -->
        <div v-else>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Campaign Status
          </label>
          <div class="input-field bg-gray-50 cursor-not-allowed">
            <span
              class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
              :class="getStatusDisplayClass(form.status)"
            >
              {{ form.status.charAt(0).toUpperCase() + form.status.slice(1) }}
            </span>
          </div>
          <p class="text-sm text-gray-500 mt-1">
            Campaign status can only be changed by administrators. Contact an admin to activate your campaign.
          </p>
        </div>

        <!-- Options -->
        <div class="space-y-3">
          <div class="flex items-center">
            <input
              id="featured"
              v-model="form.featured"
              type="checkbox"
              class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
            />
            <label for="featured" class="ml-2 block text-sm text-gray-700">
              Request to feature this campaign (subject to admin approval)
            </label>
          </div>
        </div>

        <!-- Campaign Stats (Read-only) -->
        <div v-if="campaign" class="bg-gray-50 rounded-lg p-4">
          <h3 class="text-sm font-medium text-gray-900 mb-3">Campaign Statistics</h3>
          <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
            <div>
              <div class="text-2xl font-bold text-blue-600">${{ campaign.current_amount.toLocaleString() }}</div>
              <div class="text-xs text-gray-500">Raised</div>
            </div>
            <div>
              <div class="text-2xl font-bold text-green-600">{{ campaign.progress_percentage }}%</div>
              <div class="text-xs text-gray-500">Progress</div>
            </div>
            <div>
              <div class="text-2xl font-bold text-purple-600">{{ campaign.donations_count || 0 }}</div>
              <div class="text-xs text-gray-500">Donations</div>
            </div>
            <div>
              <div class="text-2xl font-bold text-gray-600">{{ daysBetween(campaign.start_date, campaign.end_date) }}</div>
              <div class="text-xs text-gray-500">Total Days</div>
            </div>
          </div>
        </div>

        <!-- Error Display -->
        <div v-if="submitError" class="bg-red-50 border border-red-200 rounded-md p-4">
          <div class="flex">
            <div class="flex-shrink-0">
              <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
              </svg>
            </div>
            <div class="ml-3">
              <h3 class="text-sm font-medium text-red-800">
                Error updating campaign
              </h3>
              <div class="mt-2 text-sm text-red-700">
                {{ submitError }}
              </div>
            </div>
          </div>
        </div>

        <!-- Submit Buttons -->
        <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
          <router-link to="/my-campaigns" class="btn-secondary">
            Cancel
          </router-link>
          <button
            type="submit"
            :disabled="isSubmitting"
            class="btn-primary"
            :class="{ 'opacity-50 cursor-not-allowed': isSubmitting }"
          >
            <span v-if="isSubmitting">Updating...</span>
            <span v-else>Update Campaign</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useCampaignsStore } from '@/stores/campaigns'
import { useAuthStore } from '@/stores/auth'
import type { Campaign } from '@/services/api'

const route = useRoute()
const router = useRouter()
const campaignsStore = useCampaignsStore()
const authStore = useAuthStore()

// State
const campaign = ref<Campaign | null>(null)
const isLoadingCampaign = ref(false)
const accessDenied = ref(false)
const submitError = ref('')
const isSubmitting = ref(false)

// Get campaign ID from route
const campaignId = computed(() => parseInt(route.params.id as string))

// Form data
const form = ref({
  title: '',
  description: '',
  category_id: '',
  target_amount: null as number | null,
  start_date: '',
  end_date: '',
  featured: false,
  status: 'draft'
})

// Form validation
const errors = ref<Record<string, string>>({})

// Date helpers
const today = computed(() => {
  const date = new Date()
  return date.toISOString().split('T')[0]
})

const minEndDate = computed(() => {
  if (!form.value.start_date) return today.value
  const startDate = new Date(form.value.start_date)
  startDate.setDate(startDate.getDate() + 1)
  return startDate.toISOString().split('T')[0]
})

const isEligibleForCompletion = computed(() => {
  return campaign.value && (
    campaign.value.progress_percentage >= 100 ||
    new Date(campaign.value.end_date) < new Date()
  )
})

// Helper functions
function isPastDate(dateString: string) {
  return new Date(dateString) < new Date()
}

function daysBetween(startDate: string, endDate: string) {
  const start = new Date(startDate)
  const end = new Date(endDate)
  const diffTime = Math.abs(end.getTime() - start.getTime())
  return Math.ceil(diffTime / (1000 * 60 * 60 * 24))
}

function getStatusDisplayClass(status: string) {
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

// Load campaign data
async function loadCampaign() {
  isLoadingCampaign.value = true
  accessDenied.value = false

  try {
    const response = await campaignsStore.fetchCampaign(campaignId.value)
    campaign.value = response.campaign

    // Check if user owns this campaign
    if (campaign.value.user.id !== authStore.user?.id) {
      accessDenied.value = true
      return
    }

    // Populate form with campaign data
    form.value = {
      title: campaign.value.title,
      description: campaign.value.description,
      category_id: campaign.value.category.id.toString(),
      target_amount: campaign.value.target_amount,
      start_date: campaign.value.start_date,
      end_date: campaign.value.end_date,
      featured: campaign.value.featured,
      status: campaign.value.status
    }
  } catch (error) {
    console.error('Failed to load campaign:', error)
    router.push('/my-campaigns')
  } finally {
    isLoadingCampaign.value = false
  }
}

// Validation function
function validateForm() {
  errors.value = {}
  
  if (!form.value.title.trim()) {
    errors.value.title = 'Campaign title is required'
  } else if (form.value.title.length < 3) {
    errors.value.title = 'Campaign title must be at least 3 characters'
  }
  
  if (!form.value.category_id) {
    errors.value.category_id = 'Please select a category'
  }
  
  if (!form.value.target_amount || form.value.target_amount <= 0) {
    errors.value.target_amount = 'Target amount must be greater than 0'
  }
  
  if (!form.value.description.trim()) {
    errors.value.description = 'Campaign description is required'
  } else if (form.value.description.length < 50) {
    errors.value.description = 'Description must be at least 50 characters'
  } else if (form.value.description.length > 1000) {
    errors.value.description = 'Description must be less than 1000 characters'
  }
  
  if (!form.value.start_date) {
    errors.value.start_date = 'Start date is required'
  }
  
  if (!form.value.end_date) {
    errors.value.end_date = 'End date is required'
  } else if (form.value.start_date && form.value.end_date <= form.value.start_date) {
    errors.value.end_date = 'End date must be after start date'
  }
  
  return Object.keys(errors.value).length === 0
}

// Submit handler
async function handleSubmit() {
  if (!validateForm()) {
    return
  }
  
  if (!authStore.isAuthenticated || !campaign.value) {
    submitError.value = 'Unable to update campaign'
    return
  }
  
  isSubmitting.value = true
  submitError.value = ''
  
  try {
    const updateData: any = {
      title: form.value.title.trim(),
      description: form.value.description.trim(),
      category_id: parseInt(form.value.category_id),
      target_amount: form.value.target_amount!,
      start_date: form.value.start_date,
      end_date: form.value.end_date,
      featured: form.value.featured,
    }
    
    // Only include status if user is admin
    if (authStore.user?.is_admin) {
      updateData.status = form.value.status
    }
    
    await campaignsStore.updateCampaign(campaignId.value, updateData)
    
    // Success - redirect to my campaigns
    router.push('/my-campaigns')
  } catch (error: any) {
    submitError.value = error.message || 'Failed to update campaign'
  } finally {
    isSubmitting.value = false
  }
}

// Initialize
onMounted(async () => {
  // Load categories if not already loaded
  if (campaignsStore.categories.length === 0) {
    await campaignsStore.fetchCategories()
  }
  
  await loadCampaign()
})
</script>
