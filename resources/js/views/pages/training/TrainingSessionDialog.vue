<template>
  <VDialog
    :model-value="modelValue"
    max-width="720"
    scrollable
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <VCard rounded="lg">
      <VCardTitle class="text-h6">
        {{ session?.id ? 'Editar sesión' : 'Registrar sesión' }}
      </VCardTitle>
      <VCardText>
        <VRow>
          <VCol
            cols="12"
            sm="4"
          >
            <VTextField
              v-model="date"
              type="date"
              label="Fecha"
              variant="outlined"
              rounded="lg"
              hide-details
            />
          </VCol>
          <VCol
            cols="6"
            sm="4"
          >
            <VTextField
              v-model="duration"
              type="number"
              inputmode="numeric"
              label="Minutos"
              variant="outlined"
              rounded="lg"
              hide-details
            />
          </VCol>
          <VCol
            cols="6"
            sm="4"
          >
            <VTextField
              v-model="calories"
              type="number"
              inputmode="numeric"
              label="Calorías"
              variant="outlined"
              rounded="lg"
              hide-details
            />
          </VCol>
        </VRow>

        <VTextField
          v-model="notes"
          class="mt-3"
          label="Nota (opcional)"
          variant="outlined"
          rounded="lg"
          hide-details
        />

        <div class="d-flex align-center justify-space-between mt-5 mb-2">
          <p class="text-subtitle-2 mb-0">
            Ejercicios
          </p>
          <VBtn
            size="small"
            variant="tonal"
            rounded="lg"
            prepend-icon="ri-add-line"
            :disabled="!exercises.length"
            @click="addExercise"
          >
            Agregar
          </VBtn>
        </div>

        <div
          v-if="!lines.length"
          class="text-body-2 text-medium-emphasis py-4"
        >
          Agregá al menos un ejercicio de la máquina.
        </div>

        <div
          v-for="(line, lineIndex) in lines"
          :key="line.key"
          class="training-session-line"
        >
          <div class="d-flex align-center gap-2 mb-2">
            <VSelect
              v-model="line.exercise_id"
              class="flex-grow-1"
              :items="exerciseItems"
              item-title="title"
              item-value="value"
              label="Ejercicio"
              variant="outlined"
              rounded="lg"
              density="comfortable"
              hide-details
            />
            <VBtn
              icon
              variant="text"
              size="small"
              aria-label="Quitar ejercicio"
              @click="removeExercise(lineIndex)"
            >
              <VIcon icon="ri-delete-bin-line" />
            </VBtn>
          </div>

          <div
            v-for="(set, setIndex) in line.sets"
            :key="`${line.key}-s-${setIndex}`"
            class="training-session-set"
          >
            <span class="text-caption text-medium-emphasis training-session-set__n">
              S{{ setIndex + 1 }}
            </span>
            <VTextField
              v-model="set.reps"
              type="number"
              inputmode="numeric"
              label="Reps"
              variant="outlined"
              rounded="lg"
              density="compact"
              hide-details
            />
            <VTextField
              v-model="set.weight"
              type="number"
              inputmode="decimal"
              label="Kg"
              variant="outlined"
              rounded="lg"
              density="compact"
              hide-details
            />
            <VBtn
              icon
              variant="text"
              size="x-small"
              :disabled="line.sets.length <= 1"
              aria-label="Quitar serie"
              @click="removeSet(lineIndex, setIndex)"
            >
              <VIcon
                icon="ri-close-line"
                size="18"
              />
            </VBtn>
          </div>

          <VBtn
            class="mt-2"
            size="small"
            variant="text"
            prepend-icon="ri-add-line"
            @click="addSet(lineIndex)"
          >
            Serie
          </VBtn>
        </div>
      </VCardText>
      <VCardActions>
        <VSpacer />
        <VBtn
          variant="text"
          rounded="lg"
          @click="$emit('update:modelValue', false)"
        >
          Cancelar
        </VBtn>
        <VBtn
          color="primary"
          rounded="lg"
          :loading="saving"
          @click="save"
        >
          Guardar
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<script>
let lineKey = 1

function emptySet() {
  return { reps: '10', weight: '' }
}

