<template>
  <div class="payable-form-container">
    <!-- Page Header -->
    <div class="page-header">
      <div class="header-content">
        <div class="header-text">
          <h1 class="page-title">
            <i class="fas fa-file-invoice"></i>
            {{ isEditing ? 'Edit' : 'Create' }} Vendor Payable
          </h1>
          <p class="page-subtitle">
            {{ isEditing ? 'Update payable details' : 'Create a new vendor payable with multi-currency support' }}
          </p>
        </div>
        <div class="header-actions">
          <router-link to="/accounting/vendor-payables" class="btn btn-outline">
            <i class="fas fa-arrow-left"></i>
            Back to List
          </router-link>
        </div>
      </div>
    </div>

    <!-- Progress Steps -->
    <div class="progress-steps">
      <div class="step" :class="{ active: currentStep === 1, completed: currentStep > 1 }">
        <div class="step-icon">
          <i class="fas fa-building"></i>
        </div>
        <div class="step-text">
          <h4>Vendor & Invoice</h4>
          <p>Select vendor and invoice</p>
        </div>
      </div>
      
      <div class="step-connector" :class="{ active: currentStep > 1 }"></div>
      
      <div class="step" :class="{ active: currentStep === 2, completed: currentStep > 2 }">
        <div class="step-icon">
          <i class="fas fa-coins"></i>
        </div>
        <div class="step-text">
          <h4>Currency & Amount</h4>
          <p>Set currency and amounts</p>
        </div>
      </div>
      
      <div class="step-connector" :class="{ active: currentStep > 2 }"></div>
      
      <div class="step" :class="{ active: currentStep === 3 }">
        <div class="step-icon">
          <i class="fas fa-check"></i>
        </div>
        <div class="step-text">
          <h4>Review & Save</h4>
          <p>Review and confirm</p>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="form-layout">
      <div class="form-main">
        <form @submit.prevent="savePayable" class="payable-form">
          <!-- Step 1: Vendor & Invoice Selection -->
          <div v-show="currentStep === 1" class="form-step">
            <div class="form-card">
              <div class="card-header">
                <h3>
                  <i class="fas fa-building"></i>
                  Vendor Selection
                </h3>
              </div>
              <div class="card-content">
                <div class="form-group">
                  <label class="form-label required">Vendor</label>
                  <select v-model="form.vendor_id" @change="onVendorChange" class="form-select" :disabled="isEditing">
                    <option value="">Select Vendor</option>
                    <option v-for="vendor in vendors" :key="vendor.vendor_id" :value="vendor.vendor_id">
                      {{ vendor.name }} ({{ vendor.vendor_code }})
                    </option>
                  </select>
                  <div v-if="errors.vendor_id" class="error-message">{{ errors.vendor_id[0] || errors.vendor_id }}</div>
                </div>

                <div class="form-group">
                  <label class="form-label required">Invoice</label>
                  <select v-model="form.invoice_id" @change="onInvoiceChange" class="form-select" :disabled="isEditing || loadingInvoices">
                    <option value="">Select Invoice</option>
                    <option v-for="invoice in filteredInvoices" :key="invoice.invoice_id" :value="invoice.invoice_id">
                      {{ invoice.invoice_number }} - {{ formatCurrency(invoice.total_amount, invoice.currency_code) }}
                    </option>
                  </select>
                  <div v-if="loadingInvoices" class="loading-text">Loading invoices...</div>
                  <div v-if="errors.invoice_id" class="error-message">{{ errors.invoice_id[0] || errors.invoice_id }}</div>
                </div>
              </div>
            </div>
          </div>

          <!-- Step 2: Currency & Amount -->
          <div v-show="currentStep === 2" class="form-step">
            <div class="form-card">
              <div class="card-header">
                <h3>
                  <i class="fas fa-coins"></i>
                  Currency & Amount Details
                </h3>
              </div>
              <div class="card-content">
                <div class="form-row">
                  <div class="form-group">
                    <label class="form-label required">Currency</label>
                    <select v-model="form.currency_code" @change="onCurrencyChange" class="form-select">
                      <option value="">Select Currency</option>
                      <option v-for="currency in availableCurrencies" :key="currency.code" :value="currency.code">
                        {{ currency.code }} - {{ currency.name }}
                      </option>
                    </select>
                    <div v-if="errors.currency_code" class="error-message">{{ errors.currency_code[0] || errors.currency_code }}</div>
                  </div>

                  <div class="form-group">
                    <label class="form-label required">Amount</label>
                    <div class="amount-input-group">
                      <span class="currency-prefix">{{ getCurrencySymbol(form.currency_code) }}</span>
                      <input 
                        v-model.number="form.amount" 
                        @input="validateAmount; calculateBaseCurrency()"
                        type="number" 
                        step="0.01" 
                        class="form-input amount-input" 
                        placeholder="0.00"
                      >
                    </div>
                    <div v-if="errors.amount" class="error-message">{{ errors.amount[0] || errors.amount }}</div>
                  </div>
                </div>

                <!-- Exchange Rate Section -->
                <div v-if="form.currency_code && form.currency_code !== baseCurrency" class="exchange-rate-section">
                  <div class="form-row">
                    <div class="form-group">
                      <label class="form-label">Exchange Rate</label>
                      <div class="exchange-rate-input">
                        <span class="rate-label">1 {{ form.currency_code }} =</span>
                        <input 
                          v-model.number="form.exchange_rate" 
                          @input="calculateBaseCurrency"
                          type="number" 
                          step="0.000001" 
                          class="form-input rate-input"
                          placeholder="0.000000"
                        >
                        <span class="rate-label">{{ baseCurrency }}</span>
                        <button @click="fetchExchangeRate" type="button" class="btn btn-ghost btn-sm">
                          <i class="fas fa-sync" :class="{ 'fa-spin': fetchingRate }"></i>
                          Auto
                        </button>
                      </div>
                      <div v-if="errors.exchange_rate" class="error-message">{{ errors.exchange_rate[0] || errors.exchange_rate }}</div>
                      <div v-if="lastRateUpdate" class="rate-info">
                        Last updated: {{ formatDate(lastRateUpdate) }}
                      </div>
                    </div>
                  </div>

                  <!-- Base Currency Preview -->
                  <div v-if="baseCurrencyAmount" class="base-currency-preview">
                    <div class="preview-card">
                      <h4>Base Currency Amount</h4>
                      <div class="preview-amount">
                        {{ formatCurrency(baseCurrencyAmount, baseCurrency) }}
                      </div>
                      <p class="preview-calculation">
                        {{ formatCurrency(form.amount, form.currency_code) }} × {{ form.exchange_rate }} = {{ formatCurrency(baseCurrencyAmount, baseCurrency) }}
                      </p>
                    </div>
                  </div>
                </div>

                <div class="form-row">
                  <div class="form-group">
                    <label class="form-label required">Due Date</label>
                    <input v-model="form.due_date" type="date" class="form-input">
                    <div v-if="errors.due_date" class="error-message">{{ errors.due_date[0] || errors.due_date }}</div>
                  </div>

                  <div class="form-group">
                    <label class="form-label required">Status</label>
                    <select v-model="form.status" class="form-select">
                      <option value="Open">Open</option>
                      <option value="Partial">Partial</option>
                      <option value="Paid">Paid</option>
                      <option value="Overdue">Overdue</option>
                    </select>
                    <div v-if="errors.status" class="error-message">{{ errors.status[0] || errors.status }}</div>
                  </div>
                </div>

                <div class="form-group">
                  <label class="form-label">Notes</label>
                  <textarea v-model="form.notes" class="form-textarea" rows="3" placeholder="Additional notes..."></textarea>
                </div>
              </div>
            </div>
          </div>

          <!-- Step 3: Review -->
          <div v-show="currentStep === 3" class="form-step">
            <div class="form-card">
              <div class="card-header">
                <h3>
                  <i class="fas fa-check"></i>
                  Review Payable Details
                </h3>
              </div>
              <div class="card-content">
                <div class="review-grid">
                  <div class="review-section">
                    <h4>Vendor Information</h4>
                    <div class="review-item">
                      <span class="label">Vendor:</span>
                      <span class="value">{{ selectedVendor?.name }}</span>
                    </div>
                    <div class="review-item">
                      <span class="label">Vendor Code:</span>
                      <span class="value">{{ selectedVendor?.vendor_code }}</span>
                    </div>
                    <div class="review-item">
                      <span class="label">Invoice Number:</span>
                      <span class="value">{{ selectedInvoice?.invoice_number }}</span>
                    </div>
                  </div>

                  <div class="review-section">
                    <h4>Currency & Amount</h4>
                    <div class="review-item">
                      <span class="label">Currency:</span>
                      <span class="value">{{ form.currency_code }}</span>
                    </div>
                    <div class="review-item">
                      <span class="label">Amount:</span>
                      <span class="value amount">{{ formatCurrency(form.amount, form.currency_code) }}</span>
                    </div>
                    <div v-if="form.currency_code !== baseCurrency" class="review-item">
                      <span class="label">Exchange Rate:</span>
                      <span class="value">{{ form.exchange_rate }}</span>
                    </div>
                    <div v-if="baseCurrencyAmount" class="review-item">
                      <span class="label">Base Currency Amount:</span>
                      <span class="value amount">{{ formatCurrency(baseCurrencyAmount, baseCurrency) }}</span>
                    </div>
                  </div>

                  <div class="review-section">
                    <h4>Payment Details</h4>
                    <div class="review-item">
                      <span class="label">Due Date:</span>
                      <span class="value">{{ formatDate(form.due_date) }}</span>
                    </div>
                    <div class="review-item">
                      <span class="label">Status:</span>
                      <span class="value">
                        <span class="status-badge" :class="`status-${form.status}`">{{ form.status }}</span>
                      </span>
                    </div>
                    <div class="review-item">
                      <span class="label">Balance:</span>
                      <span class="value amount">{{ formatCurrency(calculatedBalance, form.currency_code) }}</span>
                    </div>
                  </div>
                </div>

                <div v-if="form.notes" class="review-notes">
                  <h4>Notes</h4>
                  <p>{{ form.notes }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Form Actions -->
          <div class="form-actions">
            <div class="action-buttons">
              <button v-if="currentStep > 1" @click="previousStep" type="button" class="btn btn-outline">
                <i class="fas fa-arrow-left"></i>
                Previous
              </button>
              
              <button v-if="currentStep < 3" @click="nextStep" type="button" class="btn btn-primary" :disabled="!canProceed">
                Next
                <i class="fas fa-arrow-right"></i>
              </button>
              
              <button v-if="currentStep === 3" type="submit" class="btn btn-primary" :disabled="submitting || !isFormValid">
                <i v-if="submitting" class="fas fa-spinner fa-spin"></i>
                <i v-else class="fas fa-save"></i>
                {{ isEditing ? 'Update' : 'Save' }} Payable
              </button>
            </div>
          </div>
        </form>
      </div>

      <!-- Sidebar Info -->
      <div class="sidebar-info">
        <!-- Vendor Information -->
        <div v-if="selectedVendor" class="info-card">
          <div class="card-header">
            <h4>
              <i class="fas fa-building"></i>
              Vendor Information
            </h4>
          </div>
          <div class="card-content">
            <div class="vendor-details">
              <div class="vendor-avatar">
                {{ selectedVendor.name.charAt(0).toUpperCase() }}
              </div>
              <div class="vendor-info">
                <h5>{{ selectedVendor.name }}</h5>
                <p>{{ selectedVendor.vendor_code }}</p>
                <div class="vendor-contact">
                  <div v-if="selectedVendor.email">
                    <i class="fas fa-envelope"></i>
                    {{ selectedVendor.email }}
                  </div>
                  <div v-if="selectedVendor.phone">
                    <i class="fas fa-phone"></i>
                    {{ selectedVendor.phone }}
                  </div>
                  <div v-if="selectedVendor.preferred_currency" class="vendor-currency">
                    <i class="fas fa-coins"></i>
                    Preferred: {{ selectedVendor.preferred_currency }}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Invoice Information -->
        <div v-if="selectedInvoice" class="info-card">
          <div class="card-header">
            <h4>
              <i class="fas fa-file-invoice"></i>
              Invoice Details
            </h4>
          </div>
          <div class="card-content">
            <div class="invoice-details">
              <div class="detail-row">
                <span>Invoice Number:</span>
                <span>{{ selectedInvoice.invoice_number }}</span>
              </div>
              <div class="detail-row">
                <span>Invoice Date:</span>
                <span>{{ formatDate(selectedInvoice.invoice_date) }}</span>
              </div>
              <div class="detail-row">
                <span>Due Date:</span>
                <span>{{ formatDate(selectedInvoice.due_date) }}</span>
              </div>
              <div class="detail-row">
                <span>Currency:</span>
                <span>{{ selectedInvoice.currency_code || 'USD' }}</span>
              </div>
              <div class="detail-row">
                <span>Total Amount:</span>
                <span class="amount">{{ formatCurrency(selectedInvoice.total_amount, selectedInvoice.currency_code) }}</span>
              </div>
              <div class="detail-row">
                <span>Status:</span>
                <span class="status-badge" :class="`status-${selectedInvoice.status}`">{{ selectedInvoice.status }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Currency Info -->
        <div v-if="form.currency_code" class="info-card">
          <div class="card-header">
            <h4>
              <i class="fas fa-coins"></i>
              Currency Information
            </h4>
          </div>
          <div class="card-content">
            <div class="currency-details">
              <div class="detail-row">
                <span>Selected Currency:</span>
                <span>{{ form.currency_code }}</span>
              </div>
              <div v-if="selectedCurrency" class="detail-row">
                <span>Currency Name:</span>
                <span>{{ selectedCurrency.name }}</span>
              </div>
              <div v-if="form.exchange_rate && form.currency_code !== baseCurrency" class="detail-row">
                <span>Exchange Rate:</span>
                <span>{{ form.exchange_rate }}</span>
              </div>
              <div class="detail-row">
                <span>Base Currency:</span>
                <span>{{ baseCurrency }}</span>
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
  name: 'PayableForm',
  props: {
    payableId: {
      type: [String, Number],
      default: null
    }
  },
  
  data() {
    return {
      currentStep: 1,
      submitting: false,
      fetchingRate: false,
      loadingInvoices: false,
      lastRateUpdate: null,
      baseCurrency: 'USD',
      
      form: {
        vendor_id: '',
        invoice_id: '',
        amount: 0,
        currency_code: '',
        exchange_rate: 1,
        due_date: '',
        status: 'Open',
        notes: ''
      },
      
      vendors: [],
      invoices: [],
      availableCurrencies: [],
      
      errors: {}
    }
  },
  
  computed: {
    isEditing() {
      return !!this.payableId
    },
    
    selectedVendor() {
      return this.vendors.find(v => v.vendor_id == this.form.vendor_id)
    },
    
    selectedInvoice() {
      return this.invoices.find(i => i.invoice_id == this.form.invoice_id)
    },

    selectedCurrency() {
      return this.availableCurrencies.find(c => c.code === this.form.currency_code)
    },
    
    filteredInvoices() {
      if (!this.form.vendor_id) return []
      return this.invoices.filter(invoice => invoice.vendor_id == this.form.vendor_id)
    },
    
    baseCurrencyAmount() {
      if (!this.form.amount || !this.form.exchange_rate) return 0
      return this.form.amount * this.form.exchange_rate
    },
    
    calculatedBalance() {
      return this.form.amount - (this.form.paid_amount || 0)
    },
    
    canProceed() {
      if (this.currentStep === 1) {
        return this.form.vendor_id && this.form.invoice_id
      }
      if (this.currentStep === 2) {
        return this.form.currency_code && this.form.amount > 0 && this.form.due_date && this.form.status
      }
      return true
    },
    
    isFormValid() {
      return this.form.vendor_id && 
             this.form.invoice_id && 
             this.form.currency_code && 
             this.form.amount > 0 && 
             this.form.due_date && 
             this.form.status &&
             (this.form.currency_code === this.baseCurrency || this.form.exchange_rate > 0)
    }
  },
  
  mounted() {
    this.loadVendors()
    this.loadInvoices()
    this.loadCurrencies()
    
    if (this.isEditing) {
      this.loadPayable()
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
    
    async loadInvoices() {
      this.loadingInvoices = true
      try {
        const response = await axios.get('/vendor-invoices')
        this.invoices = response.data.data || response.data
      } catch (error) {
        console.error('Error loading invoices:', error)
        this.$toast?.error('Failed to load invoices')
      } finally {
        this.loadingInvoices = false
      }
    },

    async loadCurrencies() {
      try {
        const response = await axios.get('/accounting/system-currencies')
        this.availableCurrencies = response.data.data || response.data
        
        // Set default currency to USD
        if (!this.form.currency_code && this.availableCurrencies.length > 0) {
          const defaultCurrency = this.availableCurrencies.find(c => c.is_base_currency) || this.availableCurrencies[0]
          this.form.currency_code = defaultCurrency.code
          this.baseCurrency = defaultCurrency.code
        }
      } catch (error) {
        console.error('Error loading currencies:', error)
        this.$toast?.error('Failed to load currencies')
      }
    },
    
    async loadPayable() {
      try {
        const response = await axios.get(`/accounting/vendor-payables/${this.payableId}`)
        const payable = response.data.data
        
        this.form = {
          vendor_id: payable.vendor_id,
          invoice_id: payable.invoice_id,
          amount: payable.amount,
          currency_code: payable.currency_code || 'USD',
          exchange_rate: payable.exchange_rate || 1,
          due_date: payable.due_date,
          status: payable.status,
          notes: payable.notes || ''
        }
        
        this.currentStep = 2 // Skip to currency step for editing
      } catch (error) {
        console.error('Error loading payable:', error)
        this.$toast?.error('Failed to load payable')
        this.goBack()
      }
    },
    
    onVendorChange() {
      this.form.invoice_id = ''
      this.errors.vendor_id = null
      
      // Auto-set currency from vendor preference
      if (this.selectedVendor && this.selectedVendor.preferred_currency) {
        this.form.currency_code = this.selectedVendor.preferred_currency
        this.onCurrencyChange()
      }
    },
    
    onInvoiceChange() {
      if (this.selectedInvoice && !this.form.amount) {
        this.form.amount = this.selectedInvoice.total_amount
        
        // Auto-set currency from invoice
        if (this.selectedInvoice.currency_code) {
          this.form.currency_code = this.selectedInvoice.currency_code
          this.onCurrencyChange()
        }
        
        // Auto-set due date from invoice
        if (this.selectedInvoice.due_date && !this.form.due_date) {
          this.form.due_date = this.selectedInvoice.due_date
        }
      }
      this.errors.invoice_id = null
    },

    onCurrencyChange() {
      this.errors.currency_code = null
      
      if (this.form.currency_code === this.baseCurrency) {
        this.form.exchange_rate = 1
      } else {
        this.fetchExchangeRate()
      }
    },

    async fetchExchangeRate() {
      if (this.form.currency_code === this.baseCurrency) {
        this.form.exchange_rate = 1
        return
      }

      this.fetchingRate = true
      try {
        const response = await axios.get(`/accounting/exchange-rates/${this.form.currency_code}`, {
          params: { 
            to_currency: this.baseCurrency,
            date: this.form.due_date || new Date().toISOString().split('T')[0]
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

    calculateBaseCurrency() {
      // Auto-calculate base currency amount when amount or exchange rate changes
      // This is handled by the computed property baseCurrencyAmount
    },
    
    validateAmount() {
      if (this.selectedInvoice && this.form.amount > this.selectedInvoice.total_amount) {
        this.errors.amount = 'Amount cannot exceed invoice total'
      } else {
        this.errors.amount = null
      }
    },
    
    nextStep() {
      if (this.canProceed && this.currentStep < 3) {
        this.currentStep++
      }
    },
    
    previousStep() {
      if (this.currentStep > 1) {
        this.currentStep--
      }
    },
    
    async savePayable() {
      if (!this.isFormValid) return
      
      this.submitting = true
      try {
        const payload = {
          ...this.form,
          base_currency: this.baseCurrency,
          base_currency_amount: this.baseCurrencyAmount,
          balance: this.calculatedBalance,
          base_currency_balance: this.baseCurrencyAmount
        }
        
        if (this.isEditing) {
          await axios.put(`/accounting/vendor-payables/${this.payableId}`, payload)
        } else {
          await axios.post('/accounting/vendor-payables', payload)
        }
        
        this.$toast?.success(`Payable ${this.isEditing ? 'updated' : 'created'} successfully`)
        this.$router.push('/accounting/vendor-payables')
      } catch (error) {
        console.error('Error saving payable:', error)
        
        if (error.response?.data?.errors) {
          this.errors = error.response.data.errors
        }
        
        const message = error.response?.data?.message || `Failed to ${this.isEditing ? 'update' : 'create'} payable`
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
    },
    
    goBack() {
      this.$router.push('/accounting/vendor-payables')
    }
  }
}
</script>

<style scoped>
/* Preserve existing styles and add multi-currency specific styles */
.payable-form-container {
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

/* Progress Steps */
.progress-steps {
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 2rem;
}

.step {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  border-radius: 12px;
  background: white;
  transition: all 0.3s ease;
}

.step.active {
  background: #6366f1;
  color: white;
}

.step.completed {
  background: #10b981;
  color: white;
}

.step-icon {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 0.2);
  font-size: 1.2rem;
}

.step.active .step-icon,
.step.completed .step-icon {
  background: rgba(255, 255, 255, 0.3);
}

.step-text h4 {
  margin: 0;
  font-size: 1rem;
  font-weight: 600;
}

.step-text p {
  margin: 0;
  font-size: 0.8rem;
  opacity: 0.8;
}

.step-connector {
  width: 80px;
  height: 2px;
  background: #e5e7eb;
  transition: background 0.3s ease;
}

.step-connector.active {
  background: #10b981;
}

/* Form Layout */
.form-layout {
  display: grid;
  grid-template-columns: 1fr 300px;
  gap: 2rem;
}

.form-main {
  background: white;
  border-radius: 16px;
  padding: 2rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}

.form-step {
  min-height: 400px;
}

.form-card {
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  overflow: hidden;
}

.card-header {
  background: #f8fafc;
  padding: 1rem 1.5rem;
  border-bottom: 1px solid #e5e7eb;
}

.card-header h3 {
  margin: 0;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #1f2937;
  font-weight: 600;
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
.form-input,
.form-textarea {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 0.9rem;
  transition: border-color 0.3s ease;
}

.form-select:focus,
.form-input:focus,
.form-textarea:focus {
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

/* Currency specific styles */
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
}

.exchange-rate-section {
  background: #f8fafc;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 1rem;
  margin: 1rem 0;
}

.exchange-rate-input {
  display: flex;
  align-items: center;
  gap: 0.5rem;
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
  font-size: 0.8rem;
  color: #6b7280;
  margin-top: 0.5rem;
}

.base-currency-preview {
  margin-top: 1rem;
}

.preview-card {
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  color: white;
  padding: 1rem;
  border-radius: 8px;
  text-align: center;
}

.preview-card h4 {
  margin: 0 0 0.5rem 0;
  font-size: 0.9rem;
  opacity: 0.9;
}

.preview-amount {
  font-size: 1.5rem;
  font-weight: 700;
  margin-bottom: 0.5rem;
}

.preview-calculation {
  font-size: 0.8rem;
  opacity: 0.8;
  margin: 0;
}

/* Review Section */
.review-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  margin-bottom: 1.5rem;
}

.review-section h4 {
  color: #1f2937;
  font-weight: 600;
  margin-bottom: 1rem;
  padding-bottom: 0.5rem;
  border-bottom: 1px solid #e5e7eb;
}

.review-item {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.75rem;
}

.review-item .label {
  color: #6b7280;
}

.review-item .value {
  font-weight: 500;
  color: #1f2937;
}

.review-item .value.amount {
  color: #059669;
  font-weight: 600;
}

.review-notes {
  background: #f8fafc;
  padding: 1rem;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
}

.review-notes h4 {
  margin: 0 0 0.5rem 0;
  color: #1f2937;
}

.review-notes p {
  margin: 0;
  color: #6b7280;
}

/* Form Actions */
.form-actions {
  margin-top: 2rem;
  padding-top: 2rem;
  border-top: 1px solid #e5e7eb;
}

.action-buttons {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
}

/* Sidebar */
.sidebar-info {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.info-card {
  background: white;
  border-radius: 12px;
  border: 1px solid #e5e7eb;
  overflow: hidden;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.info-card .card-header {
  background: #f8fafc;
  padding: 1rem;
  border-bottom: 1px solid #e5e7eb;
}

.info-card .card-header h4 {
  margin: 0;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #1f2937;
  font-weight: 600;
  font-size: 0.9rem;
}

.info-card .card-content {
  padding: 1rem;
}

.vendor-details {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.vendor-avatar {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 1.2rem;
}

.vendor-info h5 {
  margin: 0 0 0.25rem 0;
  color: #1f2937;
  font-weight: 600;
}

.vendor-info p {
  margin: 0 0 0.5rem 0;
  color: #6b7280;
  font-size: 0.8rem;
}

.vendor-contact div {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.25rem;
  font-size: 0.8rem;
  color: #6b7280;
}

.vendor-currency {
  color: #059669 !important;
  font-weight: 500 !important;
}

.invoice-details,
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

.detail-row span:first-child {
  color: #6b7280;
}

.detail-row span:last-child {
  color: #1f2937;
  font-weight: 500;
}

.detail-row .amount {
  color: #059669;
  font-weight: 600;
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
  .form-layout {
    grid-template-columns: 1fr;
  }
  
  .sidebar-info {
    order: -1;
  }
}

@media (max-width: 768px) {
  .payable-form-container {
    padding: 1rem;
  }
  
  .form-row {
    grid-template-columns: 1fr;
  }
  
  .progress-steps {
    flex-direction: column;
    gap: 1rem;
  }
  
  .step-connector {
    width: 2px;
    height: 40px;
  }
  
  .header-content {
    flex-direction: column;
    gap: 1rem;
    align-items: flex-start;
  }
  
  .action-buttons {
    flex-direction: column;
  }
}
</style>