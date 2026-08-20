<template>
  <VList
    class="py-1"
    :density="dense ? 'compact' : 'default'"
  >
    <template
      v-for="(subject, index) in term.subjects"
      :key="subject.id"
    >
      <VListItem
        class="px-4 subject-item"
        :class="{ 'subject-item--dense': dense }"
      >
        <VListItemTitle class="text-wrap text-body-2 pe-2">
          <span class="d-inline">{{ subject.name }}</span>
          <span
            v-if="subject.is_elective_slot && selectedElectiveName(subject)"
            class="text-medium-emphasis"
          >
            — {{ selectedElectiveName(subject) }}
          </span>

          <VChip
            v-if="subject.status && dense"
            size="x-small"
            variant="tonal"
            :color="statusColor(subject.status)"
            :prepend-icon="statusIcon(subject.status)"
            class="ms-2 text-uppercase"
            style="font-size: 0.65rem; letter-spacing: 0.02em; vertical-align: middle"
          >
            {{ statusLabel(subject.status) }}
          </VChip>
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
        class="px-4"
        :class="dense ? 'pb-2' : 'pb-3'"
      >
        <div class="elective-options rounded-lg pa-3">
          <div class="text-caption font-weight-medium text-primary mb-2">
            Opciones de electiva
          </div>
          <div
            v-for="opt in subject.elective_options"
            :key="opt.key"
            class="elective-option-row d-flex align-center gap-2 py-1"
            role="button"
            tabindex="0"
            @click="$emit('select-elective', subject, opt.key)"
            @keydown.enter.prevent="$emit('select-elective', subject, opt.key)"
          >
            <VIcon
              :icon="opt.key === subject.selected_elective_key ? 'ri-checkbox-circle-fill' : 'ri-checkbox-blank-circle-line'"
              :color="opt.key === subject.selected_elective_key ? 'primary' : undefined"
              size="18"
              :class="{ 'text-disabled': opt.key !== subject.selected_elective_key }"
            />
            <span
              class="text-body-2"
              :class="{ 'font-weight-medium': opt.key === subject.selected_elective_key }"
            >
              {{ opt.name }}
            </span>
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

  emits: ['edit-progress', 'delete-subject', 'select-elective'],

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
  },
}
</script>

<style scoped>
.subject-item {
  min-height: 52px;
}
.subject-item--dense {
  min-height: 40px;
}
.elective-options {
  border: 1px solid rgba(var(--v-theme-primary), 0.28);
  background: rgba(var(--v-theme-primary), 0.06);
}
.elective-option-row {
  cursor: pointer;
  border-radius: 8px;
  padding-inline: 4px;
}
.elective-option-row:hover {
  background: rgba(var(--v-theme-primary), 0.1);
}
</style>
