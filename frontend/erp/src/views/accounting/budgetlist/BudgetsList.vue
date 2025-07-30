<template>
  <AppLayout>
    <template #page-title>Budget Management</template>
    <template #page-subtitle>Manage organizational budgets and track financial performance with multi-currency support</template>
    
    <template #page-actions>
      <button @click="navigateToCreate" class="action-button primary">
        <i class="fas fa-plus"></i>
        Create Budget
      </button>
      <button @click="refreshData" class="action-button secondary" :disabled="loading">
        <i class="fas fa-sync-alt" :class="{ 'fa-spin': loading }"></i>
        Refresh
      </button>
      <button @click="exportData" class="action-button secondary" :disabled="loading">
        <i class="fas fa-download"></i>
        Export
      </button>
    </template>

    <div class="budget-list-container">
      <!-- Filters Section -->
      <div class="filters-section">
        <div class="filters-card">
          <h3><i class="fas fa-filter"></i> Filters & Currency Settings</h3>
          <div class="filters-grid">
            <div class="filter-group">
              <label>Account</label>
              <select v-model="filters.account_id" @change="applyFilters" class="filter-select">
                <option value="">All Accounts</option>
                <option v-for="account in accounts" :key="account.account_id" :value="account.account_id">
                  {{ account.account_code }} - {{ account.name }}
                </option>
              </select>
            </div>
            <div class="filter-group">
              <label>Period</label>
              <select v-model="filters.period_id" @change="applyFilters" class="filter-select">
                <option value="">All Periods</option>
                <option v-for="period in periods" :key="period.period_id" :value="period.period_id">
                  {{ period.period_name || period.name }}
                </option>
              </select>
            </div>
            <div class="filter-group">
              <label>Budget Currency</label>
              <select v-model="filters.currency" @change="applyFilters" class="filter-select">
                <option value="">All Currencies</option>
                <option v-for="currency in availableCurrencies" :key="currency" :value="currency">
                  {{ currency }}
                </option>
              </select>
            </div>
            <div class="filter-group">
              <label>Department</label>
              <select v-model="filters.department" @change="applyFilters" class="filter-select">
                <option value="">All Departments</option>
                <option v-for="department in departments" :key="department" :value="department">
                  {{ department }}
                </option>
              </select>
            </div>
            <div class="filter-group">
              <label>Display Currency</label>
              <select v-model="displayCurrency" @change="applyDisplayCurrency" class="filter-select">
                <option value="">Original Currency</option>
                <option v-for="currency in availableCurrencies" :key="currency" :value="currency">
                  {{ currency }}
                </option>
              </select>
            </div>
            <div class="filter-group">
              <label>Per Page</label>
              <select v-model="pagination.per_page" @change="applyFilters" class="filter-select">
                <option value="15">15</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
              </select>
            </div>
            <div class="filter-actions">
              <button @click="clearFilters" class="btn-clear">
                <i class="fas fa-times"></i>
                Clear
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Currency Summary Cards -->
      <div class="stats-section">
        <div class="stats-grid">
          <div class="stat-card revenue">
            <div class="stat-icon">
              <i class="fas fa-chart-line"></i>
            </div>
            <div class="stat-content">
              <h3>{{ formatCurrency(stats.totalBudgeted, displayCurrency || 'IDR') }}</h3>
              <p>Total Budgeted</p>
              <small>{{ stats.budgetCount }} budgets</small>
              <div v-if="displayCurrency" class="currency-note">
                <i class="fas fa-exchange-alt"></i>
                Converted to {{ displayCurrency }}
              </div>
            </div>
          </div>
          <div class="stat-card expenses">
            <div class="stat-icon">
              <i class="fas fa-money-bill-wave"></i>
            </div>
            <div class="stat-content">
              <h3>{{ formatCurrency(stats.totalActual, displayCurrency || 'IDR') }}</h3>
              <p>Total Actual</p>
              <small>{{ stats.actualCount }} recorded</small>
              <div v-if="displayCurrency" class="currency-note">
                <i class="fas fa-exchange-alt"></i>
                Converted to {{ displayCurrency }}
              </div>
            </div>
          </div>
          <div class="stat-card variance" :class="stats.totalVariance >= 0 ? 'positive' : 'negative'">
            <div class="stat-icon">
              <i class="fas fa-chart-bar"></i>
            </div>
            <div class="stat-content">
              <h3>{{ formatCurrency(stats.totalVariance, displayCurrency || 'IDR') }}</h3>
              <p>Total Variance</p>
              <small>{{ formatPercentage(stats.variancePercentage) }}% difference</small>
              <div v-if="displayCurrency" class="currency-note">
                <i class="fas fa-exchange-alt"></i>
                Converted to {{ displayCurrency }}
              </div>
            </div>
          </div>
          <div class="stat-card currencies">
            <div class="stat-icon">
              <i class="fas fa-globe"></i>
            </div>
            <div class="stat-content">
              <h3>{{ stats.currencyCount }}</h3>
              <p>Active Currencies</p>
              <small>{{ availableCurrencies.join(', ') }}</small>
            </div>
          </div>
        </div>
      </div>

      <!-- Budgets Table -->
      <div class="table-section">
        <div class="table-card">
          <div class="table-header">
            <h3><i class="fas fa-list"></i> Budget Records</h3>
            <div class="table-actions">
              <div class="currency-toggle">
                <label class="toggle-label">
                  <input type="checkbox" v-model="showCurrencyDetails" @change="toggleCurrencyDetails">
                  <span class="toggle-slider"></span>
                  Show Currency Details
                </label>
              </div>
              <button @click="exportData" class="btn-export">
                <i class="fas fa-download"></i>
                Export
              </button>
            </div>
          </div>
          
          <div class="table-container">
            <div v-if="loading" class="loading-state">
              <div class="loading-spinner"></div>
              <p>Loading budgets...</p>
            </div>
            
            <div v-else-if="budgets.length === 0" class="empty-state">
              <i class="fas fa-inbox"></i>
              <h3>No budgets found</h3>
              <p>Start by creating your first budget or adjust your filters.</p>
              <button @click="navigateToCreate" class="btn-create">
                <i class="fas fa-plus"></i>
                Create First Budget
              </button>
            </div>
            
            <table v-else class="budget-table">
              <thead>
                <tr>
                  <th class="sortable" @click="sort('chart_of_account.account_code')">
                    Account
                    <i class="fas fa-sort"></i>
                  </th>
                  <th class="sortable" @click="sort('accounting_period.period_name')">
                    Period
                    <i class="fas fa-sort"></i>
                  </th>
                  <th class="text-right sortable" @click="sort('budgeted_amount')">
                    Budgeted Amount
                    <i class="fas fa-sort"></i>
                  </th>
                  <th class="text-right">
                    Actual Amount
                  </th>
                  <th class="text-right">
                    Variance
                  </th>
                  <th v-if="showCurrencyDetails" class="text-center">
                    Currency Info
                  </th>
                  <th class="text-center">
                    Department
                  </th>
                  <th class="text-center">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="budget in budgets" :key="budget.budget_id" class="budget-row">
                  <td class="account-info">
                    <strong>{{ budget.chart_of_account?.account_code }}</strong>
                    <small>{{ budget.chart_of_account?.name }}</small>
                  </td>
                  <td>
                    <span class="period-badge">
                      {{ budget.accounting_period?.period_name }}
                    </span>
                    <small class="period-dates">
                      {{ formatDateRange(budget.accounting_period?.start_date, budget.accounting_period?.end_date) }}
                    </small>
                  </td>
                  <td class="text-right">
                    <div class="amount-display">
                      <span class="amount-badge budgeted">
                        {{ formatCurrencyAmount(budget) }}
                      </span>
                      <div v-if="showCurrencyDetails && budget.converted_amount" class="converted-amount">
                        <small class="text-muted">
                          Original: {{ formatCurrency(budget.budgeted_amount, budget.currency) }}
                        </small>
                      </div>
                    </div>
                  </td>
                  <td class="text-right">
                    <span v-if="budget.actual_amount !== null" class="amount-badge actual">
                      {{ formatCurrency(budget.actual_amount, budget.currency) }}
                    </span>
                    <span v-else class="no-data">-</span>
                  </td>
                  <td class="text-right">
                    <span v-if="budget.variance !== null" 
                          class="variance-badge" 
                          :class="budget.variance >= 0 ? 'positive' : 'negative'">
                      {{ formatCurrency(budget.variance, budget.currency) }}
                      <small>({{ formatPercentage(budget.variance, budget.budgeted_amount) }}%)</small>
                    </span>
                    <span v-else class="no-data">-</span>
                  </td>
                  <td v-if="showCurrencyDetails" class="text-center">
                    <div class="currency-info">
                      <div class="currency-badge" :class="budget.currency?.toLowerCase()">
                        {{ budget.currency }}
                      </div>
                      <div v-if="budget.exchange_rate" class="exchange-rate">
                        <small>Rate: {{ budget.exchange_rate?.toFixed(4) }}</small>
                      </div>
                      <div v-if="budget.base_currency_amount" class="base-amount">
                        <small>Base: {{ formatCurrency(budget.base_currency_amount, 'USD') }}</small>
                      </div>
                    </div>
                  </td>
                  <td class="text-center">
                    <span v-if="budget.department" class="department-badge">
                      {{ budget.department }}
                    </span>
                    <span v-else class="no-data">-</span>
                  </td>
                  <td class="actions text-center">
                    <div class="action-buttons">
                      <button @click="viewDetail(budget.budget_id)" class="btn-action view" title="View Details">
                        <i class="fas fa-eye"></i>
                      </button>
                      <button @click="editBudget(budget.budget_id)" class="btn-action edit" title="Edit">
                        <i class="fas fa-edit"></i>
                      </button>
                      <button @click="convertCurrency(budget)" class="btn-action convert" title="Convert Currency">
                        <i class="fas fa-exchange-alt"></i>
                      </button>
                      <button @click="confirmDelete(budget)" class="btn-action delete" title="Delete">
                        <i class="fas fa-trash"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          
          <!-- Pagination -->
          <div v-if="pagination.last_page > 1" class="pagination-section">
            <div class="pagination-info">
              Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} results
            </div>
            <div class="pagination-controls">
              <button @click="changePage(pagination.current_page - 1)" 
                      :disabled="pagination.current_page === 1" 
                      class="page-btn">
                <i class="fas fa-chevron-left"></i>
              </button>
              
              <button v-for="page in visiblePages" 
                      :key="page" 
                      @click="changePage(page)"
                      :class="['page-btn', { active: page === pagination.current_page }]">
                {{ page }}
              </button>
              
              <button @click="changePage(pagination.current_page + 1)" 
                      :disabled="pagination.current_page === pagination.last_page" 
                      class="page-btn">
                <i class="fas fa-chevron-right"></i>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div v-if="showDeleteModal" class="modal-overlay" @click="closeDeleteModal">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3><i class="fas fa-exclamation-triangle"></i> Confirm Delete</h3>
          <button @click="closeDeleteModal" class="close-btn">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <p>Are you sure you want to delete this budget?</p>
          <div class="budget-info" v-if="budgetToDelete">
            <strong>{{ budgetToDelete.chart_of_account?.name }}</strong><br>
            <small>{{ budgetToDelete.accounting_period?.period_name }} - {{ formatCurrency(budgetToDelete.budgeted_amount, budgetToDelete.currency) }}</small>
          </div>
        </div>
        <div class="modal-footer">
          <button @click="closeDeleteModal" class="btn-cancel">Cancel</button>
          <button @click="deleteBudget" class="btn-delete" :disabled="deleting">
            <i class="fas fa-trash" :class="{ 'fa-spin': deleting }"></i>
            {{ deleting ? 'Deleting...' : 'Delete' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Currency Conversion Modal -->
    <div v-if="showConversionModal" class="modal-overlay" @click="closeConversionModal">
      <div class="modal-content conversion-modal" @click.stop>
        <div class="modal-header">
          <h3><i class="fas fa-exchange-alt"></i> Convert Currency</h3>
          <button @click="closeConversionModal" class="close-btn">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <div class="conversion-form">
            <div class="form-group">
              <label>From Currency</label>
              <input type="text" :value="budgetToConvert?.currency" readonly class="form-input">
            </div>
            <div class="form-group">
              <label>To Currency</label>
              <select v-model="conversionCurrency" class="form-select">
                <option value="">Select currency</option>
                <option v-for="currency in availableCurrencies" :key="currency" :value="currency">
                  {{ currency }}
                </option>
              </select>
            </div>
            <div class="form-group">
              <label>Conversion Date</label>
              <input type="date" v-model="conversionDate" class="form-input">
            </div>
            <div v-if="conversionPreview" class="conversion-preview">
              <h4>Preview</h4>
              <div class="preview-item">
                <span>Original:</span>
                <span>{{ formatCurrency(budgetToConvert?.budgeted_amount, budgetToConvert?.currency) }}</span>
              </div>
              <div class="preview-item">
                <span>Exchange Rate:</span>
                <span>{{ conversionPreview.rate }}</span>
              </div>
              <div class="preview-item">
                <span>Converted:</span>
                <span>{{ formatCurrency(conversionPreview.amount, conversionCurrency) }}</span>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button @click="closeConversionModal" class="btn-cancel">Cancel</button>
          <button @click="previewConversion" class="btn-preview" :disabled="!conversionCurrency">
            Preview
          </button>
          <button @click="applyConversion" class="btn-confirm" :disabled="!conversionPreview">
            Convert
          </button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script>
import { ref, reactive, onMounted, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

export default {
  name: 'BudgetsList',
  setup() {
    const router = useRouter()
    const loading = ref(false)
    const deleting = ref(false)
    const budgets = ref([])
    const accounts = ref([])
    const periods = ref([])
    const departments = ref([])
    const availableCurrencies = ref(['USD', 'IDR', 'EUR', 'SGD'])
    const showDeleteModal = ref(false)
    const showConversionModal = ref(false)
    const budgetToDelete = ref(null)
    const budgetToConvert = ref(null)
    const showCurrencyDetails = ref(false)
    const displayCurrency = ref('')
    const conversionCurrency = ref('')
    const conversionDate = ref('')
    const conversionPreview = ref(null)
    
    const filters = reactive({
      account_id: '',
      period_id: '',
      currency: '',
      department: ''
    })
    
    const pagination = reactive({
      current_page: 1,
      last_page: 1,
      per_page: 15,
      total: 0,
      from: 0,
      to: 0
    })
    
    const sortField = ref('')
    const sortDirection = ref('asc')
    
    const stats = computed(() => {
      const totalBudgeted = budgets.value.reduce((sum, budget) => {
        const amount = budget.converted_amount || budget.budgeted_amount || 0
        return sum + parseFloat(amount)
      }, 0)
      
      const totalActual = budgets.value.reduce((sum, budget) => {
        const amount = budget.actual_amount || 0
        return sum + parseFloat(amount)
      }, 0)
      
      const totalVariance = totalBudgeted - totalActual
      const budgetCount = budgets.value.length
      const actualCount = budgets.value.filter(b => b.actual_amount !== null).length
      const variancePercentage = totalBudgeted > 0 ? ((totalVariance / totalBudgeted) * 100) : 0
      const currencyCount = new Set(budgets.value.map(b => b.currency)).size
      
      return {
        totalBudgeted,
        totalActual,
        totalVariance,
        budgetCount,
        actualCount,
        variancePercentage,
        currencyCount
      }
    })
    
    const visiblePages = computed(() => {
      const pages = []
      const current = pagination.current_page
      const last = pagination.last_page
      const delta = 2
      
      for (let i = Math.max(1, current - delta); i <= Math.min(last, current + delta); i++) {
        pages.push(i)
      }
      
      return pages
    })

    // Methods
    const fetchBudgets = async () => {
      try {
        loading.value = true
        const params = {
          page: pagination.current_page,
          per_page: pagination.per_page,
          ...filters
        }

        if (displayCurrency.value) {
          params.display_currency = displayCurrency.value
          params.conversion_date = new Date().toISOString().split('T')[0]
        }

        const response = await axios.get('/accounting/budgets', { params })
        
        budgets.value = response.data.data
        Object.assign(pagination, {
          current_page: response.data.current_page,
          last_page: response.data.last_page,
          per_page: response.data.per_page,
          total: response.data.total,
          from: response.data.from,
          to: response.data.to
        })
      } catch (error) {
        console.error('Error fetching budgets:', error)
      } finally {
        loading.value = false
      }
    }

    const fetchDropdownData = async () => {
      try {
        const [accountsRes, periodsRes, currenciesRes] = await Promise.all([
          axios.get('/accounting/chart-of-accounts'),
          axios.get('/accounting/accounting-periods'),
          axios.get('/accounting/budgets/available-currencies')
        ])
        
        accounts.value = accountsRes.data.data || accountsRes.data
        periods.value = periodsRes.data.data || periodsRes.data
        availableCurrencies.value = currenciesRes.data.data || ['USD', 'IDR', 'EUR', 'SGD']
        
        // Extract unique departments from budgets
        const allBudgets = await axios.get('/accounting/budgets?per_page=1000')
        const uniqueDepartments = [...new Set(
          allBudgets.data.data
            .map(b => b.department)
            .filter(d => d && d.trim() !== '')
        )]
        departments.value = uniqueDepartments
        
      } catch (error) {
        console.error('Error fetching dropdown data:', error)
      }
    }

    const applyFilters = () => {
      pagination.current_page = 1
      fetchBudgets()
    }

    const applyDisplayCurrency = () => {
      fetchBudgets()
    }

    const clearFilters = () => {
      Object.keys(filters).forEach(key => {
        filters[key] = ''
      })
      displayCurrency.value = ''
      pagination.current_page = 1
      fetchBudgets()
    }

    const changePage = (page) => {
      pagination.current_page = page
      fetchBudgets()
    }

    const sort = (field) => {
      if (sortField.value === field) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc'
      } else {
        sortField.value = field
        sortDirection.value = 'asc'
      }
      fetchBudgets()
    }

    const toggleCurrencyDetails = () => {
      // Toggle currency details display
    }

    const navigateToCreate = () => {
      router.push('/accounting/budgets/create')
    }

    const editBudget = (id) => {
      router.push(`/accounting/budgets/${id}/edit`)
    }

    const viewDetail = (id) => {
      router.push(`/accounting/budgets/${id}`)
    }

    const confirmDelete = (budget) => {
      budgetToDelete.value = budget
      showDeleteModal.value = true
    }

    const closeDeleteModal = () => {
      showDeleteModal.value = false
      budgetToDelete.value = null
    }

    const deleteBudget = async () => {
      try {
        deleting.value = true
        await axios.delete(`/accounting/budgets/${budgetToDelete.value.budget_id}`)
        await fetchBudgets()
        closeDeleteModal()
      } catch (error) {
        console.error('Error deleting budget:', error)
      } finally {
        deleting.value = false
      }
    }

    const convertCurrency = (budget) => {
      budgetToConvert.value = budget
      conversionCurrency.value = ''
      conversionDate.value = new Date().toISOString().split('T')[0]
      conversionPreview.value = null
      showConversionModal.value = true
    }

    const closeConversionModal = () => {
      showConversionModal.value = false
      budgetToConvert.value = null
      conversionCurrency.value = ''
      conversionPreview.value = null
    }

    const previewConversion = async () => {
      try {
        const response = await axios.get(`/accounting/exchange-rates/${budgetToConvert.value.currency}/${conversionCurrency.value}`, {
          params: { date: conversionDate.value }
        })
        
        const rate = response.data.rate
        const amount = budgetToConvert.value.budgeted_amount * rate
        
        conversionPreview.value = {
          rate: rate,
          amount: amount
        }
      } catch (error) {
        console.error('Error previewing conversion:', error)
      }
    }

    const applyConversion = async () => {
      try {
        await axios.patch(`/accounting/budgets/${budgetToConvert.value.budget_id}`, {
          currency: conversionCurrency.value,
          budgeted_amount: conversionPreview.value.amount
        })
        
        await fetchBudgets()
        closeConversionModal()
      } catch (error) {
        console.error('Error converting currency:', error)
      }
    }

    const refreshData = async () => {
      await fetchBudgets()
      await fetchDropdownData()
    }

    const exportData = async () => {
      try {
        const params = { ...filters }
        if (displayCurrency.value) {
          params.display_currency = displayCurrency.value
        }
        
        const response = await axios.get('/accounting/budgets/export', {
          params,
          responseType: 'blob'
        })
        
        const url = window.URL.createObjectURL(new Blob([response.data]))
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', `budgets_${new Date().toISOString().split('T')[0]}.xlsx`)
        document.body.appendChild(link)
        link.click()
        link.remove()
      } catch (error) {
        console.error('Error exporting data:', error)
      }
    }

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

    const formatCurrencyAmount = (budget) => {
      if (budget.converted_amount && displayCurrency.value) {
        return formatCurrency(budget.converted_amount, displayCurrency.value)
      }
      return formatCurrency(budget.budgeted_amount, budget.currency)
    }

    const formatPercentage = (variance, total) => {
      if (!total || total === 0) return '0.00'
      return ((variance / total) * 100).toFixed(2)
    }

    const formatDateRange = (startDate, endDate) => {
      if (!startDate || !endDate) return ''
      const start = new Date(startDate).toLocaleDateString('id-ID', { month: 'short', day: 'numeric' })
      const end = new Date(endDate).toLocaleDateString('id-ID', { month: 'short', day: 'numeric', year: 'numeric' })
      return `${start} - ${end}`
    }

    // Watchers
    watch(() => filters, () => {
      pagination.current_page = 1
    }, { deep: true })

    onMounted(async () => {
      await fetchDropdownData()
      await fetchBudgets()
    })

    return {
      loading,
      deleting,
      budgets,
      accounts,
      periods,
      departments,
      availableCurrencies,
      filters,
      pagination,
      sortField,
      sortDirection,
      stats,
      visiblePages,
      showDeleteModal,
      showConversionModal,
      budgetToDelete,
      budgetToConvert,
      showCurrencyDetails,
      displayCurrency,
      conversionCurrency,
      conversionDate,
      conversionPreview,
      fetchBudgets,
      applyFilters,
      applyDisplayCurrency,
      clearFilters,
      changePage,
      sort,
      toggleCurrencyDetails,
      navigateToCreate,
      editBudget,
      viewDetail,
      confirmDelete,
      closeDeleteModal,
      deleteBudget,
      convertCurrency,
      closeConversionModal,
      previewConversion,
      applyConversion,
      refreshData,
      exportData,
      formatCurrency,
      formatCurrencyAmount,
      formatPercentage,
      formatDateRange
    }
  }
}
</script>

<style scoped>
.budget-list-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0;
}

/* Filters Section */
.filters-section {
  margin-bottom: 2rem;
}

.filters-card {
  background: white;
  border-radius: 16px;
  padding: 1.5rem;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  border: 1px solid #e2e8f0;
}

.filters-card h3 {
  color: #1e293b;
  margin-bottom: 1rem;
  font-size: 1.1rem;
}

.filters-card h3 i {
  color: #6366f1;
  margin-right: 0.5rem;
}

.filters-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  align-items: end;
}

