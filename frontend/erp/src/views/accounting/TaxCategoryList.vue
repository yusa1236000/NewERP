<template>
  <div class="tax-category-management">
    <!-- Page Header -->
    <div class="page-header d-flex justify-content-between align-items-center mb-4">
      <div>
        <h1 class="h3 mb-1">Tax Category Management</h1>
        <p class="text-muted mb-0">Organize tax codes into categories for easier management</p>
      </div>
      <button 
        @click="showCreateModal = true" 
        class="btn btn-primary"
        :disabled="loading"
      >
        <i class="fas fa-plus me-2"></i>Add Tax Category
      </button>
    </div>

    <!-- Filters -->
    <div class="filters-section mb-4">
      <div class="card">
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Search</label>
              <input 
                v-model="filters.search" 
                @input="debounceSearch"
                type="text" 
                class="form-control" 
                placeholder="Search by category code or name..."
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
      <p class="mt-2 text-muted">Loading tax categories...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="alert alert-danger" role="alert">
      <i class="fas fa-exclamation-triangle me-2"></i>
      {{ error }}
      <button @click="fetchTaxCategories" class="btn btn-outline-danger btn-sm ms-2">
        <i class="fas fa-redo me-1"></i>Retry
      </button>
    </div>

    <!-- Categories Grid -->
    <div v-else class="categories-grid">
      <div class="row g-4">
        <div class="col-12" v-if="taxCategories.length === 0">
          <div class="text-center py-5">
            <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">No tax categories found</h5>
            <p class="text-muted">Create your first tax category to get started</p>
            <button @click="showCreateModal = true" class="btn btn-primary">
              <i class="fas fa-plus me-2"></i>Create Tax Category
            </button>
          </div>
        </div>

        <div 
          v-for="category in taxCategories" 
          :key="category.id" 
          class="col-md-6 col-lg-4"
        >
          <div class="card category-card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
              <div>
                <h6 class="mb-0 fw-bold">{{ category.category_code }}</h6>
                <span :class="getStatusBadgeClass(category)">
                  {{ category.is_active ? 'Active' : 'Inactive' }}
                </span>
              </div>
              <div class="dropdown">
                <button 
                  class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                  type="button" 
                  :id="`dropdown-${category.id}`"
                  data-bs-toggle="dropdown"
                >
                  <i class="fas fa-ellipsis-v"></i>
                </button>
                <ul class="dropdown-menu">
                  <li>
                    <button @click="editCategory(category)" class="dropdown-item">
                      <i class="fas fa-edit me-2"></i>Edit
                    </button>
                  </li>
                  <li>
                    <button @click="viewDetails(category)" class="dropdown-item">
                      <i class="fas fa-eye me-2"></i>View Details
                    </button>
                  </li>
                  <li><hr class="dropdown-divider"></li>
                  <li>
                    <button 
                      @click="deleteCategory(category)" 
                      class="dropdown-item text-danger"
                      :disabled="category.is_active"
                    >
                      <i class="fas fa-trash me-2"></i>Delete
                    </button>
                  </li>
                </ul>
              </div>
            </div>
            
            <div class="card-body">
              <h6 class="card-title">{{ category.category_name }}</h6>
              <p class="card-text text-muted small">
                {{ truncateText(category.description, 100) || 'No description available' }}
              </p>
              
              <!-- Tax Codes Summary -->
              <div class="tax-codes-summary">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <span class="small fw-medium">Tax Codes:</span>
                  <span class="badge bg-primary">{{ category.tax_codes?.length || 0 }}</span>
                </div>
                
                <div v-if="category.tax_codes && category.tax_codes.length > 0" class="tax-codes-list">
<div 
  v-for="taxCode in category.tax_codes.slice(0, 3)" 
  :key="taxCode.id"
  class="tax-code-item small"
>
                    <span class="tax-code">{{ taxCode.tax_code }}</span>
                    <span class="tax-rate">{{ formatTaxRate(taxCode) }}</span>
                    <span v-if="taxCode.pivot.is_default" class="badge bg-warning text-dark ms-1">Default</span>
                  </div>
                  <div v-if="category.tax_codes.length > 3" class="small text-muted">
                    +{{ category.tax_codes.length - 3 }} more...
                  </div>
                </div>
                <div v-else class="small text-muted">
                  No tax codes assigned
                </div>
              </div>
            </div>
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

    <!-- Modals -->
    <TaxCategoryModal 
      :show="showCreateModal || showEditModal"
      :category="selectedCategory"
      :is-edit="showEditModal"
      @close="closeModal"
      @saved="onCategorySaved"
    />

    <TaxCategoryDetailModal
      :show="showDetailModal"
      :category="selectedCategory"
      @close="showDetailModal = false"
    />
  </div>
