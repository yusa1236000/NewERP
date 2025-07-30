<template>
  <AppLayout>
    <template #page-title>{{ isEdit ? 'Edit Budget' : 'Create New Budget' }}</template>
    <template #page-subtitle>{{ isEdit ? 'Update budget information and multi-currency settings' : 'Set up a new budget with multi-currency support' }}</template>
    
    <template #page-actions>
      <button @click="goBack" class="action-button secondary">
        <i class="fas fa-arrow-left"></i>
        Back to List
      </button>
      <button @click="resetForm" class="action-button secondary" :disabled="loading">
        <i class="fas fa-undo"></i>
        Reset
      </button>
      <button v-if="isEdit" @click="copyFromPrevious" class="action-button secondary" :disabled="loading">
        <i class="fas fa-copy"></i>
        Copy from Previous
      </button>
    </template>

    <div class="budget-form-container">
      <div class="form-layout">
        <!-- Main Form -->
        <div class="form-section">
          <div class="form-card">
            <div class="form-header">
              <h3>
                <i class="fas fa-edit"></i>
                Budget Information
              </h3>
              <p>Enter the budget details with multi-currency support for the selected account and period</p>
            </div>
            
            <form @submit.prevent="saveBudget" class="budget-form">
              <!-- Account Selection -->
              <div class="form-group">
                <label for="account_id" class="form-label required">
                  <i class="fas fa-chart-pie"></i>
                  Chart of Account
                </label>
                <div class="select-wrapper">
                  <select 
                    id="account_id" 
                    v-model="formData.account_id" 
                    :disabled="isEdit || loading"
                    class="form-select"
                    :class="{ 'error': errors.account_id }"
                    @change="onAccountChange"
                  >
                    <option value="">Select an account</option>
                    <optgroup v-for="(accounts, type) in groupedAccounts" :key="type" :label="type">
                      <option v-for="account in accounts" :key="account.account_id" :value="account.account_id">
                        {{ account.account_code }} - {{ account.name }}
                      </option>
                    </optgroup>
                  </select>
                  <div v-if="errors.account_id" class="error-message">{{ errors.account_id }}</div>
                </div>
              </div>

              <!-- Period Selection -->
              <div class="form-group">
                <label for="period_id" class="form-label required">
                  <i class="fas fa-calendar"></i>
                  Accounting Period
                </label>
                <div class="select-wrapper">
                  <select 
                    id="period_id" 
                    v-model="formData.period_id" 
                    :disabled="isEdit || loading"
                    class="form-select"
                    :class="{ 'error': errors.period_id }"
                    @change="onPeriodChange"
                  >
                    <option value="">Select a period</option>
                    <option v-for="period in periods" :key="period.period_id" :value="period.period_id">
                      {{ period.period_name }} ({{ formatDateRange(period.start_date, period.end_date) }})
                    </option>
                  </select>
                  <div v-if="errors.period_id" class="error-message">{{ errors.period_id }}</div>
                </div>
              </div>

              <!-- Currency Selection -->
              <div class="form-group">
                <label for="currency" class="form-label required">
                  <i class="fas fa-money-bill-wave"></i>
                  Budget Currency
                </label>
                <div class="currency-input-container">
                  <select 
                    id="currency" 
                    v-model="formData.currency" 
                    class="form-select"
                    :class="{ 'error': errors.currency }"
                    @change="onCurrencyChange"
                  >
                    <option value="">Select currency</option>
                    <option v-for="currency in availableCurrencies" :key="currency.code" :value="currency.code">
                      {{ currency.code }} - {{ currency.name }}
                    </option>
                  </select>
                  <div v-if="selectedCurrency" class="currency-info">
                    <div class="currency-details">
                      <span class="currency-symbol">{{ selectedCurrency.symbol }}</span>
                      <span class="currency-name">{{ selectedCurrency.name }}</span>
                    </div>
                    <div v-if="exchangeRateInfo.rate && formData.currency !== baseCurrency" class="exchange-rate-info">
                      <small>
                        <i class="fas fa-exchange-alt"></i>
                        1 {{ formData.currency }} = {{ exchangeRateInfo.rate }} {{ baseCurrency }}
                        <span class="rate-date">({{ formatDate(exchangeRateInfo.date) }})</span>
                      </small>
                    </div>
                  </div>
                  <div v-if="errors.currency" class="error-message">{{ errors.currency }}</div>
                </div>
              </div>

              <!-- Department -->
              <div class="form-group">
                <label for="department" class="form-label">
                  <i class="fas fa-building"></i>
                  Department
                </label>
                <div class="input-wrapper">
                  <input 
                    id="department" 
                    v-model="formData.department" 
                    type="text" 
                    class="form-input"
                    :class="{ 'error': errors.department }"
                    placeholder="Enter department name"
                    list="departments-list"
                    @input="clearError('department')"
                  />
                  <datalist id="departments-list">
                    <option v-for="dept in departments" :key="dept" :value="dept"></option>
                  </datalist>
                  <div v-if="errors.department" class="error-message">{{ errors.department }}</div>
                </div>
              </div>

              <!-- Budget Type -->
              <div class="form-group">
                <label for="budget_type" class="form-label">
                  <i class="fas fa-tag"></i>
                  Budget Type
                </label>
                <div class="select-wrapper">
                  <select 
                    id="budget_type" 
                    v-model="formData.budget_type" 
                    class="form-select"
                    @change="clearError('budget_type')"
                  >
                    <option value="operational">Operational</option>
                    <option value="capital">Capital</option>
                    <option value="revenue">Revenue</option>
                    <option value="expense">Expense</option>
                    <option value="project">Project</option>
                  </select>
                </div>
              </div>

              <!-- Budgeted Amount -->
              <div class="form-group">
                <label for="budgeted_amount" class="form-label required">
                  <i class="fas fa-dollar-sign"></i>
                  Budgeted Amount
                </label>
                <div class="amount-input-container">
                  <div class="currency-prefix" v-if="formData.currency">
                    {{ getCurrencySymbol(formData.currency) }}
                  </div>
                  <input 
                    id="budgeted_amount" 
                    v-model="formattedBudgetedAmount"
                    type="text" 
                    class="form-input amount-input"
                    :class="{ 'error': errors.budgeted_amount, 'with-prefix': formData.currency }"
                    placeholder="0.00"
                    @input="handleBudgetedAmountInput"
                    @blur="formatBudgetedAmount"
                  />
                  <div v-if="errors.budgeted_amount" class="error-message">{{ errors.budgeted_amount }}</div>
                  
                  <!-- Base Currency Conversion -->
                  <div v-if="baseCurrencyAmount && formData.currency !== baseCurrency" class="base-currency-display">
                    <i class="fas fa-info-circle"></i>
                    <span>Base Currency ({{ baseCurrency }}): {{ formatCurrency(baseCurrencyAmount, baseCurrency) }}</span>
                  </div>
                </div>
              </div>

              <!-- Quarterly Breakdown -->
              <div class="form-group">
                <label class="form-label">
                  <i class="fas fa-chart-bar"></i>
                  Quarterly Breakdown
                  <span class="optional">(Optional)</span>
                </label>
                <div class="quarterly-container">
                  <div class="quarterly-toggle">
                    <label class="toggle-label">
                      <input type="checkbox" v-model="enableQuarterlyBreakdown" @change="toggleQuarterlyBreakdown">
                      <span class="toggle-slider"></span>
                      Enable quarterly breakdown
                    </label>
                  </div>
                  
                  <div v-if="enableQuarterlyBreakdown" class="quarterly-grid">
                    <div v-for="(quarter, index) in quarterlyBreakdown" :key="quarter.quarter" class="quarterly-item">
                      <label class="quarterly-label">{{ quarter.quarter }}</label>
                      <div class="quarterly-input-container">
                        <div class="currency-prefix" v-if="formData.currency">
                          {{ getCurrencySymbol(formData.currency) }}
                        </div>
                        <input 
                          v-model="quarter.formattedAmount"
                          type="text" 
                          class="form-input quarterly-input"
                          :class="{ 'with-prefix': formData.currency }"
                          placeholder="0.00"
                          @input="handleQuarterlyInput(index)"
                          @blur="formatQuarterlyAmount(index)"
                        />
                      </div>
                    </div>
                    
                    <div class="quarterly-summary">
                      <div class="summary-item">
                        <span>Total Quarterly:</span>
                        <span class="summary-amount">{{ formatCurrency(quarterlyTotal, formData.currency) }}</span>
                      </div>
                      <div class="summary-item" :class="{ 'error': quarterlyVariance !== 0 }">
                        <span>Variance:</span>
                        <span class="summary-amount">{{ formatCurrency(quarterlyVariance, formData.currency) }}</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Notes -->
              <div class="form-group">
                <label for="notes" class="form-label">
                  <i class="fas fa-sticky-note"></i>
                  Notes
                </label>
                <div class="input-wrapper">
                  <textarea 
                    id="notes" 
                    v-model="formData.notes" 
                    class="form-textarea"
                    placeholder="Enter any additional notes or comments..."
                    rows="4"
                    @input="clearError('notes')"
                  ></textarea>
                </div>
              </div>

              <!-- Form Actions -->
              <div class="form-actions">
                <button type="button" @click="goBack" class="btn-cancel">
                  <i class="fas fa-times"></i>
                  Cancel
                </button>
                <button type="submit" class="btn-save" :disabled="!isFormValid || loading">
                  <i class="fas fa-save" :class="{ 'fa-spin': loading }"></i>
                  {{ loading ? 'Saving...' : (isEdit ? 'Update Budget' : 'Create Budget') }}
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- Summary Panel -->
        <div class="summary-section">
          <!-- Budget Preview -->
          <div class="summary-card">
            <div class="summary-header">
              <h3>
                <i class="fas fa-chart-line"></i>
                Budget Preview
              </h3>
            </div>
            <div class="summary-content">
              <div v-if="selectedAccount" class="preview-item">
                <span class="preview-label">Account:</span>
                <span class="preview-value">
                  {{ selectedAccount.account_code }}<br>
                  <small>{{ selectedAccount.name }}</small>
                </span>
              </div>
              
              <div v-if="selectedPeriod" class="preview-item">
                <span class="preview-label">Period:</span>
                <span class="preview-value">
                  {{ selectedPeriod.period_name }}<br>
                  <small>{{ formatDateRange(selectedPeriod.start_date, selectedPeriod.end_date) }}</small>
                </span>
              </div>
              
              <div v-if="formData.currency" class="preview-item">
                <span class="preview-label">Currency:</span>
                <span class="preview-value">
                  {{ formData.currency }}<br>
                  <small>{{ selectedCurrency?.name }}</small>
                </span>
              </div>
              
              <div v-if="formData.budgeted_amount" class="preview-item highlight">
                <span class="preview-label">Budget Amount:</span>
                <span class="preview-value amount">
                  {{ formatCurrency(formData.budgeted_amount, formData.currency) }}
                </span>
              </div>
              
              <div v-if="baseCurrencyAmount && formData.currency !== baseCurrency" class="preview-item">
                <span class="preview-label">Base Currency:</span>
                <span class="preview-value">
                  {{ formatCurrency(baseCurrencyAmount, baseCurrency) }}<br>
                  <small>Rate: {{ exchangeRateInfo.rate }}</small>
                </span>
              </div>
              
              <div v-if="formData.department" class="preview-item">
                <span class="preview-label">Department:</span>
                <span class="preview-value">{{ formData.department }}</span>
              </div>
            </div>
          </div>

          <!-- Currency Tools -->
          <div class="summary-card">
            <div class="summary-header">
              <h3>
                <i class="fas fa-calculator"></i>
                Currency Tools
              </h3>
            </div>
            <div class="summary-content">
              <div class="currency-converter">
                <label>Quick Converter</label>
                <div class="converter-row">
                  <input 
                    v-model="converterAmount" 
                    type="number" 
                    class="converter-input" 
                    placeholder="Amount"
                    @input="updateConversion"
                  />
                  <select v-model="converterFromCurrency" class="converter-select" @change="updateConversion">
                    <option v-for="currency in availableCurrencies" :key="currency.code" :value="currency.code">
                      {{ currency.code }}
                    </option>
                  </select>
                </div>
                <div class="converter-result" v-if="conversionResult">
                  <i class="fas fa-arrow-down"></i>
                  <span>{{ formatCurrency(conversionResult.amount, conversionResult.currency) }}</span>
                </div>
              </div>
              
              <div class="exchange-rates">
                <label>Current Exchange Rates</label>
                <div class="rates-list">
                  <div v-for="rate in currentRates" :key="`${rate.from}-${rate.to}`" class="rate-item">
                    <span class="rate-pair">{{ rate.from }}/{{ rate.to }}</span>
                    <span class="rate-value">{{ rate.rate }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Validation Summary -->
          <div v-if="Object.keys(errors).length > 0" class="summary-card error">
            <div class="summary-header">
              <h3>
                <i class="fas fa-exclamation-triangle"></i>
                Validation Errors
              </h3>
            </div>
            <div class="summary-content">
              <ul class="error-list">
                <li v-for="(error, field) in errors" :key="field">
                  <strong>{{ getFieldLabel(field) }}:</strong> {{ error }}
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Success Modal -->
    <div v-if="showSuccessModal" class="modal-overlay" @click="closeSuccessModal">
      <div class="modal-content" @click.stop>
        <div class="modal-header success">
          <h3><i class="fas fa-check-circle"></i> Success</h3>
          <button @click="closeSuccessModal" class="close-btn">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <p>Budget has been {{ isEdit ? 'updated' : 'created' }} successfully!</p>
          <div class="budget-summary" v-if="savedBudget">
            <div class="summary-row">
              <span>Account:</span>
              <span>{{ savedBudget.chart_of_account?.name }}</span>
            </div>
            <div class="summary-row">
              <span>Period:</span>
              <span>{{ savedBudget.accounting_period?.period_name }}</span>
            </div>
            <div class="summary-row">
              <span>Amount:</span>
              <span>{{ formatCurrency(savedBudget.budgeted_amount, savedBudget.currency) }}</span>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button @click="createAnother" class="btn-secondary">
            <i class="fas fa-plus"></i>
            Create Another
          </button>
          <button @click="goBack" class="btn-primary">
            <i class="fas fa-list"></i>
            Back to List
          </button>
        </div>
      </div>
    </div>

    <!-- Copy from Previous Modal -->
    <div v-if="showCopyModal" class="modal-overlay" @click="closeCopyModal">
      <div class="modal-content copy-modal" @click.stop>
        <div class="modal-header">
          <h3><i class="fas fa-copy"></i> Copy from Previous Period</h3>
          <button @click="closeCopyModal" class="close-btn">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <div class="copy-form">
            <div class="form-group">
              <label>Source Period</label>
              <select v-model="copyOptions.sourcePeriodId" class="form-select">
                <option value="">Select source period</option>
                <option v-for="period in periods" :key="period.period_id" :value="period.period_id">
                  {{ period.period_name }}
                </option>
              </select>
            </div>
            <div class="form-group">
              <label>Adjustment Percentage</label>
              <input 
                v-model="copyOptions.adjustmentPercentage" 
                type="number" 
                class="form-input" 
                placeholder="0"
                step="0.1"
              />
              <small>Enter percentage to adjust the copied amount (e.g., 5 for 5% increase, -5 for 5% decrease)</small>
            </div>
            <div class="form-group">
              <label>Target Currency</label>
              <select v-model="copyOptions.targetCurrency" class="form-select">
                <option value="">Keep original currency</option>
                <option v-for="currency in availableCurrencies" :key="currency.code" :value="currency.code">
                  {{ currency.code }} - {{ currency.name }}
                </option>
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button @click="closeCopyModal" class="btn-cancel">Cancel</button>
          <button @click="executeCopy" class="btn-confirm" :disabled="!copyOptions.sourcePeriodId">
            Copy Budget
          </button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script>
import { ref, reactive, computed, watch, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import axios from 'axios'

export default {
  name: 'BudgetForm',
  setup() {
    const router = useRouter()
    const route = useRoute()
    const loading = ref(false)
    const isEdit = computed(() => !!route.params.id)
    const budgetId = computed(() => route.params.id)
    
    // Form data
    const formData = reactive({
      account_id: '',
      period_id: '',
      budgeted_amount: 0,
      currency: '',
      department: '',
      budget_type: 'operational',
      notes: '',
      quarterly_breakdown: null
    })
    
    // Form state
    const errors = reactive({})
    const accounts = ref([])
    const periods = ref([])
    const departments = ref([])
    const availableCurrencies = ref([])
    const baseCurrency = ref('USD')
    const formattedBudgetedAmount = ref('')
    const enableQuarterlyBreakdown = ref(false)
    const quarterlyBreakdown = ref([
      { quarter: 'Q1', amount: 0, formattedAmount: '' },
      { quarter: 'Q2', amount: 0, formattedAmount: '' },
      { quarter: 'Q3', amount: 0, formattedAmount: '' },
      { quarter: 'Q4', amount: 0, formattedAmount: '' }
    ])
    
    // Modals
    const showSuccessModal = ref(false)
    const showCopyModal = ref(false)
    const savedBudget = ref(null)
    
    // Currency tools
    const exchangeRateInfo = ref({ rate: 1, date: null })
    const converterAmount = ref(0)
    const converterFromCurrency = ref('USD')
    const conversionResult = ref(null)
    const currentRates = ref([])
    
    // Copy functionality
    const copyOptions = reactive({
      sourcePeriodId: '',
      adjustmentPercentage: 0,
      targetCurrency: ''
    })
    
    // Computed properties
    const selectedAccount = computed(() => {
      return accounts.value.find(account => account.account_id === formData.account_id)
    })
    
    const selectedPeriod = computed(() => {
      return periods.value.find(period => period.period_id === formData.period_id)
    })
    
    const selectedCurrency = computed(() => {
      return availableCurrencies.value.find(currency => currency.code === formData.currency)
    })
    
    const groupedAccounts = computed(() => {
      const grouped = {}
      accounts.value.forEach(account => {
        const type = account.account_type || 'Other'
        if (!grouped[type]) {
          grouped[type] = []
        }
        grouped[type].push(account)
      })
      return grouped
    })
    
    const baseCurrencyAmount = computed(() => {
      if (!formData.budgeted_amount || !exchangeRateInfo.value.rate || formData.currency === baseCurrency.value) {
        return 0
      }
      return formData.budgeted_amount * exchangeRateInfo.value.rate
    })
    
    const quarterlyTotal = computed(() => {
      return quarterlyBreakdown.value.reduce((sum, q) => sum + (q.amount || 0), 0)
    })
    
    const quarterlyVariance = computed(() => {
      return (formData.budgeted_amount || 0) - quarterlyTotal.value
    })
    
    const isFormValid = computed(() => {
      return formData.account_id &&
             formData.period_id &&
             formData.budgeted_amount > 0 &&
             formData.currency &&
             Object.keys(errors).length === 0 &&
             (!enableQuarterlyBreakdown.value || Math.abs(quarterlyVariance.value) < 0.01)
    })

    // Methods
    const fetchDropdownData = async () => {
      try {
        const [accountsRes, periodsRes, currenciesRes, deptRes] = await Promise.all([
          axios.get('/accounting/chart-of-accounts'),
          axios.get('/accounting/accounting-periods'),
          axios.get('/accounting/system-currencies'),
          axios.get('/accounting/budgets/departments')
        ])
        
        accounts.value = accountsRes.data.data || accountsRes.data
        periods.value = periodsRes.data.data || periodsRes.data
        availableCurrencies.value = currenciesRes.data.data || []
        departments.value = deptRes.data.data || []
        
        // Set base currency from config
        const baseConfig = await axios.get('/config/base-currency')
        baseCurrency.value = baseConfig.data.base_currency || 'USD'
        
      } catch (error) {
        console.error('Error fetching dropdown data:', error)
      }
    }

    const fetchBudget = async () => {
      if (!isEdit.value) return
      
      try {
        loading.value = true
        const response = await axios.get(`/accounting/budgets/${budgetId.value}`)
        const budget = response.data.data
        
        Object.assign(formData, {
          account_id: budget.account_id,
          period_id: budget.period_id,
          budgeted_amount: budget.budgeted_amount,
          currency: budget.currency,
          department: budget.department || '',
          budget_type: budget.budget_type || 'operational',
          notes: budget.notes || ''
        })
        
        formattedBudgetedAmount.value = formatNumberInput(budget.budgeted_amount)
        
        // Load quarterly breakdown if exists
        if (budget.quarterly_breakdown) {
          const quarterly = JSON.parse(budget.quarterly_breakdown)
          enableQuarterlyBreakdown.value = true
          quarterly.forEach((q, index) => {
            if (quarterlyBreakdown.value[index]) {
              quarterlyBreakdown.value[index].amount = q.amount
              quarterlyBreakdown.value[index].formattedAmount = formatNumberInput(q.amount)
            }
          })
        }
        
        // Fetch exchange rate info
        await fetchExchangeRate()
        
      } catch (error) {
        console.error('Error fetching budget:', error)
      } finally {
        loading.value = false
      }
    }

    const fetchExchangeRate = async () => {
      if (!formData.currency || formData.currency === baseCurrency.value) {
        exchangeRateInfo.value = { rate: 1, date: null }
        return
      }
      
      try {
        const date = selectedPeriod.value?.start_date || new Date().toISOString().split('T')[0]
        const response = await axios.get(`/accounting/exchange-rates/${formData.currency}/${baseCurrency.value}`, {
          params: { date }
        })
        
        exchangeRateInfo.value = {
          rate: response.data.rate,
          date: response.data.date
        }
      } catch (error) {
        console.error('Error fetching exchange rate:', error)
        exchangeRateInfo.value = { rate: 1, date: null }
      }
    }

    const onAccountChange = () => {
      clearError('account_id')
    }

    const onPeriodChange = () => {
      clearError('period_id')
      fetchExchangeRate()
    }

    const onCurrencyChange = () => {
      clearError('currency')
      fetchExchangeRate()
      updateConversion()
    }

    const handleBudgetedAmountInput = (event) => {
      const value = event.target.value
      formattedBudgetedAmount.value = value
      formData.budgeted_amount = parseFormattedNumber(value)
      clearError('budgeted_amount')
    }

    const formatBudgetedAmount = () => {
      formattedBudgetedAmount.value = formatNumberInput(formData.budgeted_amount)
    }

    const handleQuarterlyInput = (index) => {
      const value = quarterlyBreakdown.value[index].formattedAmount
      quarterlyBreakdown.value[index].amount = parseFormattedNumber(value)
    }

    const formatQuarterlyAmount = (index) => {
      quarterlyBreakdown.value[index].formattedAmount = formatNumberInput(quarterlyBreakdown.value[index].amount)
    }

    const toggleQuarterlyBreakdown = () => {
      if (!enableQuarterlyBreakdown.value) {
        quarterlyBreakdown.value.forEach(q => {
          q.amount = 0
          q.formattedAmount = ''
        })
      } else {
        // Auto-distribute if budget amount exists
        if (formData.budgeted_amount > 0) {
          const quarterAmount = formData.budgeted_amount / 4
          quarterlyBreakdown.value.forEach(q => {
            q.amount = quarterAmount
            q.formattedAmount = formatNumberInput(quarterAmount)
          })
        }
      }
    }

    const updateConversion = async () => {
      if (!converterAmount.value || !converterFromCurrency.value) {
        conversionResult.value = null
        return
      }
      
      try {
        const targetCurrency = formData.currency || baseCurrency.value
        if (converterFromCurrency.value === targetCurrency) {
          conversionResult.value = {
            amount: converterAmount.value,
            currency: targetCurrency
          }
          return
        }
        
        const response = await axios.get(`/accounting/exchange-rates/${converterFromCurrency.value}/${targetCurrency}`)
        conversionResult.value = {
          amount: converterAmount.value * response.data.rate,
          currency: targetCurrency
        }
      } catch (error) {
        console.error('Error converting currency:', error)
        conversionResult.value = null
      }
    }

    const saveBudget = async () => {
      try {
        loading.value = true
        
        // Prepare form data
        const submitData = { ...formData }
        
        // Add quarterly breakdown if enabled
        if (enableQuarterlyBreakdown.value) {
          submitData.quarterly_breakdown = quarterlyBreakdown.value.map(q => ({
            quarter: q.quarter,
            amount: q.amount
          }))
        }
        
        let response
        if (isEdit.value) {
          response = await axios.put(`/accounting/budgets/${budgetId.value}`, submitData)
        } else {
          response = await axios.post('/accounting/budgets', submitData)
        }
        
        savedBudget.value = response.data.data
        showSuccessModal.value = true
        
      } catch (error) {
        if (error.response?.status === 422) {
          Object.assign(errors, error.response.data.errors)
        } else {
          console.error('Error saving budget:', error)
        }
      } finally {
        loading.value = false
      }
    }

    const copyFromPrevious = () => {
      showCopyModal.value = true
    }

    const executeCopy = async () => {
      try {
        const response = await axios.post('/accounting/budgets/copy-from-previous', {
          source_period_id: copyOptions.sourcePeriodId,
          target_period_id: formData.period_id,
          adjustment_percentage: copyOptions.adjustmentPercentage,
          target_currency: copyOptions.targetCurrency,
          account_id: formData.account_id
        })
        
        if (response.data.data.length > 0) {
          const copiedBudget = response.data.data[0]
          formData.budgeted_amount = copiedBudget.budgeted_amount
          formData.currency = copiedBudget.currency
          formData.department = copiedBudget.department
          formData.budget_type = copiedBudget.budget_type
          formData.notes = copiedBudget.notes
          
          formattedBudgetedAmount.value = formatNumberInput(copiedBudget.budgeted_amount)
        }
        
        closeCopyModal()
      } catch (error) {
        console.error('Error copying budget:', error)
      }
    }

    const resetForm = () => {
      Object.keys(formData).forEach(key => {
        if (typeof formData[key] === 'string') {
          formData[key] = ''
        } else if (typeof formData[key] === 'number') {
          formData[key] = 0
        } else {
          formData[key] = null
        }
      })
      formData.budget_type = 'operational'
      formattedBudgetedAmount.value = ''
      enableQuarterlyBreakdown.value = false
      quarterlyBreakdown.value.forEach(q => {
        q.amount = 0
        q.formattedAmount = ''
      })
      Object.keys(errors).forEach(key => delete errors[key])
    }

    const goBack = () => {
      router.push('/budgets')
    }

    const closeSuccessModal = () => {
      showSuccessModal.value = false
    }

    const closeCopyModal = () => {
      showCopyModal.value = false
      Object.keys(copyOptions).forEach(key => {
        copyOptions[key] = ''
      })
      copyOptions.adjustmentPercentage = 0
    }

    const createAnother = () => {
      showSuccessModal.value = false
      router.push('/budgets/create')
    }

    const clearError = (field) => {
      if (errors[field]) {
        delete errors[field]
      }
    }

    // Utility functions
    const formatCurrency = (amount, currency = 'IDR') => {
      if (amount === null || amount === undefined || amount === '') return `${currency} 0`
      
      const currencyFormats = {
        'IDR': { locale: 'id-ID', currency: 'IDR' },
        'USD': { locale: 'en-US', currency: 'USD' },
        'EUR': { locale: 'de-DE', currency: 'EUR' },
        'SGD': { locale: 'en-SG', currency: 'SGD' }
      }
      
      const format = currencyFormats[currency] || currencyFormats['IDR']
      
      return new Intl.NumberFormat(format.locale, {
        style: 'currency',
        currency: format.currency,
        minimumFractionDigits: currency === 'IDR' ? 0 : 2
      }).format(amount)
    }

    const formatDate = (dateString) => {
      if (!dateString) return ''
      return new Intl.DateTimeFormat('id-ID', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      }).format(new Date(dateString))
    }

    const formatDateRange = (startDate, endDate) => {
      if (!startDate || !endDate) return ''
      const start = new Date(startDate).toLocaleDateString('id-ID', { month: 'short', day: 'numeric' })
      const end = new Date(endDate).toLocaleDateString('id-ID', { month: 'short', day: 'numeric', year: 'numeric' })
      return `${start} - ${end}`
    }

    const formatNumberInput = (value) => {
      if (!value) return ''
      const number = value.toString().replace(/[^\d]/g, '')
      return new Intl.NumberFormat('id-ID').format(number)
    }

    const parseFormattedNumber = (formattedValue) => {
      if (!formattedValue) return 0
      return parseFloat(formattedValue.replace(/[^\d]/g, '')) || 0
    }

    const getCurrencySymbol = (currencyCode) => {
      const currency = availableCurrencies.value.find(c => c.code === currencyCode)
      return currency?.symbol || currencyCode
    }

    const getFieldLabel = (field) => {
      const labels = {
        account_id: 'Account',
        period_id: 'Period',
        budgeted_amount: 'Budgeted Amount',
        currency: 'Currency',
        department: 'Department',
        budget_type: 'Budget Type',
        notes: 'Notes'
      }
      return labels[field] || field
    }

    // Watchers
    watch(() => formData.currency, () => {
      if (formData.currency) {
        converterFromCurrency.value = formData.currency
        updateConversion()
      }
    })

    watch(() => formData.budgeted_amount, () => {
      if (enableQuarterlyBreakdown.value && formData.budgeted_amount > 0) {
        // Auto-adjust quarterly breakdown if needed
      }
    })

    onMounted(async () => {
      await fetchDropdownData()
      if (isEdit.value) {
        await fetchBudget()
      }
    })

    return {
      loading,
      isEdit,
      formData,
      errors,
      accounts,
      periods,
      departments,
      availableCurrencies,
      baseCurrency,
      formattedBudgetedAmount,
      enableQuarterlyBreakdown,
      quarterlyBreakdown,
      showSuccessModal,
      showCopyModal,
      savedBudget,
      exchangeRateInfo,
      converterAmount,
      converterFromCurrency,
      conversionResult,
      currentRates,
      copyOptions,
      selectedAccount,
      selectedPeriod,
      selectedCurrency,
      groupedAccounts,
      baseCurrencyAmount,
      quarterlyTotal,
      quarterlyVariance,
      isFormValid,
      onAccountChange,
      onPeriodChange,
      onCurrencyChange,
      handleBudgetedAmountInput,
      formatBudgetedAmount,
      handleQuarterlyInput,
      formatQuarterlyAmount,
      toggleQuarterlyBreakdown,
      updateConversion,
      saveBudget,
      copyFromPrevious,
      executeCopy,
      resetForm,
      goBack,
      closeSuccessModal,
      closeCopyModal,
      createAnother,
      clearError,
      formatCurrency,
      formatDate,
      formatDateRange,
      getCurrencySymbol,
      getFieldLabel
    }
  }
}
</script>

<style scoped>
.budget-form-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0;
}

