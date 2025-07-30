<template>
  <div class="receivable-detail">
    <!-- Loading State -->
    <div v-if="loading" class="loading-state">
      <div class="loading-spinner"></div>
      <p>Loading receivable details...</p>
    </div>

    <!-- Main Content -->
    <div v-else-if="receivable" class="detail-content">
      <!-- Header Section -->
      <div class="page-header">
        <div class="header-content">
          <div class="header-left">
            <h1 class="page-title">
              <i class="fas fa-receipt"></i>
              Receivable Details
            </h1>
            <p class="page-subtitle">
              {{ receivable.customer?.name }} - Invoice #{{ receivable.sales_invoice?.invoice_number }}
            </p>
          </div>
          <div class="header-actions">
            <router-link to="/accounting/receivables" class="btn btn-ghost">
              <i class="fas fa-arrow-left"></i>
              Back to List
            </router-link>
            <select v-model="displayCurrency" @change="loadReceivableDetails" class="currency-selector">
              <option value="">Original Currency ({{ receivable.currency_code }})</option>
              <option v-for="currency in availableCurrencies" :key="currency" :value="currency">
                Convert to {{ currency }}
              </option>
            </select>
            <button @click="printReceivable" class="btn btn-outline">
              <i class="fas fa-print"></i>
              Print
            </button>
          </div>
        </div>
      </div>

      <!-- Status Alert -->
      <div class="status-alert" :class="getStatusAlertClass(receivable.status)">
        <div class="alert-content">
          <i :class="getStatusIcon(receivable.status)"></i>
          <div>
            <h4>{{ getStatusMessage(receivable.status) }}</h4>
            <p>{{ getStatusDescription(receivable.status) }}</p>
          </div>
        </div>
        <div class="alert-actions">
          <button 
            v-if="receivable.status !== 'Paid'" 
            @click="openAddPaymentModal"
            class="btn btn-sm btn-primary"
          >
            <i class="fas fa-plus"></i>
            Add Payment
          </button>
          <button @click="viewStatement" class="btn btn-sm btn-outline">
            <i class="fas fa-file-alt"></i>
            View Statement
          </button>
        </div>
      </div>

      <!-- Currency Conversion Notice -->
      <div v-if="displayCurrency && displayCurrency !== receivable.currency_code" class="conversion-notice">
        <div class="notice-content">
          <i class="fas fa-exchange-alt"></i>
          <div>
            <h4>Currency Conversion Applied</h4>
            <p>Amounts are converted from {{ receivable.currency_code }} to {{ displayCurrency }}</p>
          </div>
        </div>
      </div>

      <!-- Main Content Grid -->
      <div class="content-grid">
        <!-- Left Column -->
        <div class="left-column">
          <!-- Receivable Information -->
          <div class="info-card">
            <div class="card-header">
              <h3>
                <i class="fas fa-info-circle"></i>
                Receivable Information
              </h3>
              <div class="card-actions">
                <router-link 
                  :to="`/accounting/receivables/${receivable.receivable_id}/edit`"
                  class="btn btn-sm btn-outline"
                >
                  <i class="fas fa-edit"></i>
                  Edit
                </router-link>
              </div>
            </div>
            <div class="card-content">
              <div class="info-grid">
                <div class="info-item">
                  <span class="label">Receivable ID</span>
                  <span class="value">#{{ receivable.receivable_id }}</span>
                </div>
                
                <div class="info-item">
                  <span class="label">Invoice Number</span>
                  <span class="value">#{{ receivable.sales_invoice?.invoice_number || receivable.invoice_id }}</span>
                </div>
                
                <div class="info-item">
                  <span class="label">Original Amount</span>
                  <span class="value amount">
                    {{ formatCurrencyWithCode(receivable.display_amount || receivable.amount, displayCurrency || receivable.currency_code) }}
                  </span>
                </div>
                
                <div class="info-item">
                  <span class="label">Paid Amount</span>
                  <span class="value amount paid">
                    {{ formatCurrencyWithCode(receivable.display_paid_amount || receivable.paid_amount, displayCurrency || receivable.currency_code) }}
                  </span>
                </div>
                
                <div class="info-item">
                  <span class="label">Outstanding Balance</span>
                  <span class="value amount outstanding">
                    {{ formatCurrencyWithCode(receivable.display_balance || receivable.balance, displayCurrency || receivable.currency_code) }}
                  </span>
                </div>
                
                <div class="info-item">
                  <span class="label">Due Date</span>
                  <span class="value" :class="{ overdue: isOverdue(receivable.due_date) }">
                    {{ formatDate(receivable.due_date) }}
                  </span>
                </div>
                
                <div class="info-item">
                  <span class="label">Status</span>
                  <span class="status-badge" :class="receivable.status.toLowerCase()">
                    {{ receivable.status }}
                  </span>
                </div>

                <div class="info-item">
                  <span class="label">Currency</span>
                  <span class="value">{{ receivable.currency_code }}</span>
                </div>

                <div v-if="receivable.exchange_rate && receivable.currency_code !== receivable.base_currency" class="info-item">
                  <span class="label">Exchange Rate</span>
                  <span class="value">1 {{ receivable.currency_code }} = {{ receivable.exchange_rate }} {{ receivable.base_currency }}</span>
                </div>
                
                <div class="info-item">
                  <span class="label">Created Date</span>
                  <span class="value">{{ formatDate(receivable.created_at) }}</span>
                </div>
                
                <div class="info-item">
                  <span class="label">Last Updated</span>
                  <span class="value">{{ formatDate(receivable.updated_at) }}</span>
                </div>
              </div>
              
              <div v-if="receivable.notes" class="notes-section">
                <h4>Notes</h4>
                <p>{{ receivable.notes }}</p>
              </div>
            </div>
          </div>

          <!-- Customer Information -->
          <div class="info-card">
            <div class="card-header">
              <h3>
                <i class="fas fa-user"></i>
                Customer Information
              </h3>
              <div class="card-actions">
                <router-link 
                  :to="`/customers/${receivable.customer_id}`"
                  class="btn btn-sm btn-ghost"
                >
                  <i class="fas fa-external-link-alt"></i>
                  View Customer
                </router-link>
              </div>
            </div>
            <div class="card-content">
              <div class="customer-info">
                <div class="customer-header">
                  <h4>{{ receivable.customer?.name }}</h4>
                  <span class="customer-code">{{ receivable.customer?.customer_code }}</span>
                </div>
                <div class="customer-details">
                  <div class="detail-item">
                    <i class="fas fa-envelope"></i>
                    <span>{{ receivable.customer?.email || 'No email' }}</span>
                  </div>
                  <div class="detail-item">
                    <i class="fas fa-phone"></i>
                    <span>{{ receivable.customer?.phone || 'No phone' }}</span>
                  </div>
                  <div class="detail-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <span>{{ receivable.customer?.address || 'No address' }}</span>
                  </div>
                  <div class="detail-item">
                    <i class="fas fa-building"></i>
                    <span>{{ receivable.customer?.company || 'No company' }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Right Column -->
        <div class="right-column">
          <!-- Invoice Information -->
          <div class="info-card">
            <div class="card-header">
              <h3>
                <i class="fas fa-file-invoice"></i>
                Related Invoice
              </h3>
              <div class="card-actions">
                <router-link 
                  v-if="receivable.sales_invoice"
                  :to="`/sales/invoices/${receivable.invoice_id}`"
                  class="btn btn-sm btn-ghost"
                >
                  <i class="fas fa-external-link-alt"></i>
                  View Invoice
                </router-link>
              </div>
            </div>
            <div class="card-content">
              <div v-if="receivable.sales_invoice" class="invoice-info">
                <div class="info-item">
                  <span class="label">Invoice Number</span>
                  <span class="value">#{{ receivable.sales_invoice.invoice_number }}</span>
                </div>
                <div class="info-item">
                  <span class="label">Invoice Date</span>
                  <span class="value">{{ formatDate(receivable.sales_invoice.invoice_date) }}</span>
                </div>
                <div class="info-item">
                  <span class="label">Invoice Total</span>
                  <span class="value">{{ formatCurrency(receivable.sales_invoice.total_amount) }}</span>
                </div>
                <div class="info-item">
                  <span class="label">Invoice Status</span>
                  <span class="status-badge" :class="receivable.sales_invoice.status?.toLowerCase()">
                    {{ receivable.sales_invoice.status }}
                  </span>
                </div>
              </div>
              <div v-else class="no-invoice">
                <p>No invoice information available</p>
              </div>
            </div>
          </div>

          <!-- Payment History -->
          <div class="info-card">
            <div class="card-header">
              <h3>
                <i class="fas fa-history"></i>
                Payment History
              </h3>
              <div class="card-actions">
                <button 
                  v-if="receivable.status !== 'Paid'"
                  @click="openAddPaymentModal"
                  class="btn btn-sm btn-primary"
                >
                  <i class="fas fa-plus"></i>
                  Add Payment
                </button>
              </div>
            </div>
            <div class="card-content">
              <div v-if="receivable.receivable_payments && receivable.receivable_payments.length > 0" class="payments-list">
                <div 
                  v-for="payment in receivable.receivable_payments" 
                  :key="payment.payment_id"
                  class="payment-item"
                >
                  <div class="payment-info">
                    <div class="payment-header">
                      <span class="payment-amount">
                        {{ formatCurrencyWithCode(payment.display_amount || payment.amount, displayCurrency || payment.payment_currency || receivable.currency_code) }}
                      </span>
                      <span class="payment-date">{{ formatDate(payment.payment_date) }}</span>
                    </div>
                    <div class="payment-details">
                      <span class="payment-method">{{ payment.payment_method }}</span>
                      <span v-if="payment.reference_number" class="payment-ref">
                        Ref: {{ payment.reference_number }}
                      </span>
                    </div>
                  </div>
                  <div class="payment-actions">
                    <router-link 
                      :to="`/accounting/payments/${payment.payment_id}`"
                      class="btn btn-xs btn-ghost"
                    >
                      <i class="fas fa-eye"></i>
                    </router-link>
                  </div>
                </div>
              </div>
              <div v-else class="no-payments">
                <i class="fas fa-money-bill"></i>
                <p>No payments recorded</p>
              </div>
            </div>
          </div>

          <!-- Quick Actions -->
          <div class="info-card">
            <div class="card-header">
              <h3>
                <i class="fas fa-bolt"></i>
                Quick Actions
              </h3>
            </div>
            <div class="card-content">
              <div class="action-buttons">
                <button 
                  v-if="receivable.status !== 'Paid'"
                  @click="openAddPaymentModal"
                  class="action-button"
                >
                  <i class="fas fa-plus"></i>
                  <span>Add Payment</span>
                </button>
                
                <button @click="viewStatement" class="action-button">
                  <i class="fas fa-file-alt"></i>
                  <span>View Statement</span>
                </button>
                
                <button @click="sendReminder" class="action-button">
                  <i class="fas fa-envelope"></i>
                  <span>Send Reminder</span>
                </button>
                
                <button @click="printReceivable" class="action-button">
                  <i class="fas fa-print"></i>
                  <span>Print Details</span>
                </button>

                <button @click="viewCustomerTransactions" class="action-button">
                  <i class="fas fa-list"></i>
                  <span>Customer Transactions</span>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Error State -->
    <div v-else class="error-state">
      <i class="fas fa-exclamation-triangle"></i>
      <h3>Receivable Not Found</h3>
      <p>The requested receivable could not be found.</p>
      <router-link to="/accounting/receivables" class="btn btn-primary">
        Back to Receivables
      </router-link>
    </div>

    <!-- Add Payment Modal -->
    <div v-if="showAddPaymentModal" class="modal-overlay" @click="showAddPaymentModal = false">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3>
            <i class="fas fa-money-bill"></i>
            Add Payment
          </h3>
          <button @click="showAddPaymentModal = false" class="close-btn">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <!-- Payment Form -->
          <form @submit.prevent="submitPayment" class="payment-form">
            <!-- Payment Amount -->
            <div class="form-group">
              <label class="form-label required">Payment Amount</label>
              <div class="amount-input-group">
                <span class="currency-prefix">{{ getCurrencySymbol(paymentForm.payment_currency) }}</span>
                <input 
                  type="number" 
                  v-model="paymentForm.amount"
                  class="form-input amount-input"
                  :max="receivable.display_balance || receivable.balance"
                  min="0.01"
                  step="0.01"
                  required
                  :placeholder="formatCurrency(receivable.display_balance || receivable.balance)"
                >
              </div>
              <small class="form-help">
                Outstanding balance: {{ formatCurrencyWithCode(receivable.display_balance || receivable.balance, displayCurrency || receivable.currency_code) }}
              </small>
            </div>

            <!-- Payment Currency -->
            <div class="form-group">
              <label class="form-label required">Payment Currency</label>
              <select v-model="paymentForm.payment_currency" class="form-input" required>
                <option :value="receivable.currency_code">{{ receivable.currency_code }} (Original)</option>
                <option v-if="displayCurrency && displayCurrency !== receivable.currency_code" :value="displayCurrency">
                  {{ displayCurrency }} (Display)
                </option>
                <option v-for="currency in availableCurrencies" :key="currency" :value="currency">
                  {{ currency }}
                </option>
              </select>
            </div>

            <!-- Payment Date -->
            <div class="form-group">
              <label class="form-label required">Payment Date</label>
              <input 
                type="date" 
                v-model="paymentForm.payment_date"
                class="form-input"
                :max="new Date().toISOString().split('T')[0]"
                required
              >
            </div>

            <!-- Payment Method -->
            <div class="form-group">
              <label class="form-label required">Payment Method</label>
              <select v-model="paymentForm.payment_method" class="form-input" required>
                <option value="">Select Payment Method</option>
                <option value="Cash">Cash</option>
                <option value="Check">Check</option>
                <option value="Bank Transfer">Bank Transfer</option>
                <option value="Credit Card">Credit Card</option>
                <option value="Online Payment">Online Payment</option>
                <option value="Wire Transfer">Wire Transfer</option>
                <option value="Other">Other</option>
              </select>
            </div>

            <!-- Reference Number -->
            <div class="form-group">
              <label class="form-label">Reference Number</label>
              <input 
                type="text" 
                v-model="paymentForm.reference_number"
                class="form-input"
                placeholder="Transaction ID, Check Number, etc."
                maxlength="50"
              >
            </div>

            <!-- Exchange Rate (if different currency) -->
            <div v-if="paymentForm.payment_currency !== receivable.currency_code" class="form-group">
              <label class="form-label">Exchange Rate</label>
              <div class="exchange-rate-group">
                <input 
                  type="number" 
                  v-model="paymentForm.exchange_rate"
                  class="form-input"
                  min="0.000001"
                  step="0.000001"
                  placeholder="Auto-calculate if left empty"
                >
                <small class="form-help">
                  1 {{ paymentForm.payment_currency }} = ? {{ receivable.currency_code }}
                </small>
              </div>
            </div>

            <!-- Payment Notes -->
            <div class="form-group">
              <label class="form-label">Notes</label>
              <textarea 
                v-model="paymentForm.notes"
                class="form-input form-textarea"
                rows="3"
                placeholder="Additional notes about this payment..."
                maxlength="500"
              ></textarea>
            </div>

            <!-- Payment Summary -->
            <div class="payment-summary">
              <h4>Payment Summary</h4>
              <div class="summary-grid">
                <div class="summary-item">
                  <span class="label">Payment Amount:</span>
                  <span class="value">{{ formatCurrencyWithCode(paymentForm.amount, paymentForm.payment_currency) }}</span>
                </div>
                <div v-if="paymentForm.payment_currency !== receivable.currency_code" class="summary-item">
                  <span class="label">Converted Amount:</span>
                  <span class="value">{{ calculateConvertedAmount() }}</span>
                </div>
                <div class="summary-item">
                  <span class="label">Remaining Balance:</span>
                  <span class="value balance">{{ calculateRemainingBalance() }}</span>
                </div>
              </div>
            </div>

            <!-- Form Actions -->
            <div class="form-actions">
              <button 
                type="button" 
                @click="showAddPaymentModal = false" 
                class="btn btn-ghost"
                :disabled="submittingPayment"
              >
                Cancel
              </button>
              <button 
                type="submit" 
                class="btn btn-primary"
                :disabled="!isPaymentFormValid || submittingPayment"
              >
                <i v-if="submittingPayment" class="fas fa-spinner fa-spin"></i>
                <i v-else class="fas fa-check"></i>
                {{ submittingPayment ? 'Processing...' : 'Record Payment' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'ReceivableDetail',
  data() {
    return {
      receivable: null,
      loading: false,
      showAddPaymentModal: false,
      submittingPayment: false,
      displayCurrency: '',
      availableCurrencies: ['USD', 'EUR', 'GBP', 'JPY', 'CAD', 'AUD'],
      paymentForm: {
        amount: '',
        payment_currency: '',
        payment_date: new Date().toISOString().split('T')[0],
        payment_method: '',
        reference_number: '',
        exchange_rate: '',
        notes: ''
      }
    }
  },
  
  computed: {
    isPaymentFormValid() {
      return this.paymentForm.amount > 0 &&
             this.paymentForm.payment_currency &&
             this.paymentForm.payment_date &&
             this.paymentForm.payment_method &&
             parseFloat(this.paymentForm.amount) <= parseFloat(this.receivable?.display_balance || this.receivable?.balance || 0)
    }
  },
  
  async mounted() {
    await this.loadReceivableDetails()
  },
  
  watch: {
    displayCurrency() {
      this.loadReceivableDetails()
    }
  },
  
  methods: {
    async loadReceivableDetails() {
      this.loading = true
      try {
        const params = {}
        if (this.displayCurrency) {
          params.display_currency = this.displayCurrency
        }
        
        const response = await axios.get(`/accounting/customer-receivables/${this.$route.params.id}`, { params })
        this.receivable = response.data.data
        
        // Initialize payment form currency
        this.paymentForm.payment_currency = this.receivable.currency_code
        
      } catch (error) {
        console.error('Error loading receivable details:', error)
        this.$toast?.error('Failed to load receivable details')
      } finally {
        this.loading = false
      }
    },

    async submitPayment() {
      if (!this.isPaymentFormValid) return
      
      this.submittingPayment = true
      try {
        const paymentData = {
          receivable_id: this.receivable.receivable_id,
          amount: parseFloat(this.paymentForm.amount),
          payment_currency: this.paymentForm.payment_currency,
          payment_date: this.paymentForm.payment_date,
          payment_method: this.paymentForm.payment_method,
          reference_number: this.paymentForm.reference_number || null,
          notes: this.paymentForm.notes || null
        }

        // Add exchange rate if payment currency is different from receivable currency
        if (this.paymentForm.payment_currency !== this.receivable.currency_code) {
          if (this.paymentForm.exchange_rate) {
            paymentData.exchange_rate = parseFloat(this.paymentForm.exchange_rate)
          }
        }

        await axios.post('/accounting/receivable-payments', paymentData)
        
        this.$toast?.success('Payment recorded successfully')
        this.showAddPaymentModal = false
        this.resetPaymentForm()
        
        // Reload receivable details to reflect the new payment
        await this.loadReceivableDetails()
        
      } catch (error) {
        console.error('Error recording payment:', error)
        const errorMessage = error.response?.data?.message || 'Failed to record payment'
        this.$toast?.error(errorMessage)
      } finally {
        this.submittingPayment = false
      }
    },

    resetPaymentForm() {
      this.paymentForm = {
        amount: '',
        payment_currency: this.receivable?.currency_code || '',
        payment_date: new Date().toISOString().split('T')[0],
        payment_method: '',
        reference_number: '',
        exchange_rate: '',
        notes: ''
      }
    },

    calculateConvertedAmount() {
      if (!this.paymentForm.amount || this.paymentForm.payment_currency === this.receivable.currency_code) {
        return this.formatCurrencyWithCode(this.paymentForm.amount, this.receivable.currency_code)
      }

      // Simple conversion calculation (in real app, use actual exchange rates)
      const rate = this.paymentForm.exchange_rate || 1
      const convertedAmount = parseFloat(this.paymentForm.amount) * rate
      
      return this.formatCurrencyWithCode(convertedAmount, this.receivable.currency_code)
    },

    calculateRemainingBalance() {
      const currentBalance = parseFloat(this.receivable?.display_balance || this.receivable?.balance || 0)
      let paymentAmount = parseFloat(this.paymentForm.amount) || 0

      // Convert payment amount to receivable currency if different
      if (this.paymentForm.payment_currency !== this.receivable.currency_code && this.paymentForm.exchange_rate) {
        paymentAmount = paymentAmount * parseFloat(this.paymentForm.exchange_rate)
      }

      const remainingBalance = currentBalance - paymentAmount
      const currency = this.displayCurrency || this.receivable.currency_code
      
      return this.formatCurrencyWithCode(Math.max(0, remainingBalance), currency)
    },

    getCurrencySymbol(currencyCode) {
      const symbols = {
        'USD': '$',
        'EUR': '€',
        'GBP': '£',
        'JPY': '¥',
        'CAD': 'C$',
        'AUD': 'A$'
      }
      return symbols[currencyCode] || currencyCode
    },

    openAddPaymentModal() {
      this.resetPaymentForm()
      this.showAddPaymentModal = true
    },

    async viewStatement() {
      try {
        const params = {}
        if (this.displayCurrency) {
          params.display_currency = this.displayCurrency
        }
        
        await axios.get(`/accounting/receivables/${this.receivable.receivable_id}/statement`, { params })
        
        // Open statement in new window or navigate to statement page
        this.$router.push({
          path: `/accounting/receivables/${this.receivable.receivable_id}/statement`,
          query: params
        })
      } catch (error) {
        console.error('Error loading statement:', error)
        this.$toast?.error('Failed to load statement')
      }
    },

    viewCustomerTransactions() {
      this.$router.push({
        path: '/accounting/customer-transactions',
        query: { 
          customer_id: this.receivable.customer_id,
          display_currency: this.displayCurrency
        }
      })
    },
    
    async sendReminder() {
      try {
        await axios.post(`/customers/${this.receivable.customer_id}/send-payment-reminder`)
        this.$toast?.success('Payment reminder sent successfully')
      } catch (error) {
        console.error('Error sending reminder:', error)
        this.$toast?.error('Failed to send payment reminder')
      }
    },
    
    printReceivable() {
      window.print()
    },
    
    getStatusAlertClass(status) {
      switch (status) {
        case 'Paid': return 'alert-success'
        case 'Outstanding': return 'alert-warning'
        case 'Overdue': return 'alert-danger'
        case 'Partial': return 'alert-info'
        default: return 'alert-info'
      }
    },
    
    getStatusIcon(status) {
      switch (status) {
        case 'Paid': return 'fas fa-check-circle'
        case 'Outstanding': return 'fas fa-clock'
        case 'Overdue': return 'fas fa-exclamation-triangle'
        case 'Partial': return 'fas fa-info-circle'
        default: return 'fas fa-info-circle'
      }
    },
    
    getStatusMessage(status) {
      switch (status) {
        case 'Paid': return 'Payment Completed'
        case 'Outstanding': return 'Payment Outstanding'
        case 'Overdue': return 'Payment Overdue'
        case 'Partial': return 'Partial Payment Received'
        default: return 'Status Unknown'
      }
    },
    
    getStatusDescription(status) {
      switch (status) {
        case 'Paid': return 'This receivable has been fully paid'
        case 'Outstanding': return 'Payment is due and awaiting settlement'
        case 'Overdue': return 'Payment is past due date and requires immediate attention'
        case 'Partial': return 'Partial payment has been received, balance outstanding'
        default: return 'Please check the receivable status'
      }
    },
    
    isOverdue(dueDate) {
      return new Date(dueDate) < new Date()
    },
    
    formatDate(date) {
      return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
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
.receivable-detail {
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
  align-items: center;
}

.currency-selector {
  padding: 0.5rem;
  border: 1px solid var(--gray-300);
  border-radius: 6px;
  background: white;
  font-size: 0.875rem;
}

/* Status Alert */
.status-alert {
  border-radius: 12px;
  padding: 1.5rem;
  margin-bottom: 2rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.alert-success {
  background: #dcfce7;
  border: 1px solid #16a34a;
  color: #15803d;
}

.alert-warning {
  background: #fef3c7;
  border: 1px solid #d97706;
  color: #92400e;
}

.alert-danger {
  background: #fee2e2;
  border: 1px solid #dc2626;
  color: #b91c1c;
}

.alert-info {
  background: #dbeafe;
  border: 1px solid #2563eb;
  color: #1d4ed8;
}

.alert-content {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.alert-content i {
  font-size: 1.5rem;
}

.alert-content h4 {
  margin-bottom: 0.25rem;
}

.alert-actions {
  display: flex;
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

/* Content Grid */
.content-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 2rem;
}

@media (max-width: 1024px) {
  .content-grid {
    grid-template-columns: 1fr;
  }
}

/* Info Cards */
.info-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  margin-bottom: 2rem;
  overflow: hidden;
}

.card-header {
  padding: 1.5rem;
  border-bottom: 1px solid var(--gray-200);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.card-header h3 {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-size: 1.125rem;
  font-weight: 600;
  color: var(--gray-800);
}

.card-header h3 i {
  color: var(--primary-color);
}

.card-actions {
  display: flex;
  gap: 0.5rem;
}

.card-content {
  padding: 1.5rem;
}

/* Info Grid */
.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1rem;
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.info-item .label {
  font-size: 0.875rem;
  color: var(--gray-600);
  font-weight: 500;
}

.info-item .value {
  font-weight: 600;
  color: var(--gray-800);
}

.info-item .value.amount {
  font-size: 1.125rem;
}

.info-item .value.outstanding {
  color: var(--warning-color);
}

.info-item .value.paid {
  color: var(--success-color);
}

.info-item .value.overdue {
  color: var(--danger-color);
}

/* Status Badge */
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

/* Customer Info */
.customer-info {
  text-align: center;
}

.customer-header {
  margin-bottom: 1rem;
}

.customer-header h4 {
  font-size: 1.25rem;
  margin-bottom: 0.25rem;
  color: var(--gray-800);
}

.customer-code {
  color: var(--gray-600);
  font-size: 0.875rem;
}

.customer-details {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  text-align: left;
}

.detail-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  color: var(--gray-600);
}

.detail-item i {
  color: var(--primary-color);
  width: 16px;
}

/* Payments List */
.payments-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.payment-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
  background: var(--gray-50);
  border-radius: 8px;
  border-left: 4px solid var(--success-color);
}

.payment-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.25rem;
}

.payment-amount {
  font-weight: 600;
  color: var(--success-color);
}

.payment-date {
  font-size: 0.875rem;
  color: var(--gray-600);
}

.payment-details {
  display: flex;
  gap: 1rem;
  font-size: 0.875rem;
  color: var(--gray-600);
}

.no-payments {
  text-align: center;
  padding: 2rem;
  color: var(--gray-500);
}

.no-payments i {
  font-size: 2rem;
  margin-bottom: 1rem;
}

/* Action Buttons */
.action-buttons {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 1rem;
}

.action-button {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  padding: 1rem;
  background: var(--gray-50);
  border: 1px solid var(--gray-200);
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
  text-decoration: none;
  color: var(--gray-700);
}

.action-button:hover {
  background: var(--primary-color);
  color: white;
  border-color: var(--primary-color);
}

.action-button i {
  font-size: 1.25rem;
}

.action-button span {
  font-size: 0.875rem;
  font-weight: 500;
}

/* Loading and Error States */
.loading-state, .error-state {
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

.error-state i {
  font-size: 3rem;
  color: var(--danger-color);
  margin-bottom: 1rem;
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
  border-radius: 12px;
  max-width: 600px;
  width: 90%;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-header {
  padding: 1.5rem;
  border-bottom: 1px solid var(--gray-200);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-header h3 {
  margin: 0;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: var(--gray-800);
}

.close-btn {
  background: none;
  border: none;
  font-size: 1.25rem;
  cursor: pointer;
  color: var(--gray-500);
  width: 32px;
  height: 32px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.close-btn:hover {
  background: var(--gray-100);
  color: var(--gray-700);
}

.modal-body {
  padding: 1.5rem;
}

/* Payment Form Styles */
.payment-form {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-label {
  font-weight: 500;
  color: var(--gray-700);
  font-size: 0.875rem;
}

.form-label.required::after {
  content: ' *';
  color: var(--danger-color);
}

.form-input {
  padding: 0.75rem;
  border: 1px solid var(--gray-300);
  border-radius: 6px;
  font-size: 0.875rem;
  transition: border-color 0.2s;
}

.form-input:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.form-input:invalid {
  border-color: var(--danger-color);
}

.form-textarea {
  resize: vertical;
  min-height: 80px;
}

.amount-input-group {
  position: relative;
  display: flex;
  align-items: center;
}

.currency-prefix {
  position: absolute;
  left: 0.75rem;
  color: var(--gray-600);
  font-weight: 500;
  z-index: 1;
}

.amount-input {
  padding-left: 2.5rem;
}

.exchange-rate-group {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.form-help {
  color: var(--gray-600);
  font-size: 0.75rem;
  margin-top: 0.25rem;
}

/* Payment Summary */
.payment-summary {
  background: var(--gray-50);
  border-radius: 8px;
  padding: 1rem;
  border: 1px solid var(--gray-200);
}

.payment-summary h4 {
  margin-bottom: 1rem;
  color: var(--gray-800);
  font-size: 1rem;
}

.summary-grid {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.summary-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.5rem 0;
}

.summary-item .label {
  color: var(--gray-600);
  font-weight: 500;
}

.summary-item .value {
  font-weight: 600;
  color: var(--gray-800);
}

.summary-item .value.balance {
  color: var(--warning-color);
}

/* Form Actions */
.form-actions {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
  padding-top: 1rem;
  border-top: 1px solid var(--gray-200);
}

.form-actions .btn {
  min-width: 120px;
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

.btn-xs {
  padding: 0.125rem 0.25rem;
  font-size: 0.625rem;
}

.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Responsive Design */
@media (max-width: 768px) {
  .receivable-detail {
    padding: 1rem;
  }
  
  .header-content {
    flex-direction: column;
    gap: 1rem;
    align-items: stretch;
  }
  
  .modal-content {
    width: 95%;
    margin: 1rem;
  }
  
  .form-actions {
    flex-direction: column-reverse;
  }
  
  .form-actions .btn {
    width: 100%;
  }
  
  .info-grid {
    grid-template-columns: 1fr;
  }
  
  .action-buttons {
    grid-template-columns: 1fr;
  }
}
</style>