<template>
  <VDialog
    :model-value="modelValue"
    max-width="480"
    :persistent="busy"
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <VCard rounded="lg">
      <VCardTitle class="d-flex align-center justify-space-between pa-4">
        <span class="text-h6">Importar Excel</span>
        <VBtn
          icon
          variant="text"
          :disabled="busy"
          aria-label="Cerrar"
          @click="close"
        >
          <VIcon icon="ri-close-line" />
        </VBtn>
      </VCardTitle>

      <VDivider />

      <VCardText class="pa-4">
        <p class="text-body-2 text-medium-emphasis mb-4">
          Sube un Excel con:
          <strong>Fecha</strong>,
          <strong>Descripción</strong>,
          <strong>Débito / Salida</strong>,
          <strong>Crédito / Entrada</strong>,
          <strong>Método pago</strong>.
          La importación corre en segundo plano.
        </p>

        <VFileInput
          v-model="file"
          label="Archivo Excel"
          accept=".xlsx,.xls,.csv"
          prepend-icon=""
          prepend-inner-icon="ri-file-excel-2-line"
          variant="outlined"
          rounded="lg"
          show-size
          :disabled="busy"
          :error-messages="fileError"
          hide-details="auto"
        />

        <div
          v-if="importId"
          class="mt-5"
        >
          <div class="d-flex justify-space-between align-center mb-2">
            <span class="text-body-2">{{ statusMessage }}</span>
            <span class="text-caption text-medium-emphasis">{{ progress }}%</span>
          </div>

          <VProgressLinear
            :model-value="progress"
            color="primary"
            height="8"
            rounded
            :indeterminate="status === 'queued'"
          />

          <p
            v-if="importedCount > 0"
            class="text-caption text-medium-emphasis mt-2 mb-0"
          >
            Insertados: {{ importedCount }}
          </p>

          <VAlert
            v-if="statusErrors.length"
            class="mt-4"
            type="warning"
            variant="tonal"
            density="comfortable"
          >
            <ul class="ps-4 mb-0 text-caption">
              <li
                v-for="(err, i) in statusErrors.slice(0, 6)"
                :key="i"
              >
                {{ err }}
              </li>
            </ul>
          </VAlert>
        </div>
      </VCardText>

      <VDivider />

      <VCardActions class="pa-4">
        <VSpacer />
        <VBtn
          variant="text"
          rounded="lg"
          :disabled="busy"
          @click="close"
        >
          {{ isDone ? 'Cerrar' : 'Cancelar' }}
        </VBtn>
        <VBtn
          v-if="!isDone"
          color="primary"
          rounded="lg"
          :loading="uploading"
          :disabled="!hasFile || busy"
          @click="startImport"
        >
          Importar
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<script>
import axios from 'axios';

export default {
  name: 'AccountingImportDialog',

  props: {
    modelValue: {
      type: Boolean,
      default: false,
    },
  },

  emits: ['update:modelValue', 'imported'],

  data() {
    return {
      file: null,
      fileError: '',
      uploading: false,
      importId: null,
      status: '',
      progress: 0,
      importedCount: 0,
      statusMessage: '',
      statusErrors: [],
      pollTimer: null,
    }
  },

  computed: {
    hasFile() {
      const selected = Array.isArray(this.file) ? this.file[0] : this.file

      return Boolean(selected)
    },
    busy() {
      return this.uploading || ['queued', 'processing'].includes(this.status)
    },
    isDone() {
      return ['completed', 'failed'].includes(this.status)
    },
  },

  watch: {
    modelValue(open) {
      if (!open)
        this.reset()
    },
  },

  beforeUnmount() {
    this.stopPolling()
  },

  methods: {
    reset() {
      this.stopPolling()
      this.file = null
      this.fileError = ''
      this.uploading = false
      this.importId = null
      this.status = ''
      this.progress = 0
      this.importedCount = 0
      this.statusMessage = ''
      this.statusErrors = []
    },
    close() {
      if (this.busy)
        return

      this.$emit('update:modelValue', false)
    },
    stopPolling() {
      if (this.pollTimer) {
        clearInterval(this.pollTimer)
        this.pollTimer = null
      }
    },
    async startImport() {
      const selected = Array.isArray(this.file) ? this.file[0] : this.file

      if (!selected || this.busy)
        return

      this.fileError = ''
      this.uploading = true
      this.status = 'queued'
      this.progress = 0
      this.statusMessage = 'Subiendo archivo...'
      this.statusErrors = []

      try {
        const formData = new FormData()

        formData.append('file', selected)

        const { data } = await axios.post('/api/accounting/import', formData, {
          headers: { 'Content-Type': 'multipart/form-data' },
        })

        this.importId = data.import_id
        this.statusMessage = 'En cola...'
        this.startPolling()
      }
      catch (error) {
        console.log(error)
        this.status = 'failed'
        this.fileError = error?.response?.data?.message || 'No se pudo subir el archivo.'
        this.$toast.error('No se pudo iniciar la importación', {
          timeout: 3000,
          closeOnClick: true,
        })
      }
      finally {
        this.uploading = false
      }
    },
    startPolling() {
      this.stopPolling()
      this.pollStatus()
      this.pollTimer = setInterval(() => this.pollStatus(), 1000)
    },
    async pollStatus() {
      if (!this.importId)
        return

      try {
        const { data } = await axios.get(`/api/accounting/import/${this.importId}`)

        this.status = data.status
        this.progress = data.progress ?? 0
        this.importedCount = data.imported ?? 0
        this.statusMessage = data.message || ''
        this.statusErrors = data.errors || []

        if (data.status === 'completed') {
          this.stopPolling()
          this.$toast.success(data.message || 'Importación completada', {
            timeout: 2500,
            closeOnClick: true,
          })
          this.$emit('imported', data.imported)
        }

        if (data.status === 'failed') {
          this.stopPolling()
          this.$toast.error(data.message || 'Falló la importación', {
            timeout: 3000,
            closeOnClick: true,
          })
        }
      }
      catch (error) {
        console.log(error)
      }
    },
  },
}
</script>
