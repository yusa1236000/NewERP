import { defineStore } from 'pinia'
import axios from 'axios'

export const useTaxCodeStore = defineStore('taxCode', {
  state: () => ({
    taxCodes: [],
    loading: false,
    error: null,
    pagination: {
      current_page: 1,
      last_page: 1,
      per_page: 15,
      total: 0
    }
  }),

  getters: {
    activeTaxCodes: (state) => state.taxCodes.filter(code => code.is_active),
    saleTaxCodes: (state) => state.taxCodes.filter(code => 
      code.scope === 'sale' || code.scope === 'both'
    ),
    purchaseTaxCodes: (state) => state.taxCodes.filter(code => 
      code.scope === 'purchase' || code.scope === 'both'
    )
  },

  actions: {
    async fetchTaxCodes(filters = {}) {
      this.loading = true
      this.error = null
      try {
        const response = await axios.get('/accounting/tax-codes', { params: filters })
        this.taxCodes = response.data.data
        this.pagination = {
          current_page: response.data.current_page,
          last_page: response.data.last_page,
          per_page: response.data.per_page,
          total: response.data.total
        }
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || error.message
        throw error
      } finally {
        this.loading = false
      }
    },

    async getTaxesByScope(scope) {
      try {
        const response = await axios.get(`/accounting/tax-codes/scope/${scope}`)
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || error.message
        throw error
      }
    },

    async createTaxCode(taxCodeData) {
      this.loading = true
      try {
        const response = await axios.post('/accounting/tax-codes', taxCodeData)
        await this.fetchTaxCodes() // Refresh list
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || error.message
        throw error
      } finally {
        this.loading = false
      }
    },

    async updateTaxCode(id, taxCodeData) {
      this.loading = true
      try {
        const response = await axios.put(`/accounting/tax-codes/${id}`, taxCodeData)
        await this.fetchTaxCodes() // Refresh list
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || error.message
        throw error
      } finally {
        this.loading = false
      }
    },

    async deleteTaxCode(id) {
      this.loading = true
      try {
        const response = await axios.delete(`/accounting/tax-codes/${id}`)
        await this.fetchTaxCodes() // Refresh list
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || error.message
        throw error
      } finally {
        this.loading = false
      }
    },

    async calculateTax(taxCodeId, baseAmount, isInclusive = false) {
      try {
        const response = await axios.post('/accounting/tax-codes/calculate', {
          tax_code_id: taxCodeId,
          base_amount: baseAmount,
          is_inclusive: isInclusive
        })
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || error.message
        throw error
      }
    }
  }
})