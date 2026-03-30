<template>
  <q-page padding>
    <div class="page-header">
      <h1>System Management</h1>
      <p>Secure Backup & Restore using AES-256 Encrypted ZIP.</p>
    </div>

    <div class="row q-col-gutter-lg">
      <!-- MỤC BACKUP -->
      <div class="col-12 col-md-6">
        <q-card class="full-height">
          <q-card-section>
            <div class="text-h6 q-mb-md">
              <q-icon name="cloud_download" color="primary" class="q-mr-sm" />
              Backup System
            </div>
            <p class="text-body2 text-grey-8">
              Create a secure, password-protected ZIP backup containing all your database records 
              (keys, licenses, domains, logs) and RSA key `.pem` files.
            </p>
            
            <q-form @submit="handleBackup" class="q-mt-lg">
              <q-input
                v-model="backupPassword"
                label="Encryption Password"
                type="password"
                outlined
                dense
                :rules="[val => !!val || 'Password is required.', val => val.length >= 6 || 'Minimum 6 characters.']"
                hint="Do NOT lose this password. You cannot restore without it."
              >
                <template v-slot:prepend>
                  <q-icon name="lock" />
                </template>
              </q-input>

              <div class="q-mt-md">
                <q-btn
                  type="submit"
                  color="primary"
                  icon="download"
                  label="Generate & Download Backup"
                  :loading="isBackingUp"
                />
              </div>
            </q-form>
          </q-card-section>
        </q-card>
      </div>

      <!-- MỤC RESTORE -->
      <div class="col-12 col-md-6">
        <q-card class="full-height">
          <q-card-section>
            <div class="text-h6 q-mb-md text-negative">
              <q-icon name="cloud_upload" color="negative" class="q-mr-sm" />
              Restore System
            </div>
            
            <q-banner class="bg-red-1 text-negative q-mb-md rounded-borders">
              <strong>Warning:</strong> Restoring will <b>completely wipe</b> all current database records 
              and overwrite existing RSA keys. This action cannot be undone!
            </q-banner>

            <q-form @submit="handleRestore" class="q-mt-lg">
              <q-file
                v-model="restoreFile"
                label="Select Backup ZIP File"
                outlined
                dense
                accept=".zip"
                :rules="[val => !!val || 'Backup file is required.']"
              >
                <template v-slot:prepend>
                  <q-icon name="attach_file" />
                </template>
              </q-file>

              <q-input
                v-model="restorePassword"
                label="Decryption Password"
                type="password"
                outlined
                dense
                class="q-mt-md"
                :rules="[val => !!val || 'Password is required.']"
              >
                <template v-slot:prepend>
                  <q-icon name="key" />
                </template>
              </q-input>

              <div class="q-mt-md">
                <q-btn
                  type="submit"
                  color="negative"
                  icon="restore"
                  label="Wipe & Restore System"
                  :loading="isRestoring"
                />
              </div>
            </q-form>
          </q-card-section>
        </q-card>
      </div>
    </div>

    <!-- API TOKEN GENERATOR -->
    <div class="q-mt-lg">
      <q-card>
        <q-card-section>
          <div class="text-h6 q-mb-md">
            <q-icon name="token" color="teal" class="q-mr-sm" />
            API Token Generator
            <q-badge color="teal-2" text-color="teal-10" class="q-ml-sm">Base64</q-badge>
          </div>
          <p class="text-body2 text-grey-8">
            Generate a Base64-encoded token for HTTP Basic Authentication.
            This encodes <code>username:api_key</code> to use in <code>Authorization: Basic &lt;token&gt;</code> headers.
          </p>

          <div class="row q-col-gutter-md q-mt-sm">
            <div class="col-12 col-md-6">
              <q-input
                v-model="tokenUsername"
                label="Username"
                outlined
                dense
                clearable
              >
                <template v-slot:prepend>
                  <q-icon name="person" />
                </template>
              </q-input>
            </div>
            <div class="col-12 col-md-6">
              <q-input
                v-model="tokenApiKey"
                label="API Key / Password"
                :type="showApiKey ? 'text' : 'password'"
                outlined
                dense
                clearable
              >
                <template v-slot:prepend>
                  <q-icon name="key" />
                </template>
                <template v-slot:append>
                  <q-icon
                    :name="showApiKey ? 'visibility_off' : 'visibility'"
                    class="cursor-pointer"
                    @click="showApiKey = !showApiKey"
                  >
                    <q-tooltip>{{ showApiKey ? 'Hide' : 'Show' }} API Key</q-tooltip>
                  </q-icon>
                </template>
              </q-input>
            </div>
          </div>

          <div class="q-mt-md">
            <q-btn
              color="teal"
              icon="generating_tokens"
              label="Generate Token"
              :disable="!tokenUsername || !tokenApiKey"
              @click="generateBase64Token"
              class="q-mr-sm"
            />
            <q-btn
              flat
              color="grey"
              icon="clear_all"
              label="Clear"
              @click="clearTokenFields"
              v-if="tokenUsername || tokenApiKey || generatedToken"
            />
          </div>

          <!-- Generated Output -->
          <div v-if="generatedToken" class="q-mt-lg">
            <q-separator class="q-mb-md" />
            <div class="text-subtitle2 text-grey-8 q-mb-xs">
              <q-icon name="check_circle" color="positive" class="q-mr-xs" />
              Generated Base64 Token:
            </div>
            <q-input
              v-model="generatedToken"
              outlined
              dense
              readonly
              type="textarea"
              autogrow
              class="token-output"
            >
              <template v-slot:append>
                <q-btn
                  flat
                  round
                  dense
                  icon="content_copy"
                  color="primary"
                  @click="copyToken(generatedToken)"
                >
                  <q-tooltip>Copy Token</q-tooltip>
                </q-btn>
              </template>
            </q-input>

            <div class="text-subtitle2 text-grey-8 q-mt-md q-mb-xs">
              <q-icon name="terminal" color="primary" class="q-mr-xs" />
              Ready-to-use Header:
            </div>
            <q-input
              :model-value="`Authorization: Basic ${generatedToken}`"
              outlined
              dense
              readonly
              class="token-output"
            >
              <template v-slot:append>
                <q-btn
                  flat
                  round
                  dense
                  icon="content_copy"
                  color="primary"
                  @click="copyToken(`Authorization: Basic ${generatedToken}`)"
                >
                  <q-tooltip>Copy Header</q-tooltip>
                </q-btn>
              </template>
            </q-input>

            <div class="text-subtitle2 text-grey-8 q-mt-md q-mb-xs">
              <q-icon name="code" color="deep-orange" class="q-mr-xs" />
              PowerShell Command:
            </div>
            <q-input
              :model-value="powershellCommand"
              outlined
              dense
              readonly
              type="textarea"
              autogrow
              class="token-output"
              input-style="font-family: 'Consolas', 'Courier New', monospace; font-size: 12px"
            >
              <template v-slot:append>
                <q-btn
                  flat
                  round
                  dense
                  icon="content_copy"
                  color="primary"
                  @click="copyToken(powershellCommand)"
                >
                  <q-tooltip>Copy PowerShell Command</q-tooltip>
                </q-btn>
              </template>
            </q-input>
          </div>
        </q-card-section>
      </q-card>
    </div>
  </q-page>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useQuasar } from 'quasar'
