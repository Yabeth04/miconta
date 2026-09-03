<template>
  <div class="training-page">
    <div class="d-flex flex-wrap align-center justify-space-between gap-3 mb-3">
      <div class="d-flex align-center gap-2">
        <h1 class="text-h4 font-weight-medium mb-0">
          Entrenamiento
        </h1>
        <VBtn
          icon
          variant="text"
          size="small"
          aria-label="Ayuda"
          @click="helpDialog = true"
        >
          <VIcon
            icon="ri-question-line"
            size="22"
          />
        </VBtn>
      </div>
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

    <VTabs
      v-model="activeTab"
      class="mb-4 training-tabs"
      color="primary"
      density="comfortable"
      show-arrows
    >
      <VTab value="hoy">
        Hoy
      </VTab>
      <VTab value="semana">
        Semana
      </VTab>
      <VTab value="historial">
        Historial
      </VTab>
    </VTabs>

    <VWindow
      v-model="activeTab"
      :touch="false"
    >
      <!-- Hoy: lo que entrenás AHORA -->
      <VWindowItem value="hoy">
        <div
          v-if="loading && !activeDay"
          class="training-empty"
        >
          Cargando…
        </div>

        <template v-else-if="activeDay">
          <header class="training-hoy__header mb-3">
            <p class="training-hoy__eyebrow mb-1">
              Hoy · {{ calendarDayLabel }}
            </p>
            <h2 class="training-hoy__focus mb-0">
              {{ activeDay.is_rest
                ? 'Descanso / correr'
                : (activeDay.focus || 'Todavía sin plan') }}
            </h2>
            <p
              v-if="isSwappedRoutine"
              class="training-hoy__swap-note mb-0 mt-2"
            >
              Hoy toca {{ todayDay?.focus || todayDay?.label }}, pero estás haciendo la de {{ activeDay.label }}.
            </p>
          </header>

          <div class="training-hoy__pick mb-4">
            <p class="training-hoy__pick-label mb-2">
              ¿Qué rutina hago?
            </p>
            <div class="training-day-chips">
              <button
                v-for="day in days"
                :key="`hoy-${day.id}`"
                type="button"
                class="training-day-chip"
                :class="{
                  'training-day-chip--active': activeDayId === day.id,
                  'training-day-chip--today': day.weekday === todayWeekday,
                }"
                @click="activeDayId = day.id"
              >
                {{ shortDay(day.label) }}
              </button>
            </div>
            <VBtn
              v-if="isSwappedRoutine"
              size="small"
              variant="text"
              rounded="lg"
              class="mt-1 px-1"
              @click="resetToTodayRoutine"
            >
              Volver a la de hoy
            </VBtn>
          </div>

          <div
            v-if="activeDay.is_rest"
            class="training-hoy__rest"
          >
            <p class="mb-4">
              Esta rutina es descanso. Si salís a correr, anotá tiempo y calorías al terminar.
              O elegí otra rutina arriba.
            </p>
            <VBtn
              color="primary"
              rounded="lg"
              block
              prepend-icon="ri-run-line"
              @click="openSessionFromDay(activeDay)"
            >
              Anotar cardio
            </VBtn>
          </div>

          <template v-else>
            <div
              v-if="!activeDay.exercises?.length"
              class="training-hoy__empty"
            >
              <p class="mb-3">
                No hay ejercicios armados en esta rutina.
              </p>
              <VBtn
                color="primary"
                variant="tonal"
                rounded="lg"
                @click="goEditDay(activeDay)"
              >
                Armar esta rutina
              </VBtn>
            </div>

            <div
              v-else
              class="training-hoy__list"
            >
              <div
                v-for="group in activeGroupedExercises"
                :key="group.name"
                class="training-hoy__group"
              >
                <p class="training-hoy__group-title">
                  {{ group.name }}
                </p>
                <div
                  v-for="item in group.items"
                  :key="item.id"
                  class="training-hoy__ex"
                >
                  <p class="training-hoy__ex-name mb-1">
                    {{ item.name }}
                  </p>
                  <p class="training-hoy__ex-rx mb-0">
                    {{ item.reps }}×{{ item.sets }}
                    <span class="training-hoy__ex-dot">·</span>
                    {{ formatLoad(item) }}
                  </p>
                  <p
                    v-if="item.notes"
                    class="training-hoy__ex-note mb-0"
                  >
                    {{ item.notes }}
                  </p>
                </div>
              </div>
            </div>

            <div class="training-hoy__actions">
              <VBtn
                v-if="activeDay.exercises?.length"
                color="primary"
                rounded="lg"
                size="large"
                block
                prepend-icon="ri-checkbox-circle-line"
                @click="openSessionFromDay(activeDay)"
              >
                Terminé · registrar
              </VBtn>
              <VBtn
                variant="text"
                rounded="lg"
                block
                class="mt-1"
                @click="goEditDay(activeDay)"
              >
                Editar estos ejercicios
              </VBtn>
            </div>
          </template>
        </template>
      </VWindowItem>

      <!-- Semana: plan + editar cualquier día -->
      <VWindowItem value="semana">
        <VCard
          rounded="lg"
          class="mb-4"
          :loading="loading"
        >
          <div class="training-semana">
            <button
              v-for="day in days"
              :key="day.id"
              type="button"
              class="training-semana__row"
              :class="{
                'training-semana__row--today': day.weekday === todayWeekday,
                'training-semana__row--open': selectedDayId === day.id && editPanelOpen,
              }"
              @click="toggleEditDay(day)"
            >
              <span class="training-semana__day">
                {{ day.label }}
                <span
                  v-if="day.weekday === todayWeekday"
                  class="training-semana__hoy"
                >hoy</span>
              </span>
              <span class="training-semana__summary">
                {{ day.is_rest ? 'Descanso / correr' : (day.focus || 'Sin definir') }}
              </span>
              <VIcon
                :icon="selectedDayId === day.id && editPanelOpen ? 'ri-arrow-up-s-line' : 'ri-arrow-down-s-line'"
                size="20"
                class="training-semana__chevron"
              />
            </button>
          </div>
        </VCard>

        <VCard
          v-if="editPanelOpen && selectedDay"
          rounded="lg"
          :loading="loading"
        >
          <VCardText>
            <div class="d-flex flex-wrap align-center justify-space-between gap-2 mb-3">
              <p class="text-subtitle-1 font-weight-medium mb-0">
                {{ selectedDay.label }}
              </p>
              <VSwitch
                v-model="selectedDay.is_rest"
                color="primary"
                hide-details
                label="Descanso"
                density="compact"
                @update:model-value="saveDay"
              />
            </div>

            <VTextField
              v-if="!selectedDay.is_rest"
              v-model="selectedDay.focus"
              label="Resumen (ej. Pecho + hombros + tríceps)"
              variant="outlined"
              rounded="lg"
              hide-details="auto"
              class="mb-4"
              @blur="saveDay"
            />

            <div
              v-if="selectedDay.is_rest"
              class="training-empty py-6"
            >
              Día de descanso / correr.
            </div>

            <template v-else>
              <div class="d-flex align-center justify-space-between gap-2 mb-3">
                <p class="text-subtitle-2 font-weight-medium mb-0">
                  Ejercicios
                </p>
                <VBtn
                  size="small"
                  color="primary"
                  variant="tonal"
                  rounded="lg"
                  prepend-icon="ri-add-line"
                  @click="openExercise()"
                >
                  Agregar
                </VBtn>
              </div>

              <div
                v-if="!selectedDay.exercises?.length"
                class="training-empty py-6"
              >
                Agregá ejercicios con reps, series y nivel.
              </div>

              <div
                v-for="group in groupedExercises"
                :key="group.name"
                class="mb-3"
              >
                <p class="training-group__title">
                  {{ group.name }}
                </p>
                <div
                  v-for="item in group.items"
                  :key="item.id"
                  class="training-exercise"
                >
                  <div class="min-w-0">
                    <p class="font-weight-medium mb-0">
                      {{ item.name }}
                    </p>
                    <p class="text-body-2 text-medium-emphasis mb-0">
                      {{ item.reps }}×{{ item.sets }} · {{ formatLoad(item) }}
                    </p>
                  </div>
                  <div class="d-flex gap-1">
                    <VBtn
                      icon
                      variant="text"
                      size="small"
                      @click="openExercise(item)"
                    >
                      <VIcon icon="ri-pencil-line" />
                    </VBtn>
                    <VBtn
                      icon
                      variant="text"
                      size="small"
                      @click="deleteExercise(item)"
                    >
                      <VIcon icon="ri-delete-bin-line" />
                    </VBtn>
                  </div>
                </div>
              </div>
            </template>
          </VCardText>
        </VCard>
      </VWindowItem>

      <!-- Historial -->
      <VWindowItem value="historial">
        <div class="d-flex flex-wrap align-center justify-space-between gap-2 mb-3">
          <p class="text-body-2 text-medium-emphasis mb-0">
            Esta semana: {{ summary.week_sessions }} sesión(es)
            <span v-if="summary.week_minutes"> · {{ formatDuration(summary.week_minutes) }}</span>
          </p>
          <VBtn
            color="primary"
            rounded="lg"
            size="small"
            prepend-icon="ri-add-line"
            @click="openSessionFromDay(todayDay)"
          >
            Registrar
          </VBtn>
        </div>

        <VCard
          rounded="lg"
          class="training-history overflow-hidden"
          :loading="loading"
        >
          <div
            v-if="!loading && sessions.length === 0"
            class="training-empty"
          >
            Acá aparece lo que ya entrenaste.
          </div>

          <div
            v-else
            class="training-history__list"
          >
            <div
              v-for="session in sessions"
              :key="session.id"
              class="training-history__item"
            >
              <div class="min-w-0">
                <p class="font-weight-medium mb-0">
                  {{ formatSessionDate(session.date) }}
                  <span
                    v-if="session.weekday_label"
                    class="text-medium-emphasis font-weight-regular"
                  >
                    · {{ session.weekday_label }}
                  </span>
                </p>
                <p class="text-caption text-medium-emphasis mb-0">
                  {{ session.focus || muscleSummary(session) }}
                  <span v-if="session.duration_minutes"> · {{ formatDuration(session.duration_minutes) }}</span>
                  <span v-if="session.calories"> · {{ session.calories }} kcal</span>
                </p>
              </div>
              <div class="d-flex gap-1">
                <VBtn
                  icon
                  variant="text"
                  size="small"
                  @click="openSession(session)"
                >
                  <VIcon icon="ri-pencil-line" />
                </VBtn>
                <VBtn
                  icon
                  variant="text"
                  size="small"
                  @click="deleteSession(session)"
                >
                  <VIcon icon="ri-delete-bin-line" />
                </VBtn>
              </div>
            </div>
          </div>
        </VCard>
      </VWindowItem>
    </VWindow>

    <VDialog
      v-model="helpDialog"
      max-width="480"
    >
      <VCard rounded="lg">
        <VCardTitle class="text-h6">
          ¿Cómo se usa?
        </VCardTitle>
        <VCardText>
          <p class="mb-3">
            <strong>Hoy:</strong> ves la rutina del día. Si no querés hacer la de hoy, elegís otra (Lun–Dom) sin cambiar el plan de la semana.
          </p>
          <p class="mb-3">
            <strong>Semana:</strong> armás el plan fijo (lunes pecho, martes piernas…) y los ejercicios de cada día.
          </p>
          <p class="mb-0">
            <strong>Historial:</strong> al terminar, registrás la sesión (queda con la fecha de hoy y la rutina que elegiste).
          </p>
        </VCardText>
        <VCardActions>
          <VSpacer />
          <VBtn
            color="primary"
            rounded="lg"
            @click="helpDialog = false"
          >
            Entendido
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
          {{ exerciseForm.id ? 'Editar ejercicio' : 'Nuevo ejercicio' }}
        </VCardTitle>
        <VCardText class="d-flex flex-column gap-4">
          <VTextField
            v-model="exerciseForm.name"
            label="Ejercicio"
            variant="outlined"
            rounded="lg"
            hide-details="auto"
          />
          <VSelect
            v-model="exerciseForm.muscle_group"
            :items="muscleOptions"
            label="Grupo muscular"
            variant="outlined"
            rounded="lg"
            hide-details="auto"
            clearable
          />
          <div class="d-flex gap-3">
            <VNumberInput
              v-model="exerciseForm.reps"
              :min="1"
              :max="100"
              control-variant="stacked"
              label="Reps"
              variant="outlined"
              rounded="lg"
              hide-details="auto"
            />
            <VNumberInput
              v-model="exerciseForm.sets"
              :min="1"
              :max="30"
              control-variant="stacked"
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
            <VNumberInput
              v-if="exerciseForm.load_type !== 'bodyweight'"
              v-model="exerciseForm.load_value"
              :min="0"
              :max="exerciseForm.load_type === 'level' ? 50 : 500"
              :step="exerciseForm.load_type === 'level' ? 1 : 0.5"
              control-variant="stacked"
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
      v-model="sessionDialog"
      max-width="640"
      scrollable
    >
      <VCard rounded="lg">
        <VCardTitle class="text-h6">
          {{ sessionForm.id ? 'Editar sesión' : 'Registrar sesión' }}
        </VCardTitle>
        <VCardText class="d-flex flex-column gap-4">
          <VTextField
            v-model="sessionForm.date"
            type="date"
            label="Fecha"
            variant="outlined"
            rounded="lg"
            hide-details="auto"
          />
          <div class="d-flex flex-wrap gap-3">
            <VNumberInput
              v-model="sessionForm.duration_hours"
              :min="0"
              :max="12"
              control-variant="stacked"
              label="Horas"
              variant="outlined"
              rounded="lg"
              hide-details="auto"
              class="training-duration-num"
            />
            <VNumberInput
              v-model="sessionForm.duration_mins"
              :min="0"
              :max="59"
              control-variant="stacked"
              label="Minutos"
              variant="outlined"
              rounded="lg"
              hide-details="auto"
              class="training-duration-num"
            />
            <VNumberInput
              v-model="sessionForm.calories"
              :min="0"
              :max="5000"
              :step="10"
              control-variant="stacked"
              label="Calorías"
              variant="outlined"
              rounded="lg"
              hide-details="auto"
              class="training-duration-num"
            />
          </div>
          <VTextField
            v-model="sessionForm.notes"
            label="Notas"
            variant="outlined"
            rounded="lg"
            hide-details="auto"
          />

          <div
            v-for="(item, index) in sessionForm.exercises"
            :key="index"
            class="training-session-ex"
          >
            <p class="font-weight-medium mb-2">
              {{ item.name }}
            </p>
            <div class="d-flex flex-wrap gap-2">
              <VNumberInput
                v-model="item.reps"
                :min="1"
                :max="100"
                control-variant="stacked"
                label="Reps"
                density="compact"
                variant="outlined"
                rounded="lg"
                hide-details
                class="training-session-ex__num"
              />
              <VNumberInput
                v-model="item.sets"
                :min="1"
                :max="30"
                control-variant="stacked"
                label="Series"
                density="compact"
                variant="outlined"
                rounded="lg"
                hide-details
                class="training-session-ex__num"
              />
              <VNumberInput
                v-if="item.load_type !== 'bodyweight'"
                v-model="item.load_value"
                :min="0"
                :max="item.load_type === 'level' ? 50 : 500"
                :step="item.load_type === 'level' ? 1 : 0.5"
                control-variant="stacked"
                :label="item.load_type === 'level' ? 'Nivel' : 'Kg'"
                density="compact"
                variant="outlined"
                rounded="lg"
                hide-details
                class="training-session-ex__num"
              />
            </div>
          </div>
        </VCardText>
        <VCardActions>
          <VSpacer />
          <VBtn
            variant="text"
            rounded="lg"
            @click="sessionDialog = false"
          >
            Cancelar
          </VBtn>
          <VBtn
            color="primary"
            rounded="lg"
            :loading="saving"
            @click="saveSession"
          >
            Guardar
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </div>
</template>

