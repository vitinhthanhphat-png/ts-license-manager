import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { keysApi } from '@/api'

export const useKeyStore = defineStore('keys', () => {
  const keys = ref([])
  const status = ref(null)
  const loading = ref(false)
  const error = ref(null)

  const hasActiveKey = computed(() => status.value?.has_active_key === true)
  const hasPrivateKey = computed(() => status.value?.has_private_key === true)
  const activeKey = computed(() => keys.value.find(k => k.is_active == 1))

  async function fetchKeys() {
    loading.value = true
    error.value = null
    try {
      const { data } = await keysApi.getAll()
      keys.value = data
    } catch (e) {
      error.value = e.response?.data?.error || e.message
    } finally {
      loading.value = false
    }
  }

  async function fetchKeyStatus() {
    try {
      const { data } = await keysApi.getStatus()
      status.value = data
    } catch (e) {
      console.error('Failed to fetch key status', e)
    }
  }

  async function generateKey(name = 'default') {
    loading.value = true
    error.value = null
    try {
      const { data } = await keysApi.generate(name)
      await fetchKeys()
      await fetchKeyStatus()
      return data
    } catch (e) {
      error.value = e.response?.data?.error || e.message
      throw e
    } finally {
      loading.value = false
    }
  }

  async function deleteKey(id) {
    loading.value = true
    try {
      await keysApi.delete(id)
      await fetchKeys()
      await fetchKeyStatus()
    } catch (e) {
      error.value = e.response?.data?.error || e.message
      throw e
    } finally {
      loading.value = false
    }
  }

  async function importPrivateKey(id, privateKeyPem) {
    loading.value = true
    try {
      const { data } = await keysApi.importKey(id, privateKeyPem)
      await fetchKeys()
      await fetchKeyStatus()
      return data
    } catch (e) {
      error.value = e.response?.data?.error || e.message
      throw e
    } finally {
      loading.value = false
    }
  }

  return {
    keys,
    status,
    loading,
    error,
    hasActiveKey,
    hasPrivateKey,
    activeKey,
    fetchKeys,
    fetchKeyStatus,
    generateKey,
    deleteKey,
    importPrivateKey,
  }
})
