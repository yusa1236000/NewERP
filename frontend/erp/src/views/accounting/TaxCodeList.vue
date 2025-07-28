<template>
  <div class="tax-code-management">
    <!-- Page Header -->
    <div class="page-header d-flex justify-content-between align-items-center mb-4">
      <div>
        <h1 class="h3 mb-1">Tax Code Management</h1>
        <p class="text-muted mb-0">Manage tax codes and rates for your organization</p>
      </div>
      <button 
        @click="showCreateModal = true" 
        class="btn btn-primary"
        :disabled="loading"
      >
        <i class="fas fa-plus me-2"></i>Add Tax Code
      </button>
    </div>

    <!-- Filters Section -->
    <div class="filters-section mb-4">
      <div class="card">
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-3">
              <label class="form-label">Scope</label>
              <select v-model="filters.scope" @change="applyFilters" class="form-select">
                <option value="">All Scopes</option>
                <option value="sale">Sale</option>
                <option value="purchase">Purchase</option>
                <option value="both">Both</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Tax Type</label>
              <select v-model="filters.tax_type" @change="applyFilters" class="form-select">
                <option value="">All Types</option>
                <option value="vat">VAT</option>
                <option value="gst">GST</option>
                <option value="sales_tax">Sales Tax</option>
                <option value="service_tax">Service Tax</option>
                <option value="withholding_tax">Withholding Tax</option>
                <option value="excise_tax">Excise Tax</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Search</label>
              <input 
                v-model="filters.search" 
                @input="debounceSearch"
                type="text" 
                class="form-control" 
                placeholder="Search by code or name..."
              >
            </div>
            <div class="col-md-3 d-flex align-items-end">
              <div class="form-check">
                <input 
                  class="form-check-input" 
                  type="checkbox" 
                  v-model="filters.active_only" 
                  @change="applyFilters"
                  id="activeOnly"
                >
                <label class="form-check-label" for="activeOnly">
                  Active Only
                </label>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
      <p class="mt-2 text-muted">Loading tax codes...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="alert alert-danger" role="alert">
      <i class="fas fa-exclamation-triangle me-2"></i>
      {{ error }}
      <button @click="fetchTaxCodes" class="btn btn-outline-danger btn-sm ms-2">
        <i class="fas fa-redo me-1"></i>Retry
      </button>
    </div>

    <!-- Tax Codes Table -->
    <div v-else class="table-section">
      <div class="card">
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead class="table-light">
                <tr>
                  <th>Tax Code</th>
                  <th>Tax Name</th>
                  <th>Rate</th>
                  <th>Type</th>
                  <th>Scope</th>
                  <th>Effective Period</th>
                  <th>Status</th>
                  <th width="120">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="taxCodes.length === 0">
                  <td colspan="8" class="text-center py-4 text-muted">
                    <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                    No tax codes found
                  </td>
                </tr>
                <tr v-for="taxCode in taxCodes" :key="taxCode.id" class="align-middle">
                  <td>
                    <div class="fw-bold text-primary">{{ taxCode.tax_code }}</div>
                  </td>
                  <td>
                    <div class="fw-medium">{{ taxCode.tax_name }}</div>
                    <small class="text-muted" v-if="taxCode.description">
                      {{ truncateText(taxCode.description, 50) }}
                    </small>
                  </td>
                  <td>
                    <span class="badge bg-info">
                      {{ formatTaxRate(taxCode) }}
                    </span>
                  </td>
                  <td>
                    <span class="badge bg-secondary">
                      {{ formatTaxType(taxCode.tax_type) }}
                    </span>
                  </td>
                  <td>
                    <span class="badge bg-light text-dark">
                      {{ formatScope(taxCode.scope) }}
                    </span>
                  </td>
                  <td>
                    <div class="small">
                      <div>From: {{ formatDate(taxCode.effective_from) }}</div>
                      <div v-if="taxCode.effective_to" class="text-muted">
                        To: {{ formatDate(taxCode.effective_to) }}
                      </div>
                      <div v-else class="text-success">Ongoing</div>
                    </div>
                  </td>
                  <td>
                    <span :class="getStatusBadgeClass(taxCode)">
                      {{ taxCode.is_active ? 'Active' : 'Inactive' }}
                    </span>
                  </td>
                  <td>
                    <div class="btn-group" role="group">
                      <button 
                        @click="editTaxCode(taxCode)" 
                        class="btn btn-sm btn-outline-primary"
                        title="Edit"
                      >
                        <i class="fas fa-edit"></i>
                      </button>
                      <button 
                        @click="deleteTaxCode(taxCode)" 
                        class="btn btn-sm btn-outline-danger"
                        title="Delete"
                        :disabled="taxCode.is_active"
                      >
                        <i class="fas fa-trash"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <nav v-if="pagination.last_page > 1" class="mt-4">
        <ul class="pagination justify-content-center">
          <li :class="['page-item', { disabled: pagination.current_page === 1 }]">
            <button 
              class="page-link" 
              @click="changePage(pagination.current_page - 1)"
              :disabled="pagination.current_page === 1"
            >
              Previous
            </button>
          </li>
          
          <li 
            v-for="page in visiblePages" 
            :key="page"
            :class="['page-item', { active: page === pagination.current_page }]"
          >
            <button class="page-link" @click="changePage(page)">
              {{ page }}
            </button>
          </li>
          
          <li :class="['page-item', { disabled: pagination.current_page === pagination.last_page }]">
            <button 
              class="page-link" 
              @click="changePage(pagination.current_page + 1)"
              :disabled="pagination.current_page === pagination.last_page"
            >
              Next
            </button>
          </li>
        </ul>
      </nav>
    </div>

    <!-- Tax Code Modal -->
    <TaxCodeModal 
      :show="showCreateModal || showEditModal"
      :tax-code="selectedTaxCode"
      :is-edit="showEditModal"
      @close="closeModal"
      @saved="onTaxCodeSaved"
    />
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import { useTaxCodeStore } from '@/stores/taxCodeStore'
import TaxCodeModal from '@/components/accounting/TaxCodeModal.vue'

