<template>
  <div class="currency-summary">
    <!-- Header Section -->
    <div class="page-header">
      <div class="header-content">
        <div class="header-left">
          <h1 class="page-title">
            <i class="fas fa-coins"></i>
            Currency Summary Report
          </h1>
          <p class="page-subtitle">Overview of receivables by currency with conversion options</p>
        </div>
        <div class="header-actions">
          <router-link to="/accounting/receivables" class="btn btn-ghost">
            <i class="fas fa-arrow-left"></i>
            Back to Receivables
          </router-link>
          <button @click="exportSummary" class="btn btn-outline">
            <i class="fas fa-download"></i>
            Export
          </button>
        </div>
      </div>
    </div>

    <!-- Controls Section -->
    <div class="controls-section">
      <div class="controls-grid">
        <div class="control-group">
          <label>Customer Filter</label>
          <select v-model="filters.customer_id" @change="loadCurrencySummary">
            <option value="">All Customers</option>
            <option v-for="customer in customers" :key="customer.customer_id" :value="customer.customer_id">
              {{ customer.name }}
            </option>
          </select>
        </div>

        <div class="control-group">
          <label>Display Currency</label>
          <select v-model="filters.display_currency" @change="loadCurrencySummary">
            <option value="">No Conversion</option>
            <option v-for="currency in availableCurrencies" :key="currency" :value="currency">
              Convert to {{ currency }}
            </option>
          </select>
        </div>

        <div class="control-group">
          <label>Report Date</label>
          <input type="date" v-model="reportDate" @change="loadCurrencySummary">
        </div>

        <div class="control-actions">
          <button @click="clearFilters" class="btn btn-ghost">
            <i class="fas fa-times"></i>
            Clear Filters
          </button>
          <button @click="refreshData" class="btn btn-outline">
            <i class="fas fa-sync-alt"></i>
            Refresh
          </button>
        </div>
      </div>
    </div>

    <!-- Customer Information -->
    <div v-if="selectedCustomer" class="customer-info-card">
      <div class="customer-header">
        <div class="customer-details">
          <div class="customer-avatar">
            <i class="fas fa-user-circle"></i>
          </div>
          <div class="customer-text">
            <h3>{{ selectedCustomer.name }}</h3>
            <p class="customer-code">{{ selectedCustomer.customer_code }}</p>
            <p v-if="selectedCustomer.email" class="customer-contact">
              <i class="fas fa-envelope"></i>
              {{ selectedCustomer.email }}
            </p>
          </div>
        </div>
        <div class="customer-actions">
          <router-link :to="`/customers/${selectedCustomer.customer_id}`" class="btn btn-outline">
            <i class="fas fa-external-link-alt"></i>
            View Customer
          </router-link>
        </div>
      </div>
    </div>

    <!-- Currency Conversion Notice -->
    <div v-if="filters.display_currency" class="conversion-notice">
      <div class="notice-content">
        <i class="fas fa-exchange-alt"></i>
        <div>
          <h4>Currency Conversion Active</h4>
          <p>All amounts are being converted to {{ filters.display_currency }} for comparison</p>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="loading-state">
      <div class="loading-spinner"></div>
      <p>Loading currency summary...</p>
    </div>

    <!-- Summary Content -->
    <div v-else-if="currencySummary.length > 0" class="summary-content">
      <!-- Overall Statistics -->
      <div class="stats-cards">
        <div class="stat-card total">
          <div class="stat-icon">
            <i class="fas fa-globe"></i>
          </div>
          <div class="stat-content">
            <h3>{{ totalCurrencies }}</h3>
            <p>Total Currencies</p>
            <span class="stat-detail">Active in receivables</span>
          </div>
        </div>

        <div class="stat-card receivables">
          <div class="stat-icon">
            <i class="fas fa-file-invoice"></i>
          </div>
          <div class="stat-content">
            <h3>{{ totalReceivables }}</h3>
            <p>Total Receivables</p>
            <span class="stat-detail">Across all currencies</span>
          </div>
        </div>

        <div class="stat-card amount">
          <div class="stat-icon">
            <i class="fas fa-money-bill-wave"></i>
          </div>
          <div class="stat-content">
            <h3>{{ formatDisplayAmount(totalAmount) }}</h3>
            <p>Total Amount</p>
            <span class="stat-detail">{{ filters.display_currency ? `In ${filters.display_currency}` : 'Original currencies' }}</span>
          </div>
        </div>

        <div class="stat-card balance">
          <div class="stat-icon">
            <i class="fas fa-balance-scale"></i>
          </div>
          <div class="stat-content">
            <h3>{{ formatDisplayAmount(totalBalance) }}</h3>
            <p>Outstanding Balance</p>
            <span class="stat-detail">{{ filters.display_currency ? `In ${filters.display_currency}` : 'Original currencies' }}</span>
          </div>
        </div>
      </div>

      <!-- Currency Breakdown -->
      <div class="currency-breakdown">
        <div class="breakdown-header">
          <h3>
            <i class="fas fa-chart-pie"></i>
            Currency Breakdown
          </h3>
          <div class="view-toggles">
            <button 
              @click="viewMode = 'cards'"
              class="toggle-btn"
              :class="{ active: viewMode === 'cards' }"
            >
              <i class="fas fa-th-large"></i>
              Cards
            </button>
            <button 
              @click="viewMode = 'table'"
              class="toggle-btn"
              :class="{ active: viewMode === 'table' }"
            >
              <i class="fas fa-table"></i>
              Table
            </button>
          </div>
        </div>

        <!-- Cards View -->
        <div v-if="viewMode === 'cards'" class="currency-cards">
          <div 
            v-for="currency in currencySummary" 
            :key="currency.currency_code"
            class="currency-card"
            :class="getCurrencyCardClass(currency)"
          >
            <div class="currency-header">
              <div class="currency-info">
                <div class="currency-flag">
                  {{ getCurrencyFlag(currency.currency_code) }}
                </div>
                <div class="currency-details">
                  <h4>{{ currency.currency_code }}</h4>
                  <p>{{ getCurrencyName(currency.currency_code) }}</p>
                </div>
              </div>
              <div class="currency-count">
                <span class="count-badge">{{ currency.count }}</span>
                <span class="count-label">Receivables</span>
              </div>
            </div>

            <div class="currency-amounts">
              <div class="amount-section original">
                <h5>Original Currency</h5>
                <div class="amount-grid">
                  <div class="amount-item">
                    <span class="label">Total Amount</span>
                    <span class="value">{{ formatCurrency(currency.total_amount, currency.currency_code) }}</span>
                  </div>
                  <div class="amount-item">
                    <span class="label">Outstanding</span>
                    <span class="value outstanding">{{ formatCurrency(currency.total_balance, currency.currency_code) }}</span>
                  </div>
                </div>
              </div>

              <div v-if="filters.display_currency && currency.display_currency" class="amount-section converted">
                <h5>Converted to {{ filters.display_currency }}</h5>
                <div class="amount-grid">
                  <div class="amount-item">
                    <span class="label">Total Amount</span>
                    <span class="value">{{ formatCurrency(currency.display_total_amount, currency.display_currency) }}</span>
                  </div>
                  <div class="amount-item">
                    <span class="label">Outstanding</span>
                    <span class="value outstanding">{{ formatCurrency(currency.display_total_balance, currency.display_currency) }}</span>
                  </div>
                </div>
              </div>
            </div>

            <div class="currency-actions">
              <button 
                @click="viewCurrencyReceivables(currency.currency_code)"
                class="action-btn"
              >
                <i class="fas fa-list"></i>
                View Receivables
              </button>
              <button 
                @click="viewCurrencyTransactions(currency.currency_code)"
                class="action-btn"
              >
                <i class="fas fa-exchange-alt"></i>
                View Transactions
              </button>
            </div>
          </div>
        </div>

        <!-- Table View -->
        <div v-else class="currency-table-container">
          <div class="table-wrapper">
            <table class="currency-table">
              <thead>
                <tr>
                  <th class="currency-col">Currency</th>
                  <th class="count-col">Count</th>
                  <th class="amount-col">Total Amount</th>
                  <th class="amount-col">Outstanding Balance</th>
                  <th v-if="filters.display_currency" class="amount-col">Converted Amount</th>
                  <th v-if="filters.display_currency" class="amount-col">Converted Balance</th>
                  <th class="percentage-col">% of Total</th>
                  <th class="action-col">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr 
                  v-for="currency in currencySummary" 
                  :key="currency.currency_code"
                  class="currency-row"
                >
                  <td class="currency-cell">
                    <div class="currency-display">
                      <span class="currency-flag">{{ getCurrencyFlag(currency.currency_code) }}</span>
                      <div class="currency-text">
                        <strong>{{ currency.currency_code }}</strong>
                        <small>{{ getCurrencyName(currency.currency_code) }}</small>
                      </div>
                    </div>
                  </td>
                  <td class="count-cell">
                    <span class="count-badge">{{ currency.count }}</span>
                  </td>
                  <td class="amount-cell">
                    {{ formatCurrency(currency.total_amount, currency.currency_code) }}
                  </td>
                  <td class="amount-cell outstanding">
                    {{ formatCurrency(currency.total_balance, currency.currency_code) }}
                  </td>
                  <td v-if="filters.display_currency" class="amount-cell">
                    {{ currency.display_total_amount ? formatCurrency(currency.display_total_amount, currency.display_currency) : '-' }}
                  </td>
                  <td v-if="filters.display_currency" class="amount-cell outstanding">
                    {{ currency.display_total_balance ? formatCurrency(currency.display_total_balance, currency.display_currency) : '-' }}
                  </td>
                  <td class="percentage-cell">
                    <div class="percentage-bar">
                      <div 
                        class="percentage-fill"
                        :style="{ width: getPercentage(currency) + '%' }"
                      ></div>
                      <span class="percentage-text">{{ getPercentage(currency).toFixed(1) }}%</span>
                    </div>
                  </td>
                  <td class="action-cell">
                    <div class="action-buttons">
                      <button 
                        @click="viewCurrencyReceivables(currency.currency_code)"
                        class="action-btn small"
                        title="View Receivables"
                      >
                        <i class="fas fa-list"></i>
                      </button>
                      <button 
                        @click="viewCurrencyTransactions(currency.currency_code)"
                        class="action-btn small"
                        title="View Transactions"
                      >
                        <i class="fas fa-exchange-alt"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
              <tfoot>
                <tr class="totals-row">
                  <td class="currency-cell"><strong>TOTALS</strong></td>
                  <td class="count-cell"><strong>{{ totalReceivables }}</strong></td>
                  <td class="amount-cell"><strong>-</strong></td>
                  <td class="amount-cell"><strong>-</strong></td>
                  <td v-if="filters.display_currency" class="amount-cell">
                    <strong>{{ formatDisplayAmount(totalAmount) }}</strong>
                  </td>
                  <td v-if="filters.display_currency" class="amount-cell">
                    <strong>{{ formatDisplayAmount(totalBalance) }}</strong>
                  </td>
                  <td class="percentage-cell"><strong>100.0%</strong></td>
                  <td class="action-cell"></td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="empty-state">
      <i class="fas fa-coins"></i>
      <h3>No Currency Data Available</h3>
      <p>No receivables found for the selected criteria</p>
      <router-link to="/accounting/receivables" class="btn btn-primary">
        View All Receivables
      </router-link>
    </div>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'CurrencySummary',
  data() {
    return {
      currencySummary: [],
      customers: [],
      selectedCustomer: null,
      loading: false,
      viewMode: 'cards', // 'cards' or 'table'
      reportDate: new Date().toISOString().split('T')[0],
      availableCurrencies: ['USD', 'EUR', 'GBP', 'JPY', 'CAD', 'AUD', 'CHF', 'CNY'],
      filters: {
        customer_id: '',
        display_currency: ''
      },
      currencyNames: {
        'USD': 'US Dollar',
        'EUR': 'Euro',
        'GBP': 'British Pound',
        'JPY': 'Japanese Yen',
        'CAD': 'Canadian Dollar',
        'AUD': 'Australian Dollar',
        'CHF': 'Swiss Franc',
        'CNY': 'Chinese Yuan'
      },
      currencyFlags: {
        'USD': '🇺🇸',
        'EUR': '🇪🇺',
        'GBP': '🇬🇧',
        'JPY': '🇯🇵',
        'CAD': '🇨🇦',
        'AUD': '🇦🇺',
        'CHF': '🇨🇭',
        'CNY': '🇨🇳'
      }
    }
  },
  
  computed: {
    totalCurrencies() {
      return this.currencySummary.length
    },
    
    totalReceivables() {
      return this.currencySummary.reduce((sum, currency) => sum + currency.count, 0)
    },
    
    totalAmount() {
      if (this.filters.display_currency) {
        return this.currencySummary.reduce((sum, currency) => 
          sum + (currency.display_total_amount || 0), 0)
      }
      return 0
    },
    
    totalBalance() {
      if (this.filters.display_currency) {
        return this.currencySummary.reduce((sum, currency) => 
          sum + (currency.display_total_balance || 0), 0)
      }
      return 0
    }
  },
  
  async mounted() {
    await this.loadCustomers()
    await this.loadCurrencySummary()
  },
  
  methods: {
    async loadCustomers() {
      try {
        const response = await axios.get('/customers')
        this.customers = response.data.data || response.data
      } catch (error) {
        console.error('Error loading customers:', error)
      }
    },
    
    async loadCurrencySummary() {
      this.loading = true
      try {
        const params = { ...this.filters }
        
        // Remove empty filters
        Object.keys(params).forEach(key => {
          if (params[key] === '' || params[key] === null) {
            delete params[key]
          }
        })
        
        const response = await axios.get('/accounting/receivables/currency-summary', { params })
        this.currencySummary = response.data.data || []
        
        // Load selected customer details
        if (this.filters.customer_id) {
          this.selectedCustomer = this.customers.find(c => c.customer_id == this.filters.customer_id)
        } else {
          this.selectedCustomer = null
        }
        
      } catch (error) {
        console.error('Error loading currency summary:', error)
        this.$toast?.error('Failed to load currency summary')
      } finally {
        this.loading = false
      }
    },
    
    clearFilters() {
      this.filters = {
        customer_id: '',
        display_currency: ''
      }
      this.selectedCustomer = null
      this.loadCurrencySummary()
    },
    
    refreshData() {
      this.loadCurrencySummary()
    },
    
    getCurrencyCardClass(currency) {
      // Add special styling based on currency importance or balance
      if (currency.total_balance > 10000) return 'high-value'
      if (currency.total_balance > 1000) return 'medium-value'
      return 'low-value'
    },
    
    getCurrencyName(currencyCode) {
      return this.currencyNames[currencyCode] || currencyCode
    },
    
    getCurrencyFlag(currencyCode) {
      return this.currencyFlags[currencyCode] || '🏳️'
    },
    
    getPercentage(currency) {
      if (!this.filters.display_currency) return 0
      const total = this.totalBalance
      return total > 0 ? (currency.display_total_balance / total) * 100 : 0
    },
    
    viewCurrencyReceivables(currencyCode) {
      const query = { currency_code: currencyCode }
      if (this.filters.customer_id) {
        query.customer_id = this.filters.customer_id
      }
      if (this.filters.display_currency) {
        query.display_currency = this.filters.display_currency
      }
      
      this.$router.push({
        path: '/accounting/receivables',
        query
      })
    },
    
    viewCurrencyTransactions(currencyCode) {
      const query = { currency_code: currencyCode }
      if (this.filters.customer_id) {
        query.customer_id = this.filters.customer_id
      }
      if (this.filters.display_currency) {
        query.display_currency = this.filters.display_currency
      }
      
      this.$router.push({
        path: '/accounting/customer-transactions',
        query
      })
    },
    
    exportSummary() {
      const csvContent = this.generateCSV()
      this.downloadCSV(csvContent, `currency-summary-${this.reportDate}.csv`)
    },
    
    generateCSV() {
      const headers = [
        'Currency Code',
        'Currency Name',
        'Count',
        'Total Amount',
        'Outstanding Balance'
      ]
      
      if (this.filters.display_currency) {
        headers.push(`Converted Amount (${this.filters.display_currency})`)
        headers.push(`Converted Balance (${this.filters.display_currency})`)
        headers.push('Percentage of Total')
      }
      
      const rows = this.currencySummary.map(currency => {
        const row = [
          currency.currency_code,
          this.getCurrencyName(currency.currency_code),
          currency.count,
          currency.total_amount,
          currency.total_balance
        ]
        
        if (this.filters.display_currency) {
          row.push(currency.display_total_amount || 0)
          row.push(currency.display_total_balance || 0)
          row.push(this.getPercentage(currency).toFixed(2) + '%')
        }
        
        return row
      })
      
      return [headers, ...rows].map(row => row.join(',')).join('\n')
    },
    
    downloadCSV(content, filename) {
      const blob = new Blob([content], { type: 'text/csv' })
      const url = window.URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = url
      link.download = filename
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
      window.URL.revokeObjectURL(url)
    },
    
    formatCurrency(amount, currencyCode) {
      return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: currencyCode || 'USD'
      }).format(amount || 0)
    },
    
    formatDisplayAmount(amount) {
      const currency = this.filters.display_currency || 'USD'
      return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: currency
      }).format(amount || 0)
    }
  }
}
</script>

