<template>
    <div class="filing-preparation-container">
      <!-- Header Section -->
      <div class="page-header">
        <div class="header-content">
          <div class="breadcrumb">
            <router-link to="/tax-transactions" class="breadcrumb-link">
              <i class="fas fa-receipt"></i>
              Tax Transactions
            </router-link>
            <i class="fas fa-chevron-right breadcrumb-separator"></i>
            <span class="breadcrumb-current">Tax Filing Preparation</span>
          </div>
          
          <div class="title-section">
            <h1 class="page-title">
              <i class="fas fa-file-invoice"></i>
              Tax Filing Preparation
            </h1>
            <p class="page-description">Prepare and generate tax forms for filing</p>
          </div>

          <div class="header-actions">
            <button @click="goBack" class="btn btn-outline">
              <i class="fas fa-arrow-left"></i>
              Back
            </button>
            <button @click="saveProgress" class="btn btn-outline" :disabled="saving">
              <i class="fas fa-save"></i>
              Save Progress
            </button>
          </div>
        </div>
      </div>

      <!-- Progress Indicator -->
      <div class="progress-section">
        <div class="progress-header">
          <h2>Filing Progress</h2>
          <div class="progress-bar">
            <div class="progress-fill" :style="{ width: overallProgress + '%' }"></div>
          </div>
          <span class="progress-text">{{ overallProgress }}% Complete</span>
        </div>
        
        <div class="progress-steps">
          <div 
            v-for="(step, index) in filingSteps" 
            :key="index"
            class="step"
            :class="{ 
              active: currentStep === index, 
              completed: step.completed,
              error: step.hasError 
            }"
          >
            <div class="step-number">
              <i v-if="step.completed && !step.hasError" class="fas fa-check"></i>
              <i v-else-if="step.hasError" class="fas fa-exclamation"></i>
              <span v-else>{{ index + 1 }}</span>
            </div>
            <div class="step-content">
              <h3>{{ step.title }}</h3>
              <p>{{ step.description }}</p>
              <div v-if="step.hasError" class="step-error">
                {{ step.errorMessage }}
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Content -->
      <div class="main-content">
        <!-- Step 0: Filing Period Selection -->
        <div v-if="currentStep === 0" class="filing-step">
          <div class="step-card">
            <div class="step-header">
              <h2>
                <i class="fas fa-calendar-alt"></i>
                Select Filing Period
              </h2>
              <p>Choose the tax year and period for filing</p>
            </div>

            <div class="period-selection">
              <div class="form-grid">
                <div class="form-group">
                  <label class="form-label">Tax Year</label>
                  <select v-model="filingData.taxYear" class="form-select" @change="updatePeriod">
                    <option v-for="year in taxYearOptions" :key="year" :value="year">
                      {{ year }}
                    </option>
                  </select>
                </div>

                <div class="form-group">
                  <label class="form-label">Filing Period</label>
                  <select v-model="filingData.period" class="form-select" @change="updatePeriod">
                    <option value="annual">Annual</option>
                    <option value="quarterly">Quarterly</option>
                    <option value="monthly">Monthly</option>
                  </select>
                </div>

                <div v-if="filingData.period !== 'annual'" class="form-group">
                  <label class="form-label">{{ filingData.period === 'quarterly' ? 'Quarter' : 'Month' }}</label>
                  <select v-model="filingData.subPeriod" class="form-select" @change="updatePeriod">
                    <option v-for="option in subPeriodOptions" :key="option.value" :value="option.value">
                      {{ option.label }}
                    </option>
                  </select>
                </div>
              </div>

              <!-- Period Summary -->
              <div class="period-summary">
                <h3>Filing Period Summary</h3>
                <div class="summary-grid">
                  <div class="summary-item">
                    <span class="summary-label">Tax Year:</span>
                    <span class="summary-value">{{ filingData.taxYear }}</span>
                  </div>
                  <div class="summary-item">
                    <span class="summary-label">Period:</span>
                    <span class="summary-value">{{ getFilingPeriodLabel() }}</span>
                  </div>
                  <div class="summary-item">
                    <span class="summary-label">Due Date:</span>
                    <span class="summary-value">{{ formatDate(filingDueDate) }}</span>
                  </div>
                  <div class="summary-item">
                    <span class="summary-label">Days Remaining:</span>
                    <span class="summary-value" :class="getDaysRemainingClass()">
                      {{ getDaysRemaining() }} days
                    </span>
                  </div>
                </div>
              </div>

              <!-- Tax Data Preview -->
              <div class="data-preview">
                <h3>Tax Data Preview</h3>
                <div class="preview-grid">
                  <div class="preview-card">
                    <div class="preview-icon">
                      <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="preview-content">
                      <h4>${{ formatCurrency(taxSummary.total_tax_amount || 0) }}</h4>
                      <p>Total Tax Amount</p>
                    </div>
                  </div>
                  <div class="preview-card">
                    <div class="preview-icon">
                      <i class="fas fa-file-invoice"></i>
                    </div>
                    <div class="preview-content">
                      <h4>{{ taxSummary.transaction_count || 0 }}</h4>
                      <p>Transactions</p>
                    </div>
                  </div>
                  <div class="preview-card">
                    <div class="preview-icon">
                      <i class="fas fa-tags"></i>
                    </div>
                    <div class="preview-content">
                      <h4>{{ taxSummary.tax_types_count || 0 }}</h4>
                      <p>Tax Types</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Step 1: Data Validation -->
        <div v-if="currentStep === 1" class="filing-step">
          <div class="step-card">
            <div class="step-header">
              <h2>
                <i class="fas fa-check-circle"></i>
                Data Validation
              </h2>
              <p>Validate tax transaction data for completeness and accuracy</p>
            </div>

            <div class="validation-section">
              <!-- Validation Results -->
              <div class="validation-results">
                <div class="validation-overview">
                  <div class="validation-card success">
                    <div class="validation-icon">
                      <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="validation-content">
                      <h3>{{ validationResults.passed || 0 }}</h3>
                      <p>Validations Passed</p>
                    </div>
                  </div>
                  
                  <div class="validation-card warning">
                    <div class="validation-icon">
                      <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="validation-content">
                      <h3>{{ validationResults.warnings || 0 }}</h3>
                      <p>Warnings</p>
                    </div>
                  </div>
                  
                  <div class="validation-card error">
                    <div class="validation-icon">
                      <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="validation-content">
                      <h3>{{ validationResults.errors || 0 }}</h3>
                      <p>Errors</p>
                    </div>
                  </div>
                </div>

                <!-- Validation Details -->
                <div class="validation-details">
                  <h3>Validation Details</h3>
                  <div class="validation-list">
                    <div 
                      v-for="validation in validationChecks" 
                      :key="validation.id"
                      class="validation-item"
                      :class="validation.status"
                    >
                      <div class="validation-header">
                        <div class="validation-title">
                          <i :class="getValidationIcon(validation.status)"></i>
                          {{ validation.title }}
                        </div>
                        <div class="validation-status">
                          {{ validation.status.toUpperCase() }}
                        </div>
                      </div>
                      <div class="validation-content">
                        <p>{{ validation.description }}</p>
                        <button 
                          v-if="validation.hasDetails" 
                          @click="toggleDetails(validation)"
                          class="btn-link"
                        >
                          <i :class="validation.showDetails ? 'fas fa-chevron-up' : 'fas fa-chevron-down'"></i>
                          {{ validation.showDetails ? 'Hide' : 'Show' }} Details
                        </button>
                        <div v-if="validation.showDetails" class="details-content">
                          <ul>
                            <li v-for="detail in validation.details" :key="detail">{{ detail }}</li>
                          </ul>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Auto-fix Options -->
              <div class="auto-fix-section">
                <h3>Auto-fix Available Issues</h3>
                <div class="fix-options">
                  <button @click="fixMissingTaxCodes" class="btn btn-outline">
                    <i class="fas fa-magic"></i>
                    Fix Missing Tax Codes
                  </button>
                  <button @click="validateExchangeRates" class="btn btn-outline">
                    <i class="fas fa-exchange-alt"></i>
                    Update Exchange Rates
                  </button>
                  <button @click="checkSupplierInfo" class="btn btn-outline">
                    <i class="fas fa-building"></i>
                    Validate Supplier Info
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Step 2: Form Generation -->
        <div v-if="currentStep === 2" class="filing-step">
          <div class="step-card">
            <div class="step-header">
              <h2>
                <i class="fas fa-file-alt"></i>
                Tax Form Generation
              </h2>
              <p>Generate required tax forms based on your transaction data</p>
            </div>

            <div class="form-generation-section">
              <!-- Form Selection -->
              <div class="form-selection">
                <h3>Required Forms</h3>
                <div class="forms-grid">
                  <div 
                    v-for="form in requiredForms" 
                    :key="form.id"
                    class="form-card"
                    :class="{ selected: form.selected, generated: form.generated }"
                    @click="toggleFormSelection(form)"
                  >
                    <div class="form-header">
                      <div class="form-checkbox">
                        <input 
                          type="checkbox" 
                          v-model="form.selected" 
                          :id="`form-${form.id}`"
                          @change="updateFormSelection"
                        >
                        <label :for="`form-${form.id}`"></label>
                      </div>
                      <div class="form-status">
                        <i v-if="form.generated" class="fas fa-check-circle text-success"></i>
                        <i v-else-if="form.generating" class="fas fa-spinner fa-spin text-info"></i>
                        <i v-else class="fas fa-file-alt text-muted"></i>
                      </div>
                    </div>
                    
                    <div class="form-content">
                      <h4>{{ form.name }}</h4>
                      <p>{{ form.description }}</p>
                      <div class="form-details">
                        <span class="form-type">{{ form.type }}</span>
                        <span class="form-due-date">Due: {{ formatDate(form.dueDate) }}</span>
                      </div>
                      
                      <div v-if="form.generated" class="form-actions">
                        <button @click.stop="previewForm(form)" class="btn btn-outline btn-sm">
                          <i class="fas fa-eye"></i>
                          Preview
                        </button>
                        <button @click.stop="downloadForm(form)" class="btn btn-outline btn-sm">
                          <i class="fas fa-download"></i>
                          Download
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Generation Controls -->
              <div class="generation-controls">
                <div class="control-buttons">
                  <button 
                    @click="generateSelectedForms" 
                    class="btn btn-primary"
                    :disabled="!hasSelectedForms || generatingForms"
                  >
                    <i v-if="generatingForms" class="fas fa-spinner fa-spin"></i>
                    <i v-else class="fas fa-cogs"></i>
                    Generate Selected Forms
                  </button>
                  
                  <button @click="generateAllForms" class="btn btn-outline">
                    <i class="fas fa-list"></i>
                    Generate All Forms
                  </button>
                </div>
                
                <div class="generation-options">
                  <label class="checkbox-label">
                    <input type="checkbox" v-model="filingOptions.includeSupporting">
                    Include supporting documents
                  </label>
                  <label class="checkbox-label">
                    <input type="checkbox" v-model="filingOptions.electronicSignature">
                    Apply electronic signature
                  </label>
                  <label class="checkbox-label">
                    <input type="checkbox" v-model="filingOptions.autoSubmit">
                    Auto-submit after generation
                  </label>
                </div>
              </div>

              <!-- Generation Progress -->
              <div v-if="generatingForms" class="generation-progress">
                <div class="progress-header">
                  <h4>Generating Forms...</h4>
                  <span class="progress-percentage">{{ generationProgress }}%</span>
                </div>
                <div class="progress-bar">
                  <div class="progress-fill" :style="{ width: generationProgress + '%' }"></div>
                </div>
                <p class="progress-status">{{ currentGenerationTask }}</p>
              </div>

              <!-- Generated Forms List -->
              <div v-if="generatedForms.length > 0" class="generated-forms">
                <h3>Generated Forms</h3>
                <div class="forms-list">
                  <div v-for="form in generatedForms" :key="form.id" class="generated-form-item">
                    <div class="form-info">
                      <div class="form-icon">
                        <i class="fas fa-file-pdf"></i>
                      </div>
                      <div class="form-details">
                        <h4>{{ form.name }}</h4>
                        <p>{{ form.type }} • Generated {{ formatDateTime(form.generatedAt) }}</p>
                      </div>
                    </div>
                    <div class="form-actions">
                      <button @click="previewForm(form)" class="btn-action">
                        <i class="fas fa-eye"></i>
                      </button>
                      <button @click="downloadForm(form)" class="btn-action">
                        <i class="fas fa-download"></i>
                      </button>
                      <button @click="shareForm(form)" class="btn-action">
                        <i class="fas fa-share"></i>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Step 3: Review & Submit -->
        <div v-if="currentStep === 3" class="filing-step">
          <div class="step-card">
            <div class="step-header">
              <h2>
                <i class="fas fa-clipboard-check"></i>
                Review & Submit
              </h2>
              <p>Final review before submitting your tax filing</p>
            </div>

            <div class="review-section">
              <!-- Filing Summary -->
              <div class="filing-summary">
                <h3>Filing Summary</h3>
                <div class="summary-cards">
                  <div class="summary-card">
                    <div class="summary-header">
                      <h4>Tax Information</h4>
                      <i class="fas fa-receipt"></i>
                    </div>
                    <div class="summary-content">
                      <div class="summary-row">
                        <span>Total Tax Amount:</span>
                        <span class="amount">${{ formatCurrency(totalTaxAmount) }}</span>
                      </div>
                      <div class="summary-row">
                        <span>Total Transactions:</span>
                        <span>{{ taxSummary.transaction_count || 0 }}</span>
                      </div>
                      <div class="summary-row">
                        <span>Filing Period:</span>
                        <span>{{ getFilingPeriodLabel() }}</span>
                      </div>
                    </div>
                  </div>

                  <div class="summary-card">
                    <div class="summary-header">
                      <h4>Forms Generated</h4>
                      <i class="fas fa-file-alt"></i>
                    </div>
                    <div class="summary-content">
                      <div class="summary-row">
                        <span>Total Forms:</span>
                        <span>{{ generatedForms.length }}</span>
                      </div>
                      <div class="summary-row">
                        <span>Attachments:</span>
                        <span>{{ attachmentCount }}</span>
                      </div>
                      <div class="summary-row">
                        <span>File Size:</span>
                        <span>{{ getTotalFileSize() }}</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Submission Checklist -->
              <div class="submission-checklist">
                <h3>Pre-submission Checklist</h3>
                <div class="checklist-items">
                  <div 
                    v-for="item in submissionChecklist" 
                    :key="item.id"
                    class="checklist-item"
                    :class="{ completed: item.completed }"
                  >
                    <div class="checklist-checkbox">
                      <input 
                        type="checkbox" 
                        v-model="item.completed" 
                        :id="`check-${item.id}`"
                      >
                      <label :for="`check-${item.id}`"></label>
                    </div>
                    <div class="checklist-content">
                      <h4>{{ item.title }}</h4>
                      <p>{{ item.description }}</p>
                    </div>
                    <div class="checklist-status">
                      <i v-if="item.completed" class="fas fa-check-circle text-success"></i>
                      <i v-else class="fas fa-circle text-muted"></i>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Submission Details -->
              <div class="submission-details">
                <h3>
                  <i class="fas fa-info-circle"></i>
                  Submission Details
                </h3>
                <div class="details-grid">
                  <div class="detail-card">
                    <h4>Filing Information</h4>
                    <div class="detail-list">
                      <div class="detail-row">
                        <span class="detail-label">Tax Year:</span>
                        <span class="detail-value">{{ filingData.taxYear }}</span>
                      </div>
                      <div class="detail-row">
                        <span class="detail-label">Filing Period:</span>
                        <span class="detail-value">{{ getFilingPeriodLabel() }}</span>
                      </div>
                      <div class="detail-row">
                        <span class="detail-label">Due Date:</span>
                        <span class="detail-value">{{ formatDate(filingDueDate) }}</span>
                      </div>
                      <div class="detail-row">
                        <span class="detail-label">Submission Method:</span>
                        <span class="detail-value">Electronic Filing</span>
                      </div>
                    </div>
                  </div>
                  
                  <div class="detail-card">
                    <h4>Tax Summary</h4>
                    <div class="detail-list">
                      <div class="detail-row">
                        <span class="detail-label">Total Tax Amount:</span>
                        <span class="detail-value amount">${{ formatCurrency(totalTaxAmount) }}</span>
                      </div>
                      <div class="detail-row">
                        <span class="detail-label">Forms Generated:</span>
                        <span class="detail-value">{{ generatedForms.length }}</span>
                      </div>
                      <div class="detail-row">
                        <span class="detail-label">Attachments:</span>
                        <span class="detail-value">{{ attachmentCount }}</span>
                      </div>
                      <div class="detail-row">
                        <span class="detail-label">Submission Status:</span>
                        <span class="detail-value">Ready for Filing</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Final Actions -->
              <div class="final-actions">
                <div class="action-buttons">
                  <button @click="downloadFilingPackage" class="btn btn-outline">
                    <i class="fas fa-download"></i>
                    Download Filing Package
                  </button>
                  <button 
                    @click="submitFiling" 
                    class="btn btn-primary"
                    :disabled="!canSubmitFinal || submitting"
                  >
                    <i v-if="submitting" class="fas fa-spinner fa-spin"></i>
                    <i v-else class="fas fa-paper-plane"></i>
                    {{ submitting ? 'Submitting...' : 'Submit Tax Filing' }}
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Navigation -->
        <div class="step-navigation">
          <div class="nav-left">
            <button 
              v-if="currentStep > 0" 
              @click="previousStep" 
              class="btn btn-outline"
            >
              <i class="fas fa-arrow-left"></i>
              Previous
            </button>
          </div>
          
          <div class="nav-right">
            <button 
              v-if="currentStep < filingSteps.length - 1" 
              @click="nextStep" 
              class="btn btn-primary"
              :disabled="!canProceedToNext"
            >
              Next
              <i class="fas fa-arrow-right"></i>
            </button>
          </div>
        </div>
      </div>
    </div>
