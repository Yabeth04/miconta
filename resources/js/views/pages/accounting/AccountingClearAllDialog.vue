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

      <VForm
        autocomplete="off"
        class="pa-5 d-flex flex-column gap-4"
        @submit.prevent="confirmClearAll"
      >
        <VAlert
          type="warning"
          variant="tonal"
          rounded="lg"
          class="mb-0"
        >
          Se eliminarán <strong>{{ totalCount }}</strong> movimiento{{ totalCount === 1 ? '' : 's' }}.
          Esta acción no se puede deshacer. El saldo inicial se mantiene.
        </VAlert>

        <VAlert
          v-if="error"
          type="error"
          variant="tonal"
          rounded="lg"
          class="mb-0"
        >
          {{ error }}
        </VAlert>

        <VTextField
          :id="confirmationFieldId"
          v-model="confirmation"
          label='Escribí "ELIMINAR" para confirmar'
          placeholder="ELIMINAR"
          type="text"
          :name="confirmationFieldName"
          autocomplete="one-time-code"
          autocapitalize="characters"
          autocorrect="off"
          spellcheck="false"
          inputmode="text"
          data-form-type="other"
          data-lpignore="true"
          data-1p-ignore
          variant="outlined"
          rounded="lg"
          hide-details="auto"
          :readonly="fieldsLocked"
          :error-messages="fieldError('confirmation')"
          @focus="unlockFields"
        />

        <VTextField
          :id="passwordFieldId"
          v-model="password"
          label="Contraseña actual"
          placeholder="············"
          :type="passwordVisible ? 'text' : 'password'"
          :name="passwordFieldName"
          autocomplete="off"
          data-form-type="other"
          data-lpignore="true"
          data-1p-ignore
          :append-inner-icon="passwordVisible ? 'ri-eye-off-line' : 'ri-eye-line'"
          variant="outlined"
          rounded="lg"
          hide-details="auto"
          :readonly="fieldsLocked"
          :error-messages="fieldError('current_password')"
          @focus="unlockFields"
          @click:append-inner="passwordVisible = !passwordVisible"
        />

        <div
          class="clear-all-dialog__ack"
          :class="{ 'clear-all-dialog__ack--checked': acknowledged }"
          role="button"
          tabindex="0"
          @click="toggleAcknowledged"
          @keydown.enter.prevent="toggleAcknowledged"
          @keydown.space.prevent="toggleAcknowledged"
        >
          <VCheckbox
            :model-value="acknowledged"
            hide-details
            density="compact"
            color="error"
            class="clear-all-dialog__ack-checkbox flex-shrink-0"
            @click.stop
            @update:model-value="acknowledged = $event ?? false"
          />
          <div class="min-w-0">
            <p class="text-body-2 font-weight-semibold mb-1">
              Confirmación final
            </p>
            <p class="text-body-2 text-medium-emphasis mb-0">
              Entiendo que se borrarán todos mis movimientos y que no podré recuperarlos.
            </p>
          </div>
        </div>
      </VForm>

      <VCardActions class="px-5 pb-5 pt-0">
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
      fieldsLocked: true,
      fieldSuffix: '',
      saving: false,
      error: '',
      fieldErrors: {},
    }
  },

  computed: {
    confirmationFieldId() {
      return `clear-all-confirmation-${this.fieldSuffix}`
    },

    passwordFieldId() {
      return `clear-all-password-${this.fieldSuffix}`
    },

    confirmationFieldName() {
      return `clear-all-confirmation-${this.fieldSuffix}`
    },

    passwordFieldName() {
      return `clear-all-password-${this.fieldSuffix}`
    },

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
      this.fieldSuffix = Math.random().toString(36).slice(2)
      this.acknowledged = false
      this.confirmation = ''
      this.password = ''
      this.passwordVisible = false
      this.fieldsLocked = true
      this.error = ''
      this.fieldErrors = {}
    },

    unlockFields() {
      this.fieldsLocked = false
    },

    toggleAcknowledged() {
      this.acknowledged = !this.acknowledged
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

<style scoped>
.clear-all-dialog__ack {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  padding: 0.875rem 1rem;
  border-radius: 0.75rem;
  border: thin solid rgba(var(--v-theme-on-surface), 0.12);
  background: rgba(var(--v-theme-on-surface), 0.03);
  cursor: pointer;
  transition: border-color 0.2s ease, background-color 0.2s ease;
}

.clear-all-dialog__ack:hover {
  background: rgba(var(--v-theme-on-surface), 0.05);
}

.clear-all-dialog__ack--checked {
  border-color: rgba(var(--v-theme-error), 0.45);
  background: rgba(var(--v-theme-error), 0.06);
}

.clear-all-dialog__ack-checkbox {
  margin-top: 0.125rem;
  pointer-events: none;
}
</style>
