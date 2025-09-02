<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full text-center">
      <div class="text-9xl mb-4">🔍</div>
      <h1 class="text-4xl font-bold text-gray-900 mb-4">Page Not Found</h1>
      <p class="text-gray-600 mb-8">
        Sorry, the page you're looking for doesn't exist or has been moved.
      </p>
      <div class="space-y-4">
        <router-link
          to="/"
          class="btn-primary block"
        >
          Go Back Home
        </router-link>
        <router-link
          to="/campaigns"
          class="btn-secondary block"
        >
          Browse Campaigns
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
// 404 Not Found page
</script>
