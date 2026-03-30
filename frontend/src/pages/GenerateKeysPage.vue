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

    <!-- No key warning -->
    <div v-if="store.keys.length === 0 && !store.loading" class="empty-state">
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
        <template v-slot:header="props">
          <q-tr :props="props">
            <q-th auto-width />
            <q-th v-for="col in props.cols" :key="col.name" :props="props">
              {{ col.label }}
            </q-th>
          </q-tr>
        </template>

        <template v-slot:body="props">
          <q-tr :props="props" class="cursor-pointer" @click="props.expand = !props.expand">
            <q-td auto-width>
               <q-btn size="sm" color="primary" round dense outline @click.stop="props.expand = !props.expand" :icon="props.expand ? 'remove' : 'add'" />
            </q-td>
            <q-td v-for="col in props.cols" :key="col.name" :props="props">
              <template v-if="col.name === 'actions'">
                <q-btn flat round size="sm" icon="delete" color="negative" @click.stop="confirmDelete(props.row)" />
              </template>
              <template v-else>
                {{ col.value }}
              </template>
            </q-td>
          </q-tr>
          <q-tr v-show="props.expand" :props="props">
            <q-td colspan="100%" class="bg-grey-1">
              <div class="row q-pa-md q-col-gutter-md">
                 <!-- Expanded content: Public Key and Hash -->
                 <div class="col-12 col-md-8">
                   <div class="text-subtitle2 text-grey-8 q-mb-sm">Public Key</div>
                   <div class="key-display" style="font-size: 11px; white-space: pre-wrap; word-break: break-all;">
                      {{ props.row.public_key }}
                   </div>
                   <q-btn size="sm" color="primary" outline icon="content_copy" label="Copy Public Key" @click="copyText(props.row.public_key)" class="q-mt-sm" />
                   <q-btn size="sm" color="secondary" outline icon="download" label="Export Guide (.md)" @click="exportGuide(props.row)" class="q-mt-sm q-ml-sm" />
                 </div>
                 <div class="col-12 col-md-4">
                   <div class="text-subtitle2 text-grey-8 q-mb-sm">Details</div>
                   <q-list dense>
                     <q-item>
                       <q-item-section>Private Key Hash</q-item-section>
                       <q-item-section side class="text-caption" style="font-family: monospace">
                         {{ (props.row.private_key_hash || '').substring(0, 16) }}...
                       </q-item-section>
                     </q-item>
                     <q-item>
                       <q-item-section>Created By</q-item-section>
                       <q-item-section side>{{ props.row.created_by }}</q-item-section>
                     </q-item>
                   </q-list>
                 </div>
              </div>
            </q-td>
          </q-tr>
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
            You are generating a new RSA key pair. Old keys will remain active and you can choose which key to sign new licenses with later.
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
    <q-dialog v-model="showResultDialog" persistent>
      <q-card style="min-width: 800px; max-width: 95vw;">
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
  { name: 'created_at', label: 'Created', field: 'created_at', align: 'left', sortable: true },
  { name: 'actions', label: 'Actions', field: 'id', align: 'center' },
]

async function copyText(text) {
  if (!text) return
  await copyToClipboard(text)
  $q.notify({ type: 'positive', message: 'Copied to clipboard!' })
}

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
    title: '<span class="text-negative"><q-icon name="warning" /> Critical Warning</span>',
    message: `Deleting this key will <b>permanently erase</b> the Private Key file (.pem) from the server.<br><br>Old licenses previously signed by <b>${key.key_name}</b> will become orphaned and cannot be re-issued or natively verified by the system.<br><br>Type <strong>DELETE</strong> below to confirm your irreversible action:`,
    html: true,
    prompt: {
      model: '',
      type: 'text',
      isValid: val => val === 'DELETE'
    },
    cancel: true,
    persistent: true,
    color: 'negative',
  }).onOk(async () => {
    try {
      await store.deleteKey(key.id)
      $q.notify({ type: 'positive', message: 'Private Key permanently deleted.' })
    } catch (e) {
      $q.notify({ type: 'negative', message: 'Failed to delete key' })
    }
  })
}

function exportGuide(key) {
  const apiUrl = window.location.origin + '/wp-json/tslm/v1/licenses/heartbeat'
  const mdContent = `# TS License Manager Integration Guide

## 1. Overview
This guide provides the necessary code and credentials to integrate your plugin/theme with the TS License Manager using the RSA-2048 Asymmetric Signature verification method.

## 2. API Server
**Endpoint:** \`${apiUrl}\`

## 3. Your Public Key (ID: ${key.id})
To verify licenses signed by **${key.key_name}**, you must hardcode the following Public Key into your plugin's source code.

\`\`\`
${key.public_key}
\`\`\`

## 4. Integration Code Snippet
Add this snippet to your plugin to verify the signature of a stored license.

\`\`\`php
function verify_ts_license( $license_string ) {
    $parts = explode( '.', $license_string );
    if ( count( $parts ) !== 2 ) return false;
    
    $payload_json = base64_decode( $parts[0] );
    $signature    = base64_decode( $parts[1] );
    
    // HARDCODE YOUR PUBLIC KEY HERE
    $public_key = <<<EOD
${key.public_key}
EOD;

    $key_res = openssl_pkey_get_public( $public_key );
    if ( ! $key_res ) return false;

    $verified = openssl_verify( $payload_json, $signature, $key_res, OPENSSL_ALGO_SHA256 );

    if ( $verified === 1 ) {
        return json_decode( $payload_json, true );
    }

    return false;
}
\`\`\`

## 5. Pro Tips & Security Best Practices
- **Do not store the public key in the database (wp_options)**: This prevents malicious users from easily replacing it with their own key to bypass verification.
- **Obfuscation**: Use a PHP obfuscator (like **ionCube**, SourceGuardian, or php-obfuscator) on the file containing the \`verify_ts_license\` function and the Public Key string. This makes it extremely difficult for hackers to locate and modify the verification logic.
- **Hybrid Heartbeat (Optional)**: To force remote checking while allowing offline use, implement a Hybrid Heartbeat mechanism that stores the \`expires_at\` payload locally and calls the API Endpoint seamlessly via a scheduled WP-Cron job.

---
*Generated by TS License Manager on ${new Date().toLocaleString()}*
`
  
  const blob = new Blob([mdContent], { type: 'text/markdown;charset=utf-8' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `integration-guide-key-${key.id}.md`
  a.click()
  URL.revokeObjectURL(url)
  $q.notify({ type: 'positive', message: 'Integration Guide exported!' })
}

onMounted(() => {
  store.fetchKeys()
})
</script>
