<template>
  <q-page padding>
    <div class="page-header">
      <div class="row items-center justify-between">
        <div>
          <h1>Key Management</h1>
          <p>Manage RSA-2048 key pairs for license signing</p>
        </div>
        <q-btn
          color="primary"
          icon="add"
          label="Generate New Key Pair"
          @click="showGenerateDialog = true"
          :loading="store.loading"
        />
      </div>
    </div>

    <!-- Active Key Info -->
    <q-card v-if="store.activeKey" class="q-mb-lg" style="border-left: 4px solid #34a853">
      <q-card-section>
        <div class="row items-center q-mb-md">
          <q-icon name="vpn_key" color="positive" size="md" class="q-mr-sm" />
          <div class="text-h6">Active Key: {{ store.activeKey.key_name }}</div>
          <q-space />
          <q-badge color="positive">ACTIVE</q-badge>
        </div>

        <div class="row q-col-gutter-md">
          <div class="col-12 col-md-6">
            <div class="text-subtitle2 q-mb-xs text-grey-7">Public Key</div>
            <div class="key-display">{{ store.activeKey.public_key }}</div>
          </div>
          <div class="col-12 col-md-6">
            <div class="text-subtitle2 q-mb-xs text-grey-7">Details</div>
            <q-list dense>
              <q-item>
                <q-item-section>Created</q-item-section>
                <q-item-section side>{{ store.activeKey.created_at }}</q-item-section>
              </q-item>
              <q-item>
                <q-item-section>Private Key</q-item-section>
                <q-item-section side>
                  <q-badge :color="store.activeKey.has_private_key ? 'positive' : 'negative'">
                    {{ store.activeKey.has_private_key ? 'Available' : 'Missing' }}
                  </q-badge>
                </q-item-section>
              </q-item>
              <q-item>
                <q-item-section>Hash (SHA-256)</q-item-section>
                <q-item-section side class="text-caption" style="font-family: monospace">
                  {{ (store.activeKey.private_key_hash || '').substring(0, 16) }}...
                </q-item-section>
              </q-item>
            </q-list>
          </div>
        </div>
      </q-card-section>
    </q-card>

    <!-- No key warning -->
    <div v-if="!store.activeKey && !store.loading" class="empty-state">
      <q-icon name="vpn_key_off" />
      <h3>No Key Pair Generated</h3>
      <p>Generate an RSA-2048 key pair to start issuing licenses</p>
      <q-btn color="primary" icon="add" label="Generate Key Pair" @click="showGenerateDialog = true" class="q-mt-md" />
    </div>

    <!-- All Keys Table -->
    <q-card v-if="store.keys.length > 0" class="q-mt-lg">
      <q-card-section>
        <div class="text-h6">All Key Pairs</div>
      </q-card-section>
      <q-table
        :rows="store.keys"
        :columns="columns"
        row-key="id"
        flat
        :loading="store.loading"
        :rows-per-page-options="[10, 20, 50]"
      >
        <template v-slot:body-cell-is_active="props">
          <q-td :props="props">
            <q-badge :color="props.value == 1 ? 'positive' : 'grey'">
              {{ props.value == 1 ? 'Active' : 'Inactive' }}
            </q-badge>
          </q-td>
        </template>

        <template v-slot:body-cell-actions="props">
          <q-td :props="props">
            <q-btn
              flat round size="sm" icon="delete" color="negative"
              @click="confirmDelete(props.row)"
              :disable="props.row.is_active == 1"
            >
              <q-tooltip>{{ props.row.is_active == 1 ? 'Cannot delete active key' : 'Delete' }}</q-tooltip>
            </q-btn>
          </q-td>
        </template>
      </q-table>
    </q-card>

    <!-- Generate Key Dialog -->
    <q-dialog v-model="showGenerateDialog" persistent>
      <q-card style="min-width: 450px">
        <q-card-section class="row items-center">
          <q-icon name="vpn_key" color="primary" size="md" class="q-mr-sm" />
          <div class="text-h6">Generate New Key Pair</div>
        </q-card-section>

        <q-card-section>
          <q-banner class="bg-warning text-dark q-mb-md rounded-borders" dense>
            <template v-slot:avatar>
              <q-icon name="warning" />
            </template>
            Generating a new key will deactivate the current one. Existing licenses signed with the old key will become invalid!
          </q-banner>

          <q-input
            v-model="keyName"
            label="Key Name"
            hint="A friendly name for this key pair"
            outlined
            class="q-mb-md"
          />
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Cancel" @click="showGenerateDialog = false" />
          <q-btn
            color="primary"
            label="Generate"
            icon="vpn_key"
            @click="handleGenerate"
            :loading="generating"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Generated Key Result Dialog -->
    <q-dialog v-model="showResultDialog" persistent full-width>
      <q-card>
        <q-card-section class="row items-center bg-positive text-white">
          <q-icon name="check_circle" size="md" class="q-mr-sm" />
          <div class="text-h6">Key Pair Generated Successfully!</div>
        </q-card-section>

        <q-card-section>
          <q-banner class="bg-red-1 text-red-9 q-mb-md rounded-borders" dense>
            <template v-slot:avatar>
              <q-icon name="error" color="red" />
            </template>
            <strong>IMPORTANT:</strong> Download the private key NOW. It cannot be retrieved later!
          </q-banner>

          <div class="row q-col-gutter-md">
            <div class="col-12 col-md-6">
              <div class="text-subtitle2 q-mb-xs">Public Key</div>
              <div class="key-display">{{ generatedResult?.public_key }}</div>
            </div>
            <div class="col-12 col-md-6">
              <div class="text-subtitle2 q-mb-xs">Private Key (KEEP SECRET!)</div>
              <div class="key-display" style="background: #fef2f2; border-color: #fecaca;">
                {{ generatedResult?.private_key }}
              </div>
            </div>
          </div>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn
            color="primary"
            icon="download"
            label="Download Private Key"
            @click="downloadPrivateKey"
          />
          <q-btn
            color="secondary"
            icon="content_copy"
            label="Copy Public Key"
            @click="copyPublicKey"
          />
          <q-btn flat label="Close" @click="showResultDialog = false" />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useQuasar, copyToClipboard } from 'quasar'
