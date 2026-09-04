<template>
  <div class="training-page">
    <div class="d-flex flex-wrap align-center justify-space-between gap-3 mb-3">
      <div class="d-flex align-center gap-2 min-w-0">
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
      <div
        v-if="activeTab === 'hoy'"
        class="training-hoy-views"
        role="group"
        aria-label="Tipo de vista de Hoy"
      >
        <button
          v-for="option in hoyViewOptions"
          :key="option.value"
          type="button"
          class="training-hoy-views__btn"
          :class="{ 'training-hoy-views__btn--active': hoyView === option.value }"
          :title="option.title"
          :aria-label="option.title"
          @click="setHoyView(option.value)"
        >
          <VIcon
            :icon="option.icon"
            size="18"
          />
        </button>
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
      density="default"
      grow
      fixed-tabs
      height="72"
    >
      <VTab
        value="hoy"
        stacked
      >
        <VIcon icon="ri-flashlight-line" />
        Hoy
      </VTab>
      <VTab
        value="semana"
        stacked
      >
        <VIcon icon="ri-calendar-schedule-line" />
        Semana
      </VTab>
      <VTab
        value="historial"
        stacked
      >
        <VIcon icon="ri-history-line" />
        Historial
      </VTab>
      <VTab
        value="biblioteca"
        stacked
      >
        <VIcon icon="ri-book-open-line" />
        Biblioteca
      </VTab>
    </VTabs>

    <VWindow
      v-model="activeTab"
      :touch="false"
    >
      <VWindowItem value="hoy">
        <TrainingHoyTab
          ref="hoyTab"
          :loading="loading"
          :days="days"
          :today-weekday="todayWeekday"
          :hoy-view="hoyView"
          @refresh="load"
          @error="error = $event"
          @edit-day="goEditDay"
          @register="openSessionFromDay"
        />
      </VWindowItem>

      <VWindowItem
        value="semana"
        eager
      >
        <TrainingSemanaTab
          ref="semanaTab"
          :loading="loading"
          :days="days"
          :library="library"
          :today-weekday="todayWeekday"
          @refresh="load"
          @error="error = $event"
        />
      </VWindowItem>

      <VWindowItem value="historial">
        <TrainingHistorialTab
          :loading="loading"
          :summary="summary"
          :sessions="sessions"
          @refresh="load"
          @error="error = $event"
          @open-session="openSession"
        />
      </VWindowItem>

      <VWindowItem value="biblioteca">
        <TrainingBibliotecaTab
          :loading="loading"
          :library="library"
          @refresh="load"
          @error="error = $event"
        />
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
            <strong>Hoy:</strong> lo que mirás en el gym. Con el lápiz ajustás reps/nivel o km si es cardio. Podés elegir otra rutina solo por hoy. Para armar el plan, usá Semana.
          </p>
          <p class="mb-3">
            <strong>Semana:</strong> arrastrá grupos (Abdomen, Bíceps…) entre días. Tocá el día para editar ejercicios.
          </p>
          <p class="mb-3">
            <strong>Historial:</strong> lo que realmente hiciste al terminar.
          </p>
          <p class="mb-0">
            <strong>Biblioteca:</strong> solo nombre y grupo muscular. Series, reps y nivel se definen en cada día.
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
      v-model="sessionDialog"
      max-width="480"
    >
      <VCard rounded="lg">
        <VCardTitle class="text-h6">
          {{ sessionForm.id ? 'Editar sesión' : 'Registrar sesión' }}
        </VCardTitle>
        <VCardText class="d-flex flex-column gap-4 pt-4">
          <VTextField
            v-model="sessionForm.date"
            type="date"
            label="Fecha"
            variant="outlined"
            rounded="lg"
            hide-details="auto"
          />
          <div class="d-flex flex-wrap gap-3">
            <VTextField
              v-model.number="sessionForm.duration_hours"
              type="number"
              inputmode="numeric"
              min="0"
              max="12"
              label="Horas"
              variant="outlined"
              rounded="lg"
              hide-details="auto"
              class="training-meta-num"
            />
            <VTextField
              v-model.number="sessionForm.duration_mins"
              type="number"
              inputmode="numeric"
              min="0"
              max="59"
              label="Minutos"
              variant="outlined"
              rounded="lg"
              hide-details="auto"
              class="training-meta-num"
            />
            <VTextField
              v-model.number="sessionForm.calories"
              type="number"
              inputmode="numeric"
              min="0"
              label="Calorías"
              variant="outlined"
              rounded="lg"
              hide-details="auto"
              class="training-meta-num"
            />
          </div>
          <VTextField
            v-model="sessionForm.notes"
            label="Notas"
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
import {
  emptySession,
  joinDuration,
  splitDuration,
} from '@/utils/trainingFormat'
import TrainingBibliotecaTab from '@/views/pages/training/TrainingBibliotecaTab.vue'
import TrainingHistorialTab from '@/views/pages/training/TrainingHistorialTab.vue'
import TrainingHoyTab from '@/views/pages/training/TrainingHoyTab.vue'
import TrainingSemanaTab from '@/views/pages/training/TrainingSemanaTab.vue'