<script>
import { axios } from '@/plugins/axios'

const MUSCLE_OPTIONS = [
  'Pecho', 'Hombros', 'Tríceps', 'Espalda', 'Bíceps', 'Antebrazo',
  'Piernas', 'Abdomen', 'Cardio', 'Otro',
]

function emptyExercise() {
  return {
    id: null,
    name: '',
    muscle_group: null,
    sets: 4,
    reps: 11,
    load_type: 'level',
    load_value: null,
    notes: '',
  }
}

function todayIso() {
  const now = new Date()
  const month = String(now.getMonth() + 1).padStart(2, '0')
  const day = String(now.getDate()).padStart(2, '0')

  return `${now.getFullYear()}-${month}-${day}`
}

function emptySession() {
  return {
    id: null,
    workout_day_id: null,
    date: todayIso(),
    duration_hours: null,
    duration_mins: null,
    calories: null,
    notes: '',
    exercises: [],
  }
}

function splitDuration(totalMinutes) {
  if (totalMinutes == null || totalMinutes === '')
    return { hours: null, mins: null }

  const total = Math.max(0, Number(totalMinutes) || 0)

  return {
    hours: Math.floor(total / 60) || null,
    mins: (total % 60) || null,
  }
}

function joinDuration(hours, mins) {
  const h = Number(hours) || 0
  const m = Number(mins) || 0
  const total = (h * 60) + m

  return total > 0 ? total : null
}

