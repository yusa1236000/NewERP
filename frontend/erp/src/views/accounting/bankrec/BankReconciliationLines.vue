<template>
  <div class="bank-reconciliation-lines">
    <!-- Page Header -->
    <div class="page-header">
      <div class="header-content">
        <div class="title-section">
          <h1 class="page-title">
            <i class="fas fa-list-ul"></i>
            Reconciliation Lines Management
          </h1>
          <p class="page-subtitle" v-if="reconciliation.bank_account">
            {{ reconciliation.bank_account.bank_name }} - {{ reconciliation.bank_account.account_number }}
            <span class="statement-date">Statement Date: {{ formatDate(reconciliation.statement_date) }}</span>
          </p>
        </div>
        <div class="header-actions">
          <button @click="goBack" class="btn-secondary">
            <i class="fas fa-arrow-left"></i>
            Back to Detail
          </button>
          <button @click="addLine" 
                  class="btn-primary" 
                  v-if="reconciliation.status !== 'Finalized'">
            <i class="fas fa-plus"></i>
            Add Line
          </button>
        </div>
      </div>
    </div>

    <!-- Reconciliation Summary -->
    <div class="summary-section">
      <div class="summary-cards">
        <div class="summary-card">
          <div class="card-header">
            <h3>Statement Balance</h3>
            <i class="fas fa-university"></i>
          </div>
          <div class="card-value">
            {{ formatCurrency(reconciliation.statement_balance, reconciliation.bank_account?.currency) }}
          </div>
        </div>
        
        <div class="summary-card">
          <div class="card-header">
            <h3>Book Balance</h3>
            <i class="fas fa-book"></i>
          </div>
          <div class="card-value">
            {{ formatCurrency(reconciliation.book_balance, reconciliation.bank_account?.currency) }}
          </div>
        </div>
        
        <div class="summary-card">
          <div class="card-header">
            <h3>Initial Difference</h3>
            <i class="fas fa-balance-scale-right"></i>
          </div>
          <div class="card-value" :class="getDifferenceClass(reconciliation.difference)">
            {{ formatCurrency(reconciliation.difference, reconciliation.bank_account?.currency) }}
          </div>
        </div>
        
        <div class="summary-card" v-if="summary">
          <div class="card-header">
            <h3>Lines Total</h3>
            <i class="fas fa-calculator"></i>
          </div>
          <div class="card-value">
            {{ formatCurrency(summary.lines_total, reconciliation.bank_account?.currency) }}
          </div>
        </div>
        
        <div class="summary-card" :class="getAdjustedDifferenceClass()" v-if="summary">
          <div class="card-header">
            <h3>Adjusted Difference</h3>
            <i class="fas fa-equals"></i>
          </div>
          <div class="card-value">
            {{ formatCurrency(summary.adjusted_difference, reconciliation.bank_account?.currency) }}
          </div>
          <div class="balance-status">
            <span v-if="Math.abs(summary.adjusted_difference) <= 0.01" class="balanced">
              <i class="fas fa-check-circle"></i>
              Balanced
            </span>
            <span v-else class="unbalanced">
              <i class="fas fa-exclamation-triangle"></i>
              Unbalanced
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Lines Management Section -->
    <div class="lines-section">
      <div class="section-header">
        <div class="section-title">
          <h2>
            <i class="fas fa-list"></i>
            Reconciliation Lines
          </h2>
          <span class="lines-count">{{ reconciliationLines.length }} line(s)</span>
        </div>
        <div class="section-actions">
          <div class="view-toggle">
            <button @click="viewMode = 'table'" 
                    :class="['view-btn', { active: viewMode === 'table' }]"
                    title="Table View">
              <i class="fas fa-table"></i>
            </button>
            <button @click="viewMode = 'cards'" 
                    :class="['view-btn', { active: viewMode === 'cards' }]"
                    title="Card View">
              <i class="fas fa-th-large"></i>
            </button>
          </div>
          <button @click="refreshLines" class="btn-outline btn-sm">
            <i class="fas fa-refresh"></i>
            Refresh
          </button>
        </div>
      </div>

      <!-- Filter Tabs -->
      <div class="filter-tabs">
        <button @click="filterLines('all')" 
                :class="['filter-tab', { active: activeFilter === 'all' }]">
          All Lines ({{ reconciliationLines.length }})
        </button>
        <button @click="filterLines('outstanding_deposit')" 
                :class="['filter-tab', { active: activeFilter === 'outstanding_deposit' }]">
          Outstanding Deposits ({{ getLineCountByType('outstanding_deposit') }})
        </button>
        <button @click="filterLines('outstanding_check')" 
                :class="['filter-tab', { active: activeFilter === 'outstanding_check' }]">
          Outstanding Checks ({{ getLineCountByType('outstanding_check') }})
        </button>
        <button @click="filterLines('bank_charge')" 
                :class="['filter-tab', { active: activeFilter === 'bank_charge' }]">
          Bank Charges ({{ getLineCountByType('bank_charge') }})
        </button>
        <button @click="filterLines('bank_interest')" 
                :class="['filter-tab', { active: activeFilter === 'bank_interest' }]">
          Bank Interest ({{ getLineCountByType('bank_interest') }})
        </button>
        <button @click="filterLines('adjustment')" 
                :class="['filter-tab', { active: activeFilter === 'adjustment' }]">
          Adjustments ({{ getLineCountByType('adjustment') }})
        </button>
      </div>

      <!-- Table View -->
      <div v-if="viewMode === 'table'" class="table-container">
        <table class="lines-table" v-if="filteredLines.length > 0">
          <thead>
            <tr>
              <th @click="sort('line_type')" class="sortable">
                Type
                <i class="fas fa-sort" :class="getSortIcon('line_type')"></i>
              </th>
              <th @click="sort('description')" class="sortable">
                Description
                <i class="fas fa-sort" :class="getSortIcon('description')"></i>
              </th>
              <th @click="sort('amount')" class="sortable">
                Amount ({{ reconciliation.bank_account?.currency || 'USD' }})
                <i class="fas fa-sort" :class="getSortIcon('amount')"></i>
              </th>
              <th v-if="reconciliation.bank_account?.currency !== baseCurrency">
                {{ baseCurrency }} Amount
              </th>
              <th>Reference</th>
              <th @click="sort('transaction_date')" class="sortable">
                Transaction Date
                <i class="fas fa-sort" :class="getSortIcon('transaction_date')"></i>
              </th>
              <th v-if="reconciliation.status !== 'Finalized'">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="line in sortedFilteredLines" :key="line.line_id" class="line-row">
              <td>
                <span class="line-type-badge" :class="getLineTypeClass(line.line_type)">
                  {{ getLineTypeLabel(line.line_type) }}
                </span>
              </td>
              <td class="description-cell">
                <div class="description-content">
                  <span class="description-text">{{ line.description }}</span>
                  <small class="line-id">ID: {{ line.line_id }}</small>
                </div>
              </td>
              <td class="amount-cell" :class="getAmountClass(line.line_type, line.amount)">
                {{ formatCurrency(line.amount, line.currency || reconciliation.bank_account?.currency) }}
              </td>
              <td v-if="reconciliation.bank_account?.currency !== baseCurrency" class="amount-cell">
                {{ formatCurrency(line.base_currency_amount, baseCurrency) }}
                <small class="exchange-rate" v-if="line.exchange_rate">
                  (Rate: {{ line.exchange_rate?.toFixed(4) }})
                </small>
              </td>
              <td class="reference-cell">
                <span v-if="line.reference_number" class="reference-text">{{ line.reference_number }}</span>
                <span v-else class="no-reference">-</span>
              </td>
              <td class="date-cell">
                {{ formatDate(line.transaction_date) }}
              </td>
              <td v-if="reconciliation.status !== 'Finalized'" class="actions-cell">
                <div class="line-actions">
                  <button @click="editLine(line)" 
                          class="action-btn edit" 
                          title="Edit Line">
                    <i class="fas fa-edit"></i>
                  </button>
                  <button @click="duplicateLine(line)" 
                          class="action-btn duplicate" 
                          title="Duplicate Line">
                    <i class="fas fa-copy"></i>
                  </button>
                  <button @click="deleteLine(line)" 
                          class="action-btn delete" 
                          title="Delete Line">
                    <i class="fas fa-trash"></i>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Empty State for Table -->
        <div v-else class="empty-state">
          <div class="empty-icon">
            <i class="fas fa-list-ul"></i>
          </div>
          <h3>No Lines Found</h3>
          <p v-if="activeFilter !== 'all'">
            No {{ getLineTypeLabel(activeFilter).toLowerCase() }} lines found.
          </p>
          <p v-else>
            No reconciliation lines have been added yet.
          </p>
          <button @click="addLine" 
                  class="btn-primary" 
                  v-if="reconciliation.status !== 'Finalized'">
            <i class="fas fa-plus"></i>
            Add First Line
          </button>
        </div>
      </div>

      <!-- Card View -->
      <div v-if="viewMode === 'cards'" class="cards-container">
        <div v-if="filteredLines.length > 0" class="lines-grid">
          <div v-for="line in sortedFilteredLines" :key="line.line_id" class="line-card">
            <div class="card-header">
              <span class="line-type-badge" :class="getLineTypeClass(line.line_type)">
                {{ getLineTypeLabel(line.line_type) }}
              </span>
              <div class="card-actions" v-if="reconciliation.status !== 'Finalized'">
                <button @click="editLine(line)" class="action-btn edit" title="Edit">
                  <i class="fas fa-edit"></i>
                </button>
                <button @click="duplicateLine(line)" class="action-btn duplicate" title="Duplicate">
                  <i class="fas fa-copy"></i>
                </button>
                <button @click="deleteLine(line)" class="action-btn delete" title="Delete">
                  <i class="fas fa-trash"></i>
                </button>
              </div>
            </div>
            
            <div class="card-content">
              <h3 class="line-description">{{ line.description }}</h3>
              
              <div class="line-details">
                <div class="detail-row">
                  <label>Amount:</label>
                  <span class="amount" :class="getAmountClass(line.line_type, line.amount)">
                    {{ formatCurrency(line.amount, line.currency || reconciliation.bank_account?.currency) }}
                  </span>
                </div>
                
                <div class="detail-row" v-if="reconciliation.bank_account?.currency !== baseCurrency">
                  <label>{{ baseCurrency }} Amount:</label>
                  <span class="amount">{{ formatCurrency(line.base_currency_amount, baseCurrency) }}</span>
                </div>
                
                <div class="detail-row" v-if="line.reference_number">
                  <label>Reference:</label>
                  <span class="reference">{{ line.reference_number }}</span>
                </div>
                
                <div class="detail-row">
                  <label>Date:</label>
                  <span class="date">{{ formatDate(line.transaction_date) }}</span>
                </div>
                
                <div class="detail-row" v-if="line.exchange_rate && reconciliation.bank_account?.currency !== baseCurrency">
                  <label>Exchange Rate:</label>
                  <span class="rate">{{ line.exchange_rate?.toFixed(4) }}</span>
                </div>
              </div>
            </div>
            
            <div class="card-footer">
              <small class="line-id">Line ID: {{ line.line_id }}</small>
            </div>
          </div>
        </div>

        <!-- Empty State for Cards -->
        <div v-else class="empty-state">
          <div class="empty-icon">
            <i class="fas fa-th-large"></i>
          </div>
          <h3>No Lines Found</h3>
          <p v-if="activeFilter !== 'all'">
            No {{ getLineTypeLabel(activeFilter).toLowerCase() }} lines found.
          </p>
          <p v-else>
            No reconciliation lines have been added yet.
          </p>
          <button @click="addLine" 
                  class="btn-primary" 
                  v-if="reconciliation.status !== 'Finalized'">
            <i class="fas fa-plus"></i>
            Add First Line
          </button>
        </div>
      </div>
    </div>

    <!-- Add/Edit Line Modal -->
    <div v-if="showLineModal" class="modal-overlay" @click="closeLineModal">
      <div class="modal-content line-modal" @click.stop>
        <div class="modal-header">
          <h3>
            <i class="fas fa-plus" v-if="!editingLine"></i>
            <i class="fas fa-edit" v-else></i>
            {{ editingLine ? 'Edit' : 'Add' }} Reconciliation Line
          </h3>
          <button @click="closeLineModal" class="modal-close">
            <i class="fas fa-times"></i>
          </button>
        </div>
        
        <div class="modal-body">
          <form @submit.prevent="saveLine" class="line-form">
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Line Type *</label>
                <select v-model="lineForm.line_type" class="form-select" required>
                  <option value="">Select Type</option>
                  <option value="outstanding_deposit">Outstanding Deposit</option>
                  <option value="outstanding_check">Outstanding Check</option>
                  <option value="bank_charge">Bank Charge</option>
                  <option value="bank_interest">Bank Interest</option>
                  <option value="adjustment">Adjustment</option>
                </select>
              </div>
              
              <div class="form-group">
                <label class="form-label">Amount ({{ reconciliation.bank_account?.currency || 'USD' }}) *</label>
                <input type="number" 
                       v-model.number="lineForm.amount" 
                       class="form-input" 
                       step="0.01" 
                       required>
              </div>
            </div>
            
            <div class="form-group">
              <label class="form-label">Description *</label>
              <input type="text" 
                     v-model="lineForm.description" 
                     class="form-input" 
                     placeholder="Enter line description"
                     required>
            </div>
            
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Reference Number</label>
                <input type="text" 
                       v-model="lineForm.reference_number" 
                       class="form-input" 
                       placeholder="Optional reference">
              </div>
              
              <div class="form-group">
                <label class="form-label">Transaction Date</label>
                <input type="date" 
                       v-model="lineForm.transaction_date" 
                       class="form-input">
              </div>
            </div>
            
            <!-- Quick Amount Buttons -->
            <div class="quick-amounts" v-if="lineForm.line_type">
              <label class="form-label">Quick Amounts:</label>
              <div class="amount-buttons">
                <button type="button" @click="setQuickAmount(100)" class="amount-btn">$100</button>
                <button type="button" @click="setQuickAmount(500)" class="amount-btn">$500</button>
                <button type="button" @click="setQuickAmount(1000)" class="amount-btn">$1,000</button>
                <button type="button" @click="setQuickAmount(5000)" class="amount-btn">$5,000</button>
              </div>
            </div>
            
            <!-- Base Currency Preview -->
            <div class="base-currency-preview" v-if="reconciliation.bank_account?.currency !== baseCurrency && lineForm.amount">
              <div class="preview-header">
                <h4>Base Currency Preview ({{ baseCurrency }})</h4>
              </div>
              <div class="preview-content">
                <div class="preview-item">
                  <label>Amount:</label>
                  <span>{{ formatCurrency(lineForm.amount * (reconciliation.exchange_rate || 1), baseCurrency) }}</span>
                </div>
                <div class="preview-item" v-if="reconciliation.exchange_rate">
                  <label>Exchange Rate:</label>
                  <span>{{ reconciliation.exchange_rate?.toFixed(4) }}</span>
                </div>
              </div>
            </div>
          </form>
          
          <!-- Validation Errors -->
          <div class="validation-errors" v-if="lineErrors.length > 0">
            <div class="validation-header">
              <i class="fas fa-exclamation-triangle"></i>
              <h4>Please fix the following errors:</h4>
            </div>
            <ul class="validation-list">
              <li v-for="error in lineErrors" :key="error">{{ error }}</li>
            </ul>
          </div>
        </div>
        
        <div class="modal-footer">
          <button @click="closeLineModal" type="button" class="btn-secondary">Cancel</button>
          <button @click="saveLine" type="button" class="btn-primary" :disabled="savingLine">
            <i class="fas fa-spinner fa-spin" v-if="savingLine"></i>
            <i class="fas fa-save" v-else></i>
            {{ savingLine ? 'Saving...' : 'Save' }}
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
            Delete Line
          </h3>
          <button @click="closeDeleteModal" class="modal-close">
            <i class="fas fa-times"></i>
          </button>
        </div>
        
        <div class="modal-body">
          <p>Are you sure you want to delete this reconciliation line? This action cannot be undone.</p>
          <div class="line-preview" v-if="deletingLine">
            <div class="preview-type">
              <span class="line-type-badge" :class="getLineTypeClass(deletingLine.line_type)">
                {{ getLineTypeLabel(deletingLine.line_type) }}
              </span>
            </div>
            <div class="preview-details">
              <strong>{{ deletingLine.description }}</strong><br>
              <span>{{ formatCurrency(deletingLine.amount, deletingLine.currency || reconciliation.bank_account?.currency) }}</span>
            </div>
          </div>
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

    <!-- Loading Overlay -->
    <div v-if="loading" class="loading-overlay">
      <div class="loading-content">
        <div class="loading-spinner">
          <i class="fas fa-spinner fa-spin"></i>
        </div>
        <p>{{ loadingMessage }}</p>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'BankReconciliationLines',
  data() {
    return {
      reconciliation: {},
      reconciliationLines: [],
      summary: null,
      loading: false,
      loadingMessage: 'Loading...',
      baseCurrency: 'USD',
      viewMode: 'table', // 'table' or 'cards'
      activeFilter: 'all',
      sortField: 'line_id',
      sortDirection: 'asc',
      
      // Line Modal
      showLineModal: false,
      editingLine: null,
      lineForm: {
        line_type: '',
        description: '',
        amount: null,
        reference_number: '',
        transaction_date: ''
      },
      lineErrors: [],
      savingLine: false,
      
      // Delete Modal
      showDeleteModal: false,
      deletingLine: null,
      deleting: false
    }
  },
  
  computed: {
    reconciliationId() {
      return this.$route.params.id
    },
    
    filteredLines() {
      if (this.activeFilter === 'all') {
        return this.reconciliationLines
      }
      return this.reconciliationLines.filter(line => line.line_type === this.activeFilter)
    },
    
    sortedFilteredLines() {
      if (!this.filteredLines.length) return []
      
      return [...this.filteredLines].sort((a, b) => {
        let aVal = a[this.sortField]
        let bVal = b[this.sortField]
        
        // Handle null/undefined values
        if (aVal === null || aVal === undefined) aVal = ''
        if (bVal === null || bVal === undefined) bVal = ''
        
        // Convert to string for comparison
        aVal = String(aVal).toLowerCase()
        bVal = String(bVal).toLowerCase()
        
        if (this.sortDirection === 'asc') {
          return aVal.localeCompare(bVal)
        } else {
          return bVal.localeCompare(aVal)
        }
      })
    }
  },
  
  created() {
    this.loadReconciliation()
    this.loadBaseCurrency()
  },
  
  methods: {
    async loadBaseCurrency() {
      try {
        this.baseCurrency = 'USD' // Should be configurable
      } catch (error) {
        console.error('Error loading base currency:', error)
      }
    },
    
    async loadReconciliation() {
      this.loading = true
      this.loadingMessage = 'Loading reconciliation...'
      try {
        const response = await axios.get(`/accounting/bank-reconciliations/${this.reconciliationId}`)
        this.reconciliation = response.data.data
        this.summary = response.data.summary
        
        await this.loadReconciliationLines()
      } catch (error) {
        console.error('Error loading reconciliation:', error)
        this.$toast.error('Failed to load reconciliation')
        this.goBack()
      } finally {
        this.loading = false
      }
    },
    
    async loadReconciliationLines() {
      this.loadingMessage = 'Loading lines...'
      try {
        const response = await axios.get(`/accounting/bank-reconciliations/${this.reconciliationId}/lines`)
        this.reconciliationLines = response.data.data || []
      } catch (error) {
        console.error('Error loading lines:', error)
        this.$toast.error('Failed to load reconciliation lines')
      }
    },
    
    async refreshLines() {
      await this.loadReconciliationLines()
      await this.refreshSummary()
      this.$toast.success('Lines refreshed')
    },
    
    async refreshSummary() {
      try {
        const response = await axios.get(`/accounting/bank-reconciliations/${this.reconciliationId}`)
        this.summary = response.data.summary
      } catch (error) {
        console.error('Error refreshing summary:', error)
      }
    },
    
    filterLines(type) {
      this.activeFilter = type
    },
    
    getLineCountByType(type) {
      return this.reconciliationLines.filter(line => line.line_type === type).length
    },
    
    sort(field) {
      if (this.sortField === field) {
        this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc'
      } else {
        this.sortField = field
        this.sortDirection = 'asc'
      }
    },
    
    getSortIcon(field) {
      if (this.sortField !== field) return ''
      return this.sortDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down'
    },
    
    addLine() {
      this.editingLine = null
      this.lineForm = {
        line_type: '',
        description: '',
        amount: null,
        reference_number: '',
        transaction_date: this.reconciliation.statement_date || ''
      }
      this.lineErrors = []
      this.showLineModal = true
    },
    
    editLine(line) {
      this.editingLine = line
      this.lineForm = {
        line_type: line.line_type,
        description: line.description,
        amount: line.amount,
        reference_number: line.reference_number || '',
        transaction_date: line.transaction_date || ''
      }
      this.lineErrors = []
      this.showLineModal = true
    },
    
    duplicateLine(line) {
      this.editingLine = null
      this.lineForm = {
        line_type: line.line_type,
        description: `Copy of ${line.description}`,
        amount: line.amount,
        reference_number: line.reference_number || '',
        transaction_date: line.transaction_date || ''
      }
      this.lineErrors = []
      this.showLineModal = true
    },
    
    closeLineModal() {
      this.showLineModal = false
      this.editingLine = null
      this.lineForm = {
        line_type: '',
        description: '',
        amount: null,
        reference_number: '',
        transaction_date: ''
      }
      this.lineErrors = []
      this.savingLine = false
    },
    
    setQuickAmount(amount) {
      this.lineForm.amount = amount
    },
    
    async saveLine() {
      this.lineErrors = []
      
      // Validation
      if (!this.lineForm.line_type) {
        this.lineErrors.push('Line type is required')
      }
      if (!this.lineForm.description) {
        this.lineErrors.push('Description is required')
      }
      if (!this.lineForm.amount || this.lineForm.amount === 0) {
        this.lineErrors.push('Amount is required and must not be zero')
      }
      
      if (this.lineErrors.length > 0) return
      
      this.savingLine = true
      try {
        if (this.editingLine) {
          await axios.put(
            `/accounting/bank-reconciliations/${this.reconciliationId}/lines/${this.editingLine.line_id}`,
            this.lineForm
          )
          this.$toast.success('Line updated successfully')
        } else {
          await axios.post(
            `/accounting/bank-reconciliations/${this.reconciliationId}/lines`,
            this.lineForm
          )
          this.$toast.success('Line added successfully')
        }
        
        this.closeLineModal()
        await this.loadReconciliationLines()
        await this.refreshSummary()
      } catch (error) {
        console.error('Error saving line:', error)
        if (error.response?.data?.errors) {
          this.lineErrors = Object.values(error.response.data.errors).flat()
        } else {
          this.$toast.error(error.response?.data?.message || 'Failed to save line')
        }
      } finally {
        this.savingLine = false
      }
    },
    
    deleteLine(line) {
      this.deletingLine = line
      this.showDeleteModal = true
    },
    
    closeDeleteModal() {
      this.showDeleteModal = false
      this.deletingLine = null
      this.deleting = false
    },
    
    async confirmDelete() {
      if (!this.deletingLine) return
      
      this.deleting = true
      try {
        await axios.delete(`/accounting/bank-reconciliations/${this.reconciliationId}/lines/${this.deletingLine.line_id}`)
        
        this.$toast.success('Line deleted successfully')
        this.closeDeleteModal()
        await this.loadReconciliationLines()
        await this.refreshSummary()
      } catch (error) {
        console.error('Error deleting line:', error)
        this.$toast.error(error.response?.data?.message || 'Failed to delete line')
      } finally {
        this.deleting = false
      }
    },
    
    goBack() {
      this.$router.push(`/accounting/bank-reconciliations/${this.reconciliationId}`)
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
    
    getDifferenceClass(difference) {
      if (Math.abs(difference) <= 0.01) return 'balanced'
      return difference > 0 ? 'positive' : 'negative'
    },
    
    getAdjustedDifferenceClass() {
      if (!this.summary) return ''
      if (Math.abs(this.summary.adjusted_difference) <= 0.01) return 'balanced'
      return this.summary.adjusted_difference > 0 ? 'positive' : 'negative'
    },
    
    getLineTypeClass(type) {
      const classes = {
        'outstanding_deposit': 'type-deposit',
        'outstanding_check': 'type-check',
        'bank_charge': 'type-charge',
        'bank_interest': 'type-interest',
        'adjustment': 'type-adjustment'
      }
      return classes[type] || 'type-default'
    },
    
    getLineTypeLabel(type) {
      const labels = {
        'outstanding_deposit': 'Outstanding Deposit',
        'outstanding_check': 'Outstanding Check',
        'bank_charge': 'Bank Charge',
        'bank_interest': 'Bank Interest',
        'adjustment': 'Adjustment'
      }
      return labels[type] || type
    },
    
    getAmountClass(lineType, amount) {
      if (lineType === 'outstanding_deposit' || lineType === 'bank_interest') {
        return 'amount-positive'
      } else if (lineType === 'outstanding_check' || lineType === 'bank_charge') {
        return 'amount-negative'
      } else {
        return amount >= 0 ? 'amount-positive' : 'amount-negative'
      }
    }
  }
}
</script>

