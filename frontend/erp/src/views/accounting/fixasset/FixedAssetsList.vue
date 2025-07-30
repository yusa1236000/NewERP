<template>
  <AppLayout>
    <div class="fixed-assets-page">
      <!-- Page Header -->
      <div class="page-header">
        <div class="header-content">
          <div class="title-section">
            <h1>
              <i class="fas fa-cubes"></i>
              Fixed Assets
            </h1>
            <p class="page-subtitle">Manage and track your organization's fixed assets</p>
          </div>
          <div class="header-actions">
            <button @click="generateReport" class="btn btn-outline">
              <i class="fas fa-chart-line"></i>
              Generate Report
            </button>
            <button @click="createAsset" class="btn btn-primary">
              <i class="fas fa-plus"></i>
              Add Asset
            </button>
          </div>
        </div>
      </div>

      <!-- Stats Cards -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon">
            <i class="fas fa-cubes"></i>
          </div>
          <div class="stat-content">
            <div class="stat-value">{{ stats.totalAssets }}</div>
            <div class="stat-label">Total Assets</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon active">
            <i class="fas fa-check-circle"></i>
          </div>
          <div class="stat-content">
            <div class="stat-value">{{ stats.activeAssets }}</div>
            <div class="stat-label">Active Assets</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon value">
            <i class="fas fa-dollar-sign"></i>
          </div>
          <div class="stat-content">
            <div class="stat-value">
              {{ selectedDisplayCurrency }} {{ formatNumber(stats.totalValue) }}
            </div>
            <div class="stat-label">Total Value</div>
            <div v-if="currencyConversion.isConverted" class="stat-conversion">
              Original: {{ formatCurrencyValue(stats.originalTotalValue) }}
            </div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon depreciation">
            <i class="fas fa-chart-line-down"></i>
          </div>
          <div class="stat-content">
            <div class="stat-value">
              {{ selectedDisplayCurrency }} {{ formatNumber(stats.totalDepreciation) }}
            </div>
            <div class="stat-label">Total Depreciation</div>
            <div v-if="currencyConversion.isConverted" class="stat-conversion">
              Original: {{ formatCurrencyValue(stats.originalTotalDepreciation) }}
            </div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon categories">
            <i class="fas fa-tags"></i>
          </div>
          <div class="stat-content">
            <div class="stat-value">{{ stats.categoriesCount }}</div>
            <div class="stat-label">Categories</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon currencies">
            <i class="fas fa-coins"></i>
          </div>
          <div class="stat-content">
            <div class="stat-value">{{ stats.currenciesCount }}</div>
            <div class="stat-label">Currencies</div>
          </div>
        </div>
      </div>

      <!-- Filters and Search -->
      <div class="controls-section">
        <div class="search-section">
          <div class="search-input-group">
            <i class="fas fa-search"></i>
            <input
              v-model="searchQuery"
              @input="debounceSearch"
              type="text"
              placeholder="Search assets by name, code, or category..."
              class="search-input"
            />
          </div>
        </div>
        
        <div class="filters-section">
          <div class="filter-group">
            <select v-model="filters.category" @change="applyFilters" class="filter-select">
              <option value="">All Categories</option>
              <option v-for="category in categories" :key="category" :value="category">
                {{ category }}
              </option>
            </select>
          </div>
          
          <div class="filter-group">
            <select v-model="filters.status" @change="applyFilters" class="filter-select">
              <option value="">All Status</option>
              <option value="Active">Active</option>
              <option value="Inactive">Inactive</option>
              <option value="Disposed">Disposed</option>
              <option value="Under Maintenance">Under Maintenance</option>
            </select>
          </div>

          <div class="filter-group">
            <select v-model="filters.currency" @change="applyFilters" class="filter-select">
              <option value="">All Currencies</option>
              <option v-for="currency in availableCurrencies" :key="currency" :value="currency">
                {{ currency }}
              </option>
            </select>
          </div>

          <div class="filter-group">
            <label class="filter-label">Display Currency:</label>
            <select v-model="selectedDisplayCurrency" @change="changeDisplayCurrency" class="filter-select">
              <option v-for="currency in availableCurrencies" :key="currency" :value="currency">
                {{ currency }}
              </option>
            </select>
          </div>
          
          <button @click="clearFilters" class="btn btn-outline btn-sm">
            <i class="fas fa-times"></i>
            Clear Filters
          </button>
        </div>
      </div>

      <!-- Currency Summary Panel -->
      <div v-if="showCurrencySummary && currencySummary.length > 0" class="currency-summary-panel">
        <div class="panel-header">
          <h3>
            <i class="fas fa-coins"></i>
            Currency Summary
          </h3>
          <button @click="showCurrencySummary = false" class="btn btn-sm btn-outline">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="currency-summary-grid">
          <div v-for="summary in currencySummary" :key="summary.currency" class="currency-card">
            <div class="currency-header">
              <span class="currency-code">{{ summary.currency }}</span>
              <span class="asset-count">{{ summary.asset_count }} assets</span>
            </div>
            <div class="currency-amounts">
              <div class="amount-row">
                <span class="amount-label">Total Acquisition:</span>
                <span class="amount-value">{{ summary.currency }} {{ formatNumber(summary.total_acquisition_cost) }}</span>
              </div>
              <div class="amount-row">
                <span class="amount-label">Current Value:</span>
                <span class="amount-value">{{ summary.currency }} {{ formatNumber(summary.total_current_value) }}</span>
              </div>
              <div v-if="summary.converted_total_acquisition_cost" class="amount-row converted">
                <span class="amount-label">Converted ({{ selectedDisplayCurrency }}):</span>
                <span class="amount-value">{{ selectedDisplayCurrency }} {{ formatNumber(summary.converted_total_current_value) }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Assets Grid -->
      <div class="assets-section">
        <div class="section-header">
          <h2>Assets List</h2>
          <div class="view-controls">
            <button @click="toggleCurrencySummary" class="btn btn-outline btn-sm">
              <i class="fas fa-coins"></i>
              {{ showCurrencySummary ? 'Hide' : 'Show' }} Currency Summary
            </button>
            <span class="results-count">
              Showing {{ pagination.from }}-{{ pagination.to }} of {{ pagination.total }} assets
            </span>
          </div>
        </div>

        <div v-if="loading" class="loading-state">
          <div class="loading-spinner"></div>
          <p>Loading assets...</p>
        </div>

        <div v-else-if="assets.length === 0" class="empty-state">
          <div class="empty-icon">
            <i class="fas fa-cube"></i>
          </div>
          <h3>No Assets Found</h3>
          <p>No fixed assets match your current filters.</p>
          <button @click="clearFilters" class="btn btn-primary">
            <i class="fas fa-filter"></i>
            Clear Filters
          </button>
        </div>

        <div v-else class="assets-grid">
          <div
            v-for="asset in assets"
            :key="asset.asset_id"
            class="asset-card"
            @click="viewAsset(asset.asset_id)"
          >
            <div class="asset-header">
              <div class="asset-icon">
                <i :class="getAssetIcon(asset.category)"></i>
              </div>
              <div class="asset-basic-info">
                <h3 class="asset-name">{{ asset.name }}</h3>
                <p class="asset-code">{{ asset.asset_code }}</p>
              </div>
              <div class="asset-status" :class="asset.status.toLowerCase().replace(' ', '-')">
                {{ asset.status }}
              </div>
            </div>

            <div class="asset-content">
              <div class="asset-details">
                <div class="detail-row">
                  <span class="detail-label">Category:</span>
                  <span class="detail-value">{{ asset.category }}</span>
                </div>
                <div class="detail-row">
                  <span class="detail-label">Acquisition Date:</span>
                  <span class="detail-value">{{ formatDate(asset.acquisition_date) }}</span>
                </div>
                <div class="detail-row">
                  <span class="detail-label">Currency:</span>
                  <span class="detail-value currency-badge">{{ asset.currency }}</span>
                </div>
              </div>

              <div class="asset-financial">
                <div class="financial-row">
                  <span class="financial-label">Acquisition Cost:</span>
                  <div class="financial-value-group">
                    <span class="financial-value original">
                      {{ asset.currency }} {{ formatNumber(asset.acquisition_cost) }}
                    </span>
                    <span v-if="asset.converted_acquisition_cost && asset.currency !== selectedDisplayCurrency" 
                          class="financial-value converted">
                      ({{ selectedDisplayCurrency }} {{ formatNumber(asset.converted_acquisition_cost) }})
                    </span>
                  </div>
                </div>
                <div class="financial-row">
                  <span class="financial-label">Current Value:</span>
                  <div class="financial-value-group">
                    <span class="financial-value original">
                      {{ asset.currency }} {{ formatNumber(asset.current_value) }}
                    </span>
                    <span v-if="asset.converted_current_value && asset.currency !== selectedDisplayCurrency" 
                          class="financial-value converted">
                      ({{ selectedDisplayCurrency }} {{ formatNumber(asset.converted_current_value) }})
                    </span>
                  </div>
                </div>
                <div class="financial-row">
                  <span class="financial-label">Depreciation Rate:</span>
                  <span class="financial-value">{{ asset.depreciation_rate }}%</span>
                </div>
              </div>

              <!-- Currency Exchange Info -->
              <div v-if="asset.exchange_rate && asset.currency !== selectedDisplayCurrency" class="exchange-info">
                <div class="exchange-rate">
                  <i class="fas fa-exchange-alt"></i>
                  Rate: 1 {{ asset.currency }} = {{ asset.exchange_rate }} {{ selectedDisplayCurrency }}
                </div>
                <div v-if="asset.conversion_date" class="exchange-date">
                  As of {{ formatDate(asset.conversion_date) }}
                </div>
              </div>
            </div>

            <div class="asset-actions">
              <button @click.stop="editAsset(asset.asset_id)" class="btn btn-sm btn-outline">
                <i class="fas fa-edit"></i>
                Edit
              </button>
              <button @click.stop="viewAsset(asset.asset_id)" class="btn btn-sm btn-primary">
                <i class="fas fa-eye"></i>
                View
              </button>
              <button @click.stop="deleteAsset(asset)" class="btn btn-sm btn-danger">
                <i class="fas fa-trash"></i>
                Delete
              </button>
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="pagination.last_page > 1" class="pagination-section">
          <div class="pagination-info">
            Showing {{ pagination.from }}-{{ pagination.to }} of {{ pagination.total }} results
          </div>
          <div class="pagination-controls">
            <button
              @click="changePage(pagination.current_page - 1)"
              :disabled="pagination.current_page === 1"
              class="btn btn-outline btn-sm"
            >
              <i class="fas fa-chevron-left"></i>
              Previous
            </button>
            
            <div class="page-numbers">
              <button
                v-for="page in visiblePages"
                :key="page"
                @click="changePage(page)"
                :class="['btn', 'btn-sm', page === pagination.current_page ? 'btn-primary' : 'btn-outline']"
              >
                {{ page }}
              </button>
            </div>
            
            <button
              @click="changePage(pagination.current_page + 1)"
              :disabled="pagination.current_page === pagination.last_page"
              class="btn btn-outline btn-sm"
            >
              Next
              <i class="fas fa-chevron-right"></i>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div v-if="showDeleteModal" class="modal-overlay" @click="closeDeleteModal">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3>
            <i class="fas fa-exclamation-triangle"></i>
            Confirm Delete
          </h3>
          <button @click="closeDeleteModal" class="modal-close">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <p>Are you sure you want to delete this fixed asset?</p>
          <div v-if="assetToDelete" class="asset-info">
            <strong>{{ assetToDelete.name }}</strong><br>
            <span class="text-muted">{{ assetToDelete.asset_code }}</span>
          </div>
          <div class="warning-text">
            <i class="fas fa-exclamation-triangle"></i>
            This action cannot be undone.
          </div>
        </div>
        <div class="modal-actions">
          <button @click="closeDeleteModal" class="btn btn-secondary">Cancel</button>
          <button @click="confirmDelete" class="btn btn-danger" :disabled="deleting">
            <i v-if="deleting" class="fas fa-spinner fa-spin"></i>
            <span v-else>Delete Asset</span>
          </button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script>
import { ref, reactive, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

export default {
  name: 'FixedAssetsList',
  components: {
  },
  setup() {
    const router = useRouter()
    
    // Reactive data
    const assets = ref([])
    const loading = ref(false)
    const searchQuery = ref('')
    const showDeleteModal = ref(false)
    const assetToDelete = ref(null)
    const deleting = ref(false)
    const availableCurrencies = ref(['USD'])
    const selectedDisplayCurrency = ref('USD')
    const showCurrencySummary = ref(false)
    const currencySummary = ref([])
    
    const filters = reactive({
      category: '',
      status: '',
      currency: ''
    })
    
    const pagination = reactive({
      current_page: 1,
      last_page: 1,
      per_page: 12,
      total: 0,
      from: 0,
      to: 0
    })
    
    const stats = reactive({
      totalAssets: 0,
      activeAssets: 0,
      totalValue: 0,
      totalDepreciation: 0,
      categoriesCount: 0,
      currenciesCount: 0,
      originalTotalValue: 0,
      originalTotalDepreciation: 0
    })
    
    const currencyConversion = reactive({
      isConverted: false,
      rate: 1,
      date: null
    })
    
    const categories = ref([])
    let searchTimeout = null
    
    // Computed
    const visiblePages = computed(() => {
      const pages = []
      const start = Math.max(1, pagination.current_page - 2)
      const end = Math.min(pagination.last_page, pagination.current_page + 2)
      
      for (let i = start; i <= end; i++) {
        pages.push(i)
      }
      return pages
    })
    
    // Methods
    const fetchAssets = async (page = 1) => {
      try {
        loading.value = true
        const params = {
          page,
          per_page: pagination.per_page,
          search: searchQuery.value,
          display_currency: selectedDisplayCurrency.value,
          conversion_date: new Date().toISOString().split('T')[0],
          ...filters
        }
        
        const response = await axios.get('/accounting/fixed-assets', { params })
        const data = response.data
        
        assets.value = data.data
        Object.assign(pagination, {
          current_page: data.current_page,
          last_page: data.last_page,
          per_page: data.per_page,
          total: data.total,
          from: data.from,
          to: data.to
        })
        
        // Update stats
        calculateStats()
        
      } catch (error) {
        console.error('Error fetching assets:', error)
        // Show error toast/notification
      } finally {
        loading.value = false
      }
    }

    const fetchAvailableCurrencies = async () => {
      try {
        const response = await axios.get('/accounting/system-currencies')
        availableCurrencies.value = response.data.data.map(c => c.code || c.currency || c.name)
        
        // Set default display currency to base currency or first available
        const baseCurrency = 'USD' // This should come from app config
        if (availableCurrencies.value.includes(baseCurrency)) {
          selectedDisplayCurrency.value = baseCurrency
        } else if (availableCurrencies.value.length > 0) {
          selectedDisplayCurrency.value = availableCurrencies.value[0]
        }
      } catch (error) {
        console.error('Error fetching currencies:', error)
      }
    }

    const fetchCurrencySummary = async () => {
      try {
        const params = {
          display_currency: selectedDisplayCurrency.value,
          conversion_date: new Date().toISOString().split('T')[0]
        }
        
        const response = await axios.get('/accounting/fixed-assets/currency-summary', { params })
        currencySummary.value = response.data.data
      } catch (error) {
        console.error('Error fetching currency summary:', error)
      }
    }
    
    const calculateStats = () => {
      stats.totalAssets = assets.value.length
      stats.activeAssets = assets.value.filter(asset => asset.status === 'Active').length
      
      // Calculate values considering currency conversion
      let totalValue = 0
      let totalDepreciation = 0
      let originalTotalValue = 0
      let originalTotalDepreciation = 0
      
      assets.value.forEach(asset => {
        const currentValue = parseFloat(asset.current_value || 0)
        const acquisitionCost = parseFloat(asset.acquisition_cost || 0)
        const depreciation = acquisitionCost - currentValue
        
        // Use converted values if available, otherwise original
        if (asset.converted_current_value && asset.currency !== selectedDisplayCurrency.value) {
          totalValue += parseFloat(asset.converted_current_value)
          totalDepreciation += parseFloat(asset.converted_acquisition_cost || acquisitionCost) - parseFloat(asset.converted_current_value)
          currencyConversion.isConverted = true
        } else {
          totalValue += currentValue
          totalDepreciation += depreciation
        }
        
        // Keep track of original values
        originalTotalValue += currentValue
        originalTotalDepreciation += depreciation
      })
      
      stats.totalValue = totalValue
      stats.totalDepreciation = totalDepreciation
      stats.originalTotalValue = originalTotalValue
      stats.originalTotalDepreciation = originalTotalDepreciation
      
      const uniqueCategories = [...new Set(assets.value.map(asset => asset.category))]
      stats.categoriesCount = uniqueCategories.length
      categories.value = uniqueCategories
      
      const uniqueCurrencies = [...new Set(assets.value.map(asset => asset.currency))]
      stats.currenciesCount = uniqueCurrencies.length
    }
    
    const changeDisplayCurrency = async () => {
      currencyConversion.isConverted = false
      await fetchAssets(pagination.current_page)
      if (showCurrencySummary.value) {
        await fetchCurrencySummary()
      }
    }

    const toggleCurrencySummary = async () => {
      showCurrencySummary.value = !showCurrencySummary.value
      if (showCurrencySummary.value) {
        await fetchCurrencySummary()
      }
    }
    
    const debounceSearch = () => {
      clearTimeout(searchTimeout)
      searchTimeout = setTimeout(() => {
        fetchAssets(1)
      }, 500)
    }
    
    const applyFilters = () => {
      fetchAssets(1)
    }
    
    const clearFilters = () => {
      filters.category = ''
      filters.status = ''
      filters.currency = ''
      searchQuery.value = ''
      fetchAssets(1)
    }
    
    const changePage = (page) => {
      if (page >= 1 && page <= pagination.last_page) {
        fetchAssets(page)
      }
    }
    
    const createAsset = () => {
      router.push('/accounting/fixed-assets/create')
    }
    
    const editAsset = (id) => {
      router.push(`/accounting/fixed-assets/${id}/edit`)
    }
    
    const viewAsset = (id) => {
      router.push(`/accounting/fixed-assets/${id}`)
    }
    
    const generateReport = () => {
      router.push('/accounting/fixed-assets/report')
    }
    
    const deleteAsset = (asset) => {
      assetToDelete.value = asset
      showDeleteModal.value = true
    }
    
    const closeDeleteModal = () => {
      showDeleteModal.value = false
      assetToDelete.value = null
    }
    
    const confirmDelete = async () => {
      if (!assetToDelete.value) return
      
      try {
        deleting.value = true
        await axios.delete(`/accounting/fixed-assets/${assetToDelete.value.asset_id}`)
        
        // Remove from local array
        assets.value = assets.value.filter(asset => asset.asset_id !== assetToDelete.value.asset_id)
        calculateStats()
        
        closeDeleteModal()
        // Show success toast
      } catch (error) {
        console.error('Error deleting asset:', error)
        // Show error toast
      } finally {
        deleting.value = false
      }
    }
    
    const getAssetIcon = (category) => {
      const iconMap = {
        'Building': 'fas fa-building',
        'Equipment': 'fas fa-cogs',
        'Vehicle': 'fas fa-car',
        'Furniture': 'fas fa-chair',
        'Computer': 'fas fa-laptop',
        'Machinery': 'fas fa-industry',
        default: 'fas fa-cube'
      }
      return iconMap[category] || iconMap.default
    }
    
    const formatNumber = (value) => {
      return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      }).format(value || 0)
    }

    const formatCurrencyValue = (values) => {
      if (typeof values === 'object' && values !== null) {
        return Object.entries(values)
          .map(([currency, amount]) => `${currency} ${formatNumber(amount)}`)
          .join(', ')
      }
      return formatNumber(values)
    }
    
    const formatDate = (date) => {
      return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      })
    }
    
    // Lifecycle
    onMounted(async () => {
      await fetchAvailableCurrencies()
      await fetchAssets()
    })
    
    return {
      assets,
      loading,
      searchQuery,
      filters,
      pagination,
      stats,
      categories,
      showDeleteModal,
      assetToDelete,
      deleting,
      visiblePages,
      availableCurrencies,
      selectedDisplayCurrency,
      showCurrencySummary,
      currencySummary,
      currencyConversion,
      fetchAssets,
      fetchAvailableCurrencies,
      fetchCurrencySummary,
      changeDisplayCurrency,
      toggleCurrencySummary,
      debounceSearch,
      applyFilters,
      clearFilters,
      changePage,
      createAsset,
      editAsset,
      viewAsset,
      generateReport,
      deleteAsset,
      closeDeleteModal,
      confirmDelete,
      getAssetIcon,
      formatNumber,
      formatCurrencyValue,
      formatDate
    }
  }
}
</script>

