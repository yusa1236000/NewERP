<template>
  <div class="aging-report-container">
    <!-- Page Header -->
    <div class="page-header">
      <div class="header-content">
        <div class="header-text">
          <h1 class="page-title">
            <i class="fas fa-chart-bar"></i>
            Vendor Payables Aging Report
          </h1>
          <p class="page-subtitle">
            Multi-currency aging analysis of outstanding vendor payables
          </p>
        </div>
        <div class="header-actions">
          <router-link to="/accounting/vendor-payables" class="btn btn-outline">
            <i class="fas fa-arrow-left"></i>
            Back to Payables
          </router-link>
          <button @click="exportReport" class="btn btn-outline">
            <i class="fas fa-download"></i>
            Export
          </button>
          <button @click="printReport" class="btn btn-outline">
            <i class="fas fa-print"></i>
            Print
          </button>
        </div>
      </div>
    </div>

    <!-- Report Controls -->
    <div class="report-controls">
      <div class="controls-card">
        <div class="controls-grid">
          <div class="control-group">
            <label class="control-label">Report Currency</label>
            <select v-model="reportCurrency" @change="loadAgingData" class="control-select">
              <option v-for="currency in availableCurrencies" :key="currency" :value="currency">
                {{ currency }}
              </option>
            </select>
          </div>

          <div class="control-group">
            <label class="control-label">As of Date</label>
            <input v-model="asOfDate" @change="loadAgingData" type="date" class="control-input">
          </div>

          <div class="control-group">
            <label class="control-label">Vendor Filter</label>
            <select v-model="vendorFilter" @change="loadAgingData" class="control-select">
              <option value="">All Vendors</option>
              <option v-for="vendor in vendors" :key="vendor.vendor_id" :value="vendor.vendor_id">
                {{ vendor.name }}
              </option>
            </select>
          </div>

          <div class="control-group">
            <label class="control-label">Currency Filter</label>
            <select v-model="currencyFilter" @change="loadAgingData" class="control-select">
              <option value="">All Currencies</option>
              <option v-for="currency in originalCurrencies" :key="currency" :value="currency">
                {{ currency }}
              </option>
            </select>
          </div>

          <div class="control-group">
            <label class="control-label">Minimum Amount</label>
            <input v-model.number="minAmount" @change="applyFilters" type="number" step="0.01" class="control-input" placeholder="0.00">
          </div>

          <div class="control-group">
            <label class="control-label">View Mode</label>
            <select v-model="viewMode" @change="toggleViewMode" class="control-select">
              <option value="summary">Summary by Vendor</option>
              <option value="detailed">Detailed by Payable</option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <!-- Currency Summary Cards -->
    <div v-if="currencyBreakdown.length > 0" class="currency-summary">
      <h3>
        <i class="fas fa-coins"></i>
        Currency Breakdown
      </h3>
      <div class="currency-cards">
        <div v-for="currency in currencyBreakdown" :key="currency.currency" class="currency-card">
          <div class="currency-header">
            <div class="currency-icon">{{ getCurrencySymbol(currency.currency) }}</div>
            <div class="currency-info">
              <h4>{{ currency.currency }}</h4>
              <p>{{ currency.vendor_count }} vendors</p>
            </div>
          </div>
          <div class="currency-amounts">
            <div class="amount-row">
              <span>Total:</span>
              <span class="amount">{{ formatCurrency(currency.total_amount, currency.currency) }}</span>
            </div>
            <div class="amount-row converted">
              <span>In {{ reportCurrency }}:</span>
              <span class="amount">{{ formatCurrency(currency.converted_amount, reportCurrency) }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Summary Totals -->
    <div class="summary-cards">
      <div class="summary-card">
        <div class="summary-icon current">
          <i class="fas fa-clock"></i>
        </div>
        <div class="summary-content">
          <h3>{{ formatCurrency(totals.current_amount, reportCurrency) }}</h3>
          <p>Current (Not Due)</p>
          <small>{{ getPercentage(totals.current_amount, totals.total_balance) }}%</small>
        </div>
      </div>

      <div class="summary-card">
        <div class="summary-icon aging-30">
          <i class="fas fa-exclamation"></i>
        </div>
        <div class="summary-content">
          <h3>{{ formatCurrency(totals.days_1_30, reportCurrency) }}</h3>
          <p>1-30 Days</p>
          <small>{{ getPercentage(totals.days_1_30, totals.total_balance) }}%</small>
        </div>
      </div>

      <div class="summary-card">
        <div class="summary-icon aging-60">
          <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div class="summary-content">
          <h3>{{ formatCurrency(totals.days_31_60, reportCurrency) }}</h3>
          <p>31-60 Days</p>
          <small>{{ getPercentage(totals.days_31_60, totals.total_balance) }}%</small>
        </div>
      </div>

      <div class="summary-card">
        <div class="summary-icon aging-90">
          <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div class="summary-content">
          <h3>{{ formatCurrency(totals.days_61_90, reportCurrency) }}</h3>
          <p>61-90 Days</p>
          <small>{{ getPercentage(totals.days_61_90, totals.total_balance) }}%</small>
        </div>
      </div>

      <div class="summary-card">
        <div class="summary-icon overdue">
          <i class="fas fa-times-circle"></i>
        </div>
        <div class="summary-content">
          <h3>{{ formatCurrency(totals.days_over_90, reportCurrency) }}</h3>
          <p>Over 90 Days</p>
          <small>{{ getPercentage(totals.days_over_90, totals.total_balance) }}%</small>
        </div>
      </div>

      <div class="summary-card total">
        <div class="summary-icon total-icon">
          <i class="fas fa-calculator"></i>
        </div>
        <div class="summary-content">
          <h3>{{ formatCurrency(totals.total_balance, reportCurrency) }}</h3>
          <p>Total Outstanding</p>
          <small>{{ agingData.length }} vendors</small>
        </div>
      </div>
    </div>

    <!-- Aging Table -->
    <div class="aging-table-section">
      <div class="table-header">
        <h3>Aging Analysis</h3>
        <div class="table-actions">
          <div class="view-toggle">
            <button @click="viewMode = 'summary'" :class="{ active: viewMode === 'summary' }" class="btn btn-sm">
              Summary
            </button>
            <button @click="viewMode = 'detailed'" :class="{ active: viewMode === 'detailed' }" class="btn btn-sm">
              Detailed
            </button>
          </div>
          <button @click="refreshData" :disabled="loading" class="btn btn-ghost btn-sm">
            <i class="fas fa-refresh" :class="{ 'fa-spin': loading }"></i>
            Refresh
          </button>
        </div>
      </div>

      <div class="table-container">
        <div v-if="loading" class="loading-state">
          <div class="loading-spinner"></div>
          <p>Generating aging report...</p>
        </div>

        <div v-else-if="filteredAgingData.length === 0" class="empty-state">
          <i class="fas fa-chart-bar"></i>
          <h3>No Data Available</h3>
          <p>No aging data found for the selected criteria</p>
        </div>

        <table v-else class="aging-table">
          <thead>
            <tr>
              <th>
                <button @click="sortBy('vendor_name')" class="sort-header">
                  Vendor
                  <i class="fas fa-sort" :class="getSortIcon('vendor_name')"></i>
                </button>
              </th>
              <th>Currency Details</th>
              <th class="amount-col">
                <button @click="sortBy('current_amount')" class="sort-header">
                  Current
                  <i class="fas fa-sort" :class="getSortIcon('current_amount')"></i>
                </button>
              </th>
              <th class="amount-col">
                <button @click="sortBy('days_1_30')" class="sort-header">
                  1-30 Days
                  <i class="fas fa-sort" :class="getSortIcon('days_1_30')"></i>
                </button>
              </th>
              <th class="amount-col">
                <button @click="sortBy('days_31_60')" class="sort-header">
                  31-60 Days
                  <i class="fas fa-sort" :class="getSortIcon('days_31_60')"></i>
                </button>
              </th>
              <th class="amount-col">
                <button @click="sortBy('days_61_90')" class="sort-header">
                  61-90 Days
                  <i class="fas fa-sort" :class="getSortIcon('days_61_90')"></i>
                </button>
              </th>
              <th class="amount-col">
                <button @click="sortBy('days_over_90')" class="sort-header">
                  Over 90 Days
                  <i class="fas fa-sort" :class="getSortIcon('days_over_90')"></i>
                </button>
              </th>
              <th class="amount-col">
                <button @click="sortBy('total_balance')" class="sort-header">
                  Total
                  <i class="fas fa-sort" :class="getSortIcon('total_balance')"></i>
                </button>
              </th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <template v-for="vendor in filteredAgingData" :key="vendor.vendor_id">
              <!-- Vendor Summary Row -->
              <tr class="vendor-group-row" @click="toggleVendorExpansion(vendor.vendor_id)">
                <td>
                  <div class="vendor-toggle">
                    <i class="fas fa-chevron-right toggle-icon" :class="{ expanded: expandedVendors.includes(vendor.vendor_id) }"></i>
                    <div class="vendor-details">
                      <div class="vendor-avatar">
                        {{ vendor.vendor_name.charAt(0).toUpperCase() }}
                      </div>
                      <div class="vendor-text">
                        <span class="vendor-name">{{ vendor.vendor_name }}</span>
                        <small class="vendor-code">{{ getVendorCode(vendor.vendor_id) }}</small>
                      </div>
                    </div>
                  </div>
                </td>
                <td>
                  <div class="currency-breakdown-cell">
                    <div v-for="(breakdown, currency) in vendor.currency_breakdown" :key="currency" class="currency-item">
                      <span class="currency-badge" :class="`currency-${currency}`">{{ currency }}</span>
                      <span class="original-amount">{{ formatCurrency(breakdown.original_amount, currency) }}</span>
                    </div>
                  </div>
                </td>
                <td class="total-amount">
                  <div class="aging-amount current">
                    <span class="amount">{{ formatCurrency(vendor.current_amount, reportCurrency) }}</span>
                    <span class="percentage">{{ getPercentage(vendor.current_amount, vendor.total_balance) }}%</span>
                  </div>
                </td>
                <td class="total-amount">
                  <div class="aging-amount aging-30">
                    <span class="amount">{{ formatCurrency(vendor.days_1_30, reportCurrency) }}</span>
                    <span class="percentage">{{ getPercentage(vendor.days_1_30, vendor.total_balance) }}%</span>
                  </div>
                </td>
                <td class="total-amount">
                  <div class="aging-amount aging-60">
                    <span class="amount">{{ formatCurrency(vendor.days_31_60, reportCurrency) }}</span>
                    <span class="percentage">{{ getPercentage(vendor.days_31_60, vendor.total_balance) }}%</span>
                  </div>
                </td>
                <td class="total-amount">
                  <div class="aging-amount aging-90">
                    <span class="amount">{{ formatCurrency(vendor.days_61_90, reportCurrency) }}</span>
                    <span class="percentage">{{ getPercentage(vendor.days_61_90, vendor.total_balance) }}%</span>
                  </div>
                </td>
                <td class="total-amount">
                  <div class="aging-amount overdue">
                    <span class="amount">{{ formatCurrency(vendor.days_over_90, reportCurrency) }}</span>
                    <span class="percentage">{{ getPercentage(vendor.days_over_90, vendor.total_balance) }}%</span>
                  </div>
                </td>
                <td class="total-amount">
                  <span class="amount-value">{{ formatCurrency(vendor.total_balance, reportCurrency) }}</span>
                  <span class="payable-count">{{ getPayableCount(vendor.vendor_id) }} payables</span>
                </td>
                <td>
                  <div class="action-buttons">
                    <router-link :to="`/accounting/vendor-payables?vendor_id=${vendor.vendor_id}`" class="btn btn-ghost btn-sm">
                      <i class="fas fa-eye"></i>
                    </router-link>
                    <button @click.stop="viewVendorDetails(vendor.vendor_id)" class="btn btn-ghost btn-sm">
                      <i class="fas fa-chart-line"></i>
                    </button>
                  </div>
                </td>
              </tr>

              <!-- Detailed Payable Rows (when expanded) -->
              <template v-if="expandedVendors.includes(vendor.vendor_id) && viewMode === 'detailed'">
                <tr v-for="payable in getVendorPayables(vendor.vendor_id)" :key="payable.payable_id" class="payable-detail-row">
                  <td colspan="2">
                    <div class="payable-details">
                      <div class="payable-info">
                        <router-link :to="`/payables/${payable.payable_id}`" class="payable-link">
                          #{{ payable.payable_id }}
                        </router-link>
                        <span class="invoice-number">{{ payable.invoice_number }}</span>
                        <span class="due-date">Due: {{ formatDate(payable.due_date) }}</span>
                      </div>
                      <div class="payable-currency">
                        <span class="currency-badge" :class="`currency-${payable.currency}`">{{ payable.currency }}</span>
                        <span class="original-amount">{{ formatCurrency(payable.original_balance, payable.currency) }}</span>
                        <span v-if="payable.currency !== reportCurrency" class="converted-amount">
                          = {{ formatCurrency(payable.converted_balance, reportCurrency) }}
                        </span>
                      </div>
                    </div>
                  </td>
                  <td class="aging-amount current">{{ payable.aging_buckets.current > 0 ? formatCurrency(payable.aging_buckets.current, reportCurrency) : '-' }}</td>
                  <td class="aging-amount aging-30">{{ payable.aging_buckets.days_1_30 > 0 ? formatCurrency(payable.aging_buckets.days_1_30, reportCurrency) : '-' }}</td>
                  <td class="aging-amount aging-60">{{ payable.aging_buckets.days_31_60 > 0 ? formatCurrency(payable.aging_buckets.days_31_60, reportCurrency) : '-' }}</td>
                  <td class="aging-amount aging-90">{{ payable.aging_buckets.days_61_90 > 0 ? formatCurrency(payable.aging_buckets.days_61_90, reportCurrency) : '-' }}</td>
                  <td class="aging-amount overdue">{{ payable.aging_buckets.days_over_90 > 0 ? formatCurrency(payable.aging_buckets.days_over_90, reportCurrency) : '-' }}</td>
                  <td class="aging-amount">{{ formatCurrency(payable.converted_balance, reportCurrency) }}</td>
                  <td>
                    <router-link :to="`/payables/${payable.payable_id}/payment`" class="btn btn-ghost btn-sm">
                      <i class="fas fa-credit-card"></i>
                    </router-link>
                  </td>
                </tr>
              </template>
            </template>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Report Footer -->
    <div class="report-footer">
      <div class="report-info">
        <div class="info-item">
          <strong>Report Date:</strong> {{ formatDate(asOfDate) }}
        </div>
        <div class="info-item">
          <strong>Report Currency:</strong> {{ reportCurrency }}
        </div>
        <div class="info-item">
          <strong>Total Vendors:</strong> {{ agingData.length }}
        </div>
        <div class="info-item">
          <strong>Currencies Included:</strong> {{ originalCurrencies.join(', ') }}
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'AgingReport',
  data() {
    return {
      loading: false,
      reportCurrency: 'USD',
      asOfDate: new Date().toISOString().split('T')[0],
      vendorFilter: '',
      currencyFilter: '',
      minAmount: 0,
      viewMode: 'summary',
      sortField: 'total_balance',
      sortDirection: 'desc',
      
      agingData: [],
      vendors: [],
      availableCurrencies: ['USD', 'IDR', 'EUR', 'SGD', 'JPY', 'CNY', 'GBP', 'AUD'],
      originalCurrencies: [],
      expandedVendors: [],
      
      totals: {
        current_amount: 0,
        days_1_30: 0,
        days_31_60: 0,
        days_61_90: 0,
        days_over_90: 0,
        total_balance: 0
      },
      
      currencyBreakdown: [],
      detailedPayables: []
    }
  },
  
  computed: {
    filteredAgingData() {
      let filtered = [...this.agingData]
      
      if (this.vendorFilter) {
        filtered = filtered.filter(item => item.vendor_id == this.vendorFilter)
      }
      
      if (this.currencyFilter) {
        filtered = filtered.filter(item => 
          item.currency_breakdown && 
          Object.keys(item.currency_breakdown).includes(this.currencyFilter)
        )
      }
      
      if (this.minAmount > 0) {
        filtered = filtered.filter(item => item.total_balance >= this.minAmount)
      }
      
      // Sort data
      filtered.sort((a, b) => {
        const aVal = a[this.sortField] || 0
        const bVal = b[this.sortField] || 0
        
        if (this.sortField === 'vendor_name') {
          return this.sortDirection === 'asc' 
            ? aVal.localeCompare(bVal)
            : bVal.localeCompare(aVal)
        }
        
        return this.sortDirection === 'asc' ? aVal - bVal : bVal - aVal
      })
      
      return filtered
    }
  },
  
  mounted() {
    this.loadVendors()
    this.loadAgingData()
  },
  
  methods: {
    async loadVendors() {
      try {
        const response = await axios.get('/vendors')
        this.vendors = response.data.data || response.data
      } catch (error) {
        console.error('Error loading vendors:', error)
      }
    },
    
    async loadAgingData() {
      this.loading = true
      try {
        const params = {
          currency: this.reportCurrency,
          as_of_date: this.asOfDate
        }
        
        if (this.vendorFilter) {
          params.vendor_id = this.vendorFilter
        }
        
        const response = await axios.get('/accounting/vendor-payables/aging', { params })
        
        this.agingData = response.data.data || []
        this.totals = response.data.totals || this.totals
        
        // Extract original currencies
        this.originalCurrencies = response.data.metadata?.available_currencies || []
        
        // Process currency breakdown
        this.processCurrencyBreakdown()
        
        this.$toast?.success('Aging report generated successfully')
      } catch (error) {
        console.error('Error loading aging data:', error)
        this.$toast?.error('Failed to generate aging report')
        this.agingData = []
      } finally {
        this.loading = false
      }
    },
    
    processCurrencyBreakdown() {
      const currencyMap = new Map()
      
      this.agingData.forEach(vendor => {
        if (vendor.currency_breakdown) {
          Object.entries(vendor.currency_breakdown).forEach(([currency, data]) => {
            if (!currencyMap.has(currency)) {
              currencyMap.set(currency, {
                currency,
                total_amount: 0,
                converted_amount: 0,
                vendor_count: 0
              })
            }
            
            const curr = currencyMap.get(currency)
            curr.total_amount += data.original_amount || 0
            curr.converted_amount += data.converted_amount || 0
            curr.vendor_count += 1
          })
        }
      })
      
      this.currencyBreakdown = Array.from(currencyMap.values())
    },
    
    toggleVendorExpansion(vendorId) {
      const index = this.expandedVendors.indexOf(vendorId)
      if (index > -1) {
        this.expandedVendors.splice(index, 1)
      } else {
        this.expandedVendors.push(vendorId)
        this.loadVendorPayables(vendorId)
      }
    },
    
    async loadVendorPayables(vendorId) {
      try {
        const response = await axios.get('/accounting/vendor-payables', {
          params: {
            vendor_id: vendorId,
            status: 'Open,Partial,Overdue',
            convert_to_currency: this.reportCurrency,
            conversion_date: this.asOfDate
          }
        })
        
        const payables = (response.data.data || []).map(payable => ({
          ...payable,
          aging_buckets: this.calculatePayableAging(payable)
        }))
        
        this.detailedPayables = [
          ...this.detailedPayables.filter(p => p.vendor_id !== vendorId),
          ...payables
        ]
      } catch (error) {
        console.error('Error loading vendor payables:', error)
      }
    },
    
    calculatePayableAging(payable) {
      const dueDate = new Date(payable.due_date)
      const asOf = new Date(this.asOfDate)
      const daysDiff = Math.floor((asOf - dueDate) / (1000 * 60 * 60 * 24))
      
      const balance = payable.converted_amounts?.converted_balance || payable.balance
      
      const buckets = {
        current: 0,
        days_1_30: 0,
        days_31_60: 0,
        days_61_90: 0,
        days_over_90: 0
      }
      
      if (daysDiff <= 0) {
        buckets.current = balance
      } else if (daysDiff <= 30) {
        buckets.days_1_30 = balance
      } else if (daysDiff <= 60) {
        buckets.days_31_60 = balance
      } else if (daysDiff <= 90) {
        buckets.days_61_90 = balance
      } else {
        buckets.days_over_90 = balance
      }
      
      return buckets
    },
    
    getVendorPayables(vendorId) {
      return this.detailedPayables.filter(p => p.vendor_id === vendorId)
    },
    
    getVendorCode(vendorId) {
      const vendor = this.vendors.find(v => v.vendor_id === vendorId)
      return vendor?.vendor_code || ''
    },
    
    getPayableCount(vendorId) {
      return this.getVendorPayables(vendorId).length
    },
    
    toggleViewMode() {
      if (this.viewMode === 'detailed') {
        // Load all payables for all vendors when switching to detailed mode
        this.agingData.forEach(vendor => {
          if (!this.expandedVendors.includes(vendor.vendor_id)) {
            this.loadVendorPayables(vendor.vendor_id)
          }
        })
      }
    },
    
    applyFilters() {
      // Filters are applied through computed property
    },
    
    sortBy(field) {
      if (this.sortField === field) {
        this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc'
      } else {
        this.sortField = field
        this.sortDirection = 'desc'
      }
    },
    
    getSortIcon(field) {
      if (this.sortField !== field) return ''
      return this.sortDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down'
    },
    
    refreshData() {
      this.loadAgingData()
    },
    
    viewVendorDetails(vendorId) {
      this.$router.push(`/accounting/vendor-payables?vendor_id=${vendorId}`)
    },
    
    async exportReport() {
      try {
        const params = {
          currency: this.reportCurrency,
          as_of_date: this.asOfDate,
          vendor_id: this.vendorFilter,
          currency_filter: this.currencyFilter,
          min_amount: this.minAmount,
          format: 'excel'
        }
        
        const response = await axios.get('/accounting/vendor-payables/aging/export', {
          params,
          responseType: 'blob'
        })
        
        const url = window.URL.createObjectURL(new Blob([response.data]))
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', `aging-report-${this.asOfDate}.xlsx`)
        document.body.appendChild(link)
        link.click()
        link.remove()
        
        this.$toast?.success('Report exported successfully')
      } catch (error) {
        console.error('Error exporting report:', error)
        this.$toast?.error('Failed to export report')
      }
    },
    
    printReport() {
      window.print()
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
      if (!date) return ''
      return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      })
    },
    
    getPercentage(amount, total) {
      if (!total || total === 0) return '0'
      return ((amount / total) * 100).toFixed(1)
    }
  }
}
</script>

