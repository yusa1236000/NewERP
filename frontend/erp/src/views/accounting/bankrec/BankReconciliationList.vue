<template>
  <div class="bank-reconciliation-list">
    <!-- Page Header -->
    <div class="page-header">
      <div class="header-content">
        <div class="title-section">
          <h1 class="page-title">
            <i class="fas fa-balance-scale"></i>
            Bank Reconciliations
          </h1>
          <p class="page-subtitle">Manage and track bank reconciliation processes with multi-currency support</p>
        </div>
        <div class="header-actions">
          <button @click="exportSummaryReport" class="btn-outline" :disabled="loading">
            <i class="fas fa-file-export"></i>
            Export Report
          </button>
          <router-link to="/accounting/bank-reconciliations/new" class="btn-primary">
            <i class="fas fa-plus"></i>
            New Reconciliation
          </router-link>
        </div>
      </div>
    </div>

    <!-- Filters Section -->
    <div class="filters-section">
      <div class="filters-content">
        <div class="filter-row">
          <div class="filter-group">
            <label class="filter-label">Bank Account</label>
            <select v-model="filters.bank_id" @change="applyFilters" class="filter-select">
              <option value="">All Banks</option>
              <option v-for="bank in bankAccounts" :key="bank.bank_id" :value="bank.bank_id">
                {{ bank.bank_name }} ({{ bank.account_number }})
              </option>
            </select>
          </div>
          
          <div class="filter-group">
            <label class="filter-label">Status</label>
            <select v-model="filters.status" @change="applyFilters" class="filter-select">
              <option value="">All Status</option>
              <option value="Draft">Draft</option>
              <option value="In Progress">In Progress</option>
              <option value="Finalized">Finalized</option>
            </select>
          </div>
          
          <div class="filter-group">
            <label class="filter-label">Currency</label>
            <select v-model="filters.currency" @change="applyFilters" class="filter-select">
              <option value="">All Currencies</option>
              <option v-for="currency in availableCurrencies" :key="currency" :value="currency">
                {{ currency }}
              </option>
            </select>
          </div>
          
          <div class="filter-group">
            <label class="filter-label">Display Currency</label>
            <select v-model="filters.display_currency" @change="applyFilters" class="filter-select">
              <option value="">Original Currency</option>
              <option v-for="currency in availableCurrencies" :key="currency" :value="currency">
                {{ currency }}
              </option>
            </select>
          </div>
        </div>
        
        <div class="filter-row">
          <div class="filter-group">
            <label class="filter-label">From Date</label>
            <input type="date" v-model="filters.from_date" @change="applyFilters" class="filter-input">
          </div>
          
          <div class="filter-group">
            <label class="filter-label">To Date</label>
            <input type="date" v-model="filters.to_date" @change="applyFilters" class="filter-input">
          </div>
          
          <div class="filter-group">
            <label class="filter-label">Per Page</label>
            <select v-model="filters.per_page" @change="applyFilters" class="filter-select">
              <option value="15">15</option>
              <option value="25">25</option>
              <option value="50">50</option>
              <option value="100">100</option>
            </select>
          </div>
          
          <div class="filter-actions">
            <button @click="clearFilters" class="btn-secondary">
              <i class="fas fa-times"></i>
              Clear
            </button>
            <button @click="applyFilters" class="btn-primary">
              <i class="fas fa-search"></i>
              Apply
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Summary Cards -->
    <div class="summary-section" v-if="summaryData">
      <div class="summary-cards">
        <div class="summary-card">
          <div class="card-icon">
            <i class="fas fa-list-alt"></i>
          </div>
          <div class="card-content">
            <div class="card-value">{{ summaryData.total_reconciliations }}</div>
            <div class="card-label">Total Reconciliations</div>
          </div>
        </div>
        
        <div class="summary-card success">
          <div class="card-icon">
            <i class="fas fa-check-circle"></i>
          </div>
          <div class="card-content">
            <div class="card-value">{{ summaryData.finalized_count }}</div>
            <div class="card-label">Finalized</div>
          </div>
        </div>
        
        <div class="summary-card warning">
          <div class="card-icon">
            <i class="fas fa-clock"></i>
          </div>
          <div class="card-content">
            <div class="card-value">{{ summaryData.pending_count }}</div>
            <div class="card-label">Pending</div>
          </div>
        </div>
        
        <div class="summary-card" v-if="filters.display_currency">
          <div class="card-icon">
            <i class="fas fa-exchange-alt"></i>
          </div>
          <div class="card-content">
            <div class="card-value">{{ formatCurrency(summaryData.total_difference, filters.display_currency) }}</div>
            <div class="card-label">Total Difference ({{ filters.display_currency }})</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Reconciliations Table -->
    <div class="table-section">
      <div class="table-header">
        <div class="table-title">
          <h2>Reconciliation Records</h2>
          <span class="record-count">{{ reconciliations.total || 0 }} records</span>
        </div>
        <div class="table-actions">
          <div class="search-box">
            <i class="fas fa-search"></i>
            <input 
              type="text" 
              placeholder="Search reconciliations..." 
              v-model="searchQuery"
              @input="handleSearch"
              class="search-input"
            >
          </div>
        </div>
      </div>

      <div class="table-container">
        <table class="data-table" v-if="!loading">
          <thead>
            <tr>
              <th @click="sort('statement_date')" class="sortable">
                Statement Date
                <i class="fas fa-sort" :class="getSortIcon('statement_date')"></i>
              </th>
              <th>Bank Account</th>
              <th>Currency</th>
              <th @click="sort('statement_balance')" class="sortable">
                Statement Balance
                <i class="fas fa-sort" :class="getSortIcon('statement_balance')"></i>
              </th>
              <th @click="sort('book_balance')" class="sortable">
                Book Balance
                <i class="fas fa-sort" :class="getSortIcon('book_balance')"></i>
              </th>
              <th @click="sort('difference')" class="sortable">
                Difference
                <i class="fas fa-sort" :class="getSortIcon('difference')"></i>
              </th>
              <th v-if="filters.display_currency">Converted Balance</th>
              <th>Status</th>
              <th>Reconciler</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="reconciliation in reconciliations.data" :key="reconciliation.reconciliation_id" 
                class="table-row" @click="viewDetails(reconciliation.reconciliation_id)">
              <td class="date-cell">
                {{ formatDate(reconciliation.statement_date) }}
              </td>
              <td>
                <div class="bank-info">
                  <div class="bank-name">{{ reconciliation.bank_account?.bank_name }}</div>
                  <div class="account-number">{{ reconciliation.bank_account?.account_number }}</div>
                </div>
              </td>
              <td>
                <span class="currency-badge">{{ reconciliation.bank_account?.currency || 'USD' }}</span>
              </td>
              <td class="amount-cell">
                {{ formatCurrency(reconciliation.statement_balance, reconciliation.bank_account?.currency) }}
              </td>
              <td class="amount-cell">
                {{ formatCurrency(reconciliation.book_balance, reconciliation.bank_account?.currency) }}
              </td>
              <td class="amount-cell" :class="getDifferenceClass(reconciliation.difference)">
                {{ formatCurrency(reconciliation.difference, reconciliation.bank_account?.currency) }}
              </td>
              <td v-if="filters.display_currency" class="amount-cell">
                <div v-if="reconciliation.converted_statement_balance">
                  <div>{{ formatCurrency(reconciliation.converted_statement_balance, filters.display_currency) }}</div>
                  <small class="exchange-rate">Rate: {{ reconciliation.exchange_rate?.toFixed(4) }}</small>
                </div>
                <span v-else>-</span>
              </td>
              <td>
                <span class="status-badge" :class="getStatusClass(reconciliation.status)">
                  {{ reconciliation.status }}
                </span>
              </td>
              <td>
                <div class="reconciler-info" v-if="reconciliation.reconciler_name">
                  <div class="reconciler-name">{{ reconciliation.reconciler_name }}</div>
                  <div class="finalized-date" v-if="reconciliation.finalized_at">
                    {{ formatDate(reconciliation.finalized_at) }}
                  </div>
                </div>
                <span v-else class="text-muted">-</span>
              </td>
              <td class="actions-cell" @click.stop>
                <div class="action-buttons">
                  <button @click="viewDetails(reconciliation.reconciliation_id)" 
                          class="action-btn view" title="View Details">
                    <i class="fas fa-eye"></i>
                  </button>
                  <button @click="editReconciliation(reconciliation.reconciliation_id)" 
                          class="action-btn edit" 
                          :disabled="reconciliation.status === 'Finalized'"
                          title="Edit">
                    <i class="fas fa-edit"></i>
                  </button>
                  <button @click="manageLines(reconciliation.reconciliation_id)" 
                          class="action-btn lines" 
                          title="Manage Lines">
                    <i class="fas fa-list"></i>
                  </button>
                  <button @click="finalizeReconciliation(reconciliation)" 
                          class="action-btn finalize" 
                          v-if="reconciliation.status !== 'Finalized'"
                          title="Finalize">
                    <i class="fas fa-check"></i>
                  </button>
                  <button @click="deleteReconciliation(reconciliation.reconciliation_id)" 
                          class="action-btn delete" 
                          :disabled="reconciliation.status === 'Finalized'"
                          title="Delete">
                    <i class="fas fa-trash"></i>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Loading State -->
        <div v-if="loading" class="loading-container">
          <div class="loading-spinner">
            <i class="fas fa-spinner fa-spin"></i>
          </div>
          <p>Loading reconciliations...</p>
        </div>

        <!-- Empty State -->
        <div v-if="!loading && (!reconciliations.data || reconciliations.data.length === 0)" class="empty-state">
          <div class="empty-icon">
            <i class="fas fa-inbox"></i>
          </div>
          <h3>No Reconciliations Found</h3>
          <p>No bank reconciliations match your current filters.</p>
          <button @click="clearFilters" class="btn-primary">
            <i class="fas fa-refresh"></i>
            Clear Filters
          </button>
        </div>
      </div>

      <!-- Pagination -->
      <div class="pagination-section" v-if="reconciliations.total > 0">
        <div class="pagination-info">
          Showing {{ reconciliations.from }} to {{ reconciliations.to }} of {{ reconciliations.total }} results
        </div>
        <div class="pagination-controls">
          <button @click="changePage(reconciliations.current_page - 1)" 
                  :disabled="!reconciliations.prev_page_url" 
                  class="pagination-btn">
            <i class="fas fa-chevron-left"></i>
          </button>
          
          <span v-for="page in getVisiblePages()" :key="page" class="pagination-item">
            <button v-if="page !== '...'" 
                    @click="changePage(page)" 
                    :class="['pagination-btn', { active: page === reconciliations.current_page }]">
              {{ page }}
            </button>
            <span v-else class="pagination-ellipsis">...</span>
          </span>
          
          <button @click="changePage(reconciliations.current_page + 1)" 
                  :disabled="!reconciliations.next_page_url" 
                  class="pagination-btn">
            <i class="fas fa-chevron-right"></i>
          </button>
        </div>
      </div>
    </div>

    <!-- Finalize Confirmation Modal -->
    <div v-if="showFinalizeModal" class="modal-overlay" @click="closeFinalizeModal">
      <div class="modal-content finalize-modal" @click.stop>
        <div class="modal-header">
          <h3>
            <i class="fas fa-check-circle"></i>
            Finalize Reconciliation
          </h3>
          <button @click="closeFinalizeModal" class="modal-close">
            <i class="fas fa-times"></i>
          </button>
        </div>
        
        <div class="modal-body">
          <div class="warning-message" v-if="selectedReconciliation && Math.abs(selectedReconciliation.difference) > 0.01">
            <div class="warning-icon">
              <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="warning-content">
              <h4>Reconciliation is not balanced</h4>
              <p>The difference of {{ formatCurrency(selectedReconciliation.difference, selectedReconciliation.bank_account?.currency) }} must be resolved before finalizing.</p>
            </div>
          </div>
          
          <div class="reconciliation-summary" v-if="selectedReconciliation">
            <h4>Reconciliation Summary</h4>
            <div class="summary-grid">
              <div class="summary-item">
                <label>Statement Balance:</label>
                <span>{{ formatCurrency(selectedReconciliation.statement_balance, selectedReconciliation.bank_account?.currency) }}</span>
              </div>
              <div class="summary-item">
                <label>Book Balance:</label>
                <span>{{ formatCurrency(selectedReconciliation.book_balance, selectedReconciliation.bank_account?.currency) }}</span>
              </div>
              <div class="summary-item">
                <label>Difference:</label>
                <span :class="getDifferenceClass(selectedReconciliation.difference)">
                  {{ formatCurrency(selectedReconciliation.difference, selectedReconciliation.bank_account?.currency) }}
                </span>
              </div>
            </div>
          </div>
          
          <p v-if="selectedReconciliation && Math.abs(selectedReconciliation.difference) <= 0.01">
            Are you sure you want to finalize this reconciliation? This action cannot be undone.
          </p>
        </div>
        
        <div class="modal-footer">
          <button @click="closeFinalizeModal" class="btn-secondary">Cancel</button>
          <button @click="confirmFinalize" 
                  class="btn-primary" 
                  :disabled="!selectedReconciliation || Math.abs(selectedReconciliation.difference) > 0.01 || finalizing">
            <i class="fas fa-spinner fa-spin" v-if="finalizing"></i>
            <i class="fas fa-check" v-else></i>
            {{ finalizing ? 'Finalizing...' : 'Finalize' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div v-if="showDeleteModal" class="modal-overlay" @click="closeDeleteModal">
      <div class="modal-content delete-modal" @click.stop>
        <div class="modal-header">
          <h3>
            <i class="fas fa-trash"></i>
            Delete Reconciliation
          </h3>
          <button @click="closeDeleteModal" class="modal-close">
            <i class="fas fa-times"></i>
          </button>
        </div>
        
        <div class="modal-body">
          <p>Are you sure you want to delete this reconciliation? This action cannot be undone.</p>
        </div>
        
        <div class="modal-footer">
          <button @click="closeDeleteModal" class="btn-secondary">Cancel</button>
          <button @click="confirmDelete" class="btn-danger" :disabled="deleting">
            <i class="fas fa-spinner fa-spin" v-if="deleting"></i>
            <i class="fas fa-trash" v-else></i>
            {{ deleting ? 'Deleting...' : 'Delete' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import { debounce } from 'lodash'

export default {
  name: 'BankReconciliationList',
  data() {
    return {
      reconciliations: {
        data: [],
        total: 0,
        current_page: 1,
        last_page: 1,
        per_page: 15,
        from: 0,
        to: 0,
        prev_page_url: null,
        next_page_url: null
      },
      bankAccounts: [],
      availableCurrencies: ['USD', 'EUR', 'GBP', 'JPY', 'AUD', 'CAD', 'CHF', 'CNY', 'IDR'],
      summaryData: null,
      loading: false,
      searchQuery: '',
      filters: {
        bank_id: '',
        status: '',
        currency: '',
        display_currency: '',
        from_date: '',
        to_date: '',
        per_page: 15
      },
      sortField: 'statement_date',
      sortDirection: 'desc',
      showFinalizeModal: false,
      showDeleteModal: false,
      selectedReconciliation: null,
      finalizing: false,
      deleting: false,
      deleteId: null
    }
  },
  
  created() {
    this.loadBankAccounts()
    this.loadReconciliations()
    this.handleSearch = debounce(this.performSearch, 300)
  },
  
  methods: {
    async loadBankAccounts() {
      try {
        const response = await axios.get('/accounting/bank-accounts')
        this.bankAccounts = response.data.data || response.data
      } catch (error) {
        console.error('Error loading bank accounts:', error)
        this.$toast.error('Failed to load bank accounts')
      }
    },
    
    async loadReconciliations() {
      this.loading = true
      try {
        const params = {
          page: this.reconciliations.current_page,
          per_page: this.filters.per_page,
          sort_field: this.sortField,
          sort_direction: this.sortDirection,
          search: this.searchQuery,
          ...this.filters
        }
        
        // Remove empty filters
        Object.keys(params).forEach(key => {
          if (params[key] === '' || params[key] === null || params[key] === undefined) {
            delete params[key]
          }
        })
        
        const response = await axios.get('/accounting/bank-reconciliations', { params })
        this.reconciliations = response.data
        
        await this.loadSummaryData()
      } catch (error) {
        console.error('Error loading reconciliations:', error)
        this.$toast.error('Failed to load reconciliations')
      } finally {
        this.loading = false
      }
    },
    
    async loadSummaryData() {
      try {
        const params = { ...this.filters }
        Object.keys(params).forEach(key => {
          if (params[key] === '' || params[key] === null || params[key] === undefined) {
            delete params[key]
          }
        })
        
        const response = await axios.get('/accounting/bank-reconciliations/summary-report', { params })
        this.summaryData = response.data
      } catch (error) {
        console.error('Error loading summary:', error)
      }
    },
    
    applyFilters() {
      this.reconciliations.current_page = 1
      this.loadReconciliations()
    },
    
    clearFilters() {
      this.filters = {
        bank_id: '',
        status: '',
        currency: '',
        display_currency: '',
        from_date: '',
        to_date: '',
        per_page: 15
      }
      this.searchQuery = ''
      this.reconciliations.current_page = 1
      this.loadReconciliations()
    },
    
    performSearch() {
      this.reconciliations.current_page = 1
      this.loadReconciliations()
    },
    
    sort(field) {
      if (this.sortField === field) {
        this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc'
      } else {
        this.sortField = field
        this.sortDirection = 'asc'
      }
      this.loadReconciliations()
    },
    
    getSortIcon(field) {
      if (this.sortField !== field) return ''
      return this.sortDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down'
    },
    
    changePage(page) {
      if (page >= 1 && page <= this.reconciliations.last_page) {
        this.reconciliations.current_page = page
        this.loadReconciliations()
      }
    },
    
    getVisiblePages() {
      const current = this.reconciliations.current_page
      const last = this.reconciliations.last_page
      const pages = []
      
      // Always show first page
      if (current > 3) {
        pages.push(1)
        if (current > 4) pages.push('...')
      }
      
      // Show pages around current
      for (let i = Math.max(1, current - 2); i <= Math.min(last, current + 2); i++) {
        pages.push(i)
      }
      
      // Always show last page
      if (current < last - 2) {
        if (current < last - 3) pages.push('...')
        pages.push(last)
      }
      
      return pages
    },
    
    viewDetails(id) {
      this.$router.push(`/accounting/bank-reconciliations/${id}`)
    },
    
    editReconciliation(id) {
      this.$router.push(`/accounting/bank-reconciliations/${id}/edit`)
    },
    
    manageLines(id) {
      this.$router.push(`/accounting/bank-reconciliations/${id}/lines`)
    },
    
    finalizeReconciliation(reconciliation) {
      this.selectedReconciliation = reconciliation
      this.showFinalizeModal = true
    },
    
    closeFinalizeModal() {
      this.showFinalizeModal = false
      this.selectedReconciliation = null
      this.finalizing = false
    },
    
    async confirmFinalize() {
      if (!this.selectedReconciliation) return
      
      this.finalizing = true
      try {
        await axios.post(`/accounting/bank-reconciliations/${this.selectedReconciliation.reconciliation_id}/finalize`)
        
        this.$toast.success('Reconciliation finalized successfully')
        this.closeFinalizeModal()
        this.loadReconciliations()
      } catch (error) {
        console.error('Error finalizing reconciliation:', error)
        this.$toast.error(error.response?.data?.message || 'Failed to finalize reconciliation')
      } finally {
        this.finalizing = false
      }
    },
    
    deleteReconciliation(id) {
      this.deleteId = id
      this.showDeleteModal = true
    },
    
    closeDeleteModal() {
      this.showDeleteModal = false
      this.deleteId = null
      this.deleting = false
    },
    
    async confirmDelete() {
      if (!this.deleteId) return
      
      this.deleting = true
      try {
        await axios.delete(`/accounting/bank-reconciliations/${this.deleteId}`)
        
        this.$toast.success('Reconciliation deleted successfully')
        this.closeDeleteModal()
        this.loadReconciliations()
      } catch (error) {
        console.error('Error deleting reconciliation:', error)
        this.$toast.error(error.response?.data?.message || 'Failed to delete reconciliation')
      } finally {
        this.deleting = false
      }
    },
    
    async exportSummaryReport() {
      try {
        const params = { ...this.filters }
        Object.keys(params).forEach(key => {
          if (params[key] === '' || params[key] === null || params[key] === undefined) {
            delete params[key]
          }
        })
        
        const response = await axios.get('/accounting/bank-reconciliations/summary-report', { 
          params: { ...params, format: 'excel' },
          responseType: 'blob'
        })
        
        const url = window.URL.createObjectURL(new Blob([response.data]))
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', `bank-reconciliation-summary-${new Date().toISOString().split('T')[0]}.xlsx`)
        document.body.appendChild(link)
        link.click()
        link.remove()
        window.URL.revokeObjectURL(url)
        
        this.$toast.success('Report exported successfully')
      } catch (error) {
        console.error('Error exporting report:', error)
        this.$toast.error('Failed to export report')
      }
    },
    
    formatDate(date) {
      if (!date) return '-'
      return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      })
    },
    
    formatCurrency(amount, currency = 'USD') {
      if (amount === null || amount === undefined) return '-'
      return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: currency,
        minimumFractionDigits: 2
      }).format(amount)
    },
    
    getStatusClass(status) {
      const classes = {
        'Draft': 'status-draft',
        'In Progress': 'status-progress', 
        'Finalized': 'status-finalized'
      }
      return classes[status] || 'status-draft'
    },
    
    getDifferenceClass(difference) {
      if (Math.abs(difference) <= 0.01) return 'difference-zero'
      return difference > 0 ? 'difference-positive' : 'difference-negative'
    }
  }
}
</script>