<style scoped>
.fixed-assets-page {
  max-width: 1400px;
  margin: 0 auto;
  padding: 2rem;
}

/* Page Header */
.page-header {
  margin-bottom: 2rem;
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 2rem;
}

.title-section h1 {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-size: 2.5rem;
  font-weight: 700;
  color: #1e293b;
  margin: 0 0 0.5rem 0;
}

.title-section h1 i {
  color: #6366f1;
}

.page-subtitle {
  color: #64748b;
  font-size: 1.1rem;
  margin: 0;
}

.header-actions {
  display: flex;
  gap: 1rem;
  flex-shrink: 0;
}

/* Stats Grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.stat-card {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 1.5rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  transition: all 0.2s ease;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.stat-card:hover {
  border-color: #6366f1;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.stat-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  background: #f1f5f9;
  color: #64748b;
  flex-shrink: 0;
}

.stat-icon.active {
  background: #dcfce7;
  color: #16a34a;
}

.stat-icon.value {
  background: #dbeafe;
  color: #2563eb;
}

.stat-icon.depreciation {
  background: #fed7d7;
  color: #dc2626;
}

.stat-icon.categories {
  background: #fef3c7;
  color: #d97706;
}

.stat-icon.currencies {
  background: #e0e7ff;
  color: #7c3aed;
}

.stat-content {
  flex: 1;
}

.stat-value {
  font-size: 1.875rem;
  font-weight: 700;
  color: #1e293b;
  line-height: 1;
  margin-bottom: 0.25rem;
}

.stat-label {
  color: #64748b;
  font-size: 0.875rem;
  font-weight: 500;
}

.stat-conversion {
  color: #6b7280;
  font-size: 0.75rem;
  margin-top: 0.25rem;
}

/* Controls Section */
.controls-section {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 1.5rem;
  margin-bottom: 2rem;
}

