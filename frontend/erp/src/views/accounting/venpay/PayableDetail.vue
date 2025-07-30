<template>
  <div class="payable-detail-container">
    <!-- Page Header -->
    <div class="page-header">
      <div class="header-content">
        <div class="header-text">
          <h1 class="page-title">
            <i class="fas fa-file-invoice"></i>
            Payable #{{ payable.payable_id }}
          </h1>
          <p class="page-subtitle">
            Vendor payable details with multi-currency information
          </p>
        </div>
        <div class="header-actions">
          <router-link to="/accounting/payables" class="btn btn-outline">
            <i class="fas fa-arrow-left"></i>
            Back to List
          </router-link>
          <router-link v-if="canEdit" :to="`/accounting/payables/${payable.payable_id}/edit`" class="btn btn-outline">
            <i class="fas fa-edit"></i>
            Edit
          </router-link>
          <router-link v-if="canMakePayment" :to="`/accounting/payable-payments/create?payable_id=${payable.payable_id}`" class="btn btn-primary">
            <i class="fas fa-credit-card"></i>
            Record Payment
          </router-link>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="loading-state">
      <div class="loading-spinner"></div>
      <p>Loading payable details...</p>
    </div>

    <!-- Payable Details -->
    <div v-else-if="payable.payable_id" class="payable-details">
      <!-- Status & Summary Cards -->
      <div class="summary-section">
        <div class="status-card" :class="`status-${payable.status}`">
          <div class="status-icon">
            <i :class="getStatusIcon(payable.status)"></i>
          </div>
          <div class="status-content">
            <h3>{{ payable.status }}</h3>
            <p>Current Status</p>
          </div>
        </div>

        <div class="amount-card">
          <div class="amount-header">
            <h4>Amount Details</h4>
            <div class="currency-indicator">
              <span class="currency-badge" :class="`currency-${payable.currency_code}`">
                {{ payable.currency_code }}
              </span>
            </div>
          </div>
          <div class="amount-details">
            <div class="amount-row">
              <span class="label">Original Amount:</span>
              <span class="value amount">{{ formatCurrency(payable.amount, payable.currency_code) }}</span>
            </div>
            <div class="amount-row">
              <span class="label">Paid Amount:</span>
              <span class="value paid">{{ formatCurrency(payable.paid_amount || 0, payable.currency_code) }}</span>
            </div>
            <div class="amount-row balance-row">
              <span class="label">Remaining Balance:</span>
              <span class="value balance" :class="{ 'text-danger': payable.balance > 0 }">
                {{ formatCurrency(payable.balance, payable.currency_code) }}
              </span>
            </div>
          </div>
        </div>

        <!-- Currency Conversion Card -->
        <div v-if="payable.currency_code !== payable.base_currency" class="conversion-card">
          <div class="conversion-header">
            <h4>
              <i class="fas fa-exchange-alt"></i>
              Currency Conversion
            </h4>
            <div class="exchange-rate">
              1 {{ payable.currency_code }} = {{ payable.exchange_rate }} {{ payable.base_currency }}
            </div>
          </div>
          <div class="conversion-details">
            <div class="conversion-row">
              <span class="label">Base Currency Amount:</span>
              <span class="value">{{ formatCurrency(payable.base_currency_amount, payable.base_currency) }}</span>
            </div>
            <div class="conversion-row">
              <span class="label">Base Currency Balance:</span>
              <span class="value">{{ formatCurrency(payable.base_currency_balance, payable.base_currency) }}</span>
            </div>
          </div>
        </div>

        <!-- Alternative Currency Views -->
        <div v-if="alternativeCurrencies.length > 0" class="alternative-currencies">
          <h4>
            <i class="fas fa-coins"></i>
            View in Other Currencies
          </h4>
          <div class="currency-options">
            <button 
              v-for="currency in alternativeCurrencies" 
              :key="currency"
              @click="convertToCurrency(currency)"
              :class="{ active: selectedViewCurrency === currency }"
              class="currency-option"
            >
              {{ currency }}
            </button>
          </div>
          <div v-if="convertedAmounts" class="converted-display">
            <div class="converted-row">
              <span>Amount in {{ selectedViewCurrency }}:</span>
              <span class="converted-amount">{{ formatCurrency(convertedAmounts.amount, selectedViewCurrency) }}</span>
            </div>
            <div class="converted-row">
              <span>Balance in {{ selectedViewCurrency }}:</span>
              <span class="converted-amount">{{ formatCurrency(convertedAmounts.balance, selectedViewCurrency) }}</span>
            </div>
            <div class="conversion-info">
              <small>@ Rate: {{ convertedAmounts.exchange_rate }} ({{ formatDate(convertedAmounts.date) }})</small>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Information Grid -->
      <div class="info-grid">
        <!-- Vendor Information -->
        <div class="info-card">
          <div class="card-header">
            <h3>
              <i class="fas fa-building"></i>
              Vendor Information
            </h3>
          </div>
          <div class="card-content">
            <div class="vendor-profile">
              <div class="vendor-avatar">
                {{ payable.vendor?.name?.charAt(0)?.toUpperCase() || 'V' }}
              </div>
              <div class="vendor-details">
                <h4>{{ payable.vendor?.name || 'Unknown Vendor' }}</h4>
                <p class="vendor-code">{{ payable.vendor?.vendor_code }}</p>
                <div class="vendor-contact">
                  <div v-if="payable.vendor?.email" class="contact-item">
                    <i class="fas fa-envelope"></i>
                    <span>{{ payable.vendor.email }}</span>
                  </div>
                  <div v-if="payable.vendor?.phone" class="contact-item">
                    <i class="fas fa-phone"></i>
                    <span>{{ payable.vendor.phone }}</span>
                  </div>
                  <div v-if="payable.vendor?.preferred_currency" class="contact-item">
                    <i class="fas fa-coins"></i>
                    <span>Preferred Currency: {{ payable.vendor.preferred_currency }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Invoice Information -->
        <div class="info-card">
          <div class="card-header">
            <h3>
              <i class="fas fa-file-invoice-dollar"></i>
              Invoice Details
            </h3>
          </div>
          <div class="card-content">
            <div class="invoice-details">
              <div class="detail-row">
                <span class="label">Invoice Number:</span>
                <span class="value">{{ payable.vendor_invoice?.invoice_number || 'N/A' }}</span>
              </div>
              <div class="detail-row">
                <span class="label">Invoice Date:</span>
                <span class="value">{{ formatDate(payable.vendor_invoice?.invoice_date) }}</span>
              </div>
              <div class="detail-row">
                <span class="label">Due Date:</span>
                <span class="value due-date" :class="getDueDateClass(payable.due_date)">
                  {{ formatDate(payable.due_date) }}
                </span>
              </div>
              <div class="detail-row">
                <span class="label">Invoice Amount:</span>
                <span class="value amount">{{ formatCurrency(payable.vendor_invoice?.total_amount, payable.vendor_invoice?.currency_code) }}</span>
              </div>
              <div class="detail-row">
                <span class="label">Invoice Status:</span>
                <span class="value">
                  <span class="status-badge" :class="`status-${payable.vendor_invoice?.status}`">
                    {{ payable.vendor_invoice?.status || 'Unknown' }}
                  </span>
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Currency Information -->
        <div class="info-card">
          <div class="card-header">
            <h3>
              <i class="fas fa-exchange-alt"></i>
              Currency Details
            </h3>
          </div>
          <div class="card-content">
            <div class="currency-info">
              <div class="detail-row">
                <span class="label">Payable Currency:</span>
                <span class="value">
                  <span class="currency-badge" :class="`currency-${payable.currency_code}`">
                    {{ payable.currency_code }}
                  </span>
                  {{ getCurrencyName(payable.currency_code) }}
                </span>
              </div>
              <div class="detail-row">
                <span class="label">Base Currency:</span>
                <span class="value">{{ payable.base_currency }}</span>
              </div>
              <div v-if="payable.currency_code !== payable.base_currency" class="detail-row">
                <span class="label">Exchange Rate:</span>
                <span class="value">{{ payable.exchange_rate }}</span>
              </div>
              <div class="detail-row">
                <span class="label">Rate Date:</span>
                <span class="value">{{ formatDate(payable.due_date) }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Aging Information -->
        <div class="info-card">
          <div class="card-header">
            <h3>
              <i class="fas fa-clock"></i>
              Aging Analysis
            </h3>
          </div>
          <div class="card-content">
            <div class="aging-info">
              <div class="aging-item" :class="agingStatus.class">
                <div class="aging-icon">
                  <i :class="agingStatus.icon"></i>
                </div>
                <div class="aging-details">
                  <h4>{{ agingStatus.label }}</h4>
                  <p>{{ agingStatus.days }} days {{ agingStatus.status }}</p>
                </div>
              </div>
              <div class="aging-breakdown">
                <div class="breakdown-item">
                  <span class="label">Days Outstanding:</span>
                  <span class="value">{{ Math.abs(agingStatus.days) }} days</span>
                </div>
                <div class="breakdown-item">
                  <span class="label">Due Date:</span>
                  <span class="value">{{ formatDate(payable.due_date) }}</span>
                </div>
                <div class="breakdown-item">
                  <span class="label">Created Date:</span>
                  <span class="value">{{ formatDate(payable.created_at) }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Payment History -->
      <div class="payments-section">
        <div class="section-header">
          <h3>
            <i class="fas fa-credit-card"></i>
            Payment History
          </h3>
          <div class="payment-summary">
            <span class="payment-count">{{ payments.length }} payments</span>
            <span class="payment-total">Total: {{ formatCurrency(totalPaidAmount, payable.currency_code) }}</span>
          </div>
        </div>

        <div v-if="payments.length === 0" class="empty-payments">
          <i class="fas fa-credit-card"></i>
          <h4>No Payments Yet</h4>
          <p>No payments have been recorded for this payable</p>
          <router-link :to="`/accounting/payable-payments/create?payable_id=${payable.payable_id}`" class="btn btn-primary">
            <i class="fas fa-plus"></i>
            Record First Payment
          </router-link>
        </div>

        <div v-else class="payments-table">
          <table class="payments-grid">
            <thead>
              <tr>
                <th>Payment Date</th>
                <th>Amount</th>
                <th>Currency</th>
                <th>Method</th>
                <th>Reference</th>
                <th>Exchange Info</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="payment in payments" :key="payment.payment_id" class="payment-row">
                <td>
                  <div class="payment-date">
                    <i class="fas fa-calendar"></i>
                    {{ formatDate(payment.payment_date) }}
                  </div>
                </td>
                <td>
                  <div class="payment-amount">
                    <span class="amount">{{ formatCurrency(payment.amount, payment.payment_currency) }}</span>
                    <div v-if="payment.payable_amount && payment.payment_currency !== payable.currency_code" class="converted-payment">
                      <small>= {{ formatCurrency(payment.payable_amount, payable.currency_code) }}</small>
                    </div>
                  </div>
                </td>
                <td>
                  <span class="currency-badge" :class="`currency-${payment.payment_currency}`">
                    {{ payment.payment_currency }}
                  </span>
                </td>
                <td>
                  <span class="payment-method">{{ payment.payment_method }}</span>
                </td>
                <td>
                  <span class="reference">{{ payment.reference_number || '-' }}</span>
                </td>
                <td>
                  <div v-if="payment.exchange_rate && payment.exchange_rate !== 1" class="exchange-info">
                    <span class="rate">@ {{ payment.exchange_rate }}</span>
                    <div v-if="payment.exchange_difference !== 0" class="exchange-diff" :class="{ gain: payment.exchange_difference > 0, loss: payment.exchange_difference < 0 }">
                      <i :class="payment.exchange_difference > 0 ? 'fas fa-arrow-up' : 'fas fa-arrow-down'"></i>
                      {{ formatCurrency(Math.abs(payment.exchange_difference), payable.base_currency) }}
                    </div>
                  </div>
                  <span v-else>-</span>
                </td>
                <td>
                  <div class="payment-actions">
                    <button @click="viewPayment(payment)" class="btn btn-ghost btn-sm">
                      <i class="fas fa-eye"></i>
                    </button>
                    <button v-if="canEditPayment(payment)" @click="editPayment(payment)" class="btn btn-ghost btn-sm">
                      <i class="fas fa-edit"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Currency Summary if applicable -->
      <div v-if="payable.currency_summary" class="currency-summary-section">
        <div class="section-header">
          <h3>
            <i class="fas fa-chart-pie"></i>
            Currency Summary
          </h3>
        </div>
        <div class="currency-summary-grid">
          <div class="summary-item">
            <h4>Original Currency</h4>
            <div class="summary-details">
              <div class="currency-display">
                <span class="currency-badge" :class="`currency-${payable.currency_summary.original_currency}`">
                  {{ payable.currency_summary.original_currency }}
                </span>
                <span class="amount">{{ formatCurrency(payable.currency_summary.amounts.original.amount, payable.currency_summary.original_currency) }}</span>
              </div>
              <div class="balance-display">
                Balance: {{ formatCurrency(payable.currency_summary.amounts.original.balance, payable.currency_summary.original_currency) }}
              </div>
            </div>
          </div>
          <div class="summary-item">
            <h4>Base Currency</h4>
            <div class="summary-details">
              <div class="currency-display">
                <span class="currency-badge" :class="`currency-${payable.currency_summary.base_currency}`">
                  {{ payable.currency_summary.base_currency }}
                </span>
                <span class="amount">{{ formatCurrency(payable.currency_summary.amounts.base_currency.amount, payable.currency_summary.base_currency) }}</span>
              </div>
              <div class="balance-display">
                Balance: {{ formatCurrency(payable.currency_summary.amounts.base_currency.balance, payable.currency_summary.base_currency) }}
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Actions Section -->
      <div class="actions-section">
        <div class="action-group">
          <h4>Available Actions</h4>
          <div class="action-buttons">
            <router-link v-if="canEdit" :to="`/accounting/payables/${payable.payable_id}/edit`" class="btn btn-outline">
              <i class="fas fa-edit"></i>
              Edit Payable
            </router-link>
            <router-link v-if="canMakePayment" :to="`/accounting/payables/${payable.payable_id}/payment`" class="btn btn-primary">
              <i class="fas fa-credit-card"></i>
              Record Payment
            </router-link>
            <button @click="generateReport" class="btn btn-outline">
              <i class="fas fa-file-pdf"></i>
              Generate Report
            </button>
            <button @click="exportData" class="btn btn-outline">
              <i class="fas fa-download"></i>
              Export Data
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Error State -->
    <div v-else class="error-state">
      <i class="fas fa-exclamation-triangle"></i>
      <h3>Payable Not Found</h3>
      <p>The requested payable could not be found or you don't have permission to view it.</p>
      <router-link to="/accounting/vendor-payables" class="btn btn-primary">
        Back to Payables List
      </router-link>
    </div>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'PayableDetail',
  props: {
    payableId: {
      type: [String, Number],
      required: true
    }
  },
  
  data() {
    return {
      loading: false,
      payable: {},
      payments: [],
      selectedViewCurrency: '',
      convertedAmounts: null,
      alternativeCurrencies: ['USD', 'EUR', 'GBP', 'JPY', 'SGD', 'AUD'],
      currencyNames: {
        'USD': 'US Dollar',
        'EUR': 'Euro',
        'GBP': 'British Pound',
        'JPY': 'Japanese Yen',
        'CNY': 'Chinese Yuan',
        'IDR': 'Indonesian Rupiah',
        'SGD': 'Singapore Dollar',
        'AUD': 'Australian Dollar',
        'CAD': 'Canadian Dollar',
        'CHF': 'Swiss Franc',
        'MYR': 'Malaysian Ringgit',
        'THB': 'Thai Baht',
        'PHP': 'Philippine Peso',
        'VND': 'Vietnamese Dong',
        'KRW': 'South Korean Won',
        'INR': 'Indian Rupee',
        'HKD': 'Hong Kong Dollar',
        'TWD': 'Taiwan Dollar',
        'NZD': 'New Zealand Dollar'
      }
    }
  },
  
  computed: {
    canEdit() {
      return this.payable.status !== 'Paid'
    },
    
    canMakePayment() {
      return this.payable.status !== 'Paid' && this.payable.balance > 0
    },
    
    totalPaidAmount() {
      return this.payments.reduce((sum, payment) => {
        return sum + (payment.payable_amount || payment.amount || 0)
      }, 0)
    },
    
    agingStatus() {
      if (!this.payable.due_date) {
        return {
          days: 0,
          status: 'unknown',
          label: 'Unknown',
          class: 'unknown',
          icon: 'fas fa-question'
        }
      }
      
      const today = new Date()
      const dueDate = new Date(this.payable.due_date)
      const daysDiff = Math.floor((today - dueDate) / (1000 * 60 * 60 * 24))
      
      if (daysDiff < 0) {
        return {
          days: daysDiff,
          status: 'until due',
          label: 'Not Due Yet',
          class: 'not-due',
          icon: 'fas fa-clock'
        }
      } else if (daysDiff === 0) {
        return {
          days: 0,
          status: 'due today',
          label: 'Due Today',
          class: 'due-today',
          icon: 'fas fa-exclamation'
        }
      } else if (daysDiff <= 30) {
        return {
          days: daysDiff,
          status: 'overdue',
          label: '1-30 Days Overdue',
          class: 'overdue-30',
          icon: 'fas fa-exclamation-triangle'
        }
      } else if (daysDiff <= 60) {
        return {
          days: daysDiff,
          status: 'overdue',
          label: '31-60 Days Overdue',
          class: 'overdue-60',
          icon: 'fas fa-exclamation-triangle'
        }
      } else if (daysDiff <= 90) {
        return {
          days: daysDiff,
          status: 'overdue',
          label: '61-90 Days Overdue',
          class: 'overdue-90',
          icon: 'fas fa-times-circle'
        }
      } else {
        return {
          days: daysDiff,
          status: 'overdue',
          label: 'Over 90 Days Overdue',
          class: 'overdue-critical',
          icon: 'fas fa-times-circle'
        }
      }
    }
  },
  
  mounted() {
    this.loadPayableDetails()
  },
  
  methods: {
    async loadPayableDetails() {
      this.loading = true
      try {
        const response = await axios.get(`/accounting/vendor-payables/${this.payableId}`)
        this.payable = response.data.data
        
        // Load payments
        if (this.payable.payable_payments) {
          this.payments = this.payable.payable_payments
        }
        
        // Filter alternative currencies
        this.alternativeCurrencies = this.alternativeCurrencies.filter(
          currency => currency !== this.payable.currency_code
        )
        
        this.$toast?.success('Payable details loaded successfully')
      } catch (error) {
        console.error('Error loading payable details:', error)
        this.$toast?.error('Failed to load payable details')
      } finally {
        this.loading = false
      }
    },
    
    async convertToCurrency(currency) {
      this.selectedViewCurrency = currency
      
      try {
        const response = await axios.get(`/accounting/vendor-payables/${this.payableId}/convert`, {
          params: {
            to_currency: currency,
            date: new Date().toISOString().split('T')[0]
          }
        })
        
        this.convertedAmounts = response.data.converted_amounts
      } catch (error) {
        console.error('Error converting currency:', error)
        this.$toast?.error(`Failed to convert to ${currency}`)
        this.selectedViewCurrency = ''
        this.convertedAmounts = null
      }
    },
    
    getStatusIcon(status) {
      const icons = {
        'Open': 'fas fa-clock',
        'Partial': 'fas fa-clock',
        'Paid': 'fas fa-check-circle',
        'Overdue': 'fas fa-exclamation-triangle'
      }
      return icons[status] || 'fas fa-question'
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
    
    getCurrencyName(code) {
      return this.currencyNames[code] || code
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
      if (!date) return '-'
      return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      })
    },
    
    canEditPayment() {
      // Add logic for payment edit permissions
      return true
    },
    
    viewPayment(payment) {
      // Navigate to payment detail or show modal
      this.$router.push(`/accounting/payable-payments/${payment.payment_id}`)
    },
    
    editPayment(payment) {
      // Navigate to payment edit form
      this.$router.push(`/accounting/payable-payments/${payment.payment_id}/edit`)
    },
    
    async generateReport() {
      try {
        const response = await axios.get(`/accounting/vendor-payables/${this.payableId}/report`, {
          responseType: 'blob'
        })
        
        const url = window.URL.createObjectURL(new Blob([response.data]))
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', `payable-${this.payableId}-report.pdf`)
        document.body.appendChild(link)
        link.click()
        link.remove()
        
        this.$toast?.success('Report generated successfully')
      } catch (error) {
        console.error('Error generating report:', error)
        this.$toast?.error('Failed to generate report')
      }
    },
    
    async exportData() {
      try {
        const response = await axios.get(`/accounting/vendor-payables/${this.payableId}/export`, {
          responseType: 'blob'
        })
        
        const url = window.URL.createObjectURL(new Blob([response.data]))
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', `payable-${this.payableId}-data.xlsx`)
        document.body.appendChild(link)
        link.click()
        link.remove()
        
        this.$toast?.success('Data exported successfully')
      } catch (error) {
        console.error('Error exporting data:', error)
        this.$toast?.error('Failed to export data')
      }
    }
  }
}
</script>

<style scoped>
.payable-detail-container {
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

/* Loading and Error States */
.loading-state, .error-state {
  text-align: center;
  padding: 4rem;
  background: white;
  border-radius: 16px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}

.loading-spinner {
  width: 50px;
  height: 50px;
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

.error-state i {
  font-size: 4rem;
  color: #ef4444;
  margin-bottom: 1rem;
}

/* Summary Section */
.summary-section {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.status-card {
  background: white;
  border-radius: 16px;
  padding: 1.5rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
  border-left: 4px solid;
}

.status-Open { border-color: #3b82f6; }
.status-Partial { border-color: #f59e0b; }
.status-Paid { border-color: #10b981; }
.status-Overdue { border-color: #ef4444; }

.status-icon {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  color: white;
}

.status-Open .status-icon { background: #3b82f6; }
.status-Partial .status-icon { background: #f59e0b; }
.status-Paid .status-icon { background: #10b981; }
.status-Overdue .status-icon { background: #ef4444; }

.status-content h3 {
  font-size: 1.5rem;
  font-weight: 700;
  margin-bottom: 0.25rem;
  color: #1f2937;
}

.status-content p {
  color: #6b7280;
  margin: 0;
}

.amount-card {
  background: white;
  border-radius: 16px;
  padding: 1.5rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}

.amount-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.amount-header h4 {
  color: #1f2937;
  font-weight: 600;
  margin: 0;
}

.currency-indicator {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.currency-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.8rem;
  font-weight: 600;
}

.currency-USD { background: #dbeafe; color: #1e40af; }
.currency-EUR { background: #fef3c7; color: #92400e; }
.currency-GBP { background: #ecfdf5; color: #065f46; }
.currency-JPY { background: #fce7f3; color: #9d174d; }
.currency-IDR { background: #f3e8ff; color: #6b21a8; }
.currency-SGD { background: #e0f2fe; color: #0e7490; }

.amount-details {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.amount-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.amount-row .label {
  color: #6b7280;
  font-size: 0.9rem;
}

.amount-row .value {
  font-weight: 600;
  color: #1f2937;
}

.amount-row .value.amount {
  color: #3b82f6;
}

.amount-row .value.paid {
  color: #10b981;
}

.amount-row .value.balance {
  color: #1f2937;
  font-size: 1.1rem;
}

.balance-row {
  padding-top: 0.75rem;
  border-top: 1px solid #e5e7eb;
}

.text-danger {
  color: #ef4444 !important;
}

/* Conversion Card */
.conversion-card {
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  color: white;
  border-radius: 16px;
  padding: 1.5rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}

.conversion-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.conversion-header h4 {
  color: white;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.exchange-rate {
  font-size: 0.9rem;
  opacity: 0.9;
}

.conversion-details {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.conversion-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.conversion-row .label {
  opacity: 0.9;
}

.conversion-row .value {
  font-weight: 600;
}

/* Alternative Currencies */
.alternative-currencies {
  background: white;
  border-radius: 16px;
  padding: 1.5rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}

.alternative-currencies h4 {
  color: #1f2937;
  margin-bottom: 1rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.currency-options {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-bottom: 1rem;
}

.currency-option {
  padding: 0.5rem 1rem;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  background: white;
  cursor: pointer;
  transition: all 0.3s ease;
}

.currency-option:hover {
  border-color: #6366f1;
  color: #6366f1;
}

.currency-option.active {
  background: #6366f1;
  color: white;
  border-color: #6366f1;
}

.converted-display {
  background: #f8fafc;
  border-radius: 8px;
  padding: 1rem;
}

.converted-row {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.5rem;
}

.converted-amount {
  font-weight: 600;
  color: #1f2937;
}

.conversion-info {
  text-align: center;
  margin-top: 0.5rem;
}

.conversion-info small {
  color: #6b7280;
}

/* Info Grid */
.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.info-card {
  background: white;
  border-radius: 16px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
  overflow: hidden;
}

.card-header {
  background: #f8fafc;
  padding: 1rem 1.5rem;
  border-bottom: 1px solid #e5e7eb;
}

.card-header h3 {
  margin: 0;
  color: #1f2937;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.card-content {
  padding: 1.5rem;
}

/* Vendor Profile */
.vendor-profile {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
}

.vendor-avatar {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 1.5rem;
  flex-shrink: 0;
}

.vendor-details h4 {
  color: #1f2937;
  font-weight: 600;
  margin: 0 0 0.5rem 0;
}

.vendor-code {
  color: #6b7280;
  font-size: 0.9rem;
  margin: 0 0 1rem 0;
}

.vendor-contact {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.contact-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #6b7280;
  font-size: 0.9rem;
}

.contact-item i {
  width: 16px;
  color: #9ca3af;
}

/* Invoice and other details */
.invoice-details,
.currency-info {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.detail-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.detail-row .label {
  color: #6b7280;
  font-size: 0.9rem;
}

.detail-row .value {
  color: #1f2937;
  font-weight: 500;
}

.detail-row .value.amount {
  color: #3b82f6;
  font-weight: 600;
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

/* Aging Information */
.aging-info {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.aging-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  border-radius: 12px;
  border: 2px solid;
}

.aging-item.not-due {
  background: #ecfdf5;
  border-color: #10b981;
}

.aging-item.due-today {
  background: #fef3c7;
  border-color: #f59e0b;
}

.aging-item.overdue-30 {
  background: #fef2f2;
  border-color: #ef4444;
}

.aging-item.overdue-60,
.aging-item.overdue-90,
.aging-item.overdue-critical {
  background: #fee2e2;
  border-color: #dc2626;
}

.aging-icon {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.2rem;
  color: white;
}

.aging-item.not-due .aging-icon { background: #10b981; }
.aging-item.due-today .aging-icon { background: #f59e0b; }
.aging-item.overdue-30 .aging-icon { background: #ef4444; }
.aging-item.overdue-60 .aging-icon { background: #dc2626; }
.aging-item.overdue-90 .aging-icon { background: #991b1b; }
.aging-item.overdue-critical .aging-icon { background: #7f1d1d; }

.aging-details h4 {
  color: #1f2937;
  font-weight: 600;
  margin: 0 0 0.25rem 0;
}

.aging-details p {
  color: #6b7280;
  margin: 0;
}

.aging-breakdown {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.breakdown-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.9rem;
}

.breakdown-item .label {
  color: #6b7280;
}

.breakdown-item .value {
  color: #1f2937;
  font-weight: 500;
}

/* Payments Section */
.payments-section {
  background: white;
  border-radius: 16px;
  padding: 1.5rem;
  margin-bottom: 2rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.section-header h3 {
  color: #1f2937;
  font-weight: 600;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.payment-summary {
  display: flex;
  gap: 1rem;
  color: #6b7280;
  font-size: 0.9rem;
}

.payment-count {
  color: #6b7280;
}

.payment-total {
  color: #10b981;
  font-weight: 600;
}

.empty-payments {
  text-align: center;
  padding: 3rem;
  color: #6b7280;
}

.empty-payments i {
  font-size: 3rem;
  color: #d1d5db;
  margin-bottom: 1rem;
}

.empty-payments h4 {
  color: #1f2937;
  margin-bottom: 0.5rem;
}

.payments-grid {
  width: 100%;
  border-collapse: collapse;
}

.payments-grid th {
  background: #f8fafc;
  padding: 1rem;
  text-align: left;
  font-weight: 600;
  color: #374151;
  border-bottom: 2px solid #e5e7eb;
}

.payments-grid td {
  padding: 1rem;
  border-bottom: 1px solid #e5e7eb;
  vertical-align: top;
}

.payment-row:hover {
  background: #f8fafc;
}

.payment-date {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #6b7280;
}

.payment-amount {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.payment-amount .amount {
  font-weight: 600;
  color: #10b981;
}

.converted-payment small {
  color: #6b7280;
}

.payment-method,
.reference {
  color: #6b7280;
}

.exchange-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.rate {
  color: #6b7280;
  font-size: 0.8rem;
}

.exchange-diff {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.8rem;
}

.exchange-diff.gain {
  color: #10b981;
}

.exchange-diff.loss {
  color: #ef4444;
}

.payment-actions {
  display: flex;
  gap: 0.5rem;
}

/* Currency Summary Section */
.currency-summary-section {
  background: white;
  border-radius: 16px;
  padding: 1.5rem;
  margin-bottom: 2rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}

.currency-summary-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1rem;
}

.summary-item {
  background: #f8fafc;
  border-radius: 12px;
  padding: 1rem;
  border: 1px solid #e5e7eb;
}

.summary-item h4 {
  color: #1f2937;
  font-weight: 600;
  margin-bottom: 1rem;
}

.currency-display {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.5rem;
}

.currency-display .amount {
  font-weight: 600;
  color: #3b82f6;
}

.balance-display {
  color: #6b7280;
  font-size: 0.9rem;
}

/* Actions Section */
.actions-section {
  background: white;
  border-radius: 16px;
  padding: 1.5rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}

.action-group h4 {
  color: #1f2937;
  font-weight: 600;
  margin-bottom: 1rem;
}

.action-buttons {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
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

/* Responsive */
@media (max-width: 1024px) {
  .summary-section {
    grid-template-columns: 1fr;
  }
  
  .info-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .payable-detail-container {
    padding: 1rem;
  }
  
  .header-content {
    flex-direction: column;
    gap: 1rem;
    align-items: flex-start;
  }
  
  .header-actions {
    width: 100%;
    justify-content: flex-start;
  }
  
  .vendor-profile {
    flex-direction: column;
    text-align: center;
  }
  
  .payments-grid {
    font-size: 0.8rem;
  }
  
  .payments-grid th,
  .payments-grid td {
    padding: 0.5rem;
  }
  
  .action-buttons {
    flex-direction: column;
  }
  
  .currency-options {
    justify-content: center;
  }
}
</style>