.form-layout {
  display: grid;
  grid-template-columns: 1fr 400px;
  gap: 2rem;
  align-items: start;
}

/* Form Section */
.form-section {
  min-width: 0;
}

.form-card {
  background: white;
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  border: 1px solid #e2e8f0;
  overflow: hidden;
}

.form-header {
  padding: 2rem 2rem 1rem 2rem;
  border-bottom: 1px solid #e2e8f0;
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
}

.form-header h3 {
  color: #1e293b;
  margin-bottom: 0.5rem;
  font-size: 1.25rem;
}

.form-header h3 i {
  color: #6366f1;
  margin-right: 0.5rem;
}

.form-header p {
  color: #64748b;
  margin: 0;
  font-size: 0.9rem;
}

.budget-form {
  padding: 2rem;
}

.form-group {
  margin-bottom: 2rem;
}

.form-label {
  display: block;
  font-weight: 600;
  color: #374151;
  margin-bottom: 0.75rem;
  font-size: 0.9rem;
}

.form-label i {
  color: #6366f1;
  margin-right: 0.5rem;
  width: 16px;
}

.form-label.required::after {
  content: '*';
  color: #dc2626;
  margin-left: 0.25rem;
}

.optional {
  font-weight: normal;
  color: #64748b;
  font-size: 0.8rem;
}

