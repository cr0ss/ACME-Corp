<template>
  <nav class="bg-white shadow-md border-b border-gray-200 sticky top-0 z-40">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between items-center h-16">
        <!-- Left side - Logo and main navigation -->
        <div class="flex items-center space-x-4">
          <!-- Logo -->
          <div class="flex-shrink-0 flex items-center">
            <router-link to="/" class="flex items-center space-x-2 group focus:outline-none active:outline-none">
              <!-- Logo Icon -->
              <div class="h-10 w-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg flex items-center justify-center shadow-sm group-hover:shadow-md transition-shadow duration-200">
                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                </svg>
              </div>
              <!-- Logo Text -->
              <div class="hidden sm:block">
                <h1 class="text-xl font-bold text-gray-900 group-hover:text-blue-600 transition-colors duration-200">
                  ACME CSR
                </h1>
                <p class="text-xs text-gray-500 -mt-1">Corporate Social Responsibility</p>
              </div>
            </router-link>
          </div>

          <!-- Main navigation -->
          <div class="hidden md:flex md:space-x-0">
            <router-link
              to="/"
              class="nav-link"
              :class="{ 'nav-link-active': $route.name === 'home' }"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
              </svg>
              <span>Home</span>
            </router-link>
            
            <router-link
              to="/campaigns"
              class="nav-link"
              :class="{ 'nav-link-active': $route.name === 'campaigns' }"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
              </svg>
              <span>Browse Campaigns</span>
            </router-link>

            <router-link
              v-if="authStore.isAuthenticated && !authStore.isAdmin"
              to="/my-campaigns"
              class="nav-link"
              :class="{ 'nav-link-active': $route.name === 'my-campaigns' }"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              <span>My Campaigns</span>
            </router-link>

            <router-link
              v-if="authStore.isAuthenticated && !authStore.isAdmin"
              to="/my-donations"
              class="nav-link"
              :class="{ 'nav-link-active': $route.name === 'my-donations' }"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
              </svg>
              <span>My Donations</span>
            </router-link>

            <router-link
              v-if="authStore.isAdmin"
              to="/admin"
              class="nav-link nav-link-admin"
              :class="{ 'nav-link-active': $route.name?.toString().startsWith('admin') }"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
              <span>Admin</span>
            </router-link>
          </div>
        </div>

        <!-- Right side - User menu and mobile toggle -->
        <div class="flex items-center space-x-3">
          <!-- Create Campaign Button (for authenticated users) -->
          <router-link
            v-if="authStore.isAuthenticated"
            to="/campaigns/create"
            class="hidden xl:inline-flex items-center px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors duration-200 shadow-sm hover:shadow-md focus:outline-none active:outline-none"
          >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Create Campaign
          </router-link>

          <!-- User Menu -->
          <template v-if="authStore.isAuthenticated">
            <div class="relative">
              <button
                @click="showUserMenu = !showUserMenu"
                class="flex items-center space-x-2 text-sm rounded-lg hover:bg-gray-50 focus:outline-none active:outline-none p-2 transition-colors duration-200"
              >
                <span class="sr-only">Open user menu</span>
                <!-- Enhanced Avatar -->
                <div class="relative">
                  <div class="h-9 w-9 rounded-full bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-white font-semibold shadow-sm">
                    {{ authStore.user?.name?.charAt(0)?.toUpperCase() }}
                  </div>
                  <div class="absolute -bottom-1 -right-1 h-3 w-3 bg-green-400 border-2 border-white rounded-full"></div>
                </div>
                <!-- User Info -->
                <div class="hidden sm:block text-left">
                  <div class="text-sm font-medium text-gray-900">{{ authStore.user?.name }}</div>
                  <div class="text-xs text-gray-500">{{ authStore.user?.role || 'Employee' }}</div>
                </div>
                <!-- Chevron -->
                <svg 
                  class="w-4 h-4 text-gray-400 transition-transform duration-200"
                  :class="{ 'rotate-180': showUserMenu }"
                  fill="none" 
                  stroke="currentColor" 
                  viewBox="0 0 24 24"
                >
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </button>

              <!-- Enhanced Dropdown menu -->
              <transition
                enter-active-class="transition ease-out duration-100"
                enter-from-class="transform opacity-0 scale-95"
                enter-to-class="transform opacity-100 scale-100"
                leave-active-class="transition ease-in duration-75"
                leave-from-class="transform opacity-100 scale-100"
                leave-to-class="transform opacity-0 scale-95"
              >
                <div
                  v-if="showUserMenu"
                  class="origin-top-right absolute right-0 mt-2 w-56 rounded-xl shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-50 border border-gray-100"
                  @click="showUserMenu = false"
                >
                  <!-- User Info Header -->
                  <div class="px-4 py-3 border-b border-gray-100">
                    <p class="text-sm font-medium text-gray-900">{{ authStore.user?.name }}</p>
                    <p class="text-xs text-gray-500">{{ authStore.user?.email || 'Employee' }}</p>
                  </div>
                  
                  <!-- Menu Items -->
                  <div class="py-1">
                    <router-link
                      to="/profile"
                      class="dropdown-item"
                    >
                      <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                      </svg>
                      My Profile
                    </router-link>
                    
                    <router-link
                      to="/my-campaigns"
                      class="dropdown-item"
                    >
                      <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                      </svg>
                      My Campaigns
                    </router-link>
                    
                    <router-link
                      to="/my-donations"
                      class="dropdown-item"
                    >
                      <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                      </svg>
                      My Donations
                    </router-link>
                    
                    <div class="border-t border-gray-100 my-1"></div>
                    
                    <button
                      @click="handleLogout"
                      class="dropdown-item text-red-600 hover:bg-red-50 focus:outline-none active:outline-none"
                    >
                      <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                      </svg>
                      Sign out
                    </button>
                  </div>
                </div>
              </transition>
            </div>
          </template>

          <!-- Sign In Button -->
          <template v-else>
            <router-link
              to="/login"
              class="inline-flex items-center px-4 py-2 border border-blue-600 text-blue-600 hover:bg-blue-600 hover:text-white text-sm font-medium rounded-lg transition-colors duration-200 focus:outline-none active:outline-none"
            >
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
              </svg>
              Sign in
            </router-link>
          </template>

          <!-- Mobile menu button -->
          <button
            @click="showMobileMenu = !showMobileMenu"
            class="md:hidden inline-flex items-center justify-center p-2 rounded-lg text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none active:outline-none transition-colors duration-200"
          >
            <span class="sr-only">Open main menu</span>
            <svg 
              class="h-6 w-6 transition-transform duration-200" 
              :class="{ 'rotate-90': showMobileMenu }"
              fill="none" 
              stroke="currentColor" 
              viewBox="0 0 24 24"
            >
              <path v-if="!showMobileMenu" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
              <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- Enhanced Mobile menu -->
    <transition
      enter-active-class="transition ease-out duration-200"
      enter-from-class="opacity-0 -translate-y-1"
      enter-to-class="opacity-100 translate-y-0"
      leave-active-class="transition ease-in duration-150"
      leave-from-class="opacity-100 translate-y-0"
      leave-to-class="opacity-0 -translate-y-1"
    >
      <div v-if="showMobileMenu" class="md:hidden bg-white border-t border-gray-200 shadow-lg">
        <div class="px-2 pt-2 pb-3 space-y-1">
          <!-- Main Navigation Links -->
          <router-link
            to="/"
            class="mobile-nav-link"
            :class="{ 'mobile-nav-link-active': $route.name === 'home' }"
            @click="showMobileMenu = false"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
            Home
          </router-link>
          
          <router-link
            to="/campaigns"
            class="mobile-nav-link"
            :class="{ 'mobile-nav-link-active': $route.name === 'campaigns' }"
            @click="showMobileMenu = false"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
            </svg>
            Browse Campaigns
          </router-link>

          <!-- Authenticated User Links -->
          <template v-if="authStore.isAuthenticated">
            <router-link
              to="/my-campaigns"
              class="mobile-nav-link"
              :class="{ 'mobile-nav-link-active': $route.name === 'my-campaigns' }"
              @click="showMobileMenu = false"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              My Campaigns
            </router-link>
            
            <router-link
              to="/my-donations"
              class="mobile-nav-link"
              :class="{ 'mobile-nav-link-active': $route.name === 'my-donations' }"
              @click="showMobileMenu = false"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
              </svg>
              My Donations
            </router-link>
            
            <router-link
              to="/campaigns/create"
              class="mobile-nav-link-special"
              @click="showMobileMenu = false"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
              </svg>
              Create Campaign
            </router-link>

            <!-- Admin Link -->
            <router-link
              v-if="authStore.isAdmin"
              to="/admin"
              class="mobile-nav-link border-t border-gray-200 mt-2 pt-2"
              :class="{ 'mobile-nav-link-active': $route.name?.toString().startsWith('admin') }"
              @click="showMobileMenu = false"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
              Admin Dashboard
            </router-link>
          </template>
        </div>

        <!-- Mobile User Section -->
        <div v-if="authStore.isAuthenticated" class="pt-4 pb-3 border-t border-gray-200 bg-gray-50">
          <div class="flex items-center px-5">
            <div class="flex-shrink-0">
              <div class="h-10 w-10 rounded-full bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-white font-semibold shadow-sm">
                {{ authStore.user?.name?.charAt(0)?.toUpperCase() }}
              </div>
            </div>
            <div class="ml-3">
              <div class="text-base font-medium text-gray-800">{{ authStore.user?.name }}</div>
              <div class="text-sm text-gray-500">{{ authStore.user?.email || 'Employee' }}</div>
            </div>
          </div>
          <div class="mt-3 px-2 space-y-1">
            <router-link
              to="/profile"
              class="mobile-nav-link"
              @click="showMobileMenu = false"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
              Profile
            </router-link>
            <button
              @click="handleLogout"
              class="mobile-nav-link text-red-600 w-full text-left focus:outline-none active:outline-none"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
              </svg>
              Sign out
            </button>
          </div>
        </div>

        <!-- Mobile Sign In -->
        <div v-else class="pt-4 pb-3 border-t border-gray-200 bg-gray-50">
          <div class="px-2">
            <router-link
              to="/login"
              class="mobile-nav-link-special"
              @click="showMobileMenu = false"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
              </svg>
              Sign in
            </router-link>
          </div>
        </div>
      </div>
    </transition>
  </nav>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const showUserMenu = ref(false)
