<template>
  <div class="tax-selector">
    <label class="form-label fw-bold">Taxes</label>
    <div class="tax-selection-container">
      <!-- Selected Taxes Display -->
      <div class="selected-taxes" v-if="selectedTaxes.length > 0">
        <TransitionGroup name="tax-item" tag="div">
          <div 
            v-for="tax in selectedTaxes" 
            :key="tax.id"
            class="selected-tax-item"
          >
            <div class="tax-info">
              <span class="tax-code">{{ tax.tax_code }}</span>
              <span class="tax-rate">
                {{ tax.calculation_type === 'percentage' ? `${tax.tax_rate}%` : `${tax.tax_rate}` }}
              </span>
            </div>
            <div class="tax-amount">
              ${{ formatAmount(tax.calculated_amount) }}
            </div>
            <button 
              @click="removeTax(tax.id)" 
              class="remove-tax-btn"
              type="button"
              title="Remove tax"
            >
              <i class="fas fa-times"></i>
            </button>
          </div>
        </TransitionGroup>
      </div>
      
      <!-- Tax Selection Controls -->
      <div class="tax-controls">
        <div class="d-flex gap-2 align-items-center">
          <select 
            v-model="selectedTaxId" 
            @change="addTax"
            class="form-select tax-dropdown"
            :disabled="loading"
          >
            <option value="">{{ loading ? 'Loading...' : 'Select a tax...' }}</option>
            <option 
              v-for="tax in availableTaxCodes" 
              :key="tax.id"
              :value="tax.id"
              :disabled="isAlreadySelected(tax.id)"
            >
              {{ tax.tax_code }} - {{ tax.tax_name }} 
              ({{ tax.calculation_type === 'percentage' ? `${tax.tax_rate}%` : `${tax.tax_rate}` }})
            </option>
          </select>
          
          <button 
            v-if="hasTaxCategory && !allDefaultTaxesAdded"
            @click="addDefaultTaxes" 
            class="btn btn-outline-secondary"
            type="button"
            :disabled="loading"
            title="Add default taxes from category"
          >
            <i class="fas fa-plus-circle me-1"></i>
            Add Default
          </button>
        </div>
      </div>
      
      <!-- Tax Summary -->
      <div class="tax-summary mt-3" v-if="selectedTaxes.length > 0">
        <div class="row g-2">
          <div class="col-6">
            <div class="summary-item">
              <label>Subtotal:</label>
              <span class="amount">${{ formatAmount(subtotalAmount) }}</span>
            </div>
          </div>
          <div class="col-6">
            <div class="summary-item">
              <label>Total Tax:</label>
              <span class="amount text-info">${{ formatAmount(totalTaxAmount) }}</span>
            </div>
          </div>
          <div class="col-12">
            <div class="summary-item grand-total">
              <label>Grand Total:</label>
              <span class="amount text-primary fw-bold">${{ formatAmount(grandTotal) }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Error Display -->
      <div v-if="error" class="alert alert-danger mt-2" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        {{ error }}
      </div>
    </div>
  </div>
</template>

<script setup>
/* eslint-disable */
import { ref, computed, watch, onMounted } from 'vue'
import { useTaxCodeStore } from '@/stores/taxCodeStore'
import { useTaxCategoryStore } from '@/stores/taxCategoryStore'
import { useTaxCalculation } from '@/composables/useTaxCalculation'

const props = defineProps({
  modelValue: {
    type: Array,
    default: () => []
  },
  subtotalAmount: {
    type: Number,
    default: 0
  },
  taxCategoryId: {
    type: [Number, String],
    default: null
  },
  scope: {
    type: String,
    default: 'sale',
    validator: (value) => ['sale', 'purchase', 'both'].includes(value)
  },
  isInclusive: {
    type: Boolean,
    default: false
  },
  disabled: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['update:modelValue', 'tax-change'])

// Stores
const taxCodeStore = useTaxCodeStore()
const taxCategoryStore = useTaxCategoryStore()

// Composables
const { calculateSingleTax, formatAmount } = useTaxCalculation()

// Reactive data
const selectedTaxes = ref([])
const availableTaxCodes = ref([])
const defaultTaxCodes = ref([])
const selectedTaxId = ref('')
const loading = ref(false)
const error = ref(null)

// Computed properties
const hasTaxCategory = computed(() => !!props.taxCategoryId)

const totalTaxAmount = computed(() => {
  return selectedTaxes.value.reduce((total, tax) => total + (tax.calculated_amount || 0), 0)
})

const grandTotal = computed(() => {
  return props.subtotalAmount + totalTaxAmount.value
})

const allDefaultTaxesAdded = computed(() => {
  if (!defaultTaxCodes.value.length) return true
  return defaultTaxCodes.value.every(defaultTax => 
    selectedTaxes.value.some(selected => selected.id === defaultTax.id)
  )
})

// Methods
const fetchAvailableTaxes = async () => {
  if (props.disabled) return
  
  loading.value = true
  error.value = null
  try {
    const response = await taxCodeStore.getTaxesByScope(props.scope)
    availableTaxCodes.value = response.data || []
  } catch (err) {
    error.value = 'Failed to load available taxes'
    console.error('Failed to fetch taxes:', err)
  } finally {
    loading.value = false
  }
}

const fetchDefaultTaxCodes = async () => {
  if (!props.taxCategoryId) {
    defaultTaxCodes.value = []
    return
  }
  
  try {
    const response = await taxCategoryStore.getDefaultTaxCodes(props.taxCategoryId)
    defaultTaxCodes.value = response.data || []
  } catch (err) {
    console.error('Failed to fetch default tax codes:', err)
    defaultTaxCodes.value = []
  }
}

const isAlreadySelected = (taxId) => {
  return selectedTaxes.value.some(tax => tax.id === taxId)
}

const addTax = () => {
  if (!selectedTaxId.value || props.disabled) return
  
  const taxCode = availableTaxCodes.value.find(t => t.id === parseInt(selectedTaxId.value))
  if (!taxCode || isAlreadySelected(taxCode.id)) {
    selectedTaxId.value = ''
    return
  }
  
  const calculatedAmount = calculateSingleTax(taxCode, props.subtotalAmount, props.isInclusive)
  
  selectedTaxes.value.push({
    ...taxCode,
    calculated_amount: calculatedAmount
  })
  
  selectedTaxId.value = ''
  emitTaxChange()
}

const removeTax = (taxId) => {
  if (props.disabled) return
  
  selectedTaxes.value = selectedTaxes.value.filter(t => t.id !== taxId)
  emitTaxChange()
}

const addDefaultTaxes = async () => {
  if (!props.taxCategoryId || props.disabled) return
  
  loading.value = true
  try {
    await fetchDefaultTaxCodes()
    
    defaultTaxCodes.value.forEach(taxCode => {
      if (!isAlreadySelected(taxCode.id)) {
        const calculatedAmount = calculateSingleTax(taxCode, props.subtotalAmount, props.isInclusive)
        selectedTaxes.value.push({
          ...taxCode,
          calculated_amount: calculatedAmount
        })
      }
    })
    
    emitTaxChange()
  } catch (err) {
    error.value = 'Failed to add default taxes'
  } finally {
    loading.value = false
  }
}

const recalculateTaxes = () => {
  selectedTaxes.value.forEach(tax => {
    tax.calculated_amount = calculateSingleTax(tax, props.subtotalAmount, props.isInclusive)
  })
  emitTaxChange()
}

const emitTaxChange = () => {
  emit('update:modelValue', selectedTaxes.value)
  emit('tax-change', {
    taxes: selectedTaxes.value,
    totalTaxAmount: totalTaxAmount.value,
    grandTotal: grandTotal.value,
    subtotalAmount: props.subtotalAmount
  })
}

// Watchers
watch(() => props.subtotalAmount, () => {
  if (props.subtotalAmount >= 0) {
    recalculateTaxes()
  }
}, { immediate: true })

watch(() => props.taxCategoryId, () => {
  fetchDefaultTaxCodes()
})

watch(() => props.modelValue, (newValue) => {
  if (newValue && Array.isArray(newValue)) {
    selectedTaxes.value = [...newValue]
  } else {
    selectedTaxes.value = []
  }
}, { immediate: true, deep: true })

watch(() => props.scope, () => {
  fetchAvailableTaxes()
})

// Lifecycle
onMounted(() => {
  fetchAvailableTaxes()
  fetchDefaultTaxCodes()
})
</script>

<style scoped>
.tax-selector {
  margin-bottom: 1rem;
}

.tax-selection-container {
  border: 1px solid #dee2e6;
  border-radius: 8px;
  padding: 1rem;
  background-color: #f8f9fa;
}

.selected-taxes {
  margin-bottom: 1rem;
}

.selected-tax-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
  border: 1px solid #e9ecef;
  border-radius: 6px;
  padding: 0.75rem;
  margin-bottom: 0.5rem;
  box-shadow: 0 2px 4px rgba(0,0,0,0.05);
  transition: all 0.2s ease;
}

.selected-tax-item:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.tax-info {
  display: flex;
  flex-direction: column;
  flex: 1;
}

.tax-code {
  font-weight: 600;
  color: #0d6efd;
  font-size: 0.9rem;
}

.tax-rate {
  color: #6c757d;
  font-size: 0.8rem;
}

.tax-amount {
  font-weight: 600;
  color: #198754;
  font-size: 1rem;
  margin: 0 1rem;
}

.remove-tax-btn {
  background: none;
  border: none;
  color: #dc3545;
  cursor: pointer;
  padding: 0.25rem;
  border-radius: 50%;
  width: 30px;
  height: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
}

.remove-tax-btn:hover {
  background-color: #dc3545;
  color: white;
  transform: scale(1.1);
}

.tax-dropdown {
  flex: 1;
  min-width: 250px;
}

.tax-summary {
  border-top: 1px solid #dee2e6;
  padding-top: 1rem;
  background: linear-gradient(135deg, #ffffff 0%, #f1f3f4 100%);
  border-radius: 6px;
  padding: 1rem;
}

.summary-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.25rem 0;
}

.summary-item label {
  font-weight: 500;
  color: #495057;
  margin: 0;
}

.summary-item .amount {
  font-weight: 600;
}

.grand-total {
  border-top: 1px solid #dee2e6;
  padding-top: 0.5rem;
  margin-top: 0.5rem;
}

.grand-total .amount {
  font-size: 1.1rem;
}

/* Transitions */
.tax-item-enter-active,
.tax-item-leave-active {
  transition: all 0.3s ease;
}

.tax-item-enter-from {
  opacity: 0;
  transform: translateX(-30px);
}

.tax-item-leave-to {
  opacity: 0;
  transform: translateX(30px);
}

/* Responsive */
@media (max-width: 768px) {
  .selected-tax-item {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.5rem;
  }
  
  .tax-amount {
    margin: 0;
    align-self: flex-end;
  }
  
  .remove-tax-btn {
    align-self: flex-end;
  }
}
</style>