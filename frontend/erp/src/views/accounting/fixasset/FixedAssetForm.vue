<template>
  <AppLayout>
    <div class="asset-form-page">
      <!-- Page Header -->
      <div class="page-header">
        <div class="header-content">
          <div class="title-section">
            <div class="breadcrumb">
              <router-link to="/accounting/fixed-assets" class="breadcrumb-link">
                <i class="fas fa-building"></i>
                Fixed Assets
              </router-link>
              <i class="fas fa-chevron-right breadcrumb-separator"></i>
              <span class="breadcrumb-current">{{ isEditing ? 'Edit Asset' : 'Create Asset' }}</span>
            </div>
            <h1 class="page-title">
              <i :class="isEditing ? 'fas fa-edit' : 'fas fa-plus'"></i>
              {{ isEditing ? 'Edit Fixed Asset' : 'Create New Fixed Asset' }}
            </h1>
            <p class="page-subtitle">
              {{ isEditing ? 'Update asset information and track changes' : 'Add a new fixed asset to your inventory' }}
            </p>
          </div>
          <div class="header-actions">
            <button @click="goBack" class="btn btn-outline">
              <i class="fas fa-arrow-left"></i>
              Back to List
            </button>
          </div>
        </div>
      </div>

      <!-- Form Container -->
      <div class="form-container">
        <form @submit.prevent="submitForm" class="asset-form">
          <!-- Basic Information Section -->
          <div class="form-section">
            <div class="section-header">
              <h2>
                <i class="fas fa-info-circle"></i>
                Basic Information
              </h2>
              <p>Enter the basic details of the fixed asset</p>
            </div>
            
            <div class="form-grid">
              <div class="form-group">
                <label for="asset_code" class="form-label required">Asset Code</label>
                <input
                  id="asset_code"
                  v-model="form.asset_code"
                  type="text"
                  class="form-input"
                  :class="{ 'error': errors.asset_code }"
                  :disabled="hasDepreciations"
                  placeholder="Enter unique asset code"
                  required
                >
                <span v-if="errors.asset_code" class="error-message">{{ errors.asset_code[0] }}</span>
              </div>
              
              <div class="form-group">
                <label for="name" class="form-label required">Asset Name</label>
                <input
                  id="name"
                  v-model="form.name"
                  type="text"
                  class="form-input"
                  :class="{ 'error': errors.name }"
                  placeholder="Enter asset name"
                  required
                >
                <span v-if="errors.name" class="error-message">{{ errors.name[0] }}</span>
              </div>
              
              <div class="form-group">
                <label for="category" class="form-label required">Category</label>
                <select
                  id="category"
                  v-model="form.category"
                  class="form-select"
                  :class="{ 'error': errors.category }"
                  required
                >
                  <option value="">Select category</option>
                  <option value="Building">Building</option>
                  <option value="Equipment">Equipment</option>
                  <option value="Vehicle">Vehicle</option>
                  <option value="Furniture">Furniture</option>
                  <option value="Computer">Computer</option>
                  <option value="Machinery">Machinery</option>
                  <option value="Other">Other</option>
                </select>
                <span v-if="errors.category" class="error-message">{{ errors.category[0] }}</span>
              </div>
              
              <div class="form-group">
                <label for="status" class="form-label required">Status</label>
                <select
                  id="status"
                  v-model="form.status"
                  class="form-select"
                  :class="{ 'error': errors.status }"
                  required
                >
                  <option value="">Select status</option>
                  <option value="Active">Active</option>
                  <option value="Inactive">Inactive</option>
                  <option value="Under Maintenance">Under Maintenance</option>
                  <option value="Disposed">Disposed</option>
                </select>
                <span v-if="errors.status" class="error-message">{{ errors.status[0] }}</span>
              </div>
            </div>
          </div>

          <!-- Financial Information Section -->
          <div class="form-section">
            <div class="section-header">
              <h2>
                <i class="fas fa-dollar-sign"></i>
                Financial Information
              </h2>
              <p>Enter the financial details and currency information</p>
            </div>
            
            <div class="form-grid">
              <div class="form-group">
                <label for="currency" class="form-label required">Currency</label>
                <select
                  id="currency"
                  v-model="form.currency"
                  class="form-select"
                  :class="{ 'error': errors.currency }"
                  :disabled="hasDepreciations"
                  @change="onCurrencyChange"
                  required
                >
                  <option value="">Select currency</option>
                  <option v-for="currency in availableCurrencies" :key="currency" :value="currency">
                    {{ currency }}
                  </option>
                </select>
                <span v-if="errors.currency" class="error-message">{{ errors.currency[0] }}</span>
                <span v-if="hasDepreciations" class="help-text">
                  <i class="fas fa-info-circle"></i>
                  Currency cannot be changed when depreciation has been recorded
                </span>
              </div>

              <div class="form-group">
                <label for="acquisition_date" class="form-label required">Acquisition Date</label>
                <input
                  id="acquisition_date"
                  v-model="form.acquisition_date"
                  type="date"
                  class="form-input"
                  :class="{ 'error': errors.acquisition_date }"
                  :disabled="hasDepreciations"
                  :max="today"
                  required
                >
                <span v-if="errors.acquisition_date" class="error-message">{{ errors.acquisition_date[0] }}</span>
              </div>
              
              <div class="form-group">
                <label for="acquisition_cost" class="form-label required">Acquisition Cost</label>
                <div class="input-group">
                  <span v-if="form.currency" class="input-prefix">{{ form.currency }}</span>
                  <input
                    id="acquisition_cost"
                    v-model.number="form.acquisition_cost"
                    type="number"
                    step="0.01"
                    min="0"
                    class="form-input"
                    :class="{ 'error': errors.acquisition_cost, 'with-prefix': form.currency }"
                    :disabled="hasDepreciations"
                    placeholder="0.00"
                    required
                  >
                </div>
                <span v-if="errors.acquisition_cost" class="error-message">{{ errors.acquisition_cost[0] }}</span>
                
                <!-- Base Currency Equivalent -->
                <div v-if="baseCurrencyEquivalent.show" class="currency-conversion">
                  <span class="conversion-label">
                    <i class="fas fa-exchange-alt"></i>
                    Equivalent in {{ baseCurrency }}:
                  </span>
                  <span class="conversion-value">
                    {{ baseCurrency }} {{ formatNumber(baseCurrencyEquivalent.acquisitionCost) }}
                  </span>
                  <span class="conversion-rate">
                    (Rate: 1 {{ form.currency }} = {{ baseCurrencyEquivalent.rate }} {{ baseCurrency }})
                  </span>
                </div>
              </div>

              <div class="form-group">
                <label for="salvage_value" class="form-label">Salvage Value</label>
                <div class="input-group">
                  <span v-if="form.currency" class="input-prefix">{{ form.currency }}</span>
                  <input
                    id="salvage_value"
                    v-model.number="form.salvage_value"
                    type="number"
                    step="0.01"
                    min="0"
                    class="form-input"
                    :class="{ 'error': errors.salvage_value, 'with-prefix': form.currency }"
                    placeholder="0.00"
                  >
                </div>
                <span v-if="errors.salvage_value" class="error-message">{{ errors.salvage_value[0] }}</span>
                <span class="help-text">
                  <i class="fas fa-info-circle"></i>
                  Estimated value at end of useful life
                </span>
              </div>

              <div class="form-group">
                <label for="current_value" class="form-label">Current Value</label>
                <div class="input-group">
                  <span v-if="form.currency" class="input-prefix">{{ form.currency }}</span>
                  <input
                    id="current_value"
                    v-model.number="form.current_value"
                    type="number"
                    step="0.01"
                    min="0"
                    class="form-input"
                    :class="{ 'error': errors.current_value, 'with-prefix': form.currency }"
                    placeholder="0.00"
                  >
                </div>
                <span v-if="errors.current_value" class="error-message">{{ errors.current_value[0] }}</span>
                <span class="help-text">
                  <i class="fas fa-info-circle"></i>
                  Leave empty to auto-calculate from acquisition cost
                </span>
              </div>
            </div>
          </div>

          <!-- Depreciation Configuration Section -->
          <div class="form-section">
            <div class="section-header">
              <h2>
                <i class="fas fa-chart-line-down"></i>
                Depreciation Configuration
              </h2>
              <p>Configure how this asset will be depreciated</p>
            </div>
            
            <div class="form-grid">
              <div class="form-group">
                <label for="depreciation_method" class="form-label required">Depreciation Method</label>
                <select
                  id="depreciation_method"
                  v-model="form.depreciation_method"
                  class="form-select"
                  :class="{ 'error': errors.depreciation_method }"
                  :disabled="hasDepreciations"
                  required
                >
                  <option value="">Select method</option>
                  <option value="straight_line">Straight Line</option>
                  <option value="declining_balance">Declining Balance</option>
                  <option value="sum_of_years">Sum of Years Digits</option>
                  <option value="units_of_production">Units of Production</option>
                </select>
                <span v-if="errors.depreciation_method" class="error-message">{{ errors.depreciation_method[0] }}</span>
              </div>

              <div class="form-group">
                <label for="useful_life_years" class="form-label required">Useful Life (Years)</label>
                <input
                  id="useful_life_years"
                  v-model.number="form.useful_life_years"
                  type="number"
                  min="1"
                  max="100"
                  class="form-input"
                  :class="{ 'error': errors.useful_life_years }"
                  :disabled="hasDepreciations"
                  placeholder="10"
                  required
                >
                <span v-if="errors.useful_life_years" class="error-message">{{ errors.useful_life_years[0] }}</span>
              </div>
              
              <div class="form-group">
                <label for="depreciation_rate" class="form-label required">Annual Depreciation Rate (%)</label>
                <div class="input-group">
                  <input
                    id="depreciation_rate"
                    v-model.number="form.depreciation_rate"
                    type="number"
                    step="0.01"
                    min="0"
                    max="100"
                    class="form-input"
                    :class="{ 'error': errors.depreciation_rate }"
                    :disabled="hasDepreciations"
                    placeholder="10.00"
                    required
                  >
                  <span class="input-suffix">%</span>
                </div>
                <span v-if="errors.depreciation_rate" class="error-message">{{ errors.depreciation_rate[0] }}</span>
              </div>
            </div>

            <!-- Depreciation Preview -->
            <div v-if="showDepreciationPreview" class="depreciation-preview">
              <h3>
                <i class="fas fa-calculator"></i>
                Depreciation Preview
              </h3>
              <div class="preview-grid">
                <div class="preview-card">
                  <div class="preview-label">Depreciable Amount</div>
                  <div class="preview-value">
                    {{ form.currency }} {{ formatNumber(depreciationPreview.depreciableAmount) }}
                  </div>
                </div>
                <div class="preview-card">
                  <div class="preview-label">Annual Depreciation</div>
                  <div class="preview-value">
                    {{ form.currency }} {{ formatNumber(depreciationPreview.annualDepreciation) }}
                  </div>
                </div>
                <div class="preview-card">
                  <div class="preview-label">Monthly Depreciation</div>
                  <div class="preview-value">
                    {{ form.currency }} {{ formatNumber(depreciationPreview.monthlyDepreciation) }}
                  </div>
                </div>
                <div class="preview-card">
                  <div class="preview-label">Years to Full Depreciation</div>
                  <div class="preview-value">
                    {{ depreciationPreview.yearsToFullDepreciation }} years
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Exchange Rate Information (for non-base currencies) -->
          <div v-if="showExchangeRateSection" class="form-section">
            <div class="section-header">
              <h2>
                <i class="fas fa-exchange-alt"></i>
                Exchange Rate Information
              </h2>
              <p>Current exchange rate for currency conversion</p>
            </div>
            
            <div class="exchange-rate-info">
              <div class="rate-display">
                <div class="rate-pair">
                  <span class="from-currency">{{ form.currency }}</span>
                  <i class="fas fa-arrow-right"></i>
                  <span class="to-currency">{{ baseCurrency }}</span>
                </div>
                <div class="rate-value">
                  <span class="rate-number">{{ currentExchangeRate }}</span>
                  <span class="rate-date">as of {{ formatDate(today) }}</span>
                </div>
              </div>
              
              <div class="rate-warning" v-if="exchangeRateWarning">
                <i class="fas fa-exclamation-triangle"></i>
                {{ exchangeRateWarning }}
              </div>
            </div>
          </div>

          <!-- Protected Fields Warning -->
          <div v-if="hasDepreciations" class="form-section warning-section">
            <div class="warning-header">
              <i class="fas fa-shield-alt"></i>
              <h3>Protected Fields</h3>
            </div>
            <p>
              This asset has depreciation records. The following fields cannot be modified:
              <strong>Asset Code, Currency, Acquisition Date, Acquisition Cost, Depreciation Method, Useful Life, and Depreciation Rate</strong>.
            </p>
          </div>

          <!-- Form Actions -->
          <div class="form-actions">
            <button type="button" @click="goBack" class="btn btn-secondary">
              <i class="fas fa-times"></i>
              Cancel
            </button>
            <button type="submit" :disabled="loading || !isFormValid" class="btn btn-primary">
              <i :class="loading ? 'fas fa-spinner fa-spin' : (isEditing ? 'fas fa-save' : 'fas fa-plus')"></i>
              {{ loading ? 'Saving...' : (isEditing ? 'Update Asset' : 'Create Asset') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>

<script>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import axios from 'axios'

export default {
  name: 'FixedAssetForm',
  components: {
  },
  setup() {
    const router = useRouter()
    const route = useRoute()
    
    // Reactive data
    const loading = ref(false)
    const errors = ref({})
    const hasDepreciations = ref(false)
    const availableCurrencies = ref(['USD'])
    const baseCurrency = ref('USD')
    const currentExchangeRate = ref(1)
    const exchangeRateWarning = ref('')
    
    const form = reactive({
      asset_code: '',
      name: '',
      category: '',
      acquisition_date: '',
      acquisition_cost: 0,
      current_value: 0,
      depreciation_rate: 10,
      status: '',
      currency: 'USD',
      depreciation_method: 'straight_line',
      useful_life_years: 10,
      salvage_value: 0
    })
    
    // Computed
    const isEditing = computed(() => !!route.params.id)
    
    const today = computed(() => new Date().toISOString().split('T')[0])
    
    const isFormValid = computed(() => {
      return form.asset_code && form.name && form.category && 
             form.acquisition_date && form.acquisition_cost > 0 && 
             form.depreciation_rate >= 0 && form.status && form.currency &&
             form.depreciation_method && form.useful_life_years > 0
    })
    
    const showDepreciationPreview = computed(() => {
      return form.acquisition_cost > 0 && form.depreciation_rate > 0 && 
             form.useful_life_years > 0
    })
    
    const showExchangeRateSection = computed(() => {
      return form.currency && form.currency !== baseCurrency.value
    })
    
    const baseCurrencyEquivalent = computed(() => {
      if (!form.currency || form.currency === baseCurrency.value || !form.acquisition_cost) {
        return { show: false }
      }
      
      return {
        show: true,
        acquisitionCost: form.acquisition_cost * currentExchangeRate.value,
        rate: currentExchangeRate.value
      }
    })
    
    const depreciationPreview = computed(() => {
      if (!showDepreciationPreview.value) return {}
      
      const depreciableAmount = form.acquisition_cost - (form.salvage_value || 0)
      const annualDepreciation = form.depreciation_method === 'straight_line' 
        ? depreciableAmount / form.useful_life_years
        : (depreciableAmount * form.depreciation_rate) / 100
      
      return {
        depreciableAmount,
        annualDepreciation,
        monthlyDepreciation: annualDepreciation / 12,
        yearsToFullDepreciation: form.depreciation_method === 'straight_line' 
          ? form.useful_life_years 
          : Math.ceil(100 / form.depreciation_rate)
      }
    })
    
    // Methods
    const fetchAvailableCurrencies = async () => {
      try {
        const response = await axios.get('/accounting/system-currencies')
        availableCurrencies.value = response.data.data.map(c => c.code || c.currency || c.name)
        
        // Set base currency from config or default
        baseCurrency.value = 'USD' // This should come from app config
        if (!form.currency) {
          form.currency = baseCurrency.value
        }
      } catch (error) {
        console.error('Error fetching currencies:', error)
      }
    }
    
    const fetchExchangeRate = async () => {
      if (!form.currency || form.currency === baseCurrency.value) {
        currentExchangeRate.value = 1
        exchangeRateWarning.value = ''
        return
      }
      
      try {
        // This should call your exchange rate API
        const response = await axios.get('/accounting/exchange-rates/current', {
          params: {
            from_currency: form.currency,
            to_currency: baseCurrency.value,
            date: form.acquisition_date || today.value
          }
        })
        
        currentExchangeRate.value = response.data.rate || 1
        exchangeRateWarning.value = response.data.rate ? '' : 'Exchange rate not found, using rate 1.0'
      } catch (error) {
        console.error('Error fetching exchange rate:', error)
        currentExchangeRate.value = 1
        exchangeRateWarning.value = 'Unable to fetch exchange rate, using rate 1.0'
      }
    }
    
    const onCurrencyChange = async () => {
      await fetchExchangeRate()
    }
    
    const fetchAsset = async () => {
      if (!isEditing.value) return
      
      try {
        loading.value = true
        const response = await axios.get(`/accounting/fixed-assets/${route.params.id}`)
        const asset = response.data.data
        
        // Populate form
        Object.keys(form).forEach(key => {
          if (asset[key] !== undefined) {
            form[key] = asset[key]
          }
        })
        
        // Set defaults for new fields if not present
        if (!form.depreciation_method) form.depreciation_method = 'straight_line'
        if (!form.useful_life_years) form.useful_life_years = 10
        if (!form.salvage_value) form.salvage_value = 0
        if (!form.currency) form.currency = baseCurrency.value
        
        // Check if asset has depreciation records
        hasDepreciations.value = asset.asset_depreciations && asset.asset_depreciations.length > 0
        
        // Fetch exchange rate for the asset's currency
        await fetchExchangeRate()
        
      } catch (error) {
        console.error('Error fetching asset:', error)
        if (error.response?.status === 404) {
          router.push('/accounting/fixed-assets')
        }
      } finally {
        loading.value = false
      }
    }
    
    const validateForm = () => {
      errors.value = {}
      
      if (!form.asset_code) {
        errors.value.asset_code = ['Asset code is required']
      }
      
      if (!form.name) {
        errors.value.name = ['Asset name is required']
      }
      
      if (!form.category) {
        errors.value.category = ['Category is required']
      }
      
      if (!form.acquisition_date) {
        errors.value.acquisition_date = ['Acquisition date is required']
      }
      
      if (!form.acquisition_cost || form.acquisition_cost <= 0) {
        errors.value.acquisition_cost = ['Acquisition cost must be greater than 0']
      }
      
      if (form.depreciation_rate < 0 || form.depreciation_rate > 100) {
        errors.value.depreciation_rate = ['Depreciation rate must be between 0 and 100']
      }
      
      if (!form.status) {
        errors.value.status = ['Status is required']
      }
      
      if (!form.currency) {
        errors.value.currency = ['Currency is required']
      }
      
      if (!form.depreciation_method) {
        errors.value.depreciation_method = ['Depreciation method is required']
      }
      
      if (!form.useful_life_years || form.useful_life_years <= 0) {
        errors.value.useful_life_years = ['Useful life must be greater than 0']
      }
      
      if (form.salvage_value && form.salvage_value >= form.acquisition_cost) {
        errors.value.salvage_value = ['Salvage value must be less than acquisition cost']
      }
      
      return Object.keys(errors.value).length === 0
    }
    
    const submitForm = async () => {
      if (!validateForm()) return
      
      try {
        loading.value = true
        errors.value = {}
        
        // Set current_value to acquisition_cost if not provided
        if (!form.current_value) {
          form.current_value = form.acquisition_cost
        }
        
        const url = isEditing.value 
          ? `/accounting/fixed-assets/${route.params.id}`
          : '/accounting/fixed-assets'
        
        const method = isEditing.value ? 'put' : 'post'
        
        const response = await axios[method](url, form)
        
        // Show success message
        console.log('Asset saved successfully:', response.data)
        
        // Redirect to asset list or detail
        if (isEditing.value) {
          router.push(`/accounting/fixed-assets/${route.params.id}`)
        } else {
          router.push('/accounting/fixed-assets')
        }
        
      } catch (error) {
        console.error('Error saving asset:', error)
        
        if (error.response?.status === 422) {
          errors.value = error.response.data.errors || {}
        } else {
          // Show general error message
          console.error('Failed to save asset')
        }
      } finally {
        loading.value = false
      }
    }
    
    const goBack = () => {
      if (isEditing.value) {
        router.push(`/accounting/fixed-assets/${route.params.id}`)
      } else {
        router.push('/accounting/fixed-assets')
      }
    }
    
    const formatNumber = (value) => {
      return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      }).format(value || 0)
    }
    
    const formatDate = (date) => {
      return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      })
    }
    
    // Watchers
    watch(() => form.acquisition_date, fetchExchangeRate)
    watch(() => form.useful_life_years, (newValue) => {
      if (newValue && form.depreciation_method === 'straight_line') {
        form.depreciation_rate = Number((100 / newValue).toFixed(2))
      }
    })
    watch(() => form.depreciation_rate, (newValue) => {
      if (newValue && form.depreciation_method === 'straight_line') {
        form.useful_life_years = Math.ceil(100 / newValue)
      }
    })
    
    // Lifecycle
    onMounted(async () => {
      await fetchAvailableCurrencies()
      await fetchAsset()
    })
    
    return {
      loading,
      errors,
      hasDepreciations,
      availableCurrencies,
      baseCurrency,
      currentExchangeRate,
      exchangeRateWarning,
      form,
      isEditing,
      today,
      isFormValid,
      showDepreciationPreview,
      showExchangeRateSection,
      baseCurrencyEquivalent,
      depreciationPreview,
      onCurrencyChange,
      submitForm,
      goBack,
      formatNumber,
      formatDate
    }
  }
}
</script>

