<!-- frontend/erp/src/views/accounting/depreciacions/DepreciationSchedule.vue -->
<template>
  <AppLayout>
    <!-- Page Header -->
    <div class="page-header">
      <div class="header-content">
        <div class="title-section">
          <div class="breadcrumb">
            <router-link to="/accounting" class="breadcrumb-item">
              <i class="fas fa-calculator"></i>
              Accounting
            </router-link>
            <i class="fas fa-chevron-right"></i>
            <router-link to="/accounting/depreciations" class="breadcrumb-item">
              Asset Depreciations
            </router-link>
            <i class="fas fa-chevron-right"></i>
            <span class="breadcrumb-current">Depreciation Schedule</span>
          </div>
          <h1 class="page-title">
            <i class="fas fa-calendar-alt"></i>
            Depreciation Schedule
          </h1>
          <p class="page-subtitle">
            {{ selectedAsset ? `Schedule for ${selectedAsset.name} (${selectedAsset.asset_code})` : 
               'View and manage asset depreciation schedules across all periods' }}
          </p>
        </div>
        <div class="header-actions">
          <button @click="calculateNextPeriod" class="btn btn-primary" :disabled="!canCalculateNext || loading">
            <i class="fas fa-calculator"></i>
            Calculate Next
          </button>
          <button @click="showExportModal = true" class="btn btn-outline" :disabled="!scheduleData.length">
            <i class="fas fa-download"></i>
            Export
          </button>
          <button @click="refreshData" class="btn btn-secondary" :disabled="loading">
            <i class="fas fa-sync-alt" :class="{ 'fa-spin': loading }"></i>
            Refresh
          </button>
        </div>
      </div>
    </div>

    <!-- Asset Selection and Filters -->
    <div class="controls-section">
      <div class="controls-card">
        <div class="filters-row">
          <div class="filter-group">
            <label>Select Asset</label>
            <select v-model="selectedAssetId" @change="loadScheduleData">
              <option value="">All Assets</option>
              <option
                v-for="asset in assets"
                :key="asset.asset_id"
                :value="asset.asset_id"
              >
                {{ asset.name }} ({{ asset.asset_code }}) - {{ asset.currency }}
              </option>
            </select>
          </div>
          
          <div class="filter-group">
            <label>Year Filter</label>
            <select v-model="selectedYear" @change="loadScheduleData">
              <option value="">All Years</option>
              <option
                v-for="year in availableYears"
                :key="year"
                :value="year"
              >
                {{ year }}
              </option>
            </select>
          </div>
          
          <div class="filter-group">
            <label>Status Filter</label>
            <select v-model="statusFilter" @change="loadScheduleData">
              <option value="">All Records</option>
              <option value="recorded">Recorded Only</option>
              <option value="projected">Projected Only</option>
            </select>
          </div>

          <div class="filter-group">
            <label>Display Currency</label>
            <select v-model="displayCurrency" @change="loadScheduleData">
              <option
                v-for="currency in systemCurrencies"
                :key="currency.code"
                :value="currency.code"
              >
                {{ currency.code }} - {{ currency.name }}
              </option>
            </select>
          </div>
        </div>

        <div class="controls-row">
          <div class="view-controls">
            <div class="group-options">
              <label>Group By:</label>
              <select v-model="groupBy" @change="organizeScheduleData">
                <option value="year">Year</option>
                <option value="asset">Asset</option>
                <option value="quarter">Quarter</option>
              </select>
            </div>
            
            <div class="projection-controls">
              <label>Projection Years:</label>
              <select v-model="projectionYears" @change="generateProjections">
                <option value="1">1 Year</option>
                <option value="2">2 Years</option>
                <option value="3">3 Years</option>
                <option value="5">5 Years</option>
              </select>
            </div>
          </div>
          
          <div class="view-options">
            <button
              @click="viewType = 'summary'"
              :class="['view-btn', { active: viewType === 'summary' }]"
              title="Summary View"
            >
              <i class="fas fa-th-large"></i>
            </button>
            <button
              @click="viewType = 'table'"
              :class="['view-btn', { active: viewType === 'table' }]"
              title="Table View"
            >
              <i class="fas fa-table"></i>
            </button>
            <button
              @click="viewType = 'chart'"
              :class="['view-btn', { active: viewType === 'chart' }]"
              title="Chart View"
            >
              <i class="fas fa-chart-line"></i>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Asset Overview (when single asset selected) -->
    <div v-if="selectedAsset" class="asset-overview-section">
      <div class="overview-card">
        <div class="asset-summary">
          <div class="asset-info">
            <div class="asset-header">
              <div class="asset-icon">
                <i :class="getAssetIcon(selectedAsset.category)"></i>
              </div>
              <div class="asset-details">
                <h3>{{ selectedAsset.name }}</h3>
                <span class="asset-code">{{ selectedAsset.asset_code }}</span>
                <span class="asset-category">{{ selectedAsset.category }}</span>
              </div>
            </div>
            
            <div class="asset-metrics">
              <div class="metric-item">
                <span class="metric-label">Acquisition Cost</span>
                <span class="metric-value">
                  {{ selectedAsset.currency }} {{ formatCurrency(selectedAsset.acquisition_cost) }}
                  <span v-if="selectedAsset.currency !== baseCurrency" class="base-currency">
                    ({{ baseCurrency }} {{ formatCurrency(selectedAsset.base_currency_acquisition_cost) }})
                  </span>
                </span>
              </div>
              <div class="metric-item">
                <span class="metric-label">Current Value</span>
                <span class="metric-value remaining">
                  {{ selectedAsset.currency }} {{ formatCurrency(selectedAsset.current_value) }}
                  <span v-if="selectedAsset.currency !== baseCurrency" class="base-currency">
                    ({{ baseCurrency }} {{ formatCurrency(selectedAsset.base_currency_current_value) }})
                  </span>
                </span>
              </div>
              <div class="metric-item">
                <span class="metric-label">Total Depreciated</span>
                <span class="metric-value depreciated">
                  {{ selectedAsset.currency }} {{ formatCurrency(totalDepreciated) }}
                </span>
              </div>
            </div>
          </div>
          
          <div class="depreciation-progress">
            <div class="progress-header">
              <span class="progress-label">Depreciation Progress</span>
              <span class="progress-percentage">{{ depreciationProgress }}%</span>
            </div>
            <div class="progress-bar">
              <div class="progress-fill" :style="{ width: depreciationProgress + '%' }"></div>
            </div>
            <div class="progress-footer">
              <span class="progress-info">{{ estimatedYearsRemaining }} years remaining</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Schedule Content -->
    <div class="schedule-content">
      <div class="content-card">
        <div class="content-header">
          <h2>
            <i class="fas fa-calendar"></i>
            Depreciation Schedule
          </h2>
          <div class="content-stats">
            <div class="stat-item">
              <span class="stat-label">Total Periods:</span>
              <span class="stat-value">{{ totalPeriods }}</span>
            </div>
            <div class="stat-item">
              <span class="stat-label">Total Depreciation:</span>
              <span class="stat-value">{{ displayCurrency }} {{ formatCurrency(totalScheduledDepreciation) }}</span>
            </div>
            <div class="stat-item">
              <span class="stat-label">Recorded:</span>
              <span class="stat-value recorded">{{ recordedCount }}</span>
            </div>
            <div class="stat-item">
              <span class="stat-label">Projected:</span>
              <span class="stat-value projected">{{ projectedCount }}</span>
            </div>
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="loading-state">
          <div class="loading-spinner"></div>
          <p>Loading depreciation schedule...</p>
        </div>

        <!-- Empty State -->
        <div v-else-if="!scheduleData.length" class="empty-state">
          <div class="empty-icon">
            <i class="fas fa-calendar-times"></i>
          </div>
          <h3>No Schedule Data Found</h3>
          <p>{{ selectedAssetId ? 
              'No depreciation records found for this asset' : 
              'Select an asset to view its depreciation schedule' }}
          </p>
        </div>

        <!-- Summary View -->
        <div v-else-if="viewType === 'summary'" class="summary-view">
          <div class="summary-grid">
            <div
              v-for="item in organizedScheduleData"
              :key="item.depreciation_id || item.period_id || item.group_key"
              :class="['summary-card', { 
                projected: !item.depreciation_id,
                group: item.is_group,
                current: item.is_current
              }]"
              @click="item.depreciation_id ? viewDepreciationDetail(item.depreciation_id) : null"
            >
              <div v-if="item.is_group" class="group-header">
                <h3>{{ item.group_title }}</h3>
                <div class="group-summary">
                  <span>{{ item.items?.length || 0 }} periods</span>
                  <span>{{ displayCurrency }} {{ formatCurrency(item.total_amount) }}</span>
                </div>
              </div>
              
              <div v-else class="card-content">
                <div class="card-header">
                  <div class="period-info">
                    <h4>{{ item.period_name || `Period ${item.period_number}` }}</h4>
                    <span class="period-date">{{ formatDate(item.depreciation_date || item.period_start) }}</span>
                  </div>
                  <div class="card-status">
                    <span :class="['status-indicator', item.depreciation_id ? 'recorded' : 'projected']">
                      {{ item.depreciation_id ? 'Recorded' : 'Projected' }}
                    </span>
                  </div>
                </div>

                <div v-if="!selectedAssetId" class="asset-info">
                  <span class="asset-name">{{ item.asset_name }}</span>
                  <span class="asset-code">{{ item.asset_code }}</span>
                  <span class="asset-currency">{{ item.currency }}</span>
                </div>

                <div class="amounts-grid">
                  <div class="amount-item">
                    <span class="amount-label">Opening Value</span>
                    <span class="amount-value">
                      {{ getDisplayAmount(item, 'opening_value') }}
                    </span>
                  </div>
                  <div class="amount-item">
                    <span class="amount-label">Depreciation</span>
                    <span class="amount-value primary">
                      {{ getDisplayAmount(item, 'depreciation_amount') }}
                    </span>
                  </div>
                  <div class="amount-item">
                    <span class="amount-label">Accumulated</span>
                    <span class="amount-value">
                      {{ getDisplayAmount(item, 'accumulated_depreciation') }}
                    </span>
                  </div>
                  <div class="amount-item">
                    <span class="amount-label">Closing Value</span>
                    <span class="amount-value remaining">
                      {{ getDisplayAmount(item, 'remaining_value') }}
                    </span>
                  </div>
                </div>

                <div v-if="item.exchange_rate && item.currency !== displayCurrency" class="exchange-rate">
                  <span class="rate-label">Exchange Rate:</span>
                  <span class="rate-value">
                    1 {{ item.currency }} = {{ formatNumber(item.exchange_rate, 4) }} {{ displayCurrency }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Table View -->
        <div v-else-if="viewType === 'table'" class="table-view">
          <div class="table-container">
            <table class="schedule-table">
              <thead>
                <tr>
                  <th v-if="!selectedAssetId">Asset</th>
                  <th>Period</th>
                  <th>Date</th>
                  <th>Currency</th>
                  <th>Opening Value</th>
                  <th>Depreciation</th>
                  <th>Accumulated</th>
                  <th>Closing Value</th>
                  <th v-if="displayCurrency !== baseCurrency">{{ displayCurrency }} Rate</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <template v-for="group in groupedScheduleData" :key="group.group_key">
                  <tr v-if="groupBy !== 'asset'" class="group-row">
                    <td :colspan="selectedAssetId ? 8 : 9" class="group-title">
                      <i class="fas fa-calendar"></i>
                      {{ group.title }}
                      <span class="group-summary">
                        ({{ group.items.length }} periods, 
                        {{ displayCurrency }} {{ formatCurrency(group.total) }} total depreciation)
                      </span>
                    </td>
                  </tr>
                  <tr
                    v-for="item in group.items"
                    :key="item.depreciation_id || `${item.asset_id}-${item.period_id}`"
                    :class="['schedule-row', { 
                      projected: !item.depreciation_id,
                      current: item.is_current
                    }]"
                    @click="item.depreciation_id ? viewDepreciationDetail(item.depreciation_id) : null"
                  >
                    <td v-if="!selectedAssetId" class="asset-cell">
                      <div class="asset-info">
                        <strong>{{ item.asset_name }}</strong>
                        <small>{{ item.asset_code }} - {{ item.currency }}</small>
                      </div>
                    </td>
                    <td>{{ item.period_name || `Period ${item.period_number}` }}</td>
                    <td>{{ formatDate(item.depreciation_date || item.period_start) }}</td>
                    <td>
                      <span class="currency-badge">{{ item.currency }}</span>
                    </td>
                    <td class="amount-cell">{{ getDisplayAmount(item, 'opening_value') }}</td>
                    <td class="amount-cell primary">{{ getDisplayAmount(item, 'depreciation_amount') }}</td>
                    <td class="amount-cell">{{ getDisplayAmount(item, 'accumulated_depreciation') }}</td>
                    <td class="amount-cell remaining">{{ getDisplayAmount(item, 'remaining_value') }}</td>
                    <td v-if="displayCurrency !== baseCurrency" class="rate-cell">
                      <span v-if="item.exchange_rate">
                        {{ formatNumber(item.exchange_rate, 4) }}
                      </span>
                      <span v-else>-</span>
                    </td>
                    <td>
                      <span :class="['status-badge', item.depreciation_id ? 'recorded' : 'projected']">
                        {{ item.depreciation_id ? 'Recorded' : 'Projected' }}
                      </span>
                    </td>
                    <td class="actions-cell" @click.stop>
                      <div class="action-buttons">
                        <button
                          v-if="item.depreciation_id"
                          @click="viewDepreciationDetail(item.depreciation_id)"
                          class="btn-mini view"
                          title="View Details"
                        >
                          <i class="fas fa-eye"></i>
                        </button>
                        <button
                          v-else
                          @click="calculateDepreciation(item)"
                          class="btn-mini calculate"
                          title="Calculate"
                        >
                          <i class="fas fa-calculator"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                </template>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Chart View -->
        <div v-else-if="viewType === 'chart'" class="chart-view">
          <div class="chart-container">
            <div class="chart-header">
              <h3>Depreciation Trend</h3>
              <div class="chart-controls">
                <div class="chart-options">
                  <label>
                    <input type="checkbox" v-model="chartOptions.showBookValue" @change="updateChart">
                    Book Value
                  </label>
                  <label>
                    <input type="checkbox" v-model="chartOptions.showAccumulated" @change="updateChart">
                    Accumulated Depreciation
                  </label>
                  <label>
                    <input type="checkbox" v-model="chartOptions.showPeriodDepreciation" @change="updateChart">
                    Period Depreciation
                  </label>
                </div>
              </div>
            </div>
            <div class="chart-area">
              <canvas ref="scheduleChart"></canvas>
            </div>
            <div class="chart-legend">
              <div v-if="chartOptions.showBookValue" class="legend-item">
                <span class="legend-color book-value"></span>
                <span>Book Value</span>
              </div>
              <div v-if="chartOptions.showAccumulated" class="legend-item">
                <span class="legend-color accumulated"></span>
                <span>Accumulated Depreciation</span>
              </div>
              <div v-if="chartOptions.showPeriodDepreciation" class="legend-item">
                <span class="legend-color period"></span>
                <span>Period Depreciation</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Export Modal -->
    <div v-if="showExportModal" class="modal-overlay" @click="closeExportModal">
      <div class="modal-container" @click.stop>
        <div class="modal-header">
          <h3>
            <i class="fas fa-download"></i>
            Export Depreciation Schedule
          </h3>
          <button @click="closeExportModal" class="modal-close">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <div class="export-form">
            <div class="form-group">
              <label>Export Format</label>
              <div class="radio-group">
                <label class="radio-item">
                  <input type="radio" v-model="exportFormat" value="excel">
                  <span>Excel (.xlsx)</span>
                </label>
                <label class="radio-item">
                  <input type="radio" v-model="exportFormat" value="pdf">
                  <span>PDF Report</span>
                </label>
                <label class="radio-item">
                  <input type="radio" v-model="exportFormat" value="csv">
                  <span>CSV Data</span>
                </label>
              </div>
            </div>

            <div class="form-group">
              <label>Export Options</label>
              <div class="checkbox-group">
                <label class="checkbox-item">
                  <input type="checkbox" v-model="exportOptions.includeProjections">
                  <span>Include Projections</span>
                </label>
                <label class="checkbox-item">
                  <input type="checkbox" v-model="exportOptions.includeCharts">
                  <span>Include Charts (PDF only)</span>
                </label>
                <label class="checkbox-item">
                  <input type="checkbox" v-model="exportOptions.includeAssetDetails">
                  <span>Include Asset Details</span>
                </label>
                <label class="checkbox-item">
                  <input type="checkbox" v-model="exportOptions.includeCurrencyConversion">
                  <span>Include Currency Conversion</span>
                </label>
              </div>
            </div>

            <div class="form-group">
              <label>Date Range</label>
              <div class="date-range">
                <input
                  type="date"
                  v-model="exportOptions.fromDate"
                  placeholder="From Date"
                >
                <span>to</span>
                <input
                  type="date"
                  v-model="exportOptions.toDate"
                  placeholder="To Date"
                >
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button @click="closeExportModal" class="btn btn-secondary">Cancel</button>
          <button
            @click="performExport"
            class="btn btn-primary"
            :disabled="exporting"
          >
            <i v-if="exporting" class="fas fa-spinner fa-spin"></i>
            <i v-else class="fas fa-download"></i>
            {{ exporting ? 'Exporting...' : 'Export' }}
          </button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
