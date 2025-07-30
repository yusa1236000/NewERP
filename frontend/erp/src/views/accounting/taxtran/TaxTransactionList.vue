<template>
    <div class="tax-list-container">
      <!-- Header Section -->
      <div class="page-header">
        <div class="header-content">
          <div class="title-section">
            <h1 class="page-title">
              <i class="fas fa-receipt"></i>
              Tax Transactions
            </h1>
            <p class="page-description">Manage and track all tax transactions</p>
          </div>

          <div class="header-actions">
            <button @click="refreshData" class="btn btn-outline" :disabled="loading">
              <i class="fas fa-sync-alt" :class="{ 'fa-spin': loading }"></i>
              Refresh
            </button>
            <button @click="exportData" class="btn btn-outline">
              <i class="fas fa-download"></i>
              Export
            </button>
            <router-link to="/accounting/taxtran/transaction-form" class="btn btn-primary">
              <i class="fas fa-plus"></i>
              Create Transaction
            </router-link>
          </div>
        </div>
      </div>

      <!-- Statistics Cards -->
      <div class="stats-section">
        <div class="stats-grid">
          <div class="stat-card total-amount">
            <div class="stat-icon">
              <i class="fas fa-dollar-sign"></i>
            </div>
            <div class="stat-content">
              <h3>${{ formatCurrency(statistics.totalAmount) }}</h3>
              <p>Total Tax Amount</p>
            </div>
          </div>
          
          <div class="stat-card transaction-count">
            <div class="stat-icon">
              <i class="fas fa-file-invoice"></i>
            </div>
            <div class="stat-content">
              <h3>{{ statistics.totalCount }}</h3>
              <p>Total Transactions</p>
            </div>
          </div>
          
          <div class="stat-card pending">
            <div class="stat-icon">
              <i class="fas fa-clock"></i>
            </div>
            <div class="stat-content">
              <h3>{{ statistics.pending }}</h3>
              <p>Pending Transactions</p>
            </div>
          </div>
          
          <div class="stat-card completed">
            <div class="stat-icon">
              <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-content">
              <h3>{{ statistics.completed }}</h3>
              <p>Completed Transactions</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Filters Section -->
      <div class="filters-section">
        <div class="filters-card">
          <div class="filters-header">
            <h3>
              <i class="fas fa-filter"></i>
              Filters & Search
            </h3>
            <div class="filter-actions">
              <button @click="resetFilters" class="btn btn-text" v-if="hasActiveFilters">
                <i class="fas fa-times"></i>
                Clear Filters
              </button>
              <button @click="toggleFilters" class="btn btn-text">
                <i :class="showFilters ? 'fas fa-chevron-up' : 'fas fa-chevron-down'"></i>
                {{ showFilters ? 'Hide' : 'Show' }} Filters
              </button>
            </div>
          </div>
          
          <div class="search-section">
            <div class="search-wrapper">
              <i class="fas fa-search search-icon"></i>
              <input 
                v-model="searchQuery" 
                type="text" 
                placeholder="Search by tax type, code, supplier name, or invoice number..."
                class="search-input"
                @input="debounceSearch"
              />
            </div>
          </div>

          <div v-if="showFilters" class="filters-grid">
            <div class="filter-group">
              <label class="filter-label">Tax Type</label>
              <select v-model="filters.tax_type" class="filter-select" @change="applyFilters">
                <option value="">All Tax Types</option>
                <option value="VAT">VAT</option>
                <option value="Sales Tax">Sales Tax</option>
                <option value="Income Tax">Income Tax</option>
                <option value="Corporate Tax">Corporate Tax</option>
                <option value="Withholding Tax">Withholding Tax</option>
                <option value="Property Tax">Property Tax</option>
                <option value="Excise Tax">Excise Tax</option>
              </select>
            </div>

            <div class="filter-group">
              <label class="filter-label">Status</label>
              <select v-model="filters.status" class="filter-select" @change="applyFilters">
                <option value="">All Statuses</option>
                <option value="Draft">Draft</option>
                <option value="Pending">Pending</option>
                <option value="Approved">Approved</option>
                <option value="Posted">Posted</option>
                <option value="Filed">Filed</option>
                <option value="Paid">Paid</option>
                <option value="Completed">Completed</option>
                <option value="Cancelled">Cancelled</option>
              </select>
            </div>

            <div class="filter-group">
              <label class="filter-label">Currency</label>
              <select v-model="filters.currency" class="filter-select" @change="applyFilters">
                <option value="">All Currencies</option>
                <option v-for="currency in currencies" :key="currency" :value="currency">
                  {{ currency }}
                </option>
              </select>
            </div>

            <div class="filter-group">
              <label class="filter-label">Tax Code</label>
              <input 
                v-model="filters.tax_code" 
                type="text" 
                class="filter-input"
                placeholder="Enter tax code"
                @input="debounceFilter"
              />
            </div>

            <div class="filter-group">
              <label class="filter-label">From Date</label>
              <input 
                v-model="filters.from_date" 
                type="date" 
                class="filter-input"
                @change="applyFilters"
              />
            </div>

            <div class="filter-group">
              <label class="filter-label">To Date</label>
              <input 
                v-model="filters.to_date" 
                type="date" 
                class="filter-input"
                @change="applyFilters"
              />
            </div>

            <div class="filter-group">
              <label class="filter-label">Min Amount</label>
              <input 
                v-model.number="filters.min_amount" 
                type="number" 
                class="filter-input"
                step="0.01"
                placeholder="0.00"
                @input="debounceFilter"
              />
            </div>

            <div class="filter-group">
              <label class="filter-label">Max Amount</label>
              <input 
                v-model.number="filters.max_amount" 
                type="number" 
                class="filter-input"
                step="0.01"
                placeholder="0.00"
                @input="debounceFilter"
              />
            </div>

            <!-- Currency Conversion Options -->
            <div class="filter-group">
              <label class="filter-label">Display Currency</label>
              <select v-model="displayCurrency" class="filter-select" @change="applyFilters">
                <option value="">Original Currency</option>
                <option v-for="currency in currencies" :key="currency" :value="currency">
                  {{ currency }}
                </option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <!-- Table Section -->
      <div class="table-section">
        <div class="table-card">
          <div class="table-header">
            <h3>
              <i class="fas fa-list"></i>
              Transactions List
              <span class="record-count">({{ pagination.total }} records)</span>
            </h3>
            
            <div class="table-actions">
              <div class="view-options">
                <button 
                  @click="currentView = 'table'"
                  :class="['view-btn', { active: currentView === 'table' }]"
                >
                  <i class="fas fa-table"></i>
                </button>
                <button 
                  @click="currentView = 'cards'"
                  :class="['view-btn', { active: currentView === 'cards' }]"
                >
                  <i class="fas fa-th-large"></i>
                </button>
              </div>
              
              <div class="per-page-selector">
                <label>Show:</label>
                <select v-model="perPage" @change="changePerPage" class="per-page-select">
                  <option :value="10">10</option>
                  <option :value="25">25</option>
                  <option :value="50">50</option>
                  <option :value="100">100</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Loading State -->
          <div v-if="loading" class="loading-state">
            <div class="loading-spinner">
              <i class="fas fa-spinner fa-spin"></i>
            </div>
            <p>Loading transactions...</p>
          </div>

          <!-- Table View -->
          <div v-else-if="currentView === 'table'" class="table-wrapper">
            <table class="transactions-table">
              <thead>
                <tr>
                  <th @click="sortBy('tax_id')" class="sortable">
                    ID
                    <i :class="getSortIcon('tax_id')"></i>
                  </th>
                  <th @click="sortBy('tax_type')" class="sortable">
                    Tax Type
                    <i :class="getSortIcon('tax_type')"></i>
                  </th>
                  <th @click="sortBy('tax_code')" class="sortable">
                    Tax Code
                    <i :class="getSortIcon('tax_code')"></i>
                  </th>
                  <th @click="sortBy('transaction_date')" class="sortable">
                    Date
                    <i :class="getSortIcon('transaction_date')"></i>
                  </th>
                  <th @click="sortBy('tax_amount')" class="sortable">
                    Tax Amount
                    <i :class="getSortIcon('tax_amount')"></i>
                  </th>
                  <th>Reference</th>
                  <th>Supplier</th>
                  <th @click="sortBy('status')" class="sortable">
                    Status
                    <i :class="getSortIcon('status')"></i>
                  </th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="transaction in transactions" :key="transaction.tax_id" class="transaction-row">
                  <td>
                    <span class="id-badge">#{{ transaction.tax_id }}</span>
                  </td>
                  <td>
                    <div class="tax-type-cell">
                      <i class="fas fa-tag"></i>
                      {{ transaction.tax_type }}
                    </div>
                  </td>
                  <td>
                    <span class="tax-code">{{ transaction.tax_code }}</span>
                  </td>
                  <td>
                    <div class="date-cell">
                      <div class="date-primary">{{ formatDateShort(transaction.transaction_date) }}</div>
                      <div class="date-secondary">{{ formatTime(transaction.transaction_date) }}</div>
                    </div>
                  </td>
                  <td>
                    <div class="amount-cell">
                      <div class="amount-primary">
                        {{ transaction.currency }} {{ formatCurrency(transaction.tax_amount) }}
                      </div>
                      <div v-if="transaction.converted_amount" class="amount-secondary">
                        {{ transaction.display_currency }} {{ formatCurrency(transaction.converted_amount) }}
                      </div>
                    </div>
                  </td>
                  <td>
                    <div class="reference-cell">
                      <div class="reference-type">{{ transaction.reference_type }}</div>
                      <div class="reference-id">#{{ transaction.reference_id }}</div>
                      <div v-if="transaction.invoice_number" class="invoice-number">
                        Inv: {{ transaction.invoice_number }}
                      </div>
                    </div>
                  </td>
                  <td>
                    <div v-if="transaction.supplier_name" class="supplier-cell">
                      <div class="supplier-name">{{ transaction.supplier_name }}</div>
                      <div v-if="transaction.supplier_tax_id" class="supplier-tax-id">
                        Tax ID: {{ transaction.supplier_tax_id }}
                      </div>
                    </div>
                    <span v-else class="text-muted">-</span>
                  </td>
                  <td>
                    <span class="status-badge" :class="getStatusClass(transaction.status)">
                      <i class="fas fa-circle"></i>
                      {{ transaction.status }}
                    </span>
                  </td>
                  <td>
                    <div class="action-buttons">
                      <button 
                        @click="viewTransaction(transaction)" 
                        class="btn-action btn-view"
                        title="View Details"
                      >
                        <i class="fas fa-eye"></i>
                      </button>
                      <button 
                        @click="editTransaction(transaction)" 
                        class="btn-action btn-edit"
                        title="Edit Transaction"
                        v-if="canEdit(transaction)"
                      >
                        <i class="fas fa-edit"></i>
                      </button>
                      <button 
                        @click="deleteTransaction(transaction)" 
                        class="btn-action btn-delete"
                        title="Delete Transaction"
                        v-if="canDelete(transaction)"
                      >
                        <i class="fas fa-trash"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Cards View -->
          <div v-else-if="currentView === 'cards'" class="cards-wrapper">
            <div class="cards-grid">
              <div v-for="transaction in transactions" :key="transaction.tax_id" class="transaction-card">
                <div class="card-header">
                  <div class="card-title">
                    <span class="id-badge">#{{ transaction.tax_id }}</span>
                    <span class="tax-type">{{ transaction.tax_type }}</span>
                  </div>
                  <div class="card-status">
                    <span class="status-badge" :class="getStatusClass(transaction.status)">
                      {{ transaction.status }}
                    </span>
                  </div>
                </div>
                
                <div class="card-content">
                  <div class="card-amount">
                    <div class="amount-primary">
                      {{ transaction.currency }} {{ formatCurrency(transaction.tax_amount) }}
                    </div>
                    <div class="amount-label">Tax Amount</div>
                  </div>
                  
                  <div class="card-details">
                    <div class="detail-row">
                      <span class="detail-label">Tax Code:</span>
                      <span class="detail-value">{{ transaction.tax_code }}</span>
                    </div>
                    <div class="detail-row">
                      <span class="detail-label">Date:</span>
                      <span class="detail-value">{{ formatDateShort(transaction.transaction_date) }}</span>
                    </div>
                    <div class="detail-row">
                      <span class="detail-label">Reference:</span>
                      <span class="detail-value">{{ transaction.reference_type }} #{{ transaction.reference_id }}</span>
                    </div>
                    <div v-if="transaction.supplier_name" class="detail-row">
                      <span class="detail-label">Supplier:</span>
                      <span class="detail-value">{{ transaction.supplier_name }}</span>
                    </div>
                  </div>
                </div>
                
                <div class="card-actions">
                  <button @click="viewTransaction(transaction)" class="btn btn-outline btn-sm">
                    <i class="fas fa-eye"></i>
                    View
                  </button>
                  <button 
                    v-if="canEdit(transaction)"
                    @click="editTransaction(transaction)" 
                    class="btn btn-primary btn-sm"
                  >
                    <i class="fas fa-edit"></i>
                    Edit
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Empty State -->
          <div v-if="!loading && transactions.length === 0" class="empty-state">
            <div class="empty-icon">
              <i class="fas fa-receipt"></i>
            </div>
            <h3>No transactions found</h3>
            <p v-if="hasActiveFilters">Try adjusting your filters or search criteria</p>
            <p v-else>Create your first tax transaction to get started</p>
            <div class="empty-actions">
              <button v-if="hasActiveFilters" @click="resetFilters" class="btn btn-outline">
                <i class="fas fa-times"></i>
                Clear Filters
              </button>
              <router-link to="/accounting/taxtran/transaction-form" class="btn btn-primary">
                <i class="fas fa-plus"></i>
                Create Transaction
              </router-link>
            </div>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="pagination.last_page > 1" class="pagination-section">
        <div class="pagination-wrapper">
          <div class="pagination-info">
            Showing {{ pagination.from }}-{{ pagination.to }} of {{ pagination.total }} transactions
          </div>
          
          <div class="pagination-controls">
            <button 
              @click="changePage(1)" 
              class="btn-page"
              :disabled="pagination.current_page === 1"
            >
              <i class="fas fa-angle-double-left"></i>
            </button>
            <button 
              @click="changePage(pagination.current_page - 1)" 
              class="btn-page"
              :disabled="pagination.current_page === 1"
            >
              <i class="fas fa-angle-left"></i>
            </button>
            
            <div class="page-numbers">
              <button 
                v-for="page in visiblePages" 
                :key="page"
                @click="changePage(page)" 
                :class="['btn-page', { active: page === pagination.current_page }]"
              >
                {{ page }}
              </button>
            </div>
            
            <button 
              @click="changePage(pagination.current_page + 1)" 
              class="btn-page"
              :disabled="pagination.current_page === pagination.last_page"
            >
              <i class="fas fa-angle-right"></i>
            </button>
            <button 
              @click="changePage(pagination.last_page)" 
              class="btn-page"
              :disabled="pagination.current_page === pagination.last_page"
            >
              <i class="fas fa-angle-double-right"></i>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div v-if="showDeleteModal" class="modal-overlay" @click="closeDeleteModal">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3>
            <i class="fas fa-trash text-red-500"></i>
            Delete Tax Transaction
          </h3>
          <button @click="closeDeleteModal" class="modal-close">
            <i class="fas fa-times"></i>
          </button>
        </div>
        
        <div class="modal-body">
          <p>Are you sure you want to delete this tax transaction?</p>
          <p class="warning-text">
            <i class="fas fa-exclamation-triangle"></i>
            This action cannot be undone.
          </p>
          
          <div class="transaction-info">
            <strong>Transaction ID:</strong> #{{ transactionToDelete?.tax_id }}<br>
            <strong>Tax Type:</strong> {{ transactionToDelete?.tax_type }}<br>
            <strong>Amount:</strong> {{ transactionToDelete?.currency }} {{ formatCurrency(transactionToDelete?.tax_amount) }}
          </div>
        </div>
        
        <div class="modal-footer">
          <button @click="closeDeleteModal" class="btn btn-outline">
            Cancel
          </button>
          <button @click="confirmDelete" class="btn btn-danger" :disabled="deleting">
            <i class="fas fa-spinner fa-spin" v-if="deleting"></i>
            {{ deleting ? 'Deleting...' : 'Delete Transaction' }}
          </button>
        </div>
      </div>
    </div>
