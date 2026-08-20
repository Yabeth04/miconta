<template>
  <VBottomSheet
    v-model="editSheet"
    :scrim="true"
  >
    <VCard
      rounded="t-lg"
      class="accounting-edit-sheet"
    >
      <div class="d-flex align-center justify-space-between px-4 pt-4 pb-2">
        <span class="text-h6">
          Editar movimiento
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

      <VForm class="pa-4">
        <VRow
          align="start"
          dense
        >
          <VCol cols="12">
            <VDateInput
              v-model="date"
              label="Fecha"
              variant="outlined"
              rounded="lg"
              prepend-icon=""
              append-inner-icon="ri-calendar-line"
              :error-messages="errors(v$.date)"
              hide-details="auto"
              show-adjacent-months
            />
          </VCol>
          <VCol cols="12">
            <VTextField
              v-model="description"
              type="text"
              label="Descripción"
              variant="outlined"
              rounded="lg"
              hide-details="auto"
            />
          </VCol>
          <VCol cols="12">
            <VSelect
              v-model="selectedMovementType"
              label="Tipo de movimiento"
              :items="movementTypes"
              variant="outlined"
              rounded="lg"
              :error-messages="errors(v$.selectedMovementType)"
              hide-details="auto"
            />
          </VCol>
          <VCol cols="12">
            <VSelect
              v-model="selectedPaymentType"
              label="Tipo de pago"
              :items="paymentTypes"
              variant="outlined"
              rounded="lg"
              :error-messages="errors(v$.selectedPaymentType)"
              hide-details="auto"
            />
          </VCol>
          <VCol cols="12">
            <VTextField
              v-currency-live
              v-model="v$.amount.$model"
              type="text"
              inputmode="decimal"
              autocomplete="off"
              label="Monto"
              variant="outlined"
              rounded="lg"
              hide-details="auto"
              :error-messages="errors(v$.amount)"
              @blur="normalizeAmount"
            />
          </VCol>
        </VRow>

        <VBtn
          class="mt-4"
          color="primary"
          rounded="lg"
          block
          :loading="saving"
          @click="save"
        >
          Guardar cambios
        </VBtn>
      </VForm>
    </VCard>
  </VBottomSheet>
</template>

<script>
import submittedVuelidateForm from '@/mixins/submittedVuelidateForm'
import { parseAmount } from '@core/utils/formatters'
import { useVuelidate } from '@vuelidate/core'
import { helpers, required } from '@vuelidate/validators'
import axios from 'axios'

export default {
  name: 'AccountingMobileEditSheet',

  mixins: [submittedVuelidateForm],

  props: {
    movementTypes: {
      type: Array,
      required: true,
    },
    paymentTypes: {
      type: Array,
      required: true,
    },
  },

  emits: ['saved'],

  setup() {
    return {
      v$: useVuelidate({ $scope: false }),
    }
  },

  data() {
    return {
      editSheet: false,
      movement: null,
      date: new Date(),
      selectedPaymentType: null,
      selectedMovementType: null,
      amount: '',
      description: '',
      saving: false,
    }
  },

  validations() {
    return {
      date: {
        required: helpers.withMessage('Fecha requerida', required),
      },
      selectedMovementType: {
        required: helpers.withMessage('Tipo de movimiento requerido', required),
      },
      amount: {
        required: helpers.withMessage('Monto requerido', v => parseAmount(v) !== ''),
        valid: helpers.withMessage('Ingresa un monto válido', v => {
          const n = parseAmount(v)

          return n !== '' && n >= 0
        }),
      },
      selectedPaymentType: {
        required: helpers.withMessage('Tipo de pago requerido', required),
      },
    }
  },

  methods: {
    open(item) {
      this.movement = item
      this.loadMovement(item)
      this.editSheet = true
    },
    close() {
      this.editSheet = false
      this.movement = null
    },
    loadMovement(item) {
      this.date = this.parseMovementDate(item.date)
      this.description = item.description ?? ''
      this.selectedMovementType = item.movement_type
      this.selectedPaymentType = item.payment_type
      this.amount = this.$formatAmountValue(item.amount)
      this.submitted = false
      this.v$.$reset()
    },
    parseMovementDate(value) {
      if (!value)
        return new Date()

      const parts = String(value).slice(0, 10).split('-').map(Number)
      if (parts.length === 3 && parts.every(n => !Number.isNaN(n)))
        return new Date(parts[0], parts[1] - 1, parts[2])

      const parsed = new Date(value)

      return Number.isNaN(parsed.getTime()) ? new Date() : parsed
    },
    async save() {
      if (this.saving || !this.movement?.id)
        return

      this.submitted = true
      const isValid = await this.v$.$validate()

      if (!isValid)
        return

      this.saving = true

      try {
        await axios.put(`/api/accounting/${this.movement.id}`, {
          date: this.$formatDate(this.date),
          'movement_type': this.selectedMovementType,
          'payment_type': this.selectedPaymentType,
          amount: this.$parseAmount(this.amount),
          description: this.description,
        })

        this.close()
        this.$emit('saved')
        this.$toast.success('Movimiento actualizado', { timeout: 2000, closeOnClick: true })
      } catch (error) {
        console.log(error)
      } finally {
        this.saving = false
      }
    },
    normalizeAmount() {
      const n = this.$parseAmount(this.amount)

      this.amount = n === '' ? '' : this.$formatAmountValue(n)
    },
  },
}
</script>

<style scoped>
.accounting-edit-sheet {
  max-height: min(90vh, 720px);
  overflow-y: auto;
}
</style>
