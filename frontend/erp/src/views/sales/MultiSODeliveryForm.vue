<template>
  <div class="multi-so-delivery-form">
    <div class="form-header">
      <h2>📦 Create Multi-SO Delivery</h2>
      <div class="form-actions">
        <button type="button" @click="resetForm" class="btn btn-secondary">
          <i class="fas fa-undo"></i> Reset
        </button>
        <button
          type="button"
          @click="submitForm"
          :disabled="isSubmitting || deliveryItems.length === 0"
          class="btn btn-primary"
        >
          <i class="fas fa-truck"></i>
          {{ isSubmitting ? 'Creating...' : 'Create Delivery' }}
        </button>
      </div>
    </div>

    <!-- Delivery Information Section -->
    <div class="form-section">
      <h3>🚚 Delivery Information</h3>
      <div class="form-grid">
        <!-- Auto-generated Delivery Number Preview -->
        <div class="form-group">
          <label for="delivery_number">Delivery Number*</label>
          <div class="form-control-static">
            <span class="badge badge-info">{{ nextDeliveryNumber || 'Loading...' }}</span>
          </div>
          <small class="text-muted">
            Auto-generated number (will be assigned when saved)
          </small>
        </div>

        <div class="form-group">
          <label for="delivery_date">Delivery Date*</label>
          <input
            type="date"
            id="delivery_date"
            v-model="form.delivery_date"
            :min="today"
            required
            class="form-control"
          />
        </div>

        <div class="form-group">
          <label for="shipping_method">Shipping Method</label>
          <input
            type="text"
            id="shipping_method"
            v-model="form.shipping_method"
            placeholder="Enter shipping method"
            class="form-control"
          />
        </div>

        <div class="form-group">
          <label for="tracking_number">Tracking Number</label>
          <input
            type="text"
            id="tracking_number"
            v-model="form.tracking_number"
            placeholder="Enter tracking number"
            class="form-control"
          />
        </div>
      </div>
    </div>

    <!-- Customer Selection -->
    <div class="form-section">
      <h3>👤 Customer Selection</h3>
      <div class="form-group">
        <label for="customer">Customer*</label>
        <select
          id="customer"
          v-model="selectedCustomerId"
          @change="onCustomerChange"
          required
          class="form-control"
        >
          <option value="">Select Customer</option>
          <option
            v-for="customer in customers"
            :key="customer.customer_id"
            :value="customer.customer_id"
          >
            {{ customer.customer_code }} - {{ customer.name }}
          </option>
        </select>
        <small v-if="selectedCustomer">
          Customer Code: {{ selectedCustomer.customer_code }}
        </small>
      </div>
    </div>

    <!-- Sales Order Selection -->
    <div class="form-section" v-if="selectedCustomerId">
      <h3>📋 Add Sales Order</h3>
      <div class="so-input-section">
        <div class="form-row">
          <div class="form-group">
            <label>Sales Order Number</label>
            <select v-model="currentSoNumber" @change="loadSOItems">
              <option value="">Select Sales Order</option>
              <option
                v-for="so in availableSOs"
                :key="so.so_number"
                :value="so.so_number"
              >
                {{ so.so_number }} - {{ formatDate(so.so_date) }} (Outstanding: {{ so.outstanding_quantity }})
              </option>
            </select>
          </div>
          <div class="form-group">
            <button
              type="button"
              @click="addSOToDelivery"
              :disabled="!currentSoNumber || soAlreadyAdded"
              class="btn btn-primary"
            >
              <i class="fas fa-plus"></i> Add SO
            </button>
          </div>
        </div>
        <div v-if="soAlreadyAdded" class="warning-text">
          This SO is already added to the delivery
        </div>
      </div>
    </div>

    <!-- Outstanding Items from Selected SOs -->
    <div class="form-section" v-if="addedSOs.length > 0">
      <h3>📦 Outstanding Items</h3>

      <div v-for="so in addedSOs" :key="so.so_id" class="so-section">
        <div class="so-header">
          <h4>{{ so.so_number }} - {{ so.customer_name }}</h4>
          <button
            type="button"
            @click="removeSOFromDelivery(so.so_id)"
            class="btn btn-sm btn-danger"
          >
            <i class="fas fa-times"></i> Remove SO
          </button>
        </div>

        <div class="items-table">
          <div class="items-header">
            <div>Select</div>
            <div>Item Code</div>
            <div>Item Name</div>
            <div>Outstanding Qty</div>
            <div>Deliver Qty</div>
            <div>UOM</div>
            <div>Warehouse</div>
            <div>Available Stock</div>
          </div>

          <div
            v-for="item in so.items"
            :key="item.so_line_id"
            class="item-row"
            :class="getStockStatusClass(getAvailableStock(item), item.deliver_quantity)"
          >
            <div class="item-select">
              <input
                type="checkbox"
                :id="`item_${item.so_line_id}`"
                :checked="item.selected"
                @change="updateItemSelection(so.so_id, item.so_line_id, $event.target.checked)"
              />
            </div>
            <div class="item-code">{{ item.item_code }}</div>
            <div class="item-name">{{ item.item_name }}</div>
            <div class="outstanding-qty">{{ formatNumber(item.outstanding_quantity) }}</div>
            <div class="deliver-qty">
              <input
                type="number"
                v-model.number="item.deliver_quantity"
                :max="item.outstanding_quantity"
                :min="0"
                step="0.01"
                :disabled="!item.selected"
                @input="validateDeliveryQuantity(item)"
                class="form-control"
              />
            </div>
            <div class="uom">{{ item.uom_name }}</div>
            <div class="warehouse">
              <select
                v-model="item.warehouse_id"
                :disabled="!item.selected"
                @change="updateWarehouseStock(item)"
                class="form-control"
              >
                <option value="">Select Warehouse</option>
                <option
                  v-for="warehouse in item.warehouses"
                  :key="warehouse.warehouse_id"
                  :value="warehouse.warehouse_id"
                >
                  {{ warehouse.name }}
                </option>
              </select>
            </div>
            <div class="available-stock">
              {{ formatNumber(getAvailableStock(item)) }}
            </div>
          </div>
        </div>
      </div>

      <!-- Summary -->
      <div class="delivery-summary">
        <h4>📊 Delivery Summary</h4>
        <div class="summary-stats">
          <div class="stat">
            <strong>Total Items:</strong> {{ deliveryItems.length }}
          </div>
          <div class="stat">
            <strong>Total Quantity:</strong> {{ formatNumber(totalDeliveryQuantity) }}
          </div>
          <div class="stat">
            <strong>Sales Orders:</strong> {{ addedSOs.length }}
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';

