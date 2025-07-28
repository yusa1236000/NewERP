<template>
  <div class="tax-configuration">
    <!-- Page Header -->
    <div class="page-header mb-4">
      <h1 class="h3 mb-1">Tax Configuration</h1>
      <p class="text-muted mb-0">Configure global tax settings and defaults for your organization</p>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
      <p class="mt-2 text-muted">Loading tax configuration...</p>
    </div>

    <!-- Configuration Form -->
    <form v-else @submit.prevent="saveConfiguration">
      <div class="row g-4">
        <!-- Company Information -->
        <div class="col-12">
          <div class="card">
            <div class="card-header">
              <h5 class="mb-0">
                <i class="fas fa-building me-2"></i>Company Information
              </h5>
            </div>
            <div class="card-body">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label required">Company Name</label>
                  <input 
                    v-model="form.company_name" 
                    type="text" 
                    class="form-control"
                    :class="{ 'is-invalid': errors.company_name }"
                    placeholder="Your Company Name"
                    required
                  >
                  <div v-if="errors.company_name" class="invalid-feedback">
                    {{ errors.company_name[0] }}
                  </div>
                </div>

                <div class="col-md-6">
                  <label class="form-label required">Tax Registration Number</label>
                  <input 
                    v-model="form.tax_registration_number" 
                    type="text" 
                    class="form-control"
                    :class="{ 'is-invalid': errors.tax_registration_number }"
                    placeholder="TAX-123456789"
                  >
                  <div v-if="errors.tax_registration_number" class="invalid-feedback">
                    {{ errors.tax_registration_number[0] }}
                  </div>
                </div>

                <div class="col-md-6">
                  <label class="form-label required">Tax Jurisdiction</label>
                  <input 
                    v-model="form.tax_jurisdiction" 
                    type="text" 
                    class="form-control"
                    :class="{ 'is-invalid': errors.tax_jurisdiction }"
                    placeholder="e.g., United States, European Union"
                    required
                  >
                  <div v-if="errors.tax_jurisdiction" class="invalid-feedback">
                    {{ errors.tax_jurisdiction[0] }}
                  </div>
                </div>

                <div class="col-md-6">
                  <label class="form-label required">Tax Authority</label>
                  <input 
                    v-model="form.tax_authority" 
                    type="text" 
                    class="form-control"
                    :class="{ 'is-invalid': errors.tax_authority }"
                    placeholder="e.g., Internal Revenue Service"
                    required
                  >
                  <div v-if="errors.tax_authority" class="invalid-feedback">
                    {{ errors.tax_authority[0] }}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Default Tax Categories -->
        <div class="col-12">
          <div class="card">
            <div class="card-header">
              <h5 class="mb-0">
                <i class="fas fa-tags me-2"></i>Default Tax Categories
              </h5>
            </div>
            <div class="card-body">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">Default Sale Tax Category</label>
                  <select 
                    v-model="form.default_sale_tax_category_id" 
                    class="form-select"
                    :class="{ 'is-invalid': errors.default_sale_tax_category_id }"
                  >
                    <option value="">No default category</option>
                    <option 
                      v-for="category in taxCategories" 
                      :key="category.id"
                      :value="category.id"
                    >
                      {{ category.category_code }} - {{ category.category_name }}
                    </option>
                  </select>
                  <div v-if="errors.default_sale_tax_category_id" class="invalid-feedback">
                    {{ errors.default_sale_tax_category_id[0] }}
                  </div>
                </div>

                <div class="col-md-6">
                  <label class="form-label">Default Purchase Tax Category</label>
                  <select 
                    v-model="form.default_purchase_tax_category_id" 
                    class="form-select"
                    :class="{ 'is-invalid': errors.default_purchase_tax_category_id }"
                  >
                    <option value="">No default category</option>
                    <option 
                      v-for="category in taxCategories" 
                      :key="category.id"
                      :value="category.id"
                    >
                      {{ category.category_code }} - {{ category.category_name }}
                    </option>
                  </select>
                  <div v-if="errors.default_purchase_tax_category_id" class="invalid-feedback">
                    {{ errors.default_purchase_tax_category_id[0] }}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Tax Calculation Settings -->
        <div class="col-12">
          <div class="card">
            <div class="card-header">
              <h5 class="mb-0">
                <i class="fas fa-calculator me-2"></i>Tax Calculation Settings
              </h5>
            </div>
            <div class="card-body">
              <div class="row g-3">
                <div class="col-md-4">
                  <label class="form-label">Rounding Method</label>
                  <select 
                    v-model="form.rounding_method" 
                    class="form-select"
                    :class="{ 'is-invalid': errors.rounding_method }"
                  >
                    <option value="round">Round (Normal)</option>
                    <option value="round_up">Round Up (Ceiling)</option>
                    <option value="round_down">Round Down (Floor)</option>
                  </select>
                  <div v-if="errors.rounding_method" class="invalid-feedback">
                    {{ errors.rounding_method[0] }}
                  </div>
                </div>

                <div class="col-md-4">
                  <label class="form-label">Rounding Precision</label>
                  <select 
                    v-model.number="form.rounding_precision" 
                    class="form-select"
                    :class="{ 'is-invalid': errors.rounding_precision }"
                  >
                    <option :value="0">0 (Whole numbers)</option>
                    <option :value="1">1 (0.1)</option>
                    <option :value="2">2 (0.01)</option>
                    <option :value="3">3 (0.001)</option>
                    <option :value="4">4 (0.0001)</option>
                  </select>
                  <div v-if="errors.rounding_precision" class="invalid-feedback">
                    {{ errors.rounding_precision[0] }}
                  </div>
                </div>

                <div class="col-md-4">
                  <label class="form-label">Compound Tax Calculation</label>
                  <select 
                    v-model="form.compound_tax_calculation" 
                    class="form-select"
                    :class="{ 'is-invalid': errors.compound_tax_calculation }"
                  >
                    <option value="sequential">Sequential</option>
                    <option value="parallel">Parallel</option>
                  </select>
                  <div v-if="errors.compound_tax_calculation" class="invalid-feedback">
                    {{ errors.compound_tax_calculation[0] }}
                  </div>
                </div>

                <div class="col-12">
                  <div class="form-check">
                    <input 
                      class="form-check-input" 
                      type="checkbox" 
                      v-model="form.tax_inclusive_pricing" 
                      id="taxInclusive"
                    >
                    <label class="form-check-label" for="taxInclusive">
                      <strong>Tax Inclusive Pricing</strong>
                      <small class="d-block text-muted">
                        When enabled, all prices include tax by default
                      </small>
                    </label>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Test Rounding -->
        <div class="col-12">
          <div class="card">
            <div class="card-header">
              <h5 class="mb-0">
                <i class="fas fa-vial me-2"></i>Test Rounding
              </h5>
            </div>
            <div class="card-body">
              <div class="row g-3 align-items-end">
                <div class="col-md-4">
                  <label class="form-label">Test Amount</label>
                  <input 
                    v-model.number="testAmount" 
                    type="number" 
                    class="form-control"
                    step="0.000001"
                    placeholder="e.g., 123.456789"
                  >
                </div>
                <div class="col-md-4">
                  <button 
                    type="button" 
                    @click="testRounding" 
                    class="btn btn-outline-secondary"
                    :disabled="loadingTest || !testAmount"
                  >
                    <span v-if="loadingTest" class="spinner-border spinner-border-sm me-2"></span>
                    <i v-else class="fas fa-play me-2"></i>
                    Test
                  </button>
                </div>
                <div class="col-md-4" v-if="testResult">
                  <div class="alert alert-info mb-0">
                    <strong>Result:</strong> {{ testResult.rounded_amount }}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Error Alert -->
      <div v-if="generalError" class="alert alert-danger mt-4" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        {{ generalError }}
      </div>

      <!-- Success Alert -->
      <div v-if="showSuccess" class="alert alert-success mt-4" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        Tax configuration saved successfully!
      </div>

      <!-- Action Buttons -->
      <div class="d-flex justify-content-end gap-2 mt-4">
        <button 
          type="button" 
          @click="resetForm" 
          class="btn btn-outline-secondary"
          :disabled="submitting"
        >
          Reset
        </button>
        <button 
          type="submit" 
          class="btn btn-primary"
          :disabled="submitting"
        >
          <span v-if="submitting" class="spinner-border spinner-border-sm me-2"></span>
          <i v-else class="fas fa-save me-2"></i>
          Save Configuration
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import { useTaxConfigurationStore } from '@/stores/taxConfigurationStore'
import { useTaxCategoryStore } from '@/stores/taxCategoryStore'

