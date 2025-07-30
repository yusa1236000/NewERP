<!-- frontend/erp/src/views/accounting/depreciacions/DepreciationDetail.vue -->
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
            <span class="breadcrumb-current">Depreciation Detail</span>
          </div>
          <h1 class="page-title">
            <i class="fas fa-chart-line"></i>
            Depreciation Detail
          </h1>
          <p class="page-subtitle">Detailed view of asset depreciation calculation and related information</p>
        </div>
        <div class="header-actions">
          <div class="action-menu">
            <button @click="showActionsMenu = !showActionsMenu" class="btn btn-outline">
              <i class="fas fa-ellipsis-v"></i>
              Actions
            </button>
            <div v-if="showActionsMenu" class="actions-dropdown">
              <button
                v-if="canEdit"
                @click="editDepreciation"
                class="dropdown-item"
              >
                <i class="fas fa-edit"></i>
                Edit Depreciation
              </button>
              <button
                @click="createJournalEntry"
                class="dropdown-item"
                :disabled="!canCreateJournal"
              >
                <i class="fas fa-book"></i>
                Create Journal Entry
              </button>
              <button
                @click="viewSchedule"
                class="dropdown-item"
              >
                <i class="fas fa-calendar"></i>
                View Schedule
              </button>
              <hr class="dropdown-divider">
              <button
                @click="exportDetail"
                class="dropdown-item"
              >
                <i class="fas fa-download"></i>
                Export Details
              </button>
              <button
                @click="printDetail"
                class="dropdown-item"
              >
                <i class="fas fa-print"></i>
                Print Details
              </button>
              <hr class="dropdown-divider">
              <button
                v-if="canDelete"
                @click="deleteDepreciation"
                class="dropdown-item danger"
              >
                <i class="fas fa-trash"></i>
                Delete Depreciation
              </button>
            </div>
          </div>
          <button @click="goBack" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i>
            Back to List
          </button>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="loading-container">
      <div class="loading-spinner"></div>
      <p>Loading depreciation details...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="error-container">
      <div class="error-icon">
        <i class="fas fa-exclamation-triangle"></i>
      </div>
      <h3>Error Loading Details</h3>
      <p>{{ error }}</p>
      <button @click="loadDepreciationDetail" class="btn btn-primary">
        <i class="fas fa-refresh"></i>
        Retry
      </button>
    </div>

    <!-- Main Content -->
    <div v-else-if="depreciation" class="detail-content">
      <!-- Asset Overview -->
      <div class="asset-overview-section">
        <div class="overview-card">
          <div class="asset-header">
            <div class="asset-main-info">
              <div class="asset-title">
                <h2>{{ depreciation.fixed_asset?.name }}</h2>
                <span class="asset-code">{{ depreciation.fixed_asset?.asset_code }}</span>
                <span :class="['status-badge', depreciation.fixed_asset?.status?.toLowerCase()]">
                  {{ depreciation.fixed_asset?.status }}
                </span>
              </div>
              <div class="asset-category">
                <i class="fas fa-tag"></i>
                <span>{{ depreciation.fixed_asset?.category }}</span>
              </div>
            </div>
            <div class="asset-visual">
              <div class="asset-icon">
                <i :class="getAssetIcon(depreciation.fixed_asset?.category)"></i>
              </div>
              <div class="progress-circle">
                <svg viewBox="0 0 36 36" class="circular-chart">
                  <path class="circle-bg"
                    d="M18 2.0845
                      a 15.9155 15.9155 0 0 1 0 31.831
                      a 15.9155 15.9155 0 0 1 0 -31.831"
                  />
                  <path class="circle"
                    :stroke-dasharray="`${depreciationPercentage}, 100`"
                    d="M18 2.0845
                      a 15.9155 15.9155 0 0 1 0 31.831
                      a 15.9155 15.9155 0 0 1 0 -31.831"
                  />
                  <text x="18" y="20.35" class="percentage">{{ depreciationPercentage }}%</text>
                </svg>
              </div>
            </div>
          </div>

          <div class="asset-details-grid">
            <div class="detail-item">
              <span class="detail-label">Acquisition Cost</span>
              <span class="detail-value">
                {{ depreciation.fixed_asset?.currency }} {{ formatCurrency(depreciation.fixed_asset?.acquisition_cost) }}
                <span v-if="currencySummary?.base_acquisition_cost && depreciation.fixed_asset?.currency !== baseCurrency" 
                      class="base-currency">
                  ({{ baseCurrency }} {{ formatCurrency(currencySummary.base_acquisition_cost) }})
                </span>
              </span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Current Value</span>
              <span class="detail-value remaining">
                {{ depreciation.fixed_asset?.currency }} {{ formatCurrency(depreciation.remaining_value) }}
                <span v-if="depreciation.base_currency_remaining_value && depreciation.fixed_asset?.currency !== baseCurrency" 
                      class="base-currency">
                  ({{ baseCurrency }} {{ formatCurrency(depreciation.base_currency_remaining_value) }})
                </span>
              </span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Depreciation Method</span>
              <span class="detail-value">{{ depreciation.fixed_asset?.depreciation_method || 'Straight Line' }}</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Useful Life</span>
              <span class="detail-value">{{ depreciation.fixed_asset?.useful_life }} years</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Depreciation Rate</span>
              <span class="detail-value">{{ depreciation.fixed_asset?.depreciation_rate }}% per year</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Exchange Rate</span>
              <span class="detail-value">
                <span v-if="depreciation.exchange_rate && depreciation.fixed_asset?.currency !== baseCurrency">
                  1 {{ depreciation.fixed_asset?.currency }} = {{ formatNumber(depreciation.exchange_rate, 4) }} {{ baseCurrency }}
                </span>
                <span v-else>N/A (Base Currency)</span>
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Depreciation Details -->
      <div class="depreciation-details-section">
        <div class="details-card">
          <div class="card-header">
            <h3>
              <i class="fas fa-calculator"></i>
              Depreciation Calculation
            </h3>
            <div class="period-info">
              <span class="period-badge">{{ depreciation.accounting_period?.period_name }}</span>
              <span class="date-badge">{{ formatDate(depreciation.depreciation_date) }}</span>
            </div>
          </div>

          <!-- Currency Summary -->
          <div v-if="currencySummary && depreciation.fixed_asset?.currency !== baseCurrency" class="currency-summary">
            <div class="currency-row">
              <div class="currency-column">
                <h4>Original Currency ({{ depreciation.fixed_asset?.currency }})</h4>
                <div class="currency-amounts">
                  <div class="amount-item">
                    <span class="amount-label">Depreciation Amount</span>
                    <span class="amount-value">{{ formatCurrency(depreciation.depreciation_amount) }}</span>
                  </div>
                  <div class="amount-item">
                    <span class="amount-label">Accumulated Depreciation</span>
                    <span class="amount-value">{{ formatCurrency(depreciation.accumulated_depreciation) }}</span>
                  </div>
                  <div class="amount-item">
                    <span class="amount-label">Remaining Value</span>
                    <span class="amount-value remaining">{{ formatCurrency(depreciation.remaining_value) }}</span>
                  </div>
                </div>
              </div>
              <div class="currency-divider">
                <div class="exchange-rate-info">
                  <i class="fas fa-exchange-alt"></i>
                  <span>Rate: {{ formatNumber(depreciation.exchange_rate, 4) }}</span>
                </div>
              </div>
              <div class="currency-column">
                <h4>Base Currency ({{ baseCurrency }})</h4>
                <div class="currency-amounts">
                  <div class="amount-item">
                    <span class="amount-label">Depreciation Amount</span>
                    <span class="amount-value">{{ formatCurrency(depreciation.base_currency_depreciation_amount) }}</span>
                  </div>
                  <div class="amount-item">
                    <span class="amount-label">Accumulated Depreciation</span>
                    <span class="amount-value">{{ formatCurrency(depreciation.base_currency_accumulated_depreciation) }}</span>
                  </div>
                  <div class="amount-item">
                    <span class="amount-label">Remaining Value</span>
                    <span class="amount-value remaining">{{ formatCurrency(depreciation.base_currency_remaining_value) }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Calculation Breakdown -->
          <div class="calculation-breakdown">
            <h4>Calculation Breakdown</h4>
            <div class="breakdown-steps">
              <div class="breakdown-row">
                <span class="breakdown-label">
                  <i class="fas fa-dollar-sign"></i>
                  Opening Book Value
                </span>
                <span class="breakdown-value">
                  {{ depreciation.fixed_asset?.currency }} {{ formatCurrency(openingValue) }}
                </span>
              </div>

              <div class="breakdown-row">
                <span class="breakdown-label">
                  <i class="fas fa-percentage"></i>
                  Depreciation Rate ({{ depreciation.fixed_asset?.depreciation_rate }}%)
                </span>
                <span class="breakdown-value">× {{ depreciation.fixed_asset?.currency }} {{ formatCurrency(depreciation.fixed_asset?.current_value) }}</span>
              </div>

              <div class="breakdown-row highlight">
                <span class="breakdown-label">
                  <i class="fas fa-arrow-down"></i>
                  <strong>Period Depreciation</strong>
                </span>
                <span class="breakdown-value primary">
                  <strong>{{ depreciation.fixed_asset?.currency }} {{ formatCurrency(depreciation.depreciation_amount) }}</strong>
                </span>
              </div>

              <div class="breakdown-row">
                <span class="breakdown-label">
                  <i class="fas fa-plus"></i>
                  Previous Accumulated Depreciation
                </span>
                <span class="breakdown-value">{{ depreciation.fixed_asset?.currency }} {{ formatCurrency(previousAccumulated) }}</span>
              </div>

              <div class="breakdown-row total">
                <span class="breakdown-label">
                  <i class="fas fa-equals"></i>
                  <strong>Total Accumulated Depreciation</strong>
                </span>
                <span class="breakdown-value secondary">
                  <strong>{{ depreciation.fixed_asset?.currency }} {{ formatCurrency(depreciation.accumulated_depreciation) }}</strong>
                </span>
              </div>

              <div class="breakdown-row final">
                <span class="breakdown-label">
                  <i class="fas fa-hand-holding-dollar"></i>
                  <strong>Remaining Book Value</strong>
                </span>
                <span class="breakdown-value remaining">
                  <strong>{{ depreciation.fixed_asset?.currency }} {{ formatCurrency(depreciation.remaining_value) }}</strong>
                </span>
              </div>
            </div>

            <div class="calculation-metadata">
              <div class="metadata-item">
                <span class="metadata-label">Calculation Date:</span>
                <span class="metadata-value">{{ formatDateTime(depreciation.depreciation_date) }}</span>
              </div>
              <div class="metadata-item">
                <span class="metadata-label">Calculation Method:</span>
                <span class="metadata-value">{{ depreciation.fixed_asset?.depreciation_method || 'Straight Line' }}</span>
              </div>
              <div class="metadata-item">
                <span class="metadata-label">Years Depreciated:</span>
                <span class="metadata-value">{{ yearsDepreciated }} of {{ depreciation.fixed_asset?.useful_life }} years</span>
              </div>
              <div class="metadata-item">
                <span class="metadata-label">Remaining Life:</span>
                <span class="metadata-value">{{ yearsRemaining }} years</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Journal Entry Section -->
      <div v-if="journalEntry || canCreateJournal" class="journal-entry-section">
        <div class="journal-card">
          <div class="card-header">
            <h3>
              <i class="fas fa-book"></i>
              Journal Entry
            </h3>
            <div class="entry-actions" v-if="journalEntry">
              <button @click="viewJournalEntry" class="btn btn-outline">
                <i class="fas fa-external-link-alt"></i>
                View Full Entry
              </button>
            </div>
          </div>

          <div v-if="journalEntry" class="journal-content">
            <div class="journal-info">
              <div class="info-item">
                <span class="info-label">Entry Number:</span>
                <span class="info-value">{{ journalEntry.entry_number }}</span>
              </div>
              <div class="info-item">
                <span class="info-label">Date:</span>
                <span class="info-value">{{ formatDate(journalEntry.transaction_date) }}</span>
              </div>
              <div class="info-item">
                <span class="info-label">Status:</span>
                <span :class="['status-badge', journalEntry.status?.toLowerCase()]">
                  {{ journalEntry.status }}
                </span>
              </div>
              <div class="info-item">
                <span class="info-label">Reference:</span>
                <span class="info-value">{{ journalEntry.reference || '-' }}</span>
              </div>
            </div>

            <div class="journal-entries">
              <h4>Journal Lines</h4>
              <div class="journal-lines">
                <div
                  v-for="line in journalEntry.lines"
                  :key="line.line_id"
                  class="journal-line"
                >
                  <div class="line-account">
                    <strong>{{ line.account?.name }}</strong>
                    <small>{{ line.account?.account_code }}</small>
                  </div>
                  <div class="line-amounts">
                    <span v-if="line.debit_amount" class="debit-amount">
                      Dr: {{ formatCurrency(line.debit_amount) }}
                    </span>
                    <span v-if="line.credit_amount" class="credit-amount">
                      Cr: {{ formatCurrency(line.credit_amount) }}
                    </span>
                  </div>
                </div>
              </div>

              <div class="journal-totals">
                <div class="total-row">
                  <span>Total Debits:</span>
                  <span class="debit-total">{{ formatCurrency(journalTotalDebits) }}</span>
                </div>
                <div class="total-row">
                  <span>Total Credits:</span>
                  <span class="credit-total">{{ formatCurrency(journalTotalCredits) }}</span>
                </div>
                <div class="balance-check">
                  <span :class="['balance-status', { balanced: isJournalBalanced }]">
                    <i :class="isJournalBalanced ? 'fas fa-check-circle' : 'fas fa-exclamation-triangle'"></i>
                    {{ isJournalBalanced ? 'Balanced' : 'Out of Balance' }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <div v-else class="no-journal">
            <div class="no-journal-icon">
              <i class="fas fa-book-open"></i>
            </div>
            <h4>No Journal Entry Created</h4>
            <p>Create a journal entry to record this depreciation in your accounting books.</p>
            <button @click="createJournalEntry" class="btn btn-primary">
              <i class="fas fa-plus"></i>
              Create Journal Entry
            </button>
          </div>
        </div>
      </div>

      <!-- Historical Analysis -->
      <div class="historical-analysis-section">
        <div class="analysis-card">
          <div class="card-header">
            <h3>
              <i class="fas fa-chart-area"></i>
              Historical Analysis
            </h3>
            <div class="view-options">
              <button
                @click="historicalView = 'chart'"
                :class="['view-btn', { active: historicalView === 'chart' }]"
              >
                <i class="fas fa-chart-line"></i>
                Chart
              </button>
              <button
                @click="historicalView = 'table'"
                :class="['view-btn', { active: historicalView === 'table' }]"
              >
                <i class="fas fa-table"></i>
                Table
              </button>
            </div>
          </div>

          <div v-if="historicalView === 'chart'" class="chart-container">
            <div class="chart-area">
              <canvas ref="historyChart"></canvas>
            </div>
            <div class="chart-legend">
              <div class="legend-item">
                <span class="legend-color book-value"></span>
                <span>Book Value</span>
              </div>
              <div class="legend-item">
                <span class="legend-color accumulated"></span>
                <span>Accumulated Depreciation</span>
              </div>
            </div>
          </div>

          <div v-else class="history-table-container">
            <table class="history-table">
              <thead>
                <tr>
                  <th>Period</th>
                  <th>Date</th>
                  <th>Opening Value</th>
                  <th>Depreciation</th>
                  <th>Accumulated</th>
                  <th>Closing Value</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="item in historicalData"
                  :key="item.period_name"
                  :class="{ current: item.is_current }"
                >
                  <td>{{ item.period_name }}</td>
                  <td>{{ formatDate(item.date) }}</td>
                  <td>{{ formatCurrency(item.opening_value) }}</td>
                  <td>{{ formatCurrency(item.depreciation_amount) }}</td>
                  <td>{{ formatCurrency(item.accumulated_depreciation) }}</td>
                  <td>{{ formatCurrency(item.closing_value) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Future Projections -->
      <div class="projections-section">
        <div class="projections-card">
          <div class="card-header">
            <h3>
              <i class="fas fa-crystal-ball"></i>
              Future Projections
            </h3>
            <div class="projection-controls">
              <label>Projection Periods:</label>
              <select v-model="projectionPeriods" @change="generateProjections">
                <option value="6">6 Periods</option>
                <option value="12">12 Periods</option>
                <option value="24">24 Periods</option>
                <option value="36">36 Periods</option>
              </select>
            </div>
          </div>

          <div class="projections-grid">
            <div
              v-for="projection in projections"
              :key="projection.period"
              class="projection-card"
            >
              <div class="projection-header">
                <h4>Period {{ projection.period }}</h4>
                <span class="projection-date">{{ formatDate(projection.date) }}</span>
              </div>
              <div class="projection-details">
                <div class="projection-item">
                  <span class="projection-label">Depreciation</span>
                  <span class="projection-value">{{ formatCurrency(projection.depreciation_amount) }}</span>
                </div>
                <div class="projection-item">
                  <span class="projection-label">Accumulated</span>
                  <span class="projection-value">{{ formatCurrency(projection.accumulated_depreciation) }}</span>
                </div>
                <div class="projection-item">
                  <span class="projection-label">Book Value</span>
                  <span class="projection-value remaining">{{ formatCurrency(projection.book_value) }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div v-if="showDeleteModal" class="modal-overlay" @click="closeDeleteModal">
      <div class="modal-container" @click.stop>
        <div class="modal-header">
          <h3>
            <i class="fas fa-exclamation-triangle"></i>
            Confirm Deletion
          </h3>
        </div>
        <div class="modal-body">
          <p>Are you sure you want to delete this depreciation record?</p>
          <div class="deletion-details">
            <div class="detail-row">
              <span>Asset:</span>
              <span>{{ depreciation?.fixed_asset?.name }}</span>
            </div>
            <div class="detail-row">
              <span>Period:</span>
              <span>{{ depreciation?.accounting_period?.period_name }}</span>
            </div>
            <div class="detail-row">
              <span>Amount:</span>
              <span>{{ formatCurrency(depreciation?.depreciation_amount) }}</span>
            </div>
          </div>
          <div class="warning-note">
            <i class="fas fa-info-circle"></i>
            This action cannot be undone and will restore the asset's previous value.
          </div>
        </div>
        <div class="modal-footer">
          <button @click="closeDeleteModal" class="btn btn-secondary">Cancel</button>
          <button @click="confirmDelete" class="btn btn-danger" :disabled="deleting">
            <i v-if="deleting" class="fas fa-spinner fa-spin"></i>
            <i v-else class="fas fa-trash"></i>
            {{ deleting ? 'Deleting...' : 'Delete' }}
          </button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'

export default {
  name: 'DepreciationDetail',
  components: {
  },
  setup() {
    const route = useRoute()
    const router = useRouter()
    
    // Reactive state
    const loading = ref(false)
    const deleting = ref(false)
    const error = ref('')
    const depreciation = ref(null)
    const journalEntry = ref(null)
    const currencySummary = ref(null)
    const historicalData = ref([])
    const projections = ref([])
    const baseCurrency = ref('USD')
    
    // UI state
    const showActionsMenu = ref(false)
    const showDeleteModal = ref(false)
    const historicalView = ref('chart')
    const projectionPeriods = ref(6)

    // Computed properties
    const depreciationPercentage = computed(() => {
      if (!depreciation.value?.fixed_asset?.acquisition_cost) return 0
      const percentage = (depreciation.value.accumulated_depreciation / depreciation.value.fixed_asset.acquisition_cost) * 100
      return Math.min(100, Math.round(percentage))
    })

    const totalDepreciated = computed(() => {
      return depreciation.value?.accumulated_depreciation || 0
    })

    const openingValue = computed(() => {
      return (depreciation.value?.fixed_asset?.acquisition_cost || 0) - 
             ((depreciation.value?.accumulated_depreciation || 0) - (depreciation.value?.depreciation_amount || 0))
    })

    const previousAccumulated = computed(() => {
      return (depreciation.value?.accumulated_depreciation || 0) - (depreciation.value?.depreciation_amount || 0)
    })

    const estimatedUsefulLife = computed(() => {
      if (!depreciation.value?.fixed_asset?.useful_life) return 0
      return depreciation.value.fixed_asset.useful_life
    })

    const yearsDepreciated = computed(() => {
      if (!depreciation.value?.fixed_asset) return 0
      const assetAge = new Date().getFullYear() - new Date(depreciation.value.fixed_asset.acquisition_date).getFullYear()
      return Math.min(assetAge, estimatedUsefulLife.value)
    })

    const yearsRemaining = computed(() => {
      return Math.max(0, estimatedUsefulLife.value - yearsDepreciated.value)
    })

    const canEdit = computed(() => {
      return depreciation.value && (!journalEntry.value || journalEntry.value.status !== 'Posted')
    })

    const canDelete = computed(() => {
      return depreciation.value && (!journalEntry.value || journalEntry.value.status !== 'Posted')
    })

    const canCreateJournal = computed(() => {
      return depreciation.value && !journalEntry.value
    })

    const journalTotalDebits = computed(() => {
      if (!journalEntry.value?.lines) return 0
      return journalEntry.value.lines.reduce((sum, line) => sum + (line.debit_amount || 0), 0)
    })

    const journalTotalCredits = computed(() => {
      if (!journalEntry.value?.lines) return 0
      return journalEntry.value.lines.reduce((sum, line) => sum + (line.credit_amount || 0), 0)
    })

    const isJournalBalanced = computed(() => {
      return Math.abs(journalTotalDebits.value - journalTotalCredits.value) < 0.01
    })

    // Methods
    const loadDepreciationDetail = async () => {
      try {
        loading.value = true
        error.value = ''
        
        const response = await axios.get(`/accounting/asset-depreciations/${route.params.id}`)
        depreciation.value = response.data.data
        currencySummary.value = response.data.currency_summary
        
        // Load related data
        if (depreciation.value.journal_entry_id) {
          await loadJournalEntry()
        }
        
        await loadHistoricalData()
        generateProjections()
        
      } catch (err) {
        console.error('Error loading depreciation detail:', err)
        error.value = err.response?.data?.message || 'Failed to load depreciation details'
      } finally {
        loading.value = false
      }
    }

    const loadJournalEntry = async () => {
      try {
        const response = await axios.get(`/accounting/journal-entries/${depreciation.value.journal_entry_id}`)
        journalEntry.value = response.data.data
      } catch (err) {
        console.error('Error loading journal entry:', err)
      }
    }

    const loadHistoricalData = async () => {
      try {
        const response = await axios.get(`/accounting/asset-depreciations/history/${depreciation.value.asset_id}`)
        historicalData.value = response.data.data.map(item => ({
          ...item,
          is_current: item.depreciation_id === depreciation.value.depreciation_id
        }))
      } catch (err) {
        console.error('Error loading historical data:', err)
      }
    }

    const generateProjections = () => {
      if (!depreciation.value?.fixed_asset) return

      const asset = depreciation.value.fixed_asset
      const currentValue = depreciation.value.remaining_value
      const monthlyDepreciation = (asset.depreciation_rate / 100) * asset.acquisition_cost / 12
      
      projections.value = []
      let remainingValue = currentValue
      
      for (let i = 1; i <= projectionPeriods.value; i++) {
        const depreciationAmount = Math.min(monthlyDepreciation, remainingValue)
        remainingValue -= depreciationAmount
        
        const projectionDate = new Date()
        projectionDate.setMonth(projectionDate.getMonth() + i)
        
        projections.value.push({
          period: i,
          date: projectionDate.toISOString().split('T')[0],
          depreciation_amount: depreciationAmount,
          accumulated_depreciation: depreciation.value.accumulated_depreciation + (depreciationAmount * i),
          book_value: Math.max(0, remainingValue)
        })
        
        if (remainingValue <= 0) break
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

    const viewSchedule = () => {
      router.push(`/accounting/depreciations/schedule/${depreciation.value.asset_id}`)
    }

    const viewJournalEntry = () => {
      if (journalEntry.value) {
        router.push(`/accounting/journal-entries/${journalEntry.value.entry_id}`)
      }
    }

    const editDepreciation = () => {
      router.push(`/accounting/depreciations/${route.params.id}/edit`)
    }

    const createJournalEntry = async () => {
      try {
        const response = await axios.post(`/accounting/asset-depreciations/${route.params.id}/create-journal`)
        journalEntry.value = response.data.data
        // Show success message
        console.log('Journal entry created successfully')
      } catch (err) {
        console.error('Error creating journal entry:', err)
        // Handle error
      }
    }

    const deleteDepreciation = () => {
      showDeleteModal.value = true
    }

    const confirmDelete = async () => {
      try {
        deleting.value = true
        await axios.delete(`/accounting/asset-depreciations/${route.params.id}`)
        
        // Show success message and redirect
        router.push('/accounting/depreciations')
      } catch (err) {
        console.error('Error deleting depreciation:', err)
        // Handle error
      } finally {
        deleting.value = false
      }
    }

    const closeDeleteModal = () => {
      showDeleteModal.value = false
    }

    const exportDetail = async () => {
      try {
        const response = await axios.get(`/accounting/asset-depreciations/${route.params.id}/export`, {
          responseType: 'blob'
        })
        
        const blob = new Blob([response.data])
        const url = window.URL.createObjectURL(blob)
        const link = document.createElement('a')
        link.href = url
        link.download = `depreciation-${depreciation.value.fixed_asset?.asset_code}-${depreciation.value.accounting_period?.period_name}.pdf`
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
        window.URL.revokeObjectURL(url)
      } catch (err) {
        console.error('Error exporting detail:', err)
      }
    }

    const printDetail = () => {
      window.print()
    }

    const goBack = () => {
      router.push('/accounting/depreciations')
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

    const formatDateTime = (date) => {
      if (!date) return '-'
      return new Date(date).toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      })
    }

    // Click outside handler
    const handleClickOutside = (event) => {
      if (!event.target.closest('.action-menu')) {
        showActionsMenu.value = false
      }
    }

    // Lifecycle
    onMounted(() => {
      loadDepreciationDetail()
      document.addEventListener('click', handleClickOutside)
    })

    onUnmounted(() => {
      document.removeEventListener('click', handleClickOutside)
    })

    return {
      loading,
      deleting,
      error,
      depreciation,
      journalEntry,
      currencySummary,
      historicalData,
      projections,
      baseCurrency,
      showActionsMenu,
      showDeleteModal,
      historicalView,
      projectionPeriods,
      depreciationPercentage,
      totalDepreciated,
      openingValue,
      previousAccumulated,
      estimatedUsefulLife,
      yearsDepreciated,
      yearsRemaining,
      canEdit,
      canDelete,
      canCreateJournal,
      journalTotalDebits,
      journalTotalCredits,
      isJournalBalanced,
      loadDepreciationDetail,
      generateProjections,
      getAssetIcon,
      viewSchedule,
      viewJournalEntry,
      editDepreciation,
      createJournalEntry,
      deleteDepreciation,
      confirmDelete,
      closeDeleteModal,
      exportDetail,
      printDetail,
      goBack,
      formatCurrency,
      formatNumber,
      formatDate,
      formatDateTime
    }
  }
}
</script>

<style scoped>
/* Keep existing CSS structure with enhancements for multi-currency */
.depreciation-detail-page {
  padding: 2rem;
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
  min-height: 100vh;
}

/* Header - keep existing styles */
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

.title-section i {
  margin-right: 1rem;
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

/* Action Menu */
.action-menu {
  position: relative;
}

.actions-dropdown {
  position: absolute;
  top: 100%;
  right: 0;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
  z-index: 100;
  min-width: 200px;
  padding: 0.5rem 0;
  margin-top: 0.5rem;
}

.dropdown-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  width: 100%;
  padding: 0.75rem 1rem;
  border: none;
  background: none;
  text-align: left;
  cursor: pointer;
  transition: background-color 0.2s ease;
  color: #374151;
}

.dropdown-item:hover:not(:disabled) {
  background: #f3f4f6;
}

.dropdown-item:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.dropdown-item.danger {
  color: #dc2626;
}

.dropdown-item.danger:hover {
  background: #fef2f2;
}

.dropdown-divider {
  margin: 0.5rem 0;
  border: none;
  border-top: 1px solid #e2e8f0;
}

/* Detail Content */
.detail-content {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

/* Asset Overview */
.asset-overview-section {
  background: white;
  border-radius: 20px;
  padding: 2rem;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
  border: 1px solid #e2e8f0;
}

.asset-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
}

.asset-main-info {
  flex: 1;
}

.asset-title {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1rem;
}

.asset-title h2 {
  font-size: 1.8rem;
  font-weight: 700;
  color: #1f2937;
  margin: 0;
}

.asset-code {
  background: #e0e7ff;
  color: #5b21b6;
  padding: 0.5rem 1rem;
  border-radius: 8px;
  font-size: 0.9rem;
  font-weight: 600;
}

.status-badge {
  padding: 0.5rem 1rem;
  border-radius: 8px;
  font-size: 0.8rem;
  font-weight: 600;
}

.status-badge.active {
  background: #d1fae5;
  color: #065f46;
}

.status-badge.inactive {
  background: #fee2e2;
  color: #991b1b;
}

.asset-category {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #64748b;
  font-size: 0.9rem;
}

.asset-visual {
  display: flex;
  align-items: center;
  gap: 2rem;
}

.asset-icon {
  width: 80px;
  height: 80px;
  background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 2rem;
}

.progress-circle {
  width: 120px;
  height: 120px;
}

.circular-chart {
  display: block;
  margin: 10px auto;
  max-width: 80%;
  max-height: 250px;
}

.circle-bg {
  fill: none;
  stroke: #eee;
  stroke-width: 3.8;
}

.circle {
  fill: none;
  stroke-width: 2.8;
  stroke-linecap: round;
  animation: progress 1s ease-out forwards;
  stroke: #6366f1;
}

.percentage {
  fill: #666;
  font-family: sans-serif;
  font-size: 0.5em;
  text-anchor: middle;
}

@keyframes progress {
  0% {
    stroke-dasharray: 0 100;
  }
}

.asset-details-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.5rem;
}

.detail-item {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.detail-label {
  font-size: 0.9rem;
  color: #64748b;
  font-weight: 500;
}

.detail-value {
  font-size: 1rem;
  color: #1f2937;
  font-weight: 600;
}

.detail-value.remaining {
  color: #059669;
}

.base-currency {
  font-size: 0.8rem;
  color: #6b7280;
  font-weight: 400;
  display: block;
  margin-top: 0.25rem;
}

/* Depreciation Details - enhanced for multi-currency */
.depreciation-details-section {
  background: white;
  border-radius: 20px;
  padding: 2rem;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
  border: 1px solid #e2e8f0;
}

.details-card .card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  padding-bottom: 1rem;
  border-bottom: 2px solid #f1f5f9;
}

.card-header h3 {
  font-size: 1.5rem;
  font-weight: 600;
  color: #1f2937;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.period-info {
  display: flex;
  gap: 1rem;
  align-items: center;
}

.period-badge {
  background: #dbeafe;
  color: #1d4ed8;
  padding: 0.5rem 1rem;
  border-radius: 8px;
  font-size: 0.9rem;
  font-weight: 600;
}

.date-badge {
  background: #f3f4f6;
  color: #374151;
  padding: 0.5rem 1rem;
  border-radius: 8px;
  font-size: 0.9rem;
  font-weight: 500;
}

/* Currency Summary */
.currency-summary {
  background: #f8fafc;
  border-radius: 12px;
  padding: 1.5rem;
  margin-bottom: 2rem;
  border: 1px solid #e2e8f0;
}

.currency-row {
  display: grid;
  grid-template-columns: 1fr auto 1fr;
  gap: 2rem;
  align-items: center;
}

.currency-column h4 {
  font-size: 1.1rem;
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 1rem;
  text-align: center;
}

.currency-amounts {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.amount-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem;
  background: white;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
}

.amount-label {
  font-size: 0.9rem;
  color: #64748b;
  font-weight: 500;
}

.amount-value {
  font-size: 1rem;
  color: #1f2937;
  font-weight: 600;
}

.amount-value.remaining {
  color: #059669;
}

.currency-divider {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
}

.exchange-rate-info {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  padding: 1rem;
  background: white;
  border-radius: 8px;
  border: 2px solid #6366f1;
  color: #6366f1;
  font-weight: 600;
}

/* Calculation Breakdown */
.calculation-breakdown h4 {
  font-size: 1.2rem;
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 1.5rem;
}

.breakdown-steps {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  margin-bottom: 2rem;
}

.breakdown-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
  background: #f8fafc;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
}

.breakdown-row.highlight {
  background: #eff6ff;
  border-color: #3b82f6;
}

.breakdown-row.total {
  background: #f0f9ff;
  border-color: #0ea5e9;
}

.breakdown-row.final {
  background: #ecfdf5;
  border-color: #10b981;
}

.breakdown-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #374151;
  font-size: 0.9rem;
}

.breakdown-value {
  font-weight: 600;
  color: #1f2937;
}

.breakdown-value.primary {
  color: #dc2626;
}

.breakdown-value.secondary {
  color: #0ea5e9;
}

.breakdown-value.remaining {
  color: #059669;
}

.calculation-metadata {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  padding: 1rem;
  background: #f8fafc;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
}

.metadata-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.metadata-label {
  font-size: 0.8rem;
  color: #64748b;
  font-weight: 500;
}

.metadata-value {
  font-size: 0.9rem;
  color: #1f2937;
  font-weight: 600;
}

/* Journal Entry Section */
.journal-entry-section {
  background: white;
  border-radius: 20px;
  padding: 2rem;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
  border: 1px solid #e2e8f0;
}

.journal-card .card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  padding-bottom: 1rem;
  border-bottom: 2px solid #f1f5f9;
}

.journal-info {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  margin-bottom: 2rem;
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.info-label {
  font-size: 0.8rem;
  color: #64748b;
  font-weight: 500;
}

.info-value {
  font-size: 0.9rem;
  color: #1f2937;
  font-weight: 600;
}

.journal-entries h4 {
  font-size: 1.1rem;
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 1rem;
}

.journal-lines {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  margin-bottom: 1.5rem;
}

.journal-line {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
  background: #f8fafc;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
}

.line-account strong {
  color: #1f2937;
  font-weight: 600;
}

.line-account small {
  color: #64748b;
  font-size: 0.8rem;
  display: block;
}

.line-amounts {
  display: flex;
  gap: 1rem;
}

.debit-amount {
  color: #dc2626;
  font-weight: 600;
}

.credit-amount {
  color: #059669;
  font-weight: 600;
}

.journal-totals {
  background: #f1f5f9;
  border-radius: 8px;
  padding: 1rem;
}

.total-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.5rem;
}

.debit-total {
  color: #dc2626;
  font-weight: 600;
}

.credit-total {
  color: #059669;
  font-weight: 600;
}

.balance-check {
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid #e2e8f0;
  text-align: center;
}

.balance-status {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  border-radius: 8px;
  font-weight: 600;
}

.balance-status.balanced {
  background: #d1fae5;
  color: #065f46;
}

.balance-status:not(.balanced) {
  background: #fee2e2;
  color: #991b1b;
}

.no-journal {
  text-align: center;
  padding: 3rem 2rem;
}

.no-journal-icon {
  width: 80px;
  height: 80px;
  background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1.5rem;
  color: white;
  font-size: 2rem;
}

.no-journal h4 {
  font-size: 1.25rem;
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 0.5rem;
}

.no-journal p {
  color: #64748b;
  margin-bottom: 1.5rem;
}

/* Historical Analysis and other sections - keep existing styles */
.historical-analysis-section,
.projections-section {
  background: white;
  border-radius: 20px;
  padding: 2rem;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
  border: 1px solid #e2e8f0;
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

.chart-container {
  margin-top: 1.5rem;
}

.chart-area {
  height: 300px;
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

.history-table-container {
  margin-top: 1.5rem;
  overflow-x: auto;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
}

.history-table {
  width: 100%;
  border-collapse: collapse;
}

.history-table th {
  background: #f8fafc;
  padding: 1rem;
  text-align: left;
  font-weight: 600;
  color: #374151;
  border-bottom: 2px solid #e2e8f0;
}

.history-table td {
  padding: 1rem;
  border-bottom: 1px solid #f1f5f9;
}

.history-table tr.current {
  background: #eff6ff;
}

.projections-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 1rem;
  margin-top: 1.5rem;
}

.projection-card {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 1rem;
}

.projection-header {
  margin-bottom: 1rem;
  text-align: center;
}

.projection-header h4 {
  font-size: 1rem;
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 0.25rem;
}

.projection-date {
  font-size: 0.8rem;
  color: #64748b;
}

.projection-details {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.projection-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.projection-label {
  font-size: 0.8rem;
  color: #64748b;
}

.projection-value {
  font-size: 0.9rem;
  font-weight: 600;
  color: #1f2937;
}

.projection-value.remaining {
  color: #059669;
}

.projection-controls {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.projection-controls label {
  font-size: 0.9rem;
  color: #374151;
  font-weight: 500;
}

.projection-controls select {
  padding: 0.5rem;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  font-size: 0.9rem;
}

/* Loading and Error States - keep existing */
.loading-container,
.error-container {
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

.error-icon {
  width: 80px;
  height: 80px;
  background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 1.5rem;
  color: white;
  font-size: 2rem;
}

.error-container h3 {
  font-size: 1.5rem;
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 0.5rem;
}

.error-container p {
  color: #64748b;
  margin-bottom: 1.5rem;
}

/* Modal styles - keep existing */
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
  max-width: 500px;
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

.modal-body {
  padding: 0 1.5rem 1.5rem 1.5rem;
}

.deletion-details {
  background: #f8fafc;
  border-radius: 8px;
  padding: 1rem;
  margin: 1rem 0;
  text-align: left;
}

.detail-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.5rem 0;
  border-bottom: 1px solid #e2e8f0;
}

.detail-row:last-child {
  border-bottom: none;
}

.warning-note {
  background: #fef3c7;
  border: 1px solid #fde68a;
  border-radius: 8px;
  padding: 0.75rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #92400e;
  font-size: 0.9rem;
}

.modal-footer {
  display: flex;
  gap: 1rem;
  padding: 0 1.5rem 1.5rem 1.5rem;
}

/* Button Styles - keep existing */
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

.btn-danger {
  background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
  color: white;
}

.btn-danger:hover:not(:disabled) {
  background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
  transform: translateY(-2px);
  box-shadow: 0 10px 25px rgba(239, 68, 68, 0.3);
}

.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  transform: none !important;
  box-shadow: none !important;
}

/* Responsive Design */
@media (max-width: 768px) {
  .depreciation-detail-page {
    padding: 1rem;
  }

  .header-content {
    flex-direction: column;
    gap: 1rem;
    text-align: center;
  }

  .header-actions {
    flex-direction: column;
  }

  .asset-header {
    flex-direction: column;
    gap: 1rem;
    text-align: center;
  }

  .asset-details-grid {
    grid-template-columns: 1fr;
  }

  .currency-row {
    grid-template-columns: 1fr;
    gap: 1rem;
  }

  .breakdown-row {
    flex-direction: column;
    gap: 0.5rem;
    align-items: flex-start;
    text-align: left;
  }

  .calculation-metadata {
    grid-template-columns: 1fr;
  }

  .journal-line {
    flex-direction: column;
    gap: 0.5rem;
    align-items: flex-start;
  }

  .projections-grid {
    grid-template-columns: 1fr;
  }

  .chart-area {
    height: 200px;
  }

  .modal-footer {
    flex-direction: column;
  }
}

/* Print Styles */
@media print {
  .page-header,
  .header-actions,
  .action-menu,
  .entry-actions,
  .view-options,
  .projection-controls {
    display: none !important;
  }

  .depreciation-detail-page {
    padding: 0;
    background: white;
  }

  .detail-content {
    gap: 1rem;
  }

  .asset-overview-section,
  .depreciation-details-section,
  .journal-entry-section,
  .historical-analysis-section,
  .projections-section {
    box-shadow: none;
    border: 1px solid #000;
    page-break-inside: avoid;
  }
}
</style>