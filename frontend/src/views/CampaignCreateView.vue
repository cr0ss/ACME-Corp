<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">Create Campaign</h1>
        <p class="text-gray-600 mt-1">Start a fundraising campaign for a cause you believe in</p>
      </div>
      <router-link to="/campaigns" class="btn-secondary"> Back to Campaigns </router-link>
    </div>

    <!-- Campaign Creation Form -->
    <div class="card">
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
              <option
                v-for="category in campaignsStore.categories"
                :key="category.id"
                :value="category.id"
              >
                {{ category.icon }} {{ category.name }}
              </option>
            </select>
            <p v-if="errors.category_id" class="text-red-500 text-sm mt-1">
              {{ errors.category_id }}
            </p>
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
            <p v-if="errors.target_amount" class="text-red-500 text-sm mt-1">
              {{ errors.target_amount }}
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
          <p v-if="errors.description" class="text-red-500 text-sm mt-1">
            {{ errors.description }}
          </p>
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
              :min="today"
            />
            <p v-if="errors.start_date" class="text-red-500 text-sm mt-1">
              {{ errors.start_date }}
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

        <!-- Campaign Submission Note -->
        <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
          <div class="flex">
            <div class="flex-shrink-0">
              <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                <path
                  fill-rule="evenodd"
                  d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                  clip-rule="evenodd"
                />
              </svg>
            </div>
            <div class="ml-3">
              <h3 class="text-sm font-medium text-blue-800">Campaign Review Process</h3>
              <div class="mt-2 text-sm text-blue-700">
                <p>
                  Your campaign will be submitted as a draft for admin review. Once approved by an
                  administrator, it will become active and visible to all users for donations.
                </p>
              </div>
            </div>
          </div>
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

        <!-- Error Display -->
        <div v-if="submitError" class="bg-red-50 border border-red-200 rounded-md p-4">
          <div class="flex">
            <div class="flex-shrink-0">
              <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                <path
                  fill-rule="evenodd"
                  d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                  clip-rule="evenodd"
                />
              </svg>
            </div>
            <div class="ml-3">
              <h3 class="text-sm font-medium text-red-800">Error creating campaign</h3>
              <div class="mt-2 text-sm text-red-700">
                {{ submitError }}
              </div>
            </div>
          </div>
        </div>

        <!-- Submit Buttons -->
        <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
          <router-link to="/campaigns" class="btn-secondary"> Cancel </router-link>
          <button
            type="submit"
            :disabled="isSubmitting"
            class="btn-primary"
            :class="{ 'opacity-50 cursor-not-allowed': isSubmitting }"
          >
            <span v-if="isSubmitting">Creating...</span>
            <span v-else>Create Campaign</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useCampaignsStore } from '@/stores/campaigns'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const campaignsStore = useCampaignsStore()
const authStore = useAuthStore()

// Form data
const form = ref({
  title: '',
  description: '',
  category_id: '',
  target_amount: null as number | null,
  start_date: '',
  end_date: '',
  featured: false,
})

// Form validation
const errors = ref<Record<string, string>>({})
const submitError = ref('')
const isSubmitting = ref(false)

// Date helpers
const today = computed(() => {
  const date = new Date()
  return date.toISOString().split('T')[0]
})

const minEndDate = computed(() => {
  if (!form.value.start_date) return today.value
  const startDate = new Date(form.value.start_date)
  startDate.setDate(startDate.getDate() + 1) // End date must be at least 1 day after start
  return startDate.toISOString().split('T')[0]
})

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

  if (!authStore.isAuthenticated) {
    submitError.value = 'You must be logged in to create a campaign'
    return
  }

  isSubmitting.value = true
  submitError.value = ''

  try {
    await campaignsStore.createCampaign({
      title: form.value.title.trim(),
      description: form.value.description.trim(),
      category_id: parseInt(form.value.category_id),
      target_amount: form.value.target_amount!,
      start_date: form.value.start_date,
      end_date: form.value.end_date,
      featured: form.value.featured,
      status: 'draft', // Start as draft, can be activated later
    })

    // Success - redirect to campaigns list
    router.push('/campaigns')
  } catch (error: unknown) {
    submitError.value = error.message || 'Failed to create campaign'
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

  // Set default dates
  form.value.start_date = today.value
  const defaultEndDate = new Date()
  defaultEndDate.setDate(defaultEndDate.getDate() + 30) // Default to 30 days from now
  form.value.end_date = defaultEndDate.toISOString().split('T')[0]
})
</script>