.search-section {
  margin-bottom: 1rem;
}

.search-input-group {
  position: relative;
  max-width: 500px;
}

.search-input-group i {
  position: absolute;
  left: 1rem;
  top: 50%;
  transform: translateY(-50%);
  color: #9ca3af;
}

.search-input {
  width: 100%;
  padding: 0.75rem 1rem 0.75rem 2.5rem;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 0.875rem;
  transition: border-color 0.2s ease;
}

.search-input:focus {
  outline: none;
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.filters-section {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
  align-items: center;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.filter-label {
  font-size: 0.75rem;
  font-weight: 500;
  color: #374151;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.filter-select {
  padding: 0.5rem 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 0.875rem;
  background: white;
  min-width: 140px;
}

.filter-select:focus {
  outline: none;
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

/* Currency Summary Panel */
.currency-summary-panel {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  margin-bottom: 2rem;
  overflow: hidden;
}

.panel-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.5rem;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
}

.panel-header h3 {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin: 0;
  color: #1e293b;
  font-size: 1.125rem;
  font-weight: 600;
}

.currency-summary-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 1rem;
  padding: 1.5rem;
}

.currency-card {
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 1rem;
  background: #fafafa;
}

.currency-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.75rem;
}

.currency-code {
  font-weight: 600;
  color: #1e293b;
  font-size: 1.125rem;
}

