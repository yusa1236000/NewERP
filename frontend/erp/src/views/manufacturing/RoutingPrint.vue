<!-- src/views/manufacturing/RoutingPrint.vue -->
<template>
  <div class="print-container">
    <!-- Print Actions - Hidden during print -->
    <div class="print-actions no-print">
      <button class="btn btn-primary" @click="printDocument">
        <i class="fas fa-print"></i> Print Document
      </button>
      <button class="btn btn-danger" @click="printPdf" style="margin-left: 0.5rem;">
        <i class="fas fa-file-pdf"></i> Save as PDF
      </button>
      <button class="btn btn-secondary" @click="goBack">
        <i class="fas fa-arrow-left"></i> Back
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="loading-indicator no-print">
      <i class="fas fa-spinner fa-spin"></i>
      <p>Loading routing data...</p>
    </div>

    <!-- Print Content - A4 Document -->
    <div v-else-if="routing" class="document-wrapper">
      <div id="printDocument" class="routing-print-document">
        <!-- Header Section -->
        <div class="document-header">
          <div class="company-info">
            <h1 class="company-name">PROCESS CONTROL CHART</h1>
          </div>
        </div>

        <!-- Main Information Grid - 2 rows -->
        <div class="main-info-grid">
          <div class="info-row">
            <div class="info-cell">
              <label>Internal Code</label>
              <div class="value">{{ routing.routing_code }}</div>
            </div>
            <div class="info-cell">
              <label>Cust Code</label>
              <div class="value">{{ routing.item?.item_code || '-' }}</div>
            </div>
            <div class="info-cell">
              <label>Part Name</label>
              <div class="value">{{ routing.item?.name || '-' }}</div>
            </div>
            <div class="info-cell">
              <label>Overall Dim</label>
              <div class="value">{{ getOverallDimension() }}</div>
            </div>
          </div>
          <div class="info-row">
            <div class="info-cell">
              <label>Cust Name</label>
              <div class="value">INTERNAL</div>
            </div>
            <div class="info-cell">
              <label>Date</label>
              <div class="value">{{ formatDate(routing.effective_date) }}</div>
            </div>
            <div class="info-cell">
              <label>Rev.No</label>
              <div class="value">{{ routing.revision }}</div>
            </div>
            <div class="info-cell">
              <label>Rev.Date</label>
              <div class="value">{{ formatDate(routing.updated_at) }}</div>
            </div>
          </div>
        </div>

        <!-- Additional Material Information Section -->
        <div class="material-info-section">
          <div class="material-info-grid">
            <div class="material-row">
              <div class="process-cell">
                <label>Cavity</label>
                <div class="value">{{ routing.cavity || '4' }}</div>
              </div>
              <div class="process-cell">
                <label>Mould 1</label>
                <div class="value">{{ routing.mould_1 || '8321R1-01' }}</div>
              </div>
              <div class="process-cell">
                <label>Mould 2</label>
                <div class="value">{{ routing.mould_2 || '-' }}</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Process Information Section - Updated to remove UOM and Process -->
        <div class="process-info-section">
          <div class="process-grid">
            <div class="process-row">
              <div class="process-cell">
                <label>Yield 1</label>
                <div class="value">{{ routing.yield_1 || '1776' }}</div>
              </div>
              <div class="process-cell">
                <label>Y.DIM1</label>
                <div class="value">{{ getYieldDimension() }}</div>
              </div>
              <div class="process-cell">
                <label>Y.REM1</label>
                <div class="value">{{ routing.yield_rem_1 || '-' }}</div>
              </div>
            </div>

            <div class="process-row">
              <div class="process-cell">
                <label>Yield 2</label>
                <div class="value">{{ routing.yield_2 || '0' }}</div>
              </div>
              <div class="process-cell">
                <label>Y.DIM2</label>
                <div class="value">{{ routing.yield_dim_2 || '-' }}</div>
              </div>
              <div class="process-cell">
                <label>Y.REM2</label>
                <div class="value">{{ routing.yield_rem_2 || '-' }}</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Material Detail Information Table -->
        <div class="material-table-container" style="margin-bottom: 8px !important; padding-bottom: 2px !important;">
          <table class="material-table" style="border: 1px solid #000 !important; border-collapse: collapse; width: 100%; table-layout: fixed;">
            <thead>
              <tr>
                <th style="border: 1px solid #000 !important; background-color: #f0f0f0; width: 15%; padding: 2px 4px; font-size: 7pt; font-weight: bold;">Material Code</th>
                <th style="border: 1px solid #000 !important; background-color: #f0f0f0; width: 12%; padding: 2px 4px; font-size: 7pt; font-weight: bold;">Material Name</th>
                <th style="border: 1px solid #000 !important; background-color: #f0f0f0; width: 20%; padding: 2px 4px; font-size: 7pt; font-weight: bold;">Dimension</th>
                <th style="border: 1px solid #000 !important; background-color: #f0f0f0; width: 12%; padding: 2px 4px; font-size: 7pt; font-weight: bold;">Yield</th>
                <th style="border: 1px solid #000 !important; background-color: #f0f0f0; width: 10%; padding: 2px 4px; font-size: 7pt; font-weight: bold;">UOM</th>
                <th style="border: 1px solid #000 !important; background-color: #f0f0f0; width: 31%; padding: 2px 4px; font-size: 7pt; font-weight: bold;">Remarks</th>
              </tr>
            </thead>
            <tbody>
              <tr class="material-data-row">
                <td style="border: 1px solid #000 !important; width: 15%; padding: 2px 4px; font-size: 8pt; text-align: left; vertical-align: middle; word-wrap: break-word; overflow-wrap: break-word;">{{ routing.item?.item_code || '-' }}</td>
                <td style="border: 1px solid #000 !important; width: 12%; padding: 2px 4px; font-size: 8pt; text-align: left; vertical-align: middle; word-wrap: break-word; overflow-wrap: break-word;">{{ routing.item?.name || '-' }}</td>
                <td style="border: 1px solid #000 !important; width: 20%; padding: 2px 4px; font-size: 8pt; text-align: left; vertical-align: middle; word-wrap: break-word; overflow-wrap: break-word;">{{ getItemDimension() }}</td>
                <td style="border: 1px solid #000 !important; width: 12%; padding: 2px 4px; font-size: 8pt; text-align: center; vertical-align: middle;">{{ routing.yield ? routing.yield + '' : '-' }}</td>
                <td style="border: 1px solid #000 !important; width: 10%; padding: 2px 4px; font-size: 8pt; text-align: center; vertical-align: middle;">{{ routing.item?.unitOfMeasure?.symbol || 'PCS' }}</td>
                <td style="border: 1px solid #000 !important; width: 31%; padding: 2px 4px; font-size: 8pt; text-align: left; vertical-align: middle; word-wrap: break-word; overflow-wrap: break-word;">{{ routing.item?.description || 'REV PACK STD' }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Operations Table -->
        <div class="operations-table-container" style="margin-top: 2px !important; margin-bottom: 15px !important;">
          <table class="operations-table" style="width: 100%; table-layout: fixed;">
            <thead>
              <tr>
                <th style="width: 15%;" rowspan="2">Process</th>
                <th style="width: 12%;" rowspan="2">Dimensions</th>
                <th style="width: 20%;" rowspan="2">Add.Instructions and Remarks</th>
                <th colspan="2" style="width: 12%;">Tolerance</th>
                <th style="width: 10%;" rowspan="2">Machine</th>
                <th style="width: 8%;" rowspan="2">SetUp Time</th>
                <th style="width: 8%;" rowspan="2">Proc.Time per Cycle</th>
                <th style="width: 6%;" rowspan="2">Yld 1</th>
                <th style="width: 6%;" rowspan="2">Yld 2</th>
                <th style="width: 6%;" rowspan="2">No Pc</th>
               </tr>
              <tr class="sub-header">
                <th style="width: 6%;">Min</th>
                <th style="width: 6%;">Max</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="operation in operations" :key="operation.operation_id" class="operation-row">
                <td class="process-name">
                  <div class="process-notes">{{ operation.operation_name }}</div>
                  <div class="machine-code">{{ getMachineCode(operation) }} </div>
                </td>
                <td class="dimensions">{{ getDimensionText(operation) }}</td>
                <td class="instructions">
                  <div class="instruction-text">{{ getInstructionText(operation) }}</div>
                </td>
                <td class="tolerance-min">{{ getToleranceMin(operation) }}</td>
                <td class="tolerance-max">{{ getToleranceMax(operation) }}</td>
                <td class="machine">{{ operation.models || '' }}</td>
                <td class="setup-time">{{ formatTime(operation.setup_time) }}</td>
                <td class="process-time">{{ formatTime(operation.run_time) }}</td>
                <td class="yield-1">{{ getYieldValue(operation) }}</td>
                <td class="yield-2">{{ operation.yield_2 || '0' }}</td>
                <td class="piece-count">{{ getPieceCount(operation) }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Catatan Penting Section -->
        <div class="catatan-penting-section">
          <div class="catatan-penting-content">
            <div class="catatan-penting-text">
              <strong><u>Important Notes :</u></strong>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="document-footer" style="margin-top: 10px !important; border-top: none !important; padding-top: 10px !important;">
          <div class="signature-section" style="display: flex !important; justify-content: center !important; margin-bottom: 20px !important;">
            <div class="signature-container" style="width: 360px !important; height: 100px !important; border: 2px solid #000 !important; display: flex !important;">
              <div class="signature-half" style="width: 50% !important; text-align: center !important; padding: 8px !important; border-right: 1px solid #000 !important;">
                <div style="font-weight: bold !important; font-size: 8pt !important; margin-bottom: 5px !important;">Prepared By</div>
              </div>
              <div class="signature-half" style="width: 50% !important; text-align: center !important; padding: 8px !important;">
                <div style="font-weight: bold !important; font-size: 8pt !important; margin-bottom: 5px !important;">Approved By</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Error State -->
    <div v-else class="error-state no-print">
      <i class="fas fa-exclamation-triangle"></i>
      <p>Routing data not found</p>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import axios from 'axios';
import html2pdf from 'html2pdf.js';

export default {
  name: 'RoutingPrint',
  setup() {
    const router = useRouter();
    const route = useRoute();
    const routingId = computed(() => route.params.id);

    const isLoading = ref(true);
    const routing = ref(null);
    const operations = ref([]);
    const currentUser = ref(null);

    // Get current user from localStorage or session
    const getCurrentUser = () => {
      try {
        const user = localStorage.getItem('user');
        return user ? JSON.parse(user) : null;
      } catch {
        return null;
      }
    };

    // Load routing data
    const loadRoutingData = async () => {
      isLoading.value = true;
      try {
        // Load routing basic info
        const routingResponse = await axios.get(`/routings/${routingId.value}`);
        routing.value = routingResponse.data.data;

        // Load operations
        const operationsResponse = await axios.get(`/routings/${routingId.value}/operations`);
        operations.value = operationsResponse.data.data || [];

        currentUser.value = getCurrentUser();

        // Auto-print if requested in the URL
        if (route.query.autoprint === 'true') {
          setTimeout(() => {
            printDocument();
          }, 1000);
        }
      } catch (error) {
        console.error('Error loading routing data:', error);
      } finally {
        isLoading.value = false;
      }
    };

    // Utility functions
    const formatDate = (dateString) => {
      if (!dateString) return '-';
      return new Date(dateString).toLocaleDateString('en-GB');
    };

    const formatDateTime = (date) => {
      return new Date(date).toLocaleString('en-GB');
    };

    const formatTime = (time) => {
      if (!time) return '0.00';
      return parseFloat(time).toFixed(2);
    };

    const formatNumber = (number) => {
      if (!number) return '0';
      return new Intl.NumberFormat('en-US').format(number);
    };

    const getOverallDimension = () => {
      const item = routing.value?.item;
      if (!item) return '-';

      const length = item.length || '';
      const width = item.width || '';
      const thickness = item.thickness || '';

      if (length && width && thickness) {
        return `${thickness}MMX${width}MMX${length}MM`;
      }
      return '-';
    };

    const getItemDimension = () => {
      const item = routing.value?.item;
      if (!item) return '-';

      const length = item.length || '';
      const width = item.width || '';
      const thickness = item.thickness || '';

      if (length && width && thickness) {
        return `${thickness}MMX${width}MMX${length}MM`;
      }
      return item.item_code || '-';
    };

    const getYieldDimension = () => {
      const item = routing.value?.item;
      if (!item) return '-';

      const length = item.length || '58';
      const thickness = item.thickness || '0.55';
      return `${thickness}MMX${length}MMX50M`;
    };

    // Operation-specific helper functions using existing data
    const getDimensionText = (operation) => {
      return operation.dimensi || '-';
    };

    const getInstructionText = (operation) => {
      const opName = operation.operation_name?.toUpperCase() || '';

      if (opName.includes('SLIT')) {
        return `SLIT ${operation.work_flow || 'MATERIAL'} YANG RATA`;
      }
      if (opName.includes('LAMN')) {
        return `LAMN ${operation.work_flow || 'MATERIAL'} BAGIAN LUAR ROLL`;
      }
      if (opName.includes('PUNCH')) {
        return 'K/CUT LANGSUNG DEFLASING';
      }
      if (opName.includes('CUT')) {
        return 'QTY 1 SHEET = 20 PCS 2 PCS X 10 PCS= 20 PCS';
      }
      if (opName.includes('PACK')) {
        return operation.models || 'PAKAI LAYER 2 SISI';
      }

      return operation.work_flow || operation.models || '-';
    };

    const getToleranceMin = (operation) => {
      return operation.toleransi_min || '-';
    };

    const getToleranceMax = (operation) => {
      return operation.toleransi_max || '-';
    };

    const getMachineCode = (operation) => {
      if (operation.work_center?.code) {
        return operation.work_center.code;
      }
      if (operation.work_center?.name) {
        return operation.work_center.name;
      }

      const opName = operation.operation_name?.toUpperCase() || '';
      if (opName.includes('SLIT')) return 'S3/016/D/975';
      if (opName.includes('LAMN')) return 'P31/080/J2/02';
      if (opName.includes('PUNCH')) return 'P31/080/J2/02';
      if (opName.includes('PACK') || opName.includes('CUT')) return 'MANUAL';

      return '-';
    };

    const getYieldValue = (operation) => {
      return operation.yield1 ? formatNumber(operation.yield1) : '0';
    };

    const getPieceCount = (operation) => {
      const opName = operation.operation_name?.toUpperCase() || '';

      if (opName.includes('PUNCH')) return '0.00';
      if (opName.includes('PACK')) return '0.00';

      return '0.00';
    };

    // Print document function
    const printDocument = () => {
      if (!routing.value) return;

      const printWindow = window.open('', '_blank');
      const documentElement = document.getElementById('printDocument');
      const documentHTML = documentElement.outerHTML;

      const printHTML = `
        <!DOCTYPE html>
        <html>
        <head>
          <meta charset="utf-8">
          <title>Process Control Chart - ${routing.value?.routing_code || 'Document'}</title>
          <style>
            * {
              margin: 0;
              padding: 0;
              box-sizing: border-box;
            }

            body {
              font-family: Arial, sans-serif;
              font-size: 10pt;
              line-height: 1.2;
              color: #000;
              background: white;
              margin: 0;
              padding: 15px;
            }

            .routing-print-document {
              width: 100%;
              background: white;
              padding: 0;
            }

            .document-header {
              text-align: center;
              margin-bottom: 10px;
              border-bottom: 3px solid #000;
              padding-bottom: 5px;
            }

            .company-name {
              font-size: 14pt;
              font-weight: bold;
              margin: 0;
            }

            .main-info-grid, .material-info-grid, .process-grid {
              border: 2px solid #000;
              margin-bottom: 10px;
            }

            .info-row, .material-row, .process-row {
              display: flex;
              border-bottom: 1px solid #000;
            }

            .info-row:last-child, .material-row:last-child, .process-row:last-child {
              border-bottom: none;
            }

            .info-cell, .material-cell,             .process-cell {
              flex: 1;
              border-right: 1px solid #000;
              padding: 4px 6px;
              min-height: 25px;
              width: 33.33%;
            }

            .info-cell:last-child, .material-cell:last-child, .process-cell:last-child {
              border-right: none;
            }

            .info-cell label, .material-cell label, .process-cell label {
              font-size: 8pt;
              font-weight: bold;
              display: block;
              margin-bottom: 2px;
            }

            .info-cell .value, .material-cell .value, .process-cell .value {
              font-size: 9pt;
              word-break: break-word;
            }

            .material-table-container {
              margin-bottom: 8px !important;
              padding-bottom: 2px !important;
            }

            .material-table {
              width: 100%;
              border-collapse: collapse;
              border: 1px solid #000 !important;
              font-size: 8pt;
              table-layout: fixed;
            }

            .material-table th {
              border: 1px solid #000 !important;
              padding: 2px 4px;
              text-align: center;
              vertical-align: middle;
              background-color: #f0f0f0;
              font-weight: bold;
              font-size: 7pt;
            }

            .material-table td {
              border: 1px solid #000 !important;
              padding: 2px 4px;
              text-align: center;
              vertical-align: middle;
              font-size: 8pt;
              word-wrap: break-word;
              overflow-wrap: break-word;
            }

            .material-data-row {
              min-height: 20px;
            }

            .material-code {
              width: 15%;
              text-align: left !important;
              border: 2px solid #000 !important;
            }

            .material-name {
              width: 20%;
              text-align: left !important;
              border: 2px solid #000 !important;
            }

            .material-dimension {
              width: 20%;
              text-align: left !important;
              border: 2px solid #000 !important;
            }

            .material-yield {
              width: 6%;
              text-align: center !important;
              border: 2px solid #000 !important;
            }

            .material-uom {
              width: 6%;
              text-align: center !important;
              border: 2px solid #000 !important;
            }

            .material-remarks {
              width: 33%;
              text-align: left !important;
              border: 2px solid #000 !important;
            }

            .operations-table-container {
              margin-bottom: 15px !important;
              margin-top: 2px !important;
            }

            .operations-table {
              width: 100%;
              border-collapse: collapse;
              border: 2px solid #000;
              font-size: 8pt;
              table-layout: fixed;
            }

            .operations-table th,
            .operations-table td {
              border: 1px solid #000;
              padding: 2px 4px;
              text-align: center;
              vertical-align: middle;
            }

            .operations-table th {
              background-color: #f0f0f0;
              font-weight: bold;
              font-size: 7pt;
              border: 1px solid #000;
            }

            .operations-table .sub-header th {
              background-color: #e8e8e8;
              height: 15px;
              border: 1px solid #000;
            }

            .operations-table td {
              border: 1px solid #000;
            }

            .process-name {
              text-align: left;
              width: 15%;
            }

            .dimensions {
              width: 12%;
              text-align: left;
            }

            .instructions {
              width: 20%;
              text-align: left;
            }

            .tolerance-min,
            .tolerance-max {
              width: 6%;
            }

            .machine {
              width: 10%;
            }

            .setup-time,
            .process-time {
              width: 8%;
            }

            .yield-1,
            .yield-2 {
              width: 6%;
            }

            .piece-count {
              width: 6%;
            }

            .catatan-penting-section {
              margin: 15px 0 20px 0;
              border-top: 2px solid #000;
              padding: 10px 0;
            }

            .catatan-penting-content {
              text-align: left;
            }

            .catatan-penting-text {
              font-size: 10pt;
              font-weight: normal;
            }

            .catatan-penting-text strong {
              font-weight: bold;
            }

            .document-footer {
              margin-top: 10px !important;
              border-top: none !important;
              padding-top: 10px !important;
            }

            .signature-section {
              display: flex !important;
              justify-content: center !important;
              margin-bottom: 20px !important;
            }

            .signature-container {
              width: 360px !important;
              height: 100px !important;
              border: 2px solid #000 !important;
              display: flex !important;
            }

            .signature-half {
              width: 50% !important;
              text-align: center !important;
              padding: 8px !important;
            }

            .signature-half:first-child {
              border-right: 1px solid #000 !important;
            }

            .signature-half div {
              font-weight: bold !important;
              font-size: 8pt !important;
              margin-bottom: 5px !important;
            }

            @media print {
              body {
                margin: 0 !important;
                padding: 10px !important;
              }

              @page {
                size: A4;
                margin: 0.5cm;
              }
            }
          </style>
        </head>
        <body>
          ${documentHTML}
        </body>
        </html>
      `;

      printWindow.document.write(printHTML);
      printWindow.document.close();

      printWindow.onload = () => {
        setTimeout(() => {
          printWindow.print();
          printWindow.close();
        }, 500);
      };
    };

    // Print PDF function
    const printPdf = async () => {
      if (isLoading.value) {
        console.warn("Data is still loading. Please wait before printing PDF.");
        return;
      }

      if (!routing.value) {
        console.warn("No routing data available for PDF export.");
        return;
      }

      const element = document.getElementById('printDocument');

      if (!element) {
        console.error("Print document element not found.");
        return;
      }

      const opt = {
        margin: [0.5, 0.5, 0.5, 0.5],
        filename: `RoutingChart_${routing.value?.routing_code || 'document'}.pdf`,
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: {
          scale: 2,
          useCORS: true,
          letterRendering: true
        },
        jsPDF: {
          unit: 'in',
          format: 'a4',
          orientation: 'portrait'
        }
      };

      try {
        await html2pdf().set(opt).from(element).save();
      } catch (error) {
        console.error("Error generating PDF:", error);
        alert("Error generating PDF. Please try again.");
      }
    };

    // Navigation
    const goBack = () => {
      router.push(`/manufacturing/routings/${routingId.value}`);
    };

    onMounted(() => {
      loadRoutingData();
    });

    return {
      isLoading,
      routing,
      operations,
      currentUser,
      formatDate,
      formatDateTime,
      formatTime,
      formatNumber,
      getOverallDimension,
      getItemDimension,
      getYieldDimension,
      getDimensionText,
      getInstructionText,
      getToleranceMin,
      getToleranceMax,
      getMachineCode,
      getYieldValue,
      getPieceCount,
      printDocument,
      printPdf,
      goBack
    };
  }
};
</script>

<style scoped>
.print-container {
  min-height: 100vh;
  background-color: #f1f5f9;
  padding: 2rem 1rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: flex-start;
}

.print-actions {
  display: flex;
  gap: 1rem;
  margin-bottom: 2rem;
  justify-content: center;
  width: 100%;
  max-width: 210mm;
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

.btn-primary {
  background-color: #059669;
  color: white;
}

.btn-primary:hover {
  background-color: #047857;
}

.btn-secondary {
  background-color: #e2e8f0;
  color: #1e293b;
}

.btn-secondary:hover {
  background-color: #cbd5e1;
}

.btn-danger {
  background-color: #ef4444;
  color: white;
}

.btn-danger:hover {
  background-color: #dc2626;
}

.document-wrapper {
  width: 210mm;
  min-height: 297mm;
  margin: 0;
  background-color: white;
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
  border: 1px solid #e5e7eb;
  position: relative;
  display: block;
}

.routing-print-document {
  width: 100%;
  min-height: 297mm;
  padding: 20px;
  background: white;
  font-family: Arial, sans-serif;
  font-size: 11pt;
  line-height: 1.3;
  color: #000;
  box-sizing: border-box;
}

.document-header {
  text-align: center;
  margin-bottom: 10px;
  border-bottom: 3px solid #000;
  padding-bottom: 5px;
}

.company-info .company-name {
  font-size: 14pt;
  font-weight: bold;
  margin: 0;
}

.main-info-grid, .material-info-grid, .process-grid {
  border: 2px solid #000;
  margin-bottom: 10px;
}

.info-row, .material-row, .process-row {
  display: flex;
  border-bottom: 1px solid #000;
}

.info-row:last-child, .material-row:last-child, .process-row:last-child {
  border-bottom: none;
}

.info-cell, .process-cell {
  flex: 1;
  border-right: 1px solid #000;
  padding: 4px 6px;
  min-height: 25px;
}

.info-cell:last-child, .process-cell:last-child {
  border-right: none;
}

.info-cell label, .process-cell label {
  font-size: 8pt;
  font-weight: bold;
  display: block;
  margin-bottom: 2px;
}

.info-cell .value, .process-cell .value {
  font-size: 9pt;
  word-break: break-word;
}

.operations-table-container {
  margin-bottom: 15px;
}

.operations-table {
  width: 100%;
  border-collapse: collapse;
  border: 2px solid #000;
  font-size: 8pt;
  table-layout: fixed;
}

.operations-table th,
.operations-table td {
  border: 1px solid #000;
  padding: 2px 4px;
  text-align: center;
  vertical-align: middle;
}

.operations-table th {
  background-color: #f0f0f0;
  font-weight: bold;
  font-size: 7pt;
  border: 1px solid #000;
}

.operations-table .sub-header th {
  background-color: #e8e8e8;
  height: 15px;
  border: 1px solid #000;
}

.operations-table td {
  border: 1px solid #000;
}

.operation-row {
  min-height: 25px;
}

.process-name {
  text-align: left;
  width: 15%;
}

.process-code {
  font-weight: bold;
  font-size: 8pt;
}

.process-description {
  font-size: 7pt;
  color: #666;
}

.dimensions {
  width: 12%;
  text-align: left;
}

.instructions {
  width: 20%;
  text-align: left;
}

.instruction-text {
  font-size: 8pt;
}

.additional-notes {
  font-size: 7pt;
  color: #666;
  margin-top: 1px;
}

.tolerance-min,
.tolerance-max {
  width: 6%;
}

.machine {
  width: 10%;
}

.setup-time,
.process-time {
  width: 8%;
}

.yield-1,
.yield-2 {
  width: 6%;
}

.piece-count {
  width: 6%;
}

.file-path-section {
  margin-bottom: 15px;
  text-align: center;
  border: 2px solid #000;
  padding: 5px;
}

.file-path {
  font-size: 8pt;
  color: #666;
  font-family: monospace;
}

.document-footer {
  margin-top: 20px;
  border-top: 2px solid #000;
  padding-top: 10px;
}

.signature-section {
  display: flex;
  justify-content: center;
  margin-bottom: 20px;
  gap: 10px;
}

.signature-box {
  width: 150px;
  height: 80px;
  border: 2px solid #000;
  text-align: center;
}

.signature-header {
  font-weight: bold;
  font-size: 9pt;
  padding: 4px;
  border-bottom: 1px solid #000;
  background-color: white;
}

.signature-area {
  height: 50px;
  border: none;
  background-color: white;
}

.loading-indicator,
.error-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 200px;
  text-align: center;
}

.loading-indicator i {
  font-size: 2rem;
  margin-bottom: 10px;
  color: #007bff;
}

.error-state i {
  font-size: 2rem;
  margin-bottom: 10px;
  color: #dc3545;
}

@media print {
  .no-print {
    display: none !important;
  }

  @page {
    size: A4;
    margin: 0.5cm;
  }
}

@media (max-width: 768px) {
  .print-container {
    padding: 1rem;
  }

  .document-wrapper {
    width: 100%;
  }

  .routing-print-document {
    padding: 10px;
  }

  .main-info-grid .info-row,
  .material-info-grid .material-row,
  .process-grid .process-row {
    flex-direction: column;
  }

  .info-cell,
  .process-cell {
    border-right: none;
    border-bottom: 1px solid #000;
  }

  .operations-table,
  .material-table {
    font-size: 7pt;
  }

  .signature-section {
    flex-direction: column;
    gap: 20px;
  }

  .signature-box {
    width: 100%;
  }
}
</style>
