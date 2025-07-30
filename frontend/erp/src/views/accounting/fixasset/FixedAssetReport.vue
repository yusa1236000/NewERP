<template>
  <AppLayout>
    <div class="asset-report-page">
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
              <span class="breadcrumb-current">Reports</span>
            </div>
            <h1 class="page-title">
              <i class="fas fa-chart-bar"></i>
              Fixed Assets Reports
            </h1>
            <p class="page-subtitle">Generate comprehensive reports for your fixed assets with multi-currency support</p>
          </div>
          <div class="header-actions">
            <button @click="goBack" class="btn btn-outline">
              <i class="fas fa-arrow-left"></i>
              Back to Assets
            </button>
          </div>
        </div>
      </div>

      <!-- Report Configuration -->
      <div class="report-config-section">
        <div class="config-header">
          <h2>
            <i class="fas fa-cog"></i>
            Report Configuration
          </h2>
          <p>Configure your report parameters and currency settings</p>
        </div>

        <div class="config-form">
          <div class="config-grid">
            <!-- Report Type -->
            <div class="config-group">
              <label class="config-label">Report Type</label>
              <select v-model="reportConfig.type" @change="onReportTypeChange" class="config-select">
                <option value="summary">Asset Summary</option>
                <option value="detailed">Detailed Asset List</option>
                <option value="depreciation">Depreciation Report</option>
                <option value="currency_analysis">Currency Analysis</option>
                <option value="valuation">Asset Valuation</option>
              </select>
            </div>

            <!-- Date Range -->
            <div class="config-group">
              <label class="config-label">As of Date</label>
              <input 
                v-model="reportConfig.asOfDate" 
                type="date" 
                class="config-input"
                :max="today"
              >
            </div>

            <!-- Currency Settings -->
            <div class="config-group">
              <label class="config-label">Display Currency</label>
              <select v-model="reportConfig.displayCurrency" @change="onCurrencyChange" class="config-select">
                <option value="">All Currencies (Original)</option>
                <option v-for="currency in availableCurrencies" :key="currency" :value="currency">
                  {{ currency }}
                </option>
              </select>
            </div>

            <!-- Filter by Asset Currency -->
            <div class="config-group">
              <label class="config-label">Filter by Asset Currency</label>
              <select v-model="reportConfig.filterCurrency" class="config-select">
                <option value="">All Currencies</option>
                <option v-for="currency in availableCurrencies" :key="currency" :value="currency">
                  {{ currency }}
                </option>
              </select>
            </div>

            <!-- Category Filter -->
            <div class="config-group">
              <label class="config-label">Category</label>
              <select v-model="reportConfig.category" class="config-select">
                <option value="">All Categories</option>
                <option v-for="category in categories" :key="category" :value="category">
                  {{ category }}
                </option>
              </select>
            </div>

            <!-- Status Filter -->
            <div class="config-group">
              <label class="config-label">Status</label>
              <select v-model="reportConfig.status" class="config-select">
                <option value="">All Status</option>
                <option value="Active">Active</option>
                <option value="Inactive">Inactive</option>
                <option value="Disposed">Disposed</option>
                <option value="Under Maintenance">Under Maintenance</option>
              </select>
            </div>
          </div>

          <!-- Currency Conversion Options -->
          <div v-if="reportConfig.displayCurrency" class="currency-options">
            <div class="options-header">
              <h3>
                <i class="fas fa-exchange-alt"></i>
                Currency Conversion Options
              </h3>
            </div>
            <div class="options-grid">
              <div class="option-group">
                <label class="option-label">
                  <input 
                    v-model="reportConfig.showOriginalCurrency" 
                    type="checkbox"
                    class="option-checkbox"
                  >
                  Show original currency values
                </label>
              </div>
              <div class="option-group">
                <label class="option-label">
                  <input 
                    v-model="reportConfig.showExchangeRates" 
                    type="checkbox"
                    class="option-checkbox"
                  >
                  Include exchange rates
                </label>
              </div>
              <div class="option-group">
                <label class="option-label">
                  <input 
                    v-model="reportConfig.groupByCurrency" 
                    type="checkbox"
                    class="option-checkbox"
                  >
                  Group by currency
                </label>
              </div>
            </div>
          </div>

          <!-- Report Actions -->
          <div class="config-actions">
            <button @click="generateReport" :disabled="loading" class="btn btn-primary">
              <i :class="loading ? 'fas fa-spinner fa-spin' : 'fas fa-play'"></i>
              {{ loading ? 'Generating...' : 'Generate Report' }}
            </button>
            <button @click="resetConfig" class="btn btn-secondary">
              <i class="fas fa-undo"></i>
              Reset
            </button>
          </div>
        </div>
      </div>

      <!-- Report Preview/Results -->
      <div v-if="reportData" class="report-results-section">
        <div class="results-header">
          <h2>
            <i class="fas fa-chart-line"></i>
            {{ getReportTitle() }}
          </h2>
          <div class="results-actions">
            <button @click="exportToPDF" class="btn btn-outline">
              <i class="fas fa-file-pdf"></i>
              Export PDF
            </button>
            <button @click="exportToExcel" class="btn btn-outline">
              <i class="fas fa-file-excel"></i>
              Export Excel
            </button>
            <button @click="printReport" class="btn btn-outline">
              <i class="fas fa-print"></i>
              Print
            </button>
          </div>
        </div>

        <!-- Report Metadata -->
        <div class="report-metadata">
          <div class="metadata-grid">
            <div class="metadata-item">
              <span class="metadata-label">Generated:</span>
              <span class="metadata-value">{{ formatDateTime(new Date()) }}</span>
            </div>
            <div class="metadata-item">
              <span class="metadata-label">As of Date:</span>
              <span class="metadata-value">{{ formatDate(reportConfig.asOfDate) }}</span>
            </div>
            <div class="metadata-item">
              <span class="metadata-label">Total Assets:</span>
              <span class="metadata-value">{{ reportData.totalAssets || 0 }}</span>
            </div>
            <div v-if="reportConfig.displayCurrency" class="metadata-item">
              <span class="metadata-label">Display Currency:</span>
              <span class="metadata-value">{{ reportConfig.displayCurrency }}</span>
            </div>
          </div>
        </div>

        <!-- Summary Report -->
        <div v-if="reportConfig.type === 'summary'" class="report-content">
          <div class="summary-cards">
            <div class="summary-card total-value">
              <div class="card-header">
                <h4>Total Asset Value</h4>
              </div>
              <div class="card-content">
                <div class="primary-value">
                  {{ reportConfig.displayCurrency || 'Mixed' }} {{ formatNumber(reportData.summary?.totalValue || 0) }}
                </div>
                <div v-if="reportData.summary?.originalTotalValue && reportConfig.showOriginalCurrency" class="secondary-value">
                  Original: {{ formatCurrencyBreakdown(reportData.summary.originalTotalValue) }}
                </div>
              </div>
            </div>

            <div class="summary-card depreciation">
              <div class="card-header">
                <h4>Total Depreciation</h4>
              </div>
              <div class="card-content">
                <div class="primary-value">
                  {{ reportConfig.displayCurrency || 'Mixed' }} {{ formatNumber(reportData.summary?.totalDepreciation || 0) }}
                </div>
                <div v-if="reportData.summary?.originalTotalDepreciation && reportConfig.showOriginalCurrency" class="secondary-value">
                  Original: {{ formatCurrencyBreakdown(reportData.summary.originalTotalDepreciation) }}
                </div>
              </div>
            </div>

            <div class="summary-card by-category">
              <div class="card-header">
                <h4>By Category</h4>
              </div>
              <div class="card-content">
                <div class="category-breakdown">
                  <div v-for="(value, category) in reportData.summary?.byCategory" :key="category" class="category-item">
                    <span class="category-name">{{ category }}:</span>
                    <span class="category-value">{{ formatNumber(value) }}</span>
                  </div>
                </div>
              </div>
            </div>

            <div v-if="reportData.summary?.byCurrency" class="summary-card by-currency">
              <div class="card-header">
                <h4>By Currency</h4>
              </div>
              <div class="card-content">
                <div class="currency-breakdown">
                  <div v-for="(data, currency) in reportData.summary.byCurrency" :key="currency" class="currency-item">
                    <div class="currency-header">
                      <span class="currency-code">{{ currency }}</span>
                      <span class="asset-count">{{ data.count }} assets</span>
                    </div>
                    <div class="currency-amounts">
                      <div class="amount-row">
                        <span>Value: {{ currency }} {{ formatNumber(data.totalValue) }}</span>
                      </div>
                      <div v-if="data.convertedValue && reportConfig.displayCurrency" class="amount-row converted">
                        <span>Converted: {{ reportConfig.displayCurrency }} {{ formatNumber(data.convertedValue) }}</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Detailed Report -->
        <div v-if="reportConfig.type === 'detailed'" class="report-content">
          <div class="detailed-table">
            <div class="table-header">
              <div class="header-cell">Asset Code</div>
              <div class="header-cell">Name</div>
              <div class="header-cell">Category</div>
              <div class="header-cell">Currency</div>
              <div class="header-cell">Acquisition Cost</div>
              <div class="header-cell">Current Value</div>
              <div class="header-cell">Depreciation</div>
              <div v-if="reportConfig.showExchangeRates" class="header-cell">Exchange Rate</div>
            </div>

            <div v-for="asset in reportData.assets" :key="asset.asset_id" class="table-row">
              <div class="table-cell" data-label="Asset Code">{{ asset.asset_code }}</div>
              <div class="table-cell" data-label="Name">{{ asset.name }}</div>
              <div class="table-cell" data-label="Category">{{ asset.category }}</div>
              <div class="table-cell" data-label="Currency">
                <span class="currency-badge">{{ asset.currency }}</span>
              </div>
              <div class="table-cell" data-label="Acquisition Cost">
                <div class="amount-group">
                  <span class="primary-amount">{{ asset.currency }} {{ formatNumber(asset.acquisition_cost) }}</span>
                  <span v-if="asset.converted_acquisition_cost && reportConfig.displayCurrency" class="converted-amount">
                    {{ reportConfig.displayCurrency }} {{ formatNumber(asset.converted_acquisition_cost) }}
                  </span>
                </div>
              </div>
              <div class="table-cell" data-label="Current Value">
                <div class="amount-group">
                  <span class="primary-amount">{{ asset.currency }} {{ formatNumber(asset.current_value) }}</span>
                  <span v-if="asset.converted_current_value && reportConfig.displayCurrency" class="converted-amount">
                    {{ reportConfig.displayCurrency }} {{ formatNumber(asset.converted_current_value) }}
                  </span>
                </div>
              </div>
              <div class="table-cell" data-label="Depreciation">
                <div class="amount-group">
                  <span class="primary-amount">{{ asset.currency }} {{ formatNumber(asset.acquisition_cost - asset.current_value) }}</span>
                  <span v-if="asset.converted_depreciation && reportConfig.displayCurrency" class="converted-amount">
                    {{ reportConfig.displayCurrency }} {{ formatNumber(asset.converted_depreciation) }}
                  </span>
                </div>
              </div>
              <div v-if="reportConfig.showExchangeRates" class="table-cell" data-label="Exchange Rate">
                {{ asset.exchange_rate || 1.0 }}
              </div>
            </div>
          </div>
        </div>

        <!-- Currency Analysis Report -->
        <div v-if="reportConfig.type === 'currency_analysis'" class="report-content">
          <div class="currency-analysis-grid">
            <div v-for="(analysis, currency) in reportData.currencyAnalysis" :key="currency" class="analysis-card">
              <div class="analysis-header">
                <h4>{{ currency }} Analysis</h4>
                <span class="asset-count">{{ analysis.assetCount }} assets</span>
              </div>
              
              <div class="analysis-metrics">
                <div class="metric-row">
                  <span class="metric-label">Total Acquisition Cost:</span>
                  <span class="metric-value">{{ currency }} {{ formatNumber(analysis.totalAcquisitionCost) }}</span>
                </div>
                <div class="metric-row">
                  <span class="metric-label">Total Current Value:</span>
                  <span class="metric-value">{{ currency }} {{ formatNumber(analysis.totalCurrentValue) }}</span>
                </div>
                <div class="metric-row">
                  <span class="metric-label">Total Depreciation:</span>
                  <span class="metric-value">{{ currency }} {{ formatNumber(analysis.totalDepreciation) }}</span>
                </div>
                <div class="metric-row">
                  <span class="metric-label">Average Age:</span>
                  <span class="metric-value">{{ analysis.averageAge }} years</span>
                </div>
              </div>

              <div v-if="analysis.exchangeRateInfo" class="exchange-rate-section">
                <h5>Exchange Rate Impact</h5>
                <div class="rate-info">
                  <div class="rate-row">
                    <span>Current Rate (to {{ baseCurrency }}):</span>
                    <span>{{ analysis.exchangeRateInfo.currentRate }}</span>
                  </div>
                  <div class="rate-row">
                    <span>Converted Total Value:</span>
                    <span>{{ baseCurrency }} {{ formatNumber(analysis.exchangeRateInfo.convertedTotalValue) }}</span>
                  </div>
                </div>
              </div>

              <div class="category-distribution">
                <h5>Category Distribution</h5>
                <div class="distribution-items">
                  <div v-for="(count, category) in analysis.categoryDistribution" :key="category" class="distribution-item">
                    <span class="category-name">{{ category }}:</span>
                    <span class="category-count">{{ count }} assets</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Depreciation Report -->
        <div v-if="reportConfig.type === 'depreciation'" class="report-content">
          <div class="depreciation-summary">
            <div class="summary-stats">
              <div class="stat-card">
                <h4>Total Accumulated Depreciation</h4>
                <div class="stat-value">
                  {{ reportConfig.displayCurrency || 'Mixed' }} {{ formatNumber(reportData.depreciationSummary?.totalAccumulated || 0) }}
                </div>
              </div>
              <div class="stat-card">
                <h4>Annual Depreciation Rate</h4>
                <div class="stat-value">
                  {{ reportConfig.displayCurrency || 'Mixed' }} {{ formatNumber(reportData.depreciationSummary?.annualRate || 0) }}
                </div>
              </div>
              <div class="stat-card">
                <h4>Remaining Depreciable Value</h4>
                <div class="stat-value">
                  {{ reportConfig.displayCurrency || 'Mixed' }} {{ formatNumber(reportData.depreciationSummary?.remainingValue || 0) }}
                </div>
              </div>
            </div>
          </div>

          <!-- Depreciation by Period -->
          <div v-if="reportData.depreciationByPeriod" class="depreciation-by-period">
            <h4>Depreciation by Period</h4>
            <div class="period-table">
              <div class="period-header">
                <div class="period-cell">Period</div>
                <div class="period-cell">Assets Count</div>
                <div class="period-cell">Depreciation Amount</div>
                <div class="period-cell">Accumulated Total</div>
              </div>
              <div v-for="period in reportData.depreciationByPeriod" :key="period.period" class="period-row">
                <div class="period-cell">{{ period.period }}</div>
                <div class="period-cell">{{ period.assetsCount }}</div>
                <div class="period-cell">
                  <div class="amount-group">
                    <span class="primary-amount">{{ formatCurrencyValue(period.depreciationAmount) }}</span>
                    <span v-if="period.convertedDepreciationAmount && reportConfig.displayCurrency" class="converted-amount">
                      {{ reportConfig.displayCurrency }} {{ formatNumber(period.convertedDepreciationAmount) }}
                    </span>
                  </div>
                </div>
                <div class="period-cell">
                  <div class="amount-group">
                    <span class="primary-amount">{{ formatCurrencyValue(period.accumulatedTotal) }}</span>
                    <span v-if="period.convertedAccumulatedTotal && reportConfig.displayCurrency" class="converted-amount">
                      {{ reportConfig.displayCurrency }} {{ formatNumber(period.convertedAccumulatedTotal) }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

export default {
  name: 'FixedAssetReport',
  components: {
  },
  setup() {
    const router = useRouter()
    
    // Reactive data
    const loading = ref(false)
    const reportData = ref(null)
    const availableCurrencies = ref(['USD'])
    const categories = ref([])
    const baseCurrency = ref('USD')
    
    const reportConfig = reactive({
      type: 'summary',
      asOfDate: new Date().toISOString().split('T')[0],
      displayCurrency: '',
      filterCurrency: '',
      category: '',
      status: '',
      showOriginalCurrency: true,
      showExchangeRates: false,
      groupByCurrency: true
    })
    
    // Computed
    const today = computed(() => new Date().toISOString().split('T')[0])
    
    // Methods
    const fetchAvailableCurrencies = async () => {
      try {
        const response = await axios.get('/accounting/system-currencies')
        availableCurrencies.value = response.data.data.map(c => c.code || c.currency || c.name)
        
        // Set base currency
        baseCurrency.value = 'USD' // This should come from app config
      } catch (error) {
        console.error('Error fetching currencies:', error)
      }
    }
    
    const fetchCategories = async () => {
      try {
        const response = await axios.get('/accounting/fixed-assets')
        const assets = response.data.data || []
        categories.value = [...new Set(assets.map(asset => asset.category))].filter(Boolean)
      } catch (error) {
        console.error('Error fetching categories:', error)
      }
    }
    
    const onReportTypeChange = () => {
      // Reset specific configurations based on report type
      if (reportConfig.type === 'currency_analysis') {
        reportConfig.groupByCurrency = true
      }
    }
    
    const onCurrencyChange = () => {
      // Update related configurations when currency changes
      if (reportConfig.displayCurrency) {
        reportConfig.showOriginalCurrency = true
      }
    }
    
    const generateReport = async () => {
      try {
        loading.value = true
        
        const params = {
          type: reportConfig.type,
          as_of_date: reportConfig.asOfDate,
          display_currency: reportConfig.displayCurrency,
          filter_currency: reportConfig.filterCurrency,
          category: reportConfig.category,
          status: reportConfig.status,
          show_original_currency: reportConfig.showOriginalCurrency,
          show_exchange_rates: reportConfig.showExchangeRates,
          group_by_currency: reportConfig.groupByCurrency
        }
        
        // Remove empty parameters
        Object.keys(params).forEach(key => {
          if (params[key] === '' || params[key] === false) {
            delete params[key]
          }
        })
        
        let response
        
        switch (reportConfig.type) {
          case 'summary':
            response = await generateSummaryReport(params)
            break
          case 'detailed':
            response = await generateDetailedReport(params)
            break
          case 'depreciation':
            response = await generateDepreciationReport(params)
            break
          case 'currency_analysis':
            response = await generateCurrencyAnalysisReport(params)
            break
          case 'valuation':
            response = await generateValuationReport(params)
            break
          default:
            throw new Error('Invalid report type')
        }
        
        reportData.value = response.data
        
      } catch (error) {
        console.error('Error generating report:', error)
        // Show error message to user
      } finally {
        loading.value = false
      }
    }
    
    const generateSummaryReport = async (params) => {
      // Get currency summary
      const currencySummaryResponse = await axios.get('/accounting/fixed-assets/currency-summary', { params })
      
      // Get basic assets data
      const assetsResponse = await axios.get('/accounting/fixed-assets', { params })
      const assets = assetsResponse.data.data || []
      
      // Calculate summary statistics
      const summary = {
        totalValue: 0,
        totalDepreciation: 0,
        byCategory: {},
        byCurrency: {},
        originalTotalValue: {},
        originalTotalDepreciation: {}
      }
      
      assets.forEach(asset => {
        const currentValue = parseFloat(asset.converted_current_value || asset.current_value || 0)
        const acquisitionCost = parseFloat(asset.converted_acquisition_cost || asset.acquisition_cost || 0)
        const depreciation = acquisitionCost - currentValue
        
        summary.totalValue += currentValue
        summary.totalDepreciation += depreciation
        
        // By category
        if (!summary.byCategory[asset.category]) {
          summary.byCategory[asset.category] = 0
        }
        summary.byCategory[asset.category] += currentValue
        
        // Original currency tracking
        if (!summary.originalTotalValue[asset.currency]) {
          summary.originalTotalValue[asset.currency] = 0
          summary.originalTotalDepreciation[asset.currency] = 0
        }
        summary.originalTotalValue[asset.currency] += parseFloat(asset.current_value || 0)
        summary.originalTotalDepreciation[asset.currency] += parseFloat(asset.acquisition_cost || 0) - parseFloat(asset.current_value || 0)
      })
      
      return {
        data: {
          summary,
          totalAssets: assets.length,
          currencySummary: currencySummaryResponse.data.data
        }
      }
    }
    
    const generateDetailedReport = async (params) => {
      const response = await axios.get('/accounting/fixed-assets', { params })
      return {
        data: {
          assets: response.data.data || [],
          totalAssets: response.data.total || 0
        }
      }
    }
    
    const generateDepreciationReport = async (params) => {
      const depreciationsResponse = await axios.get('/accounting/asset-depreciations', { params })
      const depreciations = depreciationsResponse.data.data || []
      
      // Calculate depreciation summary
      const depreciationSummary = {
        totalAccumulated: 0,
        annualRate: 0,
        remainingValue: 0
      }
      
      // Group by period
      const depreciationByPeriod = {}
      
      depreciations.forEach(depreciation => {
        const amount = parseFloat(depreciation.converted_depreciation_amount || depreciation.depreciation_amount || 0)
        const accumulated = parseFloat(depreciation.converted_accumulated_depreciation || depreciation.accumulated_depreciation || 0)
        
        depreciationSummary.totalAccumulated += accumulated
        
        const period = depreciation.accounting_period?.period_name || 'Manual'
        if (!depreciationByPeriod[period]) {
          depreciationByPeriod[period] = {
            period,
            assetsCount: 0,
            depreciationAmount: 0,
            accumulatedTotal: 0
          }
        }
        
        depreciationByPeriod[period].assetsCount += 1
        depreciationByPeriod[period].depreciationAmount += amount
        depreciationByPeriod[period].accumulatedTotal += accumulated
      })
      
      return {
        data: {
          depreciationSummary,
          depreciationByPeriod: Object.values(depreciationByPeriod),
          totalAssets: new Set(depreciations.map(d => d.asset_id)).size
        }
      }
    }
    
    const generateCurrencyAnalysisReport = async (params) => {
      const assetsResponse = await axios.get('/accounting/fixed-assets', { params })
      const assets = assetsResponse.data.data || []
      
      // Group assets by currency
      const currencyAnalysis = {}
      
      assets.forEach(asset => {
        const currency = asset.currency
        
        if (!currencyAnalysis[currency]) {
          currencyAnalysis[currency] = {
            assetCount: 0,
            totalAcquisitionCost: 0,
            totalCurrentValue: 0,
            totalDepreciation: 0,
            categoryDistribution: {},
            averageAge: 0,
            exchangeRateInfo: null
          }
        }
        
        const analysis = currencyAnalysis[currency]
        analysis.assetCount += 1
        analysis.totalAcquisitionCost += parseFloat(asset.acquisition_cost || 0)
        analysis.totalCurrentValue += parseFloat(asset.current_value || 0)
        analysis.totalDepreciation += parseFloat(asset.acquisition_cost || 0) - parseFloat(asset.current_value || 0)
        
        // Category distribution
        if (!analysis.categoryDistribution[asset.category]) {
          analysis.categoryDistribution[asset.category] = 0
        }
        analysis.categoryDistribution[asset.category] += 1
        
        // Calculate average age
        const acquisitionDate = new Date(asset.acquisition_date)
        const now = new Date()
        const ageInYears = (now - acquisitionDate) / (365.25 * 24 * 60 * 60 * 1000)
        analysis.averageAge = (analysis.averageAge * (analysis.assetCount - 1) + ageInYears) / analysis.assetCount
        
        // Exchange rate info
        if (asset.exchange_rate && currency !== baseCurrency.value) {
          analysis.exchangeRateInfo = {
            currentRate: asset.exchange_rate,
            convertedTotalValue: analysis.totalCurrentValue * asset.exchange_rate
          }
        }
      })
      
      // Round average ages
      Object.values(currencyAnalysis).forEach(analysis => {
        analysis.averageAge = Math.round(analysis.averageAge * 10) / 10
      })
      
      return {
        data: {
          currencyAnalysis,
          totalAssets: assets.length
        }
      }
    }
    
    const generateValuationReport = async (params) => {
      // Similar to detailed report but focused on valuation aspects
      return generateDetailedReport(params)
    }
    
    const resetConfig = () => {
      Object.assign(reportConfig, {
        type: 'summary',
        asOfDate: new Date().toISOString().split('T')[0],
        displayCurrency: '',
        filterCurrency: '',
        category: '',
        status: '',
        showOriginalCurrency: true,
        showExchangeRates: false,
        groupByCurrency: true
      })
      reportData.value = null
    }
    
    const getReportTitle = () => {
      const titles = {
        summary: 'Asset Summary Report',
        detailed: 'Detailed Asset Report',
        depreciation: 'Depreciation Report',
        currency_analysis: 'Currency Analysis Report',
        valuation: 'Asset Valuation Report'
      }
      return titles[reportConfig.type] || 'Asset Report'
    }
    
    const exportToPDF = () => {
      // Implement PDF export functionality
      console.log('Exporting to PDF...')
    }
    
    const exportToExcel = () => {
      // Implement Excel export functionality
      console.log('Exporting to Excel...')
    }
    
    const printReport = () => {
      window.print()
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
    
    const formatDateTime = (date) => {
      return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      })
    }
    
    const formatCurrencyBreakdown = (currencyValues) => {
      if (typeof currencyValues === 'object' && currencyValues !== null) {
        return Object.entries(currencyValues)
          .map(([currency, amount]) => `${currency} ${formatNumber(amount)}`)
          .join(', ')
      }
      return formatNumber(currencyValues)
    }
    
    const formatCurrencyValue = (value) => {
      if (typeof value === 'object' && value !== null) {
        return Object.entries(value)
          .map(([currency, amount]) => `${currency} ${formatNumber(amount)}`)
          .join(', ')
      }
      return formatNumber(value)
    }
    
    const goBack = () => {
      router.push('/accounting/fixed-assets')
    }
    
    // Lifecycle
    onMounted(async () => {
      await fetchAvailableCurrencies()
      await fetchCategories()
    })
    
    return {
      loading,
      reportData,
      reportConfig,
      availableCurrencies,
      categories,
      baseCurrency,
      today,
      onReportTypeChange,
      onCurrencyChange,
      generateReport,
      resetConfig,
      getReportTitle,
      exportToPDF,
      exportToExcel,
      printReport,
      formatNumber,
      formatDate,
      formatDateTime,
      formatCurrencyBreakdown,
      formatCurrencyValue,
      goBack
    }
  }
}
</script>

