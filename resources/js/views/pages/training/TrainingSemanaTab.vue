<template>
  <div>
    <p class="text-body-2 text-medium-emphasis mb-3">
      Arrastrá un grupo a otro día. Tocá un día para editarlo.
    </p>

    <div
      class="training-board mb-4"
      :class="{ 'training-board--busy': dragLocked }"
    >
      <div
        v-for="column in weekBoard"
        :key="column.id"
        class="training-board__day"
        :class="{
          'training-board__day--today': column.weekday === todayWeekday,
          'training-board__day--rest': column.is_rest,
          'training-board__day--selected': editPanelOpen && selectedDayId === column.id,
        }"
      >
        <button
          type="button"
          class="training-board__head"
          @click="openDay(days.find(d => d.id === column.id))"
        >
          <span class="training-board__label">
            {{ column.label }}
            <span
              v-if="column.weekday === todayWeekday"
              class="training-board__hoy"
            >hoy</span>
          </span>
          <span class="training-board__focus">
            {{ column.is_rest
              ? (column.focus || 'Descanso / correr')
              : (column.focus || 'Sin definir') }}
          </span>
        </button>

        <VueDraggable
          v-model="column.groups"
          :group="{ name: 'training-muscle-groups', pull: true, put: true }"
          :animation="180"
          handle=".training-board__handle"
          filter="input, textarea, select, .v-field, .v-btn, button:not(.training-board__handle)"
          :prevent-on-filter="false"
          class="training-board__drop"
          :disabled="dragLocked || column.is_rest"
          @add="onGroupMoved($event, column)"
          @update="onGroupReordered(column)"
        >
          <div
            v-for="group in column.groups"
            :key="`${column.id}-${group.key}`"
            class="training-board__group"
          >
            <button
              type="button"
              class="training-board__handle"
              aria-label="Mover grupo"
            >
              <VIcon
                icon="ri-draggable"
                size="18"
              />
            </button>
            <div class="min-w-0 d-flex align-center gap-2">
              <MuscleGroupIcon
                v-if="hasMuscleIcon(group.name)"
                :group="group.name"
                size="lg"
              />
              <div class="min-w-0">
                <p class="training-board__group-name mb-0">
                  {{ group.name }}
                </p>
                <p class="training-board__group-meta mb-0">
                  {{ group.count }} ejercicio{{ group.count === 1 ? '' : 's' }}
                </p>
              </div>
            </div>
          </div>
        </VueDraggable>

        <p
          v-if="!column.groups.length"
          class="training-board__empty"
        >
          {{ column.is_rest ? 'Descanso / correr' : 'Sin grupos' }}
        </p>
      </div>
    </div>

    <VDialog
      v-model="editPanelOpen"
      max-width="560"
      scrollable
    >
      <VCard
        v-if="selectedDay"
        rounded="lg"
        :loading="loading"
        class="training-day-editor"
      >
        <VCardTitle class="d-flex align-center justify-space-between gap-2 pe-2">
          <span>{{ selectedDay.label }}</span>
          <VBtn
            icon
            variant="text"
            size="small"
            aria-label="Cerrar"
            @click="editPanelOpen = false"
          >
            <VIcon icon="ri-close-line" />
          </VBtn>
        </VCardTitle>
        <VCardText class="d-flex flex-column gap-4 pt-2">
          <div class="d-flex flex-wrap align-center justify-space-between gap-2">
            <div class="min-w-0">
              <p class="text-body-2 text-medium-emphasis mb-1">
                Resumen automático
              </p>
              <p class="text-subtitle-1 font-weight-medium mb-0">
                {{ selectedDay.is_rest
                  ? (selectedDaySummary || 'Descanso / correr')
                  : (selectedDaySummary || 'Agregá ejercicios con grupo muscular') }}
              </p>
            </div>
            <VSwitch
              v-model="selectedDay.is_rest"
              color="primary"
              hide-details
              label="Descanso"
              density="compact"
              @update:model-value="saveDay"
            />
          </div>

          <div
            v-if="selectedDay.is_rest"
            class="d-flex flex-column gap-4"
          >
            <div class="d-flex align-center justify-space-between gap-2">
              <p class="text-subtitle-2 font-weight-medium mb-0">
                Actividades (km)
              </p>
              <VBtn
                size="small"
                color="primary"
                variant="tonal"
                rounded="lg"
                prepend-icon="ri-add-line"
                @click="openCardioExercise()"
              >
                Agregar
              </VBtn>
            </div>

            <div
              v-if="!selectedDay.exercises?.length"
              class="training-empty py-6"
            >
              Ej. Correr · 5 km. Vas midiendo distancia, sin series ni nivel.
            </div>

            <div
              v-else
              class="training-drag-list"
            >
              <div
                v-for="item in selectedDay.exercises"
                :key="item.id"
                class="training-exercise"
              >
                <div class="min-w-0 flex-grow-1">
                  <p class="font-weight-medium mb-0">
                    {{ item.name }}
                  </p>
                  <p class="text-body-2 text-medium-emphasis mb-0">
                    {{ formatLoad(item) }}
                  </p>
                </div>
                <div class="d-flex gap-1">
                  <VBtn
                    icon
                    variant="text"
                    size="small"
                    @click="openExerciseFromDay(selectedDay, item)"
                  >
                    <VIcon icon="ri-pencil-line" />
                  </VBtn>
                  <VBtn
                    icon
                    variant="text"
                    size="small"
                    @click="askDeleteExercise(item)"
                  >
                    <VIcon icon="ri-delete-bin-line" />
                  </VBtn>
                </div>
              </div>
            </div>
          </div>

          <template v-else>
            <div class="d-flex align-center justify-space-between gap-2">
              <p class="text-subtitle-2 font-weight-medium mb-0">
                Ejercicios
              </p>
              <VBtn
                size="small"
                color="primary"
                variant="tonal"
                rounded="lg"
                prepend-icon="ri-add-line"
                @click="openPickExercise"
              >
                Agregar
              </VBtn>
            </div>

            <div
              v-if="!selectedDay.exercises?.length"
              class="training-empty py-6"
            >
              Agregá ejercicios con reps, series y nivel. El grupo muscular arma el resumen del día (ej. Pecho + Tríceps).
            </div>

            <template v-else>
              <p class="text-caption text-medium-emphasis mb-0">
                Arrastrá el ícono para cambiar el orden.
              </p>
              <VueDraggable
                v-model="selectedDay.exercises"
                :animation="180"
                handle=".training-drag-handle"
                filter="input, textarea, select, .v-field, .v-btn, button:not(.training-drag-handle)"
                :prevent-on-filter="false"
                class="training-drag-list"
                :disabled="dragLocked"
                @update="saveExerciseOrder"
              >
                <div
                  v-for="item in selectedDay.exercises"
                  :key="item.id"
                  class="training-exercise"
                >
                  <button
                    type="button"
                    class="training-drag-handle"
                    aria-label="Reordenar"
                  >
                    <VIcon
                      icon="ri-draggable"
                      size="20"
                    />
                  </button>
                  <div class="min-w-0 flex-grow-1">
                    <p class="font-weight-medium mb-0">
                      {{ item.name }}
                    </p>
                    <p class="text-body-2 text-medium-emphasis mb-0">
                      <span v-if="item.muscle_group">{{ item.muscle_group }} · </span>
                      {{ item.reps }}×{{ item.sets }} · {{ formatLoad(item) }}
                    </p>
                  </div>
                  <div class="d-flex gap-1">
                    <VBtn
                      icon
                      variant="text"
                      size="small"
                      @click="openExerciseFromDay(selectedDay, item)"
                    >
                      <VIcon icon="ri-pencil-line" />
                    </VBtn>
                    <VBtn
                      icon
                      variant="text"
                      size="small"
                      @click="askDeleteExercise(item)"
                    >
                      <VIcon icon="ri-delete-bin-line" />
                    </VBtn>
                  </div>
                </div>
              </VueDraggable>
            </template>
          </template>
        </VCardText>
        <VCardActions class="px-4 pb-4">
          <VSpacer />
          <VBtn
            color="primary"
            rounded="lg"
            @click="editPanelOpen = false"
          >
            Listo
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <VDialog
      v-model="pickExerciseDialog"
      max-width="480"
    >
      <VCard rounded="lg">
        <VCardTitle class="text-h6">
          Agregar ejercicio
        </VCardTitle>
        <VCardText class="d-flex flex-column gap-3 pt-4">
          <VTextField
            v-model="pickQuery"
            label="Buscar en biblioteca"
            prepend-inner-icon="ri-search-line"
            variant="outlined"
            rounded="lg"
            hide-details
            clearable
            density="comfortable"
            autofocus
          />

          <VBtn
            color="primary"
            variant="tonal"
            rounded="lg"
            block
            prepend-icon="ri-add-line"
            @click="createExerciseFromPick"
          >
            Crear nuevo
          </VBtn>

          <div
            v-if="!pickFilteredLibrary.length"
            class="training-empty py-4"
          >
            {{ pickEmptyMessage }}
          </div>

          <div
            v-else
            class="training-drag-list"
            style="max-height: 320px; overflow: auto;"
          >
            <button
              v-for="item in pickFilteredLibrary"
              :key="`pick-${item.id}`"
              type="button"
              class="training-exercise training-exercise--pick"
              :disabled="saving"
              @click="attachFromLibrary(item)"
            >
              <div class="min-w-0 flex-grow-1 text-start">
                <p class="font-weight-medium mb-0">
                  {{ item.name }}
                </p>
                <p
                  v-if="item.muscle_group"
                  class="text-body-2 text-medium-emphasis mb-0"
                >
                  {{ item.muscle_group }}
                </p>
              </div>
              <VIcon
                icon="ri-add-circle-line"
                size="22"
              />
            </button>
          </div>
        </VCardText>
        <VCardActions>
          <VSpacer />
          <VBtn
            variant="text"
            rounded="lg"
            @click="pickExerciseDialog = false"
          >
            Cancelar
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <VDialog
      v-model="exerciseDialog"
      max-width="480"
    >
      <VCard rounded="lg">
        <VCardTitle class="text-h6">
          {{ exerciseDialogTitle }}
        </VCardTitle>
        <VCardText class="d-flex flex-column gap-4 pt-4">
          <VTextField
            v-model="exerciseForm.name"
            :label="isCardioForm ? 'Actividad' : 'Ejercicio'"
            variant="outlined"
            rounded="lg"
            hide-details="auto"
            autofocus
          />
          <VSelect
            v-if="!isCardioForm"
            v-model="exerciseForm.muscle_group"
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
          <template v-if="isCardioForm">
            <VTextField
              v-model.number="exerciseForm.load_value"
              type="number"
              inputmode="decimal"
              label="Kilómetros"
              variant="outlined"
              rounded="lg"
              hide-details="auto"
            />
            <VTextField
              v-model="exerciseForm.notes"
              label="Nota (opcional)"
              variant="outlined"
              rounded="lg"
              hide-details="auto"
            />
          </template>
          <template v-else>
            <div class="d-flex gap-3">
              <VTextField
                v-model.number="exerciseForm.reps"
                type="number"
                inputmode="numeric"
                label="Reps"
                variant="outlined"
                rounded="lg"
                hide-details="auto"
              />
              <VTextField
                v-model.number="exerciseForm.sets"
                type="number"
                inputmode="numeric"
                label="Series"
                variant="outlined"
                rounded="lg"
                hide-details="auto"
              />
            </div>
            <div class="d-flex gap-3">
              <VSelect
                v-model="exerciseForm.load_type"
                :items="loadTypeOptions"
                item-title="title"
                item-value="value"
                label="Carga"
                variant="outlined"
                rounded="lg"
                hide-details="auto"
              />
              <VTextField
                v-if="exerciseForm.load_type !== 'bodyweight'"
                v-model.number="exerciseForm.load_value"
                type="number"
                inputmode="decimal"
                :label="exerciseForm.load_type === 'level' ? 'Nivel' : 'Kg'"
                variant="outlined"
                rounded="lg"
                hide-details="auto"
              />
            </div>
            <VTextField
              v-model="exerciseForm.notes"
              label="Nota (opcional)"
              variant="outlined"
              rounded="lg"
              hide-details="auto"
            />
          </template>
        </VCardText>
        <VCardActions>
          <VSpacer />
          <VBtn
            variant="text"
            rounded="lg"
            @click="exerciseDialog = false"
          >
            Cancelar
          </VBtn>
          <VBtn
            color="primary"
            rounded="lg"
            :loading="saving"
            @click="saveExercise"
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
          Eliminar ejercicio
        </VCardTitle>
        <VCardText class="text-body-2 pt-2">
          ¿Eliminar “{{ deleteTarget?.name }}” de este día?
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
            @click="confirmDeleteExercise"
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
import { VueDraggable } from 'vue-draggable-plus'
import MuscleGroupIcon from '@/views/pages/training/MuscleGroupIcon.vue'
import {
  formatLoad,
  hasMuscleIcon,
  emptyExercise,
  focusFromGroups,
  groupsFromDay,
  MUSCLE_OPTIONS,
} from '@/utils/trainingFormat'

