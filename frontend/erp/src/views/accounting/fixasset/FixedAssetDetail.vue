<template>
  <AppLayout>
    <div class="asset-detail-page">
      <!-- Loading State -->
      <div v-if="loading" class="loading-state">
        <div class="loading-spinner"></div>
        <p>Loading asset details...</p>
      </div>

      <!-- Asset Details -->
      <div v-else-if="asset" class="asset-detail-container">
        <!-- Page Header -->
        <div class="page-header">
          <div class="header-content">
            <div class="title-section">
              <div class="breadcrumb">
                <router-link to="/accounting/fixed-assets" class="breadcrumb-link">
                  <i class="fas fa-building"></i>
                  Fixed Assets
                </router-link>
                <i class="fas fa-chevron-right breadcrumb-separator"></i>
                <span class="breadcrumb-current">{{ asset.name }}</span>
              </div>
              <h1 class="page-title">
                <i :class="getAssetIcon(asset.category)"></i>
                {{ asset.name }}
              </h1>
              <div class="asset-meta">
                <span class="asset-code">{{ asset.asset_code }}</span>
                <span class="asset-status" :class="asset.status.toLowerCase().replace(' ', '-')">
                  {{ asset.status }}
                </span>
                <span class="asset-currency">{{ asset.currency }}</span>
              </div>
            </div>
            <div class="header-actions">
              <button @click="goBack" class="btn btn-outline">
                <i class="fas fa-arrow-left"></i>
                Back to List
              </button>
              <button @click="editAsset" class="btn btn-secondary">
                <i class="fas fa-edit"></i>
                Edit Asset
              </button>
              <button @click="calculateDepreciation" class="btn btn-primary">
                <i class="fas fa-calculator"></i>
                Calculate Depreciation
              </button>
            </div>
          </div>
        </div>

        <!-- Currency Display Options -->
        <div class="currency-controls">
          <div class="display-currency-selector">
            <label class="selector-label">Display Currency:</label>
            <select v-model="selectedDisplayCurrency" @change="changeDisplayCurrency" class="currency-select">
              <option v-for="currency in availableCurrencies" :key="currency" :value="currency">
                {{ currency }}
              </option>
            </select>
          </div>
          <div v-if="showConversionDate" class="conversion-date">
            <label class="selector-label">Conversion Date:</label>
            <input 
              v-model="conversionDate" 
              @change="changeDisplayCurrency"
              type="date" 
              class="date-input"
              :max="today"
            >
          </div>
        </div>

        <!-- Asset Summary Cards -->
        <div class="summary-cards">
          <div class="summary-card financial">
            <div class="card-header">
              <div class="card-icon">
                <i class="fas fa-dollar-sign"></i>
              </div>
              <div class="card-title">
                <h3>Current Value</h3>
                <p>Asset's current market value</p>
              </div>
            </div>
            <div class="card-content">
              <div class="primary-value">
                {{ asset.currency }} {{ formatNumber(asset.current_value) }}
              </div>
              <div v-if="convertedValues.current_value && asset.currency !== selectedDisplayCurrency" class="converted-value">
                {{ selectedDisplayCurrency }} {{ formatNumber(convertedValues.current_value) }}
              </div>
            </div>
          </div>

          <div class="summary-card acquisition">
            <div class="card-header">
              <div class="card-icon">
                <i class="fas fa-shopping-cart"></i>
              </div>
              <div class="card-title">
                <h3>Acquisition Cost</h3>
                <p>Original purchase price</p>
              </div>
            </div>
            <div class="card-content">
              <div class="primary-value">
                {{ asset.currency }} {{ formatNumber(asset.acquisition_cost) }}
              </div>
              <div v-if="convertedValues.acquisition_cost && asset.currency !== selectedDisplayCurrency" class="converted-value">
                {{ selectedDisplayCurrency }} {{ formatNumber(convertedValues.acquisition_cost) }}
              </div>
            </div>
          </div>

          <div class="summary-card depreciation">
            <div class="card-header">
              <div class="card-icon">
                <i class="fas fa-chart-line-down"></i>
              </div>
              <div class="card-title">
                <h3>Total Depreciation</h3>
                <p>Accumulated depreciation to date</p>
              </div>
            </div>
            <div class="card-content">
              <div class="primary-value">
                {{ asset.currency }} {{ formatNumber(totalDepreciation) }}
              </div>
              <div v-if="convertedValues.total_depreciation && asset.currency !== selectedDisplayCurrency" class="converted-value">
                {{ selectedDisplayCurrency }} {{ formatNumber(convertedValues.total_depreciation) }}
              </div>
            </div>
          </div>

          <div class="summary-card percentage">
            <div class="card-header">
              <div class="card-icon">
                <i class="fas fa-percentage"></i>
              </div>
              <div class="card-title">
                <h3>Depreciated %</h3>
                <p>Percentage of value lost</p>
              </div>
            </div>
            <div class="card-content">
              <div class="primary-value">
                {{ depreciationPercentage }}%
              </div>
            </div>
          </div>
        </div>

        <!-- Currency Information Panel -->
        <div v-if="currencySummary" class="currency-info-panel">
          <div class="panel-header">
            <h3>
              <i class="fas fa-coins"></i>
              Currency Information
            </h3>
          </div>
          <div class="currency-details">
            <div class="currency-row">
              <div class="currency-item">
                <span class="currency-label">Asset Currency:</span>
                <span class="currency-value">{{ currencySummary.asset_currency }}</span>
              </div>
              <div class="currency-item">
                <span class="currency-label">Base Currency:</span>
                <span class="currency-value">{{ currencySummary.base_currency }}</span>
              </div>
              <div v-if="currencySummary.acquisition_exchange_rate" class="currency-item">
                <span class="currency-label">Acquisition Rate:</span>
                <span class="currency-value">{{ currencySummary.acquisition_exchange_rate }}</span>
              </div>
              <div v-if="currencySummary.current_exchange_rate && currencySummary.current_exchange_rate !== 1" class="currency-item">
                <span class="currency-label">Current Rate:</span>
                <span class="currency-value">{{ currencySummary.current_exchange_rate }}</span>
              </div>
            </div>
            
            <!-- Exchange Rate History -->
            <div v-if="showExchangeRateWarning" class="exchange-rate-warning">
              <i class="fas fa-exclamation-triangle"></i>
              Exchange rates may have changed since acquisition. Current conversions are for reference only.
            </div>
          </div>
        </div>

        <!-- Tabs Navigation -->
        <div class="tabs-container">
          <div class="tabs-nav">
            <button
              v-for="tab in tabs"
              :key="tab.id"
              @click="activeTab = tab.id"
              :class="['tab-button', { active: activeTab === tab.id }]"
            >
              <i :class="tab.icon"></i>
              {{ tab.label }}
            </button>
          </div>

          <!-- Tab Content -->
          <div class="tab-content">
            <!-- Details Tab -->
            <div v-if="activeTab === 'details'" class="tab-panel">
              <div class="details-grid">
                <div class="detail-section">
                  <h4>Basic Information</h4>
                  <div class="detail-items">
                    <div class="detail-item">
                      <span class="detail-label">Asset Code:</span>
                      <span class="detail-value">{{ asset.asset_code }}</span>
                    </div>
                    <div class="detail-item">
                      <span class="detail-label">Name:</span>
                      <span class="detail-value">{{ asset.name }}</span>
                    </div>
                    <div class="detail-item">
                      <span class="detail-label">Category:</span>
                      <span class="detail-value">{{ asset.category }}</span>
                    </div>
                    <div class="detail-item">
                      <span class="detail-label">Status:</span>
                      <span class="detail-value status" :class="asset.status.toLowerCase().replace(' ', '-')">
                        {{ asset.status }}
                      </span>
                    </div>
                    <div class="detail-item">
                      <span class="detail-label">Acquisition Date:</span>
                      <span class="detail-value">{{ formatDate(asset.acquisition_date) }}</span>
                    </div>
                  </div>
                </div>

                <div class="detail-section">
                  <h4>Financial Information</h4>
                  <div class="detail-items">
                    <div class="detail-item">
                      <span class="detail-label">Currency:</span>
                      <span class="detail-value currency-badge">{{ asset.currency }}</span>
                    </div>
                    <div class="detail-item">
                      <span class="detail-label">Acquisition Cost:</span>
                      <div class="financial-value-group">
                        <span class="detail-value">{{ asset.currency }} {{ formatNumber(asset.acquisition_cost) }}</span>
                        <span v-if="convertedValues.acquisition_cost && asset.currency !== selectedDisplayCurrency" class="converted-detail">
                          ({{ selectedDisplayCurrency }} {{ formatNumber(convertedValues.acquisition_cost) }})
                        </span>
                      </div>
                    </div>
                    <div class="detail-item">
                      <span class="detail-label">Current Value:</span>
                      <div class="financial-value-group">
                        <span class="detail-value">{{ asset.currency }} {{ formatNumber(asset.current_value) }}</span>
                        <span v-if="convertedValues.current_value && asset.currency !== selectedDisplayCurrency" class="converted-detail">
                          ({{ selectedDisplayCurrency }} {{ formatNumber(convertedValues.current_value) }})
                        </span>
                      </div>
                    </div>
                    <div v-if="asset.salvage_value" class="detail-item">
                      <span class="detail-label">Salvage Value:</span>
                      <div class="financial-value-group">
                        <span class="detail-value">{{ asset.currency }} {{ formatNumber(asset.salvage_value) }}</span>
                        <span v-if="convertedValues.salvage_value && asset.currency !== selectedDisplayCurrency" class="converted-detail">
                          ({{ selectedDisplayCurrency }} {{ formatNumber(convertedValues.salvage_value) }})
                        </span>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="detail-section">
                  <h4>Depreciation Configuration</h4>
                  <div class="detail-items">
                    <div class="detail-item">
                      <span class="detail-label">Method:</span>
                      <span class="detail-value">{{ formatDepreciationMethod(asset.depreciation_method) }}</span>
                    </div>
                    <div class="detail-item">
                      <span class="detail-label">Rate:</span>
                      <span class="detail-value">{{ asset.depreciation_rate }}% per year</span>
                    </div>
                    <div v-if="asset.useful_life_years" class="detail-item">
                      <span class="detail-label">Useful Life:</span>
                      <span class="detail-value">{{ asset.useful_life_years }} years</span>
                    </div>
                  </div>
                </div>

                <div class="detail-section">
                  <h4>Asset Age & Projections</h4>
                  <div class="detail-items">
                    <div class="detail-item">
                      <span class="detail-label">Asset Age:</span>
                      <span class="detail-value">{{ assetAge }}</span>
                    </div>
                    <div class="detail-item">
                      <span class="detail-label">Remaining Life:</span>
                      <span class="detail-value">{{ estimatedRemainingLife }}</span>
                    </div>
                    <div class="detail-item">
                      <span class="detail-label">Annual Depreciation:</span>
                      <div class="financial-value-group">
                        <span class="detail-value">{{ asset.currency }} {{ formatNumber(projectedAnnualDepreciation) }}</span>
                        <span v-if="convertedValues.annual_depreciation && asset.currency !== selectedDisplayCurrency" class="converted-detail">
                          ({{ selectedDisplayCurrency }} {{ formatNumber(convertedValues.annual_depreciation) }})
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Depreciation History Tab -->
            <div v-if="activeTab === 'depreciation'" class="tab-panel">
              <div class="depreciation-section">
                <div class="section-header">
                  <h4>Depreciation History</h4>
                  <div class="depreciation-stats">
                    <span class="stat-item">
                      <strong>{{ depreciations.length }}</strong> records
                    </span>
                    <span class="stat-item">
                      <strong>{{ asset.currency }} {{ formatNumber(totalDepreciation) }}</strong> total
                    </span>
                    <span v-if="convertedValues.total_depreciation && asset.currency !== selectedDisplayCurrency" class="stat-item converted">
                      <strong>{{ selectedDisplayCurrency }} {{ formatNumber(convertedValues.total_depreciation) }}</strong> converted
                    </span>
                  </div>
                </div>

                <div v-if="depreciations.length === 0" class="empty-state">
                  <div class="empty-icon">
                    <i class="fas fa-chart-line"></i>
                  </div>
                  <h5>No Depreciation Records</h5>
                  <p>No depreciation has been calculated for this asset yet.</p>
                  <button @click="calculateDepreciation" class="btn btn-primary">
                    <i class="fas fa-calculator"></i>
                    Calculate Depreciation
                  </button>
                </div>

                <div v-else class="depreciation-table">
                  <div class="table-header">
                    <div class="header-cell">Period</div>
                    <div class="header-cell">Date</div>
                    <div class="header-cell">Depreciation Amount</div>
                    <div class="header-cell">Accumulated</div>
                    <div class="header-cell">Remaining Value</div>
                    <div class="header-cell">Actions</div>
                  </div>

                  <div
                    v-for="depreciation in depreciations"
                    :key="depreciation.depreciation_id"
                    class="table-row"
                    @click="viewDepreciationDetail(depreciation)"
                  >
                    <div class="table-cell">
                      <span class="period-name">{{ getPeriodInfo(depreciation) }}</span>
                    </div>
                    <div class="table-cell">
                      <span class="depreciation-date">{{ formatDate(depreciation.depreciation_date) }}</span>
                    </div>
                    <div class="table-cell">
                      <div class="amount-group">
                        <span class="primary-amount">{{ asset.currency }} {{ formatNumber(depreciation.depreciation_amount) }}</span>
                        <span v-if="depreciation.converted_depreciation_amount && asset.currency !== selectedDisplayCurrency" class="converted-amount">
                          {{ selectedDisplayCurrency }} {{ formatNumber(depreciation.converted_depreciation_amount) }}
                        </span>
                      </div>
                    </div>
                    <div class="table-cell">
                      <div class="amount-group">
                        <span class="primary-amount">{{ asset.currency }} {{ formatNumber(depreciation.accumulated_depreciation) }}</span>
                        <span v-if="depreciation.converted_accumulated_depreciation && asset.currency !== selectedDisplayCurrency" class="converted-amount">
                          {{ selectedDisplayCurrency }} {{ formatNumber(depreciation.converted_accumulated_depreciation) }}
                        </span>
                      </div>
                    </div>
                    <div class="table-cell">
                      <div class="amount-group">
                        <span class="primary-amount">{{ asset.currency }} {{ formatNumber(depreciation.remaining_value) }}</span>
                        <span v-if="depreciation.converted_remaining_value && asset.currency !== selectedDisplayCurrency" class="converted-amount">
                          {{ selectedDisplayCurrency }} {{ formatNumber(depreciation.converted_remaining_value) }}
                        </span>
                      </div>
                    </div>
                    <div class="table-cell">
                      <button @click.stop="viewDepreciationDetail(depreciation)" class="btn btn-sm btn-outline">
                        <i class="fas fa-eye"></i>
                        View
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Currency Analysis Tab -->
            <div v-if="activeTab === 'currency'" class="tab-panel">
              <div class="currency-analysis">
                <div class="analysis-header">
                  <h4>Currency Analysis</h4>
                  <p>Compare asset values across different currencies and time periods</p>
                </div>

                <div class="analysis-grid">
                  <div class="analysis-card">
                    <h5>
                      <i class="fas fa-exchange-alt"></i>
                      Exchange Rate History
                    </h5>
                    <div class="rate-timeline">
                      <div class="rate-item">
                        <span class="rate-label">Acquisition Rate ({{ formatDate(asset.acquisition_date) }}):</span>
                        <span class="rate-value">
                          1 {{ asset.currency }} = {{ currencySummary?.acquisition_exchange_rate || 1 }} {{ currencySummary?.base_currency }}
                        </span>
                      </div>
                      <div class="rate-item">
                        <span class="rate-label">Current Rate ({{ formatDate(today) }}):</span>
                        <span class="rate-value">
                          1 {{ asset.currency }} = {{ currencySummary?.current_exchange_rate || 1 }} {{ currencySummary?.base_currency }}
                        </span>
                      </div>
                      <div v-if="exchangeRateChange !== 0" class="rate-change">
                        <span class="change-label">Rate Change:</span>
                        <span :class="['change-value', exchangeRateChange > 0 ? 'positive' : 'negative']">
                          {{ exchangeRateChange > 0 ? '+' : '' }}{{ (exchangeRateChange * 100).toFixed(2) }}%
                        </span>
                      </div>
                    </div>
                  </div>

                  <div class="analysis-card">
                    <h5>
                      <i class="fas fa-calculator"></i>
                      Value Comparison
                    </h5>
                    <div class="value-comparison">
                      <div class="comparison-row">
                        <span class="comparison-label">Original Currency ({{ asset.currency }}):</span>
                        <span class="comparison-value">{{ formatNumber(asset.current_value) }}</span>
                      </div>
                      <div class="comparison-row">
                        <span class="comparison-label">Base Currency ({{ currencySummary?.base_currency }}):</span>
                        <span class="comparison-value">{{ formatNumber(asset.base_currency_current_value || 0) }}</span>
                      </div>
                      <div v-if="selectedDisplayCurrency !== asset.currency && selectedDisplayCurrency !== currencySummary?.base_currency" class="comparison-row">
                        <span class="comparison-label">Display Currency ({{ selectedDisplayCurrency }}):</span>
                        <span class="comparison-value">{{ formatNumber(convertedValues.current_value || 0) }}</span>
                      </div>
                    </div>
                  </div>

                  <div class="analysis-card">
                    <h5>
                      <i class="fas fa-chart-pie"></i>
                      Currency Impact
                    </h5>
                    <div class="impact-analysis">
                      <div v-if="currencyImpact.hasImpact" class="impact-items">
                        <div class="impact-item">
                          <span class="impact-label">Acquisition Cost Impact:</span>
                          <span :class="['impact-value', currencyImpact.acquisitionImpact > 0 ? 'positive' : 'negative']">
                            {{ currencyImpact.acquisitionImpact > 0 ? '+' : '' }}{{ currencyImpact.acquisitionImpact.toFixed(2) }}%
                          </span>
                        </div>
                        <div class="impact-item">
                          <span class="impact-label">Current Value Impact:</span>
                          <span :class="['impact-value', currencyImpact.currentValueImpact > 0 ? 'positive' : 'negative']">
                            {{ currencyImpact.currentValueImpact > 0 ? '+' : '' }}{{ currencyImpact.currentValueImpact.toFixed(2) }}%
                          </span>
                        </div>
                      </div>
                      <div v-else class="no-impact">
                        <i class="fas fa-equals"></i>
                        No currency impact (same currency as base)
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Error State -->
      <div v-else class="error-state">
        <div class="error-icon">
          <i class="fas fa-exclamation-triangle"></i>
        </div>
        <h3>Asset Not Found</h3>
        <p>The requested fixed asset could not be found.</p>
        <button @click="goBack" class="btn btn-primary">
          <i class="fas fa-arrow-left"></i>
          Back to List
        </button>
      </div>
    </div>
  </AppLayout>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import axios from 'axios'

