<template>
  <div>
    <!-- Encabezado -->
    <div class="d-flex flex-wrap align-center justify-space-between gap-3 mb-6">
      <div>
        <h1 class="text-h4 font-weight-medium mb-1">
          {{ greeting }}<span v-if="userName">, {{ userName }}</span>
        </h1>
        <p class="text-body-2 text-medium-emphasis mb-0">
          Resumen de tu contabilidad
        </p>
      </div>
      <VBtn
        color="primary"
        variant="tonal"
        rounded="lg"
        prepend-icon="ri-add-line"
        to="/contabilidad"
      >
        Nuevo movimiento
      </VBtn>
    </div>

    <VAlert
      v-if="error"
      type="error"
      variant="tonal"
      rounded="lg"
      class="mb-4"
    >
      {{ error }}
    </VAlert>

    <!-- KPI cards -->
    <VRow class="mb-2">
      <VCol
        v-for="card in kpiCards"
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
              <p class="text-caption text-medium-emphasis mb-1">
                {{ card.title }}
              </p>
              <p
                class="text-h5 font-weight-semibold mb-0 dashboard-kpi__value"
                :class="{
                  'text-primary': card.color === 'primary',
                  'accounting-amount--haber': card.color === 'success',
                  'accounting-amount--debe': card.color === 'error',
                }"
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

    <!-- Gráficas principales -->
    <VRow class="mb-2">
      <VCol
        cols="12"
        lg="8"
      >
        <VCard
          rounded="lg"
          :loading="loading"
        >
          <VCardItem>
            <VCardTitle class="text-h6">
              Ingresos vs gastos
            </VCardTitle>
            <VCardSubtitle class="text-body-2">
              Últimos 6 meses
            </VCardSubtitle>
          </VCardItem>
          <VCardText>
            <div
              v-if="!loading && !hasMonthlyData"
              class="dashboard-empty text-center py-10 text-medium-emphasis"
            >
              <VIcon
                icon="ri-bar-chart-grouped-line"
                size="40"
                class="mb-3 opacity-50"
              />
              <p class="text-body-2 mb-0">
                Sin movimientos en los últimos meses
              </p>
            </div>
            <VueApexCharts
              v-else-if="hasMonthlyData"
              type="bar"
              height="300"
              :options="monthlyChartOptions"
              :series="monthlyChartSeries"
            />
          </VCardText>
        </VCard>
      </VCol>

      <VCol
        cols="12"
        lg="4"
      >
        <VCard
          rounded="lg"
          class="h-100"
          :loading="loading"
        >
          <VCardItem>
            <VCardTitle class="text-h6">
              Por método de pago
            </VCardTitle>
            <VCardSubtitle class="text-body-2">
              Distribución total
            </VCardSubtitle>
          </VCardItem>
          <VCardText class="d-flex align-center justify-center">
            <div
              v-if="!loading && !hasPaymentData"
              class="dashboard-empty text-center py-10 text-medium-emphasis"
            >
              <VIcon
                icon="ri-pie-chart-line"
                size="40"
                class="mb-3 opacity-50"
              />
              <p class="text-body-2 mb-0">
                Sin datos de pago
              </p>
            </div>
            <VueApexCharts
              v-else-if="hasPaymentData"
              type="donut"
              height="300"
              :options="paymentChartOptions"
              :series="paymentChartSeries"
            />
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- Saldo y conceptos -->
    <VRow class="mb-2">
      <VCol
        cols="12"
        lg="7"
      >
        <VCard
          rounded="lg"
          :loading="loading"
        >
          <VCardItem>
            <VCardTitle class="text-h6">
              Evolución del saldo
            </VCardTitle>
            <VCardSubtitle class="text-body-2">
              Saldo acumulado mes a mes
            </VCardSubtitle>
          </VCardItem>
          <VCardText>
            <div
              v-if="!loading && !hasMonthlyData"
              class="dashboard-empty text-center py-8 text-medium-emphasis"
            >
              <VIcon
                icon="ri-line-chart-line"
                size="40"
                class="mb-3 opacity-50"
              />
              <p class="text-body-2 mb-0">
                Sin historial de saldo
              </p>
            </div>
            <VueApexCharts
              v-else-if="hasMonthlyData"
              type="area"
              height="260"
              :options="balanceChartOptions"
              :series="balanceChartSeries"
            />
          </VCardText>
        </VCard>
      </VCol>

      <VCol
        cols="12"
        lg="5"
      >
        <VCard
          rounded="lg"
          class="h-100"
          :loading="loading"
        >
          <VCardItem>
            <VCardTitle class="text-h6">
              Conceptos del mes
            </VCardTitle>
            <VCardSubtitle class="text-body-2">
              Conceptos fijos
            </VCardSubtitle>
          </VCardItem>
          <VCardText>
            <div
              v-if="!loading && !hasConceptsData"
              class="dashboard-empty text-center py-8 text-medium-emphasis"
            >
              <VIcon
                icon="ri-price-tag-3-line"
                size="40"
                class="mb-3 opacity-50"
              />
              <p class="text-body-2 mb-3">
                No hay conceptos fijos configurados
              </p>
              <VBtn
                color="primary"
                variant="tonal"
                rounded="lg"
                size="small"
                to="/contabilidad/conceptos"
              >
                Configurar conceptos
              </VBtn>
            </div>
            <VueApexCharts
              v-else-if="hasConceptsData"
              type="bar"
              :height="conceptsChartHeight"
              :options="conceptsChartOptions"
              :series="conceptsChartSeries"
            />
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- Movimientos recientes + accesos rápidos -->
    <VRow>
      <VCol
        cols="12"
        :lg="auth.isSysAdmin && studyPlanSummary ? 8 : 12"
      >
        <VCard
          rounded="lg"
          :loading="loading"
        >
          <VCardItem class="pb-2">
            <VCardTitle class="text-h6">
              Movimientos recientes
            </VCardTitle>
            <template #append>
              <VBtn
                variant="text"
                size="small"
                rounded="lg"
                append-icon="ri-arrow-right-line"
                to="/contabilidad"
              >
                Ver todos
              </VBtn>
            </template>
          </VCardItem>

          <VCardText class="pt-0">
            <div
              v-if="!loading && !recentMovements.length"
              class="dashboard-empty text-center py-8 text-medium-emphasis"
            >
              <VIcon
                icon="ri-file-list-3-line"
                size="40"
                class="mb-3 opacity-50"
              />
              <p class="text-body-2 mb-3">
                Todavía no hay movimientos
              </p>
              <VBtn
                color="primary"
                variant="tonal"
                rounded="lg"
                size="small"
                to="/contabilidad"
              >
                Registrar el primero
              </VBtn>
            </div>

            <VList
              v-else
              class="dashboard-recent-list py-0"
              lines="two"
            >
              <VListItem
                v-for="item in recentMovements"
                :key="item.id"
                rounded="lg"
                class="px-2"
              >
                <template #prepend>
                  <VAvatar
                    :color="item.movement_type === 'haber' ? 'success' : 'error'"
                    variant="tonal"
                    size="40"
                    rounded="lg"
                  >
                    <VIcon
                      :icon="item.movement_type === 'haber' ? 'ri-arrow-up-line' : 'ri-arrow-down-line'"
                      size="20"
                    />
                  </VAvatar>
                </template>

                <VListItemTitle class="font-weight-medium">
                  {{ item.concept || 'Sin concepto' }}
                </VListItemTitle>
                <VListItemSubtitle>
                  {{ $formatDate(item.date) }}
                  · {{ movementTypeLabel(item.movement_type) }}
                  · {{ paymentTypeLabel(item.payment_type) }}
                </VListItemSubtitle>

                <template #append>
                  <span
                    class="text-body-2 font-weight-semibold dashboard-recent-list__amount"
                    :class="{
                      'accounting-amount--haber': item.movement_type === 'haber',
                      'accounting-amount--debe': item.movement_type === 'debe',
                    }"
                  >
                    {{ $formatAmount(item.amount) }}
                  </span>
                </template>
              </VListItem>
            </VList>
          </VCardText>
        </VCard>
      </VCol>

      <VCol
        v-if="auth.isSysAdmin && studyPlanSummary"
        cols="12"
        lg="4"
      >
        <VCard
          rounded="lg"
          class="h-100"
          :loading="loading"
        >
          <VCardItem>
            <VCardTitle class="text-h6">
              Plan de estudios
            </VCardTitle>
            <VCardSubtitle class="text-body-2">
              Progreso académico
            </VCardSubtitle>
          </VCardItem>
          <VCardText>
            <div class="d-flex align-center justify-space-between mb-3">
              <span class="text-h4 font-weight-semibold text-primary">
                {{ studyPlanProgress }}%
              </span>
              <span class="text-body-2 text-medium-emphasis">
                {{ studyPlanSummary.aprobadas }} / {{ studyPlanSummary.total }} materias
              </span>
            </div>
            <VProgressLinear
              :model-value="studyPlanProgress"
              color="primary"
              height="10"
              rounded
              class="mb-4"
            />
            <VBtn
              variant="tonal"
              color="primary"
              rounded="lg"
              block
              append-icon="ri-arrow-right-line"
              to="/plan-estudios"
            >
              Ver plan
            </VBtn>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- Accesos rápidos -->
    <VRow class="mt-2">
      <VCol cols="12">
        <VCard
          rounded="lg"
          variant="tonal"
          color="secondary"
        >
          <VCardText class="d-flex flex-wrap align-center gap-3 py-4">
            <span class="text-body-2 font-weight-medium me-auto">
              Accesos rápidos
            </span>
            <VBtn
              variant="outlined"
              rounded="lg"
              size="small"
              prepend-icon="ri-exchange-line"
              to="/contabilidad"
            >
              Movimientos
            </VBtn>
            <VBtn
              variant="outlined"
              rounded="lg"
              size="small"
              prepend-icon="ri-price-tag-3-line"
              to="/contabilidad/conceptos"
            >
              Conceptos
            </VBtn>
            <VBtn
              v-if="auth.isSysAdmin"
              variant="outlined"
              rounded="lg"
              size="small"
              prepend-icon="ri-graduation-cap-line"
              to="/plan-estudios"
            >
              Plan de estudios
            </VBtn>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </div>
