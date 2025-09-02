<template>
  <footer class="bg-white border-t border-gray-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
        <!-- Company Info -->
        <div class="col-span-1 md:col-span-2">
          <div class="text-xl font-bold text-blue-600 mb-4">ACME CSR Platform</div>
          <p class="text-gray-600 mb-4">
            Supporting our community through corporate social responsibility initiatives.
            Together, we make a difference.
          </p>
          <p class="text-sm text-gray-500">
            © {{ currentYear }} ACME Corporation. All rights reserved.
          </p>
        </div>

        <!-- Quick Links -->
        <div>
          <h3 class="text-sm font-semibold text-gray-900 tracking-wider uppercase mb-4">
            Quick Links
          </h3>
          <ul class="space-y-2">
            <li>
              <router-link to="/" class="text-gray-600 hover:text-gray-900">
                Home
              </router-link>
            </li>
            <li>
              <router-link to="/campaigns" class="text-gray-600 hover:text-gray-900">
                Browse Campaigns
              </router-link>
            </li>
            <li>
              <router-link v-if="authStore.isAuthenticated" to="/my-donations" class="text-gray-600 hover:text-gray-900">
                My Donations
              </router-link>
            </li>
            <li>
              <router-link v-if="authStore.isAuthenticated" to="/profile" class="text-gray-600 hover:text-gray-900">
                Profile
              </router-link>
            </li>
          </ul>
        </div>

        <!-- Support -->
        <div>
          <h3 class="text-sm font-semibold text-gray-900 tracking-wider uppercase mb-4">
            Support
          </h3>
          <ul class="space-y-2">
            <li>
              <a href="mailto:csr@acme.com" class="text-gray-600 hover:text-gray-900">
                Contact Support
              </a>
            </li>
            <li>
              <a href="#" class="text-gray-600 hover:text-gray-900">
                FAQ
              </a>
            </li>
            <li>
              <a href="#" class="text-gray-600 hover:text-gray-900">
                Guidelines
              </a>
            </li>
            <li>
              <a href="#" class="text-gray-600 hover:text-gray-900">
                Privacy Policy
              </a>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </footer>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()

const currentYear = computed(() => new Date().getFullYear())
</script>
