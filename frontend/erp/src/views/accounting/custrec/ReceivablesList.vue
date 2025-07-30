<template>
  <div class="receivables-list">
    <!-- Header Section -->
    <div class="page-header">
      <div class="header-content">
        <div class="header-left">
          <h1 class="page-title">
            <i class="fas fa-receipt"></i>
            Customer Receivables
          </h1>
          <p class="page-subtitle">Manage and track customer outstanding payments</p>
        </div>
        <div class="header-actions">
          <router-link to="/accounting/receivables/create" class="btn btn-primary">
            <i class="fas fa-plus"></i>
            Create Receivable
          </router-link>
          <button @click="toggleAgingReport" class="btn btn-outline">
            <i class="fas fa-chart-bar"></i>
            Aging Report
          </button>
          <button @click="showCurrencySummary" class="btn btn-outline">
            <i class="fas fa-coins"></i>
            Currency Summary
          </button>
        </div>
      </div>
    </div>

    <!-- Filters Section -->
    <div class="filters-section">
      <div class="filters-grid">
        <div class="filter-group">
          <label>Customer</label>
          <select v-model="filters.customer_id" @change="applyFilters">
            <option value="">All Customers</option>
            <option v-for="customer in customers" :key="customer.customer_id" :value="customer.customer_id">
              {{ customer.name }}
            </option>
          </select>
        </div>
        
        <div class="filter-group">
          <label>Status</label>
          <select v-model="filters.status" @change="applyFilters">
            <option value="">All Status</option>
            <option value="Outstanding">Outstanding</option>
            <option value="Overdue">Overdue</option>
            <option value="Paid">Paid</option>
            <option value="Partial">Partial</option>
          </select>
        </div>

        <div class="filter-group">
          <label>Currency</label>
          <select v-model="filters.currency_code" @change="applyFilters">
            <option value="">All Currencies</option>
            <option v-for="currency in availableCurrencies" :key="currency" :value="currency">
              {{ currency }}
            </option>
          </select>
        </div>

        <div class="filter-group">
          <label>Display Currency</label>
          <select v-model="filters.display_currency" @change="applyFilters">
            <option value="">Original Currency</option>
            <option v-for="currency in availableCurrencies" :key="currency" :value="currency">
              {{ currency }}
            </option>
          </select>
        </div>
        
        <div class="filter-group">
          <label>From Date</label>
          <input type="date" v-model="filters.from_date" @change="applyFilters">
        </div>
        
        <div class="filter-group">
          <label>To Date</label>
          <input type="date" v-model="filters.to_date" @change="applyFilters">
        </div>
        
        <div class="filter-actions">
          <button @click="clearFilters" class="btn btn-ghost">
            <i class="fas fa-times"></i>
            Clear Filters
          </button>
          <button @click="exportData" class="btn btn-outline">
            <i class="fas fa-download"></i>
            Export
          </button>
        </div>
      </div>
    </div>

    <!-- Summary Cards -->
    <div class="summary-cards">
      <div class="summary-card">
        <div class="card-icon">
          <i class="fas fa-dollar-sign"></i>
        </div>
        <div class="card-content">
          <h3>{{ formatCurrency(summary.totalAmount) }}</h3>
          <p>Total Amount</p>
        </div>
      </div>
      
      <div class="summary-card">
        <div class="card-icon">
          <i class="fas fa-clock"></i>
        </div>
        <div class="card-content">
          <h3>{{ formatCurrency(summary.outstandingAmount) }}</h3>
          <p>Outstanding</p>
        </div>
      </div>
      
      <div class="summary-card">
        <div class="card-icon">
          <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div class="card-content">
          <h3>{{ formatCurrency(summary.overdueAmount) }}</h3>
          <p>Overdue</p>
        </div>
      </div>
      
      <div class="summary-card">
        <div class="card-icon">
          <i class="fas fa-check-circle"></i>
        </div>
        <div class="card-content">
          <h3>{{ formatCurrency(summary.paidAmount) }}</h3>
          <p>Paid</p>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="loading-state">
      <div class="loading-spinner"></div>
      <p>Loading receivables...</p>
    </div>

    <!-- Empty State -->
    <div v-else-if="receivables.length === 0" class="empty-state">
      <i class="fas fa-receipt"></i>
      <h3>No receivables found</h3>
      <p>No receivables match your current filters</p>
      <button @click="clearFilters" class="btn btn-primary">Clear Filters</button>
    </div>
    
    <div v-else class="receivables-cards">
      <div 
        v-for="receivable in receivables" 
        :key="receivable.receivable_id"
        class="receivable-card"
        :class="getStatusClass(receivable.status)"
        @click="viewReceivable(receivable.receivable_id)"
      >
        <div class="card-header">
          <div class="customer-info">
            <h3>{{ receivable.customer?.name || 'Unknown Customer' }}</h3>
            <p class="invoice-ref">Invoice #{{ receivable.sales_invoice?.invoice_number || receivable.invoice_id }}</p>
          </div>
          <div class="status-badge" :class="receivable.status.toLowerCase()">
            {{ receivable.status }}
          </div>
        </div>
        
        <div class="card-body">
          <div class="amount-section">
            <div class="amount-item">
              <span class="label">Total Amount</span>
              <span class="value">
                {{ formatCurrencyWithCode(receivable.display_amount || receivable.amount, receivable.display_currency || receivable.currency_code) }}
              </span>
            </div>
            <div class="amount-item">
              <span class="label">Paid Amount</span>
              <span class="value paid">
                {{ formatCurrencyWithCode(receivable.display_paid_amount || receivable.paid_amount, receivable.display_currency || receivable.currency_code) }}
              </span>
            </div>
            <div class="amount-item">
              <span class="label">Balance</span>
              <span class="value balance">
                {{ formatCurrencyWithCode(receivable.display_balance || receivable.balance, receivable.display_currency || receivable.currency_code) }}
              </span>
            </div>
          </div>
          
          <div class="date-section">
            <div class="date-item">
              <i class="fas fa-calendar"></i>
              <span>Due: {{ formatDate(receivable.due_date) }}</span>
            </div>
            <div class="date-item" :class="{ overdue: isOverdue(receivable.due_date) }">
              <i class="fas fa-clock"></i>
              <span>{{ getDaysFromDue(receivable.due_date) }}</span>
            </div>
            <div v-if="receivable.display_currency" class="date-item currency-note">
              <i class="fas fa-exchange-alt"></i>
              <span>Converted from {{ receivable.currency_code }}</span>
            </div>
          </div>
        </div>
        
        <div class="card-actions" @click.stop>
          <button @click="editReceivable(receivable.receivable_id)" class="action-btn">
            <i class="fas fa-edit"></i>
          </button>
          <button @click="addPayment(receivable.receivable_id)" class="action-btn">
            <i class="fas fa-money-bill"></i>
          </button>
          <button @click="printStatement(receivable.receivable_id)" class="action-btn">
            <i class="fas fa-file-alt"></i>
          </button>
          <button @click="viewCustomerTransactions(receivable.customer_id)" class="action-btn">
            <i class="fas fa-list"></i>
          </button>
          <div class="action-dropdown">
            <button class="action-btn dropdown-toggle">
              <i class="fas fa-ellipsis-v"></i>
            </button>
            <div class="dropdown-menu">
              <button @click="deleteReceivable(receivable.receivable_id)">
                <i class="fas fa-trash"></i>
                Delete
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="pagination.total > 0" class="pagination-wrapper">
      <div class="pagination-info">
        Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} results
      </div>
      <div class="pagination-controls">
        <button 
          @click="changePage(1)" 
          :disabled="pagination.current_page === 1"
          class="btn btn-sm btn-ghost"
        >
          <i class="fas fa-angle-double-left"></i>
        </button>
        <button 
          @click="changePage(pagination.current_page - 1)" 
          :disabled="pagination.current_page === 1"
          class="btn btn-sm btn-ghost"
        >
          <i class="fas fa-angle-left"></i>
        </button>
        
        <span class="page-info">
          Page {{ pagination.current_page }} of {{ pagination.last_page }}
        </span>
        
        <button 
          @click="changePage(pagination.current_page + 1)" 
          :disabled="pagination.current_page === pagination.last_page"
          class="btn btn-sm btn-ghost"
        >
          <i class="fas fa-angle-right"></i>
        </button>
        <button 
          @click="changePage(pagination.last_page)" 
          :disabled="pagination.current_page === pagination.last_page"
          class="btn btn-sm btn-ghost"
        >
          <i class="fas fa-angle-double-right"></i>
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'ReceivablesList',
  data() {
    return {
      receivables: [],
      customers: [],
      availableCurrencies: ['USD', 'EUR', 'GBP', 'JPY', 'CAD', 'AUD'],
      loading: false,
      filters: {
        customer_id: '',
        status: '',
        currency_code: '',
        display_currency: '',
        from_date: '',
        to_date: ''
      },
      summary: {
        totalAmount: 0,
        outstandingAmount: 0,
        overdueAmount: 0,
        paidAmount: 0
      },
      pagination: {
        current_page: 1,
        last_page: 1,
        from: 0,
        to: 0,
        total: 0
      }
    }
  },
  
  async mounted() {
    await this.loadCustomers()
    await this.loadAvailableCurrencies()
    await this.loadReceivables()
  },
  
  methods: {
    async loadReceivables(page = 1) {
      this.loading = true
      try {
        const params = {
          page,
          per_page: 15,
          ...this.filters
        }
        
        // Remove empty filters
        Object.keys(params).forEach(key => {
          if (params[key] === '' || params[key] === null) {
            delete params[key]
          }
        })
        
        const response = await axios.get('/accounting/customer-receivables', { params })
        this.receivables = response.data.data || []
        this.pagination = {
          current_page: response.data.current_page || 1,
          last_page: response.data.last_page || 1,
          from: response.data.from || 0,
          to: response.data.to || 0,
          total: response.data.total || 0
        }
        
        await this.loadSummary()
      } catch (error) {
        console.error('Error loading receivables:', error)
        this.$toast?.error('Failed to load receivables')
      } finally {
        this.loading = false
      }
    },
    
    async loadCustomers() {
      try {
        const response = await axios.get('/customers')
        this.customers = response.data.data || response.data
      } catch (error) {
        console.error('Error loading customers:', error)
      }
    },

    async loadAvailableCurrencies() {
      try {
        const response = await axios.get('/accounting/receivables/currency-summary')
        const currencies = response.data.data?.map(item => item.currency_code) || []
        this.availableCurrencies = ['USD', 'EUR', 'GBP', 'JPY', 'CAD', 'AUD', ...currencies].filter((v, i, a) => a.indexOf(v) === i)
      } catch (error) {
        console.error('Error loading available currencies:', error)
      }
    },
    
    async loadSummary() {
      try {
        // Calculate summary from current receivables
        this.summary = this.receivables.reduce((acc, receivable) => {
          const amount = receivable.display_amount || receivable.amount
          const balance = receivable.display_balance || receivable.balance
          const paidAmount = receivable.display_paid_amount || receivable.paid_amount
          
          acc.totalAmount += parseFloat(amount) || 0
          acc.outstandingAmount += parseFloat(balance) || 0
          
          if (this.isOverdue(receivable.due_date)) {
            acc.overdueAmount += parseFloat(balance) || 0
          }
          
          if (receivable.status === 'Paid') {
            acc.paidAmount += parseFloat(paidAmount) || 0
          }
          
          return acc
        }, {
          totalAmount: 0,
          outstandingAmount: 0,
          overdueAmount: 0,
          paidAmount: 0
        })
      } catch (error) {
        console.error('Error calculating summary:', error)
      }
    },
    
    applyFilters() {
      this.loadReceivables(1)
    },
    
    clearFilters() {
      this.filters = {
        customer_id: '',
        status: '',
        currency_code: '',
        display_currency: '',
        from_date: '',
        to_date: ''
      }
      this.loadReceivables(1)
    },
    
    changePage(page) {
      if (page >= 1 && page <= this.pagination.last_page) {
        this.loadReceivables(page)
      }
    },
    
    viewReceivable(id) {
      this.$router.push(`/accounting/receivables/${id}`)
    },
    
    editReceivable(id) {
      this.$router.push(`/accounting/receivables/${id}/edit`)
    },
    
    addPayment(id) {
      this.$router.push(`/accounting/receivables/${id}/payment`)
    },
    
    printStatement(id) {
      this.$router.push(`/accounting/receivables/${id}/statement`)
    },

    viewCustomerTransactions(customerId) {
      this.$router.push(`/accounting/customer-transactions?customer_id=${customerId}`)
    },
    
    async deleteReceivable(id) {
      if (!confirm('Are you sure you want to delete this receivable?'))
        return
      
      try {
        await axios.delete(`/accounting/customer-receivables/${id}`)
        this.$toast?.success('Receivable deleted successfully')
        this.loadReceivables()
      } catch (error) {
        console.error('Error deleting receivable:', error)
        this.$toast?.error('Failed to delete receivable')
      }
    },
    
    toggleAgingReport() {
      this.$router.push('/accounting/receivables/aging')
    },

    showCurrencySummary() {
      this.$router.push('/accounting/receivables/currency-summary')
    },
    
    exportData() {
      // Implementation for export functionality
      console.log('Export functionality to be implemented')
    },
    
    getStatusClass(status) {
      return `status-${status.toLowerCase()}`
    },
    
    isOverdue(dueDate) {
      return new Date(dueDate) < new Date()
    },
    
    getDaysFromDue(dueDate) {
      const today = new Date()
      const due = new Date(dueDate)
      const diffTime = due - today
      const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
      
      if (diffDays > 0) {
        return `${diffDays} days remaining`
      } else if (diffDays === 0) {
        return 'Due today'
      } else {
        return `${Math.abs(diffDays)} days overdue`
      }
    },
    
    formatDate(date) {
      return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      })
    },
    
    formatCurrency(amount) {
      return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
      }).format(amount || 0)
    },

    formatCurrencyWithCode(amount, currencyCode) {
      return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: currencyCode || 'USD'
      }).format(amount || 0)
    }
  }
}
</script>