export default {
  name: 'FixedAssetDetail',
  components: {
  },
  setup() {
    const router = useRouter()
    const route = useRoute()
    
    // Reactive data
    const loading = ref(true)
    const asset = ref(null)
    const depreciations = ref([])
    const activeTab = ref('details')
    const availableCurrencies = ref(['USD'])
    const selectedDisplayCurrency = ref('USD')
    const conversionDate = ref(new Date().toISOString().split('T')[0])
    const convertedValues = ref({})
    const currencySummary = ref(null)
    
    const tabs = [
      { id: 'details', label: 'Details', icon: 'fas fa-info-circle' },
      { id: 'depreciation', label: 'Depreciation History', icon: 'fas fa-chart-line' },
      { id: 'currency', label: 'Currency Analysis', icon: 'fas fa-coins' }
    ]
    
    // Computed
    const today = computed(() => new Date().toISOString().split('T')[0])
    
    const showConversionDate = computed(() => {
      return selectedDisplayCurrency.value !== asset.value?.currency
    })
    
    const showExchangeRateWarning = computed(() => {
      return asset.value?.currency !== currencySummary.value?.base_currency
    })
    
    const totalDepreciation = computed(() => {
      if (!asset.value) return 0
      return asset.value.acquisition_cost - asset.value.current_value
    })
    
    const depreciationPercentage = computed(() => {
      if (!asset.value || asset.value.acquisition_cost === 0) return 0
      return ((totalDepreciation.value / asset.value.acquisition_cost) * 100).toFixed(1)
    })
    
    const assetAge = computed(() => {
      if (!asset.value) return 'N/A'
      const acquisitionDate = new Date(asset.value.acquisition_date)
      const now = new Date()
      const years = Math.floor((now - acquisitionDate) / (365.25 * 24 * 60 * 60 * 1000))
      const months = Math.floor(((now - acquisitionDate) % (365.25 * 24 * 60 * 60 * 1000)) / (30.44 * 24 * 60 * 60 * 1000))
      
      if (years === 0) return `${months} months`
      if (months === 0) return `${years} years`
      return `${years} years, ${months} months`
    })
    
    const estimatedRemainingLife = computed(() => {
      if (!asset.value || !asset.value.useful_life_years) return 'N/A'
      const acquisitionDate = new Date(asset.value.acquisition_date)
      const now = new Date()
      const yearsElapsed = (now - acquisitionDate) / (365.25 * 24 * 60 * 60 * 1000)
      const remainingYears = Math.max(0, asset.value.useful_life_years - yearsElapsed)
      
      if (remainingYears < 1) {
        const remainingMonths = Math.floor(remainingYears * 12)
        return `${remainingMonths} months`
      }
      
      const years = Math.floor(remainingYears)
      const months = Math.floor((remainingYears - years) * 12)
      
      if (months === 0) return `${years} years`
      return `${years} years, ${months} months`
    })
    
    const projectedAnnualDepreciation = computed(() => {
      if (!asset.value) return 0
      
      const depreciableAmount = asset.value.acquisition_cost - (asset.value.salvage_value || 0)
      if (asset.value.depreciation_method === 'straight_line') {
        return depreciableAmount / (asset.value.useful_life_years || 10)
      } else {
        return depreciableAmount * (asset.value.depreciation_rate / 100)
      }
    })
    
    const exchangeRateChange = computed(() => {
      if (!currencySummary.value || !currencySummary.value.acquisition_exchange_rate || !currencySummary.value.current_exchange_rate) {
        return 0
      }
      
      const oldRate = currencySummary.value.acquisition_exchange_rate
      const newRate = currencySummary.value.current_exchange_rate
      
      return (newRate - oldRate) / oldRate
    })
    
    const currencyImpact = computed(() => {
      if (!asset.value || !currencySummary.value || asset.value.currency === currencySummary.value.base_currency) {
        return { hasImpact: false }
      }
      
      const acquisitionRate = currencySummary.value.acquisition_exchange_rate || 1
      const currentRate = currencySummary.value.current_exchange_rate || 1
      
      const acquisitionImpact = ((currentRate - acquisitionRate) / acquisitionRate) * 100
      const currentValueImpact = acquisitionImpact // Simplified calculation
      
      return {
        hasImpact: true,
        acquisitionImpact,
        currentValueImpact
      }
    })
    
    // Methods
    const fetchAsset = async () => {
      try {
        loading.value = true
        const response = await axios.get(`/accounting/fixed-assets/${route.params.id}`)
        asset.value = response.data.data
        currencySummary.value = response.data.currency_summary
        
        // Set default display currency
        if (availableCurrencies.value.includes(asset.value.currency)) {
          selectedDisplayCurrency.value = asset.value.currency
        }
        
        // Fetch depreciations
        await fetchDepreciations()
        
      } catch (error) {
        console.error('Error fetching asset:', error)
        if (error.response?.status === 404) {
          asset.value = null
        }
      } finally {
        loading.value = false
      }
    }
    
    const fetchDepreciations = async () => {
      try {
        const params = {
          asset_id: route.params.id,
          display_currency: selectedDisplayCurrency.value,
          conversion_date: conversionDate.value
        }
        
        const response = await axios.get('/accounting/asset-depreciations', { params })
        depreciations.value = response.data.data || []
        
      } catch (error) {
        console.error('Error fetching depreciations:', error)
        depreciations.value = []
      }
    }
    
    const fetchAvailableCurrencies = async () => {
      try {
        const response = await axios.get('/accounting/system-currencies')
        availableCurrencies.value = response.data.data.map(c => c.code || c.currency || c.name)
      } catch (error) {
        console.error('Error fetching currencies:', error)
      }
    }
    
    const convertAssetCurrency = async () => {
      if (!asset.value || selectedDisplayCurrency.value === asset.value.currency) {
        convertedValues.value = {}
        return
      }
      
      try {
        const response = await axios.get(`/accounting/fixed-assets/${route.params.id}/convert-currency`, {
          params: {
            target_currency: selectedDisplayCurrency.value,
            conversion_date: conversionDate.value
          }
        })
        
        convertedValues.value = response.data.data.converted_amounts
        convertedValues.value.total_depreciation = convertedValues.value.acquisition_cost - convertedValues.value.current_value
        convertedValues.value.annual_depreciation = projectedAnnualDepreciation.value * response.data.data.exchange_rate
        
      } catch (error) {
        console.error('Error converting currency:', error)
        convertedValues.value = {}
      }
    }
    
    const changeDisplayCurrency = async () => {
      await convertAssetCurrency()
      await fetchDepreciations()
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
    
    const formatDepreciationMethod = (method) => {
      const methodMap = {
        'straight_line': 'Straight Line',
        'declining_balance': 'Declining Balance',
        'sum_of_years': 'Sum of Years Digits',
        'units_of_production': 'Units of Production'
      }
      return methodMap[method] || method
    }
    
    const formatNumber = (value) => {
      return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      }).format(value || 0)
    }
    
    const formatDate = (date) => {
      return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      })
    }
    
    const getPeriodInfo = (depreciation) => {
      if (depreciation.accounting_period) {
        return depreciation.accounting_period.period_name
      }
      return 'Manual Entry'
    }
    
    const editAsset = () => {
      router.push(`/accounting/fixed-assets/${route.params.id}/edit`)
    }
    
    const calculateDepreciation = () => {
      // This would open a modal or navigate to depreciation calculation page
      console.log('Calculate depreciation for asset:', asset.value.asset_id)
    }
    
    const viewDepreciationDetail = (depreciation) => {
      // This would open a modal with detailed depreciation information
      console.log('View depreciation detail:', depreciation)
    }
    
    const goBack = () => {
      router.push('/accounting/fixed-assets')
    }
    
    // Lifecycle
    onMounted(async () => {
      await fetchAvailableCurrencies()
      await fetchAsset()
    })
    
    return {
      loading,
      asset,
      depreciations,
      activeTab,
      tabs,
      availableCurrencies,
      selectedDisplayCurrency,
      conversionDate,
      convertedValues,
      currencySummary,
      today,
      showConversionDate,
      showExchangeRateWarning,
      totalDepreciation,
      depreciationPercentage,
      assetAge,
      estimatedRemainingLife,
      projectedAnnualDepreciation,
      exchangeRateChange,
      currencyImpact,
      changeDisplayCurrency,
      getAssetIcon,
      formatDepreciationMethod,
      formatNumber,
      formatDate,
      getPeriodInfo,
      editAsset,
      calculateDepreciation,
      viewDepreciationDetail,
      goBack
    }
  }
}
</script>