.filter-group label {
  display: block;
  font-weight: 500;
  color: #374151;
  margin-bottom: 0.5rem;
  font-size: 0.9rem;
}

.filter-select {
  width: 100%;
  padding: 0.75rem;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  font-size: 0.9rem;
  background: white;
  transition: all 0.3s ease;
}

.filter-select:focus {
  outline: none;
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.filter-actions {
  display: flex;
  gap: 0.5rem;
}

.btn-clear {
  padding: 0.75rem 1rem;
  background: #f1f5f9;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  color: #64748b;
  cursor: pointer;
  transition: all 0.3s ease;
  font-size: 0.9rem;
}

.btn-clear:hover {
  background: #e2e8f0;
  color: #475569;
}

/* Stats Section */
.stats-section {
  margin-bottom: 2rem;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 1.5rem;
}

.stat-card {
  background: white;
  border-radius: 16px;
  padding: 1.5rem;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  border: 1px solid #e2e8f0;
  display: flex;
  align-items: center;
  gap: 1rem;
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

.stat-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
}

.stat-card.revenue::before {
  background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
}

.stat-card.expenses::before {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.stat-card.variance.positive::before {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.stat-card.variance.negative::before {
  background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
}

.stat-card.currencies::before {
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

.stat-icon {
  width: 60px;
  height: 60px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  color: white;
  flex-shrink: 0;
}

.revenue .stat-icon {
  background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
}

.expenses .stat-icon {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.variance.positive .stat-icon {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.variance.negative .stat-icon {
  background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
}

.currencies .stat-icon {
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

.stat-content h3 {
  color: #1e293b;
  margin: 0 0 0.25rem 0;
  font-size: 1.5rem;
  font-weight: 700;
}

.stat-content p {
  color: #64748b;
  margin: 0 0 0.25rem 0;
  font-size: 0.9rem;
  font-weight: 500;
}

.stat-content small {
  color: #94a3b8;
  font-size: 0.8rem;
}

.currency-note {
  color: #6366f1;
  font-size: 0.75rem;
  margin-top: 0.25rem;
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

/* Table Section */
.table-section {
  background: white;
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  border: 1px solid #e2e8f0;
  overflow: hidden;
}

.table-header {
  padding: 1.5rem 2rem;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
}

.table-header h3 {
  color: #1e293b;
  margin: 0;
  font-size: 1.1rem;
}

.table-header h3 i {
  color: #6366f1;
  margin-right: 0.5rem;
}

.table-actions {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.currency-toggle {
  display: flex;
  align-items: center;
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

.btn-export {
  padding: 0.5rem 1rem;
  background: #6366f1;
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s ease;
  font-size: 0.9rem;
}

.btn-export:hover {
  background: #5856eb;
}

.table-container {
  overflow-x: auto;
}

.budget-table {
  width: 100%;
  border-collapse: collapse;
}

.budget-table th {
  padding: 1rem;
  text-align: left;
  font-weight: 600;
  color: #374151;
  border-bottom: 2px solid #e2e8f0;
  font-size: 0.85rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.budget-table th.sortable {
  cursor: pointer;
  user-select: none;
  transition: all 0.3s ease;
}

.budget-table th.sortable:hover {
  background: #e2e8f0;
  color: #6366f1;
}

.budget-table th.sortable i {
  margin-left: 0.5rem;
  opacity: 0.5;
}

.budget-table th.text-right,
.budget-table td.text-right {
  text-align: right;
}

.budget-table th.text-center,
.budget-table td.text-center {
  text-align: center;
}

.budget-table td {
  padding: 1rem;
  border-bottom: 1px solid #f1f5f9;
  vertical-align: middle;
}

.budget-row:hover {
  background: #f8fafc;
}

.account-info strong {
  display: block;
  color: #1e293b;
  font-weight: 600;
}

.account-info small {
  color: #64748b;
  font-size: 0.8rem;
}

.period-badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  background: #ede9fe;
  color: #7c3aed;
  border-radius: 20px;
  font-weight: 600;
  font-size: 0.85rem;
}

.period-dates {
  display: block;
  color: #64748b;
  font-size: 0.75rem;
  margin-top: 0.25rem;
}

.amount-display {
  text-align: right;
}

.amount-badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-weight: 600;
  font-size: 0.85rem;
}

.amount-badge.budgeted {
  background: #ede9fe;
  color: #7c3aed;
}

.amount-badge.actual {
  background: #dcfce7;
  color: #16a34a;
}

.converted-amount {
  margin-top: 0.25rem;
}

.text-muted {
  color: #94a3b8;
}

.variance-badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-weight: 600;
  font-size: 0.85rem;
}

.variance-badge.positive {
  background: #dcfce7;
  color: #16a34a;
}

.variance-badge.negative {
  background: #fee2e2;
  color: #dc2626;
}

.variance-badge small {
  display: block;
  font-size: 0.7rem;
  opacity: 0.8;
}

.currency-info {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.25rem;
}

.currency-badge {
  padding: 0.25rem 0.5rem;
  border-radius: 12px;
  font-weight: 600;
  font-size: 0.75rem;
}

.currency-badge.usd {
  background: #dbeafe;
  color: #1d4ed8;
}

.currency-badge.idr {
  background: #fef3c7;
  color: #d97706;
}

.currency-badge.eur {
  background: #ecfdf5;
  color: #059669;
}

.currency-badge.sgd {
  background: #fde68a;
  color: #b45309;
}

.exchange-rate,
.base-amount {
  font-size: 0.7rem;
  color: #64748b;
}

.department-badge {
  background: #f3f4f6;
  color: #374151;
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.8rem;
}

.no-data {
  color: #94a3b8;
  font-style: italic;
}

.action-buttons {
  display: flex;
  gap: 0.25rem;
  justify-content: center;
}

.btn-action {
  width: 32px;
  height: 32px;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
  font-size: 0.8rem;
}

.btn-action.view {
  background: #ede9fe;
  color: #7c3aed;
}

.btn-action.view:hover {
  background: #ddd6fe;
}

.btn-action.edit {
  background: #fef3c7;
  color: #d97706;
}

.btn-action.edit:hover {
  background: #fde68a;
}

.btn-action.convert {
  background: #e0f2fe;
  color: #0284c7;
}

.btn-action.convert:hover {
  background: #bae6fd;
}

.btn-action.delete {
  background: #fee2e2;
  color: #dc2626;
}

.btn-action.delete:hover {
  background: #fecaca;
}

/* Loading and Empty States */
.loading-state,
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem 2rem;
  color: #64748b;
}

.loading-spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #e2e8f0;
  border-top: 4px solid #6366f1;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 1rem;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.empty-state i {
  font-size: 3rem;
  color: #cbd5e1;
  margin-bottom: 1rem;
}

.empty-state h3 {
  color: #374151;
  margin-bottom: 0.5rem;
}

.btn-create {
  margin-top: 1rem;
  padding: 0.75rem 1.5rem;
  background: #6366f1;
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s ease;
}

.btn-create:hover {
  background: #5856eb;
}

/* Pagination */
.pagination-section {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 2rem;
  border-top: 1px solid #e2e8f0;
}

.pagination-info {
  color: #64748b;
  font-size: 0.9rem;
}

.pagination-controls {
  display: flex;
  gap: 0.5rem;
}

.page-btn {
  padding: 0.5rem 0.75rem;
  border: 1px solid #e2e8f0;
  background: white;
  color: #374151;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.3s ease;
  min-width: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.page-btn:hover:not(:disabled) {
  background: #f8fafc;
  border-color: #6366f1;
}

.page-btn.active {
  background: #6366f1;
  color: white;
  border-color: #6366f1;
}

.page-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
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

.conversion-modal {
  max-width: 600px;
}

.modal-header {
  padding: 1.5rem 2rem 1rem 2rem;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-header h3 {
  color: #1e293b;
  margin: 0;
  font-size: 1.25rem;
}

.modal-header h3 i {
  color: #6366f1;
  margin-right: 0.5rem;
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

.budget-info {
  background: #f8fafc;
  padding: 1rem;
  border-radius: 8px;
  margin-top: 1rem;
}

.conversion-form {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-group label {
  font-weight: 500;
  color: #374151;
}

.form-input,
.form-select {
  padding: 0.75rem;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  font-size: 0.9rem;
  transition: all 0.3s ease;
}

.form-input:focus,
.form-select:focus {
  outline: none;
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.form-input[readonly] {
  background: #f8fafc;
  color: #64748b;
}

.conversion-preview {
  background: #f0f9ff;
  border: 1px solid #bae6fd;
  border-radius: 8px;
  padding: 1rem;
  margin-top: 1rem;
}

.conversion-preview h4 {
  color: #0c4a6e;
  margin: 0 0 0.75rem 0;
  font-size: 1rem;
}

.preview-item {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.5rem;
  font-size: 0.9rem;
}

.preview-item:last-child {
  margin-bottom: 0;
  font-weight: 600;
  color: #0c4a6e;
}

.modal-footer {
  padding: 1rem 2rem 1.5rem 2rem;
  border-top: 1px solid #e2e8f0;
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
}

.btn-cancel,
.btn-delete,
.btn-preview,
.btn-confirm {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 500;
  transition: all 0.3s ease;
}

.btn-cancel {
  background: #f1f5f9;
  color: #64748b;
}

.btn-cancel:hover {
  background: #e2e8f0;
  color: #374151;
}

.btn-delete {
  background: #dc2626;
  color: white;
}

.btn-delete:hover:not(:disabled) {
  background: #b91c1c;
}

.btn-preview {
  background: #0284c7;
  color: white;
}

.btn-preview:hover:not(:disabled) {
  background: #0369a1;
}

.btn-confirm {
  background: #059669;
  color: white;
}

.btn-confirm:hover:not(:disabled) {
  background: #047857;
}

.btn-delete:disabled,
.btn-preview:disabled,
.btn-confirm:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Responsive Design */
@media (max-width: 768px) {
  .budget-list-container {
    padding: 1rem;
  }
  
  .filters-grid {
    grid-template-columns: 1fr;
  }
  
  .stats-grid {
    grid-template-columns: 1fr;
  }
  
  .table-container {
    font-size: 0.8rem;
  }
  
  .budget-table th,
  .budget-table td {
    padding: 0.75rem 0.5rem;
  }
  
  .action-buttons {
    flex-direction: column;
    gap: 0.125rem;
  }
  
  .btn-action {
    width: 28px;
    height: 28px;
    font-size: 0.7rem;
  }
  
  .pagination-section {
    flex-direction: column;
    gap: 1rem;
    text-align: center;
  }
  
  .modal-content {
    margin: 1rem;
    max-width: calc(100vw - 2rem);
  }
}
</style>