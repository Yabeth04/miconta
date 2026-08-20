<template>
  <VDialog
    :model-value="modelValue"
    max-width="480"
    :persistent="importing"
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <VCard rounded="lg">
      <VCardTitle class="d-flex align-center justify-space-between pa-4">
        <span class="text-h6">Importar Excel</span>
        <VBtn
          icon
          variant="text"
          :disabled="importing"
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
          :disabled="importing"
          :error-messages="fileError"
          hide-details="auto"
        />

        <div
          v-if="importing"
          class="d-flex align-center justify-center gap-3 mt-6"
        >
          <VProgressCircular
            indeterminate
            color="primary"
            size="28"
            width="3"
          />
          <span class="text-body-2">Importando...</span>
        </div>
      </VCardText>

      <VDivider />

      <VCardActions class="pa-4">
        <VSpacer />
        <VBtn
          variant="text"
          rounded="lg"
          :disabled="importing"
          @click="close"
        >
          Cancelar
        </VBtn>
        <VBtn
          color="primary"
          rounded="lg"
          :loading="importing"
          :disabled="!hasFile || importing"
          @click="startImport"
        >
          Importar
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<script>
import { axios } from '@/plugins/axios'

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
      importing: false,
    }
  },

  computed: {
    hasFile() {
      const selected = Array.isArray(this.file) ? this.file[0] : this.file

      return Boolean(selected)
    },
  },

  watch: {
    modelValue(open) {
      if (!open)
        this.reset()
    },
  },

  methods: {
    reset() {
      this.file = null
      this.fileError = ''
      this.importing = false
    },
    close() {
      if (this.importing)
        return

      this.$emit('update:modelValue', false)
    },
    async startImport() {
      const selected = Array.isArray(this.file) ? this.file[0] : this.file

      if (!selected || this.importing)
        return

      this.fileError = ''
      this.importing = true

      try {
        const formData = new FormData()

        formData.append('file', selected)

        const { data } = await axios.post('/api/accounting/import', formData, {
          headers: { 'Content-Type': 'multipart/form-data' },
        })

        this.$toast.success(`Importados ${data.imported} movimiento(s)`, {
          timeout: 2500,
          closeOnClick: true,
        })
        this.$emit('imported', data.imported)
        this.$emit('update:modelValue', false)
      }
      catch (error) {
        console.log(error)
        this.fileError = error?.response?.data?.message || 'No se pudo importar el archivo.'
        this.$toast.error(this.fileError, {
          timeout: 3000,
          closeOnClick: true,
        })
      }
      finally {
        this.importing = false
      }
    },
  },
}
</script>
