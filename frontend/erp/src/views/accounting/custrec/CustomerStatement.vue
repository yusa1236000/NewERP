<template>
  <div class="customer-statement">
    <!-- Header Section -->
    <div class="page-header no-print">
      <div class="header-content">
        <div class="header-left">
          <h1 class="page-title">
            <i class="fas fa-file-alt"></i>
            Customer Statement
          </h1>
          <p class="page-subtitle">Detailed account statement for customer receivable</p>
        </div>
        <div class="header-actions">
          <router-link to="/accounting/receivables" class="btn btn-ghost">
            <i class="fas fa-arrow-left"></i>
            Back to Receivables
          </router-link>
          <select v-model="displayCurrency" @change="loadStatement" class="currency-selector no-print">
            <option value="">Original Currency</option>
            <option v-for="currency in availableCurrencies" :key="currency" :value="currency">
              Convert to {{ currency }}
            </option>
          </select>
          <button @click="printStatement" class="btn btn-outline">
            <i class="fas fa-print"></i>
            Print
          </button>
          <button @click="exportStatement" class="btn btn-outline">
            <i class="fas fa-download"></i>
            Export PDF
          </button>
        </div>
      </div>
    </div>

    <!-- Currency Conversion Notice -->
    <div v-if="displayCurrency && statementData" class="conversion-notice no-print">
      <div class="notice-content">
        <i class="fas fa-exchange-alt"></i>
        <div>
          <h4>Currency Conversion Applied</h4>
          <p>All amounts are converted from {{ statementData.original_currency }} to {{ displayCurrency }}</p>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="loading-state">
      <div class="loading-spinner"></div>
      <p>Loading statement...</p>
    </div>

    <!-- Statement Content -->
    <div v-else-if="statementData" class="statement-content">
      <!-- Statement Header -->
      <div class="statement-header">
        <div class="company-info">
          <h1 class="company-name">Your Company Name</h1>
          <div class="company-details">
            <p>123 Business Street</p>
            <p>City, State 12345</p>
            <p>Phone: (555) 123-4567</p>
            <p>Email: info@company.com</p>
          </div>
        </div>
        
        <div class="statement-meta">
          <h1 class="statement-title">ACCOUNT STATEMENT</h1>
          <div class="meta-grid">
            <div class="meta-item">
              <span class="label">Statement Date:</span>
              <span class="value">{{ formatDate(new Date()) }}</span>
            </div>
            <div class="meta-item">
              <span class="label">Receivable ID:</span>
              <span class="value">#{{ statementData.receivable.receivable_id }}</span>
            </div>
            <div class="meta-item">
              <span class="label">Invoice Number:</span>
              <span class="value">#{{ statementData.invoice?.invoice_number || statementData.receivable.invoice_id }}</span>
            </div>
            <div v-if="displayCurrency" class="meta-item">
              <span class="label">Display Currency:</span>
              <span class="value">{{ displayCurrency }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Customer Information -->
      <div class="customer-section">
        <div class="section-title">
          <h3>CUSTOMER INFORMATION</h3>
        </div>
        <div class="customer-grid">
          <div class="customer-details">
            <h4>{{ statementData.customer?.name }}</h4>
            <p v-if="statementData.customer?.customer_code">
              Customer Code: {{ statementData.customer.customer_code }}
            </p>
            <p v-if="statementData.customer?.email">
              <i class="fas fa-envelope"></i>
              {{ statementData.customer.email }}
            </p>
            <p v-if="statementData.customer?.phone">
              <i class="fas fa-phone"></i>
              {{ statementData.customer.phone }}
            </p>
            <p v-if="statementData.customer?.address">
              <i class="fas fa-map-marker-alt"></i>
              {{ statementData.customer.address }}
            </p>
          </div>
        </div>
      </div>

      <!-- Account Summary -->
      <div class="summary-section">
        <div class="section-title">
          <h3>ACCOUNT SUMMARY</h3>
        </div>
        <div class="summary-grid">
          <div class="summary-item">
            <span class="label">Original Amount</span>
            <span class="value">
              {{ formatCurrencyAmount(statementData.receivable.display_amount || statementData.receivable.amount) }}
            </span>
          </div>
          <div class="summary-item">
            <span class="label">Total Payments</span>
            <span class="value credit">
              {{ formatCurrencyAmount(statementData.receivable.display_paid_amount || statementData.receivable.paid_amount) }}
            </span>
          </div>
          <div class="summary-item highlight">
            <span class="label">Outstanding Balance</span>
            <span class="value">
              {{ formatCurrencyAmount(statementData.receivable.display_balance || statementData.receivable.balance) }}
            </span>
          </div>
          <div class="summary-item">
            <span class="label">Due Date</span>
            <span class="value" :class="{ overdue: isOverdue(statementData.receivable.due_date) }">
              {{ formatDate(statementData.receivable.due_date) }}
            </span>
          </div>
          <div class="summary-item">
            <span class="label">Status</span>
            <span class="status-badge" :class="statementData.receivable.status?.toLowerCase()">
              {{ statementData.receivable.status }}
            </span>
          </div>
          <div class="summary-item">
            <span class="label">Currency</span>
            <span class="value">{{ statementData.display_currency || statementData.original_currency }}</span>
          </div>
        </div>
      </div>

      <!-- Invoice Details -->
      <div v-if="statementData.invoice" class="invoice-section">
        <div class="section-title">
          <h3>INVOICE DETAILS</h3>
        </div>
        <div class="invoice-grid">
          <div class="invoice-item">
            <span class="label">Invoice Number</span>
            <span class="value">#{{ statementData.invoice.invoice_number }}</span>
          </div>
          <div class="invoice-item">
            <span class="label">Invoice Date</span>
            <span class="value">{{ formatDate(statementData.invoice.invoice_date) }}</span>
          </div>
          <div class="invoice-item">
            <span class="label">Invoice Total</span>
            <span class="value">{{ formatCurrency(statementData.invoice.total_amount, statementData.original_currency) }}</span>
          </div>
          <div class="invoice-item">
            <span class="label">Invoice Status</span>
            <span class="status-badge" :class="statementData.invoice.status?.toLowerCase()">
              {{ statementData.invoice.status }}
            </span>
          </div>
        </div>
      </div>

      <!-- Payment History -->
      <div class="payments-section">
        <div class="section-title">
          <h3>PAYMENT HISTORY</h3>
        </div>
        
        <div v-if="statementData.payments && statementData.payments.length > 0" class="payments-content">
          <div class="payments-table">
            <table>
              <thead>
                <tr>
                  <th>Payment Date</th>
                  <th>Amount</th>
                  <th>Method</th>
                  <th>Reference</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="payment in statementData.payments" :key="payment.payment_id">
                  <td>{{ formatDate(payment.payment_date) }}</td>
                  <td class="amount">
                    {{ formatCurrencyAmount(payment.display_amount || payment.amount) }}
                  </td>
                  <td>{{ payment.payment_method || 'N/A' }}</td>
                  <td>{{ payment.reference_number || 'N/A' }}</td>
                  <td>
                    <span class="status-badge success">Processed</span>
                  </td>
                </tr>
              </tbody>
              <tfoot>
                <tr class="total-row">
                  <td><strong>Total Payments</strong></td>
                  <td class="amount">
                    <strong>{{ formatCurrencyAmount(statementData.receivable.display_paid_amount || statementData.receivable.paid_amount) }}</strong>
                  </td>
                  <td colspan="3"></td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
        
        <div v-else class="no-payments">
          <div class="no-payments-content">
            <i class="fas fa-info-circle"></i>
            <p>No payments have been recorded for this receivable.</p>
          </div>
        </div>
      </div>

      <!-- Account Activity Timeline -->
      <div class="activity-section">
        <div class="section-title">
          <h3>ACCOUNT ACTIVITY</h3>
        </div>
        
        <div class="activity-timeline">
          <!-- Receivable Creation -->
          <div class="timeline-item">
            <div class="timeline-marker receivable">
              <i class="fas fa-file-invoice"></i>
            </div>
            <div class="timeline-content">
              <h4>Receivable Created</h4>
              <p>Original amount: {{ formatCurrencyAmount(statementData.receivable.amount, statementData.original_currency) }}</p>
              <span class="timeline-date">{{ formatDate(statementData.receivable.created_at) }}</span>
            </div>
          </div>
          
          <!-- Payments -->
          <div 
            v-for="payment in statementData.payments" 
            :key="`payment-${payment.payment_id}`"
            class="timeline-item"
          >
            <div class="timeline-marker payment">
              <i class="fas fa-money-bill"></i>
            </div>
            <div class="timeline-content">
              <h4>Payment Received</h4>
              <p>Amount: {{ formatCurrencyAmount(payment.display_amount || payment.amount) }}</p>
              <p v-if="payment.payment_method">Method: {{ payment.payment_method }}</p>
              <p v-if="payment.reference_number">Reference: {{ payment.reference_number }}</p>
              <span class="timeline-date">{{ formatDate(payment.payment_date) }}</span>
            </div>
          </div>
          
          <!-- Current Status -->
          <div class="timeline-item current">
            <div class="timeline-marker current">
              <i class="fas fa-clock"></i>
            </div>
            <div class="timeline-content">
              <h4>Current Status: {{ statementData.receivable.status }}</h4>
              <p>Outstanding balance: {{ formatCurrencyAmount(statementData.receivable.display_balance || statementData.receivable.balance) }}</p>
              <span class="timeline-date">As of {{ formatDate(new Date()) }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Statement Footer -->
      <div class="statement-footer">
        <div class="footer-content">
          <div class="footer-section">
            <h4>Payment Information</h4>
            <p>Please remit payment to the address above or contact us for electronic payment options.</p>
            <p>Include invoice number #{{ statementData.invoice?.invoice_number || statementData.receivable.invoice_id }} with your payment.</p>
          </div>
          
          <div class="footer-section">
            <h4>Questions?</h4>
            <p>If you have any questions about this statement, please contact our accounts receivable department.</p>
            <p>Phone: (555) 123-4567 | Email: ar@company.com</p>
          </div>
        </div>
        
        <div class="footer-note">
          <p><strong>Important:</strong> This is an electronically generated statement. Please verify all information and contact us immediately if you notice any discrepancies.</p>
          <p class="generation-info">Statement generated on {{ formatDateTime(new Date()) }}</p>
        </div>
      </div>
    </div>

    <!-- Error State -->
    <div v-else class="error-state">
      <i class="fas fa-exclamation-triangle"></i>
      <h3>Statement Not Available</h3>
      <p>Unable to generate statement for this receivable.</p>
      <router-link to="/accounting/receivables" class="btn btn-primary">
        Back to Receivables
      </router-link>
    </div>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'CustomerStatement',
  data() {
    return {
      statementData: null,
      loading: false,
      displayCurrency: '',
      availableCurrencies: ['USD', 'EUR', 'GBP', 'JPY', 'CAD', 'AUD']
    }
  },
  
  async mounted() {
    // Get display currency from query params if available
    this.displayCurrency = this.$route.query.display_currency || ''
    await this.loadStatement()
  },
  
  methods: {
    async loadStatement() {
      this.loading = true
      try {
        const receivableId = this.$route.params.id
        const params = {}
        
        if (this.displayCurrency) {
          params.display_currency = this.displayCurrency
        }
        
        const response = await axios.get(`/accounting/receivables/${receivableId}/statement`, { params })
        this.statementData = response.data.data
        
      } catch (error) {
        console.error('Error loading statement:', error)
        this.$toast?.error('Failed to load statement')
      } finally {
        this.loading = false
      }
    },
    
    printStatement() {
      window.print()
    },
    
    exportStatement() {
      // Implementation for PDF export would go here
      // This could use a library like jsPDF or send request to backend
      this.$toast?.info('PDF export functionality would be implemented here')
    },
    
    isOverdue(dueDate) {
      return new Date(dueDate) < new Date()
    },
    
    formatDate(date) {
      if (!date) return 'N/A'
      return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      })
    },
    
    formatDateTime(date) {
      if (!date) return 'N/A'
      return new Date(date).toLocaleString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
        hour12: true
      })
    },
    
    formatCurrency(amount, currencyCode) {
      return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: currencyCode || 'USD'
      }).format(amount || 0)
    },
    
    formatCurrencyAmount(amount) {
      const currency = this.displayCurrency || this.statementData?.original_currency || 'USD'
      return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: currency
      }).format(amount || 0)
    }
  }
}
</script>

