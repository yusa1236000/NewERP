<template>
  <div class="customer-transactions">
    <!-- Header Section -->
    <div class="page-header">
      <div class="header-content">
        <div class="header-left">
          <h1 class="page-title">
            <i class="fas fa-exchange-alt"></i>
            Customer Transactions
          </h1>
          <p class="page-subtitle">View all customer transactions including invoices, receivables, and payments</p>
        </div>
        <div class="header-actions">
          <router-link to="/accounting/receivables" class="btn btn-ghost">
            <i class="fas fa-arrow-left"></i>
            Back to Receivables
          </router-link>
          <button @click="exportTransactions" class="btn btn-outline">
            <i class="fas fa-download"></i>
            Export
          </button>
        </div>
      </div>
    </div>

    <!-- Filters Section -->
    <div class="filters-section">
      <div class="filters-grid">
        <div class="filter-group">
          <label>Customer</label>
          <select v-model="filters.customer_id" @change="applyFilters" required>
            <option value="">Select Customer</option>
            <option v-for="customer in customers" :key="customer.customer_id" :value="customer.customer_id">
              {{ customer.name }}
            </option>
          </select>
        </div>
        
        <div class="filter-group">
          <label>Currency Filter</label>
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
              Convert to {{ currency }}
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
        </div>
      </div>
    </div>

    <!-- Customer Information Card -->
    <div v-if="selectedCustomer" class="customer-card">
      <div class="customer-header">
        <div class="customer-info">
          <div class="customer-avatar">
            <i class="fas fa-user-circle"></i>
          </div>
          <div class="customer-details">
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
          <h4>Currency Conversion Applied</h4>
          <p>All amounts are converted to {{ filters.display_currency }}</p>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="loading-state">
      <div class="loading-spinner"></div>
      <p>Loading transactions...</p>
    </div>

    <!-- Empty State -->
    <div v-else-if="!filters.customer_id" class="empty-state">
      <i class="fas fa-user-plus"></i>
      <h3>Select a Customer</h3>
      <p>Please select a customer to view their transactions</p>
    </div>

    <div v-else-if="transactions.length === 0" class="empty-state">
      <i class="fas fa-file-invoice"></i>
      <h3>No Transactions Found</h3>
      <p>No transactions found for the selected filters</p>
      <button @click="clearFilters" class="btn btn-primary">Clear Filters</button>
    </div>

    <!-- Transactions List -->
    <div v-else class="transactions-content">
      <!-- Transaction Summary -->
      <div class="summary-cards">
        <div class="summary-card receivables">
          <div class="card-icon">
            <i class="fas fa-file-invoice"></i>
          </div>
          <div class="card-content">
            <h3>{{ transactionSummary.receivables.count }}</h3>
            <p>Receivables</p>
            <span class="amount">{{ formatCurrency(transactionSummary.receivables.amount) }}</span>
          </div>
        </div>
        
        <div class="summary-card invoices">
          <div class="card-icon">
            <i class="fas fa-receipt"></i>
          </div>
          <div class="card-content">
            <h3>{{ transactionSummary.invoices.count }}</h3>
            <p>Invoices</p>
            <span class="amount">{{ formatCurrency(transactionSummary.invoices.amount) }}</span>
          </div>
        </div>
        
        <div class="summary-card payments">
          <div class="card-icon">
            <i class="fas fa-money-bill"></i>
          </div>
          <div class="card-content">
            <h3>{{ transactionSummary.payments.count }}</h3>
            <p>Payments</p>
            <span class="amount">{{ formatCurrency(transactionSummary.payments.amount) }}</span>
          </div>
        </div>
      </div>

      <!-- Transactions Timeline -->
      <div class="transactions-timeline">
        <div class="timeline-header">
          <h3>
            <i class="fas fa-history"></i>
            Transaction Timeline
          </h3>
          <div class="timeline-controls">
            <button @click="sortOrder = sortOrder === 'desc' ? 'asc' : 'desc'" class="btn btn-sm btn-outline">
              <i :class="sortOrder === 'desc' ? 'fas fa-sort-amount-down' : 'fas fa-sort-amount-up'"></i>
              {{ sortOrder === 'desc' ? 'Newest First' : 'Oldest First' }}
            </button>
          </div>
        </div>

        <div class="timeline">
          <div 
            v-for="transaction in sortedTransactions" 
            :key="`${transaction.type}-${transaction.id}`"
            class="timeline-item"
            :class="getTransactionClass(transaction)"
          >
            <div class="timeline-marker">
              <i :class="getTransactionIcon(transaction)"></i>
            </div>
            
            <div class="timeline-content">
              <div class="transaction-header">
                <div class="transaction-info">
                  <h4>{{ getTransactionTitle(transaction) }}</h4>
                  <p class="transaction-subtitle">{{ getTransactionSubtitle(transaction) }}</p>
                </div>
                <div class="transaction-meta">
                  <span class="transaction-date">{{ formatDate(getTransactionDate(transaction)) }}</span>
                  <span class="transaction-amount" :class="getAmountClass(transaction)">
                    {{ formatTransactionAmount(transaction) }}
                  </span>
                </div>
              </div>
              
              <div class="transaction-details">
                <div class="detail-grid">
                  <div v-if="transaction.type === 'receivable'" class="detail-item">
                    <span class="label">Due Date:</span>
                    <span class="value" :class="{ overdue: isOverdue(transaction.due_date) }">
                      {{ formatDate(transaction.due_date) }}
                    </span>
                  </div>
                  
                  <div v-if="transaction.type === 'receivable'" class="detail-item">
                    <span class="label">Status:</span>
                    <span class="status-badge" :class="transaction.status?.toLowerCase()">
                      {{ transaction.status }}
                    </span>
                  </div>
                  
                  <div v-if="transaction.type === 'payment'" class="detail-item">
                    <span class="label">Method:</span>
                    <span class="value">{{ transaction.payment_method }}</span>
                  </div>
                  
                  <div v-if="transaction.reference_number || transaction.invoice_number" class="detail-item">
                    <span class="label">Reference:</span>
                    <span class="value">{{ transaction.reference_number || transaction.invoice_number }}</span>
                  </div>

                  <div v-if="filters.display_currency && transaction.currency_code !== filters.display_currency" class="detail-item">
                    <span class="label">Original Currency:</span>
                    <span class="value">{{ transaction.currency_code }}</span>
                  </div>
                </div>
                
                <div v-if="transaction.type === 'receivable'" class="balance-info">
                  <div class="balance-item">
                    <span class="label">Paid:</span>
                    <span class="value">{{ formatCurrency(transaction.paid_amount) }}</span>
                  </div>
                  <div class="balance-item">
                    <span class="label">Balance:</span>
                    <span class="value balance">{{ formatCurrency(transaction.balance) }}</span>
                  </div>
                </div>
              </div>
              
              <div class="transaction-actions">
                <button 
                  v-if="transaction.type === 'receivable'"
                  @click="viewReceivable(transaction.id)"
                  class="action-btn"
                >
                  <i class="fas fa-eye"></i>
                  View
                </button>
                
                <button 
                  v-if="transaction.type === 'invoice'"
                  @click="viewInvoice(transaction.id)"
                  class="action-btn"
                >
                  <i class="fas fa-eye"></i>
                  View
                </button>
                
                <button 
                  v-if="transaction.type === 'payment'"
                  @click="viewPayment(transaction.id)"
                  class="action-btn"
                >
                  <i class="fas fa-eye"></i>
                  View
                </button>
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
  name: 'CustomerTransactions',
  data() {
    return {
      transactions: [],
      customers: [],
      selectedCustomer: null,
      availableCurrencies: ['USD', 'EUR', 'GBP', 'JPY', 'CAD', 'AUD'],
      loading: false,
      sortOrder: 'desc',
      filters: {
        customer_id: '',
        currency_code: '',
        display_currency: '',
        from_date: '',
        to_date: ''
      },
      transactionSummary: {
        receivables: { count: 0, amount: 0 },
        invoices: { count: 0, amount: 0 },
        payments: { count: 0, amount: 0 }
      }
    }
  },
  
  computed: {
    sortedTransactions() {
      return [...this.transactions].sort((a, b) => {
        const dateA = new Date(this.getTransactionDate(a))
        const dateB = new Date(this.getTransactionDate(b))
        return this.sortOrder === 'desc' ? dateB - dateA : dateA - dateB
      })
    }
  },
  
  async mounted() {
    await this.loadCustomers()
    
    // Check for customer_id in query params
    if (this.$route.query.customer_id) {
      this.filters.customer_id = this.$route.query.customer_id
      this.filters.display_currency = this.$route.query.display_currency || ''
      await this.applyFilters()
    }
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
    
    async loadTransactions() {
      if (!this.filters.customer_id) {
        this.transactions = []
        return
      }
      
      this.loading = true
      try {
        const params = { ...this.filters }
        
        // Remove empty filters
        Object.keys(params).forEach(key => {
          if (params[key] === '' || params[key] === null) {
            delete params[key]
          }
        })
        
        const response = await axios.get('/accounting/customer-transactions', { params })
        this.transactions = response.data.data || []
        
        // Load selected customer details
        if (this.filters.customer_id) {
          this.selectedCustomer = this.customers.find(c => c.customer_id == this.filters.customer_id)
        }
        
        this.calculateSummary()
      } catch (error) {
        console.error('Error loading transactions:', error)
        this.$toast?.error('Failed to load transactions')
      } finally {
        this.loading = false
      }
    },
    
    calculateSummary() {
      this.transactionSummary = {
        receivables: { count: 0, amount: 0 },
        invoices: { count: 0, amount: 0 },
        payments: { count: 0, amount: 0 }
      }
      
      this.transactions.forEach(transaction => {
        if (transaction.type === 'receivable') {
          this.transactionSummary.receivables.count++
          this.transactionSummary.receivables.amount += parseFloat(transaction.amount) || 0
        } else if (transaction.type === 'invoice') {
          this.transactionSummary.invoices.count++
          this.transactionSummary.invoices.amount += parseFloat(transaction.total_amount) || 0
        } else if (transaction.type === 'payment') {
          this.transactionSummary.payments.count++
          this.transactionSummary.payments.amount += parseFloat(transaction.amount) || 0
        }
      })
    },
    
    applyFilters() {
      this.loadTransactions()
    },
    
    clearFilters() {
      this.filters = {
        customer_id: '',
        currency_code: '',
        display_currency: '',
        from_date: '',
        to_date: ''
      }
      this.selectedCustomer = null
      this.transactions = []
    },
    
    getTransactionClass(transaction) {
      return `transaction-${transaction.type}`
    },
    
    getTransactionIcon(transaction) {
      switch (transaction.type) {
        case 'receivable': return 'fas fa-file-invoice'
        case 'invoice': return 'fas fa-receipt'
        case 'payment': return 'fas fa-money-bill'
        default: return 'fas fa-file'
      }
    },
    
    getTransactionTitle(transaction) {
      switch (transaction.type) {
        case 'receivable': return 'Customer Receivable'
        case 'invoice': return 'Sales Invoice'
        case 'payment': return 'Payment Received'
        default: return 'Transaction'
      }
    },
    
    getTransactionSubtitle(transaction) {
      switch (transaction.type) {
        case 'receivable': 
          return `Invoice #${transaction.invoice_number || transaction.invoice_id}`
        case 'invoice': 
          return `Invoice #${transaction.invoice_number}`
        case 'payment': 
          return `Payment for Receivable #${transaction.receivable_id}`
        default: 
          return ''
      }
    },
    
    getTransactionDate(transaction) {
      switch (transaction.type) {
        case 'receivable': return transaction.due_date
        case 'invoice': return transaction.invoice_date
        case 'payment': return transaction.payment_date
        default: return transaction.created_at
      }
    },
    
    getAmountClass(transaction) {
      switch (transaction.type) {
        case 'payment': return 'amount-positive'
        case 'receivable': return transaction.balance > 0 ? 'amount-negative' : 'amount-neutral'
        case 'invoice': return 'amount-negative'
        default: return 'amount-neutral'
      }
    },
    
    formatTransactionAmount(transaction) {
      let amount, prefix = ''
      
      switch (transaction.type) {
        case 'receivable':
          amount = transaction.amount
          prefix = ''
          break
        case 'invoice':
          amount = transaction.total_amount
          prefix = ''
          break
        case 'payment':
          amount = transaction.amount
          prefix = '+'
          break
        default:
          amount = 0
      }
      
      return prefix + this.formatCurrency(amount)
    },
    
    isOverdue(dueDate) {
      return new Date(dueDate) < new Date()
    },
    
    viewReceivable(id) {
      this.$router.push(`/accounting/receivables/${id}`)
    },
    
    viewInvoice(id) {
      this.$router.push(`/sales/invoices/${id}`)
    },
    
    viewPayment(id) {
      this.$router.push(`/accounting/payments/${id}`)
    },
    
    exportTransactions() {
      // Implementation for export functionality
      console.log('Export functionality to be implemented')
    },
    
    formatDate(date) {
      return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      })
    },
    
    formatCurrency(amount) {
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
.customer-transactions {
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

/* Customer Card */
.customer-card {
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

.customer-info {
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

.customer-details h3 {
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

/* Loading and Empty States */
.loading-state, .empty-state {
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

.summary-card.receivables .card-icon {
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

.summary-card.invoices .card-icon {
  background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
}

.summary-card.payments .card-icon {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

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

.card-content .amount {
  font-weight: 600;
  color: var(--gray-700);
  font-size: 0.875rem;
}

/* Timeline */
.transactions-timeline {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.timeline-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid var(--gray-200);
}

.timeline-header h3 {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-size: 1.125rem;
  font-weight: 600;
  color: var(--gray-800);
}

.timeline-header h3 i {
  color: var(--primary-color);
}

.timeline {
  position: relative;
}

.timeline::before {
  content: '';
  position: absolute;
  left: 30px;
  top: 0;
  bottom: 0;
  width: 2px;
  background: var(--gray-200);
}

.timeline-item {
  position: relative;
  display: flex;
  margin-bottom: 2rem;
  padding-left: 4rem;
}

.timeline-marker {
  position: absolute;
  left: 0;
  top: 0.5rem;
  width: 60px;
  height: 60px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  color: white;
  z-index: 1;
}

.transaction-receivable .timeline-marker {
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

.transaction-invoice .timeline-marker {
  background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
}

.transaction-payment .timeline-marker {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.timeline-content {
  flex: 1;
  background: var(--gray-50);
  border-radius: 12px;
  padding: 1.5rem;
  border-left: 4px solid var(--gray-300);
}

.transaction-receivable .timeline-content {
  border-left-color: #f59e0b;
}

.transaction-invoice .timeline-content {
  border-left-color: #3b82f6;
}

.transaction-payment .timeline-content {
  border-left-color: #10b981;
}

.transaction-header {
  display: flex;
  justify-content: space-between;
  align-items: start;
  margin-bottom: 1rem;
}

.transaction-info h4 {
  color: var(--gray-800);
  margin-bottom: 0.25rem;
}

.transaction-subtitle {
  color: var(--gray-600);
  font-size: 0.875rem;
}

.transaction-meta {
  text-align: right;
}

.transaction-date {
  display: block;
  color: var(--gray-600);
  font-size: 0.875rem;
  margin-bottom: 0.25rem;
}

.transaction-amount {
  font-size: 1.125rem;
  font-weight: 600;
}

.amount-positive {
  color: var(--success-color);
}

.amount-negative {
  color: var(--danger-color);
}

.amount-neutral {
  color: var(--gray-700);
}

.transaction-details {
  margin-bottom: 1rem;
}

.detail-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 0.5rem;
  margin-bottom: 1rem;
}

.detail-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.875rem;
}

.detail-item .label {
  color: var(--gray-600);
  font-weight: 500;
}

.detail-item .value {
  color: var(--gray-800);
}

.detail-item .value.overdue {
  color: var(--danger-color);
}

.status-badge {
  padding: 0.125rem 0.5rem;
  border-radius: 12px;
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

.balance-info {
  display: flex;
  gap: 1rem;
  padding: 0.75rem;
  background: white;
  border-radius: 8px;
  border: 1px solid var(--gray-200);
}

.balance-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.balance-item .label {
  font-size: 0.75rem;
  color: var(--gray-600);
  font-weight: 500;
}

.balance-item .value {
  font-weight: 600;
  color: var(--gray-800);
}

.balance-item .value.balance {
  color: var(--warning-color);
}

.transaction-actions {
  display: flex;
  gap: 0.5rem;
  justify-content: flex-end;
}

.action-btn {
  padding: 0.25rem 0.75rem;
  border: 1px solid var(--gray-300);
  background: white;
  color: var(--gray-600);
  border-radius: 6px;
  font-size: 0.75rem;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.action-btn:hover {
  background: var(--primary-color);
  color: white;
  border-color: var(--primary-color);
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
</style>