<template>
  <div>
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
            ? (activeDaySummary || 'Descanso / correr')
            : (activeDaySummary || 'Todavía sin plan') }}
        </h2>
        <p
          v-if="isSwappedRoutine"
          class="training-hoy__swap-note mb-0 mt-2"
        >
          Hoy toca {{ todayDaySummary || todayDay?.label }}, pero estás haciendo la de {{ activeDay.label }}.
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
        <div
          v-if="isSwappedRoutine"
          class="training-hoy__swap-actions mt-2"
        >
          <VBtn
            size="small"
            variant="text"
            rounded="lg"
            class="px-1"
            @click="resetToTodayRoutine"
          >
            Volver a la de hoy
          </VBtn>
        </div>
      </div>

      <div
        v-if="activeDay.is_rest"
        class="training-hoy__rest"
      >
        <div
          v-if="!activeDay.exercises?.length"
          class="training-hoy__empty"
        >
          <p class="mb-3">
            Día libre / correr. Agregá una actividad con kilómetros para ir midiendo.
          </p>
          <VBtn
            color="primary"
            variant="tonal"
            rounded="lg"
            @click="$emit('edit-day', activeDay)"
          >
            Armar cardio
          </VBtn>
        </div>

        <template v-else>
          <div class="training-hoy__list">
            <div
              v-for="item in activeDay.exercises"
              :key="item.id"
              class="training-hoy__ex"
            >
              <div class="min-w-0 flex-grow-1">
                <p class="training-hoy__ex-name mb-1">
                  {{ item.name }}
                </p>
                <p class="training-hoy__ex-rx mb-0">
                  {{ formatLoad(item) }}
                </p>
                <p
                  v-if="item.notes"
                  class="training-hoy__ex-note mb-0"
                >
                  {{ item.notes }}
                </p>
              </div>
              <VBtn
                icon
                variant="text"
                size="small"
                aria-label="Ajustar kilómetros"
                @click="openQuickEdit(item)"
              >
                <VIcon
                  icon="ri-pencil-line"
                  size="18"
                />
              </VBtn>
            </div>
          </div>

          <div class="training-hoy__actions">
            <VBtn
              color="primary"
              rounded="lg"
              size="large"
              block
              prepend-icon="ri-checkbox-circle-line"
              @click="$emit('register', activeDay)"
            >
              Terminé · registrar
            </VBtn>
            <VBtn
              variant="text"
              rounded="lg"
              block
              class="mt-1"
              @click="$emit('edit-day', activeDay)"
            >
              Editar estas actividades
            </VBtn>
          </div>
        </template>
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
            @click="$emit('edit-day', activeDay)"
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
              <MuscleGroupIcon
                v-if="hasMuscleIcon(group.name)"
                :group="group.name"
              />
              {{ group.name }}
            </p>
            <div
              v-for="item in group.items"
              :key="item.id"
              class="training-hoy__ex"
            >
              <div class="min-w-0 flex-grow-1">
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
              <VBtn
                icon
                variant="text"
                size="small"
                aria-label="Ajustar reps o nivel"
                @click="openQuickEdit(item)"
              >
                <VIcon
                  icon="ri-pencil-line"
                  size="18"
                />
              </VBtn>
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
            @click="$emit('register', activeDay)"
          >
            Terminé · registrar
          </VBtn>
          <VBtn
            variant="text"
            rounded="lg"
            block
            class="mt-1"
            @click="$emit('edit-day', activeDay)"
          >
            Editar estos ejercicios
          </VBtn>
        </div>
      </template>
    </template>

    <VDialog
      v-model="quickEditDialog"
      max-width="420"
    >
      <VCard rounded="lg">
        <VCardTitle class="text-h6">
          {{ quickEditForm.name || 'Ajustar' }}
        </VCardTitle>
        <VCardText>
          <p class="text-caption text-medium-emphasis mb-4">
            Cambio en el plan (queda para las próximas veces).
          </p>

          <div class="training-quick">
            <template v-if="quickEditForm.load_type === 'km'">
              <div class="training-quick__row">
                <span class="training-quick__label">Km</span>
                <div class="training-quick__ctrl">
                  <VBtn
                    icon
                    variant="tonal"
                    size="small"
                    rounded="lg"
                    @click="bumpQuick('load_value', -0.5)"
                  >
                    <VIcon icon="ri-subtract-line" />
                  </VBtn>
                  <span class="training-quick__value">{{ quickEditForm.load_value ?? 0 }}</span>
                  <VBtn
                    icon
                    variant="tonal"
                    size="small"
                    rounded="lg"
                    @click="bumpQuick('load_value', 0.5)"
                  >
                    <VIcon icon="ri-add-line" />
                  </VBtn>
                </div>
              </div>
            </template>

            <template v-else>
              <div class="training-quick__row">
                <span class="training-quick__label">Reps</span>
                <div class="training-quick__ctrl">
                  <VBtn
                    icon
                    variant="tonal"
                    size="small"
                    rounded="lg"
                    @click="bumpQuick('reps', -1)"
                  >
                    <VIcon icon="ri-subtract-line" />
                  </VBtn>
                  <span class="training-quick__value">{{ quickEditForm.reps }}</span>
                  <VBtn
                    icon
                    variant="tonal"
                    size="small"
                    rounded="lg"
                    @click="bumpQuick('reps', 1)"
                  >
                    <VIcon icon="ri-add-line" />
                  </VBtn>
                </div>
              </div>

              <div class="training-quick__row">
                <span class="training-quick__label">Series</span>
                <div class="training-quick__ctrl">
                  <VBtn
                    icon
                    variant="tonal"
                    size="small"
                    rounded="lg"
                    @click="bumpQuick('sets', -1)"
                  >
                    <VIcon icon="ri-subtract-line" />
                  </VBtn>
                  <span class="training-quick__value">{{ quickEditForm.sets }}</span>
                  <VBtn
                    icon
                    variant="tonal"
                    size="small"
                    rounded="lg"
                    @click="bumpQuick('sets', 1)"
                  >
                    <VIcon icon="ri-add-line" />
                  </VBtn>
                </div>
              </div>

              <div
                v-if="quickEditForm.load_type !== 'bodyweight'"
                class="training-quick__row"
              >
                <span class="training-quick__label">
                  {{ quickEditForm.load_type === 'level' ? 'Nivel' : 'Kg' }}
                </span>
                <div class="training-quick__ctrl">
                  <VBtn
                    icon
                    variant="tonal"
                    size="small"
                    rounded="lg"
                    @click="bumpQuick('load_value', quickEditForm.load_type === 'level' ? -1 : -0.5)"
                  >
                    <VIcon icon="ri-subtract-line" />
                  </VBtn>
                  <span class="training-quick__value">{{ quickEditForm.load_value ?? 0 }}</span>
                  <VBtn
                    icon
                    variant="tonal"
                    size="small"
                    rounded="lg"
                    @click="bumpQuick('load_value', quickEditForm.load_type === 'level' ? 1 : 0.5)"
                  >
                    <VIcon icon="ri-add-line" />
                  </VBtn>
                </div>
              </div>
            </template>
          </div>
        </VCardText>
        <VCardActions>
          <VSpacer />
          <VBtn
            variant="text"
            rounded="lg"
            @click="quickEditDialog = false"
          >
            Cancelar
          </VBtn>
          <VBtn
            color="primary"
            rounded="lg"
            :loading="saving"
            @click="saveQuickEdit"
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
import MuscleGroupIcon from '@/views/pages/training/MuscleGroupIcon.vue'
import {
  shortDay,
  formatLoad,
  hasMuscleIcon,
  emptyExercise,
  groupExercises,
  focusFromGroups,
  groupsFromDay,
} from '@/utils/trainingFormat'