<style scoped>
/* CSS Variables */
:root {
  --primary-color: #2563eb;
  --primary-dark: #1d4ed8;
  --success-color: #059669;
  --warning-color: #d97706;
  --danger-color: #dc2626;
  --gray-50: #f8fafc;
  --gray-100: #f1f5f9;
  --gray-200: #e2e8f0;
  --gray-300: #cbd5e1;
  --gray-400: #94a3b8;
  --gray-500: #64748b;
  --gray-600: #475569;
  --gray-700: #334155;
  --gray-800: #1e293b;
  --gray-900: #0f172a;
  --white: #ffffff;
  --border-radius: 12px;
  --box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
  --box-shadow-lg: 0 8px 32px rgba(0, 0, 0, 0.1);
  --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.bank-reconciliation-list {
  min-height: 100vh;
  background: var(--gray-50);
  padding: 2rem;
}

/* Page Header */
.page-header {
  background: var(--white);
  border-radius: var(--border-radius);
  padding: 2rem;
  margin-bottom: 2rem;
  box-shadow: var(--box-shadow);
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 2rem;
}

.title-section {
  flex: 1;
}

.page-title {
  font-size: 2rem;
  font-weight: 700;
  color: var(--gray-900);
  margin: 0 0 0.5rem 0;
  display: flex;
  align-items: center;
  gap: 1rem;
}

.page-title i {
  color: var(--primary-color);
}

.page-subtitle {
  color: var(--gray-600);
  font-size: 1rem;
  margin: 0;
}

.header-actions {
  display: flex;
  gap: 1rem;
}

/* Buttons */
.btn-primary, .btn-secondary, .btn-outline, .btn-danger {
  padding: 0.75rem 1.5rem;
  border-radius: var(--border-radius);
  font-weight: 600;
  font-size: 0.875rem;
  border: none;
  cursor: pointer;
  transition: var(--transition);
  display: flex;
  align-items: center;
  gap: 0.5rem;
  text-decoration: none;
}

.btn-primary {
  background: var(--primary-color);
  color: var(--white);
}

.btn-primary:hover:not(:disabled) {
  background: var(--primary-dark);
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(37, 99, 235, 0.3);
}

.btn-primary:disabled {
  background: var(--gray-300);
  color: var(--gray-500);
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}

.btn-secondary {
  background: var(--gray-200);
  color: var(--gray-700);
}

.btn-secondary:hover {
  background: var(--gray-300);
}

.btn-outline {
  background: transparent;
  color: var(--gray-700);
  border: 2px solid var(--gray-300);
}

.btn-outline:hover:not(:disabled) {
  background: var(--gray-100);
  border-color: var(--gray-400);
}

.btn-danger {
  background: var(--danger-color);
  color: var(--white);
}

.btn-danger:hover:not(:disabled) {
  background: #b91c1c;
}

/* Filters Section */
.filters-section {
  background: var(--white);
  border-radius: var(--border-radius);
  padding: 1.5rem;
  margin-bottom: 2rem;
  box-shadow: var(--box-shadow);
}

.filter-row {
  display: flex;
  gap: 1rem;
  align-items: end;
  margin-bottom: 1rem;
}

.filter-row:last-child {
  margin-bottom: 0;
}

.filter-group {
  flex: 1;
  min-width: 200px;
}

.filter-label {
  display: block;
  font-weight: 600;
  color: var(--gray-700);
  margin-bottom: 0.5rem;
  font-size: 0.875rem;
}

.filter-select, .filter-input {
  width: 100%;
  padding: 0.75rem;
  border: 2px solid var(--gray-300);
  border-radius: 8px;
  font-size: 0.875rem;
  transition: var(--transition);
}

.filter-select:focus, .filter-input:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.filter-actions {
  display: flex;
  gap: 0.5rem;
  flex-shrink: 0;
}

/* Summary Section */
.summary-section {
  margin-bottom: 2rem;
}

.summary-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
}

