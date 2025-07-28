<template>
  <div class="modal fade" :class="{ show: show }" :style="{ display: show ? 'block' : 'none' }" tabindex="-1" v-if="show">
    <div class="modal-backdrop fade" :class="{ show: show }" @click="closeModal"></div>
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">
            <i class="fas fa-eye me-2"></i>
            Tax Category Details
          </h5>
          <button type="button" class="btn-close" @click="closeModal"></button>
        </div>
        
        <div class="modal-body" v-if="category">
          <!-- Category Information -->
          <div class="row g-3 mb-4">
            <div class="col-md-6">
              <div class="info-item">
                <label class="info-label">Category Code</label>
                <div class="info-value">
                  <span class="badge bg-primary">{{ category.category_code }}</span>
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="info-item">
                <label class="info-label">Status</label>
                <div class="info-value">
                  <span :class="getStatusBadgeClass(category)">
                    {{ category.is_active ? 'Active' : 'Inactive' }}
                  </span>
                </div>
              </div>
            </div>
            <div class="col-12">
              <div class="info-item">
                <label class="info-label">Category Name</label>
                <div class="info-value">{{ category.category_name }}</div>
              </div>
            </div>
            <div class="col-12" v-if="category.description">
              <div class="info-item">
                <label class="info-label">Description</label>
                <div class="info-value">{{ category.description }}</div>
              </div>
            </div>
          </div>

          <!-- Tax Codes Section -->
          <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
              <h6 class="mb-0">
                <i class="fas fa-percentage me-2"></i>
                Tax Codes ({{ category.tax_codes?.length || 0 }})
              </h6>
              <button 
                v-if="category.tax_codes?.length > 0"
                @click="showTaxCalculation = !showTaxCalculation"
                class="btn btn-sm btn-outline-info"
              >
                <i class="fas fa-calculator me-1"></i>
                {{ showTaxCalculation ? 'Hide' : 'Show' }} Calculator
              </button>
            </div>
            <div class="card-body">
              <!-- No Tax Codes -->
              <div v-if="!category.tax_codes || category.tax_codes.length === 0" class="text-center py-4 text-muted">
                <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                No tax codes assigned to this category
              </div>

              <!-- Tax Codes List -->
              <div v-else>
                <div class="table-responsive">
                  <table class="table table-sm">
                    <thead>
                      <tr>
                        <th>Tax Code</th>
                        <th>Tax Name</th>
                        <th>Rate</th>
                        <th>Type</th>
                        <th>Scope</th>
                        <th>Sequence</th>
                        <th>Default</th>
                        <th>Status</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="taxCode in sortedTaxCodes" :key="taxCode.id">
                        <td>
                          <span class="fw-bold text-primary">{{ taxCode.tax_code }}</span>
                        </td>
                        <td>{{ taxCode.tax_name }}</td>
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
                          <span class="badge bg-warning text-dark">
                            {{ taxCode.pivot?.sequence || 0 }}
                          </span>
                        </td>
                        <td>
                          <span v-if="taxCode.pivot?.is_default" class="badge bg-success">
                            <i class="fas fa-check"></i> Default
                          </span>
                          <span v-else class="text-muted">-</span>
                        </td>
                        <td>
                          <span :class="taxCode.is_active ? 'badge bg-success' : 'badge bg-danger'">
                            {{ taxCode.is_active ? 'Active' : 'Inactive' }}
                          </span>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>

                <!-- Tax Summary -->
                <div class="tax-summary mt-3">
                  <div class="row g-2">
                    <div class="col-md-4">
                      <div class="stat-card">
                        <div class="stat-value">{{ activeTaxCodes.length }}</div>
                        <div class="stat-label">Active Tax Codes</div>
                      </div>
                    </div>
                    <div class="col-md-4">
                      <div class="stat-card">
                        <div class="stat-value">{{ defaultTaxCodes.length }}</div>
                        <div class="stat-label">Default Tax Codes</div>
                      </div>
                    </div>
                    <div class="col-md-4">
                      <div class="stat-card">
                        <div class="stat-value">{{ totalTaxRate.toFixed(2) }}%</div>
                        <div class="stat-label">Total Rate (Default)</div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Tax Calculator -->
          <div v-if="showTaxCalculation && category.tax_codes?.length > 0" class="card mt-3">
            <div class="card-header">
              <h6 class="mb-0">
                <i class="fas fa-calculator me-2"></i>
                Tax Calculator
              </h6>
            </div>
            <div class="card-body">
              <div class="row g-3">
                <div class="col-md-4">
                  <label class="form-label">Base Amount</label>
                  <input 
                    type="number" 
                    v-model.number="calculatorAmount"
                    @input="calculateTaxes"
                    class="form-control"
                    min="0"
                    step="0.01"
                    placeholder="Enter amount..."
                  >
                </div>
                <div class="col-md-4">
                  <label class="form-label">Tax Type</label>
                  <select v-model="calculatorTaxType" @change="calculateTaxes" class="form-select">
                    <option value="default">Default Taxes Only</option>
                    <option value="all">All Taxes</option>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Pricing</label>
                  <select v-model="calculatorInclusive" @change="calculateTaxes" class="form-select">
                    <option :value="false">Tax Exclusive</option>
                    <option :value="true">Tax Inclusive</option>
                  </select>
                </div>
              </div>

              <!-- Calculation Results -->
              <div v-if="calculatorAmount > 0" class="calculation-results mt-3">
                <div class="table-responsive">
                  <table class="table table-sm">
                    <thead>
                      <tr>
                        <th>Tax Code</th>
                        <th>Rate</th>
                        <th>Tax Amount</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="tax in calculationResults" :key="tax.tax_code">
                        <td>{{ tax.tax_code }}</td>
                        <td>{{ tax.tax_rate }}%</td>
                        <td class="fw-bold">{{ formatCurrency(tax.tax_amount) }}</td>
                      </tr>
                    </tbody>
                    <tfoot>
                      <tr class="table-primary">
                        <th>Total Tax:</th>
                        <th></th>
                        <th class="fw-bold">{{ formatCurrency(totalCalculatedTax) }}</th>
                      </tr>
                      <tr class="table-success">
                        <th>Grand Total:</th>
                        <th></th>
                        <th class="fw-bold">{{ formatCurrency(calculatorAmount + totalCalculatedTax) }}</th>
                      </tr>
                    </tfoot>
                  </table>
                </div>
              </div>
            </div>
          </div>

          <!-- Metadata -->
          <div class="metadata mt-4">
            <div class="row g-2">
              <div class="col-md-6" v-if="category.created_at">
                <small class="text-muted">
                  <strong>Created:</strong> {{ formatDate(category.created_at) }}
                </small>
              </div>
              <div class="col-md-6" v-if="category.updated_at">
                <small class="text-muted">
                  <strong>Last Updated:</strong> {{ formatDate(category.updated_at) }}
                </small>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" @click="closeModal">
            Close
          </button>
          <button type="button" class="btn btn-primary" @click="editCategory" v-if="category">
            <i class="fas fa-edit me-2"></i>
            Edit Category
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
/* eslint-disable */
import { ref, computed, watch } from 'vue'

