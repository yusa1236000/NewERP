<template>
  <div class="payment-application-container">
    <!-- Header Section -->
    <div class="page-header">
      <div class="header-content">
        <div class="title-section">
          <h1 class="page-title">
            <i class="fas fa-link"></i>
            Payment Application
          </h1>
          <p class="page-subtitle">Apply unapplied payments to outstanding receivables with multi-currency support</p>
        </div>
      </div>
    </div>

    <!-- Customer Selection -->
    <div class="customer-selection-card">
      <div class="card-header">
        <h3 class="card-title">
          <i class="fas fa-user"></i>
          Select Customer
        </h3>
      </div>
      <div class="card-content">
        <div class="customer-select-wrapper">
          <select 
            v-model="selectedCustomerId" 
            @change="loadCustomerData"
            class="customer-select"
            :disabled="loading"
          >
            <option value="">Select a customer...</option>
            <option 
              v-for="customer in customers" 
              :key="customer.customer_id" 
              :value="customer.customer_id"
            >
              {{ customer.name }} ({{ customer.customer_code }})
              <span v-if="customer.preferred_currency"> - {{ customer.preferred_currency }}</span>
            </option>
          </select>
          <div v-if="loading" class="loading-indicator">
            <i class="fas fa-spinner fa-spin"></i>
            Loading customer data...
          </div>
        </div>
      </div>
    </div>

    <!-- Customer Summary (when customer is selected) -->
    <div v-if="selectedCustomer && customerSummary" class="customer-summary-card">
      <div class="card-header">
        <h3 class="card-title">
          <i class="fas fa-chart-bar"></i>
          Customer Summary - {{ selectedCustomer.name }}
        </h3>
      </div>
      <div class="card-content">
        <div class="summary-grid">
          <div class="summary-item">
            <div class="summary-icon receivables">
              <i class="fas fa-file-invoice"></i>
            </div>
            <div class="summary-content">
              <div class="summary-value">{{ outstandingReceivables.length }}</div>
              <div class="summary-label">Outstanding Receivables</div>
            </div>
          </div>
          
          <div class="summary-item">
            <div class="summary-icon payments">
              <i class="fas fa-money-bill-wave"></i>
            </div>
            <div class="summary-content">
              <div class="summary-value">{{ unappliedPayments.length }}</div>
              <div class="summary-label">Unapplied Payments</div>
            </div>
          </div>
          
          <div class="summary-item">
            <div class="summary-icon balance">
              <i class="fas fa-balance-scale"></i>
            </div>
            <div class="summary-content">
              <div class="summary-value">{{ customerSummary.currencies.length }}</div>
              <div class="summary-label">Currencies Involved</div>
            </div>
          </div>

          <!-- Currency Breakdown -->
          <div class="currency-breakdown">
            <h4 class="breakdown-title">Currency Breakdown:</h4>
            <div class="currency-items">
              <div v-for="currency in customerSummary.currencies" :key="currency.code" class="currency-item">
                <span class="currency-code">{{ currency.code }}</span>
                <div class="currency-amounts">
                  <span class="receivable-amount">
                    Receivables: {{ formatCurrency(currency.receivables, currency.code) }}
                  </span>
                  <span class="payment-amount">
                    Payments: {{ formatCurrency(currency.payments, currency.code) }}
                  </span>
                  <span class="net-amount" :class="currency.net >= 0 ? 'positive' : 'negative'">
                    Net: {{ formatCurrency(Math.abs(currency.net), currency.code) }}
                    {{ currency.net >= 0 ? 'Credit' : 'Debit' }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Application Interface (when customer is selected) -->
    <div v-if="selectedCustomer" class="application-interface">
      <!-- Outstanding Receivables -->
      <div class="receivables-section">
        <div class="section-header">
          <h3 class="section-title">
            <i class="fas fa-file-invoice"></i>
            Outstanding Receivables
          </h3>
          <div class="section-actions">
            <button @click="toggleAllReceivables" class="btn btn-outline-sm">
              {{ allReceivablesSelected ? 'Deselect All' : 'Select All' }}
            </button>
            <button @click="autoApplyPayments" class="btn btn-secondary-sm" :disabled="!hasSelections">
              <i class="fas fa-magic"></i>
              Auto Apply
            </button>
          </div>
        </div>
        
        <div class="receivables-table-container">
          <table class="receivables-table">
            <thead>
              <tr>
                <th width="40"></th>
                <th>Invoice</th>
                <th>Due Date</th>
                <th>Currency</th>
                <th>Original Amount</th>
                <th>Balance</th>
                <th>Application Amount</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr 
                v-for="receivable in outstandingReceivables" 
                :key="receivable.receivable_id"
                class="receivable-row"
                :class="{ 
                  'selected': selectedReceivables.includes(receivable.receivable_id),
                  'overdue': isOverdue(receivable.due_date)
                }"
              >
                <td>
                  <input 
                    type="checkbox" 
                    :value="receivable.receivable_id"
                    v-model="selectedReceivables"
                    @change="calculateTotals"
                    class="checkbox-input"
                  />
                </td>
                <td>
                  <div class="invoice-info">
                    <div class="invoice-number">#{{ receivable.invoice_id || receivable.receivable_id }}</div>
                    <div class="invoice-type">{{ receivable.invoice_type || 'Invoice' }}</div>
                  </div>
                </td>
                <td class="due-date-cell">
                  <span :class="{ 'text-danger': isOverdue(receivable.due_date) }">
                    {{ formatDate(receivable.due_date) }}
                    <span v-if="isOverdue(receivable.due_date)" class="overdue-indicator">
                      ({{ getDaysOverdue(receivable.due_date) }} days overdue)
                    </span>
                  </span>
                </td>
                <td class="currency-cell">
                  <span class="currency-badge">{{ receivable.currency_code || baseCurrency }}</span>
                </td>
                <td class="amount-cell">
                  {{ formatCurrency(receivable.amount, receivable.currency_code) }}
                </td>
                <td class="balance-cell">
                  <span class="balance-amount">{{ formatCurrency(receivable.balance, receivable.currency_code) }}</span>
                </td>
                <td class="application-cell">
                  <div class="amount-input-wrapper">
                    <input 
                      type="number" 
                      v-model="applicationAmounts[receivable.receivable_id]"
                      @input="calculateTotals"
                      :max="receivable.balance"
                      :disabled="!selectedReceivables.includes(receivable.receivable_id)"
                      class="amount-input"
                      step="0.01"
                      min="0"
                      :placeholder="receivable.currency_code || baseCurrency"
                    />
                    <button 
                      @click="applyFullBalance(receivable)" 
                      :disabled="!selectedReceivables.includes(receivable.receivable_id)"
                      class="apply-full-btn"
                      title="Apply full balance"
                    >
                      <i class="fas fa-percentage"></i>
                    </button>
                  </div>
                </td>
                <td>
                  <div class="receivable-actions">
                    <button @click="viewReceivable(receivable)" class="action-btn view" title="View Details">
                      <i class="fas fa-eye"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
          
          <div v-if="outstandingReceivables.length === 0" class="empty-state">
            <i class="fas fa-file-invoice"></i>
            <p>No outstanding receivables found for this customer.</p>
          </div>
        </div>
      </div>

      <!-- Unapplied Payments -->
      <div class="payments-section">
        <div class="section-header">
          <h3 class="section-title">
            <i class="fas fa-money-bill-wave"></i>
            Unapplied Payments
          </h3>
          <div class="section-actions">
            <button @click="toggleAllPayments" class="btn btn-outline-sm">
              {{ allPaymentsSelected ? 'Deselect All' : 'Select All' }}
            </button>
          </div>
        </div>
        
        <div class="payments-table-container">
          <table class="payments-table">
            <thead>
              <tr>
                <th width="40"></th>
                <th>Payment ID</th>
                <th>Date</th>
                <th>Payment Currency</th>
                <th>Payment Amount</th>
                <th>Available Amount</th>
                <th>Use Amount</th>
                <th>Exchange Rate</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr 
                v-for="payment in unappliedPayments" 
                :key="payment.payment_id"
                class="payment-row"
                :class="{ 'selected': selectedPayments.includes(payment.payment_id) }"
              >
                <td>
                  <input 
                    type="checkbox" 
                    :value="payment.payment_id"
                    v-model="selectedPayments"
                    @change="calculateTotals"
                    class="checkbox-input"
                  />
                </td>
                <td>
                  <div class="payment-info">
                    <div class="payment-id">#{{ payment.payment_id }}</div>
                    <div class="payment-method">{{ payment.payment_method }}</div>
                  </div>
                </td>
                <td class="date-cell">
                  {{ formatDate(payment.payment_date) }}
                </td>
                <td class="currency-cell">
                  <span class="currency-badge">{{ payment.payment_currency || baseCurrency }}</span>
                </td>
                <td class="amount-cell">
                  {{ formatCurrency(payment.amount, payment.payment_currency) }}
                </td>
                <td class="available-cell">
                  <span class="available-amount">
                    {{ formatCurrency(payment.unapplied_amount || payment.amount, payment.payment_currency) }}
                  </span>
                </td>
                <td class="use-amount-cell">
                  <div class="amount-input-wrapper">
                    <input 
                      type="number" 
                      v-model="paymentUseAmounts[payment.payment_id]"
                      @input="calculateTotals"
                      :max="payment.unapplied_amount || payment.amount"
                      :disabled="!selectedPayments.includes(payment.payment_id)"
                      class="amount-input"
                      step="0.01"
                      min="0"
                      :placeholder="payment.payment_currency || baseCurrency"
                    />
                    <button 
                      @click="useFullAmount(payment)" 
                      :disabled="!selectedPayments.includes(payment.payment_id)"
                      class="use-full-btn"
                      title="Use full amount"
                    >
                      <i class="fas fa-percentage"></i>
                    </button>
                  </div>
                </td>
                <td class="exchange-rate-cell">
                  <span v-if="payment.exchange_rate && payment.exchange_rate !== 1" class="exchange-rate">
                    {{ formatNumber(payment.exchange_rate, 4) }}
                  </span>
                  <span v-else class="no-exchange">-</span>
                </td>
                <td>
                  <div class="payment-actions">
                    <button @click="viewPayment(payment)" class="action-btn view" title="View Details">
                      <i class="fas fa-eye"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
          
          <div v-if="unappliedPayments.length === 0" class="empty-state">
            <i class="fas fa-money-bill-wave"></i>
            <p>No unapplied payments found for this customer.</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Application Summary and Controls -->
    <div v-if="hasSelections" class="application-summary-card">
      <div class="card-header">
        <h3 class="card-title">
          <i class="fas fa-calculator"></i>
          Application Summary
        </h3>
      </div>
      <div class="card-content">
        <div class="summary-content">
          <!-- Currency-wise breakdown -->
          <div class="currency-summary">
            <h4 class="summary-section-title">Currency Breakdown:</h4>
            <div class="currency-breakdown-grid">
              <div v-for="summary in currencyApplicationSummary" :key="summary.currency" class="currency-summary-item">
                <div class="currency-header">
                  <span class="currency-code">{{ summary.currency }}</span>
                  <span v-if="summary.currency !== baseCurrency" class="conversion-info">
                    (Rate: {{ formatNumber(summary.avgExchangeRate, 4) }})
                  </span>
                </div>
                <div class="currency-amounts">
                  <div class="amount-row">
                    <span>Payment Amount:</span>
                    <span>{{ formatCurrency(summary.paymentAmount, summary.currency) }}</span>
                  </div>
                  <div class="amount-row">
                    <span>Application Amount:</span>
                    <span>{{ formatCurrency(summary.applicationAmount, summary.currency) }}</span>
                  </div>
                  <div v-if="summary.currency !== baseCurrency" class="amount-row">
                    <span>Base Currency Equivalent:</span>
                    <span>{{ formatCurrency(summary.baseCurrencyAmount, baseCurrency) }}</span>
                  </div>
                  <div class="amount-row difference" :class="getDifferenceClass(summary.difference)">
                    <span>Difference:</span>
                    <span>{{ formatCurrency(Math.abs(summary.difference), summary.currency) }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Total Summary -->
          <div class="total-summary">
            <div class="total-row">
              <span class="total-label">Total Payment Amount ({{ baseCurrency }}):</span>
              <span class="total-value">{{ formatCurrency(totalPaymentAmountInBase, baseCurrency) }}</span>
            </div>
            <div class="total-row">
              <span class="total-label">Total Application Amount ({{ baseCurrency }}):</span>
              <span class="total-value">{{ formatCurrency(totalApplicationAmountInBase, baseCurrency) }}</span>
            </div>
            <div class="total-row difference" :class="getDifferenceClass(totalDifferenceInBase)">
              <span class="total-label">Net Difference ({{ baseCurrency }}):</span>
              <span class="total-value">{{ formatCurrency(Math.abs(totalDifferenceInBase), baseCurrency) }}</span>
            </div>
          </div>

          <!-- Exchange Differences -->
          <div v-if="totalExchangeDifference !== 0" class="exchange-summary">
            <h4 class="summary-section-title">Exchange Impact:</h4>
            <div class="exchange-item" :class="totalExchangeDifference > 0 ? 'gain' : 'loss'">
              <span>Total Exchange {{ totalExchangeDifference > 0 ? 'Gain' : 'Loss' }}:</span>
              <span>{{ formatCurrency(Math.abs(totalExchangeDifference), baseCurrency) }}</span>
            </div>
          </div>

          <!-- Validation Messages -->
          <div v-if="validationErrors.length > 0" class="validation-errors">
            <h4 class="error-title">Validation Errors:</h4>
            <ul class="error-list">
              <li v-for="error in validationErrors" :key="error" class="error-item">
                <i class="fas fa-exclamation-circle"></i>
                {{ error }}
              </li>
            </ul>
          </div>

          <!-- Application Status -->
          <div class="application-status">
            <div class="status-indicator" :class="getApplicationStatusClass()">
              <i class="fas" :class="getApplicationStatus() === 'Balanced' ? 'fa-check-circle' : 'fa-exclamation-triangle'"></i>
              <span>{{ getApplicationStatus() }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Action Buttons -->
    <div v-if="hasSelections" class="action-buttons">
      <button @click="clearAllApplications" class="btn btn-outline">
        <i class="fas fa-times"></i>
        Clear All
      </button>
      <button 
        @click="previewApplication" 
        :disabled="!canPreview"
        class="btn btn-secondary"
      >
        <i class="fas fa-eye"></i>
        Preview Application
      </button>
      <button 
        @click="processApplication" 
        :disabled="!canProcess || processing"
        class="btn btn-primary"
      >
        <i v-if="processing" class="fas fa-spinner fa-spin"></i>
        <i v-else class="fas fa-check"></i>
        {{ processing ? 'Processing...' : 'Process Application' }}
      </button>
    </div>

    <!-- Preview Modal -->
    <div v-if="showPreviewModal" class="modal-overlay" @click="closePreviewModal">
      <div class="modal-content preview-modal" @click.stop>
        <div class="modal-header">
          <h3 class="modal-title">Application Preview</h3>
          <button @click="closePreviewModal" class="close-btn">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <div class="preview-content">
            <h4>Applications to be processed:</h4>
            <div class="preview-applications">
              <div v-for="application in previewApplications" :key="`${application.receivableId}-${application.paymentId}`" class="preview-item">
                <div class="preview-info">
                  <span class="preview-label">Invoice #{{ application.invoiceId }}</span>
                  <span class="preview-amount">{{ formatCurrency(application.amount, application.currency) }}</span>
                </div>
                <div class="preview-details">
                  <span>Payment #{{ application.paymentId }}</span>
                  <span v-if="application.exchangeRate && application.exchangeRate !== 1">
                    (Rate: {{ formatNumber(application.exchangeRate, 4) }})
                  </span>
                </div>
              </div>
            </div>
            <div class="preview-summary">
              <div class="summary-row">
                <span>Total Applications:</span>
                <span>{{ previewApplications.length }}</span>
              </div>
              <div class="summary-row">
                <span>Total Amount ({{ baseCurrency }}):</span>
                <span>{{ formatCurrency(totalApplicationAmountInBase, baseCurrency) }}</span>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button @click="closePreviewModal" class="btn btn-outline">
            Cancel
          </button>
          <button @click="confirmAndProcess" class="btn btn-primary">
            <i class="fas fa-check"></i>
            Confirm & Process
          </button>
        </div>
      </div>
    </div>

    <!-- Success Modal -->
    <div v-if="showSuccessModal" class="modal-overlay" @click="closeSuccessModal">
      <div class="modal-content" @click.stop>
        <div class="modal-header success">
          <div class="success-icon">
            <i class="fas fa-check-circle"></i>
          </div>
          <h3 class="modal-title">Application Successful!</h3>
        </div>
        <div class="modal-body">
          <p>Payment applications have been processed successfully.</p>
          <div class="success-summary">
            <div class="success-item">
              <span>Applications Processed:</span>
              <span>{{ processedApplications }}</span>
            </div>
            <div class="success-item">
              <span>Total Amount Applied ({{ baseCurrency }}):</span>
              <span>{{ formatCurrency(totalApplicationAmountInBase, baseCurrency) }}</span>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button @click="processAnother" class="btn btn-outline">
            Process Another
          </button>
          <button @click="viewPayments" class="btn btn-primary">
            View Payments
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

export default {
  name: 'PaymentApplication',
  setup() {
    const router = useRouter()
    const loading = ref(false)
    const processing = ref(false)
    const customers = ref([])
    const selectedCustomerId = ref('')
    const selectedCustomer = ref(null)
    const outstandingReceivables = ref([])
    const unappliedPayments = ref([])
    const selectedReceivables = ref([])
    const selectedPayments = ref([])
    const applicationAmounts = reactive({})
    const paymentUseAmounts = reactive({})
    const customerSummary = ref(null)
    const showPreviewModal = ref(false)
    const showSuccessModal = ref(false)
    const previewApplications = ref([])
    const processedApplications = ref(0)
    const baseCurrency = ref('USD')
    
    const validationErrors = computed(() => {
      const errors = []
      
      if (selectedReceivables.value.length === 0) {
        errors.push('Please select at least one receivable')
      }
      
      if (selectedPayments.value.length === 0) {
        errors.push('Please select at least one payment')
      }
      
      // Check individual application amounts
      selectedReceivables.value.forEach(receivableId => {
        const amount = parseFloat(applicationAmounts[receivableId] || 0)
        const receivable = outstandingReceivables.value.find(r => r.receivable_id === receivableId)
        if (amount > receivable?.balance) {
          errors.push(`Application amount for Invoice #${receivable.invoice_id} exceeds balance`)
        }
        if (amount <= 0) {
          errors.push(`Application amount for Invoice #${receivable.invoice_id} must be greater than zero`)
        }
      })
      
      // Check payment use amounts
      selectedPayments.value.forEach(paymentId => {
        const amount = parseFloat(paymentUseAmounts[paymentId] || 0)
        const payment = unappliedPayments.value.find(p => p.payment_id === paymentId)
        if (amount > (payment?.unapplied_amount || payment?.amount)) {
          errors.push(`Use amount for Payment #${paymentId} exceeds available amount`)
        }
        if (amount <= 0) {
          errors.push(`Use amount for Payment #${paymentId} must be greater than zero`)
        }
      })
      
      return errors
    })
    
    const currencyApplicationSummary = computed(() => {
      const currencyGroups = {}
      
      // Group by currency
      selectedPayments.value.forEach(paymentId => {
        const payment = unappliedPayments.value.find(p => p.payment_id === paymentId)
        const currency = payment?.payment_currency || baseCurrency.value
        
        if (!currencyGroups[currency]) {
          currencyGroups[currency] = {
            currency,
            paymentAmount: 0,
            applicationAmount: 0,
            baseCurrencyAmount: 0,
            exchangeRates: [],
            avgExchangeRate: 1
          }
        }
        
        const useAmount = parseFloat(paymentUseAmounts[paymentId] || 0)
        const exchangeRate = payment?.exchange_rate || 1
        
        currencyGroups[currency].paymentAmount += useAmount
        currencyGroups[currency].baseCurrencyAmount += useAmount * exchangeRate
        currencyGroups[currency].exchangeRates.push(exchangeRate)
      })
      
      selectedReceivables.value.forEach(receivableId => {
        const receivable = outstandingReceivables.value.find(r => r.receivable_id === receivableId)
        const currency = receivable?.currency_code || baseCurrency.value
        const applicationAmount = parseFloat(applicationAmounts[receivableId] || 0)
        
        if (currencyGroups[currency]) {
          currencyGroups[currency].applicationAmount += applicationAmount
        }
      })
      
      // Calculate average exchange rates and differences
      Object.values(currencyGroups).forEach(group => {
        if (group.exchangeRates.length > 0) {
          group.avgExchangeRate = group.exchangeRates.reduce((sum, rate) => sum + rate, 0) / group.exchangeRates.length
        }
        group.difference = group.paymentAmount - group.applicationAmount
      })
      
      return Object.values(currencyGroups)
    })
    
    const totalPaymentAmountInBase = computed(() => {
      return currencyApplicationSummary.value.reduce((total, summary) => total + summary.baseCurrencyAmount, 0)
    })
    
    const totalApplicationAmountInBase = computed(() => {
      return selectedReceivables.value.reduce((total, receivableId) => {
        const receivable = outstandingReceivables.value.find(r => r.receivable_id === receivableId)
        const applicationAmount = parseFloat(applicationAmounts[receivableId] || 0)
        const exchangeRate = getReceivableExchangeRate(receivable)
        return total + (applicationAmount * exchangeRate)
      }, 0)
    })
    
    const totalDifferenceInBase = computed(() => {
      return totalPaymentAmountInBase.value - totalApplicationAmountInBase.value
    })
    
    const totalExchangeDifference = computed(() => {
      // Calculate potential exchange differences
      let totalDiff = 0
      
      selectedPayments.value.forEach(paymentId => {
        const payment = unappliedPayments.value.find(p => p.payment_id === paymentId)
        parseFloat(paymentUseAmounts[paymentId] || 0)

        if (payment?.payment_currency !== baseCurrency.value) {
          // Simplified calculation - in real implementation, this would be more complex
          totalDiff += payment.exchange_difference || 0
        }
      })
      
      return totalDiff
    })
    
    const allReceivablesSelected = computed(() => {
      return outstandingReceivables.value.length > 0 && 
             selectedReceivables.value.length === outstandingReceivables.value.length
    })
    
    const allPaymentsSelected = computed(() => {
      return unappliedPayments.value.length > 0 && 
             selectedPayments.value.length === unappliedPayments.value.length
    })
    
    const hasSelections = computed(() => {
      return selectedReceivables.value.length > 0 || selectedPayments.value.length > 0
    })
    
    const canPreview = computed(() => {
      return selectedReceivables.value.length > 0 && 
             selectedPayments.value.length > 0 && 
             validationErrors.value.length === 0
    })
    
    const canProcess = computed(() => {
      return canPreview.value && Math.abs(totalDifferenceInBase.value) < 0.01
    })

    const fetchCustomers = async () => {
      try {
        const response = await axios.get('/accounting/customers')
        customers.value = response.data.data || response.data
      } catch (error) {
        console.error('Error fetching customers:', error)
      }
    }

    const loadCustomerData = async () => {
      if (!selectedCustomerId.value) {
        clearData()
        return
      }
      
      try {
        loading.value = true
        
        // Get selected customer details
        selectedCustomer.value = customers.value.find(c => c.customer_id == selectedCustomerId.value)
        
        // Fetch outstanding receivables
        const receivablesResponse = await axios.get('/accounting/customer-receivables', {
          params: {
            customer_id: selectedCustomerId.value,
            status: 'Open'
          }
        })
        outstandingReceivables.value = receivablesResponse.data.data || []
        
        // Fetch unapplied payments
        const paymentsResponse = await axios.get('/accounting/receivable-payments', {
          params: {
            customer_id: selectedCustomerId.value,
            status: 'Unapplied'
          }
        })
        unappliedPayments.value = paymentsResponse.data.data || []
        
        // Calculate customer summary
        calculateCustomerSummary()
        
        // Clear previous selections
        clearSelections()
        
      } catch (error) {
        console.error('Error loading customer data:', error)
      } finally {
        loading.value = false
      }
    }

    const calculateCustomerSummary = () => {
      const currencies = {}
      
      // Process receivables
      outstandingReceivables.value.forEach(receivable => {
        const currency = receivable.currency_code || baseCurrency.value
        if (!currencies[currency]) {
          currencies[currency] = { code: currency, receivables: 0, payments: 0, net: 0 }
        }
        currencies[currency].receivables += receivable.balance
      })
      
      // Process payments
      unappliedPayments.value.forEach(payment => {
        const currency = payment.payment_currency || baseCurrency.value
        if (!currencies[currency]) {
          currencies[currency] = { code: currency, receivables: 0, payments: 0, net: 0 }
        }
        currencies[currency].payments += (payment.unapplied_amount || payment.amount)
      })
      
      // Calculate net amounts
      Object.values(currencies).forEach(currency => {
        currency.net = currency.payments - currency.receivables
      })
      
      customerSummary.value = {
        currencies: Object.values(currencies)
      }
    }

    const getReceivableExchangeRate = (receivable) => {
      // Get exchange rate for receivable currency to base currency
      // This would typically come from the exchange rate service
      return receivable?.exchange_rate || 1
    }

    const clearData = () => {
      selectedCustomer.value = null
      outstandingReceivables.value = []
      unappliedPayments.value = []
      customerSummary.value = null
      clearSelections()
    }

    const clearSelections = () => {
      selectedReceivables.value = []
      selectedPayments.value = []
      Object.keys(applicationAmounts).forEach(key => delete applicationAmounts[key])
      Object.keys(paymentUseAmounts).forEach(key => delete paymentUseAmounts[key])
    }

    const toggleAllReceivables = () => {
      if (allReceivablesSelected.value) {
        selectedReceivables.value = []
        Object.keys(applicationAmounts).forEach(key => delete applicationAmounts[key])
      } else {
        selectedReceivables.value = outstandingReceivables.value.map(r => r.receivable_id)
        outstandingReceivables.value.forEach(receivable => {
          applicationAmounts[receivable.receivable_id] = receivable.balance
        })
      }
      calculateTotals()
    }

    const toggleAllPayments = () => {
      if (allPaymentsSelected.value) {
        selectedPayments.value = []
        Object.keys(paymentUseAmounts).forEach(key => delete paymentUseAmounts[key])
      } else {
        selectedPayments.value = unappliedPayments.value.map(p => p.payment_id)
        unappliedPayments.value.forEach(payment => {
          paymentUseAmounts[payment.payment_id] = payment.unapplied_amount || payment.amount
        })
      }
      calculateTotals()
    }

    const applyFullBalance = (receivable) => {
      applicationAmounts[receivable.receivable_id] = receivable.balance
      calculateTotals()
    }

    const useFullAmount = (payment) => {
      paymentUseAmounts[payment.payment_id] = payment.unapplied_amount || payment.amount
      calculateTotals()
    }

    const calculateTotals = () => {
      // Trigger reactivity for computed values
    }

    const autoApplyPayments = () => {
      // Smart auto-application logic with currency consideration
      // const applicationPlan = []
      
      // Group by currency for smarter matching
      const receivablesByCurrency = {}
      const paymentsByCurrency = {}
      
      selectedReceivables.value.forEach(receivableId => {
        const receivable = outstandingReceivables.value.find(r => r.receivable_id === receivableId)
        const currency = receivable.currency_code || baseCurrency.value
        if (!receivablesByCurrency[currency]) receivablesByCurrency[currency] = []
        receivablesByCurrency[currency].push(receivable)
      })
      
      selectedPayments.value.forEach(paymentId => {
        const payment = unappliedPayments.value.find(p => p.payment_id === paymentId)
        const currency = payment.payment_currency || baseCurrency.value
        if (!paymentsByCurrency[currency]) paymentsByCurrency[currency] = []
        paymentsByCurrency[currency].push(payment)
      })
      
      // Apply payments to receivables in the same currency first
      Object.keys(receivablesByCurrency).forEach(currency => {
        if (paymentsByCurrency[currency]) {
          const receivables = receivablesByCurrency[currency].sort((a, b) => new Date(a.due_date) - new Date(b.due_date))
          const payments = paymentsByCurrency[currency]
          
          let availablePaymentAmount = payments.reduce((sum, p) => sum + (p.unapplied_amount || p.amount), 0)
          
          receivables.forEach(receivable => {
            if (availablePaymentAmount > 0) {
              const applyAmount = Math.min(availablePaymentAmount, receivable.balance)
              applicationAmounts[receivable.receivable_id] = applyAmount
              availablePaymentAmount -= applyAmount
            }
          })
          
          // Distribute use amounts proportionally
          const totalPaymentAmount = payments.reduce((sum, p) => sum + (p.unapplied_amount || p.amount), 0)
          const usedAmount = totalPaymentAmount - availablePaymentAmount
          
          payments.forEach(payment => {
            const proportion = (payment.unapplied_amount || payment.amount) / totalPaymentAmount
            paymentUseAmounts[payment.payment_id] = usedAmount * proportion
          })
        }
      })
      
      calculateTotals()
    }

    const clearAllApplications = () => {
      clearSelections()
    }

    const previewApplication = () => {
      previewApplications.value = []
      
      selectedReceivables.value.forEach(receivableId => {
        const amount = parseFloat(applicationAmounts[receivableId] || 0)
        if (amount > 0) {
          const receivable = outstandingReceivables.value.find(r => r.receivable_id === receivableId)
          
          // For simplicity, apply to first selected payment
          // In real implementation, you might want more sophisticated allocation
          const paymentId = selectedPayments.value[0]
          const payment = unappliedPayments.value.find(p => p.payment_id === paymentId)
          
          previewApplications.value.push({
            receivableId,
            paymentId,
            invoiceId: receivable.invoice_id || receivable.receivable_id,
            amount,
            currency: receivable.currency_code || baseCurrency.value,
            exchangeRate: payment?.exchange_rate || 1
          })
        }
      })
      
      showPreviewModal.value = true
    }

    const closePreviewModal = () => {
      showPreviewModal.value = false
    }

    const confirmAndProcess = () => {
      closePreviewModal()
      processApplication()
    }

    const processApplication = async () => {
      try {
        processing.value = true
        
        const applications = []
        
        selectedReceivables.value.forEach(receivableId => {
          const amount = parseFloat(applicationAmounts[receivableId] || 0)
          if (amount > 0) {
            // For simplicity, allocate to first payment
            // In real implementation, implement proper allocation logic
            const paymentId = selectedPayments.value[0]
            
            applications.push({
              receivable_id: receivableId,
              payment_id: paymentId,
              application_amount: amount
            })
          }
        })
        
        await axios.post('/accounting/payment-applications', {
          customer_id: selectedCustomerId.value,
          applications
        })
        
        processedApplications.value = applications.length
        showSuccessModal.value = true
        
        // Reload data
        await loadCustomerData()
        
      } catch (error) {
        console.error('Error processing application:', error)
        // Show error message
      } finally {
        processing.value = false
      }
    }

    const closeSuccessModal = () => {
      showSuccessModal.value = false
    }

    const processAnother = () => {
      closeSuccessModal()
      selectedCustomerId.value = ''
      clearData()
    }

    const viewPayments = () => {
      router.push('/accounting/receivable-payments')
    }

    const viewReceivable = (receivable) => {
      router.push(`/accounting/customer-receivables/${receivable.receivable_id}`)
    }

    const viewPayment = (payment) => {
      router.push(`/accounting/receivable-payments/${payment.payment_id}`)
    }

    const getApplicationStatus = () => {
      if (validationErrors.value.length > 0) return 'Invalid'
      if (Math.abs(totalDifferenceInBase.value) < 0.01) return 'Balanced'
      if (totalDifferenceInBase.value > 0) return 'Overpaid'
      return 'Underpaid'
    }

    const getApplicationStatusClass = () => {
      const status = getApplicationStatus()
      return {
        'status-valid': status === 'Balanced',
        'status-invalid': status === 'Invalid',
        'status-warning': status === 'Overpaid' || status === 'Underpaid'
      }
    }

    const getDifferenceClass = (difference) => {
      if (Math.abs(difference) < 0.01) return 'text-success'
      return 'text-danger'
    }

    const isOverdue = (dateString) => {
      if (!dateString) return false
      return new Date(dateString) < new Date()
    }

    const getDaysOverdue = (dateString) => {
      if (!dateString) return 0
      const dueDate = new Date(dateString)
      const today = new Date()
      const diffTime = today - dueDate
      return Math.ceil(diffTime / (1000 * 60 * 60 * 24))
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
        month: 'short',
        day: 'numeric'
      })
    }

    onMounted(async () => {
      await fetchCustomers()
      
      // Get base currency from config
      try {
        const configResponse = await axios.get('/accounting/config')
        baseCurrency.value = configResponse.data.base_currency || 'USD'
      } catch (error) {
        console.error('Error loading config:', error)
      }
    })

    return {
      loading,
      processing,
      customers,
      selectedCustomerId,
      selectedCustomer,
      outstandingReceivables,
      unappliedPayments,
      selectedReceivables,
      selectedPayments,
      applicationAmounts,
      paymentUseAmounts,
      customerSummary,
      showPreviewModal,
      showSuccessModal,
      previewApplications,
      processedApplications,
      baseCurrency,
      validationErrors,
      currencyApplicationSummary,
      totalPaymentAmountInBase,
      totalApplicationAmountInBase,
      totalDifferenceInBase,
      totalExchangeDifference,
      allReceivablesSelected,
      allPaymentsSelected,
      hasSelections,
      canPreview,
      canProcess,
      loadCustomerData,
      toggleAllReceivables,
      toggleAllPayments,
      applyFullBalance,
      useFullAmount,
      calculateTotals,
      autoApplyPayments,
      clearAllApplications,
      previewApplication,
      closePreviewModal,
      confirmAndProcess,
      processApplication,
      closeSuccessModal,
      processAnother,
      viewPayments,
      viewReceivable,
      viewPayment,
      getApplicationStatus,
      getApplicationStatusClass,
      getDifferenceClass,
      isOverdue,
      getDaysOverdue,
      formatCurrency,
      formatNumber,
      formatDate
    }
  }
}
</script>