function groupExercises(exercises) {
  const groups = []
  const map = {}

  ;(exercises || []).forEach(item => {
    const name = item.muscle_group || 'Sin grupo'
    if (!map[name]) {
      map[name] = { name, items: [] }
      groups.push(map[name])
    }
    map[name].items.push(item)
  })

  return groups
}

export default {
  name: 'ModuleTraining',

  data() {
    return {
      loading: false,
      saving: false,
      error: '',
      activeTab: 'hoy',
      days: [],
      sessions: [],
      summary: { week_sessions: 0, week_minutes: 0, week_calories: 0 },
      todayWeekday: new Date().getDay() === 0 ? 7 : new Date().getDay(),
      activeDayId: null,
      selectedDayId: null,
      editPanelOpen: false,
      helpDialog: false,
      exerciseDialog: false,
      sessionDialog: false,
      muscleOptions: MUSCLE_OPTIONS,
      loadTypeOptions: [
        { title: 'Nivel (máquina)', value: 'level' },
        { title: 'Kilogramos', value: 'kg' },
        { title: 'Peso corporal', value: 'bodyweight' },
      ],
      exerciseForm: emptyExercise(),
      sessionForm: emptySession(),
    }
  },

  computed: {
    todayDay() {
      return this.days.find(day => day.weekday === this.todayWeekday) || null
    },

    activeDay() {
      return this.days.find(day => day.id === this.activeDayId) || this.todayDay
    },

    calendarDayLabel() {
      return this.todayDay?.label || ''
    },

    isSwappedRoutine() {
      return Boolean(this.activeDay && this.todayDay && this.activeDay.id !== this.todayDay.id)
    },

    selectedDay() {
      return this.days.find(day => day.id === this.selectedDayId) || null
    },

    activeGroupedExercises() {
      return groupExercises(this.activeDay?.exercises)
    },

    groupedExercises() {
      return groupExercises(this.selectedDay?.exercises)
    },
  },

  created() {
    this.load()
  },

  methods: {
    emptyExercise,
    emptySession,
    todayIso,

    load() {
      this.loading = true
      this.error = ''

      axios.get('/api/training')
        .then(response => {
          this.days = response.data.days || []
          this.sessions = response.data.sessions || []
          this.summary = response.data.summary || this.summary
          this.todayWeekday = response.data.today_weekday ?? this.todayWeekday

          const fallbackId = this.todayDay?.id || this.days[0]?.id || null
          if (!this.activeDayId || !this.days.some(day => day.id === this.activeDayId)) {
            this.activeDayId = fallbackId
          }
          if (!this.selectedDayId) {
            this.selectedDayId = fallbackId
          }
        })
        .catch(error => {
          this.error = error.response?.data?.message || 'No se pudo cargar el entrenamiento.'
        })
        .finally(() => {
          this.loading = false
        })
    },

    saveDay() {
      if (!this.selectedDay)
        return

      this.saveDayById(this.selectedDay)
    },

    saveDayById(day) {
      if (!day)
        return

      axios.put(`/api/training/days/${day.id}`, {
        focus: day.focus,
        is_rest: day.is_rest,
      }).catch(error => {
        this.error = error.response?.data?.message || 'No se pudo guardar el día.'
      })
    },

    toggleEditDay(day) {
      if (this.selectedDayId === day.id && this.editPanelOpen) {
        this.editPanelOpen = false

        return
      }

      this.selectedDayId = day.id
      this.editPanelOpen = true
    },

    resetToTodayRoutine() {
      this.activeDayId = this.todayDay?.id || this.activeDayId
    },

    goEditDay(day) {
      this.selectedDayId = day.id
      this.editPanelOpen = true
      this.activeTab = 'semana'
    },

    shortDay(label) {
      return String(label || '').slice(0, 3)
    },

    openExercise(item = null) {
      this.exerciseForm = item
        ? { ...item }
        : this.emptyExercise()
      this.exerciseDialog = true
    },

    saveExercise() {
      if (!this.selectedDay || this.saving)
        return

      const name = String(this.exerciseForm.name || '').trim()
      if (!name) {
        this.error = 'Indicá el nombre del ejercicio.'

        return
      }

      this.saving = true
      const payload = {
        name,
        muscle_group: this.exerciseForm.muscle_group,
        sets: this.exerciseForm.sets,
        reps: this.exerciseForm.reps,
        load_type: this.exerciseForm.load_type,
        load_value: this.exerciseForm.load_type === 'bodyweight' ? null : this.exerciseForm.load_value,
        notes: this.exerciseForm.notes,
      }

      const request = this.exerciseForm.id
        ? axios.put(`/api/training/exercises/${this.exerciseForm.id}`, payload)
        : axios.post(`/api/training/days/${this.selectedDay.id}/exercises`, payload)

      request
        .then(() => {
          this.exerciseDialog = false
          this.$toast.success('Ejercicio guardado', { timeout: 2000, closeOnClick: true })
          this.load()
        })
        .catch(error => {
          this.error = error.response?.data?.message || 'No se pudo guardar el ejercicio.'
        })
        .finally(() => {
          this.saving = false
        })
    },

    deleteExercise(item) {
      if (!confirm(`¿Eliminar ${item.name}?`))
        return

      axios.delete(`/api/training/exercises/${item.id}`)
        .then(() => this.load())
        .catch(error => {
          this.error = error.response?.data?.message || 'No se pudo eliminar.'
        })
    },

    openSessionFromDay(day = null) {
      const target = day || this.todayDay || this.selectedDay || this.days[0]
      if (!target) {
        this.sessionForm = this.emptySession()
        this.sessionDialog = true

        return
      }

      this.selectedDayId = target.id
      this.sessionForm = {
        ...this.emptySession(),
        workout_day_id: target.id,
        exercises: (target.exercises || []).map(item => ({
          name: item.name,
          muscle_group: item.muscle_group,
          sets: item.sets,
          reps: item.reps,
          load_type: item.load_type,
          load_value: item.load_value,
          notes: item.notes,
        })),
      }
      this.sessionDialog = true
    },

    openSession(session) {
      const duration = splitDuration(session.duration_minutes)
      this.sessionForm = {
        id: session.id,
        workout_day_id: session.workout_day_id,
        date: session.date,
        duration_hours: duration.hours,
        duration_mins: duration.mins,
        calories: session.calories,
        notes: session.notes || '',
        exercises: (session.exercises || []).map(item => ({ ...item })),
      }
      this.sessionDialog = true
    },

    saveSession() {
      if (this.saving)
        return

      this.saving = true
      const payload = {
        date: this.sessionForm.date,
        workout_day_id: this.sessionForm.workout_day_id,
        duration_minutes: joinDuration(this.sessionForm.duration_hours, this.sessionForm.duration_mins),
        calories: this.sessionForm.calories || null,
        notes: this.sessionForm.notes,
        exercises: this.sessionForm.exercises,
      }

      const request = this.sessionForm.id
        ? axios.put(`/api/training/sessions/${this.sessionForm.id}`, payload)
        : axios.post('/api/training/sessions', payload)

      request
        .then(() => {
          this.sessionDialog = false
          this.activeTab = 'historial'
          this.$toast.success('Sesión guardada', { timeout: 2000, closeOnClick: true })
          this.load()
        })
        .catch(error => {
          this.error = error.response?.data?.message || 'No se pudo guardar la sesión.'
        })
        .finally(() => {
          this.saving = false
        })
    },

    deleteSession(session) {
      if (!confirm('¿Eliminar esta sesión?'))
        return

      axios.delete(`/api/training/sessions/${session.id}`)
        .then(() => this.load())
        .catch(error => {
          this.error = error.response?.data?.message || 'No se pudo eliminar.'
        })
    },

    formatDuration(totalMinutes) {
      if (!totalMinutes)
        return ''

      const hours = Math.floor(totalMinutes / 60)
      const mins = totalMinutes % 60

      if (hours && mins)
        return `${hours} h ${mins} min`
      if (hours)
        return `${hours} h`

      return `${mins} min`
    },

    formatLoad(item) {
      if (item.load_type === 'bodyweight')
        return 'Peso corporal'
      if (item.load_type === 'level')
        return item.load_value != null ? `lvl ${item.load_value}` : 'Sin nivel'
      if (item.load_value == null)
        return 'Sin peso'

      return `${item.load_value} kg`
    },

    formatSessionDate(value) {
      if (!value)
        return ''

      const date = new Date(`${value}T00:00:00`)

      return date.toLocaleDateString('es-CR', {
        weekday: 'short',
        day: '2-digit',
        month: 'short',
      })
    },

    muscleSummary(session) {
      const groups = [...new Set((session.exercises || []).map(item => item.muscle_group).filter(Boolean))]

      return groups.length ? groups.join(' + ') : `${(session.exercises || []).length} ejercicios`
    },
  },
}
</script>