export default {
  name: 'MultiSODeliveryForm',
  setup() {
    const router = useRouter();

    // Form data
    const form = ref({
      delivery_date: new Date().toISOString().split('T')[0],
      shipping_method: '',
      tracking_number: ''
    });

    // State variables
    const nextDeliveryNumber = ref('');
    const customers = ref([]);
    const selectedCustomerId = ref('');
    const availableSOs = ref([]);
    const currentSoNumber = ref('');
    const addedSOs = ref([]);
    const isSubmitting = ref(false);
    const isLoading = ref(false);

    // Computed properties
    const today = computed(() => {
      return new Date().toISOString().split('T')[0];
    });

    const selectedCustomer = computed(() => {
      return customers.value.find(c => c.customer_id === selectedCustomerId.value);
    });

    const soAlreadyAdded = computed(() => {
      return addedSOs.value.some(so => so.so_number === currentSoNumber.value);
    });

    const deliveryItems = computed(() => {
      const items = [];
      addedSOs.value.forEach(so => {
        so.items.forEach(item => {
          if (item.selected && item.deliver_quantity > 0) {
            items.push(item);
          }
        });
      });
      return items;
    });

    const totalDeliveryQuantity = computed(() => {
      return deliveryItems.value.reduce((total, item) => total + (item.deliver_quantity || 0), 0);
    });

    // Methods
    const fetchNextDeliveryNumber = async () => {
      try {
        const response = await axios.get('/deliveries/next-number');
        nextDeliveryNumber.value = response.data.next_delivery_number;
        console.log('Next delivery number:', nextDeliveryNumber.value);
      } catch (error) {
        console.error('Error fetching next delivery number:', error);
        nextDeliveryNumber.value = 'Error loading';
      }
    };

    const fetchCustomers = async () => {
      try {
        isLoading.value = true;
        const response = await axios.get('/customers');
        customers.value = response.data.data || [];
        console.log('Customers loaded:', customers.value.length);
      } catch (error) {
        console.error('Error fetching customers:', error);
        alert('Failed to load customers');
      } finally {
        isLoading.value = false;
      }
    };

    const onCustomerChange = async () => {
      if (!selectedCustomerId.value) {
        availableSOs.value = [];
        addedSOs.value = [];
        return;
      }

      try {
        isLoading.value = true;
        const response = await axios.get(`/sales-orders/outstanding-by-customer/${selectedCustomerId.value}`);
        availableSOs.value = response.data.data || [];
        addedSOs.value = [];
        currentSoNumber.value = '';
        console.log('Outstanding SOs loaded:', availableSOs.value.length);
      } catch (error) {
        console.error('Error fetching outstanding SOs:', error);
        alert('Failed to load outstanding sales orders');
      } finally {
        isLoading.value = false;
      }
    };

    const loadSOItems = async () => {
    if (!currentSoNumber.value) return;

    try {
        isLoading.value = true;
        const selectedSO = availableSOs.value.find(so => so.so_number === currentSoNumber.value);
        if (!selectedSO) return;

        // 🔧 FIX: Ganti endpoint
        const response = await axios.get(`/sales-orders/${selectedSO.so_id}/outstanding-items`);

        const soData = {
        ...selectedSO,
        items: (response.data.data || []).map(item => ({
            ...item,
            selected: false,
            deliver_quantity: 0,
            warehouse_id: '',
            // Use warehouse_stocks from API response
            warehouses: (item.warehouse_stocks || []).map(stock => ({
            warehouse_id: stock.warehouse_id,
            name: stock.warehouse_name,
            available_quantity: stock.available_quantity,
            total_quantity: stock.total_quantity
            })),
            warehouse_stock: null
        }))
        };

        // Update the SO in availableSOs with loaded items
        const soIndex = availableSOs.value.findIndex(so => so.so_number === currentSoNumber.value);
        if (soIndex !== -1) {
        availableSOs.value[soIndex] = soData;
        }

        console.log('SO items loaded:', soData.items.length);
    } catch (error) {
        console.error('Error loading SO items:', error);
        const errorMessage = error.response?.data?.message || 'Failed to load sales order items';
        alert(errorMessage);
    } finally {
        isLoading.value = false;
    }
    };

    const addSOToDelivery = () => {
      if (!currentSoNumber.value || soAlreadyAdded.value) return;

      const soToAdd = availableSOs.value.find(so => so.so_number === currentSoNumber.value);
      if (soToAdd && soToAdd.items) {
        addedSOs.value.push(JSON.parse(JSON.stringify(soToAdd)));
        currentSoNumber.value = '';
        console.log('SO added to delivery:', soToAdd.so_number);
      }
    };

    const removeSOFromDelivery = (soId) => {
      addedSOs.value = addedSOs.value.filter(so => so.so_id !== soId);
      console.log('SO removed from delivery:', soId);
    };

    const updateItemSelection = (soId, soLineId, selected) => {
      const so = addedSOs.value.find(s => s.so_id === soId);
      if (so) {
        const item = so.items.find(i => i.so_line_id === soLineId);
        if (item) {
          item.selected = selected;
          if (!selected) {
            item.deliver_quantity = 0;
            item.warehouse_id = '';
            item.warehouse_stock = null;
          } else {
            // Set default delivery quantity to outstanding quantity
            item.deliver_quantity = item.outstanding_quantity;
          }
        }
      }
    };

    const validateDeliveryQuantity = (item) => {
      if (item.deliver_quantity > item.outstanding_quantity) {
        item.deliver_quantity = item.outstanding_quantity;
      }
      if (item.deliver_quantity < 0) {
        item.deliver_quantity = 0;
      }
    };

    const updateWarehouseStock = async (item) => {
    if (!item.warehouse_id) {
        item.warehouse_stock = null;
        return;
    }

    // Use warehouse data that's already loaded
    const selectedWarehouse = item.warehouses.find(w => w.warehouse_id == item.warehouse_id);
    if (selectedWarehouse) {
        item.warehouse_stock = {
        available_quantity: selectedWarehouse.available_quantity,
        total_quantity: selectedWarehouse.total_quantity
        };
    }
    };

    const getAvailableStock = (item) => {
    if (item.warehouse_stock) {
        return item.warehouse_stock.available_quantity || 0;
    }

    // Fallback: get from warehouse list if warehouse is selected
    if (item.warehouse_id && item.warehouses) {
        const warehouse = item.warehouses.find(w => w.warehouse_id == item.warehouse_id);
        return warehouse ? warehouse.available_quantity : 0;
    }

    return 0;
    };

    const getStockStatusClass = (availableStock, deliverQty) => {
      if (!deliverQty || deliverQty === 0) return 'stock-normal';
      if (availableStock >= deliverQty) return 'stock-sufficient';
      return 'stock-insufficient';
    };

    const resetForm = () => {
      form.value.delivery_date = new Date().toISOString().split('T')[0];
      form.value.shipping_method = '';
      form.value.tracking_number = '';
      selectedCustomerId.value = '';
      availableSOs.value = [];
      currentSoNumber.value = '';
      addedSOs.value = [];

      // Fetch next delivery number again
      fetchNextDeliveryNumber();
    };

    const submitForm = async () => {
      // Validation
      if (deliveryItems.value.length === 0) {
        alert('Please select at least one item to deliver');
        return;
      }

      // Validate all items have warehouse selected
      const itemsWithoutWarehouse = deliveryItems.value.filter(item => !item.warehouse_id);
      if (itemsWithoutWarehouse.length > 0) {
        alert('Please select warehouse for all items');
        return;
      }

      // Validate delivery quantities
      const invalidItems = deliveryItems.value.filter(item =>
        !item.deliver_quantity || item.deliver_quantity <= 0 || item.deliver_quantity > item.outstanding_quantity
      );
      if (invalidItems.length > 0) {
        alert('Please enter valid delivery quantities for all selected items');
        return;
      }

      if (!selectedCustomerId.value) {
        alert('Please select a customer');
        return;
      }

      if (!form.value.delivery_date) {
        alert('Please select delivery date');
        return;
      }

      isSubmitting.value = true;

      try {
        const payload = {
          // Note: delivery_number is no longer required - will be auto-generated
          delivery_date: form.value.delivery_date,
          shipping_method: form.value.shipping_method || null,
          tracking_number: form.value.tracking_number || null,
          customer_id: selectedCustomerId.value,
          items: deliveryItems.value.map(item => ({
            so_line_id: item.so_line_id,
            delivered_quantity: item.deliver_quantity,
            warehouse_id: item.warehouse_id,
            batch_number: null
          }))
        };

        console.log('Submitting delivery payload:', payload);

        const response = await axios.post('/deliveries/create-from-multiple-so', payload);

        alert('Delivery order created successfully!');
        console.log('Delivery created:', response.data.data);

        // Navigate to delivery detail page
        router.push(`/sales/deliveries/${response.data.data.delivery_id}`);
      } catch (error) {
        console.error('Error creating delivery:', error);
        const errorMessage = error.response?.data?.message || 'Failed to create delivery order';
        alert(errorMessage);
      } finally {
        isSubmitting.value = false;
      }
    };

    // Utility methods
    const formatDate = (date) => {
      return new Date(date).toLocaleDateString();
    };

    const formatNumber = (number) => {
      return Number(number).toLocaleString();
    };

    // Initialize
    onMounted(() => {
      console.log('MultiSODeliveryForm mounted');
      fetchCustomers();
      fetchNextDeliveryNumber();
    });

    return {
      form,
      nextDeliveryNumber,
      customers,
      selectedCustomerId,
      availableSOs,
      currentSoNumber,
      addedSOs,
      isSubmitting,
      isLoading,
      today,
      selectedCustomer,
      soAlreadyAdded,
      deliveryItems,
      totalDeliveryQuantity,
      onCustomerChange,
      loadSOItems,
      addSOToDelivery,
      removeSOFromDelivery,
      updateItemSelection,
      validateDeliveryQuantity,
      updateWarehouseStock,
      getAvailableStock,
      getStockStatusClass,
      resetForm,
      submitForm,
      formatDate,
      formatNumber
    };
  }
};
</script>

