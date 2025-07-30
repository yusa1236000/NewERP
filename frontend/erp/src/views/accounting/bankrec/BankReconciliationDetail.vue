<template>
  <div class="bank-reconciliation-detail">
    <!-- Page Header -->
    <div class="page-header">
      <div class="header-content">
        <div class="title-section">
          <div class="header-main">
            <h1 class="page-title">
              <i class="fas fa-balance-scale"></i>
              Bank Reconciliation Detail
            </h1>
            <div class="reconciliation-status">
              <span class="status-badge" :class="getStatusClass(reconciliation.status)" v-if="reconciliation.status">
                {{ reconciliation.status }}
              </span>
            </div>
          </div>
          <p class="page-subtitle" v-if="reconciliation.bank_account">
            {{ reconciliation.bank_account.bank_name }} - {{ reconciliation.bank_account.account_number }}
          </p>
        </div>
        <div class="header-actions">
          <button @click="goBack" class="btn-secondary">
            <i class="fas fa-arrow-left"></i>
            Back
          </button>
          <button @click="editReconciliation" 
                  class="btn-outline" 
                  v-if="reconciliation.status !== 'Finalized'">
            <i class="fas fa-edit"></i>
            Edit
          </button>
          <button @click="finalizeReconciliation" 
                  class="btn-primary" 
                  v-if="reconciliation.status !== 'Finalized'"
                  :disabled="!canFinalize">
            <i class="fas fa-check"></i>
            Finalize
          </button>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="loading-overlay">
      <div class="loading-content">
        <div class="loading-spinner">
          <i class="fas fa-spinner fa-spin"></i>
        </div>
        <p>Loading reconciliation details...</p>
      </div>
    </div>

    <!-- Main Content -->
    <div v-else-if="reconciliation" class="content-grid">
      <!-- Reconciliation Summary -->
      <div class="summary-section">
        <div class="section-header">
          <h2>
            <i class="fas fa-calculator"></i>
            Reconciliation Summary
          </h2>
          <div class="summary-actions">
            <button @click="refreshSummary" class="btn-outline btn-sm">
              <i class="fas fa-refresh"></i>
              Refresh
            </button>
          </div>
        </div>

        <div class="summary-cards">
          <div class="summary-card">
            <div class="card-header">
              <h3>Statement Balance</h3>
              <i class="fas fa-university"></i>
            </div>
            <div class="card-content">
              <div class="amount-primary">
                {{ formatCurrency(reconciliation.statement_balance, reconciliation.bank_account?.currency) }}
              </div>
              <div class="amount-secondary" v-if="summary?.base_currency_statement_balance && reconciliation.bank_account?.currency !== baseCurrency">
                {{ formatCurrency(summary.base_currency_statement_balance, baseCurrency) }}
              </div>
            </div>
          </div>

          <div class="summary-card">
            <div class="card-header">
              <h3>Book Balance</h3>
              <i class="fas fa-book"></i>
            </div>
            <div class="card-content">
              <div class="amount-primary">
                {{ formatCurrency(reconciliation.book_balance, reconciliation.bank_account?.currency) }}
              </div>
              <div class="amount-secondary" v-if="summary?.base_currency_book_balance && reconciliation.bank_account?.currency !== baseCurrency">
                {{ formatCurrency(summary.base_currency_book_balance, baseCurrency) }}
              </div>
            </div>
          </div>

          <div class="summary-card" :class="getDifferenceClass(reconciliation.difference)">
            <div class="card-header">
              <h3>Difference</h3>
              <i class="fas fa-balance-scale-right"></i>
            </div>
            <div class="card-content">
              <div class="amount-primary">
                {{ formatCurrency(reconciliation.difference, reconciliation.bank_account?.currency) }}
              </div>
              <div class="amount-secondary" v-if="summary?.base_currency_difference && reconciliation.bank_account?.currency !== baseCurrency">
                {{ formatCurrency(summary.base_currency_difference, baseCurrency) }}
              </div>
            </div>
          </div>

          <div class="summary-card" :class="getAdjustedDifferenceClass(summary?.adjusted_difference)" v-if="summary">
            <div class="card-header">
              <h3>Adjusted Difference</h3>
              <i class="fas fa-calculator"></i>
            </div>
            <div class="card-content">
              <div class="amount-primary">
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

        <!-- Currency Summary -->
        <div class="currency-summary" v-if="currencySummary && Object.keys(currencySummary).length > 1">
          <h3>
            <i class="fas fa-globe"></i>
            Multi-Currency Summary
          </h3>
          <div class="currency-grid">
            <div v-for="(data, currency) in currencySummary" :key="currency" class="currency-item">
              <div class="currency-header">
                <span class="currency-code">{{ currency }}</span>
                <small>{{ data.count }} reconciliation(s)</small>
              </div>
              <div class="currency-amounts">
                <div class="currency-amount">
                  <label>Total Statement:</label>
                  <span>{{ formatCurrency(data.total_statement_balance, currency) }}</span>
                </div>
                <div class="currency-amount">
                  <label>Total Book:</label>
                  <span>{{ formatCurrency(data.total_book_balance, currency) }}</span>
                </div>
                <div class="currency-amount" :class="getDifferenceClass(data.total_difference)">
                  <label>Difference:</label>
                  <span>{{ formatCurrency(data.total_difference, currency) }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Reconciliation Details -->
      <div class="details-section">
        <div class="section-header">
          <h2>
            <i class="fas fa-info-circle"></i>
            Reconciliation Details
          </h2>
        </div>

        <div class="details-grid">
          <div class="detail-group">
            <h3>Bank Information</h3>
            <div class="detail-items">
              <div class="detail-item">
                <label>Bank Name:</label>
                <span>{{ reconciliation.bank_account?.bank_name || '-' }}</span>
              </div>
              <div class="detail-item">
                <label>Account Number:</label>
                <span>{{ reconciliation.bank_account?.account_number || '-' }}</span>
              </div>
              <div class="detail-item">
                <label>Currency:</label>
                <span class="currency-badge">{{ reconciliation.bank_account?.currency || 'USD' }}</span>
              </div>
              <div class="detail-item" v-if="reconciliation.exchange_rate && reconciliation.bank_account?.currency !== baseCurrency">
                <label>Exchange Rate:</label>
                <span>1 {{ reconciliation.bank_account?.currency }} = {{ reconciliation.exchange_rate?.toFixed(4) }} {{ baseCurrency }}</span>
              </div>
            </div>
          </div>

          <div class="detail-group">
            <h3>Reconciliation Information</h3>
            <div class="detail-items">
              <div class="detail-item">
                <label>Statement Date:</label>
                <span>{{ formatDate(reconciliation.statement_date) }}</span>
              </div>
              <div class="detail-item">
                <label>Statement Reference:</label>
                <span>{{ reconciliation.statement_reference || '-' }}</span>
              </div>
              <div class="detail-item">
                <label>Created At:</label>
                <span>{{ formatDateTime(reconciliation.created_at) }}</span>
              </div>
              <div class="detail-item">
                <label>Last Updated:</label>
                <span>{{ formatDateTime(reconciliation.updated_at) }}</span>
              </div>
            </div>
          </div>

          <div class="detail-group" v-if="reconciliation.status === 'Finalized'">
            <h3>Finalization Details</h3>
            <div class="detail-items">
              <div class="detail-item">
                <label>Finalized By:</label>
                <span>{{ reconciliation.finalized_by || '-' }}</span>
              </div>
              <div class="detail-item">
                <label>Finalized At:</label>
                <span>{{ formatDateTime(reconciliation.finalized_at) }}</span>
              </div>
            </div>
          </div>

          <div class="detail-group" v-if="reconciliation.notes">
            <h3>Notes</h3>
            <div class="notes-content">
              {{ reconciliation.notes }}
            </div>
          </div>
        </div>
      </div>

      <!-- Reconciliation Lines -->
      <div class="lines-section">
        <div class="section-header">
          <h2>
            <i class="fas fa-list-ul"></i>
            Reconciliation Lines
          </h2>
          <div class="section-actions">
            <button @click="addLine" 
                    class="btn-primary btn-sm" 
                    v-if="reconciliation.status !== 'Finalized'">
              <i class="fas fa-plus"></i>
              Add Line
            </button>
          </div>
        </div>

        <!-- Line Type Filters -->
        <div class="line-filters">
          <div class="filter-tabs">
            <button @click="filterLines('all')" 
                    :class="['filter-tab', { active: activeLineFilter === 'all' }]">
              All Lines ({{ reconciliationLines.length }})
            </button>
            <button @click="filterLines('outstanding_deposit')" 
                    :class="['filter-tab', { active: activeLineFilter === 'outstanding_deposit' }]">
              Outstanding Deposits ({{ getLineCountByType('outstanding_deposit') }})
            </button>
            <button @click="filterLines('outstanding_check')" 
                    :class="['filter-tab', { active: activeLineFilter === 'outstanding_check' }]">
              Outstanding Checks ({{ getLineCountByType('outstanding_check') }})
            </button>
            <button @click="filterLines('bank_charge')" 
                    :class="['filter-tab', { active: activeLineFilter === 'bank_charge' }]">
              Bank Charges ({{ getLineCountByType('bank_charge') }})
            </button>
            <button @click="filterLines('bank_interest')" 
                    :class="['filter-tab', { active: activeLineFilter === 'bank_interest' }]">
              Bank Interest ({{ getLineCountByType('bank_interest') }})
            </button>
            <button @click="filterLines('adjustment')" 
                    :class="['filter-tab', { active: activeLineFilter === 'adjustment' }]">
              Adjustments ({{ getLineCountByType('adjustment') }})
            </button>
          </div>
        </div>

        <!-- Lines Table -->
        <div class="lines-table-container">
          <table class="lines-table" v-if="filteredLines.length > 0">
            <thead>
              <tr>
                <th>Type</th>
                <th>Description</th>
                <th>Amount</th>
                <th v-if="reconciliation.bank_account?.currency !== baseCurrency">Base Currency Amount</th>
                <th>Reference</th>
                <th>Transaction Date</th>
                <th v-if="reconciliation.status !== 'Finalized'">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="line in filteredLines" :key="line.line_id" class="line-row">
                <td>
                  <span class="line-type-badge" :class="getLineTypeClass(line.line_type)">
                    {{ getLineTypeLabel(line.line_type) }}
                  </span>
                </td>
                <td class="description-cell">
                  {{ line.description }}
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
                  {{ line.reference_number || '-' }}
                </td>
                <td class="date-cell">
                  {{ formatDate(line.transaction_date) }}
                </td>
                <td v-if="reconciliation.status !== 'Finalized'" class="actions-cell">
                  <div class="line-actions">
                    <button @click="editLine(line)" class="action-btn edit" title="Edit">
                      <i class="fas fa-edit"></i>
                    </button>
                    <button @click="deleteLine(line)" class="action-btn delete" title="Delete">
                      <i class="fas fa-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>

          <!-- Empty Lines State -->
          <div v-else class="empty-lines">
            <div class="empty-icon">
              <i class="fas fa-list-ul"></i>
            </div>
            <h3>No Lines Found</h3>
            <p v-if="activeLineFilter !== 'all'">
              No {{ getLineTypeLabel(activeLineFilter).toLowerCase() }} lines found.
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
    </div>

    <!-- Add/Edit Line Modal -->
    <div v-if="showLineModal" class="modal-overlay" @click="closeLineModal">
      <div class="modal-content line-modal" @click.stop>
        <div class="modal-header">
          <h3>
            <i class="fas fa-plus"></i>
            {{ editingLine ? 'Edit' : 'Add' }} Reconciliation Line
          </h3>
          <button @click="closeLineModal" class="modal-close">
            <i class="fas fa-times"></i>
          </button>
        </div>
        
        <div class="modal-body">
          <form @submit.prevent="saveLine" class="line-form">
            <div class="form-grid">
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
                <label class="form-label">Amount *</label>
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
                     placeholder="Enter description"
                     required>
            </div>
            
            <div class="form-grid">
              <div class="form-group">
                <label class="form-label">Reference Number</label>
                <input type="text" 
                       v-model="lineForm.reference_number" 
                       class="form-input" 
                       placeholder="Reference number">
              </div>
              
              <div class="form-group">
                <label class="form-label">Transaction Date</label>
                <input type="date" 
                       v-model="lineForm.transaction_date" 
                       class="form-input">
              </div>
            </div>
            
            <!-- Base Currency Preview -->
            <div class="base-currency-preview" v-if="reconciliation.bank_account?.currency !== baseCurrency && lineForm.amount">
              <div class="preview-item">
                <label>Base Currency Amount ({{ baseCurrency }}):</label>
                <span>{{ formatCurrency(lineForm.amount * (reconciliation.exchange_rate || 1), baseCurrency) }}</span>
              </div>
              <div class="preview-item" v-if="reconciliation.exchange_rate">
                <label>Exchange Rate:</label>
                <span>{{ reconciliation.exchange_rate?.toFixed(4) }}</span>
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

    <!-- Delete Line Confirmation Modal -->
    <div v-if="showDeleteLineModal" class="modal-overlay" @click="closeDeleteLineModal">
      <div class="modal-content delete-modal" @click.stop>
        <div class="modal-header">
          <h3>
            <i class="fas fa-trash"></i>
            Delete Line
          </h3>
          <button @click="closeDeleteLineModal" class="modal-close">
            <i class="fas fa-times"></i>
          </button>
        </div>
        
        <div class="modal-body">
          <p>Are you sure you want to delete this reconciliation line? This action cannot be undone.</p>
          <div class="line-preview" v-if="deletingLine">
            <strong>{{ deletingLine.description }}</strong><br>
            <span>{{ formatCurrency(deletingLine.amount, deletingLine.currency || reconciliation.bank_account?.currency) }}</span>
          </div>
        </div>
        
        <div class="modal-footer">
          <button @click="closeDeleteLineModal" class="btn-secondary">Cancel</button>
          <button @click="confirmDeleteLine" class="btn-danger" :disabled="deletingLineConfirm">
            <i class="fas fa-spinner fa-spin" v-if="deletingLineConfirm"></i>
            <i class="fas fa-trash" v-else></i>
            {{ deletingLineConfirm ? 'Deleting...' : 'Delete' }}
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
          <div class="warning-message" v-if="!canFinalize">
            <div class="warning-icon">
              <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="warning-content">
              <h4>Reconciliation is not balanced</h4>
              <p>The adjusted difference of {{ formatCurrency(summary?.adjusted_difference, reconciliation.bank_account?.currency) }} must be resolved before finalizing.</p>
            </div>
          </div>
          
          <div class="finalize-summary" v-if="summary">
            <h4>Final Summary</h4>
            <div class="summary-grid">
              <div class="summary-item">
                <label>Statement Balance:</label>
                <span>{{ formatCurrency(reconciliation.statement_balance, reconciliation.bank_account?.currency) }}</span>
              </div>
              <div class="summary-item">
                <label>Book Balance:</label>
                <span>{{ formatCurrency(reconciliation.book_balance, reconciliation.bank_account?.currency) }}</span>
              </div>
              <div class="summary-item">
                <label>Lines Total:</label>
                <span>{{ formatCurrency(summary.lines_total, reconciliation.bank_account?.currency) }}</span>
              </div>
              <div class="summary-item" :class="getAdjustedDifferenceClass(summary.adjusted_difference)">
                <label>Adjusted Difference:</label>
                <span>{{ formatCurrency(summary.adjusted_difference, reconciliation.bank_account?.currency) }}</span>
              </div>
            </div>
          </div>
          
          <p v-if="canFinalize">
            Are you sure you want to finalize this reconciliation? This action cannot be undone and will update the bank account balance.
          </p>
        </div>
        
        <div class="modal-footer">
          <button @click="closeFinalizeModal" class="btn-secondary">Cancel</button>
          <button @click="confirmFinalize" 
                  class="btn-primary" 
                  :disabled="!canFinalize || finalizing">
            <i class="fas fa-spinner fa-spin" v-if="finalizing"></i>
            <i class="fas fa-check" v-else></i>
            {{ finalizing ? 'Finalizing...' : 'Finalize' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'BankReconciliationDetail',
  data() {
    return {
      reconciliation: {},
      reconciliationLines: [],
      summary: null,
      currencySummary: null,
      loading: false,
      baseCurrency: 'USD',
      activeLineFilter: 'all',
      
      // Line Management
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
      
      // Delete Line
      showDeleteLineModal: false,
      deletingLine: null,
      deletingLineConfirm: false,
      
      // Finalize
      showFinalizeModal: false,
      finalizing: false
    }
  },
  
  computed: {
    reconciliationId() {
      return this.$route.params.id
    },
    
    filteredLines() {
      if (this.activeLineFilter === 'all') {
        return this.reconciliationLines
      }
      return this.reconciliationLines.filter(line => line.line_type === this.activeLineFilter)
    },
    
    canFinalize() {
      return this.summary && Math.abs(this.summary.adjusted_difference) <= 0.01
    }
  },
  
  created() {
    this.loadReconciliation()
    this.loadBaseCurrency()
  },
  
  methods: {
    async loadBaseCurrency() {
      try {
        // You might want to get this from a config API endpoint
        this.baseCurrency = 'USD' // Default, should be configurable
      } catch (error) {
        console.error('Error loading base currency:', error)
      }
    },
    
    async loadReconciliation() {
      this.loading = true
      try {
        const response = await axios.get(`/accounting/bank-reconciliations/${this.reconciliationId}`)
        this.reconciliation = response.data.data
        this.summary = response.data.summary
        this.currencySummary = response.data.currency_summary
        
        await this.loadReconciliationLines()
      } catch (error) {
        console.error('Error loading reconciliation:', error)
        this.$toast.error('Failed to load reconciliation details')
        this.goBack()
      } finally {
        this.loading = false
      }
    },
    
    async loadReconciliationLines() {
      try {
        const response = await axios.get(`/accounting/bank-reconciliations/${this.reconciliationId}/lines`)
        this.reconciliationLines = response.data.data || []
      } catch (error) {
        console.error('Error loading reconciliation lines:', error)
        this.$toast.error('Failed to load reconciliation lines')
      }
    },
    
    async refreshSummary() {
      try {
        const response = await axios.get(`/accounting/bank-reconciliations/${this.reconciliationId}`)
        this.summary = response.data.summary
        this.currencySummary = response.data.currency_summary
      } catch (error) {
        console.error('Error refreshing summary:', error)
        this.$toast.error('Failed to refresh summary')
      }
    },
    
    filterLines(type) {
      this.activeLineFilter = type
    },
    
    getLineCountByType(type) {
      return this.reconciliationLines.filter(line => line.line_type === type).length
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
          // Update existing line
          await axios.put(
            `/accounting/bank-reconciliations/${this.reconciliationId}/lines/${this.editingLine.line_id}`,
            this.lineForm
          )
          this.$toast.success('Line updated successfully')
        } else {
          // Create new line
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
      this.showDeleteLineModal = true
    },
    
    closeDeleteLineModal() {
      this.showDeleteLineModal = false
      this.deletingLine = null
      this.deletingLineConfirm = false
    },
    
    async confirmDeleteLine() {
      if (!this.deletingLine) return
      
      this.deletingLineConfirm = true
      try {
        await axios.delete(`/accounting/bank-reconciliations/${this.reconciliationId}/lines/${this.deletingLine.line_id}`)
        
        this.$toast.success('Line deleted successfully')
        this.closeDeleteLineModal()
        await this.loadReconciliationLines()
        await this.refreshSummary()
      } catch (error) {
        console.error('Error deleting line:', error)
        this.$toast.error(error.response?.data?.message || 'Failed to delete line')
      } finally {
        this.deletingLineConfirm = false
      }
    },
    
    finalizeReconciliation() {
      this.showFinalizeModal = true
    },
    
    closeFinalizeModal() {
      this.showFinalizeModal = false
      this.finalizing = false
    },
    
    async confirmFinalize() {
      this.finalizing = true
      try {
        await axios.post(`/accounting/bank-reconciliations/${this.reconciliationId}/finalize`)
        
        this.$toast.success('Reconciliation finalized successfully')
        this.closeFinalizeModal()
        await this.loadReconciliation()
      } catch (error) {
        console.error('Error finalizing reconciliation:', error)
        this.$toast.error(error.response?.data?.message || 'Failed to finalize reconciliation')
      } finally {
        this.finalizing = false
      }
    },
    
    editReconciliation() {
      this.$router.push(`/accounting/bank-reconciliations/${this.reconciliationId}/edit`)
    },
    
    goBack() {
      this.$router.push('/accounting/bank-reconciliations')
    },
    
    formatDate(date) {
      if (!date) return '-'
      return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      })
    },
    
    formatDateTime(dateTime) {
      if (!dateTime) return '-'
      return new Date(dateTime).toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
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
    },
    
    getAdjustedDifferenceClass(difference) {
      if (!difference) return ''
      if (Math.abs(difference) <= 0.01) return 'difference-zero'
      return difference > 0 ? 'difference-positive' : 'difference-negative'
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
      // Deposits and interest are positive (credit), checks and charges are negative (debit)
      if (lineType === 'outstanding_deposit' || lineType === 'bank_interest') {
        return 'amount-positive'
      } else if (lineType === 'outstanding_check' || lineType === 'bank_charge') {
        return 'amount-negative'
      } else {
        // For adjustments, use the sign of the amount
        return amount >= 0 ? 'amount-positive' : 'amount-negative'
      }
    }
  }
}
</script>