<style scoped>
.training-tabs :deep(.v-slide-group__content) {
  gap: 0.25rem;
}

.training-hoy__eyebrow {
  font-size: 0.8125rem;
  font-weight: 600;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  color: rgb(var(--v-theme-primary));
}

.training-hoy__focus {
  font-size: clamp(1.5rem, 5vw, 2rem);
  font-weight: 700;
  line-height: 1.2;
}

.training-hoy__swap-note {
  font-size: 0.875rem;
  color: rgba(var(--v-theme-on-surface), 0.65);
}

.training-hoy__pick-label {
  font-size: 0.75rem;
  font-weight: 650;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  color: rgba(var(--v-theme-on-surface), 0.5);
}

.training-day-chips {
  display: flex;
  gap: 0.45rem;
  overflow-x: auto;
  padding-bottom: 0.15rem;
}

.training-day-chip {
  flex: 0 0 auto;
  border: thin solid rgba(var(--v-border-color), var(--v-border-opacity));
  background: rgb(var(--v-theme-surface));
  color: inherit;
  border-radius: 999px;
  padding: 0.4rem 0.8rem;
  font-size: 0.8125rem;
  font-weight: 600;
  cursor: pointer;
}

.training-day-chip--today {
  border-color: rgb(var(--v-theme-primary));
}

