<template>
  <div class="bank-reconciliation-finalize">
    <!-- Page Header -->
    <div class="page-header">
      <div class="header-content">
        <div class="title-section">
          <h1 class="page-title">
            <i class="fas fa-check-circle"></i>
            Finalize Bank Reconciliation
          </h1>
          <p class="page-subtitle" v-if="reconciliation.bank_account">
            {{ reconciliation.bank_account.bank_name }} - {{ reconciliation.bank_account.account_number }}
          </p>
        </div>
        <div class="header-actions">
          <button @click="goBack" class="btn-secondary">
            <i class="fas fa-arrow-left"></i>
            Back
          </button>
          <button @click="finalizeReconciliation" 
                  class="btn-primary" 
                  :disabled="!canFinalize || finalizing">
            <i class="fas fa-spinner fa-spin" v-if="finalizing"></i>
            <i class="fas fa-check-circle" v-else></i>
            {{ finalizing ? 'Finalizing...' : 'Finalize Reconciliation' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="loading-container">
      <div class="loading-spinner">
        <i class="fas fa-spinner fa-spin"></i>
      </div>
      <p>Loading reconciliation details...</p>
    </div>

    <!-- Main Content -->
    <div v-else-if="reconciliation" class="content-container">
      <!-- Reconciliation Status -->
      <div class="status-section">
        <div class="status-card" :class="getStatusCardClass()">
          <div class="status-icon">
            <i :class="getStatusIcon()"></i>
          </div>
          <div class="status-content">
            <h2>{{ getStatusTitle() }}</h2>
            <p>{{ getStatusMessage() }}</p>
          </div>
        </div>
      </div>

      <!-- Pre-Finalization Checklist -->
      <div class="checklist-section">
        <div class="section-header">
          <h2>
            <i class="fas fa-tasks"></i>
            Pre-Finalization Checklist
          </h2>
        </div>

        <div class="checklist-items">
          <div class="checklist-item" :class="{ completed: reconciliation.statement_date }">
            <div class="check-icon">
              <i class="fas fa-check" v-if="reconciliation.statement_date"></i>
              <i class="fas fa-times" v-else></i>
            </div>
            <div class="check-content">
              <h3>Statement Date Specified</h3>
              <p v-if="reconciliation.statement_date">
                Statement date: {{ formatDate(reconciliation.statement_date) }}
              </p>
              <p v-else class="error-text">Statement date is required</p>
            </div>
          </div>

          <div class="checklist-item" :class="{ completed: hasValidBalances }">
            <div class="check-icon">
              <i class="fas fa-check" v-if="hasValidBalances"></i>
              <i class="fas fa-times" v-else></i>
            </div>
            <div class="check-content">
              <h3>Valid Balances Entered</h3>
              <p v-if="hasValidBalances">
                Statement: {{ formatCurrency(reconciliation.statement_balance, reconciliation.bank_account?.currency) }},
                Book: {{ formatCurrency(reconciliation.book_balance, reconciliation.bank_account?.currency) }}
              </p>
              <p v-else class="error-text">Both statement and book balances must be valid numbers</p>
            </div>
          </div>

          <div class="checklist-item" :class="{ completed: isBalanced }">
            <div class="check-icon">
              <i class="fas fa-check" v-if="isBalanced"></i>
              <i class="fas fa-exclamation-triangle" v-else></i>
            </div>
            <div class="check-content">
              <h3>Reconciliation Balanced</h3>
              <p v-if="isBalanced" class="success-text">
                Adjusted difference is within acceptable range ({{ formatCurrency(Math.abs(summary?.adjusted_difference || 0), reconciliation.bank_account?.currency) }})
              </p>
              <p v-else class="error-text">
                Adjusted difference of {{ formatCurrency(summary?.adjusted_difference || 0, reconciliation.bank_account?.currency) }} must be resolved
              </p>
            </div>
          </div>

          <div class="checklist-item" :class="{ completed: hasLines }">
            <div class="check-icon">
              <i class="fas fa-check" v-if="hasLines"></i>
              <i class="fas fa-info-circle" v-else></i>
            </div>
            <div class="check-content">
              <h3>Reconciliation Lines</h3>
              <p v-if="hasLines">
                {{ reconciliationLines.length }} line(s) added with total value of {{ formatCurrency(summary?.lines_total || 0, reconciliation.bank_account?.currency) }}
              </p>
              <p v-else class="info-text">
                No reconciliation lines added. This is acceptable if no adjustments are needed.
              </p>
            </div>
          </div>

          <div class="checklist-item" :class="{ completed: hasReconciler }">
            <div class="check-icon">
              <i class="fas fa-check" v-if="hasReconciler"></i>
              <i class="fas fa-info-circle" v-else></i>
            </div>
            <div class="check-content">
              <h3>Reconciler Information</h3>
              <p v-if="hasReconciler">
                Reconciler: {{ reconciliation.reconciler_name }}
              </p>
              <p v-else class="info-text">
                No reconciler name specified (optional)
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Final Summary -->
      <div class="summary-section">
        <div class="section-header">
          <h2>
            <i class="fas fa-calculator"></i>
            Final Reconciliation Summary
          </h2>
        </div>

        <div class="summary-grid">
          <!-- Current Balances -->
          <div class="summary-group">
            <h3>Current Balances</h3>
            <div class="summary-items">
              <div class="summary-item">
                <label>Statement Balance:</label>
                <span class="amount">{{ formatCurrency(reconciliation.statement_balance, reconciliation.bank_account?.currency) }}</span>
              </div>
              <div class="summary-item">
                <label>Book Balance:</label>
                <span class="amount">{{ formatCurrency(reconciliation.book_balance, reconciliation.bank_account?.currency) }}</span>
              </div>
              <div class="summary-item difference" :class="getDifferenceClass(reconciliation.difference)">
                <label>Initial Difference:</label>
                <span class="amount">{{ formatCurrency(reconciliation.difference, reconciliation.bank_account?.currency) }}</span>
              </div>
            </div>
          </div>

          <!-- Reconciliation Lines Summary -->
          <div class="summary-group" v-if="summary">
            <h3>Reconciliation Adjustments</h3>
            <div class="summary-items">
              <div class="summary-item" v-if="linesSummary.outstanding_deposits > 0">
                <label>Outstanding Deposits:</label>
                <span class="amount positive">{{ formatCurrency(linesSummary.outstanding_deposits, reconciliation.bank_account?.currency) }}</span>
              </div>
              <div class="summary-item" v-if="linesSummary.outstanding_checks > 0">
                <label>Outstanding Checks:</label>
                <span class="amount negative">{{ formatCurrency(-linesSummary.outstanding_checks, reconciliation.bank_account?.currency) }}</span>
              </div>
              <div class="summary-item" v-if="linesSummary.bank_charges > 0">
                <label>Bank Charges:</label>
                <span class="amount negative">{{ formatCurrency(-linesSummary.bank_charges, reconciliation.bank_account?.currency) }}</span>
              </div>
              <div class="summary-item" v-if="linesSummary.bank_interest > 0">
                <label>Bank Interest:</label>
                <span class="amount positive">{{ formatCurrency(linesSummary.bank_interest, reconciliation.bank_account?.currency) }}</span>
              </div>
              <div class="summary-item" v-if="linesSummary.adjustments !== 0">
                <label>Adjustments:</label>
                <span class="amount" :class="linesSummary.adjustments >= 0 ? 'positive' : 'negative'">
                  {{ formatCurrency(linesSummary.adjustments, reconciliation.bank_account?.currency) }}
                </span>
              </div>
              <div class="summary-item total">
                <label>Total Adjustments:</label>
                <span class="amount">{{ formatCurrency(summary.lines_total, reconciliation.bank_account?.currency) }}</span>
              </div>
            </div>
          </div>

          <!-- Final Result -->
          <div class="summary-group">
            <h3>Final Result</h3>
            <div class="summary-items">
              <div class="summary-item final-difference" :class="getAdjustedDifferenceClass()">
                <label>Adjusted Difference:</label>
                <span class="amount">{{ formatCurrency(summary?.adjusted_difference || 0, reconciliation.bank_account?.currency) }}</span>
              </div>
              <div class="summary-item status-indicator">
                <label>Status:</label>
                <span v-if="isBalanced" class="status balanced">
                  <i class="fas fa-check-circle"></i>
                  Balanced & Ready to Finalize
                </span>
                <span v-else class="status unbalanced">
                  <i class="fas fa-exclamation-triangle"></i>
                  Requires Additional Adjustments
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Base Currency Summary -->
        <div class="base-currency-summary" v-if="reconciliation.bank_account?.currency !== baseCurrency && summary">
          <h3>
            <i class="fas fa-globe"></i>
            Base Currency Summary ({{ baseCurrency }})
          </h3>
          <div class="base-currency-grid">
            <div class="base-currency-item">
              <label>Statement Balance:</label>
              <span>{{ formatCurrency(reconciliation.base_currency_statement_balance, baseCurrency) }}</span>
            </div>
            <div class="base-currency-item">
              <label>Book Balance:</label>
              <span>{{ formatCurrency(reconciliation.base_currency_book_balance, baseCurrency) }}</span>
            </div>
            <div class="base-currency-item">
              <label>Adjusted Difference:</label>
              <span>{{ formatCurrency(summary.base_currency_adjusted_difference, baseCurrency) }}</span>
            </div>
            <div class="base-currency-item">
              <label>Exchange Rate:</label>
              <span>1 {{ reconciliation.bank_account.currency }} = {{ reconciliation.exchange_rate?.toFixed(4) }} {{ baseCurrency }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Impact Analysis -->
      <div class="impact-section" v-if="canFinalize">
        <div class="section-header">
          <h2>
            <i class="fas fa-chart-line"></i>
            Finalization Impact
          </h2>
        </div>

        <div class="impact-warnings">
          <div class="impact-warning">
            <div class="warning-icon">
              <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="warning-content">
              <h3>This action cannot be undone</h3>
              <p>Once finalized, this reconciliation cannot be modified or deleted.</p>
            </div>
          </div>

          <div class="impact-item">
            <div class="impact-icon">
              <i class="fas fa-university"></i>
            </div>
            <div class="impact-content">
              <h3>Bank Account Balance Update</h3>
              <p>
                The bank account balance will be updated from 
                {{ formatCurrency(reconciliation.bank_account?.current_balance, reconciliation.bank_account?.currency) }} 
                to {{ formatCurrency(reconciliation.statement_balance, reconciliation.bank_account?.currency) }}
              </p>
            </div>
          </div>

          <div class="impact-item" v-if="reconciliation.bank_account?.currency !== baseCurrency">
            <div class="impact-icon">
              <i class="fas fa-exchange-alt"></i>
            </div>
            <div class="impact-content">
              <h3>Base Currency Balance Update</h3>
              <p>
                The base currency balance will be updated to 
                {{ formatCurrency(reconciliation.base_currency_statement_balance, baseCurrency) }}
              </p>
            </div>
          </div>

          <div class="impact-item">
            <div class="impact-icon">
              <i class="fas fa-calendar-check"></i>
            </div>
            <div class="impact-content">
              <h3>Last Reconciled Date</h3>
              <p>
                The last reconciled date will be set to {{ formatDate(reconciliation.statement_date) }}
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Reconciliation Lines Detail -->
      <div class="lines-detail-section" v-if="reconciliationLines.length > 0">
        <div class="section-header">
          <h2>
            <i class="fas fa-list-ul"></i>
            Reconciliation Lines Detail
          </h2>
        </div>

        <div class="lines-table-container">
          <table class="lines-table">
            <thead>
              <tr>
                <th>Type</th>
                <th>Description</th>
                <th>Amount</th>
                <th v-if="reconciliation.bank_account?.currency !== baseCurrency">Base Currency</th>
                <th>Reference</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="line in reconciliationLines" :key="line.line_id" class="line-row">
                <td>
                  <span class="line-type-badge" :class="getLineTypeClass(line.line_type)">
                    {{ getLineTypeLabel(line.line_type) }}
                  </span>
                </td>
                <td class="description-cell">{{ line.description }}</td>
                <td class="amount-cell" :class="getAmountClass(line.line_type, line.amount)">
                  {{ formatCurrency(line.amount, line.currency || reconciliation.bank_account?.currency) }}
                </td>
                <td v-if="reconciliation.bank_account?.currency !== baseCurrency" class="amount-cell">
                  {{ formatCurrency(line.base_currency_amount, baseCurrency) }}
                </td>
                <td class="reference-cell">{{ line.reference_number || '-' }}</td>
                <td class="date-cell">{{ formatDate(line.transaction_date) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Final Confirmation -->
      <div class="confirmation-section" v-if="canFinalize">
        <div class="confirmation-card">
          <div class="confirmation-icon">
            <i class="fas fa-check-double"></i>
          </div>
          <div class="confirmation-content">
            <h3>Ready to Finalize</h3>
            <p>
              This reconciliation is balanced and ready to be finalized. 
              Click the "Finalize Reconciliation" button above to complete the process.
            </p>
          </div>
        </div>
      </div>

      <!-- Error State -->
      <div class="error-section" v-else>
        <div class="error-card">
          <div class="error-icon">
            <i class="fas fa-exclamation-circle"></i>
          </div>
          <div class="error-content">
            <h3>Cannot Finalize</h3>
            <p>
              This reconciliation cannot be finalized yet. Please review the checklist above and resolve any issues.
            </p>
            <div class="error-actions">
              <router-link :to="`/accounting/bank-reconciliations/${reconciliationId}/edit`" class="btn-primary">
                <i class="fas fa-edit"></i>
                Edit Reconciliation
              </router-link>
              <router-link :to="`/accounting/bank-reconciliations/${reconciliationId}/lines`" class="btn-outline">
                <i class="fas fa-list"></i>
                Manage Lines
              </router-link>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'BankReconciliationFinalize',
  data() {
    return {
      reconciliation: {},
      reconciliationLines: [],
      summary: null,
      loading: false,
      finalizing: false,
      baseCurrency: 'USD'
    }
  },
  
  computed: {
    reconciliationId() {
      return this.$route.params.id
    },
    
    hasValidBalances() {
      return this.reconciliation.statement_balance !== null && 
             this.reconciliation.statement_balance !== undefined &&
             this.reconciliation.book_balance !== null && 
             this.reconciliation.book_balance !== undefined &&
             !isNaN(this.reconciliation.statement_balance) &&
             !isNaN(this.reconciliation.book_balance)
    },
    
    isBalanced() {
      return this.summary && Math.abs(this.summary.adjusted_difference) <= 0.01
    },
    
    hasLines() {
      return this.reconciliationLines.length > 0
    },
    
    hasReconciler() {
      return this.reconciliation.reconciler_name && this.reconciliation.reconciler_name.trim() !== ''
    },
    
    canFinalize() {
      return this.reconciliation.statement_date && 
             this.hasValidBalances && 
             this.isBalanced &&
             this.reconciliation.status !== 'Finalized'
    },
    
    linesSummary() {
      const summary = {
        outstanding_deposits: 0,
        outstanding_checks: 0,
        bank_charges: 0,
        bank_interest: 0,
        adjustments: 0
      }
      
      this.reconciliationLines.forEach(line => {
        switch (line.line_type) {
          case 'outstanding_deposit':
            summary.outstanding_deposits += line.amount
            break
          case 'outstanding_check':
            summary.outstanding_checks += line.amount
            break
          case 'bank_charge':
            summary.bank_charges += line.amount
            break
          case 'bank_interest':
            summary.bank_interest += line.amount
            break
          case 'adjustment':
            summary.adjustments += line.amount
            break
        }
      })
      
      return summary
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
      try {
        const response = await axios.get(`/accounting/bank-reconciliations/${this.reconciliationId}`)
        this.reconciliation = response.data.data
        this.summary = response.data.summary
        
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
      }
    },
    
    async finalizeReconciliation() {
      if (!this.canFinalize) return
      
      this.finalizing = true
      try {
        await axios.post(`/accounting/bank-reconciliations/${this.reconciliationId}/finalize`)
        
        this.$toast.success('Reconciliation finalized successfully')
        this.$router.push(`/accounting/bank-reconciliations/${this.reconciliationId}`)
      } catch (error) {
        console.error('Error finalizing reconciliation:', error)
        this.$toast.error(error.response?.data?.message || 'Failed to finalize reconciliation')
      } finally {
        this.finalizing = false
      }
    },
    
    goBack() {
      this.$router.push(`/accounting/bank-reconciliations/${this.reconciliationId}`)
    },
    
    getStatusCardClass() {
      if (this.reconciliation.status === 'Finalized') return 'status-finalized'
      if (this.canFinalize) return 'status-ready'
      return 'status-pending'
    },
    
    getStatusIcon() {
      if (this.reconciliation.status === 'Finalized') return 'fas fa-check-circle'
      if (this.canFinalize) return 'fas fa-thumbs-up'
      return 'fas fa-clock'
    },
    
    getStatusTitle() {
      if (this.reconciliation.status === 'Finalized') return 'Already Finalized'
      if (this.canFinalize) return 'Ready to Finalize'
      return 'Needs Attention'
    },
    
    getStatusMessage() {
      if (this.reconciliation.status === 'Finalized') {
        return 'This reconciliation has already been finalized and cannot be modified.'
      }
      if (this.canFinalize) {
        return 'All requirements have been met. This reconciliation is ready to be finalized.'
      }
      return 'Some requirements need to be addressed before this reconciliation can be finalized.'
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
    }
  }
}
</script>

<style scoped>
/* Use existing CSS variables and add specific styles for finalize view */

.bank-reconciliation-finalize {
  min-height: 100vh;
  background: var(--gray-50);
  padding: 2rem;
}

/* Page Header - reuse existing styles */
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
}

.header-actions {
  display: flex;
  gap: 1rem;
  flex-shrink: 0;
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

.btn-secondary:hover:not(:disabled) {
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

/* Content Container */
.content-container {
  max-width: 1200px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

/* Status Section */
.status-section {
  margin-bottom: 2rem;
}

.status-card {
  background: var(--white);
  border-radius: var(--border-radius);
  padding: 2rem;
  box-shadow: var(--box-shadow);
  display: flex;
  align-items: center;
  gap: 2rem;
  border-left: 6px solid;
}

.status-card.status-ready {
  border-left-color: var(--success-color);
  background: linear-gradient(135deg, rgba(5, 150, 105, 0.05), var(--white));
}

.status-card.status-pending {
  border-left-color: var(--warning-color);
  background: linear-gradient(135deg, rgba(217, 119, 6, 0.05), var(--white));
}

.status-card.status-finalized {
  border-left-color: var(--primary-color);
  background: linear-gradient(135deg, rgba(37, 99, 235, 0.05), var(--white));
}

.status-icon {
  width: 4rem;
  height: 4rem;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2rem;
  flex-shrink: 0;
}

.status-ready .status-icon {
  background: rgba(5, 150, 105, 0.1);
  color: var(--success-color);
}

.status-pending .status-icon {
  background: rgba(217, 119, 6, 0.1);
  color: var(--warning-color);
}

.status-finalized .status-icon {
  background: rgba(37, 99, 235, 0.1);
  color: var(--primary-color);
}

.status-content h2 {
  font-size: 1.5rem;
  font-weight: 700;
  margin: 0 0 0.5rem 0;
  color: var(--gray-900);
}

.status-content p {
  margin: 0;
  color: var(--gray-600);
  line-height: 1.5;
}

/* Section Styles */
.checklist-section,
.summary-section,
.impact-section,
.lines-detail-section,
.confirmation-section,
.error-section {
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

/* Checklist Items */
.checklist-items {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.checklist-item {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  padding: 1.5rem;
  border-radius: var(--border-radius);
  border: 2px solid var(--gray-200);
  transition: var(--transition);
}

.checklist-item.completed {
  border-color: var(--success-color);
  background: rgba(5, 150, 105, 0.02);
}

.check-icon {
  width: 2.5rem;
  height: 2.5rem;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  flex-shrink: 0;
  font-weight: 700;
}

.checklist-item.completed .check-icon {
  background: var(--success-color);
  color: var(--white);
}

.checklist-item:not(.completed) .check-icon {
  background: var(--gray-200);
  color: var(--gray-500);
}

.check-content h3 {
  font-size: 1.125rem;
  font-weight: 600;
  color: var(--gray-900);
  margin: 0 0 0.5rem 0;
}

.check-content p {
  margin: 0;
  color: var(--gray-600);
  line-height: 1.5;
}

.check-content .success-text {
  color: var(--success-color);
  font-weight: 500;
}

.check-content .error-text {
  color: var(--danger-color);
  font-weight: 500;
}

.check-content .info-text {
  color: var(--gray-500);
  font-style: italic;
}

/* Summary Grid */
.summary-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
  gap: 2rem;
  margin-bottom: 2rem;
}

.summary-group {
  border: 2px solid var(--gray-200);
  border-radius: var(--border-radius);
  padding: 1.5rem;
  background: var(--gray-50);
}

.summary-group h3 {
  font-size: 1.125rem;
  font-weight: 600;
  color: var(--gray-900);
  margin: 0 0 1rem 0;
  padding-bottom: 0.5rem;
  border-bottom: 1px solid var(--gray-300);
}

.summary-items {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.summary-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.5rem 0;
}

.summary-item label {
  font-weight: 500;
  color: var(--gray-600);
}

.summary-item .amount {
  font-weight: 700;
  font-family: monospace;
  color: var(--gray-900);
}

.summary-item.difference .amount {
  font-size: 1.125rem;
}

.summary-item.total {
  border-top: 1px solid var(--gray-300);
  padding-top: 0.75rem;
  font-weight: 600;
}

.summary-item.final-difference {
  background: var(--white);
  padding: 1rem;
  border-radius: 8px;
  border: 2px solid var(--gray-300);
}

.summary-item.final-difference.balanced {
  border-color: var(--success-color);
  background: rgba(5, 150, 105, 0.05);
}

.summary-item.final-difference.positive,
.summary-item.final-difference.negative {
  border-color: var(--danger-color);
  background: rgba(220, 38, 38, 0.05);
}

.summary-item.final-difference .amount {
  font-size: 1.25rem;
}

.summary-item.balanced .amount {
  color: var(--success-color);
}

.summary-item.positive .amount {
  color: var(--success-color);
}

.summary-item.negative .amount {
  color: var(--danger-color);
}

.summary-item .amount.positive {
  color: var(--success-color);
}

.summary-item .amount.negative {
  color: var(--danger-color);
}

.status-indicator .status {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: 600;
}

.status.balanced {
  color: var(--success-color);
}

.status.unbalanced {
  color: var(--danger-color);
}

/* Base Currency Summary */
.base-currency-summary {
  border-top: 1px solid var(--gray-200);
  padding-top: 1.5rem;
  margin-top: 1.5rem;
}

.base-currency-summary h3 {
  font-size: 1.125rem;
  font-weight: 600;
  color: var(--gray-900);
  margin: 0 0 1rem 0;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.base-currency-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1rem;
}

.base-currency-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem;
  background: var(--white);
  border-radius: 6px;
  border: 1px solid var(--gray-200);
}

.base-currency-item label {
  font-weight: 500;
  color: var(--gray-600);
  font-size: 0.875rem;
}

.base-currency-item span {
  font-weight: 600;
  color: var(--gray-900);
  font-family: monospace;
}

/* Impact Section */
.impact-warnings {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.impact-warning,
.impact-item {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  padding: 1rem;
  border-radius: 8px;
}

.impact-warning {
  background: rgba(217, 119, 6, 0.05);
  border: 1px solid rgba(217, 119, 6, 0.2);
}

.impact-item {
  background: var(--gray-50);
  border: 1px solid var(--gray-200);
}

.warning-icon,
.impact-icon {
  width: 2.5rem;
  height: 2.5rem;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.125rem;
  flex-shrink: 0;
}

.warning-icon {
  background: rgba(217, 119, 6, 0.1);
  color: var(--warning-color);
}

.impact-icon {
  background: rgba(37, 99, 235, 0.1);
  color: var(--primary-color);
}

.warning-content h3,
.impact-content h3 {
  font-size: 1rem;
  font-weight: 600;
  color: var(--gray-900);
  margin: 0 0 0.5rem 0;
}

.warning-content p,
.impact-content p {
  margin: 0;
  color: var(--gray-600);
  line-height: 1.5;
}

/* Lines Table */
.lines-table-container {
  overflow-x: auto;
  margin-top: 1rem;
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

/* Confirmation/Error Cards */
.confirmation-card,
.error-card {
  display: flex;
  align-items: center;
  gap: 2rem;
  padding: 2rem;
  border-radius: var(--border-radius);
  text-align: left;
}

.confirmation-card {
  background: rgba(5, 150, 105, 0.05);
  border: 2px solid rgba(5, 150, 105, 0.2);
}

.error-card {
  background: rgba(220, 38, 38, 0.05);
  border: 2px solid rgba(220, 38, 38, 0.2);
}

.confirmation-icon,
.error-icon {
  width: 4rem;
  height: 4rem;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2rem;
  flex-shrink: 0;
}

.confirmation-icon {
  background: rgba(5, 150, 105, 0.1);
  color: var(--success-color);
}

.error-icon {
  background: rgba(220, 38, 38, 0.1);
  color: var(--danger-color);
}

.confirmation-content h3,
.error-content h3 {
  font-size: 1.25rem;
  font-weight: 600;
  margin: 0 0 0.5rem 0;
}

.confirmation-content h3 {
  color: var(--success-color);
}

.error-content h3 {
  color: var(--danger-color);
}

.confirmation-content p,
.error-content p {
  margin: 0 0 1rem 0;
  color: var(--gray-600);
  line-height: 1.5;
}

.error-actions {
  display: flex;
  gap: 1rem;
}

/* Loading State */
.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem;
  text-align: center;
  color: var(--gray-500);
}

.loading-spinner {
  font-size: 3rem;
  color: var(--primary-color);
  margin-bottom: 1rem;
}

/* Responsive Design */
@media (max-width: 1200px) {
  .summary-grid {
    grid-template-columns: 1fr;
  }
  
  .base-currency-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 768px) {
  .bank-reconciliation-finalize {
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
  
  .status-card {
    flex-direction: column;
    text-align: center;
    gap: 1rem;
  }
  
  .checklist-item {
    flex-direction: column;
    text-align: center;
    gap: 1rem;
  }
  
  .summary-item {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.25rem;
  }
  
  .base-currency-grid {
    grid-template-columns: 1fr;
  }
  
  .base-currency-item {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.25rem;
  }
  
  .impact-warning,
  .impact-item {
    flex-direction: column;
    text-align: center;
    gap: 1rem;
  }
  
  .confirmation-card,
  .error-card {
    flex-direction: column;
    text-align: center;
    gap: 1rem;
  }
  
  .error-actions {
    flex-direction: column;
  }
}
</style>