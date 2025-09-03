import axios, { type AxiosInstance, type AxiosRequestConfig, type AxiosResponse } from 'axios'
import { useAuthStore } from '@/stores/auth'

class ApiService {
  private api: AxiosInstance

  constructor() {
    this.api = axios.create({
      baseURL: import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api',
      timeout: 10000,
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
      },
    })

    this.setupInterceptors()
  }

  private setupInterceptors(): void {
    // Request interceptor to add auth token
    this.api.interceptors.request.use(
      (config) => {
        const authStore = useAuthStore()
        const token = authStore.token

        if (token) {
          config.headers.Authorization = `Bearer ${token}`
        }

        return config
      },
      (error) => {
        return Promise.reject(error)
      },
    )

    // Response interceptor to handle common errors
    this.api.interceptors.response.use(
      (response: AxiosResponse) => {
        return response
      },
      (error) => {
        const authStore = useAuthStore()

        // Handle 401 Unauthorized
        if (error.response?.status === 401) {
          authStore.logout()
          // Let the router guards handle the redirect to avoid loops
        }

        return Promise.reject(error)
      },
    )
  }

  // Generic API methods
  async get<T = unknown>(url: string, config?: AxiosRequestConfig): Promise<T> {
    const response = await this.api.get<T>(url, config)
    return response.data
  }

  // Special method for file downloads that preserves headers
  async getFile(url: string, config?: AxiosRequestConfig): Promise<{ data: Blob; filename?: string; contentType?: string }> {
    const response = await this.api.get(url, { ...config, responseType: 'blob' })
    
    // Extract filename from Content-Disposition header
    const contentDisposition = response.headers['content-disposition']
    let filename: string | undefined
    
    if (contentDisposition) {
      const filenameMatch = contentDisposition.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/)
      if (filenameMatch && filenameMatch[1]) {
        filename = filenameMatch[1].replace(/['"]/g, '')
      }
    }
    
    // Extract content type
    const contentType = response.headers['content-type']
    
    return {
      data: response.data,
      filename,
      contentType
    }
  }

  async post<T = unknown>(url: string, data?: unknown, config?: AxiosRequestConfig): Promise<T> {
    const response = await this.api.post<T>(url, data, config)
    return response.data
  }

  async put<T = unknown>(url: string, data?: unknown, config?: AxiosRequestConfig): Promise<T> {
    const response = await this.api.put<T>(url, data, config)
    return response.data
  }

  async patch<T = unknown>(url: string, data?: unknown, config?: AxiosRequestConfig): Promise<T> {
    const response = await this.api.patch<T>(url, data, config)
    return response.data
  }

  async delete<T = unknown>(url: string, config?: AxiosRequestConfig): Promise<T> {
    const response = await this.api.delete<T>(url, config)
    return response.data
  }
}

export const apiService = new ApiService()

// Specific API endpoints
export interface User {
  id: number
  name: string
  email: string
  employee_id: string
  department: string
  role: string
  is_admin: boolean
  total_donated?: number
  donation_count?: number
  campaign_count?: number
  created_at: string
  updated_at?: string
}

export interface Campaign {
  id: number
  title: string
  description: string
  target_amount: number
  current_amount: number
  start_date: string
  end_date: string
  status: 'draft' | 'active' | 'completed' | 'cancelled'
  featured: boolean
  category: CampaignCategory
  user: User
  progress_percentage: number
  is_active: boolean
  donations_count?: number
  created_at: string
  updated_at: string
}

export interface CampaignCategory {
  id: number
  name: string
  slug: string
  description: string
  icon: string
}

export interface Donation {
  id: number
  amount: number
  status: 'pending' | 'completed' | 'failed' | 'refunded'
  payment_method: string
  anonymous: boolean
  message?: string
  campaign: Campaign
  user: User
  created_at: string
}

export interface DonationStats {
  total_donated: number
  total_donations: number
  campaigns_supported: number
  avg_donation: number
  first_donation?: string
  last_donation?: string
  pending_donations: number
  failed_donations: number
}

export interface LoginCredentials {
  email: string
  password: string
}

export interface LoginResponse {
  message: string
  user: User
  token: string
}

