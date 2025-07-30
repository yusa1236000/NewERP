<template>
  <div class="payment-detail">
    <!-- Loading State -->
    <div v-if="loading" class="loading-container">
      <div class="loading-spinner">
        <i class="fas fa-spinner fa-spin"></i>
        <p>Loading payment details...</p>
      </div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="error-container">
      <div class="error-message">
        <i class="fas fa-exclamation-triangle"></i>
        <h3>Error Loading Payment</h3>
        <p>{{ error }}</p>
        <button @click="fetchPaymentDetails" class="btn btn-primary">
          Try Again
        </button>
      </div>
    </div>

    <!-- Payment Details -->
    <div v-else-if="payment" class="payment-content">
      <!-- Header -->
      <div class="page-header">
        <div class="header-left">
          <button @click="$router.go(-1)" class="back-btn">
            <i class="fas fa-arrow-left"></i>
          </button>
          <div class="header-info">
            <h1 class="page-title">Payment Details</h1>
            <p class="page-subtitle">Payment #{{ payment.payment_id }}</p>
          </div>
        </div>
        <div class="header-actions">
          <button @click="printPayment" class="btn btn-outline">
            <i class="fas fa-print"></i>
            Print
          </button>
          <button @click="downloadPDF" class="btn btn-outline">
            <i class="fas fa-download"></i>
            Download PDF
          </button>
          <button @click="showDeleteModal = true" class="btn btn-danger">
            <i class="fas fa-trash"></i>
            Delete
          </button>
        </div>
      </div>

      <!-- Stats Grid -->
      <div class="stats-grid">
        <div class="stat-card primary">
          <div class="stat-icon">
            <i class="fas fa-dollar-sign"></i>
          </div>
          <div class="stat-info">
            <div class="stat-value">{{ formatCurrency(payment.amount, payment.payment_currency) }}</div>
            <div class="stat-label">Payment Amount</div>
          </div>
        </div>

        <div class="stat-card" v-if="payment.payment_currency !== baseCurrency">
          <div class="stat-icon">
            <i class="fas fa-exchange-alt"></i>
          </div>
          <div class="stat-info">
            <div class="stat-value">{{ formatCurrency(payment.amount * payment.exchange_rate, baseCurrency) }}</div>
            <div class="stat-label">Base Currency Amount</div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon">
            <i class="fas fa-calendar"></i>
          </div>
          <div class="stat-info">
            <div class="stat-value">{{ formatDate(payment.payment_date) }}</div>
            <div class="stat-label">Payment Date</div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon">
            <i class="fas fa-check-circle"></i>
          </div>
          <div class="stat-info">
            <div class="stat-value">{{ getStatusBadge() }}</div>
            <div class="stat-label">Status</div>
          </div>
        </div>
      </div>

      <!-- Content Grid -->
      <div class="content-grid">
        <!-- Customer Information -->
        <div class="info-card">
          <div class="card-header">
            <h3 class="card-title">
              <i class="fas fa-user"></i>
              Customer Information
            </h3>
            <router-link 
              v-if="payment.customer_receivable?.customer" 
              :to="`/accounting/customers/${payment.customer_receivable.customer.customer_id}`"
              class="view-customer-btn"
            >
              <i class="fas fa-external-link-alt"></i>
              View Customer
            </router-link>
          </div>
          <div class="card-content">
            <div class="customer-info" v-if="payment.customer_receivable?.customer">
              <div class="info-grid">
                <div class="info-item">
                  <span class="info-label">Customer Name</span>
                  <span class="info-value">{{ payment.customer_receivable.customer.name }}</span>
                </div>
                <div class="info-item">
                  <span class="info-label">Customer Code</span>
                  <span class="info-value">#{{ payment.customer_receivable.customer.customer_code }}</span>
                </div>
                <div class="info-item">
                  <span class="info-label">Email</span>
                  <span class="info-value">{{ payment.customer_receivable.customer.email || 'N/A' }}</span>
                </div>
                <div class="info-item">
                  <span class="info-label">Phone</span>
                  <span class="info-value">{{ payment.customer_receivable.customer.phone || 'N/A' }}</span>
                </div>
                <div class="info-item">
                  <span class="info-label">Preferred Currency</span>
                  <span class="info-value">{{ payment.customer_receivable.customer.preferred_currency || baseCurrency }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Payment Information -->
        <div class="info-card">
          <div class="card-header">
            <h3 class="card-title">
              <i class="fas fa-credit-card"></i>
              Payment Information
            </h3>
          </div>
          <div class="card-content">
            <div class="info-grid">
              <div class="info-item">
                <span class="info-label">Payment Method</span>
                <span class="info-value">
                  <span class="payment-method-badge" :class="getMethodClass(payment.payment_method)">
                    {{ payment.payment_method }}
                  </span>
                </span>
              </div>
              <div class="info-item">
                <span class="info-label">Reference Number</span>
                <span class="info-value">{{ payment.reference_number || 'N/A' }}</span>
              </div>
              <div class="info-item">
                <span class="info-label">Payment Date</span>
                <span class="info-value">{{ formatDate(payment.payment_date) }}</span>
              </div>
              <div class="info-item">
                <span class="info-label">Payment Currency</span>
                <span class="info-value">
                  <span class="currency-badge">{{ payment.payment_currency }}</span>
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Related Receivable -->
        <div class="info-card">
          <div class="card-header">
            <h3 class="card-title">
              <i class="fas fa-file-invoice"></i>
              Related Receivable
            </h3>
            <router-link 
              v-if="payment.customer_receivable" 
              :to="`/accounting/customer-receivables/${payment.customer_receivable.receivable_id}`"
              class="view-receivable-btn"
            >
              <i class="fas fa-external-link-alt"></i>
              View Receivable
            </router-link>
          </div>
          <div class="card-content">
            <div class="receivable-info" v-if="payment.customer_receivable">
              <div class="receivable-grid">
                <div class="receivable-item">
                  <label class="receivable-label">Receivable ID</label>
                  <div class="receivable-value">
                    <span class="receivable-id-badge">#{{ payment.customer_receivable.receivable_id }}</span>
                  </div>
                </div>
                
                <div class="receivable-item">
                  <label class="receivable-label">Invoice Number</label>
                  <div class="receivable-value">
                    <span class="invoice-number">#{{ payment.customer_receivable.invoice_id || 'N/A' }}</span>
                  </div>
                </div>
                
                <div class="receivable-item">
                  <label class="receivable-label">Original Amount</label>
                  <div class="receivable-value">
                    <span class="amount-original">{{ formatCurrency(payment.customer_receivable.amount, payment.customer_receivable.currency_code) }}</span>
                  </div>
                </div>
                
                <div class="receivable-item">
                  <label class="receivable-label">Paid Amount</label>
                  <div class="receivable-value">
                    <span class="amount-paid">{{ formatCurrency(payment.customer_receivable.paid_amount, payment.customer_receivable.currency_code) }}</span>
                  </div>
                </div>
                
                <div class="receivable-item">
                  <label class="receivable-label">Current Balance</label>
                  <div class="receivable-value">
                    <span class="amount-balance">{{ formatCurrency(payment.customer_receivable.balance, payment.customer_receivable.currency_code) }}</span>
                  </div>
                </div>
                
                <div class="receivable-item">
                  <label class="receivable-label">Due Date</label>
                  <div class="receivable-value">
                    <span class="due-date" :class="{ 'overdue': isOverdue(payment.customer_receivable.due_date) }">
                      {{ formatDate(payment.customer_receivable.due_date) }}
                      <span v-if="isOverdue(payment.customer_receivable.due_date)" class="overdue-badge">Overdue</span>
                    </span>
                  </div>
                </div>

                <div class="receivable-item">
                  <label class="receivable-label">Receivable Currency</label>
                  <div class="receivable-value">
                    <span class="currency-badge">{{ payment.customer_receivable.currency_code || baseCurrency }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Amount Details -->
        <div class="info-card amount-card">
          <div class="card-header">
            <h3 class="card-title">
              <i class="fas fa-calculator"></i>
              Amount Details
            </h3>
          </div>
          <div class="card-content">
            <div class="amount-breakdown">
              <!-- Payment Amount -->
              <div class="amount-row">
                <span class="amount-label">Payment Amount</span>
                <span class="amount-value primary">
                  {{ formatCurrency(payment.amount, payment.payment_currency) }}
                </span>
              </div>
              
              <!-- Receivable Amount Applied -->
              <div class="amount-row">
                <span class="amount-label">Amount Applied to Receivable</span>
                <span class="amount-value">
                  {{ formatCurrency(payment.receivable_amount, payment.customer_receivable?.currency_code || baseCurrency) }}
                </span>
              </div>
              
              <!-- Base Currency Amount -->
              <div v-if="payment.payment_currency !== baseCurrency" class="amount-row">
                <span class="amount-label">Base Currency Amount ({{ baseCurrency }})</span>
                <span class="amount-value">
                  {{ formatCurrency(payment.amount * payment.exchange_rate, baseCurrency) }}
                </span>
              </div>
              
              <!-- Exchange Rate -->
              <div v-if="payment.exchange_rate && payment.exchange_rate !== 1" class="amount-row">
                <span class="amount-label">Exchange Rate</span>
                <span class="amount-value exchange-rate">
                  1 {{ payment.payment_currency }} = {{ formatNumber(payment.exchange_rate, 6) }} {{ baseCurrency }}
                </span>
              </div>
              
              <!-- Exchange Difference -->
              <div v-if="Math.abs(payment.exchange_difference) > 0.01" class="amount-row">
                <span class="amount-label">Exchange {{ payment.exchange_difference > 0 ? 'Gain' : 'Loss' }}</span>
                <span class="amount-value" :class="payment.exchange_difference > 0 ? 'gain' : 'loss'">
                  {{ formatCurrency(Math.abs(payment.exchange_difference), baseCurrency) }}
                </span>
              </div>

              <!-- Currency Conversion Info -->
              <div v-if="payment.conversion_info" class="conversion-info">
                <div class="conversion-title">Currency Conversion Details</div>
                <div class="conversion-details">
                  <div class="conversion-item">
                    <span>Payment Currency:</span>
                    <span>{{ payment.conversion_info.payment_currency }}</span>
                  </div>
                  <div class="conversion-item">
                    <span>Receivable Currency:</span>
                    <span>{{ payment.conversion_info.receivable_currency }}</span>
                  </div>
                  <div class="conversion-item">
                    <span>Base Currency:</span>
                    <span>{{ payment.conversion_info.base_currency }}</span>
                  </div>
                  <div v-if="payment.conversion_info.has_exchange_difference" class="conversion-item">
                    <span>Exchange Difference:</span>
                    <span>Yes</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Journal Entries -->
        <div v-if="journalEntries.length > 0" class="info-card journal-card">
          <div class="card-header">
            <h3 class="card-title">
              <i class="fas fa-book"></i>
              Related Journal Entries
            </h3>
          </div>
          <div class="card-content">
            <div class="journal-entries">
              <div v-for="entry in journalEntries" :key="entry.journal_id" class="journal-entry">
                <div class="journal-header">
                  <router-link 
                    :to="`/accounting/journal-entries/${entry.journal_id}`"
                    class="journal-number"
                  >
                    #{{ entry.journal_number }}
                  </router-link>
                  <span class="journal-date">{{ formatDate(entry.entry_date) }}</span>
                </div>
                <div class="journal-description">{{ entry.description }}</div>
                <div class="journal-lines" v-if="entry.lines">
                  <div v-for="line in entry.lines" :key="line.line_id" class="journal-line">
                    <span class="account-code">{{ line.account_code }}</span>
                    <span class="account-name">{{ line.account_name }}</span>
                    <span v-if="line.debit_amount > 0" class="debit-amount">
                      Dr. {{ formatCurrency(line.debit_amount, line.currency || baseCurrency) }}
                    </span>
                    <span v-if="line.credit_amount > 0" class="credit-amount">
                      Cr. {{ formatCurrency(line.credit_amount, line.currency || baseCurrency) }}
                    </span>
                    <span v-if="line.foreign_amount && line.currency !== baseCurrency" class="foreign-amount">
                      ({{ formatCurrency(line.foreign_amount, line.currency) }})
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="action-buttons">
        <button @click="$router.push('/accounting/receivable-payments')" class="btn btn-outline">
          <i class="fas fa-list"></i>
          Back to Payments List
        </button>
        <button @click="printPayment" class="btn btn-primary">
          <i class="fas fa-print"></i>
          Print Payment Receipt
        </button>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div v-if="showDeleteModal" class="modal-overlay" @click="showDeleteModal = false">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3 class="modal-title">Delete Payment</h3>
          <button @click="showDeleteModal = false" class="close-btn">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <div class="delete-warning">
            <i class="fas fa-exclamation-triangle"></i>
            <p>Are you sure you want to delete this payment?</p>
            <p><strong>This action cannot be undone.</strong></p>
            <div class="payment-summary">
              <div class="summary-item">
                <span>Payment Amount:</span>
                <span>{{ formatCurrency(payment.amount, payment.payment_currency) }}</span>
              </div>
              <div class="summary-item">
                <span>Customer:</span>
                <span>{{ payment.customer_receivable?.customer?.name }}</span>
              </div>
              <div class="summary-item">
                <span>Reference:</span>
                <span>{{ payment.reference_number }}</span>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button @click="showDeleteModal = false" class="btn btn-outline">
            Cancel
          </button>
          <button @click="deletePayment" class="btn btn-danger" :disabled="deleting">
            {{ deleting ? 'Deleting...' : 'Delete Payment' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'

export default {
  name: 'PaymentDetail',
  setup() {
    const route = useRoute()
    const router = useRouter()
    
    const loading = ref(true)
    const error = ref(null)
    const payment = ref(null)
    const journalEntries = ref([])
    const showDeleteModal = ref(false)
    const deleting = ref(false)
    const baseCurrency = ref('USD')

    const paymentId = computed(() => route.params.id)

    const fetchPaymentDetails = async () => {
      try {
        loading.value = true
        error.value = null
        
        const response = await axios.get(`/accounting/receivable-payments/${paymentId.value}`)
        payment.value = response.data.data
        
        // Fetch related journal entries
        await fetchJournalEntries()
        
      } catch (err) {
        console.error('Error fetching payment details:', err)
        error.value = err.response?.data?.message || 'Payment not found'
      } finally {
        loading.value = false
      }
    }

    const fetchJournalEntries = async () => {
      try {
        const response = await axios.get('/accounting/journal-entries', {
          params: {
            reference_type: 'ReceivablePayment',
            reference_id: paymentId.value
          }
        })
        journalEntries.value = response.data.data || []
      } catch (err) {
        console.error('Error fetching journal entries:', err)
        journalEntries.value = []
      }
    }

    const deletePayment = async () => {
      try {
        deleting.value = true
        await axios.delete(`/accounting/receivable-payments/${paymentId.value}`)
        router.push('/accounting/receivable-payments')
        // Show success message
      } catch (err) {
        console.error('Error deleting payment:', err)
        // Show error message
      } finally {
        deleting.value = false
        showDeleteModal.value = false
      }
    }

    const printPayment = () => {
      window.print()
    }

    const downloadPDF = () => {
      // Implement PDF download functionality
      console.log('Download PDF')
    }

    const getStatusBadge = () => {
      return 'Completed'
    }

    const getMethodClass = (method) => {
      const methodClasses = {
        'Cash': 'cash',
        'Check': 'check',
        'Bank Transfer': 'transfer',
        'Credit Card': 'credit-card',
        'Wire Transfer': 'wire',
        'Online Payment': 'online'
      }
      return methodClasses[method] || 'default'
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
        month: 'long',
        day: 'numeric'
      })
    }

    const isOverdue = (dateString) => {
      if (!dateString) return false
      return new Date(dateString) < new Date()
    }

    onMounted(() => {
      fetchPaymentDetails()
      // Get base currency from config
      baseCurrency.value = window.appConfig?.baseCurrency || 'USD'
    })

    return {
      loading,
      error,
      payment,
      journalEntries,
      showDeleteModal,
      deleting,
      baseCurrency,
      fetchPaymentDetails,
      deletePayment,
      printPayment,
      downloadPDF,
      getStatusBadge,
      getMethodClass,
      formatCurrency,
      formatNumber,
      formatDate,
      isOverdue
    }
  }
}
</script>

<style scoped>
.payment-detail {
  padding: 2rem;
  max-width: 1400px;
  margin: 0 auto;
}

.loading-container,
.error-container {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 400px;
}

.loading-spinner {
  text-align: center;
}

.loading-spinner i {
  font-size: 3rem;
  color: #6366f1;
  margin-bottom: 1rem;
}

.error-message {
  text-align: center;
  color: #ef4444;
}

.error-message i {
  font-size: 3rem;
  margin-bottom: 1rem;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid #f1f5f9;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.back-btn {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 0.75rem;
  color: #64748b;
  cursor: pointer;
  transition: all 0.3s ease;
}

.back-btn:hover {
  background: #6366f1;
  color: white;
  border-color: #6366f1;
}

.page-title {
  font-size: 2rem;
  font-weight: 700;
  color: #1e293b;
  margin: 0;
}

.page-subtitle {
  color: #64748b;
  margin: 0;
  font-size: 1rem;
}

.header-actions {
  display: flex;
  gap: 0.75rem;
}

.btn {
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s ease;
  border: none;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  text-decoration: none;
}

.btn-outline {
  background: white;
  color: #6366f1;
  border: 1px solid #6366f1;
}

.btn-outline:hover {
  background: #6366f1;
  color: white;
}

.btn-primary {
  background: #6366f1;
  color: white;
}

.btn-primary:hover {
  background: #5046e6;
}

.btn-danger {
  background: #ef4444;
  color: white;
}

.btn-danger:hover {
  background: #dc2626;
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
  padding: 1.5rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
  border: 1px solid #f1f5f9;
  transition: all 0.3s ease;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
}

.stat-card.primary {
  background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
  color: white;
}

.stat-icon {
  width: 60px;
  height: 60px;
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.2);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
}

.stat-card:not(.primary) .stat-icon {
  background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
  color: white;
}

.stat-info {
  flex: 1;
}

.stat-value {
  font-size: 1.875rem;
  font-weight: 700;
  line-height: 1;
  margin-bottom: 0.25rem;
}

.stat-label {
  font-size: 0.875rem;
  opacity: 0.8;
}

.content-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
  gap: 2rem;
  margin-bottom: 2rem;
}