const HOY_VIEW_KEY = 'training.hoyView'
const HOY_VIEWS = ['detalle', 'compacta', 'lista', 'enfoque']

function readHoyViewPref() {
  try {
    const saved = localStorage.getItem(HOY_VIEW_KEY)
    if (HOY_VIEWS.includes(saved))
      return saved
  }
  catch {
    // ignore
  }

  return 'compacta'
}

export default {
  name: 'ModuleTraining',

  components: {
    TrainingHoyTab,
    TrainingSemanaTab,
    TrainingHistorialTab,
    TrainingBibliotecaTab,
  },

  data() {
    return {
      loading: false,
      saving: false,
      error: '',
      activeTab: 'hoy',
      hoyView: readHoyViewPref(),
      hoyViewOptions: [
        { value: 'detalle', title: 'Vista detalle', icon: 'ri-layout-masonry-line' },
        { value: 'compacta', title: 'Vista compacta', icon: 'ri-list-check-2' },
        { value: 'lista', title: 'Vista lista', icon: 'ri-menu-line' },
        { value: 'enfoque', title: 'Vista enfoque', icon: 'ri-focus-3-line' },
      ],
      days: [],
      sessions: [],
      library: [],
      summary: { week_sessions: 0, week_minutes: 0, week_calories: 0 },
      todayWeekday: new Date().getDay() === 0 ? 7 : new Date().getDay(),
      helpDialog: false,
      sessionDialog: false,
      sessionForm: emptySession(),
    }
  },

  computed: {
    todayDay() {
      return this.days.find(day => day.weekday === this.todayWeekday) || null
    },
  },

  created() {
    this.load()
  },

  methods: {
    setHoyView(value) {
      if (!HOY_VIEWS.includes(value))
        return

      this.hoyView = value
      try {
        localStorage.setItem(HOY_VIEW_KEY, value)
      }
      catch {
        // ignore
      }
    },

    load() {
      this.loading = true
      this.error = ''

      return axios.get('/api/training')
        .then(response => {
          this.todayWeekday = response.data.today_weekday ?? this.todayWeekday
          this.days = response.data.days || []
          this.sessions = response.data.sessions || []
          this.library = response.data.library || []
          this.summary = response.data.summary || this.summary
        })
        .catch(error => {
          this.error = error.response?.data?.message || 'No se pudo cargar el entrenamiento.'
        })
        .finally(() => {
          this.loading = false
        })
    },

    goEditDay(day) {
      this.activeTab = 'semana'
      this.$nextTick(() => this.$refs.semanaTab?.openDay?.(day))
    },

    openSessionFromDay(day = null) {
      const target = day || this.todayDay || this.days[0]
      if (!target) {
        this.sessionForm = emptySession()
        this.sessionDialog = true

        return
      }

      this.sessionForm = {
        ...emptySession(),
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
  },
}
</script>

<style scoped>
.training-tabs :deep(.v-slide-group) {
  overflow: visible;
}

.training-tabs :deep(.v-slide-group__content) {
  width: 100%;
  gap: 0;
  transform: none !important;
}

.training-tabs :deep(.v-tabs) {
  height: auto;
}

.training-tabs :deep(.v-tab) {
  min-width: 0;
  flex: 1 1 0;
  height: auto !important;
  min-height: 64px;
  padding-top: 0.35rem;
  padding-bottom: 0.55rem;
  padding-inline: 0.15rem;
  font-size: 0.75rem;
  letter-spacing: 0;
  overflow: visible;
}

.training-tabs :deep(.v-btn__content) {
  white-space: nowrap;
}

.training-meta-num {
  flex: 1 1 5.5rem;
  min-width: 5rem;
  max-width: 9rem;
}

.training-hoy-views {
  display: inline-flex;
  flex-shrink: 0;
  gap: 0.1rem;
  padding: 0.15rem;
  border-radius: 10px;
  background: rgba(var(--v-theme-on-surface), 0.06);
}

.training-hoy-views__btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 30px;
  height: 30px;
  border: 0;
  border-radius: 8px;
  background: transparent;
  color: rgba(var(--v-theme-on-surface), 0.55);
  cursor: pointer;
}

.training-hoy-views__btn--active {
  background: rgb(var(--v-theme-surface));
  color: rgb(var(--v-theme-primary));
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.12);
}
</style>