.asset-count {
  color: #6b7280;
  font-size: 0.875rem;
}

/* .currency-amounts {
  space-y: 0.5rem;
} */

.amount-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.25rem 0;
}

.amount-row.converted {
  border-top: 1px solid #e2e8f0;
  margin-top: 0.5rem;
  padding-top: 0.5rem;
  font-weight: 500;
}

.amount-label {
  color: #6b7280;
  font-size: 0.875rem;
}

.amount-value {
  font-weight: 500;
  color: #1e293b;
}

/* Assets Section */
.assets-section {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  overflow: hidden;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-bottom: 1px solid #e2e8f0;
  background: #f8fafc;
}

.section-header h2 {
  margin: 0;
  color: #1e293b;
  font-size: 1.5rem;
  font-weight: 600;
}

.view-controls {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.results-count {
  color: #64748b;
  font-size: 0.875rem;
}

/* Loading and Empty States */
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
  border: 3px solid #e2e8f0;
  border-top: 3px solid #6366f1;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 1rem;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.empty-icon {
  font-size: 4rem;
  color: #d1d5db;
  margin-bottom: 1rem;
}

.empty-state h3 {
  margin: 0 0 0.5rem 0;
  color: #374151;
}

.empty-state p {
  margin: 0 0 1.5rem 0;
  color: #6b7280;
}