.summary-card {
  background: var(--white);
  border-radius: var(--border-radius);
  padding: 1.5rem;
  box-shadow: var(--box-shadow);
  display: flex;
  align-items: center;
  gap: 1rem;
  transition: var(--transition);
}

.summary-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--box-shadow-lg);
}

.summary-card.success {
  border-left: 4px solid var(--success-color);
}

.summary-card.warning {
  border-left: 4px solid var(--warning-color);
}

.card-icon {
  width: 3rem;
  height: 3rem;
  border-radius: 50%;
  background: var(--gray-100);
  color: var(--gray-600);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  flex-shrink: 0;
}

.summary-card.success .card-icon {
  background: rgba(5, 150, 105, 0.1);
  color: var(--success-color);
}

.summary-card.warning .card-icon {
  background: rgba(217, 119, 6, 0.1);
  color: var(--warning-color);
}

.card-content {
  flex: 1;
}

.card-value {
  font-size: 1.875rem;
  font-weight: 700;
  color: var(--gray-900);
  margin-bottom: 0.25rem;
}

.card-label {
  font-size: 0.875rem;
  color: var(--gray-600);
  font-weight: 500;
}

/* Table Section */
.table-section {
  background: var(--white);
  border-radius: var(--border-radius);
  box-shadow: var(--box-shadow);
  overflow: hidden;
}

