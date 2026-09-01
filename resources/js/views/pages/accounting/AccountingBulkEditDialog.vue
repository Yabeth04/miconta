<template>
  <VDialog
    v-if="mdAndUp"
    :model-value="modelValue"
    max-width="560"
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <VCard rounded="lg">
      <BulkEditForm
        :count="count"
        :movement-types="movementTypes"
        :payment-types="paymentTypes"
        :concepts="concepts"
        :saving="saving"
        :has-changes="hasChanges"
        :concept-value="concept"
        :detail-value="detail"
        :movement-type-value="selectedMovementType"
        :payment-type-value="selectedPaymentType"
        @close="close"
        @save="save"
        @concept-change="onConceptChange"
        @detail-change="onDetailChange"
        @movement-type-change="onMovementTypeChange"
        @payment-type-change="onPaymentTypeChange"
      />
    </VCard>
  </VDialog>

  <VBottomSheet
    v-else
    :model-value="modelValue"
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <VCard rounded="t-lg">
      <BulkEditForm
        :count="count"
        :movement-types="movementTypes"
        :payment-types="paymentTypes"
        :concepts="concepts"
        :saving="saving"
        :has-changes="hasChanges"
        :concept-value="concept"
        :detail-value="detail"
        :movement-type-value="selectedMovementType"
        :payment-type-value="selectedPaymentType"
        mobile
        @close="close"
        @save="save"
        @concept-change="onConceptChange"
        @detail-change="onDetailChange"
        @movement-type-change="onMovementTypeChange"
        @payment-type-change="onPaymentTypeChange"
      />
    </VCard>
  </VBottomSheet>
</template>

<script>
import { axios } from '@/plugins/axios'
import BulkEditForm from '@/views/pages/accounting/AccountingBulkEditForm.vue'
import { useDisplay } from 'vuetify'

export default {
  name: 'AccountingBulkEditDialog',

  components: {
    BulkEditForm,
  },

  props: {
    modelValue: {
      type: Boolean,
      default: false,
    },
    ids: {
      type: Array,
      default: () => [],
    },
    count: {
      type: Number,
      default: 0,
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
    const { mdAndUp } = useDisplay()

    return { mdAndUp }
  },

  data() {
    return {
      selectedPaymentType: null,
      selectedMovementType: null,
      concept: '',
      detail: '',
      saving: false,
      touched: {
        concept: false,
        detail: false,
        movement_type: false,
        payment_type: false,
      },
    }
  },

  computed: {
    hasChanges() {
      return this.touched.concept
        || this.touched.detail
        || this.touched.movement_type
        || this.touched.payment_type
    },
  },

  watch: {
    modelValue(open) {
      if (open)
        this.resetForm()
    },
  },

  methods: {
    resetForm() {
      this.selectedPaymentType = null
      this.selectedMovementType = null
      this.concept = ''
      this.detail = ''
      this.touched = {
        concept: false,
        detail: false,
        movement_type: false,
        payment_type: false,
      }
    },
    onConceptChange(value) {
      this.concept = value ?? ''
      this.touched.concept = true
    },
    onDetailChange(value) {
      this.detail = value ?? ''
      this.touched.detail = true
    },
    onMovementTypeChange(value) {
      this.selectedMovementType = value
      this.touched.movement_type = value != null && value !== ''
    },
    onPaymentTypeChange(value) {
      this.selectedPaymentType = value
      this.touched.payment_type = value != null && value !== ''
    },
    close() {
      this.$emit('update:modelValue', false)
    },
    buildPayload() {
      const payload = { ids: this.ids }

      if (this.touched.movement_type)
        payload.movement_type = this.selectedMovementType

      if (this.touched.payment_type)
        payload.payment_type = this.selectedPaymentType

      if (this.touched.concept)
        payload.concept = this.concept

      if (this.touched.detail)
        payload.detail = this.detail

      return payload
    },
    async save() {
      if (this.saving || !this.ids.length || !this.hasChanges)
        return

      this.saving = true

      try {
        const response = await axios.post('/api/accounting/bulk-update', this.buildPayload())

        this.close()
        this.$emit('saved')
        this.$toast.success(
          `Actualizados ${response.data.updated} movimiento${response.data.updated === 1 ? '' : 's'}`,
          { timeout: 2000, closeOnClick: true },
        )
      } catch (error) {
        const msg = error.response?.data?.message || 'No se pudieron actualizar los movimientos.'
        this.$toast.error(msg, { timeout: 3000, closeOnClick: true })
      } finally {
        this.saving = false
      }
    },
  },
}
</script>
