<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { RouterView, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import DefaultLayout from '@/layouts/DefaultLayout.vue'

const route = useRoute()
const authStore = useAuthStore()

// Pages that don't use the default layout
const isAuthPage = computed(() => {
  return route.name === 'login'
})

onMounted(() => {
  // Initialize auth store
  authStore.initialize()
})
</script>

<template>
  <div id="app">
    <!-- Check if current route should use layout -->
    <DefaultLayout v-if="!isAuthPage">
      <RouterView />
    </DefaultLayout>
    <RouterView v-else />
  </div>
</template>