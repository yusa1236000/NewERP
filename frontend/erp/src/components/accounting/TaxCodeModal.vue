<template>
  <div class="modal fade" :class="{ show: show }" :style="{ display: show ? 'block' : 'none' }" tabindex="-1" v-if="show">
    <div class="modal-backdrop fade" :class="{ show: show }" @click="closeModal"></div>
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">
            <i class="fas fa-percentage me-2"></i>
            {{ isEdit ? 'Edit Tax Code' : 'Create Tax Code' }}
          </h5>
          <button type="button" class="btn-close" @click="closeModal"></button>
        </div>
        
        <form @submit.prevent="saveTaxCode">
          <div class="modal-body">
            <div class="row g-3">
              <!-- Tax Code -->
              <div class="col-md-6">
                <label class="form-label required">Tax Code</label>
                <input 
                  v-model="form.tax_code" 
                  type="text" 
                  class="form-control"
                  :class="{ 'is-invalid': errors.tax_code }"
                  placeholder="e.g., VAT10, GST12"
                  maxlength="20"
                  required
                >
                <div v-if="errors.tax_code" class="invalid-feedback">
                  {{ errors.tax_code[0] }}
                </div>
              </div>

              <!-- Tax Name -->
              <div class="col-md-6">
                <label class="form-label required">Tax Name</label>
                <input 
                  v-model="form.tax_name" 
                  type="text" 
                  class="form-control"
                  :class="{ 'is-invalid': errors.tax_name }"
                  placeholder="e.g., Value Added Tax 10%"
                  maxlength="100"
                  required
                >
                <div v-if="errors.tax_name" class="invalid-feedback">
                  {{ errors.tax_name[0] }}
                </div>
              </div>

              <!-- Tax Rate -->
              <div class="col-md-4">
                <label class="form-label required">Tax Rate</label>
                <div class="input-group">
                  <input 
                    v-model.number="form.tax_rate" 
                    type="number" 
                    class="form-control"
                    :class="{ 'is-invalid': errors.tax_rate }"
                    step="0.0001"
                    min="0"
                    max="9999.9999"
                    required
                  >
<span class="input-group-text">
  {{ form.calculation_type === 'percentage' ? '%' : '' }}
</span>
                </div>
                <div v-if="errors.tax_rate" class="invalid-feedback">
                  {{ errors.tax_rate[0] }}
                </div>
              </div>

              <!-- Calculation Type -->
              <div class="col-md-4">
                <label class="form-label required">Calculation Type</label>
                <select 
                  v-model="form.calculation_type" 
                  class="form-select"
                  :class="{ 'is-invalid': errors.calculation_type }"
                  required
                >
                  <option value="percentage">Percentage</option>
                  <option value="fixed_amount">Fixed Amount</option>
                </select>
                <div v-if="errors.calculation_type" class="invalid-feedback">
                  {{ errors.calculation_type[0] }}
                </div>
              </div>

              <!-- Tax Type -->
              <div class="col-md-4">
                <label class="form-label required">Tax Type</label>
                <select 
                  v-model="form.tax_type" 
                  class="form-select"
                  :class="{ 'is-invalid': errors.tax_type }"
                  required
                >
                  <option value="vat">VAT</option>
                  <option value="gst">GST</option>
                  <option value="sales_tax">Sales Tax</option>
                  <option value="service_tax">Service Tax</option>
                  <option value="withholding_tax">Withholding Tax</option>
                  <option value="excise_tax">Excise Tax</option>
                </select>
                <div v-if="errors.tax_type" class="invalid-feedback">
                  {{ errors.tax_type[0] }}
                </div>
              </div>

              <!-- Scope -->
              <div class="col-md-4">
                <label class="form-label required">Scope</label>
                <select 
                  v-model="form.scope" 
                  class="form-select"
                  :class="{ 'is-invalid': errors.scope }"
                  required
                >
                  <option value="sale">Sale Only</option>
                  <option value="purchase">Purchase Only</option>
                  <option value="both">Both Sale & Purchase</option>
                </select>
                <div v-if="errors.scope" class="invalid-feedback">
                  {{ errors.scope[0] }}
                </div>
              </div>

              <!-- Effective From -->
              <div class="col-md-4">
                <label class="form-label required">Effective From</label>
                <input 
                  v-model="form.effective_from" 
                  type="date" 
                  class="form-control"
                  :class="{ 'is-invalid': errors.effective_from }"
                  required
                >
                <div v-if="errors.effective_from" class="invalid-feedback">
                  {{ errors.effective_from[0] }}
                </div>
              </div>

              <!-- Effective To -->
              <div class="col-md-4">
                <label class="form-label">Effective To</label>
                <input 
                  v-model="form.effective_to" 
                  type="date" 
                  class="form-control"
                  :class="{ 'is-invalid': errors.effective_to }"
                  :min="form.effective_from"
                >
                <div v-if="errors.effective_to" class="invalid-feedback">
                  {{ errors.effective_to[0] }}
                </div>
                <small class="text-muted">Leave empty if ongoing</small>
              </div>

              <!-- Description -->
              <div class="col-12">
                <label class="form-label">Description</label>
                <textarea 
                  v-model="form.description" 
                  class="form-control"
                  :class="{ 'is-invalid': errors.description }"
                  rows="3"
                  placeholder="Optional description for this tax code"
                ></textarea>
                <div v-if="errors.description" class="invalid-feedback">
                  {{ errors.description[0] }}
                </div>
              </div>

              <!-- Advanced Options -->
              <div class="col-12">
                <div class="card bg-light">
                  <div class="card-header">
                    <h6 class="mb-0">
                      <i class="fas fa-cog me-2"></i>Advanced Options
                    </h6>
                  </div>
                  <div class="card-body">
                    <div class="row g-3">
                      <div class="col-md-4">
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
                      <div class="col-md-4">
                        <div class="form-check">
                          <input 
                            class="form-check-input" 
                            type="checkbox" 
                            v-model="form.is_compound" 
                            id="isCompound"
                          >
                          <label class="form-check-label" for="isCompound">
                            Compound Tax
                          </label>
                        </div>
                      </div>
                      <div class="col-md-4">
                        <div class="form-check">
                          <input 
                            class="form-check-input" 
                            type="checkbox" 
                            v-model="form.include_base_amount" 
                            id="includeBase"
                          >
                          <label class="form-check-label" for="includeBase">
                            Include in Base for Other Taxes
                          </label>
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
              {{ isEdit ? 'Update' : 'Create' }} Tax Code
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, watch, nextTick, defineProps, defineEmits } from 'vue'
import { useTaxCodeStore } from '@/stores/taxCodeStore'

