<template>
  <div class="currency-dashboard">
    <!-- Page Header -->
    <div class="page-header">
      <div class="header-content">
        <div class="header-text">
          <h1 class="page-title">
            <i class="fas fa-coins"></i>
            Multi-Currency Dashboard
          </h1>
          <p class="page-subtitle">
            Overview of vendor payables across different currencies
          </p>
        </div>
        <div class="header-actions">
          <button @click="refreshData" :disabled="loading" class="btn btn-outline">
            <i class="fas fa-refresh" :class="{ 'fa-spin': loading }"></i>
            Refresh
          </button>
          <router-link to="/accounting/exchange-rates" class="btn btn-outline">
            <i class="fas fa-exchange-alt"></i>
            Manage Rates
          </router-link>
          <button @click="showConversionModal = true" class="btn btn-primary">
            <i class="fas fa-calculator"></i>
            Currency Converter
          </button>
        </div>
      </div>
    </div>

    <!-- Currency Selection -->
    <div class="currency-controls">
      <div class="controls-card">
        <div class="control-group">
          <label class="control-label">Base Currency for Reporting</label>
          <select v-model="baseCurrency" @change="loadDashboardData" class="control-select">
            <option v-for="currency in availableCurrencies" :key="currency.code" :value="currency.code">
              {{ currency.code }} - {{ currency.name }}
            </option>
          </select>
        </div>
        <div class="control-group">
          <label class="control-label">Date Range</label>
          <div class="date-range">
            <input v-model="dateRange.from" @change="loadDashboardData" type="date" class="control-input">
            <span class="date-separator">to</span>
            <input v-model="dateRange.to" @change="loadDashboardData" type="date" class="control-input">
          </div>
        </div>
      </div>
    </div>

    <!-- Currency Overview Cards -->
    <div class="currency-overview">
      <h2 class="section-title">
        <i class="fas fa-chart-pie"></i>
        Currency Overview
      </h2>
      <div class="currency-cards">
        <div v-for="currency in currencyData" :key="currency.currency_code" class="currency-card">
          <div class="currency-header">
            <div class="currency-icon" :class="`currency-${currency.currency_code}`">
              {{ getCurrencySymbol(currency.currency_code) }}
            </div>
            <div class="currency-info">
              <h3>{{ currency.currency_code }}</h3>
              <p>{{ getCurrencyName(currency.currency_code) }}</p>
            </div>
            <div class="currency-trend" :class="{ positive: currency.trend > 0, negative: currency.trend < 0 }">
              <i :class="currency.trend > 0 ? 'fas fa-arrow-up' : currency.trend < 0 ? 'fas fa-arrow-down' : 'fas fa-minus'"></i>
              <span>{{ Math.abs(currency.trend).toFixed(1) }}%</span>
            </div>
          </div>
          
          <div class="currency-metrics">
            <div class="metric-row">
              <span class="metric-label">Total Payables:</span>
              <span class="metric-value">{{ currency.payable_count }}</span>
            </div>
            <div class="metric-row">
              <span class="metric-label">Outstanding Amount:</span>
              <span class="metric-value amount">{{ formatCurrency(currency.total_balance, currency.currency_code) }}</span>
            </div>
            <div class="metric-row">
              <span class="metric-label">In {{ baseCurrency }}:</span>
              <span class="metric-value converted">{{ formatCurrency(currency.converted_balance, baseCurrency) }}</span>
            </div>
            <div class="metric-row">
              <span class="metric-label">Avg. Days Outstanding:</span>
              <span class="metric-value">{{ currency.avg_days_outstanding }} days</span>
            </div>
          </div>

          <div class="currency-actions">
            <router-link :to="`/accounting/vendor-payables?currency_code=${currency.currency_code}`" class="btn btn-ghost btn-sm">
              <i class="fas fa-list"></i>
              View Payables
            </router-link>
            <button @click="showCurrencyDetails(currency)" class="btn btn-ghost btn-sm">
              <i class="fas fa-chart-line"></i>
              Analytics
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Exchange Rate Status -->
    <div class="exchange-rates-section">
      <h2 class="section-title">
        <i class="fas fa-exchange-alt"></i>
        Exchange Rate Status
      </h2>
      <div class="rates-grid">
        <div v-for="rate in exchangeRates" :key="`${rate.from_currency}-${rate.to_currency}`" class="rate-card">
          <div class="rate-header">
            <div class="rate-pair">
              <span class="from-currency">{{ rate.from_currency }}</span>
              <i class="fas fa-arrow-right"></i>
              <span class="to-currency">{{ rate.to_currency }}</span>
            </div>
            <div class="rate-status" :class="getRateStatusClass(rate.last_updated)">
              <i :class="getRateStatusIcon(rate.last_updated)"></i>
              {{ getRateStatusText(rate.last_updated) }}
            </div>
          </div>
          
          <div class="rate-value">
            <span class="current-rate">{{ rate.rate }}</span>
            <div class="rate-change" :class="{ positive: rate.change > 0, negative: rate.change < 0 }">
              <i :class="rate.change > 0 ? 'fas fa-arrow-up' : rate.change < 0 ? 'fas fa-arrow-down' : 'fas fa-minus'"></i>
              <span>{{ Math.abs(rate.change).toFixed(4) }}</span>
            </div>
          </div>
          
          <div class="rate-meta">
            <div class="rate-info">
              <span class="label">Last Updated:</span>
              <span class="value">{{ formatDate(rate.last_updated) }}</span>
            </div>
            <div class="rate-info">
              <span class="label">Source:</span>
              <span class="value">{{ rate.source || 'Manual' }}</span>
            </div>
          </div>
          
          <div class="rate-actions">
            <button @click="updateRate(rate)" class="btn btn-ghost btn-sm">
              <i class="fas fa-sync"></i>
              Update
            </button>
            <button @click="viewRateHistory(rate)" class="btn btn-ghost btn-sm">
              <i class="fas fa-history"></i>
              History
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Summary Statistics -->
    <div class="summary-section">
      <h2 class="section-title">
        <i class="fas fa-chart-bar"></i>
        Summary Statistics
      </h2>
      <div class="summary-grid">
        <div class="summary-card total">
          <div class="summary-icon">
            <i class="fas fa-calculator"></i>
          </div>
          <div class="summary-content">
            <h3>{{ formatCurrency(totalOutstanding, baseCurrency) }}</h3>
            <p>Total Outstanding</p>
            <small>Across all currencies</small>
          </div>
        </div>
        
        <div class="summary-card currencies">
          <div class="summary-icon">
            <i class="fas fa-coins"></i>
          </div>
          <div class="summary-content">
            <h3>{{ activeCurrencies }}</h3>
            <p>Active Currencies</p>
            <small>With outstanding payables</small>
          </div>
        </div>
        
        <div class="summary-card exposure">
          <div class="summary-icon">
            <i class="fas fa-shield-alt"></i>
          </div>
          <div class="summary-content">
            <h3>{{ formatCurrency(foreignExposure, baseCurrency) }}</h3>
            <p>Foreign Exchange Exposure</p>
            <small>Non-{{ baseCurrency }} payables</small>
          </div>
        </div>
        
        <div class="summary-card avg-rate">
          <div class="summary-icon">
            <i class="fas fa-percent"></i>
          </div>
          <div class="summary-content">
            <h3>{{ averageRateAge }} days</h3>
            <p>Average Rate Age</p>
            <small>Since last update</small>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Activity -->
    <div class="activity-section">
      <h2 class="section-title">
        <i class="fas fa-clock"></i>
        Recent Activity
      </h2>
      <div class="activity-list">
        <div v-for="activity in recentActivity" :key="activity.id" class="activity-item">
          <div class="activity-icon" :class="activity.type">
            <i :class="getActivityIcon(activity.type)"></i>
          </div>
          <div class="activity-content">
            <div class="activity-title">{{ activity.title }}</div>
            <div class="activity-description">{{ activity.description }}</div>
            <div class="activity-meta">
              <span class="activity-time">{{ formatRelativeTime(activity.timestamp) }}</span>
              <span class="activity-amount" v-if="activity.amount">{{ formatCurrency(activity.amount, activity.currency) }}</span>
            </div>
          </div>
          <div class="activity-actions">
            <button @click="viewActivityDetail(activity)" class="btn btn-ghost btn-sm">
              <i class="fas fa-eye"></i>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Currency Conversion Modal -->
    <div v-if="showConversionModal" class="modal-overlay" @click="showConversionModal = false">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3>
            <i class="fas fa-calculator"></i>
            Currency Converter
          </h3>
          <button @click="showConversionModal = false" class="btn btn-ghost">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <div class="converter-form">
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">From Currency</label>
                <select v-model="converter.fromCurrency" @change="convertCurrency" class="form-select">
                  <option v-for="currency in availableCurrencies" :key="currency.code" :value="currency.code">
                    {{ currency.code }} - {{ currency.name }}
                  </option>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label">To Currency</label>
                <select v-model="converter.toCurrency" @change="convertCurrency" class="form-select">
                  <option v-for="currency in availableCurrencies" :key="currency.code" :value="currency.code">
                    {{ currency.code }} - {{ currency.name }}
                  </option>
                </select>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Amount</label>
                <input v-model.number="converter.amount" @input="convertCurrency" type="number" step="0.01" class="form-input">
              </div>
              <div class="form-group">
                <label class="form-label">Converted Amount</label>
                <input v-model="converter.convertedAmount" type="text" class="form-input" readonly>
              </div>
            </div>
            <div v-if="converter.rate" class="conversion-info">
              <p>Exchange Rate: 1 {{ converter.fromCurrency }} = {{ converter.rate }} {{ converter.toCurrency }}</p>
              <p>Rate Date: {{ formatDate(converter.rateDate) }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'CurrencyDashboard',
  data() {
    return {
      loading: false,
      baseCurrency: 'USD',
      showConversionModal: false,
      
      dateRange: {
        from: new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
        to: new Date().toISOString().split('T')[0]
      },
      
      availableCurrencies: [],
      currencyData: [],
      exchangeRates: [],
      recentActivity: [],
      
      totalOutstanding: 0,
      activeCurrencies: 0,
      foreignExposure: 0,
      averageRateAge: 0,
      
      converter: {
        fromCurrency: 'USD',
        toCurrency: 'IDR',
        amount: 1,
        convertedAmount: '',
        rate: null,
        rateDate: null
      },

      currencyNames: {
        'USD': 'US Dollar',
        'EUR': 'Euro',
        'GBP': 'British Pound',
        'JPY': 'Japanese Yen',
        'CNY': 'Chinese Yuan',
        'IDR': 'Indonesian Rupiah',
        'SGD': 'Singapore Dollar',
        'AUD': 'Australian Dollar',
        'CAD': 'Canadian Dollar',
        'CHF': 'Swiss Franc',
        'MYR': 'Malaysian Ringgit',
        'THB': 'Thai Baht',
        'PHP': 'Philippine Peso',
        'VND': 'Vietnamese Dong',
        'KRW': 'South Korean Won',
        'INR': 'Indian Rupee',
        'HKD': 'Hong Kong Dollar',
        'TWD': 'Taiwan Dollar',
        'NZD': 'New Zealand Dollar'
      }
    }
  },
  
  mounted() {
    this.loadCurrencies()
    this.loadDashboardData()
  },
  
  methods: {
    async loadCurrencies() {
      try {
        const response = await axios.get('/system-currencies')
        this.availableCurrencies = response.data.data || response.data
        
        // Set default base currency
        const baseCurrency = this.availableCurrencies.find(c => c.is_base_currency)
        if (baseCurrency) {
          this.baseCurrency = baseCurrency.code
        }
      } catch (error) {
        console.error('Error loading currencies:', error)
        this.$toast?.error('Failed to load currencies')
      }
    },
    
    async loadDashboardData() {
      this.loading = true
      try {
        // Load currency summary
        const summaryResponse = await axios.get('/accounting/vendor-payables/currency-summary', {
          params: {
            target_currency: this.baseCurrency,
            date_from: this.dateRange.from,
            date_to: this.dateRange.to
          }
        })
        
        this.currencyData = summaryResponse.data.data || []
        this.calculateSummaryStats()
        
        // Load exchange rates
        await this.loadExchangeRates()
        
        // Load recent activity
        await this.loadRecentActivity()
        
      } catch (error) {
        console.error('Error loading dashboard data:', error)
        this.$toast?.error('Failed to load dashboard data')
      } finally {
        this.loading = false
      }
    },
    
    async loadExchangeRates() {
      try {
        const response = await axios.get('/accounting/exchange-rates', {
          params: {
            target_currency: this.baseCurrency,
            include_trends: true
          }
        })
        
        this.exchangeRates = response.data.data || []
      } catch (error) {
        console.error('Error loading exchange rates:', error)
      }
    },
    
    async loadRecentActivity() {
      try {
        const response = await axios.get('/accounting/currency-activity', {
          params: {
            limit: 10,
            date_from: this.dateRange.from,
            date_to: this.dateRange.to
          }
        })
        
        this.recentActivity = response.data.data || []
      } catch (error) {
        console.error('Error loading recent activity:', error)
      }
    },
    
    calculateSummaryStats() {
      this.totalOutstanding = this.currencyData.reduce((sum, currency) => 
        sum + (currency.converted_balance || 0), 0)
      
      this.activeCurrencies = this.currencyData.length
      
      this.foreignExposure = this.currencyData
        .filter(currency => currency.currency_code !== this.baseCurrency)
        .reduce((sum, currency) => sum + (currency.converted_balance || 0), 0)
      
      // Calculate average rate age
      const rateAges = this.exchangeRates.map(rate => {
        const ageInDays = Math.floor((new Date() - new Date(rate.last_updated)) / (1000 * 60 * 60 * 24))
        return ageInDays
      })
      this.averageRateAge = rateAges.length > 0 ? 
        Math.round(rateAges.reduce((sum, age) => sum + age, 0) / rateAges.length) : 0
    },
    
    async convertCurrency() {
      if (!this.converter.fromCurrency || !this.converter.toCurrency || !this.converter.amount) {
        return
      }
      
      try {
        const response = await axios.get('/accounting/exchange-rates/convert', {
          params: {
            from: this.converter.fromCurrency,
            to: this.converter.toCurrency,
            amount: this.converter.amount
          }
        })
        
        this.converter.convertedAmount = this.formatCurrency(response.data.converted_amount, this.converter.toCurrency)
        this.converter.rate = response.data.rate
        this.converter.rateDate = response.data.rate_date
      } catch (error) {
        console.error('Error converting currency:', error)
        this.$toast?.error('Failed to convert currency')
      }
    },
    
    async updateRate(rate) {
      try {
        const response = await axios.post(`/accounting/exchange-rates/${rate.from_currency}/${rate.to_currency}/update`)
        
        // Update the rate in the list
        const index = this.exchangeRates.findIndex(r => 
          r.from_currency === rate.from_currency && r.to_currency === rate.to_currency)
        if (index > -1) {
          this.exchangeRates.splice(index, 1, response.data.data)
        }
        
        this.$toast?.success('Exchange rate updated successfully')
      } catch (error) {
        console.error('Error updating exchange rate:', error)
        this.$toast?.error('Failed to update exchange rate')
      }
    },
    
    refreshData() {
      this.loadDashboardData()
    },
    
    showCurrencyDetails(currency) {
      this.$router.push(`/accounting/currency-analytics/${currency.currency_code}`)
    },
    
    viewRateHistory(rate) {
      this.$router.push(`/accounting/exchange-rates/${rate.from_currency}/${rate.to_currency}/history`)
    },
    
    viewActivityDetail(activity) {
      // Navigate to specific activity detail based on type
      if (activity.type === 'payment') {
        this.$router.push(`/accounting/payable-payments/${activity.reference_id}`)
      } else if (activity.type === 'payable') {
        this.$router.push(`/payables/${activity.reference_id}`)
      }
    },
    
    getRateStatusClass(lastUpdated) {
      const ageInHours = (new Date() - new Date(lastUpdated)) / (1000 * 60 * 60)
      if (ageInHours < 24) return 'fresh'
      if (ageInHours < 72) return 'recent'
      return 'stale'
    },
    
    getRateStatusIcon(lastUpdated) {
      const ageInHours = (new Date() - new Date(lastUpdated)) / (1000 * 60 * 60)
      if (ageInHours < 24) return 'fas fa-check-circle'
      if (ageInHours < 72) return 'fas fa-exclamation-triangle'
      return 'fas fa-times-circle'
    },
    
    getRateStatusText(lastUpdated) {
      const ageInHours = (new Date() - new Date(lastUpdated)) / (1000 * 60 * 60)
      if (ageInHours < 24) return 'Fresh'
      if (ageInHours < 72) return 'Recent'
      return 'Stale'
    },
    
    getActivityIcon(type) {
      const icons = {
        'payment': 'fas fa-credit-card',
        'payable': 'fas fa-file-invoice',
        'rate_update': 'fas fa-exchange-alt',
        'conversion': 'fas fa-calculator'
      }
      return icons[type] || 'fas fa-info-circle'
    },
    
    getCurrencySymbol(currencyCode) {
      const symbols = {
        'USD': '$',
        'EUR': '€',
        'GBP': '£',
        'JPY': '¥',
        'CNY': '¥',
        'IDR': 'Rp',
        'SGD': 'S$',
        'AUD': 'A$',
        'CAD': 'C$',
        'CHF': 'CHF',
        'MYR': 'RM',
        'THB': '฿',
        'PHP': '₱',
        'VND': '₫',
        'KRW': '₩',
        'INR': '₹',
        'HKD': 'HK$',
        'TWD': 'NT$',
        'NZD': 'NZ$'
      }
      return symbols[currencyCode] || currencyCode
    },
    
    getCurrencyName(code) {
      return this.currencyNames[code] || code
    },
    
    formatCurrency(amount, currency = 'USD') {
      if (!amount && amount !== 0) return '-'
      
      const options = {
        style: 'currency',
        currency: currency,
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      }
      
      try {
        return new Intl.NumberFormat('en-US', options).format(amount)
      } catch (error) {
        return `${this.getCurrencySymbol(currency)} ${Number(amount).toLocaleString()}`
      }
    },
    
    formatDate(date) {
      if (!date) return '-'
      return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      })
    },
    
    formatRelativeTime(timestamp) {
      const now = new Date()
      const time = new Date(timestamp)
      const diffInHours = Math.floor((now - time) / (1000 * 60 * 60))
      
      if (diffInHours < 1) return 'Just now'
      if (diffInHours < 24) return `${diffInHours} hours ago`
      
      const diffInDays = Math.floor(diffInHours / 24)
      if (diffInDays < 7) return `${diffInDays} days ago`
      
      return this.formatDate(timestamp)
    }
  }
}
</script>

