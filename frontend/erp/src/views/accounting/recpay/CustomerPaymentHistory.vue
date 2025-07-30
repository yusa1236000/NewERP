<template>
  <div class="customer-payment-container">
    <!-- Header Section -->
    <div class="page-header">
      <div class="header-content">
        <div class="title-section">
          <h1 class="page-title">
            <i class="fas fa-user-check"></i>
            Customer Payment History
          </h1>
          <p class="page-subtitle">View comprehensive payment history with multi-currency support</p>
        </div>
        <div class="header-actions">
          <button @click="exportCustomerPayments" class="btn btn-outline">
            <i class="fas fa-download"></i>
            Export Report
          </button>
          <router-link to="/accounting/receivable-payments/create" class="btn btn-primary">
            <i class="fas fa-plus"></i>
            Record Payment
          </router-link>
        </div>
      </div>
    </div>

    <!-- Customer Selection & Filters -->
    <div class="filters-card">
      <div class="filters-header">
        <h3 class="filters-title">
          <i class="fas fa-filter"></i>
          Customer & Filters
        </h3>
      </div>
      <div class="filters-content">
        <div class="filter-row">
          <div class="filter-group customer-select-group">
            <label class="filter-label">Customer <span class="required">*</span></label>
            <select 
              v-model="selectedCustomerId" 
              @change="onCustomerChange"
              class="customer-select"
              :disabled="loading"
            >
              <option value="">Select a customer...</option>
              <option 
                v-for="customer in customers" 
                :key="customer.customer_id" 
                :value="customer.customer_id"
              >
                {{ customer.name }} ({{ customer.customer_code }})
                <span v-if="customer.preferred_currency"> - {{ customer.preferred_currency }}</span>
              </option>
            </select>
          </div>
          
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
              Apply Filters
            </button>
            <button @click="clearFilters" class="btn btn-outline">
              <i class="fas fa-times"></i>
              Clear
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Customer Summary (when customer is selected) -->
    <div v-if="selectedCustomer && customerSummary" class="customer-summary-section">
      <div class="summary-header">
        <h3 class="summary-title">
          <i class="fas fa-chart-bar"></i>
          {{ selectedCustomer.name }} - Payment Summary
        </h3>
        <div class="customer-info">
          <span class="customer-code">{{ selectedCustomer.customer_code }}</span>
          <span v-if="selectedCustomer.preferred_currency" class="preferred-currency">
            Preferred: {{ selectedCustomer.preferred_currency }}
          </span>
        </div>
      </div>
      
      <!-- Multi-Currency Summary Cards -->
      <div class="currency-summary-grid">
        <div v-for="currencySummary in customerSummary.currencies" :key="currencySummary.currency" class="currency-summary-card">
          <div class="currency-header">
            <div class="currency-info">
              <span class="currency-code">{{ currencySummary.currency }}</span>
              <span class="payment-count">{{ currencySummary.payment_count }} payments</span>
            </div>
            <div v-if="currencySummary.currency !== baseCurrency" class="conversion-indicator">
              <i class="fas fa-exchange-alt" title="Multi-currency"></i>
            </div>
          </div>
          
          <div class="currency-stats">
            <div class="stat-item">
              <div class="stat-label">Total Payments</div>
              <div class="stat-value">{{ formatCurrency(currencySummary.total_amount, currencySummary.currency) }}</div>
            </div>
            
            <div v-if="currencySummary.currency !== baseCurrency" class="stat-item">
              <div class="stat-label">Base Currency ({{ baseCurrency }})</div>
              <div class="stat-value">{{ formatCurrency(currencySummary.base_currency_total, baseCurrency) }}</div>
            </div>
            
            <div class="stat-item">
              <div class="stat-label">Average Payment</div>
              <div class="stat-value">{{ formatCurrency(currencySummary.average_payment, currencySummary.currency) }}</div>
            </div>
            
            <div v-if="Math.abs(currencySummary.total_exchange_difference) > 0.01" class="stat-item">
              <div class="stat-label">Exchange {{ currencySummary.total_exchange_difference > 0 ? 'Gain' : 'Loss' }}</div>
              <div class="stat-value" :class="currencySummary.total_exchange_difference > 0 ? 'gain' : 'loss'">
                {{ formatCurrency(Math.abs(currencySummary.total_exchange_difference), baseCurrency) }}
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Overall Summary -->
      <div class="overall-summary">
        <div class="summary-stats">
          <div class="summary-stat">
            <div class="stat-icon total">
              <i class="fas fa-money-bill-wave"></i>
            </div>
            <div class="stat-content">
              <div class="stat-value">{{ formatCurrency(customerSummary.total_base_amount, baseCurrency) }}</div>
              <div class="stat-label">Total Payments ({{ baseCurrency }})</div>
            </div>
          </div>
          
          <div class="summary-stat">
            <div class="stat-icon count">
              <i class="fas fa-hashtag"></i>
            </div>
            <div class="stat-content">
              <div class="stat-value">{{ customerSummary.total_payments }}</div>
              <div class="stat-label">Total Records</div>
            </div>
          </div>
          
          <div class="summary-stat">
            <div class="stat-icon currency">
              <i class="fas fa-globe"></i>
            </div>
            <div class="stat-content">
              <div class="stat-value">{{ customerSummary.currencies.length }}</div>
              <div class="stat-label">Currencies Used</div>
            </div>
          </div>
          
          <div class="summary-stat">
            <div class="stat-icon period">
              <i class="fas fa-calendar-alt"></i>
            </div>
            <div class="stat-content">
              <div class="stat-value">{{ customerSummary.payment_period }}</div>
              <div class="stat-label">Payment Period</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Payment History Table -->
    <div v-if="selectedCustomer" class="payments-table-card">
      <div class="table-header">
        <h3 class="table-title">Payment History</h3>
        <div class="table-actions">
          <div class="view-toggle">
            <button 
              @click="viewMode = 'detailed'"
              :class="{ active: viewMode === 'detailed' }"
              class="toggle-btn"
            >
              <i class="fas fa-list-ul"></i>
              Detailed
            </button>
            <button 
              @click="viewMode = 'summary'"
              :class="{ active: viewMode === 'summary' }"
              class="toggle-btn"
            >
              <i class="fas fa-chart-bar"></i>
              Summary
            </button>
          </div>
          <button @click="refreshPayments" class="btn btn-outline-sm">
            <i class="fas fa-sync-alt" :class="{ 'fa-spin': loading }"></i>
            Refresh
          </button>
        </div>
      </div>
      
      <!-- Detailed View -->
      <div v-if="viewMode === 'detailed'" class="detailed-view">
        <div class="table-container" v-if="!loading">
          <table class="payments-table">
            <thead>
              <tr>
                <th>Payment Date</th>
                <th>Payment ID</th>
                <th>Invoice/Receivable</th>
                <th>Payment Amount</th>
                <th>Receivable Amount</th>
                <th>Currency Details</th>
                <th>Method</th>
                <th>Exchange Impact</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="payment in payments" :key="payment.payment_id" class="payment-row">
                <td class="date-cell">
                  <div class="date-wrapper">
                    <i class="fas fa-calendar"></i>
                    <span>{{ formatDate(payment.payment_date) }}</span>
                  </div>
                </td>
                <td class="payment-id-cell">
                  <span class="payment-id-badge">#{{ payment.payment_id }}</span>
                </td>
                <td class="receivable-cell">
                  <div class="receivable-info">
                    <div class="receivable-id">#{{ payment.customer_receivable?.receivable_id }}</div>
                    <div v-if="payment.customer_receivable?.invoice_id" class="invoice-id">
                      Inv: #{{ payment.customer_receivable.invoice_id }}
                    </div>
                  </div>
                </td>
                <td class="payment-amount-cell">
                  <div class="amount-wrapper">
                    <span class="amount-value">{{ formatCurrency(payment.amount, payment.payment_currency) }}</span>
                    <span class="currency-badge">{{ payment.payment_currency }}</span>
                  </div>
                </td>
                <td class="receivable-amount-cell">
                  <div class="amount-wrapper">
                    <span class="amount-value">{{ formatCurrency(payment.receivable_amount, payment.customer_receivable?.currency_code || baseCurrency) }}</span>
                    <span class="currency-badge">{{ payment.customer_receivable?.currency_code || baseCurrency }}</span>
                  </div>
                </td>
                <td class="currency-details-cell">
                  <div class="currency-details">
                    <div v-if="payment.payment_currency !== baseCurrency" class="base-amount">
                      <span class="label">Base:</span>
                      <span class="value">{{ formatCurrency(payment.amount * payment.exchange_rate, baseCurrency) }}</span>
                    </div>
                    <div v-if="payment.exchange_rate && payment.exchange_rate !== 1" class="exchange-rate">
                      <span class="label">Rate:</span>
                      <span class="value">{{ formatNumber(payment.exchange_rate, 4) }}</span>
                    </div>
                    <div v-if="payment.payment_currency !== (payment.customer_receivable?.currency_code || baseCurrency)" class="conversion-indicator">
                      <i class="fas fa-exchange-alt" title="Currency conversion applied"></i>
                      <span>Cross-currency</span>
                    </div>
                  </div>
                </td>
                <td class="method-cell">
                  <span class="method-badge" :class="getMethodClass(payment.payment_method)">
                    <i :class="getMethodIcon(payment.payment_method)"></i>
                    {{ payment.payment_method }}
                  </span>
                </td>
                <td class="exchange-impact-cell">
                  <div v-if="Math.abs(payment.exchange_difference) > 0.01" class="exchange-impact">
                    <span class="impact-label">{{ payment.exchange_difference > 0 ? 'Gain' : 'Loss' }}</span>
                    <span class="impact-value" :class="payment.exchange_difference > 0 ? 'gain' : 'loss'">
                      {{ formatCurrency(Math.abs(payment.exchange_difference), baseCurrency) }}
                    </span>
                  </div>
                  <div v-else class="no-impact">
                    <span class="no-impact-text">No Impact</span>
                  </div>
                </td>
                <td class="status-cell">
                  <span class="status-badge completed">
                    <i class="fas fa-check-circle"></i>
                    Completed
                  </span>
                </td>
                <td class="actions-cell">
                  <div class="action-buttons">
                    <button @click="viewPaymentDetails(payment)" class="action-btn view" title="View Details">
                      <i class="fas fa-eye"></i>
                    </button>
                    <button @click="viewReceivable(payment)" class="action-btn receivable" title="View Receivable">
                      <i class="fas fa-file-invoice"></i>
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
            <p class="empty-message">
              {{ selectedCustomer ? `No payments found for ${selectedCustomer.name}` : 'No payments match your current filters.' }}
            </p>
            <button v-if="hasActiveFilters" @click="clearFilters" class="btn btn-primary">
              <i class="fas fa-times"></i>
              Clear Filters
            </button>
          </div>
        </div>
      </div>

      <!-- Summary View -->
      <div v-else-if="viewMode === 'summary'" class="summary-view">
        <div class="summary-charts">
          <!-- Payment Timeline -->
          <div class="chart-card">
            <div class="chart-header">
              <h4 class="chart-title">
                <i class="fas fa-chart-line"></i>
                Payment Timeline
              </h4>
            </div>
            <div class="chart-content">
              <div class="timeline-chart">
                <div v-for="period in paymentTimeline" :key="period.period" class="timeline-item">
                  <div class="period-label">{{ period.period }}</div>
                  <div class="period-bar">
                    <div class="bar-fill" :style="{ width: period.percentage + '%' }"></div>
                  </div>
                  <div class="period-amount">{{ formatCurrency(period.amount, baseCurrency) }}</div>
                </div>
              </div>
            </div>
          </div>

          <!-- Currency Breakdown -->
          <div class="chart-card">
            <div class="chart-header">
              <h4 class="chart-title">
                <i class="fas fa-globe"></i>
                Currency Breakdown
              </h4>
            </div>
            <div class="chart-content">
              <div class="currency-chart">
                <div v-for="currency in currencyBreakdown" :key="currency.code" class="currency-item">
                  <div class="currency-info">
                    <span class="currency-code">{{ currency.code }}</span>
                    <span class="currency-percentage">{{ currency.percentage.toFixed(1) }}%</span>
                  </div>
                  <div class="currency-bar">
                    <div class="bar-fill" :style="{ width: currency.percentage + '%' }"></div>
                  </div>
                  <div class="currency-amount">{{ formatCurrency(currency.amount, currency.code) }}</div>
                </div>
              </div>
            </div>
          </div>

          <!-- Payment Methods -->
          <div class="chart-card">
            <div class="chart-header">
              <h4 class="chart-title">
                <i class="fas fa-credit-card"></i>
                Payment Methods
              </h4>
            </div>
            <div class="chart-content">
              <div class="methods-chart">
                <div v-for="method in paymentMethods" :key="method.method" class="method-item">
                  <div class="method-info">
                    <i :class="getMethodIcon(method.method)"></i>
                    <span class="method-name">{{ method.method }}</span>
                    <span class="method-count">({{ method.count }})</span>
                  </div>
                  <div class="method-amount">{{ formatCurrency(method.amount, baseCurrency) }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="loading-container">
        <div class="loading-spinner">
          <i class="fas fa-spinner fa-spin"></i>
          <p>Loading payment history...</p>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="pagination.last_page > 1 && viewMode === 'detailed'" class="pagination-container">
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

    <!-- No Customer Selected State -->
    <div v-else class="no-customer-state">
      <div class="no-customer-content">
        <div class="no-customer-icon">
          <i class="fas fa-user-circle"></i>
        </div>
        <h3 class="no-customer-title">Select a Customer</h3>
        <p class="no-customer-message">
          Please select a customer from the dropdown above to view their payment history and multi-currency details.
        </p>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

export default {
  name: 'CustomerPayment',
  setup() {
    const router = useRouter()
    const loading = ref(false)
    const customers = ref([])
    const payments = ref([])
    const selectedCustomerId = ref('')
    const selectedCustomer = ref(null)
    const customerSummary = ref(null)
    const viewMode = ref('detailed')
    const pagination = ref({})
    const baseCurrency = ref('USD')
    const availableCurrencies = ref(['USD', 'EUR', 'GBP', 'JPY', 'CAD', 'AUD', 'CHF', 'CNY', 'SGD', 'IDR'])
    
    const filters = reactive({
      fromDate: '',
      toDate: '',
      paymentCurrency: '',
      paymentMethod: ''
    })

    const hasActiveFilters = computed(() => {
      return Object.values(filters).some(value => value !== '')
    })

    const paymentTimeline = computed(() => {
      if (!payments.value.length) return []
      
      // Group payments by month
      const timelineMap = {}
      const maxAmount = ref(0)
      
      payments.value.forEach(payment => {
        const date = new Date(payment.payment_date)
        const period = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`
        const baseAmount = payment.amount * (payment.exchange_rate || 1)
        
        if (!timelineMap[period]) {
          timelineMap[period] = { period, amount: 0, count: 0 }
        }
        
        timelineMap[period].amount += baseAmount
        timelineMap[period].count += 1
        
        if (timelineMap[period].amount > maxAmount.value) {
          maxAmount.value = timelineMap[period].amount
        }
      })
      
      return Object.values(timelineMap)
        .sort((a, b) => a.period.localeCompare(b.period))
        .map(item => ({
          ...item,
          percentage: maxAmount.value > 0 ? (item.amount / maxAmount.value) * 100 : 0
        }))
    })

    const currencyBreakdown = computed(() => {
      if (!payments.value.length) return []
      
      const currencyMap = {}
      let totalAmount = 0
      
      payments.value.forEach(payment => {
        const currency = payment.payment_currency
        const amount = payment.amount
        
        if (!currencyMap[currency]) {
          currencyMap[currency] = { code: currency, amount: 0, count: 0 }
        }
        
        currencyMap[currency].amount += amount
        currencyMap[currency].count += 1
        totalAmount += payment.amount * (payment.exchange_rate || 1)
      })
      
      return Object.values(currencyMap).map(item => ({
        ...item,
        percentage: totalAmount > 0 ? (item.amount / totalAmount) * 100 : 0
      }))
    })

    const paymentMethods = computed(() => {
      if (!payments.value.length) return []
      
      const methodMap = {}
      
      payments.value.forEach(payment => {
        const method = payment.payment_method || 'Unknown'
        const baseAmount = payment.amount * (payment.exchange_rate || 1)
        
        if (!methodMap[method]) {
          methodMap[method] = { method, amount: 0, count: 0 }
        }
        
        methodMap[method].amount += baseAmount
        methodMap[method].count += 1
      })
      
      return Object.values(methodMap).sort((a, b) => b.amount - a.amount)
    })

    onMounted(async () => {
      await loadCustomers()
      
      // Get base currency from config
      try {
        const configResponse = await axios.get('/accounting/config')
        baseCurrency.value = configResponse.data.base_currency || 'USD'
      } catch (error) {
        console.error('Error loading config:', error)
      }
    })

    // Watch for customer changes
    watch(selectedCustomerId, (newId) => {
      if (newId) {
        selectedCustomer.value = customers.value.find(c => c.customer_id == newId)
      } else {
        selectedCustomer.value = null
        customerSummary.value = null
        payments.value = []
      }
    })

    const loadCustomers = async () => {
      try {
        const response = await axios.get('customers')
        customers.value = response.data.data || response.data
      } catch (error) {
        console.error('Error loading customers:', error)
      }
    }

    const onCustomerChange = async () => {
      if (selectedCustomerId.value) {
        await loadCustomerPayments()
        await loadCustomerSummary()
      }
    }

    const loadCustomerPayments = async (page = 1) => {
      if (!selectedCustomerId.value) return
      
      try {
        loading.value = true
        const params = {
          customer_id: selectedCustomerId.value,
          page,
          per_page: 20,
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
      } catch (error) {
        console.error('Error loading customer payments:', error)
      } finally {
        loading.value = false
      }
    }

    const loadCustomerSummary = async () => {
      if (!selectedCustomerId.value) return
      
      try {
        const params = { customer_id: selectedCustomerId.value, ...filters }
        Object.keys(params).forEach(key => {
          if (params[key] === '' || params[key] === null) {
            delete params[key]
          }
        })
        
        const response = await axios.get('/accounting/receivable-payments/currency-summary', { params })
        const currencySummaries = response.data.data
        
        // Calculate overall summary
        const totalPayments = currencySummaries.reduce((sum, curr) => sum + curr.count, 0)
        const totalBaseAmount = currencySummaries.reduce((sum, curr) => sum + curr.base_currency_total, 0)
        
        // Calculate payment period
        let paymentPeriod = 'N/A'
        if (payments.value.length > 0) {
          const dates = payments.value.map(p => new Date(p.payment_date))
          const minDate = new Date(Math.min(...dates))
          const maxDate = new Date(Math.max(...dates))
          const daysDiff = Math.ceil((maxDate - minDate) / (1000 * 60 * 60 * 24))
          
          if (daysDiff === 0) {
            paymentPeriod = '1 day'
          } else if (daysDiff < 30) {
            paymentPeriod = `${daysDiff} days`
          } else if (daysDiff < 365) {
            paymentPeriod = `${Math.ceil(daysDiff / 30)} months`
          } else {
            paymentPeriod = `${Math.ceil(daysDiff / 365)} years`
          }
        }
        
        // Enhance currency summaries with additional stats
        const enhancedCurrencies = currencySummaries.map(curr => ({
          ...curr,
          average_payment: curr.total_amount / curr.count,
          payment_count: curr.count
        }))
        
        customerSummary.value = {
          currencies: enhancedCurrencies,
          total_payments: totalPayments,
          total_base_amount: totalBaseAmount,
          payment_period: paymentPeriod
        }
      } catch (error) {
        console.error('Error loading customer summary:', error)
      }
    }

    const applyFilters = async () => {
      if (selectedCustomerId.value) {
        await loadCustomerPayments(1)
        await loadCustomerSummary()
      }
    }

    const clearFilters = async () => {
      Object.keys(filters).forEach(key => {
        filters[key] = ''
      })
      if (selectedCustomerId.value) {
        await loadCustomerPayments(1)
        await loadCustomerSummary()
      }
    }

    const refreshPayments = async () => {
      if (selectedCustomerId.value) {
        await loadCustomerPayments(pagination.value.current_page || 1)
      }
    }

    const changePage = (page) => {
      if (page >= 1 && page <= pagination.value.last_page) {
        loadCustomerPayments(page)
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

    const viewPaymentDetails = (payment) => {
      router.push(`/accounting/receivable-payments/${payment.payment_id}`)
    }

    const viewReceivable = (payment) => {
      if (payment.customer_receivable?.receivable_id) {
        router.push(`/accounting/customer-receivables/${payment.customer_receivable.receivable_id}`)
      }
    }

    const exportCustomerPayments = () => {
      // Implementation for export functionality
      console.log('Export customer payments')
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

    const formatDate = (dateString) => {
      if (!dateString) return 'N/A'
      return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      })
    }

    return {
      loading,
      customers,
      payments,
      selectedCustomerId,
      selectedCustomer,
      customerSummary,
      viewMode,
      pagination,
      baseCurrency,
      availableCurrencies,
      filters,
      hasActiveFilters,
      paymentTimeline,
      currencyBreakdown,
      paymentMethods,
      onCustomerChange,
      applyFilters,
      clearFilters,
      refreshPayments,
      changePage,
      getPageNumbers,
      viewPaymentDetails,
      viewReceivable,
      exportCustomerPayments,
      getMethodClass,
      getMethodIcon,
      formatCurrency,
      formatNumber,
      formatDate
    }
  }
}
</script>

<style scoped>
.customer-payment-container {
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

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-primary {
  background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
  color: white;
  box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(99, 102, 241, 0.4);
}

.btn-outline {
  background: white;
  color: #6366f1;
  border: 2px solid #6366f1;
}

.btn-outline:hover:not(:disabled) {
  background: #6366f1;
  color: white;
}

.btn-outline-sm {
  padding: 0.5rem 1rem;
  font-size: 0.875rem;
}

.btn-secondary {
  background: #64748b;
  color: white;
}

.btn-secondary:hover:not(:disabled) {
  background: #475569;
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

.filter-group.customer-select-group {
  min-width: 300px;
}

.filter-label {
  font-weight: 500;
  color: #374151;
  font-size: 0.875rem;
}

.required {
  color: #ef4444;
}

.customer-select,
.date-input,
.filter-select {
  padding: 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 1rem;
  transition: all 0.3s ease;
  background: white;
}

.customer-select:focus,
.date-input:focus,
.filter-select:focus {
  outline: none;
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.date-range-inputs {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.date-separator {
  color: #64748b;
  font-weight: 500;
}

.filter-actions {
  display: flex;
  gap: 0.75rem;
}

.customer-summary-section {
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
  font-size: 1.25rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin: 0;
}

.customer-info {
  display: flex;
  align-items: center;
  gap: 1rem;
  font-size: 0.875rem;
}

.customer-code {
  background: rgba(255, 255, 255, 0.2);
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-weight: 600;
}

.preferred-currency {
  background: rgba(255, 255, 255, 0.1);
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
}

.currency-summary-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 1.5rem;
  padding: 1.5rem;
}

.currency-summary-card {
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

.currency-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
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

.conversion-indicator {
  color: #6366f1;
  font-size: 1.25rem;
}

.currency-stats {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.stat-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.stat-label {
  font-size: 0.875rem;
  color: #64748b;
}

.stat-value {
  font-weight: 600;
  color: #1e293b;
}

.stat-value.gain {
  color: #059669;
}

.stat-value.loss {
  color: #dc2626;
}

.overall-summary {
  padding: 1.5rem;
  border-top: 1px solid #f1f5f9;
}

.summary-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.5rem;
}

.summary-stat {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: #f8fafc;
  border-radius: 12px;
}

.stat-icon {
  width: 50px;
  height: 50px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  color: white;
}

.stat-icon.total {
  background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
}

.stat-icon.count {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.stat-icon.currency {
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

.stat-icon.period {
  background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
}

.stat-content {
  flex: 1;
}

.stat-content .stat-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1e293b;
  line-height: 1;
}

.stat-content .stat-label {
  color: #64748b;
  font-size: 0.875rem;
  margin-top: 0.25rem;
}

.payments-table-card {
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
  gap: 1rem;
  align-items: center;
}

.view-toggle {
  display: flex;
  background: #f1f5f9;
  border-radius: 8px;
  padding: 0.25rem;
}

.toggle-btn {
  padding: 0.5rem 1rem;
  border: none;
  background: transparent;
  color: #64748b;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
}

.toggle-btn.active {
  background: white;
  color: #6366f1;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.toggle-btn:not(.active):hover {
  color: #1e293b;
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

.payment-row {
  transition: all 0.3s ease;
}

.payment-row:hover {
  background: #f8fafc;
}

.date-wrapper {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #64748b;
}

.payment-id-badge {
  background: #6366f1;
  color: white;
  padding: 0.375rem 0.75rem;
  border-radius: 6px;
  font-weight: 500;
  font-size: 0.875rem;
}

.receivable-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.receivable-id {
  font-weight: 600;
  color: #1e293b;
}

.invoice-id {
  font-size: 0.75rem;
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

.conversion-indicator {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  color: #f59e0b;
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

.exchange-impact {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.impact-label {
  font-size: 0.75rem;
  color: #64748b;
}

.impact-value.gain {
  color: #059669;
  font-weight: 600;
}

.impact-value.loss {
  color: #dc2626;
  font-weight: 600;
}

.no-impact {
  color: #94a3b8;
  font-style: italic;
  font-size: 0.875rem;
}

.status-badge {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.375rem 0.75rem;
  border-radius: 6px;
  font-weight: 500;
  font-size: 0.75rem;
}

.status-badge.completed {
  background: #dcfce7;
  color: #166534;
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

.action-btn.receivable {
  background: #ecfdf5;
  color: #059669;
}

.action-btn.receivable:hover {
  background: #059669;
  color: white;
}

.summary-view {
  padding: 1.5rem;
}

.summary-charts {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
  gap: 2rem;
}

.chart-card {
  background: #f8fafc;
  border-radius: 12px;
  padding: 1.5rem;
  border: 1px solid #e2e8f0;
}

.chart-header {
  margin-bottom: 1.5rem;
}

.chart-title {
  font-size: 1rem;
  font-weight: 600;
  color: #1e293b;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin: 0;
}

.chart-title i {
  color: #6366f1;
}

.timeline-chart,
.currency-chart,
.methods-chart {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.timeline-item,
.currency-item,
.method-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 0.75rem;
  background: white;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
}

.period-label,
.currency-code,
.method-name {
  min-width: 80px;
  font-weight: 600;
  color: #1e293b;
}

.period-bar,
.currency-bar {
  flex: 1;
  height: 8px;
  background: #f1f5f9;
  border-radius: 4px;
  overflow: hidden;
}

.bar-fill {
  height: 100%;
  background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
  transition: width 0.3s ease;
}

.period-amount,
.currency-amount,
.method-amount {
  min-width: 100px;
  text-align: right;
  font-weight: 600;
  color: #1e293b;
}

.currency-percentage {
  font-size: 0.875rem;
  color: #64748b;
}

.method-info {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex: 1;
}

.method-count {
  font-size: 0.875rem;
  color: #64748b;
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

.no-customer-state {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 400px;
}

.no-customer-content {
  text-align: center;
  max-width: 400px;
}

.no-customer-icon {
  font-size: 5rem;
  color: #d1d5db;
  margin-bottom: 2rem;
}

.no-customer-title {
  font-size: 1.5rem;
  font-weight: 600;
  color: #374151;
  margin-bottom: 1rem;
}

.no-customer-message {
  color: #6b7280;
  line-height: 1.6;
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

@media (max-width: 768px) {
  .customer-payment-container {
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

  .page-title {
    font-size: 2rem;
    flex-direction: column;
    gap: 0.5rem;
  }

  .filter-row {
    grid-template-columns: 1fr;
  }

  .currency-summary-grid {
    grid-template-columns: 1fr;
  }

  .summary-stats {
    grid-template-columns: repeat(2, 1fr);
  }

  .summary-charts {
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
}
</style>