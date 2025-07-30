<template>
    <div class="tax-form-container">
      <!-- Header Section -->
      <div class="page-header">
        <div class="header-content">
          <div class="breadcrumb">
            <router-link to="/tax-transactions" class="breadcrumb-link">
              <i class="fas fa-receipt"></i>
              Tax Transactions
            </router-link>
            <i class="fas fa-chevron-right breadcrumb-separator"></i>
            <span class="breadcrumb-current">{{ isEditing ? 'Edit Transaction' : 'Create Transaction' }}</span>
          </div>
          
          <div class="title-section">
            <h1 class="page-title">
              <i class="fas fa-receipt"></i>
              {{ isEditing ? 'Edit Tax Transaction' : 'Create Tax Transaction' }}
            </h1>
            <p class="page-description">{{ isEditing ? 'Update transaction details' : 'Enter new tax transaction information' }}</p>
          </div>

          <div class="header-actions">
            <button @click="goBack" class="btn btn-outline">
              <i class="fas fa-arrow-left"></i>
              Back to List
            </button>
            <button v-if="isEditing && formData.tax_id" @click="viewTransaction" class="btn btn-outline">
              <i class="fas fa-eye"></i>
              View Details
            </button>
          </div>
        </div>
      </div>

      <!-- Progress Steps -->
      <div class="progress-section">
        <div class="progress-steps">
          <div class="step" :class="{ active: currentStep >= 1, completed: currentStep > 1 }">
            <div class="step-number">1</div>
            <span>Basic Information</span>
          </div>
          <div class="step" :class="{ active: currentStep >= 2, completed: currentStep > 2 }">
            <div class="step-number">2</div>
            <span>Tax Details</span>
          </div>
          <div class="step" :class="{ active: currentStep >= 3, completed: currentStep > 3 }">
            <div class="step-number">3</div>
            <span>Amount & Currency</span>
          </div>
          <div class="step" :class="{ active: currentStep >= 4 }">
            <div class="step-number">4</div>
            <span>Review & Save</span>
          </div>
        </div>
      </div>

      <!-- Form Content -->
      <div class="form-content">
        <form @submit.prevent="submitForm">
          <!-- Step 1: Basic Information -->
          <div v-if="currentStep === 1" class="form-step">
            <div class="step-card">
              <div class="step-header">
                <h2>
                  <i class="fas fa-info-circle"></i>
                  Basic Information
                </h2>
                <p>Enter the basic transaction details</p>
              </div>

              <div class="form-grid">
                <div class="form-group">
                  <label class="form-label" for="tax_type">
                    <i class="fas fa-tag"></i>
                    Tax Type *
                  </label>
                  <select 
                    v-model="formData.tax_type" 
                    id="tax_type" 
                    class="form-select"
                    :class="{ 'is-invalid': errors.tax_type }"
                    required
                  >
                    <option value="">Select Tax Type</option>
                    <option value="VAT">VAT</option>
                    <option value="Sales Tax">Sales Tax</option>
                    <option value="Income Tax">Income Tax</option>
                    <option value="Corporate Tax">Corporate Tax</option>
                    <option value="Withholding Tax">Withholding Tax</option>
                    <option value="Property Tax">Property Tax</option>
                    <option value="Excise Tax">Excise Tax</option>
                  </select>
                  <div v-if="errors.tax_type" class="error-message">{{ errors.tax_type[0] }}</div>
                </div>

                <div class="form-group">
                  <label class="form-label" for="tax_code">
                    <i class="fas fa-code"></i>
                    Tax Code *
                  </label>
                  <input 
                    v-model="formData.tax_code" 
                    type="text" 
                    id="tax_code" 
                    class="form-input"
                    :class="{ 'is-invalid': errors.tax_code }"
                    placeholder="Enter tax code"
                    required
                  />
                  <div v-if="errors.tax_code" class="error-message">{{ errors.tax_code[0] }}</div>
                </div>

                <div class="form-group">
                  <label class="form-label" for="reference_type">
                    <i class="fas fa-link"></i>
                    Reference Type *
                  </label>
                  <select 
                    v-model="formData.reference_type" 
                    id="reference_type" 
                    class="form-select"
                    :class="{ 'is-invalid': errors.reference_type }"
                    required
                  >
                    <option value="">Select Reference Type</option>
                    <option value="Sales Invoice">Sales Invoice</option>
                    <option value="Purchase Invoice">Purchase Invoice</option>
                    <option value="Sales Order">Sales Order</option>
                    <option value="Purchase Order">Purchase Order</option>
                    <option value="Journal Entry">Journal Entry</option>
                    <option value="Manual Entry">Manual Entry</option>
                  </select>
                  <div v-if="errors.reference_type" class="error-message">{{ errors.reference_type[0] }}</div>
                </div>

                <div class="form-group">
                  <label class="form-label" for="reference_id">
                    <i class="fas fa-hashtag"></i>
                    Reference ID *
                  </label>
                  <input 
                    v-model.number="formData.reference_id" 
                    type="number" 
                    id="reference_id" 
                    class="form-input"
                    :class="{ 'is-invalid': errors.reference_id }"
                    placeholder="Enter reference ID"
                    required
                  />
                  <div v-if="errors.reference_id" class="error-message">{{ errors.reference_id[0] }}</div>
                </div>

                <div class="form-group">
                  <label class="form-label" for="invoice_number">
                    <i class="fas fa-file-invoice"></i>
                    Invoice Number
                  </label>
                  <input 
                    v-model="formData.invoice_number" 
                    type="text" 
                    id="invoice_number" 
                    class="form-input"
                    :class="{ 'is-invalid': errors.invoice_number }"
                    placeholder="Enter invoice number"
                  />
                  <div v-if="errors.invoice_number" class="error-message">{{ errors.invoice_number[0] }}</div>
                </div>

                <div class="form-group">
                  <label class="form-label" for="transaction_date">
                    <i class="fas fa-calendar-alt"></i>
                    Transaction Date *
                  </label>
                  <input 
                    v-model="formData.transaction_date" 
                    type="date" 
                    id="transaction_date" 
                    class="form-input"
                    :class="{ 'is-invalid': errors.transaction_date }"
                    required
                  />
                  <div v-if="errors.transaction_date" class="error-message">{{ errors.transaction_date[0] }}</div>
                </div>

                <div class="form-group full-width">
                  <label class="form-label" for="description">
                    <i class="fas fa-align-left"></i>
                    Description
                  </label>
                  <textarea 
                    v-model="formData.description" 
                    id="description" 
                    class="form-textarea"
                    :class="{ 'is-invalid': errors.description }"
                    rows="3"
                    placeholder="Enter transaction description"
                  ></textarea>
                  <div v-if="errors.description" class="error-message">{{ errors.description[0] }}</div>
                </div>
              </div>
            </div>
          </div>

          <!-- Step 2: Tax Details -->
          <div v-if="currentStep === 2" class="form-step">
            <div class="step-card">
              <div class="step-header">
                <h2>
                  <i class="fas fa-percentage"></i>
                  Tax Details
                </h2>
                <p>Configure tax calculation parameters</p>
              </div>

              <div class="form-grid">
                <div class="form-group">
                  <label class="form-label" for="tax_rate">
                    <i class="fas fa-percentage"></i>
                    Tax Rate (%)
                  </label>
                  <input 
                    v-model.number="formData.tax_rate" 
                    type="number" 
                    id="tax_rate" 
                    class="form-input"
                    :class="{ 'is-invalid': errors.tax_rate }"
                    step="0.01"
                    min="0"
                    max="100"
                    placeholder="Enter tax rate"
                    @input="calculateTaxAmount"
                  />
                  <div v-if="errors.tax_rate" class="error-message">{{ errors.tax_rate[0] }}</div>
                </div>

                <div class="form-group">
                  <label class="form-label" for="status">
                    <i class="fas fa-flag"></i>
                    Status *
                  </label>
                  <select 
                    v-model="formData.status" 
                    id="status" 
                    class="form-select"
                    :class="{ 'is-invalid': errors.status }"
                    required
                  >
                    <option value="">Select Status</option>
                    <option value="Draft">Draft</option>
                    <option value="Pending">Pending</option>
                    <option value="Approved">Approved</option>
                    <option value="Posted">Posted</option>
                    <option value="Filed">Filed</option>
                    <option value="Paid">Paid</option>
                    <option value="Completed">Completed</option>
                    <option value="Cancelled">Cancelled</option>
                  </select>
                  <div v-if="errors.status" class="error-message">{{ errors.status[0] }}</div>
                </div>

                <!-- Supplier Information -->
                <div class="form-group">
                  <label class="form-label" for="supplier_name">
                    <i class="fas fa-building"></i>
                    Supplier Name
                  </label>
                  <input 
                    v-model="formData.supplier_name" 
                    type="text" 
                    id="supplier_name" 
                    class="form-input"
                    :class="{ 'is-invalid': errors.supplier_name }"
                    placeholder="Enter supplier name"
                  />
                  <div v-if="errors.supplier_name" class="error-message">{{ errors.supplier_name[0] }}</div>
                </div>

                <div class="form-group">
                  <label class="form-label" for="supplier_tax_id">
                    <i class="fas fa-id-card"></i>
                    Supplier Tax ID
                  </label>
                  <input 
                    v-model="formData.supplier_tax_id" 
                    type="text" 
                    id="supplier_tax_id" 
                    class="form-input"
                    :class="{ 'is-invalid': errors.supplier_tax_id }"
                    placeholder="Enter supplier tax ID"
                  />
                  <div v-if="errors.supplier_tax_id" class="error-message">{{ errors.supplier_tax_id[0] }}</div>
                </div>
              </div>
            </div>
          </div>

          <!-- Step 3: Amount & Currency -->
          <div v-if="currentStep === 3" class="form-step">
            <div class="step-card">
              <div class="step-header">
                <h2>
                  <i class="fas fa-dollar-sign"></i>
                  Amount & Currency
                </h2>
                <p>Enter amounts and currency information</p>
              </div>

              <div class="form-grid">
                <div class="form-group">
                  <label class="form-label" for="currency">
                    <i class="fas fa-coins"></i>
                    Currency *
                  </label>
                  <select 
                    v-model="formData.currency" 
                    id="currency" 
                    class="form-select"
                    :class="{ 'is-invalid': errors.currency }"
                    required
                    @change="onCurrencyChange"
                  >
                    <option value="">Select Currency</option>
                    <option v-for="currency in currencies" :key="currency.code" :value="currency.code">
                      {{ currency.code }} - {{ currency.name }}
                    </option>
                  </select>
                  <div v-if="errors.currency" class="error-message">{{ errors.currency[0] }}</div>
                </div>

                <div class="form-group">
                  <label class="form-label" for="taxable_amount">
                    <i class="fas fa-calculator"></i>
                    Taxable Amount
                  </label>
                  <div class="input-group">
                    <span class="input-prefix">{{ formData.currency || 'USD' }}</span>
                    <input 
                      v-model.number="formData.taxable_amount" 
                      type="number" 
                      id="taxable_amount" 
                      class="form-input"
                      :class="{ 'is-invalid': errors.taxable_amount }"
                      step="0.01"
                      min="0"
                      placeholder="0.00"
                      @input="calculateTaxAmount"
                    />
                  </div>
                  <div v-if="errors.taxable_amount" class="error-message">{{ errors.taxable_amount[0] }}</div>
                </div>

                <div class="form-group">
                  <label class="form-label" for="tax_amount">
                    <i class="fas fa-dollar-sign"></i>
                    Tax Amount *
                  </label>
                  <div class="input-group">
                    <span class="input-prefix">{{ formData.currency || 'USD' }}</span>
                    <input 
                      v-model.number="formData.tax_amount" 
                      type="number" 
                      id="tax_amount" 
                      class="form-input"
                      :class="{ 'is-invalid': errors.tax_amount }"
                      step="0.01"
                      min="0"
                      placeholder="0.00"
                      required
                    />
                  </div>
                  <div v-if="errors.tax_amount" class="error-message">{{ errors.tax_amount[0] }}</div>
                </div>

                <!-- Exchange Rate Info (show if not base currency) -->
                <div v-if="formData.currency && formData.currency !== 'USD'" class="form-group">
                  <label class="form-label" for="exchange_rate">
                    <i class="fas fa-exchange-alt"></i>
                    Exchange Rate ({{ formData.currency }}/USD)
                  </label>
                  <input 
                    v-model.number="exchangeRate" 
                    type="number" 
                    id="exchange_rate" 
                    class="form-input"
                    step="0.0001"
                    min="0"
                    placeholder="Auto-calculated"
                    readonly
                  />
                  <small class="form-hint">Exchange rate will be fetched automatically</small>
                </div>

                <!-- Base Currency Amounts (show if not base currency) -->
                <div v-if="formData.currency && formData.currency !== 'USD'" class="currency-conversion-section">
                  <h4 class="section-title">Base Currency (USD) Amounts</h4>
                  <div class="conversion-grid">
                    <div class="form-group">
                      <label class="form-label">Base Currency Taxable Amount</label>
                      <div class="input-group">
                        <span class="input-prefix">USD</span>
                        <input 
                          :value="baseCurrencyTaxableAmount" 
                          type="number" 
                          class="form-input"
                          step="0.01"
                          readonly
                        />
                      </div>
                    </div>
                    
                    <div class="form-group">
                      <label class="form-label">Base Currency Tax Amount</label>
                      <div class="input-group">
                        <span class="input-prefix">USD</span>
                        <input 
                          :value="baseCurrencyTaxAmount" 
                          type="number" 
                          class="form-input"
                          step="0.01"
                          readonly
                        />
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Calculation Summary -->
                <div class="calculation-summary">
                  <h4 class="section-title">Calculation Summary</h4>
                  <div class="summary-grid">
                    <div class="summary-item">
                      <span class="summary-label">Taxable Amount:</span>
                      <span class="summary-value">{{ formData.currency || 'USD' }} {{ formatCurrency(formData.taxable_amount || 0) }}</span>
                    </div>
                    <div class="summary-item">
                      <span class="summary-label">Tax Rate:</span>
                      <span class="summary-value">{{ formData.tax_rate || 0 }}%</span>
                    </div>
                    <div class="summary-item">
                      <span class="summary-label">Tax Amount:</span>
                      <span class="summary-value highlight">{{ formData.currency || 'USD' }} {{ formatCurrency(formData.tax_amount || 0) }}</span>
                    </div>
                    <div v-if="formData.taxable_amount" class="summary-item total">
                      <span class="summary-label">Total Amount:</span>
                      <span class="summary-value">{{ formData.currency || 'USD' }} {{ formatCurrency((formData.taxable_amount || 0) + (formData.tax_amount || 0)) }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Step 4: Review & Save -->
          <div v-if="currentStep === 4" class="form-step">
            <div class="step-card">
              <div class="step-header">
                <h2>
                  <i class="fas fa-check-circle"></i>
                  Review & Save
                </h2>
                <p>Review all information before saving</p>
              </div>

              <div class="review-content">
                <div class="review-section">
                  <h3>
                    <i class="fas fa-info-circle"></i>
                    Basic Information
                  </h3>
                  <div class="review-grid">
                    <div class="review-item">
                      <span class="review-label">Tax Type:</span>
                      <span class="review-value">{{ formData.tax_type }}</span>
                    </div>
                    <div class="review-item">
                      <span class="review-label">Tax Code:</span>
                      <span class="review-value">{{ formData.tax_code }}</span>
                    </div>
                    <div class="review-item">
                      <span class="review-label">Reference Type:</span>
                      <span class="review-value">{{ formData.reference_type }}</span>
                    </div>
                    <div class="review-item">
                      <span class="review-label">Reference ID:</span>
                      <span class="review-value">#{{ formData.reference_id }}</span>
                    </div>
                    <div class="review-item">
                      <span class="review-label">Transaction Date:</span>
                      <span class="review-value">{{ formatDate(formData.transaction_date) }}</span>
                    </div>
                    <div class="review-item">
                      <span class="review-label">Status:</span>
                      <span class="review-value">
                        <span class="status-badge" :class="getStatusClass(formData.status)">
                          {{ formData.status }}
                        </span>
                      </span>
                    </div>
                  </div>
                </div>

                <div class="review-section" v-if="formData.supplier_name || formData.supplier_tax_id">
                  <h3>
                    <i class="fas fa-building"></i>
                    Supplier Information
                  </h3>
                  <div class="review-grid">
                    <div v-if="formData.supplier_name" class="review-item">
                      <span class="review-label">Supplier Name:</span>
                      <span class="review-value">{{ formData.supplier_name }}</span>
                    </div>
                    <div v-if="formData.supplier_tax_id" class="review-item">
                      <span class="review-label">Supplier Tax ID:</span>
                      <span class="review-value">{{ formData.supplier_tax_id }}</span>
                    </div>
                  </div>
                </div>

                <div class="review-section">
                  <h3>
                    <i class="fas fa-dollar-sign"></i>
                    Amount Information
                  </h3>
                  <div class="review-grid">
                    <div class="review-item">
                      <span class="review-label">Currency:</span>
                      <span class="review-value">{{ formData.currency }}</span>
                    </div>
                    <div v-if="formData.taxable_amount" class="review-item">
                      <span class="review-label">Taxable Amount:</span>
                      <span class="review-value">{{ formData.currency }} {{ formatCurrency(formData.taxable_amount) }}</span>
                    </div>
                    <div v-if="formData.tax_rate" class="review-item">
                      <span class="review-label">Tax Rate:</span>
                      <span class="review-value">{{ formData.tax_rate }}%</span>
                    </div>
                    <div class="review-item">
                      <span class="review-label">Tax Amount:</span>
                      <span class="review-value highlight">{{ formData.currency }} {{ formatCurrency(formData.tax_amount) }}</span>
                    </div>
                    <div v-if="formData.currency !== 'USD'" class="review-item">
                      <span class="review-label">Base Currency Tax Amount:</span>
                      <span class="review-value">USD {{ formatCurrency(baseCurrencyTaxAmount) }}</span>
                    </div>
                  </div>
                </div>

                <div v-if="formData.description" class="review-section">
                  <h3>
                    <i class="fas fa-align-left"></i>
                    Description
                  </h3>
                  <p class="description-text">{{ formData.description }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Navigation Buttons -->
          <div class="form-navigation">
            <div class="nav-left">
              <button 
                v-if="currentStep > 1" 
                type="button" 
                @click="previousStep" 
                class="btn btn-outline"
              >
                <i class="fas fa-arrow-left"></i>
                Previous
              </button>
            </div>
            
            <div class="nav-right">
              <button 
                v-if="currentStep < 4" 
                type="button" 
                @click="nextStep" 
                class="btn btn-primary"
                :disabled="!canProceedToNext"
              >
                Next
                <i class="fas fa-arrow-right"></i>
              </button>
              
              <button 
                v-if="currentStep === 4" 
                type="submit" 
                class="btn btn-success"
                :disabled="saving"
              >
                <i v-if="saving" class="fas fa-spinner fa-spin"></i>
                <i v-else class="fas fa-save"></i>
                {{ saving ? 'Saving...' : (isEditing ? 'Update Transaction' : 'Create Transaction') }}
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
</template>

<script>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'

export default {
  name: 'TaxTransactionForm',
  components: {
  },
  setup() {
    const route = useRoute()
    const router = useRouter()
    
    const loading = ref(false)
    const saving = ref(false)
    const currentStep = ref(1)
    const isEditing = ref(false)
    const exchangeRate = ref(1)
    const currencies = ref([])
    
    const formData = reactive({
      tax_type: '',
      reference_type: '',
      reference_id: null,
      transaction_date: new Date().toISOString().split('T')[0],
      tax_amount: null,
      currency: 'USD',
      tax_code: '',
      tax_rate: null,
      taxable_amount: null,
      status: 'Draft',
      description: '',
      supplier_name: '',
      supplier_tax_id: '',
      invoice_number: ''
    })
    
    const errors = ref({})

    // Computed properties
    const canProceedToNext = computed(() => {
      switch (currentStep.value) {
        case 1:
          return formData.tax_type && formData.tax_code && formData.reference_type && formData.reference_id && formData.transaction_date
        case 2:
          return formData.status
        case 3:
          return formData.currency && formData.tax_amount
        default:
          return true
      }
    })

    const baseCurrencyTaxAmount = computed(() => {
      if (!formData.tax_amount || !exchangeRate.value) return 0
      return formData.tax_amount * exchangeRate.value
    })

    const baseCurrencyTaxableAmount = computed(() => {
      if (!formData.taxable_amount || !exchangeRate.value) return 0
      return formData.taxable_amount * exchangeRate.value
    })

    // Methods
    const fetchCurrencies = async () => {
      try {
        const response = await axios.get('/api/currencies')
        currencies.value = response.data.data || response.data
      } catch (error) {
        console.error('Error fetching currencies:', error)
        // Fallback currencies
        currencies.value = [
          { code: 'USD', name: 'US Dollar' },
          { code: 'EUR', name: 'Euro' },
          { code: 'GBP', name: 'British Pound' },
          { code: 'JPY', name: 'Japanese Yen' },
          { code: 'CAD', name: 'Canadian Dollar' },
          { code: 'AUD', name: 'Australian Dollar' }
        ]
      }
    }

    const fetchExchangeRate = async (fromCurrency, toCurrency = 'USD', date = null) => {
      if (fromCurrency === toCurrency) {
        exchangeRate.value = 1
        return
      }

      try {
        const params = {
          from: fromCurrency,
          to: toCurrency,
          date: date || formData.transaction_date
        }
        
        const response = await axios.get('/api/exchange-rates', { params })
        exchangeRate.value = response.data.rate || 1
      } catch (error) {
        console.error('Error fetching exchange rate:', error)
        exchangeRate.value = 1
        showNotification('Using default exchange rate of 1', 'warning')
      }
    }

    const onCurrencyChange = () => {
      if (formData.currency) {
        fetchExchangeRate(formData.currency)
      }
    }

    const calculateTaxAmount = () => {
      if (formData.taxable_amount && formData.tax_rate) {
        formData.tax_amount = (formData.taxable_amount * formData.tax_rate / 100).toFixed(2)
      }
    }

    const nextStep = () => {
      if (canProceedToNext.value && currentStep.value < 4) {
        currentStep.value++
      }
    }

    const previousStep = () => {
      if (currentStep.value > 1) {
        currentStep.value--
      }
    }

    const loadTransaction = async () => {
      loading.value = true
      try {
        const response = await axios.get(`/api/accounting/tax-transactions/${route.params.id}`)
        const transaction = response.data.data
        
        Object.assign(formData, {
          tax_type: transaction.tax_type,
          reference_type: transaction.reference_type,
          reference_id: transaction.reference_id,
          transaction_date: transaction.transaction_date,
          tax_amount: transaction.tax_amount,
          currency: transaction.currency,
          tax_code: transaction.tax_code,
          tax_rate: transaction.tax_rate,
          taxable_amount: transaction.taxable_amount,
          status: transaction.status,
          description: transaction.description,
          supplier_name: transaction.supplier_name,
          supplier_tax_id: transaction.supplier_tax_id,
          invoice_number: transaction.invoice_number
        })

        // Load exchange rate for existing transaction
        if (transaction.currency !== 'USD') {
          exchangeRate.value = transaction.exchange_rate || 1
        }
      } catch (error) {
        console.error('Error loading transaction:', error)
        showNotification('Error loading transaction data', 'error')
        router.push('/tax-transactions')
      } finally {
        loading.value = false
      }
    }

    const submitForm = async () => {
      saving.value = true
      errors.value = {}

      try {
        const payload = { ...formData }
        
        let response
        if (isEditing.value) {
          response = await axios.put(`/api/accounting/tax-transactions/${route.params.id}`, payload)
        } else {
          response = await axios.post('/api/accounting/tax-transactions', payload)
        }

        showNotification(
          isEditing.value ? 'Transaction updated successfully' : 'Transaction created successfully',
          'success'
        )

        // Redirect to transaction detail
        const transactionId = response.data.data.tax_id || route.params.id
        router.push(`/tax-transactions/${transactionId}`)
      } catch (error) {
        console.error('Error saving transaction:', error)
        
        if (error.response && error.response.status === 422) {
          errors.value = error.response.data.errors || {}
          showNotification('Please check the form for errors', 'error')
          // Go back to first step with errors
          currentStep.value = 1
        } else {
          showNotification('Error saving transaction', 'error')
        }
      } finally {
        saving.value = false
      }
    }

    const goBack = () => {
      router.push('/tax-transactions')
    }

    const viewTransaction = () => {
      router.push(`/tax-transactions/${route.params.id}`)
    }

    // Utility functions
    const formatDate = (date) => {
      if (!date) return ''
      return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      })
    }

    const formatCurrency = (amount) => {
      return new Intl.NumberFormat('en-US').format(amount || 0)
    }

    const getStatusClass = (status) => {
      const statusClasses = {
        'Draft': 'draft',
        'Pending': 'pending',
        'Approved': 'approved',
        'Posted': 'posted',
        'Filed': 'filed',
        'Paid': 'paid',
        'Completed': 'completed',
        'Cancelled': 'cancelled'
      }
      return statusClasses[status] || 'draft'
    }

    const showNotification = (message, type = 'info') => {
      console.log(`${type}: ${message}`)
      // Implement your notification system here
    }

    // Lifecycle
    onMounted(async () => {
      isEditing.value = !!route.params.id
      await fetchCurrencies()
      
      if (isEditing.value) {
        await loadTransaction()
      }

      // Handle duplicate functionality
      if (route.query.duplicate) {
        const duplicateId = route.query.duplicate
        try {
          const response = await axios.get(`/api/accounting/tax-transactions/${duplicateId}`)
          const transaction = response.data.data
          
          Object.assign(formData, {
            tax_type: transaction.tax_type,
            reference_type: transaction.reference_type,
            tax_code: transaction.tax_code,
            tax_rate: transaction.tax_rate,
            currency: transaction.currency,
            supplier_name: transaction.supplier_name,
            supplier_tax_id: transaction.supplier_tax_id,
            status: 'Draft',
            transaction_date: new Date().toISOString().split('T')[0]
          })
          
          showNotification('Transaction duplicated. Please review and update the details.', 'info')
        } catch (error) {
          console.error('Error duplicating transaction:', error)
        }
      }
    })

    return {
      loading,
      saving,
      currentStep,
      isEditing,
      formData,
      errors,
      currencies,
      exchangeRate,
      canProceedToNext,
      baseCurrencyTaxAmount,
      baseCurrencyTaxableAmount,
      onCurrencyChange,
      calculateTaxAmount,
      nextStep,
      previousStep,
      submitForm,
      goBack,
      viewTransaction,
      formatDate,
      formatCurrency,
      getStatusClass
    }
  }
}
</script>

