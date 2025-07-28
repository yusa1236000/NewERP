/* eslint-disable */
import { ref, computed } from 'vue'
import { useTaxConfigurationStore } from '@/stores/taxConfigurationStore'

export function useTaxCalculation() {
  const taxConfigStore = useTaxConfigurationStore()
  
  const calculateSingleTax = (taxCode, baseAmount, isInclusive = false) => {
    if (!taxCode || !baseAmount) return 0
    
    if (taxCode.calculation_type === 'percentage') {
      if (isInclusive) {
        return baseAmount * (taxCode.tax_rate / (100 + taxCode.tax_rate))
      }
      return baseAmount * (taxCode.tax_rate / 100)
    }
    return taxCode.tax_rate // Fixed amount
  }

  const calculateMultipleTaxes = (taxes, baseAmount, isInclusive = false) => {
    let totalTax = 0
    let calculatedTaxes = []
    
    if (!taxes || !taxes.length) return { totalTax, calculatedTaxes }
    
    for (const tax of taxes) {
      const taxAmount = calculateSingleTax(tax, baseAmount, isInclusive)
      totalTax += taxAmount
      
      calculatedTaxes.push({
        ...tax,
        calculated_amount: taxAmount,
        base_amount: baseAmount
      })
    }
    
    return { totalTax, calculatedTaxes }
  }

  const roundTaxAmount = (amount) => {
    const config = taxConfigStore.configuration
    if (!config) return parseFloat(amount.toFixed(2))
    
    const precision = config.rounding_precision || 2
    const multiplier = Math.pow(10, precision)
    
    switch (config.rounding_method) {
      case 'round_up':
        return Math.ceil(amount * multiplier) / multiplier
      case 'round_down':
        return Math.floor(amount * multiplier) / multiplier
      default:
        return Math.round(amount * multiplier) / multiplier
    }
  }

  const formatTaxDisplay = (taxCode, amount) => {
    if (taxCode.calculation_type === 'percentage') {
      return `${taxCode.tax_code} (${taxCode.tax_rate}%): ${formatAmount(amount)}`
    }
    return `${taxCode.tax_code} (Fixed): ${formatAmount(amount)}`
  }

  const formatAmount = (amount) => {
    return parseFloat(amount || 0).toFixed(2)
  }

  return {
    calculateSingleTax,
    calculateMultipleTaxes,
    roundTaxAmount,
    formatTaxDisplay,
    formatAmount
  }
}