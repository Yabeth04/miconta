<template>
  <div class="training-library">
    <div class="d-flex flex-wrap align-center justify-space-between gap-2 mb-3">
      <p class="text-body-2 text-medium-emphasis mb-0">
        Solo nombre y grupo. Series y nivel se ajustan en cada día.
      </p>
      <VBtn
        v-if="mdAndUp"
        color="primary"
        rounded="lg"
        size="small"
        prepend-icon="ri-add-line"
        @click="openLibraryExercise()"
      >
        Nuevo
      </VBtn>
    </div>

    <div class="training-library-search mb-3">
      <VTextField
        v-model="libraryQuery"
        class="training-library-search__field"
        label="Buscar"
        prepend-inner-icon="ri-search-line"
        variant="outlined"
        rounded="lg"
        hide-details
        clearable
        density="comfortable"
      />
      <VBtn
        v-if="!mdAndUp"
        color="primary"
        rounded="lg"
        class="training-library-search__btn"
        prepend-icon="ri-add-line"
        @click="openLibraryExercise()"
      >
        Nuevo
      </VBtn>
    </div>

    <div
      v-if="loading && !library.length"
      class="training-empty"
    >
      Cargando…
    </div>

    <div
      v-else-if="!filteredLibrary.length"
      class="training-empty"
    >
      {{ libraryQuery ? 'Nada coincide con la búsqueda.' : 'Todavía no hay ejercicios. Creá el primero.' }}
    </div>

    <VExpansionPanels
      v-else
      v-model="openedPanels"
      multiple
      variant="accordion"
      class="training-library-hoy"
    >
      <VExpansionPanel
        v-for="group in groupedLibrary"
        :key="group.name"
        :value="group.name"
        rounded="lg"
        class="training-library-hoy__panel"
      >
        <VExpansionPanelTitle class="training-library-hoy__title">
          <span class="d-inline-flex align-center gap-2">
            <MuscleGroupIcon
              v-if="hasMuscleIcon(group.name)"
              :group="group.name"
            />
            {{ group.name }}
            <span class="text-medium-emphasis text-caption text-none font-weight-regular">
              · {{ group.items.length }}
            </span>
          </span>
        </VExpansionPanelTitle>
        <VExpansionPanelText>
          <div
            v-for="item in group.items"
            :key="item.id"
            class="training-library-hoy__ex"
          >
            <p class="training-library-hoy__name mb-0 flex-grow-1">
              {{ item.name }}
            </p>
            <div class="d-flex gap-1">
              <VBtn
                icon
                variant="text"
                size="small"
                @click="openLibraryExercise(item)"
              >
                <VIcon icon="ri-pencil-line" />
              </VBtn>
              <VBtn
                icon
                variant="text"
                size="small"
                @click="askDeleteLibrary(item)"
              >
                <VIcon icon="ri-delete-bin-line" />
              </VBtn>
            </div>
          </div>
        </VExpansionPanelText>
      </VExpansionPanel>
    </VExpansionPanels>

    <VDialog
      v-model="libraryDialog"
      max-width="480"
    >
      <VCard rounded="lg">
        <VCardTitle class="text-h6">
          {{ libraryForm.id ? 'Editar en biblioteca' : 'Nuevo en biblioteca' }}
        </VCardTitle>
        <VCardText class="d-flex flex-column gap-4 pt-4">
          <VTextField
            v-model="libraryForm.name"
            label="Ejercicio"
            variant="outlined"
            rounded="lg"
            hide-details="auto"
            autofocus
          />
          <VSelect
            v-model="libraryForm.muscle_group"
            :items="muscleOptions"
            label="Grupo muscular"
            variant="outlined"
            rounded="lg"
            hide-details="auto"
            clearable
          >
            <template #selection="{ item }">
              <span class="d-inline-flex align-center gap-2">
                <MuscleGroupIcon
                  v-if="hasMuscleIcon(item.value)"
                  :group="item.value"
                />
                {{ item.title }}
              </span>
            </template>
            <template #item="{ props: itemProps, item }">
              <VListItem v-bind="itemProps">
                <template #prepend>
                  <MuscleGroupIcon
                    v-if="hasMuscleIcon(item.value)"
                    :group="item.value"
                    class="me-1"
                  />
                </template>
              </VListItem>
            </template>
          </VSelect>
        </VCardText>
        <VCardActions>
          <VSpacer />
          <VBtn
            variant="text"
            rounded="lg"
            @click="libraryDialog = false"
          >
            Cancelar
          </VBtn>
          <VBtn
            color="primary"
            rounded="lg"
            :loading="saving"
            @click="saveLibraryExercise"
          >
            Guardar
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <VDialog
      v-model="deleteDialog"
      max-width="400"
    >
      <VCard rounded="lg">
        <VCardTitle class="text-h6">
          Quitar de biblioteca
        </VCardTitle>
        <VCardText class="text-body-2 pt-2">
          ¿Sacar “{{ deleteTarget?.name }}” de la biblioteca? También se quita de los días donde esté.
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
            @click="confirmDeleteLibrary"
          >
            Eliminar
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </div>
</template>