<style scoped>
.tax-form-container {
  padding: 2rem;
  background: var(--bg-secondary);
  min-height: 100vh;
}

/* Header */
.page-header {
  margin-bottom: 2rem;
}

.header-content {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.breadcrumb {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.9rem;
}

.breadcrumb-link {
  color: var(--primary-color);
  text-decoration: none;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.breadcrumb-separator {
  color: var(--text-muted);
  font-size: 0.8rem;
}

.breadcrumb-current {
  color: var(--text-secondary);
}

.title-section {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 2rem;
}

.page-title {
  font-size: 2.5rem;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0;
  display: flex;
  align-items: center;
  gap: 1rem;
}

.page-title i {
  color: var(--primary-color);
}

.page-description {
  font-size: 1.1rem;
  color: var(--text-secondary);
  margin: 0.5rem 0 0 0;
}

.header-actions {
  display: flex;
  gap: 1rem;
  align-items: center;
}

/* Progress Steps */
.progress-section {
  margin-bottom: 2rem;
}

.progress-steps {
  display: flex;
  justify-content: center;
  gap: 2rem;
  background: var(--card-bg);
  padding: 2rem;
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.step {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  opacity: 0.5;
  transition: all 0.3s ease;
}

.step.active,
.step.completed {
  opacity: 1;
}

.step-number {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: var(--border-color);
  color: var(--text-muted);
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  transition: all 0.3s ease;
}

.step.active .step-number {
  background: var(--primary-color);
  color: white;
}

.step.completed .step-number {
  background: var(--success-color);
  color: white;
}

.step span {
  font-size: 0.9rem;
  font-weight: 500;
  color: var(--text-secondary);
  text-align: center;
}

/* Form Content */
.form-content {
  max-width: 1000px;
  margin: 0 auto;
}

.form-step {
  margin-bottom: 2rem;
}

.step-card {
  background: var(--card-bg);
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  border: 1px solid var(--border-color);
}

.step-header {
  padding: 2rem 2rem 1rem 2rem;
  border-bottom: 1px solid var(--border-color);
  background: var(--bg-tertiary);
}

.step-header h2 {
  font-size: 1.5rem;
  font-weight: 600;
  color: var(--text-primary);
  margin: 0 0 0.5rem 0;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.step-header h2 i {
  color: var(--primary-color);
}

.step-header p {
  color: var(--text-secondary);
  margin: 0;
}

/* Form Grid */
.form-grid {
  padding: 2rem;
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 2rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-group.full-width {
  grid-column: 1 / -1;
}

.form-label {
  font-weight: 600;
  color: var(--text-primary);
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.9rem;
}

.form-label i {
  color: var(--primary-color);
  width: 16px;
}

.form-input,
.form-select,
.form-textarea {
  padding: 0.75rem 1rem;
  border: 2px solid var(--border-color);
  border-radius: 8px;
  font-size: 1rem;
  transition: all 0.3s ease;
  background: var(--card-bg);
  color: var(--text-primary);
}

.form-input:focus,
.form-select:focus,
.form-textarea:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.form-input.is-invalid,
.form-select.is-invalid,
.form-textarea.is-invalid {
  border-color: #dc2626;
}

.input-group {
  display: flex;
  align-items: center;
}

.input-prefix {
  padding: 0.75rem 1rem;
  background: var(--bg-tertiary);
  border: 2px solid var(--border-color);
  border-right: none;
  border-radius: 8px 0 0 8px;
  font-weight: 600;
  color: var(--text-secondary);
  font-size: 0.9rem;
}

.input-group .form-input {
  border-radius: 0 8px 8px 0;
  border-left: none;
}

.form-hint {
  color: var(--text-muted);
  font-size: 0.8rem;
  margin-top: 0.25rem;
}

.error-message {
  color: #dc2626;
  font-size: 0.8rem;
  margin-top: 0.25rem;
}

/* Currency Conversion Section */
.currency-conversion-section {
  grid-column: 1 / -1;
  padding: 1.5rem;
  background: var(--bg-tertiary);
  border-radius: 12px;
  border: 1px solid var(--border-color);
}

.section-title {
  font-size: 1.1rem;
  font-weight: 600;
  color: var(--text-primary);
  margin: 0 0 1rem 0;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.conversion-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1rem;
}

/* Calculation Summary */
.calculation-summary {
  grid-column: 1 / -1;
  padding: 1.5rem;
  background: linear-gradient(135deg, rgba(99, 102, 241, 0.05), rgba(139, 92, 246, 0.05));
  border-radius: 12px;
  border: 1px solid rgba(99, 102, 241, 0.2);
}

.summary-grid {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.summary-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.5rem 0;
}

.summary-item.total {
  border-top: 2px solid var(--border-color);
  padding-top: 1rem;
  margin-top: 0.5rem;
}

.summary-label {
  font-weight: 500;
  color: var(--text-secondary);
}

.summary-value {
  font-weight: 600;
  color: var(--text-primary);
}

.summary-value.highlight {
  color: var(--primary-color);
  font-size: 1.1rem;
}

.summary-item.total .summary-value {
  font-size: 1.2rem;
  color: var(--success-color);
}

/* Review Content */
.review-content {
  padding: 2rem;
}

.review-section {
  margin-bottom: 2rem;
  padding: 1.5rem;
  background: var(--bg-tertiary);
  border-radius: 12px;
  border: 1px solid var(--border-color);
}

.review-section:last-child {
  margin-bottom: 0;
}

.review-section h3 {
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--text-primary);
  margin: 0 0 1rem 0;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.review-section h3 i {
  color: var(--primary-color);
}

.review-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1rem;
}

.review-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.review-label {
  font-size: 0.85rem;
  font-weight: 500;
  color: var(--text-muted);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.review-value {
  font-weight: 600;
  color: var(--text-primary);
}

.review-value.highlight {
  color: var(--primary-color);
  font-size: 1.1rem;
}

.description-text {
  background: var(--card-bg);
  padding: 1rem;
  border-radius: 8px;
  border: 1px solid var(--border-color);
  color: var(--text-secondary);
  line-height: 1.6;
  margin: 0;
}

/* Status Badge */
.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 500;
}

.status-badge.draft {
  background: rgba(107, 114, 128, 0.1);
  color: #374151;
}

.status-badge.pending {
  background: rgba(249, 115, 22, 0.1);
  color: #ea580c;
}

.status-badge.approved {
  background: rgba(34, 197, 94, 0.1);
  color: #16a34a;
}

.status-badge.posted,
.status-badge.filed,
.status-badge.paid {
  background: rgba(59, 130, 246, 0.1);
  color: #2563eb;
}

.status-badge.completed {
  background: rgba(16, 185, 129, 0.1);
  color: #059669;
}

.status-badge.cancelled {
  background: rgba(239, 68, 68, 0.1);
  color: #dc2626;
}

/* Navigation */
.form-navigation {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 2rem;
  background: var(--card-bg);
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  border: 1px solid var(--border-color);
  margin-top: 2rem;
}

.nav-left,
.nav-right {
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
  border: none;
  cursor: pointer;
  font-weight: 600;
  text-decoration: none;
  transition: all 0.3s ease;
  font-size: 0.9rem;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-primary {
  background: var(--primary-color);
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: var(--primary-dark);
  transform: translateY(-1px);
}

.btn-success {
  background: var(--success-color);
  color: white;
}

.btn-success:hover:not(:disabled) {
  background: #059669;
  transform: translateY(-1px);
}

.btn-outline {
  background: transparent;
  border: 2px solid var(--border-color);
  color: var(--text-primary);
}

.btn-outline:hover:not(:disabled) {
  border-color: var(--primary-color);
  background: var(--primary-color);
  color: white;
  transform: translateY(-1px);
}

/* Responsive Design */
@media (max-width: 768px) {
  .tax-form-container {
    padding: 1rem;
  }

  .progress-steps {
    flex-direction: column;
    gap: 1rem;
  }

  .form-grid {
    grid-template-columns: 1fr;
    padding: 1rem;
  }

  .review-grid {
    grid-template-columns: 1fr;
  }

  .conversion-grid {
    grid-template-columns: 1fr;
  }

  .title-section {
    flex-direction: column;
    gap: 1rem;
  }

  .header-actions {
    flex-wrap: wrap;
  }

  .page-title {
    font-size: 2rem;
  }

  .form-navigation {
    flex-direction: column;
    gap: 1rem;
  }

  .nav-left,
  .nav-right {
    width: 100%;
    justify-content: center;
  }
}

/* CSS Variables */
:root {
  --primary-color: #6366f1;
  --primary-dark: #4f46e5;
  --success-color: #10b981;
  --text-primary: #1f2937;
  --text-secondary: #6b7280;
  --text-muted: #9ca3af;
  --bg-secondary: #f9fafb;
  --bg-tertiary: #f3f4f6;
  --card-bg: #ffffff;
  --border-color: #e5e7eb;
}

@media (prefers-color-scheme: dark) {
  :root {
    --text-primary: #f9fafb;
    --text-secondary: #d1d5db;
    --text-muted: #9ca3af;
    --bg-secondary: #111827;
    --bg-tertiary: #1f2937;
    --card-bg: #1f2937;
    --border-color: #374151;
  }
}
</style>