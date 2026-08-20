<template>
  <div>
    <StudySubjectProgressDialog
      v-model="progressDialog"
      :subject="activeSubject"
      :terms="terms"
      @saved="refreshPlan"
    />

    <StudyElectivePreferencesDialog
      v-model="preferencesDialog"
      :subject="activeSubject"
      @saved="refreshPlan"
    />

    <StudySubjectDialog
      v-model="subjectDialog"
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

    <template v-else>
      <!-- Celular: un solo accordion (varios paneles) -->
      <VExpansionPanels
        v-if="mdAndDown"
        v-model="openedTerms"
        multiple
        class="term-panels"
      >
        <VExpansionPanel
          v-for="term in terms"
          :key="term.number"
          :value="term.number"
          rounded="lg"
          elevation="1"
          class="mb-3"
        >
          <VExpansionPanelTitle
            class="font-weight-bold text-body-1 py-3"
            :class="termHeaderClass(term.color)"
          >
            {{ term.name }}
          </VExpansionPanelTitle>

          <VExpansionPanelText class="px-0">
            <StudyTermSubjectsList
              :term="term"
              :status-map="statusMap"
              show-status-icon
              @edit-progress="openProgress"
              @edit-preferences="openPreferences"
              @delete-subject="askDeleteSubject"
            />
          </VExpansionPanelText>
        </VExpansionPanel>
      </VExpansionPanels>

      <!-- Desktop: tarjetas fijas -->
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
          <VCard
            rounded="lg"
            class="h-100"
          >
            <VCardTitle
              class="font-weight-bold text-body-1 py-3 px-4"
              :class="termHeaderClass(term.color)"
            >
              {{ term.name }}
            </VCardTitle>

            <VDivider />

            <StudyTermSubjectsList
              :term="term"
              :status-map="statusMap"
              dense
              @edit-progress="openProgress"
              @edit-preferences="openPreferences"
              @delete-subject="askDeleteSubject"
            />
          </VCard>
        </VCol>
      </VRow>
    </template>
  </div>
</template>

<script>
import { axios } from '@/plugins/axios'
import StudyElectivePreferencesDialog from '@/views/pages/study-plan/StudyElectivePreferencesDialog.vue'
import StudySubjectDialog from '@/views/pages/study-plan/StudySubjectDialog.vue'
import StudySubjectProgressDialog from '@/views/pages/study-plan/StudySubjectProgressDialog.vue'
import StudyTermSubjectsList from '@/views/pages/study-plan/StudyTermSubjectsList.vue'
import { useDisplay } from 'vuetify'

export default {
  name: 'ModuleStudyPlan',

  components: {
    StudyElectivePreferencesDialog,
    StudySubjectDialog,
    StudySubjectProgressDialog,
    StudyTermSubjectsList,
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

    openProgress(subject) {
      this.activeSubject = subject
      this.progressDialog = true
    },

    openPreferences(subject) {
      this.activeSubject = subject
      this.preferencesDialog = true
    },

    openNewSubject(termNumber = null) {
      this.defaultTermNumber = termNumber
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
</style>