<style scoped>
.asset-detail-page {
  max-width: 1400px;
  margin: 0 auto;
  padding: 2rem;
}

/* Loading and Error States */
.loading-state,
.error-state {
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

.error-icon {
  font-size: 4rem;
  color: #ef4444;
  margin-bottom: 1rem;
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

.title-section {
  flex: 1;
}

.breadcrumb {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 1rem;
  font-size: 0.875rem;
}

.breadcrumb-link {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  color: #6366f1;
  text-decoration: none;
  transition: color 0.2s ease;
}

.breadcrumb-link:hover {
  color: #5a67d8;
}

.breadcrumb-separator {
  color: #9ca3af;
}

.breadcrumb-current {
  color: #64748b;
}

.page-title {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-size: 2rem;
  font-weight: 700;
  color: #1e293b;
  margin: 0 0 1rem 0;
}

.page-title i {
  color: #6366f1;
}

.asset-meta {
  display: flex;
  align-items: center;
  gap: 1rem;
  flex-wrap: wrap;
}

.asset-code {
  background: #f1f5f9;
  color: #475569;
  padding: 0.25rem 0.75rem;
  border-radius: 6px;
  font-size: 0.875rem;
  font-weight: 500;
}

.asset-status {
  padding: 0.25rem 0.75rem;
  border-radius: 6px;
  font-size: 0.75rem;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.05em;
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

.asset-currency {
  background: #e0e7ff;
  color: #7c3aed;
  padding: 0.25rem 0.75rem;
  border-radius: 6px;
  font-size: 0.75rem;
  font-weight: 600;
}

.header-actions {
  display: flex;
  gap: 1rem;
  flex-shrink: 0;
}

/* Currency Controls */
.currency-controls {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 1.5rem;
  margin-bottom: 2rem;
  display: flex;
  gap: 2rem;
  align-items: center;
  flex-wrap: wrap;
}

.display-currency-selector,
.conversion-date {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.selector-label {
  font-weight: 500;
  color: #374151;
  font-size: 0.875rem;
}

.currency-select,
.date-input {
  padding: 0.5rem 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 0.875rem;
  background: white;
  min-width: 120px;
}

.currency-select:focus,
.date-input:focus {
  outline: none;
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

/* Summary Cards */
.summary-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.summary-card {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 1.5rem;
  transition: all 0.2s ease;
}

.summary-card:hover {
  border-color: #6366f1;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.card-header {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  margin-bottom: 1rem;
}

.card-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  flex-shrink: 0;
}

.financial .card-icon {
  background: #dbeafe;
  color: #2563eb;
}

.acquisition .card-icon {
  background: #dcfce7;
  color: #16a34a;
}

.depreciation .card-icon {
  background: #fed7d7;
  color: #dc2626;
}

.percentage .card-icon {
  background: #fef3c7;
  color: #d97706;
}

.card-title h3 {
  margin: 0 0 0.25rem 0;
  font-size: 1.125rem;
  font-weight: 600;
  color: #1e293b;
}

.card-title p {
  margin: 0;
  color: #64748b;
  font-size: 0.875rem;
}

.card-content {
  text-align: right;
}

.primary-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1e293b;
  line-height: 1;
}

.converted-value {
  color: #6b7280;
  font-size: 0.875rem;
  margin-top: 0.25rem;
  display: block;
}

/* Currency Info Panel */
.currency-info-panel {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  margin-bottom: 2rem;
  overflow: hidden;
}

.panel-header {
  background: #f8fafc;
  padding: 1rem 1.5rem;
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

.currency-details {
  padding: 1.5rem;
}

.currency-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  margin-bottom: 1rem;
}

.currency-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.5rem 0;
}

.currency-label {
  color: #64748b;
  font-size: 0.875rem;
}

.currency-value {
  font-weight: 500;
  color: #1e293b;
}

.exchange-rate-warning {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: #fef3c7;
  color: #92400e;
  padding: 0.75rem;
  border-radius: 6px;
  font-size: 0.875rem;
  margin-top: 1rem;
}

/* Tabs */
.tabs-container {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  overflow: hidden;
}

.tabs-nav {
  display: flex;
  border-bottom: 1px solid #e2e8f0;
  background: #f8fafc;
}

.tab-button {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 1rem 1.5rem;
  border: none;
  background: transparent;
  color: #64748b;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
  border-bottom: 2px solid transparent;
}

.tab-button:hover {
  color: #6366f1;
  background: rgba(99, 102, 241, 0.05);
}

.tab-button.active {
  color: #6366f1;
  border-bottom-color: #6366f1;
  background: white;
}

.tab-content {
  padding: 2rem;
}

/* Details Tab */
.details-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 2rem;
}

.detail-section h4 {
  color: #1e293b;
  font-size: 1.125rem;
  font-weight: 600;
  margin: 0 0 1rem 0;
  padding-bottom: 0.5rem;
  border-bottom: 1px solid #e2e8f0;
}

/* .detail-items {
  space-y: 0.75rem;
} */

.detail-item {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  padding: 0.5rem 0;
  border-bottom: 1px solid #f1f5f9;
}

.detail-item:last-child {
  border-bottom: none;
}

.detail-label {
  color: #64748b;
  font-size: 0.875rem;
  font-weight: 500;
  flex-shrink: 0;
  margin-right: 1rem;
}

.detail-value {
  color: #1e293b;
  font-weight: 500;
  text-align: right;
  font-size: 0.875rem;
}

.detail-value.status {
  padding: 0.125rem 0.5rem;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
}

.currency-badge {
  background: #e0e7ff;
  color: #7c3aed;
  padding: 0.125rem 0.5rem;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 600;
}

.financial-value-group {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 0.25rem;
}

.converted-detail {
  color: #6b7280;
  font-size: 0.75rem;
  font-weight: 400;
}

/* Depreciation Section */
.depreciation-section {
  max-width: 100%;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
  gap: 1rem;
}

.section-header h4 {
  color: #1e293b;
  font-size: 1.25rem;
  font-weight: 600;
  margin: 0;
}

.depreciation-stats {
  display: flex;
  gap: 1.5rem;
  align-items: center;
  flex-wrap: wrap;
}

.stat-item {
  color: #64748b;
  font-size: 0.875rem;
}

.stat-item.converted {
  color: #6366f1;
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem 1rem;
  text-align: center;
  color: #64748b;
}

.empty-icon {
  font-size: 3rem;
  color: #d1d5db;
  margin-bottom: 1rem;
}

.empty-state h5 {
  color: #374151;
  margin: 0 0 0.5rem 0;
}

.empty-state p {
  margin: 0 0 1.5rem 0;
}

/* Depreciation Table */
.depreciation-table {
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  overflow: hidden;
}

.table-header {
  display: grid;
  grid-template-columns: 1fr 1fr 1.5fr 1.5fr 1.5fr 100px;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
}

.header-cell {
  padding: 1rem;
  font-weight: 600;
  color: #374151;
  font-size: 0.875rem;
  text-align: left;
}

.table-row {
  display: grid;
  grid-template-columns: 1fr 1fr 1.5fr 1.5fr 1.5fr 100px;
  border-bottom: 1px solid #f1f5f9;
  cursor: pointer;
  transition: background-color 0.2s ease;
}

.table-row:hover {
  background: #f8fafc;
}

.table-row:last-child {
  border-bottom: none;
}

.table-cell {
  padding: 1rem;
  display: flex;
  align-items: center;
  font-size: 0.875rem;
}

.period-name {
  color: #1e293b;
  font-weight: 500;
}

.depreciation-date {
  color: #64748b;
}

.amount-group {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.primary-amount {
  color: #1e293b;
  font-weight: 500;
}

.converted-amount {
  color: #6b7280;
  font-size: 0.75rem;
}

/* Currency Analysis */
.currency-analysis {
  max-width: 100%;
}

.analysis-header {
  margin-bottom: 2rem;
}

.analysis-header h4 {
  color: #1e293b;
  font-size: 1.25rem;
  font-weight: 600;
  margin: 0 0 0.5rem 0;
}

.analysis-header p {
  color: #64748b;
  margin: 0;
}

.analysis-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
  gap: 1.5rem;
}

.analysis-card {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 1.5rem;
}

.analysis-card h5 {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #1e293b;
  font-size: 1rem;
  font-weight: 600;
  margin: 0 0 1rem 0;
}

.analysis-card h5 i {
  color: #6366f1;
}

.rate-timeline,
.value-comparison,
/* .impact-analysis {
  space-y: 0.75rem;
} */

.rate-item,
.comparison-row,
.impact-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.5rem 0;
  border-bottom: 1px solid #e2e8f0;
}

