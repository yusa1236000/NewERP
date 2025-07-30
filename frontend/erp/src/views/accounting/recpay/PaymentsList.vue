<template>
  <div class="payments-list-container">
    <!-- Header Section -->
    <div class="page-header">
      <div class="header-content">
        <div class="title-section">
          <h1 class="page-title">
            <i class="fas fa-credit-card"></i>
            Receivable Payments
          </h1>
          <p class="page-subtitle">Manage and track customer payments across multiple currencies</p>
        </div>
        <div class="header-actions">
          <button @click="getCurrencySummary" class="btn btn-outline">
            <i class="fas fa-chart-pie"></i>
            Currency Summary
          </button>
          <router-link to="/accounting/receivable-payments/create" class="btn btn-primary">
            <i class="fas fa-plus"></i>
            Record Payment
          </router-link>
        </div>
      </div>
    </div>

    <!-- Filters Section -->
    <div class="filters-card">
      <div class="filters-header" @click="showFilters = !showFilters">
        <h3 class="filters-title">
          <i class="fas fa-filter"></i>
          Filters
        </h3>
        <i class="fas" :class="showFilters ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
      </div>
      <transition name="slide">
        <div v-show="showFilters" class="filters-content">
          <div class="filter-row">
            <div class="filter-group">
              <label class="filter-label">Date Range</label>
              <div class="date-range-inputs">
                <input
                  type="date"
                  v-model="filters.fromDate"
                  class="date-input"
                  placeholder="From Date"
                />
                <span class="date-separator">to</span>
                <input
                  type="date"
                  v-model="filters.toDate"
                  class="date-input"
                  placeholder="To Date"
                />
              </div>
            </div>
            
            <div class="filter-group">
              <label class="filter-label">Customer</label>
              <select v-model="filters.customerId" class="filter-select">
                <option value="">All Customers</option>
                <option v-for="customer in customers" :key="customer.customer_id" :value="customer.customer_id">
                  {{ customer.name }} ({{ customer.customer_code }})
                </option>
              </select>
            </div>
            
            <div class="filter-group">
              <label class="filter-label">Payment Currency</label>
              <select v-model="filters.paymentCurrency" class="filter-select">
                <option value="">All Currencies</option>
                <option v-for="currency in availableCurrencies" :key="currency" :value="currency">
                  {{ currency }}
                </option>
              </select>
            </div>

            <div class="filter-group">
              <label class="filter-label">Payment Method</label>
              <select v-model="filters.paymentMethod" class="filter-select">
                <option value="">All Methods</option>
                <option value="Cash">Cash</option>
                <option value="Check">Check</option>
                <option value="Bank Transfer">Bank Transfer</option>
                <option value="Credit Card">Credit Card</option>
                <option value="Wire Transfer">Wire Transfer</option>
                <option value="Online Payment">Online Payment</option>
              </select>
            </div>
            
            <div class="filter-actions">
              <button @click="applyFilters" class="btn btn-secondary">
                <i class="fas fa-search"></i>
                Filter
              </button>
              <button @click="clearFilters" class="btn btn-outline">
                <i class="fas fa-times"></i>
                Clear
              </button>
            </div>
          </div>
        </div>
      </transition>
    </div>

    <!-- Currency Summary Cards (when showing summary) -->
    <div v-if="showCurrencySummary && currencySummary.length > 0" class="currency-summary-section">
      <div class="summary-header">
        <h3 class="summary-title">
          <i class="fas fa-globe"></i>
          Currency Summary
        </h3>
        <button @click="showCurrencySummary = false" class="close-summary-btn">
          <i class="fas fa-times"></i>
        </button>
      </div>
      <div class="currency-summary-grid">
        <div v-for="summary in currencySummary" :key="summary.currency" class="currency-card">
          <div class="currency-header">
            <span class="currency-code">{{ summary.currency }}</span>
            <span class="payment-count">{{ summary.count }} payments</span>
          </div>
          <div class="currency-amounts">
            <div class="amount-item">
              <span class="amount-label">Total Amount</span>
              <span class="amount-value">{{ formatCurrency(summary.total_amount, summary.currency) }}</span>
            </div>
            <div v-if="summary.currency !== baseCurrency" class="amount-item">
              <span class="amount-label">Base Currency ({{ baseCurrency }})</span>
              <span class="amount-value">{{ formatCurrency(summary.base_currency_total, baseCurrency) }}</span>
            </div>
            <div v-if="Math.abs(summary.total_exchange_difference) > 0.01" class="amount-item">
              <span class="amount-label">Exchange {{ summary.total_exchange_difference > 0 ? 'Gain' : 'Loss' }}</span>
              <span class="amount-value" :class="summary.total_exchange_difference > 0 ? 'gain' : 'loss'">
                {{ formatCurrency(Math.abs(summary.total_exchange_difference), baseCurrency) }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon">
          <i class="fas fa-money-bill-wave"></i>
        </div>
        <div class="stat-content">
          <div class="stat-value">{{ formatCurrency(stats.totalPayments, baseCurrency) }}</div>
          <div class="stat-label">Total Payments ({{ baseCurrency }})</div>
        </div>
      </div>
      
      <div class="stat-card">
        <div class="stat-icon">
          <i class="fas fa-chart-line"></i>
        </div>
        <div class="stat-content">
          <div class="stat-value">{{ stats.paymentsCount }}</div>
          <div class="stat-label">Total Records</div>
        </div>
      </div>
      
      <div class="stat-card">
        <div class="stat-icon">
          <i class="fas fa-calendar-day"></i>
        </div>
        <div class="stat-content">
          <div class="stat-value">{{ formatCurrency(stats.todayPayments, baseCurrency) }}</div>
          <div class="stat-label">Today's Payments</div>
        </div>
      </div>
      
      <div class="stat-card">
        <div class="stat-icon">
          <i class="fas fa-globe"></i>
        </div>
        <div class="stat-content">
          <div class="stat-value">{{ stats.uniqueCurrencies }}</div>
          <div class="stat-label">Currencies</div>
        </div>
      </div>
    </div>

    <!-- Payments Table -->
    <div class="table-card">
      <div class="table-header">
        <h3 class="table-title">Payment Records</h3>
        <div class="table-actions">
          <button @click="exportPayments" class="btn btn-outline">
            <i class="fas fa-download"></i>
            Export
          </button>
        </div>
      </div>
      
      <div class="table-container" v-if="!loading">
        <table class="payments-table">
          <thead>
            <tr>
              <th>Payment ID</th>
              <th>Customer</th>
              <th>Payment Date</th>
              <th>Payment Amount</th>
              <th>Receivable Amount</th>
              <th>Currency Info</th>
              <th>Method</th>
              <th>Reference</th>
              <th>Exchange</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="payment in payments" :key="payment.payment_id" class="table-row">
              <td class="payment-id">
                <span class="id-badge">#{{ payment.payment_id }}</span>
              </td>
              <td class="customer-info">
                <div class="customer-details">
                  <div class="customer-name">{{ payment.customer_receivable?.customer?.name || 'Unknown' }}</div>
                  <div class="customer-code">{{ payment.customer_receivable?.customer?.customer_code || '' }}</div>
                </div>
              </td>
              <td class="payment-date">
                <div class="date-wrapper">
                  <i class="fas fa-calendar"></i>
                  {{ formatDate(payment.payment_date) }}
                </div>
              </td>
              <td class="amount">
                <div class="amount-wrapper">
                  <span class="amount-value">{{ formatCurrency(payment.amount, payment.payment_currency) }}</span>
                  <span class="currency-badge">{{ payment.payment_currency }}</span>
                </div>
              </td>
              <td class="receivable-amount">
                <div class="amount-wrapper">
                  <span class="amount-value">{{ formatCurrency(payment.receivable_amount, payment.customer_receivable?.currency_code || baseCurrency) }}</span>
                  <span class="currency-badge">{{ payment.customer_receivable?.currency_code || baseCurrency }}</span>
                </div>
              </td>
              <td class="currency-info">
                <div class="currency-details">
                  <div v-if="payment.payment_currency !== baseCurrency" class="base-amount">
                    <span class="label">Base:</span>
                    <span class="value">{{ formatCurrency(payment.amount * payment.exchange_rate, baseCurrency) }}</span>
                  </div>
                  <div v-if="payment.exchange_rate && payment.exchange_rate !== 1" class="exchange-rate">
                    <span class="label">Rate:</span>
                    <span class="value">{{ formatNumber(payment.exchange_rate, 4) }}</span>
                  </div>
                  <div v-if="Math.abs(payment.exchange_difference) > 0.01" class="exchange-diff">
                    <span class="label">{{ payment.exchange_difference > 0 ? 'Gain:' : 'Loss:' }}</span>
                    <span class="value" :class="payment.exchange_difference > 0 ? 'gain' : 'loss'">
                      {{ formatCurrency(Math.abs(payment.exchange_difference), baseCurrency) }}
                    </span>
                  </div>
                </div>
              </td>
              <td class="payment-method">
                <span class="method-badge" :class="getMethodClass(payment.payment_method)">
                  <i :class="getMethodIcon(payment.payment_method)"></i>
                  {{ payment.payment_method || 'N/A' }}
                </span>
              </td>
              <td class="reference">
                <span class="reference-text">{{ payment.reference_number || 'N/A' }}</span>
              </td>
              <td class="exchange-status">
                <span v-if="payment.payment_currency === baseCurrency" class="exchange-badge no-exchange">
                  Same Currency
                </span>
                <span v-else-if="Math.abs(payment.exchange_difference) <= 0.01" class="exchange-badge no-difference">
                  No Difference
                </span>
                <span v-else class="exchange-badge has-difference" :class="payment.exchange_difference > 0 ? 'gain' : 'loss'">
                  {{ payment.exchange_difference > 0 ? 'Exchange Gain' : 'Exchange Loss' }}
                </span>
              </td>
              <td class="actions">
                <div class="action-buttons">
                  <button @click="viewPayment(payment.payment_id)" class="action-btn view" title="View Details">
                    <i class="fas fa-eye"></i>
                  </button>
                  <button @click="deletePayment(payment.payment_id)" class="action-btn delete" title="Delete Payment">
                    <i class="fas fa-trash"></i>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
        
        <!-- Empty State -->
        <div v-if="!loading && payments.length === 0" class="empty-state">
          <div class="empty-icon">
            <i class="fas fa-credit-card"></i>
          </div>
          <h3 class="empty-title">No Payments Found</h3>
          <p class="empty-message">No payments match your current filters. Try adjusting your search criteria.</p>
          <button @click="clearFilters" class="btn btn-primary">
            <i class="fas fa-times"></i>
            Clear Filters
          </button>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="loading-container">
        <div class="loading-spinner">
          <i class="fas fa-spinner fa-spin"></i>
          <p>Loading payments...</p>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="pagination.last_page > 1" class="pagination-container">
        <div class="pagination-info">
          Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} payments
        </div>
        <div class="pagination-controls">
          <button 
            @click="changePage(pagination.current_page - 1)"
            :disabled="pagination.current_page === 1"
            class="pagination-btn"
          >
            <i class="fas fa-chevron-left"></i>
            Previous
          </button>
          
          <div class="page-numbers">
            <button
              v-for="page in getPageNumbers()"
              :key="page"
              @click="page !== '...' && changePage(page)"
              :class="{ 
                'page-btn': true, 
                'active': page === pagination.current_page,
                'disabled': page === '...'
              }"
            >
              {{ page }}
            </button>
          </div>
          
          <button 
            @click="changePage(pagination.current_page + 1)"
            :disabled="pagination.current_page === pagination.last_page"
            class="pagination-btn"
          >
            Next
            <i class="fas fa-chevron-right"></i>
          </button>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div v-if="showDeleteModal" class="modal-overlay" @click="closeDeleteModal">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3 class="modal-title">Delete Payment</h3>
          <button @click="closeDeleteModal" class="close-btn">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <div class="delete-warning">
            <i class="fas fa-exclamation-triangle"></i>
            <p>Are you sure you want to delete this payment?</p>
            <p><strong>This action cannot be undone.</strong></p>
          </div>
        </div>
        <div class="modal-footer">
          <button @click="closeDeleteModal" class="btn btn-outline">Cancel</button>
          <button @click="confirmDelete" class="btn btn-danger">
            <i class="fas fa-trash"></i>
            Delete Payment
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