<style scoped>
.asset-form-page {
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem;
}

/* Page Header */
.page-header {
  margin-bottom: 2rem;
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 2rem;
}

.title-section {
  flex: 1;
}

.breadcrumb {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 1rem;
  font-size: 0.875rem;
}

.breadcrumb-link {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  color: #6366f1;
  text-decoration: none;
  transition: color 0.2s ease;
}

.breadcrumb-link:hover {
  color: #5a67d8;
}

.breadcrumb-separator {
  color: #9ca3af;
}

.breadcrumb-current {
  color: #64748b;
}

.page-title {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-size: 2rem;
  font-weight: 700;
  color: #1e293b;
  margin: 0 0 0.5rem 0;
}

.page-title i {
  color: #6366f1;
}

.page-subtitle {
  color: #64748b;
  font-size: 1rem;
  margin: 0;
}

.header-actions {
  flex-shrink: 0;
}

/* Form Container */
.form-container {
  background: white;
  border-radius: 16px;
  border: 1px solid #e2e8f0;
  overflow: hidden;
}

.asset-form {
  display: flex;
  flex-direction: column;
}

/* Form Sections */
.form-section {
  padding: 2rem;
  border-bottom: 1px solid #e2e8f0;
}

.form-section:last-child {
  border-bottom: none;
}