<style scoped>
.asset-report-page {
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
  margin: 0 0 0.5rem 0;
}

.page-title i {
  color: #6366f1;
}

.page-subtitle {
  color: #64748b;
  font-size: 1rem;
  margin: 0;
}

.header-actions {
  flex-shrink: 0;
}

/* Report Configuration */
.report-config-section {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 2rem;
  margin-bottom: 2rem;
}

.config-header {
  margin-bottom: 2rem;
}

.config-header h2 {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-size: 1.5rem;
  font-weight: 600;
  color: #1e293b;
  margin: 0 0 0.5rem 0;
}

.config-header h2 i {
  color: #6366f1;
}

.config-header p {
  color: #64748b;
  margin: 0;
}

/* .config-form {
  space-y: 2rem;
} */

.config-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.config-group {
  display: flex;
  flex-direction: column;
}

.config-label {
  font-weight: 500;
  color: #374151;
  margin-bottom: 0.5rem;
  font-size: 0.875rem;
}

.config-select,
.config-input {
  padding: 0.75rem 1rem;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  font-size: 0.875rem;
  transition: all 0.2s ease;
  background: white;
}

.config-select:focus,
.config-input:focus {
  outline: none;
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

/* Currency Options */
.currency-options {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 1.5rem;
  margin-bottom: 2rem;
}

.options-header h3 {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #1e293b;
  font-size: 1.125rem;
  font-weight: 600;
  margin: 0 0 1rem 0;
}

.options-header h3 i {
  color: #6366f1;
}

.options-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1rem;
}