/* Assets Grid */
.assets-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
  gap: 1.5rem;
  padding: 1.5rem;
}

.asset-card {
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 1.5rem;
  background: white;
  transition: all 0.2s ease;
  cursor: pointer;
}

.asset-card:hover {
  border-color: #6366f1;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.asset-header {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  margin-bottom: 1rem;
}

.asset-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  background: #f1f5f9;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  color: #6366f1;
  flex-shrink: 0;
}

.asset-basic-info {
  flex: 1;
}

.asset-name {
  margin: 0 0 0.25rem 0;
  font-size: 1.125rem;
  font-weight: 600;
  color: #1e293b;
  line-height: 1.2;
}

.asset-code {
  margin: 0;
  color: #64748b;
  font-size: 0.875rem;
}

.asset-status {
  padding: 0.25rem 0.75rem;
  border-radius: 6px;
  font-size: 0.75rem;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  flex-shrink: 0;
}

.asset-status.active {
  background: #dcfce7;
  color: #16a34a;
}

.asset-status.inactive {
  background: #fef3c7;
  color: #d97706;
}

.asset-status.disposed {
  background: #fed7d7;
  color: #dc2626;
}

.asset-status.under-maintenance {
  background: #dbeafe;
  color: #2563eb;
}

.asset-content {
  margin-bottom: 1rem;
}