.select-wrapper,
.input-wrapper {
  position: relative;
}

.form-select,
.form-input,
.form-textarea {
  width: 100%;
  padding: 0.875rem 1rem;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  font-size: 0.9rem;
  background: white;
  transition: all 0.3s ease;
}

.form-select:focus,
.form-input:focus,
.form-textarea:focus {
  outline: none;
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.form-select.error,
.form-input.error,
.form-textarea.error {
  border-color: #dc2626;
}

.error-message {
  color: #dc2626;
  font-size: 0.8rem;
  margin-top: 0.5rem;
}

/* Currency Input Container */
.currency-input-container {
  position: relative;
}

.currency-info {
  margin-top: 0.75rem;
  padding: 0.75rem;
  background: #f8fafc;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
}

.currency-details {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.5rem;
}

.currency-symbol {
  background: #6366f1;
  color: white;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-weight: 600;
  font-size: 0.8rem;
}

.currency-name {
  color: #374151;
  font-weight: 500;
}

.exchange-rate-info {
  color: #64748b;
  font-size: 0.8rem;
}

.rate-date {
  color: #94a3b8;
}

/* Amount Input */
.amount-input-container {
  position: relative;
}

.currency-prefix {
  position: absolute;
  left: 1rem;
  top: 50%;
  transform: translateY(-50%);
  color: #6366f1;
  font-weight: 600;
  z-index: 1;
}

.amount-input.with-prefix {
  padding-left: 3rem;
}

.base-currency-display {
  margin-top: 0.75rem;
  padding: 0.75rem;
  background: #f0f9ff;
  border: 1px solid #bae6fd;
  border-radius: 8px;
  color: #0c4a6e;
  font-size: 0.9rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

/* Quarterly Breakdown */
.quarterly-container {
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 1rem;
  background: #f8fafc;
}

.quarterly-toggle {
  margin-bottom: 1rem;
}

.toggle-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
  user-select: none;
}

.toggle-slider {
  width: 40px;
  height: 20px;
  background: #cbd5e1;
  border-radius: 10px;
  position: relative;
  transition: all 0.3s ease;
}

.toggle-slider::before {
  content: '';
  width: 16px;
  height: 16px;
  background: white;
  border-radius: 50%;
  position: absolute;
  top: 2px;
  left: 2px;
  transition: all 0.3s ease;
}

input[type="checkbox"]:checked + .toggle-slider {
  background: #6366f1;
}

input[type="checkbox"]:checked + .toggle-slider::before {
  transform: translateX(20px);
}

input[type="checkbox"] {
  display: none;
}

.quarterly-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  margin-bottom: 1rem;
}