</template>

<script>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

export default {
  name: 'TaxFilingPreparation',
  components: {
  },
  setup() {
    const router = useRouter()
    
    const currentStep = ref(0)
    const saving = ref(false)
    const submitting = ref(false)
    const generatingForms = ref(false)
    const generationProgress = ref(0)
    const currentGenerationTask = ref('')
    
    const filingData = reactive({
      taxYear: new Date().getFullYear() - 1,
      period: 'annual',
      subPeriod: ''
    })
    
    const filingOptions = reactive({
      includeSupporting: true,
      electronicSignature: false,
      autoSubmit: false
    })
    
    const taxSummary = reactive({
      total_tax_amount: 0,
      transaction_count: 0,
      tax_types_count: 0
    })
    
    const validationResults = reactive({
      passed: 0,
      warnings: 0,
      errors: 0
    })
    
    const filingSteps = ref([
      {
        title: 'Period Selection',
        description: 'Choose tax year and filing period',
        completed: false,
        hasError: false,
        errorMessage: ''
      },
      {
        title: 'Data Validation',
        description: 'Validate transaction data',
        completed: false,
        hasError: false,
        errorMessage: ''
      },
      {
        title: 'Form Generation',
        description: 'Generate required tax forms',
        completed: false,
        hasError: false,
        errorMessage: ''
      },
      {
        title: 'Review & Submit',
        description: 'Final review and submission',
        completed: false,
        hasError: false,
        errorMessage: ''
      }
    ])
    
    const validationChecks = ref([
      {
        id: 'completeness',
        title: 'Data Completeness',
        description: 'All required transaction fields are populated',
        status: 'passed',
        hasDetails: true,
        showDetails: false,
        details: ['All transactions have tax codes', 'All amounts are properly recorded', 'All dates are valid']
      },
      {
        id: 'accuracy',
        title: 'Data Accuracy',
        description: 'Tax calculations and rates are correct',
        status: 'warning',
        hasDetails: true,
        showDetails: false,
        details: ['2 transactions have unusual tax rates', 'Exchange rates need verification']
      },
      {
        id: 'consistency',
        title: 'Data Consistency',
        description: 'Transaction data is consistent across records',
        status: 'passed',
        hasDetails: false,
        details: []
      }
    ])
    
    const requiredForms = ref([
      {
        id: 'form-1',
        name: 'VAT Return Form',
        description: 'Value Added Tax return for the filing period',
        type: 'VAT',
        dueDate: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000),
        selected: true,
        generated: false,
        generating: false
      },
      {
        id: 'form-2',
        name: 'Income Tax Form',
        description: 'Corporate income tax declaration',
        type: 'Income Tax',
        dueDate: new Date(Date.now() + 45 * 24 * 60 * 60 * 1000),
        selected: true,
        generated: false,
        generating: false
      },
      {
        id: 'form-3',
        name: 'Withholding Tax Report',
        description: 'Withholding tax summary report',
        type: 'Withholding Tax',
        dueDate: new Date(Date.now() + 60 * 24 * 60 * 60 * 1000),
        selected: false,
        generated: false,
        generating: false
      }
    ])
    
    const generatedForms = ref([])
    
    const submissionChecklist = ref([
      {
        id: 'data-reviewed',
        title: 'Data Reviewed',
        description: 'All transaction data has been reviewed for accuracy',
        completed: false
      },
      {
        id: 'forms-generated',
        title: 'Forms Generated',
        description: 'All required tax forms have been generated',
        completed: false
      },
      {
        id: 'supporting-docs',
        title: 'Supporting Documents',
        description: 'All supporting documents are attached',
        completed: false
      },
      {
        id: 'authorization',
        title: 'Authorization',
        description: 'Authorized to submit on behalf of the organization',
        completed: false
      }
    ])

    // Computed properties
    const taxYearOptions = computed(() => {
      const currentYear = new Date().getFullYear()
      return Array.from({ length: 5 }, (_, i) => currentYear - i)
    })

    const subPeriodOptions = computed(() => {
      if (filingData.period === 'quarterly') {
        return [
          { value: 'q1', label: 'Q1 (Jan-Mar)' },
          { value: 'q2', label: 'Q2 (Apr-Jun)' },
          { value: 'q3', label: 'Q3 (Jul-Sep)' },
          { value: 'q4', label: 'Q4 (Oct-Dec)' }
        ]
      } else if (filingData.period === 'monthly') {
        return [
          { value: '01', label: 'January' },
          { value: '02', label: 'February' },
          { value: '03', label: 'March' },
          { value: '04', label: 'April' },
          { value: '05', label: 'May' },
          { value: '06', label: 'June' },
          { value: '07', label: 'July' },
          { value: '08', label: 'August' },
          { value: '09', label: 'September' },
          { value: '10', label: 'October' },
          { value: '11', label: 'November' },
          { value: '12', label: 'December' }
        ]
      }
      return []
    })

    const overallProgress = computed(() => {
      const completedSteps = filingSteps.value.filter(step => step.completed).length
      return Math.round((completedSteps / filingSteps.value.length) * 100)
    })

    const hasSelectedForms = computed(() => {
      return requiredForms.value.some(form => form.selected)
    })

    const canProceedToNext = computed(() => {
      return filingSteps.value[currentStep.value].completed
    })

    const totalTaxAmount = computed(() => {
      return taxSummary.total_tax_amount || 0
    })

    const attachmentCount = computed(() => {
      return generatedForms.value.length + (filingOptions.includeSupporting ? 5 : 0)
    })

    const filingDueDate = computed(() => {
      // Calculate due date based on period
      const year = filingData.taxYear
      if (filingData.period === 'annual') {
        return new Date(year + 1, 3, 15) // April 15th
      } else if (filingData.period === 'quarterly') {
        const quarter = parseInt(filingData.subPeriod.replace('q', ''))
        return new Date(year, quarter * 3, 15)
      } else {
        const month = parseInt(filingData.subPeriod)
        return new Date(year, month, 15)
      }
    })

    const canSubmitFinal = computed(() => {
      return submissionChecklist.value.every(item => item.completed)
    })

    // Methods
    const updatePeriod = async () => {
      await fetchTaxSummary()
      validateData()
    }

    const fetchTaxSummary = async () => {
      try {
        const params = {
          tax_year: filingData.taxYear,
          period: filingData.period,
          sub_period: filingData.subPeriod
        }
        
        const response = await axios.get('/api/accounting/tax-transactions/filing-summary', { params })
        Object.assign(taxSummary, response.data)
      } catch (error) {
        console.error('Error fetching tax summary:', error)
        showNotification('Error loading tax summary', 'error')
      }
    }

    const validateData = () => {
      // Simulate data validation
      const hasData = totalTaxAmount.value > 0
      
      filingSteps.value[0].completed = hasData
      if (!hasData) {
        filingSteps.value[0].hasError = true
        filingSteps.value[0].errorMessage = 'No tax data found for selected period'
      } else {
        filingSteps.value[0].hasError = false
        filingSteps.value[0].errorMessage = ''
      }
    }

    const toggleDetails = (validation) => {
      validation.showDetails = !validation.showDetails
    }

    const updateFormSelection = () => {
      // Update form generation readiness
      filingSteps.value[2].completed = hasSelectedForms.value
    }

    const toggleFormSelection = (form) => {
      form.selected = !form.selected
      updateFormSelection()
    }

    const generateSelectedForms = async () => {
      const selectedForms = requiredForms.value.filter(form => form.selected)
      await generateForms(selectedForms)
    }

    const generateAllForms = async () => {
      requiredForms.value.forEach(form => form.selected = true)
      await generateForms(requiredForms.value)
    }

    const generateForms = async (forms) => {
      generatingForms.value = true
      generationProgress.value = 0
      
      try {
        for (let i = 0; i < forms.length; i++) {
          const form = forms[i]
          form.generating = true
          currentGenerationTask.value = `Generating ${form.name}...`
          
          // Simulate form generation
          await new Promise(resolve => setTimeout(resolve, 2000))
          
          form.generated = true
          form.generating = false
          
          // Add to generated forms
          generatedForms.value.push({
            ...form,
            generatedAt: new Date(),
            fileSize: Math.floor(Math.random() * 1000) + 100 + ' KB'
          })
          
          generationProgress.value = Math.round(((i + 1) / forms.length) * 100)
        }
        
        filingSteps.value[2].completed = true
        showNotification('Forms generated successfully', 'success')
      } catch (error) {
        console.error('Error generating forms:', error)
        showNotification('Error generating forms', 'error')
      } finally {
        generatingForms.value = false
        currentGenerationTask.value = ''
      }
    }

    const previewForm = (form) => {
      showNotification(`Previewing ${form.name}`, 'info')
    }

    const downloadForm = (form) => {
      showNotification(`Downloading ${form.name}`, 'info')
    }

    const shareForm = (form) => {
      showNotification(`Sharing ${form.name}`, 'info')
    }

    const nextStep = () => {
      if (canProceedToNext.value && currentStep.value < filingSteps.value.length - 1) {
        currentStep.value++
        
        // Auto-validate when moving to validation step
        if (currentStep.value === 1) {
          performValidation()
        }
      }
    }

    const previousStep = () => {
      if (currentStep.value > 0) {
        currentStep.value--
      }
    }

    const performValidation = async () => {
      try {
        // Simulate validation process
        await new Promise(resolve => setTimeout(resolve, 1000))
        
        validationResults.passed = 8
        validationResults.warnings = 2
        validationResults.errors = 0
        
        filingSteps.value[1].completed = validationResults.errors === 0
        
        showNotification('Data validation completed', 'success')
      } catch (error) {
        console.error('Error during validation:', error)
        showNotification('Error during validation', 'error')
      }
    }

    const fixMissingTaxCodes = async () => {
      showNotification('Fixing missing tax codes...', 'info')
      // Implement auto-fix logic
    }

    const validateExchangeRates = async () => {
      showNotification('Updating exchange rates...', 'info')
      // Implement exchange rate validation
    }

    const checkSupplierInfo = async () => {
      showNotification('Validating supplier information...', 'info')
      // Implement supplier info validation
    }

    const downloadFilingPackage = async () => {
      try {
        const response = await axios.post('/api/accounting/tax-transactions/filing-package', {
          filingData,
          generatedForms: generatedForms.value.map(f => f.id)
        }, {
          responseType: 'blob'
        })
        
        const url = window.URL.createObjectURL(new Blob([response.data]))
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', `tax-filing-package-${filingData.taxYear}.zip`)
        document.body.appendChild(link)
        link.click()
        link.remove()
        window.URL.revokeObjectURL(url)
        
        showNotification('Filing package downloaded successfully', 'success')
      } catch (error) {
        console.error('Error downloading filing package:', error)
        showNotification('Error downloading filing package', 'error')
      }
    }

    const submitFiling = async () => {
      if (!canSubmitFinal.value) {
        showNotification('Please complete all checklist items before submitting', 'warning')
        return
      }
      
      submitting.value = true
      
      try {
        await axios.post('/api/accounting/tax-transactions/submit-filing', {
          filingData,
          generatedForms: generatedForms.value.map(f => f.id),
          options: filingOptions
        })
        
        filingSteps.value[3].completed = true
        showNotification('Tax filing submitted successfully!', 'success')
        
        // Redirect to confirmation page or filing history
        router.push('/tax-transactions/filing-history')
      } catch (error) {
        console.error('Error submitting filing:', error)
        showNotification('Error submitting tax filing', 'error')
      } finally {
        submitting.value = false
      }
    }

    const saveProgress = async () => {
      saving.value = true
      
      try {
        await axios.post('/api/accounting/tax-transactions/save-filing-progress', {
          currentStep: currentStep.value,
          filingData,
          validationResults,
          generatedForms: generatedForms.value,
          checklist: submissionChecklist.value
        })
        
        showNotification('Progress saved successfully', 'success')
      } catch (error) {
        console.error('Error saving progress:', error)
        showNotification('Error saving progress', 'error')
      } finally {
        saving.value = false
      }
    }

    const goBack = () => {
      router.push('/tax-transactions')
    }

    // Utility functions
    const getFilingPeriodLabel = () => {
      if (filingData.period === 'annual') {
        return `Annual ${filingData.taxYear}`
      } else if (filingData.period === 'quarterly') {
        const quarter = filingData.subPeriod.toUpperCase()
        return `${quarter} ${filingData.taxYear}`
      } else {
        const monthNames = [
          'January', 'February', 'March', 'April', 'May', 'June',
          'July', 'August', 'September', 'October', 'November', 'December'
        ]
        const monthIndex = parseInt(filingData.subPeriod) - 1
        return `${monthNames[monthIndex]} ${filingData.taxYear}`
      }
    }

    const getDaysRemaining = () => {
      const today = new Date()
      const due = filingDueDate.value
      const diffTime = due - today
      const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
      return Math.max(0, diffDays)
    }

    const getDaysRemainingClass = () => {
      const days = getDaysRemaining()
      if (days <= 7) return 'urgent'
      if (days <= 30) return 'warning'
      return 'normal'
    }

    const getTotalFileSize = () => {
      let totalSize = 0
      generatedForms.value.forEach(form => {
        const sizeStr = form.fileSize || '0 KB'
        const size = parseInt(sizeStr)
        totalSize += size
      })
      return `${totalSize} KB`
    }

    const getValidationIcon = (status) => {
      const icons = {
        'passed': 'fas fa-check-circle',
        'warning': 'fas fa-exclamation-triangle',
        'error': 'fas fa-times-circle'
      }
      return icons[status] || 'fas fa-circle'
    }

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

    const showNotification = (message, type = 'info') => {
      console.log(`${type}: ${message}`)
      // Implement your notification system here
    }

    // Lifecycle
    onMounted(() => {
      // Initialize with current year data
      updatePeriod()
    })

    return {
      currentStep,
      saving,
      submitting,
      generatingForms,
      generationProgress,
      currentGenerationTask,
      filingData,
      filingOptions,
      taxSummary,
      validationResults,
      filingSteps,
      validationChecks,
      requiredForms,
      generatedForms,
      submissionChecklist,
      taxYearOptions,
      subPeriodOptions,
      overallProgress,
      hasSelectedForms,
      canProceedToNext,
      totalTaxAmount,
      attachmentCount,
      filingDueDate,
      canSubmitFinal,
      updatePeriod,
      toggleDetails,
      updateFormSelection,
      toggleFormSelection,
      generateSelectedForms,
      generateAllForms,
      previewForm,
      downloadForm,
      shareForm,
      nextStep,
      previousStep,
      fixMissingTaxCodes,
      validateExchangeRates,
      checkSupplierInfo,
      downloadFilingPackage,
      submitFiling,
      saveProgress,
      goBack,
      getFilingPeriodLabel,
      getDaysRemaining,
      getDaysRemainingClass,
      getTotalFileSize,
      getValidationIcon,
      formatDate,
      formatDateTime,
      formatCurrency
    }
  }
}
</script>