/* eslint-disable */
export default {
  name: 'DepreciationSchedule',
  components: {
  },
  setup() {
    const route = useRoute()
    const router = useRouter()
    
    // Reactive state
    const loading = ref(false)
    const exporting = ref(false)
    const assets = ref([])
    const scheduleData = ref([])
    const projectionData = ref([])
    const systemCurrencies = ref([])
    const selectedAssetId = ref(route.params.assetId || '')
    const selectedAsset = ref(null)
    const selectedYear = ref('')
    const statusFilter = ref('')
    const displayCurrency = ref('USD')
    const baseCurrency = ref('USD')
    const viewType = ref('summary')
    const groupBy = ref('year')
    const projectionYears = ref(3)
    
    // Modal states
    const showExportModal = ref(false)
    const exportFormat = ref('excel')
    const exportOptions = ref({
      includeProjections: true,
      includeCharts: true,
      includeAssetDetails: true,
      includeCurrencyConversion: true,
      fromDate: '',
      toDate: ''
    })

    // Chart state
    const chartOptions = ref({
      showBookValue: true,
      showAccumulated: true,
      showPeriodDepreciation: false
    })

    // Computed properties
    const availableYears = computed(() => {
      const years = new Set()
      scheduleData.value.forEach(item => {
        if (item.depreciation_date) {
          years.add(new Date(item.depreciation_date).getFullYear())
        }
      })
      return Array.from(years).sort((a, b) => b - a)
    })

    const totalDepreciated = computed(() => {
      if (!selectedAsset.value) return 0
      return selectedAsset.value.acquisition_cost - selectedAsset.value.current_value
    })

    const depreciationProgress = computed(() => {
      if (!selectedAsset.value || !selectedAsset.value.acquisition_cost) return 0
      return Math.round((totalDepreciated.value / selectedAsset.value.acquisition_cost) * 100)
    })

    const estimatedYearsRemaining = computed(() => {
      if (!selectedAsset.value || selectedAsset.value.depreciation_rate === 0) return 0
      const remainingPercentage = 100 - depreciationProgress.value
      return Math.ceil(remainingPercentage / selectedAsset.value.depreciation_rate)
    })

    const totalScheduledDepreciation = computed(() => {
      return scheduleData.value.reduce((sum, item) => {
        return sum + getAmountInDisplayCurrency(item, 'depreciation_amount')
      }, 0)
    })

    const totalPeriods = computed(() => scheduleData.value.length)

    const recordedCount = computed(() => {
      return scheduleData.value.filter(item => item.depreciation_id).length
    })

    const projectedCount = computed(() => {
      return scheduleData.value.filter(item => !item.depreciation_id).length
    })

    const canCalculateNext = computed(() => {
      return selectedAsset.value && selectedAsset.value.status === 'Active'
    })

    const organizedScheduleData = computed(() => {
      if (groupBy.value === 'year') {
        return groupByYear()
      } else if (groupBy.value === 'asset') {
        return groupByAsset()
      } else if (groupBy.value === 'quarter') {
        return groupByQuarter()
      }
      return scheduleData.value
    })

    const groupedScheduleData = computed(() => {
      if (groupBy.value === 'year') {
        return groupScheduleByYear()
      } else if (groupBy.value === 'asset') {
        return groupScheduleByAsset()
      } else if (groupBy.value === 'quarter') {
        return groupScheduleByQuarter()
      }
      return [{ group_key: 'all', title: 'All Records', items: scheduleData.value, total: totalScheduledDepreciation.value }]
    })

    // Methods
    const loadAssets = async () => {
      try {
        const response = await axios.get('/accounting/fixed-assets', {
          params: { status: 'Active' }
        })
        assets.value = response.data.data
        
        if (selectedAssetId.value) {
          selectedAsset.value = assets.value.find(asset => asset.asset_id === selectedAssetId.value)
        }
      } catch (error) {
        console.error('Error loading assets:', error)
      }
    }

    const loadSystemCurrencies = async () => {
      try {
        const response = await axios.get('/system/currencies')
        systemCurrencies.value = response.data.data
        
        const defaultCurrency = systemCurrencies.value.find(c => c.is_base) || systemCurrencies.value[0]
        if (defaultCurrency) {
          baseCurrency.value = defaultCurrency.code
          displayCurrency.value = defaultCurrency.code
        }
      } catch (error) {
        console.error('Error loading currencies:', error)
      }
    }

    const loadScheduleData = async () => {
      try {
        loading.value = true
        
        const params = {}
        if (selectedAssetId.value) params.asset_id = selectedAssetId.value
        if (selectedYear.value) params.year = selectedYear.value
        if (statusFilter.value) params.status = statusFilter.value
        if (displayCurrency.value !== baseCurrency.value) {
          params.display_currency = displayCurrency.value
          params.conversion_date = new Date().toISOString().split('T')[0]
        }

        const response = await axios.get('/accounting/asset-depreciations/schedule', { params })
        scheduleData.value = response.data.data
        
        // Generate projections if we have an asset selected
        if (selectedAsset.value) {
          generateProjections()
        }
        
      } catch (error) {
        console.error('Error loading schedule data:', error)
      } finally {
        loading.value = false
      }
    }

    const generateProjections = () => {
      if (!selectedAsset.value) return

      const asset = selectedAsset.value
      const lastRecord = scheduleData.value
        .filter(item => item.depreciation_id)
        .sort((a, b) => new Date(b.depreciation_date) - new Date(a.depreciation_date))[0]
      
      const currentValue = lastRecord ? lastRecord.remaining_value : asset.current_value
      const monthlyDepreciation = (asset.depreciation_rate / 100) * asset.acquisition_cost / 12
      
      projectionData.value = []
      let remainingValue = currentValue
      const lastDate = lastRecord ? new Date(lastRecord.depreciation_date) : new Date()
      
      for (let i = 1; i <= projectionYears.value * 12; i++) {
        if (remainingValue <= 0) break
        
        const depreciationAmount = Math.min(monthlyDepreciation, remainingValue)
        remainingValue -= depreciationAmount
        
        const projectionDate = new Date(lastDate)
        projectionDate.setMonth(projectionDate.getMonth() + i)
        
        const accumulated = (lastRecord?.accumulated_depreciation || 0) + (depreciationAmount * i)
        
        projectionData.value.push({
          period_number: (lastRecord?.period_number || 0) + i,
          period_start: projectionDate.toISOString().split('T')[0],
          depreciation_amount: depreciationAmount,
          accumulated_depreciation: accumulated,
          remaining_value: Math.max(0, remainingValue),
          opening_value: remainingValue + depreciationAmount,
          currency: asset.currency,
          asset_id: asset.asset_id,
          asset_name: asset.name,
          asset_code: asset.asset_code,
          is_projection: true
        })
      }
      
      // Combine actual and projected data
      scheduleData.value = [...scheduleData.value, ...projectionData.value]
    }

    const groupByYear = () => {
      const groups = {}
      scheduleData.value.forEach(item => {
        const year = item.depreciation_date ? 
          new Date(item.depreciation_date).getFullYear() : 
          new Date(item.period_start).getFullYear()
        
        if (!groups[year]) {
          groups[year] = {
            group_key: year,
            group_title: `Year ${year}`,
            items: [],
            total_amount: 0,
            is_group: true
          }
        }
        
        groups[year].items.push(item)
        groups[year].total_amount += getAmountInDisplayCurrency(item, 'depreciation_amount')
      })
      
      return Object.values(groups).sort((a, b) => b.group_key - a.group_key)
    }

    const groupByAsset = () => {
      const groups = {}
      scheduleData.value.forEach(item => {
        const assetId = item.asset_id
        
        if (!groups[assetId]) {
          groups[assetId] = {
            group_key: assetId,
            group_title: `${item.asset_name} (${item.asset_code})`,
            items: [],
            total_amount: 0,
            is_group: true
          }
        }
        
        groups[assetId].items.push(item)
        groups[assetId].total_amount += getAmountInDisplayCurrency(item, 'depreciation_amount')
      })
      
      return Object.values(groups)
    }

    const groupByQuarter = () => {
      const groups = {}
      scheduleData.value.forEach(item => {
        const date = new Date(item.depreciation_date || item.period_start)
        const year = date.getFullYear()
        const quarter = Math.ceil((date.getMonth() + 1) / 3)
        const key = `${year}-Q${quarter}`
        
        if (!groups[key]) {
          groups[key] = {
            group_key: key,
            group_title: `${year} Q${quarter}`,
            items: [],
            total_amount: 0,
            is_group: true
          }
        }
        
        groups[key].items.push(item)
        groups[key].total_amount += getAmountInDisplayCurrency(item, 'depreciation_amount')
      })
      
      return Object.values(groups).sort((a, b) => b.group_key.localeCompare(a.group_key))
    }

    const groupScheduleByYear = () => {
      const groups = {}
      scheduleData.value.forEach(item => {
        const year = item.depreciation_date ? 
          new Date(item.depreciation_date).getFullYear() : 
          new Date(item.period_start).getFullYear()
        
        if (!groups[year]) {
          groups[year] = {
            group_key: year,
            title: `Year ${year}`,
            items: [],
            total: 0
          }
        }
        
        groups[year].items.push(item)
        groups[year].total += getAmountInDisplayCurrency(item, 'depreciation_amount')
      })
      
      return Object.values(groups).sort((a, b) => b.group_key - a.group_key)
    }

    const groupScheduleByAsset = () => {
      const groups = {}
      scheduleData.value.forEach(item => {
        const assetId = item.asset_id
        
        if (!groups[assetId]) {
          groups[assetId] = {
            group_key: assetId,
            title: `${item.asset_name} (${item.asset_code})`,
            items: [],
            total: 0
          }
        }
        
        groups[assetId].items.push(item)
        groups[assetId].total += getAmountInDisplayCurrency(item, 'depreciation_amount')
      })
      
      return Object.values(groups)
    }

    const groupScheduleByQuarter = () => {
      const groups = {}
      scheduleData.value.forEach(item => {
        const date = new Date(item.depreciation_date || item.period_start)
        const year = date.getFullYear()
        const quarter = Math.ceil((date.getMonth() + 1) / 3)
        const key = `${year}-Q${quarter}`
        
        if (!groups[key]) {
          groups[key] = {
            group_key: key,
            title: `${year} Q${quarter}`,
            items: [],
            total: 0
          }
        }
        
        groups[key].items.push(item)
        groups[key].total += getAmountInDisplayCurrency(item, 'depreciation_amount')
      })
      
      return Object.values(groups).sort((a, b) => b.group_key.localeCompare(a.group_key))
    }

    const getAmountInDisplayCurrency = (item, field) => {
      if (displayCurrency.value === item.currency) {
        return item[field] || 0
      }
      
      // Check for converted amounts
      const convertedField = `converted_${field.replace('depreciation_', '').replace('accumulated_', '').replace('remaining_', '')}`
      if (item[convertedField]) {
        return item[convertedField]
      }
      
      // Fallback to original amount
      return item[field] || 0
    }

    const getDisplayAmount = (item, field) => {
      const amount = getAmountInDisplayCurrency(item, field)
      return `${displayCurrency.value} ${formatCurrency(amount)}`
    }

    const organizeScheduleData = () => {
      // This method will be called when groupBy changes
      // The computed property organizedScheduleData will handle the actual organization
    }

    const refreshData = () => {
      loadScheduleData()
    }

    const calculateNextPeriod = async () => {
      if (!selectedAsset.value) return
      
      try {
        const response = await axios.post('/accounting/asset-depreciations/calculate-next', {
          asset_id: selectedAsset.value.asset_id
        })
        
        // Refresh the schedule data
        await loadScheduleData()
        
        // Show success message
        console.log('Next period calculated successfully')
      } catch (error) {
        console.error('Error calculating next period:', error)
      }
    }

    const calculateDepreciation = async (item) => {
      try {
        const response = await axios.post('/accounting/asset-depreciations', {
          asset_id: item.asset_id,
          depreciation_date: item.period_start,
          depreciation_amount: item.depreciation_amount,
          create_journal_entry: false
        })
        
        // Refresh the data
        await loadScheduleData()
        
        console.log('Depreciation calculated successfully')
      } catch (error) {
        console.error('Error calculating depreciation:', error)
      }
    }

    const viewDepreciationDetail = (depreciationId) => {
      router.push(`/accounting/depreciations/${depreciationId}`)
    }

    const updateChart = () => {
      // Chart update logic would go here
      console.log('Updating chart with options:', chartOptions.value)
    }

    const performExport = async () => {
      try {
        exporting.value = true
        
        const params = {
          format: exportFormat.value,
          asset_id: selectedAssetId.value,
          year: selectedYear.value,
          status: statusFilter.value,
          display_currency: displayCurrency.value,
          from_date: exportOptions.value.fromDate,
          to_date: exportOptions.value.toDate,
          include_projections: exportOptions.value.includeProjections,
          include_charts: exportOptions.value.includeCharts,
          include_asset_details: exportOptions.value.includeAssetDetails,
          include_currency_conversion: exportOptions.value.includeCurrencyConversion
        }
        
        const response = await axios.get('/accounting/asset-depreciations/schedule/export', {
          params,
          responseType: 'blob'
        })
        
        const blob = new Blob([response.data])
        const url = window.URL.createObjectURL(blob)
        const link = document.createElement('a')
        link.href = url
        
        const extension = exportFormat.value === 'excel' ? 'xlsx' : exportFormat.value
        const filename = `depreciation-schedule-${selectedAsset.value?.asset_code || 'all'}-${new Date().toISOString().split('T')[0]}.${extension}`
        link.download = filename
        
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
        window.URL.revokeObjectURL(url)
        
        closeExportModal()
      } catch (error) {
        console.error('Error exporting schedule:', error)
      } finally {
        exporting.value = false
      }
    }

    const closeExportModal = () => {
      showExportModal.value = false
      exportFormat.value = 'excel'
      exportOptions.value = {
        includeProjections: true,
        includeCharts: true,
        includeAssetDetails: true,
        includeCurrencyConversion: true,
        fromDate: '',
        toDate: ''
      }
    }

    const getAssetIcon = (category) => {
      const iconMap = {
        'Building': 'fas fa-building',
        'Equipment': 'fas fa-cogs',
        'Vehicle': 'fas fa-car',
        'Furniture': 'fas fa-couch',
        'Computer': 'fas fa-laptop',
        'Machinery': 'fas fa-industry',
        default: 'fas fa-cube'
      }
      return iconMap[category] || iconMap.default
    }

    const formatCurrency = (value) => {
      return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      }).format(value || 0)
    }

    const formatNumber = (value, decimals = 0) => {
      return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals
      }).format(value || 0)
    }

    const formatDate = (date) => {
      if (!date) return '-'
      return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      })
    }

    // Watchers
    watch(selectedAssetId, (newAssetId) => {
      if (newAssetId) {
        selectedAsset.value = assets.value.find(asset => asset.asset_id === newAssetId)
      } else {
        selectedAsset.value = null
      }
      loadScheduleData()
    })

    // Lifecycle
    onMounted(async () => {
      await loadSystemCurrencies()
      await loadAssets()
      await loadScheduleData()
    })

    return {
      loading,
      exporting,
      assets,
      scheduleData,
      projectionData,
      systemCurrencies,
      selectedAssetId,
      selectedAsset,
      selectedYear,
      statusFilter,
      displayCurrency,
      baseCurrency,
      viewType,
      groupBy,
      projectionYears,
      showExportModal,
      exportFormat,
      exportOptions,
      chartOptions,
      availableYears,
      totalDepreciated,
      depreciationProgress,
      estimatedYearsRemaining,
      totalScheduledDepreciation,
      totalPeriods,
      recordedCount,
      projectedCount,
      canCalculateNext,
      organizedScheduleData,
      groupedScheduleData,
      loadScheduleData,
      generateProjections,
      organizeScheduleData,
      refreshData,
      calculateNextPeriod,
      calculateDepreciation,
      viewDepreciationDetail,
      updateChart,
      performExport,
      closeExportModal,
      getAssetIcon,
      getDisplayAmount,
      formatCurrency,
      formatNumber,
      formatDate
    }
  }
}
</script>

