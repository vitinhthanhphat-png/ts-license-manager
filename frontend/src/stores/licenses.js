import { defineStore } from 'pinia'
import { ref } from 'vue'
import { licensesApi } from '@/api'

export const useLicenseStore = defineStore('licenses', () => {
  const licenses = ref([])
  const total = ref(0)
  const loading = ref(false)
  const error = ref(null)
  const filters = ref({
    status: '',
    domain: '',
    type: '',
    page: 1,
    per_page: 20,
  })

  async function fetchLicenses(params = {}) {
    loading.value = true
    error.value = null
    try {
      const mergedParams = { ...filters.value, ...params }
      const { data } = await licensesApi.getAll(mergedParams)
      licenses.value = data.items || []
      total.value = data.total || 0
    } catch (e) {
      error.value = e.response?.data?.error || e.message
    } finally {
      loading.value = false
    }
  }

  async function generateLicense(formData) {
    loading.value = true
    error.value = null
    try {
      const { data } = await licensesApi.generate(formData)
      await fetchLicenses()
      return data
    } catch (e) {
      error.value = e.response?.data?.error || e.message
      throw e
    } finally {
      loading.value = false
    }
  }

  async function revokeLicense(id) {
    loading.value = true
    try {
      await licensesApi.revoke(id)
      await fetchLicenses()
    } catch (e) {
      error.value = e.response?.data?.error || e.message
      throw e
    } finally {
      loading.value = false
    }
  }

  async function verifyLicense(id) {
    try {
      const { data } = await licensesApi.verify(id)
      return data
    } catch (e) {
      throw e
    }
  }

  async function bulkGenerate(domains) {
    loading.value = true
    try {
      const { data } = await licensesApi.bulkGenerate(domains)
      await fetchLicenses()
      return data
    } catch (e) {
      error.value = e.response?.data?.error || e.message
      throw e
    } finally {
      loading.value = false
    }
  }

  return {
    licenses,
    total,
    loading,
    error,
    filters,
    fetchLicenses,
    generateLicense,
    revokeLicense,
    verifyLicense,
    bulkGenerate,
  }
})
