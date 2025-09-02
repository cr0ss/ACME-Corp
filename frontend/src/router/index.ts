import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'home',
      component: () => import('@/views/HomeView.vue'),
      meta: { requiresAuth: false }
    },
    {
      path: '/login',
      name: 'login',
      component: () => import('@/views/LoginView.vue'),
      meta: { requiresAuth: false, hideForAuth: true }
    },
    {
      path: '/campaigns',
      name: 'campaigns',
      component: () => import('@/views/CampaignsView.vue'),
      meta: { requiresAuth: false }
    },
    {
      path: '/campaigns/:id',
      name: 'campaign-detail',
      component: () => import('@/views/CampaignDetailView.vue'),
      meta: { requiresAuth: false }
    },
    {
      path: '/campaigns/create',
      name: 'campaign-create',
      component: () => import('@/views/CampaignCreateView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/campaigns/:id/edit',
      name: 'campaign-edit',
      component: () => import('@/views/CampaignEditView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/my-campaigns',
      name: 'my-campaigns',
      component: () => import('@/views/MyCampaignsView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/my-donations',
      name: 'my-donations',
      component: () => import('@/views/MyDonationsView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/profile',
      name: 'profile',
      component: () => import('@/views/ProfileView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/admin',
      name: 'admin',
      component: () => import('@/views/admin/AdminDashboard.vue'),
      meta: { requiresAuth: true, requiresAdmin: true },
      children: [
        {
          path: 'campaigns',
          name: 'admin-campaigns',
          component: () => import('@/views/admin/AdminCampaigns.vue'),
        },
        {
          path: 'users',
          name: 'admin-users',
          component: () => import('@/views/admin/AdminUsers.vue'),
        },
        {
          path: 'reports',
          name: 'admin-reports',
          component: () => import('@/views/admin/AdminReports.vue'),
        },
      ]
    },
    {
      path: '/:pathMatch(.*)*',
      name: 'not-found',
      component: () => import('@/views/NotFoundView.vue')
    }
  ],
})

// Navigation guards
router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore()
  
  // Initialize auth store if token exists but user is not loaded
  if (authStore.token && !authStore.user) {
    await authStore.fetchUser()
  }

  // Check if route requires authentication
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    next({
      name: 'login',
      query: { redirect: to.fullPath }
    })
    return
  }

  // Check if route requires admin
  if (to.meta.requiresAdmin && !authStore.isAdmin) {
    next({ name: 'home' })
    return
  }

  // Redirect authenticated users away from login page
  if (to.meta.hideForAuth && authStore.isAuthenticated) {
    next({ name: 'home' })
    return
  }

  next()
})

export default router