const props = defineProps({
  show: Boolean,
  category: Object
})

const emit = defineEmits(['close', 'edit'])

// Reactive data
const showTaxCalculation = ref(false)
const calculatorAmount = ref(100)
const calculatorTaxType = ref('default')
const calculatorInclusive = ref(false)
const calculationResults = ref([])

// Computed properties
const sortedTaxCodes = computed(() => {
  if (!props.category?.tax_codes) return []
  return [...props.category.tax_codes].sort((a, b) => {
    const seqA = a.pivot?.sequence || 0
    const seqB = b.pivot?.sequence || 0
    return seqA - seqB
  })
})

const activeTaxCodes = computed(() => {
  return sortedTaxCodes.value.filter(tc => tc.is_active)
})

const defaultTaxCodes = computed(() => {
  return sortedTaxCodes.value.filter(tc => tc.pivot?.is_default && tc.is_active)
})

const totalTaxRate = computed(() => {
  return defaultTaxCodes.value.reduce((total, tc) => {
    return total + (tc.calculation_type === 'percentage' ? tc.tax_rate : 0)
  }, 0)
})

const totalCalculatedTax = computed(() => {
  return calculationResults.value.reduce((total, tax) => total + tax.tax_amount, 0)
})

// Methods
const closeModal = () => {
  emit('close')
}

