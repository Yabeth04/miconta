<template>
  <VDialog
    :model-value="modelValue"
    max-width="640"
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <VCard
      rounded="lg"
      class="accounting-edit-dialog"
    >
      <div class="d-flex align-center justify-space-between px-5 pt-5 pb-3">
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

      <VForm class="pa-5">
        <VRow align="start">
          <VCol
            cols="12"
            sm="4"
            class="pb-1"
          >
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
          <VCol
            cols="12"
            class="pb-1"
          >
            <AccountingConceptCombobox
              v-model="concept"
              :concepts="concepts"
              :error-messages="errors(v$.concept)"
            />
          </VCol>
          <VCol
            cols="12"
            class="pb-1"
          >
            <VTextField
              v-model="detail"
              type="text"
              label="Detalle (opcional)"
              placeholder="Ej. 10 litros"
              variant="outlined"
              rounded="lg"
              hide-details="auto"
            />
          </VCol>
          <VCol
            cols="12"
            sm="4"
            class="pb-1"
          >
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
          <VCol
            cols="12"
            sm="4"
            class="pb-1"
          >
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
          <VCol
            cols="12"
            sm="4"
            class="pb-1"
          >
            <VTextField
              v-currency-live
              v-model="amount"
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
            Guardar cambios
          </VBtn>
        </div>
      </VForm>
    </VCard>
  </VDialog>
</template>

<script>
import submittedVuelidateForm from '@/mixins/submittedVuelidateForm'
import { axios } from '@/plugins/axios'
import AccountingConceptCombobox from '@/views/pages/accounting/AccountingConceptCombobox.vue'
import { parseAmount } from '@core/utils/formatters'
import { useVuelidate } from '@vuelidate/core'
import { helpers, required } from '@vuelidate/validators'

export default {
  name: 'AccountingEditDialog',

  components: {
    AccountingConceptCombobox,
  },

  mixins: [submittedVuelidateForm],

  props: {
    modelValue: {
      type: Boolean,
      default: false,
    },
    movement: {
      type: Object,
      default: null,
    },
    movementTypes: {
      type: Array,
      required: true,
    },
    paymentTypes: {
      type: Array,
      required: true,
    },
    concepts: {
      type: Array,
      default: () => [],
    },
  },

  emits: ['update:modelValue', 'saved'],

  setup() {
    return {
      v$: useVuelidate({ $scope: false }),
    }
  },

  data() {
    return {
      date: new Date(),
      selectedPaymentType: null,
      selectedMovementType: null,
      amount: '',
      concept: '',
      detail: '',
      saving: false,
    }
  },

  watch: {
    modelValue(open) {
      if (open && this.movement)
        this.$nextTick(() => this.loadMovement(this.movement))
    },
    movement(item) {
      if (this.modelValue && item)
        this.$nextTick(() => this.loadMovement(item))
    },
  },

  mounted() {
    if (this.modelValue && this.movement)
      this.$nextTick(() => this.loadMovement(this.movement))
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
      concept: {
        required: helpers.withMessage('Concepto requerido', value => String(value ?? '').trim() !== ''),
      },
    }
  },

  methods: {
    loadMovement(item) {
      this.date = this.parseMovementDate(item.date)
      this.concept = item.concept ?? ''
      this.detail = item.detail ?? ''
      this.selectedMovementType = item.movement_type
      this.selectedPaymentType = item.payment_type
      this.amount = this.$formatAmountValue(item.amount)
      this.submitted = false
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
    close() {
      this.$emit('update:modelValue', false)
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
          concept: this.concept,
          detail: this.detail,
        })

        this.close()
        this.$emit('saved')
        this.$toast.success('Movimiento actualizado', { timeout: 2000, closeOnClick: true })
      } catch (error) {
        const msg = error.response?.data?.message
          || error.response?.data?.errors?.date?.[0]
          || 'No se pudo actualizar el movimiento.'
        this.$toast.error(msg, { timeout: 3500, closeOnClick: true })
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