<style scoped>
.filing-preparation-container {
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

.page-description {
  font-size: 1.1rem;
  color: var(--text-secondary);
  margin: 0.5rem 0 0 0;
}

.header-actions {
  display: flex;
  gap: 1rem;
  align-items: center;
}

/* Progress Section */
.progress-section {
  margin-bottom: 2rem;
  background: var(--card-bg);
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  border: 1px solid var(--border-color);
}

.progress-header {
  padding: 1.5rem 2rem;
  border-bottom: 1px solid var(--border-color);
  background: var(--bg-tertiary);
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 2rem;
}

.progress-header h2 {
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--text-primary);
  margin: 0;
}

.progress-bar {
  flex: 1;
  height: 8px;
  background: var(--border-color);
  border-radius: 4px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, var(--primary-color), var(--primary-dark));
  border-radius: 4px;
  transition: width 0.3s ease;
}

.progress-text {
  font-weight: 600;
  color: var(--text-primary);
  font-size: 0.9rem;
}

.progress-steps {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  padding: 2rem;
}

.step {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  opacity: 0.5;
  transition: all 0.3s ease;
}

.step.active,
.step.completed {
  opacity: 1;
}

.step.error {
  opacity: 1;
}

.step-number {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: var(--border-color);
  color: var(--text-muted);
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  flex-shrink: 0;
  transition: all 0.3s ease;
}