<style scoped>
/* Base page layout */
.schedule-page {
  padding: 2rem;
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
  min-height: 100vh;
}

/* Keep existing header styles */
.page-header {
  margin-bottom: 2rem;
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: white;
  padding: 2rem;
  border-radius: 20px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
  border: 1px solid #e2e8f0;
}

.title-section h1 {
  font-size: 2.5rem;
  font-weight: 700;
  background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
  background-clip: text;
  -webkit-text-fill-color: transparent;
  margin-bottom: 0.5rem;
}

.page-subtitle {
  color: #64748b;
  font-size: 1.1rem;
}

.breadcrumb {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 1rem;
  font-size: 0.9rem;
}

.breadcrumb-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #64748b;
  text-decoration: none;
  transition: color 0.3s ease;
}

.breadcrumb-item:hover {
  color: #6366f1;
}

.breadcrumb-current {
  color: #1f2937;
  font-weight: 600;
}

.header-actions {
  display: flex;
  gap: 1rem;
  align-items: center;
}

/* Controls section */
.controls-section {
  margin-bottom: 2rem;
}

.controls-card {
  background: white;
  border-radius: 16px;
  padding: 1.5rem;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
  border: 1px solid #e2e8f0;
}

.filters-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  margin-bottom: 1rem;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.filter-group label {
  font-size: 0.9rem;
  font-weight: 500;
  color: #374151;
}

