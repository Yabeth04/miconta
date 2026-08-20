<template>
  <div>
    <StudySubjectProgressDialog
      v-model="progressDialog"
      :subject="activeSubject"
      @saved="refreshPlan"
    />

    <StudyElectivePreferencesDialog
      v-model="preferencesDialog"
      :subject="activeSubject"
      @saved="refreshPlan"
    />

    <StudySubjectDialog
      v-model="subjectDialog"
      :subject="editingSubject"
      :terms="terms"
      :default-term-number="defaultTermNumber"
      @saved="refreshPlan"
    />

    <VDialog
      v-model="deleteDialog"
      max-width="400"
    >
      <VCard rounded="lg">
        <VCardTitle class="text-h6">
          Eliminar materia
        </VCardTitle>
        <VCardText class="text-body-2">
          ¿Eliminar “{{ deleteSubject?.name }}”?
        </VCardText>
        <VCardActions class="px-4 pb-4">
          <VSpacer />
          <VBtn
            variant="text"
            rounded="lg"
            @click="deleteDialog = false"
          >
            Cancelar
          </VBtn>
          <VBtn
            color="error"
            variant="flat"
            rounded="lg"
            :loading="deleting"
            @click="confirmDelete"
          >
            Eliminar
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <div class="d-flex flex-wrap align-center justify-space-between gap-3 mb-4">
      <div>
        <h1 class="text-h4 font-weight-medium mb-1">
          Plan de estudios
        </h1>
        <p class="text-body-2 text-medium-emphasis mb-0">
          {{ summary.aprobadas }} de {{ summary.total }} materias aprobadas
        </p>
      </div>

      <VBtn
        color="primary"
        rounded="lg"
        prepend-icon="ri-add-line"
        @click="openNewSubject()"
      >
        Materia
      </VBtn>
    </div>

    <VAlert
      v-if="error"
      type="error"
      variant="tonal"
      rounded="lg"
      class="mb-4"
    >
      {{ error }}
    </VAlert>

    <div
      v-if="loading"
      class="text-center py-12 text-medium-emphasis"
    >
      Cargando plan…
    </div>

    <VRow
      v-else
      dense
    >
      <VCol
        v-for="term in terms"
        :key="term.number"
        cols="12"
        md="6"
      >
        <VExpansionPanels
          v-model="openedTerms"
          multiple
          class="term-panels"
        >
          <VExpansionPanel
            :value="term.number"
            rounded="lg"
            elevation="1"
          >
            <VExpansionPanelTitle
              class="font-weight-bold text-body-1 py-3"
              :class="termHeaderClass(term.color)"
            >
              {{ term.name }}
            </VExpansionPanelTitle>

            <VExpansionPanelText class="px-0">
              <VList
                class="py-1"
                :density="mdAndDown ? 'default' : 'comfortable'"
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
                          v-if="subject.status && mdAndDown"
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
                              :size="mdAndDown ? 'small' : 'x-small'"
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
                              title="Editar estado"
                              prepend-icon="ri-checkbox-circle-line"
                              @click="openProgress(subject)"
                            />
                            <VListItem
                              v-if="subject.is_elective_slot"
                              title="Editar preferencias"
                              prepend-icon="ri-price-tag-3-line"
                              @click="openPreferences(subject)"
                            />
                            <VListItem
                              title="Editar materia"
                              prepend-icon="ri-pencil-line"
                              @click="openEditSubject(subject)"
                            />
                            <VListItem
                              title="Eliminar"
                              prepend-icon="ri-delete-bin-line"
                              base-color="error"
                              @click="askDeleteSubject(subject)"
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
                      @click="openPreferences(subject)"
                      @keydown.enter.prevent="openPreferences(subject)"
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
            </VExpansionPanelText>
          </VExpansionPanel>
        </VExpansionPanels>
      </VCol>
    </VRow>
  </div>
</template>

<script>
import { axios } from '@/plugins/axios'
import StudyElectivePreferencesDialog from '@/views/pages/study-plan/StudyElectivePreferencesDialog.vue'
import StudySubjectDialog from '@/views/pages/study-plan/StudySubjectDialog.vue'
import StudySubjectProgressDialog from '@/views/pages/study-plan/StudySubjectProgressDialog.vue'
import { useDisplay } from 'vuetify'