.step.active .step-number {
  background: var(--primary-color);
  color: white;
}

.step.completed .step-number {
  background: var(--success-color);
  color: white;
}

.step.error .step-number {
  background: #dc2626;
  color: white;
}

.step-content h3 {
  font-size: 1rem;
  font-weight: 600;
  color: var(--text-primary);
  margin: 0 0 0.25rem 0;
}

.step-content p {
  font-size: 0.85rem;
  color: var(--text-secondary);
  margin: 0;
  line-height: 1.4;
}

.step-error {
  font-size: 0.8rem;
  color: #dc2626;
  margin-top: 0.5rem;
  padding: 0.5rem;
  background: rgba(220, 38, 38, 0.1);
  border-radius: 4px;
}

/* Main Content */
.main-content {
  max-width: 1200px;
  margin: 0 auto;
}

.filing-step {
  margin-bottom: 2rem;
}

.step-card {
  background: var(--card-bg);
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  border: 1px solid var(--border-color);
}

.step-header {
  padding: 2rem 2rem 1rem 2rem;
  border-bottom: 1px solid var(--border-color);
  background: var(--bg-tertiary);
}

.step-header h2 {
  font-size: 1.5rem;
  font-weight: 600;
  color: var(--text-primary);
  margin: 0 0 0.5rem 0;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.step-header h2 i {
  color: var(--primary-color);
}

.step-header p {
  color: var(--text-secondary);
  margin: 0;
}

/* Period Selection */
.period-selection {
  padding: 2rem;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-label {
  font-weight: 600;
  color: var(--text-primary);
  font-size: 0.9rem;
}

.form-select {
  padding: 0.75rem 1rem;
  border: 2px solid var(--border-color);
  border-radius: 8px;
  font-size: 1rem;
  transition: border-color 0.3s ease;
}

.form-select:focus {
  outline: none;
  border-color: var(--primary-color);
}

.period-summary {
  background: var(--bg-tertiary);
  padding: 1.5rem;
  border-radius: 12px;
  border: 1px solid var(--border-color);
  margin-bottom: 2rem;
}

.period-summary h3 {
  font-size: 1.1rem;
  font-weight: 600;
  color: var(--text-primary);
  margin: 0 0 1rem 0;
}

.summary-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}

.summary-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.summary-label {
  font-weight: 500;
  color: var(--text-secondary);
}

.summary-value {
  font-weight: 600;
  color: var(--text-primary);
}

.summary-value.urgent {
  color: #dc2626;
}

.summary-value.warning {
  color: #f59e0b;
}

.summary-value.normal {
  color: var(--success-color);
}

.data-preview h3 {
  font-size: 1.1rem;
  font-weight: 600;
  color: var(--text-primary);
  margin: 0 0 1rem 0;
}

.preview-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}

