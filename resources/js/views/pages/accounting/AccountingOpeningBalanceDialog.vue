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
          @blur="normalizeAmount"
        />

        <div class="d-flex justify-end gap-2 mt-6">
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
            :loading="saving"
            @click="save"
          >
            Guardar
          </VBtn>
        </div>
      </div>
    </VCard>
  </VDialog>
</template>

<script>
import { parseAmount } from '@core/utils/formatters'
import axios from 'axios'

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
      saving: false,
    }
  },

  watch: {
    modelValue(open) {
      if (open)
        this.amount = this.$formatAmountValue(this.openingBalance)
    },
  },

  methods: {
    close() {
      this.$emit('update:modelValue', false)
    },
    normalizeAmount() {
      const n = parseAmount(this.amount)

      this.amount = n === '' ? '' : this.$formatAmountValue(n)
    },
    async save() {
      if (this.saving)
        return

      const n = parseAmount(this.amount)
      if (n === '') {
        this.$toast.error('Ingresa un saldo inicial válido')

        return
      }

      this.saving = true

      try {
        await axios.put('/api/accounting/settings', {
          opening_balance_main: n,
        })

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
