import axios from 'axios'

/**
 * API client for WordPress REST API
 * Uses nonce-based authentication passed from WordPress via wp_localize_script
 */
const api = axios.create({
  baseURL: window.tslmConfig?.restUrl || '/wp-json/tslm/v1/',
  headers: {
    'Content-Type': 'application/json',
    'X-WP-Nonce': window.tslmConfig?.nonce || '',
  },
})

// Response interceptor for error handling
api.interceptors.response.use(
  (response) => response,
  (error) => {
    const message = error.response?.data?.message
      || error.response?.data?.error
      || error.message
      || 'An error occurred'

    console.error('[TSLM API Error]', message, error.response?.data)
    return Promise.reject(error)
  }
)

export default api

// ── Dashboard ──
export const dashboardApi = {
  getStats: () => api.get('dashboard/stats'),
}

// ── Keys ──
export const keysApi = {
  getAll: () => api.get('keys'),
  generate: (name = 'default') => api.post('keys/generate', { name }),
  delete: (id) => api.delete(`keys/${id}`),
  importKey: (id, privateKey) => api.post(`keys/${id}/import`, { private_key: privateKey }),
  getStatus: () => api.get('keys/status'),
}

// ── Licenses ──
export const licensesApi = {
  getAll: (params = {}) => api.get('licenses', { params }),
  generate: (data) => api.post('licenses/generate', data),
  delete: (id) => api.delete(`licenses/${id}`),
  verify: (id) => api.post(`licenses/${id}/verify`),
  bulkGenerate: (domains) => api.post('licenses/bulk', { domains }),
}

// ── Audit Log ──
export const auditApi = {
  getLog: (params = {}) => api.get('audit-log', { params }),
  clearLog: () => api.delete('audit-log'),
}

// ── System ──
export const systemApi = {
  backup: (password) => api.post('system/backup', { password }),
  restore: (file, password) => {
    const formData = new FormData()
    formData.append('file', file)
    formData.append('password', password)
    return api.post('system/restore', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    })
  },
}