.table-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem 2rem;
  border-bottom: 1px solid var(--gray-200);
  background: var(--gray-50);
}

.table-title h2 {
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--gray-900);
  margin: 0;
}

.record-count {
  font-size: 0.875rem;
  color: var(--gray-500);
  margin-left: 0.5rem;
}

.search-box {
  position: relative;
  width: 300px;
}

.search-box i {
  position: absolute;
  left: 1rem;
  top: 50%;
  transform: translateY(-50%);
  color: var(--gray-400);
}

.search-input {
  width: 100%;
  padding: 0.75rem 1rem 0.75rem 2.5rem;
  border: 2px solid var(--gray-300);
  border-radius: 8px;
  font-size: 0.875rem;
  transition: var(--transition);
}

.search-input:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.table-container {
  overflow-x: auto;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
}

.data-table th {
  background: var(--gray-50);
  padding: 1rem;
  text-align: left;
  font-weight: 600;
  color: var(--gray-700);
  border-bottom: 1px solid var(--gray-200);
  white-space: nowrap;
}

.data-table th.sortable {
  cursor: pointer;
  user-select: none;
  transition: var(--transition);
}

.data-table th.sortable:hover {
  background: var(--gray-100);
}

.data-table th i {
  margin-left: 0.5rem;
  opacity: 0.5;
}