.warning-section {
  background: #fefce8;
  border-color: #facc15;
}

.section-header {
  margin-bottom: 2rem;
}

.section-header h2 {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-size: 1.5rem;
  font-weight: 600;
  color: #1e293b;
  margin: 0 0 0.5rem 0;
}

.section-header h2 i {
  color: #6366f1;
}

.section-header p {
  color: #64748b;
  margin: 0;
}

.warning-header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 1rem;
}

.warning-header i {
  color: #f59e0b;
}

.warning-header h3 {
  color: #92400e;
  margin: 0;
}

/* Form Grid */
.form-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 1.5rem;
}

/* Form Groups */
.form-group {
  display: flex;
  flex-direction: column;
}

.form-label {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  font-weight: 500;
  color: #374151;
  margin-bottom: 0.5rem;
  font-size: 0.875rem;
}

.form-label.required::after {
  content: '*';
  color: #ef4444;
  margin-left: 0.25rem;
}

/* Form Inputs */
.form-input,
.form-select {
  padding: 0.75rem 1rem;
  border: 2px solid #e2e8f0;
  border-radius: 10px;
  font-size: 1rem;
  transition: all 0.3s ease;
  background: white;
}

.form-input:focus,
.form-select:focus {
  outline: none;
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.form-input.error,
.form-select.error {
  border-color: #ef4444;
}

.form-input:disabled,
.form-select:disabled {
  background: #f8fafc;
  color: #94a3b8;
  cursor: not-allowed;
}

/* Input Groups */
.input-group {
  position: relative;
  display: flex;
  align-items: center;
}

.input-prefix {
  position: absolute;
  left: 1rem;
  color: #64748b;
  font-weight: 500;
  z-index: 1;
  font-size: 0.875rem;
}

.input-suffix {
  position: absolute;
  right: 1rem;
  color: #64748b;
  font-weight: 500;
  z-index: 1;
  font-size: 0.875rem;
}

.form-input.with-prefix {
  padding-left: 3rem;
}

/* Error Messages */
.error-message {
  color: #ef4444;
  font-size: 0.75rem;
  margin-top: 0.25rem;
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.help-text {
  color: #6b7280;
  font-size: 0.75rem;
  margin-top: 0.25rem;
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

/* Currency Conversion */
.currency-conversion {
  background: #f0f9ff;
  border: 1px solid #bae6fd;
  border-radius: 6px;
  padding: 0.75rem;
  margin-top: 0.5rem;
}

.conversion-label {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.75rem;
  color: #0369a1;
  font-weight: 500;
  margin-bottom: 0.25rem;
}

.conversion-value {
  font-weight: 600;
  color: #1e293b;
  font-size: 0.875rem;
}

.conversion-rate {
  color: #64748b;
  font-size: 0.625rem;
  margin-top: 0.25rem;
  display: block;
}

/* Depreciation Preview */
.depreciation-preview {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 1.5rem;
  margin-top: 2rem;
}

.depreciation-preview h3 {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #1e293b;
  margin: 0 0 1rem 0;
  font-size: 1.125rem;
}

.depreciation-preview h3 i {
  color: #6366f1;
}

.preview-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}

.preview-card {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 1rem;
  text-align: center;
}

.preview-label {
  color: #64748b;
  font-size: 0.75rem;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-bottom: 0.5rem;
}

.preview-value {
  color: #1e293b;
  font-size: 1.125rem;
  font-weight: 600;
}

/* Exchange Rate Section */
.exchange-rate-info {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 1.5rem;
}

.rate-display {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.rate-pair {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.from-currency,
.to-currency {
  background: #6366f1;
  color: white;
  padding: 0.25rem 0.75rem;
  border-radius: 6px;
  font-weight: 600;
  font-size: 0.875rem;
}

.rate-pair i {
  color: #64748b;
}

.rate-value {
  text-align: right;
}

.rate-number {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1e293b;
  display: block;
}

.rate-date {
  color: #64748b;
  font-size: 0.75rem;
}

.rate-warning {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: #fef3c7;
  color: #92400e;
  padding: 0.75rem;
  border-radius: 6px;
  font-size: 0.875rem;
}

/* Form Actions */
.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  padding: 2rem;
  background: #f8fafc;
  border-top: 1px solid #e2e8f0;
}

/* Buttons */
.btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  border: 1px solid transparent;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 500;
  text-decoration: none;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-primary {
  background: #6366f1;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: #5a67d8;
}

.btn-secondary {
  background: #e2e8f0;
  color: #64748b;
}

.btn-secondary:hover:not(:disabled) {
  background: #cbd5e1;
}

.btn-outline {
  border-color: #d1d5db;
  background: white;
  color: #374151;
}

.btn-outline:hover:not(:disabled) {
  border-color: #6366f1;
  color: #6366f1;
}

/* Responsive Design */
@media (max-width: 768px) {
  .asset-form-page {
    padding: 1rem;
  }
  
  .header-content {
    flex-direction: column;
    gap: 1rem;
  }
  
  .form-grid {
    grid-template-columns: 1fr;
  }
  
  .preview-grid {
    grid-template-columns: 1fr;
  }
  
  .rate-display {
    flex-direction: column;
    gap: 1rem;
    text-align: center;
  }
  
  .form-actions {
    flex-direction: column;
  }
}
</style>