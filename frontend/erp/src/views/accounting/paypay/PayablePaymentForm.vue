<template>
  <div class="payment-form-container">
    <!-- Page Header -->
    <div class="page-header">
      <div class="header-content">
        <div class="header-text">
          <h1 class="page-title">
            <i class="fas fa-credit-card"></i>
            {{ isEdit ? 'Edit Payment' : 'Record Payment' }}
          </h1>
          <p class="page-subtitle">
            {{ isEdit ? 'Update payment details' : 'Record a new payment to vendor with multi-currency support' }}
          </p>
        </div>
        <div class="header-actions">
          <router-link to="/accounting/payable-payments" class="btn btn-outline">
            <i class="fas fa-arrow-left"></i>
            Back to List
          </router-link>
        </div>
      </div>
    </div>

    <!-- Payment Form -->
    <div class="form-container">
      <form @submit.prevent="submitForm" class="payment-form">
        <!-- Left Column -->
        <div class="form-column">
          <!-- Vendor & Payable Selection -->
          <div class="form-card">
            <div class="card-header">
              <h3>
                <i class="fas fa-building"></i>
                Vendor & Payable
              </h3>
            </div>
            <div class="card-content">
              <!-- Vendor Selection -->
              <div class="form-group">
                <label class="form-label required">Vendor</label>
                <div class="vendor-selector">
                  <select v-model="form.vendor_id" @change="loadPayables" class="form-select" :disabled="isEdit">
                    <option value="">Select Vendor</option>
                    <template v-if="vendors && vendors.length">
                      <option 
                        v-for="vendor in vendors" 
                        :key="vendor.vendor_id" 
                        :value="vendor.vendor_id"
                      >
                        {{ vendor.name }}
                      </option>
                    </template>
                  </select>
                  <button type="button" @click="showVendorModal = true" class="vendor-add-btn">
                    <i class="fas fa-plus"></i>
                  </button>
                </div>
                <div v-if="errors.vendor_id" class="error-message">{{ errors.vendor_id[0] }}</div>
              </div>

              <!-- Payable Selection -->
              <div class="form-group">
                <label class="form-label required">Outstanding Payable</label>
                <select v-model="form.payable_id" @change="selectPayable" class="form-select" :disabled="isEdit || loadingPayables">
                  <option value="">Select Payable</option>
                  <option 
                    v-for="payable in payables" 
                    :key="payable.payable_id" 
                    :value="payable.payable_id"
                  >
                    #{{ payable.payable_id }} - {{ payable.vendor_invoice?.invoice_number }} 
                    ({{ formatCurrency(payable.balance, payable.currency_code) }})
                  </option>
                </select>
                <div v-if="loadingPayables" class="loading-text">Loading payables...</div>
                <div v-if="errors.payable_id" class="error-message">{{ errors.payable_id[0] }}</div>
              </div>
            </div>
          </div>

          <!-- Payment Details -->
          <div class="form-card">
            <div class="card-header">
              <h3>
                <i class="fas fa-credit-card"></i>
                Payment Details
              </h3>
            </div>
            <div class="card-content">
              <div class="form-row">
                <div class="form-group">
                  <label class="form-label required">Payment Date</label>
                  <input v-model="form.payment_date" @change="onPaymentDateChange" type="date" class="form-input" required>
                  <div v-if="errors.payment_date" class="error-message">{{ errors.payment_date[0] }}</div>
                </div>

                <div class="form-group">
                  <label class="form-label required">Payment Method</label>
                  <select v-model="form.payment_method" class="form-select" required>
                    <option value="">Select Method</option>
                    <option value="Bank Transfer">Bank Transfer</option>
                    <option value="Check">Check</option>
                    <option value="Cash">Cash</option>
                    <option value="Credit Card">Credit Card</option>
                    <option value="Wire Transfer">Wire Transfer</option>
                    <option value="Other">Other</option>
                  </select>
                  <div v-if="errors.payment_method" class="error-message">{{ errors.payment_method[0] }}</div>
                </div>
              </div>

              <div class="form-group">
                <label class="form-label">Reference Number</label>
                <input v-model="form.reference_number" type="text" class="form-input" placeholder="Transaction reference, check number, etc.">
                <div v-if="errors.reference_number" class="error-message">{{ errors.reference_number[0] }}</div>
              </div>
            </div>
          </div>

          <!-- Currency & Amount -->
          <div class="form-card">
            <div class="card-header">
              <h3>
                <i class="fas fa-coins"></i>
                Currency & Amount
              </h3>
            </div>
            <div class="card-content">
              <!-- Payment Currency Selection -->
              <div class="form-group">
                <label class="form-label required">Payment Currency</label>
                <select v-model="form.payment_currency" @change="onCurrencyChange" class="form-select">
                  <option value="">Select Currency</option>
                  <option v-for="currency in availableCurrencies" :key="currency.code" :value="currency.code">
                    {{ currency.code }} - {{ currency.name }}
                  </option>
                </select>
                <div v-if="selectedPayable && selectedPayable.currency_code !== form.payment_currency" class="currency-info">
                  <small>
                    <i class="fas fa-info-circle"></i>
                    Payable is in {{ selectedPayable.currency_code }}. Exchange rate conversion will be applied.
                  </small>
                </div>
                <div v-if="errors.payment_currency" class="error-message">{{ errors.payment_currency[0] }}</div>
              </div>

              <!-- Payment Amount -->
              <div class="form-group">
                <label class="form-label required">Payment Amount</label>
                <div class="amount-input-group">
                  <span class="currency-prefix">{{ getCurrencySymbol(form.payment_currency) }}</span>
                  <input 
                    v-model.number="form.amount" 
                    @input="calculateAmounts"
                    type="number" 
                    step="0.01" 
                    class="form-input amount-input" 
                    placeholder="0.00"
                    required
                  >
                  <button 
                    v-if="selectedPayable && form.payment_currency === selectedPayable.currency_code" 
                    @click="setFullPayment" 
                    type="button" 
                    class="full-payment-btn"
                  >
                    Full
                  </button>
                </div>
                <div v-if="errors.amount" class="error-message">{{ errors.amount[0] }}</div>
              </div>

              <!-- Exchange Rate Section -->
              <div v-if="needsExchangeRate" class="exchange-rate-section">
                <div class="exchange-rate-header">
                  <h4>
                    <i class="fas fa-exchange-alt"></i>
                    Exchange Rate Conversion
                  </h4>
                  <div class="rate-status" :class="{ success: form.exchange_rate > 0, warning: !form.exchange_rate }">
                    <i :class="form.exchange_rate > 0 ? 'fas fa-check-circle' : 'fas fa-exclamation-triangle'"></i>
                    {{ form.exchange_rate > 0 ? 'Rate Set' : 'Rate Required' }}
                  </div>
                </div>

                <div class="exchange-rate-input">
                  <div class="rate-display">
                    <span class="rate-label">1 {{ form.payment_currency }} =</span>
                    <input 
                      v-model.number="form.exchange_rate" 
                      @input="calculateAmounts"
                      type="number" 
                      step="0.000001" 
                      class="form-input rate-input"
                      placeholder="0.000000"
                      required
                    >
                    <span class="rate-label">{{ getTargetCurrency() }}</span>
                  </div>
                  <button @click="fetchExchangeRate" type="button" class="btn btn-outline btn-sm" :disabled="fetchingRate">
                    <i class="fas fa-sync" :class="{ 'fa-spin': fetchingRate }"></i>
                    Auto Fetch
                  </button>
                </div>

                <div v-if="lastRateUpdate" class="rate-info">
                  <i class="fas fa-info-circle"></i>
                  Rate updated on {{ formatDate(lastRateUpdate) }}
                </div>

                <div v-if="errors.exchange_rate" class="error-message">{{ errors.exchange_rate[0] }}</div>
              </div>

              <!-- Amount Conversion Preview -->
              <div v-if="needsExchangeRate && form.amount > 0 && form.exchange_rate > 0" class="conversion-preview">
                <div class="preview-card">
                  <h4>Conversion Preview</h4>
                  <div class="conversion-calculation">
                    <div class="calc-row">
                      <span class="calc-label">Payment Amount:</span>
                      <span class="calc-value">{{ formatCurrency(form.amount, form.payment_currency) }}</span>
                    </div>
                    <div class="calc-row">
                      <span class="calc-label">Exchange Rate:</span>
                      <span class="calc-value">{{ form.exchange_rate }}</span>
                    </div>
                    <div class="calc-row total">
                      <span class="calc-label">Amount in {{ getTargetCurrency() }}:</span>
                      <span class="calc-value">{{ formatCurrency(convertedAmount, getTargetCurrency()) }}</span>
                    </div>
                  </div>
                  
                  <!-- Exchange Gain/Loss -->
                  <div v-if="exchangeGainLoss !== 0" class="exchange-impact">
                    <div class="impact-header">
                      <i :class="exchangeGainLoss > 0 ? 'fas fa-arrow-up text-success' : 'fas fa-arrow-down text-danger'"></i>
                      Exchange {{ exchangeGainLoss > 0 ? 'Gain' : 'Loss' }}
                    </div>
                    <div class="impact-amount" :class="{ gain: exchangeGainLoss > 0, loss: exchangeGainLoss < 0 }">
                      {{ formatCurrency(Math.abs(exchangeGainLoss), getBaseCurrency()) }}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Right Column - Summary & Info -->
        <div class="form-sidebar">
          <!-- Selected Payable Info -->
          <div v-if="selectedPayable" class="info-card">
            <div class="card-header">
              <h4>
                <i class="fas fa-file-invoice"></i>
                Payable Details
              </h4>
            </div>
            <div class="card-content">
              <div class="payable-summary">
                <div class="summary-row">
                  <span class="label">Payable ID:</span>
                  <span class="value">#{{ selectedPayable.payable_id }}</span>
                </div>
                <div class="summary-row">
                  <span class="label">Invoice:</span>
                  <span class="value">{{ selectedPayable.vendor_invoice?.invoice_number }}</span>
                </div>
                <div class="summary-row">
                  <span class="label">Due Date:</span>
                  <span class="value due-date" :class="getDueDateClass(selectedPayable.due_date)">
                    {{ formatDate(selectedPayable.due_date) }}
                  </span>
                </div>
                <div class="summary-row">
                  <span class="label">Currency:</span>
                  <span class="value">
                    <span class="currency-badge" :class="`currency-${selectedPayable.currency_code}`">
                      {{ selectedPayable.currency_code }}
                    </span>
                  </span>
                </div>
                <div class="summary-row">
                  <span class="label">Original Amount:</span>
                  <span class="value amount">{{ formatCurrency(selectedPayable.amount, selectedPayable.currency_code) }}</span>
                </div>
                <div class="summary-row">
                  <span class="label">Paid Amount:</span>
                  <span class="value paid">{{ formatCurrency(selectedPayable.paid_amount || 0, selectedPayable.currency_code) }}</span>
                </div>
                <div class="summary-row balance-row">
                  <span class="label">Remaining Balance:</span>
                  <span class="value balance">{{ formatCurrency(selectedPayable.balance, selectedPayable.currency_code) }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Payment Summary -->
          <div v-if="form.amount > 0" class="info-card payment-summary-card">
            <div class="card-header">
              <h4>
                <i class="fas fa-calculator"></i>
                Payment Summary
              </h4>
            </div>
            <div class="card-content">
              <div class="payment-summary">
                <div class="summary-section">
                  <h5>Payment Details</h5>
                  <div class="summary-row">
                    <span class="label">Payment Amount:</span>
                    <span class="value">{{ formatCurrency(form.amount, form.payment_currency) }}</span>
                  </div>
                  <div v-if="needsExchangeRate" class="summary-row">
                    <span class="label">Exchange Rate:</span>
                    <span class="value">{{ form.exchange_rate || 'Not set' }}</span>
                  </div>
                  <div v-if="convertedAmount && needsExchangeRate" class="summary-row">
                    <span class="label">Converted Amount:</span>
                    <span class="value">{{ formatCurrency(convertedAmount, getTargetCurrency()) }}</span>
                  </div>
                </div>

                <div v-if="selectedPayable" class="summary-section">
                  <h5>After Payment</h5>
                  <div class="summary-row">
                    <span class="label">New Balance:</span>
                    <span class="value new-balance">{{ formatCurrency(newBalance, selectedPayable.currency_code) }}</span>
                  </div>
                  <div class="summary-row">
                    <span class="label">Payment Progress:</span>
                    <div class="progress-container">
                      <div class="progress-bar">
                        <div class="progress-fill" :style="{ width: `${paymentProgress}%` }"></div>
                      </div>
                      <span class="progress-text">{{ Math.round(paymentProgress) }}%</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Currency Information -->
          <div v-if="form.payment_currency" class="info-card">
            <div class="card-header">
              <h4>
                <i class="fas fa-coins"></i>
                Currency Information
              </h4>
            </div>
            <div class="card-content">
              <div class="currency-details">
                <div class="detail-row">
                  <span class="label">Payment Currency:</span>
                  <span class="value">{{ form.payment_currency }}</span>
                </div>
                <div v-if="selectedCurrency" class="detail-row">
                  <span class="label">Currency Name:</span>
                  <span class="value">{{ selectedCurrency.name }}</span>
                </div>
                <div v-if="selectedPayable" class="detail-row">
                  <span class="label">Payable Currency:</span>
                  <span class="value">{{ selectedPayable.currency_code }}</span>
                </div>
                <div class="detail-row">
                  <span class="label">Base Currency:</span>
                  <span class="value">{{ getBaseCurrency() }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Validation Status -->
          <div class="validation-card" :class="{ valid: isFormValid, invalid: !isFormValid }">
            <div class="validation-header">
              <i :class="isFormValid ? 'fas fa-check-circle' : 'fas fa-exclamation-triangle'"></i>
              <h4>{{ isFormValid ? 'Ready to Submit' : 'Form Incomplete' }}</h4>
            </div>
            <div class="validation-list">
              <div class="validation-item" :class="{ valid: form.payable_id }">
                <i :class="form.payable_id ? 'fas fa-check' : 'fas fa-times'"></i>
                Payable selected
              </div>
              <div class="validation-item" :class="{ valid: form.amount > 0 }">
                <i :class="form.amount > 0 ? 'fas fa-check' : 'fas fa-times'"></i>
                Payment amount entered
              </div>
              <div class="validation-item" :class="{ valid: form.payment_currency }">
                <i :class="form.payment_currency ? 'fas fa-check' : 'fas fa-times'"></i>
                Payment currency selected
              </div>
              <div v-if="needsExchangeRate" class="validation-item" :class="{ valid: form.exchange_rate > 0 }">
                <i :class="form.exchange_rate > 0 ? 'fas fa-check' : 'fas fa-times'"></i>
                Exchange rate set
              </div>
              <div class="validation-item" :class="{ valid: !amountExceedsBalance }">
                <i :class="!amountExceedsBalance ? 'fas fa-check' : 'fas fa-times'"></i>
                Amount within balance
              </div>
            </div>
          </div>
        </div>

        <!-- Form Actions -->
        <div class="form-actions">
          <div class="action-buttons">
            <router-link to="/accounting/payable-payments" class="btn btn-outline">
              <i class="fas fa-times"></i>
              Cancel
            </router-link>
            <button type="submit" class="btn btn-primary" :disabled="!isFormValid || submitting">
              <i v-if="submitting" class="fas fa-spinner fa-spin"></i>
              <i v-else class="fas fa-save"></i>
              {{ isEdit ? 'Update Payment' : 'Record Payment' }}
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'PayablePaymentForm',
  props: {
    paymentId: {
      type: [String, Number],
      default: null
    }
  },
  
  data() {
    return {
      submitting: false,
      fetchingRate: false,
      loadingPayables: false,
      lastRateUpdate: null,
      showVendorModal: false,
      
      form: {
        vendor_id: '',
        payable_id: '',
        payment_date: new Date().toISOString().split('T')[0],
        amount: 0,
        payment_method: '',
        reference_number: '',
        payment_currency: '',
        exchange_rate: 1,
        payable_amount: 0,
        exchange_difference: 0
      },
      
      vendors: [],
      payables: [],
      availableCurrencies: [],
      baseCurrency: 'USD',
      
      errors: {}
    }
  },
  
  computed: {
    isEdit() {
      return !!this.paymentId
    },
    
    selectedPayable() {
      return this.payables.find(p => p.payable_id == this.form.payable_id)
    },

    selectedCurrency() {
      return this.availableCurrencies.find(c => c.code === this.form.payment_currency)
    },
    
    needsExchangeRate() {
      return this.form.payment_currency && 
             this.selectedPayable && 
             this.form.payment_currency !== this.selectedPayable.currency_code &&
             this.form.payment_currency !== this.baseCurrency
    },
    
    convertedAmount() {
      if (!this.needsExchangeRate || !this.form.exchange_rate || !this.form.amount) {
        return this.form.amount
      }
      
      if (this.form.payment_currency === this.selectedPayable.currency_code) {
        return this.form.amount
      }
      
      // Convert payment currency to payable currency
      return this.form.amount * this.form.exchange_rate
    },
    
    newBalance() {
      if (!this.selectedPayable || !this.convertedAmount) return 0
      return Math.max(0, this.selectedPayable.balance - this.convertedAmount)
    },
    
    paymentProgress() {
      if (!this.selectedPayable || !this.convertedAmount) return 0
      const totalPaid = (this.selectedPayable.paid_amount || 0) + this.convertedAmount
      return Math.min(100, (totalPaid / this.selectedPayable.amount) * 100)
    },
    
    amountExceedsBalance() {
      if (!this.selectedPayable || !this.convertedAmount) return false
      return this.convertedAmount > this.selectedPayable.balance
    },
    
    exchangeGainLoss() {
      // Calculate exchange gain/loss based on the difference between
      // the original exchange rate and current exchange rate
      if (!this.needsExchangeRate || !this.selectedPayable || !this.form.exchange_rate) {
        return 0
      }
      
      const originalRate = this.selectedPayable.exchange_rate || 1
      const currentRate = this.form.exchange_rate
      const baseCurrencyAmount = this.form.amount * currentRate
      const originalBaseCurrencyAmount = this.form.amount * originalRate
      
      return baseCurrencyAmount - originalBaseCurrencyAmount
    },
    
    isFormValid() {
      return this.form.payable_id &&
             this.form.amount > 0 &&
             this.form.payment_currency &&
             this.form.payment_date &&
             this.form.payment_method &&
             (!this.needsExchangeRate || this.form.exchange_rate > 0) &&
             !this.amountExceedsBalance
    }
  },
  
  mounted() {
    this.loadVendors()
    this.loadCurrencies()
    
    if (this.isEdit) {
      this.loadPayment()
    }
  },
  
  methods: {
    async loadVendors() {
      try {
        const response = await axios.get('/vendors')
        this.vendors = response.data.data || response.data
      } catch (error) {
        console.error('Error loading vendors:', error)
        this.$toast?.error('Failed to load vendors')
      }
    },
    
    async loadCurrencies() {
      try {
        const response = await axios.get('/accounting/system-currencies')
        this.availableCurrencies = response.data.data || response.data
        
        // Set base currency
        const baseCurrency = this.availableCurrencies.find(c => c.is_base_currency)
        if (baseCurrency) {
          this.baseCurrency = baseCurrency.code
        }
      } catch (error) {
        console.error('Error loading currencies:', error)
        this.$toast?.error('Failed to load currencies')
      }
    },
    
    async loadPayables() {
      if (!this.form.vendor_id) {
        this.payables = []
        return
      }
      
      this.loadingPayables = true
      try {
        const response = await axios.get('/accounting/vendor-payables', {
          params: {
            vendor_id: this.form.vendor_id,
            status: 'Open,Partial,Overdue'
          }
        })
        this.payables = response.data.data || response.data
      } catch (error) {
        console.error('Error loading payables:', error)
        this.$toast?.error('Failed to load payables')
      } finally {
        this.loadingPayables = false
      }
    },

    selectPayable() {
      this.selectedPayable = this.payables.find(p => p.payable_id == this.form.payable_id)
      
      if (this.selectedPayable && !this.isEdit) {
        // Auto-set currency from payable
        this.form.payment_currency = this.selectedPayable.currency_code || 'USD'
        
        // Auto-fetch exchange rate if needed
        if (this.form.payment_currency !== 'USD') {
          this.fetchExchangeRate()
        }
      }
    },

    async fetchExchangeRate() {
      if (this.form.payment_currency === this.baseCurrency) {
        this.form.exchange_rate = 1.0000
        return
      }

      this.fetchingRate = true
      try {
        const response = await axios.get(`/accounting/exchange-rates/${this.form.payment_currency}`, {
          params: { 
            to_currency: this.getTargetCurrency(),
            date: this.form.payment_date 
          }
        })
        this.form.exchange_rate = response.data.rate
        this.lastRateUpdate = response.data.date
        this.$toast?.success('Exchange rate updated')
      } catch (error) {
        console.warn('Could not fetch exchange rate:', error)
        this.$toast?.warning('Could not fetch current exchange rate. Please enter manually.')
      } finally {
        this.fetchingRate = false
      }
    },
    
    onCurrencyChange() {
      this.errors.payment_currency = null
      this.calculateAmounts()
      
      if (this.needsExchangeRate) {
        this.fetchExchangeRate()
      } else {
        this.form.exchange_rate = 1
      }
    },

    onPaymentDateChange() {
      if (this.needsExchangeRate) {
        this.fetchExchangeRate()
      }
    },
    
    calculateAmounts() {
      if (!this.selectedPayable || !this.form.amount) return
      
      if (this.needsExchangeRate && this.form.exchange_rate) {
        // Calculate payable amount in payable currency
        this.form.payable_amount = this.convertedAmount
        
        // Calculate exchange difference
        this.form.exchange_difference = this.exchangeGainLoss
      } else {
        this.form.payable_amount = this.form.amount
        this.form.exchange_difference = 0
      }
    },
    
    setFullPayment() {
      if (this.selectedPayable) {
        this.form.amount = this.selectedPayable.balance
        this.calculateAmounts()
      }
    },
    
    getTargetCurrency() {
      if (this.selectedPayable) {
        return this.selectedPayable.currency_code
      }
      return this.baseCurrency
    },
    
    getBaseCurrency() {
      return this.baseCurrency
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
    
    async loadPayment() {
      try {
        const response = await axios.get(`/accounting/payable-payments/${this.paymentId}`)
        const payment = response.data.data
        
        this.form = {
          vendor_id: payment.vendor_payable?.vendor_id,
          payable_id: payment.payable_id,
          payment_date: payment.payment_date,
          amount: payment.amount,
          payment_method: payment.payment_method,
          reference_number: payment.reference_number || '',
          payment_currency: payment.payment_currency || 'USD',
          exchange_rate: payment.exchange_rate || 1,
          payable_amount: payment.payable_amount || payment.amount,
          exchange_difference: payment.exchange_difference || 0
        }
        
        // Load payables for the vendor
        await this.loadPayables()
      } catch (error) {
        console.error('Error loading payment:', error)
        this.$toast?.error('Failed to load payment')
        this.$router.push('/accounting/payable-payments')
      }
    },
    
    async submitForm() {
      if (!this.isFormValid) return
      
      this.submitting = true
      try {
        const payload = {
          ...this.form,
          payable_amount: this.convertedAmount,
          exchange_difference: this.exchangeGainLoss
        }
        
        if (this.isEdit) {
          await axios.put(`/accounting/payable-payments/${this.paymentId}`, payload)
        } else {
          await axios.post('/accounting/payable-payments', payload)
        }
        
        this.$toast?.success(`Payment ${this.isEdit ? 'updated' : 'recorded'} successfully`)
        this.$router.push('/accounting/payable-payments')
      } catch (error) {
        console.error('Error saving payment:', error)
        
        if (error.response?.data?.errors) {
          this.errors = error.response.data.errors
        }
        
        const message = error.response?.data?.message || `Failed to ${this.isEdit ? 'update' : 'record'} payment`
        this.$toast?.error(message)
      } finally {
        this.submitting = false
      }
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
    }
  }
}
</script>

<style scoped>
/* Existing styles preserved and enhanced for multi-currency */
.payment-form-container {
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

/* Form Layout */
.form-container {
  display: grid;
  grid-template-columns: 1fr 350px;
  gap: 2rem;
}

.form-column {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.form-sidebar {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.form-card {
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

.form-group {
  margin-bottom: 1.5rem;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.form-label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
  color: #374151;
}

.form-label.required::after {
  content: ' *';
  color: #ef4444;
}

.form-select,
.form-input {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 0.9rem;
  transition: border-color 0.3s ease;
}

.form-select:focus,
.form-input:focus {
  outline: none;
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.error-message {
  color: #ef4444;
  font-size: 0.8rem;
  margin-top: 0.25rem;
}

.loading-text {
  color: #6b7280;
  font-size: 0.8rem;
  margin-top: 0.25rem;
}

/* Vendor Selector */
.vendor-selector {
  display: flex;
  gap: 0.5rem;
}

.vendor-selector .form-select {
  flex: 1;
}

.vendor-add-btn {
  padding: 0.75rem;
  background: #6366f1;
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  transition: background 0.3s ease;
}

.vendor-add-btn:hover {
  background: #5b21b6;
}

/* Currency & Amount specific styles */
.amount-input-group {
  position: relative;
  display: flex;
  align-items: center;
}

.currency-prefix {
  position: absolute;
  left: 12px;
  color: #6b7280;
  font-weight: 500;
  z-index: 1;
}

.amount-input {
  padding-left: 40px;
  padding-right: 60px;
}

.full-payment-btn {
  position: absolute;
  right: 8px;
  padding: 0.25rem 0.75rem;
  background: #10b981;
  color: white;
  border: none;
  border-radius: 4px;
  font-size: 0.8rem;
  cursor: pointer;
  transition: background 0.3s ease;
}

.full-payment-btn:hover {
  background: #059669;
}

.currency-info {
  margin-top: 0.5rem;
  padding: 0.5rem;
  background: #f0f9ff;
  border-radius: 6px;
  border-left: 3px solid #0ea5e9;
}

.currency-info small {
  color: #0369a1;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

/* Exchange Rate Section */
.exchange-rate-section {
  background: #f8fafc;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 1rem;
  margin-top: 1rem;
}

.exchange-rate-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.exchange-rate-header h4 {
  margin: 0;
  color: #1f2937;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.rate-status {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.8rem;
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
}

.rate-status.success {
  background: #d1fae5;
  color: #065f46;
}

.rate-status.warning {
  background: #fef3c7;
  color: #92400e;
}

.exchange-rate-input {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1rem;
}

.rate-display {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex: 1;
}

.rate-label {
  color: #6b7280;
  font-size: 0.9rem;
  white-space: nowrap;
}

.rate-input {
  flex: 1;
  min-width: 120px;
}

.rate-info {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #6b7280;
  font-size: 0.8rem;
}

/* Conversion Preview */
.conversion-preview {
  margin-top: 1rem;
}

.preview-card {
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  color: white;
  padding: 1rem;
  border-radius: 12px;
}

.preview-card h4 {
  margin: 0 0 1rem 0;
  font-size: 1rem;
  opacity: 0.9;
}

.conversion-calculation {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  margin-bottom: 1rem;
}

.calc-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.calc-row.total {
  padding-top: 0.5rem;
  border-top: 1px solid rgba(255, 255, 255, 0.2);
  font-weight: 600;
}

.calc-label {
  opacity: 0.9;
}

.calc-value {
  font-weight: 600;
}

.exchange-impact {
  background: rgba(255, 255, 255, 0.1);
  padding: 0.75rem;
  border-radius: 8px;
  text-align: center;
}

.impact-header {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  font-size: 0.8rem;
  margin-bottom: 0.5rem;
  opacity: 0.9;
}

.impact-amount {
  font-size: 1.1rem;
  font-weight: 700;
}

.impact-amount.gain {
  color: #86efac;
}

.impact-amount.loss {
  color: #fca5a5;
}

/* Sidebar Info Cards */
.info-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
  overflow: hidden;
}

.info-card .card-header {
  background: #f8fafc;
  padding: 1rem;
  border-bottom: 1px solid #e5e7eb;
}

.info-card .card-header h4 {
  margin: 0;
  color: #1f2937;
  font-weight: 600;
  font-size: 0.9rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.info-card .card-content {
  padding: 1rem;
}

/* Payable Summary */
.payable-summary {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.summary-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.summary-row .label {
  color: #6b7280;
  font-size: 0.8rem;
}

.summary-row .value {
  color: #1f2937;
  font-weight: 500;
  font-size: 0.9rem;
}

.summary-row .value.amount {
  color: #3b82f6;
  font-weight: 600;
}

.summary-row .value.paid {
  color: #10b981;
  font-weight: 600;
}

.summary-row .value.balance {
  color: #ef4444;
  font-weight: 600;
}

.balance-row {
  padding-top: 0.75rem;
  border-top: 1px solid #e5e7eb;
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

.currency-badge {
  padding: 0.125rem 0.375rem;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 600;
}

.currency-USD { background: #dbeafe; color: #1e40af; }
.currency-EUR { background: #fef3c7; color: #92400e; }
.currency-GBP { background: #ecfdf5; color: #065f46; }
.currency-JPY { background: #fce7f3; color: #9d174d; }
.currency-IDR { background: #f3e8ff; color: #6b21a8; }
.currency-SGD { background: #e0f2fe; color: #0e7490; }

/* Payment Summary Card */
.payment-summary-card {
  border: 2px solid #6366f1;
}

.payment-summary {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.summary-section h5 {
  color: #1f2937;
  font-weight: 600;
  margin: 0 0 0.5rem 0;
  font-size: 0.9rem;
}

.new-balance {
  color: #10b981 !important;
  font-weight: 700 !important;
}

.progress-container {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.progress-bar {
  flex: 1;
  height: 8px;
  background: #e5e7eb;
  border-radius: 4px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #10b981, #059669);
  transition: width 0.3s ease;
}

.progress-text {
  font-size: 0.8rem;
  color: #6b7280;
  min-width: 35px;
}

/* Currency Details */
.currency-details {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.detail-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.8rem;
}

.detail-row .label {
  color: #6b7280;
}

.detail-row .value {
  color: #1f2937;
  font-weight: 500;
}

/* Validation Card */
.validation-card {
  border-radius: 12px;
  padding: 1rem;
  border: 2px solid;
}

.validation-card.valid {
  background: #ecfdf5;
  border-color: #10b981;
}

.validation-card.invalid {
  background: #fef2f2;
  border-color: #ef4444;
}

.validation-header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 1rem;
}

.validation-card.valid .validation-header {
  color: #065f46;
}

.validation-card.invalid .validation-header {
  color: #991b1b;
}

.validation-header h4 {
  margin: 0;
  font-size: 0.9rem;
  font-weight: 600;
}

.validation-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.validation-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.8rem;
}

.validation-item.valid {
  color: #065f46;
}

.validation-item:not(.valid) {
  color: #991b1b;
}

/* Form Actions */
.form-actions {
  grid-column: 1 / -1;
  background: white;
  border-radius: 16px;
  padding: 1.5rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
  margin-top: 1rem;
}

.action-buttons {
  display: flex;
  justify-content: flex-end;
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

.text-success {
  color: #10b981;
}

.text-danger {
  color: #ef4444;
}

/* Responsive */
@media (max-width: 1024px) {
  .form-container {
    grid-template-columns: 1fr;
  }
  
  .form-sidebar {
    order: -1;
  }
}

@media (max-width: 768px) {
  .payment-form-container {
    padding: 1rem;
  }
  
  .header-content {
    flex-direction: column;
    gap: 1rem;
    align-items: flex-start;
  }
  
  .form-row {
    grid-template-columns: 1fr;
  }
  
  .vendor-selector {
    flex-direction: column;
  }
  
  .amount-input-group {
    flex-direction: column;
    align-items: stretch;
  }
  
  .currency-prefix {
    position: static;
    order: -1;
  }
  
  .amount-input {
    padding-left: 0.75rem;
    padding-right: 0.75rem;
  }
  
  .full-payment-btn {
    position: static;
    margin-top: 0.5rem;
  }
  
  .exchange-rate-input {
    flex-direction: column;
    align-items: stretch;
  }
  
  .rate-display {
    justify-content: space-between;
  }
  
  .action-buttons {
    flex-direction: column;
  }
}
</style>