<style scoped>
/* Main container styles */
.payment-application-container {
  min-height: 100vh;
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
  padding: 2rem;
}

.page-header {
  background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
  border-radius: 20px;
  padding: 2rem;
  margin-bottom: 2rem;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
}

.header-content {
  text-align: center;
}

.page-title {
  font-size: 2.5rem;
  font-weight: 700;
  color: #1e293b;
  margin: 0 0 0.5rem 0;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1rem;
}

.page-title i {
  color: #6366f1;
}

.page-subtitle {
  color: #64748b;
  font-size: 1.125rem;
  margin: 0;
}

/* Card styles */
.customer-selection-card,
.customer-summary-card,
.application-summary-card {
  background: white;
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
  margin-bottom: 2rem;
  overflow: hidden;
}

.card-header {
  padding: 1.5rem;
  background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
  border-bottom: 1px solid #f1f5f9;
}

.card-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #1e293b;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin: 0;
}

.card-title i {
  color: #6366f1;
}

.card-content {
  padding: 1.5rem;
}

/* Customer selection */
.customer-select-wrapper {
  position: relative;
}

.customer-select {
  width: 100%;
  padding: 1rem;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  font-size: 1rem;
  background: white;
  transition: all 0.3s ease;
}

.customer-select:focus {
  outline: none;
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.loading-indicator {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-top: 0.5rem;
  color: #6366f1;
  font-size: 0.875rem;
}

/* Customer summary */
.summary-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.5rem;
  margin-bottom: 1.5rem;
}

