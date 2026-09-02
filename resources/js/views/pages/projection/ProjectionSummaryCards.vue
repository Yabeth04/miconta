<template>
  <VRow class="mb-4">
    <VCol
      v-for="card in cards"
      :key="card.title"
      cols="12"
      sm="6"
      lg="3"
    >
      <VCard
        rounded="lg"
        :loading="loading"
      >
        <VCardText class="d-flex align-center gap-4">
          <VAvatar
            :color="card.color"
            variant="tonal"
            size="48"
            rounded="lg"
          >
            <VIcon
              :icon="card.icon"
              size="24"
            />
          </VAvatar>
          <div class="min-w-0">
            <p class="text-caption text-medium-emphasis mb-1 d-inline-flex align-center gap-1">
              {{ card.title }}
              <VTooltip
                v-if="card.tooltip"
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
                <span>{{ card.tooltip }}</span>
              </VTooltip>
            </p>
            <p
              class="text-h5 font-weight-semibold mb-0 projection__num"
              :class="card.valueClass"
            >
              {{ card.value }}
            </p>
            <p class="text-caption text-medium-emphasis mb-0 mt-1">
              {{ card.subtitle }}
            </p>
          </div>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
</template>

<script>
export default {
  name: 'ProjectionSummaryCards',

  props: {
    loading: {
      type: Boolean,
      default: false,
    },
    summary: {
      type: Object,
      default: null,
    },
    sources: {
      type: Object,
      default: () => ({}),
    },
  },

  computed: {
    cards() {
      const s = this.summary || {
        total_salary_in: 0,
        total_expenses_out: 0,
        total_delta: 0,
        ending_balance: 0,
        free_months_count: 0,
      }

      return [
        {
          title: 'Saldo al final',
          value: this.$formatAmount(s.ending_balance),
          subtitle: `Hoy ${this.$formatAmount(this.sources.anchor_balance || this.sources.account_balance)}`,
          tooltip: 'Cierre del rango. La base del mes se arma desde el saldo de hoy para proyectar quincenas completas.',
          icon: 'ri-wallet-3-line',
          color: 'primary',
          valueClass: 'text-primary',
        },
        {
          title: 'Salario proyectado',
          value: this.$formatAmount(s.total_salary_in),
          subtitle: `Quincena ${this.$formatAmount((this.sources.monthly_salary || 0) / 2)}`,
          tooltip: 'Suma del salario de ambos pagos (1 y 15) en todos los meses del rango. Viene de Pagos fijos.',
          icon: 'ri-money-dollar-circle-line',
          color: 'info',
          valueClass: '',
        },
        {
          title: 'Gastos proyectados',
          value: this.$formatAmount(s.total_expenses_out),
          subtitle: `${s.free_months_count} mes(es) sin U`,
          tooltip: 'Suma de pagos fijos del rango (sin cuota U en meses libres).',
          icon: 'ri-bill-line',
          color: 'error',
          valueClass: '',
        },
        {
          title: 'Δ total',
          value: this.$formatAmount(s.total_delta),
          subtitle: 'Ingresos − gastos',
          tooltip: 'Salario proyectado − gastos proyectados en todo el rango.',
          icon: 'ri-line-chart-line',
          color: 'warning',
          valueClass: 'projection__delta',
        },
      ]
    },
  },
}
</script>

<style scoped>
.projection__num {
  font-variant-numeric: tabular-nums;
  font-feature-settings: 'tnum';
}

.projection__delta {
  color: color-mix(in srgb, rgb(var(--v-theme-primary)) 70%, rgb(var(--v-theme-on-surface)) 30%);
}
</style>
