<template>
  <div class="journal-entry-form-container">
    <!-- Header -->
    <div class="page-header">
      <div class="header-left">
        <h1>
          <i class="fas fa-book"></i>
          {{ isEdit ? 'Edit' : 'Create' }} Journal Entry
        </h1>
        <p class="page-subtitle">
          {{ isEdit ? 'Update existing journal entry' : 'Create a new multicurrency journal entry' }}
        </p>
      </div>
      <div class="header-actions">
        <button @click="resetForm" class="btn btn-secondary" :disabled="saving">
          <i class="fas fa-undo"></i>
          Reset
        </button>
        <button @click="saveEntry" class="btn btn-primary" :disabled="saving || !isFormValid">
          <i v-if="saving" class="fas fa-spinner fa-spin"></i>
          <i v-else class="fas fa-save"></i>
          {{ isEdit ? 'Update' : 'Save' }} Entry
        </button>
      </div>
    </div>

    <!-- Main Form -->
    <div class="form-container">
      <div class="form-layout">
        <!-- Left Column - Entry Details -->
        <div class="entry-details-section">
          <div class="section-card">
            <div class="section-header">
              <h3>
                <i class="fas fa-info-circle"></i>
                Entry Information
              </h3>
            </div>
            
            <div class="section-content">
              <div class="form-row">
                <div class="form-group">
                  <label class="form-label required">Journal Number</label>
                  <input
                    type="text"
                    v-model="form.journal_number"
                    class="form-input"
                    :class="{ 'error': errors.journal_number }"
                    placeholder="Auto-generated"
                    required
                  />
                  <span v-if="errors.journal_number" class="error-message">
                    {{ errors.journal_number[0] }}
                  </span>
                </div>

                <div class="form-group">
                  <label class="form-label required">Entry Date</label>
                  <input
                    type="date"
                    v-model="form.entry_date"
                    class="form-input"
                    :class="{ 'error': errors.entry_date }"
                    @change="onDateChange"
                    required
                  />
                  <span v-if="errors.entry_date" class="error-message">
                    {{ errors.entry_date[0] }}
                  </span>
                </div>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label class="form-label required">Accounting Period</label>
                  <select
                    v-model="form.period_id"
                    class="form-select"
                    :class="{ 'error': errors.period_id }"
                    required
                  >
                    <option value="">Select Period</option>
                    <option v-for="period in periods" :key="period.period_id" :value="period.period_id">
                      {{ period.period_name }} ({{ formatDate(period.start_date) }} - {{ formatDate(period.end_date) }})
                    </option>
                  </select>
                  <span v-if="errors.period_id" class="error-message">
                    {{ errors.period_id[0] }}
                  </span>
                </div>

                <div class="form-group">
                  <label class="form-label">Status</label>
                  <select v-model="form.status" class="form-select">
                    <option value="Draft">Draft</option>
                    <option value="Posted">Posted</option>
                    <option value="Cancelled">Cancelled</option>
                  </select>
                </div>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label class="form-label">Reference Type</label>
                  <select v-model="form.reference_type" class="form-select">
                    <option value="">Select Reference Type</option>
                    <option value="Invoice">Invoice</option>
                    <option value="Payment">Payment</option>
                    <option value="Receipt">Receipt</option>
                    <option value="Adjustment">Adjustment</option>
                    <option value="Transfer">Transfer</option>
                    <option value="Other">Other</option>
                  </select>
                </div>

                <div class="form-group">
                  <label class="form-label">Reference ID</label>
                  <input
                    type="number"
                    v-model="form.reference_id"
                    class="form-input"
                    placeholder="Reference ID"
                  />
                </div>
              </div>

              <div class="form-group">
                <label class="form-label">Description</label>
                <textarea
                  v-model="form.description"
                  class="form-textarea"
                  rows="3"
                  placeholder="Enter journal entry description..."
                ></textarea>
              </div>
            </div>
          </div>

          <!-- Currency Summary -->
          <div class="section-card" v-if="currencySummary.length > 0">
            <div class="section-header">
              <h3>
                <i class="fas fa-exchange-alt"></i>
                Currency Summary
              </h3>
            </div>
            <div class="section-content">
              <div class="currency-summary-grid">
                <div v-for="currency in currencySummary" :key="currency.currency" class="currency-summary-item">
                  <div class="currency-code">{{ currency.currency }}</div>
                  <div class="currency-amounts">
                    <div class="amount-row">
                      <span class="label">Debits:</span>
                      <span class="amount debit">{{ formatCurrency(currency.total_debits, currency.currency) }}</span>
                    </div>
                    <div class="amount-row">
                      <span class="label">Credits:</span>
                      <span class="amount credit">{{ formatCurrency(currency.total_credits, currency.currency) }}</span>
                    </div>
                    <div class="amount-row balance">
                      <span class="label">Balance:</span>
                      <span class="amount" :class="{ 'positive': currency.balance >= 0, 'negative': currency.balance < 0 }">
                        {{ formatCurrency(Math.abs(currency.balance), currency.currency) }}
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Right Column - Journal Lines -->
        <div class="journal-lines-section">
          <div class="section-card">
            <div class="section-header">
              <h3>
                <i class="fas fa-list"></i>
                Journal Entry Lines
              </h3>
              <div class="header-actions">
                <button @click="addLine" class="btn btn-sm btn-success">
                  <i class="fas fa-plus"></i>
                  Add Line
                </button>
                <button @click="showCurrencyConverter = true" class="btn btn-sm btn-secondary">
                  <i class="fas fa-calculator"></i>
                  Currency Converter
                </button>
              </div>
            </div>

            <div class="section-content">
              <!-- Lines Table -->
              <div class="lines-table-container">
                <table class="lines-table">
                  <thead>
                    <tr>
                      <th width="250">Account</th>
                      <th width="120">Currency</th>
                      <th width="140">Debit Amount</th>
                      <th width="140">Credit Amount</th>
                      <th width="120">Foreign Amount</th>
                      <th width="100">Rate</th>
                      <th width="200">Description</th>
                      <th width="60">Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(line, index) in lines" :key="index" class="line-row" :class="{ 'error': lineErrors[index] }">
                      <td>
                        <select
                          v-model="line.account_id"
                          class="form-select"
                          :class="{ 'error': lineErrors[index]?.account_id }"
                          @change="onAccountChange(index)"
                          required
                        >
                          <option value="">Select Account</option>
                          <option v-for="account in accounts" :key="account.account_id" :value="account.account_id">
                            {{ account.account_code }} - {{ account.name }}
                          </option>
                        </select>
                      </td>
                      
                      <td>
                        <select
                          v-model="line.currency"
                          class="form-select"
                          @change="onCurrencyChange(index)"
                        >
                          <option :value="baseCurrency">{{ baseCurrency }}</option>
                          <option v-for="currency in availableCurrencies" :key="currency.code" :value="currency.code">
                            {{ currency.code }}
                          </option>
                        </select>
                      </td>

                      <td>
                        <div class="amount-input-group">
                          <input
                            type="number"
                            v-model.number="line.debit_amount"
                            class="form-input amount-input"
                            :class="{ 'error': lineErrors[index]?.debit_amount }"
                            @input="onAmountChange(index, 'debit')"
                            step="0.01"
                            min="0"
                            placeholder="0.00"
                          />
                          <span class="currency-symbol">{{ line.currency || baseCurrency }}</span>
                        </div>
                      </td>

                      <td>
                        <div class="amount-input-group">
                          <input
                            type="number"
                            v-model.number="line.credit_amount"
                            class="form-input amount-input"
                            :class="{ 'error': lineErrors[index]?.credit_amount }"
                            @input="onAmountChange(index, 'credit')"
                            step="0.01"
                            min="0"
                            placeholder="0.00"
                          />
                          <span class="currency-symbol">{{ line.currency || baseCurrency }}</span>
                        </div>
                      </td>

                      <td>
                        <input
                          type="number"
                          v-model.number="line.foreign_amount"
                          class="form-input"
                          :class="{ 'error': lineErrors[index]?.foreign_amount }"
                          :disabled="line.currency === baseCurrency"
                          @input="onForeignAmountChange(index)"
                          step="0.01"
                          min="0"
                          placeholder="0.00"
                        />
                      </td>

                      <td>
                        <div class="exchange-rate-group">
                          <input
                            type="number"
                            v-model.number="line.exchange_rate"
                            class="form-input rate-input"
                            :disabled="line.currency === baseCurrency"
                            @input="onExchangeRateChange(index)"
                            step="0.000001"
                            min="0"
                            placeholder="1.000000"
                          />
                          <button 
                            v-if="line.currency !== baseCurrency"
                            @click="fetchExchangeRate(index)"
                            class="btn btn-xs btn-secondary rate-fetch-btn"
                            :disabled="fetchingRates[index]"
                            title="Fetch current rate"
                          >
                            <i v-if="fetchingRates[index]" class="fas fa-spinner fa-spin"></i>
                            <i v-else class="fas fa-sync"></i>
                          </button>
                        </div>
                      </td>

                      <td>
                        <input
                          type="text"
                          v-model="line.description"
                          class="form-input"
                          placeholder="Line description"
                        />
                      </td>

                      <td>
                        <button @click="removeLine(index)" class="btn btn-xs btn-danger" :disabled="lines.length <= 2">
                          <i class="fas fa-trash"></i>
                        </button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <!-- Totals Row -->
              <div class="totals-section">
                <div class="totals-grid">
                  <div class="total-item">
                    <label>Total Debits ({{ baseCurrency }}):</label>
                    <span class="total-amount debit">{{ formatCurrency(totalDebits) }}</span>
                  </div>
                  <div class="total-item">
                    <label>Total Credits ({{ baseCurrency }}):</label>
                    <span class="total-amount credit">{{ formatCurrency(totalCredits) }}</span>
                  </div>
                  <div class="total-item">
                    <label>Difference:</label>
                    <span class="total-amount" :class="{ 'balanced': isBalanced, 'unbalanced': !isBalanced }">
                      {{ formatCurrency(Math.abs(totalDebits - totalCredits)) }}
                    </span>
                  </div>
                  <div class="total-item">
                    <label>Status:</label>
                    <span class="balance-status" :class="{ 'balanced': isBalanced, 'unbalanced': !isBalanced }">
                      <i :class="isBalanced ? 'fas fa-check-circle' : 'fas fa-exclamation-triangle'"></i>
                      {{ isBalanced ? 'Balanced' : 'Out of Balance' }}
                    </span>
                  </div>
                </div>
              </div>

              <!-- Line Errors Display -->
              <div v-if="Object.keys(lineErrors).length > 0" class="line-errors">
                <h4>Line Validation Errors:</h4>
                <div v-for="(lineError, index) in lineErrors" :key="index" class="line-error">
                  <strong>Line {{ index + 1 }}:</strong>
                  <ul>
                    <li v-for="(error, field) in lineError" :key="field">
                      {{ field }}: {{ error }}
                    </li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Currency Converter Modal -->
    <div v-if="showCurrencyConverter" class="modal-overlay" @click="showCurrencyConverter = false">
      <div class="modal-content currency-converter-modal" @click.stop>
        <div class="modal-header">
          <h3>
            <i class="fas fa-calculator"></i>
            Currency Converter
          </h3>
          <button @click="showCurrencyConverter = false" class="close-btn">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <div class="converter-form">
            <div class="form-row">
              <div class="form-group">
                <label>From Currency</label>
                <select v-model="converter.fromCurrency" class="form-select">
                  <option :value="baseCurrency">{{ baseCurrency }}</option>
                  <option v-for="currency in availableCurrencies" :key="currency.code" :value="currency.code">
                    {{ currency.code }} - {{ currency.name }}
                  </option>
                </select>
              </div>
              <div class="form-group">
                <label>To Currency</label>
                <select v-model="converter.toCurrency" class="form-select">
                  <option :value="baseCurrency">{{ baseCurrency }}</option>
                  <option v-for="currency in availableCurrencies" :key="currency.code" :value="currency.code">
                    {{ currency.code }} - {{ currency.name }}
                  </option>
                </select>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Amount</label>
                <input
                  type="number"
                  v-model.number="converter.amount"
                  class="form-input"
                  step="0.01"
                  min="0"
                  placeholder="Enter amount"
                />
              </div>
              <div class="form-group">
                <label>Exchange Rate</label>
                <div class="rate-input-group">
                  <input
                    type="number"
                    v-model.number="converter.exchangeRate"
                    class="form-input"
                    step="0.000001"
                    min="0"
                    placeholder="1.000000"
                    readonly
                  />
                  <button @click="fetchConverterRate" class="btn btn-secondary" :disabled="fetchingConverterRate">
                    <i v-if="fetchingConverterRate" class="fas fa-spinner fa-spin"></i>
                    <i v-else class="fas fa-sync"></i>
                    Fetch Rate
                  </button>
                </div>
              </div>
            </div>
            <div class="conversion-result" v-if="converter.result !== null">
              <div class="result-display">
                <span class="original-amount">{{ formatCurrency(converter.amount, converter.fromCurrency) }}</span>
                <i class="fas fa-arrow-right"></i>
                <span class="converted-amount">{{ formatCurrency(converter.result, converter.toCurrency) }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading Overlay -->
    <div v-if="loading" class="loading-overlay">
      <div class="loading-content">
        <i class="fas fa-spinner fa-spin"></i>
        <p>Loading...</p>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'JournalEntryForm',
  props: {
    id: {
      type: [String, Number],
      default: null
    }
  },
  data() {
    return {
      loading: false,
      saving: false,
      
      // Form data
      form: {
        journal_number: '',
        entry_date: new Date().toISOString().split('T')[0],
        reference_type: '',
        reference_id: '',
        description: '',
        period_id: '',
        status: 'Draft'
      },
      
      lines: [],
      
      // Master data
      accounts: [],
      periods: [],
      availableCurrencies: [],
      baseCurrency: 'USD',
      
      // Validation
      errors: {},
      lineErrors: {},
      
      // Exchange rates
      exchangeRates: {},
      fetchingRates: {},
      
      // Currency converter
      showCurrencyConverter: false,
      converter: {
        fromCurrency: 'USD',
        toCurrency: 'USD',
        amount: 0,
        exchangeRate: 1,
        result: null
      },
      fetchingConverterRate: false,
      
      // Original data for reset
      originalData: null
    }
  },
  
  computed: {
    isEdit() {
      return !!this.id
    },
    
    totalDebits() {
      return this.lines.reduce((sum, line) => {
        const amount = line.debit_amount || 0
        const rate = line.exchange_rate || 1
        return sum + (amount * rate)
      }, 0)
    },
    
    totalCredits() {
      return this.lines.reduce((sum, line) => {
        const amount = line.credit_amount || 0
        const rate = line.exchange_rate || 1
        return sum + (amount * rate)
      }, 0)
    },
    
    isBalanced() {
      return Math.abs(this.totalDebits - this.totalCredits) < 0.01
    },
    
    isFormValid() {
      return this.form.journal_number && 
             this.form.entry_date && 
             this.form.period_id && 
             this.lines.length >= 2 &&
             this.lines.every(line => line.account_id && (line.debit_amount > 0 || line.credit_amount > 0)) &&
             this.isBalanced
    },
    
    currencySummary() {
      const summary = {}
      
      this.lines.forEach(line => {
        const currency = line.currency || this.baseCurrency
        if (!summary[currency]) {
          summary[currency] = {
            currency: currency,
            total_debits: 0,
            total_credits: 0,
            balance: 0
          }
        }
        
        summary[currency].total_debits += (line.debit_amount || 0)
        summary[currency].total_credits += (line.credit_amount || 0)
        summary[currency].balance = summary[currency].total_debits - summary[currency].total_credits
      })
      
      return Object.values(summary)
    }
  },
  
  async mounted() {
    await this.loadMasterData()
    await this.initializeForm()
  },
  
  methods: {
    async loadMasterData() {
      this.loading = true
      try {
        const [accountsRes, periodsRes, currenciesRes, settingsRes] = await Promise.all([
          axios.get('/accounting/chart-of-accounts'),
          axios.get('/accounting/accounting-periods'),
          axios.get('/accounting/chart-of-accounts/currencies/available'),
          axios.get('/settings/group/currency')
        ])
        
        this.accounts = accountsRes.data.data || accountsRes.data
        this.periods = periodsRes.data.data || periodsRes.data
        this.availableCurrencies = currenciesRes.data.data || currenciesRes.data
        
        if (settingsRes.data.data) {
          const baseCurrencySetting = settingsRes.data.data.find(s => s.key === 'base_currency')
          if (baseCurrencySetting) {
            this.baseCurrency = baseCurrencySetting.value
          }
        }
        
      } catch (error) {
        console.error('Error loading master data:', error)
        this.$toast.error('Failed to load master data')
      } finally {
        this.loading = false
      }
    },
    
    async initializeForm() {
      if (this.isEdit) {
        await this.loadJournalEntry()
      } else {
        this.lines = [
          this.createEmptyLine(),
          this.createEmptyLine()
        ]
        await this.generateJournalNumber()
      }
      
      // Store original data for reset
      this.originalData = {
        form: JSON.parse(JSON.stringify(this.form)),
        lines: JSON.parse(JSON.stringify(this.lines))
      }
    },
    
    async loadJournalEntry() {
      try {
        const response = await axios.get(`/accounting/journal-entries/${this.id}`)
        const data = response.data.data
        
        this.form = {
          journal_number: data.journal_number,
          entry_date: data.entry_date,
          reference_type: data.reference_type || '',
          reference_id: data.reference_id || '',
          description: data.description || '',
          period_id: data.period_id,
          status: data.status
        }
        
        this.lines = data.journal_entry_lines.map(line => ({
          account_id: line.account_id,
          debit_amount: line.debit_amount || 0,
          credit_amount: line.credit_amount || 0,
          description: line.description || '',
          currency: line.currency || this.baseCurrency,
          foreign_amount: line.foreign_amount || 0,
          exchange_rate: line.exchange_rate || 1
        }))
        
      } catch (error) {
        console.error('Error loading journal entry:', error)
        this.$toast.error('Failed to load journal entry')
        this.$router.push('/accounting/journal-entries')
      }
    },
    
    async generateJournalNumber() {
      try {
        const response = await axios.post('/accounting/journal-entries/generate-number')
        this.form.journal_number = response.data.journal_number
      } catch (error) {
        console.error('Error generating journal number:', error)
      }
    },
    
    createEmptyLine() {
      return {
        account_id: '',
        debit_amount: 0,
        credit_amount: 0,
        description: '',
        currency: this.baseCurrency,
        foreign_amount: 0,
        exchange_rate: 1
      }
    },
    
    addLine() {
      this.lines.push(this.createEmptyLine())
    },
    
    removeLine(index) {
      if (this.lines.length > 2) {
        this.lines.splice(index, 1)
        this.$delete(this.lineErrors, index)
      }
    },
    
    async onAccountChange(index) {
      const line = this.lines[index]
      if (line.account_id) {
        // Check if account supports multi-currency
        const account = this.accounts.find(a => a.account_id === line.account_id)
        if (account && !account.allow_multi_currency) {
          line.currency = this.baseCurrency
          line.exchange_rate = 1
          line.foreign_amount = 0
        }
      }
    },
    
    async onCurrencyChange(index) {
      const line = this.lines[index]
      
      if (line.currency === this.baseCurrency) {
        line.exchange_rate = 1
        line.foreign_amount = 0
      } else {
        await this.fetchExchangeRate(index)
      }
      
      this.updateForeignAmount(index)
    },
    
    onAmountChange(index, type) {
      const line = this.lines[index]
      
      // Clear opposite amount
      if (type === 'debit') {
        line.credit_amount = 0
      } else {
        line.debit_amount = 0
      }
      
      this.updateForeignAmount(index)
    },
    
    onForeignAmountChange(index) {
      const line = this.lines[index]
      if (line.currency !== this.baseCurrency && line.foreign_amount && line.exchange_rate) {
        const baseAmount = line.foreign_amount * line.exchange_rate
        if (line.debit_amount > 0) {
          line.debit_amount = baseAmount
        } else if (line.credit_amount > 0) {
          line.credit_amount = baseAmount
        }
      }
    },
    
    onExchangeRateChange(index) {
      this.updateForeignAmount(index)
    },
    
    updateForeignAmount(index) {
      const line = this.lines[index]
      
      if (line.currency === this.baseCurrency) {
        line.foreign_amount = 0
        return
      }
      
      const amount = line.debit_amount || line.credit_amount || 0
      if (amount && line.exchange_rate) {
        line.foreign_amount = amount / line.exchange_rate
      }
    },
    
    async fetchExchangeRate(index) {
      const line = this.lines[index]
      
      if (line.currency === this.baseCurrency) {
        return
      }
      
      this.fetchingRates = { ...this.fetchingRates, [index]: true }
      
      try {
        const response = await axios.get('/accounting/currency-rates/current-rate', {
          params: {
            from_currency: line.currency,
            to_currency: this.baseCurrency,
            date: this.form.entry_date
          }
        })
        
        if (response.data.data && response.data.data.rate) {
          line.exchange_rate = response.data.data.rate
          this.updateForeignAmount(index)
        }
        
      } catch (error) {
        console.error('Error fetching exchange rate:', error)
        this.$toast.error('Failed to fetch exchange rate')
      } finally {
        this.fetchingRates = { ...this.fetchingRates, [index]: false }
      }
    },
    
    async fetchConverterRate() {
      if (this.converter.fromCurrency === this.converter.toCurrency) {
        this.converter.exchangeRate = 1
        this.convertAmount()
        return
      }
      
      this.fetchingConverterRate = true
      
      try {
        const response = await axios.get('/accounting/currency-rates/current-rate', {
          params: {
            from_currency: this.converter.fromCurrency,
            to_currency: this.converter.toCurrency,
            date: this.form.entry_date
          }
        })
        
        if (response.data.data && response.data.data.rate) {
          this.converter.exchangeRate = response.data.data.rate
          this.convertAmount()
        }
        
      } catch (error) {
        console.error('Error fetching converter rate:', error)
        this.$toast.error('Failed to fetch exchange rate')
      } finally {
        this.fetchingConverterRate = false
      }
    },
    
    convertAmount() {
      if (this.converter.amount && this.converter.exchangeRate) {
        this.converter.result = this.converter.amount * this.converter.exchangeRate
      }
    },
    
    onDateChange() {
      // Update exchange rates when date changes
      this.lines.forEach((line, index) => {
        if (line.currency !== this.baseCurrency) {
          this.fetchExchangeRate(index)
        }
      })
    },
    
    async saveEntry() {
      this.saving = true
      this.errors = {}
      this.lineErrors = {}
      
      try {
        const payload = {
          ...this.form,
          lines: this.lines.map(line => ({
            account_id: line.account_id,
            debit_amount: line.debit_amount || 0,
            credit_amount: line.credit_amount || 0,
            description: line.description || '',
            currency: line.currency || this.baseCurrency,
            foreign_amount: line.currency !== this.baseCurrency ? line.foreign_amount : null,
            exchange_rate: line.currency !== this.baseCurrency ? line.exchange_rate : null
          }))
        }
        
        let response
        if (this.isEdit) {
          response = await axios.put(`/accounting/journal-entries/${this.id}`, payload)
        } else {
          response = await axios.post('/accounting/journal-entries', payload)
        }
        
        this.$toast.success(`Journal entry ${this.isEdit ? 'updated' : 'created'} successfully`)
        this.$router.push(`/accounting/journal-entries/${response.data.data.journal_id}`)
        
      } catch (error) {
        if (error.response?.status === 422) {
          this.errors = error.response.data.errors || {}
          
          if (error.response.data.line_errors) {
            this.lineErrors = error.response.data.line_errors
          }
          
          this.$toast.error('Please fix validation errors')
        } else {
          this.$toast.error(`Failed to ${this.isEdit ? 'update' : 'create'} journal entry`)
        }
        console.error('Save error:', error)
      } finally {
        this.saving = false
      }
    },
    
    resetForm() {
      if (this.originalData) {
        this.form = JSON.parse(JSON.stringify(this.originalData.form))
        this.lines = JSON.parse(JSON.stringify(this.originalData.lines))
      }
      this.errors = {}
      this.lineErrors = {}
    },
    
    formatDate(date) {
      return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      })
    },
    
    formatCurrency(amount, currency = null) {
      return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: currency || this.baseCurrency,
        minimumFractionDigits: 2,
        maximumFractionDigits: 6
      }).format(amount || 0)
    }
  }
}
</script>

