<template>
  <div>
    <div
      v-for="group in groups"
      :key="group.key"
      class="training-group"
    >
      <p class="training-group__title">
        {{ group.label }}
      </p>
      <div
        v-for="item in group.exercises"
        :key="item.id || item.exercise_id"
        class="training-group__item"
      >
        <p class="training-group__name mb-0">
          {{ item.name }}
        </p>
        <p
          v-if="item.cue"
          class="training-group__cue mb-0"
        >
          {{ item.cue }}
        </p>
        <p class="training-group__summary mb-0">
          {{ item.summary }}
        </p>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'TrainingGroupedList',

  props: {
    groups: { type: Array, default: () => [] },
  },
}
</script>

<style scoped>
.training-group + .training-group {
  margin-top: 1.1rem;
}

.training-group__title {
  margin: 0 0 0.5rem;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: rgba(var(--v-theme-on-surface), 0.55);
}

.training-group__item + .training-group__item {
  margin-top: 0.75rem;
}

.training-group__name {
  font-weight: 600;
  font-size: 0.95rem;
}

.training-group__cue {
  font-size: 12px;
  color: rgba(var(--v-theme-on-surface), 0.55);
}

.training-group__summary {
  margin-top: 0.15rem;
  font-size: 0.875rem;
  color: rgba(var(--v-theme-on-surface), 0.72);
}
</style>
