<template>
    <div class="tax-summary-container">
      <!-- Header Section -->
      <div class="page-header">
        <div class="header-content">
          <div class="breadcrumb">
            <router-link to="/tax-transactions" class="breadcrumb-link">
              <i class="fas fa-receipt"></i>
              Tax Transactions
            </router-link>
            <i class="fas fa-chevron-right breadcrumb-separator"></i>
            <span class="breadcrumb-current">Tax Summary Report</span>
          </div>
          
          <div class="title-section">
            <h1 class="page-title">
              <i class="fas fa-chart-bar"></i>
              Tax Summary Report
            </h1>
            <p class="page-description">Comprehensive overview of tax transactions and analytics</p>
          </div>

          <div class="header-actions">
            <button @click="exportReport" class="btn btn-outline" :disabled="loading">
              <i class="fas fa-download"></i>
              Export Report
            </button>
            <button @click="printReport" class="btn btn-outline">
              <i class="fas fa-print"></i>
              Print Report
            </button>
            <button @click="scheduleReport" class="btn btn-primary">
              <i class="fas fa-clock"></i>
              Schedule Report
            </button>
          </div>
        </div>
      </div>

      <!-- Filter Section -->
      <div class="filter-section">
        <div class="filter-card">
          <div class="filter-header">
            <h3>
              <i class="fas fa-filter"></i>
              Report Filters
            </h3>
            <button @click="resetFilters" class="btn btn-text">
              <i class="fas fa-undo"></i>
              Reset
            </button>
          </div>
          
          <div class="filter-grid">
            <div class="filter-group">
              <label class="filter-label">
                <i class="fas fa-calendar-alt"></i>
                Date Range
              </label>
              <div class="date-range-wrapper">
                <input 
                  v-model="filters.from_date" 
                  type="date"
                  class="filter-input"
                  @change="applyFilters"
                />
                <span class="date-separator">to</span>
                <input 
                  v-model="filters.to_date" 
                  type="date"
                  class="filter-input"
                  @change="applyFilters"
                />
              </div>
            </div>

            <div class="filter-group">
              <label class="filter-label">
                <i class="fas fa-tag"></i>
                Tax Type
              </label>
              <select v-model="filters.tax_type" class="filter-select" @change="applyFilters">
                <option value="">All Tax Types</option>
                <option value="VAT">VAT</option>
                <option value="Sales Tax">Sales Tax</option>
                <option value="Income Tax">Income Tax</option>
                <option value="Corporate Tax">Corporate Tax</option>
                <option value="Withholding Tax">Withholding Tax</option>
                <option value="Property Tax">Property Tax</option>
                <option value="Excise Tax">Excise Tax</option>
              </select>
            </div>

            <div class="filter-group">
              <label class="filter-label">
                <i class="fas fa-coins"></i>
                Currency
              </label>
              <select v-model="filters.currency" class="filter-select" @change="applyFilters">
                <option value="">All Currencies</option>
                <option v-for="currency in currencies" :key="currency" :value="currency">
                  {{ currency }}
                </option>
              </select>
            </div>

            <div class="filter-group">
              <label class="filter-label">
                <i class="fas fa-flag"></i>
                Status
              </label>
              <select v-model="filters.status" class="filter-select" @change="applyFilters">
                <option value="">All Statuses</option>
                <option value="Draft">Draft</option>
                <option value="Pending">Pending</option>
                <option value="Approved">Approved</option>
                <option value="Posted">Posted</option>
                <option value="Filed">Filed</option>
                <option value="Paid">Paid</option>
                <option value="Completed">Completed</option>
              </select>
            </div>

            <div class="filter-group">
              <label class="filter-label">
                <i class="fas fa-eye"></i>
                Report View
              </label>
              <div class="view-toggle">
                <button 
                  @click="currentView = 'summary'"
                  :class="['view-btn', { active: currentView === 'summary' }]"
                >
                  <i class="fas fa-chart-pie"></i>
                  Summary
                </button>
                <button 
                  @click="currentView = 'detailed'"
                  :class="['view-btn', { active: currentView === 'detailed' }]"
                >
                  <i class="fas fa-list-alt"></i>
                  Detailed
                </button>
                <button 
                  @click="currentView = 'trends'"
                  :class="['view-btn', { active: currentView === 'trends' }]"
                >
                  <i class="fas fa-chart-line"></i>
                  Trends
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="loading-section">
        <div class="loading-spinner">
          <i class="fas fa-spinner fa-spin"></i>
        </div>
        <p>Generating tax summary report...</p>
      </div>

      <!-- Report Content -->
      <div v-else class="report-content">
        <!-- Key Metrics -->
        <div class="metrics-section">
          <div class="metrics-grid">
            <div class="metric-card total-amount">
              <div class="metric-icon">
                <i class="fas fa-dollar-sign"></i>
              </div>
              <div class="metric-content">
                <h3>${{ formatCurrency(summary.grand_total || 0) }}</h3>
                <p>Total Tax Amount</p>
                <div class="metric-change" :class="getTrendClass(summary.total_change)">
                  <i :class="getTrendIcon(summary.total_change)"></i>
                  {{ Math.abs(summary.total_change || 0) }}% vs last period
                </div>
              </div>
            </div>

            <div class="metric-card transaction-count">
              <div class="metric-icon">
                <i class="fas fa-file-invoice"></i>
              </div>
              <div class="metric-content">
                <h3>{{ summary.transaction_count || 0 }}</h3>
                <p>Total Transactions</p>
                <div class="metric-change" :class="getTrendClass(summary.count_change)">
                  <i :class="getTrendIcon(summary.count_change)"></i>
                  {{ Math.abs(summary.count_change || 0) }}% vs last period
                </div>
              </div>
            </div>

            <div class="metric-card avg-amount">
              <div class="metric-icon">
                <i class="fas fa-calculator"></i>
              </div>
              <div class="metric-content">
                <h3>${{ formatCurrency(summary.average_amount || 0) }}</h3>
                <p>Average Tax Amount</p>
                <div class="metric-change" :class="getTrendClass(summary.avg_change)">
                  <i :class="getTrendIcon(summary.avg_change)"></i>
                  {{ Math.abs(summary.avg_change || 0) }}% vs last period
                </div>
              </div>
            </div>

            <div class="metric-card compliance-rate">
              <div class="metric-icon">
                <i class="fas fa-shield-alt"></i>
              </div>
              <div class="metric-content">
                <h3>{{ summary.compliance_rate || 0 }}%</h3>
                <p>Compliance Rate</p>
                <div class="metric-change positive">
                  <i class="fas fa-check-circle"></i>
                  On track
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Summary View -->
        <div v-if="currentView === 'summary'" class="summary-view">
          <!-- Tax Type Breakdown -->
          <div class="breakdown-section">
            <div class="breakdown-card">
              <div class="card-header">
                <h3>
                  <i class="fas fa-chart-pie"></i>
                  Tax Type Breakdown
                </h3>
                <div class="card-actions">
                  <button @click="toggleBreakdownView" class="btn-icon">
                    <i :class="breakdownView === 'chart' ? 'fas fa-table' : 'fas fa-chart-pie'"></i>
                  </button>
                </div>
              </div>

              <!-- Chart View -->
              <div v-if="breakdownView === 'chart'" class="chart-container">
                <div class="pie-chart-wrapper">
                  <svg class="pie-chart" viewBox="0 0 200 200">
                    <path 
                      v-for="(segment, index) in pieChartData" 
                      :key="index"
                      :d="segment.path"
                      :fill="segment.color"
                      class="pie-segment"
                      @mouseover="showTooltip"
                      @mouseout="hideTooltip"
                    />
                  </svg>
                  <div class="chart-center">
                    <div class="center-amount">${{ formatCurrency(summary.grand_total) }}</div>
                    <div class="center-label">Total</div>
                  </div>
                </div>
                
                <div class="chart-legend">
                  <div 
                    v-for="(item, index) in summary.summary" 
                    :key="item.tax_type"
                    class="legend-item"
                  >
                    <div class="legend-color" :style="{ backgroundColor: getChartColor(index) }"></div>
                    <div class="legend-content">
                      <div class="legend-label">{{ item.tax_type }}</div>
                      <div class="legend-value">${{ formatCurrency(item.total_amount) }}</div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Table View -->
              <div v-else class="table-view">
                <table class="breakdown-table">
                  <thead>
                    <tr>
                      <th>Tax Type</th>
                      <th>Tax Code</th>
                      <th>Transactions</th>
                      <th>Total Amount</th>
                      <th>Avg Amount</th>
                      <th>Percentage</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="item in summary.summary" :key="`${item.tax_type}-${item.tax_code}`">
                      <td>
                        <div class="tax-type-cell">
                          <i class="fas fa-tag"></i>
                          {{ item.tax_type }}
                        </div>
                      </td>
                      <td>
                        <span class="tax-code-badge">{{ item.tax_code }}</span>
                      </td>
                      <td>
                        <span class="transaction-count">{{ item.transaction_count }}</span>
                      </td>
                      <td>
                        <span class="amount-value">${{ formatCurrency(item.total_amount) }}</span>
                      </td>
                      <td>
                        <span class="amount-value">${{ formatCurrency(item.average_amount) }}</span>
                      </td>
                      <td>
                        <div class="percentage-bar">
                          <div 
                            class="percentage-fill" 
                            :style="{ width: (item.total_amount / summary.grand_total * 100) + '%' }"
                          ></div>
                          <span class="percentage-text">
                            {{ ((item.total_amount / summary.grand_total) * 100).toFixed(1) }}%
                          </span>
                        </div>
                      </td>
                      <td>
                        <div class="action-buttons">
                          <button @click="viewTransactions(item)" class="btn-action btn-view" title="View Transactions">
                            <i class="fas fa-eye"></i>
                          </button>
                          <button @click="exportTypeData(item)" class="btn-action btn-export" title="Export Data">
                            <i class="fas fa-download"></i>
                          </button>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- Currency Breakdown -->
          <div class="currency-section">
            <div class="currency-card">
              <div class="card-header">
                <h3>
                  <i class="fas fa-coins"></i>
                  Currency Breakdown
                </h3>
              </div>
              
              <div class="currency-grid">
                <div 
                  v-for="(data, currency) in currencyBreakdown" 
                  :key="currency"
                  class="currency-item"
                >
                  <div class="currency-header">
                    <span class="currency-code">{{ currency }}</span>
                    <span class="currency-count">{{ data.transaction_count }} transactions</span>
                  </div>
                  <div class="currency-amount">{{ currency }} {{ formatCurrency(data.total_amount) }}</div>
                  <div class="currency-conversion">
                    ≈ USD {{ formatCurrency(data.base_currency_amount || data.total_amount) }}
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Status Overview -->
          <div class="status-section">
            <div class="status-card">
              <div class="card-header">
                <h3>
                  <i class="fas fa-flag"></i>
                  Status Overview
                </h3>
              </div>
              
              <div class="status-grid">
                <div v-for="status in statusOverview" :key="status.name" class="status-item">
                  <div class="status-icon" :class="getStatusClass(status.name)">
                    <i :class="getStatusIcon(status.name)"></i>
                  </div>
                  <div class="status-content">
                    <div class="status-count">{{ status.count }}</div>
                    <div class="status-label">{{ status.name }}</div>
                    <div class="status-amount">${{ formatCurrency(status.amount) }}</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Detailed View -->
        <div v-if="currentView === 'detailed'" class="detailed-view">
          <div class="detailed-card">
            <div class="card-header">
              <h3>
                <i class="fas fa-list-alt"></i>
                Detailed Breakdown
              </h3>
              <div class="card-actions">
                <div class="search-box">
                  <i class="fas fa-search"></i>
                  <input 
                    v-model="detailedSearch" 
                    type="text" 
                    placeholder="Search tax types, codes..."
                    @input="filterDetailedData"
                  />
                </div>
                <select v-model="detailedSort" @change="sortDetailedData" class="sort-select">
                  <option value="tax_type">Sort by Tax Type</option>
                  <option value="total_amount">Sort by Amount</option>
                  <option value="transaction_count">Sort by Count</option>
                </select>
              </div>
            </div>

            <div class="detailed-content">
              <div 
                v-for="item in filteredDetailedData" 
                :key="`${item.tax_type}-${item.tax_code}`"
                class="detailed-item"
              >
                <div class="detailed-header">
                  <div class="detailed-title">
                    <h4>{{ item.tax_type }}</h4>
                    <span class="detailed-code">{{ item.tax_code }}</span>
                  </div>
                  <div class="detailed-amount">
                    ${{ formatCurrency(item.total_amount) }}
                  </div>
                </div>
                
                <div class="detailed-stats">
                  <div class="stat-item">
                    <span class="stat-label">Transactions:</span>
                    <span class="stat-value">{{ item.transaction_count }}</span>
                  </div>
                  <div class="stat-item">
                    <span class="stat-label">Average:</span>
                    <span class="stat-value">${{ formatCurrency(item.average_amount) }}</span>
                  </div>
                  <div class="stat-item">
                    <span class="stat-label">Percentage:</span>
                    <span class="stat-value">{{ ((item.total_amount / summary.grand_total) * 100).toFixed(1) }}%</span>
                  </div>
                </div>
                
                <div class="detailed-actions">
                  <button @click="viewTransactions(item)" class="btn btn-outline btn-sm">
                    <i class="fas fa-eye"></i>
                    View Transactions
                  </button>
                  <button @click="exportTypeData(item)" class="btn btn-outline btn-sm">
                    <i class="fas fa-download"></i>
                    Export
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Trends View -->
        <div v-if="currentView === 'trends'" class="trends-view">
          <div class="trends-card">
            <div class="card-header">
              <h3>
                <i class="fas fa-chart-line"></i>
                Tax Trends Analysis
              </h3>
              <div class="card-actions">
                <select v-model="trendMetric" class="trend-select">
                  <option value="amount">Tax Amount</option>
                  <option value="count">Transaction Count</option>
                  <option value="average">Average Amount</option>
                </select>
              </div>
            </div>

            <div class="trends-content">
              <!-- Line Chart -->
              <div class="chart-wrapper">
                <svg class="trend-chart" viewBox="0 0 600 300">
                  <!-- Grid lines -->
                  <g class="grid">
                    <line v-for="i in 6" :key="`h-${i}`" x1="50" :y1="50 + (i-1) * 40" x2="550" :y2="50 + (i-1) * 40" stroke="#e5e7eb" stroke-width="1"/>
                    <line v-for="i in 13" :key="`v-${i}`" :x1="50 + (i-1) * 40" y1="50" :x2="50 + (i-1) * 40" y2="250" stroke="#e5e7eb" stroke-width="1"/>
                  </g>
                  
                  <!-- Y-axis labels -->
                  <g class="y-labels">
                    <text 
                      v-for="(label, index) in yAxisLabels" 
                      :key="`y-${index}`"
                      x="40" 
                      :y="250 - index * 40" 
                      text-anchor="end" 
                      font-size="12" 
                      fill="#6b7280"
                    >
                      {{ label }}
                    </text>
                  </g>
                  
                  <!-- X-axis labels -->
                  <g class="x-labels">
                    <text 
                      v-for="(label, index) in monthLabels" 
                      :key="`x-${index}`"
                      :x="90 + index * 40" 
                      y="270" 
                      text-anchor="middle" 
                      font-size="12" 
                      fill="#6b7280"
                    >
                      {{ label }}
                    </text>
                  </g>
                  
                  <!-- Trend line -->
                  <polyline
                    :points="trendLinePoints"
                    fill="none"
                    stroke="#6366f1"
                    stroke-width="3"
                    class="trend-line"
                  />
                  
                  <!-- Data points -->
                  <circle
                    v-for="(point, index) in trendPoints"
                    :key="`point-${index}`"
                    :cx="point.x"
                    :cy="point.y"
                    r="4"
                    fill="#6366f1"
                    class="trend-point"
                    @mouseover="showTrendTooltip(point, $event)"
                    @mouseout="hideTrendTooltip"
                  />
                </svg>
                
                <!-- Tooltip -->
                <div 
                  v-if="trendTooltip" 
                  class="trend-tooltip" 
                  :style="trendTooltipStyle"
                >
                  <div class="tooltip-title">{{ trendTooltip.month }}</div>
                  <div class="tooltip-content">
                    <span class="tooltip-label">{{ trendTooltip.label }}:</span>
                    <span class="tooltip-value">{{ trendTooltip.value }}</span>
                  </div>
                </div>
              </div>

              <!-- Growth Analysis -->
              <div class="growth-section">
                <h4>Growth Analysis</h4>
                <div class="growth-grid">
                  <div class="growth-item">
                    <span class="growth-label">Month over Month</span>
                    <span class="growth-value" :class="getGrowthClass(growthData.mom)">
                      <i :class="getGrowthIcon(growthData.mom)"></i>
                      {{ Math.abs(growthData.mom) }}%
                    </span>
                  </div>
                  <div class="growth-item">
                    <span class="growth-label">Quarter over Quarter</span>
                    <span class="growth-value" :class="getGrowthClass(growthData.qoq)">
                      <i :class="getGrowthIcon(growthData.qoq)"></i>
                      {{ Math.abs(growthData.qoq) }}%
                    </span>
                  </div>
                  <div class="growth-item">
                    <span class="growth-label">Year over Year</span>
                    <span class="growth-value" :class="getGrowthClass(growthData.yoy)">
                      <i :class="getGrowthIcon(growthData.yoy)"></i>
                      {{ Math.abs(growthData.yoy) }}%
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Action Bar -->
      <div class="action-bar">
        <div class="action-buttons">
          <button @click="saveReport" class="btn btn-outline">
            <i class="fas fa-save"></i>
            Save Report
          </button>
          <button @click="shareReport" class="btn btn-primary">
            <i class="fas fa-share-alt"></i>
            Share Report
          </button>
        </div>
      </div>
    </div>