<style scoped>
.journal-entry-form-container {
  padding: 1rem;
  max-width: 1400px;
  margin: 0 auto;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 2rem;
  padding-bottom: 1rem;
  border-bottom: 2px solid var(--gray-200);
}

.header-left h1 {
  margin: 0;
  color: var(--gray-800);
  font-size: 1.75rem;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.page-subtitle {
  margin: 0.5rem 0 0 0;
  color: var(--gray-600);
  font-size: 0.95rem;
}

.header-actions {
  display: flex;
  gap: 0.75rem;
}

.form-layout {
  display: grid;
  grid-template-columns: 400px 1fr;
  gap: 2rem;
  align-items: start;
}

.section-card {
  background: white;
  border-radius: 0.75rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  margin-bottom: 1.5rem;
  overflow: hidden;
}

.section-header {
  padding: 1.25rem 1.5rem;
  background: var(--gray-50);
  border-bottom: 1px solid var(--gray-200);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.section-header h3 {
  margin: 0;
  color: var(--gray-700);
  font-size: 1.1rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.header-actions {
  display: flex;
  gap: 0.5rem;
}

.section-content {
  padding: 1.5rem;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
  margin-bottom: 1rem;
}

.form-group {
  margin-bottom: 1rem;
}

.form-label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
  color: var(--gray-700);
}

.form-label.required::after {
  content: ' *';
  color: var(--red-500);
}

.form-input,
.form-select,
.form-textarea {
  width: 100%;
  padding: 0.625rem;
  border: 1px solid var(--gray-300);
  border-radius: 0.375rem;
  font-size: 0.875rem;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.form-input:focus,
.form-select:focus,
.form-textarea:focus {
  outline: none;
  border-color: var(--blue-500);
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-input.error,
.form-select.error,
.form-textarea.error {
  border-color: var(--red-500);
}

.error-message {
  color: var(--red-500);
  font-size: 0.875rem;
  margin-top: 0.25rem;
  display: block;
}

.lines-table-container {
  overflow-x: auto;
  margin-bottom: 1rem;
}

.lines-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.875rem;
}

.lines-table th,
.lines-table td {
  padding: 0.5rem;
  border: 1px solid var(--gray-200);
  text-align: left;
}

.lines-table th {
  background: var(--gray-50);
  font-weight: 600;
  color: var(--gray-700);
}

.line-row.error {
  background-color: rgba(239, 68, 68, 0.05);
}

.amount-input-group {
  position: relative;
}

.amount-input {
  padding-right: 3rem;
}

.currency-symbol {
  position: absolute;
  right: 0.5rem;
  top: 50%;
  transform: translateY(-50%);
  color: var(--gray-500);
  font-size: 0.75rem;
  font-weight: 500;
}

.exchange-rate-group {
  display: flex;
  gap: 0.25rem;
}

.rate-input {
  flex: 1;
}

.rate-fetch-btn {
  padding: 0.25rem 0.5rem;
  min-width: 2rem;
}

.totals-section {
  background: var(--gray-50);
  border-radius: 0.5rem;
  padding: 1rem;
  margin-top: 1rem;
}

.totals-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1rem;
}

.total-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.total-item label {
  font-size: 0.875rem;
  color: var(--gray-600);
  font-weight: 500;
}

.total-amount {
  font-size: 1rem;
  font-weight: 600;
}

.total-amount.debit {
  color: var(--blue-600);
}

.total-amount.credit {
  color: var(--green-600);
}

.total-amount.balanced {
  color: var(--green-600);
}

.total-amount.unbalanced {
  color: var(--red-600);
}

.balance-status {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: 600;
}

.balance-status.balanced {
  color: var(--green-600);
}

.balance-status.unbalanced {
  color: var(--red-600);
}

.currency-summary-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}

.currency-summary-item {
  padding: 1rem;
  background: var(--gray-50);
  border-radius: 0.5rem;
  border: 1px solid var(--gray-200);
}

.currency-code {
  font-weight: 600;
  font-size: 1.1rem;
  color: var(--gray-800);
  margin-bottom: 0.5rem;
}

.amount-row {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.25rem;
  font-size: 0.875rem;
}

.amount-row.balance {
  font-weight: 600;
  padding-top: 0.25rem;
  border-top: 1px solid var(--gray-300);
}

.amount.positive {
  color: var(--green-600);
}

.amount.negative {
  color: var(--red-600);
}

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
}