.asset-details {
  margin-bottom: 1rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid #f1f5f9;
}

.detail-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.5rem;
}

.detail-row:last-child {
  margin-bottom: 0;
}

.detail-label {
  color: #64748b;
  font-size: 0.875rem;
}

.detail-value {
  color: #1e293b;
  font-weight: 500;
  font-size: 0.875rem;
}

.currency-badge {
  background: #e0e7ff;
  color: #7c3aed;
  padding: 0.125rem 0.5rem;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 600;
}

.asset-financial {
  margin-bottom: 1rem;
}

.financial-row {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 0.75rem;
}

.financial-row:last-child {
  margin-bottom: 0;
}

.financial-label {
  color: #64748b;
  font-size: 0.875rem;
  flex-shrink: 0;
}

.financial-value-group {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 0.25rem;
}

.financial-value {
  font-weight: 600;
  color: #1e293b;
  font-size: 0.875rem;
}

.financial-value.original {
  color: #1e293b;
}

.financial-value.converted {
  color: #6b7280;
  font-size: 0.75rem;
  font-weight: 500;
}

.exchange-info {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  padding: 0.75rem;
  margin-top: 1rem;
}

.exchange-rate {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.75rem;
  color: #374151;
  font-weight: 500;
}

.exchange-rate i {
  color: #6b7280;
}

