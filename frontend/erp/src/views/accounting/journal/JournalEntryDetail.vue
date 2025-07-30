<template>
  <div class="journal-entry-detail-container">
    <!-- Loading State -->
    <div v-if="loading" class="loading-overlay">
      <div class="loading-content">
        <i class="fas fa-spinner fa-spin"></i>
        <p>Loading journal entry...</p>
      </div>
    </div>

    <!-- Header -->
    <div v-else-if="journalEntry" class="page-header">
      <div class="header-left">
        <h1>
          <i class="fas fa-book"></i>
          Journal Entry #{{ journalEntry.journal_number }}
        </h1>
        <div class="header-meta">
          <span class="status-badge" :class="journalEntry.status.toLowerCase()">
            {{ journalEntry.status }}
          </span>
          <span class="entry-date">{{ formatDate(journalEntry.entry_date) }}</span>
          <span v-if="journalEntry.accounting_period" class="period-info">
            Period: {{ journalEntry.accounting_period.period_name }}
          </span>
        </div>
      </div>
      <div class="header-actions">
        <button @click="goBack" class="btn btn-secondary">
          <i class="fas fa-arrow-left"></i>
          Back
        </button>
        <router-link
          v-if="journalEntry.status !== 'Posted'"
          :to="`/accounting/journal-entries/${journalEntry.journal_id}/edit`"
          class="btn btn-primary"
        >
          <i class="fas fa-edit"></i>
          Edit
        </router-link>
        <button
          v-if="journalEntry.status === 'Draft'"
          @click="postEntry"
          class="btn btn-success"
          :disabled="posting"
        >
          <i v-if="posting" class="fas fa-spinner fa-spin"></i>
          <i v-else class="fas fa-check"></i>
          Post Entry
        </button>
        <div class="dropdown">
          <button @click="showActionsMenu = !showActionsMenu" class="btn btn-secondary dropdown-toggle">
            <i class="fas fa-ellipsis-v"></i>
            More Actions
          </button>
          <div v-if="showActionsMenu" class="dropdown-menu" @click.stop>
            <button @click="showCurrencyConverter = true" class="dropdown-item">
              <i class="fas fa-calculator"></i>
              Currency Converter
            </button>
            <button @click="convertToCurrency" class="dropdown-item">
              <i class="fas fa-exchange-alt"></i>
              Convert to Currency
            </button>
            <button @click="printEntry" class="dropdown-item">
              <i class="fas fa-print"></i>
              Print Entry
            </button>
            <button @click="exportEntry" class="dropdown-item">
              <i class="fas fa-download"></i>
              Export
            </button>
            <div class="dropdown-divider"></div>
            <button @click="duplicateEntry" class="dropdown-item">
              <i class="fas fa-copy"></i>
              Duplicate Entry
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Content -->
    <div v-if="journalEntry" class="content-layout">
      <!-- Left Column - Entry Information -->
      <div class="entry-info-section">
        <!-- Basic Information -->
        <div class="info-card">
          <div class="card-header">
            <h3>
              <i class="fas fa-info-circle"></i>
              Entry Information
            </h3>
          </div>
          <div class="card-content">
            <div class="info-grid">
              <div class="info-item">
                <label>Journal Number</label>
                <span class="value">{{ journalEntry.journal_number }}</span>
              </div>
              <div class="info-item">
                <label>Entry Date</label>
                <span class="value">{{ formatDate(journalEntry.entry_date) }}</span>
              </div>
              <div class="info-item">
                <label>Status</label>
                <span class="status-badge" :class="journalEntry.status.toLowerCase()">
                  {{ journalEntry.status }}
                </span>
              </div>
              <div class="info-item">
                <label>Period</label>
                <span class="value">{{ journalEntry.accounting_period?.period_name || '-' }}</span>
              </div>
              <div class="info-item" v-if="journalEntry.reference_type">
                <label>Reference</label>
                <span class="value">
                  {{ journalEntry.reference_type }}
                  <span v-if="journalEntry.reference_id">#{{ journalEntry.reference_id }}</span>
                </span>
              </div>
              <div class="info-item full-width" v-if="journalEntry.description">
                <label>Description</label>
                <span class="value">{{ journalEntry.description }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Currency Summary -->
        <div class="info-card" v-if="currencySummary.length > 0">
          <div class="card-header">
            <h3>
              <i class="fas fa-exchange-alt"></i>
              Currency Summary
            </h3>
          </div>
          <div class="card-content">
            <div class="currency-summary-grid">
              <div v-for="currency in currencySummary" :key="currency.currency" class="currency-summary-item">
                <div class="currency-header">
                  <span class="currency-code">{{ currency.currency }}</span>
                  <span v-if="currency.currency !== baseCurrency" class="currency-label">(Foreign)</span>
                  <span v-else class="currency-label">(Base)</span>
                </div>
                <div class="currency-details">
                  <div class="amount-row">
                    <span class="label">Total Debits:</span>
                    <span class="amount debit">{{ formatCurrency(currency.total_debits, currency.currency) }}</span>
                  </div>
                  <div class="amount-row">
                    <span class="label">Total Credits:</span>
                    <span class="amount credit">{{ formatCurrency(currency.total_credits, currency.currency) }}</span>
                  </div>
                  <div class="amount-row balance">
                    <span class="label">Net Amount:</span>
                    <span class="amount" :class="{ 'positive': currency.net_amount >= 0, 'negative': currency.net_amount < 0 }">
                      {{ formatCurrency(Math.abs(currency.net_amount), currency.currency) }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Exchange Rate Information -->
        <div class="info-card" v-if="exchangeRateInfo.length > 0">
          <div class="card-header">
            <h3>
              <i class="fas fa-calculator"></i>
              Exchange Rates
            </h3>
          </div>
          <div class="card-content">
            <div class="exchange-rates-list">
              <div v-for="rate in exchangeRateInfo" :key="`${rate.from_currency}-${rate.to_currency}`" class="rate-item">
                <div class="rate-header">
                  <span class="rate-pair">{{ rate.from_currency }} → {{ rate.to_currency }}</span>
                  <span class="rate-value">{{ formatExchangeRate(rate.rate) }}</span>
                </div>
                <div class="rate-details">
                  <span class="rate-date">As of {{ formatDate(journalEntry.entry_date) }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Right Column - Journal Lines -->
      <div class="journal-lines-section">
        <div class="lines-card">
          <div class="card-header">
            <h3>
              <i class="fas fa-list"></i>
              Journal Entry Lines
              <span class="lines-count">({{ journalEntry.journal_entry_lines?.length || 0 }} lines)</span>
            </h3>
            <div class="header-actions">
              <button @click="toggleCurrencyView" class="btn btn-sm btn-secondary">
                <i class="fas fa-eye"></i>
                {{ showForeignAmounts ? 'Show Base Only' : 'Show All Currencies' }}
              </button>
            </div>
          </div>
          
          <div class="card-content">
            <!-- Lines Table -->
            <div class="lines-table-container">
              <table class="lines-table">
                <thead>
                  <tr>
                    <th>Account</th>
                    <th v-if="showForeignAmounts">Currency</th>
                    <th>Debit Amount</th>
                    <th>Credit Amount</th>
                    <th v-if="showForeignAmounts">Foreign Amount</th>
                    <th v-if="showForeignAmounts">Exchange Rate</th>
                    <th>Description</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(line, index) in journalEntry.journal_entry_lines" :key="index" class="line-row">
                    <td>
                      <div class="account-cell">
                        <div class="account-code">{{ line.chart_of_account?.account_code }}</div>
                        <div class="account-name">{{ line.chart_of_account?.name }}</div>
                      </div>
                    </td>
                    <td v-if="showForeignAmounts">
                      <span class="currency-badge" :class="{ 'foreign': line.currency !== baseCurrency }">
                        {{ line.currency || baseCurrency }}
                      </span>
                    </td>
                    <td class="amount-cell">
                      <div class="amount-value debit" v-if="line.debit_amount > 0">
                        {{ formatCurrency(line.debit_amount) }}
                      </div>
                      <span v-else class="amount-placeholder">-</span>
                    </td>
                    <td class="amount-cell">
                      <div class="amount-value credit" v-if="line.credit_amount > 0">
                        {{ formatCurrency(line.credit_amount) }}
                      </div>
                      <span v-else class="amount-placeholder">-</span>
                    </td>
                    <td v-if="showForeignAmounts" class="amount-cell">
                      <div v-if="line.foreign_amount && line.currency !== baseCurrency" class="foreign-amount-value">
                        {{ formatCurrency(line.foreign_amount, line.currency) }}
                      </div>
                      <span v-else class="amount-placeholder">-</span>
                    </td>
                    <td v-if="showForeignAmounts" class="rate-cell">
                      <div v-if="line.exchange_rate && line.currency !== baseCurrency" class="rate-value">
                        {{ formatExchangeRate(line.exchange_rate) }}
                      </div>
                      <span v-else class="amount-placeholder">-</span>
                    </td>
                    <td>
                      <span class="description-text">{{ line.description || '-' }}</span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Totals Summary -->
            <div class="totals-summary">
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
                    Fetch
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

    <!-- Convert to Currency Modal -->
    <div v-if="showConvertModal" class="modal-overlay" @click="showConvertModal = false">
      <div class="modal-content convert-modal" @click.stop>
        <div class="modal-header">
          <h3>
            <i class="fas fa-exchange-alt"></i>
            Convert Entry to Currency
          </h3>
          <button @click="showConvertModal = false" class="close-btn">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <div class="convert-form">
            <div class="form-group">
              <label>Target Currency</label>
              <select v-model="convertForm.targetCurrency" class="form-select">
                <option value="">Select Currency</option>
                <option :value="baseCurrency">{{ baseCurrency }} (Base)</option>
                <option v-for="currency in availableCurrencies" :key="currency.code" :value="currency.code">
                  {{ currency.code }} - {{ currency.name }}
                </option>
              </select>
            </div>
            
            <div class="form-group">
              <label>Conversion Date</label>
              <input
                type="date"
                v-model="convertForm.conversionDate"
                class="form-input"
              />
            </div>
            
            <div class="convert-actions">
              <button @click="showConvertModal = false" class="btn btn-secondary">Cancel</button>
              <button @click="performConversion" class="btn btn-primary" :disabled="!convertForm.targetCurrency || converting">
                <i v-if="converting" class="fas fa-spinner fa-spin"></i>
                <i v-else class="fas fa-exchange-alt"></i>
                Convert
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="error-state">
      <div class="error-icon">
        <i class="fas fa-exclamation-triangle"></i>
      </div>
      <h3>Error Loading Journal Entry</h3>
      <p>{{ error }}</p>
      <button @click="loadJournalEntry" class="btn btn-primary">
        <i class="fas fa-retry"></i>
        Retry
      </button>
    </div>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'JournalEntryDetail',
  props: {
    id: {
      type: [String, Number],
      required: true
    }
  },
  data() {
    return {
      loading: false,
      error: null,
      journalEntry: null,
      
      // Master data
      availableCurrencies: [],
      baseCurrency: 'USD',
      
      // Actions
      posting: false,
      showActionsMenu: false,
      
      // View options
      showForeignAmounts: true,
      
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
      
      // Convert to currency
      showConvertModal: false,
      convertForm: {
        targetCurrency: '',
        conversionDate: new Date().toISOString().split('T')[0]
      },
      converting: false
    }
  },
  
  computed: {
    totalDebits() {
      if (!this.journalEntry?.journal_entry_lines) return 0
      return this.journalEntry.journal_entry_lines.reduce((sum, line) => sum + (line.debit_amount || 0), 0)
    },
    
    totalCredits() {
      if (!this.journalEntry?.journal_entry_lines) return 0
      return this.journalEntry.journal_entry_lines.reduce((sum, line) => sum + (line.credit_amount || 0), 0)
    },
    
    isBalanced() {
      return Math.abs(this.totalDebits - this.totalCredits) < 0.01
    },
    
    currencySummary() {
      if (!this.journalEntry?.journal_entry_lines) return []
      
      const summary = {}
      
      this.journalEntry.journal_entry_lines.forEach(line => {
        const currency = line.currency || this.baseCurrency
        if (!summary[currency]) {
          summary[currency] = {
            currency: currency,
            total_debits: 0,
            total_credits: 0,
            net_amount: 0
          }
        }
        
        if (line.currency === this.baseCurrency || !line.currency) {
          // Base currency amounts
          summary[currency].total_debits += (line.debit_amount || 0)
          summary[currency].total_credits += (line.credit_amount || 0)
        } else {
          // Foreign currency amounts
          summary[currency].total_debits += (line.debit_amount > 0 ? line.foreign_amount : 0) || 0
          summary[currency].total_credits += (line.credit_amount > 0 ? line.foreign_amount : 0) || 0
        }
        
        summary[currency].net_amount = summary[currency].total_debits - summary[currency].total_credits
      })
      
      return Object.values(summary)
    },
    
    exchangeRateInfo() {
      if (!this.journalEntry?.journal_entry_lines) return []
      
      const rates = new Map()
      
      this.journalEntry.journal_entry_lines.forEach(line => {
        if (line.currency && line.currency !== this.baseCurrency && line.exchange_rate) {
          const key = `${line.currency}-${this.baseCurrency}`
          if (!rates.has(key)) {
            rates.set(key, {
              from_currency: line.currency,
              to_currency: this.baseCurrency,
              rate: line.exchange_rate
            })
          }
        }
      })
      
      return Array.from(rates.values())
    }
  },
  
  async mounted() {
    await this.loadMasterData()
    await this.loadJournalEntry()
    
    // Close actions menu when clicking outside
    document.addEventListener('click', this.closeActionsMenu)
  },
  
  beforeUnmount() {
    document.removeEventListener('click', this.closeActionsMenu)
  },
  
  methods: {
    async loadMasterData() {
      try {
        const [currenciesRes, settingsRes] = await Promise.all([
          axios.get('/accounting/chart-of-accounts/currencies/available'),
          axios.get('/settings/group/currency')
        ])
        
        this.availableCurrencies = currenciesRes.data.data || currenciesRes.data
        
        if (settingsRes.data.data) {
          const baseCurrencySetting = settingsRes.data.data.find(s => s.key === 'base_currency')
          if (baseCurrencySetting) {
            this.baseCurrency = baseCurrencySetting.value
          }
        }
        
      } catch (error) {
        console.error('Error loading master data:', error)
      }
    },
    
    async loadJournalEntry() {
      this.loading = true
      this.error = null
      
      try {
        const response = await axios.get(`/accounting/journal-entries/${this.id}`)
        this.journalEntry = response.data.data
        
      } catch (error) {
        console.error('Error loading journal entry:', error)
        this.error = error.response?.data?.message || 'Failed to load journal entry'
      } finally {
        this.loading = false
      }
    },
    
    async postEntry() {
      this.posting = true
      
      try {
        await axios.post(`/accounting/journal-entries/${this.id}/post`)
        this.$toast.success('Journal entry posted successfully')
        await this.loadJournalEntry()
        
      } catch (error) {
        console.error('Error posting entry:', error)
        this.$toast.error('Failed to post journal entry')
      } finally {
        this.posting = false
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
        const response = await axios.get('/accounting/exchange-rates', {
          params: {
            from_currency: this.converter.fromCurrency,
            to_currency: this.converter.toCurrency,
            date: this.journalEntry.entry_date
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
    
    convertToCurrency() {
      this.showConvertModal = true
      this.showActionsMenu = false
    },
    
    async performConversion() {
      if (!this.convertForm.targetCurrency) return
      
      this.converting = true
      
      try {
        const response = await axios.post(`/accounting/journal-entries/${this.id}/convert-to-currency`, {
          target_currency: this.convertForm.targetCurrency,
          conversion_date: this.convertForm.conversionDate
        })
        
        this.$toast.success('Currency conversion completed')
        this.showConvertModal = false
        
        // You could show conversion results in a new modal or navigate to results page
        console.log('Conversion results:', response.data.data)
        
      } catch (error) {
        console.error('Error converting currency:', error)
        this.$toast.error('Failed to convert currency')
      } finally {
        this.converting = false
      }
    },
    
    toggleCurrencyView() {
      this.showForeignAmounts = !this.showForeignAmounts
    },
    
    closeActionsMenu() {
      this.showActionsMenu = false
    },
    
    goBack() {
      this.$router.go(-1)
    },
    
    printEntry() {
      window.print()
      this.showActionsMenu = false
    },
    
    exportEntry() {
      // Implement export functionality
      this.$toast.info('Export functionality coming soon')
      this.showActionsMenu = false
    },
    
    duplicateEntry() {
      this.$router.push(`/accounting/journal-entries/create?duplicate=${this.id}`)
      this.showActionsMenu = false
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
    },
    
    formatExchangeRate(rate) {
      return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 6
      }).format(rate || 0)
    }
  }
}
</script>

<style scoped>
.journal-entry-detail-container {
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

.header-meta {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-top: 0.75rem;
}

.status-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 0.375rem;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
}

.status-badge.draft {
  background: var(--yellow-100);
  color: var(--yellow-800);
}

.status-badge.posted {
  background: var(--green-100);
  color: var(--green-800);
}

.status-badge.cancelled {
  background: var(--red-100);
  color: var(--red-800);
}

.entry-date,
.period-info {
  color: var(--gray-600);
  font-size: 0.875rem;
}

.header-actions {
  display: flex;
  gap: 0.75rem;
  position: relative;
}

.dropdown {
  position: relative;
}

.dropdown-toggle {
  position: relative;
}

.dropdown-menu {
  position: absolute;
  top: 100%;
  right: 0;
  background: white;
  border: 1px solid var(--gray-200);
  border-radius: 0.5rem;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  min-width: 180px;
  z-index: 1000;
  padding: 0.5rem 0;
}

.dropdown-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  width: 100%;
  padding: 0.5rem 1rem;
  border: none;
  background: none;
  color: var(--gray-700);
  font-size: 0.875rem;
  cursor: pointer;
  transition: background-color 0.2s;
}

.dropdown-item:hover {
  background: var(--gray-50);
}

.dropdown-divider {
  height: 1px;
  background: var(--gray-200);
  margin: 0.5rem 0;
}

.content-layout {
  display: grid;
  grid-template-columns: 400px 1fr;
  gap: 2rem;
  align-items: start;
}

.info-card,
.lines-card {
  background: white;
  border-radius: 0.75rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  margin-bottom: 1.5rem;
  overflow: hidden;
}

.card-header {
  padding: 1.25rem 1.5rem;
  background: var(--gray-50);
  border-bottom: 1px solid var(--gray-200);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.card-header h3 {
  margin: 0;
  color: var(--gray-700);
  font-size: 1.1rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.lines-count {
  color: var(--gray-500);
  font-weight: normal;
  font-size: 0.9rem;
}

.card-content {
  padding: 1.5rem;
}

.info-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.info-item.full-width {
  grid-column: 1 / -1;
}

.info-item label {
  font-weight: 500;
  color: var(--gray-600);
  font-size: 0.875rem;
}

.info-item .value {
  color: var(--gray-800);
  font-weight: 500;
}

.currency-summary-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1rem;
}

.currency-summary-item {
  padding: 1rem;
  background: var(--gray-50);
  border-radius: 0.5rem;
  border: 1px solid var(--gray-200);
}

.currency-header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.75rem;
}

.currency-code {
  font-weight: 600;
  font-size: 1.1rem;
  color: var(--gray-800);
}

.currency-label {
  font-size: 0.75rem;
  color: var(--gray-500);
  background: var(--gray-200);
  padding: 0.125rem 0.375rem;
  border-radius: 0.25rem;
}

.currency-details {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.amount-row {
  display: flex;
  justify-content: space-between;
  font-size: 0.875rem;
}

.amount-row.balance {
  font-weight: 600;
  padding-top: 0.25rem;
  border-top: 1px solid var(--gray-300);
  margin-top: 0.25rem;
}

.amount.debit {
  color: var(--blue-600);
}

.amount.credit {
  color: var(--green-600);
}

.amount.positive {
  color: var(--green-600);
}

.amount.negative {
  color: var(--red-600);
}

.exchange-rates-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.rate-item {
  padding: 0.75rem;
  background: var(--gray-50);
  border-radius: 0.5rem;
  border: 1px solid var(--gray-200);
}

.rate-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.25rem;
}

.rate-pair {
  font-weight: 600;
  color: var(--gray-800);
}

.rate-value {
  font-weight: 600;
  color: var(--blue-600);
}

.rate-details {
  font-size: 0.75rem;
  color: var(--gray-500);
}

.lines-table-container {
  overflow-x: auto;
  margin-bottom: 1.5rem;
}

.lines-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.875rem;
}

.lines-table th,
.lines-table td {
  padding: 0.75rem;
  border-bottom: 1px solid var(--gray-200);
  text-align: left;
}

.lines-table th {
  background: var(--gray-50);
  font-weight: 600;
  color: var(--gray-700);
}

.account-cell {
  min-width: 200px;
}

.account-code {
  font-weight: 600;
  color: var(--gray-800);
  margin-bottom: 0.25rem;
}

.account-name {
  color: var(--gray-600);
  font-size: 0.8125rem;
}

.currency-badge {
  padding: 0.25rem 0.5rem;
  border-radius: 0.25rem;
  font-size: 0.75rem;
  font-weight: 600;
  background: var(--blue-100);
  color: var(--blue-800);
}

.currency-badge.foreign {
  background: var(--orange-100);
  color: var(--orange-800);
}

.amount-cell {
  text-align: right;
  min-width: 120px;
}

.amount-value {
  font-weight: 600;
}

.amount-value.debit {
  color: var(--blue-600);
}

.amount-value.credit {
  color: var(--green-600);
}

.foreign-amount-value {
  font-weight: 600;
  color: var(--orange-600);
}

.rate-cell {
  text-align: right;
}

.rate-value {
  font-weight: 500;
  color: var(--gray-700);
}

.amount-placeholder {
  color: var(--gray-400);
}

.description-text {
  color: var(--gray-700);
}

.totals-summary {
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

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
  margin-bottom: 1rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-group label {
  font-weight: 500;
  color: var(--gray-700);
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

.convert-actions {
  display: flex;
  gap: 0.75rem;
  margin-top: 1.5rem;
}

.convert-actions .btn {
  flex: 1;
}

.loading-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(255, 255, 255, 0.9);
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

.error-state {
  text-align: center;
  padding: 3rem 1rem;
  color: var(--gray-600);
}

.error-icon {
  font-size: 3rem;
  color: var(--red-500);
  margin-bottom: 1rem;
}

.error-state h3 {
  margin: 0 0 0.5rem 0;
  color: var(--gray-800);
}

/* Form Styles */
.form-input,
.form-select {
  width: 100%;
  padding: 0.625rem;
  border: 1px solid var(--gray-300);
  border-radius: 0.375rem;
  font-size: 0.875rem;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.form-input:focus,
.form-select:focus {
  outline: none;
  border-color: var(--blue-500);
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
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

.btn-sm {
  padding: 0.375rem 0.75rem;
  font-size: 0.8125rem;
}

/* Responsive Design */
@media (max-width: 1200px) {
  .content-layout {
    grid-template-columns: 350px 1fr;
  }
}

@media (max-width: 992px) {
  .content-layout {
    grid-template-columns: 1fr;
  }
  
  .totals-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .info-grid {
    grid-template-columns: 1fr;
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
  
  .header-meta {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.5rem;
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
  
  .dropdown-menu {
    right: auto;
    left: 0;
  }
}

/* Print Styles */
@media print {
  .header-actions,
  .btn,
  .modal-overlay {
    display: none !important;
  }
  
  .page-header {
    border-bottom: 2px solid #000;
    margin-bottom: 1rem;
  }
  
  .content-layout {
    grid-template-columns: 1fr;
  }
  
  .info-card,
  .lines-card {
    box-shadow: none;
    border: 1px solid #ccc;
  }
}
</style>