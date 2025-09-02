<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Navigation -->
    <AppNavigation />

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <slot />
    </main>

    <!-- Footer -->
    <AppFooter />
  </div>
</template>

<script setup lang="ts">
import AppNavigation from '@/components/common/AppNavigation.vue'
import AppFooter from '@/components/common/AppFooter.vue'
</script>
