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
      class="projection-month-list"
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
          <template v-if="projectionMode === 'real'">
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
          </template>
          <template v-else>
            <div class="projection-month-card__cell">
              <span class="text-caption text-medium-emphasis d-inline-flex align-center gap-1">
                Queda
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
                  <span>{{ columnTooltips.remaining }}</span>
                </VTooltip>
              </span>
              <span class="text-body-2 projection__num">
                {{ $formatAmount(row.monthly_remaining) }}
              </span>
            </div>
            <div class="projection-month-card__cell">
              <span class="text-caption text-medium-emphasis d-inline-flex align-center gap-1">
                Libre U
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
                  <span>{{ columnTooltips.universityFreed }}</span>
                </VTooltip>
              </span>
              <span
                class="text-body-2 projection__num"
                :class="{ 'projection__freed': row.university_freed > 0 }"
              >
                {{ $formatAmount(row.university_freed) }}
              </span>
            </div>
          </template>
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

    <!-- Desktop: tabla -->
    <VTable
      v-else-if="months.length"
      class="projection-table"
    >
      <thead>
        <tr>
          <th class="text-left">
            Mes
          </th>
          <th class="text-left">
            Tipo
          </th>
          <th
            v-if="projectionMode === 'real'"
            class="text-right"
          >
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
          <th
            v-if="projectionMode === 'real'"
            class="text-right"
          >
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
          <th
            v-if="projectionMode === 'fixed'"
            class="text-right"
          >
            <span class="d-inline-flex align-center justify-end gap-1">
              Queda
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
                <span>{{ columnTooltips.remaining }}</span>
              </VTooltip>
            </span>
          </th>
          <th
            v-if="projectionMode === 'fixed'"
            class="text-right"
          >
            <span class="d-inline-flex align-center justify-end gap-1">
              Libre U
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
                <span>{{ columnTooltips.universityFreed }}</span>
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
          <td
            v-if="projectionMode === 'real'"
            class="text-right projection__num"
          >
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
          <td
            v-if="projectionMode === 'real'"
            class="text-right projection__num"
          >
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
          <td
            v-if="projectionMode === 'fixed'"
            class="text-right projection__num"
          >
            {{ $formatAmount(row.monthly_remaining) }}
          </td>
          <td
            v-if="projectionMode === 'fixed'"
            class="text-right projection__num"
          >
            <span :class="{ 'projection__freed': row.university_freed > 0 }">
              {{ $formatAmount(row.university_freed) }}
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

    <VCardText
      v-if="hasSummary"
      class="pt-4 text-body-2 text-medium-emphasis"
    >
      <template v-if="projectionMode === 'real'">
        Proyecta cada mes completo (1 y 15): salario quincenal y pagos fijos.
        El saldo inicial se apoya en el de hoy y recrea el mes desde el 1 para no dejar filas a medias.
        En meses sin U no descuenta la cuota.
      </template>
      <template v-else>
        En meses con pago U solo suma lo que te queda.
        En meses sin pago U también suma la cuota ({{ $formatAmount(universityFee) }}),
        porque esa plata se queda en la cuenta.
      </template>
    </VCardText>
  </VCard>
</template>

<script>
import { useDisplay } from 'vuetify'

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
    projectionMode: {
      type: String,
      required: true,
    },
    universityFee: {
      type: [Number, String],
      default: 0,
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

  computed: {
    columnTooltips() {
      if (this.projectionMode === 'real') {
        return {
          salary: 'Salario del mes completo: quincena del 1 + quincena del 15.',
          expenses: 'Pagos fijos del mes (primero + segundo). En meses sin U no cuenta la cuota de universidad.',
          delta: 'Salario del mes − gastos del mes.',
          balance: 'Saldo al cierre del mes tras ambas quincenas.',
        }
      }

      return {
        remaining: 'Monto “queda al mes” (salario − pagos fijos, o el valor guardado en proyección).',
        universityFreed: 'En meses sin pago U se suma la cuota, porque esa plata no sale de la cuenta.',
        delta: 'Queda al mes + liberado por U (si el mes es libre).',
        balance: 'Saldo acumulado partiendo del saldo inicial + Δ de cada mes.',
      }
    },
  },

  methods: {
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

.projection__freed {
  color: color-mix(in srgb, rgb(var(--v-theme-success)) 78%, rgb(var(--v-theme-on-surface)) 22%);
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

/* Ventana con scroll (como movimientos): crece hasta el tope y scrollea adentro */
.projection-table :deep(.v-table__wrapper) {
  max-height: min(410px, 55vh);
  overflow-y: auto;
}

.projection-table :deep(thead th) {
  position: sticky;
  top: 0;
  z-index: 1;
  background: rgb(var(--v-theme-surface));
}

.projection-month-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
  padding: 12px 16px 4px;
  max-height: min(520px, 65vh);
  overflow-y: auto;
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
