/**
 * Utility functions for formatting numbers, currency, and other display values
 */

/**
 * Format a number as currency (USD)
 * @param value - The numeric value to format
 * @param options - Optional formatting options
 * @returns Formatted currency string
 */
export function formatCurrency(
  value: number | string,
  options?: {
    minimumFractionDigits?: number
    maximumFractionDigits?: number
    showCents?: boolean
  },
): string {
  const numValue = typeof value === 'string' ? parseFloat(value) : value

  // Handle invalid numbers
  if (isNaN(numValue)) {
    return '$0'
  }

  const defaultOptions = {
    minimumFractionDigits: options?.showCents !== false ? 2 : 0,
    maximumFractionDigits: options?.showCents !== false ? 2 : 0,
    ...options,
  }

  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    ...defaultOptions,
  }).format(numValue)
}

/**
 * Format a number with proper thousand separators
 * @param value - The numeric value to format
 * @param options - Optional formatting options
 * @returns Formatted number string
 */
export function formatNumber(
  value: number | string,
  options?: {
    minimumFractionDigits?: number
    maximumFractionDigits?: number
  },
): string {
  const numValue = typeof value === 'string' ? parseFloat(value) : value

  // Handle invalid numbers
  if (isNaN(numValue)) {
    return '0'
  }

  return new Intl.NumberFormat('en-US', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
    ...options,
  }).format(numValue)
}

/**
 * Format a large number with abbreviations (K, M, B)
 * @param value - The numeric value to format
 * @param options - Optional formatting options
 * @returns Formatted number string with abbreviation
 */
export function formatCompactNumber(
  value: number | string,
  options?: {
    maximumFractionDigits?: number
  },
): string {
  const numValue = typeof value === 'string' ? parseFloat(value) : value

  // Handle invalid numbers
  if (isNaN(numValue)) {
    return '0'
  }

  // For smaller numbers, use regular formatting
  if (numValue < 1000) {
    return formatNumber(numValue)
  }

  return new Intl.NumberFormat('en-US', {
    notation: 'compact',
    maximumFractionDigits: options?.maximumFractionDigits ?? 1,
  }).format(numValue)
}

/**
 * Format a percentage value
 * @param value - The numeric value to format (as decimal, e.g., 0.25 for 25%)
 * @param options - Optional formatting options
 * @returns Formatted percentage string
 */
export function formatPercentage(
  value: number | string,
  options?: {
    minimumFractionDigits?: number
    maximumFractionDigits?: number
  },
): string {
  const numValue = typeof value === 'string' ? parseFloat(value) : value

  // Handle invalid numbers
  if (isNaN(numValue)) {
    return '0%'
  }

  return new Intl.NumberFormat('en-US', {
    style: 'percent',
    minimumFractionDigits: options?.minimumFractionDigits ?? 0,
    maximumFractionDigits: options?.maximumFractionDigits ?? 1,
  }).format(numValue)
}

/**
 * Format currency for large amounts with smart abbreviations
 * @param value - The numeric value to format
 * @param options - Optional formatting options
 * @returns Formatted currency string with abbreviation if needed
 */
export function formatSmartCurrency(
  value: number | string,
  options?: {
    threshold?: number
    showCents?: boolean
  },
): string {
  const numValue = typeof value === 'string' ? parseFloat(value) : value
  const threshold = options?.threshold ?? 10000

  // Handle invalid numbers
  if (isNaN(numValue)) {
    return '$0'
  }

  // For large amounts, use compact notation
  if (numValue >= threshold) {
    const formatted = new Intl.NumberFormat('en-US', {
      notation: 'compact',
      style: 'currency',
      currency: 'USD',
      maximumFractionDigits: 1,
    }).format(numValue)

    return formatted
  }

  // For smaller amounts, use regular currency formatting
  return formatCurrency(numValue, { showCents: options?.showCents })
}