<style scoped>
.currency-summary {
  max-width: 1600px;
  margin: 0 auto;
  padding: 2rem;
  background: var(--gray-50);
  min-height: 100vh;
}

/* Header Section */
.page-header {
  background: white;
  border-radius: 12px;
  padding: 2rem;
  margin-bottom: 2rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.page-title {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  color: var(--gray-800);
  font-size: 1.875rem;
  font-weight: 700;
  margin-bottom: 0.5rem;
}

.page-subtitle {
  color: var(--gray-600);
  font-size: 1rem;
}

.header-actions {
  display: flex;
  gap: 1rem;
}

/* Controls Section */
.controls-section {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  margin-bottom: 2rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.controls-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  align-items: end;
}

.control-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.control-group label {
  font-weight: 500;
  color: var(--gray-700);
  font-size: 0.875rem;
}

.control-group select,
.control-group input {
  padding: 0.5rem;
  border: 1px solid var(--gray-300);
  border-radius: 6px;
  font-size: 0.875rem;
}

.control-actions {
  display: flex;
  gap: 0.5rem;
}

/* Customer Info Card */
.customer-info-card {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  margin-bottom: 2rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.customer-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.customer-details {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.customer-avatar {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  background: var(--primary-color);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2rem;
}

.customer-text h3 {
  margin-bottom: 0.25rem;
  color: var(--gray-800);
}

.customer-code {
  color: var(--gray-600);
  font-size: 0.875rem;
  margin-bottom: 0.25rem;
}

.customer-contact {
  color: var(--gray-600);
  font-size: 0.875rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

/* Conversion Notice */
.conversion-notice {
  background: #eff6ff;
  border: 1px solid #3b82f6;
  color: #1e40af;
  border-radius: 12px;
  padding: 1rem;
  margin-bottom: 2rem;
}

.notice-content {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.notice-content i {
  font-size: 1.25rem;
}

.notice-content h4 {
  margin-bottom: 0.25rem;
}

/* Loading State */
.loading-state {
  text-align: center;
  padding: 4rem;
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.loading-spinner {
  width: 40px;
  height: 40px;
  border: 3px solid var(--gray-200);
  border-top-color: var(--primary-color);
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 1rem;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Stats Cards */
.stats-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.stat-card {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  display: flex;
  align-items: center;
  gap: 1rem;
  transition: transform 0.2s;
}

.stat-card:hover {
  transform: translateY(-2px);
}

.stat-card.total .stat-icon { background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); }
.stat-card.receivables .stat-icon { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
.stat-card.amount .stat-icon { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
.stat-card.balance .stat-icon { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); }

.stat-icon {
  width: 60px;
  height: 60px;
  border-radius: 12px;
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
}

.stat-content h3 {
  font-size: 1.5rem;
  font-weight: 700;
  margin-bottom: 0.25rem;
  color: var(--gray-800);
}

.stat-content p {
  color: var(--gray-600);
  font-size: 0.875rem;
  margin-bottom: 0.25rem;
}

.stat-detail {
  font-weight: 500;
  color: var(--gray-500);
  font-size: 0.75rem;
}

/* Currency Breakdown */
.currency-breakdown {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.breakdown-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid var(--gray-200);
}

.breakdown-header h3 {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-size: 1.125rem;
  font-weight: 600;
  color: var(--gray-800);
}

.view-toggles {
  display: flex;
  gap: 0.5rem;
}

.toggle-btn {
  padding: 0.5rem 1rem;
  border: 1px solid var(--gray-300);
  background: white;
  color: var(--gray-600);
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
}

.toggle-btn:hover,
.toggle-btn.active {
  background: var(--primary-color);
  color: white;
  border-color: var(--primary-color);
}

/* Currency Cards */
.currency-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
  gap: 1.5rem;
}

.currency-card {
  border: 1px solid var(--gray-200);
  border-radius: 12px;
  padding: 1.5rem;
  transition: all 0.2s;
}

.currency-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
}

.currency-card.high-value {
  border-left: 4px solid #10b981;
  background: linear-gradient(135deg, #ffffff 0%, #f0fdf4 100%);
}

.currency-card.medium-value {
  border-left: 4px solid #f59e0b;
  background: linear-gradient(135deg, #ffffff 0%, #fffbeb 100%);
}

.currency-card.low-value {
  border-left: 4px solid var(--gray-400);
}

.currency-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.currency-info {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.currency-flag {
  font-size: 2rem;
}

.currency-details h4 {
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--gray-800);
  margin-bottom: 0.25rem;
}

.currency-details p {
  color: var(--gray-600);
  font-size: 0.875rem;
}

.currency-count {
  text-align: center;
}

.count-badge {
  display: block;
  background: var(--primary-color);
  color: white;
  border-radius: 20px;
  padding: 0.25rem 0.75rem;
  font-weight: 600;
  font-size: 0.875rem;
  margin-bottom: 0.25rem;
}

.count-label {
  font-size: 0.75rem;
  color: var(--gray-600);
}

.currency-amounts {
  margin-bottom: 1.5rem;
}

.amount-section {
  margin-bottom: 1rem;
}

.amount-section h5 {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--gray-700);
  margin-bottom: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.amount-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.amount-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.amount-item .label {
  font-size: 0.75rem;
  color: var(--gray-600);
  font-weight: 500;
}

.amount-item .value {
  font-weight: 600;
  color: var(--gray-800);
}

.amount-item .value.outstanding {
  color: var(--warning-color);
}

.converted {
  padding: 1rem;
  background: var(--gray-50);
  border-radius: 8px;
  border: 1px solid var(--gray-200);
}

.currency-actions {
  display: flex;
  gap: 0.5rem;
}

.action-btn {
  flex: 1;
  padding: 0.5rem 1rem;
  border: 1px solid var(--gray-300);
  background: white;
  color: var(--gray-600);
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  font-size: 0.875rem;
}

.action-btn:hover {
  background: var(--primary-color);
  color: white;
  border-color: var(--primary-color);
}

.action-btn.small {
  width: 32px;
  height: 32px;
  padding: 0;
  flex: none;
}

/* Currency Table */
.currency-table-container {
  overflow-x: auto;
}

.currency-table {
  width: 100%;
  border-collapse: collapse;
}

.currency-table th,
.currency-table td {
  padding: 1rem;
  text-align: left;
  border-bottom: 1px solid var(--gray-200);
}

.currency-table th {
  background: var(--gray-50);
  font-weight: 600;
  color: var(--gray-700);
  font-size: 0.875rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.amount-col,
.amount-cell {
  text-align: right;
}

.currency-display {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.currency-text {
  display: flex;
  flex-direction: column;
}

.currency-text small {
  color: var(--gray-600);
  font-size: 0.75rem;
}

.count-cell .count-badge {
  background: var(--primary-color);
  color: white;
  border-radius: 12px;
  padding: 0.25rem 0.5rem;
  font-weight: 500;
  font-size: 0.75rem;
}

.amount-cell.outstanding {
  color: var(--warning-color);
  font-weight: 600;
}

.percentage-bar {
  position: relative;
  height: 20px;
  background: var(--gray-200);
  border-radius: 10px;
  overflow: hidden;
}

.percentage-fill {
  height: 100%;
  background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
  transition: width 0.3s ease;
}

.percentage-text {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--gray-700);
}

.action-buttons {
  display: flex;
  gap: 0.25rem;
}

.totals-row {
  background: var(--gray-100);
  font-weight: 600;
}

.totals-row td {
  border-top: 2px solid var(--gray-300);
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 4rem;
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.empty-state i {
  font-size: 3rem;
  color: var(--gray-400);
  margin-bottom: 1rem;
}

.empty-state h3 {
  margin-bottom: 0.5rem;
  color: var(--gray-700);
}

.empty-state p {
  color: var(--gray-600);
  margin-bottom: 2rem;
}

/* Button Styles */
.btn {
  padding: 0.5rem 1rem;
  border-radius: 6px;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  border: 1px solid transparent;
}

.btn-primary {
  background: var(--primary-color);
  color: white;
}

.btn-primary:hover {
  background: var(--primary-dark);
}

.btn-outline {
  border-color: var(--gray-300);
  color: var(--gray-700);
  background: white;
}

.btn-outline:hover {
  background: var(--gray-100);
}

.btn-ghost {
  color: var(--gray-600);
  background: transparent;
}

.btn-ghost:hover {
  background: var(--gray-100);
}

/* Responsive Design */
@media (max-width: 1024px) {
  .currency-summary {
    padding: 1rem;
  }
  
  .controls-grid {
    grid-template-columns: 1fr;
  }
  
  .currency-cards {
    grid-template-columns: 1fr;
  }
  
  .stats-cards {
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  }
}

@media (max-width: 768px) {
  .header-content {
    flex-direction: column;
    gap: 1rem;
    align-items: stretch;
  }
  
  .customer-header {
    flex-direction: column;
    gap: 1rem;
    align-items: stretch;
  }
  
  .breakdown-header {
    flex-direction: column;
    gap: 1rem;
    align-items: stretch;
  }
  
  .currency-table th,
  .currency-table td {
    padding: 0.5rem;
    font-size: 0.75rem;
  }
}
</style>