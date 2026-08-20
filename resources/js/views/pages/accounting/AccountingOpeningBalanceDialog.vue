<template>
  <VDialog
    :model-value="modelValue"
    max-width="420"
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <VCard rounded="lg">
      <div class="d-flex align-center justify-space-between px-5 pt-5 pb-3">
        <span class="text-h6">
          Saldo inicial
        </span>
        <VBtn
          icon
          variant="text"
          aria-label="Cerrar"
          @click="close"
        >
          <VIcon icon="ri-close-line" />
        </VBtn>
      </div>

      <VDivider />

      <div class="pa-5">
        <p class="text-body-2 text-medium-emphasis mb-4">
          Monto con el que empezó la cuenta principal (BN). El monto en cuenta se calcula así:
          saldo inicial + haber − debe.
        </p>

        <VAlert
          v-if="!unlocked"
          color="primary"
          variant="tonal"
          density="compact"
          rounded="lg"
          class="mb-4"
          icon="ri-lock-line"
        >
          El campo está bloqueado. Tocá <strong>Desbloquear</strong> 3 veces para editar.
        </VAlert>

        <VTextField
          v-currency-live
          v-model="amount"
          type="text"
          inputmode="decimal"
          autocomplete="off"
          label="Saldo inicial"
          variant="outlined"
          rounded="lg"
          hide-details="auto"
          :readonly="!unlocked"
          :disabled="!unlocked"
          @blur="normalizeAmount"
        />

        <div class="d-flex flex-wrap justify-space-between align-center gap-2 mt-6">
          <VBtn
            v-if="!unlocked"
            variant="tonal"
            color="primary"
            rounded="lg"
            @click="tapUnlock"
          >
            Desbloquear ({{ unlockClicks }}/3)
          </VBtn>
          <VChip
            v-else
            color="success"
            variant="tonal"
            size="small"
            prepend-icon="ri-lock-unlock-line"
          >
            Editable
          </VChip>

          <div class="d-flex gap-2 ms-auto">
            <VBtn
              variant="text"
              rounded="lg"
              @click="close"
            >
              Cancelar
            </VBtn>
            <VBtn
              color="primary"
              rounded="lg"
              :disabled="!unlocked"
              :loading="saving"
              @click="requestSave"
            >
              Guardar
            </VBtn>
          </div>
        </div>
      </div>
    </VCard>
  </VDialog>

  <VDialog
    v-model="confirmDialog"
    max-width="400"
  >
    <VCard rounded="lg">
      <VCardTitle class="text-h6">
        Confirmar cambio
      </VCardTitle>
      <VCardText class="text-body-2">
        Vas a cambiar el saldo inicial de
        <strong>{{ $formatAmountValue(originalAmount) }}</strong>
        a
        <strong>{{ $formatAmountValue(pendingAmount) }}</strong>.
        Esto afecta el monto en cuenta. ¿Continuar?
      </VCardText>
      <VCardActions class="px-4 pb-4">
        <VSpacer />
        <VBtn
          variant="text"
          rounded="lg"
          @click="confirmDialog = false"
        >
          Cancelar
        </VBtn>
        <VBtn
          color="primary"
          variant="flat"
          rounded="lg"
          :loading="saving"
          @click="save"
        >
          Sí, guardar
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<script>
import { parseAmount } from '@core/utils/formatters';
import axios from 'axios';

export default {
  name: 'AccountingOpeningBalanceDialog',

  props: {
    modelValue: {
      type: Boolean,
      default: false,
    },
    openingBalance: {
      type: [Number, String],
      default: 0,
    },
  },

  emits: ['update:modelValue', 'saved'],

  data() {
    return {
      amount: '',
      originalAmount: 0,
      pendingAmount: 0,
      unlockClicks: 0,
      unlocked: false,
      saving: false,
      confirmDialog: false,
    }
  },

  watch: {
    modelValue(open) {
      if (open)
        this.resetForm()
      else
        this.confirmDialog = false
    },
  },

  methods: {
    resetForm() {
      this.originalAmount = parseAmount(this.openingBalance) || 0
      this.amount = this.$formatAmountValue(this.originalAmount)
      this.unlockClicks = 0
      this.unlocked = false
      this.pendingAmount = 0
      this.confirmDialog = false
    },
    close() {
      this.$emit('update:modelValue', false)
    },
    tapUnlock() {
      this.unlockClicks += 1
      if (this.unlockClicks >= 3)
        this.unlocked = true
    },
    normalizeAmount() {
      const n = parseAmount(this.amount)

      this.amount = n === '' ? '' : this.$formatAmountValue(n)
    },
    requestSave() {
      if (!this.unlocked || this.saving)
        return

      const n = parseAmount(this.amount)
      if (n === '') {
        this.$toast.error('Ingresa un saldo inicial válido')

        return
      }

      // Sin cambios reales: cerrar sin confirmar
      if (Number(n) === Number(this.originalAmount)) {
        this.close()

        return
      }

      this.pendingAmount = n
      this.confirmDialog = true
    },
    async save() {
      if (this.saving)
        return

      const n = this.pendingAmount
      if (n === '' || n === null || Number.isNaN(Number(n))) {
        this.$toast.error('Ingresa un saldo inicial válido')

        return
      }

      this.saving = true

      try {
        await axios.put('/api/accounting/settings', {
          opening_balance_main: n,
        })

        this.confirmDialog = false
        this.close()
        this.$emit('saved')
        this.$toast.success('Saldo inicial guardado', { timeout: 2000, closeOnClick: true })
      } catch (error) {
        console.log(error)
      } finally {
        this.saving = false
      }
    },
  },
}
</script>