.training-day-chip--active {
  background: color-mix(in srgb, rgb(var(--v-theme-primary)) 16%, rgb(var(--v-theme-surface)));
  border-color: rgb(var(--v-theme-primary));
}

.training-hoy__rest,
.training-hoy__empty {
  padding: 1.5rem 0.25rem 2rem;
  color: rgba(var(--v-theme-on-surface), 0.7);
}

.training-hoy__group {
  margin-bottom: 1.5rem;
}

.training-hoy__group-title {
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: rgba(var(--v-theme-on-surface), 0.5);
  margin: 0 0 0.65rem;
}

.training-hoy__ex {
  padding: 1rem 0;
  border-bottom: thin solid rgba(var(--v-border-color), var(--v-border-opacity));
}

.training-hoy__ex:last-child {
  border-bottom: 0;
}

.training-hoy__ex-name {
  font-size: 1.125rem;
  font-weight: 650;
  line-height: 1.3;
}

.training-hoy__ex-rx {
  font-size: 1.35rem;
  font-weight: 700;
  font-variant-numeric: tabular-nums;
  letter-spacing: -0.02em;
}

.training-hoy__ex-dot {
  margin: 0 0.15rem;
  opacity: 0.45;
  font-weight: 500;
}

.training-hoy__ex-note {
  margin-top: 0.25rem;
  font-size: 0.8125rem;
  color: rgba(var(--v-theme-on-surface), 0.55);
}