<style scoped>
/* Inherit all styles from BankReconciliationList and add specific styles */

/* Additional specific styles for detail view */
.header-main {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.reconciliation-status {
  flex-shrink: 0;
}

.content-grid {
  display: grid;
  gap: 2rem;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 2px solid var(--gray-200);
}

.section-header h2 {
  font-size: 1.5rem;
  font-weight: 600;
  color: var(--gray-900);
  margin: 0;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.section-header h2 i {
  color: var(--primary-color);
}

.section-actions, .summary-actions {
  display: flex;
  gap: 0.5rem;
}

.btn-sm {
  padding: 0.5rem 1rem;
  font-size: 0.75rem;
}

/* Summary Section */
.summary-section {
  background: var(--white);
  border-radius: var(--border-radius);
  padding: 2rem;
  box-shadow: var(--box-shadow);
}

.summary-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.summary-card {
  border: 2px solid var(--gray-200);
  border-radius: var(--border-radius);
  padding: 1.5rem;
  transition: var(--transition);
}

.summary-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--box-shadow);
}

.summary-card.difference-zero {
  border-color: var(--success-color);
  background: rgba(5, 150, 105, 0.02);
}

.summary-card.difference-positive {
  border-color: var(--success-color);
  background: rgba(5, 150, 105, 0.02);
}