<script>
import { axios } from '@/plugins/axios'
import MuscleGroupIcon from '@/views/pages/training/MuscleGroupIcon.vue'
import {
  hasMuscleIcon,
  groupExercises,
  emptyExercise,
  MUSCLE_OPTIONS,
} from '@/utils/trainingFormat'
import { useDisplay } from 'vuetify'

export default {
  name: 'TrainingBibliotecaTab',

  components: { MuscleGroupIcon },

  props: {
    loading: { type: Boolean, default: false },
    library: { type: Array, default: () => [] },
  },

  emits: ['refresh', 'error'],

  setup() {
    const { mdAndUp } = useDisplay()

    return { mdAndUp }
  },

  data() {
    return {
      libraryQuery: '',
      openedPanels: [],
      libraryDialog: false,
      libraryForm: emptyExercise(),
      deleteDialog: false,
      deleteTarget: null,
      deleting: false,
      saving: false,
      muscleOptions: MUSCLE_OPTIONS,
    }
  },

  computed: {
    filteredLibrary() {
      const q = String(this.libraryQuery || '').trim().toLowerCase()
      if (!q)
        return this.library

      return this.library.filter(item => {
        const hay = `${item.name} ${item.muscle_group || ''}`.toLowerCase()

        return hay.includes(q)
      })
    },

    groupedLibrary() {
      return groupExercises(this.filteredLibrary)
    },
  },

  methods: {
    hasMuscleIcon,

    openLibraryExercise(item = null) {
      this.libraryForm = item
        ? { ...emptyExercise(), ...item }
        : emptyExercise()
      this.libraryDialog = true
    },

    saveLibraryExercise() {
      if (this.saving)
        return

      const name = String(this.libraryForm.name || '').trim()
      if (!name) {
        this.$emit('error', 'Indicá el nombre del ejercicio.')

        return
      }

      this.saving = true
      const payload = {
        name,
        muscle_group: this.libraryForm.muscle_group,
      }
      const request = this.libraryForm.id
        ? axios.put(`/api/training/library/${this.libraryForm.id}`, payload)
        : axios.post('/api/training/library', payload)

      request
        .then(() => {
          this.libraryDialog = false
          this.$toast.success('Guardado', { timeout: 2000, closeOnClick: true })
          this.$emit('refresh')
        })
        .catch(error => {
          this.$emit('error', error.response?.data?.message || 'No se pudo guardar.')
        })
        .finally(() => {
          this.saving = false
        })
    },

    askDeleteLibrary(item) {
      this.deleteTarget = item
      this.deleteDialog = true
    },

    confirmDeleteLibrary() {
      if (!this.deleteTarget || this.deleting)
        return

      this.deleting = true
      axios.delete(`/api/training/library/${this.deleteTarget.id}`)
        .then(() => {
          this.deleteDialog = false
          this.deleteTarget = null
          this.$toast.success('Eliminado de biblioteca y rutina', { timeout: 2000, closeOnClick: true })
          this.$emit('refresh')
        })
        .catch(error => {
          this.deleteDialog = false
          const message = error.response?.data?.message || 'No se pudo eliminar.'
          this.$emit('error', message)
          this.$toast.error(message, { timeout: 3500, closeOnClick: true })
        })
        .finally(() => {
          this.deleting = false
        })
    },
  },
}
</script>

<style scoped>
.training-empty {
  text-align: center;
  padding: 2.5rem 1rem;
  color: rgba(var(--v-theme-on-surface), 0.6);
  font-size: 0.875rem;
}

.training-library-search {
  display: flex;
  align-items: center;
  gap: 0.65rem;
}

.training-library-search__field {
  flex: 1 1 auto;
  min-width: 0;
}

.training-library-search__btn {
  flex-shrink: 0;
}

.training-library-hoy {
  max-width: 40rem;
  margin-inline: auto;
}

.training-library-hoy__panel {
  background: transparent !important;
}

.training-library-hoy :deep(.v-expansion-panel) {
  background: transparent;
  box-shadow: none !important;
}

.training-library-hoy :deep(.v-expansion-panel-title) {
  min-height: 40px;
  padding-inline: 0.15rem;
  font-size: 0.6875rem;
  font-weight: 700;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: rgba(var(--v-theme-on-surface), 0.45);
}

.training-library-hoy :deep(.v-expansion-panel-text__wrapper) {
  padding-inline: 0.15rem;
  padding-bottom: 0.35rem;
}

.training-library-hoy__ex {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
  padding: 0.65rem 0;
  border-bottom: thin solid rgba(var(--v-border-color), var(--v-border-opacity));
}

.training-library-hoy__ex:last-child {
  border-bottom: 0;
}

.training-library-hoy__name {
  font-size: 0.9375rem;
  font-weight: 600;
}
</style>