.preview-card {
  background: var(--card-bg);
  padding: 1rem;
  border-radius: 12px;
  border: 1px solid var(--border-color);
  display: flex;
  align-items: center;
  gap: 1rem;
}

.preview-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  flex-shrink: 0;
}

.preview-content h4 {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0;
}

.preview-content p {
  font-size: 0.85rem;
  color: var(--text-secondary);
  margin: 0;
}

/* Validation Section */
.validation-section {
  padding: 2rem;
}

.validation-overview {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.validation-card {
  background: var(--card-bg);
  padding: 1.5rem;
  border-radius: 12px;
  border: 1px solid var(--border-color);
  display: flex;
  align-items: center;
  gap: 1rem;
}

.validation-card.success {
  border-color: var(--success-color);
  background: rgba(16, 185, 129, 0.05);
}

.validation-card.warning {
  border-color: #f59e0b;
  background: rgba(245, 158, 11, 0.05);
}

.validation-card.error {
  border-color: #dc2626;
  background: rgba(220, 38, 38, 0.05);
}

.validation-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  flex-shrink: 0;
}

.validation-card.success .validation-icon {
  background: rgba(16, 185, 129, 0.1);
  color: var(--success-color);
}

.validation-card.warning .validation-icon {
  background: rgba(245, 158, 11, 0.1);
  color: #f59e0b;
}