import { useKeyStore } from '@/stores/keys'

const $q = useQuasar()
const store = useKeyStore()

const showGenerateDialog = ref(false)
const showResultDialog = ref(false)
const keyName = ref('default')
const generating = ref(false)
const generatedResult = ref(null)

const columns = [
  { name: 'id', label: 'ID', field: 'id', align: 'left', sortable: true },
  { name: 'key_name', label: 'Name', field: 'key_name', align: 'left' },
  { name: 'is_active', label: 'Status', field: 'is_active', align: 'center' },
  { name: 'created_at', label: 'Created', field: 'created_at', align: 'left', sortable: true },
  { name: 'actions', label: 'Actions', field: 'id', align: 'center' },
]

async function handleGenerate() {
  generating.value = true
  try {
    const result = await store.generateKey(keyName.value || 'default')
    generatedResult.value = result
    showGenerateDialog.value = false
    showResultDialog.value = true
    $q.notify({ type: 'positive', message: 'Key pair generated successfully!' })
  } catch (e) {
    $q.notify({ type: 'negative', message: e.response?.data?.error || 'Failed to generate key pair' })
  } finally {
    generating.value = false
  }
}

function downloadPrivateKey() {
  if (!generatedResult.value?.private_key) return
  const blob = new Blob([generatedResult.value.private_key], { type: 'application/x-pem-file' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `${keyName.value || 'private'}_key.pem`
  a.click()
  URL.revokeObjectURL(url)
  $q.notify({ type: 'positive', message: 'Private key downloaded!' })
}

async function copyPublicKey() {
  if (!generatedResult.value?.public_key) return
  await copyToClipboard(generatedResult.value.public_key)
  $q.notify({ type: 'positive', message: 'Public key copied to clipboard!' })
}

function confirmDelete(key) {
  $q.dialog({
    title: 'Delete Key Pair',
    message: `Are you sure you want to delete key "${key.key_name}"? This action cannot be undone.`,
    cancel: true,
    persistent: true,
    color: 'negative',
  }).onOk(async () => {
    try {
      await store.deleteKey(key.id)
      $q.notify({ type: 'positive', message: 'Key deleted' })
    } catch (e) {
      $q.notify({ type: 'negative', message: 'Failed to delete key' })
    }
  })
}

onMounted(() => {
  store.fetchKeys()
})
</script>