.filter-group select {
  padding: 0.75rem;
  border: 2px solid #e5e7eb;
  border-radius: 10px;
  font-size: 0.9rem;
  transition: border-color 0.3s ease;
}

.filter-group select:focus {
  outline: none;
  border-color: #6366f1;
}

.controls-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 1rem;
  border-top: 1px solid #e2e8f0;
}

.view-controls {
  display: flex;
  align-items: center;
  gap: 2rem;
}

.group-options,
.projection-controls {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.group-options label,
.projection-controls label {
  font-size: 0.9rem;
  color: #374151;
  font-weight: 500;
}

.group-options select,
.projection-controls select {
  padding: 0.5rem;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  font-size: 0.9rem;
}

.view-options {
  display: flex;
  gap: 0.5rem;
}

.view-btn {
  width: 40px;
  height: 40px;
  border: 2px solid #e2e8f0;
  background: white;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.3s ease;
  color: #6b7280;
}

.view-btn:hover,
.view-btn.active {
  border-color: #6366f1;
  background: #6366f1;
  color: white;
}

/* Asset overview section */
.asset-overview-section {
  margin-bottom: 2rem;
}

.overview-card {
  background: white;
  border-radius: 16px;
  padding: 2rem;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
  border: 1px solid #e2e8f0;
}

.asset-summary {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 2rem;
  align-items: start;
}

.asset-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.asset-icon {
  width: 60px;
  height: 60px;
  background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.5rem;
}

.asset-details h3 {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1f2937;
  margin-bottom: 0.5rem;
}

.asset-code {
  background: #e0e7ff;
  color: #5b21b6;
  padding: 0.25rem 0.75rem;
  border-radius: 6px;
  font-size: 0.8rem;
  font-weight: 600;
  margin-right: 0.5rem;
}

.asset-category {
  color: #64748b;
  font-size: 0.9rem;
  font-weight: 500;
}

.asset-metrics {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}

.metric-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.metric-label {
  font-size: 0.8rem;
  color: #64748b;
  font-weight: 500;
}

.metric-value {
  font-size: 1rem;
  color: #1f2937;
  font-weight: 600;
}

.metric-value.remaining {
  color: #059669;
}

.metric-value.depreciated {
  color: #dc2626;
}

.base-currency {
  font-size: 0.8rem;
  color: #6b7280;
  font-weight: 400;
  display: block;
  margin-top: 0.25rem;
}

.depreciation-progress {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.progress-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.progress-label {
  font-size: 0.9rem;
  color: #374151;
  font-weight: 500;
}

.progress-percentage {
  font-size: 1.5rem;
  color: #6366f1;
  font-weight: 700;
}

.progress-bar {
  width: 100%;
  height: 12px;
  background: #f1f5f9;
  border-radius: 6px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #10b981 0%, #059669 100%);
  transition: width 0.3s ease;
}

.progress-footer {
  text-align: center;
}

.progress-info {
  font-size: 0.8rem;
  color: #64748b;
}

/* Schedule content */
.schedule-content {
  margin-bottom: 2rem;
}

.content-card {
  background: white;
  border-radius: 20px;
  padding: 2rem;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
  border: 1px solid #e2e8f0;
}

.content-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  padding-bottom: 1rem;
  border-bottom: 2px solid #f1f5f9;
}

.content-header h2 {
  font-size: 1.5rem;
  font-weight: 600;
  color: #1f2937;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.content-stats {
  display: flex;
  gap: 2rem;
}

.stat-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.25rem;
}

