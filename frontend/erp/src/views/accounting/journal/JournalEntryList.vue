<template>
  <div class="journal-entry-list-container">
    <!-- Header -->
    <div class="page-header">
      <div class="header-left">
        <h1>
          <i class="fas fa-book"></i>
          Journal Entries
        </h1>
        <p class="page-subtitle">Manage multicurrency journal entries and accounting transactions</p>
      </div>
      <div class="header-actions">
        <router-link to="/accounting/journal-entries/create" class="btn btn-primary">
          <i class="fas fa-plus"></i>
          Create Entry
        </router-link>
      </div>
    </div>

    <!-- Filters -->
    <div class="filters-section">
      <div class="filters-card">
        <div class="filters-header">
          <h3>
            <i class="fas fa-filter"></i>
            Filters
          </h3>
          <button @click="clearFilters" class="btn btn-sm btn-secondary">
            <i class="fas fa-times"></i>
            Clear
          </button>
        </div>
        
        <div class="filters-content">
          <div class="filters-grid">
            <div class="filter-group">
              <label>Date Range</label>
              <div class="date-range">
                <input
                  type="date"
                  v-model="filters.from_date"
                  class="form-input"
                  placeholder="From Date"
                />
                <span class="date-separator">to</span>
                <input
                  type="date"
                  v-model="filters.to_date"
                  class="form-input"
                  placeholder="To Date"
                />
              </div>
            </div>

            <div class="filter-group">
              <label>Period</label>
              <select v-model="filters.period_id" class="form-select">
                <option value="">All Periods</option>
                <option v-for="period in periods" :key="period.period_id" :value="period.period_id">
                  {{ period.period_name }}
                </option>
              </select>
            </div>

            <div class="filter-group">
              <label>Status</label>
              <select v-model="filters.status" class="form-select">
                <option value="">All Status</option>
                <option value="Draft">Draft</option>
                <option value="Posted">Posted</option>
                <option value="Cancelled">Cancelled</option>
              </select>
            </div>

            <div class="filter-group">
              <label>Currency</label>
              <select v-model="filters.currency" class="form-select">
                <option value="">All Currencies</option>
                <option :value="baseCurrency">{{ baseCurrency }} (Base)</option>
                <option v-for="currency in availableCurrencies" :key="currency.code" :value="currency.code">
                  {{ currency.code }} - {{ currency.name }}
                </option>
              </select>
            </div>

            <div class="filter-group">
              <label>Reference Type</label>
              <select v-model="filters.reference_type" class="form-select">
                <option value="">All Types</option>
                <option value="Invoice">Invoice</option>
                <option value="Payment">Payment</option>
                <option value="Receipt">Receipt</option>
                <option value="Adjustment">Adjustment</option>
                <option value="Transfer">Transfer</option>
                <option value="Other">Other</option>
              </select>
            </div>

            <div class="filter-group">
              <label>Search</label>
              <div class="search-input-group">
                <i class="fas fa-search search-icon"></i>
                <input
                  type="text"
                  v-model="filters.search"
                  class="form-input search-input"
                  placeholder="Search journal number, description..."
                />
              </div>
            </div>
          </div>
          
          <div class="filters-actions">
            <button @click="applyFilters" class="btn btn-primary" :disabled="loading">
              <i v-if="loading" class="fas fa-spinner fa-spin"></i>
              <i v-else class="fas fa-search"></i>
              Apply Filters
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Entries Table -->
    <div class="entries-section">
      <div class="entries-card">
        <div class="entries-header">
          <h3>
            <i class="fas fa-list"></i>
            Journal Entries
            <span class="entries-count" v-if="journalEntries.total">
              ({{ journalEntries.total }} entries)
            </span>
          </h3>
          <div class="view-options">
            <button
              @click="viewMode = 'table'"
              class="btn btn-sm"
              :class="{ 'btn-primary': viewMode === 'table', 'btn-secondary': viewMode !== 'table' }"
            >
              <i class="fas fa-table"></i>
              Table
            </button>
            <button
              @click="viewMode = 'cards'"
              class="btn btn-sm"
              :class="{ 'btn-primary': viewMode === 'cards', 'btn-secondary': viewMode !== 'cards' }"
            >
              <i class="fas fa-th-large"></i>
              Cards
            </button>
          </div>
        </div>

        <div class="entries-content">
          <!-- Loading State -->
          <div v-if="loading" class="loading-state">
            <i class="fas fa-spinner fa-spin"></i>
            <p>Loading journal entries...</p>
          </div>

          <!-- Empty State -->
          <div v-else-if="!journalEntries.data || journalEntries.data.length === 0" class="empty-state">
            <div class="empty-icon">
              <i class="fas fa-book-open"></i>
            </div>
            <h3>No Journal Entries Found</h3>
            <p>{{ hasActiveFilters ? 'Try adjusting your filters' : 'Create your first journal entry to get started' }}</p>
            <router-link to="/accounting/journal-entries/create" class="btn btn-primary">
              <i class="fas fa-plus"></i>
              Create Journal Entry
            </router-link>
          </div>

          <!-- Table View -->
          <div v-else-if="viewMode === 'table'" class="table-container">
            <table class="entries-table">
              <thead>
                <tr>
                  <th>
                    <button @click="sortBy('journal_number')" class="sort-header">
                      Journal Number
                      <i class="fas fa-sort" :class="getSortIcon('journal_number')"></i>
                    </button>
                  </th>
                  <th>
                    <button @click="sortBy('entry_date')" class="sort-header">
                      Date
                      <i class="fas fa-sort" :class="getSortIcon('entry_date')"></i>
                    </button>
                  </th>
                  <th>Period</th>
                  <th>Description</th>
                  <th>Status</th>
                  <th>Currencies</th>
                  <th>Total Amount</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="entry in journalEntries.data" :key="entry.journal_id" class="entry-row">
                  <td>
                    <router-link :to="`/accounting/journal-entries/${entry.journal_id}`" class="journal-number-link">
                      {{ entry.journal_number }}
                    </router-link>
                  </td>
                  <td>{{ formatDate(entry.entry_date) }}</td>
                  <td>
                    <span v-if="entry.accounting_period" class="period-badge">
                      {{ entry.accounting_period.period_name }}
                    </span>
                    <span v-else class="text-muted">-</span>
                  </td>
                  <td>
                    <div class="description-cell">
                      <span class="description-text">{{ entry.description || '-' }}</span>
                      <div v-if="entry.reference_type" class="reference-info">
                        {{ entry.reference_type }}
                        <span v-if="entry.reference_id">#{{ entry.reference_id }}</span>
                      </div>
                    </div>
                  </td>
                  <td>
                    <span class="status-badge" :class="entry.status.toLowerCase()">
                      {{ entry.status }}
                    </span>
                  </td>
                  <td>
                    <div class="currencies-cell">
                      <span v-for="currency in getEntryCurrencies(entry)" :key="currency" class="currency-tag">
                        {{ currency }}
                      </span>
                    </div>
                  </td>
                  <td>
                    <div class="amount-cell">
                      <div class="amount-primary">
                        {{ formatCurrency(getEntryTotal(entry)) }}
                      </div>
                      <div v-if="getEntryForeignAmounts(entry).length > 0" class="foreign-amounts">
                        <span
                          v-for="amount in getEntryForeignAmounts(entry)"
                          :key="amount.currency"
                          class="foreign-amount"
                        >
                          {{ formatCurrency(amount.total, amount.currency) }}
                        </span>
                      </div>
                    </div>
                  </td>
                  <td>
                    <div class="actions-cell">
                      <router-link :to="`/accounting/journal-entries/${entry.journal_id}`" class="btn btn-xs btn-secondary" title="View">
                        <i class="fas fa-eye"></i>
                      </router-link>
                      <router-link
                        v-if="entry.status !== 'Posted'"
                        :to="`/accounting/journal-entries/${entry.journal_id}/edit`"
                        class="btn btn-xs btn-primary"
                        title="Edit"
                      >
                        <i class="fas fa-edit"></i>
                      </router-link>
                      <button
                        v-if="entry.status === 'Draft'"
                        @click="postEntry(entry)"
                        class="btn btn-xs btn-success"
                        title="Post Entry"
                        :disabled="posting[entry.journal_id]"
                      >
                        <i v-if="posting[entry.journal_id]" class="fas fa-spinner fa-spin"></i>
                        <i v-else class="fas fa-check"></i>
                      </button>
                      <button @click="convertEntry(entry)" class="btn btn-xs btn-warning" title="Convert Currency">
                        <i class="fas fa-exchange-alt"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Cards View -->
          <div v-else class="cards-container">
            <div v-for="entry in journalEntries.data" :key="entry.journal_id" class="entry-card">
              <div class="card-header">
                <div class="card-title">
                  <router-link :to="`/accounting/journal-entries/${entry.journal_id}`" class="journal-number-link">
                    {{ entry.journal_number }}
                  </router-link>
                  <span class="status-badge" :class="entry.status.toLowerCase()">
                    {{ entry.status }}
                  </span>
                </div>
                <div class="card-date">{{ formatDate(entry.entry_date) }}</div>
              </div>
              
              <div class="card-content">
                <div class="card-description">
                  <p>{{ entry.description || 'No description' }}</p>
                  <div v-if="entry.reference_type" class="reference-info">
                    <i class="fas fa-link"></i>
                    {{ entry.reference_type }}
                    <span v-if="entry.reference_id">#{{ entry.reference_id }}</span>
                  </div>
                </div>
                
                <div class="card-details">
                  <div class="detail-item">
                    <label>Period:</label>
                    <span>{{ entry.accounting_period?.period_name || '-' }}</span>
                  </div>
                  <div class="detail-item">
                    <label>Currencies:</label>
                    <div class="currencies-list">
                      <span v-for="currency in getEntryCurrencies(entry)" :key="currency" class="currency-tag">
                        {{ currency }}
                      </span>
                    </div>
                  </div>
                  <div class="detail-item">
                    <label>Total Amount:</label>
                    <div class="amounts-list">
                      <div class="amount-primary">{{ formatCurrency(getEntryTotal(entry)) }}</div>
                      <div v-if="getEntryForeignAmounts(entry).length > 0" class="foreign-amounts">
                        <span
                          v-for="amount in getEntryForeignAmounts(entry)"
                          :key="amount.currency"
                          class="foreign-amount"
                        >
                          {{ formatCurrency(amount.total, amount.currency) }}
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              
              <div class="card-actions">
                <router-link :to="`/accounting/journal-entries/${entry.journal_id}`" class="btn btn-sm btn-secondary">
                  <i class="fas fa-eye"></i>
                  View
                </router-link>
                <router-link
                  v-if="entry.status !== 'Posted'"
                  :to="`/accounting/journal-entries/${entry.journal_id}/edit`"
                  class="btn btn-sm btn-primary"
                >
                  <i class="fas fa-edit"></i>
                  Edit
                </router-link>
                <button
                  v-if="entry.status === 'Draft'"
                  @click="postEntry(entry)"
                  class="btn btn-sm btn-success"
                  :disabled="posting[entry.journal_id]"
                >
                  <i v-if="posting[entry.journal_id]" class="fas fa-spinner fa-spin"></i>
                  <i v-else class="fas fa-check"></i>
                  Post
                </button>
              </div>
            </div>
          </div>

          <!-- Pagination -->
          <div v-if="journalEntries.data && journalEntries.data.length > 0" class="pagination-section">
            <div class="pagination-info">
              Showing {{ journalEntries.from }} to {{ journalEntries.to }} of {{ journalEntries.total }} entries
            </div>
            <div class="pagination-controls">
              <button
                @click="goToPage(journalEntries.current_page - 1)"
                class="btn btn-sm btn-secondary"
                :disabled="!journalEntries.prev_page_url"
              >
                <i class="fas fa-chevron-left"></i>
                Previous
              </button>
              
              <div class="page-numbers">
                <button
                  v-for="page in getPageNumbers()"
                  :key="page"
                  @click="goToPage(page)"
                  class="btn btn-sm"
                  :class="{ 'btn-primary': page === journalEntries.current_page, 'btn-secondary': page !== journalEntries.current_page }"
                >
                  {{ page }}
                </button>
              </div>
              
              <button
                @click="goToPage(journalEntries.current_page + 1)"
                class="btn btn-sm btn-secondary"
                :disabled="!journalEntries.next_page_url"
              >
                Next
                <i class="fas fa-chevron-right"></i>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Currency Conversion Modal -->
    <div v-if="showConversionModal" class="modal-overlay" @click="showConversionModal = false">
      <div class="modal-content conversion-modal" @click.stop>
        <div class="modal-header">
          <h3>
            <i class="fas fa-exchange-alt"></i>
            Convert Journal Entry Currency
          </h3>
          <button @click="showConversionModal = false" class="close-btn">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <div v-if="selectedEntry" class="conversion-form">
            <div class="entry-info">
              <h4>{{ selectedEntry.journal_number }}</h4>
              <p>{{ selectedEntry.description }}</p>
            </div>
            
            <div class="form-group">
              <label>Convert to Currency</label>
              <select v-model="conversionCurrency" class="form-select">
                <option value="">Select Currency</option>
                <option :value="baseCurrency">{{ baseCurrency }} (Base)</option>
                <option v-for="currency in availableCurrencies" :key="currency.code" :value="currency.code">
                  {{ currency.code }} - {{ currency.name }}
                </option>
              </select>
            </div>
            
            <div class="form-group">
              <label>Conversion Date</label>
              <input
                type="date"
                v-model="conversionDate"
                class="form-input"
              />
            </div>
            
            <div class="conversion-actions">
              <button @click="showConversionModal = false" class="btn btn-secondary">Cancel</button>
              <button @click="performConversion" class="btn btn-primary" :disabled="!conversionCurrency || converting">
                <i v-if="converting" class="fas fa-spinner fa-spin"></i>
                <i v-else class="fas fa-exchange-alt"></i>
                Convert
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'JournalEntryList',
  data() {
    return {
      loading: false,
      journalEntries: {
        data: [],
        total: 0,
        current_page: 1,
        last_page: 1,
        from: 0,
        to: 0,
        prev_page_url: null,
        next_page_url: null
      },
      
      // Master data
      periods: [],
      availableCurrencies: [],
      baseCurrency: 'USD',
      
      // Filters
      filters: {
        from_date: '',
        to_date: '',
        period_id: '',
        status: '',
        currency: '',
        reference_type: '',
        search: ''
      },
      
      // Sorting
      sortField: 'entry_date',
      sortDirection: 'desc',
      
      // View options
      viewMode: 'table', // 'table' or 'cards'
      
      // Actions
      posting: {},
      
      // Currency conversion
      showConversionModal: false,
      selectedEntry: null,
      conversionCurrency: '',
      conversionDate: new Date().toISOString().split('T')[0],
      converting: false
    }
  },
  
  computed: {
    hasActiveFilters() {
      return Object.values(this.filters).some(filter => filter !== '')
    }
  },
  
  async mounted() {
    await this.loadMasterData()
    await this.loadJournalEntries()
  },
  
  methods: {
    async loadMasterData() {
      try {
        const [periodsRes, currenciesRes, settingsRes] = await Promise.all([
          axios.get('/accounting/accounting-periods'),
          axios.get('/accounting/chart-of-accounts/currencies/available'),
          axios.get('/settings/group/currency')
        ])
        
        this.periods = periodsRes.data.data || periodsRes.data
        this.availableCurrencies = currenciesRes.data.data || currenciesRes.data
        
        if (settingsRes.data.data) {
          const baseCurrencySetting = settingsRes.data.data.find(s => s.key === 'base_currency')
          if (baseCurrencySetting) {
            this.baseCurrency = baseCurrencySetting.value
          }
        }
        
      } catch (error) {
        console.error('Error loading master data:', error)
        this.$toast.error('Failed to load master data')
      }
    },
    
    async loadJournalEntries(page = 1) {
      this.loading = true
      
      try {
        const params = {
          page,
          per_page: 15,
          sort_field: this.sortField,
          sort_direction: this.sortDirection,
          ...this.filters
        }
        
        // Remove empty filters
        Object.keys(params).forEach(key => {
          if (params[key] === '' || params[key] === null || params[key] === undefined) {
            delete params[key]
          }
        })
        
        const response = await axios.get('/accounting/journal-entries', { params })
        this.journalEntries = response.data
        
      } catch (error) {
        console.error('Error loading journal entries:', error)
        this.$toast.error('Failed to load journal entries')
      } finally {
        this.loading = false
      }
    },
    
    applyFilters() {
      this.loadJournalEntries(1)
    },
    
    clearFilters() {
      this.filters = {
        from_date: '',
        to_date: '',
        period_id: '',
        status: '',
        currency: '',
        reference_type: '',
        search: ''
      }
      this.loadJournalEntries(1)
    },
    
    sortBy(field) {
      if (this.sortField === field) {
        this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc'
      } else {
        this.sortField = field
        this.sortDirection = 'asc'
      }
      this.loadJournalEntries(1)
    },
    
    getSortIcon(field) {
      if (this.sortField !== field) {
        return ''
      }
      return this.sortDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down'
    },
    
    goToPage(page) {
      if (page >= 1 && page <= this.journalEntries.last_page) {
        this.loadJournalEntries(page)
      }
    },
    
    getPageNumbers() {
      const current = this.journalEntries.current_page
      const last = this.journalEntries.last_page
      const pages = []
      
      // Show up to 5 page numbers
      let start = Math.max(1, current - 2)
      let end = Math.min(last, start + 4)
      
      // Adjust start if we're near the end
      if (end - start < 4) {
        start = Math.max(1, end - 4)
      }
      
      for (let i = start; i <= end; i++) {
        pages.push(i)
      }
      
      return pages
    },
    
    getEntryCurrencies(entry) {
      if (!entry.journal_entry_lines) return [this.baseCurrency]
      
      const currencies = new Set()
      entry.journal_entry_lines.forEach(line => {
        currencies.add(line.currency || this.baseCurrency)
      })
      
      return Array.from(currencies)
    },
    
    getEntryTotal(entry) {
      if (!entry.journal_entry_lines) return 0
      
      return entry.journal_entry_lines.reduce((sum, line) => {
        return sum + (line.debit_amount || 0)
      }, 0)
    },
    
    getEntryForeignAmounts(entry) {
      if (!entry.journal_entry_lines) return []
      
      const foreignAmounts = {}
      
      entry.journal_entry_lines.forEach(line => {
        if (line.currency && line.currency !== this.baseCurrency && line.foreign_amount) {
          if (!foreignAmounts[line.currency]) {
            foreignAmounts[line.currency] = 0
          }
          foreignAmounts[line.currency] += line.foreign_amount
        }
      })
      
      return Object.entries(foreignAmounts).map(([currency, total]) => ({
        currency,
        total
      }))
    },
    
    async postEntry(entry) {
      this.$set(this.posting, entry.journal_id, true)
      
      try {
        await axios.post(`/accounting/journal-entries/${entry.journal_id}/post`)
        this.$toast.success('Journal entry posted successfully')
        await this.loadJournalEntries(this.journalEntries.current_page)
        
      } catch (error) {
        console.error('Error posting entry:', error)
        this.$toast.error('Failed to post journal entry')
      } finally {
        this.$set(this.posting, entry.journal_id, false)
      }
    },
    
    convertEntry(entry) {
      this.selectedEntry = entry
      this.conversionCurrency = ''
      this.conversionDate = new Date().toISOString().split('T')[0]
      this.showConversionModal = true
    },
    
    async performConversion() {
      if (!this.conversionCurrency) return
      
      this.converting = true
      
      try {
        const response = await axios.post(`/accounting/journal-entries/${this.selectedEntry.journal_id}/convert-to-currency`, {
          target_currency: this.conversionCurrency,
          conversion_date: this.conversionDate
        })
        
        this.$toast.success('Currency conversion completed')
        this.showConversionModal = false
        
        // Show conversion results (could open a new modal or navigate to results page)
        console.log('Conversion results:', response.data.data)
        
      } catch (error) {
        console.error('Error converting currency:', error)
        this.$toast.error('Failed to convert currency')
      } finally {
        this.converting = false
      }
    },
    
    formatDate(date) {
      return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      })
    },
    
    formatCurrency(amount, currency = null) {
      return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: currency || this.baseCurrency,
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      }).format(amount || 0)
    }
  }
}
</script>