// Authentication API
export const authApi = {
  login: (credentials: LoginCredentials): Promise<LoginResponse> =>
    apiService.post('/login', credentials),

  logout: (): Promise<{ message: string }> => apiService.post('/logout'),

  getUser: (): Promise<{ user: User }> => apiService.get('/user'),

  updateProfile: (data: Partial<User>): Promise<{ message: string; user: User }> =>
    apiService.put('/profile', data),

  updatePassword: (data: {
    current_password: string
    password: string
    password_confirmation: string
  }): Promise<{ message: string }> => apiService.put('/profile/password', data),
}

// Pagination response interface
export interface PaginatedResponse<T> {
  data: T[]
  current_page: number
  last_page: number
  per_page: number
  total: number
  from?: number | null
  to?: number
}

// Campaigns API
export const campaignsApi = {
  getAll: (params?: Record<string, unknown>): Promise<PaginatedResponse<Campaign>> =>
    apiService.get('/campaigns', { params }),

  getMyCampaigns: (params?: Record<string, unknown>): Promise<PaginatedResponse<Campaign>> =>
    apiService.get('/campaigns/my-campaigns', { params }),

  getById: (id: number): Promise<{ campaign: Campaign; stats: Record<string, unknown> }> =>
    apiService.get(`/campaigns/${id}`),

  create: (data: Partial<Campaign>): Promise<{ message: string; campaign: Campaign }> =>
    apiService.post('/campaigns', data),

  update: (id: number, data: Partial<Campaign>): Promise<{ message: string; campaign: Campaign }> =>
    apiService.put(`/campaigns/${id}`, data),

  delete: (id: number): Promise<{ message: string }> => apiService.delete(`/campaigns/${id}`),

  getTrending: (): Promise<PaginatedResponse<Campaign>> => apiService.get('/campaigns/trending'),

  getEndingSoon: (): Promise<PaginatedResponse<Campaign>> =>
    apiService.get('/campaigns/ending-soon'),

  getTotalRaised: (): Promise<{
    total_raised: string
    total_donations: number
    average_donation: string
  }> => apiService.get('/campaigns/total-raised'),

  getStats: (): Promise<{
    active: number
    completed: number
    cancelled: number
    draft: number
    total: number
  }> => apiService.get('/campaigns/stats'),
}

// Categories API
export const categoriesApi = {
  getAll: (): Promise<CampaignCategory[]> => apiService.get('/categories'),

  getById: (id: number): Promise<CampaignCategory> => apiService.get(`/categories/${id}`),
}

// Donations API
export const donationsApi = {
  getStats: (): Promise<DonationStats> => apiService.get('/donations/my-stats'),

  getMyDonations: (params?: Record<string, unknown>): Promise<PaginatedResponse<Donation>> =>
    apiService.get('/donations/my-donations', { params }),

  getAll: (params?: Record<string, unknown>): Promise<PaginatedResponse<Donation>> =>
    apiService.get('/admin/donations', { params }),

  create: (data: Record<string, unknown>): Promise<{ message: string; donation: Donation }> =>
    apiService.post('/donations', data),

  getById: (id: number): Promise<Donation> => apiService.get(`/donations/${id}`),

  getReceipt: (id: number): Promise<{ data: Blob; filename?: string; contentType?: string }> => 
    apiService.getFile(`/donations/${id}/receipt`),
}

// Users API (Admin)
export const usersApi = {
  getAll: (params?: Record<string, unknown>): Promise<PaginatedResponse<User>> =>
    apiService.get('/admin/users', { params }),

  getById: (id: number): Promise<User> => apiService.get(`/admin/users/${id}`),

  create: (
    data: Partial<User> & { password: string; password_confirmation: string },
  ): Promise<User> => apiService.post('/admin/users', data),

  update: (id: number, data: Partial<User>): Promise<User> =>
    apiService.put(`/admin/users/${id}`, data),

  delete: (id: number): Promise<void> => apiService.delete(`/admin/users/${id}`),

  getStatistics: (): Promise<{
    overview: {
      total_users: number
      admin_users: number
      active_users: number
      new_users_this_month: number
    }
    departments: Array<{ department: string; count: number }>
    roles: Array<{ role: string; count: number }>
    registration_trend: Array<{ date: string; count: number }>
  }> => apiService.get('/admin/users/statistics'),

  bulkUpdate: (data: {
    user_ids: number[]
    action:
      | 'activate'
      | 'deactivate'
      | 'change_department'
      | 'change_role'
      | 'make_admin'
      | 'remove_admin'
    value?: string
  }): Promise<{ message: string; updated_users: number[]; total_updated: number }> =>
    apiService.post('/admin/users/bulk-update', data),

  export: (
    params?: Record<string, unknown>,
  ): Promise<{
    data: unknown[]
    format: string
    filename: string
    count: number
  }> => apiService.get('/export/users', { params }),
}

