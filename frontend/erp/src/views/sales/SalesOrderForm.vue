<!-- src/views/sales/SalesOrderForm.vue -->
<template>
    <div class="order-form">
        <div class="page-header">
            <h1>{{ isEditMode ? "Edit Order" : "Create New Order" }}</h1>
            <div class="page-actions">
                <button class="btn btn-secondary" @click="goBack">
                    <i class="fas fa-arrow-left"></i> Back
                </button>
                <button
                    class="btn btn-primary"
                    @click="saveOrder"
                    :disabled="isSubmitting"
                >
                    <i class="fas fa-save"></i>
                    {{ isSubmitting ? "Saving..." : "Save" }}
                </button>
            </div>
        </div>

        <div v-if="error" class="alert alert-danger">
            {{ error }}
        </div>

        <div class="form-container">
            <div class="form-card">
                <div class="card-header">
                    <h2>Order Information</h2>
                </div>
                <div class="card-body">
                    <div class="form-row">
                        <!-- Sales Order Number - Show readonly in edit mode -->
                        <div class="form-group" v-if="isEditMode">
                            <label for="so_number">Order Number*</label>
                            <input
                                type="text"
                                id="so_number"
                                v-model="form.so_number"
                                required
                                readonly
                                class="form-control readonly"
                            />
                            <small class="text-muted">
                                Auto-generated order number (cannot be changed)
                            </small>
                        </div>

                        <!-- Show next number preview in create mode -->
                        <div class="form-group" v-if="!isEditMode">
                            <label for="so_number">Order Number*</label>
                            <div class="form-control-static">
                                <span class="badge badge-info">{{ nextSoNumber || 'Loading...' }}</span>
                            </div>
                            <small class="text-muted">
                                Auto-generated number (will be assigned when saved)
                            </small>
                        </div>

                        <div class="form-group">
                            <label for="po_number_customer">Customer PO Number</label>
                            <input
                                type="text"
                                id="po_number_customer"
                                v-model="form.po_number_customer"
                                placeholder="Enter customer's PO number"
                                maxlength="100"
                            />
                            <small class="text-muted">
                                Customer's Purchase Order number (optional)
                            </small>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="so_date">Order Date*</label>
                            <input
                                type="date"
                                id="so_date"
                                v-model="form.so_date"
                                required
                            />
                        </div>

                        <div class="form-group">
                            <label for="expected_delivery">Expected Delivery</label>
                            <input
                                type="date"
                                id="expected_delivery"
                                v-model="form.expected_delivery"
                            />
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="customer_id">Customer*</label>
                            <div class="dropdown-container">
                                <input
                                    type="text"
                                    id="customer_search"
                                    v-model="customerSearch"
                                    class="form-control"
                                    :class="{ 'is-invalid': !form.customer_id && customerSearch }"
                                    placeholder="Search for a customer..."
                                    @focus="showCustomerDropdown = true"
                                    @input="showCustomerDropdown = true"
                                />
                                <div v-if="showCustomerDropdown" class="dropdown-menu">
                                    <div
                                        v-for="customer in getFilteredCustomers(customerSearch)"
                                        :key="customer.customer_id"
                                        @mousedown="selectCustomer(customer)"
                                        class="dropdown-item"
                                    >
                                        <div class="customer-info">
                                            <div class="customer-name">{{ customer.name }}</div>
                                            <div class="customer-code" v-if="customer.customer_code">{{ customer.customer_code }}</div>
                                            <div class="customer-tax-info" v-if="customer.is_tax_exempt">
                                                <small class="text-warning">
                                                    <i class="fas fa-exclamation-triangle"></i> Tax Exempt
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                    <div v-if="getFilteredCustomers(customerSearch).length === 0" class="dropdown-item text-muted">
                                        No customers found
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="currency_code">Currency Code*</label>
                            <select
                                id="currency_code"
                                v-model="form.currency_code"
                                required
                                :disabled="isLoadingCurrencies"
                                @change="onCurrencyChange"
                            >
                                <option value="">{{ isLoadingCurrencies ? 'Loading currencies...' : 'Select Currency' }}</option>
                                <option 
                                    v-for="currency in activeCurrencies" 
                                    :key="currency.code" 
                                    :value="currency.code"
                                >
                                    {{ currency.code }} - {{ currency.name }} ({{ currency.symbol }})
                                </option>
                            </select>
                            <small class="text-muted" v-if="activeCurrencies.length > 0">
                                {{ activeCurrencies.length }} currencies available
                            </small>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="payment_terms">Payment Terms</label>
                            <input
                                type="text"
                                id="payment_terms"
                                v-model="form.payment_terms"
                                placeholder="Example: 30 days after delivery"
                            />
                        </div>

                        <div class="form-group">
                            <label for="delivery_terms">Delivery Terms</label>
                            <input
                                type="text"
                                id="delivery_terms"
                                v-model="form.delivery_terms"
                                placeholder="Example: Free to buyer's warehouse"
                            />
                        </div>
                    </div>

                    <!-- Tax Configuration Info -->
                    <div class="form-row" v-if="taxConfiguration">
                        <div class="form-group">
                            <div class="tax-config-info">
                                <h6 class="mb-2">
                                    <i class="fas fa-cog me-2"></i>Tax Configuration
                                </h6>
                                <div class="d-flex gap-3">
                                    <small class="text-muted">
                                        <strong>Pricing:</strong> 
                                        {{ taxConfiguration.tax_inclusive_pricing ? 'Tax Inclusive' : 'Tax Exclusive' }}
                                    </small>
                                    <small class="text-muted">
                                        <strong>Rounding:</strong> 
                                        {{ formatRoundingMethod(taxConfiguration.rounding_method) }} 
                                        ({{ taxConfiguration.rounding_precision }} decimal places)
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-row" v-if="isEditMode">
                        <div class="form-group">
                            <label for="status">Status*</label>
                            <select id="status" v-model="form.status" required>
                                <option value="Draft">Draft</option>
                                <option value="Confirmed">Confirmed</option>
                                <option value="Processing">Processing</option>
                                <option value="Shipped">Shipped</option>
                                <option value="Delivered">Delivered</option>
                                <option value="Invoiced" disabled>
                                    Invoiced
                                </option>
                                <option value="Closed" disabled>Closed</option>
                            </select>
                            <small
                                v-if="
                                    form.status === 'Invoiced' ||
                                    form.status === 'Closed'
                                "
                                class="text-muted"
                            >
                               Status cannot be changed because it is already
                                {{
                                    form.status === "Invoiced"
                                        ? "invoiced"
                                        : "closed"
                                }}
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-card">
                <div class="card-header">
                    <h2>Item Order</h2>
                    <button
                        type="button"
                        class="btn btn-sm btn-primary"
                        @click="addLine"
                    >
                        <i class="fas fa-plus"></i> Add Item
                    </button>
                </div>
                <div class="card-body">
                    <div v-if="form.lines.length === 0" class="empty-lines">
                        <p>
                            No items added yet. Click "Add Item" to add an item.
                        </p>
                    </div>

                    <div v-else class="order-lines">
                        <div class="order-currency-info" v-if="form.currency_code !== 'IDR'">
                            <i class="fas fa-info-circle"></i>
                            All prices in <strong>{{ form.currency_code }}</strong>
                            <span v-if="taxConfiguration?.tax_inclusive_pricing" class="ms-2">
                                <i class="fas fa-percentage"></i>
                                Tax Inclusive Pricing
                            </span>
                        </div>

                        <div class="line-headers">
                            <div class="line-header">Item</div>
                            <div class="line-header">Unit Price</div>
                            <div class="line-header">Quantity</div>
                            <div class="line-header">UOM</div>
                            <div class="line-header">Delivery Date</div>
                            <div class="line-header">Discount</div>
                            <div class="line-header">Taxes</div>
                            <div class="line-header">Subtotal</div>
                            <div class="line-header">Tax Amount</div>
                            <div class="line-header">Total</div>
                            <div class="line-header"></div>
                        </div>

                        <div
                            v-for="(line, index) in form.lines"
                            :key="index"
                            class="order-line"
                        >
                            <!-- ITEM DROPDOWN -->
                            <div class="line-item" data-label="Item" :data-line-index="index">
                                <div class="item-code" v-if="line.item_code" style="font-weight: bold; margin-bottom: 0.25rem;">
                                    {{ line.item_code }}
                                </div>
                                <div class="dropdown-container">
                                    <input
                                        type="text"
                                        v-model="line.itemSearch"
                                        class="form-control"
                                        placeholder="Search for an item..."
                                        @focus="showItemDropdown(index)"
                                        @input="handleItemSearch(index, $event)"
                                        @click="showItemDropdown(index)"
                                        autocomplete="off"
                                    />
                                    <div v-if="line.showDropdown" class="dropdown-menu">
                                        <!-- Show filtered items -->
                                        <div v-if="getFilteredItems(line.itemSearch || '').length > 0">
                                            <div
                                                v-for="item in getFilteredItems(line.itemSearch || '')"
                                                :key="item.item_id"
                                                @mousedown.prevent="selectItem(item, index)"
                                                class="dropdown-item"
                                                style="cursor: pointer;"
                                            >
                                                <div class="item-display">
                                                    <strong>{{ item.item_code }}</strong> - {{ item.name }}
                                                    <small v-if="item.description" class="text-muted d-block">{{ item.description }}</small>
                                                    <div v-if="item.sale_tax_category" class="item-tax-info">
                                                        <small class="text-info">
                                                            <i class="fas fa-tag"></i> 
                                                            Tax Category: {{ item.sale_tax_category.category_name }}
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Show "no items found" if search term exists but no results -->
                                        <div v-else-if="line.itemSearch && line.itemSearch.trim().length > 0" class="dropdown-item text-muted">
                                            No items found for "{{ line.itemSearch }}"
                                        </div>
                                        <!-- Show sample items when no search term -->
                                        <div v-else>
                                            <div class="dropdown-item text-muted">
                                                Type to search items... ({{ sellableItems.length }} items available)
                                            </div>
                                            <div
                                                v-for="item in sellableItems.slice(0, 10)"
                                                :key="'sample-' + item.item_id"
                                                @mousedown.prevent="selectItem(item, index)"
                                                class="dropdown-item"
                                                style="cursor: pointer;"
                                            >
                                                <div class="item-display">
                                                    <strong>{{ item.item_code }}</strong> - {{ item.name }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="line-item" data-label="Unit Price">
                                <input
                                    type="number"
                                    v-model.number="line.unit_price"
                                    min="0"
                                    step="0.01"
                                    required
                                    @input="calculateLineTotals(index)"
                                    placeholder="0.00"
                                />
                            </div>

                            <div class="line-item" data-label="Quantity">
                                <input
                                    type="number"
                                    v-model.number="line.quantity"
                                    min="0"
                                    step="0.01"
                                    required
                                    @input="calculateLineTotals(index)"
                                    placeholder="0"
                                />
                            </div>

                            <div class="line-item" data-label="UOM">
                                <select v-model="line.uom_id" required>
                                    <option value="">-- UOM --</option>
                                    <option
                                        v-for="uom in unitOfMeasures"
                                        :key="uom.uom_id"
                                        :value="uom.uom_id"
                                    >
                                        {{ uom.symbol }}
                                    </option>
                                </select>
                            </div>

                            <div class="line-item" data-label="Delivery Date">
                                <input
                                    type="date"
                                    v-model="line.delivery_date"
                                    class="form-control"
                                    :min="form.so_date"
                                />
                                <small class="text-muted" v-if="line.delivery_date && line.delivery_date < form.so_date">
                                    Warning: Delivery date is before order date
                                </small>
                            </div>

                            <div class="line-item" data-label="Discount">
                                <input
                                    type="number"
                                    v-model.number="line.discount"
                                    min="0"
                                    step="0.01"
                                    @input="calculateLineTotals(index)"
                                    placeholder="0.00"
                                />
                            </div>

                            <!-- Tax Selector -->
                            <div class="line-item tax-selector-container" data-label="Taxes">
                                <TaxSelector
                                    v-model="line.taxes"
                                    :subtotal-amount="line.subtotal || 0"
                                    :tax-category-id="line.item?.sale_tax_category_id"
                                    scope="sale"
                                    :is-inclusive="taxConfiguration?.tax_inclusive_pricing"
                                    :disabled="!line.item_id || isCustomerTaxExempt"
                                    @tax-change="onLineTaxChange(index, $event)"
                                />
                                <div v-if="isCustomerTaxExempt" class="tax-exempt-notice">
                                    <small class="text-warning">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        Customer is tax exempt
                                    </small>
                                </div>
                            </div>

                            <div
                                class="line-item subtotal"
                                data-label="Subtotal"
                            >
                                {{ formatCurrency(line.subtotal || 0) }}
                            </div>

                            <div
                                class="line-item tax-amount"
                                data-label="Tax Amount"
                            >
                                {{ formatCurrency(line.tax_amount || 0) }}
                            </div>

                            <div class="line-item total" data-label="Total">
                                {{ formatCurrency(line.total || 0) }}
                            </div>

                            <div class="line-item actions">
                                <button
                                    type="button"
                                    class="btn-icon delete"
                                    title="Delete Item"
                                    @click="removeLine(index)"
                                >
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Order Totals -->
                        <div class="order-totals">
                            <div class="total-row">
                                <div class="total-label">Subtotal:</div>
                                <div class="total-value">
                                    {{ formatCurrency(calculateSubtotal()) }}
                                </div>
                            </div>
                            <div class="total-row">
                                <div class="total-label">Total Discount:</div>
                                <div class="total-value">
                                    {{ formatCurrency(calculateTotalDiscount()) }}
                                </div>
                            </div>
                            <div class="total-row">
                                <div class="total-label">Total Tax:</div>
                                <div class="total-value">
                                    {{ formatCurrency(calculateTotalTax()) }}
                                </div>
                            </div>
                            
                            <!-- Tax Breakdown -->
                            <div v-if="orderTaxBreakdown.length > 0" class="tax-breakdown">
                                <div class="tax-breakdown-header">
                                    <small class="text-muted">Tax Breakdown:</small>
                                </div>
                                <div 
                                    v-for="taxItem in orderTaxBreakdown" 
                                    :key="taxItem.tax_code"
                                    class="tax-breakdown-item"
                                >
                                    <small>
                                        {{ taxItem.tax_code }} ({{ taxItem.tax_rate }}%): 
                                        {{ formatCurrency(taxItem.total_amount) }}
                                    </small>
                                </div>
                            </div>

                            <div class="total-row grand-total">
                                <div class="total-label">Grand Total:</div>
                                <div class="total-value">
                                    {{ formatCurrency(calculateGrandTotal()) }}
                                </div>
                            </div>

                            <!-- Exchange Rate Info (if not base currency) -->
                            <div v-if="form.currency_code && form.currency_code !== baseCurrency" class="exchange-rate-info">
                                <small class="text-muted">
                                    <i class="fas fa-exchange-alt"></i>
                                    Exchange rate information will be applied during processing
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
/* eslint-disable */
import { ref, computed, onMounted, watch } from "vue";
import { useRouter, useRoute } from "vue-router";
import axios from "axios";
import { useCurrency } from '@/composables/useCurrency';
import { useTaxConfigurationStore } from '@/stores/taxConfigurationStore';
import { useTaxCategoryStore } from '@/stores/taxCategoryStore';
import TaxSelector from '@/components/accounting/TaxSelector.vue';

export default {
    name: "SalesOrderForm",
    components: {
        TaxSelector
    },
    setup() {
        const router = useRouter();
        const route = useRoute();

        // Stores
        const taxConfigStore = useTaxConfigurationStore();
        const taxCategoryStore = useTaxCategoryStore();

        const safeParseFloat = (value) => {
            if (value === null || value === undefined || value === '') return 0;
            const parsed = parseFloat(value);
            return isNaN(parsed) ? 0 : parsed;
        };

        // Initialize currency composable
        const currency = useCurrency();

        // Form data
        const form = ref({
            so_number: "",
            po_number_customer: "",
            so_date: new Date().toISOString().substr(0, 10),
            customer_id: "",
            quotation_id: "",
            payment_terms: "",
            delivery_terms: "",
            expected_delivery: "",
            currency_code: "",
            status: "Draft",
            lines: [],
        });

        // Reference data
        const customers = ref([]);
        const items = ref([]);
        const unitOfMeasures = ref([]);
        const selectedCustomer = ref(null);
        const taxConfiguration = ref(null);

        // Customer search functionality
        const customerSearch = ref('');
        const showCustomerDropdown = ref(false);

        // UI state
        const currencies = ref([]);
        const isLoading = ref(false);
        const isLoadingCurrencies = ref(false);
        const isSubmitting = ref(false);
        const error = ref("");
        const nextSoNumber = ref('');

        // Computed properties
        const sellableItems = computed(() => {
            const sellable = items.value.filter(item => item.is_sellable === true || item.is_sellable === 1);
            console.log('🔍 Sellable items computed:', sellable.length, 'out of', items.value.length);
            return sellable;
        });

        const activeCurrencies = computed(() => {
            return currencies.value
                .filter(currency => currency.is_active)
                .sort((a, b) => (a.sort_order || 0) - (b.sort_order || 0));
        });

        const isEditMode = computed(() => {
            return route.params.id !== undefined;
        });

        const baseCurrency = computed(() => {
            return currency.baseCurrency.value;
        });

        const isCustomerTaxExempt = computed(() => {
            return selectedCustomer.value?.is_tax_exempt || false;
        });

        // Tax breakdown calculation
        const orderTaxBreakdown = computed(() => {
            const taxMap = new Map();
            
            form.value.lines.forEach(line => {
                if (line.taxes && line.taxes.length > 0) {
                    line.taxes.forEach(tax => {
                        const key = tax.tax_code;
                        if (taxMap.has(key)) {
                            const existing = taxMap.get(key);
                            existing.total_amount += tax.calculated_amount || 0;
                        } else {
                            taxMap.set(key, {
                                tax_code: tax.tax_code,
                                tax_rate: tax.tax_rate,
                                total_amount: tax.calculated_amount || 0
                            });
                        }
                    });
                }
            });

            return Array.from(taxMap.values()).sort((a, b) => a.tax_code.localeCompare(b.tax_code));
        });

        // Load next sales order number for preview
        const loadNextSalesOrderNumber = async () => {
            if (!isEditMode.value) {
                try {
                    const response = await axios.get('/orders/next-number');
                    nextSoNumber.value = response.data.next_so_number;
                } catch (error) {
                    console.error('Error loading next sales order number:', error);
                    nextSoNumber.value = 'SO-' + new Date().getFullYear().toString().substr(-2) + '-000001';
                }
            }
        };

        // Load currencies from API
        const loadCurrencies = async () => {
            try {
                isLoadingCurrencies.value = true;
                console.log('🔄 Loading currencies from API...');
                
                try {
                    const fallbackResponse = await axios.get("/system-currencies");
                    if (fallbackResponse.data && fallbackResponse.data.data) {
                        currencies.value = fallbackResponse.data.data;
                        console.log('✅ Currencies loaded from fallback:', currencies.value.length, 'currencies');
                    } else {
                        throw new Error('Fallback also failed');
                    }
                } catch (fallbackErr) {
                    console.error("Error loading currencies from both endpoints:", fallbackErr);
                    error.value = "Error loading currencies. Using default options.";
                    
                    // Final fallback: use hardcoded currencies
                    currencies.value = [
                        { code: 'IDR', name: 'Indonesian Rupiah', symbol: 'Rp', decimal_places: 0, is_active: true, sort_order: 1 },
                        { code: 'USD', name: 'US Dollar', symbol: '$', decimal_places: 2, is_active: true, sort_order: 2 },
                        { code: 'EUR', name: 'Euro', symbol: '€', decimal_places: 2, is_active: true, sort_order: 3 },
                        { code: 'SGD', name: 'Singapore Dollar', symbol: 'S$', decimal_places: 2, is_active: true, sort_order: 4 },
                        { code: 'MYR', name: 'Malaysian Ringgit', symbol: 'RM', decimal_places: 2, is_active: true, sort_order: 5 }
                    ];
                }
            } finally {
                isLoadingCurrencies.value = false;
            }
        };

        // Load tax configuration
        const loadTaxConfiguration = async () => {
            try {
                const config = await taxConfigStore.fetchTaxConfiguration();
                taxConfiguration.value = config.data;
                console.log('✅ Tax configuration loaded:', taxConfiguration.value);
            } catch (error) {
                console.error('❌ Error loading tax configuration:', error);
                // Continue without tax configuration
            }
        };

        // Load reference data with enhanced logging
        const loadReferenceData = async () => {
            try {
                console.log('🔄 Loading reference data...');

                // Load currencies first
                await loadCurrencies();
                
                // Load tax configuration
                await loadTaxConfiguration();
                
                // Load customers with tax information
                const customersResponse = await axios.get("/customers");
                customers.value = customersResponse.data.data || [];
                console.log('✅ Customers loaded:', customers.value.length);

                // Load items with tax categories
                const itemsResponse = await axios.get("/items");
                items.value = itemsResponse.data.data || [];
                console.log('✅ Items loaded:', items.value.length);

                // Debug: show sample items
                if (items.value.length > 0) {
                    console.log('📦 Sample item:', items.value[0]);
                    const sellableCount = items.value.filter(item => item.is_sellable).length;
                    console.log('🏷️ Sellable items:', sellableCount);
                }

                // Load unit of measures
                const uomResponse = await axios.get("/unit-of-measures");
                unitOfMeasures.value = uomResponse.data.data || [];
                console.log('✅ UOMs loaded:', unitOfMeasures.value.length);

            } catch (err) {
                console.error("❌ Error loading reference data:", err);
                error.value = "Error loading reference data: " + err.message;
            }
        };

        // Helper function to convert snake_case keys to camelCase recursively
        const toCamelCase = (obj) => {
            if (Array.isArray(obj)) {
                return obj.map(v => toCamelCase(v));
            } else if (obj !== null && obj.constructor === Object) {
                return Object.keys(obj).reduce((result, key) => {
                    const camelKey = key.replace(/_([a-z])/g, g => g[1].toUpperCase());
                    result[camelKey] = toCamelCase(obj[key]);
                    return result;
                }, {});
            }
            return obj;
        };

        // Load order data if in edit mode
        const loadOrder = async () => {
            if (!isEditMode.value) {
                await loadNextSalesOrderNumber();
                return;
            }

            isLoading.value = true;
            error.value = "";

            try {
                const response = await axios.get(`/orders/${route.params.id}`);
                let order = response.data.data;

                // Convert order keys to camelCase
                order = toCamelCase(order);

                // Set form data
                form.value = {
                    so_id: order.soId,
                    so_number: order.soNumber,
                    po_number_customer: order.poNumberCustomer || "",
                    so_date: order.soDate.substr(0, 10),
                    customer_id: order.customerId,
                    quotation_id: order.quotationId || "",
                    payment_terms: order.paymentTerms || "",
                    delivery_terms: order.deliveryTerms || "",
                    expected_delivery: order.expectedDelivery
                        ? order.expectedDelivery.substr(0, 10)
                        : "",
                    currency_code: order.currencyCode || "IDR",
                    status: order.status,
                    lines: [],
                };

                // Set line items with tax information
                if (order.salesOrderLines && order.salesOrderLines.length > 0) {
                    form.value.lines = order.salesOrderLines.map((line) => {
                        const selectedItem = items.value.find(i => i.item_id == line.itemId);

                        return {
                            line_id: line.lineId,
                            item_id: line.itemId,
                            item: selectedItem,
                            item_code: selectedItem ? selectedItem.item_code : '',
                            itemSearch: selectedItem ? `${selectedItem.item_code} - ${selectedItem.name}` : '',
                            showDropdown: false,
                            unit_price: safeParseFloat(line.unitPrice),
                            quantity: safeParseFloat(line.quantity) || 1,
                            uom_id: line.uomId,
                            delivery_date: line.deliveryDate ? line.deliveryDate.substr(0, 10) : '',
                            discount: safeParseFloat(line.discount),
                            taxes: line.taxes || [], // Load existing tax data
                            tax_amount: safeParseFloat(line.taxAmount || 0),
                            subtotal: safeParseFloat(line.subtotal),
                            total: safeParseFloat(line.total),
                        };
                    });

                    // Recalculate all line totals
                    form.value.lines.forEach((_, index) => {
                        calculateLineTotals(index);
                    });
                }

                // Find selected customer and set search field
                if (form.value.customer_id) {
                    selectedCustomer.value = customers.value.find(
                        c => c.customer_id === form.value.customer_id
                    );
                    if (selectedCustomer.value) {
                        customerSearch.value = selectedCustomer.value.name;
                    }
                }
            } catch (err) {
                console.error("Error loading order:", err);
                error.value = "Error loading order.";
            } finally {
                isLoading.value = false;
            }
        };

        // Method to filter customers based on search input
        const getFilteredCustomers = (searchInput) => {
            if (!searchInput) {
                return customers.value;
            }
            return customers.value.filter(customer =>
                customer.name.toLowerCase().includes(searchInput.toLowerCase()) ||
                (customer.customer_code && customer.customer_code.toLowerCase().includes(searchInput.toLowerCase()))
            );
        };

        // Method to select a customer from the dropdown
        const selectCustomer = (customer) => {
            form.value.customer_id = customer.customer_id;
            customerSearch.value = customer.name;
            showCustomerDropdown.value = false;
            selectedCustomer.value = customer;

            // If customer has preferred currency, set it as the order currency
            if (customer.preferred_currency) {
                form.value.currency_code = customer.preferred_currency;
            }

            // Recalculate taxes for all lines if customer tax status changed
            form.value.lines.forEach((_, index) => {
                calculateLineTotals(index);
            });
        };

        // Enhanced item filtering with better logic
        const getFilteredItems = (searchInput) => {
            const searchTerm = (searchInput || '').trim();

            if (!searchTerm) {
                // Return first 20 sellable items when no search term
                return sellableItems.value.slice(0, 20);
            }

            const searchLower = searchTerm.toLowerCase();
            const filtered = sellableItems.value.filter(item => {
                const matchCode = item.item_code && item.item_code.toLowerCase().includes(searchLower);
                const matchName = item.name && item.name.toLowerCase().includes(searchLower);
                const matchDesc = item.description && item.description.toLowerCase().includes(searchLower);

                return matchCode || matchName || matchDesc;
            });

            console.log(`🔍 Search "${searchTerm}" found ${filtered.length} items`);
            return filtered.slice(0, 50); // Limit to 50 results for performance
        };

        // Show item dropdown with proper state management
        const showItemDropdown = (index) => {
            console.log('👆 Show dropdown for line', index);

            // Hide all other dropdowns first
            form.value.lines.forEach((line, i) => {
                if (i !== index) {
                    line.showDropdown = false;
                }
            });

            // Show current dropdown
            if (form.value.lines[index]) {
                form.value.lines[index].showDropdown = true;
                console.log('📋 Available sellable items:', sellableItems.value.length);
            }
        };

        // Handle item search input
        const handleItemSearch = (index, event) => {
            const searchTerm = event.target.value;
            console.log('🔍 Search term changed:', searchTerm, 'for line', index);

            // Always show dropdown when typing
            if (form.value.lines[index]) {
                form.value.lines[index].showDropdown = true;
            }
        };

        // Enhanced item selection with tax category handling
        const selectItem = async (item, lineIndex) => {
            console.log('✅ Selecting item:', item.item_code, 'for line', lineIndex);

            const line = form.value.lines[lineIndex];

            // Set item data
            line.item_id = item.item_id;
            line.item = item;
            line.item_code = item.item_code;
            line.itemSearch = `${item.item_code} - ${item.name}`;
            line.showDropdown = false;

            // Clear existing taxes when item changes
            line.taxes = [];
            line.tax_amount = 0;

            // Set UOM automatically if available
            if (item.uom_id) {
                line.uom_id = item.uom_id;
                console.log('🏷️ UOM set from item.uom_id:', item.uom_id);
            }

            // Set default delivery date
            if (!line.delivery_date) {
                if (form.value.expected_delivery) {
                    line.delivery_date = form.value.expected_delivery;
                } else {
                    const soDate = new Date(form.value.so_date);
                    soDate.setDate(soDate.getDate() + 7);
                    line.delivery_date = soDate.toISOString().substr(0, 10);
                }
            }

            // Get price information
            try {
                if (form.value.customer_id) {
                    // Try to get best price from API
                    const response = await axios.get(`/items/${item.item_id}/best-sale-price`, {
                        params: {
                            customer_id: form.value.customer_id,
                            quantity: safeParseFloat(line.quantity) || 1,
                            currency_code: form.value.currency_code
                        }
                    });

                    if (response.data && response.data.price) {
                        line.unit_price = safeParseFloat(response.data.price);
                        console.log('💰 Price from API:', line.unit_price);
                    } else {
                        line.unit_price = safeParseFloat(item.sale_price);
                        console.log('💰 Price from item.sale_price:', line.unit_price);
                    }
                } else {
                    // No customer selected, use item sale price
                    line.unit_price = safeParseFloat(item.sale_price);
                    console.log('💰 Price from item (no customer):', line.unit_price);
                }
            } catch (err) {
                console.error("❌ Error fetching item price:", err);
                line.unit_price = safeParseFloat(item.sale_price);
                console.log('💰 Fallback price:', line.unit_price);
            }

            // Always recalculate after setting price
            calculateLineTotals(lineIndex);
            console.log('🧮 Line totals calculated');
        };

        // Line item operations
        const addLine = () => {
            let defaultDeliveryDate = '';
            if (form.value.expected_delivery) {
                defaultDeliveryDate = form.value.expected_delivery;
            } else {
                const soDate = new Date(form.value.so_date);
                soDate.setDate(soDate.getDate() + 7);
                defaultDeliveryDate = soDate.toISOString().substr(0, 10);
            }

            form.value.lines.push({
                item_id: "",
                item: null,
                item_code: "",
                itemSearch: "",
                showDropdown: false,
                unit_price: 0,
                quantity: 0,
                uom_id: "",
                delivery_date: defaultDeliveryDate,
                discount: 0,
                taxes: [],
                tax_amount: 0,
                subtotal: 0,
                total: 0,
            });

            console.log('➕ Added new line, total lines:', form.value.lines.length);
        };

        const removeLine = (index) => {
            form.value.lines.splice(index, 1);
            console.log('➖ Removed line', index, ', total lines:', form.value.lines.length);
        };

        // Enhanced line calculation with tax support
        const calculateLineTotals = (index) => {
            const line = form.value.lines[index];

            // Ensure all numeric values are valid
            const unitPrice = safeParseFloat(line.unit_price);
            const quantity = safeParseFloat(line.quantity);
            const discount = safeParseFloat(line.discount);

            // Calculate subtotal (unit_price * quantity - discount)
            line.subtotal = Math.max(0, (unitPrice * quantity) - discount);

            // Tax amount will be calculated by TaxSelector component
            // We just ensure tax_amount exists
            if (!line.tax_amount) {
                line.tax_amount = 0;
            }

            // Calculate total (subtotal + tax_amount)
            line.total = line.subtotal + line.tax_amount;

            // Ensure values are not negative
            line.subtotal = Math.max(0, line.subtotal);
            line.total = Math.max(0, line.total);
        };

        // Handle tax changes from TaxSelector component
        const onLineTaxChange = (lineIndex, taxData) => {
            const line = form.value.lines[lineIndex];
            line.tax_amount = taxData.totalTaxAmount || 0;
            line.total = line.subtotal + line.tax_amount;
            console.log('💰 Tax changed for line', lineIndex, ':', taxData);
        };

        // Handle currency change
        const onCurrencyChange = () => {
            // Recalculate all line totals when currency changes
            form.value.lines.forEach((_, index) => {
                calculateLineTotals(index);
            });
        };

        // Calculate totals
        const calculateSubtotal = () => {
            return form.value.lines.reduce((sum, line) => {
                return sum + safeParseFloat(line.subtotal);
            }, 0);
        };

        const calculateTotalDiscount = () => {
            return form.value.lines.reduce((sum, line) => {
                return sum + safeParseFloat(line.discount);
            }, 0);
        };

        const calculateTotalTax = () => {
            return form.value.lines.reduce((sum, line) => {
                return sum + safeParseFloat(line.tax_amount);
            }, 0);
        };

        const calculateGrandTotal = () => {
            return form.value.lines.reduce((sum, line) => {
                return sum + safeParseFloat(line.total);
            }, 0);
        };

        // Format currency
        const formatCurrency = (value) => {
            const safeValue = safeParseFloat(value);
            const currencyCode = form.value.currency_code || "IDR";

            try {
                return new Intl.NumberFormat("id-ID", {
                    style: "currency",
                    currency: currencyCode,
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }).format(safeValue);
            } catch (error) {
                console.error('Currency formatting error:', error);
                return `${currencyCode} ${safeValue.toFixed(2)}`;
            }
        };

        // Format rounding method for display
        const formatRoundingMethod = (method) => {
            const methods = {
                'round': 'Normal',
                'round_up': 'Round Up',
                'round_down': 'Round Down'
            };
            return methods[method] || method;
        };

        // Navigation
        const goBack = () => {
            router.push("/sales/orders");
        };

        // Save order with tax information
        const saveOrder = async () => {
            // Validate form
            if (
                !form.value.so_date ||
                !form.value.customer_id ||
                !form.value.currency_code
            ) {
                error.value = "Please fill in all required fields.";
                return;
            }

            // Validate line items
            if (form.value.lines.length === 0) {
                error.value = "Order must have at least 1 item.";
                return;
            }

            for (let i = 0; i < form.value.lines.length; i++) {
                const line = form.value.lines[i];
                if (
                    !line.item_id ||
                    !line.unit_price ||
                    !line.quantity ||
                    !line.uom_id
                ) {
                    error.value = `Item ${i + 1} has incomplete data.`;
                    return;
                }
            }

            isSubmitting.value = true;
            error.value = "";

            try {
                // Prepare order data with tax information
                const orderLines = form.value.lines.map(line => ({
                    line_id: line.line_id,
                    item_id: line.item_id,
                    unit_price: safeParseFloat(line.unit_price),
                    quantity: safeParseFloat(line.quantity),
                    uom_id: line.uom_id,
                    delivery_date: line.delivery_date,
                    discount: safeParseFloat(line.discount),
                    taxes: line.taxes || [], // Include tax information
                    tax_amount: safeParseFloat(line.tax_amount),
                    subtotal: safeParseFloat(line.subtotal),
                    total: safeParseFloat(line.total)
                }));

                const orderData = {
                    ...form.value,
                    total_amount: calculateGrandTotal(),
                    tax_amount: calculateTotalTax(),
                    subtotal_amount: calculateSubtotal(),
                    discount_amount: calculateTotalDiscount(),
                    lines: orderLines
                };

                delete orderData.so_number;

                if (isEditMode.value) {
                    await axios.put(`/orders/${form.value.so_id}`, orderData);
                    alert("Order successfully updated!");
                } else {
                    await axios.post("/orders", orderData);
                    alert("Order successfully created!");
                }

                router.push("/sales/orders");
            } catch (err) {
                console.error("Error saving order:", err);

                if (err.response?.data?.errors) {
                    const errors = err.response.data.errors;
                    const firstError = Object.values(errors)[0][0];
                    error.value = firstError;
                } else if (err.response?.data?.message) {
                    error.value = err.response.data.message;
                } else {
                    error.value = "An error occurred while saving the order.";
                }
            } finally {
                isSubmitting.value = false;
            }
        };

        // Setup click outside handler
        const setupClickOutsideHandler = () => {
            document.addEventListener('click', (e) => {
                // Handle customer dropdown
                if (!e.target.closest('.dropdown-container') || e.target.closest('#customer_search')) {
                    const customerContainer = e.target.closest('.dropdown-container');
                    if (!customerContainer || customerContainer.querySelector('#customer_search')) {
                        if (!e.target.closest('.dropdown-menu')) {
                            showCustomerDropdown.value = false;
                        }
                    }
                }

                // Handle item dropdowns
                form.value.lines.forEach((line, index) => {
                    const lineContainer = e.target.closest(`[data-line-index="${index}"]`);
                    if (!lineContainer && !e.target.closest('.dropdown-menu')) {
                        line.showDropdown = false;
                    }
                });
            });
        };

        // Component initialization
        onMounted(async () => {
            console.log('🚀 Component mounted, initializing...');

            setupClickOutsideHandler();

            try {
                await loadReferenceData();

                // Fetch currency settings from backend
                await currency.fetchCurrencySettings();

                // Set form.currency_code to base currency if not set
                if (!form.value.currency_code || form.value.currency_code === "INR") {
                    form.value.currency_code = currency.baseCurrency.value;
                }

                await loadOrder();
                console.log('✅ Component initialization complete');
            } catch (error) {
                console.error('❌ Error during initialization:', error);
            }
        });

        return {
            form,
            customers,
            items,
            unitOfMeasures,
            currencies,
            activeCurrencies,
            isLoading,
            isLoadingCurrencies,
            isSubmitting,
            error,
            isEditMode,
            sellableItems,
            selectedCustomer,
            customerSearch,
            showCustomerDropdown,
            nextSoNumber,
            taxConfiguration,
            baseCurrency,
            isCustomerTaxExempt,
            orderTaxBreakdown,
            safeParseFloat,
            getFilteredCustomers,
            selectCustomer,
            getFilteredItems,
            showItemDropdown,
            handleItemSearch,
            selectItem,
            addLine,
            removeLine,
            calculateLineTotals,
            onLineTaxChange,
            onCurrencyChange,
            calculateSubtotal,
            calculateTotalDiscount,
            calculateTotalTax,
            calculateGrandTotal,
            formatCurrency,
            formatRoundingMethod,
            goBack,
            saveOrder,
            currency,
        };
    },
};
</script>

<style scoped>
.order-form {
    padding: 1rem 0;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.page-header h1 {
    font-size: 1.5rem;
    font-weight: 600;
    margin: 0;
    color: #1e293b;
}

.page-actions {
    display: flex;
    gap: 0.75rem;
}

.alert {
    padding: 1rem;
    border-radius: 0.375rem;
    margin-bottom: 1.5rem;
}

.alert-danger {
    background-color: #fee2e2;
    color: #b91c1c;
    border: 1px solid #fecaca;
}

.form-container {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.form-card {
    background-color: white;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    background-color: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
}

.card-header h2 {
    font-size: 1.125rem;
    font-weight: 600;
    margin: 0;
    color: #1e293b;
}

.card-body {
    padding: 1.5rem;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: #334155;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 0.625rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.375rem;
    font-size: 0.875rem;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: 2px solid #2563eb;
    outline-offset: 1px;
}

.form-group input.readonly {
    background-color: #f8fafc;
    cursor: not-allowed;
}

.badge {
    display: inline-block;
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    font-weight: 500;
    line-height: 1;
    text-align: center;
    white-space: nowrap;
    vertical-align: baseline;
    border-radius: 0.375rem;
}

.badge-info {
    color: #fff;
    background-color: #17a2b8;
}

.form-control-static {
    padding-top: 0.65rem;
    padding-bottom: 0.65rem;
    margin-bottom: 0;
    min-height: calc(1.5em + 1.3rem + 2px);
}

.text-muted {
    color: #64748b;
    font-size: 0.75rem;
    margin-top: 0.25rem;
}

.text-warning {
    color: #f59e0b;
}

.text-info {
    color: #0ea5e9;
}

/* Tax Configuration Info */
.tax-config-info {
    background-color: #eff6ff;
    border: 1px solid #bfdbfe;
    border-radius: 0.375rem;
    padding: 1rem;
}

.tax-config-info h6 {
    color: #1e40af;
    margin-bottom: 0.5rem;
}

.d-flex {
    display: flex;
}

.gap-3 {
    gap: 1rem;
}

.ms-2 {
    margin-left: 0.5rem;
}

.me-2 {
    margin-right: 0.5rem;
}

.mb-2 {
    margin-bottom: 0.5rem;
}

/* Customer Tax Info */
.customer-tax-info {
    margin-top: 0.25rem;
}

.item-tax-info {
    margin-top: 0.25rem;
}

.empty-lines {
    background-color: #f8fafc;
    padding: 2rem;
    border-radius: 0.375rem;
    text-align: center;
    color: #64748b;
    border: 1px dashed #cbd5e1;
}

.order-lines {
    border: 1px solid #e2e8f0;
    border-radius: 0.375rem;
    overflow: hidden;
}

.order-currency-info {
    background-color: #eff6ff;
    color: #1e40af;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    border-bottom: 1px solid #dbeafe;
}

.line-headers {
    display: grid;
    grid-template-columns: 2fr 0.8fr 0.8fr 0.6fr 1fr 0.8fr 1.5fr 1fr 1fr 1fr 0.5fr;
    gap: 0.5rem;
    background-color: #f8fafc;
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #e2e8f0;
    font-weight: 500;
    color: #475569;
    font-size: 0.75rem;
}

.order-line {
    display: grid;
    grid-template-columns: 2fr 0.8fr 0.8fr 0.6fr 1fr 0.8fr 1.5fr 1fr 1fr 1fr 0.5fr;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #e2e8f0;
    align-items: flex-start;
}

.order-line:last-child {
    border-bottom: none;
}

.line-item input,
.line-item select {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.25rem;
    font-size: 0.875rem;
}

.line-item.subtotal,
.line-item.total,
.line-item.tax-amount {
    font-weight: 500;
    text-align: right;
    align-self: center;
}

.line-item.total {
    color: #2563eb;
}

.line-item.tax-amount {
    color: #059669;
}

.line-item.actions {
    text-align: center;
    align-self: center;
}

/* Tax Selector Container */
.tax-selector-container {
    min-width: 300px;
}

.tax-exempt-notice {
    margin-top: 0.5rem;
}

.btn-icon {
    background: none;
    border: none;
    color: #64748b;
    cursor: pointer;
    padding: 0.375rem;
    border-radius: 0.25rem;
}

.btn-icon:hover {
    background-color: #f1f5f9;
}

.btn-icon.delete:hover {
    color: #dc2626;
    background-color: #fee2e2;
}

.order-totals {
    border-top: 1px solid #e2e8f0;
    padding: 1rem;
    background-color: #f8fafc;
}

.total-row {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 1rem;
    margin-bottom: 0.5rem;
}

.total-row:last-child {
    margin-bottom: 0;
}

.total-label {
    font-weight: 500;
    color: #475569;
    width: 10rem;
    text-align: right;
}

.total-value {
    width: 10rem;
    text-align: right;
    font-weight: 500;
}

.grand-total .total-label,
.grand-total .total-value {
    font-size: 1.125rem;
    font-weight: 600;
    color: #1e293b;
}

/* Tax Breakdown */
.tax-breakdown {
    margin: 0.5rem 0;
    padding: 0.5rem;
    background-color: #f0f9ff;
    border-radius: 0.25rem;
    border: 1px solid #e0f2fe;
}

.tax-breakdown-header {
    margin-bottom: 0.25rem;
}

.tax-breakdown-item {
    display: flex;
    justify-content: flex-end;
    margin-bottom: 0.125rem;
}

.tax-breakdown-item:last-child {
    margin-bottom: 0;
}

/* Exchange Rate Info */
.exchange-rate-info {
    margin-top: 0.5rem;
    text-align: right;
}

.btn {
    padding: 0.625rem 1rem;
    font-size: 0.875rem;
    font-weight: 500;
    border-radius: 0.375rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    border: none;
    transition: background-color 0.2s, color 0.2s;
}

.btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.75rem;
}

.btn-primary {
    background-color: #2563eb;
    color: white;
}

.btn-primary:hover:not(:disabled) {
    background-color: #1d4ed8;
}

.btn-primary:disabled {
    background-color: #93c5fd;
    cursor: not-allowed;
}

.btn-secondary {
    background-color: #e2e8f0;
    color: #1e293b;
}

.btn-secondary:hover {
    background-color: #cbd5e1;
}

/* Enhanced dropdown styling */
.dropdown-container {
    position: relative;
    width: 100%;
    z-index: 10;
}

.dropdown-menu {
    position: absolute;
    top: 100%;
    left: 0;
    width: 100%;
    max-height: 250px;
    overflow-y: auto;
    background-color: white;
    border: 1px solid #e2e8f0;
    border-radius: 0.375rem;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    z-index: 1000;
    margin-top: 0.25rem;
}

.dropdown-item {
    padding: 0.75rem 1rem;
    cursor: pointer;
    transition: background-color 0.2s;
    border-bottom: 1px solid #f8fafc;
}

.dropdown-item:last-child {
    border-bottom: none;
}

.dropdown-item:hover {
    background-color: #f8fafc;
}

.dropdown-item.text-muted {
    color: #94a3b8;
    cursor: default;
    font-style: italic;
}

.dropdown-item.text-muted:hover {
    background-color: transparent;
}

/* Item display styling */
.item-display {
    display: flex;
    flex-direction: column;
}

.item-display strong {
    color: #1e293b;
    font-weight: 600;
}

.item-display small {
    color: #64748b;
    font-size: 0.75rem;
    margin-top: 0.125rem;
}

.d-block {
    display: block;
}

.customer-info {
    display: flex;
    flex-direction: column;
}

.customer-name {
    font-weight: 500;
    color: #1e293b;
}

.customer-code {
    font-size: 0.75rem;
    color: #64748b;
    margin-top: 0.125rem;
}

.form-control.is-invalid {
    border-color: #dc2626;
}

.form-control.is-invalid:focus {
    outline: 2px solid #dc2626;
    outline-offset: 1px;
}

.item-code {
    font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
    font-size: 0.75rem;
    color: #4338ca;
    background-color: #f0f4ff;
    padding: 0.125rem 0.25rem;
    border-radius: 0.25rem;
    display: inline-block;
}

/* Improve line item container for better dropdown positioning */
.line-item {
    position: relative;
}

.line-item .dropdown-container {
    z-index: 100;
}

@media (max-width: 1200px) {
    .line-headers {
        grid-template-columns: 1.5fr 0.7fr 0.7fr 0.5fr 0.8fr 0.7fr 1.2fr 0.8fr 0.8fr 0.8fr 0.4fr;
        font-size: 0.7rem;
    }

    .order-line {
        grid-template-columns: 1.5fr 0.7fr 0.7fr 0.5fr 0.8fr 0.7fr 1.2fr 0.8fr 0.8fr 0.8fr 0.4fr;
    }
}

@media (max-width: 1024px) {
    .form-row {
        grid-template-columns: 1fr;
        gap: 0;
    }

    .order-line,
    .line-headers {
        grid-template-columns: 1fr;
        font-size: 0.75rem;
        padding: 0.5rem;
        gap: 0.25rem;
    }

    .line-header {
        display: none;
    }

    .line-item {
        display: flex;
        align-items: flex-start;
        width: 100%;
        margin-bottom: 0.5rem;
    }

    .line-item::before {
        content: attr(data-label) ': ';
        font-weight: 500;
        width: 8rem;
        text-align: left;
        flex-shrink: 0;
        align-self: center;
        color: #475569;
    }

    .tax-selector-container {
        min-width: auto;
        width: 100%;
    }
}

@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }

    .total-row {
        flex-direction: column;
        align-items: flex-end;
    }

    .total-label,
    .total-value {
        width: auto;
    }

    .tax-breakdown-item {
        justify-content: flex-start;
    }
}
</style>