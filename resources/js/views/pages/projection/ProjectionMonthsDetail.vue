<template>
  <VCard
    rounded="lg"
    class="projection-months-card overflow-hidden"
    :loading="loading"
  >
    <VCardItem>
      <VCardTitle class="text-h6">
        Detalle mes a mes
      </VCardTitle>
      <VCardSubtitle class="text-body-2">
        {{ periodLabel }}
      </VCardSubtitle>
    </VCardItem>

    <VDivider />

    <div
      v-if="!loading && !months.length"
      class="text-center py-10 text-medium-emphasis"
    >
      Sin meses en el rango elegido
    </div>

    <!-- Móvil: cards por mes -->
    <div
      v-else-if="months.length && mdAndDown"
      class="projection-month-shell"
    >
      <div
        ref="monthScroll"
        class="projection-month-list"
        @scroll="onMonthScroll"
      >
        <div
          v-for="row in months"
          :key="`${row.year}-${row.month}`"
          class="projection-month-card"
          :class="{ 'projection-month-card--free': !row.pays_university }"
        >
        <div class="projection-month-card__head">
          <span class="text-body-1 font-weight-semibold">
            {{ row.label }}
          </span>
          <VChip
            size="small"
            rounded="lg"
            :color="row.pays_university ? 'info' : 'success'"
            variant="tonal"
          >
            {{ row.kind_label }}
          </VChip>
        </div>

        <div class="projection-month-card__grid">
          <div class="projection-month-card__cell">
            <span class="text-caption text-medium-emphasis d-inline-flex align-center gap-1">
              Salario in
              <VTooltip
                location="top"
                max-width="320"
              >
                <template #activator="{ props: tipProps }">
                  <VIcon
                    v-bind="tipProps"
                    icon="ri-information-line"
                    size="14"
                    class="text-medium-emphasis"
                  />
                </template>
                <span>{{ salaryInTooltip(row) }}</span>
              </VTooltip>
            </span>
            <span class="text-body-2 projection__num">
              {{ $formatAmount(row.salary_in) }}
            </span>
          </div>
          <div class="projection-month-card__cell">
            <span class="text-caption text-medium-emphasis d-inline-flex align-center gap-1">
              Gastos out
              <VTooltip
                location="top"
                max-width="280"
              >
                <template #activator="{ props: tipProps }">
                  <VIcon
                    v-bind="tipProps"
                    icon="ri-information-line"
                    size="14"
                    class="text-medium-emphasis"
                  />
                </template>
                <span>{{ expensesOutTooltip(row) }}</span>
              </VTooltip>
            </span>
            <span class="text-body-2 projection__num">
              {{ $formatAmount(row.expenses_out) }}
            </span>
          </div>
          <div class="projection-month-card__cell">
            <span class="text-caption text-medium-emphasis d-inline-flex align-center gap-1">
              Δ mes
              <VTooltip
                location="top"
                max-width="280"
              >
                <template #activator="{ props: tipProps }">
                  <VIcon
                    v-bind="tipProps"
                    icon="ri-information-line"
                    size="14"
                    class="text-medium-emphasis"
                  />
                </template>
                <span>{{ columnTooltips.delta }}</span>
              </VTooltip>
            </span>
            <span class="text-body-2 projection__num projection__delta">
              {{ $formatAmount(row.delta) }}
            </span>
          </div>
          <div class="projection-month-card__cell">
            <span class="text-caption text-medium-emphasis d-inline-flex align-center gap-1">
              Saldo
              <VTooltip
                location="top"
                max-width="280"
              >
                <template #activator="{ props: tipProps }">
                  <VIcon
                    v-bind="tipProps"
                    icon="ri-information-line"
                    size="14"
                    class="text-medium-emphasis"
                  />
                </template>
                <span>{{ columnTooltips.balance }}</span>
              </VTooltip>
            </span>
            <span class="text-body-2 font-weight-semibold projection__num">
              {{ $formatAmount(row.balance) }}
            </span>
          </div>
        </div>
      </div>
      </div>

      <div
        v-if="canScrollMore"
        class="projection-scroll-cue"
        aria-hidden="true"
      >
        <span class="projection-scroll-cue__pill">
          Deslizá
          <VIcon
            icon="ri-arrow-down-s-line"
            size="16"
          />
        </span>
      </div>
    </div>

    <!-- Desktop: tabla con panel de scroll visible -->
    <div
      v-else-if="months.length"
      class="projection-table-shell"
    >
      <div
        ref="tableScroll"
        class="projection-table-scroll"
        @scroll="onTableScroll"
      >
        <VTable class="projection-table">
          <thead>
            <tr>
              <th class="text-left">
                Mes
              </th>
              <th class="text-left">
                Tipo
              </th>
              <th class="text-right">
                <span class="d-inline-flex align-center justify-end gap-1">
                  Salario
                  <VTooltip
                    location="top"
                    max-width="280"
                  >
                    <template #activator="{ props: tipProps }">
                      <VIcon
                        v-bind="tipProps"
                        icon="ri-information-line"
                        size="16"
                        class="text-medium-emphasis"
                      />
                    </template>
                    <span>{{ columnTooltips.salary }}</span>
                  </VTooltip>
                </span>
              </th>
              <th class="text-right">
                <span class="d-inline-flex align-center justify-end gap-1">
                  Gastos
                  <VTooltip
                    location="top"
                    max-width="280"
                  >
                    <template #activator="{ props: tipProps }">
                      <VIcon
                        v-bind="tipProps"
                        icon="ri-information-line"
                        size="16"
                        class="text-medium-emphasis"
                      />
                    </template>
                    <span>{{ columnTooltips.expenses }}</span>
                  </VTooltip>
                </span>
              </th>
              <th class="text-right">
                <span class="d-inline-flex align-center justify-end gap-1">
                  Δ mes
                  <VTooltip
                    location="top"
                    max-width="280"
                  >
                    <template #activator="{ props: tipProps }">
                      <VIcon
                        v-bind="tipProps"
                        icon="ri-information-line"
                        size="16"
                        class="text-medium-emphasis"
                      />
                    </template>
                    <span>{{ columnTooltips.delta }}</span>
                  </VTooltip>
                </span>
              </th>
              <th class="text-right">
                <span class="d-inline-flex align-center justify-end gap-1">
                  Saldo
                  <VTooltip
                    location="top"
                    max-width="280"
                  >
                    <template #activator="{ props: tipProps }">
                      <VIcon
                        v-bind="tipProps"
                        icon="ri-information-line"
                        size="16"
                        class="text-medium-emphasis"
                      />
                    </template>
                    <span>{{ columnTooltips.balance }}</span>
                  </VTooltip>
                </span>
              </th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="row in months"
              :key="`${row.year}-${row.month}`"
              :class="{ 'projection-table__row--free': !row.pays_university }"
            >
              <td class="font-weight-medium">
                {{ row.label }}
              </td>
              <td>
                <VChip
                  size="small"
                  rounded="lg"
                  :color="row.pays_university ? 'info' : 'success'"
                  variant="tonal"
                >
                  {{ row.kind_label }}
                </VChip>
              </td>
              <td class="text-right projection__num">
                <span class="d-inline-flex align-center justify-end gap-1">
                  <VTooltip
                    location="top"
                    max-width="320"
                  >
                    <template #activator="{ props: tipProps }">
                      <VIcon
                        v-bind="tipProps"
                        icon="ri-information-line"
                        size="16"
                        class="text-medium-emphasis"
                      />
                    </template>
                    <span>{{ salaryInTooltip(row) }}</span>
                  </VTooltip>
                  <span>{{ $formatAmount(row.salary_in) }}</span>
                </span>
              </td>
              <td class="text-right projection__num">
                <span class="d-inline-flex align-center justify-end gap-1">
                  <VTooltip
                    location="top"
                    max-width="280"
                  >
                    <template #activator="{ props: tipProps }">
                      <VIcon
                        v-bind="tipProps"
                        icon="ri-information-line"
                        size="16"
                        class="text-medium-emphasis"
                      />
                    </template>
                    <span>{{ expensesOutTooltip(row) }}</span>
                  </VTooltip>
                  <span>{{ $formatAmount(row.expenses_out) }}</span>
                </span>
              </td>
              <td class="text-right projection__num projection__delta">
                {{ $formatAmount(row.delta) }}
              </td>
              <td class="text-right projection__num font-weight-semibold">
                {{ $formatAmount(row.balance) }}
              </td>
            </tr>
          </tbody>
        </VTable>
      </div>

      <div
        v-if="canScrollMore"
        class="projection-scroll-cue"
        aria-hidden="true"
      >
        <span class="projection-scroll-cue__pill">
          Deslizá
          <VIcon
            icon="ri-arrow-down-s-line"
            size="16"
          />
        </span>
      </div>
    </div>

    <VCardText
      v-if="hasSummary"
      class="pt-4 text-body-2 text-medium-emphasis"
    >
      Proyecta desde el mes actual hacia adelante (1 y 15): salario quincenal y pagos fijos.
      El saldo inicial es el de hoy; el mes en curso se arma completo desde el 1.
      En meses sin U no descuenta la cuota.
    </VCardText>
  </VCard>
