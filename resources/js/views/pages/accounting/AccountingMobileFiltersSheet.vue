<template>
  <VBottomSheet
    :model-value="modelValue"
    :scrim="true"
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <VCard
      rounded="t-lg"
      class="accounting-filter-sheet"
    >
      <div class="d-flex align-center justify-space-between px-4 pt-4 pb-2">
        <span class="text-h6">
          Filtros
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

      <div class="pa-4">
        <VRow
          align="start"
          dense
        >
          <VCol cols="12">
            <VDateInput
              :model-value="dateRange"
              label="Rango de fechas"
              placeholder="Desde — hasta"
              multiple="range"
              variant="outlined"
              rounded="lg"
              prepend-icon=""
              append-inner-icon="ri-calendar-line"
              hide-details="auto"
              clearable
              show-adjacent-months
              @update:model-value="$emit('update:dateRange', $event)"
            />
          </VCol>
          <VCol cols="12">
            <VSelect
              :model-value="selectedMovementTypes"
              label="Tipo de movimiento"
              :items="movementTypes"
              variant="outlined"
              rounded="lg"
              multiple
              clearable
              hide-details="auto"
              @update:model-value="$emit('update:selectedMovementTypes', $event)"
            />
          </VCol>
          <VCol cols="12">
            <VSelect
              :model-value="selectedPaymentTypes"
              label="Tipo de pago"
              :items="paymentTypes"
              variant="outlined"
              rounded="lg"
              multiple
              clearable
              hide-details="auto"
              @update:model-value="$emit('update:selectedPaymentTypes', $event)"
            />
          </VCol>
        </VRow>

        <VBtn
          v-if="hasFilters"
          class="mt-2"
          variant="text"
          color="default"
          rounded="lg"
          block
          prepend-icon="ri-filter-off-line"
          @click="$emit('clear')"
        >
          Limpiar filtros
        </VBtn>

        <VBtn
          class="mt-2"
          color="primary"
          rounded="lg"
          block
          @click="close"
        >
          Listo
        </VBtn>
      </div>
    </VCard>
  </VBottomSheet>
</template>

<script>
export default {
  name: 'AccountingMobileFiltersSheet',

  props: {
    modelValue: {
      type: Boolean,
      default: false,
    },
    dateRange: {
      type: [Array, null],
      default: null,
    },
    selectedMovementTypes: {
      type: Array,
      default: () => [],
    },
    selectedPaymentTypes: {
      type: Array,
      default: () => [],
    },
    movementTypes: {
      type: Array,
      required: true,
    },
    paymentTypes: {
      type: Array,
      required: true,
    },
  },

  emits: [
    'update:modelValue',
    'update:dateRange',
    'update:selectedMovementTypes',
    'update:selectedPaymentTypes',
    'clear',
  ],

  computed: {
    hasFilters() {
      const range = this.dateRange

      return Boolean(
        (Array.isArray(range) && range.length)
        || this.selectedMovementTypes.length
        || this.selectedPaymentTypes.length,
      )
    },
  },

  methods: {
    close() {
      this.$emit('update:modelValue', false)
    },
  },
}
</script>

<style scoped>
.accounting-filter-sheet {
  max-height: min(90vh, 720px);
  overflow-y: auto;
}
</style>
