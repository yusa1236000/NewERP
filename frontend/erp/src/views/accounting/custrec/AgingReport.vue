<template>
  <div class="aging-report">
    <!-- Header Section -->
    <div class="page-header">
      <div class="header-content">
        <div class="header-left">
          <h1 class="page-title">
            <i class="fas fa-chart-bar"></i>
            Accounts Receivable Aging Report
          </h1>
          <p class="page-subtitle">Analyze customer payment patterns and overdue accounts</p>
        </div>
        <div class="header-actions">
          <router-link to="/accounting/receivables" class="btn btn-ghost">
            <i class="fas fa-arrow-left"></i>
            Back to Receivables
          </router-link>
          <button @click="printReport" class="btn btn-outline">
            <i class="fas fa-print"></i>
            Print
          </button>
          <button @click="exportReport" class="btn btn-outline">
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
          <label>Display Currency</label>
          <select v-model="displayCurrency" @change="loadAgingData">
            <option value="">Base Currency ({{ baseCurrency }})</option>
            <option v-for="currency in availableCurrencies" :key="currency" :value="currency">
              {{ currency }}
            </option>
          </select>
        </div>

        <div class="control-group">
          <label>Base Currency Only</label>
          <label class="checkbox-label">
            <input type="checkbox" v-model="baseCurrencyOnly" @change="loadAgingData">
            <span class="checkmark"></span>
            Use optimized base currency calculation
          </label>
        </div>

        <div class="control-group">
          <label>Report Date</label>
          <input type="date" v-model="reportDate" @change="loadAgingData">
        </div>

        <div class="control-group">
          <label>Search Customer</label>
          <input 
            type="text" 
            v-model="searchTerm" 
            placeholder="Search customers..."
            @input="filterAgingData"
          >
        </div>
      </div>
    </div>

    <!-- Currency Notice -->
    <div v-if="displayCurrency && !baseCurrencyOnly" class="currency-notice">
      <div class="notice-content">
        <i class="fas fa-exchange-alt"></i>
        <div>
          <h4>Currency Conversion Applied</h4>
          <p>All amounts are converted to {{ displayCurrency }} using current exchange rates</p>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="loading-state">
      <div class="loading-spinner"></div>
      <p>Generating aging report...</p>
    </div>

    <!-- Report Content -->
    <div v-else-if="agingData.length > 0" class="report-content">
      <!-- Summary Cards -->
      <div class="summary-cards">
        <div class="summary-card total">
          <div class="card-icon">
            <i class="fas fa-chart-pie"></i>
          </div>
          <div class="card-content">
            <h3>{{ formatCurrency(summary.total_balance) }}</h3>
            <p>Total Outstanding</p>
            <span class="customers-count">{{ agingData.length }} customers</span>
          </div>
        </div>

        <div class="summary-card current">
          <div class="card-icon">
            <i class="fas fa-check-circle"></i>
          </div>
          <div class="card-content">
            <h3>{{ formatCurrency(summary.current_amount) }}</h3>
            <p>Current (Not Due)</p>
            <span class="percentage">{{ getPercentage(summary.current_amount) }}%</span>
          </div>
        </div>

        <div class="summary-card days-30">
          <div class="card-icon">
            <i class="fas fa-clock"></i>
          </div>
          <div class="card-content">
            <h3>{{ formatCurrency(summary.days_1_30) }}</h3>
            <p>1-30 Days</p>
            <span class="percentage">{{ getPercentage(summary.days_1_30) }}%</span>
          </div>
        </div>

        <div class="summary-card days-60">
          <div class="card-icon">
            <i class="fas fa-exclamation-triangle"></i>
          </div>
          <div class="card-content">
            <h3>{{ formatCurrency(summary.days_31_60) }}</h3>
            <p>31-60 Days</p>
            <span class="percentage">{{ getPercentage(summary.days_31_60) }}%</span>
          </div>
        </div>

        <div class="summary-card days-90">
          <div class="card-icon">
            <i class="fas fa-exclamation-circle"></i>
          </div>
          <div class="card-content">
            <h3>{{ formatCurrency(summary.days_61_90) }}</h3>
            <p>61-90 Days</p>
            <span class="percentage">{{ getPercentage(summary.days_61_90) }}%</span>
          </div>
        </div>

        <div class="summary-card over-90">
          <div class="card-icon">
            <i class="fas fa-ban"></i>
          </div>
          <div class="card-content">
            <h3>{{ formatCurrency(summary.days_over_90) }}</h3>
            <p>Over 90 Days</p>
            <span class="percentage">{{ getPercentage(summary.days_over_90) }}%</span>
          </div>
        </div>
      </div>

      <!-- Aging Table -->
      <div class="aging-table-container">
        <div class="table-header">
          <h3>
            <i class="fas fa-table"></i>
            Customer Aging Detail
          </h3>
          <div class="table-controls">
            <select v-model="sortField" @change="sortAgingData" class="sort-select">
              <option value="customer_name">Sort by Customer</option>
              <option value="total_balance">Sort by Total Balance</option>
              <option value="days_over_90">Sort by Over 90 Days</option>
              <option value="current_amount">Sort by Current</option>
            </select>
            <button @click="toggleSortOrder" class="btn btn-sm btn-outline">
              <i :class="sortOrder === 'asc' ? 'fas fa-sort-amount-up' : 'fas fa-sort-amount-down'"></i>
              {{ sortOrder === 'asc' ? 'Ascending' : 'Descending' }}
            </button>
          </div>
        </div>

        <div class="table-wrapper">
          <table class="aging-table">
            <thead>
              <tr>
                <th class="customer-col">Customer</th>
                <th class="amount-col">Current</th>
                <th class="amount-col">1-30 Days</th>
                <th class="amount-col">31-60 Days</th>
                <th class="amount-col">61-90 Days</th>
                <th class="amount-col">Over 90 Days</th>
                <th class="amount-col total-col">Total Balance</th>
                <th class="action-col">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr 
                v-for="customer in filteredAgingData" 
                :key="customer.customer_id"
                class="aging-row"
                :class="getRiskClass(customer)"
              >
                <td class="customer-cell">
                  <div class="customer-info">
                    <div class="customer-name">{{ customer.customer_name }}</div>
                    <div class="customer-id">#{{ customer.customer_id }}</div>
                  </div>
                </td>
                <td class="amount-cell current">
                  {{ formatCurrency(customer.current_amount) }}
                </td>
                <td class="amount-cell days-30">
                  {{ formatCurrency(customer.days_1_30) }}
                </td>
                <td class="amount-cell days-60" :class="{ 'highlight-warning': customer.days_31_60 > 0 }">
                  {{ formatCurrency(customer.days_31_60) }}
                </td>
                <td class="amount-cell days-90" :class="{ 'highlight-danger': customer.days_61_90 > 0 }">
                  {{ formatCurrency(customer.days_61_90) }}
                </td>
                <td class="amount-cell over-90" :class="{ 'highlight-critical': customer.days_over_90 > 0 }">
                  {{ formatCurrency(customer.days_over_90) }}
                </td>
                <td class="amount-cell total">
                  <strong>{{ formatCurrency(customer.total_balance) }}</strong>
                </td>
                <td class="action-cell">
                  <div class="action-buttons">
                    <button 
                      @click="viewCustomerReceivables(customer.customer_id)"
                      class="action-btn view"
                      title="View Receivables"
                    >
                      <i class="fas fa-eye"></i>
                    </button>
                    <button 
                      @click="generateStatement(customer.customer_id)"
                      class="action-btn statement"
                      title="Generate Statement"
                    >
                      <i class="fas fa-file-alt"></i>
                    </button>
                    <button 
                      @click="sendReminder(customer.customer_id)"
                      class="action-btn reminder"
                      title="Send Payment Reminder"
                    >
                      <i class="fas fa-envelope"></i>
                    </button>
                    <button 
                      @click="viewCustomerDetail(customer.customer_id)"
                      class="action-btn detail"
                      title="View Customer Details"
                    >
                      <i class="fas fa-user"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
            <tfoot>
              <tr class="totals-row">
                <td class="customer-cell">
                  <strong>TOTALS</strong>
                </td>
                <td class="amount-cell current">
                  <strong>{{ formatCurrency(summary.current_amount) }}</strong>
                </td>
                <td class="amount-cell days-30">
                  <strong>{{ formatCurrency(summary.days_1_30) }}</strong>
                </td>
                <td class="amount-cell days-60">
                  <strong>{{ formatCurrency(summary.days_31_60) }}</strong>
                </td>
                <td class="amount-cell days-90">
                  <strong>{{ formatCurrency(summary.days_61_90) }}</strong>
                </td>
                <td class="amount-cell over-90">
                  <strong>{{ formatCurrency(summary.days_over_90) }}</strong>
                </td>
                <td class="amount-cell total">
                  <strong>{{ formatCurrency(summary.total_balance) }}</strong>
                </td>
                <td class="action-cell"></td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>

      <!-- Risk Analysis -->
      <div class="risk-analysis">
        <div class="analysis-header">
          <h3>
            <i class="fas fa-chart-line"></i>
            Risk Analysis
          </h3>
        </div>
        <div class="risk-cards">
          <div class="risk-card high-risk">
            <div class="risk-icon">
              <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="risk-content">
              <h4>High Risk Customers</h4>
              <p>{{ highRiskCustomers.length }} customers with significant overdue amounts</p>
              <div class="risk-amount">{{ formatCurrency(highRiskAmount) }}</div>
            </div>
          </div>

          <div class="risk-card medium-risk">
            <div class="risk-icon">
              <i class="fas fa-clock"></i>
            </div>
            <div class="risk-content">
              <h4>Medium Risk Customers</h4>
              <p>{{ mediumRiskCustomers.length }} customers with moderate aging</p>
              <div class="risk-amount">{{ formatCurrency(mediumRiskAmount) }}</div>
            </div>
          </div>

          <div class="risk-card low-risk">
            <div class="risk-icon">
              <i class="fas fa-check-circle"></i>
            </div>
            <div class="risk-content">
              <h4>Low Risk Customers</h4>
              <p>{{ lowRiskCustomers.length }} customers with current payments</p>
              <div class="risk-amount">{{ formatCurrency(lowRiskAmount) }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="empty-state">
      <i class="fas fa-chart-bar"></i>
      <h3>No Aging Data Available</h3>
      <p>No outstanding receivables found for the selected criteria</p>
      <router-link to="/accounting/receivables" class="btn btn-primary">
        View All Receivables
      </router-link>
    </div>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'AgingReport',
  data() {
    return {
      agingData: [],
      filteredAgingData: [],
      loading: false,
      displayCurrency: '',
      baseCurrencyOnly: false,
      baseCurrency: 'USD',
      availableCurrencies: ['USD', 'EUR', 'GBP', 'JPY', 'CAD', 'AUD'],
      reportDate: new Date().toISOString().split('T')[0],
      searchTerm: '',
      sortField: 'total_balance',
      sortOrder: 'desc',
      summary: {
        current_amount: 0,
        days_1_30: 0,
        days_31_60: 0,
        days_61_90: 0,
        days_over_90: 0,
        total_balance: 0
      }
    }
  },
  
  computed: {
    highRiskCustomers() {
      return this.filteredAgingData.filter(customer => {
        const total = customer.total_balance
        const highRisk = customer.days_61_90 + customer.days_over_90
        return total > 0 && (highRisk / total) > 0.3
      })
    },
    
    mediumRiskCustomers() {
      return this.filteredAgingData.filter(customer => {
        const total = customer.total_balance
        const highRisk = customer.days_61_90 + customer.days_over_90
        const mediumRisk = customer.days_31_60
        return total > 0 && (highRisk / total) <= 0.3 && (mediumRisk / total) > 0.3
      })
    },
    
    lowRiskCustomers() {
      return this.filteredAgingData.filter(customer => {
        const total = customer.total_balance
        const highRisk = customer.days_61_90 + customer.days_over_90
        const mediumRisk = customer.days_31_60
        return total > 0 && (highRisk / total) <= 0.3 && (mediumRisk / total) <= 0.3
      })
    },
    
    highRiskAmount() {
      return this.highRiskCustomers.reduce((sum, customer) => sum + customer.total_balance, 0)
    },
    
    mediumRiskAmount() {
      return this.mediumRiskCustomers.reduce((sum, customer) => sum + customer.total_balance, 0)
    },
    
    lowRiskAmount() {
      return this.lowRiskCustomers.reduce((sum, customer) => sum + customer.total_balance, 0)
    }
  },
  
  async mounted() {
    await this.loadAgingData()
  },
  
  methods: {
    async loadAgingData() {
      this.loading = true
      try {
        const params = {}
        
        if (this.displayCurrency) {
          params.display_currency = this.displayCurrency
        }
        
        if (this.baseCurrencyOnly) {
          params.base_currency_only = true
        }
        
        const response = await axios.get('/accounting/customer-receivables/aging', { params })
        
        this.agingData = response.data.data || []
        this.summary = response.data.totals || this.summary
        this.baseCurrency = response.data.display_currency || 'USD'
        
        this.filterAgingData()
        this.sortAgingData()
      } catch (error) {
        console.error('Error loading aging data:', error)
        this.$toast?.error('Failed to load aging report')
      } finally {
        this.loading = false
      }
    },
    
    filterAgingData() {
      if (!this.searchTerm) {
        this.filteredAgingData = [...this.agingData]
      } else {
        const term = this.searchTerm.toLowerCase()
        this.filteredAgingData = this.agingData.filter(customer =>
          customer.customer_name.toLowerCase().includes(term) ||
          customer.customer_id.toString().includes(term)
        )
      }
      this.sortAgingData()
    },
    
    sortAgingData() {
      this.filteredAgingData.sort((a, b) => {
        let valueA = a[this.sortField]
        let valueB = b[this.sortField]
        
        // Handle string sorting
        if (typeof valueA === 'string') {
          valueA = valueA.toLowerCase()
          valueB = valueB.toLowerCase()
        }
        
        if (this.sortOrder === 'asc') {
          return valueA > valueB ? 1 : -1
        } else {
          return valueA < valueB ? 1 : -1
        }
      })
    },
    
    toggleSortOrder() {
      this.sortOrder = this.sortOrder === 'asc' ? 'desc' : 'asc'
      this.sortAgingData()
    },
    
    getRiskClass(customer) {
      const total = customer.total_balance
      const highRisk = customer.days_61_90 + customer.days_over_90
      const mediumRisk = customer.days_31_60
      
      if (total === 0) return 'risk-none'
      if (highRisk / total > 0.3) return 'risk-high'
      if (mediumRisk / total > 0.3) return 'risk-medium'
      return 'risk-low'
    },
    
    getPercentage(amount) {
      return this.summary.total_balance > 0 ? 
        ((amount / this.summary.total_balance) * 100).toFixed(1) : '0.0'
    },
    
    viewCustomerReceivables(customerId) {
      this.$router.push(`/accounting/receivables?customer_id=${customerId}`)
    },
    
    generateStatement(customerId) {
      this.$router.push(`/accounting/receivables/statement/${customerId}`)
    },
    
    async sendReminder(customerId) {
      try {
        await axios.post(`/customers/${customerId}/send-payment-reminder`)
        this.$toast?.success('Payment reminder sent successfully')
      } catch (error) {
        console.error('Error sending reminder:', error)
        this.$toast?.error('Failed to send payment reminder')
      }
    },
    
    viewCustomerDetail(customerId) {
      this.$router.push(`/customers/${customerId}`)
    },
    
    exportReport() {
      const csvContent = this.generateCSV()
      this.downloadCSV(csvContent, `aging-report-${this.reportDate}.csv`)
    },
    
    generateCSV() {
      const headers = [
        'Customer ID',
        'Customer Name', 
        'Current', 
        '1-30 Days', 
        '31-60 Days', 
        '61-90 Days', 
        'Over 90 Days', 
        'Total Balance'
      ]
      
      const rows = this.filteredAgingData.map(customer => [
        customer.customer_id,
        customer.customer_name,
        customer.current_amount,
        customer.days_1_30,
        customer.days_31_60,
        customer.days_61_90,
        customer.days_over_90,
        customer.total_balance
      ])
      
      // Add totals row
      rows.push([
        '',
        'TOTALS',
        this.summary.current_amount,
        this.summary.days_1_30,
        this.summary.days_31_60,
        this.summary.days_61_90,
        this.summary.days_over_90,
        this.summary.total_balance
      ])
      
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
    
    printReport() {
      window.print()
    },
    
    formatCurrency(amount) {
      const currency = this.displayCurrency || this.baseCurrency
      return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: currency
      }).format(amount || 0)
    }
  }
}
</script>