</template>

<script>
import { useDisplay } from 'vuetify';

export default {
  name: 'ProjectionMonthsDetail',

  props: {
    loading: {
      type: Boolean,
      default: false,
    },
    months: {
      type: Array,
      default: () => [],
    },
    periodLabel: {
      type: String,
      default: '',
    },
    hasSummary: {
      type: Boolean,
      default: false,
    },
  },

  setup() {
    const { mdAndDown } = useDisplay()

    return { mdAndDown }
  },

  data() {
    return {
      canScrollMore: false,
    }
  },

  computed: {
    columnTooltips() {
      return {
        salary: 'Salario del mes completo: quincena del 1 + quincena del 15.',
        expenses: 'Pagos fijos del mes (primero + segundo). En meses sin U no cuenta la cuota de universidad.',
        delta: 'Salario del mes − gastos del mes.',
        balance: 'Saldo al cierre del mes tras ambas quincenas.',
      }
    },
  },

  watch: {
    months: {
      immediate: true,
      handler() {
        this.$nextTick(() => this.updateScrollCue())
      },
    },

    mdAndDown() {
      this.$nextTick(() => this.updateScrollCue())
    },
  },

  mounted() {
    this.updateScrollCue()
    window.addEventListener('resize', this.updateScrollCue)
  },

  beforeUnmount() {
    window.removeEventListener('resize', this.updateScrollCue)
  },

  methods: {
    onTableScroll() {
      this.updateScrollCue()
    },

    onMonthScroll() {
      this.updateScrollCue()
    },

    updateScrollCue() {
      const el = this.mdAndDown ? this.$refs.monthScroll : this.$refs.tableScroll
      if (!el) {
        this.canScrollMore = false

        return
      }

      const remaining = el.scrollHeight - el.scrollTop - el.clientHeight
      this.canScrollMore = remaining > 8
    },

    salaryInTooltip(row) {
      return `Salario del mes: ${this.$formatAmount(row.salary_in)} (quincenas del 1 y del 15).`
    },

    expensesOutTooltip(row) {
      const p = this.$formatAmount(row?.primero?.expense || 0)
      const s = this.$formatAmount(row?.segundo?.expense || 0)
      const freed = row.university_freed > 0
        ? ` Sin U este mes (−${this.$formatAmount(row.university_freed)}).`
        : ''

      return `Gastos del mes: ${this.$formatAmount(row.expenses_out)} = primero ${p} + segundo ${s}.${freed}`
    },
  },
}
</script>