// Stores
const taxConfigStore = useTaxConfigurationStore()
const taxCategoryStore = useTaxCategoryStore()

// Reactive data
const submitting = ref(false)
const loadingTest = ref(false)
const errors = ref({})
const generalError = ref('')
const showSuccess = ref(false)
const testAmount = ref('')
const testResult = ref(null)

const form = reactive({
  company_name: '',
  tax_jurisdiction: '',
  tax_authority: '',
  tax_registration_number: '',
  default_sale_tax_category_id: '',
  default_purchase_tax_category_id: '',
  tax_inclusive_pricing: false,
  rounding_method: 'round',
  rounding_precision: 2,
  compound_tax_calculation: 'sequential'
})

// Computed properties
const loading = computed(() => taxConfigStore.loading)
const configuration = computed(() => taxConfigStore.configuration)
const taxCategories = computed(() => taxCategoryStore.activeCategories)

// Methods
const loadConfiguration = () => {
  if (configuration.value) {
    Object.assign(form, {
      company_name: configuration.value.company_name || '',
      tax_jurisdiction: configuration.value.tax_jurisdiction || '',
      tax_authority: configuration.value.tax_authority || '',
      tax_registration_number: configuration.value.tax_registration_number || '',
      default_sale_tax_category_id: configuration.value.default_sale_tax_category_id || '',
      default_purchase_tax_category_id: configuration.value.default_purchase_tax_category_id || '',
      tax_inclusive_pricing: configuration.value.tax_inclusive_pricing || false,
      rounding_method: configuration.value.rounding_method || 'round',
      rounding_precision: configuration.value.rounding_precision || 2,
      compound_tax_calculation: configuration.value.compound_tax_calculation || 'sequential'
    })
  }
}