<style scoped>
.customer-statement {
  max-width: 1000px;
  margin: 0 auto;
  padding: 2rem;
  background: var(--gray-50);
  min-height: 100vh;
}

/* No-print elements */
@media print {
  .no-print {
    display: none !important;
  }
  
  .customer-statement {
    padding: 0;
    background: white;
  }
  
  .statement-content {
    box-shadow: none;
    margin: 0;
  }
}

/* Header Section */
.page-header {
  background: white;
  border-radius: 12px;
  padding: 2rem;
  margin-bottom: 2rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.page-title {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  color: var(--gray-800);
  font-size: 1.875rem;
  font-weight: 700;
  margin-bottom: 0.5rem;
}

.page-subtitle {
  color: var(--gray-600);
  font-size: 1rem;
}

.header-actions {
  display: flex;
  gap: 1rem;
  align-items: center;
}

.currency-selector {
  padding: 0.5rem;
  border: 1px solid var(--gray-300);
  border-radius: 6px;
  background: white;
  font-size: 0.875rem;
}

/* Conversion Notice */
.conversion-notice {
  background: #eff6ff;
  border: 1px solid #3b82f6;
  color: #1e40af;
  border-radius: 12px;
  padding: 1rem;
  margin-bottom: 2rem;
}

.notice-content {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.notice-content i {
  font-size: 1.25rem;
}

.notice-content h4 {
  margin-bottom: 0.25rem;
}

/* Loading State */
.loading-state {
  text-align: center;
  padding: 4rem;
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.loading-spinner {
  width: 40px;
  height: 40px;
  border: 3px solid var(--gray-200);
  border-top-color: var(--primary-color);
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 1rem;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Statement Content */
.statement-content {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  overflow: hidden;
}

/* Statement Header */
.statement-header {
  display: flex;
  justify-content: space-between;
  align-items: start;
  padding: 2rem;
  border-bottom: 2px solid var(--gray-300);
  background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
}

.company-name {
  font-size: 2rem;
  font-weight: 700;
  color: var(--gray-800);
  margin-bottom: 1rem;
}

.company-details p {
  color: var(--gray-600);
  margin-bottom: 0.25rem;
}

.statement-title {
  text-align: right;
  font-size: 2rem;
  font-weight: 700;
  color: var(--gray-800);
  margin-bottom: 1rem;
  letter-spacing: 2px;
}

.meta-grid {
  display: grid;
  gap: 0.5rem;
  text-align: right;
}

.meta-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  min-width: 250px;
}

.meta-item .label {
  color: var(--gray-600);
  font-weight: 500;
}

.meta-item .value {
  color: var(--gray-800);
  font-weight: 600;
}

/* Section Styles */
.customer-section,
.summary-section,
.invoice-section,
.payments-section,
.activity-section {
  padding: 2rem;
  border-bottom: 1px solid var(--gray-200);
}

.section-title {
  margin-bottom: 1.5rem;
  padding-bottom: 0.5rem;
  border-bottom: 2px solid var(--gray-300);
}

.section-title h3 {
  color: var(--gray-800);
  font-size: 1.125rem;
  font-weight: 600;
  margin: 0;
  letter-spacing: 1px;
}

/* Customer Details */
.customer-details h4 {
  color: var(--gray-800);
  margin-bottom: 0.75rem;
  font-size: 1.25rem;
}

.customer-details p {
  color: var(--gray-600);
  margin-bottom: 0.5rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.customer-details i {
  color: var(--primary-color);
  width: 16px;
}

/* Summary Grid */
.summary-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}

.summary-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
  background: var(--gray-50);
  border-radius: 8px;
  border: 1px solid var(--gray-200);
}

.summary-item.highlight {
  background: var(--primary-color);
  color: white;
  border-color: var(--primary-color);
}

.summary-item .label {
  font-weight: 500;
  font-size: 0.875rem;
}

.summary-item .value {
  font-weight: 600;
  font-size: 1rem;
}

.summary-item .value.credit {
  color: var(--success-color);
}

.summary-item .value.overdue {
  color: var(--danger-color);
}

.summary-item.highlight .value.credit {
  color: #d1fae5;
}

/* Status Badges */
.status-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 500;
  text-transform: uppercase;
}

.status-badge.outstanding {
  background: #fef3c7;
  color: #92400e;
}

.status-badge.overdue {
  background: #fee2e2;
  color: #dc2626;
}

.status-badge.paid {
  background: #dcfce7;
  color: #15803d;
}

.status-badge.partial {
  background: #dbeafe;
  color: #1d4ed8;
}

.status-badge.success {
  background: #dcfce7;
  color: #15803d;
}

/* Invoice Grid */
.invoice-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}

