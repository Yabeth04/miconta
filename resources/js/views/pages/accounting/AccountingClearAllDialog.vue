<template>
  <VDialog
    :model-value="modelValue"
    max-width="480"
    persistent
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <VCard rounded="lg">
      <VCardTitle class="text-h6 px-5 pt-5 pb-3">
        Limpiar todos los movimientos
      </VCardTitle>

      <VDivider />

      <VCardText class="pa-5 d-flex flex-column gap-4">
        <VAlert
          type="warning"
          variant="tonal"
          rounded="lg"
        >
          Se eliminarán <strong>{{ totalCount }}</strong> movimiento{{ totalCount === 1 ? '' : 's' }}.
          Esta acción no se puede deshacer. El saldo inicial se mantiene.
        </VAlert>

        <VAlert
          v-if="error"
          type="error"
          variant="tonal"
          rounded="lg"
        >
          {{ error }}
        </VAlert>

        <VCheckbox
          v-model="acknowledged"
          hide-details
          label="Entiendo que se borrarán todos mis movimientos"
        />

        <VTextField
          v-model="confirmation"
          label='Escribí "ELIMINAR" para confirmar'
          placeholder="ELIMINAR"
          variant="outlined"
          rounded="lg"
          hide-details="auto"
          :error-messages="fieldError('confirmation')"
        />

        <VTextField
          v-model="password"
          label="Contraseña actual"
          placeholder="············"
          :type="passwordVisible ? 'text' : 'password'"
          :append-inner-icon="passwordVisible ? 'ri-eye-off-line' : 'ri-eye-line'"
          autocomplete="current-password"
          variant="outlined"
          rounded="lg"
          hide-details="auto"
          :error-messages="fieldError('current_password')"
          @click:append-inner="passwordVisible = !passwordVisible"
        />
      </VCardText>

      <VCardActions class="px-5 pb-5">
        <VSpacer />
        <VBtn
          variant="text"
          rounded="lg"
          :disabled="saving"
          @click="close"
        >
          Cancelar
        </VBtn>
        <VBtn
          color="error"
          variant="flat"
          rounded="lg"
          :loading="saving"
          :disabled="!canConfirm"
          @click="confirmClearAll"
        >
          Eliminar todo
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<script>
import { axios } from '@/plugins/axios'

export default {
  name: 'AccountingClearAllDialog',

  props: {
    modelValue: {
      type: Boolean,
      default: false,
    },
    totalCount: {
      type: Number,
      default: 0,
    },
  },

  emits: ['update:modelValue', 'cleared'],

  data() {
    return {
      acknowledged: false,
      confirmation: '',
      password: '',
      passwordVisible: false,
      saving: false,
      error: '',
      fieldErrors: {},
    }
  },

  computed: {
    canConfirm() {
      return this.acknowledged
        && this.confirmation.trim().toUpperCase() === 'ELIMINAR'
        && Boolean(this.password)
        && this.totalCount > 0
        && !this.saving
    },
  },

  watch: {
    modelValue(open) {
      if (open) {
        this.resetForm()
      }
    },
  },

  methods: {
    resetForm() {
      this.acknowledged = false
      this.confirmation = ''
      this.password = ''
      this.passwordVisible = false
      this.error = ''
      this.fieldErrors = {}
    },

    close() {
      if (this.saving) {
        return
      }

      this.$emit('update:modelValue', false)
    },

    fieldError(field) {
      return this.fieldErrors[field]?.[0] || null
    },

    async confirmClearAll() {
      if (!this.canConfirm || this.saving) {
        return
      }

      this.saving = true
      this.error = ''
      this.fieldErrors = {}

      try {
        const response = await axios.post('/api/accounting/destroy-all', {
          current_password: this.password,
          confirmation: this.confirmation.trim().toUpperCase(),
        })

        this.$emit('update:modelValue', false)
        this.$emit('cleared', { deleted: response.data.deleted })
        this.$toast.success(
          `Eliminados ${response.data.deleted} movimiento${response.data.deleted === 1 ? '' : 's'}`,
          { timeout: 2500, closeOnClick: true },
        )
      } catch (error) {
        this.error = error.response?.data?.message || 'No se pudieron eliminar los movimientos.'
        this.fieldErrors = error.response?.data?.errors || {}
      } finally {
        this.saving = false
      }
    },
  },
}
</script>