.exchange-date {
  font-size: 0.625rem;
  color: #9ca3af;
  margin-top: 0.25rem;
}

.asset-actions {
  display: flex;
  gap: 0.75rem;
}

/* Pagination */
.pagination-section {
  display: flex;
  justify-content: between;
  align-items: center;
  padding: 1.5rem;
  border-top: 1px solid #e2e8f0;
  background: #f8fafc;
}

.pagination-info {
  color: #64748b;
  font-size: 0.875rem;
}

.pagination-controls {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.page-numbers {
  display: flex;
  gap: 0.25rem;
}

/* Buttons */
.btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  border: 1px solid transparent;
  border-radius: 6px;
  font-size: 0.875rem;
  font-weight: 500;
  text-decoration: none;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-primary {
  background: #6366f1;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: #5a67d8;
}

.btn-secondary {
  background: #e2e8f0;
  color: #64748b;
}

.btn-secondary:hover:not(:disabled) {
  background: #cbd5e1;
}

.btn-outline {
  border-color: #d1d5db;
  background: white;
  color: #374151;
}

.btn-outline:hover:not(:disabled) {
  border-color: #6366f1;
  color: #6366f1;
}

.btn-danger {
  background: #dc2626;
  color: white;
}

.btn-danger:hover:not(:disabled) {
  background: #b91c1c;
}

.btn-sm {
  padding: 0.375rem 0.75rem;
  font-size: 0.75rem;
}

/* Modal */
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
}