.option-group {
  display: flex;
  align-items: center;
}

.option-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #374151;
  font-size: 0.875rem;
  cursor: pointer;
}

.option-checkbox {
  width: 1rem;
  height: 1rem;
  accent-color: #6366f1;
}

/* Config Actions */
.config-actions {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
  padding-top: 1rem;
  border-top: 1px solid #e2e8f0;
}

/* Report Results */
.report-results-section {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  overflow: hidden;
}

.results-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem 2rem;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
}

.results-header h2 {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  color: #1e293b;
  font-size: 1.5rem;
  font-weight: 600;
  margin: 0;
}

.results-header h2 i {
  color: #6366f1;
}

.results-actions {
  display: flex;
  gap: 1rem;
}

/* Report Metadata */
.report-metadata {
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
  padding: 1rem 2rem;
}

.metadata-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}

.metadata-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.metadata-label {
  color: #64748b;
  font-size: 0.875rem;
  font-weight: 500;
}

.metadata-value {
  color: #1e293b;
  font-weight: 600;
  font-size: 0.875rem;
}

/* Report Content */
.report-content {
  padding: 2rem;
}

/* Summary Cards */
.summary-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 1.5rem;
}

.summary-card {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 1.5rem;
}

.summary-card .card-header h4 {
  color: #1e293b;
  font-size: 1.125rem;
  font-weight: 600;
  margin: 0 0 1rem 0;
}

