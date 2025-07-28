<template>
  <div class="modal fade" :class="{ show: show }" :style="{ display: show ? 'block' : 'none' }" tabindex="-1" v-if="show">
    <div class="modal-backdrop fade" :class="{ show: show }" @click="closeModal"></div>
    <div class="modal-dialog modal-xl">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">
            <i class="fas fa-tags me-2"></i>
            {{ isEdit ? 'Edit Tax Category' : 'Create Tax Category' }}
          </h5>
          <button type="button" class="btn-close" @click="closeModal"></button>
        </div>
        
        <form @submit.prevent="saveTaxCategory">
          <div class="modal-body">
            <div class="row g-3">
              <!-- Category Code -->
              <div class="col-md-6">
                <label class="form-label required">Category Code</label>
                <input 
                  v-model="form.category_code" 
                  type="text" 
                  class="form-control"
                  :class="{ 'is-invalid': errors.category_code }"
                  placeholder="e.g., STANDARD, EXEMPT"
                  maxlength="20"
                  required
                >
                <div v-if="errors.category_code" class="invalid-feedback">
                  {{ errors.category_code[0] }}
                </div>
              </div>

              <!-- Category Name -->
              <div class="col-md-6">
                <label class="form-label required">Category Name</label>
                <input 
                  v-model="form.category_name" 
                  type="text" 
                  class="form-control"
                  :class="{ 'is-invalid': errors.category_name }"
                  placeholder="e.g., Standard Tax Category"
                  maxlength="100"
                  required
                >
                <div v-if="errors.category_name" class="invalid-feedback">
                  {{ errors.category_name[0] }}
                </div>
              </div>

              <!-- Description -->
              <div class="col-12">
                <label class="form-label">Description</label>
                <textarea 
                  v-model="form.description" 
                  class="form-control"
                  :class="{ 'is-invalid': errors.description }"
                  rows="3"
                  placeholder="Optional description for this tax category"
                ></textarea>
                <div v-if="errors.description" class="invalid-feedback">
                  {{ errors.description[0] }}
                </div>
              </div>

              <!-- Active Status -->
              <div class="col-12">
                <div class="form-check">
                  <input 
                    class="form-check-input" 
                    type="checkbox" 
                    v-model="form.is_active" 
                    id="isActive"
                  >
                  <label class="form-check-label" for="isActive">
                    Active
                  </label>
                </div>
              </div>

              <!-- Tax Codes Assignment -->
              <div class="col-12">
                <div class="card">
                  <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Assign Tax Codes</h6>
                    <button 
                      type="button" 
                      class="btn btn-sm btn-outline-primary"
                      @click="showAddTaxCodeModal = true"
                      :disabled="loadingTaxCodes"
                    >
                      <i class="fas fa-plus me-1"></i>Add Tax Code
                    </button>
                  </div>
                  <div class="card-body">
                    <!-- Loading State -->
                    <div v-if="loadingTaxCodes" class="text-center py-3">
                      <div class="spinner-border spinner-border-sm text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                      </div>
                      <p class="mt-2 text-muted mb-0">Loading tax codes...</p>
                    </div>

                    <!-- No Tax Codes -->
                    <div v-else-if="form.tax_codes.length === 0" class="text-center py-4 text-muted">
                      <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                      No tax codes assigned yet
                    </div>

                    <!-- Tax Codes List -->
                    <div v-else class="tax-codes-list">
                      <div 
                        v-for="(taxCode, index) in form.tax_codes" 
                        :key="taxCode.tax_code_id"
                        class="tax-code-item"
                      >
                        <div class="tax-code-info">
                          <div class="tax-code-header">
                            <strong>{{ getTaxCodeById(taxCode.tax_code_id)?.tax_code }}</strong>
                            <span class="badge bg-info ms-2">
                              {{ getTaxCodeById(taxCode.tax_code_id)?.tax_rate }}%
                            </span>
                            <span v-if="taxCode.is_default" class="badge bg-warning text-dark ms-1">
                              Default
                            </span>
                          </div>
                          <div class="tax-code-details">
                            <small class="text-muted">
                              {{ getTaxCodeById(taxCode.tax_code_id)?.tax_name }}
                            </small>
                          </div>
                        </div>
                        
                        <div class="tax-code-controls">
                          <div class="row g-2">
                            <div class="col-auto">
                              <label class="form-label small">Sequence</label>
                              <input 
                                type="number" 
                                v-model.number="taxCode.sequence"
                                class="form-control form-control-sm"
                                min="0"
                                style="width: 80px;"
                              >
                            </div>
                            <div class="col-auto d-flex align-items-end">
                              <div class="form-check">
                                <input 
                                  class="form-check-input" 
                                  type="checkbox" 
                                  v-model="taxCode.is_default"
                                  :id="`default-${index}`"
                                >
                                <label class="form-check-label small" :for="`default-${index}`">
                                  Default
                                </label>
                              </div>
                            </div>
                            <div class="col-auto d-flex align-items-end">
                              <button 
                                type="button" 
                                class="btn btn-sm btn-outline-danger"
                                @click="removeTaxCode(index)"
                              >
                                <i class="fas fa-trash"></i>
                              </button>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Error Alert -->
            <div v-if="generalError" class="alert alert-danger mt-3" role="alert">
              <i class="fas fa-exclamation-triangle me-2"></i>
              {{ generalError }}
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="closeModal" :disabled="loading">
              Cancel
            </button>
            <button type="submit" class="btn btn-primary" :disabled="loading">
              <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
              <i v-else class="fas fa-save me-2"></i>
              {{ isEdit ? 'Update' : 'Create' }} Tax Category
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Add Tax Code Modal -->
    <div v-if="showAddTaxCodeModal" class="modal fade show" style="display: block;" tabindex="-1">
      <div class="modal-backdrop fade show"></div>
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Add Tax Code</h5>
            <button type="button" class="btn-close" @click="showAddTaxCodeModal = false"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Select Tax Code</label>
              <select v-model="selectedTaxCodeId" class="form-select">
                <option value="">Choose a tax code...</option>
                <option 
                  v-for="taxCode in availableTaxCodes" 
                  :key="taxCode.id"
                  :value="taxCode.id"
                >
                  {{ taxCode.tax_code }} - {{ taxCode.tax_name }} ({{ taxCode.tax_rate }}%)
                </option>
              </select>
            </div>
            <div class="row g-2">
              <div class="col-6">
                <label class="form-label">Sequence</label>
                <input 
                  type="number" 
                  v-model.number="newTaxCodeSequence"
                  class="form-control"
                  min="0"
                  placeholder="0"
                >
              </div>
              <div class="col-6 d-flex align-items-end">
                <div class="form-check">
                  <input 
                    class="form-check-input" 
                    type="checkbox" 
                    v-model="newTaxCodeIsDefault"
                    id="newDefault"
                  >
                  <label class="form-check-label" for="newDefault">
                    Default
                  </label>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="closeAddTaxCodeModal">
              Cancel
            </button>
            <button 
              type="button" 
              class="btn btn-primary" 
              @click="addTaxCode"
              :disabled="!selectedTaxCodeId"
            >
              Add Tax Code
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
/* eslint-disable */
import { ref, reactive, watch, computed, onMounted } from 'vue'
import { useTaxCategoryStore } from '@/stores/taxCategoryStore'
import { useTaxCodeStore } from '@/stores/taxCodeStore'