.summary-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: #f8fafc;
  border-radius: 12px;
}

.summary-icon {
  width: 50px;
  height: 50px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  color: white;
}

.summary-icon.receivables {
  background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
}

.summary-icon.payments {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.summary-icon.balance {
  background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
}

.summary-content {
  flex: 1;
}

.summary-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1e293b;
  line-height: 1;
}

.summary-label {
  color: #64748b;
  font-size: 0.875rem;
  margin-top: 0.25rem;
}

.currency-breakdown {
  grid-column: 1 / -1;
  background: #f0f9ff;
  border-radius: 12px;
  padding: 1.5rem;
  border: 1px solid #bae6fd;
}

.breakdown-title {
  font-size: 1rem;
  font-weight: 600;
  color: #0c4a6e;
  margin-bottom: 1rem;
}

.currency-items {
  display: grid;
  gap: 1rem;
}

.currency-item {
  background: white;
  border-radius: 8px;
  padding: 1rem;
  border: 1px solid #e0f2fe;
}

.currency-code {
  font-weight: 700;
  color: #0c4a6e;
  font-size: 1.125rem;
}

.currency-amounts {
  margin-top: 0.5rem;
  display: grid;
  gap: 0.25rem;
  font-size: 0.875rem;
}

.receivable-amount {
  color: #dc2626;
}