.stat-label {
  font-size: 0.8rem;
  color: #64748b;
  font-weight: 500;
}

.stat-value {
  font-size: 1rem;
  color: #1f2937;
  font-weight: 600;
}

.stat-value.recorded {
  color: #059669;
}

.stat-value.projected {
  color: #3b82f6;
}

/* Loading and empty states */
.loading-state,
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem 2rem;
  color: #64748b;
  text-align: center;
}

.loading-spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #f3f4f6;
  border-top: 4px solid #6366f1;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 1rem;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.empty-icon {
  width: 80px;
  height: 80px;
  background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 1.5rem;
  color: white;
  font-size: 2rem;
}

.empty-state h3 {
  font-size: 1.5rem;
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 0.5rem;
}

.empty-state p {
  color: #64748b;
  margin-bottom: 1.5rem;
}

/* Summary view */
.summary-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 1.5rem;
}

.summary-card {
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  padding: 1.5rem;
  transition: all 0.3s ease;
  background: #fafbfc;
}

.summary-card:not(.group):hover {
  transform: translateY(-3px);
  border-color: #6366f1;
  box-shadow: 0 8px 20px rgba(99, 102, 241, 0.15);
  cursor: pointer;
}

.summary-card.projected {
  background: #f0f9ff;
  border-color: #0ea5e9;
}