// Reports API (Admin)
export interface ReportParams {
  start_date: string
  end_date: string
  group_by?: 'day' | 'week' | 'month' | 'quarter'
  status?: string
  category_id?: number
  department?: string
}

export interface FinancialReport {
  summary: {
    total_raised: number
    total_donations: number
    avg_donation: number
    unique_donors: number
    campaigns_funded: number
  }
  trends: Array<{
    period: string
    donations_count: number
    total_amount: number
    avg_amount: number
  }>
  by_category: Array<{
    name: string
    donations_count: number
    total_amount: number
    avg_donation: number
  }>
  by_department: Array<{
    department: string
    donations_count: number
    total_amount: number
    unique_donors: number
  }>
  by_payment_method: Array<{
    payment_method: string
    donations_count: number
    total_amount: number
    avg_amount: number
  }>
  top_campaigns: Campaign[]
  top_donors: User[]
}

export interface CampaignReport {
  summary: {
    total_campaigns: number
    total_target: number
    total_raised: number
    avg_target: number
    avg_raised: number
    success_rate: number
  }
  by_status: Record<
    string,
    {
      count: number
      total_target: number
      total_raised: number
    }
  >
  by_category: Record<
    string,
    {
      count: number
      total_target: number
      total_raised: number
      avg_progress: number
    }
  >
  performance_ranges: {
    '0-25%': number
    '25-50%': number
    '50-75%': number
    '75-100%': number
    'Over 100%': number
  }
  detailed_campaigns: Array<{
    id: number
    title: string
    category: string
    creator: string
    target_amount: number
    current_amount: number
    progress_percentage: number
    status: string
    donations_count: number
    created_at: string
    days_active: number
  }>
}

export interface UserEngagementReport {
  summary: {
    total_users: number
    active_users: number
    engagement_rate: number
    avg_donations_per_user: number
    avg_amount_per_user: number
  }
  by_department: Record<
    string,
    {
      total_users: number
      active_users: number
      total_donations: number
      total_amount: number
      engagement_rate: number
    }
  >
  participation_levels: {
    non_participants: number
    light_participants: number
    moderate_participants: number
    heavy_participants: number
  }
  top_participants: Array<{
    id: number
    name: string
    employee_id: string
    department: string
    donations_count: number
    total_donated: number
    avg_donation: number
  }>
}

export interface ImpactReport {
  overview: {
    total_funds_raised: number
    campaigns_completed: number
    employees_participated: number
    beneficiary_categories: number
  }
  category_impact: Array<{
    id: number
    name: string
    description: string
    total_raised: number
    campaigns_count: number
  }>
  monthly_impact: Array<{
    month: string
    donations_count: number
    total_raised: number
    unique_donors: number
    campaigns_supported: number
  }>
  success_stories: Array<{
    id: number
    title: string
    category: string
    target_amount: number
    final_amount: number
    percentage_achieved: number
    donors_count: number
    creator: string
  }>
  department_participation: Array<{
    department: string
    total_employees: number
    participants: number
    total_donations: number
    total_contributed: number
  }>
}

export const reportsApi = {
  getFinancialReport: (params: ReportParams): Promise<FinancialReport> =>
    apiService.get('/admin/reports/financial', { params }),

  getCampaignReport: (params: ReportParams): Promise<CampaignReport> =>
    apiService.get('/admin/reports/campaigns', { params }),

  getUserEngagementReport: (params: ReportParams): Promise<UserEngagementReport> =>
    apiService.get('/admin/reports/user-engagement', { params }),

  getImpactReport: (params: ReportParams): Promise<ImpactReport> =>
    apiService.get('/admin/reports/impact', { params }),

  exportData: (data: {
    type: 'donations' | 'campaigns' | 'users' | 'financial' | 'impact'
    format: 'csv' | 'json' | 'excel'
    start_date?: string
    end_date?: string
  }): Promise<{
    data: unknown[]
    type: string
    format: string
    filename: string
    count: number
    generated_at: string
  }> => apiService.post('/admin/export', data),
}