</template>

<script>
import { axios } from '@/plugins/axios'
import { useAuthStore } from '@/stores/auth'
import { formatAmount } from '@core/utils/formatters'
import { useTheme } from 'vuetify'

export default {
  name: 'Dashboard',

  setup() {
    const auth = useAuthStore()
    const theme = useTheme()

    return { auth, theme }
  },

  data() {
    return {
      loading: true,
      error: null,
      totals: {
        debe: 0,
        haber: 0,
        count: 0,
        opening_balance: 0,
        account_balance: 0,
      },
      monthTotals: {
        debe: 0,
        haber: 0,
        count: 0,
      },
      monthly: [],
      paymentTypes: [],
      concepts: [],
      recentMovements: [],
      studyPlanSummary: null,
      paymentTypeLabels: {
        sinpe: 'Sinpe',
        efectivo: 'Efectivo',
        tarjeta: 'Tarjeta',
        transferencia: 'Transferencia',
        otros: 'Otros',
      },
      movementTypeLabels: {
        haber: 'Ingreso',
        debe: 'Gasto',
      },
    }
  },

  computed: {
    isDark() {
      return this.theme.global.current.value.dark
    },

    chartForeColor() {
      return this.isDark ? 'rgba(255,255,255,0.7)' : 'rgba(46,38,61,0.68)'
    },

    chartGridColor() {
      return this.isDark ? 'rgba(255,255,255,0.08)' : 'rgba(46,38,61,0.08)'
    },

    greeting() {
      const hour = new Date().getHours()
      if (hour < 12)
        return 'Buenos días'
      if (hour < 19)
        return 'Buenas tardes'

      return 'Buenas noches'
    },

    userName() {
      return this.auth.user?.name?.split(' ')[0] ?? ''
    },

    currentMonthLabel() {
      const label = new Date().toLocaleDateString('es-CR', { month: 'long', year: 'numeric' })

      return label.charAt(0).toUpperCase() + label.slice(1)
    },

    kpiCards() {
      return [
        {
          title: 'Saldo en cuenta',
          value: formatAmount(this.totals.account_balance),
          icon: 'ri-wallet-3-line',
          color: 'primary',
          subtitle: `Saldo inicial: ${formatAmount(this.totals.opening_balance)}`,
        },
        {
          title: 'Ingresos del mes',
          value: formatAmount(this.monthTotals.haber),
          icon: 'ri-arrow-up-circle-line',
          color: 'success',
          subtitle: this.currentMonthLabel,
        },
        {
          title: 'Gastos del mes',
          value: formatAmount(this.monthTotals.debe),
          icon: 'ri-arrow-down-circle-line',
          color: 'error',
          subtitle: this.currentMonthLabel,
        },
        {
          title: 'Movimientos del mes',
          value: this.monthTotals.count.toLocaleString('es-CR'),
          icon: 'ri-exchange-line',
          color: 'info',
          subtitle: this.currentMonthLabel,
        },
      ]
    },

    monthLabels() {
      return this.monthly.map(item => {
        const [year, month] = item.month.split('-')
        const date = new Date(Number(year), Number(month) - 1, 1)

        return date.toLocaleDateString('es-CR', { month: 'short' }).replace('.', '')
      })
    },

    monthlyChartOptions() {
      return {
        chart: {
          type: 'bar',
          toolbar: { show: false },
          fontFamily: 'inherit',
          foreColor: this.chartForeColor,
        },
        plotOptions: {
          bar: {
            borderRadius: 6,
            columnWidth: '55%',
          },
        },
        colors: ['#56CA00', '#FF4C51'],
        dataLabels: { enabled: false },
        grid: {
          borderColor: this.chartGridColor,
          strokeDashArray: 4,
          padding: { left: 8, right: 8 },
        },
        legend: {
          position: 'top',
          horizontalAlign: 'left',
          fontSize: '13px',
          markers: { radius: 12 },
        },
        xaxis: {
          categories: this.monthLabels,
          axisBorder: { show: false },
          axisTicks: { show: false },
        },
        yaxis: {
          labels: {
            formatter: val => formatAmount(val).replace(',00', ''),
          },
        },
        tooltip: {
          theme: this.isDark ? 'dark' : 'light',
          y: {
            formatter: val => formatAmount(val),
          },
        },
      }
    },

    monthlyChartSeries() {
      return [
        { name: 'Ingresos', data: this.monthly.map(m => m.haber) },
        { name: 'Gastos', data: this.monthly.map(m => m.debe) },
      ]
    },

    balanceChartOptions() {
      return {
        chart: {
          type: 'area',
          toolbar: { show: false },
          fontFamily: 'inherit',
          foreColor: this.chartForeColor,
          sparkline: { enabled: false },
        },
        colors: ['#8C57FF'],
        stroke: { curve: 'smooth', width: 3 },
        fill: {
          type: 'gradient',
          gradient: {
            shadeIntensity: 0.9,
            opacityFrom: 0.45,
            opacityTo: 0.05,
            stops: [0, 90, 100],
          },
        },
        dataLabels: { enabled: false },
        grid: {
          borderColor: this.chartGridColor,
          strokeDashArray: 4,
          padding: { left: 8, right: 8 },
        },
        xaxis: {
          categories: this.monthLabels,
          axisBorder: { show: false },
          axisTicks: { show: false },
        },
        yaxis: {
          labels: {
            formatter: val => formatAmount(val).replace(',00', ''),
          },
        },
        tooltip: {
          theme: this.isDark ? 'dark' : 'light',
          y: {
            formatter: val => formatAmount(val),
          },
        },
      }
    },

    balanceChartSeries() {
      return [
        { name: 'Saldo', data: this.monthly.map(m => m.balance) },
      ]
    },

    paymentChartOptions() {
      return {
        chart: {
          type: 'donut',
          fontFamily: 'inherit',
          foreColor: this.chartForeColor,
        },
        labels: this.paymentTypes.map(p => this.paymentTypeLabels[p.payment_type] ?? p.payment_type),
        colors: ['#8C57FF', '#16B1FF', '#56CA00', '#FFB400', '#FF4C51'],
        legend: {
          position: 'bottom',
          fontSize: '13px',
          markers: { radius: 12 },
        },
        dataLabels: { enabled: false },
        plotOptions: {
          pie: {
            donut: {
              size: '70%',
              labels: {
                show: true,
                name: { fontSize: '14px' },
                value: {
                  fontSize: '16px',
                  fontWeight: 600,
                  formatter: val => formatAmount(val),
                },
                total: {
                  show: true,
                  label: 'Total',
                  fontSize: '13px',
                  formatter: () => formatAmount(
                    this.paymentTypes.reduce((sum, p) => sum + p.total, 0),
                  ),
                },
              },
            },
          },
        },
        tooltip: {
          theme: this.isDark ? 'dark' : 'light',
          y: {
            formatter: val => formatAmount(val),
          },
        },
      }
    },

    paymentChartSeries() {
      return this.paymentTypes.map(p => p.total)
    },

    conceptsChartOptions() {
      return {
        chart: {
          type: 'bar',
          toolbar: { show: false },
          fontFamily: 'inherit',
          foreColor: this.chartForeColor,
        },
        plotOptions: {
          bar: {
            horizontal: true,
            borderRadius: 6,
            barHeight: '60%',
          },
        },
        colors: ['#16B1FF'],
        dataLabels: { enabled: false },
        grid: {
          borderColor: this.chartGridColor,
          strokeDashArray: 4,
          padding: { left: 8, right: 8 },
        },
        xaxis: {
          categories: this.concepts.map(c => c.concept),
          labels: {
            formatter: val => formatAmount(val).replace(',00', ''),
          },
        },
        yaxis: {
          labels: {
            maxWidth: 140,
          },
        },
        tooltip: {
          theme: this.isDark ? 'dark' : 'light',
          y: {
            formatter: val => formatAmount(val),
          },
        },
      }
    },

    conceptsChartSeries() {
      return [
        { name: 'Monto', data: this.concepts.map(c => c.total) },
      ]
    },

    hasMonthlyData() {
      return this.monthly.some(m => m.debe > 0 || m.haber > 0)
    },

    hasPaymentData() {
      return this.paymentTypes.some(p => p.total > 0)
    },

    hasConceptsData() {
      return this.concepts.length > 0
    },

    conceptsChartHeight() {
      return Math.max(260, this.concepts.length * 48)
    },

    studyPlanProgress() {
      if (!this.studyPlanSummary?.total)
        return 0

      return Math.round((this.studyPlanSummary.aprobadas / this.studyPlanSummary.total) * 100)
    },
  },

  mounted() {
    this.fetchDashboard()
  },

  methods: {
    async fetchDashboard() {
      this.loading = true
      this.error = null

      try {
        const requests = [
          axios.get('/api/accounting/stats'),
          axios.get('/api/accounting', { params: { page: 1 } }),
        ]

        if (this.auth.isSysAdmin)
          requests.push(axios.get('/api/study-plan').catch(() => null))

        const [statsRes, movementsRes, studyRes] = await Promise.all(requests)

        this.totals = statsRes.data.totals
        this.monthTotals = statsRes.data.month_totals
        this.monthly = statsRes.data.monthly
        this.paymentTypes = statsRes.data.payment_types
        this.concepts = statsRes.data.concepts
        this.recentMovements = movementsRes.data.data ?? []

        if (studyRes?.data?.summary)
          this.studyPlanSummary = studyRes.data.summary
      } catch (e) {
        this.error = 'No se pudo cargar el resumen.'
        console.error(e)
      } finally {
        this.loading = false
      }
    },

    paymentTypeLabel(value) {
      return this.paymentTypeLabels[value] ?? value
    },

    movementTypeLabel(value) {
      return this.movementTypeLabels[value] ?? value
    },
  },
}
</script>

<style scoped>
.dashboard-kpi__value {
  font-variant-numeric: tabular-nums;
  font-feature-settings: 'tnum';
  line-height: 1.2;
}

.dashboard-recent-list__amount {
  font-variant-numeric: tabular-nums;
  font-feature-settings: 'tnum';
  white-space: nowrap;
}

.accounting-amount--debe {
  color: color-mix(in srgb, rgb(var(--v-theme-error)) 86%, rgb(var(--v-theme-on-surface)) 14%);
}

.accounting-amount--haber {
  color: color-mix(in srgb, rgb(var(--v-theme-success)) 86%, rgb(var(--v-theme-on-surface)) 14%);
}

.dashboard-recent-list :deep(.v-list-item) {
  margin-bottom: 2px;
}

.dashboard-recent-list :deep(.v-list-item:hover) {
  background: rgba(var(--v-theme-on-surface), 0.04);
}
</style>