/* .summary-card .card-content {
  space-y: 0.5rem;
} */

.primary-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1e293b;
  line-height: 1;
}

.secondary-value {
  color: #6b7280;
  font-size: 0.875rem;
  margin-top: 0.5rem;
}

.category-breakdown,
/* .currency-breakdown {
  space-y: 0.5rem;
} */

.category-item,
.currency-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.5rem 0;
  border-bottom: 1px solid #e2e8f0;
}

.category-item:last-child,
.currency-item:last-child {
  border-bottom: none;
}

.category-name,
.currency-code {
  color: #374151;
  font-weight: 500;
  font-size: 0.875rem;
}

.category-value,
.asset-count {
  color: #1e293b;
  font-weight: 600;
  font-size: 0.875rem;
}

.currency-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.5rem;
}

/* .currency-amounts {
  space-y: 0.25rem;
} */

.amount-row {
  font-size: 0.875rem;
  color: #64748b;
}

.amount-row.converted {
  color: #6366f1;
  font-weight: 500;
}

/* Detailed Table */
.detailed-table {
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  overflow: hidden;
}

.table-header {
  display: grid;
  grid-template-columns: 120px 200px 120px 80px 150px 150px 150px 100px;
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
  grid-template-columns: 120px 200px 120px 80px 150px 150px 150px 100px;
  border-bottom: 1px solid #f1f5f9;
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

.currency-badge {
  background: #e0e7ff;
  color: #7c3aed;
  padding: 0.125rem 0.5rem;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 600;
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
.currency-analysis-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
  gap: 1.5rem;
}

.analysis-card {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 1.5rem;
}

.analysis-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.analysis-header h4 {
  color: #1e293b;
  font-size: 1.25rem;
  font-weight: 600;
  margin: 0;
}

.analysis-metrics {
  margin-bottom: 1.5rem;
  /* space-y: 0.5rem; */
}

.metric-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.5rem 0;
  border-bottom: 1px solid #e2e8f0;
}