</template>

<script>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

export default {
  name: 'TaxTransactionList',
  components: {
  },
  setup() {
    const router = useRouter()
    
    const loading = ref(false)
    const deleting = ref(false)
    const showFilters = ref(false)
    const showDeleteModal = ref(false)
    const transactionToDelete = ref(null)
    const currentView = ref('table')
    const searchQuery = ref('')
    const displayCurrency = ref('')
    const perPage = ref(25)
    const sortField = ref('transaction_date')
    const sortOrder = ref('desc')
    
    const transactions = ref([])
    const currencies = ref(['USD', 'EUR', 'GBP', 'JPY', 'CAD', 'AUD'])
    
    const filters = reactive({
      tax_type: '',
      tax_code: '',
      status: '',
      currency: '',
      from_date: '',
      to_date: '',
      min_amount: null,
      max_amount: null
    })
    
    const pagination = reactive({
      current_page: 1,
      last_page: 1,
      total: 0,
      from: 0,
      to: 0
    })
    
    const statistics = reactive({
      totalAmount: 0,
      totalCount: 0,
      pending: 0,
      completed: 0
    })

    // Computed properties
    const hasActiveFilters = computed(() => {
      return Object.values(filters).some(value => value !== '' && value !== null) || searchQuery.value !== '' || displayCurrency.value !== ''
    })

    const visiblePages = computed(() => {
      const current = pagination.current_page
      const last = pagination.last_page
      const delta = 2
      const range = []
      
      for (let i = Math.max(2, current - delta); i <= Math.min(last - 1, current + delta); i++) {
        range.push(i)
      }
      
      if (current - delta > 2) {
        range.unshift('...')
      }
      if (current + delta < last - 1) {
        range.push('...')
      }
      
      range.unshift(1)
      if (last !== 1) {
        range.push(last)
      }
      
      return range
    })

    // Methods
    const fetchTransactions = async (page = 1) => {
      loading.value = true
      try {
        const params = {
          page,
          per_page: perPage.value,
          sort_by: sortField.value,
          sort_order: sortOrder.value,
          search: searchQuery.value,
          display_currency: displayCurrency.value,
          ...filters
        }

        // Remove empty values
        Object.keys(params).forEach(key => {
          if (params[key] === '' || params[key] === null) {
            delete params[key]
          }
        })

        const response = await axios.get('/accounting/tax-transactions', { params })
        transactions.value = response.data.data
        
        // Update pagination
        Object.assign(pagination, {
          current_page: response.data.current_page,
          last_page: response.data.last_page,
          total: response.data.total,
          from: response.data.from,
          to: response.data.to
        })

        // Calculate statistics
        calculateStatistics()
      } catch (error) {
        console.error('Error fetching transactions:', error)
        showNotification('Error loading transactions', 'error')
      } finally {
        loading.value = false
      }
    }

    const calculateStatistics = () => {
      statistics.totalAmount = transactions.value.reduce((sum, t) => sum + (t.tax_amount || 0), 0)
      statistics.totalCount = pagination.total
      statistics.pending = transactions.value.filter(t => ['Draft', 'Pending'].includes(t.status)).length
      statistics.completed = transactions.value.filter(t => ['Completed', 'Filed', 'Paid'].includes(t.status)).length
    }

    const refreshData = () => {
      fetchTransactions(pagination.current_page)
    }

    const applyFilters = () => {
      pagination.current_page = 1
      fetchTransactions()
    }

    const resetFilters = () => {
      Object.assign(filters, {
        tax_type: '',
        tax_code: '',
        status: '',
        currency: '',
        from_date: '',
        to_date: '',
        min_amount: null,
        max_amount: null
      })
      searchQuery.value = ''
      displayCurrency.value = ''
      applyFilters()
    }

    const toggleFilters = () => {
      showFilters.value = !showFilters.value
    }

    const sortBy = (field) => {
      if (sortField.value === field) {
        sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
      } else {
        sortField.value = field
        sortOrder.value = 'asc'
      }
      applyFilters()
    }

    const getSortIcon = (field) => {
      if (sortField.value !== field) return 'fas fa-sort'
      return sortOrder.value === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down'
    }

    const changePage = (page) => {
      if (page >= 1 && page <= pagination.last_page) {
        pagination.current_page = page
        fetchTransactions(page)
      }
    }

    const changePerPage = () => {
      pagination.current_page = 1
      fetchTransactions()
    }

    const viewTransaction = (transaction) => {
      router.push(`/tax-transactions/${transaction.tax_id}`)
    }

    const editTransaction = (transaction) => {
      router.push(`/tax-transactions/${transaction.tax_id}/edit`)
    }

    const deleteTransaction = (transaction) => {
      transactionToDelete.value = transaction
      showDeleteModal.value = true
    }

    const closeDeleteModal = () => {
      showDeleteModal.value = false
      transactionToDelete.value = null
    }

    const confirmDelete = async () => {
      if (!transactionToDelete.value) return
      
      deleting.value = true
      try {
        await axios.delete(`/accounting/tax-transactions/${transactionToDelete.value.tax_id}`)
        showNotification('Transaction deleted successfully', 'success')
        closeDeleteModal()
        refreshData()
      } catch (error) {
        console.error('Error deleting transaction:', error)
        if (error.response && error.response.status === 422) {
          showNotification(error.response.data.message, 'error')
        } else {
          showNotification('Error deleting transaction', 'error')
        }
      } finally {
        deleting.value = false
      }
    }

    const canEdit = (transaction) => {
      return ['Draft', 'Pending'].includes(transaction.status)
    }

    const canDelete = (transaction) => {
      return !['Posted', 'Filed', 'Paid'].includes(transaction.status)
    }

    const exportData = async () => {
      try {
        const params = {
          ...filters,
          search: searchQuery.value,
          display_currency: displayCurrency.value
        }

        // Remove empty values
        Object.keys(params).forEach(key => {
          if (params[key] === '' || params[key] === null) {
            delete params[key]
          }
        })

        const response = await axios.get('/accounting/tax-transactions/export', {
          params,
          responseType: 'blob'
        })
        
        const url = window.URL.createObjectURL(new Blob([response.data]))
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', `tax-transactions-${new Date().toISOString().split('T')[0]}.xlsx`)
        document.body.appendChild(link)
        link.click()
        link.remove()
        window.URL.revokeObjectURL(url)
        
        showNotification('Data exported successfully', 'success')
      } catch (error) {
        console.error('Error exporting data:', error)
        showNotification('Error exporting data', 'error')
      }
    }

    // Debounced search and filter functions
    let searchTimeout = null
    const debounceSearch = () => {
      clearTimeout(searchTimeout)
      searchTimeout = setTimeout(() => {
        applyFilters()
      }, 500)
    }

    let filterTimeout = null
    const debounceFilter = () => {
      clearTimeout(filterTimeout)
      filterTimeout = setTimeout(() => {
        applyFilters()
      }, 500)
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

    const formatDateShort = (date) => {
      if (!date) return ''
      return new Date(date).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric'
      })
    }

    const formatTime = (date) => {
      if (!date) return ''
      return new Date(date).toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit'
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
    onMounted(() => {
      fetchTransactions()
    })

    return {
      loading,
      deleting,
      showFilters,
      showDeleteModal,
      transactionToDelete,
      currentView,
      searchQuery,
      displayCurrency,
      perPage,
      transactions,
      currencies,
      filters,
      pagination,
      statistics,
      hasActiveFilters,
      visiblePages,
      refreshData,
      applyFilters,
      resetFilters,
      toggleFilters,
      sortBy,
      getSortIcon,
      changePage,
      changePerPage,
      viewTransaction,
      editTransaction,
      deleteTransaction,
      closeDeleteModal,
      confirmDelete,
      canEdit,
      canDelete,
      exportData,
      debounceSearch,
      debounceFilter,
      formatDate,
      formatDateShort,
      formatTime,
      formatCurrency,
      getStatusClass
    }
  }
}
</script>

