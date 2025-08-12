<!-- src/views/manufacturing/RoutingDetail.vue -->
<template>
    <div class="routing-detail-container">
      <!-- Header with actions -->
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="page-title">Routing Detail</h1>
        <div class="action-buttons">
          <router-link to="/manufacturing/routings" class="btn btn-secondary mr-2">
            <i class="fas fa-list mr-1"></i> Routing List
          </router-link>
           <router-link :to="`/manufacturing/routings/${routingId}/print`" class="btn btn-info mr-2" target="_blank">
            <i class="fas fa-print mr-1"></i> Print
          </router-link>
          <router-link :to="`/manufacturing/routings/${routingId}/edit`" class="btn btn-primary mr-2">
            <i class="fas fa-edit mr-1"></i> Edit Routing
          </router-link>
          <button @click="confirmDelete" class="btn btn-danger">
            <i class="fas fa-trash-alt mr-1"></i> Delete
          </button>
        </div>
      </div>

      <!-- Loading indicator -->
      <div v-if="isLoading" class="text-center py-5">
        <i class="fas fa-spinner fa-spin fa-2x"></i>
        <p class="mt-2">Loading routing data...</p>
      </div>

      <div v-else>
        <!-- Routing Information Card -->
        <div class="card mb-4">
          <div class="card-header">
            <h2 class="card-title">Routing Information</h2>
          </div>
          <div class="card-body">
            <!-- Main Information Grid -->
            <div class="info-grid-simple">
              <div class="info-row">
                <div class="info-label">Routing Code:</div>
                <div class="info-value">{{ routing.routing_code }}</div>
                <div class="info-label">Product:</div>
                <div class="info-value">
                  {{ routing.item ? `${routing.item.name} (${routing.item.item_code})` : '-' }}
                </div>
              </div>

              <div class="info-row">
                <div class="info-label">Revised:</div>
                <div class="info-value">{{ routing.revision }}</div>
                <div class="info-label">Effective Date:</div>
                <div class="info-value">{{ formatDate(routing.effective_date) }}</div>
              </div>

              <div class="info-row">
                <div class="info-label">Status:</div>
                <div class="info-value">
                  <span
                    class="badge"
                    :class="{
                      'badge-success': routing.status === 'Active',
                      'badge-warning': routing.status === 'Draft',
                      'badge-secondary': routing.status === 'Obsolete'
                    }"
                  >
                    {{ routing.status }}
                  </span>
                </div>
                <div class="info-label">Total Operation:</div>
                <div class="info-value">{{ operations.length }}</div>
              </div>
            </div>

            <!-- Additional Information - Simple Version -->
            <div class="additional-info-simple mt-4">
              <div class="info-row">
                <div class="info-label">Cavity:</div>
                <div class="info-value">{{ routing.cavity || 'N/A' }}</div>
                <div class="info-label">Process:</div>
                <div class="info-value">{{ routing.process || 'N/A' }}</div>
              </div>

              <div class="info-row">
                <div class="info-label">Set Jump:</div>
                <div class="info-value">{{ routing.set_jump || 'N/A' }}</div>
                <div class="info-label">Yield:</div>
                <div class="info-value">
                  <span v-if="routing.yield" class="badge badge-info">
                    {{ routing.yield }}
                  </span>
                  <span v-else class="text-muted">N/A</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Operations Card -->
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h2 class="card-title">Routing Operation</h2>
            <button @click="addOperation" class="btn btn-primary">
              <i class="fas fa-plus mr-1"></i> Add Operation
            </button>
          </div>
          <div class="card-body p-0">
            <DataTable
              :columns="operationColumns"
              :items="sortedOperations"
              :is-loading="isLoadingOperations"
              empty-title="No operations yet"
              empty-message="Add operations for this routing using the 'Add Operation' button"
              initial-sort-key="sequence"
              initial-sort-order="asc"
            >
              <!-- Work Flow column -->
              <template #work_flow="{ value }">
                {{ value || '-' }}
              </template>

              <!-- Models column -->
              <template #models="{ value }">
                {{ getModelDisplayName(value) || '-' }}
              </template>

              <!-- Run Time column -->
              <template #run_time="{ value, item }">
                {{ value }} {{ getUnitName(value, item) }}
              </template>

              <!-- Setup Time column -->
              <template #setup_time="{ value, item }">
                {{ value }} {{ getUnitName(value, item) }}
              </template>

              <!-- Total Time column -->
              <template #total_time="{ value, item }">
                <strong class="text-primary">
                  {{ value || (item.setup_time + item.run_time) }} {{ getUnitName(value, item) }}
                </strong>
              </template>

              <!-- Yield1 column -->
              <template #yield1="{ value }">
                {{ value !== null && value !== undefined ? value : '0' }}
              </template>

              <!-- Cost columns -->
              <template #labor_cost="{ value }">
                {{ formatCurrency(value) }}
              </template>

              <template #overhead_cost="{ value }">
                {{ formatCurrency(value) }}
              </template>


              <!-- Actions column -->
              <template #actions="{ item }">
                <div class="btn-group btn-group-sm">
                  <button
                    @click="editOperation(item)"
                    class="btn btn-outline-primary"
                    title="Edit Operation"
                  >
                    <i class="fas fa-edit"></i>
                  </button>
                  <button
                    @click="confirmDeleteOperation(item)"
                    class="btn btn-outline-danger"
                    title="Delete Operation"
                  >
                    <i class="fas fa-trash"></i>
                  </button>
                </div>
              </template>
            </DataTable>
          </div>
        </div>
      </div>

      <!-- Operation Form Modal - Enhanced Version -->
      <div v-if="showOperationModal" class="modal">
        <div class="modal-backdrop" @click="cancelOperationForm"></div>
        <div class="modal-content modal-lg">
          <div class="modal-header">
            <h2>{{ selectedOperation ? 'Edit Operation' : 'Add New Operation' }}</h2>
            <button class="close-btn" @click="cancelOperationForm">
              <i class="fas fa-times"></i>
            </button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="saveOperation" class="operation-form">

              <!-- Basic Information Section -->
              <div class="form-section">
                <h3 class="section-title">Basic Information</h3>

                <div class="form-row">
                  <div class="form-group col-md-6">
                    <label for="operation_name">Operation Name <span class="required">*</span></label>
                    <input
                      id="operation_name"
                      v-model="operationForm.operation_name"
                      type="text"
                      class="form-control"
                      :class="{ 'is-invalid': operationErrors.operation_name }"
                      placeholder="Enter operation name"
                      required
                    />
                    <div v-if="operationErrors.operation_name" class="invalid-feedback">
                      {{ operationErrors.operation_name[0] }}
                    </div>
                  </div>

                  <div class="form-group col-md-6">
                    <label for="workcenter_id">Work Center <span class="required">*</span></label>
                    <select
                      id="workcenter_id"
                      v-model="operationForm.workcenter_id"
                      class="form-control"
                      :class="{ 'is-invalid': operationErrors.workcenter_id }"
                      required
                    >
                      <option value="" disabled>-- Select Work Center --</option>
                      <option
                        v-for="wc in workCenters"
                        :key="wc.workcenter_id"
                        :value="wc.workcenter_id"
                      >
                        {{ wc.name }} ({{ wc.code }})
                      </option>
                    </select>
                    <div v-if="operationErrors.workcenter_id" class="invalid-feedback">
                      {{ operationErrors.workcenter_id[0] }}
                    </div>
                  </div>
                </div>

                <div class="form-row">
                  <div class="form-group col-md-6">
                    <label for="work_flow">Work Flow</label>
                    <input
                      id="work_flow"
                      v-model="operationForm.work_flow"
                      type="text"
                      class="form-control"
                      :class="{ 'is-invalid': operationErrors.work_flow }"
                      placeholder="Enter work flow"
                    />
                    <div v-if="operationErrors.work_flow" class="invalid-feedback">
                      {{ operationErrors.work_flow[0] }}
                    </div>
                  </div>

                  <div class="form-group col-md-6">
                    <label for="models">
                      Models
                    </label>
                    <select
                      id="models"
                      v-model="operationForm.models"
                      class="form-control model-dropdown"
                      :class="{
                        'is-invalid': operationErrors.models,
                        'loading': isLoadingModelItems
                      }"
                      :disabled="isLoadingModelItems"
                    >
                      <option value="">-- Select Model --</option>
                      <option
                        v-for="item in modelItems"
                        :key="item.item_id"
                        :value="item.item_code"
                      >
                        {{ item.item_code }} - {{ item.name }}
                      </option>
                    </select>
                    <small v-if="isLoadingModelItems" class="help-text loading-text">
                      <i class="fas fa-spinner fa-spin mr-1"></i> Loading models...
                    </small>
                    <small v-else-if="modelItems.length === 0" class="help-text text-warning">
                      <i class="fas fa-exclamation-triangle mr-1"></i> No models available
                    </small>
                    <div v-if="operationErrors.models" class="invalid-feedback">
                      {{ operationErrors.models[0] }}
                    </div>
                  </div>
                </div>
              </div>

            <div class="form-section">
              <h3 class="section-title">
                Tolerance Specification
              </h3>

              <div class="form-row">
                <div class="form-group col-md-6">
                  <label for="toleransi_min">
                    Tolerance Min
                  </label>
                  <input
                    id="toleransi_min"
                    v-model="operationForm.toleransi_min"
                    type="text"
                    class="form-control"
                    :class="{ 'is-invalid': operationErrors.toleransi_min }"
                    placeholder="Minimum tolerance value"
                  />
                  <div v-if="operationErrors.toleransi_min" class="invalid-feedback">
                    {{ operationErrors.toleransi_min[0] }}
                  </div>
                </div>

                <div class="form-group col-md-6">
                  <label for="toleransi_max">
                    Tolerance Max
                  </label>
                  <input
                    id="toleransi_max"
                    v-model="operationForm.toleransi_max"
                    type="text"
                    class="form-control"
                    :class="{ 'is-invalid': operationErrors.toleransi_max }"
                    placeholder="Maximum tolerance value"
                  />
                  <div v-if="operationErrors.toleransi_max" class="invalid-feedback">
                    {{ operationErrors.toleransi_max[0] }}
                  </div>
                </div>
              </div>

              <div class="alert alert-info mt-2">
                <strong>Sample Format:</strong> ±0.1, +0.05/-0.02, 0.1-0.5
              </div>
            </div>

            <!-- Additional Fields Section -->
            <div class="form-section">
              <h3 class="section-title">
                Additional Specifications
              </h3>

              <div class="form-row">
                <div class="form-group col-md-8">
                  <label for="dimensi">
                    Dimension
                  </label>
                  <input
                    id="dimensi"
                    v-model="operationForm.dimensi"
                    type="text"
                    class="form-control"
                    :class="{ 'is-invalid': operationErrors.dimensi }"
                    placeholder="Specification dimension"
                    maxlength="100"
                  />
                  <div v-if="operationErrors.dimensi" class="invalid-feedback">
                    {{ operationErrors.dimensi[0] }}
                  </div>
                </div>

                <div class="form-group col-md-4">
                  <label for="yield1">
                    Yield 1
                  </label>
                  <input
                    id="yield1"
                    v-model.number="operationForm.yield1"
                    type="number"
                    class="form-control"
                    :class="{ 'is-invalid': operationErrors.yield1 }"
                    placeholder="Enter yield value"
                    step="0.0001"
                    min="0"
                    max="100"
                  />
                  <div v-if="operationErrors.yield1" class="invalid-feedback">
                    {{ operationErrors.yield1[0] }}
                  </div>
                </div>
              </div>
            </div>

              <!-- Time & Sequence Section -->
              <div class="form-section">
                <h3 class="section-title">
                  Time & Sequence
                </h3>

                <div class="form-row">
                  <div class="form-group col-md-3">
                    <label for="sequence">
                      Sequence <span class="required">*</span>
                    </label>
                    <input
                      id="sequence"
                      v-model.number="operationForm.sequence"
                      type="number"
                      min="1"
                      step="10"
                      class="form-control"
                      :class="{ 'is-invalid': operationErrors.sequence }"
                      required
                    />
                    <div v-if="operationErrors.sequence" class="invalid-feedback">
                      {{ operationErrors.sequence[0] }}
                    </div>
                  </div>

                  <div class="form-group col-md-3">
                    <label for="uom_id">
                      Unit of Measure <span class="required">*</span>
                    </label>
                    <select
                      id="uom_id"
                      v-model="operationForm.uom_id"
                      class="form-control"
                      :class="{ 'is-invalid': operationErrors.uom_id }"
                      required
                    >
                      <option value="" disabled>-- Select UOM --</option>
                      <option
                        v-for="uom in unitOfMeasures"
                        :key="uom.uom_id"
                        :value="uom.uom_id"
                      >
                        {{ uom.name }} ({{ uom.symbol }})
                      </option>
                    </select>
                    <div v-if="operationErrors.uom_id" class="invalid-feedback">
                      {{ operationErrors.uom_id[0] }}
                    </div>
                  </div>

                  <div class="form-group col-md-3">
                    <label for="setup_time">
                      Setup Time (Minutes) <span class="required">*</span>
                    </label>
                    <input
                      id="setup_time"
                      v-model.number="operationForm.setup_time"
                      type="number"
                      min="0"
                      step="0.1"
                      class="form-control"
                      :class="{ 'is-invalid': operationErrors.setup_time }"
                      required
                    />
                    <div v-if="operationErrors.setup_time" class="invalid-feedback">
                      {{ operationErrors.setup_time[0] }}
                    </div>
                  </div>

                  <div class="form-group col-md-3">
                    <label for="run_time">
                      Process Time (Second) <span class="required">*</span>
                    </label>
                    <input
                      id="run_time"
                      v-model.number="operationForm.run_time"
                      type="number"
                      min="0"
                      step="0.000001"
                      class="form-control"
                      :class="{ 'is-invalid': operationErrors.run_time }"
                      required
                    />
                    <div v-if="operationErrors.run_time" class="invalid-feedback">
                      {{ operationErrors.run_time[0] }}
                    </div>
                  </div>
                </div>

                <div class="form-row">
                  <div class="form-group col-md-4">
                    <label>
                      Total Time (Calculated)
                    </label>
                    <input
                      type="text"
                      class="form-control calculated-field"
                      :value="totalOperationTime !== undefined && totalOperationTime !== null ? totalOperationTime.toFixed(4) : '0.0000'"
                      readonly
                    />
                    <small class="help-text">
                      Setup Time + Process Time
                    </small>
                  </div>

                  <div class="form-group col-md-4">
                    <label>Selected Model Info</label>
                    <div class="model-info-display">
                      <span v-if="selectedModelInfo" class="badge badge-info">
                        {{ selectedModelInfo.item_code }} - {{ selectedModelInfo.name }}
                      </span>
                      <span v-else class="text-muted">
                        <i class="fas fa-info-circle mr-1"></i>
                        No model selected
                      </span>
                    </div>
                    <small class="help-text">Currently selected model information</small>
                  </div>
                </div>
              </div>

              <!-- Form Actions -->
              <div class="form-actions">
                <button type="button" class="btn btn-secondary" @click="cancelOperationForm">
                  <i class="fas fa-times mr-1"></i>
                  Cancel
                </button>
                <button type="submit" class="btn btn-primary" :disabled="isSavingOperation">
                  <i class="fas fa-save mr-1" :class="{ 'fa-spin': isSavingOperation }"></i>
                  {{ isSavingOperation ? 'Saving...' : (selectedOperation ? 'Update Operation' : 'Save Operation') }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <!-- Confirmation Modal for Delete Routing -->
      <ConfirmationModal
        v-if="showDeleteModal"
        title="Delete Routing"
        :message="`Are you sure you want to delete routing <strong>${routing.routing_code}</strong>?<br>This action cannot be undone.`"
        confirm-button-text="Delete"
        confirm-button-class="btn btn-danger"
        @confirm="deleteRouting"
        @close="showDeleteModal = false"
      />

      <!-- Confirmation Modal for Delete Operation -->
      <ConfirmationModal
        v-if="showDeleteOperationModal"
        title="Delete Operation"
        :message="`Are you sure you want to delete operation <strong>${selectedOperation?.operation_name}</strong>?<br>This action cannot be undone.`"
        confirm-button-text="Delete"
        confirm-button-class="btn btn-danger"
        @confirm="deleteOperation"
        @close="showDeleteOperationModal = false"
      />
    </div>
</template>

<script>
import { ref, reactive, computed, onMounted, watch } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import axios from 'axios';

export default {
  name: 'RoutingDetail',
  setup() {
    const router = useRouter();
    const route = useRoute();
const routingId = computed(() => parseInt(route.params.id, 10));

    const isLoading = ref(true);
    const isLoadingOperations = ref(true);
    const isLoadingModelItems = ref(false);
    const routing = ref({});
    const operations = ref([]);
    const workCenters = ref([]);
    const unitOfMeasures = ref([]);
    const modelItems = ref([]);
    const defaultUomId = ref('');

    const selectedOperation = ref(null);
    const showOperationModal = ref(false);
    const isSavingOperation = ref(false);
    const operationErrors = ref({});

    const showDeleteModal = ref(false);
    const showDeleteOperationModal = ref(false);

    // Initial operation form values
    const operationForm = reactive({
      workcenter_id: '',
      operation_name: '',
      work_flow: '',
      models: '',
      dimensi:'',
      toleransi_max:'',
      toleransi_min:'',
      sequence: 10,
      setup_time: 0,
      run_time: 0,
      uom_id: '',
      labor_cost: 0,
      overhead_cost: 0,
      yield1: 0,
    });

    // Computed property for total time safely calculated
    const totalOperationTime = computed(() => {
      const setup = Number(operationForm.setup_time) || 0;
      const run = Number(operationForm.run_time) || 0;
      return setup + run;
    });

    // Computed property for selected model information
    const selectedModelInfo = computed(() => {
      if (!operationForm.models || !modelItems.value.length) return null;
      return modelItems.value.find(item => item.item_code === operationForm.models);
    });

    // Operation table columns
    const operationColumns = [
      { key: 'sequence', label: 'Seq', sortable: true, width: '60px' },
      { key: 'operation_name', label: 'Operation Name', sortable: true },
      { key: 'work_center_name', label: 'Work Center' },
      { key: 'work_flow', label: 'Work Flow', sortable: true },
      { key: 'models', label: 'Models', sortable: true },
      { key: 'dimensi', label: 'Dimension', sortable: true },
      { key: 'setup_time', label: 'Setup Time' },
      { key: 'run_time', label: 'Process Time' },
      { key: 'total_time', label: 'Total Time' },
      { key: 'toleransi_min', label: 'Tol. Min', sortable: true },
      { key: 'toleransi_max', label: 'Tol. Max', sortable: true },
      { key: 'yield1', label: 'Yield 1 (PCS)', sortable: true },
      { key: 'actions', label: 'Actions', width: '100px' },
    ];

    // Sort operations by sequence
    const sortedOperations = computed(() => {
      return [...operations.value].sort((a, b) => a.sequence - b.sequence);
    });

    // Format date
    const formatDate = (dateString) => {
      if (!dateString) return '-';
      const date = new Date(dateString);
      return date.toLocaleDateString('en-US', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
      });
    };

    // Format currency
    const formatCurrency = (value) => {
      if (value === null || value === undefined) return '-';
      return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
      }).format(value);
    };

    // Get unit of measure name based on ID
    const getUnitName = (value, item) => {
      if (!item || !item.unit_of_measure) return '';
      return item.unit_of_measure.symbol || '';
    };

    // Get model display name from item_code for table display
    const getModelDisplayName = (itemCode) => {
      if (!itemCode) return '';
      const modelItem = modelItems.value.find(item => item.item_code === itemCode);
      return modelItem ? `${modelItem.item_code}` : itemCode;
    };

    // Load routing data
    const loadRouting = async () => {
      if (isNaN(routingId.value)) {
        console.error('Invalid routing ID:', route.params.id);
        alert('Invalid routing ID. Please check the URL.');
        router.push('/manufacturing/routings');
        return;
      }

      isLoading.value = true;
      try {
        const response = await axios.get(`/routings/${routingId.value}`);
        routing.value = response.data.data;
      } catch (error) {
        console.error('Error loading routing:', error);
        if (error.response?.status === 404) {
          alert('Routing not found.');
          router.push('/manufacturing/routings');
        } else {
          alert('Failed to load routing data. Please try again.');
        }
      } finally {
        isLoading.value = false;
      }
    };

    // Load operations
    const loadOperations = async () => {
      if (isNaN(routingId.value)) {
        return;
      }

      isLoadingOperations.value = true;
      try {
        const response = await axios.get(`/routings/${routingId.value}/operations`);
        console.log('Operations data:', response.data.data);
        // Map operations to add work_center_name property and format total_time
        operations.value = response.data.data.map(op => ({
          ...op,
          work_center_name: op.work_center ? op.work_center.name : '-',
          total_time: op.total_time || (op.setup_time + op.run_time)
        }));
      } catch (error) {
        console.error('Error loading operations:', error);
        alert('Failed to load operations data. Please try again.');
      } finally {
        isLoadingOperations.value = false;
      }
    };

    // Load work centers for dropdown
    const loadWorkCenters = async () => {
      try {
        const response = await axios.get('/work-centers');
        workCenters.value = response.data.data;
      } catch (error) {
        console.error('Error loading work centers:', error);
      }
    };

    // Load units of measure for dropdown
    const loadUnitOfMeasures = async () => {
      try {
        const response = await axios.get('/uoms');
        unitOfMeasures.value = response.data.data;
        // Find the uom_id for "MNT" and set defaultUomId
        const mntUom = unitOfMeasures.value.find(uom => uom.symbol === 'MNT' || uom.name === 'MNT');
        if (mntUom) {
          defaultUomId.value = mntUom.uom_id;
        }
      } catch (error) {
        console.error('Error loading units of measure:', error);
      }
    };

    // Load model items for dropdown
    const loadModelItems = async () => {
      isLoadingModelItems.value = true;
      try {
        const response = await axios.get('/routings/model-items');
        modelItems.value = response.data.data || [];
        console.log('Model items loaded:', modelItems.value.length);
      } catch (error) {
        console.error('Error loading model items:', error);
        modelItems.value = [];
      } finally {
        isLoadingModelItems.value = false;
      }
    };

    // Edit operation
    const editOperation = (operation) => {
      selectedOperation.value = operation;

      // Copy operation data to form
      operationForm.workcenter_id = operation.workcenter_id;
      operationForm.operation_name = operation.operation_name;
      operationForm.work_flow = operation.work_flow || '';
      operationForm.models = operation.models || '';
      operationForm.dimensi = operation.dimensi || '';
      operationForm.toleransi_max = operation.toleransi_max || '';
      operationForm.toleransi_min = operation.toleransi_min || '';
      operationForm.sequence = operation.sequence;
      operationForm.setup_time = operation.setup_time;
      operationForm.run_time = operation.run_time;
      operationForm.uom_id = operation.uom_id;
      operationForm.labor_cost = operation.labor_cost || 0;
      operationForm.overhead_cost = operation.overhead_cost || 0;
      operationForm.yield1 = operation.yield1 || 0;

      showOperationModal.value = true;
    };

    // Reset operation form
    const resetOperationForm = () => {
      selectedOperation.value = null;
      operationForm.workcenter_id = '';
      operationForm.operation_name = '';
      operationForm.work_flow = '';
      operationForm.models = '';
      operationForm.dimensi = '';
      operationForm.toleransi_max = '';
      operationForm.toleransi_min = '';
      operationForm.sequence = operations.value.length > 0
        ? Math.max(...operations.value.map(op => op.sequence)) + 10
        : 10;
      operationForm.setup_time = 0;
      operationForm.run_time = 0;
      operationForm.uom_id = defaultUomId.value;
      operationForm.labor_cost = 0;
      operationForm.overhead_cost = 0;
      operationForm.yield1 = 0; // Ensure yield1 is initialized to 0
      operationErrors.value = {};
    };

    // Cancel operation form
    const cancelOperationForm = () => {
      showOperationModal.value = false;
      resetOperationForm();
    };

    // Save operation
    const saveOperation = async () => {
      isSavingOperation.value = true;
      operationErrors.value = {};

      try {
        // Ensure yield1 is never null/undefined, default to 0
        const formData = {
          ...operationForm,
          yield1: operationForm.yield1 !== null && operationForm.yield1 !== undefined ? operationForm.yield1 : 0
        };

        if (selectedOperation.value) {
          // Update existing operation
          await axios.put(
            `/routings/${routingId.value}/operations/${selectedOperation.value.operation_id}`,
            formData
          );
        } else {
          // Create new operation
          await axios.post(
            `/routings/${routingId.value}/operations`,
            formData
          );
        }

        await loadOperations(); // Reload operations
        showOperationModal.value = false;
        resetOperationForm();

        // Show success message
        alert(selectedOperation.value ? 'Operation updated successfully!' : 'Operation created successfully!');
      } catch (error) {
        console.error('Error saving operation:', error);

        if (error.response && error.response.data && error.response.data.errors) {
          operationErrors.value = error.response.data.errors;
        } else {
          const message = error.response?.data?.message || 'Failed to save operation. Please try again.';
          alert(message);
        }
      } finally {
        isSavingOperation.value = false;
      }
    };

    // Confirm delete routing
    const confirmDelete = () => {
      showDeleteModal.value = true;
    };

    // Delete routing
    const deleteRouting = async () => {
      try {
        await axios.delete(`/routings/${routingId.value}`);
        router.push('/manufacturing/routings');
      } catch (error) {
        console.error('Error deleting routing:', error);

        if (error.response && error.response.data && error.response.data.message) {
          alert(error.response.data.message);
        } else {
          alert('Failed to delete routing. Please try again.');
        }

        showDeleteModal.value = false;
      }
    };

    // Confirm delete operation
    const confirmDeleteOperation = (operation) => {
      selectedOperation.value = operation;
      showDeleteOperationModal.value = true;
    };

    // Delete operation
    const deleteOperation = async () => {
      try {
        await axios.delete(
          `/routings/${routingId.value}/operations/${selectedOperation.value.operation_id}`
        );
        await loadOperations(); // Reload operations
        showDeleteOperationModal.value = false;
        alert('Operation deleted successfully!');
      } catch (error) {
        console.error('Error deleting operation:', error);

        if (error.response && error.response.data && error.response.data.message) {
          alert(error.response.data.message);
        } else {
          alert('Failed to delete operation. Please try again.');
        }

        showDeleteOperationModal.value = false;
      }
    };

    // Add new operation
    const addOperation = () => {
      selectedOperation.value = null;
      resetOperationForm();
      showOperationModal.value = true;
    };

    // Watch for modal closing to clear errors
    watch(showOperationModal, (newValue) => {
      if (!newValue) {
        operationErrors.value = {};
      }
    });

    // Load data on component mount
    onMounted(async () => {
      try {
        await Promise.all([
          loadRouting(),
          loadOperations(),
          loadWorkCenters(),
          loadUnitOfMeasures(),
          loadModelItems()
        ]);
      } catch (error) {
        console.error('Error loading initial data:', error);
      }
    });

    return {
      routingId,
      isLoading,
      isLoadingOperations,
      isLoadingModelItems,
      routing,
      operations,
      sortedOperations,
      operationColumns,
      operationForm,
      operationErrors,
      selectedOperation,
      showOperationModal,
      isSavingOperation,
      workCenters,
      unitOfMeasures,
      modelItems,
      showDeleteModal,
      showDeleteOperationModal,
      totalOperationTime,
      selectedModelInfo,
      formatDate,
      formatCurrency,
      getUnitName,
      getModelDisplayName,
      editOperation,
      cancelOperationForm,
      saveOperation,
      confirmDelete,
      deleteRouting,
      confirmDeleteOperation,
      deleteOperation,
      addOperation,
    };
  },
};
</script>