// Store
const taxCodeStore = useTaxCodeStore()

// Reactive data
const showCreateModal = ref(false)
const showEditModal = ref(false)
const selectedTaxCode = ref(null)
const searchTimeout = ref(null)

const filters = reactive({
  scope: '',
  tax_type: '',
  search: '',
  active_only: true,
  page: 1,
  per_page: 15
})

// Computed properties
const taxCodes = computed(() => taxCodeStore.taxCodes)
const loading = computed(() => taxCodeStore.loading)
const error = computed(() => taxCodeStore.error)
const pagination = computed(() => taxCodeStore.pagination)

const visiblePages = computed(() => {
  const pages = []
  const current = pagination.value.current_page
  const last = pagination.value.last_page
  
  let start = Math.max(1, current - 2)
  let end = Math.min(last, current + 2)
  
  if (end - start < 4) {
    if (start === 1) {
      end = Math.min(last, start + 4)
    } else {
      start = Math.max(1, end - 4)
    }
  }
  
  for (let i = start; i <= end; i++) {
    pages.push(i)
  }
  
  return pages
})

// Methods
const fetchTaxCodes = async (page = 1) => {
  filters.page = page
  await taxCodeStore.fetchTaxCodes(filters)
}

const applyFilters = () => {
  filters.page = 1
  fetchTaxCodes()
}

const debounceSearch = () => {
  if (searchTimeout.value) {
    clearTimeout(searchTimeout.value)
  }
  searchTimeout.value = setTimeout(() => {
    applyFilters()
  }, 500)
}

const changePage = (page) => {
  if (page >= 1 && page <= pagination.value.last_page) {
    fetchTaxCodes(page)
  }
}

const editTaxCode = (taxCode) => {
  selectedTaxCode.value = { ...taxCode }
  showEditModal.value = true
}

const deleteTaxCode = async (taxCode) => {
  if (!confirm(`Are you sure you want to delete tax code "${taxCode.tax_code}"?`)) {
    return
  }
  
  try {
    await taxCodeStore.deleteTaxCode(taxCode.id)
  } catch (error) {
    alert('Failed to delete tax code: ' + error.message)
  }
}

const closeModal = () => {
  showCreateModal.value = false
  showEditModal.value = false
  selectedTaxCode.value = null
}

const onTaxCodeSaved = () => {
  closeModal()
  fetchTaxCodes(filters.page)
}

// Utility methods
const formatTaxRate = (taxCode) => {
  if (taxCode.calculation_type === 'percentage') {
    return `${taxCode.tax_rate}%`
  }
  return `${taxCode.tax_rate}`
}

const formatTaxType = (type) => {
  return type.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const formatScope = (scope) => {
  return scope.charAt(0).toUpperCase() + scope.slice(1)
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString()
}

const getStatusBadgeClass = (taxCode) => {
  return taxCode.is_active ? 'badge bg-success' : 'badge bg-danger'
}

const truncateText = (text, length) => {
  if (!text) return ''
  return text.length > length ? text.substring(0, length) + '...' : text
}

// Lifecycle
onMounted(() => {
  fetchTaxCodes()
})
</script>

<style scoped>
.page-header h1 {
  color: #2c3e50;
  font-weight: 600;
}

.filters-section .card {
  border: none;
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.table-section .card {
  border: none;
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.table th {
  font-weight: 600;
  color: #495057;
  border-bottom: 2px solid #dee2e6;
}

.table tbody tr:hover {
  background-color: #f8f9fa;
}

.btn-group .btn {
  border-radius: 0.375rem;
}

.btn-group .btn:not(:last-child) {
  margin-right: 0.25rem;
}

.pagination .page-link {
  color: #0d6efd;
  border-color: #dee2e6;
}

.pagination .page-item.active .page-link {
  background-color: #0d6efd;
  border-color: #0d6efd;
}

.badge {
  font-size: 0.75rem;
}

@media (max-width: 768px) {
  .page-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }
  
  .table-responsive {
    font-size: 0.875rem;
  }
  
  .btn-group {
    flex-direction: column;
    width: 100%;
  }
  
  .btn-group .btn {
    margin-right: 0;
    margin-bottom: 0.25rem;
  }
}
</style>