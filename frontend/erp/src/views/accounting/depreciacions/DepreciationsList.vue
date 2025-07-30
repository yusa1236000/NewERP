<!-- frontend/erp/src/views/accounting/depreciacions/DepreciationsList.vue -->
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
            <span class="breadcrumb-current">Asset Depreciations</span>
          </div>
          <h1 class="page-title">
            <i class="fas fa-chart-line"></i>
            Asset Depreciations
          </h1>
          <p class="page-subtitle">Manage and track asset depreciation calculations across all periods</p>
        </div>
        <div class="header-actions">
          <button @click="calculateBulkDepreciation" class="btn btn-primary" :disabled="loading">
            <i class="fas fa-calculator"></i>
            Bulk Calculate
          </button>
          <button @click="exportDepreciations" class="btn btn-outline" :disabled="loading">
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

    <!-- Statistics Cards -->
    <div class="stats-section">
      <div class="stats-grid">
        <div class="stat-card primary">
          <div class="stat-icon">
            <i class="fas fa-list"></i>
          </div>
          <div class="stat-content">
            <div class="stat-value">{{ formatNumber(totalDepreciations) }}</div>
            <div class="stat-label">Total Records</div>
          </div>
        </div>
        <div class="stat-card success">
          <div class="stat-icon">
            <i class="fas fa-arrow-down"></i>
          </div>
          <div class="stat-content">
            <div class="stat-value">${{ formatCurrency(totalDepreciationAmount) }}</div>
            <div class="stat-label">Total Depreciation</div>
            <div class="stat-sublabel" v-if="displayCurrency !== baseCurrency">
              {{ displayCurrency }} ({{ baseCurrency }}: ${{ formatCurrency(totalDepreciationAmountBase) }})
            </div>
          </div>
        </div>
        <div class="stat-card warning">
          <div class="stat-icon">
            <i class="fas fa-accumulate"></i>
          </div>
          <div class="stat-content">
            <div class="stat-value">${{ formatCurrency(totalAccumulated) }}</div>
            <div class="stat-label">Total Accumulated</div>
            <div class="stat-sublabel" v-if="displayCurrency !== baseCurrency">
              {{ displayCurrency }} ({{ baseCurrency }}: ${{ formatCurrency(totalAccumulatedBase) }})
            </div>
          </div>
        </div>
        <div class="stat-card info">
          <div class="stat-icon">
            <i class="fas fa-money-bill-wave"></i>
          </div>
          <div class="stat-content">
            <div class="stat-value">${{ formatCurrency(totalRemainingValue) }}</div>
            <div class="stat-label">Total Remaining</div>
            <div class="stat-sublabel" v-if="displayCurrency !== baseCurrency">
              {{ displayCurrency }} ({{ baseCurrency }}: ${{ formatCurrency(totalRemainingValueBase) }})
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Controls Section -->
    <div class="controls-section">
      <div class="controls-card">
        <!-- Filters Row -->
        <div class="filters-row">
          <div class="filter-group">
            <label>Search</label>
            <div class="search-input">
              <i class="fas fa-search"></i>
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Search by asset name, code, or period..."
                @input="debouncedSearch"
              />
            </div>
          </div>
          
          <div class="filter-group">
            <label>Asset</label>
            <select v-model="filters.asset_id" @change="fetchDepreciations(1)">
              <option value="">All Assets</option>
              <option
                v-for="asset in assets"
                :key="asset.asset_id"
                :value="asset.asset_id"
              >
                {{ asset.name }} ({{ asset.asset_code }})
              </option>
            </select>
          </div>
          
          <div class="filter-group">
            <label>Period</label>
            <select v-model="filters.period_id" @change="fetchDepreciations(1)">
              <option value="">All Periods</option>
              <option
                v-for="period in periods"
                :key="period.period_id"
                :value="period.period_id"
              >
                {{ period.period_name }}
              </option>
            </select>
          </div>

          <div class="filter-group">
            <label>Currency</label>
            <select v-model="filters.currency" @change="fetchDepreciations(1)">
              <option value="">All Currencies</option>
              <option
                v-for="currency in availableCurrencies"
                :key="currency"
                :value="currency"
              >
                {{ currency }}
              </option>
            </select>
          </div>
        </div>

        <!-- Advanced Filters Row -->
        <div class="filters-row" v-if="showAdvancedFilters">
          <div class="filter-group">
            <label>Date From</label>
            <input
              v-model="filters.from_date"
              type="date"
              @change="fetchDepreciations(1)"
            />
          </div>
          
          <div class="filter-group">
            <label>Date To</label>
            <input
              v-model="filters.to_date"
              type="date"
              @change="fetchDepreciations(1)"
            />
          </div>

          <div class="filter-group">
            <label>Display Currency</label>
            <select v-model="displayCurrency" @change="fetchDepreciations(1)">
              <option
                v-for="currency in systemCurrencies"
                :key="currency.code"
                :value="currency.code"
              >
                {{ currency.code }} - {{ currency.name }}
              </option>
            </select>
          </div>

          <div class="filter-group">
            <label>Conversion Date</label>
            <input
              v-model="conversionDate"
              type="date"
              @change="fetchDepreciations(1)"
            />
          </div>
        </div>

        <!-- Controls Row -->
        <div class="controls-row">
          <div class="view-controls">
            <button
              @click="toggleAdvancedFilters"
              class="btn btn-link"
            >
              <i :class="showAdvancedFilters ? 'fas fa-chevron-up' : 'fas fa-chevron-down'"></i>
              {{ showAdvancedFilters ? 'Hide' : 'Show' }} Advanced Filters
            </button>
          </div>
          
          <div class="view-options">
            <button
              @click="viewMode = 'card'"
              :class="['view-btn', { active: viewMode === 'card' }]"
              title="Card View"
            >
              <i class="fas fa-th-large"></i>
            </button>
            <button
              @click="viewMode = 'table'"
              :class="['view-btn', { active: viewMode === 'table' }]"
              title="Table View"
            >
              <i class="fas fa-list"></i>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Content Section -->
    <div class="content-section">
      <div class="content-card">
        <div class="content-header">
          <h2>Depreciation Records</h2>
          <div class="content-meta">
            <span>{{ pagination.from }}-{{ pagination.to }} of {{ pagination.total }} records</span>
            <select v-model="pagination.per_page" @change="fetchDepreciations(1)">
              <option value="15">15 per page</option>
              <option value="25">25 per page</option>
              <option value="50">50 per page</option>
              <option value="100">100 per page</option>
            </select>
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="loading-state">
          <div class="loading-spinner"></div>
          <p>Loading depreciation records...</p>
        </div>

        <!-- Empty State -->
        <div v-else-if="!depreciations.length" class="empty-state">
          <div class="empty-icon">
            <i class="fas fa-chart-line"></i>
          </div>
          <h3>No Depreciation Records Found</h3>
          <p>{{ searchQuery || Object.values(filters).some(f => f) ? 
              'No records match your current filters.' : 
              'Start by calculating depreciation for your fixed assets.' }}
          </p>
          <button v-if="!searchQuery && !Object.values(filters).some(f => f)" 
                  @click="calculateBulkDepreciation" 
                  class="btn btn-primary">
            <i class="fas fa-calculator"></i>
            Calculate Depreciation
          </button>
        </div>

        <!-- Cards Grid -->
        <div v-else-if="viewMode === 'card'" class="cards-grid">
          <div
            v-for="depreciation in depreciations"
            :key="depreciation.depreciation_id"
            @click="viewDepreciation(depreciation.depreciation_id)"
            class="depreciation-card"
          >
            <div class="card-header">
              <div class="asset-info">
                <h3>{{ depreciation.fixed_asset?.name }}</h3>
                <span class="asset-code">{{ depreciation.fixed_asset?.asset_code }}</span>
                <span class="currency-badge">{{ depreciation.currency }}</span>
              </div>
              <div class="card-actions" @click.stop>
                <button
                  @click="viewSchedule(depreciation.asset_id)"
                  class="action-btn schedule"
                  title="View Schedule"
                >
                  <i class="fas fa-calendar"></i>
                </button>
                <button
                  v-if="depreciation.journal_entry_id"
                  @click="viewJournalEntry(depreciation.journal_entry_id)"
                  class="action-btn journal"
                  title="View Journal Entry"
                >
                  <i class="fas fa-book"></i>
                </button>
                <button
                  @click="deleteDepreciation(depreciation)"
                  class="action-btn delete"
                  title="Delete"
                >
                  <i class="fas fa-trash"></i>
                </button>
              </div>
            </div>

            <div class="info-grid">
              <div class="info-item">
                <span class="label">Period</span>
                <span class="value">{{ depreciation.accounting_period?.period_name }}</span>
              </div>
              <div class="info-item">
                <span class="label">Date</span>
                <span class="value">{{ formatDate(depreciation.depreciation_date) }}</span>
              </div>
              <div class="info-item">
                <span class="label">Depreciation Amount</span>
                <span class="value amount">
                  {{ displayCurrency === depreciation.currency ? 
                     `${depreciation.currency} ${formatCurrency(depreciation.depreciation_amount)}` :
                     `${displayCurrency} ${formatCurrency(depreciation.converted_amount || depreciation.depreciation_amount)}` }}
                </span>
                <span v-if="depreciation.converted_amount && displayCurrency !== depreciation.currency" 
                      class="original-amount">
                  ({{ depreciation.currency }} {{ formatCurrency(depreciation.depreciation_amount) }})
                </span>
              </div>
              <div class="info-item">
                <span class="label">Accumulated</span>
                <span class="value">
                  {{ displayCurrency === depreciation.currency ? 
                     `${depreciation.currency} ${formatCurrency(depreciation.accumulated_depreciation)}` :
                     `${displayCurrency} ${formatCurrency(depreciation.converted_accumulated || depreciation.accumulated_depreciation)}` }}
                </span>
              </div>
              <div class="info-item">
                <span class="label">Remaining Value</span>
                <span class="value remaining">
                  {{ displayCurrency === depreciation.currency ? 
                     `${depreciation.currency} ${formatCurrency(depreciation.remaining_value)}` :
                     `${displayCurrency} ${formatCurrency(depreciation.converted_remaining || depreciation.remaining_value)}` }}
                </span>
              </div>
              <div class="info-item" v-if="depreciation.exchange_rate && displayCurrency !== depreciation.currency">
                <span class="label">Exchange Rate</span>
                <span class="value exchange-rate">
                  1 {{ depreciation.currency }} = {{ formatNumber(depreciation.exchange_rate, 4) }} {{ displayCurrency }}
                </span>
              </div>
            </div>

            <div class="card-footer">
              <div class="progress-section">
                <div class="progress-label">
                  <span>Depreciation Progress</span>
                  <span>{{ getDepreciationPercentage(depreciation) }}%</span>
                </div>
                <div class="progress-bar">
                  <div
                    class="progress-fill"
                    :style="{ width: getDepreciationPercentage(depreciation) + '%' }"
                  ></div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Table View -->
        <div v-else class="table-container">
          <table class="depreciations-table">
            <thead>
              <tr>
                <th>Asset</th>
                <th>Period</th>
                <th>Date</th>
                <th>Currency</th>
                <th>Depreciation Amount</th>
                <th>Accumulated</th>
                <th>Remaining Value</th>
                <th v-if="displayCurrency !== baseCurrency">{{ displayCurrency }} Rate</th>
                <th>Progress</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="depreciation in depreciations"
                :key="depreciation.depreciation_id"
                @click="viewDepreciation(depreciation.depreciation_id)"
                class="table-row"
              >
                <td>
                  <div class="asset-info">
                    <strong>{{ depreciation.fixed_asset?.name }}</strong>
                    <small>{{ depreciation.fixed_asset?.asset_code }}</small>
                  </div>
                </td>
                <td>{{ depreciation.accounting_period?.period_name }}</td>
                <td>{{ formatDate(depreciation.depreciation_date) }}</td>
                <td>
                  <span class="currency-badge">{{ depreciation.currency }}</span>
                </td>
                <td class="amount-cell">
                  <div class="amount-wrapper">
                    <span class="primary-amount">
                      {{ displayCurrency === depreciation.currency ? 
                         `${depreciation.currency} ${formatCurrency(depreciation.depreciation_amount)}` :
                         `${displayCurrency} ${formatCurrency(depreciation.converted_amount || depreciation.depreciation_amount)}` }}
                    </span>
                    <span v-if="depreciation.converted_amount && displayCurrency !== depreciation.currency" 
                          class="original-amount">
                      {{ depreciation.currency }} {{ formatCurrency(depreciation.depreciation_amount) }}
                    </span>
                  </div>
                </td>
                <td class="amount-cell">
                  {{ displayCurrency === depreciation.currency ? 
                     `${depreciation.currency} ${formatCurrency(depreciation.accumulated_depreciation)}` :
                     `${displayCurrency} ${formatCurrency(depreciation.converted_accumulated || depreciation.accumulated_depreciation)}` }}
                </td>
                <td class="amount-cell remaining">
                  {{ displayCurrency === depreciation.currency ? 
                     `${depreciation.currency} ${formatCurrency(depreciation.remaining_value)}` :
                     `${displayCurrency} ${formatCurrency(depreciation.converted_remaining || depreciation.remaining_value)}` }}
                </td>
                <td v-if="displayCurrency !== baseCurrency" class="rate-cell">
                  <span v-if="depreciation.exchange_rate">
                    {{ formatNumber(depreciation.exchange_rate, 4) }}
                  </span>
                  <span v-else>-</span>
                </td>
                <td>
                  <div class="progress-mini">
                    <div class="progress-value">{{ getDepreciationPercentage(depreciation) }}%</div>
                    <div class="progress-bar-mini">
                      <div
                        class="progress-fill-mini"
                        :style="{ width: getDepreciationPercentage(depreciation) + '%' }"
                      ></div>
                    </div>
                  </div>
                </td>
                <td class="actions-cell" @click.stop>
                  <div class="action-buttons">
                    <button
                      @click="viewSchedule(depreciation.asset_id)"
                      class="btn-mini schedule"
                      title="View Schedule"
                    >
                      <i class="fas fa-calendar"></i>
                    </button>
                    <button
                      v-if="depreciation.journal_entry_id"
                      @click="viewJournalEntry(depreciation.journal_entry_id)"
                      class="btn-mini journal"
                      title="View Journal Entry"
                    >
                      <i class="fas fa-book"></i>
                    </button>
                    <button
                      @click="deleteDepreciation(depreciation)"
                      class="btn-mini delete"
                      title="Delete"
                    >
                      <i class="fas fa-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="pagination.last_page > 1" class="pagination-section">
          <div class="pagination">
            <button
              @click="fetchDepreciations(1)"
              :disabled="pagination.current_page === 1"
              class="pagination-btn"
            >
              <i class="fas fa-angle-double-left"></i>
            </button>
            <button
              @click="fetchDepreciations(pagination.current_page - 1)"
              :disabled="pagination.current_page === 1"
              class="pagination-btn"
            >
              <i class="fas fa-angle-left"></i>
            </button>
            
            <button
              v-for="page in paginationPages"
              :key="page"
              @click="fetchDepreciations(page)"
              :class="['pagination-btn', { active: page === pagination.current_page }]"
            >
              {{ page }}
            </button>
            
            <button
              @click="fetchDepreciations(pagination.current_page + 1)"
              :disabled="pagination.current_page === pagination.last_page"
              class="pagination-btn"
            >
              <i class="fas fa-angle-right"></i>
            </button>
            <button
              @click="fetchDepreciations(pagination.last_page)"
              :disabled="pagination.current_page === pagination.last_page"
              class="pagination-btn"
            >
              <i class="fas fa-angle-double-right"></i>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Bulk Calculation Modal -->
    <div v-if="showBulkModal" class="modal-overlay" @click="closeBulkModal">
      <div class="modal-container" @click.stop>
        <div class="modal-header">
          <h3>
            <i class="fas fa-calculator"></i>
            Bulk Depreciation Calculation
          </h3>
          <button @click="closeBulkModal" class="modal-close">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <div class="bulk-form">
            <div class="form-group">
              <label>Accounting Period *</label>
              <select v-model="bulkCalculation.period_id" required>
                <option value="">Select Period</option>
                <option
                  v-for="period in periods"
                  :key="period.period_id"
                  :value="period.period_id"
                >
                  {{ period.period_name }} ({{ formatDate(period.start_date) }} - {{ formatDate(period.end_date) }})
                </option>
              </select>
            </div>

            <div class="form-group">
              <label>Depreciation Date</label>
              <input
                v-model="bulkCalculation.depreciation_date"
                type="date"
                :min="selectedPeriod?.start_date"
                :max="selectedPeriod?.end_date"
              />
            </div>

            <div class="form-group">
              <label>Assets to Calculate</label>
              <div class="checkbox-group">
                <label class="checkbox-item">
                  <input
                    type="checkbox"
                    :checked="bulkCalculation.asset_ids.length === availableAssets.length"
                    @change="toggleAllAssets"
                  />
                  <span>Select All ({{ availableAssets.length }} assets)</span>
                </label>
              </div>
              <div class="asset-selection">
                <div
                  v-for="asset in availableAssets"
                  :key="asset.asset_id"
                  class="checkbox-item"
                >
                  <label>
                    <input
                      type="checkbox"
                      :value="asset.asset_id"
                      v-model="bulkCalculation.asset_ids"
                    />
                    <span class="asset-name">
                      {{ asset.name }} ({{ asset.asset_code }})
                      <small>{{ asset.category }} - {{ asset.currency }}</small>
                    </span>
                  </label>
                </div>
              </div>
            </div>

            <div class="form-group">
              <div class="checkbox-item">
                <label>
                  <input
                    type="checkbox"
                    v-model="bulkCalculation.create_journal_entries"
                  />
                  <span>Create Journal Entries</span>
                </label>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button @click="closeBulkModal" class="btn btn-secondary">Cancel</button>
          <button
            @click="performBulkCalculation"
            class="btn btn-primary"
            :disabled="!bulkCalculation.period_id || !bulkCalculation.asset_ids.length || calculating"
          >
            <i v-if="calculating" class="fas fa-spinner fa-spin"></i>
            <i v-else class="fas fa-calculator"></i>
            {{ calculating ? 'Calculating...' : 'Calculate Depreciation' }}
          </button>
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
              <span>{{ selectedDepreciation?.fixed_asset?.name }}</span>
            </div>
            <div class="detail-row">
              <span>Period:</span>
              <span>{{ selectedDepreciation?.accounting_period?.period_name }}</span>
            </div>
            <div class="detail-row">
              <span>Amount:</span>
              <span>${{ formatCurrency(selectedDepreciation?.depreciation_amount) }}</span>
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
            <i class="fas fa-trash"></i>
            {{ deleting ? 'Deleting...' : 'Delete' }}
          </button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

