<template>
  <VDialog
    :model-value="modelValue"
    max-width="480"
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <VCard rounded="lg">
      <div class="d-flex align-center justify-space-between px-5 pt-5 pb-3">
        <span class="text-h6">
          Editar materia
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
        <VTextField
          v-model="name"
          label="Nombre"
          variant="outlined"
          rounded="lg"
          hide-details="auto"
        />

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

        <VSelect
          v-model="status"
          :items="statusItems"
          item-title="title"
          item-value="value"
          label="Estado"
          variant="outlined"
          rounded="lg"
          clearable
          hide-details="auto"
        />

        <VTextField
          v-model="note"
          label="Nota"
          placeholder="Ej. reprobé una vez"
          variant="outlined"
          rounded="lg"
          hide-details="auto"
          counter="255"
          maxlength="255"
        />

        <VSelect
          v-if="subject?.is_elective_slot"
          v-model="selectedKey"
          :items="optionItems"
          item-title="title"
          item-value="value"
          label="Electiva elegida"
          variant="outlined"
          rounded="lg"
          clearable
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
  name: 'StudySubjectProgressDialog',

  props: {
    modelValue: { type: Boolean, required: true },
    subject: { type: Object, default: null },
    terms: { type: Array, default: () => [] },
  },

  emits: ['update:modelValue', 'saved'],

  data() {
    return {
      statusItems: [
        { title: 'Matriculado', value: 'matriculado' },
        { title: 'En curso', value: 'en_curso' },
        { title: 'Aprobado', value: 'aprobado' },
        { title: 'Reprobado', value: 'reprobado' },
      ],
      name: '',
      termNumber: null,
      status: null,
      note: '',
      selectedKey: null,
      saving: false,
    }
  },

  computed: {
    termItems() {
      return this.terms.map(t => ({ title: t.name, value: t.number }))
    },
    optionItems() {
      return (this.subject?.elective_options || []).map(o => ({
        title: o.name,
        value: o.key,
      }))
    },
    canSave() {
      return this.termNumber && this.name.trim()
    },
  },

  watch: {
    modelValue: {
      immediate: true,
      handler(open) {
        if (!open || !this.subject)
          return

        this.hydrate()
      },
    },
    subject() {
      if (!this.modelValue || !this.subject)
        return

      this.hydrate()
    },
  },

  methods: {
    hydrate() {
      this.name = this.subject.name ?? ''
      this.termNumber = this.subject.term_number ?? null
      this.status = this.subject.status ?? null
      this.note = this.subject.note ?? ''
      this.selectedKey = this.subject.selected_elective_key ?? null
    },

    close() {
      this.$emit('update:modelValue', false)
    },

    save() {
      if (!this.subject || !this.canSave)
        return

      this.saving = true

      const prefs = {}
      for (const opt of this.subject.elective_options || []) {
        if (opt.preference_note)
          prefs[opt.key] = opt.preference_note
      }

      const payload = {
        name: this.name.trim(),
        term_number: this.termNumber,
        status: this.status || null,
        note: this.note?.trim() || null,
        selected_elective_key: this.selectedKey || null,
      }

      if (this.subject.is_elective_slot)
        payload.elective_preferences = prefs

      axios.put(`/api/study-plan/subjects/${this.subject.id}`, payload)
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