.invoice-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem;
  background: var(--gray-50);
  border-radius: 6px;
}

.invoice-item .label {
  font-weight: 500;
  color: var(--gray-600);
  font-size: 0.875rem;
}

.invoice-item .value {
  font-weight: 600;
  color: var(--gray-800);
}

/* Payments Table */
.payments-table {
  overflow-x: auto;
}

.payments-table table {
  width: 100%;
  border-collapse: collapse;
}

.payments-table th,
.payments-table td {
  padding: 1rem;
  text-align: left;
  border-bottom: 1px solid var(--gray-200);
}

.payments-table th {
  background: var(--gray-50);
  font-weight: 600;
  color: var(--gray-700);
  font-size: 0.875rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.payments-table .amount {
  text-align: right;
  font-weight: 600;
}

.total-row {
  background: var(--gray-100);
  border-top: 2px solid var(--gray-300);
}

.no-payments {
  text-align: center;
  padding: 3rem;
}

.no-payments-content {
  color: var(--gray-500);
}

.no-payments-content i {
  font-size: 2rem;
  margin-bottom: 1rem;
}

/* Activity Timeline */
.activity-timeline {
  position: relative;
}

.activity-timeline::before {
  content: '';
  position: absolute;
  left: 30px;
  top: 0;
  bottom: 0;
  width: 2px;
  background: var(--gray-300);
}

.timeline-item {
  position: relative;
  display: flex;
  margin-bottom: 2rem;
  padding-left: 4rem;
}

.timeline-marker {
  position: absolute;
  left: 0;
  top: 0;
  width: 60px;
  height: 60px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  color: white;
  z-index: 1;
}

.timeline-marker.receivable {
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

.timeline-marker.payment {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.timeline-marker.current {
  background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
}

.timeline-content {
  flex: 1;
  background: var(--gray-50);
  border-radius: 12px;
  padding: 1.5rem;
  border-left: 4px solid var(--gray-300);
  margin-left: 1rem;
}

.timeline-item:has(.timeline-marker.receivable) .timeline-content {
  border-left-color: #f59e0b;
}

.timeline-item:has(.timeline-marker.payment) .timeline-content {
  border-left-color: #10b981;
}

.timeline-item:has(.timeline-marker.current) .timeline-content {
  border-left-color: #6366f1;
}

.timeline-content h4 {
  color: var(--gray-800);
  margin-bottom: 0.5rem;
}

.timeline-content p {
  color: var(--gray-600);
  margin-bottom: 0.25rem;
}

.timeline-date {
  font-size: 0.875rem;
  color: var(--gray-500);
  font-style: italic;
}

/* Statement Footer */
.statement-footer {
  padding: 2rem;
  background: var(--gray-50);
  border-top: 2px solid var(--gray-300);
}

.footer-content {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 2rem;
  margin-bottom: 2rem;
}

.footer-section h4 {
  color: var(--gray-800);
  margin-bottom: 0.75rem;
}

.footer-section p {
  color: var(--gray-600);
  margin-bottom: 0.5rem;
  line-height: 1.5;
}

.footer-note {
  border-top: 1px solid var(--gray-300);
  padding-top: 1.5rem;
  text-align: center;
}

.footer-note p {
  color: var(--gray-600);
  margin-bottom: 0.5rem;
}

.generation-info {
  font-size: 0.875rem;
  color: var(--gray-500);
  font-style: italic;
}

/* Error State */
.error-state {
  text-align: center;
  padding: 4rem;
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.error-state i {
  font-size: 3rem;
  color: var(--danger-color);
  margin-bottom: 1rem;
}

.error-state h3 {
  margin-bottom: 0.5rem;
  color: var(--gray-700);
}

.error-state p {
  color: var(--gray-600);
  margin-bottom: 2rem;
}

/* Button Styles */
.btn {
  padding: 0.5rem 1rem;
  border-radius: 6px;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  border: 1px solid transparent;
}

.btn-primary {
  background: var(--primary-color);
  color: white;
}

.btn-primary:hover {
  background: var(--primary-dark);
}

.btn-outline {
  border-color: var(--gray-300);
  color: var(--gray-700);
  background: white;
}

.btn-outline:hover {
  background: var(--gray-100);
}

.btn-ghost {
  color: var(--gray-600);
  background: transparent;
}

.btn-ghost:hover {
  background: var(--gray-100);
}

/* Responsive Design */
@media (max-width: 1024px) {
  .customer-statement {
    padding: 1rem;
  }
  
  .statement-header {
    flex-direction: column;
    gap: 2rem;
    text-align: center;
  }
  
  .statement-title {
    text-align: center;
  }
  
  .meta-grid {
    text-align: center;
  }
  
  .meta-item {
    justify-content: center;
  }
}

@media (max-width: 768px) {
  .header-content {
    flex-direction: column;
    gap: 1rem;
    align-items: stretch;
  }
  
  .summary-grid,
  .invoice-grid {
    grid-template-columns: 1fr;
  }
  
  .footer-content {
    grid-template-columns: 1fr;
  }
  
  .payments-table th,
  .payments-table td {
    padding: 0.5rem;
    font-size: 0.875rem;
  }
  
  .timeline-item {
    padding-left: 3rem;
  }
  
  .timeline-marker {
    width: 40px;
    height: 40px;
    font-size: 1rem;
  }
  
  .activity-timeline::before {
    left: 20px;
  }
}
</style>