.training-hoy__actions {
  position: sticky;
  bottom: 0.75rem;
  margin-top: 1.5rem;
  padding-top: 0.5rem;
  background: linear-gradient(to top, rgb(var(--v-theme-background)) 70%, transparent);
}

.training-semana__row {
  display: grid;
  grid-template-columns: 6.5rem 1fr auto;
  gap: 0.75rem;
  width: 100%;
  align-items: center;
  border: 0;
  border-bottom: thin solid rgba(var(--v-border-color), var(--v-border-opacity));
  background: transparent;
  color: inherit;
  text-align: left;
  padding: 0.95rem 1rem;
  cursor: pointer;
}

.training-semana__row:last-child {
  border-bottom: 0;
}

.training-semana__row--today .training-semana__day {
  color: rgb(var(--v-theme-primary));
}

.training-semana__row--open {
  background: color-mix(in srgb, rgb(var(--v-theme-primary)) 10%, rgb(var(--v-theme-surface)));
}

.training-semana__day {
  font-weight: 650;
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
}

.training-semana__hoy {
  font-size: 0.65rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  opacity: 0.85;
}

.training-semana__summary {
  color: rgba(var(--v-theme-on-surface), 0.78);
  font-size: 0.9375rem;
}

.training-semana__chevron {
  opacity: 0.45;
}

