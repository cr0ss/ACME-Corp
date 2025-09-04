<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">User Management</h1>
        <p class="text-gray-600 mt-1">Manage all users across the platform</p>
      </div>
      <div class="flex space-x-3">
        <button @click="exportUsers" class="btn-secondary">Export Data</button>
        <button @click="showCreateUserModal = true" class="btn-primary">Add New User</button>
        <button @click="refreshData" class="btn-primary" :disabled="isLoading">
          <span v-if="isLoading">Refreshing...</span>
          <span v-else>Refresh</span>
        </button>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
      <div class="card text-center">
        <div class="text-3xl font-bold text-blue-600">{{ totalUsers }}</div>
        <div class="text-sm text-gray-500">Total Users</div>
      </div>
      <div class="card text-center">
        <div class="text-3xl font-bold text-green-600">{{ adminUsers }}</div>
        <div class="text-sm text-gray-500">Admin Users</div>
      </div>
      <div class="card text-center">
        <div class="text-3xl font-bold text-yellow-600">{{ activeUsers }}</div>
        <div class="text-sm text-gray-500">Active Users</div>
      </div>
      <div class="card text-center">
        <div class="text-3xl font-bold text-purple-600">{{ newUsersThisMonth }}</div>
        <div class="text-sm text-gray-500">New This Month</div>
      </div>
    </div>

    <!-- Filters and Search -->
    <div class="card">
      <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
          <input
            v-model="filters.search"
            type="text"
            placeholder="Search users..."
            class="input-field"
            @input="debouncedSearch"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
          <select v-model="filters.department" class="input-field" @change="applyFilters">
            <option value="">All Departments</option>
            <option v-for="dept in departments" :key="dept.department" :value="dept.department">
              {{ dept.department }} ({{ dept.count }})
            </option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
          <select v-model="filters.role" class="input-field" @change="applyFilters">
            <option value="">All Roles</option>
            <option v-for="role in roles" :key="role.role" :value="role.role">
              {{ role.role }} ({{ role.count }})
            </option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Admin Status</label>
          <select v-model="filters.is_admin" class="input-field" @change="applyFilters">
            <option value="">All Users</option>
            <option value="true">Admins Only</option>
            <option value="false">Regular Users</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Sort By</label>
          <select v-model="filters.sortBy" class="input-field" @change="applyFilters">
            <option value="created_at_desc">Newest First</option>
            <option value="created_at_asc">Oldest First</option>
            <option value="name_asc">Name A-Z</option>
            <option value="name_desc">Name Z-A</option>
            <option value="email_asc">Email A-Z</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Users Table -->
    <div class="bg-white shadow-sm rounded-lg border border-gray-200 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full">
          <thead class="bg-gray-50">
            <tr>
              <th
                class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider w-2/5"
              >
                User & Details
              </th>
              <th
                class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider w-1/5"
              >
                Role & Department
              </th>
              <th
                class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider w-1/5"
              >
                Activity
              </th>
              <th
                class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider w-1/5"
              >
                Actions
              </th>
            </tr>
          </thead>
          <tbody class="bg-white">
            <tr v-if="isLoading">
              <td colspan="4" class="px-6 py-12 text-center">
                <div class="flex flex-col items-center">
                  <div
                    class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mb-3"
                  ></div>
                  <p class="text-gray-600 font-medium">Loading users...</p>
                </div>
              </td>
            </tr>
            <tr v-else-if="paginatedUsers.length === 0">
              <td colspan="4" class="px-6 py-12 text-center">
                <div class="flex flex-col items-center">
                  <div class="text-6xl mb-4">👥</div>
                  <h3 class="text-lg font-medium text-gray-900 mb-2">No users found</h3>
                  <p class="text-gray-500">Try adjusting your filters or create your first user.</p>
                </div>
              </td>
            </tr>
            <template v-else v-for="user in paginatedUsers" :key="user.id">
              <tr
                class="border-b border-gray-100 hover:bg-gray-50/50 transition-colors duration-150"
              >
                <!-- User & Details -->
                <td class="px-6 py-4">
                  <div class="flex items-start space-x-4">
                    <div class="flex-shrink-0">
                      <div
                        class="w-12 h-12 rounded-lg bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-bold text-lg"
                      >
                        {{ user.name.charAt(0) }}
                      </div>
                    </div>
                    <div class="flex-1 min-w-0">
                      <div class="flex items-center space-x-2 mb-1">
                        <h3 class="text-lg font-semibold text-gray-900 truncate">
                          {{ user.name }}
                        </h3>
                        <span
                          v-if="user.is_admin"
                          class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800"
                        >
                          <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path
                              fill-rule="evenodd"
                              d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                              clip-rule="evenodd"
                            ></path>
                          </svg>
                          Admin
                        </span>
                      </div>
                      <p class="text-sm text-gray-600 mb-2">{{ user.email }}</p>
                      <div class="flex items-center space-x-4 text-xs text-gray-500">
                        <span>ID: {{ user.employee_id }}</span>
                        <span>•</span>
                        <span>Joined: {{ formatDate(user.created_at) }}</span>
                      </div>
                    </div>
                  </div>
                </td>

                <!-- Role & Department -->
                <td class="px-6 py-4">
                  <div class="space-y-2">
                    <div>
                      <span
                        class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800"
                      >
                        {{ user.role }}
                      </span>
                    </div>
                    <div>
                      <span
                        class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800"
                      >
                        {{ user.department }}
                      </span>
                    </div>
                  </div>
                </td>

                <!-- Activity -->
                <td class="px-6 py-4">
                  <div class="space-y-2">
                    <div class="flex justify-between text-sm">
                      <span class="text-gray-500">Donations:</span>
                      <span class="font-medium text-gray-900">{{ user.donation_count || 0 }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                      <span class="text-gray-500">Campaigns:</span>
                      <span class="font-medium text-gray-900">{{ user.campaign_count || 0 }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                      <span class="text-gray-500">Total Donated:</span>
                      <span class="font-medium text-green-600"
                        >${{ (user.total_donated || 0).toLocaleString() }}</span
                      >
                    </div>
                  </div>
                </td>

                <!-- Actions -->
                <td class="px-6 py-4">
                  <div class="flex flex-col space-y-2">
                    <button
                      @click="viewUser(user)"
                      class="w-full inline-flex items-center justify-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                      <svg
                        class="w-4 h-4 mr-2"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                      >
                        <path
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                        ></path>
                        <path
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                        ></path>
                      </svg>
                      View
                    </button>
                    <button
                      @click="editUser(user)"
                      class="w-full inline-flex items-center justify-center px-3 py-2 border border-blue-300 text-sm font-medium rounded-md text-blue-700 bg-blue-50 hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                      <svg
                        class="w-4 h-4 mr-2"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                      >
                        <path
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                        ></path>
                      </svg>
                      Edit
                    </button>
                    <button
                      @click="toggleAdminStatus(user)"
                      class="w-full inline-flex items-center justify-center px-3 py-2 border text-sm font-medium rounded-md focus:outline-none focus:ring-2"
                      :class="
                        user.is_admin
                          ? 'border-yellow-300 text-yellow-700 bg-yellow-50 hover:bg-yellow-100 focus:ring-yellow-500'
                          : 'border-purple-300 text-purple-700 bg-purple-50 hover:bg-purple-100 focus:ring-purple-500'
                      "
                      :disabled="isUpdating === user.id"
                    >
                      <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path
                          fill-rule="evenodd"
                          d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                          clip-rule="evenodd"
                        ></path>
                      </svg>
                      {{ user.is_admin ? 'Remove Admin' : 'Make Admin' }}
                    </button>
                    <button
                      @click="deleteUser(user)"
                      class="w-full inline-flex items-center justify-center px-3 py-2 border border-red-300 text-sm font-medium rounded-md text-red-700 bg-red-50 hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-500"
                      :disabled="user.is_admin && totalAdmins <= 1"
                      :title="
                        user.is_admin && totalAdmins <= 1
                          ? 'Cannot delete the last admin user'
                          : 'Delete user'
                      "
                    >
                      <svg
                        class="w-4 h-4 mr-2"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                      >
                        <path
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                        ></path>
                      </svg>
                      Delete
                    </button>
                  </div>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div
        v-if="totalPages > 1"
        class="flex flex-col sm:flex-row justify-between items-center mt-8 pt-6 border-t border-gray-200 bg-gray-50 rounded-lg px-6 py-4"
      >
        <div class="text-sm text-gray-600 mb-4 sm:mb-0">
          Showing
          <span class="font-medium text-gray-900">{{ (currentPage - 1) * pageSize + 1 }}</span
          >-<span class="font-medium text-gray-900">{{
            Math.min(currentPage * pageSize, filteredUsers.length)
          }}</span>
          of <span class="font-medium text-gray-900">{{ filteredUsers.length }}</span> users
        </div>
        <nav class="flex items-center space-x-1">
          <button
            @click="currentPage = Math.max(1, currentPage - 1)"
            :disabled="currentPage === 1"
            class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-l-md hover:bg-gray-50 focus:z-10 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Previous
          </button>
          <div class="flex items-center space-x-1 mx-2">
            <button
              v-for="page in visiblePages"
              :key="page"
              @click="currentPage = page"
              class="relative inline-flex items-center px-4 py-2 text-sm font-medium border focus:z-10 focus:ring-2 focus:ring-blue-500"
              :class="
                page === currentPage
                  ? 'z-10 bg-blue-600 border-blue-600 text-white'
                  : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50'
              "
            >
              {{ page }}
            </button>
          </div>
          <button
            @click="currentPage = Math.min(totalPages, currentPage + 1)"
            :disabled="currentPage === totalPages"
            class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-r-md hover:bg-gray-50 focus:z-10 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Next
          </button>
        </nav>
      </div>
    </div>

    <!-- Create User Modal -->
    <div
      v-if="showCreateUserModal"
      class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
      @click="showCreateUserModal = false"
    >
      <div
        class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white"
        @click.stop
      >
        <div class="mt-3">
          <h3 class="text-lg font-medium text-gray-900 mb-4">Add New User</h3>
          <form @submit.prevent="createUser" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">Name</label>
              <input v-model="newUser.name" type="text" required class="input-field" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Email</label>
              <input v-model="newUser.email" type="email" required class="input-field" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Employee ID</label>
              <input v-model="newUser.employee_id" type="text" required class="input-field" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Department</label>
              <input v-model="newUser.department" type="text" required class="input-field" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Role</label>
              <input v-model="newUser.role" type="text" required class="input-field" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Password</label>
              <input v-model="newUser.password" type="password" required class="input-field" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Confirm Password</label>
              <input
                v-model="newUser.password_confirmation"
                type="password"
                required
                class="input-field"
              />
            </div>
            <div class="flex items-center">
              <input v-model="newUser.is_admin" type="checkbox" class="mr-2" />
              <label class="text-sm font-medium text-gray-700">Admin User</label>
            </div>
            <div class="flex justify-end space-x-3 pt-4">
              <button type="button" @click="showCreateUserModal = false" class="btn-secondary">
                Cancel
              </button>
              <button type="submit" :disabled="isCreating" class="btn-primary">
                {{ isCreating ? 'Creating...' : 'Create User' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { usersApi, type User } from '@/services/api'

const authStore = useAuthStore()

// State
const users = ref<User[]>([])
  const statistics = ref<{
  overview?: {
    total_users: number
    admin_users: number
    active_users: number
    new_users_this_month: number
  }
}>({})
const departments = ref<Array<{ department: string; count: number }>>([])
const roles = ref<Array<{ role: string; count: number }>>([])
const isLoading = ref(false)
const isUpdating = ref<number | null>(null)
const isCreating = ref(false)
const currentPage = ref(1)
const pageSize = ref(15)
const showCreateUserModal = ref(false)

// Filters
const filters = ref({
  search: '',
  department: '',
  role: '',
  is_admin: '',
  sortBy: 'created_at_desc',
})

// New user form
const newUser = ref({
  name: '',
  email: '',
  employee_id: '',
  department: '',
  role: '',
  password: '',
  password_confirmation: '',
  is_admin: false,
})

// Computed
const totalUsers = computed(() => statistics.value.overview?.total_users || 0)
const adminUsers = computed(() => statistics.value.overview?.admin_users || 0)
const activeUsers = computed(() => statistics.value.overview?.active_users || 0)
const newUsersThisMonth = computed(() => statistics.value.overview?.new_users_this_month || 0)
const totalAdmins = computed(() => users.value.filter((u) => u.is_admin).length)

const filteredUsers = computed(() => {
  let filtered = [...users.value]

  // Apply search filter
  if (filters.value.search) {
    const search = filters.value.search.toLowerCase()
    filtered = filtered.filter(
      (user) =>
        user.name.toLowerCase().includes(search) ||
        user.email.toLowerCase().includes(search) ||
        user.employee_id.toLowerCase().includes(search) ||
        user.department.toLowerCase().includes(search) ||
        user.role.toLowerCase().includes(search),
    )
  }

  // Apply department filter
  if (filters.value.department) {
    filtered = filtered.filter((user) => user.department === filters.value.department)
  }

  // Apply role filter
  if (filters.value.role) {
    filtered = filtered.filter((user) => user.role === filters.value.role)
  }

  // Apply admin filter
  if (filters.value.is_admin !== '') {
    const isAdmin = filters.value.is_admin === 'true'
    filtered = filtered.filter((user) => user.is_admin === isAdmin)
  }

  // Apply sorting
  const [sortField, sortDirection] = filters.value.sortBy.split('_')
  filtered.sort((a, b) => {
    let aValue, bValue

    switch (sortField) {
      case 'created':
        aValue = new Date(a.created_at).getTime()
        bValue = new Date(b.created_at).getTime()
        break
      case 'name':
        aValue = a.name.toLowerCase()
        bValue = b.name.toLowerCase()
        break
      case 'email':
        aValue = a.email.toLowerCase()
        bValue = b.email.toLowerCase()
        break
      default:
        aValue = new Date(a.created_at).getTime()
        bValue = new Date(b.created_at).getTime()
    }

    if (sortDirection === 'desc') {
      return aValue > bValue ? -1 : aValue < bValue ? 1 : 0
    } else {
      return aValue < bValue ? -1 : aValue > bValue ? 1 : 0
    }
  })

  return filtered
})

const totalPages = computed(() => Math.ceil(filteredUsers.value.length / pageSize.value))

const paginatedUsers = computed(() => {
  const start = (currentPage.value - 1) * pageSize.value
  const end = start + pageSize.value
  return filteredUsers.value.slice(start, end)
})

const visiblePages = computed(() => {
  const current = currentPage.value
  const total = totalPages.value
  const pages = []

  for (let i = Math.max(1, current - 2); i <= Math.min(total, current + 2); i++) {
    pages.push(i)
  }

  return pages
})

// Methods
async function fetchAllUsers() {
  isLoading.value = true
  try {
    const response = await usersApi.getAll({
      per_page: 100, // Maximum allowed by backend validation
    })

    if (response.data) {
      users.value = response.data
    } else if (Array.isArray(response)) {
      users.value = response
    } else {
      users.value = []
    }
  } catch (error) {
    console.error('Failed to fetch users:', error)
  } finally {
    isLoading.value = false
  }
}

async function fetchStatistics() {
  try {
    const stats = await usersApi.getStatistics()
    statistics.value = stats
    departments.value = stats.departments || []
    roles.value = stats.roles || []
  } catch (error) {
    console.error('Failed to fetch statistics:', error)
  }
}

function formatDate(dateString: string) {
  return new Date(dateString).toLocaleDateString()
}

function viewUser(user: User) {
  // Navigate to user detail page or show modal
  alert(`View user: ${user.name}`)
}

function editUser(user: User) {
  // Navigate to user edit page or show modal
  alert(`Edit user: ${user.name}`)
}

async function toggleAdminStatus(user: User) {
  if (user.is_admin && totalAdmins.value <= 1) {
    alert('Cannot remove admin status from the last admin user')
    return
  }

  isUpdating.value = user.id
  try {
    await usersApi.update(user.id, {
      is_admin: !user.is_admin,
    })

    // Update local state
    user.is_admin = !user.is_admin

    // Refresh statistics
    await fetchStatistics()
  } catch (error) {
    console.error('Failed to toggle admin status:', error)
  } finally {
    isUpdating.value = null
  }
}

async function deleteUser(user: User) {
  if (user.is_admin && totalAdmins.value <= 1) {
    alert('Cannot delete the last admin user')
    return
  }

  if (user.id === authStore.user?.id) {
    alert('Cannot delete your own account')
    return
  }

  if (!confirm(`Are you sure you want to delete "${user.name}"? This action cannot be undone.`)) {
    return
  }

  try {
    await usersApi.delete(user.id)
    users.value = users.value.filter((u) => u.id !== user.id)
    await fetchStatistics()
  } catch (error) {
    console.error('Failed to delete user:', error)
  }
}

async function createUser() {
  if (newUser.value.password !== newUser.value.password_confirmation) {
    alert('Passwords do not match')
    return
  }

  isCreating.value = true
  try {
    const user = await usersApi.create(newUser.value)
    users.value.push(user)

    // Reset form
    newUser.value = {
      name: '',
      email: '',
      employee_id: '',
      department: '',
      role: '',
      password: '',
      password_confirmation: '',
      is_admin: false,
    }

    showCreateUserModal.value = false
    await fetchStatistics()
  } catch (error) {
    console.error('Failed to create user:', error)
  } finally {
    isCreating.value = false
  }
}

function exportUsers() {
  const csvContent = [
    [
      'ID',
      'Name',
      'Email',
      'Employee ID',
      'Department',
      'Role',
      'Admin',
      'Total Donated',
      'Donations',
      'Campaigns',
      'Created At',
    ].join(','),
    ...filteredUsers.value.map((user) =>
      [
        user.id,
        `"${user.name}"`,
        `"${user.email}"`,
        `"${user.employee_id}"`,
        `"${user.department}"`,
        `"${user.role}"`,
        user.is_admin ? 'Yes' : 'No',
        (user.total_donated || 0).toLocaleString(),
        user.donation_count || 0,
        user.campaign_count || 0,
        user.created_at,
      ].join(','),
    ),
  ].join('\n')

  const blob = new Blob([csvContent], { type: 'text/csv' })
  const url = window.URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = `users-export-${new Date().toISOString().split('T')[0]}.csv`
  link.click()
  window.URL.revokeObjectURL(url)
}

async function refreshData() {
  await Promise.all([fetchAllUsers(), fetchStatistics()])
}

function applyFilters() {
  currentPage.value = 1
}

// Debounced search
let searchTimeout: ReturnType<typeof setTimeout>
function debouncedSearch() {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    applyFilters()
  }, 300)
}

// Watch for filter changes to reset pagination
watch(
  () => [filters.value.department, filters.value.role, filters.value.is_admin],
  () => {
    currentPage.value = 1
  },
)

// Lifecycle
onMounted(async () => {
  await refreshData()
})
</script>

<style scoped>
.input-field {
  @apply mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm;
}

.btn-primary {
  @apply inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500;
}

.btn-secondary {
  @apply inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500;
}

.card {
  @apply bg-white shadow-sm rounded-lg border border-gray-200 p-6;
}
</style>