.summary-card.current {
  background: #ecfdf5;
  border-color: #10b981;
}

.summary-card.group {
  background: #f8fafc;
  border-color: #d1d5db;
}

.group-header {
  text-align: center;
  padding: 1rem 0;
}

.group-header h3 {
  font-size: 1.2rem;
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 0.5rem;
}

.group-summary {
  display: flex;
  justify-content: space-between;
  align-items: center;
  color: #64748b;
  font-size: 0.9rem;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1rem;
}

.period-info h4 {
  font-size: 1rem;
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 0.25rem;
}

.period-date {
  font-size: 0.8rem;
  color: #64748b;
}

.status-indicator {
  padding: 0.25rem 0.75rem;
  border-radius: 6px;
  font-size: 0.75rem;
  font-weight: 600;
}

.status-indicator.recorded {
  background: #d1fae5;
  color: #065f46;
}

.status-indicator.projected {
  background: #dbeafe;
  color: #1e40af;
}

.asset-info {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 1rem;
  flex-wrap: wrap;
}

.asset-name {
  font-weight: 600;
  color: #1f2937;
}

.asset-code {
  background: #e0e7ff;
  color: #5b21b6;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 500;
}

.asset-currency {
  background: #fef3c7;
  color: #92400e;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 500;
}

.amounts-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem;
  margin-bottom: 1rem;
}

