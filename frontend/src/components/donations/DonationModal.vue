<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 max-w-lg w-full mx-4 max-h-[90vh] overflow-y-auto">
      <div class="flex justify-between items-center mb-6">
        <h3 class="text-xl font-bold text-gray-900">Support {{ campaign?.title }}</h3>
        <button @click="handleClose" class="text-gray-400 hover:text-gray-600">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M6 18L18 6M6 6l12 12"
            ></path>
          </svg>
        </button>
      </div>

      <form @submit.prevent="submitDonation" class="space-y-6">
        <!-- Donation Amount -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Donation Amount</label>
          <div class="space-y-3">
            <!-- Quick Amount Buttons -->
            <div class="grid grid-cols-4 gap-2">
              <button
                v-for="quickAmount in quickAmounts"
                :key="quickAmount"
                type="button"
                @click="donationForm.amount = quickAmount"
                :class="[
                  'px-3 py-2 text-sm font-medium rounded-md border transition-colors',
                  donationForm.amount === quickAmount
                    ? 'bg-blue-600 text-white border-blue-600'
                    : 'bg-white text-gray-700 border-gray-300 hover:border-blue-300',
                ]"
              >
                ${{ quickAmount }}
              </button>
            </div>

            <!-- Custom Amount Input -->
            <div class="relative">
              <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500"
                >$</span
              >
              <input
                v-model.number="donationForm.amount"
                type="number"
                min="1"
                step="0.01"
                required
                placeholder="Enter custom amount"
                class="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
          </div>
          <p v-if="amountError" class="mt-1 text-sm text-red-600">{{ amountError }}</p>
        </div>

        <!-- Payment Method -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Payment Method</label>
          <div class="grid grid-cols-2 gap-3">
            <button
              v-for="method in paymentMethods"
              :key="method.value"
              type="button"
              @click="donationForm.payment_method = method.value"
              :class="[
                'flex items-center justify-center px-4 py-3 border rounded-md transition-colors',
                donationForm.payment_method === method.value
                  ? 'border-blue-600 bg-blue-50 text-blue-700'
                  : 'border-gray-300 bg-white text-gray-700 hover:border-blue-300',
              ]"
            >
              {{ method.label }}
            </button>
          </div>
        </div>

        <!-- Anonymous Donation -->
        <div class="flex items-center">
          <input
            v-model="donationForm.anonymous"
            type="checkbox"
            id="anonymous"
            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
          />
          <label for="anonymous" class="ml-2 block text-sm text-gray-700">
            Make this donation anonymous
          </label>
        </div>

        <!-- Optional Message -->
        <div>
          <label for="message" class="block text-sm font-medium text-gray-700 mb-2">
            Optional Message (Public)
          </label>
          <textarea
            v-model="donationForm.message"
            id="message"
            rows="3"
            maxlength="500"
            placeholder="Leave a message of support..."
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          ></textarea>
          <p class="mt-1 text-sm text-gray-500">{{ messageLength }}/500 characters</p>
        </div>

        <!-- Campaign Info Summary -->
        <div class="bg-gray-50 rounded-lg p-4">
          <h4 class="font-medium text-gray-900 mb-2">Donation Summary</h4>
          <div class="space-y-1 text-sm text-gray-600">
            <div class="flex justify-between">
              <span>Campaign:</span>
              <span class="font-medium">{{ campaign?.title }}</span>
            </div>
            <div class="flex justify-between">
              <span>Amount:</span>
              <span class="font-medium">${{ donationForm.amount || 0 }}</span>
            </div>
            <div class="flex justify-between">
              <span>Payment Method:</span>
              <span class="font-medium capitalize">{{
                donationForm.payment_method.replace('_', ' ')
              }}</span>
            </div>
          </div>
        </div>

        <!-- Error Display -->
        <div v-if="error" class="bg-red-50 border border-red-200 rounded-md p-3">
          <p class="text-sm text-red-600">{{ error }}</p>
        </div>

        <!-- Action Buttons -->
        <div class="flex space-x-3 pt-4">
          <button
            type="button"
            @click="handleClose"
            class="flex-1 px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            Cancel
          </button>
          <button
            type="submit"
            :disabled="isSubmitting || !isFormValid"
            :class="[
              'flex-1 px-4 py-2 rounded-md font-medium focus:outline-none focus:ring-2 focus:ring-blue-500',
              isSubmitting || !isFormValid
                ? 'bg-gray-400 text-white cursor-not-allowed'
                : 'bg-blue-600 text-white hover:bg-blue-700',
            ]"
          >
            <span v-if="isSubmitting" class="flex items-center justify-center">
              <svg
                class="animate-spin -ml-1 mr-2 h-4 w-4 text-white"
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
                  d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                ></path>
              </svg>
              Processing...
            </span>
            <span v-else>Donate ${{ donationForm.amount || 0 }}</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, defineEmits, defineProps } from 'vue'
import { useDonationsStore } from '@/stores/donations'
import type { Campaign } from '@/services/api'

// Props and Emits
const props = defineProps<{
  campaign: Campaign | null
}>()

const emit = defineEmits<{
  close: []
  success: [donation: { amount: number }]
}>()

// Store
const donationsStore = useDonationsStore()

// Form Data
const donationForm = ref({
  campaign_id: props.campaign?.id || 0,
  amount: 0,
  payment_method: 'credit_card',
  anonymous: false,
  message: '',
})

const isSubmitting = ref(false)
const error = ref<string | null>(null)

// Quick amount options
const quickAmounts = [10, 25, 50, 100]

// Payment methods
const paymentMethods = [
  { value: 'credit_card', label: 'Credit Card' },
  { value: 'debit_card', label: 'Debit Card' },
  { value: 'paypal', label: 'PayPal' },
  { value: 'bank_transfer', label: 'Bank Transfer' },
]

// Computed
const messageLength = computed(() => donationForm.value.message?.length || 0)

const amountError = computed(() => {
  if (donationForm.value.amount && donationForm.value.amount < 1) {
    return 'Minimum donation amount is $1'
  }
  return null
})

const isFormValid = computed(() => {
  return donationForm.value.amount >= 1 && donationForm.value.payment_method && !amountError.value
})

// Methods
const handleClose = () => {
  emit('close')
}

const submitDonation = async () => {
  if (!isFormValid.value) return

  isSubmitting.value = true
  error.value = null

  try {
    const donationData = {
      ...donationForm.value,
      campaign_id: props.campaign?.id || 0,
    }

    const response = await donationsStore.createDonation(donationData)

    // Emit success event with the donation data
    emit('success', response.donation)
  } catch (err: unknown) {
    const errorMessage =
      err instanceof Error ? err.message : 'Failed to process donation. Please try again.'
    error.value = errorMessage
  } finally {
    isSubmitting.value = false
  }
}
</script>