export default {
  name: 'PaymentsList',
  setup() {
    const router = useRouter()
    const loading = ref(false)
    const showFilters = ref(true)
    const showCurrencySummary = ref(false)
    const payments = ref([])
    const customers = ref([])
    const currencySummary = ref([])
    const pagination = ref({})
    const baseCurrency = ref('USD')
    const availableCurrencies = ref(['USD', 'EUR', 'GBP', 'JPY', 'CAD', 'AUD', 'CHF', 'CNY', 'SGD', 'IDR'])
    
    const stats = ref({
      totalPayments: 0,
      paymentsCount: 0,
      todayPayments: 0,
      uniqueCurrencies: 0
    })
    
    const filters = reactive({
      fromDate: '',
      toDate: '',
      customerId: '',
      paymentCurrency: '',
      paymentMethod: ''
    })
    
    const showDeleteModal = ref(false)
    const deletePaymentId = ref(null)

    const fetchPayments = async (page = 1) => {
      try {
        loading.value = true
        const params = {
          page,
          per_page: 15,
          ...filters
        }
        
        // Remove empty filters
        Object.keys(params).forEach(key => {
          if (params[key] === '' || params[key] === null) {
            delete params[key]
          }
        })
        
        const response = await axios.get('/accounting/receivable-payments', { params })
        payments.value = response.data.data
        pagination.value = {
          current_page: response.data.current_page,
          last_page: response.data.last_page,
          per_page: response.data.per_page,
          total: response.data.total,
          from: response.data.from,
          to: response.data.to
        }
        
        calculateStats()
      } catch (error) {
        console.error('Error fetching payments:', error)
      } finally {
        loading.value = false
      }
    }

    const fetchCustomers = async () => {
      try {
        const response = await axios.get('/customers')
        customers.value = response.data.data || response.data
      } catch (error) {
        console.error('Error fetching customers:', error)
      }
    }

    const getCurrencySummary = async () => {
      try {
        const params = { ...filters }
        Object.keys(params).forEach(key => {
          if (params[key] === '' || params[key] === null) {
            delete params[key]
          }
        })
        
        const response = await axios.get('/accounting/receivable-payments/currency-summary', { params })
        currencySummary.value = response.data.data
        showCurrencySummary.value = true
      } catch (error) {
        console.error('Error fetching currency summary:', error)
      }
    }

    const calculateStats = () => {
      // Calculate total in base currency
      const totalInBase = payments.value.reduce((sum, payment) => {
        const baseAmount = payment.amount * (payment.exchange_rate || 1)
        return sum + baseAmount
      }, 0)
      
      const today = new Date().toISOString().split('T')[0]
      const todayTotal = payments.value
        .filter(payment => payment.payment_date === today)
        .reduce((sum, payment) => {
          const baseAmount = payment.amount * (payment.exchange_rate || 1)
          return sum + baseAmount
        }, 0)
      
      const uniqueCurrencies = new Set(
        payments.value.map(payment => payment.payment_currency)
      )
      
      stats.value = {
        totalPayments: totalInBase,
        paymentsCount: payments.value.length,
        todayPayments: todayTotal,
        uniqueCurrencies: uniqueCurrencies.size
      }
    }

    const applyFilters = () => {
      fetchPayments(1)
    }

    const clearFilters = () => {
      Object.keys(filters).forEach(key => {
        filters[key] = ''
      })
      showCurrencySummary.value = false
      fetchPayments(1)
    }

    const changePage = (page) => {
      if (page >= 1 && page <= pagination.value.last_page) {
        fetchPayments(page)
      }
    }

    const getPageNumbers = () => {
      const current = pagination.value.current_page
      const last = pagination.value.last_page
      const pages = []
      
      if (last <= 7) {
        for (let i = 1; i <= last; i++) {
          pages.push(i)
        }
      } else {
        if (current <= 4) {
          for (let i = 1; i <= 5; i++) pages.push(i)
          pages.push('...')
          pages.push(last)
        } else if (current >= last - 3) {
          pages.push(1)
          pages.push('...')
          for (let i = last - 4; i <= last; i++) pages.push(i)
        } else {
          pages.push(1)
          pages.push('...')
          for (let i = current - 1; i <= current + 1; i++) pages.push(i)
          pages.push('...')
          pages.push(last)
        }
      }
      
      return pages
    }

    const viewPayment = (paymentId) => {
      router.push(`/accounting/receivable-payments/${paymentId}`)
    }

    const deletePayment = (paymentId) => {
      deletePaymentId.value = paymentId
      showDeleteModal.value = true
    }

    const confirmDelete = async () => {
      try {
        await axios.delete(`/accounting/receivable-payments/${deletePaymentId.value}`)
        showDeleteModal.value = false
        fetchPayments(pagination.value.current_page)
      } catch (error) {
        console.error('Error deleting payment:', error)
      }
    }

    const closeDeleteModal = () => {
      showDeleteModal.value = false
      deletePaymentId.value = null
    }

    const exportPayments = () => {
      // Implement export functionality
      console.log('Export payments')
    }

    const formatCurrency = (amount, currency = 'USD') => {
      if (amount === null || amount === undefined) return 'N/A'
      return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: currency,
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      }).format(amount)
    }

    const formatNumber = (number, decimals = 2) => {
      if (number === null || number === undefined) return 'N/A'
      return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals
      }).format(number)
    }

    const formatDate = (date) => {
      if (!date) return 'N/A'
      return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      })
    }

    const getMethodClass = (method) => {
      const methodClasses = {
        'Cash': 'method-cash',
        'Bank Transfer': 'method-transfer',
        'Credit Card': 'method-card',
        'Check': 'method-check',
        'Wire Transfer': 'method-wire',
        'Online Payment': 'method-online'
      }
      return methodClasses[method] || 'method-default'
    }

    const getMethodIcon = (method) => {
      const methodIcons = {
        'Cash': 'fas fa-money-bill',
        'Bank Transfer': 'fas fa-exchange-alt',
        'Credit Card': 'fas fa-credit-card',
        'Check': 'fas fa-money-check',
        'Wire Transfer': 'fas fa-university',
        'Online Payment': 'fas fa-globe'
      }
      return methodIcons[method] || 'fas fa-payment'
    }

    onMounted(async () => {
      await Promise.all([
        fetchPayments(),
        fetchCustomers()
      ])
      
      // Get base currency from config
      try {
        const configResponse = await axios.get('/accounting/config')
        baseCurrency.value = configResponse.data.base_currency || 'USD'
      } catch (error) {
        console.error('Error loading config:', error)
      }
    })

    return {
      loading,
      showFilters,
      showCurrencySummary,
      payments,
      customers,
      currencySummary,
      pagination,
      stats,
      filters,
      showDeleteModal,
      deletePaymentId,
      baseCurrency,
      availableCurrencies,
      fetchPayments,
      getCurrencySummary,
      applyFilters,
      clearFilters,
      changePage,
      getPageNumbers,
      viewPayment,
      deletePayment,
      confirmDelete,
      closeDeleteModal,
      exportPayments,
      formatCurrency,
      formatNumber,
      formatDate,
      getMethodClass,
      getMethodIcon
    }
  }
}
</script>