<style scoped>
.aging-report {
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
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
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
.control-group input[type="date"],
.control-group input[type="text"] {
  padding: 0.5rem;
  border: 1px solid var(--gray-300);
  border-radius: 6px;
  font-size: 0.875rem;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
  font-size: 0.875rem;
  color: var(--gray-700);
}

.checkbox-label input[type="checkbox"] {
  margin: 0;
}

/* Currency Notice */
.currency-notice {
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

/* Summary Cards */
.summary-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.summary-card {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  display: flex;
  align-items: center;
  gap: 1rem;
  transition: transform 0.2s;
}

.summary-card:hover {
  transform: translateY(-2px);
}

.summary-card.total .card-icon { background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); }
.summary-card.current .card-icon { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
.summary-card.days-30 .card-icon { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
.summary-card.days-60 .card-icon { background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); }
.summary-card.days-90 .card-icon { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); }
.summary-card.over-90 .card-icon { background: linear-gradient(135deg, #991b1b 0%, #7f1d1d 100%); }

.card-icon {
  width: 60px;
  height: 60px;
  border-radius: 12px;
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
}

.card-content h3 {
  font-size: 1.5rem;
  font-weight: 700;
  margin-bottom: 0.25rem;
  color: var(--gray-800);
}

.card-content p {
  color: var(--gray-600);
  font-size: 0.875rem;
  margin-bottom: 0.25rem;
}

.customers-count,
.percentage {
  font-weight: 600;
  color: var(--gray-700);
  font-size: 0.75rem;
}

/* Aging Table */
.aging-table-container {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  margin-bottom: 2rem;
  overflow: hidden;
}

.table-header {
  padding: 1.5rem;
  border-bottom: 1px solid var(--gray-200);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.table-header h3 {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-size: 1.125rem;
  font-weight: 600;
  color: var(--gray-800);
}

.table-controls {
  display: flex;
  gap: 1rem;
  align-items: center;
}

.sort-select {
  padding: 0.5rem;
  border: 1px solid var(--gray-300);
  border-radius: 6px;
  font-size: 0.875rem;
}

.table-wrapper {
  overflow-x: auto;
}

.aging-table {
  width: 100%;
  border-collapse: collapse;
}

.aging-table th,
.aging-table td {
  padding: 1rem;
  text-align: left;
  border-bottom: 1px solid var(--gray-200);
}

.aging-table th {
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

.customer-col {
  min-width: 200px;
}

.action-col {
  width: 120px;
}

.aging-row {
  transition: background-color 0.2s;
}

.aging-row:hover {
  background: var(--gray-50);
}

.aging-row.risk-high {
  border-left: 4px solid #ef4444;
}

.aging-row.risk-medium {
  border-left: 4px solid #f59e0b;
}

.aging-row.risk-low {
  border-left: 4px solid #10b981;
}

.customer-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.customer-name {
  font-weight: 600;
  color: var(--gray-800);
}

.customer-id {
  font-size: 0.75rem;
  color: var(--gray-600);
}

.amount-cell {
  font-weight: 500;
  color: var(--gray-800);
}

.amount-cell.total {
  font-size: 1.125rem;
}

.highlight-warning {
  background: #fef3c7 !important;
  color: #92400e;
}

.highlight-danger {
  background: #fee2e2 !important;
  color: #dc2626;
}

.highlight-critical {
  background: #fecaca !important;
  color: #991b1b;
  font-weight: 600;
}

.totals-row {
  background: var(--gray-100);
  font-weight: 600;
}

.totals-row td {
  border-top: 2px solid var(--gray-300);
  font-size: 1.125rem;
}

/* Action Buttons */
.action-buttons {
  display: flex;
  gap: 0.25rem;
}

.action-btn {
  width: 28px;
  height: 28px;
  border-radius: 4px;
  border: 1px solid var(--gray-300);
  background: white;
  color: var(--gray-600);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
  font-size: 0.75rem;
}

.action-btn:hover {
  background: var(--primary-color);
  color: white;
  border-color: var(--primary-color);
}

.action-btn.view:hover { background: #3b82f6; border-color: #3b82f6; }
.action-btn.statement:hover { background: #10b981; border-color: #10b981; }
.action-btn.reminder:hover { background: #f59e0b; border-color: #f59e0b; }
.action-btn.detail:hover { background: #6366f1; border-color: #6366f1; }

/* Risk Analysis */
.risk-analysis {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.analysis-header {
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid var(--gray-200);
}

.analysis-header h3 {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-size: 1.125rem;
  font-weight: 600;
  color: var(--gray-800);
}

.risk-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 1.5rem;
}

.risk-card {
  padding: 1.5rem;
  border-radius: 12px;
  display: flex;
  align-items: center;
  gap: 1rem;
  border-left: 4px solid;
}

.risk-card.high-risk {
  background: #fef2f2;
  border-left-color: #ef4444;
}

.risk-card.medium-risk {
  background: #fffbeb;
  border-left-color: #f59e0b;
}

.risk-card.low-risk {
  background: #f0fdf4;
  border-left-color: #10b981;
}

.risk-icon {
  width: 50px;
  height: 50px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  color: white;
}

.high-risk .risk-icon { background: #ef4444; }
.medium-risk .risk-icon { background: #f59e0b; }
.low-risk .risk-icon { background: #10b981; }

.risk-content h4 {
  margin-bottom: 0.5rem;
  color: var(--gray-800);
}

.risk-content p {
  color: var(--gray-600);
  font-size: 0.875rem;
  margin-bottom: 0.5rem;
}

.risk-amount {
  font-weight: 600;
  font-size: 1.125rem;
  color: var(--gray-800);
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

.btn-sm {
  padding: 0.25rem 0.5rem;
  font-size: 0.75rem;
}

/* Responsive Design */
@media (max-width: 1024px) {
  .aging-report {
    padding: 1rem;
  }
  
  .controls-grid {
    grid-template-columns: 1fr;
  }
  
  .summary-cards {
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  }
  
  .risk-cards {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .header-content {
    flex-direction: column;
    gap: 1rem;
    align-items: stretch;
  }
  
  .table-controls {
    flex-direction: column;
    gap: 0.5rem;
  }
  
  .aging-table th,
  .aging-table td {
    padding: 0.5rem;
    font-size: 0.75rem;
  }
  
  .action-buttons {
    flex-direction: column;
  }
}
</style>