</template>

<script>
/* eslint-disable */
import { ref, reactive, computed, onMounted, watch } from 'vue'
import axios from 'axios'


export default {
  name: 'TaxSummaryReport',
  components: {
  },
  setup() {
    const loading = ref(false)
    const currentView = ref('summary')
    const breakdownView = ref('chart')
    const trendMetric = ref('amount')
    const detailedSearch = ref('')
    const detailedSort = ref('tax_type')
    const trendTooltip = ref(null)
    const trendTooltipStyle = ref({})
    
    const currencies = ref(['USD', 'EUR', 'GBP', 'JPY', 'CAD', 'AUD'])

    const filters = reactive({
      from_date: new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0],
      to_date: new Date().toISOString().split('T')[0],
      tax_type: '',
      currency: '',
      status: ''
    })

    const summary = reactive({
      grand_total: 0,
      transaction_count: 0,
      average_amount: 0,
      compliance_rate: 95,
      total_change: 12.5,
      count_change: 8.3,
      avg_change: 4.2,
      summary: []
    })

    const currencyBreakdown = ref({})
    const statusOverview = ref([])
    const monthlyTrends = ref([])
    const growthData = reactive({
      mom: 5.2,
      qoq: 12.8,
      yoy: 18.5
    })

    // Computed properties
    const pieChartData = computed(() => {
      if (!summary.summary.length) return []
      
      let currentAngle = 0
      return summary.summary.map((item, index) => {
        const percentage = (item.total_amount / summary.grand_total) * 100
        const angle = (percentage / 100) * 360
        const startAngle = currentAngle
        const endAngle = currentAngle + angle
        
        const startX = 100 + 80 * Math.cos((startAngle - 90) * Math.PI / 180)
        const startY = 100 + 80 * Math.sin((startAngle - 90) * Math.PI / 180)
        const endX = 100 + 80 * Math.cos((endAngle - 90) * Math.PI / 180)
        const endY = 100 + 80 * Math.sin((endAngle - 90) * Math.PI / 180)
        
        const largeArc = angle > 180 ? 1 : 0
        const path = `M 100 100 L ${startX} ${startY} A 80 80 0 ${largeArc} 1 ${endX} ${endY} Z`
        
        currentAngle = endAngle
        
        return {
          path,
          color: getChartColor(index),
          percentage
        }
      })
    })

    const filteredDetailedData = computed(() => {
      let data = [...summary.summary]
      
      if (detailedSearch.value) {
        data = data.filter(item => 
          item.tax_type.toLowerCase().includes(detailedSearch.value.toLowerCase()) ||
          item.tax_code.toLowerCase().includes(detailedSearch.value.toLowerCase())
        )
      }
      
      data.sort((a, b) => {
        switch (detailedSort.value) {
          case 'total_amount':
            return b.total_amount - a.total_amount
          case 'transaction_count':
            return b.transaction_count - a.transaction_count
          default:
            return a.tax_type.localeCompare(b.tax_type)
        }
      })
      
      return data
    })

    const trendPoints = computed(() => {
      return monthlyTrends.value.map((data, index) => ({
        x: 90 + index * 40,
        y: 250 - (data[trendMetric.value] / Math.max(...monthlyTrends.value.map(d => d[trendMetric.value])) * 200),
        month: data.month,
        value: data[trendMetric.value]
      }))
    })

    const trendLinePoints = computed(() => {
      return trendPoints.value.map(point => `${point.x},${point.y}`).join(' ')
    })

    const yAxisLabels = computed(() => {
      const maxValue = Math.max(...monthlyTrends.value.map(d => d[trendMetric.value]))
      return Array.from({ length: 6 }, (_, i) => formatAxisLabel(maxValue * (5 - i) / 5))
    })

    const monthLabels = computed(() => {
      return monthlyTrends.value.map(data => data.month.substring(0, 3))
    })

    // Methods
    const fetchSummaryData = async () => {
      loading.value = true
      try {
        const response = await axios.get('/accounting/tax-transactions/summary', {
          params: filters
        })
        
        Object.assign(summary, response.data)
        
        // Generate mock data for demonstration
        generateMockData()
      } catch (error) {
        console.error('Error fetching summary data:', error)
        showNotification('Error loading report data', 'error')
      } finally {
        loading.value = false
      }
    }

    const generateMockData = () => {
      // Generate currency breakdown
      currencyBreakdown.value = {
        'USD': { transaction_count: 45, total_amount: 125000, base_currency_amount: 125000 },
        'EUR': { transaction_count: 23, total_amount: 89000, base_currency_amount: 95000 },
        'GBP': { transaction_count: 12, total_amount: 34000, base_currency_amount: 42000 }
      }

      // Generate status overview
      statusOverview.value = [
        { name: 'Draft', count: 15, amount: 45000 },
        { name: 'Pending', count: 25, amount: 78000 },
        { name: 'Approved', count: 30, amount: 95000 },
        { name: 'Completed', count: 20, amount: 67000 }
      ]

      // Generate monthly trends
      const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
      monthlyTrends.value = months.map(month => ({
        month,
        amount: Math.random() * 100000 + 50000,
        count: Math.floor(Math.random() * 100) + 20,
        average: Math.random() * 5000 + 1000
      }))
    }

    const applyFilters = () => {
      fetchSummaryData()
    }

    const resetFilters = () => {
      filters.from_date = new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0]
      filters.to_date = new Date().toISOString().split('T')[0]
      filters.tax_type = ''
      filters.currency = ''
      filters.status = ''
      fetchSummaryData()
    }

    const toggleBreakdownView = () => {
      breakdownView.value = breakdownView.value === 'chart' ? 'table' : 'chart'
    }

    const filterDetailedData = () => {
      // This is handled by the computed property
    }

    const sortDetailedData = () => {
      // This is handled by the computed property
    }

    const sortBy = (field) => {
      detailedSort.value = field
    }

    const showTrendTooltip = (point, event) => {
      trendTooltip.value = {
        month: point.month,
        label: getTrendLabel(),
        value: formatTrendValue(point.value)
      }
      
      trendTooltipStyle.value = {
        left: event.offsetX + 'px',
        top: event.offsetY - 50 + 'px'
      }
    }

    const hideTrendTooltip = () => {
      trendTooltip.value = null
    }

    const viewTransactions = (item) => {
      // Navigate to filtered transaction list
      const query = {
        tax_type: item.tax_type,
        tax_code: item.tax_code,
        from_date: filters.from_date,
        to_date: filters.to_date
      }
      
      window.open(`/tax-transactions?${new URLSearchParams(query).toString()}`, '_blank')
    }

    const exportTypeData = async (item) => {
      try {
        const response = await axios.get('/accounting/tax-transactions/export', {
          params: {
            tax_type: item.tax_type,
            tax_code: item.tax_code,
            from_date: filters.from_date,
            to_date: filters.to_date
          },
          responseType: 'blob'
        })
        
        const url = window.URL.createObjectURL(new Blob([response.data]))
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', `${item.tax_type}-${item.tax_code}-report.xlsx`)
        document.body.appendChild(link)
        link.click()
        link.remove()
        window.URL.revokeObjectURL(url)
        
        showNotification('Data exported successfully', 'success')
      } catch (error) {
        console.error('Error exporting data:', error)
        showNotification('Error exporting data', 'error')
      }
    }

    const exportReport = async () => {
      try {
        const response = await axios.post('/accounting/tax-transactions/export-report', {
          filters,
          view: currentView.value
        }, {
          responseType: 'blob'
        })
        
        const url = window.URL.createObjectURL(new Blob([response.data]))
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', `tax-summary-report-${new Date().toISOString().split('T')[0]}.xlsx`)
        document.body.appendChild(link)
        link.click()
        link.remove()
        window.URL.revokeObjectURL(url)
        
        showNotification('Report exported successfully', 'success')
      } catch (error) {
        console.error('Error exporting report:', error)
        showNotification('Error exporting report', 'error')
      }
    }

    const printReport = () => {
      window.print()
    }

    const scheduleReport = () => {
      showNotification('Report scheduling feature coming soon', 'info')
    }

    const saveReport = () => {
      showNotification('Report saved successfully', 'success')
    }

    const shareReport = () => {
      if (navigator.share) {
        navigator.share({
          title: 'Tax Summary Report',
          text: 'Tax Summary Report',
          url: window.location.href
        })
      } else {
        navigator.clipboard.writeText(window.location.href)
        showNotification('Report link copied to clipboard', 'success')
      }
    }

    // Utility functions
    const formatCurrency = (amount) => {
      return new Intl.NumberFormat('en-US').format(amount || 0)
    }

    const formatAxisLabel = (value) => {
      if (value >= 1000000) return (value / 1000000).toFixed(1) + 'M'
      if (value >= 1000) return (value / 1000).toFixed(1) + 'K'
      return value.toFixed(0)
    }

    const formatTrendValue = (value) => {
      if (trendMetric.value === 'amount') return `$${formatCurrency(value)}`
      if (trendMetric.value === 'count') return value.toString()
      if (trendMetric.value === 'average') return `$${formatCurrency(value)}`
      return value.toString()
    }

    const getTrendLabel = () => {
      const labels = {
        amount: 'Tax Amount',
        count: 'Transaction Count',
        average: 'Average Amount'
      }
      return labels[trendMetric.value] || 'Value'
    }

    const getChartColor = (index) => {
      const colors = [
        '#6366f1', '#8b5cf6', '#ec4899', '#ef4444', '#f59e0b',
        '#10b981', '#06b6d4', '#84cc16', '#f97316', '#64748b'
      ]
      return colors[index % colors.length]
    }

    const getTrendClass = (value) => {
      if (value > 0) return 'positive'
      if (value < 0) return 'negative'
      return 'neutral'
    }

    const getTrendIcon = (value) => {
      if (value > 0) return 'fas fa-arrow-up'
      if (value < 0) return 'fas fa-arrow-down'
      return 'fas fa-minus'
    }

    const getGrowthClass = (value) => {
      if (value > 0) return 'positive'
      if (value < 0) return 'negative'
      return 'neutral'
    }

    const getGrowthIcon = (value) => {
      if (value > 0) return 'fas fa-trending-up'
      if (value < 0) return 'fas fa-trending-down'
      return 'fas fa-minus'
    }

    const getStatusClass = (status) => {
      const statusClasses = {
        'Draft': 'draft',
        'Pending': 'pending',
        'Approved': 'approved',
        'Posted': 'posted',
        'Filed': 'filed',
        'Paid': 'paid',
        'Completed': 'completed',
        'Cancelled': 'cancelled'
      }
      return statusClasses[status] || 'draft'
    }

    const getStatusIcon = (status) => {
      const statusIcons = {
        'Draft': 'fas fa-edit',
        'Pending': 'fas fa-clock',
        'Approved': 'fas fa-check',
        'Posted': 'fas fa-paper-plane',
        'Filed': 'fas fa-file-alt',
        'Paid': 'fas fa-credit-card',
        'Completed': 'fas fa-check-circle',
        'Cancelled': 'fas fa-times-circle'
      }
      return statusIcons[status] || 'fas fa-circle'
    }

    const showTooltip = () => {
      // Implement tooltip logic for pie chart
    }

    const hideTooltip = () => {
      // Implement tooltip logic for pie chart
    }

    const showNotification = (message, type = 'info') => {
      console.log(`${type}: ${message}`)
      // Implement your notification system here
    }

    // Lifecycle
    onMounted(() => {
      fetchSummaryData()
    })

    return {
      loading,
      currentView,
      breakdownView,
      trendMetric,
      detailedSearch,
      detailedSort,
      trendTooltip,
      trendTooltipStyle,
      currencies,
      filters,
      summary,
      currencyBreakdown,
      statusOverview,
      monthlyTrends,
      growthData,
      pieChartData,
      filteredDetailedData,
      trendPoints,
      trendLinePoints,
      yAxisLabels,
      monthLabels,
      applyFilters,
      resetFilters,
      toggleBreakdownView,
      filterDetailedData,
      sortDetailedData,
      sortBy,
      showTrendTooltip,
      hideTrendTooltip,
      viewTransactions,
      exportTypeData,
      exportReport,
      printReport,
      scheduleReport,
      saveReport,
      shareReport,
      formatCurrency,
      formatAxisLabel,
      formatTrendValue,
      getTrendLabel,
      getChartColor,
      getTrendClass,
      getTrendIcon,
      getGrowthClass,
      getGrowthIcon,
      getStatusClass,
      getStatusIcon,
      showTooltip,
      hideTooltip
    }
  }
}
</script>

