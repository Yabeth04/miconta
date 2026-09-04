<template>
  <div :class="`training-hoy training-hoy--${hoyView}`">
    <div
      v-if="loading && !activeDay"
      class="training-empty"
    >
      Cargando…
    </div>

    <template v-else-if="activeDay">
      <header class="training-hoy__header mb-2">
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
          Viendo {{ activeDay.label }} · hoy toca {{ shortDay(todayDay.label) }}
        </p>
      </header>

      <div class="training-hoy__pick mb-3">
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
          <!-- Lista -->
          <div
            v-if="hoyView === 'lista'"
            class="training-hoy__list"
          >
            <button
              v-for="item in activeDay.exercises"
              :key="item.id"
              type="button"
              class="training-hoy__ex training-hoy__ex--row"
              @click="openQuickEdit(item)"
            >
              <span class="training-hoy__ex-name">{{ item.name }}</span>
              <span class="training-hoy__ex-rx">{{ formatLoad(item) }}</span>
            </button>
          </div>

          <!-- Enfoque -->
          <div
            v-else-if="hoyView === 'enfoque' && focusItem"
            class="training-hoy__focus-mode"
          >
            <p class="training-hoy__focus-count mb-3">
              {{ focusIndex + 1 }} / {{ flatExercises.length }}
            </p>
            <div class="training-hoy__focus-card">
              <p class="training-hoy__focus-group mb-1">
                {{ focusItem.muscle_group || 'Actividad' }}
              </p>
              <p class="training-hoy__focus-name mb-2">
                {{ focusItem.name }}
              </p>
              <p class="training-hoy__focus-rx mb-0">
                {{ formatLoad(focusItem) }}
              </p>
              <p
                v-if="focusItem.notes"
                class="training-hoy__ex-note mt-2 mb-0"
              >
                {{ focusItem.notes }}
              </p>
              <VBtn
                class="mt-3"
                variant="tonal"
                size="small"
                rounded="lg"
                prepend-icon="ri-pencil-line"
                @click="openQuickEdit(focusItem)"
              >
                Ajustar
              </VBtn>
            </div>
            <div class="training-hoy__focus-nav mt-3">
              <VBtn
                variant="tonal"
                rounded="lg"
                :disabled="focusIndex <= 0"
                prepend-icon="ri-arrow-left-s-line"
                @click="focusPrev"
              >
                Anterior
              </VBtn>
              <VBtn
                color="primary"
                variant="tonal"
                rounded="lg"
                :disabled="focusIndex >= flatExercises.length - 1"
                append-icon="ri-arrow-right-s-line"
                @click="focusNext"
              >
                Siguiente
              </VBtn>
            </div>
          </div>

          <!-- Detalle / compacta -->
          <div
            v-else
            class="training-hoy__list"
          >
            <div
              v-for="item in activeDay.exercises"
              :key="item.id"
              class="training-hoy__ex"
            >
              <div class="min-w-0 flex-grow-1">
                <p class="training-hoy__ex-name mb-0">
                  {{ item.name }}
                </p>
                <p class="training-hoy__ex-rx mb-0">
                  {{ formatLoad(item) }}
                </p>
                <p
                  v-if="item.notes && hoyView === 'detalle'"
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

        <template v-else>
          <!-- Vista lista: plana, una línea -->
          <div
            v-if="hoyView === 'lista'"
            class="training-hoy__list"
          >
            <button
              v-for="item in activeDay.exercises"
              :key="item.id"
              type="button"
              class="training-hoy__ex training-hoy__ex--row"
              @click="openQuickEdit(item)"
            >
              <span class="training-hoy__ex-name">{{ item.name }}</span>
              <span class="training-hoy__ex-rx">
                <template v-if="item.load_type === 'km'">
                  {{ formatLoad(item) }}
                </template>
                <template v-else>
                  {{ item.reps }}×{{ item.sets }} · {{ formatLoad(item) }}
                </template>
              </span>
            </button>
          </div>

          <!-- Enfoque: un ejercicio a la vez -->
          <div
            v-else-if="hoyView === 'enfoque' && focusItem"
            class="training-hoy__focus-mode"
          >
            <p class="training-hoy__focus-count mb-3">
              {{ focusIndex + 1 }} / {{ flatExercises.length }}
            </p>
            <div class="training-hoy__focus-card">
              <p class="training-hoy__focus-group mb-1">
                {{ focusItem.muscle_group || 'Ejercicio' }}
              </p>
              <p class="training-hoy__focus-name mb-2">
                {{ focusItem.name }}
              </p>
              <p class="training-hoy__focus-rx mb-0">
                <template v-if="focusItem.load_type === 'km'">
                  {{ formatLoad(focusItem) }}
                </template>
                <template v-else>
                  {{ focusItem.reps }}×{{ focusItem.sets }}
                  <span class="training-hoy__ex-dot">·</span>
                  {{ formatLoad(focusItem) }}
                </template>
              </p>
              <p
                v-if="focusItem.notes"
                class="training-hoy__ex-note mt-2 mb-0"
              >
                {{ focusItem.notes }}
              </p>
              <VBtn
                class="mt-3"
                variant="tonal"
                size="small"
                rounded="lg"
                prepend-icon="ri-pencil-line"
                @click="openQuickEdit(focusItem)"
              >
                Ajustar
              </VBtn>
            </div>
            <div class="training-hoy__focus-nav mt-3">
              <VBtn
                variant="tonal"
                rounded="lg"
                :disabled="focusIndex <= 0"
                prepend-icon="ri-arrow-left-s-line"
                @click="focusPrev"
              >
                Anterior
              </VBtn>
              <VBtn
                color="primary"
                variant="tonal"
                rounded="lg"
                :disabled="focusIndex >= flatExercises.length - 1"
                append-icon="ri-arrow-right-s-line"
                @click="focusNext"
              >
                Siguiente
              </VBtn>
            </div>
          </div>

          <!-- Detalle / compacta: por grupos -->
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
                  v-if="hasMuscleIcon(group.name) && hoyView === 'detalle'"
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
                  <p class="training-hoy__ex-name mb-0">
                    {{ item.name }}
                  </p>
                  <p class="training-hoy__ex-rx mb-0">
                    {{ item.reps }}×{{ item.sets }}
                    <span class="training-hoy__ex-dot">·</span>
                    {{ formatLoad(item) }}
                  </p>
                  <p
                    v-if="item.notes && hoyView === 'detalle'"
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
              color="primary"
              rounded="lg"
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
    hoyView: { type: String, default: 'compacta' },
  },

  emits: ['refresh', 'error', 'edit-day', 'register'],

  data() {
    return {
      activeDayId: null,
      focusIndex: 0,
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

    calendarDayLabel() {
      return this.todayDay?.label || ''
    },

    isSwappedRoutine() {
      return Boolean(this.activeDay && this.todayDay && this.activeDay.id !== this.todayDay.id)
    },

    activeGroupedExercises() {
      return groupExercises(this.activeDay?.exercises)
    },

    flatExercises() {
      return this.activeDay?.exercises || []
    },

    focusItem() {
      return this.flatExercises[this.focusIndex] || null
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

    activeDayId() {
      this.focusIndex = 0
    },

    hoyView(value) {
      if (value === 'enfoque')
        this.focusIndex = 0
    },

    flatExercises(list) {
      if (this.focusIndex >= list.length)
        this.focusIndex = Math.max(0, list.length - 1)
    },
  },

  methods: {
    shortDay,
    formatLoad,
    hasMuscleIcon,

    focusPrev() {
      if (this.focusIndex > 0)
        this.focusIndex -= 1
    },

    focusNext() {
      if (this.focusIndex < this.flatExercises.length - 1)
        this.focusIndex += 1
    },

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
  font-size: 0.75rem;
  font-weight: 600;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  color: rgb(var(--v-theme-primary));
}

.training-hoy__focus {
  font-size: clamp(1.2rem, 4vw, 1.5rem);
  font-weight: 700;
  line-height: 1.25;
}

.training-hoy--compacta .training-hoy__focus,
.training-hoy--lista .training-hoy__focus,
.training-hoy--enfoque .training-hoy__focus {
  font-size: clamp(1.1rem, 3.5vw, 1.35rem);
}

.training-hoy__swap-note {
  font-size: 0.8125rem;
  color: rgba(var(--v-theme-on-surface), 0.65);
}

.training-day-chips {
  display: flex;
  gap: 0.4rem;
  overflow-x: auto;
  padding-bottom: 0.15rem;
}

.training-day-chip {
  flex: 0 0 auto;
  border: thin solid rgba(var(--v-border-color), var(--v-border-opacity));
  background: rgb(var(--v-theme-surface));
  color: inherit;
  border-radius: 999px;
  padding: 0.32rem 0.7rem;
  font-size: 0.75rem;
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
  padding: 1.25rem 0.25rem 1.75rem;
  color: rgba(var(--v-theme-on-surface), 0.7);
}

.training-hoy__group {
  margin-bottom: 1.1rem;
}

.training-hoy--compacta .training-hoy__group {
  margin-bottom: 0.85rem;
}

.training-hoy__group-title {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  font-size: 0.6875rem;
  font-weight: 700;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: rgba(var(--v-theme-on-surface), 0.45);
  margin: 0 0 0.35rem;
}

.training-hoy--compacta .training-hoy__group-title {
  margin-bottom: 0.25rem;
  letter-spacing: 0.04em;
}

.training-hoy__ex {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
  padding: 0.65rem 0;
  border-bottom: thin solid rgba(var(--v-border-color), var(--v-border-opacity));
}

.training-hoy--compacta .training-hoy__ex {
  padding: 0.5rem 0;
  gap: 0.5rem;
}

.training-hoy__ex:last-child {
  border-bottom: 0;
}

.training-hoy__ex--row {
  width: 100%;
  border: 0;
  border-bottom: thin solid rgba(var(--v-border-color), var(--v-border-opacity));
  background: transparent;
  color: inherit;
  font: inherit;
  text-align: left;
  cursor: pointer;
  padding: 0.65rem 0;
  gap: 0.75rem;
}

.training-hoy__ex--row .training-hoy__ex-name {
  flex: 1 1 auto;
  min-width: 0;
  font-size: 0.9375rem;
  font-weight: 600;
}

.training-hoy__ex--row .training-hoy__ex-rx {
  flex-shrink: 0;
  font-size: 0.875rem;
  font-weight: 650;
  color: rgba(var(--v-theme-on-surface), 0.72);
}

.training-hoy__ex-name {
  font-size: 0.9375rem;
  font-weight: 600;
  line-height: 1.3;
}

.training-hoy--compacta .training-hoy__ex-name {
  font-size: 0.875rem;
}

.training-hoy__ex-rx {
  margin-top: 0.1rem;
  font-size: 0.875rem;
  font-weight: 650;
  font-variant-numeric: tabular-nums;
  color: rgba(var(--v-theme-on-surface), 0.7);
}

.training-hoy--compacta .training-hoy__ex-rx {
  font-size: 0.8125rem;
  color: rgba(var(--v-theme-on-surface), 0.65);
}

.training-hoy__ex-dot {
  margin: 0 0.15rem;
  opacity: 0.45;
  font-weight: 500;
}

.training-hoy__ex-note {
  margin-top: 0.15rem;
  font-size: 0.75rem;
  color: rgba(var(--v-theme-on-surface), 0.5);
}

.training-hoy__actions {
  margin-top: 1.15rem;
  padding-bottom: 1rem;
}

.training-hoy--compacta .training-hoy__actions,
.training-hoy--lista .training-hoy__actions,
.training-hoy--enfoque .training-hoy__actions {
  margin-top: 1rem;
}

.training-hoy__focus-mode {
  padding: 0.25rem 0 0.5rem;
}

.training-hoy__focus-count {
  font-size: 0.75rem;
  font-weight: 650;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  color: rgba(var(--v-theme-on-surface), 0.5);
  text-align: center;
}

.training-hoy__focus-card {
  text-align: center;
  padding: 1.5rem 1rem;
  border-radius: 16px;
  background: color-mix(in srgb, rgb(var(--v-theme-primary)) 8%, rgb(var(--v-theme-surface)));
  border: thin solid rgba(var(--v-border-color), var(--v-border-opacity));
}

.training-hoy__focus-group {
  font-size: 0.6875rem;
  font-weight: 700;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: rgba(var(--v-theme-on-surface), 0.45);
}

.training-hoy__focus-name {
  font-size: clamp(1.35rem, 5vw, 1.75rem);
  font-weight: 700;
  line-height: 1.2;
}

.training-hoy__focus-rx {
  font-size: clamp(1.15rem, 4vw, 1.45rem);
  font-weight: 700;
  font-variant-numeric: tabular-nums;
  color: rgb(var(--v-theme-primary));
}

.training-hoy__focus-nav {
  display: flex;
  gap: 0.65rem;
  justify-content: space-between;
}

.training-hoy__focus-nav .v-btn {
  flex: 1 1 0;
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
