<template>
  <div class="bank-reconciliation-form">
    <!-- Page Header -->
    <div class="page-header">
      <div class="header-content">
        <div class="title-section">
          <h1 class="page-title">
            <i class="fas fa-balance-scale"></i>
            {{ isEdit ? 'Edit' : 'Create' }} Bank Reconciliation
          </h1>
          <p class="page-subtitle">{{ isEdit ? 'Update existing' : 'Create new' }} bank reconciliation with multi-currency support</p>
        </div>
        <div class="header-actions">
          <button @click="goBack" class="btn-secondary">
            <i class="fas fa-arrow-left"></i>
            Back
          </button>
          <button @click="saveDraft" class="btn-outline" :disabled="saving" v-if="!isEdit">
            <i class="fas fa-save"></i>
            Save Draft
          </button>
          <button @click="saveReconciliation" class="btn-primary" :disabled="saving">
            <i class="fas fa-spinner fa-spin" v-if="saving"></i>
            <i class="fas fa-save" v-else></i>
            {{ saving ? 'Saving...' : (isEdit ? 'Update' : 'Create') }}
          </button>
        </div>
      </div>
    </div>

    <!-- Form Content -->
    <div class="form-container">
      <form @submit.prevent="saveReconciliation" class="reconciliation-form">
        <!-- Bank Account Selection -->
        <div class="form-section">
          <div class="section-header">
            <h2>
              <i class="fas fa-university"></i>
              Bank Account Information
            </h2>
          </div>
          
          <div class="form-grid">
            <div class="form-group">
              <label class="form-label">Bank Account *</label>
              <select v-model="form.bank_id" 
                      @change="onBankAccountChange"
                      class="form-select" 
                      required
                      :disabled="isEdit">
                <option value="">Select Bank Account</option>
                <option v-for="bank in bankAccounts" 
                        :key="bank.bank_id" 
                        :value="bank.bank_id">
                  {{ bank.bank_name }} - {{ bank.account_number }} ({{ bank.currency }})
                </option>
              </select>
            </div>
            
            <div class="form-group" v-if="selectedBankAccount">
              <label class="form-label">Current Bank Balance</label>
              <div class="bank-balance">
                <span class="balance-amount">
                  {{ formatCurrency(selectedBankAccount.current_balance, selectedBankAccount.currency) }}
                </span>
                <small class="balance-date" v-if="selectedBankAccount.last_reconciled_date">
                  Last reconciled: {{ formatDate(selectedBankAccount.last_reconciled_date) }}
                </small>
                <small class="balance-date" v-else>
                  Never reconciled
                </small>
              </div>
            </div>
          </div>

          <div class="bank-details" v-if="selectedBankAccount">
            <div class="bank-details-grid">
              <div class="bank-detail">
                <label>Currency:</label>
                <span class="currency-badge">{{ selectedBankAccount.currency }}</span>
              </div>
              <div class="bank-detail" v-if="selectedBankAccount.currency !== baseCurrency">
                <label>Base Currency ({{ baseCurrency }}):</label>
                <span>{{ formatCurrency(selectedBankAccount.base_currency_balance, baseCurrency) }}</span>
              </div>
              <div class="bank-detail">
                <label>Bank Type:</label>
                <span>{{ selectedBankAccount.bank_type || 'Standard' }}</span>
              </div>
              <div class="bank-detail">
                <label>Status:</label>
                <span :class="['status-badge', 'status-' + (selectedBankAccount.status || 'active').toLowerCase()]">
                  {{ selectedBankAccount.status || 'Active' }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Reconciliation Details -->
        <div class="form-section">
          <div class="section-header">
            <h2>
              <i class="fas fa-calendar-alt"></i>
              Reconciliation Details
            </h2>
          </div>
          
          <div class="form-grid">
            <div class="form-group">
              <label class="form-label">Statement Date *</label>
              <input type="date" 
                     v-model="form.statement_date" 
                     @change="onDateChange"
                     class="form-input" 
                     required>
            </div>
            
            <div class="form-group">
              <label class="form-label">Statement Reference</label>
              <input type="text" 
                     v-model="form.statement_reference" 
                     class="form-input" 
                     placeholder="e.g., Statement #2024-01">
            </div>
            
            <div class="form-group">
              <label class="form-label">Reconciler Name</label>
              <input type="text" 
                     v-model="form.reconciler_name" 
                     class="form-input" 
                     placeholder="Enter reconciler name">
            </div>
            
            <div class="form-group">
              <label class="form-label">Status</label>
              <select v-model="form.status" class="form-select">
                <option value="Draft">Draft</option>
                <option value="In Progress">In Progress</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Balance Information -->
        <div class="form-section">
          <div class="section-header">
            <h2>
              <i class="fas fa-calculator"></i>
              Balance Information
            </h2>
            <div class="exchange-rate-info" v-if="exchangeRate && selectedBankAccount?.currency !== baseCurrency">
              <span class="rate-label">Exchange Rate:</span>
              <span class="rate-value">
                1 {{ selectedBankAccount.currency }} = {{ exchangeRate.toFixed(4) }} {{ baseCurrency }}
              </span>
              <small class="rate-date">{{ formatDate(form.statement_date) }}</small>
            </div>
          </div>
          
          <div class="balance-grid">
            <div class="balance-group">
              <h3>Statement Balance</h3>
              <div class="balance-input-group">
                <div class="form-group">
                  <label class="form-label">Amount ({{ selectedBankAccount?.currency || 'USD' }}) *</label>
                  <input type="number" 
                         v-model.number="form.statement_balance" 
                         @input="calculateDifference"
                         class="form-input balance-input" 
                         step="0.01" 
                         required>
                </div>
                <div class="base-currency-amount" v-if="selectedBankAccount?.currency !== baseCurrency">
                  <label>{{ baseCurrency }} Equivalent:</label>
                  <span>{{ formatCurrency(baseCurrencyStatementBalance, baseCurrency) }}</span>
                </div>
              </div>
            </div>
            
            <div class="balance-group">
              <h3>Book Balance</h3>
              <div class="balance-input-group">
                <div class="form-group">
                  <label class="form-label">Amount ({{ selectedBankAccount?.currency || 'USD' }}) *</label>
                  <input type="number" 
                         v-model.number="form.book_balance" 
                         @input="calculateDifference"
                         class="form-input balance-input" 
                         step="0.01" 
                         required>
                </div>
                <div class="base-currency-amount" v-if="selectedBankAccount?.currency !== baseCurrency">
                  <label>{{ baseCurrency }} Equivalent:</label>
                  <span>{{ formatCurrency(baseCurrencyBookBalance, baseCurrency) }}</span>
                </div>
              </div>
            </div>
            
            <div class="balance-group">
              <h3>Difference</h3>
              <div class="difference-display" :class="getDifferenceClass(difference)">
                <div class="difference-amount">
                  {{ formatCurrency(difference, selectedBankAccount?.currency || 'USD') }}
                </div>
                <div class="difference-status">
                  <span v-if="Math.abs(difference) <= 0.01" class="balanced">
                    <i class="fas fa-check-circle"></i>
                    Balanced
                  </span>
                  <span v-else class="unbalanced">
                    <i class="fas fa-exclamation-triangle"></i>
                    {{ difference > 0 ? 'Over' : 'Under' }} by {{ formatCurrency(Math.abs(difference), selectedBankAccount?.currency || 'USD') }}
                  </span>
                </div>
                <div class="base-currency-amount" v-if="selectedBankAccount?.currency !== baseCurrency">
                  <label>{{ baseCurrency }} Difference:</label>
                  <span>{{ formatCurrency(baseCurrencyDifference, baseCurrency) }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Notes Section -->
        <div class="form-section">
          <div class="section-header">
            <h2>
              <i class="fas fa-sticky-note"></i>
              Additional Information
            </h2>
          </div>
          
          <div class="form-group">
            <label class="form-label">Notes</label>
            <textarea v-model="form.notes" 
                      class="form-textarea" 
                      rows="4" 
                      placeholder="Enter any additional notes or comments about this reconciliation..."></textarea>
          </div>
        </div>

        <!-- Validation Errors -->
        <div class="validation-errors" v-if="errors.length > 0">
          <div class="validation-header">
            <i class="fas fa-exclamation-triangle"></i>
            <h4>Please fix the following errors:</h4>
          </div>
          <ul class="validation-list">
            <li v-for="error in errors" :key="error">{{ error }}</li>
          </ul>
        </div>
      </form>
    </div>

    <!-- Loading Overlay -->
    <div v-if="loading" class="loading-overlay">
      <div class="loading-content">
        <div class="loading-spinner">
          <i class="fas fa-spinner fa-spin"></i>
        </div>
        <p>{{ loadingMessage }}</p>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'BankReconciliationForm',
  data() {
    return {
      form: {
        bank_id: '',
        statement_date: '',
        statement_balance: null,
        book_balance: null,
        statement_reference: '',
        reconciler_name: '',
        status: 'Draft',
        notes: ''
      },
      bankAccounts: [],
      selectedBankAccount: null,
      exchangeRate: null,
      baseCurrency: 'USD',
      errors: [],
      loading: false,
      saving: false,
      loadingMessage: 'Loading...'
    }
  },
  
  computed: {
    isEdit() {
      return this.$route.params.id && this.$route.params.id !== 'new'
    },
    
    reconciliationId() {
      return this.$route.params.id
    },
    
    difference() {
      if (this.form.statement_balance === null || this.form.book_balance === null) {
        return 0
      }
      return this.form.statement_balance - this.form.book_balance
    },
    
    baseCurrencyStatementBalance() {
      if (!this.form.statement_balance || !this.exchangeRate) return 0
      return this.form.statement_balance * this.exchangeRate
    },
    
    baseCurrencyBookBalance() {
      if (!this.form.book_balance || !this.exchangeRate) return 0
      return this.form.book_balance * this.exchangeRate
    },
    
    baseCurrencyDifference() {
      if (!this.exchangeRate) return 0
      return this.difference * this.exchangeRate
    }
  },
  
  created() {
    this.loadBankAccounts()
    this.loadBaseCurrency()
    
    if (this.isEdit) {
      this.loadReconciliation()
    } else {
      // Set default date to today
      this.form.statement_date = new Date().toISOString().split('T')[0]
    }
  },
  
  methods: {
    async loadBaseCurrency() {
      try {
        // This should come from a config API endpoint
        this.baseCurrency = 'USD' // Default, should be configurable
      } catch (error) {
        console.error('Error loading base currency:', error)
      }
    },
    
    async loadBankAccounts() {
      this.loading = true
      this.loadingMessage = 'Loading bank accounts...'
      try {
        const response = await axios.get('/accounting/bank-accounts')
        this.bankAccounts = response.data.data || response.data
      } catch (error) {
        console.error('Error loading bank accounts:', error)
        this.$toast.error('Failed to load bank accounts')
      } finally {
        this.loading = false
      }
    },
    
    async loadReconciliation() {
      this.loading = true
      this.loadingMessage = 'Loading reconciliation...'
      try {
        const response = await axios.get(`/accounting/bank-reconciliations/${this.reconciliationId}`)
        const reconciliation = response.data.data
        
        this.form = {
          bank_id: reconciliation.bank_id,
          statement_date: reconciliation.statement_date,
          statement_balance: reconciliation.statement_balance,
          book_balance: reconciliation.book_balance,
          statement_reference: reconciliation.statement_reference || '',
          reconciler_name: reconciliation.reconciler_name || '',
          status: reconciliation.status,
          notes: reconciliation.notes || ''
        }
        
        // Set selected bank account
        this.selectedBankAccount = reconciliation.bank_account
        this.exchangeRate = reconciliation.exchange_rate
        
      } catch (error) {
        console.error('Error loading reconciliation:', error)
        this.$toast.error('Failed to load reconciliation')
        this.goBack()
      } finally {
        this.loading = false
      }
    },
    
    onBankAccountChange() {
      this.selectedBankAccount = this.bankAccounts.find(bank => bank.bank_id === this.form.bank_id)
      if (this.selectedBankAccount) {
        // Set book balance to current bank balance
        this.form.book_balance = this.selectedBankAccount.current_balance
        this.loadExchangeRate()
      }
    },
    
    onDateChange() {
      if (this.selectedBankAccount && this.selectedBankAccount.currency !== this.baseCurrency) {
        this.loadExchangeRate()
      }
    },
    
    async loadExchangeRate() {
      if (!this.selectedBankAccount || !this.form.statement_date) return
      if (this.selectedBankAccount.currency === this.baseCurrency) {
        this.exchangeRate = 1
        return
      }
      
      try {
        // This should call an exchange rate API endpoint
        const response = await axios.get('/accounting/exchange-rates', {
          params: {
            from_currency: this.selectedBankAccount.currency,
            to_currency: this.baseCurrency,
            date: this.form.statement_date
          }
        })
        this.exchangeRate = response.data.rate
      } catch (error) {
        console.error('Error loading exchange rate:', error)
        // Fallback to 1:1 if exchange rate service is not available
        this.exchangeRate = 1
        this.$toast.warning('Could not load current exchange rate, using 1:1 ratio')
      }
    },
    
    calculateDifference() {
      // This is computed automatically, but we might want to trigger other calculations here
    },
    
    validateForm() {
      this.errors = []
      
      if (!this.form.bank_id) {
        this.errors.push('Bank account is required')
      }
      
      if (!this.form.statement_date) {
        this.errors.push('Statement date is required')
      }
      
      if (this.form.statement_balance === null || this.form.statement_balance === '') {
        this.errors.push('Statement balance is required')
      }
      
      if (this.form.book_balance === null || this.form.book_balance === '') {
        this.errors.push('Book balance is required')
      }
      
      // Check if statement date is in the future
      if (this.form.statement_date && new Date(this.form.statement_date) > new Date()) {
        this.errors.push('Statement date cannot be in the future')
      }
      
      // Validate amounts are numbers
      if (isNaN(this.form.statement_balance)) {
        this.errors.push('Statement balance must be a valid number')
      }
      
      if (isNaN(this.form.book_balance)) {
        this.errors.push('Book balance must be a valid number')
      }
      
      return this.errors.length === 0
    },
    
    async saveDraft() {
      if (!this.validateForm()) return
      
      this.form.status = 'Draft'
      await this.submitForm()
    },
    
    async saveReconciliation() {
      if (!this.validateForm()) return
      
      await this.submitForm()
    },
    
    async submitForm() {
      this.saving = true
      try {
        const payload = {
          ...this.form,
          difference: this.difference,
          base_currency_statement_balance: this.baseCurrencyStatementBalance,
          base_currency_book_balance: this.baseCurrencyBookBalance,
          base_currency_difference: this.baseCurrencyDifference,
          exchange_rate: this.exchangeRate
        }
        
        let response
        if (this.isEdit) {
          response = await axios.put(`/accounting/bank-reconciliations/${this.reconciliationId}`, payload)
          this.$toast.success('Reconciliation updated successfully')
        } else {
          response = await axios.post('/accounting/bank-reconciliations', payload)
          this.$toast.success('Reconciliation created successfully')
        }
        
        // Redirect to detail view
        const reconciliationId = response.data.data.reconciliation_id
        this.$router.push(`/accounting/bank-reconciliations/${reconciliationId}`)
        
      } catch (error) {
        console.error('Error saving reconciliation:', error)
        
        if (error.response?.data?.errors) {
          this.errors = Object.values(error.response.data.errors).flat()
        } else {
          this.$toast.error(error.response?.data?.message || 'Failed to save reconciliation')
        }
      } finally {
        this.saving = false
      }
    },
    
    goBack() {
      if (this.isEdit) {
        this.$router.push(`/accounting/bank-reconciliations/${this.reconciliationId}`)
      } else {
        this.$router.push('/accounting/bank-reconciliations')
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
    
    formatCurrency(amount, currency = 'USD') {
      if (amount === null || amount === undefined) return '-'
      return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: currency,
        minimumFractionDigits: 2
      }).format(amount)
    },
    
    getDifferenceClass(difference) {
      if (Math.abs(difference) <= 0.01) return 'difference-zero'
      return difference > 0 ? 'difference-positive' : 'difference-negative'
    }
  }
}
</script>

<style scoped>
/* CSS Variables */
:root {
  --primary-color: #2563eb;
  --primary-dark: #1d4ed8;
  --success-color: #059669;
  --warning-color: #d97706;
  --danger-color: #dc2626;
  --gray-50: #f8fafc;
  --gray-100: #f1f5f9;
  --gray-200: #e2e8f0;
  --gray-300: #cbd5e1;
  --gray-400: #94a3b8;
  --gray-500: #64748b;
  --gray-600: #475569;
  --gray-700: #334155;
  --gray-800: #1e293b;
  --gray-900: #0f172a;
  --white: #ffffff;
  --border-radius: 12px;
  --box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
  --box-shadow-lg: 0 8px 32px rgba(0, 0, 0, 0.1);
  --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.bank-reconciliation-form {
  min-height: 100vh;
  background: var(--gray-50);
  padding: 2rem;
}

/* Page Header */
.page-header {
  position: sticky;
  top: 0;
  z-index: 100;
  background: var(--white);
  border-radius: var(--border-radius);
  padding: 2rem;
  margin-bottom: 2rem;
  box-shadow: var(--box-shadow);
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 2rem;
}

.title-section {
  flex: 1;
}

.page-title {
  font-size: 2rem;
  font-weight: 700;
  color: var(--gray-900);
  margin: 0 0 0.5rem 0;
  display: flex;
  align-items: center;
  gap: 1rem;
}

.page-title i {
  color: var(--primary-color);
}

.page-subtitle {
  color: var(--gray-600);
  font-size: 1rem;
  margin: 0;
}

.header-actions {
  display: flex;
  gap: 1rem;
}

/* Buttons */
.btn-primary, .btn-secondary, .btn-outline {
  padding: 0.75rem 1.5rem;
  border-radius: var(--border-radius);
  font-weight: 600;
  font-size: 0.875rem;
  border: none;
  cursor: pointer;
  transition: var(--transition);
  display: flex;
  align-items: center;
  gap: 0.5rem;
  text-decoration: none;
}

.btn-primary {
  background: var(--primary-color);
  color: var(--white);
}

.btn-primary:hover:not(:disabled) {
  background: var(--primary-dark);
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(37, 99, 235, 0.3);
}

.btn-primary:disabled {
  background: var(--gray-300);
  color: var(--gray-500);
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}

.btn-secondary {
  background: var(--gray-200);
  color: var(--gray-700);
}

.btn-secondary:hover {
  background: var(--gray-300);
}

.btn-outline {
  background: transparent;
  color: var(--gray-700);
  border: 2px solid var(--gray-300);
}

.btn-outline:hover:not(:disabled) {
  background: var(--gray-100);
  border-color: var(--gray-400);
}

/* Form Container */
.form-container {
  max-width: 1200px;
  margin: 0 auto;
}

.reconciliation-form {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

/* Form Sections */
.form-section {
  background: var(--white);
  border-radius: var(--border-radius);
  padding: 2rem;
  box-shadow: var(--box-shadow);
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  padding-bottom: 1rem;
  border-bottom: 2px solid var(--gray-200);
}

.section-header h2 {
  font-size: 1.5rem;
  font-weight: 600;
  color: var(--gray-900);
  margin: 0;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.section-header h2 i {
  color: var(--primary-color);
}

.exchange-rate-info {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 0.25rem;
}

.rate-label {
  font-size: 0.875rem;
  color: var(--gray-600);
  font-weight: 500;
}

.rate-value {
  font-size: 1rem;
  color: var(--gray-900);
  font-weight: 700;
  font-family: monospace;
}

.rate-date {
  font-size: 0.75rem;
  color: var(--gray-500);
}

/* Form Elements */
.form-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-label {
  font-weight: 600;
  color: var(--gray-700);
  font-size: 0.875rem;
}

.form-input, .form-select, .form-textarea {
  padding: 0.875rem;
  border: 2px solid var(--gray-300);
  border-radius: 8px;
  font-size: 0.875rem;
  transition: var(--transition);
  background: var(--white);
}

.form-input:focus, .form-select:focus, .form-textarea:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.form-input:disabled, .form-select:disabled {
  background: var(--gray-100);
  color: var(--gray-500);
  cursor: not-allowed;
}

.form-textarea {
  resize: vertical;
  min-height: 100px;
}

/* Bank Account Section */
.bank-balance {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.balance-amount {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--gray-900);
  font-family: monospace;
}

.balance-date {
  font-size: 0.75rem;
  color: var(--gray-500);
}

.bank-details {
  margin-top: 1.5rem;
  padding-top: 1.5rem;
  border-top: 1px solid var(--gray-200);
}

.bank-details-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}

.bank-detail {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem;
  background: var(--gray-50);
  border-radius: 8px;
}

.bank-detail label {
  font-weight: 500;
  color: var(--gray-600);
  font-size: 0.875rem;
}

.bank-detail span {
  font-weight: 600;
  color: var(--gray-900);
}

.currency-badge {
  background: var(--primary-color);
  color: var(--white);
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-weight: 600;
  font-size: 0.75rem;
}

.status-badge {
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-weight: 600;
  font-size: 0.75rem;
  text-transform: uppercase;
}

.status-active {
  background: rgba(5, 150, 105, 0.1);
  color: var(--success-color);
}

.status-inactive {
  background: rgba(107, 114, 128, 0.1);
  color: var(--gray-600);
}

/* Balance Section */
.balance-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 2rem;
}

.balance-group {
  border: 2px solid var(--gray-200);
  border-radius: var(--border-radius);
  padding: 1.5rem;
  background: var(--gray-50);
}

.balance-group h3 {
  font-size: 1.125rem;
  font-weight: 600;
  color: var(--gray-900);
  margin: 0 0 1rem 0;
  text-align: center;
}

.balance-input-group {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.balance-input {
  font-size: 1.125rem;
  font-weight: 600;
  text-align: center;
  font-family: monospace;
}

.base-currency-amount {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem;
  background: var(--white);
  border-radius: 6px;
  border: 1px solid var(--gray-200);
}

.base-currency-amount label {
  font-size: 0.875rem;
  color: var(--gray-600);
  font-weight: 500;
}

.base-currency-amount span {
  font-weight: 600;
  color: var(--gray-900);
  font-family: monospace;
}

.difference-display {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.75rem;
  padding: 1rem;
  background: var(--white);
  border-radius: 8px;
  border: 2px solid var(--gray-200);
}

.difference-display.difference-zero {
  border-color: var(--success-color);
  background: rgba(5, 150, 105, 0.02);
}

.difference-display.difference-positive {
  border-color: var(--success-color);
  background: rgba(5, 150, 105, 0.02);
}

.difference-display.difference-negative {
  border-color: var(--danger-color);
  background: rgba(220, 38, 38, 0.02);
}

.difference-amount {
  font-size: 1.5rem;
  font-weight: 700;
  font-family: monospace;
}

.difference-display.difference-zero .difference-amount {
  color: var(--success-color);
}

.difference-display.difference-positive .difference-amount {
  color: var(--success-color);
}

.difference-display.difference-negative .difference-amount {
  color: var(--danger-color);
}

.difference-status {
  text-align: center;
}

.difference-status .balanced {
  color: var(--success-color);
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.875rem;
}

.difference-status .unbalanced {
  color: var(--danger-color);
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.875rem;
}

/* Validation Errors */
.validation-errors {
  background: rgba(220, 38, 38, 0.05);
  border: 2px solid rgba(220, 38, 38, 0.2);
  border-radius: var(--border-radius);
  padding: 1.5rem;
  margin-top: 2rem;
}

.validation-header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 1rem;
}

.validation-header i {
  color: var(--danger-color);
  font-size: 1.25rem;
}

.validation-header h4 {
  color: var(--danger-color);
  margin: 0;
  font-size: 1rem;
}

.validation-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

.validation-list li {
  color: var(--danger-color);
  padding: 0.25rem 0;
  font-size: 0.875rem;
}

.validation-list li::before {
  content: '•';
  margin-right: 0.5rem;
  font-weight: bold;
}

/* Loading Overlay */
.loading-overlay {
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

.loading-content {
  background: var(--white);
  border-radius: var(--border-radius);
  padding: 2rem;
  text-align: center;
  box-shadow: var(--box-shadow-lg);
}

.loading-spinner {
  font-size: 2rem;
  color: var(--primary-color);
  margin-bottom: 1rem;
}

.loading-content p {
  color: var(--gray-600);
  margin: 0;
}

/* Responsive Design */
@media (max-width: 1024px) {
  .bank-reconciliation-form {
    padding: 1rem;
  }

  .page-header {
    top: 1rem;
  }

  .header-content {
    flex-direction: column;
    align-items: stretch;
    gap: 1rem;
  }

  .header-actions {
    justify-content: center;
  }

  .form-grid {
    grid-template-columns: 1fr;
  }

  .balance-grid {
    grid-template-columns: 1fr;
    gap: 1rem;
  }

  .bank-details-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .title-section {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.5rem;
  }

  .page-title {
    font-size: 1.5rem;
  }

  .form-section {
    padding: 1.5rem;
  }

  .section-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.5rem;
  }

  .exchange-rate-info {
    align-items: flex-start;
  }

  .bank-detail {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.25rem;
  }

  .base-currency-amount {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.25rem;
  }

  .difference-status .balanced,
  .difference-status .unbalanced {
    flex-direction: column;
    gap: 0.125rem;
  }
}
</style>