export default {
  name: 'TrainingHoyTab',

  components: { MuscleGroupIcon },

  props: {
    loading: { type: Boolean, default: false },
    days: { type: Array, default: () => [] },
    todayWeekday: { type: Number, default: null },
  },

  emits: ['refresh', 'error', 'edit-day', 'register'],

  data() {
    return {
      activeDayId: null,
      quickEditDialog: false,
      quickEditForm: emptyExercise(),
      saving: false,
    }
  },

  computed: {
    todayDay() {
      return this.days.find(day => day.weekday === this.todayWeekday) || null
    },

    activeDay() {
      return this.days.find(day => day.id === this.activeDayId) || this.todayDay
    },

    activeDaySummary() {
      if (!this.activeDay)
        return null

      if (this.activeDay.is_rest) {
        const names = (this.activeDay.exercises || []).map(item => item.name).filter(Boolean)

        return names.length ? names.join(' + ') : null
      }

      return focusFromGroups(groupsFromDay(this.activeDay))
    },

    todayDaySummary() {
      if (!this.todayDay)
        return null

      if (this.todayDay.is_rest) {
        const names = (this.todayDay.exercises || []).map(item => item.name).filter(Boolean)

        return names.length ? names.join(' + ') : null
      }

      return focusFromGroups(groupsFromDay(this.todayDay))
    },

    calendarDayLabel() {
      return this.todayDay?.label || ''
    },

    isSwappedRoutine() {
      return Boolean(this.activeDay && this.todayDay && this.activeDay.id !== this.todayDay.id)
    },

    activeGroupedExercises() {
      return groupExercises(this.activeDay?.exercises)
    },
  },

  watch: {
    days: {
      immediate: true,
      handler(days) {
        const list = days || []
        const fallbackId = this.todayDay?.id || list[0]?.id || null
        if (!this.activeDayId || !list.some(day => day.id === this.activeDayId))
          this.activeDayId = fallbackId
      },
    },
  },

  methods: {
    shortDay,
    formatLoad,
    hasMuscleIcon,

    resetToTodayRoutine() {
      this.activeDayId = this.todayDay?.id || this.activeDayId
    },

    openQuickEdit(item) {
      this.quickEditForm = { ...emptyExercise(), ...item }
      this.quickEditDialog = true
    },

    bumpQuick(field, delta) {
      const current = Number(this.quickEditForm[field])
      const base = Number.isFinite(current) ? current : 0
      let next = base + delta

      if (field === 'reps')
        next = Math.min(100, Math.max(1, next))
      else if (field === 'sets')
        next = Math.min(30, Math.max(1, next))
      else if (field === 'load_value')
        next = Math.max(0, Math.round(next * 100) / 100)

      this.quickEditForm[field] = next
    },

    saveQuickEdit() {
      if (!this.quickEditForm.id || this.saving)
        return

      this.saving = true
      const payload = {
        name: this.quickEditForm.name,
        muscle_group: this.quickEditForm.muscle_group,
        sets: this.quickEditForm.sets,
        reps: this.quickEditForm.reps,
        load_type: this.quickEditForm.load_type,
        load_value: this.quickEditForm.load_type === 'bodyweight' ? null : this.quickEditForm.load_value,
        notes: this.quickEditForm.notes,
      }

      axios.put(`/api/training/exercises/${this.quickEditForm.id}`, payload)
        .then(() => {
          this.quickEditDialog = false
          this.$toast.success('Actualizado', { timeout: 1500, closeOnClick: true })
          this.$emit('refresh')
        })
        .catch(error => {
          this.$emit('error', error.response?.data?.message || 'No se pudo guardar.')
        })
        .finally(() => {
          this.saving = false
        })
    },
  },
}
</script>

<style scoped>
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

.training-day-chip--active {
  border-color: rgb(var(--v-theme-primary));
  background: color-mix(in srgb, rgb(var(--v-theme-primary)) 16%, rgb(var(--v-theme-surface)));
  color: rgb(var(--v-theme-primary));
}

.training-day-chip--today:not(.training-day-chip--active) {
  border-color: rgba(var(--v-theme-primary), 0.45);
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
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: rgba(var(--v-theme-on-surface), 0.5);
  margin: 0 0 0.65rem;
}

.training-hoy__ex {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
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
  margin-top: 1.5rem;
  padding-bottom: 1rem;
}

.training-hoy__swap-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.35rem;
  align-items: center;
}

.training-empty {
  text-align: center;
  padding: 2.5rem 1rem;
  color: rgba(var(--v-theme-on-surface), 0.55);
}

.training-quick {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.training-quick__row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
}

.training-quick__label {
  font-weight: 600;
  min-width: 4rem;
}

.training-quick__ctrl {
  display: flex;
  align-items: center;
  gap: 0.65rem;
}

.training-quick__value {
  min-width: 2.5rem;
  text-align: center;
  font-size: 1.35rem;
  font-weight: 700;
  font-variant-numeric: tabular-nums;
}
</style>