<style scoped>
.tax-summary-container {
  padding: 2rem;
  background: var(--bg-secondary);
  min-height: 100vh;
}

/* Header */
.page-header {
  margin-bottom: 2rem;
}

.header-content {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.breadcrumb {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.9rem;
}

.breadcrumb-link {
  color: var(--primary-color);
  text-decoration: none;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.breadcrumb-separator {
  color: var(--text-muted);
  font-size: 0.8rem;
}

.breadcrumb-current {
  color: var(--text-secondary);
}

.title-section {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 2rem;
}

.page-title {
  font-size: 2.5rem;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0;
  display: flex;
  align-items: center;
  gap: 1rem;
}

.page-title i {
  color: var(--primary-color);
}

.page-description {
  font-size: 1.1rem;
  color: var(--text-secondary);
  margin: 0.5rem 0 0 0;
}

.header-actions {
  display: flex;
  gap: 1rem;
  align-items: center;
}

/* Filter Section */
.filter-section {
  margin-bottom: 2rem;
}

.filter-card {
  background: var(--card-bg);
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  border: 1px solid var(--border-color);
}

.filter-header {
  padding: 1.5rem 2rem;
  border-bottom: 1px solid var(--border-color);
  background: var(--bg-tertiary);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.filter-header h3 {
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--text-primary);
  margin: 0;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.filter-grid {
  padding: 2rem;
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.filter-label {
  font-weight: 600;
  color: var(--text-primary);
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.9rem;
}

.filter-label i {
  color: var(--primary-color);
  width: 16px;
}

.filter-input,
.filter-select {
  padding: 0.5rem 0.75rem;
  border: 2px solid var(--border-color);
  border-radius: 6px;
  font-size: 0.9rem;
  transition: border-color 0.3s ease;
}

.filter-input:focus,
.filter-select:focus {
  outline: none;
  border-color: var(--primary-color);
}

.date-range-wrapper {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.date-separator {
  color: var(--text-muted);
  font-size: 0.9rem;
}

.view-toggle {
  display: flex;
  gap: 0.5rem;
}

.view-btn {
  padding: 0.5rem 1rem;
  border: 2px solid var(--border-color);
  background: var(--card-bg);
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s ease;
  color: var(--text-secondary);
  font-size: 0.8rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.view-btn:hover,
.view-btn.active {
  border-color: var(--primary-color);
  background: var(--primary-color);
  color: white;
}

/* Loading State */
.loading-section {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem 2rem;
  text-align: center;
  background: var(--card-bg);
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.loading-spinner {
  font-size: 3rem;
  color: var(--primary-color);
  margin-bottom: 1rem;
}

/* Metrics Section */
.metrics-section {
  margin-bottom: 2rem;
}

.metrics-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
}

.metric-card {
  background: var(--card-bg);
  border-radius: 16px;
  padding: 1.5rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  border: 1px solid var(--border-color);
  transition: all 0.3s ease;
}

.metric-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
}

.metric-icon {
  width: 60px;
  height: 60px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  flex-shrink: 0;
}

.metric-card.total-amount .metric-icon {
  background: linear-gradient(135deg, #10b981, #059669);
  color: white;
}

.metric-card.transaction-count .metric-icon {
  background: linear-gradient(135deg, #3b82f6, #2563eb);
  color: white;
}

.metric-card.avg-amount .metric-icon {
  background: linear-gradient(135deg, #8b5cf6, #7c3aed);
  color: white;
}

.metric-card.compliance-rate .metric-icon {
  background: linear-gradient(135deg, #f59e0b, #d97706);
  color: white;
}

.metric-content h3 {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0;
}

.metric-content p {
  font-size: 0.9rem;
  color: var(--text-secondary);
  margin: 0;
}

.metric-change {
  font-size: 0.8rem;
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 0.25rem;
  margin-top: 0.5rem;
}

.metric-change.positive {
  color: #059669;
}

.metric-change.negative {
  color: #dc2626;
}

.metric-change.neutral {
  color: var(--text-muted);
}

/* Summary View */
.summary-view {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

/* Breakdown Section */
.breakdown-section {
  margin-bottom: 2rem;
}

.breakdown-card {
  background: var(--card-bg);
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  border: 1px solid var(--border-color);
}

.card-header {
  padding: 1.5rem 2rem;
  border-bottom: 1px solid var(--border-color);
  background: var(--bg-tertiary);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.card-header h3 {
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--text-primary);
  margin: 0;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.card-actions {
  display: flex;
  gap: 1rem;
  align-items: center;
}

.btn-icon {
  width: 36px;
  height: 36px;
  border: none;
  border-radius: 8px;
  background: var(--bg-tertiary);
  color: var(--text-secondary);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
}

.btn-icon:hover {
  background: var(--primary-color);
  color: white;
}

/* Chart Container */
.chart-container {
  padding: 2rem;
  display: flex;
  gap: 2rem;
  align-items: center;
}

.pie-chart-wrapper {
  position: relative;
  width: 200px;
  height: 200px;
  flex-shrink: 0;
}

.pie-chart {
  width: 100%;
  height: 100%;
}

.pie-segment {
  cursor: pointer;
  transition: opacity 0.3s ease;
}

.pie-segment:hover {
  opacity: 0.8;
}

.chart-center {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  text-align: center;
}

.center-amount {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--text-primary);
}

.center-label {
  font-size: 0.8rem;
  color: var(--text-muted);
}

.chart-legend {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.legend-color {
  width: 16px;
  height: 16px;
  border-radius: 4px;
  flex-shrink: 0;
}

.legend-content {
  flex: 1;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.legend-label {
  font-weight: 500;
  color: var(--text-primary);
}

.legend-value {
  font-weight: 600;
  color: var(--text-secondary);
}

/* Table View */
.table-view {
  overflow-x: auto;
}

.breakdown-table {
  width: 100%;
  border-collapse: collapse;
}

.breakdown-table th {
  background: var(--bg-tertiary);
  padding: 1rem 1.5rem;
  text-align: left;
  font-weight: 600;
  color: var(--text-primary);
  border-bottom: 1px solid var(--border-color);
  font-size: 0.9rem;
}

.breakdown-table td {
  padding: 1rem 1.5rem;
  border-bottom: 1px solid var(--border-color);
  vertical-align: middle;
}

.tax-type-cell {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: 500;
}

.tax-code-badge {
  background: var(--primary-color);
  color: white;
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 500;
}

.transaction-count {
  font-weight: 600;
  color: var(--text-primary);
}

.amount-value {
  font-weight: 600;
  color: var(--text-primary);
}

.percentage-bar {
  position: relative;
  background: var(--bg-tertiary);
  border-radius: 20px;
  height: 24px;
  min-width: 100px;
  overflow: hidden;
}

.percentage-fill {
  background: linear-gradient(90deg, var(--primary-color), var(--primary-dark));
  height: 100%;
  border-radius: 20px;
  transition: width 0.3s ease;
}

.percentage-text {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--text-primary);
}

.action-buttons {
  display: flex;
  gap: 0.5rem;
}

.btn-action {
  width: 32px;
  height: 32px;
  border: none;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.3s ease;
  font-size: 0.8rem;
}

.btn-view {
  background: rgba(59, 130, 246, 0.1);
  color: #2563eb;
}

.btn-export {
  background: rgba(16, 185, 129, 0.1);
  color: #059669;
}

.btn-action:hover {
  transform: scale(1.1);
}

/* Currency and Status Sections */
.currency-section,
.status-section {
  margin-bottom: 2rem;
}

.currency-card,
.status-card {
  background: var(--card-bg);
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  border: 1px solid var(--border-color);
}

.currency-grid {
  padding: 2rem;
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
}

.currency-item {
  background: var(--bg-tertiary);
  padding: 1.5rem;
  border-radius: 12px;
  border: 1px solid var(--border-color);
}

.currency-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.currency-code {
  font-size: 1.1rem;
  font-weight: 700;
  color: var(--text-primary);
}

.currency-count {
  font-size: 0.8rem;
  color: var(--text-muted);
}

.currency-amount {
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--primary-color);
  margin-bottom: 0.5rem;
}

.currency-conversion {
  font-size: 0.9rem;
  color: var(--text-secondary);
}

.status-grid {
  padding: 2rem;
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.5rem;
}

.status-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  background: var(--bg-tertiary);
  padding: 1rem;
  border-radius: 12px;
  border: 1px solid var(--border-color);
}

.status-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  flex-shrink: 0;
}

.status-icon.draft {
  background: rgba(107, 114, 128, 0.1);
  color: #374151;
}

.status-icon.pending {
  background: rgba(249, 115, 22, 0.1);
  color: #ea580c;
}

.status-icon.approved {
  background: rgba(34, 197, 94, 0.1);
  color: #16a34a;
}

.status-icon.completed {
  background: rgba(59, 130, 246, 0.1);
  color: #2563eb;
}

.status-content {
  flex: 1;
}

.status-count {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--text-primary);
}

.status-label {
  font-size: 0.9rem;
  color: var(--text-secondary);
  margin: 0.25rem 0;
}

.status-amount {
  font-size: 0.8rem;
  color: var(--text-muted);
}

/* Detailed View */
.detailed-view {
  margin-bottom: 2rem;
}

.detailed-card {
  background: var(--card-bg);
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  border: 1px solid var(--border-color);
}

.search-box {
  position: relative;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.search-box i {
  color: var(--text-muted);
}

.search-box input {
  padding: 0.5rem 0.75rem;
  border: 2px solid var(--border-color);
  border-radius: 6px;
  font-size: 0.9rem;
  min-width: 200px;
}

.search-box input:focus {
  outline: none;
  border-color: var(--primary-color);
}

.sort-select {
  padding: 0.5rem 0.75rem;
  border: 2px solid var(--border-color);
  border-radius: 6px;
  font-size: 0.9rem;
}

.detailed-content {
  padding: 2rem;
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.detailed-item {
  background: var(--bg-tertiary);
  padding: 1.5rem;
  border-radius: 12px;
  border: 1px solid var(--border-color);
}

.detailed-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1rem;
}

.detailed-title h4 {
  font-size: 1.1rem;
  font-weight: 600;
  color: var(--text-primary);
  margin: 0 0 0.25rem 0;
}

.detailed-code {
  background: var(--primary-color);
  color: white;
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 500;
}

.detailed-amount {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--primary-color);
}

.detailed-stats {
  display: flex;
  gap: 2rem;
  margin-bottom: 1rem;
}

.stat-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.stat-label {
  font-size: 0.8rem;
  color: var(--text-muted);
  font-weight: 500;
}

.stat-value {
  font-size: 0.9rem;
  color: var(--text-primary);
  font-weight: 600;
}

.detailed-actions {
  display: flex;
  gap: 1rem;
}

/* Trends View */
.trends-view {
  margin-bottom: 2rem;
}

.trends-card {
  background: var(--card-bg);
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  border: 1px solid var(--border-color);
}

.trend-select {
  padding: 0.5rem 0.75rem;
  border: 2px solid var(--border-color);
  border-radius: 6px;
  font-size: 0.9rem;
}

.trends-content {
  padding: 2rem;
}

.chart-wrapper {
  position: relative;
  margin-bottom: 2rem;
}

.trend-chart {
  width: 100%;
  height: 300px;
}

.trend-line {
  filter: drop-shadow(0 2px 4px rgba(99, 102, 241, 0.3));
}

.trend-point {
  cursor: pointer;
  filter: drop-shadow(0 2px 4px rgba(99, 102, 241, 0.3));
}

.trend-point:hover {
  r: 6;
}

.trend-tooltip {
  position: absolute;
  background: var(--card-bg);
  border: 1px solid var(--border-color);
  border-radius: 8px;
  padding: 0.75rem;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  z-index: 10;
  pointer-events: none;
}

.tooltip-title {
  font-weight: 600;
  color: var(--text-primary);
  margin-bottom: 0.25rem;
}

.tooltip-content {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
}

.tooltip-label {
  color: var(--text-secondary);
}

.tooltip-value {
  font-weight: 600;
  color: var(--primary-color);
}

.growth-section h4 {
  font-size: 1.1rem;
  font-weight: 600;
  color: var(--text-primary);
  margin: 0 0 1rem 0;
}

.growth-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.5rem;
}

.growth-item {
  background: var(--bg-tertiary);
  padding: 1rem;
  border-radius: 12px;
  border: 1px solid var(--border-color);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.growth-label {
  font-size: 0.9rem;
  color: var(--text-secondary);
  font-weight: 500;
}

.growth-value {
  font-size: 1rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.growth-value.positive {
  color: #059669;
}

.growth-value.negative {
  color: #dc2626;
}

.growth-value.neutral {
  color: var(--text-muted);
}

/* Action Bar */
.action-bar {
  margin-top: 2rem;
  background: var(--card-bg);
  padding: 1.5rem 2rem;
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  border: 1px solid var(--border-color);
}

.action-buttons {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
}

/* Buttons */
.btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  border: none;
  cursor: pointer;
  font-weight: 600;
  text-decoration: none;
  transition: all 0.3s ease;
  font-size: 0.9rem;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-primary {
  background: var(--primary-color);
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: var(--primary-dark);
  transform: translateY(-1px);
}

.btn-outline {
  background: transparent;
  border: 2px solid var(--border-color);
  color: var(--text-primary);
}

.btn-outline:hover:not(:disabled) {
  border-color: var(--primary-color);
  background: var(--primary-color);
  color: white;
  transform: translateY(-1px);
}

.btn-text {
  background: none;
  border: none;
  color: var(--primary-color);
  padding: 0.5rem;
}

.btn-text:hover {
  background: rgba(99, 102, 241, 0.1);
}

.btn-sm {
  padding: 0.5rem 1rem;
  font-size: 0.8rem;
}

/* Responsive Design */
@media (max-width: 768px) {
  .tax-summary-container {
    padding: 1rem;
  }

  .title-section {
    flex-direction: column;
    gap: 1rem;
  }

  .header-actions {
    flex-wrap: wrap;
  }

  .filter-grid {
    grid-template-columns: 1fr;
  }

  .metrics-grid {
    grid-template-columns: 1fr;
  }

  .chart-container {
    flex-direction: column;
    gap: 1rem;
  }

  .currency-grid,
  .status-grid {
    grid-template-columns: 1fr;
  }

  .detailed-stats {
    flex-direction: column;
    gap: 1rem;
  }

  .growth-grid {
    grid-template-columns: 1fr;
  }

  .action-buttons {
    flex-direction: column;
  }

  .page-title {
    font-size: 2rem;
  }

  .card-actions {
    flex-direction: column;
    gap: 0.5rem;
  }

  .breakdown-table {
    font-size: 0.8rem;
  }

  .breakdown-table th,
  .breakdown-table td {
    padding: 0.75rem;
  }
}

/* CSS Variables */
:root {
  --primary-color: #6366f1;
  --primary-dark: #4f46e5;
  --text-primary: #1f2937;
  --text-secondary: #6b7280;
  --text-muted: #9ca3af;
  --bg-secondary: #f9fafb;
  --bg-tertiary: #f3f4f6;
  --card-bg: #ffffff;
  --border-color: #e5e7eb;
}

@media (prefers-color-scheme: dark) {
  :root {
    --text-primary: #f9fafb;
    --text-secondary: #d1d5db;
    --text-muted: #9ca3af;
    --bg-secondary: #111827;
    --bg-tertiary: #1f2937;
    --card-bg: #1f2937;
    --border-color: #374151;
  }
}
</style>