.validation-card.error .validation-icon {
  background: rgba(220, 38, 38, 0.1);
  color: #dc2626;
}

.validation-content h3 {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0;
}

.validation-content p {
  font-size: 0.85rem;
  color: var(--text-secondary);
  margin: 0;
}

.validation-details h3 {
  font-size: 1.1rem;
  font-weight: 600;
  color: var(--text-primary);
  margin: 0 0 1rem 0;
}

.validation-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.validation-item {
  background: var(--bg-tertiary);
  padding: 1.5rem;
  border-radius: 12px;
  border: 1px solid var(--border-color);
}

.validation-item.passed {
  border-color: var(--success-color);
  background: rgba(16, 185, 129, 0.05);
}

.validation-item.warning {
  border-color: #f59e0b;
  background: rgba(245, 158, 11, 0.05);
}

.validation-item.error {
  border-color: #dc2626;
  background: rgba(220, 38, 38, 0.05);
}

.validation-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.5rem;
}

.validation-title {
  font-weight: 600;
  color: var(--text-primary);
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.validation-status {
  font-size: 0.8rem;
  font-weight: 500;
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
}

.validation-item.passed .validation-status {
  background: rgba(16, 185, 129, 0.1);
  color: var(--success-color);
}

.validation-item.warning .validation-status {
  background: rgba(245, 158, 11, 0.1);
  color: #f59e0b;
}

.validation-item.error .validation-status {
  background: rgba(220, 38, 38, 0.1);
  color: #dc2626;
}

.validation-content p {
  color: var(--text-secondary);
  margin: 0.25rem 0;
}

.btn-link {
  background: none;
  border: none;
  color: var(--primary-color);
  cursor: pointer;
  font-size: 0.8rem;
  display: flex;
  align-items: center;
  gap: 0.25rem;
  margin-top: 0.5rem;
}

.details-content {
  margin-top: 0.75rem;
  padding: 0.75rem;
  background: var(--card-bg);
  border-radius: 8px;
}

.details-content ul {
  margin: 0;
  padding-left: 1.5rem;
}

.details-content li {
  margin: 0.25rem 0;
  font-size: 0.85rem;
  color: var(--text-secondary);
}

.auto-fix-section {
  margin-top: 2rem;
  padding: 1.5rem;
  background: var(--bg-tertiary);
  border-radius: 12px;
  border: 1px solid var(--border-color);
}

.auto-fix-section h3 {
  font-size: 1.1rem;
  font-weight: 600;
  color: var(--text-primary);
  margin: 0 0 1rem 0;
}

.fix-options {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
}

/* Form Generation Section */
.form-generation-section {
  padding: 2rem;
}

.form-selection h3 {
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--text-primary);
  margin-bottom: 1.5rem;
}

