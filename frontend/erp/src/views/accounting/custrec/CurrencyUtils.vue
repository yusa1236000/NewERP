<template>
  <div class="currency-utils">
    <!-- Currency Selector Component -->
    <div v-if="showSelector" class="currency-selector-component">
      <label v-if="label" class="currency-label">{{ label }}</label>
      <select 
        :value="selectedCurrency" 
        @change="$emit('update:selectedCurrency', $event.target.value)"
        class="currency-select"
        :disabled="disabled"
      >
        <option value="">{{ placeholder }}</option>
        <option 
          v-for="currency in availableCurrencies" 
          :key="currency.code" 
          :value="currency.code"
        >
          {{ currency.flag }} {{ currency.code }} - {{ currency.name }}
        </option>
      </select>
    </div>

    <!-- Currency Display Component -->
    <div v-if="showDisplay" class="currency-display-component">
      <div class="currency-amount" :class="amountClass">
        <span class="currency-symbol">{{ getCurrencySymbol(displayCurrency) }}</span>
        <span class="amount-value">{{ formatAmount(amount) }}</span>
        <span class="currency-code">{{ displayCurrency }}</span>
      </div>
      
      <div v-if="originalCurrency && originalCurrency !== displayCurrency" class="original-amount">
        <small>
          ({{ formatCurrency(originalAmount, originalCurrency) }} {{ originalCurrency }})
        </small>
      </div>
      
      <div v-if="showExchangeRate && exchangeRate" class="exchange-rate">
        <small>
          1 {{ originalCurrency }} = {{ exchangeRate }} {{ displayCurrency }}
        </small>
      </div>
    </div>

    <!-- Currency Comparison Component -->
    <div v-if="showComparison" class="currency-comparison-component">
      <div class="comparison-header">
        <h4>Currency Comparison</h4>
      </div>
      <div class="comparison-grid">
        <div 
          v-for="currency in comparisonCurrencies" 
          :key="currency" 
          class="comparison-item"
        >
          <div class="currency-info">
            <span class="currency-flag">{{ getCurrencyFlag(currency) }}</span>
            <span class="currency-code">{{ currency }}</span>
          </div>
          <div class="converted-amount">
            {{ formatCurrency(convertAmount(amount, baseCurrency, currency), currency) }}
          </div>
        </div>
      </div>
    </div>

    <!-- Currency Conversion Notice -->
    <div v-if="showConversionNotice && isConverted" class="conversion-notice">
      <div class="notice-content">
        <i class="fas fa-exchange-alt"></i>
        <span>
          Converted from {{ originalCurrency }} to {{ displayCurrency }}
          <span v-if="conversionDate">on {{ formatDate(conversionDate) }}</span>
        </span>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'CurrencyUtils',
  emits: ['update:selectedCurrency', 'currency-changed'],
  props: {
    // Selector props
    showSelector: {
      type: Boolean,
      default: false
    },
    selectedCurrency: {
      type: String,
      default: ''
    },
    label: {
      type: String,
      default: ''
    },
    placeholder: {
      type: String,
      default: 'Select Currency'
    },
    disabled: {
      type: Boolean,
      default: false
    },
    
    // Display props
    showDisplay: {
      type: Boolean,
      default: false
    },
    amount: {
      type: [Number, String],
      default: 0
    },
    displayCurrency: {
      type: String,
      default: 'USD'
    },
    originalAmount: {
      type: [Number, String],
      default: null
    },
    originalCurrency: {
      type: String,
      default: null
    },
    exchangeRate: {
      type: [Number, String],
      default: null
    },
    showExchangeRate: {
      type: Boolean,
      default: false
    },
    amountClass: {
      type: String,
      default: ''
    },
    
    // Comparison props
    showComparison: {
      type: Boolean,
      default: false
    },
    baseCurrency: {
      type: String,
      default: 'USD'
    },
    comparisonCurrencies: {
      type: Array,
      default: () => ['USD', 'EUR', 'GBP']
    },
    
    // Notice props
    showConversionNotice: {
      type: Boolean,
      default: false
    },
    conversionDate: {
      type: [String, Date],
      default: null
    }
  },
  
  data() {
    return {
      currencyData: {
        'USD': { name: 'US Dollar', flag: '🇺🇸', symbol: '$' },
        'EUR': { name: 'Euro', flag: '🇪🇺', symbol: '€' },
        'GBP': { name: 'British Pound', flag: '🇬🇧', symbol: '£' },
        'JPY': { name: 'Japanese Yen', flag: '🇯🇵', symbol: '¥' },
        'CAD': { name: 'Canadian Dollar', flag: '🇨🇦', symbol: 'C$' },
        'AUD': { name: 'Australian Dollar', flag: '🇦🇺', symbol: 'A$' },
        'CHF': { name: 'Swiss Franc', flag: '🇨🇭', symbol: 'CHF' },
        'CNY': { name: 'Chinese Yuan', flag: '🇨🇳', symbol: '¥' },
        'INR': { name: 'Indian Rupee', flag: '🇮🇳', symbol: '₹' },
        'SGD': { name: 'Singapore Dollar', flag: '🇸🇬', symbol: 'S$' },
        'HKD': { name: 'Hong Kong Dollar', flag: '🇭🇰', symbol: 'HK$' },
        'SEK': { name: 'Swedish Krona', flag: '🇸🇪', symbol: 'kr' },
        'NOK': { name: 'Norwegian Krone', flag: '🇳🇴', symbol: 'kr' },
        'DKK': { name: 'Danish Krone', flag: '🇩🇰', symbol: 'kr' },
        'PLN': { name: 'Polish Zloty', flag: '🇵🇱', symbol: 'zł' },
        'CZK': { name: 'Czech Koruna', flag: '🇨🇿', symbol: 'Kč' },
        'HUF': { name: 'Hungarian Forint', flag: '🇭🇺', symbol: 'Ft' },
        'THB': { name: 'Thai Baht', flag: '🇹🇭', symbol: '฿' },
        'MYR': { name: 'Malaysian Ringgit', flag: '🇲🇾', symbol: 'RM' },
        'KRW': { name: 'South Korean Won', flag: '🇰🇷', symbol: '₩' }
      },
      
      // Mock exchange rates - in real app, fetch from API
      exchangeRates: {
        'USD': 1.0,
        'EUR': 0.85,
        'GBP': 0.73,
        'JPY': 110.0,
        'CAD': 1.25,
        'AUD': 1.35,
        'CHF': 0.92,
        'CNY': 6.45
      }
    }
  },
  
  computed: {
    availableCurrencies() {
      return Object.entries(this.currencyData).map(([code, data]) => ({
        code,
        name: data.name,
        flag: data.flag,
        symbol: data.symbol
      }))
    },
    
    isConverted() {
      return this.originalCurrency && 
             this.displayCurrency && 
             this.originalCurrency !== this.displayCurrency
    }
  },
  
  methods: {
    formatCurrency(amount, currencyCode) {
      try {
        return new Intl.NumberFormat('en-US', {
          style: 'currency',
          currency: currencyCode || 'USD',
          minimumFractionDigits: 2,
          maximumFractionDigits: 2
        }).format(amount || 0)
      } catch (error) {
        // Fallback for unsupported currencies
        const symbol = this.getCurrencySymbol(currencyCode)
        return `${symbol}${this.formatAmount(amount)}`
      }
    },
    
    formatAmount(amount) {
      return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      }).format(amount || 0)
    },
    
    getCurrencySymbol(currencyCode) {
      return this.currencyData[currencyCode]?.symbol || currencyCode || '$'
    },
    
    getCurrencyFlag(currencyCode) {
      return this.currencyData[currencyCode]?.flag || '🏳️'
    },
    
    getCurrencyName(currencyCode) {
      return this.currencyData[currencyCode]?.name || currencyCode
    },
    
    convertAmount(amount, fromCurrency, toCurrency) {
      if (!amount || fromCurrency === toCurrency) return amount
      
      // Convert to USD first, then to target currency
      const usdAmount = parseFloat(amount) / (this.exchangeRates[fromCurrency] || 1)
      const convertedAmount = usdAmount * (this.exchangeRates[toCurrency] || 1)
      
      return convertedAmount
    },
    
    formatDate(date) {
      if (!date) return ''
      return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      })
    },
    
    // Helper method to get exchange rate between two currencies
    getExchangeRate(fromCurrency, toCurrency) {
      if (fromCurrency === toCurrency) return 1
      
      const fromRate = this.exchangeRates[fromCurrency] || 1
      const toRate = this.exchangeRates[toCurrency] || 1
      
      return toRate / fromRate
    },
    
    // Method to emit currency change event
    onCurrencyChange(newCurrency) {
      this.$emit('update:selectedCurrency', newCurrency)
      this.$emit('currency-changed', {
        currency: newCurrency,
        exchangeRate: this.getExchangeRate(this.baseCurrency, newCurrency)
      })
    }
  }
}
</script>

