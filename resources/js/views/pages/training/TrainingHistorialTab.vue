<template>
  <div>
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
        @click="$emit('register')"
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
              @click="$emit('open-session', session)"
            >
              <VIcon icon="ri-pencil-line" />
            </VBtn>
            <VBtn
              icon
              variant="text"
              size="small"
              @click="askDeleteSession(session)"
            >
              <VIcon icon="ri-delete-bin-line" />
            </VBtn>
          </div>
        </div>
      </div>
    </VCard>

    <VDialog
      v-model="deleteDialog"
      max-width="400"
    >
      <VCard rounded="lg">
        <VCardTitle class="text-h6">
          Eliminar sesión
        </VCardTitle>
        <VCardText class="text-body-2 pt-2">
          ¿Eliminar esta sesión? No se puede deshacer.
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
            @click="confirmDeleteSession"
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
import { formatDuration, formatSessionDate, muscleSummary } from '@/utils/trainingFormat'

export default {
  name: 'TrainingHistorialTab',

  props: {
    loading: { type: Boolean, default: false },
    summary: {
      type: Object,
      default: () => ({ week_sessions: 0, week_minutes: 0, week_calories: 0 }),
    },
    sessions: { type: Array, default: () => [] },
  },

  emits: ['refresh', 'error', 'register', 'open-session'],

  data() {
    return {
      deleteDialog: false,
      deleteTarget: null,
      deleting: false,
    }
  },

  methods: {
    formatDuration,
    formatSessionDate,
    muscleSummary,

    askDeleteSession(session) {
      this.deleteTarget = session
      this.deleteDialog = true
    },

    confirmDeleteSession() {
      if (!this.deleteTarget || this.deleting)
        return

      this.deleting = true
      axios.delete(`/api/training/sessions/${this.deleteTarget.id}`)
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
</style>
