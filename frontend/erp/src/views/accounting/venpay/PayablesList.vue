<template>
  <div class="payables-container">
    <!-- Page Header -->
    <div class="page-header">
      <div class="header-content">
        <div class="header-text">
          <h1 class="page-title">
            <i class="fas fa-file-invoice"></i>
            Vendor Payables
          </h1>
          <p class="page-subtitle">
            Manage and track vendor payables across multiple currencies
          </p>
        </div>
        <div class="header-actions">
          <button @click="showCurrencyModal = true" class="btn btn-outline">
            <i class="fas fa-exchange-alt"></i>
            Currency View
          </button>
          <router-link to="/accounting/aging-report" class="btn btn-outline">
            <i class="fas fa-chart-bar"></i>
            Aging Report
          </router-link>
          <router-link to="/accounting/payables/create" class="btn btn-primary">
            <i class="fas fa-plus"></i>
            New Payable
          </router-link>
        </div>
      </div>
    </div>

    <!-- Currency Summary -->
    <div v-if="currencySummary.length > 0" class="currency-summary">
      <div class="summary-header">
        <h3>
          <i class="fas fa-coins"></i>
          Multi-Currency Summary
        </h3>
        <div class="summary-controls">
          <select v-model="selectedDisplayCurrency" @change="loadData" class="currency-select">
            <option value="">Original Currencies</option>
            <option v-for="currency in availableCurrencies" :key="currency" :value="currency">
              Convert to {{ currency }}
            </option>
          </select>
        </div>
      </div>
      <div class="currency-cards">
        <div v-for="currency in currencySummary" :key="currency.currency_code" class="currency-card">
          <div class="currency-header">
            <div class="currency-icon">{{ getCurrencySymbol(currency.currency_code) }}</div>
            <div class="currency-info">
              <h4>{{ currency.currency_code }}</h4>
              <p>{{ currency.count }} payables</p>
            </div>
          </div>
          <div class="currency-amounts">
            <div class="amount-row">
              <span class="label">Total:</span>
              <span class="amount">{{ formatCurrency(currency.amounts.original.total_amount, currency.currency_code) }}</span>
            </div>
            <div class="amount-row">
              <span class="label">Balance:</span>
              <span class="amount balance">{{ formatCurrency(currency.amounts.original.total_balance, currency.currency_code) }}</span>
            </div>
            <div v-if="selectedDisplayCurrency && currency.amounts.converted" class="converted-amounts">
              <div class="conversion-info">
                <small>@ {{ currency.amounts.converted.exchange_rate }} = {{ formatCurrency(currency.amounts.converted.total_balance, selectedDisplayCurrency) }}</small>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="filters-section">
      <div class="filters-card">
        <div class="filters-grid">
          <div class="filter-group">
            <label class="filter-label">Search</label>
            <div class="search-box">
              <i class="fas fa-search"></i>
              <input 
                v-model="searchQuery" 
                @input="debounceSearch"
                type="text" 
                class="search-input" 
                placeholder="Search payables..."
              >
            </div>
          </div>
          
          <div class="filter-group">
            <label class="filter-label">Vendor</label>
            <select v-model="filters.vendor_id" @change="applyFilters" class="filter-select">
              <option value="">All Vendors</option>
              <option v-for="vendor in vendors" :key="vendor.vendor_id" :value="vendor.vendor_id">
                {{ vendor.name }}
              </option>
            </select>
          </div>
          
          <div class="filter-group">
            <label class="filter-label">Status</label>
            <select v-model="filters.status" @change="applyFilters" class="filter-select">
              <option value="">All Status</option>
              <option value="Open">Open</option>
              <option value="Partial">Partial</option>
              <option value="Paid">Paid</option>
              <option value="Overdue">Overdue</option>
            </select>
          </div>

          <div class="filter-group">
            <label class="filter-label">Currency</label>
            <select v-model="filters.currency_code" @change="applyFilters" class="filter-select">
              <option value="">All Currencies</option>
              <option v-for="currency in availableCurrencies" :key="currency" :value="currency">
                {{ currency }}
              </option>
            </select>
          </div>
          
          <div class="filter-group">
            <label class="filter-label">From Date</label>
            <input v-model="filters.from_date" @change="applyFilters" type="date" class="filter-input">
          </div>
          
          <div class="filter-group">
            <label class="filter-label">To Date</label>
            <input v-model="filters.to_date" @change="applyFilters" type="date" class="filter-input">
          </div>
        </div>
        
        <div class="filters-actions">
          <button @click="clearFilters" class="btn btn-ghost">
            <i class="fas fa-times"></i>
            Clear
          </button>
          <button @click="exportData" class="btn btn-outline">
            <i class="fas fa-download"></i>
            Export
          </button>
        </div>
      </div>
    </div>

    <!-- Summary Cards -->
    <div class="summary-grid">
      <div class="summary-card">
        <div class="summary-icon total">
          <i class="fas fa-file-invoice"></i>
        </div>
        <div class="summary-content">
          <h3>{{ formatCurrency(summary.total_amount) }}</h3>
          <p>Total Amount</p>
        </div>
      </div>
      
      <div class="summary-card">
        <div class="summary-icon pending">
          <i class="fas fa-clock"></i>
        </div>
        <div class="summary-content">
          <h3>{{ formatCurrency(summary.pending_amount) }}</h3>
          <p>Pending Amount</p>
        </div>
      </div>
      
      <div class="summary-card">
        <div class="summary-icon overdue">
          <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div class="summary-content">
          <h3>{{ formatCurrency(summary.overdue_amount) }}</h3>
          <p>Overdue Amount</p>
        </div>
      </div>
      
      <div class="summary-card">
        <div class="summary-icon paid">
          <i class="fas fa-check-circle"></i>
        </div>
        <div class="summary-content">
          <h3>{{ summary.paid_count }}</h3>
          <p>Paid This Month</p>
        </div>
      </div>
    </div>

    <!-- Payables Table -->
    <div class="table-section">
      <div class="table-header">
        <h3>Payables List</h3>
        <div class="table-actions">
          <button @click="refreshData" :disabled="loading" class="btn btn-ghost">
            <i class="fas fa-refresh" :class="{ 'fa-spin': loading }"></i>
            Refresh
          </button>
        </div>
      </div>
      
      <div class="table-container">
        <div v-if="loading" class="loading-state">
          <div class="loading-spinner"></div>
          <p>Loading payables...</p>
        </div>
        
        <div v-else-if="payables.length === 0" class="empty-state">
          <i class="fas fa-file-invoice"></i>
          <h3>No Payables Found</h3>
          <p>No payables match your current filters</p>
          <button @click="clearFilters" class="btn btn-primary">Clear Filters</button>
        </div>
        
        <table v-else class="payables-table">
          <thead>
            <tr>
              <th @click="sortBy('payable_id')" class="sortable">
                Payable ID
                <i class="fas fa-sort" :class="getSortIcon('payable_id')"></i>
              </th>
              <th>Vendor</th>
              <th>Invoice</th>
              <th @click="sortBy('amount')" class="sortable">
                Amount
                <i class="fas fa-sort" :class="getSortIcon('amount')"></i>
              </th>
              <th>Currency</th>
              <th>Paid Amount</th>
              <th>Balance</th>
              <th @click="sortBy('due_date')" class="sortable">
                Due Date
                <i class="fas fa-sort" :class="getSortIcon('due_date')"></i>
              </th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="payable in payables" :key="payable.payable_id" class="payable-row">
              <td class="payable-id">
                <router-link :to="`/payables/${payable.payable_id}`" class="id-link">
                  #{{ payable.payable_id }}
                </router-link>
              </td>
              <td>
                <div class="vendor-info">
                  <span class="vendor-name">{{ payable.vendor?.name || 'N/A' }}</span>
                  <small class="vendor-code">{{ payable.vendor?.vendor_code }}</small>
                </div>
              </td>
              <td>
                <span class="invoice-number">{{ payable.vendor_invoice?.invoice_number || 'N/A' }}</span>
              </td>
              <td>
                <div class="amount-cell">
                  <span class="amount">{{ formatCurrency(payable.amount, payable.currency_code) }}</span>
                  <div v-if="payable.converted_amounts && selectedDisplayCurrency" class="converted-amount">
                    <small>{{ formatCurrency(payable.converted_amounts.converted_amount, selectedDisplayCurrency) }}</small>
                  </div>
                </div>
              </td>
              <td>
                <div class="currency-info">
                  <span class="currency-badge" :class="`currency-${payable.currency_code}`">
                    {{ payable.currency_code }}
                  </span>
                  <div v-if="payable.exchange_rate && payable.exchange_rate !== 1" class="exchange-rate">
                    <small>@ {{ payable.exchange_rate }}</small>
                  </div>
                </div>
              </td>
              <td>
                <div class="amount-cell">
                  <span class="paid-amount">{{ formatCurrency(payable.paid_amount || 0, payable.currency_code) }}</span>
                  <div v-if="payable.converted_amounts && selectedDisplayCurrency" class="converted-amount">
                    <small>{{ formatCurrency((payable.paid_amount || 0) * (payable.converted_amounts.exchange_rate || 1), selectedDisplayCurrency) }}</small>
                  </div>
                </div>
              </td>
              <td>
                <div class="amount-cell">
                  <span class="balance" :class="{ 'text-danger': payable.balance > 0 }">
                    {{ formatCurrency(payable.balance, payable.currency_code) }}
                  </span>
                  <div v-if="payable.converted_amounts && selectedDisplayCurrency" class="converted-amount">
                    <small>{{ formatCurrency(payable.converted_amounts.converted_balance, selectedDisplayCurrency) }}</small>
                  </div>
                </div>
              </td>
              <td>
                <div class="due-date" :class="getDueDateClass(payable.due_date)">
                  <i class="fas fa-calendar-alt"></i>
                  {{ formatDate(payable.due_date) }}
                </div>
              </td>
              <td>
                <span class="status-badge" :class="`status-${payable.status}`">
                  {{ getStatusText(payable.status) }}
                </span>
              </td>
              <td>
                <div class="action-buttons">
                  <router-link :to="`/accounting/payables/${payable.payable_id}`" class="btn btn-ghost btn-sm">
                    <i class="fas fa-eye"></i>
                  </router-link>
                  <router-link :to="`accounting/payables/${payable.payable_id}/edit`" class="btn btn-ghost btn-sm">
                    <i class="fas fa-edit"></i>
                  </router-link>
                  <router-link :to="`accounting/payables/${payable.payable_id}/payment`" class="btn btn-ghost btn-sm">
                    <i class="fas fa-credit-card"></i>
                  </router-link>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      
      <!-- Pagination -->
      <div v-if="pagination.total > pagination.per_page" class="pagination-section">
        <div class="pagination-info">
          Showing {{ pagination.from || 0 }} to {{ pagination.to || 0 }} of {{ pagination.total || 0 }} entries
        </div>
        <div class="pagination-buttons">
          <button 
            @click="changePage(pagination.current_page - 1)" 
            :disabled="!pagination.prev_page_url"
            class="btn btn-outline btn-sm"
          >
            <i class="fas fa-chevron-left"></i>
            Previous
          </button>
          
          <div class="page-numbers">
            <button 
              v-for="page in getVisiblePages()" 
              :key="page"
              @click="changePage(page)"
              :class="{ active: page === pagination.current_page }"
              class="btn btn-outline btn-sm"
            >
              {{ page }}
            </button>
          </div>
          
          <button 
            @click="changePage(pagination.current_page + 1)" 
            :disabled="!pagination.next_page_url"
            class="btn btn-outline btn-sm"
          >
            Next
            <i class="fas fa-chevron-right"></i>
          </button>
        </div>
      </div>
    </div>

    <!-- Currency Modal -->
    <div v-if="showCurrencyModal" class="modal-overlay" @click="showCurrencyModal = false">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3>Currency Summary</h3>
          <button @click="showCurrencyModal = false" class="btn btn-ghost">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <div class="currency-summary-grid">
            <div v-for="currency in currencySummary" :key="currency.currency_code" class="currency-summary-card">
              <div class="currency-header">
                <h4>{{ currency.currency_code }}</h4>
                <span class="currency-count">{{ currency.count }} payables</span>
              </div>
              <div class="currency-details">
                <div class="detail-row">
                  <span>Total Amount:</span>
                  <span>{{ formatCurrency(currency.amounts.original.total_amount, currency.currency_code) }}</span>
                </div>
                <div class="detail-row">
                  <span>Total Balance:</span>
                  <span>{{ formatCurrency(currency.amounts.original.total_balance, currency.currency_code) }}</span>
                </div>
                <div class="detail-row">
                  <span>Total Paid:</span>
                  <span>{{ formatCurrency(currency.amounts.original.total_paid, currency.currency_code) }}</span>
                </div>
              </div>
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
  name: 'PayablesList',
  data() {
    return {
      payables: [],
      vendors: [],
      loading: false,
      searchQuery: '',
      selectedDisplayCurrency: '',
      showCurrencyModal: false,
      availableCurrencies: ['USD', 'IDR', 'EUR', 'SGD', 'JPY', 'CNY', 'GBP', 'AUD'],
      currencySummary: [],
      filters: {
        vendor_id: '',
        status: '',
        currency_code: '',
        from_date: '',
        to_date: ''
      },
      sortField: 'due_date',
      sortDirection: 'asc',
      summary: {
        total_amount: 0,
        pending_amount: 0,
        overdue_amount: 0,
        paid_count: 0
      },
      pagination: {
        current_page: 1,
        per_page: 15,
        total: 0,
        from: 0,
        to: 0,
        prev_page_url: null,
        next_page_url: null
      },
      searchTimeout: null,
      filterTimeout: null
    }
  },
  
  computed: {
    filteredPayables() {
      return this.payables.filter(payable => {
        const matchesSearch = !this.searchQuery || 
          payable.vendor?.name?.toLowerCase().includes(this.searchQuery.toLowerCase()) ||
          payable.vendor_invoice?.invoice_number?.toLowerCase().includes(this.searchQuery.toLowerCase()) ||
          payable.payable_id.toString().includes(this.searchQuery)
        
        return matchesSearch
      })
    }
  },
  
  mounted() {
    this.loadData()
    this.loadVendors()
    this.loadCurrencySummary()
  },
  
  methods: {
    async loadData() {
      this.loading = true
      try {
        const params = {
          page: this.pagination.current_page,
          per_page: this.pagination.per_page,
          sort_field: this.sortField,
          sort_direction: this.sortDirection,
          ...this.filters
        }

        // Add currency conversion if selected
        if (this.selectedDisplayCurrency) {
          params.convert_to_currency = this.selectedDisplayCurrency
          params.conversion_date = new Date().toISOString().split('T')[0]
        }

        if (this.searchQuery) {
          params.search = this.searchQuery
        }
        
        const response = await axios.get('/accounting/vendor-payables', { params })
        
        if (response.data.data) {
          this.payables = response.data.data
          this.pagination = {
            current_page: response.data.current_page || 1,
            per_page: response.data.per_page || 15,
            total: response.data.total || 0,
            from: response.data.from || 0,
            to: response.data.to || 0,
            prev_page_url: response.data.prev_page_url,
            next_page_url: response.data.next_page_url
          }
        } else {
          this.payables = Array.isArray(response.data) ? response.data : []
        }
        
        this.calculateSummary()
        this.$toast?.success('Payables loaded successfully')
      } catch (error) {
        console.error('Error loading payables:', error)
        this.$toast?.error('Failed to load payables')
        this.payables = []
      } finally {
        this.loading = false
      }
    },

    async loadCurrencySummary() {
      try {
        const params = {
          target_currency: this.selectedDisplayCurrency || 'USD'
        }
        const response = await axios.get('/accounting/vendor-payables/currency-summary', { params })
        this.currencySummary = response.data.data || []
      } catch (error) {
        console.error('Error loading currency summary:', error)
        this.currencySummary = []
      }
    },
    
    async loadVendors() {
      try {
        const response = await axios.get('/vendors')
        this.vendors = response.data.data ? response.data.data : (Array.isArray(response.data) ? response.data : [])
        this.$toast?.success('Vendors loaded successfully')
      } catch (error) {
        console.error('Error loading vendors:', error)
        this.$toast?.error('Failed to load vendors')
        this.vendors = []
      }
    },
    
    calculateSummary() {
      this.summary = {
        total_amount: this.payables.reduce((sum, p) => sum + (p.amount || 0), 0),
        pending_amount: this.payables.filter(p => p.status === 'pending').reduce((sum, p) => sum + (p.balance || 0), 0),
        overdue_amount: this.payables.filter(p => p.status === 'overdue').reduce((sum, p) => sum + (p.balance || 0), 0),
        paid_count: this.payables.filter(p => p.status === 'paid').length
      }
    },
    
    applyFilters() {
      clearTimeout(this.filterTimeout)
      this.filterTimeout = setTimeout(() => {
        this.pagination.current_page = 1
        this.loadData()
        this.loadCurrencySummary()
      }, 300)
    },
    
    clearFilters() {
      this.filters = {
        vendor_id: '',
        status: '',
        currency_code: '',
        from_date: '',
        to_date: ''
      }
      this.searchQuery = ''
      this.selectedDisplayCurrency = ''
      this.pagination.current_page = 1
      this.loadData()
      this.loadCurrencySummary()
    },
    
    debounceSearch() {
      clearTimeout(this.searchTimeout)
      this.searchTimeout = setTimeout(() => {
        this.applyFilters()
      }, 500)
    },
    
    sortBy(field) {
      if (this.sortField === field) {
        this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc'
      } else {
        this.sortField = field
        this.sortDirection = 'asc'
      }
      this.loadData()
    },
    
    getSortIcon(field) {
      if (this.sortField !== field) return ''
      return this.sortDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down'
    },
    
    changePage(page) {
      if (page >= 1 && page <= Math.ceil(this.pagination.total / this.pagination.per_page)) {
        this.pagination.current_page = page
        this.loadData()
      }
    },
    
    getVisiblePages() {
      const total = Math.ceil(this.pagination.total / this.pagination.per_page)
      const current = this.pagination.current_page
      const pages = []
      
      const start = Math.max(1, current - 2)
      const end = Math.min(total, current + 2)
      
      for (let i = start; i <= end; i++) {
        pages.push(i)
      }
      
      return pages
    },
    
    refreshData() {
      this.loadData()
      this.loadCurrencySummary()
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
    
    formatCurrency(amount, currency = '') {
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
    
    getDueDateClass(dueDate) {
      if (!dueDate) return ''
      const today = new Date()
      const due = new Date(dueDate)
      const daysDiff = Math.floor((due - today) / (1000 * 60 * 60 * 24))
      
      if (daysDiff < 0) return 'overdue'
      if (daysDiff <= 7) return 'due-soon'
      return 'due-normal'
    },
    
    getStatusText(status) {
      const statusMap = {
        'Open': 'Open',
        'Partial': 'Partially Paid',
        'Paid': 'Paid',
        'Overdue': 'Overdue'
      }
      return statusMap[status] || status
    },

    async exportData() {
      try {
        const params = {
          ...this.filters,
          export: true,
          format: 'excel'
        }
        
        if (this.selectedDisplayCurrency) {
          params.convert_to_currency = this.selectedDisplayCurrency
        }
        
        const response = await axios.get('/accounting/vendor-payables/export', { 
          params,
          responseType: 'blob'
        })
        
        const url = window.URL.createObjectURL(new Blob([response.data]))
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', `vendor-payables-${new Date().toISOString().split('T')[0]}.xlsx`)
        document.body.appendChild(link)
        link.click()
        link.remove()
        
        this.$toast?.success('Export completed successfully')
      } catch (error) {
        console.error('Error exporting data:', error)
        this.$toast?.error('Failed to export data')
      }
    }
  }
}
</script>

<style scoped>
/* Existing styles preserved */
.payables-container {
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

/* Multi-Currency specific styles */
.currency-summary {
  background: white;
  border-radius: 16px;
  padding: 1.5rem;
  margin-bottom: 2rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}

.summary-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.summary-header h3 {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #1f2937;
  font-weight: 600;
}

.currency-select {
  padding: 0.5rem 1rem;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  background: white;
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

.amount-row .label {
  color: #6b7280;
}

.amount-row .amount {
  font-weight: 600;
  color: #1f2937;
}

.amount-row .amount.balance {
  color: #ef4444;
}

.converted-amounts {
  margin-top: 0.5rem;
  padding-top: 0.5rem;
  border-top: 1px solid #e5e7eb;
}

.conversion-info small {
  color: #6b7280;
}

/* Enhanced table styles for multi-currency */
.amount-cell {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.converted-amount small {
  color: #6b7280;
  font-style: italic;
}

.currency-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.currency-badge {
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 600;
  text-align: center;
  max-width: fit-content;
}

.currency-USD { background: #dbeafe; color: #1e40af; }
.currency-EUR { background: #fef3c7; color: #92400e; }
.currency-GBP { background: #ecfdf5; color: #065f46; }
.currency-JPY { background: #fce7f3; color: #9d174d; }
.currency-IDR { background: #f3e8ff; color: #6b21a8; }
.currency-SGD { background: #e0f2fe; color: #0e7490; }

.exchange-rate small {
  color: #6b7280;
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
}

.modal-content {
  background: white;
  border-radius: 16px;
  max-width: 800px;
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

.modal-body {
  padding: 1.5rem;
}

.currency-summary-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 1rem;
}

.currency-summary-card {
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 1rem;
}

.currency-summary-card .currency-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.currency-summary-card h4 {
  color: #1f2937;
  font-weight: 600;
}

.currency-count {
  color: #6b7280;
  font-size: 0.9rem;
}

.currency-details .detail-row {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.5rem;
}

/* Preserve existing styles */
.filters-section {
  background: white;
  border-radius: 16px;
  padding: 1.5rem;
  margin-bottom: 2rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}

.filters-grid {
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

.filter-label {
  font-weight: 500;
  color: #374151;
  font-size: 0.9rem;
}

.search-box {
  position: relative;
  display: flex;
  align-items: center;
}

.search-box i {
  position: absolute;
  left: 12px;
  color: #6b7280;
  z-index: 1;
}

.search-input {
  width: 100%;
  padding: 0.75rem 0.75rem 0.75rem 40px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 0.9rem;
}

.filter-select,
.filter-input {
  padding: 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 0.9rem;
  background: white;
}

.filters-actions {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  padding-top: 1rem;
  border-top: 1px solid #e5e7eb;
}

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

.summary-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
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

.summary-icon.total { background: linear-gradient(135deg, #6366f1, #8b5cf6); }
.summary-icon.pending { background: linear-gradient(135deg, #f59e0b, #d97706); }
.summary-icon.overdue { background: linear-gradient(135deg, #ef4444, #dc2626); }
.summary-icon.paid { background: linear-gradient(135deg, #10b981, #059669); }

.summary-content h3 {
  font-size: 1.5rem;
  font-weight: 700;
  margin-bottom: 0.25rem;
  color: #1f2937;
}

.summary-content p {
  color: #6b7280;
  font-size: 0.9rem;
}

.table-section {
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

.table-container {
  overflow-x: auto;
}

.payables-table {
  width: 100%;
  border-collapse: collapse;
}

.payables-table th {
  background: #f8fafc;
  padding: 1rem;
  text-align: left;
  font-weight: 600;
  color: #374151;
  border-bottom: 2px solid #e5e7eb;
}

.payables-table th.sortable {
  cursor: pointer;
  user-select: none;
}

.payables-table th.sortable:hover {
  background: #f1f5f9;
}

.payables-table td {
  padding: 1rem;
  border-bottom: 1px solid #e5e7eb;
}

.payable-row:hover {
  background: #f8fafc;
}

.id-link {
  color: #6366f1;
  font-weight: 600;
  text-decoration: none;
}

.id-link:hover {
  text-decoration: underline;
}

.vendor-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.vendor-name {
  font-weight: 500;
}

.vendor-code {
  color: #6b7280;
  font-size: 0.8rem;
}

.due-date {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.due-date.overdue {
  color: #ef4444;
}

.due-date.due-soon {
  color: #f59e0b;
}

.due-date.due-normal {
  color: #10b981;
}

.status-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.8rem;
  font-weight: 500;
}

.status-Open { background: #dbeafe; color: #1e40af; }
.status-Partial { background: #fef3c7; color: #92400e; }
.status-Paid { background: #d1fae5; color: #065f46; }
.status-Overdue { background: #fee2e2; color: #dc2626; }

.action-buttons {
  display: flex;
  gap: 0.5rem;
}

.text-danger {
  color: #ef4444;
}

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

.pagination-section {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 2rem;
  padding-top: 2rem;
  border-top: 1px solid #e5e7eb;
}

.pagination-info {
  color: #6b7280;
}

.pagination-buttons {
  display: flex;
  gap: 0.5rem;
  align-items: center;
}

.page-numbers {
  display: flex;
  gap: 0.25rem;
}

.page-numbers .btn.active {
  background: #6366f1;
  color: white;
}

.fa-sort, .fa-sort-up, .fa-sort-down {
  margin-left: 0.5rem;
  opacity: 0.6;
}

.sortable .fa-sort-up, .sortable .fa-sort-down {
  opacity: 1;
  color: #6366f1;
}
</style>