.metric-row:last-child {
  border-bottom: none;
}

.metric-label {
  color: #64748b;
  font-size: 0.875rem;
}

.metric-value {
  color: #1e293b;
  font-weight: 500;
  font-size: 0.875rem;
}

.exchange-rate-section {
  margin-bottom: 1.5rem;
}

.exchange-rate-section h5 {
  color: #1e293b;
  font-size: 1rem;
  font-weight: 600;
  margin: 0 0 0.5rem 0;
}

/* .rate-info {
  space-y: 0.25rem;
} */

.rate-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.875rem;
  color: #64748b;
}

.category-distribution h5 {
  color: #1e293b;
  font-size: 1rem;
  font-weight: 600;
  margin: 0 0 0.5rem 0;
}

/* .distribution-items {
  space-y: 0.25rem;
} */

.distribution-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.875rem;
}

.category-count {
  color: #1e293b;
  font-weight: 500;
}

/* Depreciation Report */
.depreciation-summary {
  margin-bottom: 2rem;
}

.summary-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
}

.stat-card {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 1.5rem;
  text-align: center;
}

.stat-card h4 {
  color: #1e293b;
  font-size: 1rem;
  font-weight: 600;
  margin: 0 0 1rem 0;
}

.stat-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: #6366f1;
}

.depreciation-by-period h4 {
  color: #1e293b;
  font-size: 1.25rem;
  font-weight: 600;
  margin: 0 0 1rem 0;
}

