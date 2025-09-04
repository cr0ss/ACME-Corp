<template>
  <div class="space-y-6">
    <div class="flex justify-between items-center">
      <h1 class="text-3xl font-bold text-gray-900">Reports & Analytics</h1>
      <div class="flex items-center space-x-4">
        <select
          v-model="selectedExportType"
          class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          <option value="financial">Financial</option>
          <option value="campaigns">Campaigns</option>
          <option value="users">Users</option>
          <option value="donations">Donations</option>
          <option value="impact">Impact</option>
        </select>
        <select
          v-model="selectedExportFormat"
          class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          <option value="csv">CSV</option>
          <option value="json">JSON</option>
          <option value="excel">Excel</option>
        </select>
        <button @click="exportData" :disabled="isExporting" class="btn-primary disabled:opacity-50">
          {{ isExporting ? 'Exporting...' : 'Export Data' }}
        </button>
      </div>
    </div>

    <!-- Date Range Selector -->
    <div class="card">
      <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold text-gray-900">Report Filters</h2>
        <div class="text-sm text-blue-600 bg-blue-50 px-3 py-1 rounded">
          📊 Sample data available: Sep 2025 - Nov 2025
        </div>
      </div>
      <div class="flex flex-wrap items-end gap-4">
        <div class="flex-1 min-w-[200px]">
          <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
          <input
            v-model="startDate"
            type="date"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>
        <div class="flex-1 min-w-[200px]">
          <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
          <input
            v-model="endDate"
            type="date"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>
        <div class="flex-1 min-w-[150px]">
          <label class="block text-sm font-medium text-gray-700 mb-1">Group By</label>
          <select
            v-model="groupBy"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option value="month">Month</option>
            <option value="week">Week</option>
            <option value="day">Day</option>
            <option value="quarter">Quarter</option>
          </select>
        </div>
        <div>
          <button
            @click="loadReports"
            :disabled="isLoading"
            class="btn-primary disabled:opacity-50 whitespace-nowrap"
          >
            {{ isLoading ? 'Loading...' : 'Generate Reports' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Report Tabs -->
    <div class="card">
      <div class="border-b border-gray-200 mb-6">
        <nav class="flex space-x-8">
          <button
            v-for="tab in tabs"
            :key="tab.id"
            @click="activeTab = tab.id"
            :class="[
              activeTab === tab.id
                ? 'border-blue-500 text-blue-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
              'py-4 px-1 border-b-2 font-medium text-sm',
            ]"
          >
            {{ tab.name }}
          </button>
        </nav>
      </div>

      <div>
        <!-- Financial Report Tab -->
        <div v-if="activeTab === 'financial'" class="space-y-6">
          <div v-if="financialReport">
            <!-- Financial Summary Cards -->
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
              <div class="bg-blue-50 border border-blue-200 p-4 rounded-lg shadow-sm">
                <h3 class="text-xs font-medium text-blue-800 mb-2">Total Raised</h3>
                <p class="text-xl font-bold text-blue-900">
                  {{ formatSmartCurrency(financialReport.summary.total_raised) }}
                </p>
              </div>
              <div class="bg-green-50 border border-green-200 p-4 rounded-lg shadow-sm">
                <h3 class="text-xs font-medium text-green-800 mb-2">Total Donations</h3>
                <p class="text-xl font-bold text-green-900">
                  {{ formatCompactNumber(financialReport.summary.total_donations) }}
                </p>
              </div>
              <div class="bg-purple-50 border border-purple-200 p-4 rounded-lg shadow-sm">
                <h3 class="text-xs font-medium text-purple-800 mb-2">Avg Donation</h3>
                <p class="text-xl font-bold text-purple-900">
                  {{ formatCurrency(financialReport.summary.avg_donation) }}
                </p>
              </div>
              <div class="bg-yellow-50 border border-yellow-200 p-4 rounded-lg shadow-sm">
                <h3 class="text-xs font-medium text-yellow-800 mb-2">Unique Donors</h3>
                <p class="text-xl font-bold text-yellow-900">
                  {{ formatCompactNumber(financialReport.summary.unique_donors) }}
                </p>
              </div>
              <div class="bg-red-50 border border-red-200 p-4 rounded-lg shadow-sm">
                <h3 class="text-xs font-medium text-red-800 mb-2">Campaigns Funded</h3>
                <p class="text-xl font-bold text-red-900">
                  {{ formatNumber(financialReport.summary.campaigns_funded) }}
                </p>
              </div>
            </div>

            <!-- Financial Trends Chart -->
            <div class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm shadow-sm mb-6">
              <h3 class="text-lg font-semibold mb-6">Donation Trends</h3>
              <div
                class="h-64 flex items-center justify-center text-gray-500 border-2 border-dashed border-gray-200 rounded-lg"
              >
                Chart visualization would go here (trends: {{ financialReport.trends.length }} data
                points)
              </div>
            </div>

            <!-- Category Breakdown -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
              <div class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm shadow-sm">
                <h3 class="text-lg font-semibold mb-6">By Category</h3>
                <div class="space-y-3">
                  <div
                    v-for="item in financialReport.by_category"
                    :key="item.name"
                    class="flex justify-between items-center py-2 border-b border-gray-100"
                  >
                    <span class="font-medium">{{ item.name }}</span>
                    <div class="text-right">
                      <div class="font-bold">{{ formatCurrency(item.total_amount) }}</div>
                      <div class="text-sm text-gray-500">{{ item.donations_count }} donations</div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm shadow-sm">
                <h3 class="text-lg font-semibold mb-6">By Department</h3>
                <div class="space-y-3">
                  <div
                    v-for="item in financialReport.by_department"
                    :key="item.department"
                    class="flex justify-between items-center py-2 border-b border-gray-100"
                  >
                    <span class="font-medium">{{ item.department }}</span>
                    <div class="text-right">
                      <div class="font-bold">{{ formatCurrency(item.total_amount) }}</div>
                      <div class="text-sm text-gray-500">{{ item.unique_donors }} donors</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Top Campaigns and Donors -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
              <div class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm shadow-sm">
                <h3 class="text-lg font-semibold mb-6">Top Campaigns</h3>
                <div class="space-y-3">
                  <div
                    v-for="campaign in financialReport.top_campaigns.slice(0, 5)"
                    :key="campaign.id"
                    class="flex justify-between items-center py-2 border-b border-gray-100"
                  >
                    <div>
                      <div class="font-medium">{{ campaign.title }}</div>
                      <div class="text-sm text-gray-500">{{ campaign.category?.name }}</div>
                    </div>
                    <div class="text-right">
                      <div class="font-bold">
                        {{ formatCurrency(campaign.current_amount) }}
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm shadow-sm">
                <h3 class="text-lg font-semibold mb-6">Top Donors</h3>
                <div class="space-y-3">
                  <div
                    v-for="donor in financialReport.top_donors.slice(0, 5)"
                    :key="donor.id"
                    class="flex justify-between items-center py-2 border-b border-gray-100"
                  >
                    <div>
                      <div class="font-medium">{{ donor.name }}</div>
                      <div class="text-sm text-gray-500">{{ donor.department }}</div>
                    </div>
                    <div class="text-right">
                      <div class="font-bold">{{ formatCurrency(donor.total_donated || 0) }}</div>
                      <div class="text-sm text-gray-500">{{ donor.donation_count || 0 }} donations</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div v-else class="text-center py-8 text-gray-500">
            Select a date range and click "Generate Reports" to view financial data
          </div>
        </div>

        <!-- Campaign Report Tab -->
        <div v-if="activeTab === 'campaigns'" class="space-y-6">
          <div v-if="campaignReport">
            <!-- Campaign Summary Cards -->
            <div class="grid grid-cols-2 md:grid-cols-6 gap-4 mb-6">
              <div class="bg-blue-50 border border-blue-200 p-4 rounded-lg shadow-sm">
                <h3 class="text-xs font-medium text-blue-800 mb-2">Total Campaigns</h3>
                <p class="text-xl font-bold text-blue-900">
                  {{ formatNumber(campaignReport.summary.total_campaigns) }}
                </p>
              </div>
              <div class="bg-green-50 border border-green-200 p-4 rounded-lg shadow-sm">
                <h3 class="text-xs font-medium text-green-800 mb-2">Total Target</h3>
                <p class="text-xl font-bold text-green-900">
                  {{ formatSmartCurrency(campaignReport.summary.total_target) }}
                </p>
              </div>
              <div class="bg-purple-50 border border-purple-200 p-4 rounded-lg shadow-sm">
                <h3 class="text-xs font-medium text-purple-800 mb-2">Total Raised</h3>
                <p class="text-xl font-bold text-purple-900">
                  {{ formatSmartCurrency(campaignReport.summary.total_raised) }}
                </p>
              </div>
              <div class="bg-yellow-50 border border-yellow-200 p-4 rounded-lg shadow-sm">
                <h3 class="text-xs font-medium text-yellow-800 mb-2">Avg Target</h3>
                <p class="text-xl font-bold text-yellow-900">
                  {{ formatCurrency(campaignReport.summary.avg_target) }}
                </p>
              </div>
              <div class="bg-red-50 border border-red-200 p-4 rounded-lg shadow-sm">
                <h3 class="text-xs font-medium text-red-800 mb-2">Avg Raised</h3>
                <p class="text-xl font-bold text-red-900">
                  {{ formatCurrency(campaignReport.summary.avg_raised) }}
                </p>
              </div>
              <div class="bg-indigo-50 border border-indigo-200 p-4 rounded-lg shadow-sm">
                <h3 class="text-xs font-medium text-indigo-800 mb-2">Success Rate</h3>
                <p class="text-xl font-bold text-indigo-900">
                  {{ formatPercentage(campaignReport.summary.success_rate / 100) }}
                </p>
              </div>
            </div>

            <!-- Performance Ranges -->
            <div class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm mb-6">
              <h3 class="text-lg font-semibold mb-6">Campaign Performance Distribution</h3>
              <div class="grid grid-cols-5 gap-4">
                <div
                  v-for="(count, range) in campaignReport.performance_ranges"
                  :key="range"
                  class="text-center p-4 bg-gray-50 rounded-lg"
                >
                  <div class="text-2xl font-bold text-gray-900">{{ count }}</div>
                  <div class="text-sm text-gray-600">{{ range }}</div>
                </div>
              </div>
            </div>

            <!-- Status and Category Breakdown -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
              <div class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm">
                <h3 class="text-lg font-semibold mb-6">By Status</h3>
                <div class="space-y-3">
                  <div
                    v-for="(data, status) in campaignReport.by_status"
                    :key="status"
                    class="flex justify-between items-center py-2 border-b border-gray-100"
                  >
                    <span class="font-medium capitalize">{{ status }}</span>
                    <div class="text-right">
                      <div class="font-bold">{{ data.count }} campaigns</div>
                      <div class="text-sm text-gray-500">
                        {{ formatCurrency(data.total_raised) }} raised
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm">
                <h3 class="text-lg font-semibold mb-6">By Category</h3>
                <div class="space-y-3">
                  <div
                    v-for="(data, category) in campaignReport.by_category"
                    :key="category"
                    class="flex justify-between items-center py-2 border-b border-gray-100"
                  >
                    <span class="font-medium">{{ category }}</span>
                    <div class="text-right">
                      <div class="font-bold">{{ data.count }} campaigns</div>
                      <div class="text-sm text-gray-500">
                        {{ formatPercentage(data.avg_progress / 100) }} avg progress
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div v-else class="text-center py-8 text-gray-500">
            Select a date range and click "Generate Reports" to view campaign performance data
          </div>
        </div>

        <!-- User Engagement Report Tab -->
        <div v-if="activeTab === 'engagement'" class="space-y-6">
          <div v-if="engagementReport">
            <!-- Engagement Summary Cards -->
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
              <div class="bg-blue-50 border border-blue-200 p-4 rounded-lg shadow-sm">
                <h3 class="text-xs font-medium text-blue-800 mb-2">Total Users</h3>
                <p class="text-xl font-bold text-blue-900">
                  {{ formatNumber(engagementReport.summary.total_users) }}
                </p>
              </div>
              <div class="bg-green-50 border border-green-200 p-4 rounded-lg shadow-sm">
                <h3 class="text-xs font-medium text-green-800 mb-2">Active Users</h3>
                <p class="text-xl font-bold text-green-900">
                  {{ formatNumber(engagementReport.summary.active_users) }}
                </p>
              </div>
              <div class="bg-purple-50 border border-purple-200 p-4 rounded-lg shadow-sm">
                <h3 class="text-xs font-medium text-purple-800 mb-2">Engagement Rate</h3>
                <p class="text-xl font-bold text-purple-900">
                  {{ formatPercentage(engagementReport.summary.engagement_rate / 100) }}
                </p>
              </div>
              <div class="bg-yellow-50 border border-yellow-200 p-4 rounded-lg shadow-sm">
                <h3 class="text-xs font-medium text-yellow-800 mb-2">Avg Donations/User</h3>
                <p class="text-xl font-bold text-yellow-900">
                  {{ formatNumber(engagementReport.summary.avg_donations_per_user) }}
                </p>
              </div>
              <div class="bg-red-50 border border-red-200 p-4 rounded-lg shadow-sm">
                <h3 class="text-xs font-medium text-red-800 mb-2">Avg Amount/User</h3>
                <p class="text-xl font-bold text-red-900">
                  {{ formatCurrency(engagementReport.summary.avg_amount_per_user) }}
                </p>
              </div>
            </div>

            <!-- Participation Levels -->
            <div class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm mb-6">
              <h3 class="text-lg font-semibold mb-6">Participation Levels</h3>
              <div class="grid grid-cols-4 gap-4">
                <div class="text-center p-4 bg-red-50 rounded-lg">
                  <div class="text-2xl font-bold text-red-900">
                    {{ engagementReport.participation_levels.non_participants }}
                  </div>
                  <div class="text-sm text-red-700">Non-Participants</div>
                </div>
                <div class="text-center p-4 bg-yellow-50 rounded-lg">
                  <div class="text-2xl font-bold text-yellow-900">
                    {{ engagementReport.participation_levels.light_participants }}
                  </div>
                  <div class="text-sm text-yellow-700">Light (1-3 donations)</div>
                </div>
                <div class="text-center p-4 bg-blue-50 rounded-lg">
                  <div class="text-2xl font-bold text-blue-900">
                    {{ engagementReport.participation_levels.moderate_participants }}
                  </div>
                  <div class="text-sm text-blue-700">Moderate (4-10 donations)</div>
                </div>
                <div class="text-center p-4 bg-green-50 rounded-lg">
                  <div class="text-2xl font-bold text-green-900">
                    {{ engagementReport.participation_levels.heavy_participants }}
                  </div>
                  <div class="text-sm text-green-700">Heavy (10+ donations)</div>
                </div>
              </div>
            </div>

            <!-- Department Engagement and Top Participants -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
              <div class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm">
                <h3 class="text-lg font-semibold mb-6">By Department</h3>
                <div class="space-y-3">
                  <div
                    v-for="(data, department) in engagementReport.by_department"
                    :key="department"
                    class="flex justify-between items-center py-2 border-b border-gray-100"
                  >
                    <span class="font-medium">{{ department }}</span>
                    <div class="text-right">
                      <div class="font-bold">
                        {{ formatPercentage(data.engagement_rate / 100) }}
                      </div>
                      <div class="text-sm text-gray-500">
                        {{ data.active_users }}/{{ data.total_users }} users
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm">
                <h3 class="text-lg font-semibold mb-6">Top Participants</h3>
                <div class="space-y-3">
                  <div
                    v-for="participant in engagementReport.top_participants.slice(0, 5)"
                    :key="participant.id"
                    class="flex justify-between items-center py-2 border-b border-gray-100"
                  >
                    <div>
                      <div class="font-medium">{{ participant.name }}</div>
                      <div class="text-sm text-gray-500">{{ participant.department }}</div>
                    </div>
                    <div class="text-right">
                      <div class="font-bold">{{ formatCurrency(participant.total_donated) }}</div>
                      <div class="text-sm text-gray-500">
                        {{ participant.donations_count }} donations
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div v-else class="text-center py-8 text-gray-500">
            Select a date range and click "Generate Reports" to view user engagement data
          </div>
        </div>

        <!-- Impact Report Tab -->
        <div v-if="activeTab === 'impact'" class="space-y-6">
          <div v-if="impactReport">
            <!-- Impact Overview Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
              <div class="bg-blue-50 border border-blue-200 p-4 rounded-lg shadow-sm">
                <h3 class="text-xs font-medium text-blue-800 mb-2">Total Funds Raised</h3>
                <p class="text-xl font-bold text-blue-900">
                  {{ formatSmartCurrency(impactReport.overview.total_funds_raised) }}
                </p>
              </div>
              <div class="bg-green-50 border border-green-200 p-4 rounded-lg shadow-sm">
                <h3 class="text-xs font-medium text-green-800 mb-2">Campaigns Completed</h3>
                <p class="text-xl font-bold text-green-900">
                  {{ formatNumber(impactReport.overview.campaigns_completed) }}
                </p>
              </div>
              <div class="bg-purple-50 border border-purple-200 p-4 rounded-lg shadow-sm">
                <h3 class="text-xs font-medium text-purple-800 mb-2">Employees Participated</h3>
                <p class="text-xl font-bold text-purple-900">
                  {{ formatNumber(impactReport.overview.employees_participated) }}
                </p>
              </div>
              <div class="bg-yellow-50 border border-yellow-200 p-4 rounded-lg shadow-sm">
                <h3 class="text-xs font-medium text-yellow-800 mb-2">Beneficiary Categories</h3>
                <p class="text-xl font-bold text-yellow-900">
                  {{ formatNumber(impactReport.overview.beneficiary_categories) }}
                </p>
              </div>
            </div>

            <!-- Success Stories -->
            <div class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm mb-6">
              <h3 class="text-lg font-semibold mb-6">Success Stories</h3>
              <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <div
                  v-for="story in impactReport.success_stories.slice(0, 4)"
                  :key="story.id"
                  class="p-4 bg-green-50 rounded-lg"
                >
                  <h4 class="font-semibold text-green-900">{{ story.title }}</h4>
                  <p class="text-sm text-green-700 mt-1">{{ story.category }}</p>
                  <div class="mt-2 flex justify-between items-center">
                    <div>
                      <span class="text-2xl font-bold text-green-900">{{
                        formatPercentage(story.percentage_achieved / 100)
                      }}</span>
                      <span class="text-sm text-green-700"> achieved</span>
                    </div>
                    <div class="text-right">
                      <div class="font-bold text-green-900">
                        {{ formatCurrency(story.final_amount) }}
                      </div>
                      <div class="text-sm text-green-700">{{ story.donors_count }} donors</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Category Impact and Department Participation -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
              <div class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm">
                <h3 class="text-lg font-semibold mb-6">Category Impact</h3>
                <div class="space-y-3">
                  <div
                    v-for="category in impactReport.category_impact.slice(0, 5)"
                    :key="category.id"
                    class="flex justify-between items-center py-2 border-b border-gray-100"
                  >
                    <span class="font-medium">{{ category.name }}</span>
                    <div class="text-right">
                      <div class="font-bold">{{ formatCurrency(category.total_raised) }}</div>
                      <div class="text-sm text-gray-500">
                        {{ category.campaigns_count }} campaigns
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm">
                <h3 class="text-lg font-semibold mb-6">Department Participation</h3>
                <div class="space-y-3">
                  <div
                    v-for="dept in impactReport.department_participation.slice(0, 5)"
                    :key="dept.department"
                    class="flex justify-between items-center py-2 border-b border-gray-100"
                  >
                    <span class="font-medium">{{ dept.department }}</span>
                    <div class="text-right">
                      <div class="font-bold">{{ formatCurrency(dept.total_contributed || 0) }}</div>
                      <div class="text-sm text-gray-500">
                        {{ dept.participants }}/{{ dept.total_employees }} employees
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div v-else class="text-center py-8 text-gray-500">
            Select a date range and click "Generate Reports" to view impact data
          </div>
        </div>
      </div>
    </div>

    <!-- Loading Overlay -->
    <div
      v-if="isLoading"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[9999]"
    >
      <div class="bg-white p-6 rounded-lg shadow-lg">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
        <p class="mt-4 text-gray-600">Generating reports...</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import {
  reportsApi,
  type FinancialReport,
  type CampaignReport,
  type UserEngagementReport,
  type ImpactReport,
  type ReportParams,
} from '@/services/api'
import {
  formatCurrency,
  formatNumber,
  formatPercentage,
  formatSmartCurrency,
  formatCompactNumber,
} from '@/utils/formatters'

// State
const isLoading = ref(false)
const isExporting = ref(false)
const activeTab = ref('financial')

// Date range
const startDate = ref('')
const endDate = ref('')
const groupBy = ref<'day' | 'week' | 'month' | 'quarter'>('month')

// Export options
const selectedExportType = ref<'donations' | 'campaigns' | 'users' | 'financial' | 'impact'>('financial')
const selectedExportFormat = ref<'csv' | 'json' | 'excel'>('csv')

// Report data
const financialReport = ref<FinancialReport | null>(null)
const campaignReport = ref<CampaignReport | null>(null)
const engagementReport = ref<UserEngagementReport | null>(null)
const impactReport = ref<ImpactReport | null>(null)

// Tabs configuration
const tabs = [
  { id: 'financial', name: 'Financial Analysis' },
  { id: 'campaigns', name: 'Campaign Performance' },
  { id: 'engagement', name: 'User Engagement' },
  { id: 'impact', name: 'CSR Impact' },
]

// Computed
const reportParams = computed(
  (): ReportParams => ({
    start_date: startDate.value,
    end_date: endDate.value,
    group_by: groupBy.value,
  }),
)

// Helper functions

function setDefaultDates() {
  // Set dates to match the sample data range (Sep 2025 - Nov 2025)
  // In a real application, you would query the API for the actual data range
  startDate.value = '2025-09-01'
  endDate.value = '2025-11-30'
}

// Methods
async function loadReports() {
  if (!startDate.value || !endDate.value) {
    alert('Please select both start and end dates')
    return
  }

  isLoading.value = true
  try {
    const params = reportParams.value

    // Load all reports in parallel
    const [financial, campaigns, engagement, impact] = await Promise.all([
      reportsApi.getFinancialReport(params),
      reportsApi.getCampaignReport(params),
      reportsApi.getUserEngagementReport(params),
      reportsApi.getImpactReport(params),
    ])

    financialReport.value = financial
    campaignReport.value = campaigns
    engagementReport.value = engagement
    impactReport.value = impact
  } catch (error) {
    console.error('Failed to load reports:', error)
    alert('Failed to load reports. Please try again.')
  } finally {
    isLoading.value = false
  }
}

async function exportData() {
  if (!startDate.value || !endDate.value) {
    alert('Please select both start and end dates')
    return
  }

  isExporting.value = true
  try {
    const exportData = await reportsApi.exportData({
      type: selectedExportType.value,
      format: selectedExportFormat.value,
      start_date: startDate.value,
      end_date: endDate.value,
    })

    // Create and download file
    const dataStr =
      selectedExportFormat.value === 'json'
        ? JSON.stringify(exportData.data, null, 2)
        : convertToCSV(exportData.data)

    const dataBlob = new Blob([dataStr], {
      type: selectedExportFormat.value === 'json' ? 'application/json' : 'text/csv',
    })

    const url = window.URL.createObjectURL(dataBlob)
    const link = document.createElement('a')
    link.href = url
    link.download = `${exportData.filename}.${selectedExportFormat.value}`
    link.click()

    window.URL.revokeObjectURL(url)
  } catch (error) {
    console.error('Failed to export data:', error)
    alert('Failed to export data. Please try again.')
  } finally {
    isExporting.value = false
  }
}

  function convertToCSV(data: unknown[]): string {
  if (!data.length) return ''

  const firstRow = data[0] as Record<string, unknown>
  const headers = Object.keys(firstRow)
  const csvContent = [
    headers.join(','),
    ...data.map((row) =>
      headers
        .map((header) => {
          const value = (row as Record<string, unknown>)[header]
          return typeof value === 'string' && value.includes(',') ? `"${value}"` : value
        })
        .join(','),
    ),
  ].join('\n')

  return csvContent
}

// Lifecycle
onMounted(() => {
  setDefaultDates()
})
</script>