<style scoped>
.receivables-list {
  max-width: 1400px;
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

/* Filters Section */
.filters-section {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  margin-bottom: 2rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.filters-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  align-items: end;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.filter-group label {
  font-weight: 500;
  color: var(--gray-700);
  font-size: 0.875rem;
}

.filter-group select,
.filter-group input {
  padding: 0.5rem;
  border: 1px solid var(--gray-300);
  border-radius: 6px;
  font-size: 0.875rem;
}

.filter-actions {
  display: flex;
  gap: 0.5rem;
}

/* Summary Cards */
.summary-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
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
}

.card-icon {
  width: 60px;
  height: 60px;
  border-radius: 12px;
  background: var(--primary-color);
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

/* Receivables Cards */
.receivables-cards {
  display: grid;
  gap: 1.5rem;
}

.receivable-card {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  cursor: pointer;
  transition: all 0.2s;
  border-left: 4px solid var(--gray-300);
}

.receivable-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
}

.receivable-card.status-outstanding {
  border-left-color: var(--warning-color);
}

.receivable-card.status-overdue {
  border-left-color: var(--danger-color);
}

.receivable-card.status-paid {
  border-left-color: var(--success-color);
}

.receivable-card.status-partial {
  border-left-color: var(--primary-color);
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: start;
  margin-bottom: 1rem;
}

.customer-info h3 {
  font-size: 1.125rem;
  margin-bottom: 0.25rem;
}

.invoice-ref {
  color: var(--gray-600);
  font-size: 0.875rem;
}

.status-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 500;
  text-transform: uppercase;
}