.payment-amount {
  color: #059669;
}

.net-amount.positive {
  color: #059669;
}

.net-amount.negative {
  color: #dc2626;
}

/* Application interface */
.application-interface {
  display: grid;
  gap: 2rem;
  margin-bottom: 2rem;
}

.receivables-section,
.payments-section {
  background: white;
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
  border: 1px solid #f1f5f9;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-bottom: 1px solid #f1f5f9;
  background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
}

.section-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #1e293b;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin: 0;
}

.section-title i {
  color: #6366f1;
}

.section-actions {
  display: flex;
  gap: 0.75rem;
}

/* Button styles */
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

.btn-primary {
  background: #6366f1;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: #5046e6;
}

.btn-secondary {
  background: #64748b;
  color: white;
}

.btn-secondary:hover:not(:disabled) {
  background: #475569;
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

.btn-outline-sm,
.btn-secondary-sm {
  padding: 0.5rem 1rem;
  font-size: 0.875rem;
}

/* Table styles */
.receivables-table-container,
.payments-table-container {
  overflow-x: auto;
}

.receivables-table,
.payments-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.875rem;
}

.receivables-table th,
.receivables-table td,
.payments-table th,
.payments-table td {
  padding: 1rem;
  text-align: left;
  border-bottom: 1px solid #f1f5f9;
}