<style scoped>
/* Preserve existing aging report styles and add multi-currency enhancements */
.aging-report-container {
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

/* Report Controls */
.report-controls {
  background: white;
  border-radius: 16px;
  padding: 1.5rem;
  margin-bottom: 2rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}

.controls-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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

/* Currency Summary */
.currency-summary {
  background: white;
  border-radius: 16px;
  padding: 1.5rem;
  margin-bottom: 2rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}

.currency-summary h3 {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 1rem;
  color: #1f2937;
  font-weight: 600;
}

.currency-cards {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 1rem;
}

.currency-card {
  background: #f8fafc;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 1rem;
}

.currency-header {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 1rem;
}

.currency-icon {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
}

.currency-info h4 {
  font-weight: 600;
  color: #1f2937;
  margin: 0;
}

.currency-info p {
  color: #6b7280;
  font-size: 0.9rem;
  margin: 0;
}

.currency-amounts .amount-row {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.5rem;
}

.amount-row.converted {
  font-style: italic;
  color: #6b7280;
}

.amount-row .amount {
  font-weight: 600;
}

/* Summary Cards */
.summary-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  margin-bottom: 2rem;
}

.summary-card {
  background: white;
  border-radius: 16px;
  padding: 1.5rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
  transition: transform 0.3s ease;
}

