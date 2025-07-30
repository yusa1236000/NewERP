<!-- frontend/erp/src/views/accounting/depreciacions/DepreciationForm.vue -->
<template>
  <AppLayout>
    <!-- Page Header -->
    <div class="page-header">
      <div class="header-content">
        <div class="title-section">
          <div class="breadcrumb">
            <router-link to="/accounting" class="breadcrumb-item">
              <i class="fas fa-calculator"></i>
              Accounting
            </router-link>
            <i class="fas fa-chevron-right"></i>
            <router-link to="/accounting/depreciations" class="breadcrumb-item">
              Asset Depreciations
            </router-link>
            <i class="fas fa-chevron-right"></i>
            <span class="breadcrumb-current">{{ isEditMode ? 'Edit' : 'Create' }} Depreciation</span>
          </div>
          <h1 class="page-title">
            <i class="fas fa-calculator"></i>
            {{ isEditMode ? 'Edit' : 'Create' }} Asset Depreciation
          </h1>
          <p class="page-subtitle">
            {{ isEditMode ? 'Modify existing depreciation record' : 'Calculate and record depreciation for an asset' }}
          </p>
        </div>
        <div class="header-actions">
          <button @click="previewCalculation" class="btn btn-outline" :disabled="!canPreview">
            <i class="fas fa-eye"></i>
            Preview
          </button>
          <button @click="goBack" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i>
            Back
          </button>
        </div>
      </div>
    </div>

    <!-- Form Content -->
    <div class="form-content">
      <form @submit.prevent="submitForm" class="depreciation-form">
        <!-- Asset Selection Section -->
        <div class="form-section">
          <div class="section-header">
            <h3>
              <i class="fas fa-cube"></i>
              Asset Information
            </h3>
            <p>Select the asset and period for depreciation calculation</p>
          </div>

          <div class="form-grid">
            <div class="form-group">
              <label>Asset *</label>
              <select 
                v-model="formData.asset_id" 
                @change="onAssetChange"
                :disabled="isEditMode"
                required
              >
                <option value="">Select Asset</option>
                <option 
                  v-for="asset in assets" 
                  :key="asset.asset_id" 
                  :value="asset.asset_id"
                >
                  {{ asset.name }} ({{ asset.asset_code }}) - {{ asset.currency }}
                </option>
              </select>
              <small v-if="selectedAsset" class="asset-info">
                Current Value: {{ selectedAsset.currency }} {{ formatCurrency(selectedAsset.current_value) }} |
                Rate: {{ selectedAsset.depreciation_rate }}% per year
              </small>
            </div>

            <div class="form-group">
              <label>Accounting Period *</label>
              <select 
                v-model="formData.period_id" 
                @change="onPeriodChange"
                :disabled="isEditMode"
                required
              >
                <option value="">Select Period</option>
                <option 
                  v-for="period in periods" 
                  :key="period.period_id" 
                  :value="period.period_id"
                  :disabled="period.status === 'Closed'"
                >
                  {{ period.period_name }} ({{ formatDate(period.start_date) }} - {{ formatDate(period.end_date) }})
                </option>
              </select>
              <small v-if="selectedPeriod" class="period-info">
                Status: {{ selectedPeriod.status }} | Duration: {{ getPeriodDuration(selectedPeriod) }} days
              </small>
            </div>
          </div>

          <!-- Asset Details Card -->
          <div v-if="selectedAsset" class="asset-details-card">
            <div class="asset-header">
              <div class="asset-icon">
                <i :class="getAssetIcon(selectedAsset.category)"></i>
              </div>
              <div class="asset-info">
                <h4>{{ selectedAsset.name }}</h4>
                <div class="asset-meta">
                  <span class="asset-code">{{ selectedAsset.asset_code }}</span>
                  <span class="asset-category">{{ selectedAsset.category }}</span>
                  <span class="asset-status">{{ selectedAsset.status }}</span>
                </div>
              </div>
            </div>
            
            <div class="asset-metrics">
              <div class="metric-item">
                <span class="metric-label">Acquisition Cost</span>
                <span class="metric-value">
                  {{ selectedAsset.currency }} {{ formatCurrency(selectedAsset.acquisition_cost) }}
                </span>
              </div>
              <div class="metric-item">
                <span class="metric-label">Current Value</span>
                <span class="metric-value">
                  {{ selectedAsset.currency }} {{ formatCurrency(selectedAsset.current_value) }}
                </span>
              </div>
              <div class="metric-item">
                <span class="metric-label">Depreciation Method</span>
                <span class="metric-value">{{ selectedAsset.depreciation_method || 'Straight Line' }}</span>
              </div>
              <div class="metric-item">
                <span class="metric-label">Useful Life</span>
                <span class="metric-value">{{ selectedAsset.useful_life }} years</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Calculation Section -->
        <div class="form-section">
          <div class="section-header">
            <h3>
              <i class="fas fa-calculator"></i>
              Depreciation Calculation
            </h3>
            <p>Configure depreciation calculation parameters</p>
          </div>

          <div class="form-grid">
            <div class="form-group">
              <label>Depreciation Date *</label>
              <input 
                v-model="formData.depreciation_date" 
                type="date"
                :min="selectedPeriod?.start_date"
                :max="selectedPeriod?.end_date"
                required
              />
              <small class="field-help">Date when depreciation is calculated</small>
            </div>

            <div class="form-group">
              <label>Calculation Method</label>
              <select v-model="formData.calculation_method" @change="calculateDepreciation">
                <option value="straight_line">Straight Line</option>
                <option value="declining_balance">Declining Balance</option>
                <option value="sum_of_years">Sum of Years Digits</option>
                <option value="manual">Manual Entry</option>
              </select>
            </div>
          </div>

          <div class="form-grid">
            <div class="form-group">
              <label>Depreciation Amount *</label>
              <div class="amount-input">
                <span class="currency-prefix">{{ selectedAsset?.currency || 'USD' }}</span>
                <input 
                  v-model.number="formData.depreciation_amount" 
                  type="number" 
                  step="0.01" 
                  min="0"
                  :readonly="formData.calculation_method !== 'manual'"
                  required
                />
              </div>
              <small class="field-help">
                {{ formData.calculation_method === 'manual' ? 'Enter custom amount' : 'Automatically calculated' }}
              </small>
            </div>

            <div class="form-group">
              <label>Prorate Period</label>
              <div class="checkbox-wrapper">
                <input 
                  type="checkbox" 
                  v-model="formData.prorate_period"
                  @change="calculateDepreciation"
                />
                <span>Prorate for partial period</span>
              </div>
              <small class="field-help">Calculate proportional amount for partial periods</small>
            </div>
          </div>

          <!-- Calculation Preview -->
          <div v-if="calculationPreview" class="calculation-preview">
            <h4>Calculation Preview</h4>
            <div class="preview-grid">
              <div class="preview-item">
                <span class="preview-label">Opening Book Value</span>
                <span class="preview-value">
                  {{ selectedAsset?.currency }} {{ formatCurrency(calculationPreview.opening_value) }}
                </span>
              </div>
              <div class="preview-item">
                <span class="preview-label">Depreciation Rate</span>
                <span class="preview-value">{{ selectedAsset?.depreciation_rate }}% annually</span>
              </div>
              <div class="preview-item primary">
                <span class="preview-label">Period Depreciation</span>
                <span class="preview-value">
                  {{ selectedAsset?.currency }} {{ formatCurrency(calculationPreview.depreciation_amount) }}
                </span>
              </div>
              <div class="preview-item">
                <span class="preview-label">Accumulated Depreciation</span>
                <span class="preview-value">
                  {{ selectedAsset?.currency }} {{ formatCurrency(calculationPreview.accumulated_depreciation) }}
                </span>
              </div>
              <div class="preview-item">
                <span class="preview-label">Remaining Book Value</span>
                <span class="preview-value remaining">
                  {{ selectedAsset?.currency }} {{ formatCurrency(calculationPreview.remaining_value) }}
                </span>
              </div>
              <div class="preview-item">
                <span class="preview-label">Depreciation Progress</span>
                <span class="preview-value">{{ calculationPreview.progress_percentage }}%</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Multi-Currency Section -->
        <div v-if="selectedAsset && selectedAsset.currency !== baseCurrency" class="form-section">
          <div class="section-header">
            <h3>
              <i class="fas fa-exchange-alt"></i>
              Currency Conversion
            </h3>
            <p>Exchange rate and base currency calculations</p>
          </div>

          <div class="form-grid">
            <div class="form-group">
              <label>Exchange Rate Date</label>
              <input 
                v-model="formData.exchange_rate_date" 
                type="date"
                @change="loadExchangeRate"
              />
            </div>

            <div class="form-group">
              <label>Exchange Rate ({{ selectedAsset.currency }} to {{ baseCurrency }})</label>
              <input 
                v-model.number="formData.exchange_rate" 
                type="number" 
                step="0.0001" 
                min="0"
                @input="calculateBaseCurrencyAmounts"
              />
              <small class="field-help">1 {{ selectedAsset.currency }} = {{ formData.exchange_rate }} {{ baseCurrency }}</small>
            </div>
          </div>

          <div class="currency-conversion-preview">
            <h4>Base Currency Amounts ({{ baseCurrency }})</h4>
            <div class="conversion-grid">
              <div class="conversion-item">
                <span class="conversion-label">Depreciation Amount</span>
                <span class="conversion-value">
                  {{ baseCurrency }} {{ formatCurrency(formData.base_currency_depreciation_amount) }}
                </span>
              </div>
              <div class="conversion-item">
                <span class="conversion-label">Accumulated Depreciation</span>
                <span class="conversion-value">
                  {{ baseCurrency }} {{ formatCurrency(formData.base_currency_accumulated_depreciation) }}
                </span>
              </div>
              <div class="conversion-item">
                <span class="conversion-label">Remaining Value</span>
                <span class="conversion-value">
                  {{ baseCurrency }} {{ formatCurrency(formData.base_currency_remaining_value) }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Journal Entry Section -->
        <div class="form-section">
          <div class="section-header">
            <h3>
              <i class="fas fa-book"></i>
              Journal Entry Options
            </h3>
            <p>Configure automatic journal entry creation</p>
          </div>

          <div class="form-grid">
            <div class="form-group">
              <div class="checkbox-wrapper">
                <input 
                  type="checkbox" 
                  v-model="formData.create_journal_entry"
                />
                <span>Create Journal Entry</span>
              </div>
              <small class="field-help">Automatically create journal entry for this depreciation</small>
            </div>

            <div class="form-group">
              <div class="checkbox-wrapper">
                <input 
                  type="checkbox" 
                  v-model="formData.post_journal_entry"
                  :disabled="!formData.create_journal_entry"
                />
                <span>Post Journal Entry</span>
              </div>
              <small class="field-help">Automatically post the journal entry after creation</small>
            </div>
          </div>

          <div v-if="formData.create_journal_entry" class="journal-preview">
            <h4>Journal Entry Preview</h4>
            <div class="journal-lines">
              <div class="journal-line debit">
                <div class="line-account">
                  <span class="account-name">Depreciation Expense</span>
                  <span class="account-code">{{ depreciationExpenseAccount?.account_code }}</span>
                </div>
                <div class="line-amount">
                  <span class="debit-amount">
                    Dr: {{ selectedAsset?.currency }} {{ formatCurrency(formData.depreciation_amount) }}
                  </span>
                </div>
              </div>
              <div class="journal-line credit">
                <div class="line-account">
                  <span class="account-name">Accumulated Depreciation</span>
                  <span class="account-code">{{ accumulatedDepreciationAccount?.account_code }}</span>
                </div>
                <div class="line-amount">
                  <span class="credit-amount">
                    Cr: {{ selectedAsset?.currency }} {{ formatCurrency(formData.depreciation_amount) }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Form Actions -->
        <div class="form-actions">
          <div class="actions-left">
            <button type="button" @click="resetForm" class="btn btn-outline">
              <i class="fas fa-undo"></i>
              Reset
            </button>
          </div>
          <div class="actions-right">
            <button type="button" @click="goBack" class="btn btn-secondary">
              Cancel
            </button>
            <button 
              type="submit" 
              class="btn btn-primary" 
              :disabled="!canSubmit || submitting"
            >
              <i v-if="submitting" class="fas fa-spinner fa-spin"></i>
              <i v-else :class="isEditMode ? 'fas fa-save' : 'fas fa-calculator'"></i>
              {{ submitting ? 'Processing...' : (isEditMode ? 'Update' : 'Calculate') }} Depreciation
            </button>
          </div>
        </div>
      </form>
    </div>

    <!-- Calculation Preview Modal -->
    <div v-if="showPreviewModal" class="modal-overlay" @click="closePreviewModal">
      <div class="modal-container" @click.stop>
        <div class="modal-header">
          <h3>
            <i class="fas fa-eye"></i>
            Depreciation Calculation Preview
          </h3>
          <button @click="closePreviewModal" class="modal-close">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <DepreciationCalculationPreview 
            :asset="selectedAsset"
            :period="selectedPeriod"
            :calculation-data="formData"
            :preview-data="calculationPreview"
          />
        </div>
        <div class="modal-footer">
          <button @click="closePreviewModal" class="btn btn-secondary">Close</button>
          <button @click="applyPreviewAndClose" class="btn btn-primary">
            Apply Values
          </button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import DepreciationCalculationPreview from '@/components/accounting/DepreciationCalculationPreview.vue'

export default {
  name: 'DepreciationForm',
  components: {
    DepreciationCalculationPreview
  },
  setup() {
    const route = useRoute()
    const router = useRouter()
    
    // Reactive state
    const loading = ref(false)
    const submitting = ref(false)
    const showPreviewModal = ref(false)
    const assets = ref([])
    const periods = ref([])
    const accounts = ref([])
    const calculationPreview = ref(null)
    const baseCurrency = ref('USD')
    
    // Form data
    const formData = ref({
      asset_id: '',
      period_id: '',
      depreciation_date: '',
      calculation_method: 'straight_line',
      depreciation_amount: 0,
      accumulated_depreciation: 0,
      remaining_value: 0,
      prorate_period: true,
      exchange_rate_date: new Date().toISOString().split('T')[0],
      exchange_rate: 1,
      base_currency_depreciation_amount: 0,
      base_currency_accumulated_depreciation: 0,
      base_currency_remaining_value: 0,
      create_journal_entry: true,
      post_journal_entry: false
    })
    
    // Computed properties
    const isEditMode = computed(() => !!route.params.id)
    
    const selectedAsset = computed(() => {
      return assets.value.find(asset => asset.asset_id === formData.value.asset_id)
    })
    
    const selectedPeriod = computed(() => {
      return periods.value.find(period => period.period_id === formData.value.period_id)
    })
    
    const depreciationExpenseAccount = computed(() => {
      return accounts.value.find(account => account.account_type === 'Expense' && account.name.includes('Depreciation'))
    })
    
    const accumulatedDepreciationAccount = computed(() => {
      return accounts.value.find(account => account.account_type === 'Asset' && account.name.includes('Accumulated'))
    })
    
    const canPreview = computed(() => {
      return formData.value.asset_id && formData.value.period_id && formData.value.depreciation_date
    })
    
    const canSubmit = computed(() => {
      return canPreview.value && 
             formData.value.depreciation_amount > 0 && 
             (!selectedAsset.value || selectedAsset.value.currency === baseCurrency.value || formData.value.exchange_rate > 0)
    })
    
    // Methods
    const loadAssets = async () => {
      try {
        const response = await axios.get('/accounting/fixed-assets', {
          params: { status: 'Active' }
        })
        assets.value = response.data.data
      } catch (error) {
        console.error('Error loading assets:', error)
      }
    }
    
    const loadPeriods = async () => {
      try {
        const response = await axios.get('/accounting/accounting-periods')
        periods.value = response.data.data
      } catch (error) {
        console.error('Error loading periods:', error)
      }
    }
    
    const loadAccounts = async () => {
      try {
        const response = await axios.get('/accounting/chart-of-accounts')
        accounts.value = response.data.data
      } catch (error) {
        console.error('Error loading accounts:', error)
      }
    }
    
    const loadDepreciationData = async () => {
      if (!isEditMode.value) return
      
      try {
        loading.value = true
        const response = await axios.get(`/accounting/asset-depreciations/${route.params.id}`)
        const depreciation = response.data.data
        
        // Populate form with existing data
        formData.value = {
          asset_id: depreciation.asset_id,
          period_id: depreciation.period_id,
          depreciation_date: depreciation.depreciation_date,
          calculation_method: depreciation.calculation_method || 'straight_line',
          depreciation_amount: depreciation.depreciation_amount,
          accumulated_depreciation: depreciation.accumulated_depreciation,
          remaining_value: depreciation.remaining_value,
          prorate_period: depreciation.prorate_period || false,
          exchange_rate_date: depreciation.exchange_rate_date || formData.value.exchange_rate_date,
          exchange_rate: depreciation.exchange_rate || 1,
          base_currency_depreciation_amount: depreciation.base_currency_depreciation_amount || 0,
          base_currency_accumulated_depreciation: depreciation.base_currency_accumulated_depreciation || 0,
          base_currency_remaining_value: depreciation.base_currency_remaining_value || 0,
          create_journal_entry: false,
          post_journal_entry: false
        }
        
      } catch (error) {
        console.error('Error loading depreciation data:', error)
      } finally {
        loading.value = false
      }
    }
    
    const onAssetChange = () => {
      if (selectedAsset.value) {
        // Set exchange rate date to today for new calculations
        formData.value.exchange_rate_date = new Date().toISOString().split('T')[0]
        
        // Load exchange rate if asset currency differs from base
        if (selectedAsset.value.currency !== baseCurrency.value) {
          loadExchangeRate()
        } else {
          formData.value.exchange_rate = 1
        }
        
        // Calculate depreciation if period is also selected
        if (formData.value.period_id) {
          calculateDepreciation()
        }
      }
    }
    
    const onPeriodChange = () => {
      if (selectedPeriod.value) {
        // Set depreciation date to period end date by default
        formData.value.depreciation_date = selectedPeriod.value.end_date
        
        // Calculate depreciation if asset is also selected
        if (formData.value.asset_id) {
          calculateDepreciation()
        }
      }
    }
    
    const loadExchangeRate = async () => {
      if (!selectedAsset.value || selectedAsset.value.currency === baseCurrency.value) {
        formData.value.exchange_rate = 1
        return
      }
      
      try {
        const response = await axios.get('/system/exchange-rates', {
          params: {
            from_currency: selectedAsset.value.currency,
            to_currency: baseCurrency.value,
            date: formData.value.exchange_rate_date
          }
        })
        
        if (response.data.rate) {
          formData.value.exchange_rate = response.data.rate
          calculateBaseCurrencyAmounts()
        }
      } catch (error) {
        console.error('Error loading exchange rate:', error)
        // Keep existing rate or default to 1
      }
    }
    
    const calculateDepreciation = async () => {
      if (!selectedAsset.value || !selectedPeriod.value || !formData.value.depreciation_date) {
        return
      }
      
      try {
        const response = await axios.post('/accounting/asset-depreciations/calculate-preview', {
          asset_id: formData.value.asset_id,
          period_id: formData.value.period_id,
          depreciation_date: formData.value.depreciation_date,
          calculation_method: formData.value.calculation_method,
          prorate_period: formData.value.prorate_period
        })
        
        const preview = response.data.data
        calculationPreview.value = preview
        
        // Update form with calculated values
        if (formData.value.calculation_method !== 'manual') {
          formData.value.depreciation_amount = preview.depreciation_amount
        }
        formData.value.accumulated_depreciation = preview.accumulated_depreciation
        formData.value.remaining_value = preview.remaining_value
        
        // Calculate base currency amounts
        calculateBaseCurrencyAmounts()
        
      } catch (error) {
        console.error('Error calculating depreciation:', error)
      }
    }
    
    const calculateBaseCurrencyAmounts = () => {
      if (!selectedAsset.value || selectedAsset.value.currency === baseCurrency.value) {
        formData.value.base_currency_depreciation_amount = formData.value.depreciation_amount
        formData.value.base_currency_accumulated_depreciation = formData.value.accumulated_depreciation
        formData.value.base_currency_remaining_value = formData.value.remaining_value
        return
      }
      
      const rate = formData.value.exchange_rate || 1
      formData.value.base_currency_depreciation_amount = formData.value.depreciation_amount * rate
      formData.value.base_currency_accumulated_depreciation = formData.value.accumulated_depreciation * rate
      formData.value.base_currency_remaining_value = formData.value.remaining_value * rate
    }
    
    const previewCalculation = () => {
      if (canPreview.value) {
        calculateDepreciation()
        showPreviewModal.value = true
      }
    }
    
    const closePreviewModal = () => {
      showPreviewModal.value = false
    }
    
    const applyPreviewAndClose = () => {
      if (calculationPreview.value) {
        formData.value.depreciation_amount = calculationPreview.value.depreciation_amount
        formData.value.accumulated_depreciation = calculationPreview.value.accumulated_depreciation
        formData.value.remaining_value = calculationPreview.value.remaining_value
        calculateBaseCurrencyAmounts()
      }
      closePreviewModal()
    }
    
    const resetForm = () => {
      formData.value = {
        asset_id: '',
        period_id: '',
        depreciation_date: '',
        calculation_method: 'straight_line',
        depreciation_amount: 0,
        accumulated_depreciation: 0,
        remaining_value: 0,
        prorate_period: true,
        exchange_rate_date: new Date().toISOString().split('T')[0],
        exchange_rate: 1,
        base_currency_depreciation_amount: 0,
        base_currency_accumulated_depreciation: 0,
        base_currency_remaining_value: 0,
        create_journal_entry: true,
        post_journal_entry: false
      }
      calculationPreview.value = null
    }
    
    const submitForm = async () => {
      if (!canSubmit.value) return
      
      try {
        submitting.value = true
        
        const url = isEditMode.value 
          ? `/accounting/asset-depreciations/${route.params.id}`
          : '/accounting/asset-depreciations'
        
        const method = isEditMode.value ? 'put' : 'post'
        
        const response = await axios[method](url, formData.value)
        
        // Show success message
        console.log('Depreciation saved successfully:', response.data)
        
        // Navigate back to list or detail
        if (isEditMode.value) {
          router.push(`/accounting/depreciations/${route.params.id}`)
        } else {
          router.push(`/accounting/depreciations/${response.data.data.depreciation_id}`)
        }
        
      } catch (error) {
        console.error('Error submitting form:', error)
        // Handle error display
      } finally {
        submitting.value = false
      }
    }
    
    const goBack = () => {
      if (isEditMode.value) {
        router.push(`/accounting/depreciations/${route.params.id}`)
      } else {
        router.push('/accounting/depreciations')
      }
    }
    
    const getAssetIcon = (category) => {
      const iconMap = {
        'Building': 'fas fa-building',
        'Equipment': 'fas fa-cogs',
        'Vehicle': 'fas fa-car',
        'Furniture': 'fas fa-couch',
        'Computer': 'fas fa-laptop',
        'Machinery': 'fas fa-industry',
        default: 'fas fa-cube'
      }
      return iconMap[category] || iconMap.default
    }
    
    const getPeriodDuration = (period) => {
      if (!period) return 0
      const start = new Date(period.start_date)
      const end = new Date(period.end_date)
      return Math.ceil((end - start) / (1000 * 60 * 60 * 24))
    }
    
    const formatCurrency = (value) => {
      return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      }).format(value || 0)
    }
    
    const formatDate = (date) => {
      if (!date) return '-'
      return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      })
    }
    
    // Watchers
    watch(() => formData.value.depreciation_amount, () => {
      calculateBaseCurrencyAmounts()
    })
    
    watch(() => formData.value.exchange_rate, () => {
      calculateBaseCurrencyAmounts()
    })
    
    // Lifecycle
    onMounted(async () => {
      await Promise.all([
        loadAssets(),
        loadPeriods(),
        loadAccounts()
      ])
      
      if (isEditMode.value) {
        await loadDepreciationData()
      }
    })
    
    return {
      loading,
      submitting,
      showPreviewModal,
      assets,
      periods,
      accounts,
      calculationPreview,
      baseCurrency,
      formData,
      isEditMode,
      selectedAsset,
      selectedPeriod,
      depreciationExpenseAccount,
      accumulatedDepreciationAccount,
      canPreview,
      canSubmit,
      onAssetChange,
      onPeriodChange,
      loadExchangeRate,
      calculateDepreciation,
      calculateBaseCurrencyAmounts,
      previewCalculation,
      closePreviewModal,
      applyPreviewAndClose,
      resetForm,
      submitForm,
      goBack,
      getAssetIcon,
      getPeriodDuration,
      formatCurrency,
      formatDate
    }
  }
}
</script>