<style scoped>
.journal-entry-list-container {
  padding: 1rem;
  max-width: 1400px;
  margin: 0 auto;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 2rem;
  padding-bottom: 1rem;
  border-bottom: 2px solid var(--gray-200);
}

.header-left h1 {
  margin: 0;
  color: var(--gray-800);
  font-size: 1.75rem;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.page-subtitle {
  margin: 0.5rem 0 0 0;
  color: var(--gray-600);
  font-size: 0.95rem;
}

.filters-section,
.entries-section {
  margin-bottom: 2rem;
}

.filters-card,
.entries-card {
  background: white;
  border-radius: 0.75rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.filters-header,
.entries-header {
  padding: 1.25rem 1.5rem;
  background: var(--gray-50);
  border-bottom: 1px solid var(--gray-200);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.filters-header h3,
.entries-header h3 {
  margin: 0;
  color: var(--gray-700);
  font-size: 1.1rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.entries-count {
  color: var(--gray-500);
  font-weight: normal;
  font-size: 0.9rem;
}

.filters-content,
.entries-content {
  padding: 1.5rem;
}

.filters-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.filter-group label {
  font-weight: 500;
  color: var(--gray-700);
  font-size: 0.875rem;
}

.date-range {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.date-separator {
  color: var(--gray-500);
  font-size: 0.875rem;
}

.search-input-group {
  position: relative;
}

.search-icon {
  position: absolute;
  left: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  color: var(--gray-500);
}

.search-input {
  padding-left: 2.5rem;
}

.view-options {
  display: flex;
  gap: 0.5rem;
}

.table-container {
  overflow-x: auto;
}

.entries-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.875rem;
}

.entries-table th,
.entries-table td {
  padding: 0.75rem;
  border-bottom: 1px solid var(--gray-200);
  text-align: left;
}

.entries-table th {
  background: var(--gray-50);
  font-weight: 600;
  color: var(--gray-700);
}

.sort-header {
  background: none;
  border: none;
  font-weight: 600;
  color: var(--gray-700);
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0;
}

.sort-header:hover {
  color: var(--blue-600);
}

.entry-row:hover {
  background: var(--gray-50);
}

.journal-number-link {
  color: var(--blue-600);
  text-decoration: none;
  font-weight: 600;
}

.journal-number-link:hover {
  text-decoration: underline;
}

.status-badge {
  padding: 0.25rem 0.5rem;
  border-radius: 0.25rem;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
}

.status-badge.draft {
  background: var(--yellow-100);
  color: var(--yellow-800);
}

.status-badge.posted {
  background: var(--green-100);
  color: var(--green-800);
}

.status-badge.cancelled {
  background: var(--red-100);
  color: var(--red-800);
}

.period-badge {
  background: var(--blue-100);
  color: var(--blue-800);
  padding: 0.25rem 0.5rem;
  border-radius: 0.25rem;
  font-size: 0.75rem;
  font-weight: 500;
}

.description-cell {
  max-width: 200px;
}

.description-text {
  display: block;
  margin-bottom: 0.25rem;
}

.reference-info {
  font-size: 0.75rem;
  color: var(--gray-500);
}

.currencies-cell {
  display: flex;
  flex-wrap: wrap;
  gap: 0.25rem;
}

.currency-tag {
  background: var(--blue-100);
  color: var(--blue-800);
  padding: 0.125rem 0.375rem;
  border-radius: 0.25rem;
  font-size: 0.75rem;
  font-weight: 500;
}

.amount-cell {
  text-align: right;
}

.amount-primary {
  font-weight: 600;
  color: var(--gray-800);
}

.foreign-amounts {
  margin-top: 0.25rem;
}

.foreign-amount {
  display: block;
  font-size: 0.75rem;
  color: var(--gray-600);
}

.actions-cell {
  display: flex;
  gap: 0.25rem;
}

.cards-container {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 1rem;
}

.entry-card {
  border: 1px solid var(--gray-200);
  border-radius: 0.5rem;
  background: white;
  transition: box-shadow 0.2s;
}

.entry-card:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.card-header {
  padding: 1rem;
  border-bottom: 1px solid var(--gray-200);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.card-title {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.card-date {
  color: var(--gray-600);
  font-size: 0.875rem;
}

.card-content {
  padding: 1rem;
}

.card-description {
  margin-bottom: 1rem;
}

.card-description p {
  margin: 0;
  color: var(--gray-700);
}

.card-details {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.detail-item {
  display: flex;
  align-items: flex-start;
  gap: 0.5rem;
}

.detail-item label {
  font-weight: 500;
  color: var(--gray-600);
  min-width: 80px;
}

.currencies-list {
  display: flex;
  flex-wrap: wrap;
  gap: 0.25rem;
}

.amounts-list {
  text-align: right;
}

.card-actions {
  padding: 1rem;
  border-top: 1px solid var(--gray-200);
  display: flex;
  gap: 0.5rem;
}

.pagination-section {
  display: flex;
  justify-content: between;
  align-items: center;
  margin-top: 1.5rem;
  padding-top: 1rem;
  border-top: 1px solid var(--gray-200);
}

.pagination-info {
  color: var(--gray-600);
  font-size: 0.875rem;
}

.pagination-controls {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.page-numbers {
  display: flex;
  gap: 0.25rem;
}

.loading-state,
.empty-state {
  text-align: center;
  padding: 3rem 1rem;
  color: var(--gray-600);
}

.loading-state i {
  font-size: 2rem;
  margin-bottom: 1rem;
}

.empty-icon {
  font-size: 3rem;
  color: var(--gray-400);
  margin-bottom: 1rem;
}

.empty-state h3 {
  margin: 0 0 0.5rem 0;
  color: var(--gray-800);
}

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
}

.modal-content {
  background: white;
  border-radius: 0.75rem;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
  max-width: 500px;
  width: 90%;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-header {
  padding: 1.5rem;
  border-bottom: 1px solid var(--gray-200);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-header h3 {
  margin: 0;
  color: var(--gray-800);
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.close-btn {
  background: none;
  border: none;
  font-size: 1.25rem;
  color: var(--gray-500);
  cursor: pointer;
  padding: 0.25rem;
  border-radius: 0.25rem;
  transition: color 0.2s;
}

.close-btn:hover {
  color: var(--gray-700);
}

.modal-body {
  padding: 1.5rem;
}

.entry-info {
  margin-bottom: 1.5rem;
  padding: 1rem;
  background: var(--gray-50);
  border-radius: 0.5rem;
}

.entry-info h4 {
  margin: 0 0 0.5rem 0;
  color: var(--gray-800);
}

.entry-info p {
  margin: 0;
  color: var(--gray-600);
}

.conversion-actions {
  display: flex;
  gap: 0.75rem;
  margin-top: 1.5rem;
}

.conversion-actions .btn {
  flex: 1;
}

/* Form Styles */
.form-input,
.form-select {
  width: 100%;
  padding: 0.625rem;
  border: 1px solid var(--gray-300);
  border-radius: 0.375rem;
  font-size: 0.875rem;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.form-input:focus,
.form-select:focus {
  outline: none;
  border-color: var(--blue-500);
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Button Styles */
.btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.625rem 1rem;
  border: none;
  border-radius: 0.375rem;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  text-decoration: none;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-primary {
  background: var(--blue-600);
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: var(--blue-700);
}

.btn-secondary {
  background: var(--gray-600);
  color: white;
}

.btn-secondary:hover:not(:disabled) {
  background: var(--gray-700);
}

.btn-success {
  background: var(--green-600);
  color: white;
}

.btn-success:hover:not(:disabled) {
  background: var(--green-700);
}

.btn-warning {
  background: var(--yellow-600);
  color: white;
}

.btn-warning:hover:not(:disabled) {
  background: var(--yellow-700);
}

.btn-sm {
  padding: 0.375rem 0.75rem;
  font-size: 0.8125rem;
}

.btn-xs {
  padding: 0.25rem 0.5rem;
  font-size: 0.75rem;
}

/* Responsive Design */
@media (max-width: 1200px) {
  .filters-grid {
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  }
}

@media (max-width: 992px) {
  .page-header {
    flex-direction: column;
    gap: 1rem;
  }
  
  .header-actions {
    width: 100%;
  }
  
  .filters-grid {
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  }
  
  .pagination-section {
    flex-direction: column;
    gap: 1rem;
  }
}

@media (max-width: 768px) {
  .filters-grid {
    grid-template-columns: 1fr;
  }
  
  .date-range {
    flex-direction: column;
    gap: 0.5rem;
  }
  
  .view-options {
    order: -1;
    margin-bottom: 0.5rem;
  }
  
  .entries-header {
    flex-direction: column;
    align-items: flex-start;
  }
  
  .table-container {
    font-size: 0.75rem;
  }
  
  .cards-container {
    grid-template-columns: 1fr;
  }
  
  .card-actions {
    flex-direction: column;
  }
  
  .actions-cell {
    flex-direction: column;
  }
}
</style>