const editCategory = () => {
  emit('edit', props.category)
  closeModal()
}

const getStatusBadgeClass = (category) => {
  return category.is_active ? 'badge bg-success' : 'badge bg-danger'
}

const formatTaxRate = (taxCode) => {
  if (taxCode.calculation_type === 'percentage') {
    return `${taxCode.tax_rate}%`
  }
  return `$${taxCode.tax_rate}`
}

const formatTaxType = (type) => {
  return type.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const formatScope = (scope) => {
  return scope.charAt(0).toUpperCase() + scope.slice(1)
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }).format(amount)
}

const calculateTaxes = () => {
  if (!calculatorAmount.value || calculatorAmount.value <= 0) {
    calculationResults.value = []
    return
  }

  const taxCodesToUse = calculatorTaxType.value === 'default' 
    ? defaultTaxCodes.value 
    : activeTaxCodes.value

  calculationResults.value = taxCodesToUse.map(taxCode => {
    let taxAmount = 0
    
    if (taxCode.calculation_type === 'percentage') {
      if (calculatorInclusive.value) {
        taxAmount = calculatorAmount.value * (taxCode.tax_rate / (100 + taxCode.tax_rate))
      } else {
        taxAmount = calculatorAmount.value * (taxCode.tax_rate / 100)
      }
    } else {
      taxAmount = taxCode.tax_rate // Fixed amount
    }

    return {
      tax_code: taxCode.tax_code,
      tax_rate: taxCode.tax_rate,
      tax_amount: taxAmount
    }
  })
}

// Watchers
watch(() => props.show, (newValue) => {
  if (newValue) {
    showTaxCalculation.value = false
    calculatorAmount.value = 100
    calculatorTaxType.value = 'default'
    calculatorInclusive.value = false
    calculationResults.value = []
  }
})

watch(() => props.category, () => {
  if (props.category && calculatorAmount.value > 0) {
    calculateTaxes()
  }
}, { deep: true })
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

.info-item {
  margin-bottom: 1rem;
}

.info-label {
  font-weight: 600;
  color: #495057;
  font-size: 0.875rem;
  margin-bottom: 0.25rem;
  display: block;
}

.info-value {
  color: #212529;
  font-size: 0.95rem;
}

.tax-summary {
  background-color: #f8f9fa;
  border-radius: 8px;
  padding: 1rem;
}

.stat-card {
  text-align: center;
  background-color: white;
  border-radius: 6px;
  padding: 1rem;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.stat-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: #2563eb;
}

.stat-label {
  font-size: 0.75rem;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-top: 0.25rem;
}

.calculation-results {
  background-color: #f8f9fa;
  border-radius: 8px;
  padding: 1rem;
}

.table th {
  background-color: #e9ecef;
  font-weight: 600;
  color: #495057;
  border-bottom: 2px solid #dee2e6;
}

.metadata {
  border-top: 1px solid #dee2e6;
  padding-top: 1rem;
}

.badge {
  font-size: 0.75rem;
}

@media (max-width: 768px) {
  .modal-dialog {
    margin: 0.5rem;
    max-width: calc(100% - 1rem);
  }
  
  .table-responsive {
    font-size: 0.875rem;
  }
  
  .stat-value {
    font-size: 1.25rem;
  }
}
</style>