<style scoped>
/* Base styles */
.depreciation-form-page {
  padding: 2rem;
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
  min-height: 100vh;
}

/* Header styles */
.page-header {
  margin-bottom: 2rem;
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: white;
  padding: 2rem;
  border-radius: 20px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
  border: 1px solid #e2e8f0;
}

.title-section h1 {
  font-size: 2.5rem;
  font-weight: 700;
  background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
  background-clip: text;
  -webkit-text-fill-color: transparent;
  margin-bottom: 0.5rem;
}

.page-subtitle {
  color: #64748b;
  font-size: 1.1rem;
}

.breadcrumb {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 1rem;
  font-size: 0.9rem;
}

.breadcrumb-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #64748b;
  text-decoration: none;
  transition: color 0.3s ease;
}

.breadcrumb-item:hover {
  color: #6366f1;
}

.breadcrumb-current {
  color: #1f2937;
  font-weight: 600;
}

.header-actions {
  display: flex;
  gap: 1rem;
  align-items: center;
}

/* Form content */
.form-content {
  max-width: 1200px;
  margin: 0 auto;
}

.depreciation-form {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

/* Form sections */
.form-section {
  background: white;
  border-radius: 16px;
  padding: 2rem;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
  border: 1px solid #e2e8f0;
}

.section-header {
  margin-bottom: 2rem;
  padding-bottom: 1rem;
  border-bottom: 2px solid #f1f5f9;
}

.section-header h3 {
  font-size: 1.25rem;
  font-weight: 600;
  color: #1f2937;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.5rem;
}

.section-header p {
  color: #64748b;
  font-size: 0.9rem;
}

/* Form grid and groups */
.form-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-group label {
  font-size: 0.9rem;
  font-weight: 600;
  color: #374151;
}

.form-group select,
.form-group input {
  padding: 0.75rem 1rem;
  border: 2px solid #e5e7eb;
  border-radius: 10px;
  font-size: 0.9rem;
  transition: border-color 0.3s ease;
}

.form-group select:focus,
.form-group input:focus {
  outline: none;
  border-color: #6366f1;
}

.form-group select:disabled,
.form-group input:disabled,
.form-group input:read-only {
  background: #f9fafb;
  color: #9ca3af;
  cursor: not-allowed;
}

.asset-info,
.period-info,
.field-help {
  font-size: 0.8rem;
  color: #6b7280;
  margin-top: 0.25rem;
}

/* Amount input */
.amount-input {
  position: relative;
  display: flex;
  align-items: center;
}

.currency-prefix {
  position: absolute;
  left: 1rem;
  color: #6b7280;
  font-weight: 600;
  font-size: 0.9rem;
  z-index: 1;
}

.amount-input input {
  padding-left: 3rem;
}

/* Checkbox wrapper */
.checkbox-wrapper {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem;
  border: 2px solid #e5e7eb;
  border-radius: 10px;
  cursor: pointer;
  transition: border-color 0.3s ease, background-color 0.3s ease;
}

.checkbox-wrapper:hover {
  border-color: #6366f1;
  background: rgba(99, 102, 241, 0.05);
}

.checkbox-wrapper input[type="checkbox"] {
  width: 18px;
  height: 18px;
  accent-color: #6366f1;
}

.checkbox-wrapper span {
  font-weight: 500;
  color: #374151;
}

/* Asset details card */
.asset-details-card {
  background: #f8fafc;
  border-radius: 12px;
  padding: 1.5rem;
  border: 1px solid #e2e8f0;
  margin-top: 1rem;
}

.asset-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.asset-icon {
  width: 50px;
  height: 50px;
  background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.2rem;
}

.asset-info h4 {
  font-size: 1.1rem;
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 0.5rem;
}

.asset-meta {
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.asset-code {
  background: #e0e7ff;
  color: #5b21b6;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 500;
}

.asset-category {
  background: #fef3c7;
  color: #92400e;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 500;
}

.asset-status {
  background: #d1fae5;
  color: #065f46;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 500;
}

.asset-metrics {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}

.metric-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem;
  background: white;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
}

.metric-label {
  font-size: 0.8rem;
  color: #6b7280;
  font-weight: 500;
}

.metric-value {
  font-size: 0.9rem;
  color: #1f2937;
  font-weight: 600;
}

/* Calculation preview */
.calculation-preview {
  background: #f0f9ff;
  border-radius: 12px;
  padding: 1.5rem;
  border: 1px solid #0ea5e9;
  margin-top: 1rem;
}

.calculation-preview h4 {
  font-size: 1.1rem;
  font-weight: 600;
  color: #0c4a6e;
  margin-bottom: 1rem;
}

.preview-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}