.training-empty {
  text-align: center;
  padding: 2.5rem 1rem;
  color: rgba(var(--v-theme-on-surface), 0.6);
  font-size: 0.875rem;
}

.training-group__title {
  font-size: 0.7rem;
  font-weight: 700;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: rgba(var(--v-theme-on-surface), 0.55);
  margin: 0 0 0.5rem;
}

.training-exercise {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  padding: 0.75rem 0.9rem;
  border: thin solid rgba(var(--v-border-color), var(--v-border-opacity));
  border-radius: 12px;
  margin-bottom: 0.5rem;
}

.training-history__list {
  max-height: min(420px, 55vh);
  overflow-y: auto;
}

.training-history__item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  padding: 0.9rem 1rem;
  border-top: thin solid rgba(var(--v-border-color), var(--v-border-opacity));
}

.training-history__item:first-child {
  border-top: 0;
}

.training-session-ex {
  padding: 0.85rem;
  border: thin solid rgba(var(--v-border-color), var(--v-border-opacity));
  border-radius: 12px;
}

.training-session-ex__num {
  width: 8.5rem;
  flex: 1 1 8.5rem;
  max-width: 11rem;
}

.training-duration-num {
  flex: 1 1 7rem;
  min-width: 6.5rem;
  max-width: 10rem;
}

@media (max-width: 599px) {
  .training-semana__row {
    grid-template-columns: 1fr auto;
    grid-template-rows: auto auto;
  }

  .training-semana__day {
    grid-column: 1;
  }

  .training-semana__summary {
    grid-column: 1;
  }

  .training-semana__chevron {
    grid-column: 2;
    grid-row: 1 / span 2;
  }
}
</style>
