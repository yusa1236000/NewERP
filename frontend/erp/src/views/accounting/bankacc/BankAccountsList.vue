<template>
  <div class="bank-accounts-container">
    <!-- Header Section -->
    <div class="page-header">
      <div class="header-content">
        <div class="header-left">
          <h1 class="page-title">
            <i class="fas fa-university"></i>
            Bank Accounts
          </h1>
          <p class="page-subtitle">Manage your organization's bank accounts</p>
        </div>
        <div class="header-actions">
          <button @click="refreshData" class="btn btn-secondary" :disabled="loading">
            <i class="fas fa-sync-alt" :class="{ 'fa-spin': loading }"></i>
            Refresh
          </button>
          <router-link to="/accounting/bank-accounts/create" class="btn btn-primary">
            <i class="fas fa-plus"></i>
            Add New Account
          </router-link>
        </div>
      </div>
    </div>

    <!-- Filter Section -->
    <div class="filters-card">
      <div class="filters-content">
        <div class="search-wrapper">
          <i class="fas fa-search search-icon"></i>
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search by bank name, account name, or number..."
            class="search-input"
            @input="handleSearch"
          >
        </div>
        <div class="filter-actions">
          <select v-model="filterCurrency" @change="applyFilters" class="filter-select">
            <option value="">All Currencies</option>
            <option v-for="currency in availableCurrencies" :key="currency" :value="currency">
              {{ currency }}
            </option>
          </select>
          <select v-model="sortBy" @change="sortAccounts" class="filter-select">
            <option value="bank_name">Sort by Bank Name</option>
            <option value="account_name">Sort by Account Name</option>
            <option value="current_balance">Sort by Balance</option>
            <option value="currency_code">Sort by Currency</option>
            <option value="created_at">Sort by Date Created</option>
          </select>
          <select v-model="sortOrder" @change="sortAccounts" class="filter-select">
            <option value="asc">Ascending</option>
            <option value="desc">Descending</option>
          </select>
          <button @click="toggleCurrencyView" class="btn btn-outline" :title="showBaseCurrency ? 'Show Account Currency' : 'Show Base Currency'">
            <i class="fas fa-exchange-alt"></i>
            {{ showBaseCurrency ? 'Base Currency' : 'Account Currency' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Summary Cards -->
    <div class="summary-cards" v-if="!loading && bankAccounts.length > 0">
      <div class="summary-card">
        <div class="card-content">
          <div class="card-icon">
            <i class="fas fa-university"></i>
          </div>
          <div class="card-stats">
            <h3>{{ bankAccounts.length }}</h3>
            <p>Total Accounts</p>
          </div>
        </div>
      </div>
      
      <div class="summary-card">
        <div class="card-content">
          <div class="card-icon">
            <i class="fas fa-coins"></i>
          </div>
          <div class="card-stats">
            <h3>{{ uniqueCurrencies.length }}</h3>
            <p>Currencies</p>
          </div>
        </div>
      </div>
      
      <div class="summary-card" v-for="summary in currencySummary" :key="summary.currency">
        <div class="card-content">
          <div class="card-icon currency-icon" :style="{ background: getCurrencyColor(summary.currency) }">
            {{ summary.currency }}
          </div>
          <div class="card-stats">
            <h3 :class="getBalanceClass(summary.total)">
              {{ formatCurrency(summary.total, summary.currency) }}
            </h3>
            <p>Total in {{ summary.currency }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="loading-container">
      <div class="loading-content">
        <div class="loading-spinner"></div>
        <p>Loading bank accounts...</p>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else-if="bankAccounts.length === 0" class="empty-state">
      <div class="empty-content">
        <i class="fas fa-university"></i>
        <h3>No Bank Accounts Found</h3>
        <p>Get started by adding your first bank account to track balances and transactions.</p>
        <router-link to="/accounting/bank-accounts/create" class="btn btn-primary">
          <i class="fas fa-plus"></i>
          Add Your First Account
        </router-link>
      </div>
    </div>

    <!-- Accounts Grid -->
    <div class="accounts-grid" v-else-if="filteredAccounts.length > 0">
      <div
        v-for="account in filteredAccounts"
        :key="account.bank_id"
        class="account-card"
        @click="viewAccountDetail(account.bank_id)"
      >
        <div class="card-header">
          <div class="bank-info">
            <div class="bank-icon">
              <i class="fas fa-building"></i>
            </div>
            <div class="bank-details">
              <h3 class="bank-name">{{ account.bank_name }}</h3>
              <p class="account-number">{{ formatAccountNumber(account.account_number) }}</p>
              <p class="account-name">{{ account.account_name }}</p>
            </div>
          </div>
          <div class="currency-badge" :style="{ background: getCurrencyColor(account.currency_code) }">
            {{ account.currency_code }}
          </div>
        </div>
        
        <div class="card-body">
          <!-- Balance Information -->
          <div class="balance-section">
            <div class="primary-balance">
              <span class="balance-label">
                {{ showBaseCurrency ? 'Base Currency Balance' : 'Account Balance' }}
              </span>
              <span class="balance-amount" :class="getBalanceClass(showBaseCurrency ? account.base_currency_balance : account.current_balance)">
                {{ showBaseCurrency 
                    ? formatCurrency(account.base_currency_balance, account.base_currency)
                    : formatCurrency(account.current_balance, account.currency_code) }}
              </span>
            </div>
            
            <!-- Secondary Balance (Base Currency if showing account currency, or vice versa) -->
            <div v-if="account.currency_code !== account.base_currency" class="secondary-balance">
              <span class="balance-label secondary">
                {{ showBaseCurrency ? 'Account Currency' : 'Base Currency' }}
              </span>
              <span class="balance-amount secondary">
                {{ showBaseCurrency 
                    ? formatCurrency(account.current_balance, account.currency_code)
                    : formatCurrency(account.base_currency_balance, account.base_currency) }}
              </span>
            </div>
            
            <!-- Exchange Rate Information -->
            <div v-if="account.currency_code !== account.base_currency" class="exchange-rate-info">
              <span class="rate-label">
                Rate: 1 {{ account.currency_code }} = {{ account.exchange_rate }} {{ account.base_currency }}
              </span>
            </div>
          </div>
          
          <!-- GL Account Information -->
          <div class="gl-account-info" v-if="account.chart_of_account">
            <span class="gl-label">GL Account:</span>
            <span class="gl-account">
              {{ account.chart_of_account.account_code }} - {{ account.chart_of_account.name }}
            </span>
            <span v-if="account.chart_of_account.allow_multi_currency" class="multi-currency-indicator">
              <i class="fas fa-globe" title="Multi-currency account"></i>
            </span>
          </div>
        </div>
        
        <div class="card-footer">
          <div class="card-actions">
            <button
              @click.stop="viewAccountDetail(account.bank_id)"
              class="action-btn view-btn"
              title="View Details"
            >
              <i class="fas fa-eye"></i>
            </button>
            <button
              @click.stop="editAccount(account.bank_id)"
              class="action-btn edit-btn"
              title="Edit Account"
            >
              <i class="fas fa-edit"></i>
            </button>
            <button
              @click.stop="reconcileAccount(account.bank_id)"
              class="action-btn reconcile-btn"
              title="Bank Reconciliation"
            >
              <i class="fas fa-calculator"></i>
            </button>
            <button
              @click.stop="deleteAccount(account)"
              class="action-btn delete-btn"
              title="Delete Account"
              :disabled="account.bank_reconciliations && account.bank_reconciliations.length > 0"
            >
              <i class="fas fa-trash"></i>
            </button>
          </div>
          
          <div class="last-updated">
            <span v-if="account.updated_at">
              Updated: {{ formatDate(account.updated_at) }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- No Results -->
    <div v-else class="no-results">
      <div class="no-results-content">
        <i class="fas fa-search"></i>
        <h3>No Accounts Match Your Filters</h3>
        <p>Try adjusting your search criteria or filters to see more results.</p>
        <button @click="clearFilters" class="btn btn-secondary">
          <i class="fas fa-times"></i>
          Clear Filters
        </button>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div v-if="showDeleteModal" class="modal-overlay" @click="closeDeleteModal">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3>
            <i class="fas fa-exclamation-triangle"></i>
            Confirm Deletion
          </h3>
          <button @click="closeDeleteModal" class="modal-close">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <p>Are you sure you want to delete the following bank account?</p>
          <div class="account-to-delete" v-if="accountToDelete">
            <div class="delete-account-info">
              <strong>{{ accountToDelete.bank_name }}</strong><br>
              {{ accountToDelete.account_name }}<br>
              <span class="account-number">{{ formatAccountNumber(accountToDelete.account_number) }}</span>
            </div>
          </div>
          <div class="warning-message">
            <i class="fas fa-exclamation-triangle"></i>
            This action cannot be undone. All related data will be permanently deleted.
          </div>
        </div>
        <div class="modal-footer">
          <button @click="closeDeleteModal" class="btn btn-secondary">
            Cancel
          </button>
          <button @click="confirmDelete" class="btn btn-danger" :disabled="deleting">
            <i class="fas" :class="deleting ? 'fa-spinner fa-spin' : 'fa-trash'"></i>
            {{ deleting ? 'Deleting...' : 'Delete Account' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

export default {
  name: 'BankAccountsList',
  setup() {
    const router = useRouter()
    
    // Reactive data
    const bankAccounts = ref([])
    const loading = ref(false)
    const searchQuery = ref('')
    const filterCurrency = ref('')
    const sortBy = ref('bank_name')
    const sortOrder = ref('asc')
    const showBaseCurrency = ref(false)
    const showDeleteModal = ref(false)
    const accountToDelete = ref(null)
    const deleting = ref(false)
    
    // Computed properties
    const filteredAccounts = computed(() => {
      let accounts = [...bankAccounts.value]
      
      // Apply search filter
      if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase()
        accounts = accounts.filter(account =>
          account.bank_name.toLowerCase().includes(query) ||
          account.account_name.toLowerCase().includes(query) ||
          account.account_number.toLowerCase().includes(query)
        )
      }
      
      // Apply currency filter
      if (filterCurrency.value) {
        accounts = accounts.filter(account => account.currency_code === filterCurrency.value)
      }
      
      return accounts
    })
    
    const availableCurrencies = computed(() => {
      const currencies = new Set(bankAccounts.value.map(account => account.currency_code))
      return Array.from(currencies).sort()
    })
    
    const uniqueCurrencies = computed(() => {
      return availableCurrencies.value
    })
    
    const currencySummary = computed(() => {
      const summary = {}
      
      bankAccounts.value.forEach(account => {
        const currency = showBaseCurrency.value ? account.base_currency : account.currency_code
        const balance = showBaseCurrency.value ? account.base_currency_balance : account.current_balance
        
        if (!summary[currency]) {
          summary[currency] = { currency, total: 0 }
        }
        summary[currency].total += parseFloat(balance || 0)
      })
      
      return Object.values(summary).sort((a, b) => b.total - a.total)
    })
    
    // Methods
    const loadBankAccounts = async () => {
      loading.value = true
      try {
        const response = await axios.get('/accounting/bank-accounts')
        bankAccounts.value = response.data.data || []
        sortAccounts()
      } catch (error) {
        console.error('Error loading bank accounts:', error)
        showToast('Failed to load bank accounts', 'error')
      } finally {
        loading.value = false
      }
    }
    
    const refreshData = () => {
      loadBankAccounts()
    }
    
    const handleSearch = () => {
      // Debounce search if needed
    }
    
    const applyFilters = () => {
      // Filters are applied via computed property
    }
    
    const sortAccounts = () => {
      bankAccounts.value.sort((a, b) => {
        let aValue = a[sortBy.value]
        let bValue = b[sortBy.value]
        
        // Handle currency-specific sorting
        if (sortBy.value === 'current_balance') {
          aValue = showBaseCurrency.value ? a.base_currency_balance : a.current_balance
          bValue = showBaseCurrency.value ? b.base_currency_balance : b.current_balance
          aValue = parseFloat(aValue || 0)
          bValue = parseFloat(bValue || 0)
        }
        
        // Handle string comparison
        if (typeof aValue === 'string' && typeof bValue === 'string') {
          aValue = aValue.toLowerCase()
          bValue = bValue.toLowerCase()
        }
        
        if (sortOrder.value === 'asc') {
          return aValue > bValue ? 1 : -1
        } else {
          return aValue < bValue ? 1 : -1
        }
      })
    }
    
    const toggleCurrencyView = () => {
      showBaseCurrency.value = !showBaseCurrency.value
    }
    
    const clearFilters = () => {
      searchQuery.value = ''
      filterCurrency.value = ''
      sortBy.value = 'bank_name'
      sortOrder.value = 'asc'
    }
    
    const viewAccountDetail = (accountId) => {
      router.push(`/accounting/bank-accounts/${accountId}`)
    }
    
    const editAccount = (accountId) => {
      router.push(`/accounting/bank-accounts/${accountId}/edit`)
    }
    
    const reconcileAccount = (accountId) => {
      router.push(`/accounting/bank-reconciliation/${accountId}`)
    }
    
    const deleteAccount = (account) => {
      accountToDelete.value = account
      showDeleteModal.value = true
    }
    
    const closeDeleteModal = () => {
      showDeleteModal.value = false
      accountToDelete.value = null
    }
    
    const confirmDelete = async () => {
      if (!accountToDelete.value) return
      
      deleting.value = true
      try {
        await axios.delete(`/bank-accounts/${accountToDelete.value.bank_id}`)
        
        // Remove from local array
        const index = bankAccounts.value.findIndex(acc => acc.bank_id === accountToDelete.value.bank_id)
        if (index !== -1) {
          bankAccounts.value.splice(index, 1)
        }
        
        showToast('Bank account deleted successfully', 'success')
        closeDeleteModal()
      } catch (error) {
        console.error('Error deleting account:', error)
        showToast(
          error.response?.data?.message || 'Failed to delete bank account',
          'error'
        )
      } finally {
        deleting.value = false
      }
    }
    
    const formatAccountNumber = (accountNumber) => {
      if (!accountNumber) return ''
      if (accountNumber.length <= 4) return accountNumber
      return `****${accountNumber.slice(-4)}`
    }
    
    const formatCurrency = (amount, currencyCode) => {
      if (!amount && amount !== 0) return '0.00'
      
      const formatter = new Intl.NumberFormat('en-US', {
        style: 'decimal',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      })
      
      return `${currencyCode} ${formatter.format(amount)}`
    }
    
    const formatDate = (dateString) => {
      if (!dateString) return ''
      return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      })
    }
    
    const getBalanceClass = (balance) => {
      const amount = parseFloat(balance)
      if (amount > 0) return 'positive'
      if (amount < 0) return 'negative'
      return 'zero'
    }
    
    const getCurrencyColor = (currency) => {
      const colors = {
        'USD': '#10b981',
        'EUR': '#3b82f6',
        'IDR': '#f59e0b',
        'SGD': '#8b5cf6',
        'GBP': '#ef4444',
        'JPY': '#06b6d4',
        'AUD': '#84cc16',
        'CAD': '#f97316'
      }
      return colors[currency] || '#6b7280'
    }
    
    const showToast = (message, type = 'info') => {
      // Implement your toast notification here
      console.log(`${type.toUpperCase()}: ${message}`)
    }
    
    // Lifecycle
    onMounted(() => {
      loadBankAccounts()
    })
    
    // Watch for sort changes
    watch([sortBy, sortOrder], () => {
      sortAccounts()
    })
    
    return {
      bankAccounts,
      loading,
      searchQuery,
      filterCurrency,
      sortBy,
      sortOrder,
      showBaseCurrency,
      showDeleteModal,
      accountToDelete,
      deleting,
      filteredAccounts,
      availableCurrencies,
      uniqueCurrencies,
      currencySummary,
      refreshData,
      handleSearch,
      applyFilters,
      sortAccounts,
      toggleCurrencyView,
      clearFilters,
      viewAccountDetail,
      editAccount,
      reconcileAccount,
      deleteAccount,
      closeDeleteModal,
      confirmDelete,
      formatAccountNumber,
      formatCurrency,
      formatDate,
      getBalanceClass,
      getCurrencyColor
    }
  }
}
</script>

<style scoped>
/* Container */
.bank-accounts-container {
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

/* Filters */
.filters-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  margin-bottom: 2rem;
  padding: 1.5rem;
}

.filters-content {
  display: flex;
  gap: 1rem;
  align-items: center;
  flex-wrap: wrap;
}

.search-wrapper {
  position: relative;
  flex: 1;
  min-width: 300px;
}

.search-icon {
  position: absolute;
  left: 1rem;
  top: 50%;
  transform: translateY(-50%);
  color: var(--gray-400, #9ca3af);
}

.search-input {
  width: 100%;
  padding: 0.75rem 1rem 0.75rem 2.5rem;
  border: 2px solid var(--gray-200, #e5e7eb);
  border-radius: 8px;
  font-size: 1rem;
  transition: border-color 0.2s ease;
}

.search-input:focus {
  outline: none;
  border-color: var(--primary-color, #2563eb);
}

.filter-actions {
  display: flex;
  gap: 1rem;
  align-items: center;
}

.filter-select {
  padding: 0.75rem 1rem;
  border: 2px solid var(--gray-200, #e5e7eb);
  border-radius: 8px;
  font-size: 0.9rem;
  background: white;
  min-width: 150px;
}

/* Summary Cards */
.summary-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.summary-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.card-content {
  padding: 1.5rem;
  display: flex;
  align-items: center;
  gap: 1rem;
}

.card-icon {
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

.currency-icon {
  font-size: 1rem;
  font-weight: 600;
}

.card-stats h3 {
  font-size: 1.5rem;
  font-weight: 700;
  margin: 0 0 0.25rem 0;
  color: var(--gray-900, #111827);
}

.card-stats p {
  color: var(--gray-600, #4b5563);
  margin: 0;
  font-size: 0.9rem;
}

/* Loading State */
.loading-container {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 300px;
}

.loading-content {
  text-align: center;
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

/* Empty State */
.empty-state,
.no-results {
  text-align: center;
  padding: 4rem 2rem;
}

.empty-content,
.no-results-content {
  max-width: 400px;
  margin: 0 auto;
}

.empty-content i,
.no-results-content i {
  font-size: 4rem;
  color: var(--gray-300, #d1d5db);
  margin-bottom: 1rem;
}

.empty-content h3,
.no-results-content h3 {
  font-size: 1.5rem;
  font-weight: 600;
  color: var(--gray-900, #111827);
  margin: 0 0 1rem 0;
}

.empty-content p,
.no-results-content p {
  color: var(--gray-600, #4b5563);
  margin: 0 0 2rem 0;
  line-height: 1.6;
}

/* Accounts Grid */
.accounts-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
  gap: 1.5rem;
}

.account-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  overflow: hidden;
  transition: all 0.2s ease;
  cursor: pointer;
}

.account-card:hover {
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15);
  transform: translateY(-2px);
}

.card-header {
  padding: 1.5rem;
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  border-bottom: 1px solid var(--gray-200, #e5e7eb);
}

.bank-info {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  flex: 1;
}

.bank-icon {
  width: 50px;
  height: 50px;
  background: var(--gray-100, #f3f4f6);
  color: var(--gray-600, #4b5563);
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  flex-shrink: 0;
}

.bank-details {
  flex: 1;
}

.bank-name {
  font-size: 1.125rem;
  font-weight: 600;
  color: var(--gray-900, #111827);
  margin: 0 0 0.25rem 0;
}

.account-number {
  color: var(--gray-500, #6b7280);
  font-family: 'Courier New', monospace;
  font-size: 0.9rem;
  margin: 0 0 0.25rem 0;
}

.account-name {
  color: var(--gray-600, #4b5563);
  font-size: 0.95rem;
  margin: 0;
}

.currency-badge {
  padding: 0.5rem 0.75rem;
  border-radius: 6px;
  color: white;
  font-size: 0.8rem;
  font-weight: 600;
  letter-spacing: 0.5px;
}

.card-body {
  padding: 1.5rem;
}

.balance-section {
  margin-bottom: 1rem;
}

.primary-balance {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.5rem;
}

.balance-label {
  font-weight: 500;
  color: var(--gray-600, #4b5563);
  font-size: 0.9rem;
}

.balance-amount {
  font-weight: 700;
  font-family: 'Courier New', monospace;
  font-size: 1.1rem;
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

.secondary-balance {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.5rem;
}

.balance-label.secondary,
.balance-amount.secondary {
  font-size: 0.85rem;
  color: var(--gray-500, #6b7280);
  font-weight: 400;
}

.exchange-rate-info {
  padding: 0.5rem;
  background: var(--gray-50, #f9fafb);
  border-radius: 6px;
  margin-top: 0.5rem;
}

.rate-label {
  font-size: 0.8rem;
  color: var(--gray-600, #4b5563);
  font-family: 'Courier New', monospace;
}

.gl-account-info {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem;
  background: var(--gray-50, #f9fafb);
  border-radius: 6px;
  font-size: 0.85rem;
}

.gl-label {
  font-weight: 500;
  color: var(--gray-600, #4b5563);
}

.gl-account {
  color: var(--gray-700, #374151);
  flex: 1;
}

.multi-currency-indicator {
  color: var(--success-color, #059669);
}

.card-footer {
  padding: 1rem 1.5rem;
  background: var(--gray-50, #f9fafb);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.card-actions {
  display: flex;
  gap: 0.5rem;
}

.action-btn {
  width: 36px;
  height: 36px;
  border: none;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s ease;
  font-size: 0.9rem;
}

.view-btn {
  background: #e0f2fe;
  color: #0277bd;
}

.edit-btn {
  background: #fff3e0;
  color: #f57c00;
}

.reconcile-btn {
  background: #f3e8ff;
  color: #7c3aed;
}

.delete-btn {
  background: #ffebee;
  color: #d32f2f;
}

.action-btn:hover:not(:disabled) {
  transform: scale(1.1);
}

.action-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.last-updated {
  font-size: 0.8rem;
  color: var(--gray-500, #6b7280);
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

.btn-outline {
  background: transparent;
  color: var(--gray-700, #374151);
  border: 2px solid var(--gray-200, #e5e7eb);
}

.btn-outline:hover {
  background: var(--gray-50, #f9fafb);
  border-color: var(--gray-300, #d1d5db);
}

.btn-danger {
  background: var(--danger-color, #dc2626);
  color: white;
}

.btn-danger:hover:not(:disabled) {
  background: #b91c1c;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* Modal */
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
  border-radius: 12px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
  max-width: 500px;
  width: 90%;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-header {
  padding: 1.5rem;
  border-bottom: 1px solid var(--gray-200, #e5e7eb);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-header h3 {
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--gray-900, #111827);
  margin: 0;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.modal-close {
  width: 32px;
  height: 32px;
  border: none;
  background: var(--gray-100, #f3f4f6);
  color: var(--gray-500, #6b7280);
  border-radius: 8px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
}

.modal-close:hover {
  background: var(--gray-200, #e5e7eb);
  color: var(--gray-700, #374151);
}

.modal-body {
  padding: 1.5rem;
}

.account-to-delete {
  padding: 1rem;
  background: var(--gray-50, #f9fafb);
  border-radius: 8px;
  margin: 1rem 0;
}

.delete-account-info {
  font-size: 0.95rem;
  line-height: 1.5;
}

.warning-message {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 1rem;
  background: #fef3c7;
  color: #92400e;
  border-radius: 8px;
  margin-top: 1rem;
  font-size: 0.9rem;
}

.modal-footer {
  padding: 1.5rem;
  border-top: 1px solid var(--gray-200, #e5e7eb);
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
}

/* Responsive Design */
@media (max-width: 768px) {
  .bank-accounts-container {
    padding: 1rem;
  }
  
  .header-content {
    flex-direction: column;
    gap: 1rem;
  }
  
  .filters-content {
    flex-direction: column;
    align-items: stretch;
  }
  
  .search-wrapper {
    min-width: auto;
  }
  
  .filter-actions {
    flex-wrap: wrap;
  }
  
  .summary-cards {
    grid-template-columns: 1fr;
  }
  
  .accounts-grid {
    grid-template-columns: 1fr;
  }
  
  .card-header {
    flex-direction: column;
    gap: 1rem;
  }
  
  .bank-info {
    flex-direction: column;
    text-align: center;
  }
  
  .modal-content {
    width: 95%;
  }
  
  .modal-footer {
    flex-direction: column;
  }
}

@media (max-width: 480px) {
  .card-footer {
    flex-direction: column;
    gap: 1rem;
    align-items: stretch;
  }
  
  .card-actions {
    justify-content: center;
  }
}
</style>