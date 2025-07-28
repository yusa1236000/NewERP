import { defineStore } from 'pinia'
import axios from 'axios'

export const useTaxCategoryStore = defineStore('taxCategory', {
  state: () => ({
    taxCategories: [],
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
    activeCategories: (state) => state.taxCategories.filter(cat => cat.is_active)
  },

  actions: {
    async fetchTaxCategories(filters = {}) {
      this.loading = true
      this.error = null
      try {
        const response = await axios.get('/accounting/tax-categories', { params: filters })
        this.taxCategories = response.data.data
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

    async createTaxCategory(categoryData) {
      this.loading = true
      try {
        const response = await axios.post('/accounting/tax-categories', categoryData)
        await this.fetchTaxCategories() // Refresh list
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || error.message
        throw error
      } finally {
        this.loading = false
      }
    },

    async updateTaxCategory(id, categoryData) {
      this.loading = true
      try {
        const response = await axios.put(`/accounting/tax-categories/${id}`, categoryData)
        await this.fetchTaxCategories() // Refresh list
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || error.message
        throw error
      } finally {
        this.loading = false
      }
    },

    async deleteTaxCategory(id) {
      this.loading = true
      try {
        const response = await axios.delete(`/accounting/tax-categories/${id}`)
        await this.fetchTaxCategories() // Refresh list
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || error.message
        throw error
      } finally {
        this.loading = false
      }
    },

    async getDefaultTaxCodes(categoryId) {
      try {
        const response = await axios.get(`/accounting/tax-categories/${categoryId}/default-tax-codes`)
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || error.message
        throw error
      }
    },

    async calculateCategoryTax(categoryId, baseAmount, options = {}) {
      try {
        const response = await axios.post(`/accounting/tax-categories/${categoryId}/calculate`, {
          base_amount: baseAmount,
          ...options
        })
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || error.message
        throw error
      }
    }
  }
})