<style scoped>
.currency-dashboard {
  padding: 2rem;
  background: #f8fafc;
  min-height: 100vh;
}

.page-header {
  background: white;
  border-radius: 16px;
  padding: 2rem;
  margin-bottom: 2rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.header-text h1 {
  font-size: 2rem;
  font-weight: 700;
  color: #1f2937;
  margin-bottom: 0.5rem;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.header-text h1 i {
  color: #6366f1;
}

.page-subtitle {
  color: #6b7280;
  font-size: 1.1rem;
}

.header-actions {
  display: flex;
  gap: 1rem;
}

/* Controls */
.currency-controls {
  background: white;
  border-radius: 16px;
  padding: 1.5rem;
  margin-bottom: 2rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}

.controls-card {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1rem;
}

.control-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.control-label {
  font-weight: 500;
  color: #374151;
  font-size: 0.9rem;
}

.control-select,
.control-input {
  padding: 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 0.9rem;
  background: white;
}

.date-range {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.date-separator {
  color: #6b7280;
  font-size: 0.9rem;
}

/* Section Titles */
.section-title {
  color: #1f2937;
  font-weight: 600;
  margin-bottom: 1.5rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

/* Currency Overview */
.currency-overview {
  margin-bottom: 2rem;
}

.currency-cards {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 1.5rem;
}

.currency-card {
  background: white;
  border-radius: 16px;
  padding: 1.5rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
  transition: transform 0.3s ease;
}

.currency-card:hover {
  transform: translateY(-2px);
}

.currency-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1rem;
}

.currency-icon {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 1.2rem;
  color: white;
}

.currency-USD { background: linear-gradient(135deg, #3b82f6, #1d4ed8); }
.currency-EUR { background: linear-gradient(135deg, #f59e0b, #d97706); }
.currency-GBP { background: linear-gradient(135deg, #10b981, #059669); }
.currency-JPY { background: linear-gradient(135deg, #ef4444, #dc2626); }
.currency-IDR { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }
.currency-SGD { background: linear-gradient(135deg, #06b6d4, #0891b2); }

.currency-info h3 {
  color: #1f2937;
  font-weight: 600;
  margin: 0;
}

.currency-info p {
  color: #6b7280;
  font-size: 0.9rem;
  margin: 0;
}

.currency-trend {
  margin-left: auto;
  display: flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.8rem;
  font-weight: 600;
}

.currency-trend.positive {
  color: #10b981;
}

.currency-trend.negative {
  color: #ef4444;
}

.currency-metrics {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  margin-bottom: 1rem;
}

.metric-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.metric-label {
  color: #6b7280;
  font-size: 0.8rem;
}

.metric-value {
  color: #1f2937;
  font-weight: 500;
  font-size: 0.9rem;
}

.metric-value.amount {
  color: #3b82f6;
  font-weight: 600;
}

.metric-value.converted {
  color: #6b7280;
  font-style: italic;
}

.currency-actions {
  display: flex;
  gap: 0.5rem;
}

/* Exchange Rates */
.exchange-rates-section {
  margin-bottom: 2rem;
}

.rates-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1rem;
}

.rate-card {
  background: white;
  border-radius: 12px;
  padding: 1rem;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.rate-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.rate-pair {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: 600;
  color: #1f2937;
}

.rate-pair i {
  color: #6b7280;
  font-size: 0.8rem;
}

.rate-status {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.7rem;
  padding: 0.25rem 0.5rem;
  border-radius: 9999px;
  font-weight: 500;
}

.rate-status.fresh {
  background: #d1fae5;
  color: #065f46;
}

.rate-status.recent {
  background: #fef3c7;
  color: #92400e;
}

.rate-status.stale {
  background: #fee2e2;
  color: #991b1b;
}

.rate-value {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.current-rate {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1f2937;
}

.rate-change {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.8rem;
  font-weight: 600;
}

.rate-change.positive {
  color: #10b981;
}

.rate-change.negative {
  color: #ef4444;
}

.rate-meta {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  margin-bottom: 1rem;
}

.rate-info {
  display: flex;
  justify-content: space-between;
  font-size: 0.8rem;
}

.rate-info .label {
  color: #6b7280;
}

.rate-info .value {
  color: #1f2937;
}

.rate-actions {
  display: flex;
  gap: 0.5rem;
}

/* Summary Section */
.summary-section {
  margin-bottom: 2rem;
}

.summary-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1rem;
}

.summary-card {
  background: white;
  border-radius: 16px;
  padding: 1.5rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}

.summary-icon {
  width: 60px;
  height: 60px;
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  color: white;
}

.summary-card.total .summary-icon {
  background: linear-gradient(135deg, #6366f1, #4f46e5);
}

.summary-card.currencies .summary-icon {
  background: linear-gradient(135deg, #f59e0b, #d97706);
}

.summary-card.exposure .summary-icon {
  background: linear-gradient(135deg, #ef4444, #dc2626);
}

.summary-card.avg-rate .summary-icon {
  background: linear-gradient(135deg, #10b981, #059669);
}

.summary-content h3 {
  font-size: 1.5rem;
  font-weight: 700;
  margin-bottom: 0.25rem;
  color: #1f2937;
}

.summary-content p {
  color: #6b7280;
  font-size: 0.9rem;
  margin-bottom: 0.25rem;
}

.summary-content small {
  color: #9ca3af;
  font-size: 0.8rem;
}

/* Activity Section */
.activity-section {
  margin-bottom: 2rem;
}

.activity-list {
  background: white;
  border-radius: 16px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
  overflow: hidden;
}

.activity-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem 1.5rem;
  border-bottom: 1px solid #e5e7eb;
  transition: background 0.3s ease;
}

.activity-item:last-child {
  border-bottom: none;
}

.activity-item:hover {
  background: #f8fafc;
}

.activity-icon {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 0.9rem;
}

.activity-icon.payment {
  background: #10b981;
}

.activity-icon.payable {
  background: #3b82f6;
}

.activity-icon.rate_update {
  background: #f59e0b;
}

.activity-icon.conversion {
  background: #8b5cf6;
}

.activity-content {
  flex: 1;
}

.activity-title {
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 0.25rem;
}

.activity-description {
  color: #6b7280;
  font-size: 0.9rem;
  margin-bottom: 0.25rem;
}

.activity-meta {
  display: flex;
  gap: 1rem;
  font-size: 0.8rem;
}

.activity-time {
  color: #9ca3af;
}

.activity-amount {
  color: #3b82f6;
  font-weight: 600;
}

.activity-actions {
  display: flex;
  gap: 0.5rem;
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
  border-radius: 16px;
  max-width: 600px;
  width: 90%;
  max-height: 80vh;
  overflow-y: auto;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-bottom: 1px solid #e5e7eb;
}

.modal-header h3 {
  margin: 0;
  color: #1f2937;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.modal-body {
  padding: 1.5rem;
}

.converter-form {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-label {
  font-weight: 500;
  color: #374151;
  font-size: 0.9rem;
}

.form-select,
.form-input {
  padding: 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 0.9rem;
}

.conversion-info {
  background: #f8fafc;
  padding: 1rem;
  border-radius: 8px;
  border-left: 3px solid #6366f1;
}

.conversion-info p {
  margin: 0.25rem 0;
  color: #6b7280;
  font-size: 0.9rem;
}

/* Button Styles */
.btn {
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 500;
  text-decoration: none;
  cursor: pointer;
  border: none;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.3s ease;
}

.btn-primary {
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  color: white;
}

.btn-outline {
  background: white;
  color: #6366f1;
  border: 1px solid #6366f1;
}

.btn-ghost {
  background: transparent;
  color: #6b7280;
  border: 1px solid #e5e7eb;
}

.btn-sm {
  padding: 0.5rem 1rem;
  font-size: 0.875rem;
}

.btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  transform: none;
}

/* Responsive */
@media (max-width: 1024px) {
  .currency-cards {
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  }
  
  .rates-grid {
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  }
}

@media (max-width: 768px) {
  .currency-dashboard {
    padding: 1rem;
  }
  
  .header-content {
    flex-direction: column;
    gap: 1rem;
    align-items: flex-start;
  }
  
  .controls-card {
    grid-template-columns: 1fr;
  }
  
  .currency-cards {
    grid-template-columns: 1fr;
  }
  
  .summary-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .rates-grid {
    grid-template-columns: 1fr;
  }
  
  .form-row {
    grid-template-columns: 1fr;
  }
  
  .activity-item {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.5rem;
  }
  
  .activity-meta {
    flex-direction: column;
    gap: 0.25rem;
  }
}
</style>