<style scoped>
.tax-list-container {
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
  justify-content: space-between;
  align-items: flex-start;
  gap: 2rem;
}

.title-section h1 {
  font-size: 2.5rem;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0;
  display: flex;
  align-items: center;
  gap: 1rem;
}

.title-section h1 i {
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

/* Statistics Cards */
.stats-section {
  margin-bottom: 2rem;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
}

.stat-card {
  background: var(--card-bg);
  border-radius: 16px;
  padding: 1.5rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  border: 1px solid var(--border-color);
  transition: all 0.3s ease;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
}

.stat-icon {
  width: 60px;
  height: 60px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  flex-shrink: 0;
}

.stat-card.total-amount .stat-icon {
  background: linear-gradient(135deg, #10b981, #059669);
  color: white;
}

.stat-card.transaction-count .stat-icon {
  background: linear-gradient(135deg, #3b82f6, #2563eb);
  color: white;
}

.stat-card.pending .stat-icon {
  background: linear-gradient(135deg, #f59e0b, #d97706);
  color: white;
}

.stat-card.completed .stat-icon {
  background: linear-gradient(135deg, #8b5cf6, #7c3aed);
  color: white;
}

.stat-content h3 {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0;
}

.stat-content p {
  font-size: 0.9rem;
  color: var(--text-secondary);
  margin: 0;
}

/* Filters Section */
.filters-section {
  margin-bottom: 2rem;
}

.filters-card {
  background: var(--card-bg);
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  border: 1px solid var(--border-color);
}

.filters-header {
  padding: 1.5rem 2rem;
  border-bottom: 1px solid var(--border-color);
  background: var(--bg-tertiary);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.filters-header h3 {
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--text-primary);
  margin: 0;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.filter-actions {
  display: flex;
  gap: 1rem;
}

.search-section {
  padding: 1.5rem 2rem;
  border-bottom: 1px solid var(--border-color);
}

.search-wrapper {
  position: relative;
  max-width: 500px;
}

.search-icon {
  position: absolute;
  left: 1rem;
  top: 50%;
  transform: translateY(-50%);
  color: var(--text-muted);
}

.search-input {
  width: 100%;
  padding: 0.75rem 1rem 0.75rem 2.5rem;
  border: 2px solid var(--border-color);
  border-radius: 8px;
  font-size: 1rem;
  transition: all 0.3s ease;
}

.search-input:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.filters-grid {
  padding: 2rem;
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.5rem;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.filter-label {
  font-weight: 600;
  color: var(--text-primary);
  font-size: 0.9rem;
}

.filter-input,
.filter-select {
  padding: 0.5rem 0.75rem;
  border: 2px solid var(--border-color);
  border-radius: 6px;
  font-size: 0.9rem;
  transition: border-color 0.3s ease;
}

.filter-input:focus,
.filter-select:focus {
  outline: none;
  border-color: var(--primary-color);
}

/* Table Section */
.table-section {
  margin-bottom: 2rem;
}

.table-card {
  background: var(--card-bg);
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  border: 1px solid var(--border-color);
}

.table-header {
  padding: 1.5rem 2rem;
  border-bottom: 1px solid var(--border-color);
  background: var(--bg-tertiary);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.table-header h3 {
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--text-primary);
  margin: 0;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.record-count {
  font-size: 0.85rem;
  color: var(--text-muted);
  font-weight: 400;
}

.table-actions {
  display: flex;
  gap: 2rem;
  align-items: center;
}

.view-options {
  display: flex;
  gap: 0.5rem;
}

.view-btn {
  width: 40px;
  height: 40px;
  border: 2px solid var(--border-color);
  background: var(--card-bg);
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.3s ease;
  color: var(--text-secondary);
}

.view-btn:hover,
.view-btn.active {
  border-color: var(--primary-color);
  background: var(--primary-color);
  color: white;
}

.per-page-selector {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.9rem;
}

.per-page-select {
  padding: 0.25rem 0.5rem;
  border: 1px solid var(--border-color);
  border-radius: 4px;
}

/* Loading State */
.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem 2rem;
  text-align: center;
}

.loading-spinner {
  font-size: 3rem;
  color: var(--primary-color);
  margin-bottom: 1rem;
}

/* Table */
.table-wrapper {
  overflow-x: auto;
}

.transactions-table {
  width: 100%;
  border-collapse: collapse;
}

.transactions-table th {
  background: var(--bg-tertiary);
  padding: 1rem 1.5rem;
  text-align: left;
  font-weight: 600;
  color: var(--text-primary);
  border-bottom: 1px solid var(--border-color);
  font-size: 0.9rem;
  white-space: nowrap;
}

.transactions-table th.sortable {
  cursor: pointer;
  user-select: none;
  transition: background-color 0.2s ease;
}

.transactions-table th.sortable:hover {
  background: var(--border-color);
}

.transactions-table th i {
  margin-left: 0.5rem;
  color: var(--text-muted);
}

.transactions-table td {
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid var(--border-color);
  vertical-align: middle;
}

.transaction-row {
  transition: background-color 0.2s ease;
}

.transaction-row:hover {
  background: var(--bg-tertiary);
}

.id-badge {
  background: var(--primary-color);
  color: white;
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 500;
}

.tax-type-cell {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: 500;
}

.tax-code {
  background: var(--bg-tertiary);
  padding: 0.25rem 0.75rem;
  border-radius: 8px;
  font-family: monospace;
  font-size: 0.9rem;
}

.date-cell {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.date-primary {
  font-weight: 500;
  color: var(--text-primary);
}

.date-secondary {
  font-size: 0.8rem;
  color: var(--text-muted);
}

.amount-cell {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.amount-primary {
  font-weight: 600;
  color: var(--text-primary);
}

.amount-secondary {
  font-size: 0.8rem;
  color: var(--text-muted);
}

.reference-cell {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.reference-type {
  font-weight: 500;
  color: var(--text-primary);
}

.reference-id {
  font-size: 0.8rem;
  color: var(--text-muted);
}

.invoice-number {
  font-size: 0.8rem;
  color: var(--text-muted);
  font-style: italic;
}

.supplier-cell {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.supplier-name {
  font-weight: 500;
  color: var(--text-primary);
}

.supplier-tax-id {
  font-size: 0.8rem;
  color: var(--text-muted);
}

.text-muted {
  color: var(--text-muted);
}

/* Status Badge */
.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
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

/* Action Buttons */
.action-buttons {
  display: flex;
  gap: 0.5rem;
}

.btn-action {
  width: 32px;
  height: 32px;
  border: none;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.3s ease;
  font-size: 0.8rem;
}

.btn-view {
  background: rgba(59, 130, 246, 0.1);
  color: #2563eb;
}

.btn-edit {
  background: rgba(16, 185, 129, 0.1);
  color: #059669;
}

.btn-delete {
  background: rgba(239, 68, 68, 0.1);
  color: #dc2626;
}

.btn-action:hover {
  transform: scale(1.1);
}

/* Cards View */
.cards-wrapper {
  padding: 2rem;
}

.cards-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 1.5rem;
}

.transaction-card {
  background: var(--bg-tertiary);
  border: 2px solid var(--border-color);
  border-radius: 16px;
  padding: 1.5rem;
  transition: all 0.3s ease;
}

.transaction-card:hover {
  transform: translateY(-4px);
  border-color: var(--primary-color);
  box-shadow: 0 8px 25px rgba(99, 102, 241, 0.15);
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.card-title {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.tax-type {
  font-weight: 600;
  color: var(--text-primary);
}

.card-content {
  margin-bottom: 1.5rem;
}

.card-amount {
  text-align: center;
  margin-bottom: 1rem;
}

.amount-primary {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--primary-color);
}

.amount-label {
  font-size: 0.9rem;
  color: var(--text-muted);
  margin-top: 0.25rem;
}

.card-details {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.detail-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.detail-label {
  font-size: 0.85rem;
  color: var(--text-muted);
  font-weight: 500;
}

.detail-value {
  font-size: 0.9rem;
  color: var(--text-primary);
  font-weight: 500;
}

.card-actions {
  display: flex;
  gap: 0.75rem;
}

/* Empty State */
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem 2rem;
  text-align: center;
}

.empty-icon {
  font-size: 4rem;
  color: var(--text-muted);
  margin-bottom: 1rem;
}

.empty-state h3 {
  font-size: 1.5rem;
  font-weight: 600;
  color: var(--text-primary);
  margin: 0 0 0.5rem 0;
}

.empty-state p {
  color: var(--text-secondary);
  margin: 0 0 2rem 0;
}

.empty-actions {
  display: flex;
  gap: 1rem;
}

/* Pagination */
.pagination-section {
  margin-top: 2rem;
}

.pagination-wrapper {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: var(--card-bg);
  padding: 1.5rem 2rem;
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  border: 1px solid var(--border-color);
}

.pagination-info {
  font-size: 0.9rem;
  color: var(--text-secondary);
}

.pagination-controls {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.btn-page {
  width: 40px;
  height: 40px;
  border: 1px solid var(--border-color);
  background: var(--card-bg);
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.3s ease;
  font-size: 0.9rem;
  color: var(--text-primary);
}

.btn-page:hover:not(:disabled) {
  border-color: var(--primary-color);
  background: var(--primary-color);
  color: white;
}

.btn-page.active {
  border-color: var(--primary-color);
  background: var(--primary-color);
  color: white;
}

.btn-page:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.page-numbers {
  display: flex;
  gap: 0.25rem;
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

.btn-danger {
  background: #dc2626;
  color: white;
}

.btn-danger:hover:not(:disabled) {
  background: #b91c1c;
  transform: translateY(-1px);
}

.btn-text {
  background: none;
  border: none;
  color: var(--primary-color);
  padding: 0.5rem;
}

.btn-text:hover {
  background: rgba(99, 102, 241, 0.1);
}

.btn-sm {
  padding: 0.5rem 1rem;
  font-size: 0.8rem;
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
  z-index: 9999;
}

.modal-content {
  background: var(--card-bg);
  border-radius: 16px;
  max-width: 500px;
  width: 90%;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
}

.modal-header {
  padding: 1.5rem 2rem;
  border-bottom: 1px solid var(--border-color);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-header h3 {
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--text-primary);
  margin: 0;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.modal-close {
  width: 32px;
  height: 32px;
  border: none;
  background: var(--bg-tertiary);
  border-radius: 8px;
  color: var(--text-secondary);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
}

.modal-close:hover {
  background: var(--primary-color);
  color: white;
}

.modal-body {
  padding: 2rem;
}

.modal-body p {
  margin: 0 0 1rem 0;
  color: var(--text-secondary);
  line-height: 1.6;
}

.warning-text {
  background: rgba(249, 115, 22, 0.1);
  color: #ea580c;
  padding: 1rem;
  border-radius: 8px;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: 500;
}

.transaction-info {
  background: var(--bg-tertiary);
  padding: 1rem;
  border-radius: 8px;
  font-size: 0.9rem;
  line-height: 1.6;
  margin-top: 1rem;
}

.modal-footer {
  padding: 1.5rem 2rem;
  border-top: 1px solid var(--border-color);
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
}

/* Responsive Design */
@media (max-width: 768px) {
  .tax-list-container {
    padding: 1rem;
  }

  .header-content {
    flex-direction: column;
    gap: 1rem;
  }

  .header-actions {
    flex-wrap: wrap;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }

  .filters-grid {
    grid-template-columns: 1fr;
  }

  .table-actions {
    flex-direction: column;
    gap: 1rem;
  }

  .cards-grid {
    grid-template-columns: 1fr;
  }

  .pagination-wrapper {
    flex-direction: column;
    gap: 1rem;
  }

  .page-numbers {
    flex-wrap: wrap;
  }

  .title-section h1 {
    font-size: 2rem;
  }

  .modal-content {
    margin: 1rem;
    width: calc(100% - 2rem);
  }

  .modal-header,
  .modal-body,
  .modal-footer {
    padding-left: 1rem;
    padding-right: 1rem;
  }
}

/* CSS Variables */
:root {
  --primary-color: #6366f1;
  --primary-dark: #4f46e5;
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