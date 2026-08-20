<template>
  <VList
    class="py-1"
    :density="dense ? 'comfortable' : 'default'"
  >
    <template
      v-for="(subject, index) in term.subjects"
      :key="subject.id"
    >
      <VListItem class="px-4 subject-item">
        <VListItemTitle class="text-wrap text-body-2 pe-2">
          {{ subject.name }}
          <span
            v-if="subject.is_elective_slot && selectedElectiveName(subject)"
            class="text-medium-emphasis"
          >
            — {{ selectedElectiveName(subject) }}
          </span>
        </VListItemTitle>

        <VListItemSubtitle
          v-if="subject.note"
          class="text-caption mt-1"
        >
          {{ subject.note }}
        </VListItemSubtitle>

        <template #append>
          <div class="d-flex align-center gap-1 ms-1">
            <VTooltip
              v-if="subject.status && showStatusIcon"
              location="top"
            >
              <template #activator="{ props: tipProps }">
                <VIcon
                  v-bind="tipProps"
                  :icon="statusIcon(subject.status)"
                  :color="statusColor(subject.status)"
                  size="22"
                  :aria-label="statusLabel(subject.status)"
                />
              </template>
              <span>{{ statusLabel(subject.status) }}</span>
            </VTooltip>

            <VChip
              v-else-if="subject.status"
              size="small"
              variant="tonal"
              :color="statusColor(subject.status)"
              class="text-uppercase"
              style="font-size: 0.65rem; letter-spacing: 0.02em"
            >
              {{ statusLabel(subject.status) }}
            </VChip>

            <VMenu>
              <template #activator="{ props: menuProps }">
                <VBtn
                  v-bind="menuProps"
                  icon
                  :size="showStatusIcon ? 'small' : 'x-small'"
                  variant="text"
                  @click.stop
                >
                  <VIcon
                    icon="ri-more-2-fill"
                    size="18"
                  />
                </VBtn>
              </template>
              <VList density="compact">
                <VListItem
                  title="Editar"
                  prepend-icon="ri-pencil-line"
                  @click="$emit('edit-progress', subject)"
                />
                <VListItem
                  title="Eliminar"
                  prepend-icon="ri-delete-bin-line"
                  base-color="error"
                  @click="$emit('delete-subject', subject)"
                />
              </VList>
            </VMenu>
          </div>
        </template>
      </VListItem>

      <div
        v-if="subject.is_elective_slot && (subject.elective_options || []).length"
        class="px-4 pb-3"
      >
        <div
          class="elective-options rounded-lg pa-3"
          role="button"
          tabindex="0"
          @click="$emit('edit-preferences', subject)"
          @keydown.enter.prevent="$emit('edit-preferences', subject)"
        >
          <div class="d-flex align-center justify-space-between mb-2">
            <span class="text-caption text-medium-emphasis">
              Tocá aquí para cambiar preferencias
            </span>
            <VIcon
              icon="ri-pencil-line"
              size="14"
              class="text-medium-emphasis"
            />
          </div>
          <div
            v-for="opt in subject.elective_options"
            :key="opt.key"
            class="d-flex align-center justify-space-between gap-2 py-1"
          >
            <span
              class="text-body-2"
              :class="{ 'font-weight-medium': opt.key === subject.selected_elective_key }"
            >
              {{ opt.name }}
            </span>
            <VChip
              v-if="opt.preference_note"
              size="x-small"
              variant="tonal"
              :color="priorityColor(opt.preference_note)"
            >
              {{ priorityLabel(opt.preference_note) }}
            </VChip>
          </div>
        </div>
      </div>

      <VDivider
        v-if="index < term.subjects.length - 1"
        class="mx-4"
      />
    </template>

    <VListItem
      v-if="!term.subjects.length"
      class="text-medium-emphasis"
    >
      Sin materias aún.
    </VListItem>
  </VList>
</template>

<script>
export default {
  name: 'StudyTermSubjectsList',

  props: {
    term: { type: Object, required: true },
    showStatusIcon: { type: Boolean, default: false },
    dense: { type: Boolean, default: false },
    statusMap: { type: Object, required: true },
  },

  emits: ['edit-progress', 'edit-preferences', 'delete-subject'],

  methods: {
    statusLabel(status) {
      return this.statusMap[status]?.label || status
    },

    statusColor(status) {
      return this.statusMap[status]?.color || 'secondary'
    },

    statusIcon(status) {
      return this.statusMap[status]?.icon || 'ri-question-line'
    },

    selectedElectiveName(subject) {
      if (!subject.selected_elective_key)
        return null

      return (subject.elective_options || []).find(o => o.key === subject.selected_elective_key)?.name || null
    },

    priorityLabel(value) {
      const v = String(value || '').toLowerCase()
      if (v === 'alto' || v.includes('1') || v.includes('definitiva'))
        return 'Alto'
      if (v === 'medio' || v.includes('2'))
        return 'Medio'
      if (v === 'bajo' || v.includes('3'))
        return 'Bajo'

      return value
    },

    priorityColor(value) {
      const v = String(value || '').toLowerCase()
      if (v === 'alto' || v.includes('1') || v.includes('definitiva'))
        return 'error'
      if (v === 'medio' || v.includes('2'))
        return 'warning'
      if (v === 'bajo' || v.includes('3'))
        return 'info'

      return 'primary'
    },
  },
}
</script>

<style scoped>
.subject-item {
  min-height: 52px;
}
.elective-options {
  background: rgba(var(--v-theme-on-surface), 0.04);
  cursor: pointer;
}
.elective-options:hover {
  background: rgba(var(--v-theme-primary), 0.08);
}
</style>
