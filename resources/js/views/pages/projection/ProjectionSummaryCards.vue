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
    projectionMode: {
      type: String,
      required: true,
    },
    summary: {
      type: Object,
      default: null,
    },
    startingBalance: {
      type: Number,
      default: 0,
    },
    sources: {
      type: Object,
      default: () => ({}),
    },
  },

  computed: {
    cards() {
      const s = this.summary || {
        total_monthly_remaining: 0,
        total_university_freed: 0,
        total_salary_in: 0,
        total_expenses_out: 0,
        total_delta: 0,
        ending_balance: 0,
        free_months_count: 0,
        payment_months_count: 0,
      }

      if (this.projectionMode === 'real') {
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
      }

      return [
        {
          title: 'Saldo al final',
          value: this.$formatAmount(s.ending_balance),
          subtitle: `Partiendo de ${this.$formatAmount(this.startingBalance)}`,
          tooltip: 'Saldo inicial + suma de “queda” + liberado por U en el rango.',
          icon: 'ri-wallet-3-line',
          color: 'primary',
          valueClass: 'text-primary',
        },
        {
          title: 'Suma de “quedan libres al mes”',
          value: this.$formatAmount(s.total_monthly_remaining),
          subtitle: `${s.payment_months_count + s.free_months_count} meses`,
          tooltip: '“Queda al mes” × cantidad de meses del rango (salario − fijos, o el monto guardado).',
          icon: 'ri-stack-line',
          color: 'info',
          valueClass: '',
        },
        {
          title: 'Liberado por U',
          value: this.$formatAmount(s.total_university_freed),
          subtitle: `${s.free_months_count} mes(es) sin pago`,
          tooltip: 'Cuota U × meses sin pago. Esa plata se queda en la cuenta.',
          icon: 'ri-gift-line',
          color: 'success',
          valueClass: 'projection__freed',
        },
        {
          title: 'Total proyectado',
          value: this.$formatAmount(s.total_delta),
          subtitle: 'Queda + liberado U',
          tooltip: 'Suma de lo que queda cada mes más lo liberado en meses sin U.',
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

.projection__freed {
  color: color-mix(in srgb, rgb(var(--v-theme-success)) 78%, rgb(var(--v-theme-on-surface)) 22%);
}

.projection__delta {
  color: color-mix(in srgb, rgb(var(--v-theme-primary)) 70%, rgb(var(--v-theme-on-surface)) 30%);
}
</style>
