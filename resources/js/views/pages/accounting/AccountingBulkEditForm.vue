<template>
  <div>
    <div
      class="d-flex align-center justify-space-between px-4 pt-4 pb-3"
      :class="{ 'px-5 pt-5': !mobile }"
    >
      <div>
        <span class="text-h6 d-block">
          Editar seleccionados
        </span>
        <span class="text-body-2 text-medium-emphasis">
          {{ count }} movimiento{{ count === 1 ? '' : 's' }}
        </span>
      </div>
      <VBtn
        icon
        variant="text"
        aria-label="Cerrar"
        @click="$emit('close')"
      >
        <VIcon icon="ri-close-line" />
      </VBtn>
    </div>

    <VDivider />

    <div
      class="pa-4"
      :class="{ 'pa-5': !mobile }"
    >
      <p class="text-body-2 text-medium-emphasis mb-4">
        Completá solo los campos que quieras cambiar en todos los seleccionados.
      </p>

      <AccountingConceptCombobox
        :model-value="conceptValue"
        :concepts="concepts"
        class="mb-3"
        @update:model-value="$emit('concept-change', $event)"
      />

      <VTextField
        :model-value="detailValue"
        type="text"
        label="Detalle"
        placeholder="Sin cambio"
        variant="outlined"
        rounded="lg"
        hide-details="auto"
        clearable
        class="mb-3"
        @update:model-value="$emit('detail-change', $event)"
      />

      <VSelect
        :model-value="movementTypeValue"
        label="Tipo de movimiento"
        :items="movementTypes"
        variant="outlined"
        rounded="lg"
        hide-details="auto"
        clearable
        placeholder="Sin cambio"
        class="mb-3"
        @update:model-value="$emit('movement-type-change', $event)"
      />

      <VSelect
        :model-value="paymentTypeValue"
        label="Tipo de pago"
        :items="paymentTypes"
        variant="outlined"
        rounded="lg"
        hide-details="auto"
        clearable
        placeholder="Sin cambio"
        @update:model-value="$emit('payment-type-change', $event)"
      />

      <div
        class="d-flex gap-2 mt-6"
        :class="mobile ? 'flex-column' : 'justify-end'"
      >
        <VBtn
          variant="text"
          rounded="lg"
          :block="mobile"
          @click="$emit('close')"
        >
          Cancelar
        </VBtn>
        <VBtn
          color="primary"
          rounded="lg"
          :block="mobile"
          :loading="saving"
          :disabled="!hasChanges"
          @click="$emit('save')"
        >
          Aplicar cambios
        </VBtn>
      </div>
    </div>
  </div>
</template>

<script>
import AccountingConceptCombobox from '@/views/pages/accounting/AccountingConceptCombobox.vue'

export default {
  name: 'AccountingBulkEditForm',

  components: {
    AccountingConceptCombobox,
  },

  props: {
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
    saving: {
      type: Boolean,
      default: false,
    },
    hasChanges: {
      type: Boolean,
      default: false,
    },
    mobile: {
      type: Boolean,
      default: false,
    },
    conceptValue: {
      type: String,
      default: '',
    },
    detailValue: {
      type: String,
      default: '',
    },
    movementTypeValue: {
      type: [String, null],
      default: null,
    },
    paymentTypeValue: {
      type: [String, null],
      default: null,
    },
  },

  emits: [
    'close',
    'save',
    'concept-change',
    'detail-change',
    'movement-type-change',
    'payment-type-change',
  ],
}
</script>