.receivables-table th,
.payments-table th {
  background: #f8fafc;
  font-weight: 600;
  color: #374151;
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.receivable-row,
.payment-row {
  transition: all 0.3s ease;
}

.receivable-row:hover,
.payment-row:hover {
  background: #f8fafc;
}

.receivable-row.selected,
.payment-row.selected {
  background: rgba(99, 102, 241, 0.05);
}

.receivable-row.overdue {
  background: rgba(239, 68, 68, 0.05);
}

.checkbox-input {
  width: 18px;
  height: 18px;
  border-radius: 4px;
  border: 2px solid #e2e8f0;
  cursor: pointer;
}

.checkbox-input:checked {
  background: #6366f1;
  border-color: #6366f1;
}

/* Table cell styles */
.invoice-info,
.payment-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.invoice-number,
.payment-id {
  font-weight: 600;
  color: #1e293b;
}

.invoice-type,
.payment-method {
  font-size: 0.75rem;
  color: #64748b;
}

.currency-badge {
  background: #e0e7ff;
  color: #3730a3;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 600;
}

.due-date-cell,
.date-cell {
  color: #64748b;
}

.text-danger {
  color: #ef4444;
}

.overdue-indicator {
  display: block;
  font-size: 0.75rem;
  color: #ef4444;
  margin-top: 0.25rem;
}

.amount-cell,
.balance-cell,
.available-cell {
  font-weight: 600;
}

.balance-amount {
  color: #ef4444;
}

.available-amount {
  color: #059669;
}

.amount-input-wrapper {
  display: flex;
  gap: 0.5rem;
  align-items: center;
}

.amount-input {
  flex: 1;
  padding: 0.5rem;
  border: 2px solid #e2e8f0;
  border-radius: 6px;
  font-size: 0.875rem;
  min-width: 100px;
}

.amount-input:focus {
  outline: none;
  border-color: #6366f1;
}

.amount-input:disabled {
  background: #f8fafc;
  color: #94a3b8;
}

.apply-full-btn,
.use-full-btn {
  background: #6366f1;
  color: white;
  border: none;
  border-radius: 4px;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.3s ease;
}

.apply-full-btn:hover:not(:disabled),
.use-full-btn:hover:not(:disabled) {
  background: #5046e6;
}

.apply-full-btn:disabled,
.use-full-btn:disabled {
  background: #e2e8f0;
  color: #94a3b8;
  cursor: not-allowed;
}

.exchange-rate {
  font-family: monospace;
  color: #6366f1;
  font-weight: 600;
}

.no-exchange {
  color: #94a3b8;
}

.action-btn {
  width: 32px;
  height: 32px;
  border-radius: 6px;
  border: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
}

.action-btn.view {
  background: #dbeafe;
  color: #1e40af;
}

.action-btn.view:hover {
  background: #1e40af;
  color: white;
}

.empty-state {
  text-align: center;
  padding: 3rem 2rem;
  color: #64748b;
}

.empty-state i {
  font-size: 3rem;
  margin-bottom: 1rem;
}

/* Application summary */
.summary-content {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

.summary-section-title {
  font-size: 1rem;
  font-weight: 600;
  color: #1e293b;
  margin-bottom: 1rem;
}

.currency-breakdown-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 1.5rem;
}

.currency-summary-item {
  background: #f8fafc;
  border-radius: 12px;
  padding: 1.5rem;
  border: 1px solid #e2e8f0;
}

.currency-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.currency-code {
  font-size: 1.25rem;
  font-weight: 700;
  color: #1e293b;
}

.conversion-info {
  font-size: 0.875rem;
  color: #64748b;
}

.currency-amounts {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.amount-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.amount-row.difference {
  padding-top: 0.75rem;
  border-top: 1px solid #e2e8f0;
  font-weight: 600;
}

.total-summary {
  background: #f0f9ff;
  border-radius: 12px;
  padding: 1.5rem;
  border: 1px solid #bae6fd;
}

.total-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.75rem;
}

.total-row:last-child {
  margin-bottom: 0;
}

.total-row.difference {
  padding-top: 0.75rem;
  border-top: 2px solid #0284c7;
  font-weight: 700;
  font-size: 1.125rem;
}

.total-label {
  color: #0c4a6e;
  font-weight: 500;
}

.total-value {
  color: #1e293b;
  font-weight: 600;
}

.text-success {
  color: #059669;
}

.text-danger {
  color: #dc2626;
}

.exchange-summary {
  background: #fef3c7;
  border-radius: 12px;
  padding: 1.5rem;
  border: 1px solid #fbbf24;
}

.exchange-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-weight: 600;
}