.modal-content {
  background: white;
  border-radius: 12px;
  max-width: 500px;
  width: 90%;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-bottom: 1px solid #e2e8f0;
}

.modal-header h3 {
  margin: 0;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #1e293b;
}

.modal-close {
  background: none;
  border: none;
  font-size: 1.25rem;
  color: #64748b;
  cursor: pointer;
  padding: 0.25rem;
}

.modal-body {
  padding: 1.5rem;
}

.asset-info {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  padding: 1rem;
  margin: 1rem 0;
}

.warning-text {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #dc2626;
  font-size: 0.875rem;
  margin-top: 1rem;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  padding: 1.5rem;
  border-top: 1px solid #e2e8f0;
}

.text-muted {
  color: #64748b;
}

/* Responsive Design */
@media (max-width: 768px) {
  .fixed-assets-page {
    padding: 1rem;
  }
  
  .header-content {
    flex-direction: column;
    gap: 1rem;
  }
  
  .stats-grid {
    grid-template-columns: 1fr;
  }
  
  .filters-section {
    flex-direction: column;
    align-items: stretch;
  }
  
  .filter-group {
    width: 100%;
  }
  
  .filter-select {
    min-width: auto;
  }
  
  .assets-grid {
    grid-template-columns: 1fr;
  }
  
  .asset-header {
    flex-direction: column;
    align-items: flex-start;
  }
  
  .asset-status {
    align-self: flex-start;
  }
  
  .pagination-section {
    flex-direction: column;
    gap: 1rem;
  }
  
  .page-numbers {
    order: -1;
  }
}
</style>