<style scoped>
/* Inherit all base styles and add specific styles for lines management */

.bank-reconciliation-lines {
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
  font-size: 0.875rem;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.statement-date {
  font-weight: 500;
  color: var(--gray-700);
}

.header-actions {
  display: flex;
  gap: 1rem;
  flex-shrink: 0;
}

/* Summary Section */
.summary-section {
  margin-bottom: 2rem;
}

.summary-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1rem;
}

.summary-card {
  background: var(--white);
  border-radius: var(--border-radius);
  padding: 1.5rem;
  box-shadow: var(--box-shadow);
  border-left: 4px solid var(--gray-300);
  transition: var(--transition);
}

.summary-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--box-shadow-lg);
}

.summary-card.balanced {
  border-left-color: var(--success-color);
}

.summary-card.positive {
  border-left-color: var(--success-color);
}

.summary-card.negative {
  border-left-color: var(--danger-color);
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.card-header h3 {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--gray-700);
  margin: 0;
  text-transform: uppercase;
  letter-spacing: 0.025em;
}

.card-header i {
  color: var(--gray-400);
  font-size: 1.125rem;
}

.card-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--gray-900);
  font-family: monospace;
  margin-bottom: 0.5rem;
}

.card-value.balanced {
  color: var(--success-color);
}