.summary-card.difference-negative {
  border-color: var(--danger-color);
  background: rgba(220, 38, 38, 0.02);
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.card-header h3 {
  font-size: 1rem;
  font-weight: 600;
  color: var(--gray-700);
  margin: 0;
}

.card-header i {
  color: var(--gray-400);
  font-size: 1.25rem;
}

.card-content {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.amount-primary {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--gray-900);
  font-family: monospace;
}

.amount-secondary {
  font-size: 0.875rem;
  color: var(--gray-500);
  font-family: monospace;
}

.balance-status {
  margin-top: 0.5rem;
}

.balance-status .balanced {
  color: var(--success-color);
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.875rem;
}

.balance-status .unbalanced {
  color: var(--danger-color);
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.875rem;
}

/* Currency Summary */
.currency-summary {
  border-top: 1px solid var(--gray-200);
  padding-top: 1.5rem;
}

.currency-summary h3 {
  font-size: 1.125rem;
  font-weight: 600;
  color: var(--gray-900);
  margin: 0 0 1rem 0;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.currency-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1rem;
}

.currency-item {
  border: 1px solid var(--gray-200);
  border-radius: 8px;
  padding: 1rem;
  background: var(--gray-50);
}

.currency-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.75rem;
}

.currency-code {
  font-weight: 700;
  color: var(--gray-900);
  font-size: 1rem;
}