.rate-item:last-child,
.comparison-row:last-child,
.impact-item:last-child {
  border-bottom: none;
}

.rate-label,
.comparison-label,
.impact-label {
  color: #64748b;
  font-size: 0.875rem;
}

.rate-value,
.comparison-value {
  color: #1e293b;
  font-weight: 500;
  font-size: 0.875rem;
}

.rate-change {
  background: #f0f9ff;
  border: 1px solid #bae6fd;
  border-radius: 6px;
  padding: 0.5rem;
  margin-top: 0.5rem;
}

.change-label {
  color: #0369a1;
  font-size: 0.75rem;
  font-weight: 500;
}

.change-value {
  font-weight: 600;
  font-size: 0.875rem;
}

.change-value.positive {
  color: #16a34a;
}

.change-value.negative {
  color: #dc2626;
}

.impact-value {
  font-weight: 600;
  font-size: 0.875rem;
}

.impact-value.positive {
  color: #16a34a;
}

.impact-value.negative {
  color: #dc2626;
}

.no-impact {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  color: #64748b;
  font-style: italic;
  padding: 1rem;
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

.btn-sm {
  padding: 0.375rem 0.75rem;
  font-size: 0.75rem;
}

/* Responsive Design */
@media (max-width: 768px) {
  .asset-detail-page {
    padding: 1rem;
  }
  
  .header-content {
    flex-direction: column;
    gap: 1rem;
  }
  
  .currency-controls {
    flex-direction: column;
    align-items: stretch;
    gap: 1rem;
  }
  
  .summary-cards {
    grid-template-columns: 1fr;
  }
  
  .details-grid {
    grid-template-columns: 1fr;
  }
  
  .analysis-grid {
    grid-template-columns: 1fr;
  }
  
  .table-header,
  .table-row {
    grid-template-columns: 1fr;
    gap: 0.5rem;
  }
  
  .table-cell {
    padding: 0.5rem;
    border-bottom: 1px solid #f1f5f9;
  }
  
  .header-cell {
    display: none;
  }
  
  .table-cell::before {
    content: attr(data-label);
    font-weight: 600;
    color: #374151;
    margin-right: 0.5rem;
    min-width: 100px;
  }
}
</style>