<style scoped>
.projection-months-card {
  border-color: rgba(var(--v-theme-on-surface), 0.08);
}

.projection__num {
  font-variant-numeric: tabular-nums;
  font-feature-settings: 'tnum';
}

.projection__delta {
  color: color-mix(in srgb, rgb(var(--v-theme-primary)) 70%, rgb(var(--v-theme-on-surface)) 30%);
}

.projection-table__row--free {
  background: color-mix(in srgb, rgb(var(--v-theme-success)) 6%, rgb(var(--v-theme-surface)) 94%);
}

.projection-table :deep(th),
.projection-table :deep(td) {
  white-space: nowrap;
}

.projection-table-shell {
  position: relative;
  margin: 0 16px 12px;
}

.projection-table-scroll {
  position: relative;
  max-height: min(480px, 58vh);
  overflow-y: auto;
  overflow-x: auto;
  border: thin solid rgba(var(--v-border-color), var(--v-border-opacity));
  border-radius: 12px;
  background: rgb(var(--v-theme-surface));
  scrollbar-gutter: stable;
}

.projection-table-scroll__cue,
.projection-scroll-cue {
  position: absolute;
  inset-inline: 0;
  bottom: 0.55rem;
  z-index: 3;
  display: flex;
  justify-content: center;
  pointer-events: none;
}