const props = defineProps({
  show: Boolean,
  category: Object,
  isEdit: Boolean
})

const emit = defineEmits(['close', 'saved'])

// Stores
const taxCategoryStore = useTaxCategoryStore()
const taxCodeStore = useTaxCodeStore()

// Reactive data
const loading = ref(false)
const loadingTaxCodes = ref(false)
const errors = ref({})
const generalError = ref('')
const showAddTaxCodeModal = ref(false)
const availableTaxCodes = ref([])
const selectedTaxCodeId = ref('')
const newTaxCodeSequence = ref(0)
const newTaxCodeIsDefault = ref(false)

const form = reactive({
  category_code: '',
  category_name: '',
  description: '',
  is_active: true,
  tax_codes: []
})

// Computed
const formTitle = computed(() => {
  return props.isEdit ? 'Edit Tax Category' : 'Create Tax Category'
})

// Methods
const resetForm = () => {
  Object.assign(form, {
    category_code: '',
    category_name: '',
    description: '',
    is_active: true,
    tax_codes: []
  })
  errors.value = {}
  generalError.value = ''
}

const loadCategory = () => {
  if (props.category && props.isEdit) {
    Object.assign(form, {
      category_code: props.category.category_code,
      category_name: props.category.category_name,
      description: props.category.description || '',
      is_active: props.category.is_active,
      tax_codes: props.category.tax_codes ? props.category.tax_codes.map(tc => ({
        tax_code_id: tc.id,
        is_default: tc.pivot?.is_default || false,
        sequence: tc.pivot?.sequence || 0
      })) : []
    })
  }
}

