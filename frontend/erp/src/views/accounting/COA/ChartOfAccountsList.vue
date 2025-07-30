<!-- src/views/accounting/COA/ChartOfAccountsList.vue -->
<template>
  <div class="chart-accounts-page">
    <!-- Page Header -->
    <div class="page-header">
      <div class="header-content">
        <div class="title-section">
          <h1 class="page-title">
            <i class="fas fa-sitemap"></i>
            Chart of Accounts
          </h1>
          <p class="page-subtitle">Manage your accounting structure and account hierarchy</p>
        </div>
        <div class="header-actions">
          <button @click="refreshData" class="btn btn-secondary" :disabled="loading">
            <i class="fas fa-sync-alt" :class="{ 'fa-spin': loading }"></i>
            Refresh
          </button>
          <button @click="toggleView" class="btn btn-outline">
            <i :class="viewMode === 'hierarchy' ? 'fas fa-list' : 'fas fa-sitemap'"></i>
            {{ viewMode === 'hierarchy' ? 'List View' : 'Tree View' }}
          </button>
          <button @click="showTrialBalance" class="btn btn-info">
            <i class="fas fa-balance-scale"></i>
            Trial Balance
          </button>
          <router-link to="/accounting/chart-of-accounts/create" class="btn btn-primary">
            <i class="fas fa-plus"></i>
            Add Account
          </router-link>
        </div>
      </div>
    </div>

    <!-- Debug Info (Remove in production) -->
    <div v-if="showDebug" class="debug-info">
      <h3>Debug Information:</h3>
      <div class="debug-grid">
        <div><strong>View Mode:</strong> {{ viewMode }}</div>
        <div><strong>Loading:</strong> {{ loading }}</div>
        <div><strong>Error:</strong> {{ error }}</div>
        <div><strong>Total Accounts:</strong> {{ accounts.length }}</div>
        <div><strong>Hierarchy Accounts:</strong> {{ hierarchyAccounts.length }}</div>
        <div><strong>Filtered Accounts:</strong> {{ filteredAccounts.length }}</div>
        <div><strong>Root Accounts:</strong> {{ rootAccounts.length }}</div>
      </div>
      
      <details>
        <summary>Root Accounts with Children</summary>
        <pre>{{ JSON.stringify(rootAccounts.map(acc => ({
          id: acc.account_id,
          code: acc.account_code,
          name: acc.name,
          expanded: acc.expanded,
          children_count: acc.child_accounts?.length || 0,
          children: acc.child_accounts?.map(child => ({
            id: child.account_id,
            code: child.account_code,
            name: child.name
          })) || []
        })), null, 2) }}</pre>
      </details>
    </div>

    <!-- Multi-Currency Controls -->
    <div class="currency-section" v-if="showCurrencyControls">
      <div class="currency-controls">
        <div class="currency-selector">
          <label>View Currency:</label>
          <select v-model="selectedCurrency" @change="loadAccountsWithCurrency" class="form-select">
            <option value="">Base Currency</option>
            <option v-for="currency in availableCurrencies" :key="currency.code" :value="currency.code">
              {{ currency.code }} - {{ currency.name }}
            </option>
          </select>
        </div>
        <div class="date-selector">
          <label>As of Date:</label>
          <input 
            v-model="asOfDate" 
            type="date" 
            @change="loadAccountsWithCurrency"
            class="form-input"
          />
        </div>
        <div class="balance-toggle">
          <label class="toggle-option">
            <input v-model="showBalances" type="checkbox" @change="loadAccountsWithCurrency">
            <span class="toggle-label">Show Balances</span>
          </label>
        </div>
      </div>
    </div>

    <!-- Filters and Search -->
    <div class="filters-section">
      <div class="search-filters">
        <div class="search-box">
          <i class="fas fa-search"></i>
          <input 
            v-model="searchTerm" 
            type="text" 
            placeholder="Search accounts by code or name..."
            @input="handleSearch"
          />
        </div>
        <div class="filter-group">
          <select v-model="selectedType" @change="applyFilters" class="filter-select">
            <option value="">All Types</option>
            <option value="CURRENT ASSETS">Current Assets</option>
            <option value="FIXED ASSETS">Fixed Assets</option>
            <option value="OTHER ASSETS">Other Assets</option>
            <option value="CURRENT LIABILITIES">Current Liabilities</option>
            <option value="LONG TERM LIABILITIES">Long Term Liabilities</option>
            <option value="CAPITAL">Capital</option>
            <option value="RETAINED EARNING">Retained Earning</option>
            <option value="SALES">Sales</option>
            <option value="COST OF GOODS SOLD">Cost of Goods Sold</option>
            <option value="EXPENSE">Expense</option>
            <option value="OTHER INCOME">Other Income</option>
            <option value="OTHER EXPENSES">Other Expenses</option>
            <option value="TAXATION">Taxation</option>
          </select>
          <select v-model="selectedStatus" @change="applyFilters" class="filter-select">
            <option value="">All Status</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
          <button @click="toggleCurrencyControls" class="btn btn-outline">
            <i class="fas fa-money-bill-wave"></i>
            Multi-Currency
          </button>
          <button @click="toggleDebug" class="btn btn-outline debug-btn">
            <i class="fas fa-bug"></i>
            Debug
          </button>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="loading-container">
      <div class="loading-spinner"></div>
      <p>Loading chart of accounts...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="error-container">
      <i class="fas fa-exclamation-triangle"></i>
      <h3>Error Loading Accounts</h3>
      <p>{{ error }}</p>
      <button @click="loadAccounts" class="btn btn-primary">Try Again</button>
    </div>

    <!-- Content -->
    <div v-else class="accounts-content">
      <!-- Statistics Cards -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon asset">
            <i class="fas fa-building"></i>
          </div>
          <div class="stat-info">
            <h3>{{ stats.assets || 0 }}</h3>
            <p>Assets</p>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon liability">
            <i class="fas fa-credit-card"></i>
          </div>
          <div class="stat-info">
            <h3>{{ stats.liabilities || 0 }}</h3>
            <p>Liabilities</p>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon equity">
            <i class="fas fa-chart-pie"></i>
          </div>
          <div class="stat-info">
            <h3>{{ stats.equity || 0 }}</h3>
            <p>Equity</p>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon revenue">
            <i class="fas fa-arrow-up"></i>
          </div>
          <div class="stat-info">
            <h3>{{ stats.revenue || 0 }}</h3>
            <p>Revenue</p>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon expense">
            <i class="fas fa-receipt"></i>
          </div>
          <div class="stat-info">
            <h3>{{ stats.expenses || 0 }}</h3>
            <p>Expenses</p>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon total">
            <i class="fas fa-calculator"></i>
          </div>
          <div class="stat-info">
            <h3>{{ filteredAccounts.length }}</h3>
            <p>Total Accounts</p>
          </div>
        </div>
      </div>

      <!-- No Data State -->
      <div v-if="!loading && (viewMode === 'list' ? filteredAccounts.length === 0 : rootAccounts.length === 0)" class="no-data-container">
        <i class="fas fa-folder-open"></i>
        <h3>No Accounts Found</h3>
        <p v-if="searchTerm || selectedType || selectedStatus">
          No accounts match your current filters. Try adjusting your search criteria.
        </p>
        <p v-else>
          No chart of accounts data available. Start by creating your first account.
        </p>
        <div class="no-data-actions">
          <button v-if="searchTerm || selectedType || selectedStatus" @click="clearFilters" class="btn btn-outline">
            Clear Filters
          </button>
          <router-link to="/accounting/chart-of-accounts/create" class="btn btn-primary">
            <i class="fas fa-plus"></i>
            Create First Account
          </router-link>
        </div>
      </div>

      <!-- Hierarchy View -->
      <div v-else-if="viewMode === 'hierarchy'" class="hierarchy-view">
        <h3>Tree View ({{ rootAccounts.length }} root accounts)</h3>
        
        <div v-if="rootAccounts.length === 0 && hierarchyAccounts.length > 0" class="no-root-accounts">
          <i class="fas fa-folder-open"></i>
          <h4>No Root Accounts Found</h4>
          <p>All {{ hierarchyAccounts.length }} accounts have parent relationships.</p>
          <p v-if="searchTerm || selectedType || selectedStatus">
            This might be due to current filters. Try clearing filters.
          </p>
          <div class="no-data-actions">
            <button v-if="searchTerm || selectedType || selectedStatus" @click="clearFilters" class="btn btn-outline">
              Clear Filters
            </button>
            <button @click="showAllAsFlat = true" class="btn btn-outline">Show All Accounts</button>
          </div>
        </div>
        
        <div v-else-if="rootAccounts.length > 0" class="accounts-tree">
          <!-- Root Accounts dengan Simple Rendering -->
          <div v-for="account in rootAccounts" :key="`root-${account.account_id}`" class="simple-account-node">
            <!-- Root Account Header -->
            <div class="account-header" @click="toggleAccount(account)">
              <span v-if="account.child_accounts && account.child_accounts.length > 0" class="toggle-icon">
                <i :class="account.expanded ? 'fas fa-chevron-down' : 'fas fa-chevron-right'"></i>
              </span>
              <span v-else class="toggle-spacer"></span>
              
              <span class="account-code">{{ account.account_code }}</span>
              <span class="account-name">{{ account.name }}</span>
              <span class="account-type" :class="getAccountTypeClass(account.account_type)">
                {{ account.account_type }}
              </span>
              
              <span v-if="account.child_accounts && account.child_accounts.length > 0" class="child-count">
                ({{ account.child_accounts.length }} children)
              </span>
              
              <span v-if="showBalances && account.base_balance !== undefined" class="account-balance">
                {{ formatCurrency(account.base_balance) }}
                <span v-if="selectedCurrency && account.balance_in_currency !== undefined" class="currency-balance">
                  ({{ formatCurrency(account.balance_in_currency, selectedCurrency) }})
                </span>
              </span>
              
              <span class="account-status" :class="{ active: account.is_active, inactive: !account.is_active }">
                <i :class="account.is_active ? 'fas fa-check-circle' : 'fas fa-times-circle'"></i>
              </span>
              
              <div class="node-actions" @click.stop>
                <button @click="viewAccount(account.account_id)" class="action-btn view">
                  <i class="fas fa-eye"></i>
                </button>
                <button @click="editAccount(account.account_id)" class="action-btn edit">
                  <i class="fas fa-edit"></i>
                </button>
                <button 
                  v-if="showBalances"
                  @click="showBalanceDetails(account)"
                  class="action-btn info"
                >
                  <i class="fas fa-info-circle"></i>
                </button>
                <button @click="deleteAccount(account)" class="action-btn delete" :disabled="hasChildren(account)">
                  <i class="fas fa-trash"></i>
                </button>
              </div>
            </div>
            
            <!-- Children (Simple Recursive Rendering) -->
            <div v-if="account.expanded && account.child_accounts && account.child_accounts.length > 0" class="children">
              <div v-for="child in account.child_accounts" :key="`child-${child.account_id}`" class="child-account">
                <!-- Child Level 1 -->
                <div class="account-header child-header" @click="toggleAccount(child)" :style="{ paddingLeft: '50px' }">
                  <span v-if="child.child_accounts && child.child_accounts.length > 0" class="toggle-icon">
                    <i :class="child.expanded ? 'fas fa-chevron-down' : 'fas fa-chevron-right'"></i>
                  </span>
                  <span v-else class="toggle-spacer"></span>
                  
                  <span class="account-code">{{ child.account_code }}</span>
                  <span class="account-name">{{ child.name }}</span>
                  <span class="account-type" :class="getAccountTypeClass(child.account_type)">
                    {{ child.account_type }}
                  </span>
                  
                  <span v-if="child.child_accounts && child.child_accounts.length > 0" class="child-count">
                    ({{ child.child_accounts.length }} children)
                  </span>
                  
                  <span v-if="showBalances && child.base_balance !== undefined" class="account-balance">
                    {{ formatCurrency(child.base_balance) }}
                  </span>
                  
                  <span class="account-status" :class="{ active: child.is_active, inactive: !child.is_active }">
                    <i :class="child.is_active ? 'fas fa-check-circle' : 'fas fa-times-circle'"></i>
                  </span>
                  
                  <div class="node-actions" @click.stop>
                    <button @click="viewAccount(child.account_id)" class="action-btn view">
                      <i class="fas fa-eye"></i>
                    </button>
                    <button @click="editAccount(child.account_id)" class="action-btn edit">
                      <i class="fas fa-edit"></i>
                    </button>
                    <button @click="deleteAccount(child)" class="action-btn delete">
                      <i class="fas fa-trash"></i>
                    </button>
                  </div>
                </div>
                
                <!-- Children Level 2 -->
                <div v-if="child.expanded && child.child_accounts && child.child_accounts.length > 0" class="children">
                  <div v-for="grandchild in child.child_accounts" :key="`grandchild-${grandchild.account_id}`" class="child-account">
                    <div class="account-header child-header" @click="toggleAccount(grandchild)" :style="{ paddingLeft: '80px' }">
                      <span v-if="grandchild.child_accounts && grandchild.child_accounts.length > 0" class="toggle-icon">
                        <i :class="grandchild.expanded ? 'fas fa-chevron-down' : 'fas fa-chevron-right'"></i>
                      </span>
                      <span v-else class="toggle-spacer"></span>
                      
                      <span class="account-code">{{ grandchild.account_code }}</span>
                      <span class="account-name">{{ grandchild.name }}</span>
                      <span class="account-type" :class="getAccountTypeClass(grandchild.account_type)">
                        {{ grandchild.account_type }}
                      </span>
                      
                      <span class="account-status" :class="{ active: grandchild.is_active, inactive: !grandchild.is_active }">
                        <i :class="grandchild.is_active ? 'fas fa-check-circle' : 'fas fa-times-circle'"></i>
                      </span>
                      
                      <div class="node-actions" @click.stop>
                        <button @click="viewAccount(grandchild.account_id)" class="action-btn view">
                          <i class="fas fa-eye"></i>
                        </button>
                        <button @click="editAccount(grandchild.account_id)" class="action-btn edit">
                          <i class="fas fa-edit"></i>
                        </button>
                        <button @click="deleteAccount(grandchild)" class="action-btn delete">
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

        <!-- Show all accounts as flat list if needed -->
        <div v-if="showAllAsFlat && hierarchyAccounts.length > 0" class="flat-hierarchy">
          <h4>All Accounts (Flat View) - {{ hierarchyAccounts.length }} accounts</h4>
          <div v-for="account in hierarchyAccounts.slice(0, 20)" :key="`flat-${account.account_id}`" class="flat-account">
            <span class="account-code">{{ account.account_code }}</span>
            <span class="account-name">{{ account.name }}</span>
            <span class="account-type">{{ account.account_type }}</span>
            <span class="parent-info" v-if="account.parent_account_id">
              (Parent: {{ account.parent_account_id }})
            </span>
          </div>
          <p v-if="hierarchyAccounts.length > 20">... and {{ hierarchyAccounts.length - 20 }} more accounts</p>
        </div>
      </div>

      <!-- List View -->
      <div v-else class="list-view">
        <div class="accounts-table">
          <table class="data-table">
            <thead>
              <tr>
                <th>Code</th>
                <th>Name</th>
                <th>Type</th>
                <th>Parent</th>
                <th v-if="showBalances">Balance</th>
                <th v-if="showBalances && selectedCurrency">{{ selectedCurrency }} Balance</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr 
                v-for="account in filteredAccounts" 
                :key="account.account_id"
                :class="{ inactive: !account.is_active }"
              >
                <td class="account-code">{{ account.account_code }}</td>
                <td class="account-name">{{ account.name }}</td>
                <td>
                  <span class="account-type" :class="getAccountTypeClass(account.account_type)">
                    {{ account.account_type }}
                  </span>
                </td>
                <td class="parent-account">
                  <router-link 
                    v-if="account.parent_account"
                    :to="`/accounting/chart-of-accounts/${account.parent_account.account_id}`"
                    class="parent-link"
                  >
                    {{ account.parent_account.account_code }}
                  </router-link>
                  <span v-else class="no-parent">-</span>
                </td>
                <td v-if="showBalances" class="balance-cell">
                  <span v-if="account.base_balance !== undefined" class="balance-amount">
                    {{ formatCurrency(account.base_balance) }}
                  </span>
                  <span v-else class="balance-loading">Loading...</span>
                </td>
                <td v-if="showBalances && selectedCurrency" class="balance-cell">
                  <span v-if="account.balance_in_currency !== undefined" class="balance-amount">
                    {{ formatCurrency(account.balance_in_currency, selectedCurrency) }}
                  </span>
                  <button 
                    v-else-if="account.base_balance !== undefined"
                    @click="convertBalance(account)"
                    class="btn btn-sm btn-outline"
                  >
                    Convert
                  </button>
                </td>
                <td>
                  <span class="status-badge" :class="{ active: account.is_active, inactive: !account.is_active }">
                    <i :class="account.is_active ? 'fas fa-check-circle' : 'fas fa-times-circle'"></i>
                    {{ account.is_active ? 'Active' : 'Inactive' }}
                  </span>
                </td>
                <td class="actions-cell">
                  <div class="action-buttons">
                    <button @click="viewAccount(account.account_id)" class="action-btn view">
                      <i class="fas fa-eye"></i>
                    </button>
                    <button @click="editAccount(account.account_id)" class="action-btn edit">
                      <i class="fas fa-edit"></i>
                    </button>
                    <button 
                      @click="deleteAccount(account)" 
                      class="action-btn delete"
                      :disabled="hasChildren(account) || hasTransactions(account)"
                    >
                      <i class="fas fa-trash"></i>
                    </button>
                    <button 
                      v-if="showBalances"
                      @click="showBalanceDetails(account)"
                      class="action-btn info"
                    >
                      <i class="fas fa-info-circle"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Trial Balance Modal -->
    <div v-if="showTrialBalanceModal" class="modal-overlay" @click="closeTrialBalance">
      <div class="modal-content trial-balance-modal" @click.stop>
        <div class="modal-header">
          <h2>
            <i class="fas fa-balance-scale"></i>
            Trial Balance
          </h2>
          <button @click="closeTrialBalance" class="close-btn">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <div class="trial-balance-controls">
            <div class="control-group">
              <label>Currency:</label>
              <select v-model="trialBalanceCurrency" class="form-select">
                <option value="">Base Currency</option>
                <option v-for="currency in availableCurrencies" :key="currency.code" :value="currency.code">
                  {{ currency.code }} - {{ currency.name }}
                </option>
              </select>
            </div>
            <div class="control-group">
              <label>As of Date:</label>
              <input v-model="trialBalanceDate" type="date" class="form-input">
            </div>
            <button @click="loadTrialBalance" class="btn btn-primary">
              Generate
            </button>
          </div>
          <div v-if="trialBalanceLoading" class="loading-container">
            <div class="loading-spinner"></div>
            <p>Generating trial balance...</p>
          </div>
          <div v-else-if="trialBalanceData" class="trial-balance-table">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Account Code</th>
                  <th>Account Name</th>
                  <th>Debit</th>
                  <th>Credit</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in trialBalanceData.accounts" :key="item.account_id">
                  <td>{{ item.account_code }}</td>
                  <td>{{ item.account_name }}</td>
                  <td class="amount">{{ item.debit > 0 ? formatCurrency(item.debit, trialBalanceCurrency) : '-' }}</td>
                  <td class="amount">{{ item.credit > 0 ? formatCurrency(item.credit, trialBalanceCurrency) : '-' }}</td>
                </tr>
              </tbody>
              <tfoot>
                <tr class="total-row">
                  <td colspan="2"><strong>Total</strong></td>
                  <td class="amount"><strong>{{ formatCurrency(trialBalanceData.total_debit, trialBalanceCurrency) }}</strong></td>
                  <td class="amount"><strong>{{ formatCurrency(trialBalanceData.total_credit, trialBalanceCurrency) }}</strong></td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Balance Details Modal -->
    <div v-if="showBalanceModal" class="modal-overlay" @click="closeBalanceDetails">
      <div class="modal-content balance-modal" @click.stop>
        <div class="modal-header">
          <h2>
            <i class="fas fa-money-bill-wave"></i>
            Balance Details - {{ selectedAccount?.account_code }}
          </h2>
          <button @click="closeBalanceDetails" class="close-btn">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <div v-if="balanceDetailsLoading" class="loading-container">
            <div class="loading-spinner"></div>
            <p>Loading balance details...</p>
          </div>
          <div v-else-if="balanceDetails" class="balance-details">
            <div class="balance-grid">
              <div class="balance-item" v-for="balance in balanceDetails.balances" :key="balance.currency">
                <div class="currency-code">{{ balance.currency }}</div>
                <div class="balance-amount">{{ formatCurrency(balance.balance, balance.currency) }}</div>
                <div class="exchange-info" v-if="balance.exchange_rate !== 1">
                  Rate: {{ balance.exchange_rate }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div v-if="showDeleteModal" class="modal-overlay" @click="closeDeleteModal">
      <div class="modal-content delete-modal" @click.stop>
        <div class="modal-header">
          <h2>
            <i class="fas fa-exclamation-triangle"></i>
            Confirm Delete
          </h2>
        </div>
        <div class="modal-body">
          <p>Are you sure you want to delete this account?</p>
          <div class="account-info">
            <strong>{{ accountToDelete?.account_code }} - {{ accountToDelete?.name }}</strong>
          </div>
          <div class="warning-text">
            This action cannot be undone.
          </div>
        </div>
        <div class="modal-actions">
          <button @click="closeDeleteModal" class="btn btn-secondary">Cancel</button>
          <button @click="confirmDelete" class="btn btn-danger" :disabled="deleting">
            <i v-if="deleting" class="fas fa-spinner fa-spin"></i>
            <span v-else>Delete Account</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'ChartOfAccountsList',
  data() {
    return {
      accounts: [], // Original data from API
      hierarchyAccounts: [], // Data dengan struktur hierarchy untuk tree view
      filteredAccounts: [], // Data yang sudah difilter untuk list view
      availableCurrencies: [],
      loading: false,
      error: null,
      searchTerm: '',
      selectedType: '',
      selectedStatus: '',
      selectedCurrency: '',
      asOfDate: new Date().toISOString().split('T')[0],
      showBalances: false,
      showCurrencyControls: false,
      viewMode: 'hierarchy', // 'hierarchy' or 'list'
      showDebug: false, // For debugging
      showAllAsFlat: false, // Fallback when no root accounts
      
      // Statistics
      stats: {
        assets: 0,
        liabilities: 0,
        equity: 0,
        revenue: 0,
        expenses: 0
      },

      // Trial Balance
      showTrialBalanceModal: false,
      trialBalanceCurrency: '',
      trialBalanceDate: new Date().toISOString().split('T')[0],
      trialBalanceData: null,
      trialBalanceLoading: false,

      // Balance Details
      showBalanceModal: false,
      selectedAccount: null,
      balanceDetails: null,
      balanceDetailsLoading: false,

      // Delete Confirmation
      showDeleteModal: false,
      accountToDelete: null,
      deleting: false
    };
  },
  computed: {
    rootAccounts() {
      console.log('🔍 Computing root accounts...', {
        viewMode: this.viewMode,
        hierarchyAccountsLength: this.hierarchyAccounts.length,
        accountsLength: this.accounts.length
      });
      
      if (this.viewMode === 'hierarchy' && this.hierarchyAccounts.length > 0) {
        const roots = this.hierarchyAccounts.filter(account => {
          const isRoot = !account.parent_account_id || account.parent_account_id === null;
          if (isRoot) {
            console.log(`✅ Root account: ${account.account_code} (${account.name}) - children: ${account.child_accounts?.length || 0}`);
            // Debug children structure
            if (account.child_accounts && account.child_accounts.length > 0) {
              console.log(`   Children:`, account.child_accounts.map(child => `${child.account_code} (${child.name})`));
            }
          }
          return isRoot;
        });
        
        console.log(`📊 Total root accounts found: ${roots.length}`);
        return roots;
      }
      
      console.log('❌ No root accounts - wrong view mode or no hierarchy data');
      return [];
    }
  },
  async mounted() {
    console.log('🚀 Component mounted, loading data...');
    await this.loadAvailableCurrencies();
    await this.loadAccounts();
  },
  methods: {
    async loadAccounts() {
      console.log('📥 Loading accounts...');
      this.loading = true;
      this.error = null;
      
      try {
        let response;
        let url = '';
        
        // Fix URL paths - add prefix
        if (this.showBalances && this.selectedCurrency) {
          url = '/accounting/chart-of-accounts/hierarchy/currencies';
          console.log('Loading with currency balances:', url);
          response = await axios.get(url, {
            params: {
              currency: this.selectedCurrency,
              as_of_date: this.asOfDate
            }
          });
          this.accounts = response.data.data?.accounts || response.data.data || [];
        } else if (this.showBalances) {
          url = '/accounting/chart-of-accounts/hierarchy';
          console.log('Loading with base currency balances:', url);
          response = await axios.get(url);
          this.accounts = response.data.data || [];
        } else {
          url = '/accounting/chart-of-accounts';
          console.log('Loading basic accounts:', url);
          response = await axios.get(url);
          this.accounts = response.data.data || [];
        }

        console.log('📨 API Response received:', {
          status: response.status,
          dataLength: this.accounts.length
        });
        console.log('📋 First few accounts:', this.accounts.slice(0, 3));

        // Process and build hierarchy
        this.processAccountsData();
        this.buildHierarchy();
        this.calculateStats();
        this.applyFilters();
        
        console.log('🏁 Account processing completed');
        
      } catch (error) {
        console.error('❌ Error loading accounts:', error);
        this.error = error.response?.data?.message || error.message || 'Failed to load chart of accounts';
      } finally {
        this.loading = false;
      }
    },

    processAccountsData() {
      console.log('🔧 Processing accounts data...');
      // Ensure all accounts have required properties
      this.accounts = this.accounts.map(account => ({
        ...account,
        child_accounts: account.child_accounts || [],
        is_active: account.is_active !== false, // Default to true if undefined
        parent_account_id: account.parent_account_id || null,
        expanded: false // Add expanded state for tree view
      }));
      
      console.log(`✅ Processed ${this.accounts.length} accounts`);
      console.log('Sample processed account:', this.accounts[0]);
    },

    async loadAccountsWithCurrency() {
      console.log('Loading accounts with currency changes...');
      if (this.showBalances) {
        await this.loadAccounts();
      }
    },

    async loadAvailableCurrencies() {
      try {
        console.log('Loading available currencies...');
        const response = await axios.get('/accounting/chart-of-accounts/currencies/available');
        this.availableCurrencies = response.data.data || [];
        console.log('Currencies loaded:', this.availableCurrencies.length);
      } catch (error) {
        console.error('Error loading currencies:', error);
        // Don't fail completely if currencies can't be loaded
        this.availableCurrencies = [];
      }
    },

    buildHierarchy() {
      console.log('🏗️ Building hierarchy...');
      
      // Create deep copy
      const accountsCopy = JSON.parse(JSON.stringify(this.accounts));
      
      // Create map
      const accountMap = new Map();
      accountsCopy.forEach(account => {
        accountMap.set(account.account_id, { 
          ...account, 
          child_accounts: [],
          expanded: false
        });
      });

      console.log(`📋 Account map created with ${accountMap.size} entries`);

      // Build parent-child relationships
      let relationshipsBuilt = 0;
      accountsCopy.forEach(account => {
        if (account.parent_account_id) {
          const parent = accountMap.get(account.parent_account_id);
          const child = accountMap.get(account.account_id);
          
          if (parent && child) {
            parent.child_accounts.push(child);
            relationshipsBuilt++;
            console.log(`🔗 Added ${child.account_code} as child of ${parent.account_code}`);
          } else {
            console.warn(`⚠️ Parent-child relationship issue: parent_id=${account.parent_account_id}, account_id=${account.account_id}`);
          }
        }
      });

      this.hierarchyAccounts = Array.from(accountMap.values());
      console.log(`✅ Hierarchy built: ${this.hierarchyAccounts.length} accounts, ${relationshipsBuilt} relationships`);
      
      // Debug: Show hierarchy structure
      const rootCount = this.hierarchyAccounts.filter(acc => !acc.parent_account_id).length;
      console.log(`🌳 Root accounts in hierarchy: ${rootCount}`);
      
      // Debug: Show a few root accounts with their children
      this.hierarchyAccounts.filter(acc => !acc.parent_account_id).slice(0, 3).forEach(root => {
        console.log(`🌳 Root: ${root.account_code} (${root.name}) - ${root.child_accounts.length} children`);
        root.child_accounts.forEach(child => {
          console.log(`   📁 Child: ${child.account_code} (${child.name})`);
        });
      });
    },

    calculateStats() {
      console.log('📊 Calculating statistics...');
      
      const accountTypeMapping = {
        'CURRENT ASSETS': 'assets',
        'FIXED ASSETS': 'assets',
        'OTHER ASSETS': 'assets',
        'CURRENT LIABILITIES': 'liabilities',
        'LONG TERM LIABILITIES': 'liabilities',
        'CAPITAL': 'equity',
        'RETAINED EARNING': 'equity',
        'SALES': 'revenue',
        'OTHER INCOME': 'revenue',
        'COST OF GOODS SOLD': 'expenses',
        'EXPENSE': 'expenses',
        'OTHER EXPENSES': 'expenses',
        'TAXATION': 'expenses'
      };

      this.stats = {
        assets: 0,
        liabilities: 0,
        equity: 0,
        revenue: 0,
        expenses: 0
      };

      this.accounts.forEach(account => {
        const statType = accountTypeMapping[account.account_type];
        if (statType) {
          this.stats[statType]++;
        }
      });

      console.log('📈 Stats calculated:', this.stats);
    },

    applyFilters() {
      console.log('🔍 Applying filters...', {
        searchTerm: this.searchTerm,
        selectedType: this.selectedType,
        selectedStatus: this.selectedStatus
      });
      
      let filtered = [...this.accounts];

      if (this.searchTerm) {
        const term = this.searchTerm.toLowerCase();
        filtered = filtered.filter(account => 
          account.account_code.toLowerCase().includes(term) ||
          account.name.toLowerCase().includes(term)
        );
      }

      if (this.selectedType) {
        filtered = filtered.filter(account => account.account_type === this.selectedType);
      }

      if (this.selectedStatus) {
        const isActive = this.selectedStatus === 'active';
        filtered = filtered.filter(account => account.is_active === isActive);
      }

      this.filteredAccounts = filtered;
      
      // Rebuild hierarchy with filters if needed
      if (this.viewMode === 'hierarchy' && (this.searchTerm || this.selectedType || this.selectedStatus)) {
        this.applyHierarchyFilters();
      }
      
      console.log(`✅ Filters applied: ${this.filteredAccounts.length} accounts remaining`);
    },

    applyHierarchyFilters() {
      console.log('🌳 Applying hierarchy filters...');
      
      // Use filtered accounts to rebuild hierarchy
      const filteredForHierarchy = [...this.filteredAccounts];
      
      const accountMap = new Map();
      filteredForHierarchy.forEach(account => {
        accountMap.set(account.account_id, { 
          ...account, 
          child_accounts: [],
          expanded: false
        });
      });

      filteredForHierarchy.forEach(account => {
        if (account.parent_account_id) {
          const parent = accountMap.get(account.parent_account_id);
          const child = accountMap.get(account.account_id);
          
          if (parent && child) {
            parent.child_accounts.push(child);
          }
        }
      });

      this.hierarchyAccounts = Array.from(accountMap.values());
      console.log(`🔄 Hierarchy filters applied: ${this.hierarchyAccounts.length} accounts in filtered hierarchy`);
    },

    handleSearch() {
      console.log('🔍 Search triggered:', this.searchTerm);
      this.applyFilters();
    },

    toggleView() {
      this.viewMode = this.viewMode === 'hierarchy' ? 'list' : 'hierarchy';
      console.log(`🔄 View mode changed to: ${this.viewMode}`);
      
      // Reset hierarchy when switching to tree view
      if (this.viewMode === 'hierarchy') {
        this.buildHierarchy();
      }
      
      this.applyFilters();
    },

    toggleDebug() {
      this.showDebug = !this.showDebug;
      console.log('🐛 Debug mode:', this.showDebug);
    },

    clearFilters() {
      console.log('🧹 Clearing filters...');
      this.searchTerm = '';
      this.selectedType = '';
      this.selectedStatus = '';
      this.showAllAsFlat = false;
      
      if (this.viewMode === 'hierarchy') {
        this.buildHierarchy();
      }
      this.applyFilters();
    },

    toggleCurrencyControls() {
      this.showCurrencyControls = !this.showCurrencyControls;
      if (!this.showCurrencyControls) {
        this.showBalances = false;
        this.selectedCurrency = '';
        this.loadAccounts();
      }
    },

    // Tree View Methods - FIXED TOGGLE
    toggleAccount(account) {
      console.log('🔄 Toggling account:', account.account_code, 'current expanded:', account.expanded);
      
      // Find the account in hierarchyAccounts and toggle it
      const findAndToggle = (accounts) => {
        for (let acc of accounts) {
          if (acc.account_id === account.account_id) {
            acc.expanded = !acc.expanded;
            console.log(`✅ Toggled ${acc.account_code} to expanded: ${acc.expanded}`);
            return true;
          }
          if (acc.child_accounts && acc.child_accounts.length > 0) {
            if (findAndToggle(acc.child_accounts)) {
              return true;
            }
          }
        }
        return false;
      };

      // Toggle in hierarchyAccounts
      findAndToggle(this.hierarchyAccounts);
      
      // Force Vue to update the DOM
      this.$forceUpdate();
    },

    async refreshData() {
      console.log('🔄 Refreshing data...');
      await this.loadAccounts();
    },

    viewAccount(accountId) {
      console.log('👁️ Viewing account:', accountId);
      this.$router.push(`/accounting/chart-of-accounts/${accountId}`);
    },

    editAccount(accountId) {
      console.log('✏️ Editing account:', accountId);
      this.$router.push(`/accounting/chart-of-accounts/${accountId}/edit`);
    },

    deleteAccount(account) {
      this.accountToDelete = account;
      this.showDeleteModal = true;
    },

    async confirmDelete() {
      if (!this.accountToDelete) return;

      this.deleting = true;
      try {
        await axios.delete(`/accounting/chart-of-accounts/${this.accountToDelete.account_id}`);
        await this.loadAccounts();
        this.closeDeleteModal();
        this.$toast?.success('Account deleted successfully');
      } catch (error) {
        console.error('Error deleting account:', error);
        this.$toast?.error(error.response?.data?.message || 'Failed to delete account');
      } finally {
        this.deleting = false;
      }
    },

    closeDeleteModal() {
      this.showDeleteModal = false;
      this.accountToDelete = null;
      this.deleting = false;
    },

    async convertBalance(account) {
      if (!this.selectedCurrency || !account.base_balance) return;

      try {
        const response = await axios.post('/accounting/chart-of-accounts/convert-balance', {
          account_id: account.account_id,
          from_currency: 'USD',
          to_currency: this.selectedCurrency,
          amount: account.base_balance,
          exchange_date: this.asOfDate
        });

        account.balance_in_currency = response.data.data.converted_amount;
        account.exchange_rate = response.data.data.exchange_rate;
      } catch (error) {
        console.error('Error converting balance:', error);
        this.$toast?.error('Failed to convert balance');
      }
    },

    async showBalanceDetails(account) {
      this.selectedAccount = account;
      this.showBalanceModal = true;
      this.balanceDetailsLoading = true;

      try {
        const response = await axios.get(`/accounting/chart-of-accounts/${account.account_id}/balances-in-currencies`, {
          params: {
            as_of_date: this.asOfDate,
            currencies: this.availableCurrencies.map(c => c.code)
          }
        });
        this.balanceDetails = response.data.data;
      } catch (error) {
        console.error('Error loading balance details:', error);
      } finally {
        this.balanceDetailsLoading = false;
      }
    },

    closeBalanceDetails() {
      this.showBalanceModal = false;
      this.selectedAccount = null;
      this.balanceDetails = null;
    },

    showTrialBalance() {
      this.showTrialBalanceModal = true;
    },

    async loadTrialBalance() {
      this.trialBalanceLoading = true;
      try {
        const response = await axios.get('/accounting/chart-of-accounts/trial-balance/currency', {
          params: {
            currency: this.trialBalanceCurrency,
            as_of_date: this.trialBalanceDate
          }
        });
        this.trialBalanceData = response.data.data;
      } catch (error) {
        console.error('Error loading trial balance:', error);
        this.$toast?.error('Failed to load trial balance');
      } finally {
        this.trialBalanceLoading = false;
      }
    },

    closeTrialBalance() {
      this.showTrialBalanceModal = false;
      this.trialBalanceData = null;
    },

    hasChildren(account) {
      return account.child_accounts && account.child_accounts.length > 0;
    },

    hasTransactions() {
      return false;
    },

    formatCurrency(amount, currency = 'USD') {
      if (amount === undefined || amount === null) return '-';
      const formatter = new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: currency,
        minimumFractionDigits: 2
      });
      return formatter.format(amount);
    },

    getAccountTypeClass(type) {
      if (!type) return '';
      return type.toLowerCase().replace(/\s+/g, '-');
    }
  }
};
</script>