.summary-card:hover {
  transform: translateY(-2px);
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

.summary-icon.current { background: linear-gradient(135deg, #10b981, #059669); }
.summary-icon.aging-30 { background: linear-gradient(135deg, #f59e0b, #d97706); }
.summary-icon.aging-60 { background: linear-gradient(135deg, #ef4444, #dc2626); }
.summary-icon.aging-90 { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }
.summary-icon.overdue { background: linear-gradient(135deg, #991b1b, #7f1d1d); }
.summary-icon.total-icon { background: linear-gradient(135deg, #6366f1, #4f46e5); }

.summary-content h3 {
  font-size: 1.25rem;
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

/* Table Section */
.aging-table-section {
  background: white;
  border-radius: 16px;
  padding: 1.5rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}

.table-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.table-header h3 {
  font-size: 1.25rem;
  font-weight: 600;
  color: #1f2937;
}

.table-actions {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.view-toggle {
  display: flex;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  overflow: hidden;
}

.view-toggle .btn {
  border-radius: 0;
  border: none;
}

.view-toggle .btn.active {
  background: #6366f1;
  color: white;
}

.table-container {
  overflow-x: auto;
}

.aging-table {
  width: 100%;
  border-collapse: collapse;
  margin-bottom: 1.5rem;
}

.aging-table th {
  background: #f8fafc;
  padding: 1rem;
  text-align: left;
  font-weight: 600;
  color: #374151;
  border-bottom: 2px solid #e5e7eb;
  white-space: nowrap;
}

.aging-table th.amount-col {
  text-align: right;
  min-width: 120px;
}

.sort-header {
  background: none;
  border: none;
  padding: 0;
  font-weight: 600;
  color: #374151;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  width: 100%;
  justify-content: flex-start;
}

.sort-header:hover {
  color: #6366f1;
}

.aging-table th.amount-col .sort-header {
  justify-content: flex-end;
}

.aging-table td {
  padding: 1rem;
  border-bottom: 1px solid #e5e7eb;
  vertical-align: top;
}

/* Vendor Group Row */
.vendor-group-row {
  background: #f8fafc;
  cursor: pointer;
  transition: background 0.3s ease;
}

.vendor-group-row:hover {
  background: #f1f5f9;
}

.vendor-toggle {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.toggle-icon {
  transition: transform 0.3s ease;
  color: #6b7280;
}

.toggle-icon.expanded {
  transform: rotate(90deg);
}

.vendor-details {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.vendor-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
}

.vendor-text {
  display: flex;
  flex-direction: column;
}

.vendor-name {
  font-weight: 600;
  color: #1f2937;
}

.vendor-code {
  color: #6b7280;
  font-size: 0.8rem;
}

.currency-breakdown-cell {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.currency-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.currency-badge {
  padding: 0.125rem 0.375rem;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 600;
  text-align: center;
}

.currency-USD { background: #dbeafe; color: #1e40af; }
.currency-EUR { background: #fef3c7; color: #92400e; }
.currency-GBP { background: #ecfdf5; color: #065f46; }
.currency-JPY { background: #fce7f3; color: #9d174d; }
.currency-IDR { background: #f3e8ff; color: #6b21a8; }
.currency-SGD { background: #e0f2fe; color: #0e7490; }

.original-amount {
  font-size: 0.8rem;
  color: #6b7280;
}

.total-amount {
  text-align: right;
}

.amount-value {
  font-size: 1.1rem;
  font-weight: 700;
  color: #1f2937;
  display: block;
}

.payable-count {
  font-size: 0.8rem;
  color: #6b7280;
  margin-top: 0.25rem;
}

.aging-amount {
  text-align: right;
}

.aging-amount .amount {
  font-weight: 600;
  display: block;
}

.aging-amount .percentage {
  font-size: 0.8rem;
  color: #6b7280;
  margin-top: 0.25rem;
}

.aging-amount.current .amount { color: #10b981; }
.aging-amount.aging-30 .amount { color: #f59e0b; }
.aging-amount.aging-60 .amount { color: #ef4444; }
.aging-amount.aging-90 .amount { color: #8b5cf6; }
.aging-amount.overdue .amount { color: #991b1b; }

/* Payable Detail Row */
.payable-detail-row {
  background: white;
  border-left: 4px solid #e5e7eb;
}

.payable-detail-row:hover {
  background: #f9fafb;
  border-left-color: #6366f1;
}

.payable-details {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  padding-left: 40px;
}

.payable-info {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.payable-link {
  color: #6366f1;
  font-weight: 600;
  text-decoration: none;
}

.payable-link:hover {
  text-decoration: underline;
}

.invoice-number {
  color: #6b7280;
  font-size: 0.9rem;
}

.due-date {
  color: #6b7280;
  font-size: 0.8rem;
}

.payable-currency {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.converted-amount {
  color: #6b7280;
  font-style: italic;
  font-size: 0.8rem;
}

/* Report Footer */
.report-footer {
  background: white;
  border-radius: 16px;
  padding: 1.5rem;
  margin-top: 2rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}

.report-info {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}

.info-item {
  color: #6b7280;
  font-size: 0.9rem;
}

.info-item strong {
  color: #1f2937;
}

/* Loading and Empty States */
.loading-state, .empty-state {
  text-align: center;
  padding: 3rem;
  color: #6b7280;
}

.loading-spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #e5e7eb;
  border-top: 4px solid #6366f1;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 1rem;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.empty-state i {
  font-size: 3rem;
  color: #d1d5db;
  margin-bottom: 1rem;
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

.action-buttons {
  display: flex;
  gap: 0.5rem;
}

/* Responsive */
@media (max-width: 1024px) {
  .aging-table {
    font-size: 0.8rem;
  }
  
  .aging-table th,
  .aging-table td {
    padding: 0.75rem 0.5rem;
  }
}

@media (max-width: 768px) {
  .aging-report-container {
    padding: 1rem;
  }
  
  .header-content {
    flex-direction: column;
    gap: 1rem;
    align-items: flex-start;
  }
  
  .controls-grid {
    grid-template-columns: 1fr;
  }
  
  .summary-cards {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .aging-table {
    font-size: 0.7rem;
  }
  
  .vendor-details {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.5rem;
  }
  
  .payable-details {
    padding-left: 20px;
  }
}

@media print {
  .page-header .header-actions,
  .table-actions,
  .action-buttons {
    display: none;
  }
  
  .aging-report-container {
    padding: 0;
    background: white;
  }
  
  .summary-cards,
  .aging-table-section,
  .currency-summary {
    box-shadow: none;
    border: 1px solid #e5e7eb;
  }
}
</style>