.info-card {
  background: white;
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
  border: 1px solid #f1f5f9;
  transition: all 0.3s ease;
}

.info-card:hover {
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
}

.card-header {
  padding: 1.5rem;
  border-bottom: 1px solid #f1f5f9;
  background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.card-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #1e293b;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.card-title i {
  color: #6366f1;
}

.view-customer-btn,
.view-receivable-btn {
  background: #f1f5f9;
  color: #6366f1;
  padding: 0.5rem 1rem;
  border-radius: 8px;
  text-decoration: none;
  font-size: 0.875rem;
  font-weight: 500;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.view-customer-btn:hover,
.view-receivable-btn:hover {
  background: #6366f1;
  color: white;
}

.card-content {
  padding: 1.5rem;
}

.info-grid {
  display: grid;
  gap: 1rem;
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.info-label {
  font-weight: 500;
  color: #64748b;
  font-size: 0.875rem;
}

.info-value {
  font-weight: 600;
  color: #1e293b;
}

.payment-method-badge {
  padding: 0.375rem 0.75rem;
  border-radius: 6px;
  font-size: 0.875rem;
  font-weight: 500;
}

.payment-method-badge.cash {
  background: #dcfce7;
  color: #166534;
}

.payment-method-badge.check {
  background: #fef3c7;
  color: #92400e;
}

.payment-method-badge.transfer {
  background: #dbeafe;
  color: #1e40af;
}

.payment-method-badge.credit-card {
  background: #f3e8ff;
  color: #7c3aed;
}

.payment-method-badge.wire {
  background: #ecfdf5;
  color: #059669;
}

.payment-method-badge.online {
  background: #fef2f2;
  color: #dc2626;
}

.payment-method-badge.default {
  background: #f1f5f9;
  color: #475569;
}

.currency-badge {
  background: #6366f1;
  color: white;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 600;
}

.receivable-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}

.receivable-item {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.receivable-label {
  font-size: 0.875rem;
  font-weight: 500;
  color: #64748b;
}

.receivable-value {
  font-weight: 600;
  color: #1e293b;
}

.receivable-id-badge {
  background: #6366f1;
  color: white;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-size: 0.875rem;
}

.invoice-number {
  color: #059669;
  font-weight: 600;
}

.amount-original {
  color: #6366f1;
}

.amount-paid {
  color: #059669;
}

.amount-balance {
  color: #dc2626;
  font-weight: 700;
}

.due-date.overdue {
  color: #dc2626;
}

.overdue-badge {
  background: #dc2626;
  color: white;
  padding: 0.125rem 0.375rem;
  border-radius: 4px;
  font-size: 0.75rem;
  margin-left: 0.5rem;
}

.amount-breakdown {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.amount-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem 0;
  border-bottom: 1px solid #f1f5f9;
}

.amount-row:last-child {
  border-bottom: none;
}

.amount-label {
  font-weight: 500;
  color: #64748b;
}

.amount-value {
  font-weight: 600;
  color: #1e293b;
}

.amount-value.primary {
  color: #6366f1;
  font-size: 1.125rem;
}

.amount-value.gain {
  color: #059669;
}

.amount-value.loss {
  color: #dc2626;
}

.exchange-rate {
  font-family: monospace;
  background: #f8fafc;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
}

.conversion-info {
  margin-top: 1rem;
  padding: 1rem;
  background: #f8fafc;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
}

.conversion-title {
  font-weight: 600;
  color: #1e293b;
  margin-bottom: 0.75rem;
}

.conversion-details {
  display: grid;
  gap: 0.5rem;
}

.conversion-item {
  display: flex;
  justify-content: space-between;
  font-size: 0.875rem;
}

.conversion-item span:first-child {
  color: #64748b;
}

.conversion-item span:last-child {
  font-weight: 500;
  color: #1e293b;
}

.journal-entries {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.journal-entry {
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 1rem;
  background: #f8fafc;
}

.journal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.5rem;
}

.journal-number {
  color: #6366f1;
  font-weight: 600;
  text-decoration: none;
}

.journal-number:hover {
  text-decoration: underline;
}

.journal-date {
  color: #64748b;
  font-size: 0.875rem;
}

.journal-description {
  color: #1e293b;
  margin-bottom: 0.75rem;
}

.journal-lines {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.journal-line {
  display: grid;
  grid-template-columns: auto 1fr auto auto auto;
  gap: 1rem;
  align-items: center;
  padding: 0.5rem;
  background: white;
  border-radius: 4px;
  font-size: 0.875rem;
}

.account-code {
  font-weight: 600;
  color: #6366f1;
}

.account-name {
  color: #1e293b;
}

.debit-amount {
  color: #059669;
  font-weight: 500;
}

.credit-amount {
  color: #dc2626;
  font-weight: 500;
}

.foreign-amount {
  color: #64748b;
  font-style: italic;
}

.action-buttons {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
  margin-top: 2rem;
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

.payment-summary {
  background: #f8fafc;
  border-radius: 8px;
  padding: 1rem;
  margin-top: 1rem;
}

.summary-item {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.5rem;
}

.summary-item:last-child {
  margin-bottom: 0;
}

.summary-item span:first-child {
  color: #64748b;
}

.summary-item span:last-child {
  font-weight: 600;
  color: #1e293b;
}

.modal-footer {
  padding: 1.5rem;
  border-top: 1px solid #f1f5f9;
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
}

@media (max-width: 768px) {
  .payment-detail {
    padding: 1rem;
  }

  .page-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }

  .header-actions {
    width: 100%;
    justify-content: flex-start;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }

  .content-grid {
    grid-template-columns: 1fr;
  }

  .journal-line {
    grid-template-columns: 1fr;
    gap: 0.5rem;
  }

  .action-buttons {
    flex-direction: column;
  }

  .modal-content {
    margin: 1rem;
    width: calc(100% - 2rem);
  }
}

@media print {
  .page-header,
  .action-buttons,
  .header-actions {
    display: none;
  }

  .payment-detail {
    padding: 0;
  }

  .info-card {
    box-shadow: none;
    border: 1px solid #e2e8f0;
    break-inside: avoid;
  }
}
</style>