<style scoped>
/* Base Styles */
.chart-accounts-page {
  padding: 1rem;
  background: var(--bg-primary, #f8fafc);
  min-height: 100vh;
}

/* Debug Info */
.debug-info {
  background: #fff3cd;
  border: 1px solid #ffeaa7;
  border-radius: 8px;
  padding: 1rem;
  margin-bottom: 1rem;
  font-family: monospace;
  font-size: 0.875rem;
}

.debug-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 0.5rem;
  margin-bottom: 1rem;
}

.debug-btn {
  background: #6c757d !important;
  color: white !important;
}

/* No Data States */
.no-data-container,
.no-root-accounts {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem;
  text-align: center;
  background: var(--white, #ffffff);
  border-radius: var(--border-radius, 8px);
  box-shadow: var(--box-shadow, 0 1px 3px rgba(0, 0, 0, 0.1));
}

.no-data-container i,
.no-root-accounts i {
  font-size: 3rem;
  color: var(--text-secondary, #6b7280);
  margin-bottom: 1rem;
  opacity: 0.5;
}

.no-data-container h3,
.no-root-accounts h3,
.no-root-accounts h4 {
  margin: 0 0 0.5rem 0;
  color: var(--text-primary, #1f2937);
}

.no-data-container p,
.no-root-accounts p {
  margin: 0 0 1.5rem 0;
  color: var(--text-secondary, #6b7280);
}

.no-data-actions {
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
  justify-content: center;
}

/* Page Header */
.page-header {
  background: var(--white, #ffffff);
  border-radius: var(--border-radius, 8px);
  box-shadow: var(--box-shadow, 0 1px 3px rgba(0, 0, 0, 0.1));
  margin-bottom: 1.5rem;
  position: sticky;
  top: 1rem;
  z-index: 100;
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
}

.title-section {
  flex: 1;
}

.page-title {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin: 0 0 0.5rem 0;
  color: var(--text-primary, #1f2937);
  font-size: 1.75rem;
  font-weight: 600;
}

.page-subtitle {
  color: var(--text-secondary, #6b7280);
  margin: 0;
  font-size: 0.875rem;
}

.header-actions {
  display: flex;
  gap: 0.75rem;
  align-items: center;
}

/* Currency Controls */
.currency-section {
  background: var(--white, #ffffff);
  border-radius: var(--border-radius, 8px);
  box-shadow: var(--box-shadow, 0 1px 3px rgba(0, 0, 0, 0.1));
  margin-bottom: 1.5rem;
  padding: 1rem;
}

.currency-controls {
  display: flex;
  gap: 1rem;
  align-items: end;
  flex-wrap: wrap;
}

.currency-selector,
.date-selector,
.balance-toggle {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.currency-selector label,
.date-selector label {
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--text-primary, #1f2937);
}

.toggle-option {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
}

/* Filters */
.filters-section {
  background: var(--white, #ffffff);
  border-radius: var(--border-radius, 8px);
  box-shadow: var(--box-shadow, 0 1px 3px rgba(0, 0, 0, 0.1));
  margin-bottom: 1.5rem;
  padding: 1rem;
}

.search-filters {
  display: flex;
  gap: 1rem;
  align-items: center;
  flex-wrap: wrap;
}

.search-box {
  position: relative;
  flex: 1;
  min-width: 300px;
}

.search-box i {
  position: absolute;
  left: 1rem;
  top: 50%;
  transform: translateY(-50%);
  color: var(--text-secondary, #6b7280);
}

.search-box input {
  width: 100%;
  padding: 0.75rem 1rem 0.75rem 2.5rem;
  border: 2px solid var(--border-color, #e5e7eb);
  border-radius: var(--border-radius, 8px);
  font-size: 0.875rem;
}

.filter-group {
  display: flex;
  gap: 0.75rem;
  align-items: center;
}

.filter-select {
  padding: 0.75rem;
  border: 2px solid var(--border-color, #e5e7eb);
  border-radius: var(--border-radius, 8px);
  font-size: 0.875rem;
  min-width: 140px;
}

/* Statistics Grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.stat-card {
  background: var(--white, #ffffff);
  border-radius: var(--border-radius, 8px);
  box-shadow: var(--box-shadow, 0 1px 3px rgba(0, 0, 0, 0.1));
  padding: 1.25rem;
  display: flex;
  align-items: center;
  gap: 1rem;
}

.stat-icon {
  width: 48px;
  height: 48px;
  border-radius: var(--border-radius, 8px);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  color: white;
}

.stat-icon.asset { background: #3b82f6; }
.stat-icon.liability { background: #ef4444; }
.stat-icon.equity { background: #10b981; }
.stat-icon.revenue { background: #8b5cf6; }
.stat-icon.expense { background: #f59e0b; }
.stat-icon.total { background: #6b7280; }

.stat-info h3 {
  margin: 0;
  font-size: 1.75rem;
  font-weight: 700;
  color: var(--text-primary, #1f2937);
}

.stat-info p {
  margin: 0;
  font-size: 0.875rem;
  color: var(--text-secondary, #6b7280);
}

/* Tree View - FIXED STYLES */
.hierarchy-view {
  background: var(--white, #ffffff);
  border-radius: var(--border-radius, 8px);
  box-shadow: var(--box-shadow, 0 1px 3px rgba(0, 0, 0, 0.1));
  padding: 1rem;
}

.hierarchy-view h3 {
  margin: 0 0 1rem 0;
  color: var(--text-primary, #1f2937);
  border-bottom: 2px solid var(--border-color, #e5e7eb);
  padding-bottom: 0.5rem;
}

.accounts-tree {
  margin-top: 1rem;
}

.simple-account-node {
  margin-bottom: 0.5rem;
  border: 1px solid var(--border-color, #e5e7eb);
  border-radius: var(--border-radius, 8px);
  overflow: hidden;
}

.account-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 0.75rem;
  cursor: pointer;
  transition: background-color 0.2s;
  background: var(--white, #ffffff);
}

.account-header:hover {
  background: var(--bg-secondary, #f1f5f9);
}

.child-header {
  background: #f8fafc;
  border-top: 1px solid var(--border-color, #e5e7eb);
}

.toggle-icon {
  width: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--text-secondary, #6b7280);
  font-size: 0.875rem;
}

.toggle-spacer {
  width: 24px;
}

.account-code {
  font-family: 'Courier New', monospace;
  font-weight: 600;
  min-width: 100px;
  color: var(--text-primary, #1f2937);
}

.account-name {
  flex: 1;
  color: var(--text-primary, #1f2937);
}

.account-type {
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 500;
  text-transform: uppercase;
}

.account-type.current-assets,
.account-type.fixed-assets,
.account-type.other-assets { background: #dbeafe; color: #1e40af; }
.account-type.current-liabilities,
.account-type.long-term-liabilities { background: #fee2e2; color: #dc2626; }
.account-type.capital,
.account-type.retained-earning { background: #d1fae5; color: #065f46; }
.account-type.sales,
.account-type.other-income { background: #e0e7ff; color: #5b21b6; }
.account-type.cost-of-goods-sold,
.account-type.expense,
.account-type.other-expenses,
.account-type.taxation { background: #fef3c7; color: #92400e; }

.child-count {
  font-size: 0.75rem;
  color: var(--text-secondary, #6b7280);
  font-style: italic;
}

.account-balance {
  font-family: 'Courier New', monospace;
  font-weight: 600;
  color: var(--text-primary, #1f2937);
}

.currency-balance {
  color: var(--text-secondary, #6b7280);
  font-size: 0.875rem;
}

.account-status {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.875rem;
}

.account-status.active { color: #059669; }
.account-status.inactive { color: #dc2626; }

.node-actions {
  display: flex;
  gap: 0.25rem;
}

/* CRITICAL: Ensure children are visible */
.children {
  /* Remove any hidden/overflow styles */
  display: block !important;
  visibility: visible !important;
  opacity: 1 !important;
  height: auto !important;
  border-top: 1px solid var(--border-color, #e5e7eb);
}

.child-account {
  display: block !important;
  visibility: visible !important;
}

.flat-hierarchy {
  margin-top: 2rem;
  padding: 1rem;
  background: #f8f9fa;
  border-radius: 8px;
}

.flat-hierarchy h4 {
  margin: 0 0 1rem 0;
  color: var(--text-primary, #1f2937);
}

.flat-account {
  display: flex;
  gap: 1rem;
  padding: 0.5rem;
  margin-bottom: 0.5rem;
  background: var(--white, #ffffff);
  border-radius: 4px;
  border: 1px solid var(--border-color, #e5e7eb);
}

.parent-info {
  font-size: 0.75rem;
  color: var(--text-secondary, #6b7280);
  font-style: italic;
}

/* List View */
.list-view {
  background: var(--white, #ffffff);
  border-radius: var(--border-radius, 8px);
  box-shadow: var(--box-shadow, 0 1px 3px rgba(0, 0, 0, 0.1));
  overflow: hidden;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
}

.data-table th,
.data-table td {
  padding: 1rem;
  text-align: left;
  border-bottom: 1px solid var(--border-color, #e5e7eb);
}

.data-table th {
  background: var(--bg-secondary, #f1f5f9);
  font-weight: 600;
  color: var(--text-primary, #1f2937);
}

.data-table tr.inactive {
  opacity: 0.6;
}

.account-code {
  font-family: 'Courier New', monospace;
  font-weight: 600;
}

.parent-link {
  color: var(--primary-color, #3b82f6);
  text-decoration: none;
  font-family: 'Courier New', monospace;
  font-size: 0.875rem;
}

.no-parent {
  color: var(--text-secondary, #6b7280);
}

.balance-cell {
  font-family: 'Courier New', monospace;
  font-weight: 600;
  text-align: right;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 500;
}

.status-badge.active {
  background: #d1fae5;
  color: #065f46;
}

.status-badge.inactive {
  background: #fee2e2;
  color: #dc2626;
}

.actions-cell {
  width: 120px;
}

.action-buttons {
  display: flex;
  gap: 0.25rem;
}

.action-btn {
  width: 32px;
  height: 32px;
  border: none;
  border-radius: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: background-color 0.2s;
}

.action-btn.view {
  background: #e0f2fe;
  color: #0277bd;
}

.action-btn.edit {
  background: #fff3e0;
  color: #f57c00;
}

.action-btn.delete {
  background: #ffebee;
  color: #d32f2f;
}

.action-btn.info {
  background: #e8f5e8;
  color: #2e7d32;
}

.action-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Modals */
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
  background: var(--white, #ffffff);
  border-radius: var(--border-radius, 8px);
  box-shadow: var(--box-shadow-lg, 0 10px 25px rgba(0, 0, 0, 0.15));
  max-width: 90vw;
  max-height: 90vh;
  overflow: auto;
}

.trial-balance-modal {
  width: 800px;
}

.balance-modal {
  width: 600px;
}

.delete-modal {
  width: 400px;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-bottom: 1px solid var(--border-color, #e5e7eb);
}

.modal-header h2 {
  margin: 0;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: var(--text-primary, #1f2937);
}

.close-btn {
  background: none;
  border: none;
  font-size: 1.25rem;
  cursor: pointer;
  color: var(--text-secondary, #6b7280);
}

.modal-body {
  padding: 1.5rem;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  padding: 1.5rem;
  border-top: 1px solid var(--border-color, #e5e7eb);
}

/* Trial Balance */
.trial-balance-controls {
  display: flex;
  gap: 1rem;
  align-items: end;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
}

.control-group {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.control-group label {
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--text-primary, #1f2937);
}

.trial-balance-table .amount {
  text-align: right;
  font-family: 'Courier New', monospace;
}

.total-row {
  background: var(--bg-secondary, #f1f5f9);
  font-weight: 600;
}

/* Balance Details */
.balance-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}

.balance-item {
  padding: 1rem;
  border: 1px solid var(--border-color, #e5e7eb);
  border-radius: var(--border-radius, 8px);
  text-align: center;
}

.currency-code {
  font-weight: 600;
  font-size: 0.875rem;
  color: var(--text-secondary, #6b7280);
  margin-bottom: 0.5rem;
}

.balance-amount {
  font-size: 1.25rem;
  font-weight: 700;
  font-family: 'Courier New', monospace;
  color: var(--text-primary, #1f2937);
}

.exchange-info {
  font-size: 0.75rem;
  color: var(--text-secondary, #6b7280);
  margin-top: 0.25rem;
}

/* Form Elements */
.form-input,
.form-select {
  padding: 0.75rem;
  border: 2px solid var(--border-color, #e5e7eb);
  border-radius: var(--border-radius, 8px);
  font-size: 0.875rem;
  transition: border-color 0.2s;
}

.form-input:focus,
.form-select:focus {
  outline: none;
  border-color: var(--primary-color, #3b82f6);
}

/* Buttons */
.btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  border: none;
  border-radius: var(--border-radius, 8px);
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  text-decoration: none;
}

.btn-primary {
  background: var(--primary-color, #3b82f6);
  color: white;
}

.btn-secondary {
  background: var(--bg-secondary, #f1f5f9);
  color: var(--text-primary, #1f2937);
}

.btn-outline {
  background: transparent;
  border: 1px solid var(--border-color, #e5e7eb);
  color: var(--text-primary, #1f2937);
}

.btn-info {
  background: #06b6d4;
  color: white;
}

.btn-danger {
  background: #dc2626;
  color: white;
}

.btn-sm {
  padding: 0.5rem 0.75rem;
  font-size: 0.75rem;
}

.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Loading States */
.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem;
  color: var(--text-secondary, #6b7280);
}

.loading-spinner {
  width: 40px;
  height: 40px;
  border: 3px solid var(--border-color, #e5e7eb);
  border-top-color: var(--primary-color, #3b82f6);
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 1rem;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Error States */
.error-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem;
  text-align: center;
}

.error-container i {
  font-size: 3rem;
  color: #dc2626;
  margin-bottom: 1rem;
}

.error-container h3 {
  margin: 0 0 0.5rem 0;
  color: var(--text-primary, #1f2937);
}

.error-container p {
  margin: 0 0 1.5rem 0;
  color: var(--text-secondary, #6b7280);
}

/* Responsive Design */
@media (max-width: 1024px) {
  .chart-accounts-page {
    padding: 0.5rem;
  }

  .header-content {
    flex-direction: column;
    align-items: stretch;
    gap: 1rem;
  }

  .header-actions {
    justify-content: center;
  }

  .currency-controls,
  .search-filters {
    flex-direction: column;
    align-items: stretch;
  }

  .stats-grid {
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  }
}

@media (max-width: 768px) {
  .page-title {
    font-size: 1.5rem;
  }

  .modal-content {
    margin: 1rem;
    width: auto !important;
  }

  .data-table {
    font-size: 0.875rem;
  }

  .data-table th,
  .data-table td {
    padding: 0.75rem 0.5rem;
  }

  .account-header {
    flex-wrap: wrap;
    gap: 0.5rem;
  }
}
</style>