.forms-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.form-card {
  background: var(--bg-tertiary);
  border: 2px solid var(--border-color);
  border-radius: 12px;
  padding: 1.5rem;
  transition: all 0.3s ease;
  cursor: pointer;
}

.form-card.selected {
  border-color: var(--primary-color);
  background: rgba(99, 102, 241, 0.05);
}

.form-card.generated {
  border-color: var(--success-color);
  background: rgba(16, 185, 129, 0.05);
}

.form-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.form-checkbox {
  position: relative;
}

.form-checkbox input[type="checkbox"] {
  opacity: 0;
  position: absolute;
}

.form-checkbox label {
  width: 20px;
  height: 20px;
  border: 2px solid var(--border-color);
  border-radius: 4px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
}

.form-checkbox input[type="checkbox"]:checked + label {
  background: var(--primary-color);
  border-color: var(--primary-color);
  color: white;
}

.form-checkbox label::after {
  content: '✓';
  opacity: 0;
  transition: opacity 0.2s ease;
}

.form-checkbox input[type="checkbox"]:checked + label::after {
  opacity: 1;
}

.form-status {
  font-size: 1.25rem;
}

.text-success {
  color: var(--success-color);
}

.text-info {
  color: var(--primary-color);
}

.text-muted {
  color: var(--text-muted);
}

.form-content h4 {
  font-size: 1.1rem;
  font-weight: 600;
  color: var(--text-primary);
  margin: 0 0 0.5rem 0;
}

.form-content p {
  font-size: 0.9rem;
  color: var(--text-secondary);
  margin: 0 0 1rem 0;
}

.form-details {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.form-type {
  background: var(--primary-color);
  color: white;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-size: 0.8rem;
  font-weight: 500;
}

.form-due-date {
  font-size: 0.8rem;
  color: var(--text-muted);
}

.form-actions {
  display: flex;
  gap: 0.5rem;
}

.generation-controls {
  margin-bottom: 2rem;
  padding: 1.5rem;
  background: var(--bg-tertiary);
  border-radius: 12px;
  border: 1px solid var(--border-color);
}

.control-buttons {
  display: flex;
  gap: 1rem;
  margin-bottom: 1rem;
}

.generation-options {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.9rem;
  color: var(--text-secondary);
}

.generation-progress {
  margin-bottom: 2rem;
  padding: 1.5rem;
  background: var(--card-bg);
  border-radius: 12px;
  border: 1px solid var(--border-color);
}

.progress-percentage {
  font-weight: 600;
  color: var(--primary-color);
}

.progress-status {
  color: var(--text-secondary);
  margin: 0.5rem 0 0 0;
  font-style: italic;
}

.generated-forms h3 {
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--text-primary);
  margin-bottom: 1.5rem;
}