.exchange-item.gain {
  color: #059669;
}

.exchange-item.loss {
  color: #dc2626;
}

.validation-errors {
  background: #fef2f2;
  border-radius: 12px;
  padding: 1.5rem;
  border: 1px solid #fecaca;
}

.error-title {
  color: #dc2626;
  font-size: 1rem;
  font-weight: 600;
  margin-bottom: 1rem;
}

.error-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

.error-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #dc2626;
  margin-bottom: 0.5rem;
}

.error-item:last-child {
  margin-bottom: 0;
}

.application-status {
  text-align: center;
}

.status-indicator {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 600;
  font-size: 1rem;
}

.status-indicator.status-valid {
  background: #dcfce7;
  color: #166534;
}

.status-indicator.status-invalid {
  background: #fef2f2;
  color: #dc2626;
}

.status-indicator.status-warning {
  background: #fef3c7;
  color: #92400e;
}

/* Action buttons */
.action-buttons {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
  margin-bottom: 2rem;
}

/* Modal styles */
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

.preview-modal {
  max-width: 700px;
}

.modal-header {
  padding: 1.5rem;
  border-bottom: 1px solid #f1f5f9;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-header.success {
  background: linear-gradient(135deg, #059669 0%, #10b981 100%);
  color: white;
  text-align: center;
  flex-direction: column;
}

.success-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
}