<style scoped>
.payments-list-container {
  min-height: 100vh;
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
  padding: 2rem;
}

.page-header {
  background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
  border-radius: 20px;
  padding: 2rem;
  margin-bottom: 2rem;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.title-section {
  flex: 1;
}

.page-title {
  font-size: 2.5rem;
  font-weight: 700;
  color: #1e293b;
  margin: 0 0 0.5rem 0;
  display: flex;
  align-items: center;
  gap: 1rem;
}

.page-title i {
  color: #6366f1;
}

.page-subtitle {
  color: #64748b;
  font-size: 1.125rem;
  margin: 0;
}

.header-actions {
  display: flex;
  gap: 1rem;
}

.btn {
  padding: 0.75rem 1.5rem;
  border-radius: 12px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s ease;
  border: none;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  text-decoration: none;
  font-size: 1rem;
}

.btn-primary {
  background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
  color: white;
  box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(99, 102, 241, 0.4);
}

.btn-outline {
  background: white;
  color: #6366f1;
  border: 2px solid #6366f1;
}

.btn-outline:hover {
  background: #6366f1;
  color: white;
}

.btn-secondary {
  background: #64748b;
  color: white;
}

.btn-secondary:hover {
  background: #475569;
}

.btn-danger {
  background: #ef4444;
  color: white;
}

.btn-danger:hover {
  background: #dc2626;
}

.filters-card {
  background: white;
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
  margin-bottom: 2rem;
  overflow: hidden;
}

.filters-header {
  padding: 1.5rem;
  background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
  border-bottom: 1px solid #f1f5f9;
  cursor: pointer;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.filters-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #1e293b;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin: 0;
}

.filters-title i {
  color: #6366f1;
}

.filters-content {
  padding: 1.5rem;
}

.filter-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  align-items: end;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.filter-label {
  font-weight: 500;
  color: #374151;
  font-size: 0.875rem;
}

.date-range-inputs {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.date-input,
.filter-select {
  padding: 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 1rem;
  transition: all 0.3s ease;
  background: white;
}

.date-input:focus,
.filter-select:focus {
  outline: none;
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.date-separator {
  color: #64748b;
  font-weight: 500;
}

.filter-actions {
  display: flex;
  gap: 0.75rem;
}

.slide-enter-active,
.slide-leave-active {
  transition: all 0.3s ease;
}

.slide-enter-from,
.slide-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

.currency-summary-section {
  background: white;
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
  margin-bottom: 2rem;
  overflow: hidden;
}

.summary-header {
  padding: 1.5rem;
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  color: white;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.summary-title {
  font-size: 1.125rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin: 0;
}

.close-summary-btn {
  background: rgba(255, 255, 255, 0.2);
  border: none;
  color: white;
  padding: 0.5rem;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.3s ease;
}

.close-summary-btn:hover {
  background: rgba(255, 255, 255, 0.3);
}

.currency-summary-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 1.5rem;
  padding: 1.5rem;
}

.currency-card {
  background: #f8fafc;
  border-radius: 12px;
  padding: 1.5rem;
  border: 1px solid #e2e8f0;
}

.currency-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.currency-code {
  font-size: 1.25rem;
  font-weight: 700;
  color: #1e293b;
}

.payment-count {
  font-size: 0.875rem;
  color: #64748b;
}

.currency-amounts {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.amount-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.amount-label {
  font-size: 0.875rem;
  color: #64748b;
}

.amount-value {
  font-weight: 600;
  color: #1e293b;
}

.amount-value.gain {
  color: #059669;
}

.amount-value.loss {
  color: #dc2626;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 2rem;
  margin-bottom: 2rem;
}

.stat-card {
  background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
  border-radius: 16px;
  padding: 2rem;
  display: flex;
  align-items: center;
  gap: 1.5rem;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
  border: 1px solid #f1f5f9;
  transition: all 0.3s ease;
}

.stat-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 12px 35px rgba(0, 0, 0, 0.1);
}

.stat-icon {
  width: 70px;
  height: 70px;
  border-radius: 16px;
  background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2rem;
  color: white;
}

.stat-content {
  flex: 1;
}

.stat-value {
  font-size: 2rem;
  font-weight: 700;
  color: #1e293b;
  line-height: 1;
  margin-bottom: 0.5rem;
}

.stat-label {
  color: #64748b;
  font-size: 0.875rem;
}

.table-card {
  background: white;
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
  overflow: hidden;
}

.table-header {
  padding: 1.5rem;
  background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
  border-bottom: 1px solid #f1f5f9;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.table-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: #1e293b;
  margin: 0;
}

.table-actions {
  display: flex;
  gap: 0.75rem;
}

.table-container {
  overflow-x: auto;
}

.payments-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.875rem;
}

.payments-table th {
  background: #f8fafc;
  padding: 1rem;
  text-align: left;
  font-weight: 600;
  color: #374151;
  border-bottom: 1px solid #f1f5f9;
  white-space: nowrap;
}

.payments-table td {
  padding: 1rem;
  border-bottom: 1px solid #f1f5f9;
  vertical-align: top;
}

.table-row {
  transition: all 0.3s ease;
}

.table-row:hover {
  background: #f8fafc;
}

.payment-id .id-badge {
  background: #6366f1;
  color: white;
  padding: 0.375rem 0.75rem;
  border-radius: 6px;
  font-weight: 500;
  font-size: 0.875rem;
}

.customer-details {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.customer-name {
  font-weight: 600;
  color: #1e293b;
}

.customer-code {
  font-size: 0.75rem;
  color: #64748b;
}

.date-wrapper {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #64748b;
}

.amount-wrapper {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.amount-value {
  font-weight: 600;
  color: #1e293b;
}

.currency-badge {
  background: #e0e7ff;
  color: #3730a3;
  padding: 0.125rem 0.375rem;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 500;
}

.currency-details {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  font-size: 0.75rem;
}

.currency-details .label {
  color: #64748b;
  font-weight: 500;
}

.currency-details .value {
  color: #1e293b;
  font-weight: 600;
}

.currency-details .value.gain {
  color: #059669;
}

.currency-details .value.loss {
  color: #dc2626;
}

.method-badge {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.375rem 0.75rem;
  border-radius: 6px;
  font-weight: 500;
  font-size: 0.75rem;
}

.method-cash {
  background: #dcfce7;
  color: #166534;
}

.method-transfer {
  background: #dbeafe;
  color: #1e40af;
}

.method-card {
  background: #f3e8ff;
  color: #7c3aed;
}

.method-check {
  background: #fef3c7;
  color: #92400e;
}

.method-wire {
  background: #ecfdf5;
  color: #059669;
}

.method-online {
  background: #fef2f2;
  color: #dc2626;
}

.method-default {
  background: #f1f5f9;
  color: #475569;
}

.reference-text {
  color: #64748b;
  font-family: monospace;
}

.exchange-badge {
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 500;
}

.exchange-badge.no-exchange {
  background: #e0e7ff;
  color: #3730a3;
}

.exchange-badge.no-difference {
  background: #dcfce7;
  color: #166534;
}

.exchange-badge.has-difference {
  background: #fef3c7;
  color: #92400e;
}

.exchange-badge.gain {
  background: #dcfce7;
  color: #166534;
}

.exchange-badge.loss {
  background: #fef2f2;
  color: #dc2626;
}

.action-buttons {
  display: flex;
  gap: 0.5rem;
}

.action-btn {
  width: 32px;
  height: 32px;
  border-radius: 6px;
  border: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
}

.action-btn.view {
  background: #dbeafe;
  color: #1e40af;
}

.action-btn.view:hover {
  background: #1e40af;
  color: white;
}

.action-btn.delete {
  background: #fef2f2;
  color: #dc2626;
}

.action-btn.delete:hover {
  background: #dc2626;
  color: white;
}

.empty-state {
  text-align: center;
  padding: 4rem 2rem;
}

.empty-icon {
  font-size: 4rem;
  color: #d1d5db;
  margin-bottom: 1rem;
}

.empty-title {
  font-size: 1.5rem;
  font-weight: 600;
  color: #374151;
  margin-bottom: 0.5rem;
}

.empty-message {
  color: #6b7280;
  margin-bottom: 2rem;
}

.loading-container {
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 4rem 2rem;
}

.loading-spinner {
  text-align: center;
}

.loading-spinner i {
  font-size: 2rem;
  color: #6366f1;
  margin-bottom: 1rem;
}

.pagination-container {
  padding: 1.5rem;
  border-top: 1px solid #f1f5f9;
  display: flex;
  justify-content: space-between;
  align-items: center;
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

.pagination-btn {
  padding: 0.5rem 1rem;
  border: 1px solid #d1d5db;
  background: white;
  color: #374151;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.pagination-btn:hover:not(:disabled) {
  background: #f9fafb;
  border-color: #6366f1;
}

.pagination-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.page-numbers {
  display: flex;
  gap: 0.25rem;
}

.page-btn {
  width: 40px;
  height: 40px;
  border: 1px solid #d1d5db;
  background: white;
  color: #374151;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
}

.page-btn:hover:not(.disabled) {
  background: #f9fafb;
  border-color: #6366f1;
}

.page-btn.active {
  background: #6366f1;
  color: white;
  border-color: #6366f1;
}

.page-btn.disabled {
  cursor: default;
  color: #9ca3af;
}

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
}

.modal-content {
  background: white;
  border-radius: 16px;
  max-width: 500px;
  width: 90%;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.modal-header {
  padding: 1.5rem;
  border-bottom: 1px solid #f1f5f9;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: #1e293b;
  margin: 0;
}

.close-btn {
  background: none;
  border: none;
  font-size: 1.25rem;
  color: #64748b;
  cursor: pointer;
  padding: 0.5rem;
  border-radius: 4px;
}

.close-btn:hover {
  color: #1e293b;
  background: #f1f5f9;
}

.modal-body {
  padding: 1.5rem;
}

.delete-warning {
  text-align: center;
}

.delete-warning i {
  font-size: 3rem;
  color: #ef4444;
  margin-bottom: 1rem;
}

.delete-warning p {
  margin-bottom: 1rem;
  color: #64748b;
}

.modal-footer {
  padding: 1.5rem;
  border-top: 1px solid #f1f5f9;
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
}

@media (max-width: 768px) {
  .payments-list-container {
    padding: 1rem;
  }

  .header-content {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }

  .header-actions {
    width: 100%;
    justify-content: flex-start;
  }

  .filter-row {
    grid-template-columns: 1fr;
  }

  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .currency-summary-grid {
    grid-template-columns: 1fr;
  }

  .table-container {
    font-size: 0.75rem;
  }

  .pagination-container {
    flex-direction: column;
    gap: 1rem;
  }

  .pagination-controls {
    width: 100%;
    justify-content: center;
  }

  .modal-content {
    margin: 1rem;
    width: calc(100% - 2rem);
  }
}
</style>