.projection-table-scroll__cue-pill,
.projection-scroll-cue__pill {
  display: inline-flex;
  align-items: center;
  gap: 0.15rem;
  padding: 0.35rem 0.8rem;
  border-radius: 999px;
  border: thin solid rgba(var(--v-border-color), var(--v-border-opacity));
  background: color-mix(in srgb, rgb(var(--v-theme-surface)) 86%, rgb(var(--v-theme-primary)) 14%);
  color: rgb(var(--v-theme-primary));
  font-size: 0.75rem;
  font-weight: 650;
  box-shadow: 0 4px 14px rgba(0, 0, 0, 0.16);
  animation: projection-scroll-bounce 1.4s ease-in-out infinite;
}

@keyframes projection-scroll-bounce {
  0%,
  100% {
    transform: translateY(0);
  }

  50% {
    transform: translateY(3px);
  }
}

.projection-table-scroll::-webkit-scrollbar {
  width: 10px;
}

.projection-table-scroll::-webkit-scrollbar-thumb {
  border-radius: 999px;
  background: rgba(var(--v-theme-on-surface), 0.28);
  border: 2px solid transparent;
  background-clip: padding-box;
}

.projection-table-scroll::-webkit-scrollbar-track {
  background: rgba(var(--v-theme-on-surface), 0.06);
  border-radius: 999px;
}

.projection-table :deep(.v-table__wrapper) {
  max-height: none;
  overflow: visible;
}

.projection-table :deep(thead th) {
  position: sticky;
  top: 0;
  z-index: 1;
  background: rgb(var(--v-theme-surface));
  box-shadow: 0 1px 0 rgba(var(--v-border-color), var(--v-border-opacity));
}

.projection-month-shell {
  position: relative;
  margin: 0 12px 12px;
}

.projection-month-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
  padding: 12px;
  max-height: min(520px, 65vh);
  overflow-y: auto;
  border: thin solid rgba(var(--v-border-color), var(--v-border-opacity));
  border-radius: 12px;
  background: color-mix(in srgb, rgb(var(--v-theme-on-surface)) 3%, rgb(var(--v-theme-surface)));
  scrollbar-gutter: stable;
}

.projection-month-list::-webkit-scrollbar {
  width: 8px;
}

.projection-month-list::-webkit-scrollbar-thumb {
  border-radius: 999px;
  background: rgba(var(--v-theme-on-surface), 0.28);
  border: 2px solid transparent;
  background-clip: padding-box;
}

.projection-month-card {
  padding: 14px;
  border: thin solid rgba(var(--v-border-color), var(--v-border-opacity));
  border-radius: 12px;
  background: rgb(var(--v-theme-surface));
  flex-shrink: 0;
}

.projection-month-card--free {
  border-color: color-mix(in srgb, rgb(var(--v-theme-success)) 35%, rgba(var(--v-border-color), var(--v-border-opacity)));
  background: color-mix(in srgb, rgb(var(--v-theme-success)) 8%, rgb(var(--v-theme-surface)) 92%);
}

.projection-month-card__head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  margin-bottom: 12px;
}

.projection-month-card__grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px 12px;
}

.projection-month-card__cell {
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 0;
}

.projection-month-card__cell .projection__num {
  word-break: break-word;
}
</style>
