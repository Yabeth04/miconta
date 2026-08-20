<template>
  <VDialog
    :model-value="modelValue"
    max-width="480"
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <VCard rounded="lg">
      <div class="d-flex align-center justify-space-between px-5 pt-5 pb-3">
        <span class="text-h6">
          Prioridad / interés
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

      <div class="pa-5 d-flex flex-column gap-3">
        <p class="text-caption text-medium-emphasis mb-0">
          Alto equivale a tu opción 1.
        </p>

        <div
          v-for="opt in subject?.elective_options || []"
          :key="opt.key"
          class="d-flex align-center gap-3"
        >
          <span class="text-body-2 flex-grow-1">
            {{ opt.name }}
          </span>
          <VSelect
            v-model="preferenceNotes[opt.key]"
            :items="priorityItems"
            item-title="title"
            item-value="value"
            density="compact"
            variant="outlined"
            rounded="lg"
            hide-details
            clearable
            placeholder="—"
            style="max-width: 140px"
          />
        </div>
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
  name: 'StudyElectivePreferencesDialog',

  props: {
    modelValue: { type: Boolean, required: true },
    subject: { type: Object, default: null },
  },

  emits: ['update:modelValue', 'saved'],

  data() {
    return {
      priorityItems: [
        { title: 'Alto', value: 'alto' },
        { title: 'Medio', value: 'medio' },
        { title: 'Bajo', value: 'bajo' },
      ],
      preferenceNotes: {},
      saving: false,
    }
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
    normalizePriority(value) {
      if (!value)
        return null

      const v = String(value).trim().toLowerCase()
      if (['alto', 'medio', 'bajo'].includes(v))
        return v

      if (v.includes('1') || v.includes('definitiva'))
        return 'alto'
      if (v.includes('2'))
        return 'medio'
      if (v.includes('3'))
        return 'bajo'

      return null
    },

    hydrate() {
      const notes = {}
      for (const opt of this.subject.elective_options || [])
        notes[opt.key] = this.normalizePriority(opt.preference_note)

      this.preferenceNotes = notes
    },

    close() {
      this.$emit('update:modelValue', false)
    },

    save() {
      if (!this.subject)
        return

      this.saving = true

      const prefs = {}
      for (const [key, value] of Object.entries(this.preferenceNotes)) {
        if (value)
          prefs[key] = value
      }

      axios.put(`/api/study-plan/subjects/${this.subject.id}/progress`, {
        status: this.subject.status || null,
        note: this.subject.note || null,
        selected_elective_key: this.subject.selected_elective_key || null,
        elective_preferences: prefs,
      })
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
