import { computed } from 'vue'
import type { Campaign } from '@/services/api'
import { useAuthStore } from '@/stores/auth'
import { formatDate } from '@/utils/formatters'

export function useCampaignDonation(campaign: Campaign) {
  const authStore = useAuthStore()

  // Donation validation computed properties
  const canDonate = computed(() => {
    return authStore.isAuthenticated && campaign.status === 'active'
  })

  const isDonationAllowed = computed(() => {
    if (!canDonate.value) return false

    const now = new Date()
    const startDate = new Date(campaign.start_date)
    const endDate = new Date(campaign.end_date)

    // Check if campaign is within its date range
    return startDate <= now && now <= endDate
  })

  const donationButtonText = computed(() => {
    if (!canDonate.value) return 'Donate'

    const now = new Date()
    const startDate = new Date(campaign.start_date)
    const endDate = new Date(campaign.end_date)

    if (startDate > now) {
      return 'Not Started'
    } else if (endDate < now) {
      return 'Ended'
    } else {
      return 'Donate'
    }
  })

  const donationButtonTextPrimary = computed(() => {
    if (!canDonate.value) return 'Donate Now'

    const now = new Date()
    const startDate = new Date(campaign.start_date)
    const endDate = new Date(campaign.end_date)

    if (startDate > now) {
      return 'Not Started'
    } else if (endDate < now) {
      return 'Ended'
    } else {
      return 'Donate Now'
    }
  })

  const campaignStatusInfo = computed(() => {
    if (!canDonate.value || isDonationAllowed.value) return null

    const now = new Date()
    const startDate = new Date(campaign.start_date)
    const endDate = new Date(campaign.end_date)

    if (startDate > now) {
      return {
        type: 'not-started' as const,
        message: `Campaign starts ${formatDate(campaign.start_date)}`,
        icon: 'clock',
        colorClass: 'bg-blue-50 border-blue-200 text-blue-800'
      }
    } else if (endDate < now) {
      return {
        type: 'ended' as const,
        message: `Campaign ended ${formatDate(campaign.end_date)}`,
        icon: 'x',
        colorClass: 'bg-red-50 border-red-200 text-red-800'
      }
    }

    return null
  })

  const buttonClasses = computed(() => {
    if (!isDonationAllowed.value) {
      return 'border-gray-300 bg-gray-100 text-gray-500 cursor-not-allowed'
    }
    return 'border-blue-300 bg-white text-blue-700 hover:bg-blue-50 hover:border-blue-400'
  })

  const buttonClassesPrimary = computed(() => {
    if (!isDonationAllowed.value) {
      return 'border-gray-300 bg-gray-100 text-gray-500 cursor-not-allowed'
    }
    return 'border-blue-300 bg-blue-600 text-white hover:bg-blue-700 hover:border-blue-700'
  })

  const handleDonateClick = () => {
    if (!isDonationAllowed.value) {
      console.log('Donation not allowed for this campaign')
      return
    }

    // Emit event or call callback - this will be handled by the parent component
    return true
  }

  return {
    canDonate,
    isDonationAllowed,
    donationButtonText,
    donationButtonTextPrimary,
    campaignStatusInfo,
    buttonClasses,
    buttonClassesPrimary,
    handleDonateClick
  }
}
