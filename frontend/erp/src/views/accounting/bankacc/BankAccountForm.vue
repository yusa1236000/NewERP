<template>
  <div class="bank-account-form-container">
    <!-- Header Section -->
    <div class="page-header">
      <div class="header-content">
        <div class="header-left">
          <router-link to="/accounting/bank-accounts" class="back-link">
            <i class="fas fa-arrow-left"></i>
            Back to Bank Accounts
          </router-link>
          <h1 class="page-title">
            <i class="fas fa-university"></i>
            {{ isEdit ? 'Edit Bank Account' : 'Create New Bank Account' }}
          </h1>
          <p class="page-subtitle">
            {{ isEdit ? 'Update bank account information' : 'Add a new bank account to your organization' }}
          </p>
        </div>
        <div class="header-actions" v-if="isEdit">
          <router-link :to="`/accounting/bank-accounts/${accountId}`" class="btn btn-secondary">
            <i class="fas fa-eye"></i>
            View Details
          </router-link>
        </div>
      </div>
    </div>

    <!-- Form Card -->
    <div class="form-card">
      <form @submit.prevent="submitForm" class="bank-account-form">
        <!-- Bank Information Section -->
        <div class="form-section">
          <div class="section-header">
            <h3>
              <i class="fas fa-building"></i>
              Bank Information
            </h3>
            <p>Basic bank and account details</p>
          </div>
          
          <div class="form-grid">
            <div class="form-group">
              <label for="bank_name" class="form-label required">
                Bank Name
              </label>
              <input
                id="bank_name"
                v-model="form.bank_name"
                type="text"
                class="form-input"
                :class="{ 'error': errors.bank_name }"
                placeholder="e.g., Bank of America, Chase, Wells Fargo"
                maxlength="100"
                required
              >
              <div class="error-message" v-if="errors.bank_name">
                {{ errors.bank_name[0] }}
              </div>
            </div>

            <div class="form-group">
              <label for="account_number" class="form-label required">
                Account Number
              </label>
              <input
                id="account_number"
                v-model="form.account_number"
                type="text"
                class="form-input"
                :class="{ 'error': errors.account_number }"
                placeholder="Enter account number"
                maxlength="50"
                required
              >
              <div class="input-help">
                Your full account number (will be masked in lists for security)
              </div>
              <div class="error-message" v-if="errors.account_number">
                {{ errors.account_number[0] }}
              </div>
            </div>

            <div class="form-group">
              <label for="account_name" class="form-label required">
                Account Name
              </label>
              <input
                id="account_name"
                v-model="form.account_name"
                type="text"
                class="form-input"
                :class="{ 'error': errors.account_name }"
                placeholder="e.g., Main Operating Account, Payroll Account"
                maxlength="100"
                required
              >
              <div class="error-message" v-if="errors.account_name">
                {{ errors.account_name[0] }}
              </div>
            </div>

            <div class="form-group">
              <label for="gl_account_id" class="form-label required">
                General Ledger Account
              </label>
              <div class="select-wrapper">
                <select
                  id="gl_account_id"
                  v-model="form.gl_account_id"
                  class="form-select"
                  :class="{ 'error': errors.gl_account_id }"
                  required
                  @change="onGLAccountChange"
                >
                  <option value="">Select GL Account</option>
                  <option
                    v-for="account in assetAccounts"
                    :key="account.account_id"
                    :value="account.account_id"
                  >
                    {{ account.account_code }} - {{ account.name }}
                    {{ account.allow_multi_currency ? '(Multi-Currency)' : `(${account.default_currency})` }}
                  </option>
                </select>
                <i class="fas fa-chevron-down select-icon"></i>
              </div>
              <div class="input-help">
                This links the bank account to your chart of accounts
              </div>
              <div class="error-message" v-if="errors.gl_account_id">
                {{ errors.gl_account_id[0] }}
              </div>
            </div>
          </div>
        </div>

        <!-- Currency & Balance Section -->
        <div class="form-section">
          <div class="section-header">
            <h3>
              <i class="fas fa-coins"></i>
              Currency & Balance Information
            </h3>
            <p>Set the account currency and current balance</p>
          </div>
          
          <div class="form-grid">
            <div class="form-group">
              <label for="currency_code" class="form-label required">
                Account Currency
              </label>
              <div class="select-wrapper">
                <select
                  id="currency_code"
                  v-model="form.currency_code"
                  class="form-select"
                  :class="{ 'error': errors.currency_code }"
                  required
                  @change="onCurrencyChange"
                  :disabled="!selectedGLAccount"
                >
                  <option value="">Select Currency</option>
                  <option
                    v-for="currency in availableCurrencies"
                    :key="currency.code"
                    :value="currency.code"
                  >
                    {{ currency.code }} - {{ currency.name }} ({{ currency.symbol }})
                  </option>
                </select>
                <i class="fas fa-chevron-down select-icon"></i>
              </div>
              <div class="input-help" v-if="selectedGLAccount">
                <span v-if="selectedGLAccount.allow_multi_currency" class="text-success">
                  <i class="fas fa-check-circle"></i>
                  Multi-currency GL account - any currency allowed
                </span>
                <span v-else class="text-warning">
                  <i class="fas fa-exclamation-triangle"></i>
                  Single currency GL account - only {{ selectedGLAccount.default_currency }} allowed
                </span>
              </div>
              <div class="error-message" v-if="errors.currency_code">
                {{ errors.currency_code[0] }}
              </div>
            </div>

            <div class="form-group">
              <label for="current_balance" class="form-label required">
                Current Balance
              </label>
              <div class="currency-input">
                <span v-if="selectedCurrency" class="currency-symbol">
                  {{ selectedCurrency.symbol }}
                </span>
                <input
                  id="current_balance"
                  v-model="form.current_balance"
                  type="number"
                  step="0.01"
                  class="form-input"
                  :class="{ 'error': errors.current_balance, 'with-symbol': selectedCurrency }"
                  placeholder="0.00"
                  required
                  @input="calculateBaseCurrencyBalance"
                >
              </div>
              <div class="balance-info" v-if="form.current_balance && form.currency_code">
                <div class="balance-display">
                  <span class="balance-label">Account Balance:</span>
                  <span class="balance-amount" :class="getBalanceClass(form.current_balance)">
                    {{ formatCurrency(form.current_balance, form.currency_code) }}
                  </span>
                </div>
                <div v-if="form.currency_code !== baseCurrency && baseCurrencyBalance" class="balance-display">
                  <span class="balance-label">Base Currency ({{ baseCurrency }}):</span>
                  <span class="balance-amount">
                    {{ formatCurrency(baseCurrencyBalance, baseCurrency) }}
                  </span>
                </div>
              </div>
              <div class="error-message" v-if="errors.current_balance">
                {{ errors.current_balance[0] }}
              </div>
            </div>

            <!-- Exchange Rate Section -->
            <div v-if="form.currency_code && form.currency_code !== baseCurrency" class="form-group">
              <label for="exchange_rate" class="form-label">
                Exchange Rate ({{ form.currency_code }} to {{ baseCurrency }})
              </label>
              <div class="exchange-rate-input">
                <input
                  id="exchange_rate"
                  v-model="form.exchange_rate"
                  type="number"
                  step="0.000001"
                  min="0.000001"
                  class="form-input"
                  :class="{ 'error': errors.exchange_rate }"
                  placeholder="0.000000"
                  @input="calculateBaseCurrencyBalance"
                >
                <button
                  type="button"
                  @click="fetchCurrentExchangeRate"
                  class="fetch-rate-btn"
                  :disabled="fetchingRate"
                >
                  <i class="fas fa-sync-alt" :class="{ 'fa-spin': fetchingRate }"></i>
                  {{ fetchingRate ? 'Fetching...' : 'Get Current Rate' }}
                </button>
              </div>
              <div class="input-help">
                <span v-if="lastRateUpdate">
                  Last updated: {{ formatDate(lastRateUpdate) }}
                </span>
                <span v-else>
                  Leave empty to use current market rate
                </span>
              </div>
              <div class="error-message" v-if="errors.exchange_rate">
                {{ errors.exchange_rate[0] }}
              </div>
            </div>
          </div>
        </div>

        <!-- Additional Information Section -->
        <div class="form-section">
          <div class="section-header">
            <h3>
              <i class="fas fa-info-circle"></i>
              Additional Information
            </h3>
            <p>Optional details for better account management</p>
          </div>
          
          <div class="form-grid">
            <div class="form-group">
              <label for="bank_branch" class="form-label">
                Bank Branch
              </label>
              <input
                id="bank_branch"
                v-model="form.bank_branch"
                type="text"
                class="form-input"
                placeholder="e.g., Downtown Branch, Main Office"
                maxlength="100"
              >
            </div>

            <div class="form-group">
              <label for="routing_number" class="form-label">
                Routing Number
              </label>
              <input
                id="routing_number"
                v-model="form.routing_number"
                type="text"
                class="form-input"
                placeholder="e.g., 121000248"
                maxlength="20"
              >
            </div>

            <div class="form-group full-width">
              <label for="notes" class="form-label">
                Notes
              </label>
              <textarea
                id="notes"
                v-model="form.notes"
                class="form-textarea"
                placeholder="Any additional notes about this account..."
                maxlength="500"
                rows="3"
              ></textarea>
              <div class="character-count">
                {{ form.notes ? form.notes.length : 0 }}/500
              </div>
            </div>
          </div>
        </div>

        <!-- Form Actions -->
        <div class="form-actions">
          <div class="actions-left">
            <router-link to="/accounting/bank-accounts" class="btn btn-secondary">
              <i class="fas fa-times"></i>
              Cancel
            </router-link>
          </div>
          <div class="actions-right">
            <button type="submit" class="btn btn-primary" :disabled="submitting">
              <i class="fas" :class="submitting ? 'fa-spinner fa-spin' : 'fa-save'"></i>
              {{ submitting ? 'Saving...' : (isEdit ? 'Update Account' : 'Create Account') }}
            </button>
          </div>
        </div>
      </form>
    </div>

    <!-- Preview Card -->
    <div class="preview-card" v-if="form.bank_name || form.account_name">
      <div class="preview-header">
        <h3>
          <i class="fas fa-eye"></i>
          Preview
        </h3>
        <p>How this account will appear in the system</p>
      </div>
      
      <div class="account-preview">
        <div class="preview-icon">
          <i class="fas fa-university"></i>
        </div>
        <div class="preview-details">
          <h4>{{ form.bank_name || 'Bank Name' }}</h4>
          <p class="preview-account-name">{{ form.account_name || 'Account Name' }}</p>
          <p class="preview-account-number">
            {{ form.account_number ? `****${form.account_number.slice(-4)}` : '****XXXX' }}
          </p>
          <div class="preview-balance" v-if="form.current_balance">
            <span class="balance-label">Balance:</span>
            <span class="balance-amount" :class="getBalanceClass(form.current_balance)">
              {{ formatCurrency(form.current_balance, form.currency_code) }}
            </span>
          </div>
          <div v-if="baseCurrencyBalance && form.currency_code !== baseCurrency" class="preview-balance">
            <span class="balance-label">Base Currency:</span>
            <span class="balance-amount">
              {{ formatCurrency(baseCurrencyBalance, baseCurrency) }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading Overlay -->
    <div v-if="loadingAccount" class="loading-overlay">
      <div class="loading-content">
        <div class="loading-spinner"></div>
        <p>Loading account data...</p>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, computed, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import axios from 'axios'

export default {
  name: 'BankAccountForm',
  setup() {
    const router = useRouter()
    const route = useRoute()
    
    // Reactive data
    const form = ref({
      bank_name: '',
      account_number: '',
      account_name: '',
      current_balance: '',
      currency_code: '',
      exchange_rate: '',
      gl_account_id: '',
      bank_branch: '',
      routing_number: '',
      notes: ''
    })
    
    const errors = ref({})
    const submitting = ref(false)
    const loadingAccount = ref(false)
    const loadingGLAccounts = ref(false)
    const loadingCurrencies = ref(false)
    const fetchingRate = ref(false)
    const assetAccounts = ref([])
    const currencies = ref([])
    const lastRateUpdate = ref(null)
    const baseCurrency = ref('USD')
    
    // Computed properties
    const isEdit = computed(() => route.name === 'EditBankAccount')
    const accountId = computed(() => route.params.id)
    
    const selectedGLAccount = computed(() => {
      return assetAccounts.value.find(acc => acc.account_id == form.value.gl_account_id)
    })
    
    const selectedCurrency = computed(() => {
      return currencies.value.find(curr => curr.code === form.value.currency_code)
    })
    
    const availableCurrencies = computed(() => {
      if (!selectedGLAccount.value) return currencies.value
      
      if (selectedGLAccount.value.allow_multi_currency) {
        return currencies.value
      } else {
        return currencies.value.filter(curr => curr.code === selectedGLAccount.value.default_currency)
      }
    })
    
    const baseCurrencyBalance = computed(() => {
      if (!form.value.current_balance || !form.value.exchange_rate) return null
      return parseFloat(form.value.current_balance) * parseFloat(form.value.exchange_rate)
    })
    
    // Methods
    const loadAssetAccounts = async () => {
      loadingGLAccounts.value = true
      try {
        const response = await axios.get('/accounting/chart-of-accounts', {
          params: { account_type: 'Asset', is_active: true }
        })
        assetAccounts.value = response.data.data || []
      } catch (error) {
        console.error('Error loading GL accounts:', error)
        showToast('Failed to load GL accounts', 'error')
      } finally {
        loadingGLAccounts.value = false
      }
    }
    
    const loadCurrencies = async () => {
      loadingCurrencies.value = true
      try {
        const response = await axios.get('/accounting/system-currencies')
        currencies.value = response.data.data || []
      } catch (error) {
        console.error('Error loading currencies:', error)
        showToast('Failed to load currencies', 'error')
      } finally {
        loadingCurrencies.value = false
      }
    }
    
    const loadAccount = async () => {
      if (!isEdit.value) return
      
      loadingAccount.value = true
      try {
        const response = await axios.get(`/bank-accounts/${accountId.value}`)
        const account = response.data.data
        
        Object.keys(form.value).forEach(key => {
          if (account[key] !== undefined) {
            form.value[key] = account[key]
          }
        })
        
        lastRateUpdate.value = account.updated_at
      } catch (error) {
        console.error('Error loading account:', error)
        showToast('Failed to load account data', 'error')
        router.push('/accounting/bank-accounts')
      } finally {
        loadingAccount.value = false
      }
    }
    
    const onGLAccountChange = () => {
      // Reset currency if not compatible
      if (form.value.currency_code && selectedGLAccount.value && !selectedGLAccount.value.allow_multi_currency) {
        if (form.value.currency_code !== selectedGLAccount.value.default_currency) {
          form.value.currency_code = selectedGLAccount.value.default_currency
        }
      }
      
      // Set default currency if single currency account
      if (selectedGLAccount.value && !selectedGLAccount.value.allow_multi_currency && !form.value.currency_code) {
        form.value.currency_code = selectedGLAccount.value.default_currency
      }
    }
    
    const onCurrencyChange = () => {
      if (form.value.currency_code === baseCurrency.value) {
        form.value.exchange_rate = '1.000000'
      } else {
        form.value.exchange_rate = ''
        fetchCurrentExchangeRate()
      }
      calculateBaseCurrencyBalance()
    }
    
    const fetchCurrentExchangeRate = async () => {
      if (!form.value.currency_code || form.value.currency_code === baseCurrency.value) return
      
      fetchingRate.value = true
      try {
        const response = await axios.get('/bank-accounts/exchange-rate', {
          params: {
            from_currency: form.value.currency_code,
            to_currency: baseCurrency.value
          }
        })
        
        if (response.data.data) {
          form.value.exchange_rate = response.data.data.rate.toString()
          lastRateUpdate.value = response.data.data.date
          showToast('Exchange rate updated successfully', 'success')
        }
      } catch (error) {
        console.error('Error fetching exchange rate:', error)
        showToast('Could not fetch current exchange rate', 'warning')
      } finally {
        fetchingRate.value = false
      }
    }
    
    const calculateBaseCurrencyBalance = () => {
      // This is handled by computed property
    }
    
    const submitForm = async () => {
      submitting.value = true
      errors.value = {}
      
      try {
        const url = isEdit.value 
          ? `/bank-accounts/${accountId.value}`
          : '/bank-accounts'
        
        const method = isEdit.value ? 'put' : 'post'
        
        await axios[method](url, form.value)
        
        showToast(
          isEdit.value ? 'Bank account updated successfully' : 'Bank account created successfully',
          'success'
        )
        
        router.push('/accounting/bank-accounts')
      } catch (error) {
        if (error.response?.status === 422) {
          errors.value = error.response.data.errors || {}
        } else {
          showToast(
            error.response?.data?.message || 'An error occurred while saving the account',
            'error'
          )
        }
      } finally {
        submitting.value = false
      }
    }
    
    const formatCurrency = (amount, currencyCode = 'USD') => {
      if (!amount) return '0.00'
      const currency = currencies.value.find(c => c.code === currencyCode)
      const symbol = currency?.symbol || currencyCode
      return `${symbol} ${parseFloat(amount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`
    }
    
    const formatDate = (dateString) => {
      if (!dateString) return ''
      return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      })
    }
    
    const getBalanceClass = (balance) => {
      const amount = parseFloat(balance)
      if (amount > 0) return 'positive'
      if (amount < 0) return 'negative'
      return 'zero'
    }
    
    const showToast = (message, type = 'info') => {
      // Implement your toast notification here
      console.log(`${type.toUpperCase()}: ${message}`)
    }
    
    // Lifecycle
    onMounted(async () => {
      await Promise.all([
        loadAssetAccounts(),
        loadCurrencies()
      ])
      
      if (isEdit.value) {
        await loadAccount()
      }
    })
    
    // Watch for currency changes to auto-fetch rate
    watch(() => form.value.currency_code, (newCurrency, oldCurrency) => {
      if (newCurrency && newCurrency !== oldCurrency && newCurrency !== baseCurrency.value) {
        fetchCurrentExchangeRate()
      }
    })
    
    return {
      form,
      errors,
      submitting,
      loadingAccount,
      loadingGLAccounts,
      loadingCurrencies,
      fetchingRate,
      assetAccounts,
      currencies,
      lastRateUpdate,
      baseCurrency,
      isEdit,
      accountId,
      selectedGLAccount,
      selectedCurrency,
      availableCurrencies,
      baseCurrencyBalance,
      onGLAccountChange,
      onCurrencyChange,
      fetchCurrentExchangeRate,
      calculateBaseCurrencyBalance,
      submitForm,
      formatCurrency,
      formatDate,
      getBalanceClass
    }
  }
}
</script>