.preview-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem;
  background: white;
  border-radius: 8px;
  border: 1px solid #e0f2fe;
}

.preview-item.primary {
  background: #fef2f2;
  border-color: #fecaca;
}

.preview-label {
  font-size: 0.8rem;
  color: #6b7280;
  font-weight: 500;
}

.preview-value {
  font-size: 0.9rem;
  color: #1f2937;
  font-weight: 600;
}

.preview-item.primary .preview-value {
  color: #dc2626;
}

.preview-value.remaining {
  color: #059669;
}

/* Currency conversion */
.currency-conversion-preview {
  background: #fef3c7;
  border-radius: 12px;
  padding: 1.5rem;
  border: 1px solid #fbbf24;
  margin-top: 1rem;
}

.currency-conversion-preview h4 {
  font-size: 1.1rem;
  font-weight: 600;
  color: #92400e;
  margin-bottom: 1rem;
}

.conversion-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}

.conversion-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem;
  background: white;
  border-radius: 8px;
  border: 1px solid #fde68a;
}

.conversion-label {
  font-size: 0.8rem;
  color: #6b7280;
  font-weight: 500;
}

.conversion-value {
  font-size: 0.9rem;
  color: #1f2937;
  font-weight: 600;
}

/* Journal preview */
.journal-preview {
  background: #f0fdf4;
  border-radius: 12px;
  padding: 1.5rem;
  border: 1px solid #10b981;
  margin-top: 1rem;
}