.amount-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.amount-label {
  font-size: 0.8rem;
  color: #64748b;
  font-weight: 500;
}

.amount-value {
  font-size: 0.9rem;
  color: #1f2937;
  font-weight: 600;
}

.amount-value.primary {
  color: #dc2626;
}

.amount-value.remaining {
  color: #059669;
}

.exchange-rate {
  text-align: center;
  padding: 0.5rem;
  background: #f8fafc;
  border-radius: 6px;
  font-size: 0.8rem;
}

.rate-label {
  color: #64748b;
  margin-right: 0.5rem;
}

.rate-value {
  color: #6366f1;
  font-weight: 600;
}

/* Table view */
.table-container {
  overflow-x: auto;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
}

.schedule-table {
  width: 100%;
  border-collapse: collapse;
  background: white;
}

.schedule-table th {
  background: #f8fafc;
  padding: 1rem;
  text-align: left;
  font-weight: 600;
  color: #374151;
  border-bottom: 2px solid #e2e8f0;
  font-size: 0.9rem;
}

.schedule-table td {
  padding: 1rem;
  border-bottom: 1px solid #f1f5f9;
  font-size: 0.9rem;
}

.group-row {
  background: #f8fafc;
}

.group-title {
  font-weight: 600;
  color: #374151;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.schedule-row {
  transition: background-color 0.2s ease;
}