import { systemApi } from '../api'

const $q = useQuasar()

// Backup state
const backupPassword = ref('')
const isBackingUp = ref(false)

// Restore state
const restoreFile = ref(null)
const restorePassword = ref('')
const isRestoring = ref(false)

// Token Generator state
const tokenUsername = ref('')
const tokenApiKey = ref('')
const showApiKey = ref(false)
const generatedToken = ref('')

const powershellCommand = computed(() => {
  if (!tokenUsername.value || !tokenApiKey.value) return ''
  return `[Convert]::ToBase64String([Text.Encoding]::UTF8.GetBytes("${tokenUsername.value}:${tokenApiKey.value}"))`
})

function generateBase64Token() {
  if (!tokenUsername.value || !tokenApiKey.value) return
  const raw = `${tokenUsername.value}:${tokenApiKey.value}`
  generatedToken.value = btoa(unescape(encodeURIComponent(raw)))
  $q.notify({
    type: 'positive',
    message: 'Base64 token generated!',
    icon: 'check_circle',
    timeout: 1500,
  })
}

function clearTokenFields() {
  tokenUsername.value = ''
  tokenApiKey.value = ''
  generatedToken.value = ''
  showApiKey.value = false
}

function copyToken(text) {
  navigator.clipboard.writeText(text).then(() => {
    $q.notify({
      type: 'positive',
      message: 'Copied to clipboard!',
      icon: 'content_copy',
      timeout: 1200,
    })
  }).catch(() => {
    // Fallback
    const textarea = document.createElement('textarea')
    textarea.value = text
    document.body.appendChild(textarea)
    textarea.select()
    document.execCommand('copy')
    document.body.removeChild(textarea)
    $q.notify({
      type: 'positive',
      message: 'Copied to clipboard!',
      icon: 'content_copy',
      timeout: 1200,
    })
  })
}