<style scoped>
/* Container styling */
.routing-detail-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 1.5rem;
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
  color: #334155;
}

/* Header styling */
.page-title {
  font-size: 1.75rem;
  font-weight: 600;
  color: #1e293b;
  margin: 0;
}

.d-flex {
  display: flex;
}

.justify-content-between {
  justify-content: space-between;
}

.align-items-center {
  align-items: center;
}

.mb-3 {
  margin-bottom: 1.5rem;
}

.action-buttons {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

/* Button styling */
.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.65rem 1rem;
  font-size: 0.95rem;
  font-weight: 500;
  line-height: 1.5;
  border-radius: 0.5rem;
  border: 1px solid transparent;
  transition: all 0.2s ease;
  cursor: pointer;
  text-decoration: none;
  gap: 0.5rem;
}

.btn-primary {
  background-color: #3b82f6;
  border-color: #3b82f6;
  color: white;
}

.btn-primary:hover {
  background-color: #2563eb;
  border-color: #2563eb;
  box-shadow: 0 2px 4px rgba(37, 99, 235, 0.25);
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-secondary {
  background-color: #f1f5f9;
  border-color: #cbd5e1;
  color: #475569;
}

.btn-secondary:hover {
  background-color: #e2e8f0;
  color: #334155;
}

.btn-danger {
  background-color: #ef4444;
  border-color: #ef4444;
  color: white;
}

.btn-danger:hover {
  background-color: #dc2626;
  border-color: #dc2626;
  box-shadow: 0 2px 4px rgba(220, 38, 38, 0.25);
}

.btn-info {
  background-color: #0ea5e9;
  border-color: #0ea5e9;
  color: white;
}

.btn-info:hover {
  background-color: #0284c7;
  border-color: #0284c7;
}

.btn-sm {
  padding: 0.4rem 0.7rem;
  font-size: 0.85rem;
}

.btn-outline-primary {
  background-color: transparent;
  border-color: #3b82f6;
  color: #3b82f6;
}

.btn-outline-primary:hover {
  background-color: #3b82f6;
  color: white;
}

.btn-outline-danger {
  background-color: transparent;
  border-color: #ef4444;
  color: #ef4444;
}

.btn-outline-danger:hover {
  background-color: #ef4444;
  color: white;
}

.btn-group {
  display: flex;
  gap: 0.25rem;
}

.mr-1 {
  margin-right: 0.25rem;
}

.mr-2 {
  margin-right: 0.5rem;
}

/* Card styling */
.card {
  background-color: white;
  border-radius: 0.5rem;
  box-shadow: 0 1px 8px rgba(0, 0, 0, 0.08);
  margin-bottom: 1.5rem;
  overflow: hidden;
  border: none;
}

.card-header {
  padding: 1.25rem 1.5rem;
  background-color: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.card-title {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 600;
  color: #1e293b;
}

.card-body {
  padding: 1.5rem;
}

.p-0 {
  padding: 0;
}

/* Badge styling */
.badge {
  display: inline-block;
  padding: 0.35em 0.65em;
  font-size: 0.75em;
  font-weight: 600;
  line-height: 1;
  text-align: center;
  white-space: nowrap;
  vertical-align: baseline;
  border-radius: 0.25rem;
}

.badge-success {
  background-color: #22c55e;
  color: white;
}

.badge-warning {
  background-color: #f59e0b;
  color: white;
}

.badge-secondary {
  background-color: #94a3b8;
  color: white;
}

.badge-info {
  background-color: #3b82f6;
  color: white;
}

/* Simple Grid Layout */
.info-grid-simple {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.info-row {
  display: grid;
  grid-template-columns: 150px 1fr 150px 1fr;
  gap: 1rem;
  align-items: center;
  padding: 0.75rem 0;
  border-bottom: 1px solid #f1f5f9;
}

.info-row:last-child {
  border-bottom: none;
}

.info-label {
  font-weight: 500;
  color: #64748b;
  font-size: 0.9rem;
}

.info-value {
  color: #334155;
  font-size: 0.9rem;
  font-weight: 400;
}

/* Additional Information Simple */
.additional-info-simple {
  padding-top: 1rem;
  border-top: 2px solid #e2e8f0;
}

.text-muted {
  color: #9ca3af;
}

.text-warning {
  color: #f59e0b;
}

.text-danger {
  color: #ef4444;
}

.text-success {
  color: #22c55e;
}

/* Loading indicator */
.text-center {
  text-align: center;
}

.py-5 {
  padding-top: 3rem;
  padding-bottom: 3rem;
}

.mt-2 {
  margin-top: 0.5rem;
}

.mt-4 {
  margin-top: 1.5rem;
}

.text-primary {
  color: #3b82f6;
}

.fa-spinner {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* Enhanced Modal Styling */
.modal {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 1050;
  display: flex;
  justify-content: center;
  align-items: flex-start;
  padding: 2rem 1rem;
  overflow-y: auto;
}

.modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(15, 23, 42, 0.6);
  z-index: 1051;
  backdrop-filter: blur(3px);
}

.modal-content {
  background-color: white;
  border-radius: 0.75rem;
  box-shadow: 0 10px 50px rgba(0, 0, 0, 0.2);
  width: 100%;
  max-width: 1000px;
  z-index: 1052;
  max-height: calc(100vh - 4rem);
  overflow: hidden;
  animation: modal-appear 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
  border: none;
  margin-top: auto;
  margin-bottom: auto;
}

@keyframes modal-appear {
  from {
    opacity: 0;
    transform: scale(0.9) translateY(-20px);
  }
  to {
    opacity: 1;
    transform: scale(1) translateY(0);
  }
}

.modal-lg {
  max-width: 1000px;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem 2rem;
  border-bottom: 1px solid #e2e8f0;
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
}

.modal-header h2 {
  margin: 0;
  font-size: 1.5rem;
  font-weight: 600;
  color: #1e293b;
}

.modal-body {
  padding: 0;
  overflow-y: auto;
  max-height: calc(100vh - 8rem);
}

.close-btn {
  background: none;
  border: none;
  font-size: 1.25rem;
  cursor: pointer;
  color: #64748b;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 2.5rem;
  height: 2.5rem;
  border-radius: 50%;
  transition: all 0.2s ease;
}

.close-btn:hover {
  background-color: #f1f5f9;
  color: #334155;
  transform: scale(1.1);
}

/* Enhanced Form Styling */
.operation-form {
  padding: 2rem;
}

.form-section {
  margin-bottom: 2.5rem;
  padding: 1.5rem;
  background-color: #fafbfc;
  border-radius: 0.75rem;
  border: 1px solid #e2e8f0;
}

.form-section:last-of-type {
  margin-bottom: 1rem;
}

.form-section .section-title {
  margin: 0 0 1.5rem 0;
  font-size: 1.125rem;
  font-weight: 600;
  color: #374151;
  padding-bottom: 0.75rem;
  border-bottom: 2px solid #e5e7eb;
  display: flex;
  align-items: center;
}

.form-section .section-title::before {
  content: '';
  width: 4px;
  height: 1.25rem;
  background: linear-gradient(135deg, #3b82f6, #1d4ed8);
  border-radius: 2px;
  margin-right: 0.75rem;
}

.form-row {
  display: flex;
  flex-wrap: wrap;
  margin: 0 -0.75rem;
}

.form-group {
  margin-bottom: 1.5rem;
  padding: 0 0.75rem;
}

.col-md-3 {
  flex: 0 0 25%;
  max-width: 25%;
}

.col-md-4 {
  flex: 0 0 33.333333%;
  max-width: 33.333333%;
}

.col-md-6 {
  flex: 0 0 50%;
  max-width: 50%;
}

.col-md-8 {
  flex: 0 0 66.666667%;
  max-width: 66.666667%;
}

/* Enhanced Form Controls */
label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
  font-size: 0.95rem;
  color: #374151;
  line-height: 1.4;
}

.required {
  color: #ef4444;
  font-weight: 600;
}

.form-control {
  display: block;
  width: 100%;
  padding: 0.75rem 1rem;
  font-size: 0.95rem;
  line-height: 1.5;
  color: #374151;
  background-color: #fff;
  background-clip: padding-box;
  border: 2px solid #d1d5db;
  border-radius: 0.5rem;
  transition: all 0.2s ease;
  font-family: inherit;
}

.form-control:focus {
  border-color: #3b82f6;
  outline: 0;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
  background-color: #fefefe;
}

.form-control:hover:not(:focus) {
  border-color: #9ca3af;
}

.form-control.is-invalid {
  border-color: #ef4444;
}

.form-control.is-invalid:focus {
  border-color: #ef4444;
  box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}

.calculated-field {
  background-color: #f9fafb;
  border-color: #d1d5db;
  color: #6b7280;
  font-weight: 500;
}

.calculated-field:focus {
  border-color: #d1d5db;
  box-shadow: none;
}

/* Model Dropdown Specific Styling */
.model-dropdown {
  background-color: #fff;
  transition: all 0.2s ease;
}

.model-dropdown:disabled {
  background-color: #f9fafb;
  border-color: #e5e7eb;
  color: #9ca3af;
  cursor: not-allowed;
  opacity: 0.7;
}

.model-dropdown.loading {
  background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23d1d5db' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
}

/* Model Info Display */
.model-info-display {
  padding: 0.75rem 1rem;
  background-color: #f8fafc;
  border: 2px solid #e2e8f0;
  border-radius: 0.5rem;
  min-height: 2.75rem;
  display: flex;
  align-items: center;
}

.model-info-display .badge {
  font-size: 0.85rem;
  padding: 0.5rem 1rem;
}

/* Select Styling */
select.form-control {
  appearance: none;
  background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 1rem center;
  background-size: 1rem;
  padding-right: 3rem;
  cursor: pointer;
}

select.form-control:focus {
  background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%233b82f6' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
}

select.form-control:disabled {
  cursor: not-allowed;
  background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23d1d5db' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
}

/* Helper Text & Error Messages */
.help-text {
  display: block;
  margin-top: 0.35rem;
  font-size: 0.8rem;
  color: #6b7280;
  font-style: italic;
}

.help-text.loading-text {
  color: #3b82f6;
  font-weight: 500;
}

.invalid-feedback {
  display: block;
  margin-top: 0.35rem;
  font-size: 0.8rem;
  color: #ef4444;
  font-weight: 500;
}

/* Alert styling */
.alert {
  padding: 1rem 1.25rem;
  margin-bottom: 1rem;
  border: 1px solid transparent;
  border-radius: 0.5rem;
}

.alert-info {
  color: #0c4a6e;
  background-color: #e0f2fe;
  border-color: #bae6fd;
}

/* Enhanced Form Actions */
.form-actions {
  display: flex;
  justify-content: flex-end;
  padding: 1.5rem 2rem;
  background-color: #f8fafc;
  border-top: 1px solid #e2e8f0;
  gap: 1rem;
  margin: 0 -2rem -2rem -2rem;
}

.form-actions .btn {
  padding: 0.75rem 1.5rem;
  font-size: 0.95rem;
  font-weight: 500;
  border-radius: 0.5rem;
  border: 2px solid transparent;
  transition: all 0.2s ease;
  cursor: pointer;
  text-decoration: none;
  gap: 0.5rem;
  min-width: 120px;
}

.form-actions .btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.form-actions .btn-primary {
  background: linear-gradient(135deg, #3b82f6, #2563eb);
  border-color: #3b82f6;
  color: white;
  box-shadow: 0 2px 4px rgba(59, 130, 246, 0.2);
}

.form-actions .btn-primary:hover:not(:disabled) {
  background: linear-gradient(135deg, #2563eb, #1d4ed8);
  border-color: #2563eb;
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
  transform: translateY(-1px);
}

.form-actions .btn-secondary {
  background-color: #f8fafc;
  border-color: #d1d5db;
  color: #374151;
}

.form-actions .btn-secondary:hover {
  background-color: #f1f5f9;
  border-color: #9ca3af;
  color: #1f2937;
}

/* Responsive Design */
@media (max-width: 768px) {
  .d-flex.justify-content-between.align-items-center {
    flex-direction: column;
    align-items: flex-start;
  }

  .action-buttons {
    margin-top: 1rem;
    width: 100%;
  }

  .btn {
    flex: 1;
  }

  .info-row {
    grid-template-columns: 1fr;
    gap: 0.5rem;
    padding: 1rem 0;
  }

  .info-label {
    font-weight: 600;
    margin-bottom: 0.25rem;
  }

  .info-value {
    margin-bottom: 1rem;
    padding-left: 1rem;
  }

  .modal {
    padding: 1rem 0.5rem;
  }

  .modal-content {
    max-width: 100%;
  }

  .modal-header {
    padding: 1rem 1.5rem;
  }

  .modal-header h2 {
    font-size: 1.25rem;
  }

  .operation-form {
    padding: 1.5rem;
  }

  .form-section {
    padding: 1rem;
    margin-bottom: 1.5rem;
  }

  .form-row {
    margin: 0 -0.5rem;
  }

  .form-group {
    padding: 0 0.5rem;
  }

  .col-md-3,
  .col-md-4,
  .col-md-6,
  .col-md-8 {
    flex: 0 0 100%;
    max-width: 100%;
  }

  .form-actions {
    flex-direction: column;
    padding: 1rem 1.5rem;
  }

  .form-actions .btn {
    width: 100%;
    justify-content: center;
  }
}

/* Touch optimizations for smaller screens */
@media (max-width: 576px) {
  .routing-detail-container {
    padding: 1rem;
  }

  .page-title {
    font-size: 1.5rem;
  }

  .btn {
    padding: 0.75rem 1rem;
  }

  .card-header {
    padding: 1rem;
  }

  .card-body {
    padding: 1rem;
  }

  .info-row {
    padding: 0.75rem 0;
  }

  .info-label {
    font-size: 0.85rem;
  }

  .info-value {
    font-size: 0.85rem;
  }

  .modal {
    padding: 0;
  }

  .modal-content {
    border-radius: 0;
    max-height: 100vh;
  }

  .operation-form {
    padding: 1rem;
  }

  .form-section {
    padding: 0.75rem;
    border-radius: 0.5rem;
  }

  .form-actions {
    padding: 1rem;
  }
}
</style>