.card-value.positive {
  color: var(--success-color);
}

.card-value.negative {
  color: var(--danger-color);
}

.balance-status {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.025em;
}

.balance-status .balanced {
  color: var(--success-color);
}

.balance-status .unbalanced {
  color: var(--danger-color);
}

/* Lines Section */
.lines-section {
  background: var(--white);
  border-radius: var(--border-radius);
  padding: 2rem;
  box-shadow: var(--box-shadow);
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  padding-bottom: 1rem;
  border-bottom: 2px solid var(--gray-200);
}

.section-title {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.section-title h2 {
  font-size: 1.5rem;
  font-weight: 600;
  color: var(--gray-900);
  margin: 0;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.section-title h2 i {
  color: var(--primary-color);
}

.lines-count {
  background: var(--gray-100);
  color: var(--gray-600);
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.875rem;
  font-weight: 500;
}

.section-actions {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.view-toggle {
  display: flex;
  gap: 0.25rem;
  background: var(--gray-100);
  border-radius: 8px;
  padding: 0.25rem;
}

.view-btn {
  width: 2.5rem;
  height: 2.5rem;
  border: none;
  border-radius: 6px;
  background: transparent;
  color: var(--gray-600);
  cursor: pointer;
  transition: var(--transition);
  display: flex;
  align-items: center;
  justify-content: center;
}

.view-btn:hover {
  background: var(--gray-200);
}

.view-btn.active {
  background: var(--primary-color);
  color: var(--white);
}

.btn-sm {
  padding: 0.5rem 1rem;
  font-size: 0.75rem;
}

/* Filter Tabs */
.filter-tabs {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 2rem;
  flex-wrap: wrap;
}

.filter-tab {
  padding: 0.5rem 1rem;
  border: 2px solid var(--gray-300);
  border-radius: 8px;
  background: var(--white);
  color: var(--gray-700);
  font-weight: 500;
  cursor: pointer;
  transition: var(--transition);
  font-size: 0.875rem;
}

.filter-tab:hover {
  border-color: var(--primary-color);
  background: rgba(37, 99, 235, 0.05);
}

.filter-tab.active {
  border-color: var(--primary-color);
  background: var(--primary-color);
  color: var(--white);
}

/* Table View */
.table-container {
  overflow-x: auto;
}

.lines-table {
  width: 100%;
  border-collapse: collapse;
}

.lines-table th {
  background: var(--gray-50);
  padding: 1rem;
  text-align: left;
  font-weight: 600;
  color: var(--gray-700);
  border-bottom: 2px solid var(--gray-200);
  white-space: nowrap;
}

.lines-table th.sortable {
  cursor: pointer;
  user-select: none;
  transition: var(--transition);
}

.lines-table th.sortable:hover {
  background: var(--gray-100);
}

.lines-table th i {
  margin-left: 0.5rem;
  opacity: 0.5;
}

.lines-table td {
  padding: 1rem;
  border-bottom: 1px solid var(--gray-200);
  vertical-align: middle;
}

.line-row {
  transition: var(--transition);
}

.line-row:hover {
  background: var(--gray-50);
}

.line-type-badge {
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-weight: 600;
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.025em;
}

.line-type-badge.type-deposit {
  background: rgba(5, 150, 105, 0.1);
  color: var(--success-color);
}

.line-type-badge.type-check {
  background: rgba(217, 119, 6, 0.1);
  color: var(--warning-color);
}

.line-type-badge.type-charge {
  background: rgba(220, 38, 38, 0.1);
  color: var(--danger-color);
}

.line-type-badge.type-interest {
  background: rgba(37, 99, 235, 0.1);
  color: var(--primary-color);
}

.line-type-badge.type-adjustment {
  background: rgba(107, 114, 128, 0.1);
  color: var(--gray-600);
}

.description-cell {
  max-width: 300px;
}

.description-content {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.description-text {
  font-weight: 500;
  color: var(--gray-900);
  word-break: break-word;
}

.line-id {
  font-size: 0.75rem;
  color: var(--gray-500);
  font-family: monospace;
}

.amount-cell {
  text-align: right;
  font-family: monospace;
  font-weight: 600;
}

.amount-cell.amount-positive {
  color: var(--success-color);
}

.amount-cell.amount-negative {
  color: var(--danger-color);
}

.exchange-rate {
  display: block;
  color: var(--gray-500);
  font-size: 0.75rem;
  margin-top: 0.25rem;
}

.reference-cell {
  font-family: monospace;
}

.reference-text {
  color: var(--gray-700);
  font-weight: 500;
}

.no-reference {
  color: var(--gray-400);
  font-style: italic;
}

.date-cell {
  color: var(--gray-700);
  white-space: nowrap;
}

.actions-cell {
  width: 1%;
  white-space: nowrap;
}

.line-actions {
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

.action-btn.edit {
  background: rgba(217, 119, 6, 0.1);
  color: var(--warning-color);
}

.action-btn.duplicate {
  background: rgba(37, 99, 235, 0.1);
  color: var(--primary-color);
}

.action-btn.delete {
  background: rgba(220, 38, 38, 0.1);
  color: var(--danger-color);
}

.action-btn:hover {
  transform: scale(1.1);
}

/* Card View */
.cards-container {
  margin-top: 1rem;
}

.lines-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 1.5rem;
}

.line-card {
  border: 2px solid var(--gray-200);
  border-radius: var(--border-radius);
  padding: 1.5rem;
  background: var(--white);
  transition: var(--transition);
}

.line-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--box-shadow-lg);
  border-color: var(--primary-color);
}

.line-card .card-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1rem;
}

.line-card .card-actions {
  display: flex;
  gap: 0.5rem;
}

.card-content {
  margin-bottom: 1rem;
}

.line-description {
  font-size: 1.125rem;
  font-weight: 600;
  color: var(--gray-900);
  margin: 0 0 1rem 0;
  word-break: break-word;
}

.line-details {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.detail-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.detail-row label {
  font-weight: 500;
  color: var(--gray-600);
  font-size: 0.875rem;
}

.detail-row .amount {
  font-weight: 700;
  font-family: monospace;
}

.detail-row .amount.amount-positive {
  color: var(--success-color);
}

.detail-row .amount.amount-negative {
  color: var(--danger-color);
}

.detail-row .reference,
.detail-row .date,
.detail-row .rate {
  font-family: monospace;
  color: var(--gray-700);
}

.card-footer {
  border-top: 1px solid var(--gray-200);
  padding-top: 1rem;
  text-align: center;
}

.card-footer .line-id {
  font-size: 0.75rem;
  color: var(--gray-500);
  font-family: monospace;
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 4rem 2rem;
  color: var(--gray-500);
}

.empty-state .empty-icon {
  font-size: 4rem;
  color: var(--gray-300);
  margin-bottom: 1rem;
}

.empty-state h3 {
  font-size: 1.25rem;
  color: var(--gray-700);
  margin-bottom: 0.5rem;
}

/* Line Modal */
.line-modal {
  max-width: 700px;
  width: 100%;
}

.line-form .form-row {
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

.form-label {
  font-weight: 600;
  color: var(--gray-700);
  font-size: 0.875rem;
}

.form-input, .form-select {
  padding: 0.75rem;
  border: 2px solid var(--gray-300);
  border-radius: 8px;
  font-size: 0.875rem;
  transition: var(--transition);
}

.form-input:focus, .form-select:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.quick-amounts {
  margin: 1rem 0;
}

.amount-buttons {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.amount-btn {
  padding: 0.5rem 1rem;
  border: 2px solid var(--gray-300);
  border-radius: 6px;
  background: var(--white);
  color: var(--gray-700);
  font-weight: 500;
  cursor: pointer;
  transition: var(--transition);
  font-size: 0.875rem;
}

.amount-btn:hover {
  border-color: var(--primary-color);
  background: rgba(37, 99, 235, 0.05);
}

.base-currency-preview {
  background: var(--gray-50);
  border-radius: 8px;
  padding: 1rem;
  margin-top: 1rem;
}

.preview-header h4 {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--gray-700);
  margin: 0 0 0.75rem 0;
  text-transform: uppercase;
  letter-spacing: 0.025em;
}

.preview-content {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.preview-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.preview-item label {
  font-weight: 500;
  color: var(--gray-600);
  font-size: 0.875rem;
}

.preview-item span {
  font-weight: 600;
  color: var(--gray-900);
  font-family: monospace;
}

/* Delete Modal */
.line-preview {
  background: var(--gray-50);
  border-radius: 8px;
  padding: 1rem;
  margin-top: 1rem;
  display: flex;
  align-items: center;
  gap: 1rem;
}

.preview-type {
  flex-shrink: 0;
}

.preview-details {
  flex: 1;
}

/* Validation Errors */
.validation-errors {
  background: rgba(220, 38, 38, 0.05);
  border: 1px solid rgba(220, 38, 38, 0.2);
  border-radius: 8px;
  padding: 1rem;
  margin-top: 1rem;
}

.validation-header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.5rem;
}

.validation-header i {
  color: var(--danger-color);
}

.validation-header h4 {
  color: var(--danger-color);
  margin: 0;
  font-size: 0.875rem;
}

.validation-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

.validation-list li {
  color: var(--danger-color);
  font-size: 0.875rem;
  margin-bottom: 0.25rem;
}

.validation-list li::before {
  content: '•';
  margin-right: 0.5rem;
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

/* Loading Overlay */
.loading-overlay {
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

.loading-content {
  background: var(--white);
  border-radius: var(--border-radius);
  padding: 2rem;
  text-align: center;
  box-shadow: var(--box-shadow-lg);
}

.loading-spinner {
  font-size: 2rem;
  color: var(--primary-color);
  margin-bottom: 1rem;
}

.loading-content p {
  color: var(--gray-600);
  margin: 0;
}

/* Responsive Design */
@media (max-width: 1200px) {
  .summary-cards {
    grid-template-columns: repeat(3, 1fr);
  }
  
  .lines-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 768px) {
  .bank-reconciliation-lines {
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
  
  .section-header {
    flex-direction: column;
    align-items: stretch;
    gap: 1rem;
  }
  
  .section-title {
    justify-content: center;
  }
  
  .section-actions {
    justify-content: center;
  }
  
  .filter-tabs {
    flex-direction: column;
  }
  
  .lines-grid {
    grid-template-columns: 1fr;
  }
  
  .line-form .form-row {
    grid-template-columns: 1fr;
  }
  
  .amount-buttons {
    justify-content: center;
  }
  
  .detail-row {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.25rem;
  }
  
  .line-preview {
    flex-direction: column;
    text-align: center;
  }
  
  .modal-content {
    margin: 0;
    min-height: 100vh;
    border-radius: 0;
  }
}
</style>