.period-table {
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  overflow: hidden;
}

.period-header {
  display: grid;
  grid-template-columns: 200px 120px 200px 200px;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
}

.period-cell {
  padding: 1rem;
  font-weight: 600;
  color: #374151;
  font-size: 0.875rem;
  text-align: left;
}

.period-row {
  display: grid;
  grid-template-columns: 200px 120px 200px 200px;
  border-bottom: 1px solid #f1f5f9;
}

.period-row:last-child {
  border-bottom: none;
}

.period-row .period-cell {
  font-weight: 500;
  color: #1e293b;
}

/* Buttons */
.btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
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

/* Responsive Design */
@media (max-width: 768px) {
  .asset-report-page {
    padding: 1rem;
  }
  
  .header-content {
    flex-direction: column;
    gap: 1rem;
  }
  
  .config-grid {
    grid-template-columns: 1fr;
  }
  
  .options-grid {
    grid-template-columns: 1fr;
  }
  
  .config-actions {
    flex-direction: column;
  }
  
  .results-header {
    flex-direction: column;
    gap: 1rem;
    align-items: stretch;
  }
  
  .results-actions {
    justify-content: stretch;
  }
  
  .metadata-grid {
    grid-template-columns: 1fr;
  }
  
  .summary-cards {
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
    content: attr(data-label) ': ';
    font-weight: 600;
    color: #374151;
    margin-right: 0.5rem;
  }
  
  .currency-analysis-grid {
    grid-template-columns: 1fr;
  }
  
  .summary-stats {
    grid-template-columns: 1fr;
  }
  
  .period-header,
  .period-row {
    grid-template-columns: 1fr;
  }
}

/* Print Styles */
@media print {
  .page-header,
  .report-config-section,
  .results-actions {
    display: none;
  }
  
  .asset-report-page {
    padding: 0;
    max-width: none;
  }
  
  .report-results-section {
    border: none;
    box-shadow: none;
  }
  
  .btn {
    display: none;
  }
}
</style>