.modal-content {
  background: white;
  border-radius: 0.75rem;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
  max-width: 600px;
  width: 90%;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-header {
  padding: 1.5rem;
  border-bottom: 1px solid var(--gray-200);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-header h3 {
  margin: 0;
  color: var(--gray-800);
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.close-btn {
  background: none;
  border: none;
  font-size: 1.25rem;
  color: var(--gray-500);
  cursor: pointer;
  padding: 0.25rem;
  border-radius: 0.25rem;
  transition: color 0.2s;
}

.close-btn:hover {
  color: var(--gray-700);
}

.modal-body {
  padding: 1.5rem;
}

.converter-form .form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
  margin-bottom: 1rem;
}

.rate-input-group {
  display: flex;
  gap: 0.5rem;
}

.rate-input-group .form-input {
  flex: 1;
}

.conversion-result {
  margin-top: 1.5rem;
  padding: 1rem;
  background: var(--blue-50);
  border-radius: 0.5rem;
  border: 1px solid var(--blue-200);
}

.result-display {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1rem;
  font-size: 1.1rem;
  font-weight: 600;
}

.original-amount {
  color: var(--blue-600);
}

.converted-amount {
  color: var(--green-600);
}

.line-errors {
  margin-top: 1rem;
  padding: 1rem;
  background: var(--red-50);
  border-radius: 0.5rem;
  border: 1px solid var(--red-200);
}

.line-errors h4 {
  margin: 0 0 0.5rem 0;
  color: var(--red-700);
}

.line-error {
  margin-bottom: 0.5rem;
}

.line-error ul {
  margin: 0.25rem 0 0 1rem;
  padding: 0;
}

.line-error li {
  color: var(--red-600);
  font-size: 0.875rem;
}

.loading-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(255, 255, 255, 0.8);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1001;
}

.loading-content {
  text-align: center;
  color: var(--gray-600);
}

.loading-content i {
  font-size: 2rem;
  margin-bottom: 1rem;
}

/* Button Styles */
.btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.625rem 1rem;
  border: none;
  border-radius: 0.375rem;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  text-decoration: none;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-primary {
  background: var(--blue-600);
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: var(--blue-700);
}

.btn-secondary {
  background: var(--gray-600);
  color: white;
}

.btn-secondary:hover:not(:disabled) {
  background: var(--gray-700);
}

.btn-success {
  background: var(--green-600);
  color: white;
}

.btn-success:hover:not(:disabled) {
  background: var(--green-700);
}

.btn-danger {
  background: var(--red-600);
  color: white;
}

.btn-danger:hover:not(:disabled) {
  background: var(--red-700);
}

.btn-sm {
  padding: 0.375rem 0.75rem;
  font-size: 0.8125rem;
}

.btn-xs {
  padding: 0.25rem 0.5rem;
  font-size: 0.75rem;
}

/* Responsive Design */
@media (max-width: 1200px) {
  .form-layout {
    grid-template-columns: 350px 1fr;
  }
}

@media (max-width: 992px) {
  .form-layout {
    grid-template-columns: 1fr;
  }
  
  .totals-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 768px) {
  .page-header {
    flex-direction: column;
    gap: 1rem;
  }
  
  .header-actions {
    width: 100%;
    justify-content: stretch;
  }
  
  .header-actions .btn {
    flex: 1;
  }
  
  .form-row {
    grid-template-columns: 1fr;
  }
  
  .totals-grid {
    grid-template-columns: 1fr;
  }
  
  .lines-table-container {
    font-size: 0.75rem;
  }
  
  .currency-summary-grid {
    grid-template-columns: 1fr;
  }
}
</style>