.quarterly-item {
  background: white;
  border-radius: 8px;
  padding: 1rem;
  border: 1px solid #e2e8f0;
}

.quarterly-label {
  display: block;
  font-weight: 600;
  color: #374151;
  margin-bottom: 0.5rem;
  font-size: 0.9rem;
}

.quarterly-input-container {
  position: relative;
}

.quarterly-input.with-prefix {
  padding-left: 2.5rem;
}

.quarterly-summary {
  background: white;
  border-radius: 8px;
  padding: 1rem;
  border: 1px solid #e2e8f0;
}

.summary-item {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.5rem;
  font-size: 0.9rem;
}

.summary-item:last-child {
  margin-bottom: 0;
  font-weight: 600;
}

.summary-item.error {
  color: #dc2626;
}

.summary-amount {
  font-weight: 600;
}

/* Form Actions */
.form-actions {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
  margin-top: 2rem;
  padding-top: 2rem;
  border-top: 1px solid #e2e8f0;
}

.btn-cancel,
.btn-save {
  padding: 0.875rem 2rem;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.btn-cancel {
  background: #f1f5f9;
  color: #64748b;
}

.btn-cancel:hover {
  background: #e2e8f0;
  color: #374151;
}

.btn-save {
  background: #6366f1;
  color: white;
}

.btn-save:hover:not(:disabled) {
  background: #5856eb;
}

.btn-save:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Summary Section */
.summary-section {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.summary-card {
  background: white;
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  border: 1px solid #e2e8f0;
  overflow: hidden;
}

.summary-card.error {
  border-color: #dc2626;
}

.summary-header {
  padding: 1.5rem 1.5rem 1rem 1.5rem;
  border-bottom: 1px solid #e2e8f0;
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
}

.summary-card.error .summary-header {
  background: linear-gradient(135deg, #fef2f2 0%, #fecaca 100%);
}

.summary-header h3 {
  color: #1e293b;
  margin: 0;
  font-size: 1rem;
}

.summary-card.error .summary-header h3 {
  color: #dc2626;
}

.summary-header h3 i {
  color: #6366f1;
  margin-right: 0.5rem;
}

.summary-card.error .summary-header h3 i {
  color: #dc2626;
}

.summary-content {
  padding: 1.5rem;
}

.preview-item {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid #f1f5f9;
}

.preview-item:last-child {
  margin-bottom: 0;
  border-bottom: none;
  padding-bottom: 0;
}

.preview-item.highlight {
  background: #f0f9ff;
  margin: 0 -1.5rem 1rem -1.5rem;
  padding: 1rem 1.5rem;
  border-bottom: 1px solid #bae6fd;
}

.preview-label {
  font-weight: 500;
  color: #64748b;
  font-size: 0.9rem;
}

.preview-value {
  text-align: right;
  color: #1e293b;
  font-weight: 500;
}

.preview-value.amount {
  font-size: 1.1rem;
  font-weight: 700;
  color: #6366f1;
}

.preview-value small {
  display: block;
  color: #64748b;
  font-size: 0.8rem;
  font-weight: normal;
}

/* Currency Tools */
.currency-converter {
  margin-bottom: 1.5rem;
}

.currency-converter label {
  display: block;
  font-weight: 500;
  color: #374151;
  margin-bottom: 0.75rem;
  font-size: 0.9rem;
}

.converter-row {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 0.75rem;
}

.converter-input,
.converter-select {
  flex: 1;
  padding: 0.5rem;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  font-size: 0.8rem;
}

.converter-result {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem;
  background: #f0f9ff;
  border: 1px solid #bae6fd;
  border-radius: 6px;
  color: #0c4a6e;
  font-weight: 600;
}

.exchange-rates label {
  display: block;
  font-weight: 500;
  color: #374151;
  margin-bottom: 0.75rem;
  font-size: 0.9rem;
}

.rates-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.rate-item {
  display: flex;
  justify-content: space-between;
  padding: 0.5rem 0.75rem;
  background: #f8fafc;
  border-radius: 6px;
  font-size: 0.8rem;
}

.rate-pair {
  font-weight: 500;
  color: #374151;
}

.rate-value {
  color: #6366f1;
  font-weight: 600;
}

.error-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

.error-list li {
  margin-bottom: 0.5rem;
  color: #dc2626;
  font-size: 0.9rem;
}

.error-list li:last-child {
  margin-bottom: 0;
}

/* Modal Styles */
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
  padding: 1rem;
}

.modal-content {
  background: white;
  border-radius: 16px;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
  max-width: 500px;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
}

.copy-modal {
  max-width: 600px;
}

.modal-header {
  padding: 1.5rem 2rem 1rem 2rem;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-header.success {
  background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
}

.modal-header h3 {
  color: #1e293b;
  margin: 0;
  font-size: 1.25rem;
}

.modal-header.success h3 {
  color: #166534;
}

.modal-header h3 i {
  color: #6366f1;
  margin-right: 0.5rem;
}

.modal-header.success h3 i {
  color: #16a34a;
}

.close-btn {
  width: 32px;
  height: 32px;
  border: none;
  background: #f1f5f9;
  color: #64748b;
  border-radius: 50%;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
}

.close-btn:hover {
  background: #e2e8f0;
  color: #374151;
}

.modal-body {
  padding: 1.5rem 2rem;
}

.budget-summary {
  background: #f8fafc;
  padding: 1rem;
  border-radius: 8px;
  margin-top: 1rem;
}

.summary-row {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.5rem;
  font-size: 0.9rem;
}

.summary-row:last-child {
  margin-bottom: 0;
  font-weight: 600;
}

.copy-form {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.copy-form .form-group {
  margin-bottom: 1rem;
}

.copy-form label {
  display: block;
  font-weight: 500;
  color: #374151;
  margin-bottom: 0.5rem;
  font-size: 0.9rem;
}

.copy-form .form-input,
.copy-form .form-select {
  width: 100%;
  padding: 0.75rem;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  font-size: 0.9rem;
}

.copy-form small {
  color: #64748b;
  font-size: 0.8rem;
  margin-top: 0.25rem;
  display: block;
}

.modal-footer {
  padding: 1rem 2rem 1.5rem 2rem;
  border-top: 1px solid #e2e8f0;
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
}

.btn-cancel,
.btn-confirm,
.btn-primary,
.btn-secondary {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 500;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.btn-cancel {
  background: #f1f5f9;
  color: #64748b;
}

.btn-cancel:hover {
  background: #e2e8f0;
  color: #374151;
}

.btn-confirm {
  background: #059669;
  color: white;
}

.btn-confirm:hover:not(:disabled) {
  background: #047857;
}

.btn-primary {
  background: #6366f1;
  color: white;
}

.btn-primary:hover {
  background: #5856eb;
}

.btn-secondary {
  background: #e2e8f0;
  color: #374151;
}

.btn-secondary:hover {
  background: #cbd5e1;
}

.btn-confirm:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Responsive Design */
@media (max-width: 768px) {
  .budget-form-container {
    padding: 1rem;
  }
  
  .form-layout {
    grid-template-columns: 1fr;
    gap: 1.5rem;
  }
  
  .form-header,
  .budget-form,
  .summary-content {
    padding: 1rem;
  }
  
  .form-actions {
    flex-direction: column-reverse;
  }
  
  .btn-cancel,
  .btn-save {
    width: 100%;
    justify-content: center;
  }
  
  .quarterly-grid {
    grid-template-columns: 1fr;
  }
  
  .converter-row {
    flex-direction: column;
  }
  
  .modal-content {
    margin: 1rem;
    max-width: calc(100vw - 2rem);
  }
  
  .modal-footer {
    flex-direction: column-reverse;
  }
  
  .modal-footer button {
    width: 100%;
    justify-content: center;
  }
}
</style>