<style scoped>
/* Container */
.bank-account-form-container {
  padding: 2rem;
  background: var(--gray-50, #f9fafb);
  min-height: 100vh;
}

/* Header */
.page-header {
  margin-bottom: 2rem;
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
}

.header-left {
  flex: 1;
}

.back-link {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  color: var(--primary-color, #2563eb);
  text-decoration: none;
  font-weight: 500;
  margin-bottom: 1rem;
  transition: color 0.2s ease;
}

.back-link:hover {
  color: var(--primary-dark, #1d4ed8);
}

.page-title {
  font-size: 2rem;
  font-weight: 700;
  color: var(--gray-900, #111827);
  margin: 0 0 0.5rem 0;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.page-subtitle {
  color: var(--gray-600, #4b5563);
  font-size: 1.1rem;
  margin: 0;
}

.header-actions {
  display: flex;
  gap: 1rem;
}

/* Form Card */
.form-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  overflow: hidden;
  margin-bottom: 2rem;
}

.bank-account-form {
  padding: 2rem;
}

/* Form Sections */
.form-section {
  margin-bottom: 3rem;
}

.section-header {
  margin-bottom: 2rem;
  padding-bottom: 1rem;
  border-bottom: 2px solid var(--gray-100, #f3f4f6);
}

.section-header h3 {
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--gray-900, #111827);
  margin: 0 0 0.5rem 0;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.section-header p {
  color: var(--gray-600, #4b5563);
  margin: 0;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 1.5rem;
}

.form-group.full-width {
  grid-column: 1 / -1;
}

.form-group {
  display: flex;
  flex-direction: column;
}

.form-label {
  font-weight: 600;
  color: var(--gray-700, #374151);
  margin-bottom: 0.5rem;
  font-size: 0.95rem;
}

.form-label.required::after {
  content: " *";
  color: var(--danger-color, #dc2626);
}

.form-input,
.form-select,
.form-textarea {
  width: 100%;
  padding: 0.75rem 1rem;
  border: 2px solid var(--gray-200, #e5e7eb);
  border-radius: 8px;
  font-size: 1rem;
  transition: all 0.2s ease;
  background: white;
}

.form-input:focus,
.form-select:focus,
.form-textarea:focus {
  outline: none;
  border-color: var(--primary-color, #2563eb);
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.form-input.error,
.form-select.error,
.form-textarea.error {
  border-color: var(--danger-color, #dc2626);
}

.form-input.with-symbol {
  padding-left: 3rem;
}

.select-wrapper {
  position: relative;
}

.select-icon {
  position: absolute;
  right: 1rem;
  top: 50%;
  transform: translateY(-50%);
  color: var(--gray-400, #9ca3af);
  pointer-events: none;
}

.currency-input {
  position: relative;
  display: flex;
  align-items: center;
}

.currency-symbol {
  position: absolute;
  left: 1rem;
  color: var(--gray-600, #4b5563);
  font-weight: 600;
  z-index: 1;
}

.exchange-rate-input {
  display: flex;
  gap: 0.5rem;
}

.exchange-rate-input .form-input {
  flex: 1;
}

.fetch-rate-btn {
  padding: 0.75rem 1rem;
  background: var(--primary-color, #2563eb);
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.2s ease;
  white-space: nowrap;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.fetch-rate-btn:hover:not(:disabled) {
  background: var(--primary-dark, #1d4ed8);
}

.fetch-rate-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.form-textarea {
  resize: vertical;
  min-height: 80px;
}

.input-help {
  font-size: 0.85rem;
  color: var(--gray-500, #6b7280);
  margin-top: 0.25rem;
}

.text-success {
  color: var(--success-color, #059669);
}

.text-warning {
  color: var(--warning-color, #d97706);
}

.character-count {
  font-size: 0.85rem;
  color: var(--gray-500, #6b7280);
  margin-top: 0.25rem;
  text-align: right;
}

.error-message {
  color: var(--danger-color, #dc2626);
  font-size: 0.85rem;
  margin-top: 0.25rem;
  font-weight: 500;
}

.balance-info {
  margin-top: 0.75rem;
  padding: 1rem;
  background: var(--gray-50, #f9fafb);
  border-radius: 8px;
  border: 1px solid var(--gray-200, #e5e7eb);
}

.balance-display {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.5rem;
}

.balance-display:last-child {
  margin-bottom: 0;
}

.balance-label {
  font-weight: 500;
  color: var(--gray-600, #4b5563);
}

.balance-amount {
  font-weight: 600;
  font-family: 'Courier New', monospace;
}

.balance-amount.positive {
  color: var(--success-color, #059669);
}

.balance-amount.negative {
  color: var(--danger-color, #dc2626);
}

.balance-amount.zero {
  color: var(--gray-500, #6b7280);
}

/* Form Actions */
.form-actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 2rem;
  border-top: 2px solid var(--gray-100, #f3f4f6);
  margin-top: 2rem;
}

.actions-left,
.actions-right {
  display: flex;
  gap: 1rem;
}

/* Buttons */
.btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 500;
  text-decoration: none;
  border: none;
  cursor: pointer;
  transition: all 0.2s ease;
  font-size: 0.95rem;
}

.btn-primary {
  background: var(--primary-color, #2563eb);
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: var(--primary-dark, #1d4ed8);
}

.btn-secondary {
  background: var(--gray-100, #f3f4f6);
  color: var(--gray-700, #374151);
}

.btn-secondary:hover {
  background: var(--gray-200, #e5e7eb);
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* Preview Card */
.preview-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  overflow: hidden;
  margin-bottom: 2rem;
}

.preview-header {
  padding: 1.5rem 2rem 1rem 2rem;
  border-bottom: 1px solid var(--gray-200, #e5e7eb);
}

.preview-header h3 {
  font-size: 1.125rem;
  font-weight: 600;
  color: var(--gray-900, #111827);
  margin: 0 0 0.25rem 0;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.preview-header p {
  color: var(--gray-600, #4b5563);
  margin: 0;
  font-size: 0.9rem;
}

.account-preview {
  padding: 2rem;
  display: flex;
  align-items: flex-start;
  gap: 1rem;
}

.preview-icon {
  width: 60px;
  height: 60px;
  background: var(--primary-color, #2563eb);
  color: white;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  flex-shrink: 0;
}

.preview-details h4 {
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--gray-900, #111827);
  margin: 0 0 0.5rem 0;
}

.preview-account-name {
  color: var(--gray-600, #4b5563);
  margin: 0 0 0.25rem 0;
  font-weight: 500;
}

.preview-account-number {
  color: var(--gray-500, #6b7280);
  margin: 0 0 1rem 0;
  font-family: 'Courier New', monospace;
  font-size: 0.9rem;
}

.preview-balance {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.5rem;
}

.preview-balance:last-child {
  margin-bottom: 0;
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
  background: white;
  padding: 2rem;
  border-radius: 12px;
  text-align: center;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
}

.loading-spinner {
  width: 40px;
  height: 40px;
  border: 4px solid var(--gray-200, #e5e7eb);
  border-left: 4px solid var(--primary-color, #2563eb);
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 1rem auto;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* Responsive Design */
@media (max-width: 768px) {
  .bank-account-form-container {
    padding: 1rem;
  }
  
  .header-content {
    flex-direction: column;
    gap: 1rem;
  }
  
  .form-grid {
    grid-template-columns: 1fr;
  }
  
  .form-actions {
    flex-direction: column;
    gap: 1rem;
  }
  
  .actions-left,
  .actions-right {
    width: 100%;
    justify-content: center;
  }
  
  .account-preview {
    flex-direction: column;
    text-align: center;
  }
  
  .exchange-rate-input {
    flex-direction: column;
  }
}
</style>