.schedule-row:hover {
  background: #f8fafc;
}

.schedule-row.projected {
  background: #f0f9ff;
}

.schedule-row.current {
  background: #ecfdf5;
}

.asset-cell strong {
  color: #1f2937;
  font-weight: 600;
}

.asset-cell small {
  color: #64748b;
  font-size: 0.8rem;
  display: block;
}

.currency-badge {
  background: #fef3c7;
  color: #92400e;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 500;
}

.amount-cell {
  text-align: right;
  font-weight: 600;
}

.amount-cell.primary {
  color: #dc2626;
}

.amount-cell.remaining {
  color: #059669;
}

.rate-cell {
  text-align: center;
  font-family: monospace;
  font-size: 0.8rem;
}

.status-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 6px;
  font-size: 0.75rem;
  font-weight: 600;
}

.status-badge.recorded {
  background: #d1fae5;
  color: #065f46;
}

.status-badge.projected {
  background: #dbeafe;
  color: #1e40af;
}

.actions-cell {
  text-align: center;
}

.action-buttons {
  display: flex;
  gap: 0.5rem;
  justify-content: center;
}

.btn-mini {
  width: 28px;
  height: 28px;
  border: none;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.3s ease;
  font-size: 0.7rem;
}

.btn-mini.view {
  background: #dbeafe;
  color: #1d4ed8;
}

.btn-mini.calculate {
  background: #fef3c7;
  color: #d97706;
}

.btn-mini:hover {
  transform: scale(1.1);
}

/* Chart view */
.chart-container {
  margin-top: 1.5rem;
}

.chart-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.chart-header h3 {
  font-size: 1.2rem;
  font-weight: 600;
  color: #1f2937;
}

.chart-options {
  display: flex;
  gap: 1rem;
}

.chart-options label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.9rem;
  color: #374151;
  cursor: pointer;
}

.chart-options input[type="checkbox"] {
  accent-color: #6366f1;
}

.chart-area {
  height: 400px;
  margin-bottom: 1rem;
}

.chart-legend {
  display: flex;
  justify-content: center;
  gap: 2rem;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.legend-color {
  width: 12px;
  height: 12px;
  border-radius: 2px;
}

.legend-color.book-value {
  background: #6366f1;
}

.legend-color.accumulated {
  background: #ef4444;
}

.legend-color.period {
  background: #10b981;
}

/* Modal styles */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 2rem;
}

.modal-container {
  background: white;
  border-radius: 20px;
  max-width: 600px;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem 1.5rem 0 1.5rem;
  margin-bottom: 1rem;
}

.modal-header h3 {
  font-size: 1.25rem;
  font-weight: 600;
  color: #1f2937;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.modal-close {
  width: 32px;
  height: 32px;
  border: none;
  background: #f3f4f6;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: #6b7280;
  transition: all 0.3s ease;
}

.modal-close:hover {
  background: #e5e7eb;
  color: #374151;
}

.modal-body {
  padding: 0 1.5rem 1.5rem 1.5rem;
}

.export-form {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.form-group label {
  font-size: 0.9rem;
  font-weight: 500;
  color: #374151;
}

.radio-group,
.checkbox-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.radio-item,
.checkbox-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem;
  border-radius: 8px;
  transition: background-color 0.2s ease;
  cursor: pointer;
}

.radio-item:hover,
.checkbox-item:hover {
  background: #f8fafc;
}

.radio-item input,
.checkbox-item input {
  accent-color: #6366f1;
}

.date-range {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.date-range input {
  flex: 1;
  padding: 0.75rem;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  font-size: 0.9rem;
}

.date-range span {
  color: #64748b;
  font-size: 0.9rem;
}

.modal-footer {
  display: flex;
  gap: 1rem;
  padding: 0 1.5rem 1.5rem 1.5rem;
}

/* Button styles */
.btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  border-radius: 10px;
  font-size: 0.9rem;
  font-weight: 500;
  text-decoration: none;
  transition: all 0.3s ease;
  cursor: pointer;
  border: none;
}

.btn-primary {
  background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: linear-gradient(135deg, #5b21b6 0%, #7c3aed 100%);
  transform: translateY(-2px);
  box-shadow: 0 10px 25px rgba(99, 102, 241, 0.3);
}

.btn-secondary {
  background: #f3f4f6;
  color: #374151;
  border: 2px solid #e5e7eb;
}

.btn-secondary:hover:not(:disabled) {
  background: #e5e7eb;
  border-color: #d1d5db;
}

.btn-outline {
  background: transparent;
  color: #6366f1;
  border: 2px solid #6366f1;
}

.btn-outline:hover:not(:disabled) {
  background: #6366f1;
  color: white;
}

.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  transform: none !important;
  box-shadow: none !important;
}

/* Responsive design */
@media (max-width: 768px) {
  .schedule-page {
    padding: 1rem;
  }

  .header-content {
    flex-direction: column;
    gap: 1rem;
    text-align: center;
  }

  .header-actions {
    flex-direction: column;
    width: 100%;
  }

  .filters-row {
    grid-template-columns: 1fr;
  }

  .controls-row {
    flex-direction: column;
    gap: 1rem;
    align-items: stretch;
  }

  .content-stats {
    flex-direction: column;
    gap: 1rem;
  }

  .asset-summary {
    grid-template-columns: 1fr;
  }

  .summary-grid {
    grid-template-columns: 1fr;
  }

  .amounts-grid {
    grid-template-columns: 1fr;
  }

  .modal-overlay {
    padding: 1rem;
  }

  .modal-footer {
    flex-direction: column;
  }

  .date-range {
    flex-direction: column;
    align-items: stretch;
  }
}
</style>