const handleBackup = async () => {
  if (isBackingUp.value) return
  isBackingUp.value = true

  try {
    const { data } = await systemApi.backup(backupPassword.value)
    if (data.success && data.file) {
      // Decode base64 to binary
      const binaryString = atob(data.file)
      const len = binaryString.length
      const bytes = new Uint8Array(len)
      for (let i = 0; i < len; i++) {
        bytes[i] = binaryString.charCodeAt(i)
      }
      
      // Create blob and download
      const blob = new Blob([bytes], { type: 'application/zip' })
      const url = window.URL.createObjectURL(blob)
      const a = document.createElement('a')
      a.href = url
      a.download = data.name || 'tslm_backup.zip'
      document.body.appendChild(a)
      a.click()
      window.URL.revokeObjectURL(url)
      document.body.removeChild(a)

      backupPassword.value = ''
      $q.notify({
        type: 'positive',
        message: 'Backup generated successfully!',
        icon: 'check_circle',
      })
    } else {
      throw new Error('Missing file data in response')
    }
  } catch (error) {
    const msg = error.response?.data?.message || error.message || 'Backup failed'
    $q.notify({
      type: 'negative',
      message: msg,
      icon: 'error',
    })
  } finally {
    isBackingUp.value = false
  }
}

const handleRestore = async () => {
  if (isRestoring.value) return

  $q.dialog({
    title: 'Confirm Restore',
    message: 'Are you sure you want to restore? ALL current data will be erased and replaced with the backup.',
    cancel: true,
    persistent: true,
    color: 'negative',
    ok: {
      label: 'Yes, Wipe & Restore',
      color: 'negative',
      flat: false
    }
  }).onOk(async () => {
    isRestoring.value = true

    try {
      const { data } = await systemApi.restore(restoreFile.value, restorePassword.value)
      if (data.success) {
        $q.notify({
          type: 'positive',
          message: 'System restored successfully! Please reload the page.',
          icon: 'check_circle',
        })
        restoreFile.value = null
        restorePassword.value = ''
        
        // Reload after short delay
        setTimeout(() => {
          window.location.reload()
        }, 1500)
      }
    } catch (error) {
      const msg = error.response?.data?.message || 'Restore failed. Incorrect password or invalid file.'
      $q.notify({
        type: 'warning',
        message: msg,
        icon: 'warning',
      })
    } finally {
      isRestoring.value = false
    }
  })
}
</script>