export default {
  name: 'TrainingSemanaTab',

  components: { VueDraggable, MuscleGroupIcon },

  props: {
    loading: { type: Boolean, default: false },
    days: { type: Array, default: () => [] },
    library: { type: Array, default: () => [] },
    todayWeekday: { type: Number, default: null },
  },

  emits: ['refresh', 'error'],

  data() {
    return {
      weekBoard: [],
      movingGroup: false,
      editPanelOpen: false,
      selectedDayId: null,
      pickExerciseDialog: false,
      pickQuery: '',
      exerciseDialog: false,
      exerciseForm: emptyExercise(),
      deleteDialog: false,
      deleteTarget: null,
      deleting: false,
      saving: false,
      muscleOptions: MUSCLE_OPTIONS,
      loadTypeOptions: [
        { title: 'Nivel (máquina)', value: 'level' },
        { title: 'Kilogramos', value: 'kg' },
        { title: 'Peso corporal', value: 'bodyweight' },
      ],
    }
  },

  computed: {
    selectedDay() {
      return this.days.find(day => day.id === this.selectedDayId) || null
    },

    selectedDaySummary() {
      if (!this.selectedDay)
        return null

      if (this.selectedDay.is_rest) {
        const names = (this.selectedDay.exercises || []).map(item => item.name).filter(Boolean)

        return names.length ? names.join(' + ') : null
      }

      return focusFromGroups(groupsFromDay(this.selectedDay))
    },

    dragLocked() {
      return this.movingGroup
        || this.editPanelOpen
        || this.pickExerciseDialog
        || this.exerciseDialog
        || this.deleteDialog
    },

    isCardioForm() {
      return this.exerciseForm.load_type === 'km' || Boolean(this.selectedDay?.is_rest)
    },

    exerciseDialogTitle() {
      if (this.isCardioForm)
        return this.exerciseForm.id ? 'Editar actividad' : 'Nueva actividad'

      return this.exerciseForm.id ? 'Editar ejercicio' : 'Nuevo ejercicio'
    },

    pickFilteredLibrary() {
      const usedIds = new Set(
        (this.selectedDay?.exercises || [])
          .map(item => item.library_exercise_id)
          .filter(Boolean),
      )
      const usedNames = new Set(
        (this.selectedDay?.exercises || [])
          .map(item => String(item.name || '').trim().toLowerCase())
          .filter(Boolean),
      )

      const available = this.library.filter(item => {
        if (usedIds.has(item.id))
          return false

        const name = String(item.name || '').trim().toLowerCase()

        return !name || !usedNames.has(name)
      })

      const q = String(this.pickQuery || '').trim().toLowerCase()
      if (!q)
        return available

      return available.filter(item => {
        const hay = `${item.name} ${item.muscle_group || ''}`.toLowerCase()

        return hay.includes(q)
      })
    },

    pickEmptyMessage() {
      if (!this.library.length)
        return 'Biblioteca vacía: creá el primero.'

      const q = String(this.pickQuery || '').trim()
      if (q)
        return 'Nada coincide.'

      const dayHasExercises = Boolean(this.selectedDay?.exercises?.length)
      if (dayHasExercises && this.pickFilteredLibrary.length === 0)
        return 'Ya están todos en este día.'

      return 'Nada coincide.'
    },
  },

  watch: {
    days: {
      immediate: true,
      handler() {
        this.rebuildWeekBoard()
        if (!this.selectedDayId) {
          const fallbackId = this.days.find(day => day.weekday === this.todayWeekday)?.id
            || this.days[0]?.id
            || null
          this.selectedDayId = fallbackId
        }
      },
    },
  },

  methods: {
    formatLoad,
    hasMuscleIcon,

    openDay(day) {
      if (!day)
        return

      this.selectedDayId = day.id
      this.editPanelOpen = true
    },

    rebuildWeekBoard() {
      this.weekBoard = this.days.map(day => {
        const groups = groupsFromDay(day)

        return {
          id: day.id,
          label: day.label,
          weekday: day.weekday,
          is_rest: day.is_rest,
          focus: day.is_rest
            ? ((day.exercises || []).map(item => item.name).filter(Boolean).join(' + ') || null)
            : focusFromGroups(groups),
          groups,
        }
      })
    },

    onGroupMoved(event, targetColumn) {
      const group = targetColumn.groups[event.newIndex]
      if (!group || this.movingGroup)
        return

      if (group.source_day_id === targetColumn.id) {
        this.rebuildWeekBoard()

        return
      }

      this.movingGroup = true
      axios.post('/api/training/move-group', {
        source_day_id: group.source_day_id,
        target_day_id: targetColumn.id,
        muscle_group: group.muscle_group,
      })
        .then(() => this.$emit('refresh'))
        .catch(error => {
          this.$emit('error', error.response?.data?.message || 'No se pudo mover el grupo.')
          this.rebuildWeekBoard()
        })
        .finally(() => {
          this.movingGroup = false
        })
    },

    onGroupReordered(column) {
      if (this.movingGroup || !column?.groups?.length)
        return

      if (column.groups.some(group => group.source_day_id !== column.id))
        return

      this.movingGroup = true
      axios.put(`/api/training/days/${column.id}/groups/reorder`, {
        groups: column.groups.map(group => group.muscle_group),
      })
        .then(() => this.$emit('refresh'))
        .catch(error => {
          this.$emit('error', error.response?.data?.message || 'No se pudo guardar el orden.')
          this.rebuildWeekBoard()
        })
        .finally(() => {
          this.movingGroup = false
        })
    },

    saveDay() {
      if (!this.selectedDay)
        return

      axios.put(`/api/training/days/${this.selectedDay.id}`, {
        focus: this.selectedDay.focus,
        is_rest: this.selectedDay.is_rest,
      }).then(() => {
        this.rebuildWeekBoard()
      }).catch(error => {
        this.$emit('error', error.response?.data?.message || 'No se pudo guardar el día.')
      })
    },

    openCardioExercise(item = null) {
      this.exerciseForm = item
        ? { ...emptyExercise(), ...item, load_type: 'km', muscle_group: item.muscle_group || 'Cardio' }
        : {
            ...emptyExercise(),
            name: 'Correr',
            muscle_group: 'Cardio',
            sets: 1,
            reps: 1,
            load_type: 'km',
            load_value: 5,
          }
      this.exerciseDialog = true
    },

    openPickExercise() {
      this.pickQuery = ''
      this.pickExerciseDialog = true
    },

    createExerciseFromPick() {
      this.pickExerciseDialog = false
      this.openExercise()
    },

    attachFromLibrary(item) {
      if (!item?.id)
        return

      this.pickExerciseDialog = false
      this.exerciseForm = {
        ...emptyExercise(),
        library_exercise_id: item.id,
        name: item.name,
        muscle_group: item.muscle_group || null,
      }
      this.exerciseDialog = true
    },

    openExerciseFromDay(day, item = null) {
      if (day?.id)
        this.selectedDayId = day.id

      if (day?.is_rest || item?.load_type === 'km') {
        this.openCardioExercise(item)

        return
      }

      this.openExercise(item)
    },

    openExercise(item = null) {
      if (this.selectedDay?.is_rest && !item) {
        this.openCardioExercise()

        return
      }

      this.exerciseForm = item
        ? { ...emptyExercise(), ...item }
        : emptyExercise()
      this.exerciseDialog = true
    },

    saveExercise() {
      if (this.saving)
        return

      if (!this.exerciseForm.id && !this.selectedDay)
        return

      const name = String(this.exerciseForm.name || '').trim()
      if (!name) {
        this.$emit('error', this.isCardioForm ? 'Indicá el nombre de la actividad.' : 'Indicá el nombre del ejercicio.')

        return
      }

      this.saving = true

      const dayPayload = this.isCardioForm
        ? {
            name,
            muscle_group: 'Cardio',
            sets: 1,
            reps: 1,
            load_type: 'km',
            load_value: this.exerciseForm.load_value,
            notes: this.exerciseForm.notes,
          }
        : {
            name,
            muscle_group: this.exerciseForm.muscle_group,
            sets: this.exerciseForm.sets,
            reps: this.exerciseForm.reps,
            load_type: this.exerciseForm.load_type,
            load_value: this.exerciseForm.load_type === 'bodyweight' ? null : this.exerciseForm.load_value,
            notes: this.exerciseForm.notes,
          }

      let request
      if (this.exerciseForm.id) {
        request = axios.put(`/api/training/exercises/${this.exerciseForm.id}`, dayPayload)
      }
      else if (this.exerciseForm.library_exercise_id) {
        request = axios.post(`/api/training/days/${this.selectedDay.id}/exercises/attach`, {
          library_exercise_id: this.exerciseForm.library_exercise_id,
          sets: dayPayload.sets,
          reps: dayPayload.reps,
          load_type: dayPayload.load_type,
          load_value: dayPayload.load_value,
          notes: dayPayload.notes,
        })
      }
      else {
        request = axios.post(`/api/training/days/${this.selectedDay.id}/exercises`, dayPayload)
      }

      request
        .then(() => {
          this.exerciseDialog = false
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

    askDeleteExercise(item) {
      this.deleteTarget = item
      this.deleteDialog = true
    },

    confirmDeleteExercise() {
      if (!this.deleteTarget || this.deleting)
        return

      this.deleting = true
      axios.delete(`/api/training/exercises/${this.deleteTarget.id}`)
        .then(() => {
          this.deleteDialog = false
          this.deleteTarget = null
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

    saveExerciseOrder() {
      if (!this.selectedDay?.exercises?.length)
        return

      const exerciseIds = this.selectedDay.exercises.map(item => item.id)

      axios.put(`/api/training/days/${this.selectedDay.id}/exercises/reorder`, {
        exercise_ids: exerciseIds,
      }).catch(error => {
        this.$emit('error', error.response?.data?.message || 'No se pudo guardar el orden.')
        this.$emit('refresh')
      })
    },
  },
}
</script>

<style scoped>
.training-board {
  display: grid;
  gap: 0.75rem;
}

.training-board--busy {
  opacity: 0.7;
  pointer-events: none;
}

.training-board__day {
  border: thin solid rgba(var(--v-border-color), var(--v-border-opacity));
  border-radius: 14px;
  background: rgb(var(--v-theme-surface));
  padding: 0.75rem;
  min-height: 5.5rem;
}

.training-board__day--today {
  border-color: color-mix(in srgb, rgb(var(--v-theme-primary)) 55%, rgba(var(--v-border-color), var(--v-border-opacity)));
}

.training-board__day--selected {
  border-color: rgb(var(--v-theme-primary));
  box-shadow: 0 0 0 1px rgb(var(--v-theme-primary));
  background: color-mix(in srgb, rgb(var(--v-theme-primary)) 8%, rgb(var(--v-theme-surface)));
}

.training-board__day--rest {
  opacity: 0.72;
}

.training-board__head {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
  width: 100%;
  border: 0;
  background: transparent;
  color: inherit;
  text-align: left;
  padding: 0 0 0.55rem;
  margin: 0 0 0.35rem;
  cursor: pointer;
  border-bottom: thin solid rgba(var(--v-border-color), var(--v-border-opacity));
}

.training-board__label {
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 0.4rem;
}

.training-board__hoy {
  font-size: 0.65rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  color: rgb(var(--v-theme-primary));
}

.training-board__focus {
  font-size: 0.8125rem;
  color: rgba(var(--v-theme-on-surface), 0.65);
}

.training-board__drop {
  min-height: 2.5rem;
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.training-board__group {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  padding: 0.55rem 0.6rem;
  border-radius: 10px;
  background: color-mix(in srgb, rgb(var(--v-theme-primary)) 10%, rgb(var(--v-theme-surface)));
  border: thin solid color-mix(in srgb, rgb(var(--v-theme-primary)) 22%, rgba(var(--v-border-color), var(--v-border-opacity)));
}

.training-board__handle {
  display: inline-flex;
  border: 0;
  background: transparent;
  color: rgba(var(--v-theme-on-surface), 0.45);
  cursor: grab;
  padding: 0.15rem;
  touch-action: none;
}

.training-board__handle:active {
  cursor: grabbing;
}

.training-board__group-name {
  font-weight: 650;
  font-size: 0.9rem;
}

.training-board__group-meta {
  font-size: 0.75rem;
  color: rgba(var(--v-theme-on-surface), 0.55);
}

.training-board__empty {
  margin: 0.35rem 0 0;
  font-size: 0.75rem;
  color: rgba(var(--v-theme-on-surface), 0.45);
}

@media (min-width: 900px) {
  .training-board {
    grid-template-columns: repeat(7, minmax(0, 1fr));
    align-items: start;
  }
}

@media (min-width: 600px) and (max-width: 899px) {
  .training-board {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

.training-empty {
  text-align: center;
  padding: 2.5rem 1rem;
  color: rgba(var(--v-theme-on-surface), 0.6);
  font-size: 0.875rem;
}

.training-exercise {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
  padding: 0.75rem 0.75rem;
  border: thin solid rgba(var(--v-border-color), var(--v-border-opacity));
  border-radius: 12px;
  margin-bottom: 0.5rem;
  background: rgb(var(--v-theme-surface));
}

.training-exercise--pick {
  width: 100%;
  cursor: pointer;
  text-align: left;
  font: inherit;
  color: inherit;
}

.training-exercise--pick:hover {
  border-color: rgba(var(--v-theme-primary), 0.45);
}

.training-exercise--pick:disabled {
  opacity: 0.55;
  cursor: wait;
}

.training-drag-handle {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: 0;
  background: transparent;
  color: rgba(var(--v-theme-on-surface), 0.45);
  cursor: grab;
  padding: 0.25rem;
  touch-action: none;
}

.training-drag-handle:active {
  cursor: grabbing;
}

.training-drag-list .sortable-ghost {
  opacity: 0.45;
}
</style>