const props = defineProps({
  show: Boolean,
  taxCode: Object,
  isEdit: Boolean
})

const emit = defineEmits(['close', 'saved'])

// Store
const taxCodeStore = useTaxCodeStore()

// Reactive data
const loading = ref(false)
const errors = ref({})
const generalError = ref('')

const form = reactive({
  tax_code: '',
  tax_name: '',
  tax_rate: 0,
  tax_type: 'vat',
  calculation_type: 'percentage',
  scope: 'both',
  is_active: true,
  is_compound: false,
  include_base_amount: true,
  effective_from: new Date().toISOString().split('T')[0],
  effective_to: '',
  description: ''
})

// Computed
// const formTitle = computed(() => {
//   return props.isEdit ? 'Edit Tax Code' : 'Create Tax Code'
// })

// Methods
const resetForm = () => {
  Object.assign(form, {
    tax_code: '',
    tax_name: '',
    tax_rate: 0,
    tax_type: 'vat',
    calculation_type: 'percentage',
    scope: 'both',
    is_active: true,
    is_compound: false,
    include_base_amount: true,
    effective_from: new Date().toISOString().split('T')[0],
    effective_to: '',
    description: ''
  })
  errors.value = {}
  generalError.value = ''
}

const loadTaxCode = () => {
  if (props.taxCode && props.isEdit) {
    Object.assign(form, {
      tax_code: props.taxCode.tax_code,
      tax_name: props.taxCode.tax_name,
      tax_rate: props.taxCode.tax_rate,
      tax_type: props.taxCode.tax_type,
      calculation_type: props.taxCode.calculation_type,
      scope: props.taxCode.scope,
      is_active: props.taxCode.is_active,
      is_compound: props.taxCode.is_compound,
      include_base_amount: props.taxCode.include_base_amount,
      effective_from: props.taxCode.effective_from,
      effective_to: props.taxCode.effective_to || '',
      description: props.taxCode.description || ''
    })
  }
}

const saveTaxCode = async () => {
  loading.value = true
  errors.value = {}
  generalError.value = ''

  try {
    const formData = { ...form }
    if (!formData.effective_to) {
      delete formData.effective_to
    }

    if (props.isEdit) {
      await taxCodeStore.updateTaxCode(props.taxCode.id, formData)
    } else {
      await taxCodeStore.createTaxCode(formData)
    }

    emit('saved')
  } catch (error) {
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors || {}
    } else {
      generalError.value = error.response?.data?.message || 'An error occurred while saving the tax code'
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
    if (props.isEdit && props.taxCode) {
      loadTaxCode()
    } else {
      resetForm()
    }
    // Focus on first input
    nextTick(() => {
      const firstInput = document.querySelector('.modal input[type="text"]')
      if (firstInput) {
        firstInput.focus()
      }
    })
  }
})

// Handle ESC key
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape' && props.show) {
    closeModal()
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

.form-check-input:checked {
  background-color: #0d6efd;
  border-color: #0d6efd;
}

.card {
  border: none;
}

.card-header {
  background-color: #f8f9fa;
  border-bottom: 1px solid #dee2e6;
}

.input-group-text {
  background-color: #e9ecef;
  border-color: #ced4da;
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
}
</style>