.status-badge.outstanding {
  background: #fef3c7;
  color: #92400e;
}

.status-badge.overdue {
  background: #fee2e2;
  color: #dc2626;
}

.status-badge.paid {
  background: #dcfce7;
  color: #15803d;
}

.status-badge.partial {
  background: #dbeafe;
  color: #1d4ed8;
}

.card-body {
  margin-bottom: 1rem;
}

.amount-section {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1rem;
  margin-bottom: 1rem;
}

.amount-item {
  text-align: center;
}

.amount-item .label {
  font-size: 0.75rem;
  color: var(--gray-600);
  margin-bottom: 0.25rem;
  display: block;
}

.amount-item .value {
  font-weight: 600;
  font-size: 0.875rem;
}

.date-section {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.date-item {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.75rem;
  color: var(--gray-600);
}

.date-item.overdue {
  color: var(--danger-color);
}

.date-item.currency-note {
  color: var(--primary-color);
  font-style: italic;
}

.card-actions {
  display: flex;
  gap: 0.5rem;
  justify-content: flex-end;
}

.action-btn {
  width: 32px;
  height: 32px;
  border-radius: 6px;
  border: 1px solid var(--gray-300);
  background: white;
  color: var(--gray-600);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
}

.action-btn:hover {
  background: var(--primary-color);
  color: white;
  border-color: var(--primary-color);
}

.action-dropdown {
  position: relative;
}

.dropdown-menu {
  position: absolute;
  top: 100%;
  right: 0;
  background: white;
  border: 1px solid var(--gray-300);
  border-radius: 6px;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
  z-index: 100;
  min-width: 120px;
  display: none;
}

.action-dropdown:hover .dropdown-menu {
  display: block;
}

.dropdown-menu button {
  width: 100%;
  padding: 0.5rem;
  text-align: left;
  border: none;
  background: none;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
}

.dropdown-menu button:hover {
  background: var(--gray-100);
}

/* Pagination */
.pagination-wrapper {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 2rem;
  padding: 1rem;
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.pagination-info {
  color: var(--gray-600);
  font-size: 0.875rem;
}

.pagination-controls {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.page-info {
  margin: 0 1rem;
  font-size: 0.875rem;
  color: var(--gray-700);
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

.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
</style>