.data-table td {
  padding: 1rem;
  border-bottom: 1px solid var(--gray-200);
  vertical-align: middle;
}

.table-row {
  cursor: pointer;
  transition: var(--transition);
}

.table-row:hover {
  background: var(--gray-50);
}

.date-cell {
  font-weight: 500;
  color: var(--gray-700);
}

.bank-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.bank-name {
  font-weight: 600;
  color: var(--gray-900);
}

.account-number {
  font-size: 0.875rem;
  color: var(--gray-500);
  font-family: monospace;
}

.currency-badge {
  background: var(--gray-100);
  color: var(--gray-700);
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-weight: 600;
  font-size: 0.75rem;
}

.amount-cell {
  font-weight: 600;
  text-align: right;
  font-family: monospace;
}

.amount-cell.difference-zero {
  color: var(--success-color);
}

.amount-cell.difference-positive {
  color: var(--success-color);
}

.amount-cell.difference-negative {
  color: var(--danger-color);
}

.exchange-rate {
  display: block;
  color: var(--gray-500);
  font-size: 0.75rem;
  margin-top: 0.25rem;
}

.status-badge {
  padding: 0.375rem 0.75rem;
  border-radius: 9999px;
  font-weight: 600;
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.025em;
}

.status-draft {
  background: rgba(107, 114, 128, 0.1);
  color: var(--gray-700);
}