.journal-preview h4 {
  font-size: 1.1rem;
  font-weight: 600;
  color: #064e3b;
  margin-bottom: 1rem;
}

.journal-lines {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.journal-line {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem;
  background: white;
  border-radius: 8px;
  border: 1px solid #d1fae5;
}

.journal-line.debit {
  border-left: 4px solid #dc2626;
}

.journal-line.credit {
  border-left: 4px solid #059669;
}

.line-account {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.account-name {
  font-weight: 600;
  color: #1f2937;
}

.account-code {
  font-size: 0.8rem;
  color: #6b7280;
}

.line-amount {
  text-align: right;
}

.debit-amount {
  color: #dc2626;
  font-weight: 600;
}

.credit-amount {
  color: #059669;
  font-weight: 600;
}

/* Form actions */
.form-actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 2rem;
  background: white;
  border-radius: 16px;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
  border: 1px solid #e2e8f0;
}

.actions-left,
.actions-right {
  display: flex;
  gap: 1rem;
}

/* Button styles */
.btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  border-radius: 10px;
  font-size: 0.9rem;
  font-weight: 500;
  text-decoration: none;
  transition: all 0.3s ease;
  cursor: pointer;
  border: none;
}

.btn-primary {
  background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: linear-gradient(135deg, #5b21b6 0%, #7c3aed 100%);
  transform: translateY(-2px);
  box-shadow: 0 10px 25px rgba(99, 102, 241, 0.3);
}

.btn-secondary {
  background: #f3f4f6;
  color: #374151;
  border: 2px solid #e5e7eb;
}

.btn-secondary:hover:not(:disabled) {
  background: #e5e7eb;
  border-color: #d1d5db;
}

.btn-outline {
  background: transparent;
  color: #6366f1;
  border: 2px solid #6366f1;
}

.btn-outline:hover:not(:disabled) {
  background: #6366f1;
  color: white;
}

.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  transform: none !important;
  box-shadow: none !important;
}