.currency-header small {
  color: var(--gray-500);
  font-size: 0.75rem;
}

.currency-amounts {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.currency-amount {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.875rem;
}

.currency-amount label {
  color: var(--gray-600);
  font-weight: 500;
}

.currency-amount span {
  font-weight: 600;
  font-family: monospace;
}

/* Details Section */
.details-section {
  background: var(--white);
  border-radius: var(--border-radius);
  padding: 2rem;
  box-shadow: var(--box-shadow);
}

.details-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 2rem;
}

.detail-group h3 {
  font-size: 1.125rem;
  font-weight: 600;
  color: var(--gray-900);
  margin: 0 0 1rem 0;
  padding-bottom: 0.5rem;
  border-bottom: 1px solid var(--gray-200);
}

.detail-items {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.detail-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.5rem 0;
}

.detail-item label {
  font-weight: 500;
  color: var(--gray-600);
  flex-shrink: 0;
}

.detail-item span {
  font-weight: 600;
  color: var(--gray-900);
  text-align: right;
  word-break: break-word;
}

.notes-content {
  background: var(--gray-50);
  border-radius: 8px;
  padding: 1rem;
  font-style: italic;
  color: var(--gray-700);
  line-height: 1.5;
}

/* Lines Section */
.lines-section {
  background: var(--white);
  border-radius: var(--border-radius);
  padding: 2rem;
  box-shadow: var(--box-shadow);
}