.status-progress {
  background: rgba(217, 119, 6, 0.1);
  color: var(--warning-color);
}

.status-finalized {
  background: rgba(5, 150, 105, 0.1);
  color: var(--success-color);
}

.reconciler-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.reconciler-name {
  font-weight: 500;
  color: var(--gray-900);
}

.finalized-date {
  font-size: 0.75rem;
  color: var(--gray-500);
}

.text-muted {
  color: var(--gray-400);
}

.actions-cell {
  width: 1%;
  white-space: nowrap;
}

.action-buttons {
  display: flex;
  gap: 0.5rem;
}

.action-btn {
  width: 2rem;
  height: 2rem;
  border: none;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: var(--transition);
  font-size: 0.75rem;
}

.action-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.action-btn.view {
  background: rgba(37, 99, 235, 0.1);
  color: var(--primary-color);
}

.action-btn.edit {
  background: rgba(217, 119, 6, 0.1);
  color: var(--warning-color);
}

.action-btn.lines {
  background: rgba(107, 114, 128, 0.1);
  color: var(--gray-600);
}

.action-btn.finalize {
  background: rgba(5, 150, 105, 0.1);
  color: var(--success-color);
}

.action-btn.delete {
  background: rgba(220, 38, 38, 0.1);
  color: var(--danger-color);
}