export default {
  name: 'TrainingSessionDialog',

  props: {
    modelValue: { type: Boolean, required: true },
    session: { type: Object, default: null },
    exercises: { type: Array, default: () => [] },
    saving: { type: Boolean, default: false },
  },

  emits: ['update:modelValue', 'save'],

  data() {
    return {
      date: '',
      duration: '',
      calories: '',
      notes: '',
      lines: [],
    }
  },

  computed: {
    exerciseItems() {
      return this.exercises.map(item => ({
        value: item.id,
        title: `${item.name} · ${item.muscle_label}`,
      }))
    },
  },

  watch: {
    modelValue(open) {
      if (open)
        this.hydrate()
    },
  },

  methods: {
    hydrate() {
      const today = new Date()
      const iso = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`

      if (this.session?.id) {
        this.date = this.session.date
        this.duration = this.session.duration_minutes ? String(this.session.duration_minutes) : ''
        this.calories = this.session.calories != null ? String(this.session.calories) : ''
        this.notes = this.session.notes || ''
        this.lines = (this.session.exercises || []).map(item => ({
          key: lineKey++,
          exercise_id: item.exercise_id,
          sets: (item.sets || []).map(set => ({
            reps: String(set.reps ?? ''),
            weight: set.weight == null ? '' : String(set.weight),
          })),
        }))

        return
      }

      this.date = iso
      this.duration = ''
      this.calories = ''
      this.notes = ''
      this.lines = []
      if (this.exercises.length)
        this.addExercise()
    },

    addExercise() {
      const first = this.exercises[0]
      const last = first?.last_weight != null ? String(first.last_weight) : ''
      const lastReps = first?.last_reps ? String(first.last_reps) : '10'

      this.lines.push({
        key: lineKey++,
        exercise_id: first?.id || null,
        sets: [
          { reps: lastReps, weight: last },
          { reps: lastReps, weight: last },
          { reps: lastReps, weight: last },
        ],
      })
    },

    removeExercise(index) {
      this.lines.splice(index, 1)
    },

    addSet(lineIndex) {
      const sets = this.lines[lineIndex].sets
      const prev = sets[sets.length - 1] || emptySet()
      sets.push({ reps: prev.reps, weight: prev.weight })
    },

    removeSet(lineIndex, setIndex) {
      const sets = this.lines[lineIndex].sets
      if (sets.length <= 1)
        return
      sets.splice(setIndex, 1)
    },

    save() {
      if (!this.date) {
        this.$toast.error('Indicá la fecha')

        return
      }

      if (!this.lines.length) {
        this.$toast.error('Agregá al menos un ejercicio')

        return
      }

      const exercises = []

      for (const line of this.lines) {
        if (!line.exercise_id) {
          this.$toast.error('Elegí un ejercicio en cada fila')

          return
        }

        const sets = []
        for (const set of line.sets) {
          const reps = Number(set.reps)
          if (!reps || reps < 1) {
            this.$toast.error('Cada serie necesita repeticiones')

            return
          }

          const weight = set.weight === '' || set.weight == null
            ? null
            : Number(set.weight)

          if (weight != null && Number.isNaN(weight)) {
            this.$toast.error('Hay un peso inválido')

            return
          }

          sets.push({ reps, weight })
        }

        exercises.push({
          exercise_id: line.exercise_id,
          sets,
        })
      }

      const duration = this.duration === '' ? null : Number(this.duration)
      const calories = this.calories === '' ? null : Number(this.calories)

      this.$emit('save', {
        date: this.date,
        duration_minutes: duration || null,
        calories: calories == null || Number.isNaN(calories) ? null : calories,
        notes: this.notes.trim() || null,
        exercises,
      })
    },
  },
}
</script>

<style scoped>
.training-session-line {
  padding: 12px 0;
  border-top: thin solid rgba(var(--v-border-color), var(--v-border-opacity));
}

.training-session-set {
  display: grid;
  grid-template-columns: 2rem minmax(0, 1fr) minmax(0, 1fr) 2rem;
  gap: 0.5rem;
  align-items: center;
  margin-top: 0.5rem;
}

.training-session-set__n {
  text-align: center;
}
</style>
