<template>
  <div class="multi-so-delivery-form">
    <div class="form-header">
      <h2>📦 Create Delivery Order (Multiple SO)</h2>
      <div class="form-actions">
        <button @click="resetForm" type="button" class="btn btn-secondary">
          <i class="fas fa-undo"></i> Reset
        </button>
      </div>
    </div>

    <form @submit.prevent="submitForm" class="delivery-form-content">
      <!-- Basic Information -->
      <div class="form-section">
        <h3>📋 Basic Information</h3>
        <div class="form-grid">
          <div class="form-group required">
            <label>Delivery Number</label>
            <input
              type="text"
              v-model="form.delivery_number"
              placeholder="DO-2024-001234"
              required
            >
          </div>

          <div class="form-group required">
            <label>Delivery Date</label>
            <input
              type="date"
              v-model="form.delivery_date"
              :min="today"
              required
            >
          </div>

          <div class="form-group">
            <label>Shipping Method</label>
            <input
              type="text"
              v-model="form.shipping_method"
              placeholder="e.g., Truck, Express"
            >
          </div>

          <div class="form-group">
            <label>Tracking Number</label>
            <input
              type="text"
              v-model="form.tracking_number"
              placeholder="Track-123456"
            >
          </div>
        </div>

        <!-- Customer Selection -->
        <div class="form-group required">
          <label>Customer</label>
          <select v-model="selectedCustomerId" @change="onCustomerChange" required>
            <option value="">Select Customer</option>
            <option v-for="customer in customers" :key="customer.customer_id" :value="customer.customer_id">
              {{ customer.name }} ({{ customer.customer_code }})
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

          <div class="items-table-container">
            <table class="items-table">
              <thead>
                <tr>
                  <th>Select</th>
                  <th>Item Code</th>
                  <th>Item Name</th>
                  <th>UOM</th>
                  <th>Ordered</th>
                  <th>Delivered</th>
                  <th>Outstanding</th>
                  <th>Deliver Qty</th>
                  <th>Warehouse</th>
                  <th>Available Stock</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in so.outstanding_items" :key="item.so_line_id">
                  <td>
                    <input
                      type="checkbox"
                      v-model="item.selected"
                      @change="updateItemSelection(so.so_id, item)"
                    >
                  </td>
                  <td>{{ item.item_code }}</td>
                  <td>{{ item.item_name }}</td>
                  <td>{{ item.uom_name }}</td>
                  <td>{{ formatNumber(item.ordered_quantity) }}</td>
                  <td>{{ formatNumber(item.delivered_quantity) }}</td>
                  <td>{{ formatNumber(item.outstanding_quantity) }}</td>
                  <td>
                    <input
                      type="number"
                      v-model="item.deliver_quantity"
                      :max="item.outstanding_quantity"
                      :min="0"
                      step="0.01"
                      :disabled="!item.selected"
                      @input="validateDeliveryQuantity(item)"
                      class="qty-input"
                    >
                  </td>
                  <td>
                    <select
                      v-model="item.warehouse_id"
                      :disabled="!item.selected"
                      @change="updateWarehouseStock(item)"
                    >
                      <option value="">Select Warehouse</option>
                      <option
                        v-for="stock in item.warehouse_stocks"
                        :key="stock.warehouse_id"
                        :value="stock.warehouse_id"
                      >
                        {{ stock.warehouse_name }} ({{ formatNumber(stock.available_quantity) }})
                      </option>
                    </select>
                  </td>
                  <td>
                    <span
                      :class="getStockStatusClass(item.available_stock, item.deliver_quantity)"
                    >
                      {{ formatNumber(item.available_stock || 0) }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Summary -->
      <div class="form-section" v-if="deliveryItems.length > 0">
        <h3>📊 Delivery Summary</h3>
        <div class="summary-grid">
          <div class="summary-card">
            <div class="summary-label">Total SOs</div>
            <div class="summary-value">{{ addedSOs.length }}</div>
          </div>
          <div class="summary-card">
            <div class="summary-label">Total Items</div>
            <div class="summary-value">{{ deliveryItems.length }}</div>
          </div>
          <div class="summary-card">
            <div class="summary-label">Total Quantity</div>
            <div class="summary-value">{{ totalDeliveryQuantity }}</div>
          </div>
        </div>
      </div>

      <!-- Form Actions -->
      <div class="form-footer">
        <div class="form-actions">
          <router-link to="/sales/deliveries" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Deliveries
          </router-link>
          <button
            type="submit"
            :disabled="deliveryItems.length === 0 || isSubmitting"
            class="btn btn-primary btn-lg"
          >
            <i class="fas fa-save"></i>
            {{ isSubmitting ? 'Creating...' : 'Create Delivery Order' }}
          </button>
        </div>
      </div>
    </form>
  </div>
</template>

<script>
import { ref, reactive, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';

export default {
  name: 'MultiSODeliveryForm',
  setup() {
    const router = useRouter();

    // Reactive data
    const form = reactive({
      delivery_number: '',
      delivery_date: '',
      shipping_method: '',
      tracking_number: ''
    });

    const customers = ref([]);
    const selectedCustomerId = ref('');
    const availableSOs = ref([]);
    const currentSoNumber = ref('');
    const addedSOs = ref([]);
    const isSubmitting = ref(false);

    // Computed properties
    const today = computed(() => {
      return new Date().toISOString().split('T')[0];
    });

    const selectedCustomer = computed(() => {
      return customers.value.find(c => c.customer_id == selectedCustomerId.value);
    });

    const soAlreadyAdded = computed(() => {
      return addedSOs.value.some(so => so.so_number === currentSoNumber.value);
    });

    const deliveryItems = computed(() => {
      const items = [];
      addedSOs.value.forEach(so => {
        so.outstanding_items.forEach(item => {
          if (item.selected && item.deliver_quantity > 0) {
            items.push({
              so_line_id: item.so_line_id,
              item_code: item.item_code,
              item_name: item.item_name,
              deliver_quantity: item.deliver_quantity,
              warehouse_id: item.warehouse_id,
              so_number: so.so_number
            });
          }
        });
      });
      return items;
    });

    const totalDeliveryQuantity = computed(() => {
      return deliveryItems.value.reduce((total, item) => total + Number(item.deliver_quantity), 0);
    });

    // Methods
    const fetchCustomers = async () => {
      try {
        const response = await axios.get('/customers');
        customers.value = response.data.data;
      } catch (error) {
        console.error('Error fetching customers:', error);
        alert('Failed to load customers');
      }
    };

    const onCustomerChange = async () => {
      if (!selectedCustomerId.value) {
        availableSOs.value = [];
        addedSOs.value = [];
        return;
      }

      try {
        const response = await axios.get(`/sales-orders/outstanding-by-customer/${selectedCustomerId.value}`);
        availableSOs.value = response.data.data;
      } catch (error) {
        console.error('Error fetching outstanding SOs:', error);
        alert('Failed to load outstanding sales orders');
      }
    };

    const loadSOItems = async () => {
      if (!currentSoNumber.value) return;

      const selectedSO = availableSOs.value.find(so => so.so_number === currentSoNumber.value);
      if (!selectedSO) return;

      try {
        const response = await axios.get(`/deliveries/outstanding-items/${selectedSO.so_id}`);
        selectedSO.outstanding_items = response.data.data.outstanding_items.map(item => ({
          ...item,
          selected: false,
          deliver_quantity: 0,
          warehouse_id: '',
          available_stock: 0
        }));
      } catch (error) {
        console.error('Error loading SO items:', error);
        alert('Failed to load SO items');
      }
    };

    const addSOToDelivery = () => {
      if (!currentSoNumber.value || soAlreadyAdded.value) return;

      const soToAdd = availableSOs.value.find(so => so.so_number === currentSoNumber.value);
      if (soToAdd && soToAdd.outstanding_items) {
        addedSOs.value.push({ ...soToAdd });
        currentSoNumber.value = '';
      }
    };

    const removeSOFromDelivery = (soId) => {
      addedSOs.value = addedSOs.value.filter(so => so.so_id !== soId);
    };

    const updateItemSelection = (soId, item) => {
      if (item.selected) {
        item.deliver_quantity = item.outstanding_quantity;
      } else {
        item.deliver_quantity = 0;
        item.warehouse_id = '';
        item.available_stock = 0;
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

    const updateWarehouseStock = (item) => {
      if (!item.warehouse_id) {
        item.available_stock = 0;
        return;
      }

      const warehouseStock = item.warehouse_stocks.find(stock => stock.warehouse_id == item.warehouse_id);
      item.available_stock = warehouseStock ? warehouseStock.available_quantity : 0;
    };

    const getStockStatusClass = (availableStock, deliverQty) => {
      if (!deliverQty || deliverQty === 0) return 'stock-normal';
      if (availableStock >= deliverQty) return 'stock-sufficient';
      return 'stock-insufficient';
    };

    const resetForm = () => {
      form.delivery_number = '';
      form.delivery_date = '';
      form.shipping_method = '';
      form.tracking_number = '';
      selectedCustomerId.value = '';
      availableSOs.value = [];
      currentSoNumber.value = '';
      addedSOs.value = [];
    };

    const submitForm = async () => {
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

      isSubmitting.value = true;

      try {
        const payload = {
          delivery_number: form.delivery_number,
          delivery_date: form.delivery_date,
          shipping_method: form.shipping_method,
          tracking_number: form.tracking_number,
          customer_id: selectedCustomerId.value,
          items: deliveryItems.value.map(item => ({
            so_line_id: item.so_line_id,
            delivered_quantity: item.deliver_quantity,
            warehouse_id: item.warehouse_id,
            batch_number: null
          }))
        };

        const response = await axios.post('/deliveries/create-from-multiple-so', payload);

        alert('Delivery order created successfully!');
        router.push(`/sales/deliveries/${response.data.data.delivery_id}`);
      } catch (error) {
        console.error('Error creating delivery:', error);
        alert(error.response?.data?.message || 'Failed to create delivery order');
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
      fetchCustomers();
    });

    return {
      form,
      customers,
      selectedCustomerId,
      availableSOs,
      currentSoNumber,
      addedSOs,
      isSubmitting,
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
  margin-bottom: 20px;
}

.form-group.required label::after {
  content: " *";
  color: #ef4444;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  font-weight: 500;
  color: #374151;
}

.form-group input,
.form-group select,
.form-group textarea {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.form-group input:focus,
.form-group select:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-row {
  display: flex;
  gap: 15px;
  align-items: end;
}

.so-input-section {
  background: #f8fafc;
  padding: 20px;
  border-radius: 6px;
  border: 1px solid #e2e8f0;
}

.warning-text {
  color: #f59e0b;
  font-size: 14px;
  margin-top: 8px;
  font-weight: 500;
}

.so-section {
  margin-bottom: 30px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 20px;
  background: #fafafa;
}

.so-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 15px;
  padding-bottom: 10px;
  border-bottom: 1px solid #d1d5db;
}

.so-header h4 {
  margin: 0;
  color: #1f2937;
  font-size: 1.1rem;
}

.items-table-container {
  overflow-x: auto;
}

.items-table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 15px;
  background: white;
  border-radius: 6px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.items-table th,
.items-table td {
  padding: 12px 8px;
  text-align: left;
  border-bottom: 1px solid #e5e7eb;
}

.items-table th {
  background: #f9fafb;
  font-weight: 600;
  color: #374151;
  font-size: 13px;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.items-table tr:hover {
  background: #f9fafb;
}

.qty-input {
  width: 80px !important;
  padding: 6px 8px !important;
  font-size: 13px;
}

.stock-sufficient {
  color: #059669;
  font-weight: 500;
}

.stock-insufficient {
  color: #dc2626;
  font-weight: 500;
}

.stock-normal {
  color: #6b7280;
}

.summary-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 20px;
}

.summary-card {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 20px;
  border-radius: 8px;
  text-align: center;
}

.summary-label {
  font-size: 14px;
  opacity: 0.9;
  margin-bottom: 8px;
}

.summary-value {
  font-size: 24px;
  font-weight: 700;
}

.form-footer {
  background: white;
  border-radius: 8px;
  padding: 25px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.form-actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 15px;
}

.btn {
  padding: 10px 20px;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  transition: all 0.2s;
}

.btn-sm {
  padding: 6px 12px;
  font-size: 12px;
}

.btn-lg {
  padding: 15px 30px;
  font-size: 16px;
}

.btn-primary {
  background: #3b82f6;
  color: white;
}

.btn-secondary {
  background: #6b7280;
  color: white;
}

.btn-danger {
  background: #ef4444;
  color: white;
}

.btn:hover:not(:disabled) {
  opacity: 0.9;
  transform: translateY(-1px);
}

.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  transform: none;
}

@media (max-width: 768px) {
  .multi-so-delivery-form {
    padding: 15px;
  }

  .form-grid {
    grid-template-columns: 1fr;
  }

  .form-row {
    flex-direction: column;
    align-items: stretch;
  }

  .form-actions {
    flex-direction: column;
  }

  .items-table {
    font-size: 12px;
  }

  .items-table th,
  .items-table td {
    padding: 8px 4px;
  }
}
</style>
