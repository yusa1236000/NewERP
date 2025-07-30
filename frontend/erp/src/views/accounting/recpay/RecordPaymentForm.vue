<template>
  <div class="record-payment-form">
    <div class="form-container">
      <!-- Header -->
      <div class="form-header">
        <div class="header-left">
          <button @click="$router.go(-1)" class="back-btn">
            <i class="fas fa-arrow-left"></i>
          </button>
          <div class="header-info">
            <h1 class="form-title">Record Receivable Payment</h1>
            <p class="form-subtitle">Record a new payment from customer</p>
          </div>
        </div>
      </div>

      <form @submit.prevent="submitPayment" class="payment-form">
        <!-- Customer Selection -->
        <div class="form-section">
          <div class="section-header">
            <h3 class="section-title">
              <i class="fas fa-user"></i>
              Customer Information
            </h3>
            <p class="section-description">Select customer and receivable to pay</p>
          </div>
          
          <div class="form-grid">
            <div class="form-group">
              <label for="customer">Customer <span class="required">*</span></label>
              <select 
                id="customer"
                v-model="form.customerId" 
                @change="loadCustomerReceivables"
                :class="{ 'error': errors.customerId }"
                required
              >
                <option value="">Select a customer...</option>
                <option 
                  v-for="customer in customers" 
                  :key="customer.customer_id" 
                  :value="customer.customer_id"
                >
                  {{ customer.name }} ({{ customer.customer_code }})
                </option>
              </select>
              <div v-if="errors.customerId" class="error-message">{{ errors.customerId }}</div>
            </div>

            <div class="form-group">
              <label for="receivable">Outstanding Receivable <span class="required">*</span></label>
              <select 
                id="receivable"
                v-model="form.receivableId" 
                @change="onReceivableChange"
                :class="{ 'error': errors.receivableId }"
                :disabled="!form.customerId || loadingReceivables"
                required
              >
                <option value="">
                  {{ loadingReceivables ? 'Loading receivables...' : 'Select a receivable...' }}
                </option>
                <option 
                  v-for="receivable in customerReceivables" 
                  :key="receivable.receivable_id" 
                  :value="receivable.receivable_id"
                >
                  #{{ receivable.receivable_id }} - {{ formatCurrency(receivable.balance, receivable.currency_code) }} 
                  {{ receivable.currency_code }} (Due: {{ formatDate(receivable.due_date) }})
                </option>
              </select>
              <div v-if="errors.receivableId" class="error-message">{{ errors.receivableId }}</div>
            </div>
          </div>

          <!-- Selected Receivable Details -->
          <div v-if="selectedReceivable" class="receivable-details">
            <h4 class="details-title">Receivable Details</h4>
            <div class="details-grid">
              <div class="detail-card">
                <div class="detail-item">
                  <span class="detail-label">Receivable ID</span>
                  <span class="detail-value">#{{ selectedReceivable.receivable_id }}</span>
                </div>
                <div class="detail-item">
                  <span class="detail-label">Invoice Number</span>
                  <span class="detail-value">#{{ selectedReceivable.invoice_id || 'N/A' }}</span>
                </div>
                <div class="detail-item">
                  <span class="detail-label">Total Amount</span>
                  <span class="detail-value">{{ formatCurrency(selectedReceivable.amount, selectedReceivable.currency_code) }}</span>
                </div>
                <div class="detail-item">
                  <span class="detail-label">Paid Amount</span>
                  <span class="detail-value">{{ formatCurrency(selectedReceivable.paid_amount, selectedReceivable.currency_code) }}</span>
                </div>
                <div class="detail-item">
                  <span class="detail-label">Outstanding Balance</span>
                  <span class="detail-value outstanding">{{ formatCurrency(selectedReceivable.balance, selectedReceivable.currency_code) }}</span>
                </div>
                <div class="detail-item">
                  <span class="detail-label">Due Date</span>
                  <span class="detail-value" :class="{ 'overdue': isOverdue(selectedReceivable.due_date) }">
                    {{ formatDate(selectedReceivable.due_date) }}
                    <span v-if="isOverdue(selectedReceivable.due_date)" class="overdue-badge">Overdue</span>
                  </span>
                </div>
                <div class="detail-item">
                  <span class="detail-label">Receivable Currency</span>
                  <span class="detail-value">
                    <span class="currency-badge">{{ selectedReceivable.currency_code || baseCurrency }}</span>
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Payment Details -->
        <div class="form-section">
          <div class="section-header">
            <h3 class="section-title">
              <i class="fas fa-money-bill-wave"></i>
              Payment Details
            </h3>
            <p class="section-description">Enter payment amount and details</p>
          </div>
          
          <div class="form-grid">
            <div class="form-group">
              <label for="paymentDate">Payment Date <span class="required">*</span></label>
              <input 
                type="date" 
                id="paymentDate"
                v-model="form.paymentDate"
                :class="{ 'error': errors.paymentDate }"
                required
              />
              <div v-if="errors.paymentDate" class="error-message">{{ errors.paymentDate }}</div>
            </div>

            <div class="form-group">
              <label for="paymentMethod">Payment Method <span class="required">*</span></label>
              <select 
                id="paymentMethod"
                v-model="form.paymentMethod"
                :class="{ 'error': errors.paymentMethod }"
                required
              >
                <option value="">Select payment method...</option>
                <option value="Cash">Cash</option>
                <option value="Check">Check</option>
                <option value="Bank Transfer">Bank Transfer</option>
                <option value="Credit Card">Credit Card</option>
                <option value="Wire Transfer">Wire Transfer</option>
                <option value="Online Payment">Online Payment</option>
              </select>
              <div v-if="errors.paymentMethod" class="error-message">{{ errors.paymentMethod }}</div>
            </div>

            <div class="form-group">
              <label for="paymentCurrency">Payment Currency <span class="required">*</span></label>
              <select 
                id="paymentCurrency"
                v-model="form.paymentCurrency"
                @change="onCurrencyChange"
                :class="{ 'error': errors.paymentCurrency }"
                required
              >
                <option value="">Select currency...</option>
                <option v-for="currency in availableCurrencies" :key="currency" :value="currency">
                  {{ currency }}
                </option>
              </select>
              <div v-if="errors.paymentCurrency" class="error-message">{{ errors.paymentCurrency }}</div>
            </div>

            <div class="form-group">
              <label for="amount">Payment Amount <span class="required">*</span></label>
              <div class="input-group">
                <span class="input-prefix">{{ form.paymentCurrency || 'USD' }}</span>
                <input 
                  type="number" 
                  id="amount"
                  v-model="form.amount"
                  @input="calculateConversions"
                  :class="{ 'error': errors.amount }"
                  step="0.01"
                  min="0.01"
                  placeholder="0.00"
                  required
                />
              </div>
              <div v-if="errors.amount" class="error-message">{{ errors.amount }}</div>
            </div>

            <!-- Exchange Rate Section -->
            <div v-if="showExchangeSection" class="form-group exchange-section">
              <label for="exchangeRate">
                Exchange Rate 
                <span class="required">*</span>
                <span class="exchange-info">
                  (1 {{ form.paymentCurrency }} = ? {{ baseCurrency }})
                </span>
              </label>
              <div class="input-group">
                <input 
                  type="number" 
                  id="exchangeRate"
                  v-model="form.exchangeRate"
                  @input="calculateConversions"
                  :class="{ 'error': errors.exchangeRate }"
                  step="0.000001"
                  min="0.000001"
                  placeholder="1.000000"
                  required
                />
                <button type="button" @click="fetchCurrentRate" class="rate-btn" :disabled="loadingRate">
                  <i v-if="loadingRate" class="fas fa-spinner fa-spin"></i>
                  <i v-else class="fas fa-sync-alt"></i>
                  {{ loadingRate ? 'Loading...' : 'Get Rate' }}
                </button>
              </div>
              <div v-if="errors.exchangeRate" class="error-message">{{ errors.exchangeRate }}</div>
              <div v-if="lastRateUpdate" class="rate-info">
                Last updated: {{ lastRateUpdate }}
              </div>
            </div>

            <div class="form-group">
              <label for="referenceNumber">Reference Number <span class="required">*</span></label>
              <input 
                type="text" 
                id="referenceNumber"
                v-model="form.referenceNumber"
                :class="{ 'error': errors.referenceNumber }"
                placeholder="Enter reference number..."
                maxlength="50"
                required
              />
              <div v-if="errors.referenceNumber" class="error-message">{{ errors.referenceNumber }}</div>
            </div>

            <div class="form-group">
              <label for="notes">Notes</label>
              <textarea 
                id="notes"
                v-model="form.notes"
                placeholder="Additional notes about this payment..."
                rows="3"
                maxlength="500"
              ></textarea>
              <div class="character-count">{{ form.notes.length }}/500</div>
            </div>
          </div>

          <!-- Currency Conversion Summary -->
          <div v-if="showConversionSummary" class="conversion-summary">
            <h4 class="summary-title">Currency Conversion Summary</h4>
            <div class="conversion-grid">
              <div class="conversion-item">
                <span class="conversion-label">Payment Amount:</span>
                <span class="conversion-value">{{ formatCurrency(form.amount, form.paymentCurrency) }}</span>
              </div>
              <div class="conversion-item">
                <span class="conversion-label">Exchange Rate:</span>
                <span class="conversion-value">1 {{ form.paymentCurrency }} = {{ formatNumber(form.exchangeRate, 6) }} {{ baseCurrency }}</span>
              </div>
              <div class="conversion-item">
                <span class="conversion-label">Base Currency Amount:</span>
                <span class="conversion-value">{{ formatCurrency(baseCurrencyAmount, baseCurrency) }}</span>
              </div>
              <div v-if="selectedReceivable && receivableCurrencyAmount" class="conversion-item">
                <span class="conversion-label">Amount Applied to Receivable:</span>
                <span class="conversion-value">{{ formatCurrency(receivableCurrencyAmount, selectedReceivable.currency_code) }}</span>
              </div>
              <div v-if="Math.abs(exchangeDifference) > 0.01" class="conversion-item">
                <span class="conversion-label">Exchange {{ exchangeDifference > 0 ? 'Gain' : 'Loss' }}:</span>
                <span class="conversion-value" :class="exchangeDifference > 0 ? 'gain' : 'loss'">
                  {{ formatCurrency(Math.abs(exchangeDifference), baseCurrency) }}
                </span>
              </div>
              <div v-if="remainingBalance !== null" class="conversion-item balance">
                <span class="conversion-label">Remaining Balance After Payment:</span>
                <span class="conversion-value">{{ formatCurrency(remainingBalance, selectedReceivable?.currency_code) }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Journal Entry Options -->
        <div class="form-section">
          <div class="section-header">
            <h3 class="section-title">
              <i class="fas fa-book"></i>
              Journal Entry Options
            </h3>
            <p class="section-description">Configure automatic journal entry creation</p>
          </div>
          
          <div class="form-group checkbox-group">
            <label class="checkbox-label">
              <input 
                type="checkbox" 
                v-model="form.createJournalEntry"
                @change="onJournalEntryToggle"
              />
              <span class="checkbox-text">Create journal entry automatically</span>
            </label>
          </div>

          <div v-if="form.createJournalEntry" class="journal-options">
            <div class="form-grid">
              <div class="form-group">
                <label for="cashAccountId">Cash/Bank Account <span class="required">*</span></label>
                <select 
                  id="cashAccountId"
                  v-model="form.cashAccountId"
                  :class="{ 'error': errors.cashAccountId }"
                >
                  <option value="">Select cash/bank account...</option>
                  <option 
                    v-for="account in cashAccounts" 
                    :key="account.account_id" 
                    :value="account.account_id"
                  >
                    {{ account.account_code }} - {{ account.name }}
                  </option>
                </select>
                <div v-if="errors.cashAccountId" class="error-message">{{ errors.cashAccountId }}</div>
              </div>

              <div class="form-group">
                <label for="receivableAccountId">Accounts Receivable Account <span class="required">*</span></label>
                <select 
                  id="receivableAccountId"
                  v-model="form.receivableAccountId"
                  :class="{ 'error': errors.receivableAccountId }"
                >
                  <option value="">Select receivable account...</option>
                  <option 
                    v-for="account in receivableAccounts" 
                    :key="account.account_id" 
                    :value="account.account_id"
                  >
                    {{ account.account_code }} - {{ account.name }}
                  </option>
                </select>
                <div v-if="errors.receivableAccountId" class="error-message">{{ errors.receivableAccountId }}</div>
              </div>

              <div v-if="showExchangeSection" class="form-group">
                <label for="exchangeGainLossAccountId">Exchange Gain/Loss Account</label>
                <select 
                  id="exchangeGainLossAccountId"
                  v-model="form.exchangeGainLossAccountId"
                >
                  <option value="">Select exchange gain/loss account...</option>
                  <option 
                    v-for="account in exchangeAccounts" 
                    :key="account.account_id" 
                    :value="account.account_id"
                  >
                    {{ account.account_code }} - {{ account.name }}
                  </option>
                </select>
              </div>
            </div>
          </div>
        </div>

        <!-- Form Actions -->
        <div class="form-actions">
          <button type="button" @click="resetForm" class="btn btn-outline">
            <i class="fas fa-undo"></i>
            Reset Form
          </button>
          <button type="submit" class="btn btn-primary" :disabled="submitting || !isFormValid">
            <i v-if="submitting" class="fas fa-spinner fa-spin"></i>
            <i v-else class="fas fa-save"></i>
            {{ submitting ? 'Recording Payment...' : 'Record Payment' }}
          </button>
        </div>
      </form>
    </div>

    <!-- Success Modal -->
    <div v-if="showSuccessModal" class="modal-overlay" @click="closeSuccessModal">
      <div class="modal-content" @click.stop>
        <div class="modal-header success">
          <div class="success-icon">
            <i class="fas fa-check-circle"></i>
          </div>
          <h3 class="modal-title">Payment Recorded Successfully!</h3>
        </div>
        <div class="modal-body">
          <p>Payment has been successfully recorded and applied to the receivable.</p>
          <div class="success-details">
            <div class="detail-row">
              <span>Payment ID:</span>
              <span class="payment-id">#{{ recordedPaymentId }}</span>
            </div>
            <div class="detail-row">
              <span>Customer:</span>
              <span>{{ getSelectedCustomer()?.name }}</span>
            </div>
            <div class="detail-row">
              <span>Payment Amount:</span>
              <span>{{ formatCurrency(form.amount, form.paymentCurrency) }}</span>
            </div>
            <div v-if="showConversionSummary" class="detail-row">
              <span>Base Currency Amount:</span>
              <span>{{ formatCurrency(baseCurrencyAmount, baseCurrency) }}</span>
            </div>
            <div class="detail-row">
              <span>Remaining Balance:</span>
              <span>{{ formatCurrency(remainingBalance, selectedReceivable?.currency_code) }}</span>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button @click="recordAnother" class="btn btn-outline">
            Record Another Payment
          </button>
          <button @click="viewPayment" class="btn btn-primary">
            View Payment Details
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

export default {
  name: 'RecordPaymentForm',
  setup() {
    const router = useRouter()
    const submitting = ref(false)
    const loadingReceivables = ref(false)
    const loadingRate = ref(false)
    const customers = ref([])
    const customerReceivables = ref([])
    const cashAccounts = ref([])
    const receivableAccounts = ref([])
    const exchangeAccounts = ref([])
    const availableCurrencies = ref(['USD', 'EUR', 'GBP', 'JPY', 'CAD', 'AUD', 'CHF', 'CNY', 'SGD', 'IDR'])
    const showSuccessModal = ref(false)
    const recordedPaymentId = ref(null)
    const baseCurrency = ref('USD')
    const lastRateUpdate = ref(null)
    
    const form = reactive({
      customerId: '',
      receivableId: '',
      paymentDate: new Date().toISOString().split('T')[0],
      paymentMethod: '',
      paymentCurrency: 'USD',
      amount: '',
      exchangeRate: 1,
      referenceNumber: '',
      notes: '',
      createJournalEntry: false,
      cashAccountId: '',
      receivableAccountId: '',
      exchangeGainLossAccountId: ''
    })
    
    const errors = reactive({})
    
    const selectedReceivable = computed(() => {
      return customerReceivables.value.find(r => r.receivable_id == form.receivableId)
    })

    const getSelectedCustomer = () => {
      return customers.value.find(c => c.customer_id == form.customerId)
    }

    const showExchangeSection = computed(() => {
      return form.paymentCurrency && form.paymentCurrency !== baseCurrency.value
    })

    const showConversionSummary = computed(() => {
      return form.amount && form.exchangeRate && 
             (showExchangeSection.value || selectedReceivable.value)
    })

    const baseCurrencyAmount = computed(() => {
      if (!form.amount || !form.exchangeRate) return 0
      return parseFloat(form.amount) * parseFloat(form.exchangeRate)
    })

    const receivableCurrencyAmount = computed(() => {
      if (!selectedReceivable.value || !form.amount) return null
      
      const receivableCurrency = selectedReceivable.value.currency_code || baseCurrency.value
      const paymentCurrency = form.paymentCurrency
      
      if (paymentCurrency === receivableCurrency) {
        return parseFloat(form.amount)
      }
      
      // Convert via base currency
      const baseAmount = baseCurrencyAmount.value
      if (receivableCurrency === baseCurrency.value) {
        return baseAmount
      }
      
      // This would need the receivable currency exchange rate
      // For now, assume it's the same as base amount
      return baseAmount
    })

    const exchangeDifference = computed(() => {
      if (!selectedReceivable.value || !showConversionSummary.value) return 0
      
      // Simplified calculation - in real implementation,
      // this would calculate the actual exchange gain/loss
      return 0
    })

    const remainingBalance = computed(() => {
      if (!selectedReceivable.value || !receivableCurrencyAmount.value) return null
      return selectedReceivable.value.balance - receivableCurrencyAmount.value
    })

    const isFormValid = computed(() => {
      return form.customerId && 
             form.receivableId && 
             form.paymentDate && 
             form.paymentMethod && 
             form.paymentCurrency &&
             form.amount && 
             parseFloat(form.amount) > 0 &&
             form.referenceNumber &&
             (!showExchangeSection.value || (form.exchangeRate && parseFloat(form.exchangeRate) > 0)) &&
             (!form.createJournalEntry || (form.cashAccountId && form.receivableAccountId))
    })

    // Load initial data
    onMounted(async () => {
      await Promise.all([
        loadCustomers(),
        loadAccounts()
      ])
      
      // Get base currency from config
      try {
        const configResponse = await axios.get('/accounting/config')
        baseCurrency.value = configResponse.data.base_currency || 'USD'
        form.paymentCurrency = baseCurrency.value
      } catch (error) {
        console.error('Error loading config:', error)
      }
    })

    const loadCustomers = async () => {
      try {
        const response = await axios.get('/customers', {
          params: { has_receivables: true }
        })
        customers.value = response.data.data || []
      } catch (error) {
        console.error('Error loading customers:', error)
      }
    }

    const loadCustomerReceivables = async () => {
      if (!form.customerId) {
        customerReceivables.value = []
        return
      }
      
      try {
        loadingReceivables.value = true
        const response = await axios.get('/accounting/customer-receivables', {
          params: { 
            customer_id: form.customerId,
            status: 'Open'
          }
        })
        customerReceivables.value = response.data.data || []
      } catch (error) {
        console.error('Error loading receivables:', error)
        customerReceivables.value = []
      } finally {
        loadingReceivables.value = false
      }
    }

    const loadAccounts = async () => {
      try {
        const response = await axios.get('/accounting/chart-of-accounts')
        const accounts = response.data.data || []
        
        // Filter accounts by type
        cashAccounts.value = accounts.filter(acc => 
          ['Cash', 'Bank'].includes(acc.account_type) && acc.is_active
        )
        receivableAccounts.value = accounts.filter(acc => 
          acc.account_type === 'Accounts Receivable' && acc.is_active
        )
        exchangeAccounts.value = accounts.filter(acc => 
          ['Exchange Gain/Loss', 'Other Income', 'Other Expense'].includes(acc.account_type) && acc.is_active
        )
      } catch (error) {
        console.error('Error loading accounts:', error)
      }
    }

    const onReceivableChange = () => {
      // Reset amount when receivable changes
      form.amount = ''
      calculateConversions()
    }

    const onCurrencyChange = async () => {
      if (form.paymentCurrency === baseCurrency.value) {
        form.exchangeRate = 1
        lastRateUpdate.value = null
      } else {
        await fetchCurrentRate()
      }
      calculateConversions()
    }

    const fetchCurrentRate = async () => {
      if (!form.paymentCurrency || form.paymentCurrency === baseCurrency.value) {
        form.exchangeRate = 1
        return
      }
      
      try {
        loadingRate.value = true
        const response = await axios.get('/accounting/receivable-payments/exchange-rates', {
          params: {
            from_currency: form.paymentCurrency,
            to_currency: baseCurrency.value,
            date: form.paymentDate
          }
        })
        form.exchangeRate = response.data.data.rate || 1
        lastRateUpdate.value = new Date().toLocaleString()
      } catch (error) {
        console.error('Error fetching exchange rate:', error)
        // Keep current rate or default to 1
        if (!form.exchangeRate) {
          form.exchangeRate = 1
        }
      } finally {
        loadingRate.value = false
      }
    }

    const calculateConversions = () => {
      // Trigger reactivity for computed values
      // The computed properties will automatically recalculate
    }

    const onJournalEntryToggle = () => {
      if (!form.createJournalEntry) {
        // Clear journal entry fields when disabled
        form.cashAccountId = ''
        form.receivableAccountId = ''
        form.exchangeGainLossAccountId = ''
      }
    }

    const validateForm = () => {
      Object.keys(errors).forEach(key => delete errors[key])
      
      if (!form.customerId) {
        errors.customerId = 'Customer is required'
      }
      
      if (!form.receivableId) {
        errors.receivableId = 'Receivable is required'
      }
      
      if (!form.paymentDate) {
        errors.paymentDate = 'Payment date is required'
      }
      
      if (!form.paymentMethod) {
        errors.paymentMethod = 'Payment method is required'
      }
      
      if (!form.paymentCurrency) {
        errors.paymentCurrency = 'Payment currency is required'
      }
      
      if (!form.amount || parseFloat(form.amount) <= 0) {
        errors.amount = 'Valid payment amount is required'
      } else if (selectedReceivable.value && receivableCurrencyAmount.value > selectedReceivable.value.balance) {
        errors.amount = 'Payment amount cannot exceed outstanding balance'
      }
      
      if (showExchangeSection.value && (!form.exchangeRate || parseFloat(form.exchangeRate) <= 0)) {
        errors.exchangeRate = 'Valid exchange rate is required'
      }
      
      if (!form.referenceNumber) {
        errors.referenceNumber = 'Reference number is required'
      }
      
      if (form.createJournalEntry) {
        if (!form.cashAccountId) {
          errors.cashAccountId = 'Cash account is required for journal entry'
        }
        if (!form.receivableAccountId) {
          errors.receivableAccountId = 'Receivable account is required for journal entry'
        }
      }
      
      return Object.keys(errors).length === 0
    }

    const submitPayment = async () => {
      if (!validateForm()) return
      
      try {
        submitting.value = true
        
        const paymentData = {
          receivable_id: form.receivableId,
          payment_date: form.paymentDate,
          payment_method: form.paymentMethod,
          amount: parseFloat(form.amount),
          payment_currency: form.paymentCurrency,
          exchange_rate: parseFloat(form.exchangeRate),
          reference_number: form.referenceNumber,
          notes: form.notes,
          create_journal_entry: form.createJournalEntry,
          cash_account_id: form.cashAccountId || null,
          receivable_account_id: form.receivableAccountId || null,
          exchange_gain_loss_account_id: form.exchangeGainLossAccountId || null
        }
        
        const response = await axios.post('/accounting/receivable-payments', paymentData)
        
        recordedPaymentId.value = response.data.data.payment_id
        showSuccessModal.value = true
        
      } catch (error) {
        console.error('Error recording payment:', error)
        const message = error.response?.data?.message || 'Failed to record payment'
        alert('Error: ' + message)
      } finally {
        submitting.value = false
      }
    }

    const resetForm = () => {
      Object.keys(form).forEach(key => {
        if (key === 'paymentDate') {
          form[key] = new Date().toISOString().split('T')[0]
        } else if (key === 'paymentCurrency') {
          form[key] = baseCurrency.value
        } else if (key === 'exchangeRate') {
          form[key] = 1
        } else if (key === 'createJournalEntry') {
          form[key] = false
        } else if (key === 'notes') {
          form[key] = ''
        } else {
          form[key] = ''
        }
      })
      
      Object.keys(errors).forEach(key => delete errors[key])
      customerReceivables.value = []
      lastRateUpdate.value = null
    }

    const closeSuccessModal = () => {
      showSuccessModal.value = false
    }

    const recordAnother = () => {
      closeSuccessModal()
      resetForm()
    }

    const viewPayment = () => {
      router.push(`/accounting/receivable-payments/${recordedPaymentId.value}`)
    }

    const formatCurrency = (amount, currency = 'USD') => {
      if (amount === null || amount === undefined || amount === '') return 'N/A'
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

    const isOverdue = (dateString) => {
      if (!dateString) return false
      return new Date(dateString) < new Date()
    }

    // Watch for currency changes to auto-update exchange rate
    watch(() => form.paymentDate, () => {
      if (showExchangeSection.value) {
        fetchCurrentRate()
      }
    })

    return {
      form,
      errors,
      submitting,
      loadingReceivables,
      loadingRate,
      customers,
      customerReceivables,
      cashAccounts,
      receivableAccounts,
      exchangeAccounts,
      availableCurrencies,
      showSuccessModal,
      recordedPaymentId,
      baseCurrency,
      lastRateUpdate,
      selectedReceivable,
      getSelectedCustomer,
      showExchangeSection,
      showConversionSummary,
      baseCurrencyAmount,
      receivableCurrencyAmount,
      exchangeDifference,
      remainingBalance,
      isFormValid,
      loadCustomerReceivables,
      onReceivableChange,
      onCurrencyChange,
      fetchCurrentRate,
      calculateConversions,
      onJournalEntryToggle,
      submitPayment,
      resetForm,
      closeSuccessModal,
      recordAnother,
      viewPayment,
      formatCurrency,
      formatNumber,
      formatDate,
      isOverdue
    }
  }
}
</script>

<style scoped>
.record-payment-form {
  padding: 2rem;
  max-width: 1200px;
  margin: 0 auto;
}

.form-container {
  background: white;
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
  overflow: hidden;
}

.form-header {
  padding: 2rem;
  background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
  color: white;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.back-btn {
  background: rgba(255, 255, 255, 0.2);
  border: 1px solid rgba(255, 255, 255, 0.3);
  border-radius: 8px;
  padding: 0.75rem;
  color: white;
  cursor: pointer;
  transition: all 0.3s ease;
}

.back-btn:hover {
  background: rgba(255, 255, 255, 0.3);
}

.form-title {
  font-size: 2rem;
  font-weight: 700;
  margin: 0;
}

.form-subtitle {
  margin: 0;
  opacity: 0.9;
  font-size: 1rem;
}

.payment-form {
  padding: 2rem;
}

.form-section {
  margin-bottom: 2.5rem;
}

.section-header {
  margin-bottom: 1.5rem;
}

.section-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: #1e293b;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 0.5rem;
}

.section-title i {
  color: #6366f1;
}

.section-description {
  color: #64748b;
  margin: 0;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 1.5rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-group label {
  font-weight: 500;
  color: #374151;
  font-size: 0.875rem;
}

.required {
  color: #ef4444;
}

.form-group input,
.form-group select,
.form-group textarea {
  padding: 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 1rem;
  transition: all 0.3s ease;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.form-group input.error,
.form-group select.error,
.form-group textarea.error {
  border-color: #ef4444;
}

.input-group {
  display: flex;
  align-items: center;
}

.input-prefix {
  background: #f9fafb;
  border: 1px solid #d1d5db;
  border-right: none;
  border-radius: 8px 0 0 8px;
  padding: 0.75rem;
  font-weight: 500;
  color: #374151;
  min-width: 60px;
  text-align: center;
}

.input-group input {
  border-radius: 0 8px 8px 0;
  border-left: none;
}

.rate-btn {
  background: #6366f1;
  color: white;
  border: none;
  border-radius: 0 8px 8px 0;
  padding: 0.75rem 1rem;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
  white-space: nowrap;
}

.rate-btn:hover:not(:disabled) {
  background: #5046e6;
}

.rate-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.error-message {
  color: #ef4444;
  font-size: 0.875rem;
  margin-top: 0.25rem;
}

.character-count {
  font-size: 0.75rem;
  color: #64748b;
  text-align: right;
}

.exchange-section {
  grid-column: 1 / -1;
}

.exchange-info {
  font-size: 0.75rem;
  color: #64748b;
  font-weight: normal;
}

.rate-info {
  font-size: 0.75rem;
  color: #059669;
  font-style: italic;
}

.receivable-details {
  margin-top: 1.5rem;
  padding: 1.5rem;
  background: #f8fafc;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
}

.details-title {
  font-size: 1rem;
  font-weight: 600;
  color: #1e293b;
  margin-bottom: 1rem;
}

.details-grid {
  display: grid;
  gap: 1rem;
}

.detail-card {
  background: white;
  border-radius: 8px;
  padding: 1rem;
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}

.detail-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.detail-label {
  font-size: 0.75rem;
  color: #64748b;
  font-weight: 500;
}

.detail-value {
  font-weight: 600;
  color: #1e293b;
}

.detail-value.outstanding {
  color: #dc2626;
  font-size: 1.125rem;
}

.detail-value.overdue {
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

.currency-badge {
  background: #6366f1;
  color: white;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 600;
}

.conversion-summary {
  margin-top: 1.5rem;
  padding: 1.5rem;
  background: #f0f9ff;
  border-radius: 12px;
  border: 1px solid #bae6fd;
}

.summary-title {
  font-size: 1rem;
  font-weight: 600;
  color: #0c4a6e;
  margin-bottom: 1rem;
}

.conversion-grid {
  display: grid;
  gap: 0.75rem;
}

.conversion-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.5rem 0;
  border-bottom: 1px solid #bae6fd;
}

.conversion-item:last-child {
  border-bottom: none;
}

.conversion-item.balance {
  font-weight: 600;
  border-top: 2px solid #0284c7;
  padding-top: 0.75rem;
  margin-top: 0.5rem;
}

.conversion-label {
  color: #0c4a6e;
  font-weight: 500;
}

.conversion-value {
  font-weight: 600;
  color: #1e293b;
}

.conversion-value.gain {
  color: #059669;
}

.conversion-value.loss {
  color: #dc2626;
}

.checkbox-group {
  margin-bottom: 1rem;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
  font-weight: 500;
  color: #374151;
}

.checkbox-label input[type="checkbox"] {
  width: 1.125rem;
  height: 1.125rem;
  margin: 0;
}

.journal-options {
  margin-top: 1rem;
  padding: 1rem;
  background: #fefce8;
  border-radius: 8px;
  border: 1px solid #fde047;
}

.form-actions {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
  margin-top: 2rem;
  padding-top: 2rem;
  border-top: 1px solid #e5e7eb;
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
  font-size: 1rem;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-outline {
  background: white;
  color: #6366f1;
  border: 1px solid #6366f1;
}

.btn-outline:hover:not(:disabled) {
  background: #6366f1;
  color: white;
}

.btn-primary {
  background: #6366f1;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: #5046e6;
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
  padding: 2rem;
  text-align: center;
}

.modal-header.success {
  background: linear-gradient(135deg, #059669 0%, #10b981 100%);
  color: white;
}

.success-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
}

.modal-title {
  font-size: 1.5rem;
  font-weight: 600;
  margin: 0;
}

.modal-body {
  padding: 1.5rem 2rem;
}

.success-details {
  background: #f0fdf4;
  border-radius: 8px;
  padding: 1rem;
  margin-top: 1rem;
}

.detail-row {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.5rem;
}

.detail-row:last-child {
  margin-bottom: 0;
}

.detail-row span:first-child {
  color: #374151;
}

.detail-row span:last-child {
  font-weight: 600;
  color: #1e293b;
}

.payment-id {
  color: #6366f1;
  font-weight: 700;
}

.modal-footer {
  padding: 1.5rem 2rem;
  border-top: 1px solid #f1f5f9;
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
}

@media (max-width: 768px) {
  .record-payment-form {
    padding: 1rem;
  }

  .form-header {
    padding: 1.5rem;
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }

  .form-title {
    font-size: 1.5rem;
  }

  .payment-form {
    padding: 1.5rem;
  }

  .form-grid {
    grid-template-columns: 1fr;
    gap: 1rem;
  }

  .detail-card {
    grid-template-columns: 1fr;
  }

  .form-actions {
    flex-direction: column;
  }

  .modal-content {
    margin: 1rem;
    width: calc(100% - 2rem);
  }

  .modal-footer {
    flex-direction: column;
  }
}
</style>