export default {
  name: 'DepreciationsList',
  components: {
  },
  setup() {
    const router = useRouter()
    
    // Reactive state
    const loading = ref(false)
    const deleting = ref(false)
    const calculating = ref(false)
    const depreciations = ref([])
    const assets = ref([])
    const periods = ref([])
    const systemCurrencies = ref([])
    const viewMode = ref('card')
    const searchQuery = ref('')
    const showDeleteModal = ref(false)
    const showBulkModal = ref(false)
    const selectedDepreciation = ref(null)
    const showAdvancedFilters = ref(false)
    
    // Configuration
    const baseCurrency = ref('USD')
    const displayCurrency = ref('USD')
    const conversionDate = ref(new Date().toISOString().split('T')[0])
    
    // Filters
    const filters = ref({
      asset_id: '',
      period_id: '',
      currency: '',
      from_date: '',
      to_date: ''
    })
    
    // Pagination
    const pagination = ref({
      current_page: 1,
      last_page: 1,
      per_page: 15,
      total: 0,
      from: 0,
      to: 0
    })

    // Bulk calculation
    const bulkCalculation = ref({
      period_id: '',
      depreciation_date: '',
      asset_ids: [],
      create_journal_entries: false
    })

    // Computed properties
    const totalDepreciations = computed(() => pagination.value.total)
    
    const totalDepreciationAmount = computed(() => {
      return depreciations.value.reduce((sum, dep) => {
        const amount = displayCurrency.value === dep.currency ? 
          dep.depreciation_amount : 
          (dep.converted_amount || dep.depreciation_amount)
        return sum + (amount || 0)
      }, 0)
    })

    const totalDepreciationAmountBase = computed(() => {
      return depreciations.value.reduce((sum, dep) => sum + (dep.base_currency_depreciation_amount || dep.depreciation_amount || 0), 0)
    })
    
    const totalAccumulated = computed(() => {
      return depreciations.value.reduce((sum, dep) => {
        const amount = displayCurrency.value === dep.currency ? 
          dep.accumulated_depreciation : 
          (dep.converted_accumulated || dep.accumulated_depreciation)
        return sum + (amount || 0)
      }, 0)
    })

    const totalAccumulatedBase = computed(() => {
      return depreciations.value.reduce((sum, dep) => sum + (dep.base_currency_accumulated_depreciation || dep.accumulated_depreciation || 0), 0)
    })

    const totalRemainingValue = computed(() => {
      return depreciations.value.reduce((sum, dep) => {
        const amount = displayCurrency.value === dep.currency ? 
          dep.remaining_value : 
          (dep.converted_remaining || dep.remaining_value)
        return sum + (amount || 0)
      }, 0)
    })

    const totalRemainingValueBase = computed(() => {
      return depreciations.value.reduce((sum, dep) => sum + (dep.base_currency_remaining_value || dep.remaining_value || 0), 0)
    })

    const availableCurrencies = computed(() => {
      const currencies = new Set(assets.value.map(asset => asset.currency).filter(Boolean))
      return Array.from(currencies).sort()
    })

    const availableAssets = computed(() => {
      if (!bulkCalculation.value.period_id) return []
      // Filter assets that don't already have depreciation for the selected period
      return assets.value.filter(asset => 
        asset.status === 'Active' && 
        !asset.depreciations?.some(dep => dep.period_id === bulkCalculation.value.period_id)
      )
    })

    const selectedPeriod = computed(() => {
      return periods.value.find(p => p.period_id === bulkCalculation.value.period_id)
    })

    const paginationPages = computed(() => {
      const pages = []
      const current = pagination.value.current_page
      const last = pagination.value.last_page
      
      for (let i = Math.max(1, current - 2); i <= Math.min(last, current + 2); i++) {
        pages.push(i)
      }
      
      return pages
    })

    // Methods
    const fetchDepreciations = async (page = 1) => {
      try {
        loading.value = true
        const params = {
          page,
          per_page: pagination.value.per_page,
          ...filters.value
        }
        
        if (searchQuery.value) {
          params.search = searchQuery.value
        }

        if (displayCurrency.value !== baseCurrency.value) {
          params.display_currency = displayCurrency.value
          params.conversion_date = conversionDate.value
        }

        const response = await axios.get('/accounting/asset-depreciations', { params })
        
        depreciations.value = response.data.data
        pagination.value = {
          current_page: response.data.current_page,
          last_page: response.data.last_page,
          per_page: response.data.per_page,
          total: response.data.total,
          from: response.data.from,
          to: response.data.to
        }
      } catch (error) {
        console.error('Error fetching depreciations:', error)
        // Handle error
      } finally {
        loading.value = false
      }
    }

    const fetchAssets = async () => {
      try {
        const response = await axios.get('/accounting/fixed-assets')
        assets.value = response.data.data
      } catch (error) {
        console.error('Error fetching assets:', error)
      }
    }

    const fetchPeriods = async () => {
      try {
        const response = await axios.get('/accounting/accounting-periods')
        periods.value = response.data.data
      } catch (error) {
        console.error('Error fetching periods:', error)
      }
    }

    const fetchSystemCurrencies = async () => {
      try {
        const response = await axios.get('/accounting/system-currencies')
        systemCurrencies.value = response.data.data
        if (systemCurrencies.value.length > 0) {
          const defaultCurrency = systemCurrencies.value.find(c => c.is_base) || systemCurrencies.value[0]
          baseCurrency.value = defaultCurrency.code
          displayCurrency.value = defaultCurrency.code
        }
      } catch (error) {
        console.error('Error fetching currencies:', error)
      }
    }

    const refreshData = () => {
      fetchDepreciations(pagination.value.current_page)
      fetchAssets()
      fetchPeriods()
    }

    const calculateBulkDepreciation = () => {
      showBulkModal.value = true
      if (selectedPeriod.value) {
        bulkCalculation.value.depreciation_date = selectedPeriod.value.end_date
      }
    }

    const performBulkCalculation = async () => {
      try {
        calculating.value = true
        
        const payload = {
          period_id: bulkCalculation.value.period_id,
          depreciation_date: bulkCalculation.value.depreciation_date,
          asset_ids: bulkCalculation.value.asset_ids,
          create_journal_entries: bulkCalculation.value.create_journal_entries
        }

        const response = await axios.post('/accounting/asset-depreciations/calculate-bulk', payload)
        
        // Show success message
        console.log('Bulk calculation completed:', response.data)
        
        closeBulkModal()
        refreshData()
      } catch (error) {
        console.error('Error performing bulk calculation:', error)
        // Handle error
      } finally {
        calculating.value = false
      }
    }

    const toggleAllAssets = () => {
      if (bulkCalculation.value.asset_ids.length === availableAssets.value.length) {
        bulkCalculation.value.asset_ids = []
      } else {
        bulkCalculation.value.asset_ids = availableAssets.value.map(asset => asset.asset_id)
      }
    }

    const closeBulkModal = () => {
      showBulkModal.value = false
      bulkCalculation.value = {
        period_id: '',
        depreciation_date: '',
        asset_ids: [],
        create_journal_entries: false
      }
    }

    const viewDepreciation = (depreciationId) => {
      router.push(`/accounting/depreciations/${depreciationId}`)
    }

    const viewSchedule = (assetId) => {
      router.push(`/accounting/depreciations/schedule/${assetId}`)
    }

    const viewJournalEntry = (journalEntryId) => {
      router.push(`/accounting/journal-entries/${journalEntryId}`)
    }

    const deleteDepreciation = (depreciation) => {
      selectedDepreciation.value = depreciation
      showDeleteModal.value = true
    }

    const confirmDelete = async () => {
      try {
        deleting.value = true
        await axios.delete(`/accounting/asset-depreciations/${selectedDepreciation.value.depreciation_id}`)
        
        closeDeleteModal()
        refreshData()
      } catch (error) {
        console.error('Error deleting depreciation:', error)
        // Handle error
      } finally {
        deleting.value = false
      }
    }

    const closeDeleteModal = () => {
      showDeleteModal.value = false
      selectedDepreciation.value = null
    }

    const exportDepreciations = async () => {
      try {
        const params = {
          ...filters.value,
          format: 'excel'
        }
        
        if (searchQuery.value) {
          params.search = searchQuery.value
        }

        if (displayCurrency.value !== baseCurrency.value) {
          params.display_currency = displayCurrency.value
          params.conversion_date = conversionDate.value
        }

        const response = await axios.get('/accounting/asset-depreciations/export', { 
          params,
          responseType: 'blob'
        })
        
        const blob = new Blob([response.data])
        const url = window.URL.createObjectURL(blob)
        const link = document.createElement('a')
        link.href = url
        link.download = `asset-depreciations-${new Date().toISOString().split('T')[0]}.xlsx`
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
        window.URL.revokeObjectURL(url)
      } catch (error) {
        console.error('Error exporting depreciations:', error)
      }
    }

    const toggleAdvancedFilters = () => {
      showAdvancedFilters.value = !showAdvancedFilters.value
    }

    const debouncedSearch = (() => {
      let timeout
      return () => {
        clearTimeout(timeout)
        timeout = setTimeout(() => {
          fetchDepreciations(1)
        }, 500)
      }
    })()

    const getDepreciationPercentage = (depreciation) => {
      if (!depreciation.fixed_asset?.acquisition_cost) return 0
      const percentage = (depreciation.accumulated_depreciation / depreciation.fixed_asset.acquisition_cost) * 100
      return Math.min(100, Math.round(percentage))
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

    // Lifecycle
    onMounted(() => {
      fetchSystemCurrencies()
      fetchAssets()
      fetchPeriods()
      fetchDepreciations()
    })

    return {
      loading,
      deleting,
      calculating,
      depreciations,
      assets,
      periods,
      systemCurrencies,
      viewMode,
      searchQuery,
      showDeleteModal,
      showBulkModal,
      selectedDepreciation,
      showAdvancedFilters,
      baseCurrency,
      displayCurrency,
      conversionDate,
      filters,
      pagination,
      bulkCalculation,
      totalDepreciations,
      totalDepreciationAmount,
      totalDepreciationAmountBase,
      totalAccumulated,
      totalAccumulatedBase,
      totalRemainingValue,
      totalRemainingValueBase,
      availableCurrencies,
      availableAssets,
      selectedPeriod,
      paginationPages,
      fetchDepreciations,
      refreshData,
      calculateBulkDepreciation,
      performBulkCalculation,
      toggleAllAssets,
      closeBulkModal,
      viewDepreciation,
      viewSchedule,
      viewJournalEntry,
      deleteDepreciation,
      confirmDelete,
      closeDeleteModal,
      exportDepreciations,
      toggleAdvancedFilters,
      debouncedSearch,
      getDepreciationPercentage,
      formatCurrency,
      formatNumber,
      formatDate
    }
  }
}
</script>

<style scoped>
/* Base styles remain the same as original CSS */
.depreciation-page {
  padding: 2rem;
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
  min-height: 100vh;
}

/* Page Header - keep existing styles */
.page-header {
  margin-bottom: 2rem;
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
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

.page-subtitle {
  color: #64748b;
  font-size: 1.1rem;
}

.header-actions {
  display: flex;
  gap: 1rem;
  align-items: center;
}

/* Statistics Section - enhanced for multi-currency */
.stats-section {
  margin-bottom: 2rem;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 1.5rem;
}

.stat-card {
  background: white;
  border-radius: 16px;
  padding: 1.5rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
  border: 1px solid #e2e8f0;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.stat-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
}

.stat-icon {
  width: 60px;
  height: 60px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  color: white;
}

.stat-card.primary .stat-icon {
  background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
}

.stat-card.success .stat-icon {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.stat-card.warning .stat-icon {
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

.stat-card.info .stat-icon {
  background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
}

.stat-content {
  flex: 1;
}

.stat-value {
  font-size: 1.8rem;
  font-weight: 700;
  color: #1f2937;
  margin-bottom: 0.25rem;
}

.stat-label {
  color: #64748b;
  font-size: 0.9rem;
  font-weight: 500;
}

.stat-sublabel {
  color: #9ca3af;
  font-size: 0.8rem;
  margin-top: 0.25rem;
}

/* Controls Section - enhanced with currency controls */
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

.filters-row:last-child {
  margin-bottom: 0;
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

.search-input {
  position: relative;
}

.search-input i {
  position: absolute;
  left: 1rem;
  top: 50%;
  transform: translateY(-50%);
  color: #9ca3af;
}

.search-input input {
  width: 100%;
  padding: 0.75rem 1rem 0.75rem 2.5rem;
  border: 2px solid #e5e7eb;
  border-radius: 10px;
  font-size: 0.9rem;
  transition: border-color 0.3s ease;
}

.search-input input:focus {
  outline: none;
  border-color: #6366f1;
}

.filter-group select,
.filter-group input[type="date"] {
  padding: 0.75rem 1rem;
  border: 2px solid #e5e7eb;
  border-radius: 10px;
  font-size: 0.9rem;
  transition: border-color 0.3s ease;
}

.filter-group select:focus,
.filter-group input[type="date"]:focus {
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
  gap: 1rem;
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

/* Content Section - keep existing styles but add currency support */
.content-section {
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
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 2px solid #f1f5f9;
}

.content-header h2 {
  font-size: 1.5rem;
  font-weight: 600;
  color: #1f2937;
}

.content-meta {
  display: flex;
  align-items: center;
  gap: 1rem;
  color: #64748b;
  font-size: 0.9rem;
}

.content-meta select {
  padding: 0.5rem;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  font-size: 0.9rem;
}

/* Loading and Empty States - keep existing */
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
  border-radius: 20px;
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

/* Cards Grid - enhanced with currency display */
.cards-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
  gap: 1.5rem;
}

.depreciation-card {
  border: 2px solid #e2e8f0;
  border-radius: 16px;
  padding: 1.5rem;
  cursor: pointer;
  transition: all 0.3s ease;
  background: #fafbfc;
}

.depreciation-card:hover {
  transform: translateY(-5px);
  border-color: #6366f1;
  box-shadow: 0 10px 25px rgba(99, 102, 241, 0.15);
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1rem;
}

.asset-info h3 {
  font-size: 1.1rem;
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 0.25rem;
}

.asset-code {
  background: #e0e7ff;
  color: #5b21b6;
  padding: 0.25rem 0.5rem;
  border-radius: 6px;
  font-size: 0.75rem;
  font-weight: 500;
  margin-right: 0.5rem;
}

.currency-badge {
  background: #fef3c7;
  color: #92400e;
  padding: 0.25rem 0.5rem;
  border-radius: 6px;
  font-size: 0.75rem;
  font-weight: 500;
}

.card-actions {
  display: flex;
  gap: 0.5rem;
}

.action-btn {
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

.action-btn.schedule {
  background: #dbeafe;
  color: #1d4ed8;
}

.action-btn.journal {
  background: #fef3c7;
  color: #d97706;
}

.action-btn.delete {
  background: #fee2e2;
  color: #dc2626;
}

.action-btn:hover {
  transform: scale(1.1);
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem;
  margin-bottom: 1rem;
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.info-item .label {
  font-size: 0.8rem;
  color: #64748b;
  font-weight: 500;
}

.info-item .value {
  font-size: 0.9rem;
  color: #1f2937;
  font-weight: 600;
}

.info-item .value.amount {
  color: #dc2626;
}

.info-item .value.remaining {
  color: #059669;
}

.original-amount {
  font-size: 0.75rem;
  color: #6b7280;
  font-style: italic;
}

.exchange-rate {
  font-size: 0.8rem;
  color: #6366f1;
  font-weight: 500;
}

.card-footer {
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid #e2e8f0;
}

.progress-section {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.progress-label {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.8rem;
  color: #64748b;
  font-weight: 500;
}

.progress-bar {
  width: 100%;
  height: 8px;
  background: #f1f5f9;
  border-radius: 4px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #10b981 0%, #059669 100%);
  transition: width 0.3s ease;
}

/* Table View - enhanced with currency columns */
.table-container {
  overflow-x: auto;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
}

.depreciations-table {
  width: 100%;
  border-collapse: collapse;
  background: white;
}

.depreciations-table th {
  background: #f8fafc;
  padding: 1rem;
  text-align: left;
  font-weight: 600;
  color: #374151;
  border-bottom: 2px solid #e2e8f0;
  font-size: 0.9rem;
}

.depreciations-table td {
  padding: 1rem;
  border-bottom: 1px solid #f1f5f9;
  font-size: 0.9rem;
}

.table-row {
  cursor: pointer;
  transition: background-color 0.2s ease;
}

.table-row:hover {
  background: #f8fafc;
}

.asset-info strong {
  color: #1f2937;
  font-weight: 600;
}

.asset-info small {
  color: #64748b;
  font-size: 0.8rem;
  display: block;
}

.amount-cell {
  text-align: right;
  font-weight: 600;
}

.amount-wrapper {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 0.25rem;
}

.primary-amount {
  font-weight: 600;
  color: #1f2937;
}

.amount-cell.remaining {
  color: #059669;
}

.rate-cell {
  text-align: center;
  font-family: monospace;
  font-size: 0.8rem;
}

.progress-mini {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.25rem;
}

.progress-value {
  font-size: 0.8rem;
  font-weight: 600;
  color: #374151;
}

.progress-bar-mini {
  width: 60px;
  height: 4px;
  background: #f1f5f9;
  border-radius: 2px;
  overflow: hidden;
}

.progress-fill-mini {
  height: 100%;
  background: linear-gradient(90deg, #10b981 0%, #059669 100%);
  transition: width 0.3s ease;
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

.btn-mini.schedule {
  background: #dbeafe;
  color: #1d4ed8;
}

.btn-mini.journal {
  background: #fef3c7;
  color: #d97706;
}

.btn-mini.delete {
  background: #fee2e2;
  color: #dc2626;
}

.btn-mini:hover {
  transform: scale(1.1);
}

/* Pagination - keep existing */
.pagination-section {
  display: flex;
  justify-content: center;
  margin-top: 2rem;
  padding-top: 2rem;
  border-top: 1px solid #e2e8f0;
}

.pagination {
  display: flex;
  gap: 0.5rem;
  align-items: center;
}

.pagination-btn {
  min-width: 40px;
  height: 40px;
  border: 2px solid #e2e8f0;
  background: white;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.3s ease;
  font-weight: 500;
  color: #374151;
}

.pagination-btn:hover:not(:disabled) {
  border-color: #6366f1;
  color: #6366f1;
}

.pagination-btn.active {
  border-color: #6366f1;
  background: #6366f1;
  color: white;
}

.pagination-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Modals - enhanced for bulk calculation */
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

.bulk-form {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-group label {
  font-size: 0.9rem;
  font-weight: 500;
  color: #374151;
}

.form-group select,
.form-group input {
  padding: 0.75rem;
  border: 2px solid #e5e7eb;
  border-radius: 10px;
  font-size: 0.9rem;
  transition: border-color 0.3s ease;
}

.form-group select:focus,
.form-group input:focus {
  outline: none;
  border-color: #6366f1;
}

.checkbox-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.checkbox-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem;
  border-radius: 8px;
  transition: background-color 0.2s ease;
}

.checkbox-item:hover {
  background: #f8fafc;
}

.checkbox-item input[type="checkbox"] {
  width: 16px;
  height: 16px;
  accent-color: #6366f1;
}

.asset-selection {
  max-height: 200px;
  overflow-y: auto;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 0.5rem;
}

.asset-name {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.asset-name small {
  color: #6b7280;
  font-size: 0.8rem;
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

/* Button Styles */
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

.btn-link {
  background: none;
  color: #6366f1;
  border: none;
  padding: 0.5rem;
  font-size: 0.9rem;
}

.btn-link:hover {
  color: #5b21b6;
  text-decoration: underline;
}

.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  transform: none !important;
  box-shadow: none !important;
}

/* Responsive Design */
@media (max-width: 768px) {
  .depreciation-page {
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

  .stats-grid {
    grid-template-columns: 1fr;
  }

  .filters-row {
    grid-template-columns: 1fr;
  }

  .controls-row {
    flex-direction: column;
    gap: 1rem;
    align-items: stretch;
  }

  .cards-grid {
    grid-template-columns: 1fr;
  }

  .info-grid {
    grid-template-columns: 1fr;
  }

  .table-container {
    font-size: 0.8rem;
  }

  .modal-overlay {
    padding: 1rem;
  }

  .modal-footer {
    flex-direction: column;
  }
}
</style>