.modal-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: #1e293b;
  margin: 0;
}

.modal-header.success .modal-title {
  color: white;
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

.preview-content h4 {
  margin-bottom: 1rem;
  color: #1e293b;
}

.preview-applications {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.preview-item {
  background: #f8fafc;
  border-radius: 8px;
  padding: 1rem;
  border: 1px solid #e2e8f0;
}

.preview-info {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.5rem;
}

.preview-label {
  font-weight: 600;
  color: #1e293b;
}

.preview-amount {
  font-weight: 600;
  color: #6366f1;
}

.preview-details {
  display: flex;
  justify-content: space-between;
  font-size: 0.875rem;
  color: #64748b;
}

.preview-summary {
  background: #f0f9ff;
  border-radius: 8px;
  padding: 1rem;
  border: 1px solid #bae6fd;
}

.summary-row {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.5rem;
}

.summary-row:last-child {
  margin-bottom: 0;
  font-weight: 600;
  border-top: 1px solid #0284c7;
  padding-top: 0.5rem;
}

.success-summary {
  background: #f0fdf4;
  border-radius: 8px;
  padding: 1rem;
  margin-top: 1rem;
}

.success-item {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.5rem;
}

.success-item:last-child {
  margin-bottom: 0;
}

.modal-footer {
  padding: 1.5rem;
  border-top: 1px solid #f1f5f9;
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
}

/* Responsive design */
@media (max-width: 768px) {
  .payment-application-container {
    padding: 1rem;
  }

  .page-title {
    font-size: 2rem;
    flex-direction: column;
    gap: 0.5rem;
  }

  .summary-grid {
    grid-template-columns: 1fr;
  }

  .currency-breakdown-grid {
    grid-template-columns: 1fr;
  }

  .section-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }

  .section-actions {
    width: 100%;
    justify-content: flex-start;
  }

  .receivables-table,
  .payments-table {
    font-size: 0.75rem;
  }

  .action-buttons {
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