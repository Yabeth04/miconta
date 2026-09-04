<template>
  <svg
    v-if="isCardio"
    class="muscle-icon muscle-icon--heart"
    :class="{ 'muscle-icon--lg': size === 'lg' }"
    viewBox="0 0 24 24"
    xmlns="http://www.w3.org/2000/svg"
    aria-hidden="true"
  >
    <path
      class="muscle-icon__heart"
      d="M12 21s-6.7-4.35-9.33-7.7C.7 10.9 1.1 7.2 3.6 5.5 5.5 4.2 8 4.5 9.5 6.2L12 9l2.5-2.8c1.5-1.7 4-2 5.9-.7 2.5 1.7 2.9 5.4.93 7.8C18.7 16.65 12 21 12 21z"
    />
  </svg>

  <svg
    v-else
    class="muscle-icon"
    :class="{ 'muscle-icon--lg': size === 'lg' }"
    viewBox="0 0 200 420"
    xmlns="http://www.w3.org/2000/svg"
    aria-hidden="true"
  >
    <ellipse
      class="muscle-icon__base"
      cx="100"
      cy="28"
      rx="22"
      ry="26"
    />
    <path
      class="muscle-icon__base"
      d="M78 52 C78 70 86 78 100 78 C114 78 122 70 122 52 Z"
    />

    <template v-if="side === 'front'">
      <path
        class="muscle-icon__zone"
        :class="{ 'muscle-icon__zone--on': group === 'Hombros' }"
        d="M58 88 C48 92 42 104 46 118 L70 112 C68 100 66 92 58 88 Z M142 88 C152 92 158 104 154 118 L130 112 C132 100 134 92 142 88 Z"
      />
      <path
        class="muscle-icon__zone"
        :class="{ 'muscle-icon__zone--on': group === 'Pecho' }"
        d="M70 96 C78 90 122 90 130 96 C136 110 134 132 120 138 C110 142 90 142 80 138 C66 132 64 110 70 96 Z"
      />
      <path
        class="muscle-icon__zone"
        :class="{ 'muscle-icon__zone--on': group === 'Bíceps' }"
        d="M42 120 C34 128 32 150 36 168 L54 162 C52 146 52 130 42 120 Z M158 120 C166 128 168 150 164 168 L146 162 C148 146 148 130 158 120 Z"
      />
      <path
        class="muscle-icon__zone"
        :class="{ 'muscle-icon__zone--on': group === 'Tríceps' }"
        d="M54 124 C48 132 46 152 48 166 L40 168 C38 150 40 130 54 124 Z M146 124 C152 132 154 152 152 166 L160 168 C162 150 160 130 146 124 Z"
      />
      <path
        class="muscle-icon__zone"
        :class="{ 'muscle-icon__zone--on': group === 'Antebrazo' }"
        d="M36 170 C30 188 28 210 34 228 L50 222 C48 206 48 186 36 170 Z M164 170 C170 188 172 210 166 228 L150 222 C152 206 152 186 164 170 Z"
      />
      <path
        class="muscle-icon__zone"
        :class="{ 'muscle-icon__zone--on': group === 'Abdomen' }"
        d="M78 140 C88 138 112 138 122 140 C126 168 124 198 118 210 C110 218 90 218 82 210 C76 198 74 168 78 140 Z"
      />
      <path
        class="muscle-icon__zone"
        :class="{ 'muscle-icon__zone--on': group === 'Piernas' }"
        d="M78 214 C86 214 96 216 98 250 L98 330 C96 350 90 370 86 390 L70 386 C72 360 74 330 72 280 C70 240 72 216 78 214 Z M122 214 C114 214 104 216 102 250 L102 330 C104 350 110 370 114 390 L130 386 C128 360 126 330 128 280 C130 240 128 216 122 214 Z"
      />
    </template>

    <template v-else>
      <path
        class="muscle-icon__zone"
        :class="{ 'muscle-icon__zone--on': group === 'Hombros' }"
        d="M58 88 C48 92 42 104 46 118 L70 112 C68 100 66 92 58 88 Z M142 88 C152 92 158 104 154 118 L130 112 C132 100 134 92 142 88 Z"
      />
      <path
        class="muscle-icon__zone"
        :class="{ 'muscle-icon__zone--on': group === 'Espalda' }"
        d="M70 96 C78 90 122 90 130 96 C138 120 136 160 122 188 C112 200 88 200 78 188 C64 160 62 120 70 96 Z"
      />
      <path
        class="muscle-icon__zone"
        :class="{ 'muscle-icon__zone--on': group === 'Tríceps' }"
        d="M42 120 C34 128 32 150 36 168 L54 162 C52 146 52 130 42 120 Z M158 120 C166 128 168 150 164 168 L146 162 C148 146 148 130 158 120 Z"
      />
      <path
        class="muscle-icon__zone"
        :class="{ 'muscle-icon__zone--on': group === 'Antebrazo' }"
        d="M36 170 C30 188 28 210 34 228 L50 222 C48 206 48 186 36 170 Z M164 170 C170 188 172 210 166 228 L150 222 C152 206 152 186 164 170 Z"
      />
      <path
        class="muscle-icon__zone"
        :class="{ 'muscle-icon__zone--on': group === 'Piernas' }"
        d="M78 200 C86 198 96 204 98 240 L98 330 C96 350 90 370 86 390 L70 386 C72 360 74 330 72 270 C70 230 72 204 78 200 Z M122 200 C114 198 104 204 102 240 L102 330 C104 350 110 370 114 390 L130 386 C128 360 126 330 128 270 C130 230 128 204 122 200 Z"
      />
    </template>
  </svg>
</template>

<script>
const BACK_GROUPS = new Set(['Espalda', 'Tríceps'])

export default {
  name: 'MuscleGroupIcon',

  props: {
    group: { type: String, default: null },
    size: { type: String, default: 'sm' },
  },

  computed: {
    isCardio() {
      return this.group === 'Cardio'
    },

    side() {
      return BACK_GROUPS.has(this.group) ? 'back' : 'front'
    },
  },
}
</script>

<style scoped>
.muscle-icon {
  width: 22px;
  height: auto;
  flex-shrink: 0;
  display: block;
  vertical-align: middle;
}

.muscle-icon--heart {
  width: 16px;
  height: 16px;
}

.muscle-icon--lg {
  width: 28px;
}

.muscle-icon--heart.muscle-icon--lg {
  width: 20px;
  height: 20px;
}

.muscle-icon__base {
  fill: rgba(var(--v-theme-on-surface), 0.1);
}

.muscle-icon__zone {
  fill: rgba(var(--v-theme-on-surface), 0.14);
}

.muscle-icon__zone--on {
  fill: rgba(var(--v-theme-primary), 0.85);
}

.muscle-icon__heart {
  fill: rgba(var(--v-theme-primary), 0.85);
}
</style>