<style scoped>
.currency-utils {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

/* Currency Selector Styles */
.currency-selector-component {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.currency-label {
  font-weight: 500;
  color: var(--gray-700);
  font-size: 0.875rem;
}

.currency-select {
  padding: 0.5rem;
  border: 1px solid var(--gray-300);
  border-radius: 6px;
  background: white;
  font-size: 0.875rem;
  color: var(--gray-800);
  transition: border-color 0.2s;
}

.currency-select:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.currency-select:disabled {
  background: var(--gray-100);
  color: var(--gray-500);
  cursor: not-allowed;
}

/* Currency Display Styles */
.currency-display-component {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.currency-amount {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: 600;
  color: var(--gray-800);
}

.currency-symbol {
  font-size: 1.25rem;
  color: var(--primary-color);
}

.amount-value {
  font-size: 1.125rem;
}

.currency-code {
  font-size: 0.875rem;
  color: var(--gray-600);
  background: var(--gray-100);
  padding: 0.125rem 0.5rem;
  border-radius: 12px;
}

.original-amount {
  color: var(--gray-600);
  font-style: italic;
}

.exchange-rate {
  color: var(--gray-500);
  font-size: 0.75rem;
}

/* Amount Classes */
.currency-amount.positive {
  color: var(--success-color);
}

.currency-amount.negative {
  color: var(--danger-color);
}

.currency-amount.warning {
  color: var(--warning-color);
}

.currency-amount.large {
  font-size: 1.5rem;
}

.currency-amount.small {
  font-size: 0.875rem;
}

/* Currency Comparison Styles */
.currency-comparison-component {
  background: var(--gray-50);
  border-radius: 8px;
  padding: 1rem;
  border: 1px solid var(--gray-200);
}

.comparison-header h4 {
  margin-bottom: 1rem;
  color: var(--gray-800);
  font-size: 1rem;
}

.comparison-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 0.75rem;
}

.comparison-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.5rem;
  background: white;
  border-radius: 6px;
  border: 1px solid var(--gray-200);
}

.currency-info {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.currency-flag {
  font-size: 1.25rem;
}

.currency-code {
  font-weight: 500;
  color: var(--gray-700);
}

.converted-amount {
  font-weight: 600;
  color: var(--gray-800);
  font-size: 0.875rem;
}

/* Conversion Notice Styles */
.conversion-notice {
  background: #eff6ff;
  border: 1px solid #3b82f6;
  color: #1e40af;
  border-radius: 8px;
  padding: 0.75rem;
}

.notice-content {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
}

.notice-content i {
  font-size: 1rem;
}

/* Responsive Design */
@media (max-width: 768px) {
  .currency-amount {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.25rem;
  }
  
  .comparison-grid {
    grid-template-columns: 1fr;
  }
  
  .comparison-item {
    flex-direction: column;
    gap: 0.25rem;
    text-align: center;
  }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
  .currency-select {
    background: var(--gray-800);
    border-color: var(--gray-600);
    color: var(--gray-200);
  }
  
  .currency-amount {
    color: var(--gray-200);
  }
  
  .currency-code {
    background: var(--gray-700);
    color: var(--gray-300);
  }
  
  .comparison-item {
    background: var(--gray-800);
    border-color: var(--gray-600);
  }
}

/* Animation for currency changes */
.currency-amount {
  transition: all 0.3s ease;
}

.currency-amount.updating {
  opacity: 0.6;
  transform: scale(0.98);
}

/* Loading state */
.currency-display-component.loading {
  opacity: 0.6;
  pointer-events: none;
}

.currency-display-component.loading::after {
  content: '';
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 20px;
  height: 20px;
  border: 2px solid var(--gray-300);
  border-top-color: var(--primary-color);
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: translate(-50%, -50%) rotate(360deg); }
}
</style>