const loadAvailableTaxCodes = async () => {
  try {
    loadingTaxCodes.value = true
    await taxCodeStore.fetchTaxCodes({ active_only: true, per_page: 100 })
    availableTaxCodes.value = taxCodeStore.taxCodes
  } catch (error) {
    console.error('Failed to load tax codes:', error)
  } finally {
    loadingTaxCodes.value = false
  }
}

const getTaxCodeById = (id) => {
  return availableTaxCodes.value.find(tc => tc.id === id)
}

const addTaxCode = () => {
  if (!selectedTaxCodeId.value) return

  // Check if already exists
  if (form.tax_codes.find(tc => tc.tax_code_id === selectedTaxCodeId.value)) {
    alert('Tax code already added to this category')
    return
  }

  form.tax_codes.push({
    tax_code_id: selectedTaxCodeId.value,
    is_default: newTaxCodeIsDefault.value,
    sequence: newTaxCodeSequence.value || 0
  })

  closeAddTaxCodeModal()
}

const removeTaxCode = (index) => {
  form.tax_codes.splice(index, 1)
}

const closeAddTaxCodeModal = () => {
  showAddTaxCodeModal.value = false
  selectedTaxCodeId.value = ''
  newTaxCodeSequence.value = 0
  newTaxCodeIsDefault.value = false
}

const saveTaxCategory = async () => {
  loading.value = true
  errors.value = {}
  generalError.value = ''

  try {
    const formData = { ...form }

    if (props.isEdit) {
      await taxCategoryStore.updateTaxCategory(props.category.id, formData)
    } else {
      await taxCategoryStore.createTaxCategory(formData)
    }

    emit('saved')
  } catch (error) {
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors || {}
    } else {
      generalError.value = error.response?.data?.message || 'An error occurred while saving the tax category'
    }
  } finally {
    loading.value = false
  }
}

const closeModal = () => {
  emit('close')
}

// Watchers
watch(() => props.show, (newValue) => {
  if (newValue) {
    if (props.isEdit && props.category) {
      loadCategory()
    } else {
      resetForm()
    }
    loadAvailableTaxCodes()
  }
})

// Handle ESC key
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape' && props.show) {
    if (showAddTaxCodeModal.value) {
      closeAddTaxCodeModal()
    } else {
      closeModal()
    }
  }
})
</script>

<style scoped>
.modal {
  background-color: rgba(0, 0, 0, 0.5);
}

.modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  z-index: -1;
}

.modal-dialog {
  z-index: 1050;
  margin: 1.75rem auto;
}

.modal-content {
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
}

.modal-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.modal-header .btn-close {
  filter: invert(1);
}

.required:after {
  content: " *";
  color: #dc3545;
}

.tax-codes-list {
  max-height: 400px;
  overflow-y: auto;
}

.tax-code-item {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  padding: 1rem;
  border: 1px solid #e9ecef;
  border-radius: 8px;
  margin-bottom: 0.75rem;
  background-color: #f8f9fa;
}

.tax-code-item:last-child {
  margin-bottom: 0;
}

.tax-code-info {
  flex: 1;
  margin-right: 1rem;
}

.tax-code-header {
  display: flex;
  align-items: center;
  margin-bottom: 0.25rem;
}

.tax-code-details {
  font-size: 0.875rem;
}

.tax-code-controls {
  flex-shrink: 0;
}

.form-check-input:checked {
  background-color: #0d6efd;
  border-color: #0d6efd;
}

.is-invalid {
  border-color: #dc3545;
}

.invalid-feedback {
  display: block;
}

@media (max-width: 768px) {
  .modal-dialog {
    margin: 0.5rem;
    max-width: calc(100% - 1rem);
  }

  .tax-code-item {
    flex-direction: column;
    gap: 1rem;
  }

  .tax-code-info {
    margin-right: 0;
  }
}
</style>