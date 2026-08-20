<template>
  <VDialog
    :model-value="modelValue"
    max-width="480"
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <VCard rounded="lg">
      <div class="d-flex align-center justify-space-between px-5 pt-5 pb-3">
        <span class="text-h6">
          {{ subject ? 'Editar materia' : 'Nueva materia' }}
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

      <div class="pa-5 d-flex flex-column gap-4">
        <VSelect
          v-model="termNumber"
          :items="termItems"
          item-title="title"
          item-value="value"
          label="Cuatrimestre"
          variant="outlined"
          rounded="lg"
          hide-details="auto"
        />

        <VTextField
          v-model="name"
          label="Nombre"
          variant="outlined"
          rounded="lg"
          hide-details="auto"
        />
      </div>

      <div class="d-flex justify-end gap-2 px-5 pb-5">
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
          :disabled="!canSave"
          @click="save"
        >
          Guardar
        </VBtn>
      </div>
    </VCard>
  </VDialog>
</template>

<script>
import { axios } from '@/plugins/axios'

export default {
  name: 'StudySubjectDialog',

  props: {
    modelValue: { type: Boolean, required: true },
    subject: { type: Object, default: null },
    terms: { type: Array, default: () => [] },
    defaultTermNumber: { type: [Number, String], default: null },
  },

  emits: ['update:modelValue', 'saved'],

  data() {
    return {
      termNumber: null,
      name: '',
      saving: false,
    }
  },

  computed: {
    termItems() {
      return this.terms.map(t => ({ title: t.name, value: t.number }))
    },
    canSave() {
      return this.termNumber && this.name.trim()
    },
  },

  watch: {
    modelValue: {
      immediate: true,
      handler(open) {
        if (!open)
          return

        this.termNumber = this.subject?.term_number
          ?? this.defaultTermNumber
          ?? this.terms[0]?.number
          ?? null
        this.name = this.subject?.name ?? ''
      },
    },
    subject() {
      if (!this.modelValue)
        return

      this.termNumber = this.subject?.term_number
        ?? this.defaultTermNumber
        ?? this.terms[0]?.number
        ?? null
      this.name = this.subject?.name ?? ''
    },
  },

  methods: {
    close() {
      this.$emit('update:modelValue', false)
    },

    save() {
      this.saving = true

      const payload = {
        term_number: this.termNumber,
        name: this.name.trim(),
      }

      const request = this.subject?.id
        ? axios.put(`/api/study-plan/subjects/${this.subject.id}`, payload)
        : axios.post('/api/study-plan/subjects', payload)

      request
        .then(() => {
          this.$emit('saved')
          this.close()
        })
        .finally(() => {
          this.saving = false
        })
    },
  },
}
</script>
