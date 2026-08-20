<template>
  <VDialog
    :model-value="modelValue"
    max-width="520"
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
        >
          <template #selection="{ item }">
            <div class="d-flex align-center gap-2">
              <VIcon
                :icon="item.raw.icon"
                :color="item.raw.color"
                size="20"
              />
              <span>{{ item.title }}</span>
            </div>
          </template>

          <template #item="{ props: itemProps, item }">
            <VListItem v-bind="itemProps">
              <template #prepend>
                <VIcon
                  :icon="item.raw.icon"
                  :color="item.raw.color"
                  size="20"
                  class="me-1"
                />
              </template>
            </VListItem>
          </template>
        </VSelect>

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

        <div v-if="subject?.is_elective_slot">
          <div class="text-body-2 font-weight-medium mb-2">
            Electiva elegida
          </div>
          <div class="d-flex flex-column gap-1">
            <button
              v-for="opt in subject.elective_options || []"
              :key="opt.key"
              type="button"
              class="elective-option-btn d-flex align-center gap-2 text-left pa-2 rounded-lg"
              :class="{ 'elective-option-btn--selected': selectedKey === opt.key }"
              @click="selectedKey = opt.key"
            >
              <VIcon
                :icon="selectedKey === opt.key ? 'ri-checkbox-circle-fill' : 'ri-checkbox-blank-circle-line'"
                :color="selectedKey === opt.key ? 'primary' : undefined"
                size="20"
              />
              <span class="text-body-2">{{ opt.name }}</span>
            </button>
          </div>
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
          :disabled="!canSave"
          @click="requestSave"
        >
          Guardar
        </VBtn>
      </div>
    </VCard>
  </VDialog>

  <VDialog
    v-model="confirmDialog"
    max-width="400"
  >
    <VCard rounded="lg">
      <VCardTitle class="text-h6">
        Cambiar electiva
      </VCardTitle>
      <VCardText class="text-body-2">
        Vas a cambiar la electiva seleccionada
        <template v-if="previousElectiveName">
          de <strong>{{ previousElectiveName }}</strong>
        </template>
        <template v-if="nextElectiveName">
          a <strong>{{ nextElectiveName }}</strong>
        </template>.
        ¿Confirmás el cambio?
      </VCardText>
      <VCardActions class="px-4 pb-4">
        <VSpacer />
        <VBtn
          variant="text"
          rounded="lg"
          @click="confirmDialog = false"
        >
          Cancelar
        </VBtn>
        <VBtn
          color="primary"
          variant="flat"
          rounded="lg"
          :loading="saving"
          @click="confirmAndSave"
        >
          Confirmar
        </VBtn>
      </VCardActions>
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
        { title: 'Matriculado', value: 'matriculado', icon: 'ri-bookmark-fill', color: 'info' },
        { title: 'En curso', value: 'en_curso', icon: 'ri-play-circle-fill', color: 'warning' },
        { title: 'Aprobado', value: 'aprobado', icon: 'ri-checkbox-circle-fill', color: 'success' },
        { title: 'Reprobado', value: 'reprobado', icon: 'ri-close-circle-fill', color: 'error' },
      ],
      name: '',
      termNumber: null,
      status: null,
      note: '',
      selectedKey: null,
      originalSelectedKey: null,
      saving: false,
      confirmDialog: false,
    }
  },

  computed: {
    termItems() {
      return this.terms.map(t => ({ title: t.name, value: t.number }))
    },
    canSave() {
      return this.termNumber && this.name.trim()
    },
    electiveChanged() {
      return (this.selectedKey || null) !== (this.originalSelectedKey || null)
    },
    previousElectiveName() {
      return this.optionName(this.originalSelectedKey)
    },
    nextElectiveName() {
      return this.optionName(this.selectedKey)
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
    optionName(key) {
      if (!key)
        return null

      return (this.subject?.elective_options || []).find(o => o.key === key)?.name || null
    },

    hydrate() {
      this.name = this.subject.name ?? ''
      this.termNumber = this.subject.term_number ?? null
      this.status = this.subject.status ?? null
      this.note = this.subject.note ?? ''
      this.selectedKey = this.subject.selected_elective_key ?? null
      this.originalSelectedKey = this.subject.selected_elective_key ?? null
      this.confirmDialog = false
    },

    close() {
      this.confirmDialog = false
      this.$emit('update:modelValue', false)
    },

    requestSave() {
      if (!this.subject || !this.canSave)
        return

      if (this.subject.is_elective_slot && this.electiveChanged) {
        this.confirmDialog = true

        return
      }

      this.save()
    },

    confirmAndSave() {
      this.confirmDialog = false
      this.save()
    },

    save() {
      if (!this.subject || !this.canSave)
        return

      this.saving = true

      axios.put(`/api/study-plan/subjects/${this.subject.id}`, {
        name: this.name.trim(),
        term_number: this.termNumber,
        status: this.status || null,
        note: this.note?.trim() || null,
        selected_elective_key: this.selectedKey || null,
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

<style scoped>
.elective-option-btn {
  border: 1px solid rgba(var(--v-theme-on-surface), 0.12);
  background: transparent;
  cursor: pointer;
  width: 100%;
}
.elective-option-btn:hover {
  background: rgba(var(--v-theme-primary), 0.06);
}
.elective-option-btn--selected {
  border-color: rgb(var(--v-theme-primary));
  background: rgba(var(--v-theme-primary), 0.08);
}
</style>