</template>

<script setup>
/* eslint-disable */
import { ref, reactive, onMounted, computed } from 'vue'
import { useTaxCategoryStore } from '@/stores/taxCategoryStore'
import TaxCategoryModal from '@/components/accounting/TaxCategoryModal.vue'
import TaxCategoryDetailModal from '@/components/accounting/TaxCategoryDetailModal.vue'

// Store
const taxCategoryStore = useTaxCategoryStore()

// Reactive data
const showCreateModal = ref(false)
const showEditModal = ref(false)
const showDetailModal = ref(false)
const selectedCategory = ref(null)
const searchTimeout = ref(null)

const filters = reactive({
  search: '',
  active_only: true,
  page: 1,
  per_page: 12
})

// Computed properties
const taxCategories = computed(() => taxCategoryStore.taxCategories)
const loading = computed(() => taxCategoryStore.loading)
const error = computed(() => taxCategoryStore.error)
const pagination = computed(() => taxCategoryStore.pagination)

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
const fetchTaxCategories = async (page = 1) => {
  filters.page = page
  await taxCategoryStore.fetchTaxCategories(filters)
}

const applyFilters = () => {
  filters.page = 1
  fetchTaxCategories()
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
    fetchTaxCategories(page)
  }
}

const editCategory = (category) => {
  selectedCategory.value = { ...category }
  showEditModal.value = true
}

const viewDetails = (category) => {
  selectedCategory.value = category
  showDetailModal.value = true
}

const deleteCategory = async (category) => {
  if (!confirm(`Are you sure you want to delete category "${category.category_code}"?`)) {
    return
  }
  
  try {
    await taxCategoryStore.deleteTaxCategory(category.id)
  } catch (error) {
    alert('Failed to delete tax category: ' + error.message)
  }
}

const closeModal = () => {
  showCreateModal.value = false
  showEditModal.value = false
  selectedCategory.value = null
}

const onCategorySaved = () => {
  closeModal()
  fetchTaxCategories(filters.page)
}

// Utility methods
const formatTaxRate = (taxCode) => {
  if (taxCode.calculation_type === 'percentage') {
    return `${taxCode.tax_rate}%`
  }
  return `${taxCode.tax_rate}`
}

const getStatusBadgeClass = (category) => {
  return category.is_active ? 'badge bg-success' : 'badge bg-danger'
}

const truncateText = (text, length) => {
  if (!text) return ''
  return text.length > length ? text.substring(0, length) + '...' : text
}

// Lifecycle
onMounted(() => {
  fetchTaxCategories()
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

.category-card {
  border: none;
  box-shadow: 0 4px 15px rgba(0,0,0,0.1);
  transition: all 0.3s ease;
  overflow: hidden;
}

.category-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.category-card .card-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border-bottom: none;
}

.category-card .card-header .badge {
  background-color: rgba(255,255,255,0.2) !important;
  color: white !important;
}

.tax-codes-summary {
  background-color: #f8f9fa;
  border-radius: 6px;
  padding: 0.75rem;
  margin-top: 1rem;
}

.tax-code-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.25rem 0;
  border-bottom: 1px solid #e9ecef;
}

.tax-code-item:last-child {
  border-bottom: none;
}

.tax-code {
  font-weight: 600;
  color: #0d6efd;
}

.dropdown-toggle::after {
  display: none;
}

.dropdown-menu {
  box-shadow: 0 4px 15px rgba(0,0,0,0.1);
  border: none;
}

.pagination .page-link {
  color: #0d6efd;
  border-color: #dee2e6;
}

.pagination .page-item.active .page-link {
  background-color: #0d6efd;
  border-color: #0d6efd;
}

@media (max-width: 768px) {
  .page-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }
  
  .categories-grid .col-md-6 {
    margin-bottom: 1rem;
  }
}
</style>