const resetForm = () => {
  loadConfiguration()
  errors.value = {}
  generalError.value = ''
  showSuccess.value = false
}

const saveConfiguration = async () => {
  submitting.value = true
  errors.value = {}
  generalError.value = ''
  showSuccess.value = false

  try {
    const formData = { ...form }
    
    // Convert empty strings to null for foreign keys
    if (!formData.default_sale_tax_category_id) {
      formData.default_sale_tax_category_id = null
    }
    if (!formData.default_purchase_tax_category_id) {
      formData.default_purchase_tax_category_id = null
    }

    if (configuration.value) {
      await taxConfigStore.updateTaxConfiguration(configuration.value.id, formData)
    } else {
      await taxConfigStore.createTaxConfiguration(formData)
    }

    showSuccess.value = true
    setTimeout(() => {
      showSuccess.value = false
    }, 5000)
  } catch (error) {
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors || {}
    } else {
      generalError.value = error.response?.data?.message || 'An error occurred while saving the configuration'
    }
  } finally {
    submitting.value = false
  }
}

const testRounding = async () => {
  if (!testAmount.value) return

  loadingTest.value = true
  testResult.value = null

  try {
    const result = await taxConfigStore.testRounding(
      testAmount.value,
      form.rounding_method,
      form.rounding_precision
    )
    testResult.value = result.data
  } catch (error) {
    alert('Failed to test rounding: ' + error.message)
  } finally {
    loadingTest.value = false
  }
}

const loadTaxCategories = async () => {
  try {
    await taxCategoryStore.fetchTaxCategories({ active_only: true, per_page: 100 })
  } catch (error) {
    console.error('Failed to load tax categories:', error)
  }
}

// Lifecycle
onMounted(async () => {
  await Promise.all([
    taxConfigStore.fetchTaxConfiguration(),
    loadTaxCategories()
  ])
  loadConfiguration()
})
</script>

<style scoped>
.page-header h1 {
  color: #2c3e50;
  font-weight: 600;
}

.card {
  border: none;
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
  margin-bottom: 1.5rem;
}

.card-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border-bottom: none;
}

.card-header h5 {
  margin: 0;
}

.required:after {
  content: " *";
  color: #dc3545;
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

.alert {
  border: none;
  border-radius: 8px;
}

.btn {
  border-radius: 6px;
}

@media (max-width: 768px) {
  .d-flex.justify-content-end {
    flex-direction: column;
  }
  
  .d-flex.justify-content-end .btn {
    margin-bottom: 0.5rem;
  }
}
</style>