/* Modal styles */
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
  z-index: 1000;
  padding: 2rem;
}

.modal-container {
  background: white;
  border-radius: 20px;
  max-width: 800px;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem 1.5rem 0 1.5rem;
  margin-bottom: 1rem;
}

.modal-header h3 {
  font-size: 1.25rem;
  font-weight: 600;
  color: #1f2937;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.modal-close {
  width: 32px;
  height: 32px;
  border: none;
  background: #f3f4f6;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: #6b7280;
  transition: all 0.3s ease;
}

.modal-close:hover {
  background: #e5e7eb;
  color: #374151;
}

.modal-body {
  padding: 0 1.5rem 1.5rem 1.5rem;
}

.modal-footer {
  display: flex;
  gap: 1rem;
  padding: 0 1.5rem 1.5rem 1.5rem;
}

/* Responsive design */
@media (max-width: 768px) {
  .depreciation-form-page {
    padding: 1rem;
  }

  .header-content {
    flex-direction: column;
    gap: 1rem;
    text-align: center;
  }

  .header-actions {
    flex-direction: column;
    width: 100%;
  }

  .form-grid {
    grid-template-columns: 1fr;
  }

  .asset-header {
    flex-direction: column;
    text-align: center;
    gap: 1rem;
  }

  .asset-meta {
    justify-content: center;
  }

  .asset-metrics {
    grid-template-columns: 1fr;
  }

  .preview-grid,
  .conversion-grid {
    grid-template-columns: 1fr;
  }

  .form-actions {
    flex-direction: column;
    gap: 1rem;
  }

  .actions-left,
  .actions-right {
    width: 100%;
    justify-content: center;
  }

  .modal-overlay {
    padding: 1rem;
  }

  .modal-footer {
    flex-direction: column;
  }

  .journal-line {
    flex-direction: column;
    gap: 0.5rem;
    align-items: flex-start;
  }
}
</style>