export default {
  name: 'ModuleStudyPlan',

  components: {
    StudyElectivePreferencesDialog,
    StudySubjectDialog,
    StudySubjectProgressDialog,
  },

  setup() {
    const { mdAndDown } = useDisplay()

    return { mdAndDown }
  },

  data() {
    return {
      terms: [],
      openedTerms: [],
      summary: { total: 0, aprobadas: 0 },
      loading: true,
      error: '',
      progressDialog: false,
      preferencesDialog: false,
      activeSubject: null,
      subjectDialog: false,
      editingSubject: null,
      defaultTermNumber: null,
      deleteDialog: false,
      deleteSubject: null,
      deleting: false,
      statusMap: {
        matriculado: { label: 'Matriculado', color: 'info', icon: 'ri-bookmark-fill' },
        en_curso: { label: 'En curso', color: 'warning', icon: 'ri-play-circle-fill' },
        aprobado: { label: 'Aprobado', color: 'success', icon: 'ri-checkbox-circle-fill' },
        reprobado: { label: 'Reprobado', color: 'error', icon: 'ri-close-circle-fill' },
      },
    }
  },

  mounted() {
    this.loadPlan()
  },

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

    termHeaderClass(color) {
      const map = {
        info: 'term-header-info',
        warning: 'term-header-warning',
        success: 'term-header-success',
        secondary: 'term-header-secondary',
        purple: 'term-header-purple',
        teal: 'term-header-teal',
        olive: 'term-header-olive',
        error: 'term-header-error',
        primary: 'term-header-primary',
      }

      return map[color] || 'term-header-primary'
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

    openProgress(subject) {
      this.activeSubject = subject
      this.progressDialog = true
    },

    openPreferences(subject) {
      this.activeSubject = subject
      this.preferencesDialog = true
    },

    openNewSubject(termNumber = null) {
      this.editingSubject = null
      this.defaultTermNumber = termNumber
      this.subjectDialog = true
    },

    openEditSubject(subject) {
      this.editingSubject = subject
      this.defaultTermNumber = subject.term_number
      this.subjectDialog = true
    },

    askDeleteSubject(subject) {
      this.deleteSubject = subject
      this.deleteDialog = true
    },

    confirmDelete() {
      if (!this.deleteSubject)
        return

      this.deleting = true

      axios.delete(`/api/study-plan/subjects/${this.deleteSubject.id}`)
        .then(() => {
          this.deleteDialog = false

          return this.refreshPlan()
        })
        .finally(() => {
          this.deleting = false
        })
    },

    loadPlan({ silent = false } = {}) {
      if (!silent)
        this.loading = true

      this.error = ''

      return axios.get('/api/study-plan')
        .then(({ data }) => {
          this.terms = data.terms || []
          this.summary = data.summary || { total: 0, aprobadas: 0 }

          if (!silent || !this.openedTerms.length)
            this.openedTerms = this.terms.map(t => t.number)
          else {
            const numbers = this.terms.map(t => t.number)
            this.openedTerms = this.openedTerms.filter(n => numbers.includes(n))
          }
        })
        .catch(e => {
          this.error = e?.response?.data?.message || 'No se pudo cargar el plan de estudios.'
        })
        .finally(() => {
          if (!silent)
            this.loading = false
        })
    },

    refreshPlan() {
      return this.loadPlan({ silent: true })
    },
  },
}
</script>

<style scoped>
.term-panels :deep(.v-expansion-panel) {
  overflow: hidden;
}
.term-panels :deep(.v-expansion-panel-text__wrapper) {
  padding-inline: 0;
  padding-bottom: 0;
}
.term-header-info {
  background: rgba(var(--v-theme-info), 0.16);
}
.term-header-warning {
  background: rgba(var(--v-theme-warning), 0.18);
}
.term-header-success {
  background: rgba(var(--v-theme-success), 0.16);
}
.term-header-secondary {
  background: rgba(var(--v-theme-secondary), 0.16);
}
.term-header-primary {
  background: rgba(var(--v-theme-primary), 0.14);
}
.term-header-error {
  background: rgba(var(--v-theme-error), 0.14);
}
.term-header-purple {
  background: rgba(156, 39, 176, 0.14);
}
.term-header-teal {
  background: rgba(0, 150, 136, 0.14);
}
.term-header-olive {
  background: rgba(124, 142, 58, 0.18);
}
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