.forms-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.generated-form-item {
  background: var(--bg-tertiary);
  padding: 1rem;
  border-radius: 12px;
  border: 1px solid var(--border-color);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.form-info {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.form-icon {
  width: 40px;
  height: 40px;
  border-radius: 8px;
  background: linear-gradient(135deg, #dc2626, #b91c1c);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
}

.form-details h4 {
  font-size: 1rem;
  font-weight: 600;
  color: var(--text-primary);
  margin: 0;
}

.form-details p {
  font-size: 0.8rem;
  color: var(--text-secondary);
  margin: 0;
}

.btn-action {
  width: 36px;
  height: 36px;
  border: none;
  border-radius: 8px;
  background: var(--bg-tertiary);
  color: var(--text-secondary);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
}

.btn-action:hover {
  background: var(--primary-color);
  color: white;
}

/* Review Section */
.review-section {
  padding: 2rem;
}

.filing-summary h3 {
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--text-primary);
  margin-bottom: 1.5rem;
}

.summary-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.summary-card {
  background: var(--bg-tertiary);
  border-radius: 12px;
  border: 1px solid var(--border-color);
  overflow: hidden;
}

.summary-header {
  padding: 1rem 1.5rem;
  border-bottom: 1px solid var(--border-color);
  background: var(--card-bg);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.summary-header h4 {
  font-size: 1rem;
  font-weight: 600;
  color: var(--text-primary);
  margin: 0;
}

.summary-header i {
  color: var(--primary-color);
  font-size: 1.25rem;
}

.summary-content {
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.summary-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.summary-row span:first-child {
  color: var(--text-secondary);
}

.summary-row span:last-child {
  font-weight: 600;
  color: var(--text-primary);
}

.summary-row .amount {
  color: var(--primary-color);
  font-size: 1.1rem;
}

.submission-checklist h3 {
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--text-primary);
  margin-bottom: 1.5rem;
}

.checklist-items {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  margin-bottom: 2rem;
}

.checklist-item {
  background: var(--bg-tertiary);
  padding: 1rem;
  border-radius: 12px;
  border: 1px solid var(--border-color);
  display: flex;
  align-items: center;
  gap: 1rem;
  transition: all 0.3s ease;
}

.checklist-item.completed {
  border-color: var(--success-color);
  background: rgba(16, 185, 129, 0.05);
}

.checklist-checkbox {
  position: relative;
  flex-shrink: 0;
}

.checklist-checkbox input[type="checkbox"] {
  opacity: 0;
  position: absolute;
}

.checklist-checkbox label {
  width: 24px;
  height: 24px;
  border: 2px solid var(--border-color);
  border-radius: 6px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
}

.checklist-checkbox input[type="checkbox"]:checked + label {
  background: var(--success-color);
  border-color: var(--success-color);
  color: white;
}

.checklist-checkbox label::after {
  content: '✓';
  opacity: 0;
  transition: opacity 0.2s ease;
  font-weight: bold;
}

.checklist-checkbox input[type="checkbox"]:checked + label::after {
  opacity: 1;
}

.checklist-content {
  flex: 1;
}

.checklist-content h4 {
  font-size: 1rem;
  font-weight: 600;
  color: var(--text-primary);
  margin: 0 0 0.25rem 0;
}

.checklist-content p {
  font-size: 0.85rem;
  color: var(--text-secondary);
  margin: 0;
}

.checklist-status {
  font-size: 1.25rem;
  flex-shrink: 0;
}

.submission-details h3 {
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--text-primary);
  margin-bottom: 1.5rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.details-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.detail-card {
  background: var(--bg-tertiary);
  padding: 1.5rem;
  border-radius: 12px;
  border: 1px solid var(--border-color);
}

.detail-card h4 {
  font-size: 1.1rem;
  font-weight: 600;
  color: var(--text-primary);
  margin: 0 0 1rem 0;
}

.detail-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.detail-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.detail-label {
  color: var(--text-secondary);
  font-weight: 500;
}

.detail-value {
  font-weight: 600;
  color: var(--text-primary);
}

.detail-value.amount {
  color: var(--primary-color);
  font-size: 1.1rem;
}

.final-actions {
  display: flex;
  justify-content: center;
  margin-top: 2rem;
}

.action-buttons {
  display: flex;
  gap: 1rem;
}

/* Navigation */
.step-navigation {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 2rem;
  background: var(--card-bg);
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  border: 1px solid var(--border-color);
  margin-top: 2rem;
}

.nav-left,
.nav-right {
  display: flex;
  gap: 1rem;
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

.btn-sm {
  padding: 0.5rem 1rem;
  font-size: 0.8rem;
}

/* Responsive Design */
@media (max-width: 768px) {
  .filing-preparation-container {
    padding: 1rem;
  }

  .title-section {
    flex-direction: column;
    gap: 1rem;
  }

  .header-actions {
    flex-wrap: wrap;
  }

  .progress-header {
    flex-direction: column;
    gap: 1rem;
  }

  .progress-steps {
    grid-template-columns: 1fr;
  }

  .form-grid {
    grid-template-columns: 1fr;
  }

  .forms-grid {
    grid-template-columns: 1fr;
  }

  .summary-cards {
    grid-template-columns: 1fr;
  }

  .details-grid {
    grid-template-columns: 1fr;
  }

  .control-buttons {
    flex-direction: column;
  }

  .action-buttons {
    flex-direction: column;
    width: 100%;
  }

  .page-title {
    font-size: 2rem;
  }

  .step-navigation {
    flex-direction: column;
    gap: 1rem;
  }

  .nav-left,
  .nav-right {
    width: 100%;
    justify-content: center;
  }
}

/* CSS Variables */
:root {
  --primary-color: #6366f1;
  --primary-dark: #4f46e5;
  --success-color: #10b981;
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