<style scoped>
.multi-so-delivery-form {
  max-width: 1400px;
  margin: 0 auto;
  padding: 20px;
}

.form-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 30px;
  padding-bottom: 15px;
  border-bottom: 2px solid #e2e8f0;
}

.form-header h2 {
  margin: 0;
  color: #1e293b;
  font-size: 1.8rem;
  font-weight: 600;
}

.form-actions {
  display: flex;
  gap: 10px;
}

.form-section {
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  padding: 25px;
  margin-bottom: 25px;
}

.form-section h3 {
  margin: 0 0 20px 0;
  color: #374151;
  font-size: 1.25rem;
  font-weight: 600;
  padding-bottom: 10px;
  border-bottom: 1px solid #e5e7eb;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 20px;
}

.form-group {
  display: flex;
  flex-direction: column;
}

.form-group label {
  font-weight: 500;
  margin-bottom: 8px;
  color: #374151;
}

.form-control {
  padding: 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  font-size: 0.875rem;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.form-control:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-control:disabled {
  background-color: #f9fafb;
  color: #6b7280;
  cursor: not-allowed;
}

.form-control-static {
  padding-top: 0.65rem;
  padding-bottom: 0.65rem;
  margin-bottom: 0;
  min-height: calc(1.5em + 1.3rem + 2px);
  display: flex;
  align-items: center;
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

.text-muted {
  color: #64748b;
  font-size: 0.75rem;
  margin-top: 0.25rem;
}

.btn {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 0.375rem;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}

.btn-primary {
  background-color: #3b82f6;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background-color: #2563eb;
}

.btn-primary:disabled {
  background-color: #9ca3af;
  cursor: not-allowed;
}

.btn-secondary {
  background-color: #6b7280;
  color: white;
}

.btn-secondary:hover:not(:disabled) {
  background-color: #4b5563;
}

.btn-danger {
  background-color: #ef4444;
  color: white;
}

.btn-danger:hover:not(:disabled) {
  background-color: #dc2626;
}

.btn-sm {
  padding: 0.5rem 1rem;
  font-size: 0.75rem;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr auto;
  gap: 15px;
  align-items: end;
}

.so-input-section {
  background-color: #f8fafc;
  padding: 20px;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
}

.warning-text {
  color: #dc2626;
  font-size: 0.875rem;
  margin-top: 10px;
  font-weight: 500;
}

.so-section {
  background-color: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  margin-bottom: 20px;
  overflow: hidden;
}

.so-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px 20px;
  background-color: #e2e8f0;
  border-bottom: 1px solid #d1d5db;
}

.so-header h4 {
  margin: 0;
  color: #1e293b;
  font-size: 1.1rem;
}

.items-table {
  overflow-x: auto;
}

.items-header {
  display: grid;
  grid-template-columns: 60px 120px 1fr 100px 120px 80px 150px 120px;
  gap: 10px;
  background-color: #f1f5f9;
  padding: 15px 20px;
  border-bottom: 1px solid #e2e8f0;
  font-weight: 600;
  font-size: 0.875rem;
  color: #475569;
}

.item-row {
  display: grid;
  grid-template-columns: 60px 120px 1fr 100px 120px 80px 150px 120px;
  gap: 10px;
  padding: 15px 20px;
  border-bottom: 1px solid #e2e8f0;
  align-items: center;
  transition: background-color 0.2s;
}

.item-row:last-child {
  border-bottom: none;
}

.item-row:hover {
  background-color: #f8fafc;
}

.item-select {
  display: flex;
  justify-content: center;
}

.item-code {
  font-weight: 500;
  color: #1e293b;
}

.item-name {
  color: #374151;
}

.outstanding-qty,
.available-stock {
  text-align: right;
  font-weight: 500;
}

.deliver-qty input {
  width: 100%;
  padding: 0.5rem;
  border: 1px solid #d1d5db;
  border-radius: 0.25rem;
  font-size: 0.875rem;
}

.warehouse select {
  width: 100%;
  padding: 0.5rem;
  border: 1px solid #d1d5db;
  border-radius: 0.25rem;
  font-size: 0.875rem;
}

.stock-sufficient {
  background-color: #f0fdf4;
}

.stock-insufficient {
  background-color: #fef2f2;
}

.stock-normal {
  background-color: white;
}

.delivery-summary {
  background-color: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 20px;
  margin-top: 20px;
}

.delivery-summary h4 {
  margin: 0 0 15px 0;
  color: #1e293b;
}

.summary-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 15px;
}

.stat {
  padding: 10px 15px;
  background-color: white;
  border-radius: 6px;
  border: 1px solid #e5e7eb;
}

@media (max-width: 768px) {
  .form-grid {
    grid-template-columns: 1fr;
  }

  .form-row {
    grid-template-columns: 1fr;
  }

  .items-header,
  .item-row {
    grid-template-columns: 1fr;
    gap: 5px;
  }

  .items-header > div,
  .item-row > div {
    padding: 5px 0;
    border-bottom: 1px solid #e5e7eb;
  }

  .summary-stats {
    grid-template-columns: 1fr;
  }
}
</style>