.line-filters {
  margin-bottom: 1.5rem;
}

.filter-tabs {
  display: flex;
  gap: 0.5rem;
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

.lines-table-container {
  overflow-x: auto;
}

.lines-table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 1rem;
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

.lines-table td {
  padding: 1rem;
  border-bottom: 1px solid var(--gray-200);
  vertical-align: middle;
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
  word-break: break-word;
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

.reference-cell {
  font-family: monospace;
  color: var(--gray-600);
}

.date-cell {
  color: var(--gray-700);
  white-space: nowrap;
}

.line-actions {
  display: flex;
  gap: 0.5rem;
}

.line-actions .action-btn {
  width: 1.75rem;
  height: 1.75rem;
  font-size: 0.75rem;
}

.empty-lines {
  text-align: center;
  padding: 3rem;
  color: var(--gray-500);
}

.empty-lines .empty-icon {
  font-size: 3rem;
  color: var(--gray-300);
  margin-bottom: 1rem;
}

.empty-lines h3 {
  font-size: 1.25rem;
  color: var(--gray-700);
  margin-bottom: 0.5rem;
}

/* Line Modal */
.line-modal {
  max-width: 600px;
}

.line-form .form-grid {
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

.base-currency-preview {
  background: var(--gray-50);
  border-radius: 8px;
  padding: 1rem;
  margin-top: 1rem;
}

.preview-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.5rem;
}

.preview-item:last-child {
  margin-bottom: 0;
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

/* Delete Modal */
.line-preview {
  background: var(--gray-50);
  border-radius: 8px;
  padding: 1rem;
  margin-top: 1rem;
  text-align: center;
}

/* Finalize Modal */
.finalize-summary h4 {
  font-size: 1rem;
  font-weight: 600;
  color: var(--gray-900);
  margin: 0 0 1rem 0;
}

.finalize-summary .summary-grid {
  display: grid;
  gap: 0.75rem;
}

.finalize-summary .summary-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem;
  background: var(--gray-50);
  border-radius: 6px;
}

.finalize-summary .summary-item.difference-zero {
  background: rgba(5, 150, 105, 0.05);
  border: 1px solid rgba(5, 150, 105, 0.2);
}

.finalize-summary .summary-item.difference-positive,
.finalize-summary .summary-item.difference-negative {
  background: rgba(220, 38, 38, 0.05);
  border: 1px solid rgba(220, 38, 38, 0.2);
}

/* Responsive Design */
@media (max-width: 1200px) {
  .summary-cards {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .details-grid {
    grid-template-columns: 1fr;
  }
  
  .currency-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .bank-reconciliation-detail {
    padding: 1rem;
  }
  
  .header-content {
    flex-direction: column;
    align-items: stretch;
    gap: 1rem;
  }
  
  .header-main {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.5rem;
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
  
  .filter-tabs {
    flex-direction: column;
  }
  
  .line-form .form-grid {
    grid-template-columns: 1fr;
  }
  
  .detail-item {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.25rem;
  }
  
  .detail-item span {
    text-align: left;
  }
}
</style>