.action-btn:hover:not(:disabled) {
  transform: scale(1.1);
}

/* Loading and Empty States */
.loading-container, .empty-state {
  padding: 4rem 2rem;
  text-align: center;
  color: var(--gray-500);
}

.loading-spinner {
  font-size: 2rem;
  color: var(--primary-color);
  margin-bottom: 1rem;
}

.empty-icon {
  font-size: 4rem;
  color: var(--gray-300);
  margin-bottom: 1rem;
}

.empty-state h3 {
  font-size: 1.25rem;
  color: var(--gray-700);
  margin-bottom: 0.5rem;
}

/* Pagination */
.pagination-section {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 2rem;
  border-top: 1px solid var(--gray-200);
  background: var(--gray-50);
}

.pagination-info {
  font-size: 0.875rem;
  color: var(--gray-600);
}

.pagination-controls {
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.pagination-btn {
  width: 2.5rem;
  height: 2.5rem;
  border: none;
  border-radius: 6px;
  background: var(--white);
  color: var(--gray-700);
  font-weight: 500;
  cursor: pointer;
  transition: var(--transition);
  display: flex;
  align-items: center;
  justify-content: center;
}

.pagination-btn:hover:not(:disabled) {
  background: var(--primary-color);
  color: var(--white);
}

.pagination-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.pagination-btn.active {
  background: var(--primary-color);
  color: var(--white);
}

.pagination-ellipsis {
  padding: 0 0.5rem;
  color: var(--gray-400);
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
  background: var(--white);
  border-radius: var(--border-radius);
  max-width: 500px;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: var(--box-shadow-lg);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-bottom: 1px solid var(--gray-200);
}

.modal-header h3 {
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--gray-900);
  margin: 0;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.modal-close {
  width: 2rem;
  height: 2rem;
  border: none;
  border-radius: 50%;
  background: var(--gray-100);
  color: var(--gray-500);
  cursor: pointer;
  transition: var(--transition);
  display: flex;
  align-items: center;
  justify-content: center;
}

.modal-close:hover {
  background: var(--gray-200);
  color: var(--gray-700);
}

.modal-body {
  padding: 1.5rem;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  padding: 1.5rem;
  border-top: 1px solid var(--gray-200);
}

.warning-message {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  padding: 1rem;
  background: rgba(220, 38, 38, 0.05);
  border: 1px solid rgba(220, 38, 38, 0.2);
  border-radius: 8px;
  margin-bottom: 1.5rem;
}

.warning-icon {
  color: var(--danger-color);
  font-size: 1.25rem;
  flex-shrink: 0;
}

.warning-content h4 {
  color: var(--danger-color);
  margin: 0 0 0.5rem 0;
  font-size: 1rem;
}

.warning-content p {
  color: var(--danger-color);
  margin: 0;
  font-size: 0.875rem;
}

.reconciliation-summary h4 {
  font-size: 1rem;
  font-weight: 600;
  color: var(--gray-900);
  margin: 0 0 1rem 0;
}

.summary-grid {
  display: grid;
  gap: 0.75rem;
}

.summary-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem;
  background: var(--gray-50);
  border-radius: 6px;
}

.summary-item label {
  font-weight: 500;
  color: var(--gray-700);
}

.summary-item span {
  font-weight: 600;
  font-family: monospace;
}

/* Responsive Design */
@media (max-width: 1200px) {
  .summary-cards {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .filter-row {
    flex-wrap: wrap;
  }
  
  .filter-group {
    min-width: 180px;
  }
}

@media (max-width: 768px) {
  .bank-reconciliation-list {
    padding: 1rem;
  }
  
  .header-content {
    flex-direction: column;
    align-items: stretch;
    gap: 1rem;
  }
  
  .header-actions {
    justify-content: center;
  }
  
  .summary-cards {
    grid-template-columns: 1fr;
  }
  
  .filter-row {
    flex-direction: column;
  }
  
  .filter-group {
    min-width: auto;
  }
  
  .table-header {
    flex-direction: column;
    gap: 1rem;
    align-items: stretch;
  }
  
  .search-box {
    width: 100%;
  }
  
  .pagination-section {
    flex-direction: column;
    gap: 1rem;
    align-items: center;
  }
  
  .action-buttons {
    flex-wrap: wrap;
  }
  
  .modal-content {
    margin: 0;
    min-height: 100vh;
    border-radius: 0;
  }
}
</style>