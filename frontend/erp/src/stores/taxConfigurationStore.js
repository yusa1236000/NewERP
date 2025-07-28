import { defineStore } from 'pinia'
import axios from 'axios'

export const useTaxConfigurationStore = defineStore('taxConfiguration', {
  state: () => ({
    configuration: null,
    loading: false,
    error: null
  }),

  getters: {
    isActive: (state) => state.configuration?.is_active || false,
    defaultSaleCategory: (state) => state.configuration?.default_sale_tax_category,
    defaultPurchaseCategory: (state) => state.configuration?.default_purchase_tax_category,
    taxInclusivePricing: (state) => state.configuration?.tax_inclusive_pricing || false,
    roundingMethod: (state) => state.configuration?.rounding_method || 'round',
    roundingPrecision: (state) => state.configuration?.rounding_precision || 2
  },

  actions: {
    async fetchTaxConfiguration() {
      this.loading = true
      this.error = null
      try {
        const response = await axios.get('/accounting/tax-configuration')
        this.configuration = response.data.data
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || error.message
        throw error
      } finally {
        this.loading = false
      }
    },

    async createTaxConfiguration(configData) {
      this.loading = true
      try {
        const response = await axios.post('/accounting/tax-configuration', configData)
        this.configuration = response.data.data
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || error.message
        throw error
      } finally {
        this.loading = false
      }
    },

    async updateTaxConfiguration(id, configData) {
      this.loading = true
      try {
        const response = await axios.put(`/accounting/tax-configuration/${id}`, configData)
        this.configuration = response.data.data
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || error.message
        throw error
      } finally {
        this.loading = false
      }
    },

    async testRounding(amount, roundingMethod, roundingPrecision) {
      try {
        const response = await axios.post('/accounting/tax-configuration/test-rounding', {
          amount,
          rounding_method: roundingMethod,
          rounding_precision: roundingPrecision
        })
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || error.message
        throw error
      }
    }
  }
})