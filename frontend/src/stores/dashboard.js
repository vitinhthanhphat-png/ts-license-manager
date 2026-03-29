import { defineStore } from 'pinia'
import { ref } from 'vue'
import { dashboardApi } from '@/api'

export const useDashboardStore = defineStore('dashboard', () => {
  const stats = ref(null)
  const loading = ref(false)
  const error = ref(null)

  async function fetchStats() {
    loading.value = true
    error.value = null
    try {
      const { data } = await dashboardApi.getStats()
      stats.value = data
    } catch (e) {
      error.value = e.response?.data?.error || e.message
    } finally {
      loading.value = false
    }
  }

  return { stats, loading, error, fetchStats }
})
