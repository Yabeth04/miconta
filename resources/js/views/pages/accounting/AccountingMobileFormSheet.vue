<template>
  <div>
    <VBottomSheet
      v-model="formSheet"
      :scrim="true"
    >
      <VCard
        rounded="t-lg"
        class="accounting-form-sheet"
      >
        <div class="d-flex align-center justify-space-between px-4 pt-4 pb-2">
          <span class="text-h6">
            Registrar movimiento
          </span>
          <VBtn
            icon
            variant="text"
            aria-label="Cerrar"
            @click="closeFormSheet"
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
              <AccountingConceptCombobox
                v-model="concept"
                :concepts="concepts"
              />
            </VCol>

            <VCol cols="12">
              <VTextField
                v-model="detail"
                type="text"
                label="Detalle"
                placeholder="Opcional, ej. 10 litros"
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
                @keyup.enter="storeAccounting"
              />
            </VCol>
          </VRow>

          <VBtn
            class="mt-4"
            color="primary"
            rounded="lg"
            block
            @click="storeAccounting"
          >
            Contabilizar
          </VBtn>
        </VForm>
      </VCard>
    </VBottomSheet>
  </div>
</template>

<script>
import submittedVuelidateForm from '@/mixins/submittedVuelidateForm'
import { parseAmount } from '@core/utils/formatters'
import { useVuelidate } from '@vuelidate/core'
import { helpers, required } from '@vuelidate/validators'
import { axios } from '@/plugins/axios'
import AccountingConceptCombobox from '@/views/pages/accounting/AccountingConceptCombobox.vue'

export default {
  name: 'AccountingMobileFormSheet',
  components: {
    AccountingConceptCombobox,
  },
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
    concepts: {
      type: Array,
      default: () => [],
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
      formSheet: false,
      date: new Date(),
      selectedPaymentType: null,
      selectedMovementType: null,
      amount: '',
      concept: '',
      detail: '',
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
    openFormSheet() {
      this.resetForm()
      this.formSheet = true
    },
    closeFormSheet() {
      this.formSheet = false
      this.resetForm()
    },
    async storeAccounting() {
      this.submitted = true
      const isValid = await this.v$.$validate()

      if (!isValid) {
        return
      }

      axios
        .post('/api/accounting', {
          date: this.$formatDate(this.date),
          'movement_type': this.selectedMovementType,
          'payment_type': this.selectedPaymentType,
          amount: this.$parseAmount(this.amount),
          concept: this.concept,
          detail: this.detail,
        })
        .then(() => {
          this.resetForm()
          this.formSheet = false
          this.$emit('saved')
          this.$toast.success('Guardado correctamente', {
            timeout: 2000,
            closeOnClick: true,
          })
        })
        .catch(error => {
          console.log(error)
        })
    },
    resetForm() {
      this.date = new Date()
      this.selectedMovementType = null
      this.selectedPaymentType = null
      this.amount = ''
      this.concept = ''
      this.detail = ''
      this.submitted = false
      this.v$.$reset()
    },
    normalizeAmount() {
      const n = this.$parseAmount(this.amount)

      this.amount = n === '' ? '' : this.$formatAmountValue(n)
    },
  },
}
</script>

<style scoped>
.accounting-form-sheet {
  max-height: min(90vh, 720px);
  overflow-y: auto;
}
</style>
