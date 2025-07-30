<template>
  <AppLayout>
    <div class="tax-detail-container">
      <!-- Page Header -->
      <div class="page-header">
        <div class="header-content">
          <div class="breadcrumb">
            <router-link to="/tax-transactions" class="breadcrumb-link">
              <i class="fas fa-receipt"></i>
              Tax Transactions
            </router-link>
            <span class="breadcrumb-separator">
              <i class="fas fa-chevron-right"></i>
            </span>
            <span class="breadcrumb-current">Transaction Detail</span>
          </div>

          <div class="title-section">
            <div>
              <h1 class="page-title">
                <i class="fas fa-receipt"></i>
                Tax Transaction Detail
              </h1>
              <div v-if="transaction" class="title-actions">
                <span class="transaction-id">#{{ transaction.tax_id }}</span>
                <span class="status-badge" :class="getStatusClass(transaction.status)">
                  <i class="fas fa-circle"></i>
                  {{ transaction.status }}
                </span>
              </div>
            </div>

            <div class="header-actions">
              <button 
                v-if="canEdit" 
                @click="router.push(`/tax-transactions/${transaction.tax_id}/edit`)" 
                class="btn btn-primary"
              >
                <i class="fas fa-edit"></i>
                Edit Transaction
              </button>
              
              <button @click="printTransaction" class="btn btn-outline">
                <i class="fas fa-print"></i>
                Print
              </button>
              
              <button @click="exportPDF" class="btn btn-outline">
                <i class="fas fa-file-pdf"></i>
                Export PDF
              </button>
              
              <div class="dropdown">
                <button class="btn btn-outline dropdown-toggle">
                  <i class="fas fa-ellipsis-v"></i>
                  More Actions
                </button>
                <div class="dropdown-menu">
                  <button @click="duplicateTransaction" class="dropdown-item">
                    <i class="fas fa-copy"></i>
                    Duplicate Transaction
                  </button>
                  <button v-if="canArchive" @click="archiveTransaction" class="dropdown-item">
                    <i class="fas fa-archive"></i>
                    Archive Transaction
                  </button>
                  <div class="dropdown-divider"></div>
                  <button 
                    v-if="canDelete" 
                    @click="deleteTransaction" 
                    class="dropdown-item danger"
                  >
                    <i class="fas fa-trash"></i>
                    Delete Transaction
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="loading-container">
        <div class="loading-spinner">
          <i class="fas fa-spinner fa-spin"></i>
        </div>
        <p>Loading transaction details...</p>
      </div>

      <!-- Main Content -->
      <div v-else-if="transaction" class="main-content">
        <!-- Quick Info Cards -->
        <div class="quick-info-grid">
          <div class="info-card amount">
            <div class="info-icon">
              <i class="fas fa-dollar-sign"></i>
            </div>
            <div class="info-content">
              <h3>{{ transaction.currency }} {{ formatCurrency(transaction.tax_amount) }}</h3>
              <p>Tax Amount</p>
            </div>
          </div>
          
          <div class="info-card date">
            <div class="info-icon">
              <i class="fas fa-calendar-alt"></i>
            </div>
            <div class="info-content">
              <h3>{{ formatDate(transaction.transaction_date) }}</h3>
              <p>Transaction Date</p>
            </div>
          </div>
          
          <div class="info-card type">
            <div class="info-icon">
              <i class="fas fa-tag"></i>
            </div>
            <div class="info-content">
              <h3>{{ transaction.tax_type }}</h3>
              <p>Tax Type</p>
            </div>
          </div>
          
          <div class="info-card reference">
            <div class="info-icon">
              <i class="fas fa-link"></i>
            </div>
            <div class="info-content">
              <h3>{{ transaction.reference_type }}</h3>
              <p>Reference Type</p>
            </div>
          </div>
        </div>

        <!-- Main Details -->
        <div class="details-grid">
          <!-- Transaction Information -->
          <div class="detail-card">
            <div class="card-header">
              <h2>
                <i class="fas fa-info-circle"></i>
                Transaction Information
              </h2>
              <div class="card-actions">
                <button @click="copyToClipboard(transaction.tax_id)" class="btn-icon" title="Copy Transaction ID">
                  <i class="fas fa-copy"></i>
                </button>
              </div>
            </div>
            
            <div class="detail-grid">
              <div class="detail-item">
                <span class="detail-label">Transaction ID</span>
                <span class="detail-value">#{{ transaction.tax_id }}</span>
              </div>
              
              <div class="detail-item">
                <span class="detail-label">Tax Type</span>
                <span class="detail-value tax-type">
                  <i class="fas fa-tag"></i>
                  {{ transaction.tax_type }}
                </span>
              </div>
              
              <div class="detail-item">
                <span class="detail-label">Tax Code</span>
                <span class="detail-value tax-code">{{ transaction.tax_code }}</span>
              </div>
              
              <div class="detail-item">
                <span class="detail-label">Tax Rate</span>
                <span class="detail-value">{{ transaction.tax_rate }}%</span>
              </div>
              
              <div class="detail-item">
                <span class="detail-label">Transaction Date</span>
                <span class="detail-value">{{ formatDate(transaction.transaction_date) }}</span>
              </div>
              
              <div class="detail-item">
                <span class="detail-label">Status</span>
                <span class="detail-value">
                  <span class="status-badge" :class="getStatusClass(transaction.status)">
                    <i class="fas fa-circle"></i>
                    {{ transaction.status }}
                  </span>
                </span>
              </div>
              
              <div class="detail-item" v-if="transaction.description">
                <span class="detail-label">Description</span>
                <span class="detail-value">{{ transaction.description }}</span>
              </div>
            </div>
          </div>

          <!-- Amount Information -->
          <div class="detail-card">
            <div class="card-header">
              <h2>
                <i class="fas fa-calculator"></i>
                Amount Information
              </h2>
            </div>
            
            <div class="detail-grid">
              <div class="detail-item">
                <span class="detail-label">Currency</span>
                <span class="detail-value">{{ transaction.currency }}</span>
              </div>
              
              <div class="detail-item">
                <span class="detail-label">Tax Amount</span>
                <span class="detail-value amount">{{ transaction.currency }} {{ formatCurrency(transaction.tax_amount) }}</span>
              </div>
              
              <div class="detail-item" v-if="transaction.taxable_amount">
                <span class="detail-label">Taxable Amount</span>
                <span class="detail-value amount">{{ transaction.currency }} {{ formatCurrency(transaction.taxable_amount) }}</span>
              </div>
              
              <div class="detail-item" v-if="transaction.exchange_rate && transaction.currency !== 'USD'">
                <span class="detail-label">Exchange Rate</span>
                <span class="detail-value">1 {{ transaction.currency }} = {{ formatCurrency(transaction.exchange_rate) }} USD</span>
              </div>
              
              <div class="detail-item" v-if="transaction.base_currency_amount">
                <span class="detail-label">Base Currency Amount</span>
                <span class="detail-value amount">USD {{ formatCurrency(transaction.base_currency_amount) }}</span>
              </div>
              
              <div class="detail-item" v-if="transaction.base_currency_taxable_amount">
                <span class="detail-label">Base Currency Taxable Amount</span>
                <span class="detail-value amount">USD {{ formatCurrency(transaction.base_currency_taxable_amount) }}</span>
              </div>
            </div>
          </div>

          <!-- Reference Information -->
          <div class="detail-card">
            <div class="card-header">
              <h2>
                <i class="fas fa-external-link-alt"></i>
                Reference Information
              </h2>
              <div class="card-actions">
                <button 
                  @click="viewReference" 
                  class="btn-small btn-primary"
                  v-if="transaction.reference_id"
                >
                  <i class="fas fa-eye"></i>
                  View Reference
                </button>
              </div>
            </div>
            
            <div class="detail-grid">
              <div class="detail-item">
                <span class="detail-label">Reference Type</span>
                <span class="detail-value">{{ transaction.reference_type }}</span>
              </div>
              
              <div class="detail-item">
                <span class="detail-label">Reference ID</span>
                <span class="detail-value">#{{ transaction.reference_id }}</span>
              </div>
              
              <div class="detail-item" v-if="transaction.invoice_number">
                <span class="detail-label">Invoice Number</span>
                <span class="detail-value">{{ transaction.invoice_number }}</span>
              </div>
            </div>

            <div v-if="referenceDetails" class="reference-details">
              <div class="detail-item">
                <span class="detail-label">Reference Description</span>
                <span class="detail-value">{{ referenceDetails.description }}</span>
              </div>
              
              <div class="detail-item" v-if="referenceDetails.amount">
                <span class="detail-label">Reference Amount</span>
                <span class="detail-value amount">${{ formatCurrency(referenceDetails.amount) }}</span>
              </div>
              
              <div class="detail-item" v-if="referenceDetails.date">
                <span class="detail-label">Reference Date</span>
                <span class="detail-value">{{ formatDate(referenceDetails.date) }}</span>
              </div>
            </div>
          </div>

          <!-- Supplier Information (if available) -->
          <div class="detail-card" v-if="transaction.supplier_name || transaction.supplier_tax_id">
            <div class="card-header">
              <h2>
                <i class="fas fa-building"></i>
                Supplier Information
              </h2>
            </div>
            
            <div class="detail-grid">
              <div class="detail-item" v-if="transaction.supplier_name">
                <span class="detail-label">Supplier Name</span>
                <span class="detail-value">{{ transaction.supplier_name }}</span>
              </div>
              
              <div class="detail-item" v-if="transaction.supplier_tax_id">
                <span class="detail-label">Supplier Tax ID</span>
                <span class="detail-value">{{ transaction.supplier_tax_id }}</span>
              </div>
            </div>
          </div>

          <!-- Currency Summary (if available) -->
          <div class="detail-card" v-if="currencySummary">
            <div class="card-header">
              <h2>
                <i class="fas fa-chart-line"></i>
                Currency Summary
              </h2>
            </div>
            
            <div class="detail-grid">
              <div class="detail-item" v-for="(summary, currency) in currencySummary" :key="currency">
                <span class="detail-label">{{ currency }} Total</span>
                <span class="detail-value amount">{{ currency }} {{ formatCurrency(summary.total_amount) }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Tax Calculations -->
        <div class="calculation-section">
          <div class="calculation-card">
            <div class="card-header">
              <h2>
                <i class="fas fa-calculator"></i>
                Tax Calculations
              </h2>
            </div>
            
            <div class="calculation-content">
              <div class="calc-breakdown">
                <div class="calc-item" v-if="transaction.taxable_amount">
                  <span class="calc-label">Taxable Amount</span>
                  <span class="calc-value">{{ transaction.currency }} {{ formatCurrency(transaction.taxable_amount) }}</span>
                </div>
                
                <div class="calc-item" v-if="transaction.tax_rate">
                  <span class="calc-label">Tax Rate</span>
                  <span class="calc-value">{{ transaction.tax_rate }}%</span>
                </div>
                
                <div class="calc-item">
                  <span class="calc-label">Tax Amount</span>
                  <span class="calc-value highlight">{{ transaction.currency }} {{ formatCurrency(transaction.tax_amount) }}</span>
                </div>
                
                <div class="calc-divider" v-if="transaction.taxable_amount"></div>
                
                <div class="calc-item total" v-if="transaction.taxable_amount">
                  <span class="calc-label">Total Amount</span>
                  <span class="calc-value">{{ transaction.currency }} {{ formatCurrency(parseFloat(transaction.taxable_amount) + parseFloat(transaction.tax_amount)) }}</span>
                </div>

                <!-- Base Currency Calculations (if different from transaction currency) -->
                <div v-if="transaction.currency !== 'USD' && transaction.base_currency_amount" class="base-currency-section">
                  <div class="calc-divider"></div>
                  <h4>Base Currency (USD)</h4>
                  
                  <div class="calc-item" v-if="transaction.base_currency_taxable_amount">
                    <span class="calc-label">Base Taxable Amount</span>
                    <span class="calc-value">USD {{ formatCurrency(transaction.base_currency_taxable_amount) }}</span>
                  </div>
                  
                  <div class="calc-item">
                    <span class="calc-label">Base Tax Amount</span>
                    <span class="calc-value highlight">USD {{ formatCurrency(transaction.base_currency_amount) }}</span>
                  </div>
                  
                  <div class="calc-item total" v-if="transaction.base_currency_taxable_amount">
                    <span class="calc-label">Base Total Amount</span>
                    <span class="calc-value">USD {{ formatCurrency(parseFloat(transaction.base_currency_taxable_amount) + parseFloat(transaction.base_currency_amount)) }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Timeline Section -->
        <div class="timeline-section">
          <div class="timeline-card">
            <div class="card-header">
              <h2>
                <i class="fas fa-history"></i>
                Transaction Timeline
              </h2>
              <button @click="refreshTimeline" class="btn-icon" title="Refresh Timeline">
                <i class="fas fa-sync-alt"></i>
              </button>
            </div>
            
            <div class="timeline-content">
              <div class="timeline">
                <div v-for="(event, index) in timeline" :key="index" class="timeline-item" :class="event.type">
                  <div class="timeline-marker">
                    <i :class="event.icon"></i>
                  </div>
                  <div class="timeline-content-item">
                    <div class="timeline-header">
                      <h4>{{ event.title }}</h4>
                      <span class="timeline-date">{{ formatDateTime(event.date) }}</span>
                    </div>
                    <p>{{ event.description }}</p>
                    <span class="timeline-user">by {{ event.user }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Related Documents -->
        <div class="documents-section">
          <div class="documents-card">
            <div class="card-header">
              <h2>
                <i class="fas fa-paperclip"></i>
                Related Documents
              </h2>
              <button @click="uploadDocument" class="btn-small btn-outline">
                <i class="fas fa-upload"></i>
                Upload Document
              </button>
            </div>
            
            <div v-if="documents.length > 0" class="documents-grid">
              <div v-for="doc in documents" :key="doc.id" class="document-item">
                <div class="document-icon" :class="getDocumentIconClass(doc.type)">
                  <i :class="getDocumentIcon(doc.type)"></i>
                </div>
                <div class="document-info">
                  <h4>{{ doc.name }}</h4>
                  <p>{{ doc.size }} • {{ formatDate(doc.uploaded_at) }}</p>
                </div>
                <div class="document-actions">
                  <button @click="downloadDocument(doc)" class="btn-icon">
                    <i class="fas fa-download"></i>
                  </button>
                  <button @click="previewDocument(doc)" class="btn-icon">
                    <i class="fas fa-eye"></i>
                  </button>
                </div>
              </div>
            </div>
            
            <div v-else class="empty-documents">
              <div class="empty-icon">
                <i class="fas fa-file-alt"></i>
              </div>
              <h3>No documents attached</h3>
              <p>Upload relevant documents for this tax transaction</p>
              <button @click="uploadDocument" class="btn btn-primary">
                <i class="fas fa-upload"></i>
                Upload First Document
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Error State -->
      <div v-else class="error-container">
        <div class="error-icon">
          <i class="fas fa-exclamation-triangle"></i>
        </div>
        <h2>Transaction Not Found</h2>
        <p>The requested tax transaction could not be found or you don't have permission to view it.</p>
        <router-link to="/tax-transactions" class="btn btn-primary">
          <i class="fas fa-arrow-left"></i>
          Back to Tax Transactions
        </router-link>
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
            <strong>Transaction ID:</strong> #{{ transaction?.tax_id }}<br>
            <strong>Amount:</strong> {{ transaction?.currency }} {{ formatCurrency(transaction?.tax_amount) }}<br>
            <strong>Date:</strong> {{ formatDate(transaction?.transaction_date) }}
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
  </AppLayout>
</template>

<script>
/* eslint-disable */
import { ref, reactive, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import AppLayout from '@/layouts/AppLayout.vue'


export default {
  name: 'TaxTransactionDetail',
  components: {
    AppLayout
  },
  setup() {
    const route = useRoute()
    const router = useRouter()
    
    const loading = ref(true)
    const deleting = ref(false)
    const showDeleteModal = ref(false)
    const transaction = ref(null)
    const currencySummary = ref(null)
    const referenceDetails = ref(null)
    const documents = ref([])
    
    const timeline = ref([
      {
        title: 'Transaction Created',
        description: 'Tax transaction was created in the system',
        date: new Date(),
        user: 'John Doe',
        type: 'success',
        icon: 'fas fa-plus-circle'
      },
      {
        title: 'Status Updated',
        description: 'Transaction status changed to Pending',
        date: new Date(Date.now() - 86400000),
        user: 'Jane Smith',
        type: 'info',
        icon: 'fas fa-edit'
      },
      {
        title: 'Document Attached',
        description: 'Supporting document was uploaded',
        date: new Date(Date.now() - 172800000),
        user: 'Mike Johnson',
        type: 'info',
        icon: 'fas fa-paperclip'
      }
    ])

    // Computed properties
    const canEdit = computed(() => {
      return transaction.value && ['Draft', 'Pending'].includes(transaction.value.status)
    })

    const canDelete = computed(() => {
      return transaction.value && !['Posted', 'Filed', 'Paid'].includes(transaction.value.status)
    })

    const canArchive = computed(() => {
      return transaction.value && transaction.value.status === 'Completed'
    })

    // Methods
    const fetchTransaction = async () => {
      try {
        loading.value = true
        const response = await axios.get(`/api/accounting/tax-transactions/${route.params.id}`)
        
        transaction.value = response.data.data
        currencySummary.value = response.data.currency_summary
        
        // Fetch reference details if available
        if (transaction.value.reference_id && transaction.value.reference_type) {
          await fetchReferenceDetails()
        }
        
      } catch (error) {
        console.error('Error fetching transaction:', error)
        showNotification('Error loading transaction details', 'error')
      } finally {
        loading.value = false
      }
    }

    const fetchReferenceDetails = async () => {
      try {
        // This would be implemented based on reference type
        // For now, we'll use mock data
        referenceDetails.value = {
          description: 'Mock reference description',
          amount: 1000,
          date: new Date()
        }
      } catch (error) {
        console.error('Error fetching reference details:', error)
      }
    }

    const printTransaction = () => {
      window.print()
    }

    const exportPDF = async () => {
      try {
        const response = await axios.get(`/api/accounting/tax-transactions/${transaction.value.tax_id}/pdf`, {
          responseType: 'blob'
        })
        
        const url = window.URL.createObjectURL(new Blob([response.data]))
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', `tax-transaction-${transaction.value.tax_id}.pdf`)
        document.body.appendChild(link)
        link.click()
        link.remove()
        window.URL.revokeObjectURL(url)
        
        showNotification('PDF exported successfully', 'success')
      } catch (error) {
        console.error('Error exporting PDF:', error)
        showNotification('Error exporting PDF', 'error')
      }
    }

    const viewReference = () => {
      const referenceRoutes = {
        'Sales Invoice': `/sales/invoices/${transaction.value.reference_id}`,
        'Purchase Invoice': `/purchase/invoices/${transaction.value.reference_id}`,
        'Sales Order': `/sales/orders/${transaction.value.reference_id}`,
        'Purchase Order': `/purchase/orders/${transaction.value.reference_id}`,
        'Journal Entry': `/accounting/journal-entries/${transaction.value.reference_id}`
      }
      
      const route = referenceRoutes[transaction.value.reference_type]
      if (route) {
        router.push(route)
      }
    }

    const duplicateTransaction = () => {
      router.push({
        path: '/tax-transactions/create',
        query: { duplicate: transaction.value.tax_id }
      })
    }

    const archiveTransaction = async () => {
      try {
        await axios.post(`/api/accounting/tax-transactions/${transaction.value.tax_id}/archive`)
        showNotification('Transaction archived successfully', 'success')
        router.push('/tax-transactions')
      } catch (error) {
        console.error('Error archiving transaction:', error)
        showNotification('Error archiving transaction', 'error')
      }
    }

    const deleteTransaction = () => {
      showDeleteModal.value = true
    }

    const closeDeleteModal = () => {
      showDeleteModal.value = false
    }

    const confirmDelete = async () => {
      deleting.value = true
      try {
        await axios.delete(`/api/accounting/tax-transactions/${transaction.value.tax_id}`)
        showNotification('Transaction deleted successfully', 'success')
        router.push('/tax-transactions')
      } catch (error) {
        console.error('Error deleting transaction:', error)
        if (error.response && error.response.status === 422) {
          showNotification(error.response.data.message, 'error')
        } else {
          showNotification('Error deleting transaction', 'error')
        }
      } finally {
        deleting.value = false
        closeDeleteModal()
      }
    }

    const refreshTimeline = () => {
      showNotification('Timeline refreshed', 'info')
    }

    const uploadDocument = () => {
      // Implement file upload functionality
      showNotification('Document upload functionality coming soon', 'info')
    }

    const downloadDocument = (doc) => {
      showNotification(`Downloading ${doc.name}`, 'info')
    }

    const previewDocument = (doc) => {
      showNotification(`Previewing ${doc.name}`, 'info')
    }

    const copyToClipboard = async (text) => {
      try {
        await navigator.clipboard.writeText(text)
        showNotification('Copied to clipboard', 'success')
      } catch (error) {
        console.error('Error copying to clipboard:', error)
        showNotification('Error copying to clipboard', 'error')
      }
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

    const formatDateTime = (date) => {
      if (!date) return ''
      return new Date(date).toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
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

    const getDocumentIcon = (type) => {
      const iconMap = {
        'pdf': 'fas fa-file-pdf',
        'excel': 'fas fa-file-excel',
        'word': 'fas fa-file-word',
        'image': 'fas fa-file-image',
        'default': 'fas fa-file'
      }
      return iconMap[type] || iconMap.default
    }

    const getDocumentIconClass = (type) => {
      const classMap = {
        'pdf': 'pdf',
        'excel': 'excel',
        'word': 'word',
        'image': 'image',
        'default': 'default'
      }
      return classMap[type] || classMap.default
    }

    const showNotification = (message, type = 'info') => {
      console.log(`${type}: ${message}`)
      // Implement your notification system here
    }

    // Lifecycle
    onMounted(() => {
      fetchTransaction()
    })

    return {
      loading,
      deleting,
      showDeleteModal,
      transaction,
      currencySummary,
      referenceDetails,
      documents,
      timeline,
      router,
      canEdit,
      canDelete,
      canArchive,
      printTransaction,
      exportPDF,
      viewReference,
      duplicateTransaction,
      archiveTransaction,
      deleteTransaction,
      closeDeleteModal,
      confirmDelete,
      refreshTimeline,
      uploadDocument,
      downloadDocument,
      previewDocument,
      copyToClipboard,
      formatDate,
      formatDateTime,
      formatCurrency,
      getStatusClass,
      getDocumentIcon,
      getDocumentIconClass
    }
  }
}
</script>

<style scoped>
.tax-detail-container {
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
  flex-direction: column;
  gap: 1.5rem;
}

.breadcrumb {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.9rem;
}

.breadcrumb-link {
  color: var(--primary-color);
  text-decoration: none;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.breadcrumb-separator {
  color: var(--text-muted);
  font-size: 0.8rem;
}

.breadcrumb-current {
  color: var(--text-secondary);
}

.title-section {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 2rem;
}

.page-title {
  font-size: 2.5rem;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0;
  display: flex;
  align-items: center;
  gap: 1rem;
}

.page-title i {
  color: var(--primary-color);
}

.title-actions {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-top: 0.5rem;
}

.transaction-id {
  background: var(--bg-tertiary);
  padding: 0.5rem 1rem;
  border-radius: 8px;
  font-family: monospace;
  font-weight: 600;
  color: var(--text-primary);
}

.header-actions {
  display: flex;
  gap: 1rem;
  align-items: center;
}

/* Loading and Error States */
.loading-container,
.error-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem 2rem;
  text-align: center;
  background: var(--card-bg);
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.loading-spinner {
  font-size: 3rem;
  color: var(--primary-color);
  margin-bottom: 1rem;
}

.error-icon {
  font-size: 4rem;
  color: #ef4444;
  margin-bottom: 1rem;
}

/* Quick Info Grid */
.quick-info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.info-card {
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

.info-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
}

.info-icon {
  width: 60px;
  height: 60px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  flex-shrink: 0;
}

.info-card.amount .info-icon {
  background: linear-gradient(135deg, #10b981, #059669);
  color: white;
}

.info-card.date .info-icon {
  background: linear-gradient(135deg, #3b82f6, #2563eb);
  color: white;
}

.info-card.type .info-icon {
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  color: white;
}

.info-card.reference .info-icon {
  background: linear-gradient(135deg, #f59e0b, #d97706);
  color: white;
}

.info-content h3 {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0;
}

.info-content p {
  font-size: 0.9rem;
  color: var(--text-secondary);
  margin: 0;
}

/* Details Grid */
.details-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.detail-card {
  background: var(--card-bg);
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  border: 1px solid var(--border-color);
}

.card-header {
  padding: 1.5rem 2rem;
  border-bottom: 1px solid var(--border-color);
  background: var(--bg-tertiary);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.card-header h2 {
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--text-primary);
  margin: 0;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.card-actions {
  display: flex;
  gap: 0.5rem;
}

.detail-grid {
  padding: 2rem;
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.5rem;
}

.detail-item {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.detail-label {
  font-size: 0.8rem;
  color: var(--text-muted);
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.detail-value {
  font-size: 1rem;
  color: var(--text-primary);
  font-weight: 500;
}

.detail-value.amount {
  font-size: 1.25rem;
  color: var(--primary-color);
  font-weight: 700;
}

.detail-value.tax-type {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.detail-value.tax-code {
  font-family: monospace;
  background: var(--bg-tertiary);
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  display: inline-block;
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

/* Calculation Section */
.calculation-section {
  margin-bottom: 2rem;
}

.calculation-card {
  background: var(--card-bg);
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  border: 1px solid var(--border-color);
  overflow: hidden;
}

.calculation-content {
  padding: 2rem;
}

.calc-breakdown {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.calc-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem 0;
}

.calc-label {
  font-size: 1rem;
  color: var(--text-secondary);
  font-weight: 500;
}

.calc-value {
  font-size: 1.1rem;
  color: var(--text-primary);
  font-weight: 600;
}

.calc-value.highlight {
  color: var(--primary-color);
  font-weight: 700;
}

.calc-item.total {
  border-top: 2px solid var(--border-color);
  padding-top: 1rem;
  margin-top: 0.5rem;
}

.calc-item.total .calc-value {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--primary-color);
}

.calc-divider {
  height: 1px;
  background: var(--border-color);
  margin: 1rem 0;
}

.base-currency-section {
  margin-top: 1.5rem;
  padding-top: 1.5rem;
  border-top: 2px solid var(--border-color);
}

.base-currency-section h4 {
  font-size: 1.1rem;
  font-weight: 600;
  color: var(--text-secondary);
  margin-bottom: 1rem;
}

/* Timeline Section */
.timeline-section {
  margin-bottom: 2rem;
}

.timeline-card {
  background: var(--card-bg);
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  border: 1px solid var(--border-color);
  overflow: hidden;
}

.timeline-content {
  padding: 2rem;
}

.timeline {
  position: relative;
}

.timeline::before {
  content: '';
  position: absolute;
  left: 20px;
  top: 0;
  bottom: 0;
  width: 2px;
  background: var(--border-color);
}

.timeline-item {
  position: relative;
  padding-left: 4rem;
  margin-bottom: 2rem;
}

.timeline-item:last-child {
  margin-bottom: 0;
}

.timeline-marker {
  position: absolute;
  left: 0;
  top: 0;
  width: 40px;
  height: 40px;
  background: var(--card-bg);
  border: 3px solid var(--primary-color);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1;
}

.timeline-item.success .timeline-marker {
  border-color: #10b981;
  color: #10b981;
}

.timeline-item.info .timeline-marker {
  border-color: #3b82f6;
  color: #3b82f6;
}

.timeline-item.warning .timeline-marker {
  border-color: #f59e0b;
  color: #f59e0b;
}

.timeline-content-item {
  background: var(--bg-tertiary);
  padding: 1.5rem;
  border-radius: 12px;
  border: 1px solid var(--border-color);
}

.timeline-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.5rem;
}

.timeline-header h4 {
  font-size: 1.1rem;
  font-weight: 600;
  color: var(--text-primary);
  margin: 0;
}

.timeline-date {
  font-size: 0.8rem;
  color: var(--text-muted);
}

.timeline-content-item p {
  color: var(--text-secondary);
  margin: 0 0 0.5rem 0;
  line-height: 1.5;
}

.timeline-user {
  font-size: 0.8rem;
  color: var(--text-muted);
  font-style: italic;
}

/* Documents Section */
.documents-section {
  margin-bottom: 2rem;
}

.documents-card {
  background: var(--card-bg);
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  border: 1px solid var(--border-color);
  overflow: hidden;
}

.documents-grid {
  padding: 2rem;
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1rem;
}

.document-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: var(--bg-tertiary);
  border-radius: 12px;
  border: 1px solid var(--border-color);
  transition: all 0.3s ease;
}

.document-item:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.document-icon {
  width: 40px;
  height: 40px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.2rem;
  flex-shrink: 0;
}

.document-icon.pdf {
  background: rgba(239, 68, 68, 0.1);
  color: #dc2626;
}

.document-icon.excel {
  background: rgba(34, 197, 94, 0.1);
  color: #16a34a;
}

.document-icon.word {
  background: rgba(59, 130, 246, 0.1);
  color: #2563eb;
}

.document-icon.image {
  background: rgba(147, 51, 234, 0.1);
  color: #9333ea;
}

.document-icon.default {
  background: rgba(107, 114, 128, 0.1);
  color: #6b7280;
}

.document-info {
  flex: 1;
  min-width: 0;
}

.document-info h4 {
  font-size: 0.9rem;
  font-weight: 600;
  color: var(--text-primary);
  margin: 0 0 0.25rem 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.document-info p {
  font-size: 0.8rem;
  color: var(--text-muted);
  margin: 0;
}

.document-actions {
  display: flex;
  gap: 0.25rem;
}

.empty-documents {
  padding: 3rem 2rem;
  text-align: center;
}

.empty-icon {
  font-size: 3rem;
  color: var(--text-muted);
  margin-bottom: 1rem;
}

.empty-documents h3 {
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--text-primary);
  margin: 0 0 0.5rem 0;
}

.empty-documents p {
  color: var(--text-secondary);
  margin: 0 0 1.5rem 0;
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

.btn-small {
  padding: 0.5rem 1rem;
  font-size: 0.8rem;
}

.btn-icon {
  width: 36px;
  height: 36px;
  padding: 0;
  border-radius: 8px;
  background: var(--bg-tertiary);
  border: 1px solid var(--border-color);
  color: var(--text-secondary);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.3s ease;
}

.btn-icon:hover {
  background: var(--primary-color);
  color: white;
  border-color: var(--primary-color);
  transform: translateY(-1px);
}

/* Dropdown */
.dropdown {
  position: relative;
  display: inline-block;
}

.dropdown-toggle {
  background: transparent;
  border: 2px solid var(--border-color);
  color: var(--text-primary);
}

.dropdown-menu {
  position: absolute;
  top: 100%;
  right: 0;
  background: var(--card-bg);
  border: 1px solid var(--border-color);
  border-radius: 8px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
  padding: 0.5rem 0;
  min-width: 200px;
  z-index: 1000;
  display: none;
}

.dropdown:hover .dropdown-menu {
  display: block;
}

.dropdown-item {
  width: 100%;
  padding: 0.75rem 1rem;
  background: none;
  border: none;
  text-align: left;
  cursor: pointer;
  transition: background 0.2s ease;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.9rem;
  color: var(--text-primary);
}

.dropdown-item:hover {
  background: var(--bg-tertiary);
}

.dropdown-item.danger {
  color: #dc2626;
}

.dropdown-divider {
  height: 1px;
  background: var(--border-color);
  margin: 0.5rem 0;
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
  .tax-detail-container {
    padding: 1rem;
  }

  .title-section {
    flex-direction: column;
    gap: 1rem;
  }

  .header-actions {
    flex-wrap: wrap;
  }

  .quick-info-grid {
    grid-template-columns: 1fr;
  }

  .details-grid {
    grid-template-columns: 1fr;
  }

  .detail-grid {
    grid-template-columns: 1fr;
  }

  .page-title {
    font-size: 2rem;
  }

  .calc-item {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.5rem;
  }

  .timeline::before {
    left: 15px;
  }

  .timeline-item {
    padding-left: 3rem;
  }

  .timeline-marker {
    width: 30px;
    height: 30px;
    left: 0;
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

/* CSS Variables (add these to your global styles) */
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