const showMobileMenu = ref(false)

async function handleLogout() {
  try {
    await authStore.logout()
    router.push('/login')
  } catch (error) {
    console.error('Logout failed:', error)
    // Force logout even if API call fails
    router.push('/login')
  }
}

// Close dropdown when clicking outside
function handleClickOutside(event: Event) {
  const target = event.target as Element
  if (!target.closest('.relative')) {
    showUserMenu.value = false
    showMobileMenu.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>

<style scoped>
/* Navigation Links */
.nav-link {
  @apply flex items-center space-x-2 px-2 py-3 text-sm font-medium text-gray-600 hover:text-gray-900 transition-all duration-200 relative focus:outline-none active:outline-none;
}

.nav-link:active,
.nav-link:focus {
  outline: none !important;
  border: none !important;
  box-shadow: none !important;
  transform: none !important;
  filter: none !important;
}

.nav-link-active {
  @apply text-blue-600;
}

.nav-link-admin {
  @apply text-purple-600 hover:text-purple-700;
}

.nav-link-admin.nav-link-active {
  @apply text-purple-700;
}

/* Enhanced underline for active links */
.nav-link-active::after {
  content: '';
  @apply absolute bottom-0 left-0 right-0 h-0.5 bg-blue-600 transform scale-x-100 transition-transform duration-300;
}

.nav-link::after {
  content: '';
  @apply absolute bottom-0 left-0 right-0 h-0.5 bg-blue-600 transform scale-x-0 transition-transform duration-300;
}

.nav-link:hover::after {
  @apply transform scale-x-50;
}

.nav-link-admin.nav-link-active::after {
  @apply bg-purple-600;
}

.nav-link-admin::after {
  @apply bg-purple-600;
}

/* Icon enhancements */
.nav-link svg {
  @apply transition-transform duration-200;
  filter: drop-shadow(0 1px 1px rgba(0, 0, 0, 0.05));
}

.nav-link:hover svg {
  @apply transform scale-110;
}

.nav-link-active svg {
  @apply transform scale-110;
  filter: drop-shadow(0 2px 4px rgba(59, 130, 246, 0.2));
}

.nav-link-admin.nav-link-active svg {
  filter: drop-shadow(0 2px 4px rgba(147, 51, 234, 0.2));
}

/* Dropdown Items */
.dropdown-item {
  @apply flex items-center w-full px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition-colors duration-150 focus:outline-none active:outline-none;
}

/* Mobile Navigation Links */
.mobile-nav-link {
  @apply flex items-center space-x-3 px-4 py-3 rounded-lg text-base font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 transition-all duration-200 focus:outline-none active:outline-none;
}

.mobile-nav-link:active,
.mobile-nav-link:focus {
  outline: none !important;
  border: none !important;
  box-shadow: none !important;
}

.mobile-nav-link-active {
  @apply text-blue-600 bg-blue-50 border-l-4 border-blue-500;
}

.mobile-nav-link-special {
  @apply flex items-center space-x-3 px-4 py-3 rounded-lg text-base font-medium text-white bg-blue-600 hover:bg-blue-700 transition-all duration-200 shadow-sm focus:outline-none active:outline-none;
}

/* Mobile navigation icon enhancements */
.mobile-nav-link svg {
  @apply transition-transform duration-200;
  filter: drop-shadow(0 1px 1px rgba(0, 0, 0, 0.05));
}

.mobile-nav-link:hover svg {
  @apply transform scale-105;
}

.mobile-nav-link-active svg {
  @apply transform scale-105;
  filter: drop-shadow(0 2px 4px rgba(59, 130, 246, 0.2));
}

/* Smooth transitions */
.transition-transform {
  transition-property: transform;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 200ms;
}

/* Specific state overrides for navigation elements only */
button:active,
button:focus,
a:active,
a:focus,
router-link:active,
router-link:focus,
[role="button"]:active,
[role="button"]:focus {
  outline: none !important;
  border: none !important;
  -webkit-tap-highlight-color: transparent !important;
  -webkit-user-select: none !important;
  -moz-user-select: none !important;
  user-select: none !important;
  -webkit-touch-callout: none !important;
  -webkit-appearance: none !important;
}

/* Only remove box-shadow from specific navigation elements, not all elements */
.nav-link:active,
.nav-link:focus,
.mobile-nav-link:active,
.mobile-nav-link:focus,
.dropdown-item:active,
.dropdown-item:focus {
  box-shadow: none !important;
}

/* User menu enhancements */
.dropdown-item:hover {
  @apply transform translate-x-1;
}

/* Vue Router specific focus override */
.router-link-exact-active:focus,
.router-link-active:focus {
  outline: none !important;
  border: none !important;
  box-shadow: none !important;
}

/* Mobile menu animations */
@media (max-width: 768px) {
  .mobile-nav-link {
    @apply border-l-4 border-transparent;
  }
  
  .mobile-nav-link:hover {
    @apply border-l-4 border-gray-300;
  }
}

/* Logo hover effect */
.group:hover .group-hover\:shadow-md {
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

/* Text styling improvements */
.nav-link span {
  @apply font-medium tracking-wide;
}

.mobile-nav-link span {
  @apply font-medium;
}

/* Hover effects for better interaction feedback */
.nav-link:hover {
  @apply transform -translate-y-px;
}

/* Removed active transform that might create visual effects */
</style>
