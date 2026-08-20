<template>
  <VCombobox
    :model-value="normalizedValue"
    :items="items"
    label="Concepto"
    variant="outlined"
    rounded="lg"
    hide-details="auto"
    clearable
    @update:model-value="onUpdate"
  />
</template>

<script>
export default {
  name: 'AccountingConceptCombobox',

  props: {
    modelValue: {
      type: [String, Object, null],
      default: '',
    },
    concepts: {
      type: Array,
      default: () => [],
    },
  },

  emits: ['update:modelValue'],

  computed: {
    items() {
      return this.concepts
        .map(item => (typeof item === 'string' ? item : item?.name))
        .filter(Boolean)
    },
    normalizedValue() {
      const value = this.modelValue

      if (value && typeof value === 'object')
        return value.name ?? ''

      return value ?? ''
    },
  },

  methods: {
    onUpdate(value) {
      if (value && typeof value === 'object') {
        this.$emit('update:modelValue', value.name ?? '')

        return
      }

      this.$emit('update:modelValue', value ?? '')
    },
  },
}
</script>
