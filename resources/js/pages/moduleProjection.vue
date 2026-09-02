<template>
  <div>
    <div class="d-flex flex-wrap align-center justify-space-between gap-3 mb-4">
      <div>
        <h1 class="text-h4 font-weight-medium mb-1">
          Proyección
        </h1>
        <p class="text-body-2 text-medium-emphasis mb-0">
          {{ projectionMode === 'real'
            ? 'Flujo real desde hoy: salario (1 y 15), pagos fijos y meses sin U'
            : 'Escenario fijo: lo que te queda al mes y meses sin pago de universidad' }}
        </p>
      </div>
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

    <VCard
      rounded="lg"
      class="mb-4"
      :loading="loading"
    >
      <VCardText class="projection-form">
        <VRow class="projection-form__row">
          <VCol
            cols="12"
            md="3"
          >
            <VSelect
              v-model="projectionMode"
              :items="projectionModeOptions"
              label="Modo"
              variant="outlined"
              rounded="lg"
              hide-details
            />
          </VCol>
          <VCol
            cols="6"
            md="3"
          >
            <VSelect
              v-model="year"
              :items="yearOptions"
              label="Año"
              variant="outlined"
              rounded="lg"
              hide-details
            />
          </VCol>
          <VCol
            cols="6"
            md="3"
          >
            <VSelect
              v-model="rangeMode"
              :items="rangeModeOptions"
              label="Periodo"
              variant="outlined"
              rounded="lg"
              hide-details
            />
          </VCol>
          <VCol
            v-if="rangeMode === 'custom'"
            cols="6"
            md="3"
          >
            <VSelect
              v-model="fromMonth"
              :items="monthOptions"
              label="Desde"
              variant="outlined"
              rounded="lg"
              hide-details
            />
          </VCol>
          <VCol
            v-if="rangeMode === 'custom'"
            cols="6"
            md="3"
          >
            <VSelect
              v-model="toMonth"
              :items="monthOptions"
              label="Hasta"
              variant="outlined"
              rounded="lg"
              hide-details
            />
          </VCol>
        </VRow>

        <!-- Móvil: resumen + acciones -->
        <template v-if="mdAndDown">
          <VDivider class="projection-form__divider" />

          <div class="projection-form__params">
            <template v-if="projectionMode === 'fixed'">
              <div class="projection-form__params-row">
                <span class="text-caption text-medium-emphasis">Queda al mes</span>
                <span class="text-body-2 font-weight-medium projection__num">
                  {{ $formatAmount(settings.monthly_remaining) }}
                </span>
              </div>
            </template>
            <template v-else>
              <div class="projection-form__params-row">
                <span class="text-caption text-medium-emphasis">Salario al mes</span>
                <span class="text-body-2 font-weight-medium projection__num">
                  {{ $formatAmount(sources.monthly_salary || 0) }}
                </span>
              </div>
            </template>
            <div class="projection-form__params-row">
              <span class="text-caption text-medium-emphasis">Cuota U</span>
              <span class="text-body-2 font-weight-medium projection__num">
                {{ $formatAmount(settings.university_fee) }}
              </span>
            </div>
            <div class="projection-form__params-row">
              <span class="text-caption text-medium-emphasis">Saldo inicial</span>
              <span class="text-body-2 font-weight-medium projection__num">
                {{ $formatAmount(startingBalance) }}
              </span>
            </div>
          </div>

          <div class="projection-form__actions projection-form__actions--mobile">
            <VBtn
              variant="tonal"
              rounded="lg"
              class="flex-grow-1"
              prepend-icon="ri-equalizer-line"
              @click="amountsSheet = true"
            >
              Ajustar
            </VBtn>
            <VBtn
              color="primary"
              rounded="lg"
              class="flex-grow-1"
              :loading="saving"
              @click="saveAndReload"
            >
              Calcular
            </VBtn>
          </div>
        </template>

        <!-- Desktop: inputs en la misma tarjeta -->
        <template v-else>
          <VDivider class="projection-form__divider" />

          <VRow class="projection-form__row">
            <VCol
              v-if="projectionMode === 'fixed'"
              cols="12"
              md="4"
              class="projection-form__field"
            >
              <VTextField
                v-currency-live
                v-model="monthlyRemainingInput"
                class="monto-with-action"
                type="text"
                inputmode="decimal"
                autocomplete="off"
                label="Queda al mes libre"
                variant="outlined"
                rounded="lg"
                hide-details="auto"
                :hint="remainingHint"
                persistent-hint
              >
                <template #append-inner>
                  <VBtn
                    color="primary"
                    variant="flat"
                    class="monto-with-action__btn rounded-s-0 rounded-e-lg"
                    :disabled="loading || saving || sources.fixed_payments_remaining == null"
                    aria-label="Usar monto de pagos fijos"
                    title="Usar monto de pagos fijos"
                    type="button"
                    tabindex="-1"
                    @click="useFixedRemaining"
                  >
                    <VIcon
                      icon="ri-calendar-check-line"
                      size="22"
                    />
                  </VBtn>
                </template>
              </VTextField>
            </VCol>
            <VCol
              cols="12"
              md="4"
              class="projection-form__field"
            >
              <VTextField
                v-currency-live
                v-model="universityFeeInput"
                type="text"
                inputmode="decimal"
                autocomplete="off"
                label="Cuota universidad"
                variant="outlined"
                rounded="lg"
                hide-details="auto"
                :hint="projectionMode === 'real'
                  ? 'No se descuenta en meses sin pago U'
                  : 'Se suma en meses sin pago U'"
                persistent-hint
              />
            </VCol>
            <VCol
              v-if="projectionMode === 'real'"
              cols="12"
              md="4"
              class="projection-form__field"
            >
              <VTextField
                :model-value="$formatAmountValue(sources.monthly_salary || 0)"
                label="Salario al mes"
                variant="outlined"
                rounded="lg"
                hide-details="auto"
                :hint="`Quincena (1 y 15): ${$formatAmount(sources.payday_amount || 0)} · se edita en Pagos fijos`"
                persistent-hint
                readonly
              />
            </VCol>
            <VCol
              cols="12"
              md="4"
              class="projection-form__field"
            >
              <VTextField
                v-currency-live
                v-model="startingBalanceInput"
                class="monto-with-action"
                type="text"
                inputmode="decimal"
                autocomplete="off"
                label="Saldo inicial"
                variant="outlined"
                rounded="lg"
                hide-details="auto"
                :hint="startingBalanceHint"
                persistent-hint
              >
                <template #append-inner>
                  <VBtn
                    color="primary"
                    variant="flat"
                    class="monto-with-action__btn rounded-s-0 rounded-e-lg"
                    :disabled="loading || saving"
                    aria-label="Usar saldo actual de la cuenta"
                    title="Usar saldo actual de la cuenta"
                    type="button"
                    tabindex="-1"
                    @click="useAccountBalance"
                  >
                    <VIcon
                      icon="ri-wallet-3-line"
                      size="22"
                    />
                  </VBtn>
                </template>
              </VTextField>
            </VCol>
          </VRow>

          <div class="projection-form__actions">
            <VBtn
              color="primary"
              rounded="lg"
              class="projection-form__submit"
              :loading="saving"
              @click="saveAndReload"
            >
              Guardar y calcular
            </VBtn>
          </div>
        </template>
      </VCardText>
    </VCard>

    <VBottomSheet
      v-if="mdAndDown"
      v-model="amountsSheet"
      :scrim="true"
    >
      <VCard
        rounded="t-lg"
        class="projection-amounts-sheet"
      >
        <div class="d-flex align-center justify-space-between px-4 pt-4 pb-2">
          <span class="text-h6">
            Ajustar montos
          </span>
          <VBtn
            icon
            variant="text"
            aria-label="Cerrar"
            @click="amountsSheet = false"
          >
            <VIcon icon="ri-close-line" />
          </VBtn>
        </div>

        <VDivider />

        <div class="pa-4">
          <VTextField
            v-if="projectionMode === 'fixed'"
            v-currency-live
            v-model="monthlyRemainingInput"
            class="monto-with-action mb-4"
            type="text"
            inputmode="decimal"
            autocomplete="off"
            label="Queda al mes"
            variant="outlined"
            rounded="lg"
            hide-details="auto"
            :hint="remainingHint"
            persistent-hint
          >
            <template #append-inner>
              <VBtn
                color="primary"
                variant="flat"
                class="monto-with-action__btn rounded-s-0 rounded-e-lg"
                :disabled="loading || saving || sources.fixed_payments_remaining == null"
                aria-label="Usar monto de pagos fijos"
                title="Usar monto de pagos fijos"
                type="button"
                tabindex="-1"
                @click="useFixedRemaining"
              >
                <VIcon
                  icon="ri-calendar-check-line"
                  size="22"
                />
              </VBtn>
            </template>
          </VTextField>

          <VTextField
            v-if="projectionMode === 'real'"
            class="mb-4"
            :model-value="$formatAmountValue(sources.monthly_salary || 0)"
            label="Salario al mes"
            variant="outlined"
            rounded="lg"
            hide-details="auto"
            :hint="`Quincena: ${$formatAmount(sources.payday_amount || 0)} · se edita en Pagos fijos`"
            persistent-hint
            readonly
          />

          <VTextField
            v-currency-live
            v-model="universityFeeInput"
            class="mb-4"
            type="text"
            inputmode="decimal"
            autocomplete="off"
            label="Cuota universidad"
            variant="outlined"
            rounded="lg"
            hide-details="auto"
            hint="Se suma en meses sin pago U"
            persistent-hint
          />

          <VTextField
            v-currency-live
            v-model="startingBalanceInput"
            class="monto-with-action mb-4"
            type="text"
            inputmode="decimal"
            autocomplete="off"
            label="Saldo inicial"
            variant="outlined"
            rounded="lg"
            hide-details="auto"
            :hint="startingBalanceHint"
            persistent-hint
          >
            <template #append-inner>
              <VBtn
                color="primary"
                variant="flat"
                class="monto-with-action__btn rounded-s-0 rounded-e-lg"
                :disabled="loading || saving"
                aria-label="Usar saldo actual de la cuenta"
                title="Usar saldo actual de la cuenta"
                type="button"
                tabindex="-1"
                @click="useAccountBalance"
              >
                <VIcon
                  icon="ri-wallet-3-line"
                  size="22"
                />
              </VBtn>
            </template>
          </VTextField>

          <VBtn
            color="primary"
            rounded="lg"
            block
            :loading="saving"
            @click="saveAndReload({ closeSheet: true })"
          >
            Guardar y calcular
          </VBtn>
        </div>
      </VCard>
    </VBottomSheet>

    <VRow class="mb-4">
      <VCol
        v-for="card in summaryCards"
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

    <VCard
      rounded="lg"
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
                <span class="text-caption text-medium-emphasis">Salario in</span>
                <span class="text-body-2 projection__num">
                  {{ $formatAmount(row.salary_in) }}
                </span>
              </div>
              <div class="projection-month-card__cell">
                <span class="text-caption text-medium-emphasis">Gastos out</span>
                <span class="text-body-2 projection__num">
                  {{ $formatAmount(row.expenses_out) }}
                </span>
              </div>
            </template>
            <template v-else>
              <div class="projection-month-card__cell">
                <span class="text-caption text-medium-emphasis">Queda</span>
                <span class="text-body-2 projection__num">
                  {{ $formatAmount(row.monthly_remaining) }}
                </span>
              </div>
              <div class="projection-month-card__cell">
                <span class="text-caption text-medium-emphasis">Libre U</span>
                <span
                  class="text-body-2 projection__num"
                  :class="{ 'projection__freed': row.university_freed > 0 }"
                >
                  {{ $formatAmount(row.university_freed) }}
                </span>
              </div>
            </template>
            <div class="projection-month-card__cell">
              <span class="text-caption text-medium-emphasis">Δ mes</span>
              <span class="text-body-2 projection__num projection__delta">
                {{ $formatAmount(row.delta) }}
              </span>
            </div>
            <div class="projection-month-card__cell">
              <span class="text-caption text-medium-emphasis">Saldo</span>
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
              Salario
            </th>
            <th
              v-if="projectionMode === 'real'"
              class="text-right"
            >
              Gastos
            </th>
            <th
              v-if="projectionMode === 'fixed'"
              class="text-right"
            >
              Queda
            </th>
            <th
              v-if="projectionMode === 'fixed'"
              class="text-right"
            >
              Libre U
            </th>
            <th class="text-right">
              Δ mes
            </th>
            <th class="text-right">
              Saldo
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
              {{ $formatAmount(row.salary_in) }}
            </td>
            <td
              v-if="projectionMode === 'real'"
              class="text-right projection__num"
            >
              {{ $formatAmount(row.expenses_out) }}
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
        v-if="summary"
        class="pt-4 text-body-2 text-medium-emphasis"
      >
        <template v-if="projectionMode === 'real'">
          Simula el 1 y el 15: entra el salario quincenal y salen los pagos fijos.
          En el año actual omite quincenas ya pasadas. En meses sin U no descuenta la cuota.
        </template>
        <template v-else>
          En meses con pago U solo suma lo que te queda.
          En meses sin pago U también suma la cuota ({{ $formatAmount(settings.university_fee) }}),
          porque esa plata se queda en la cuenta.
        </template>
      </VCardText>
    </VCard>
  </div>
</template>

<script>
import { axios } from '@/plugins/axios';
import { useDisplay } from 'vuetify'

const MONTH_OPTIONS = [
  { title: 'Enero', value: 1 },
  { title: 'Febrero', value: 2 },
  { title: 'Marzo', value: 3 },
  { title: 'Abril', value: 4 },
  { title: 'Mayo', value: 5 },
  { title: 'Junio', value: 6 },
  { title: 'Julio', value: 7 },
  { title: 'Agosto', value: 8 },
  { title: 'Septiembre', value: 9 },
  { title: 'Octubre', value: 10 },
  { title: 'Noviembre', value: 11 },
  { title: 'Diciembre', value: 12 },
]

export default {
  name: 'ModuleProjection',

  setup() {
    const { mdAndDown } = useDisplay()

    return { mdAndDown }
  },

  data() {
    const currentYear = new Date().getFullYear()

    return {
      loading: true,
      saving: false,
      error: '',
      amountsSheet: false,
      projectionMode: 'fixed',
      year: currentYear,
      rangeMode: 'year',
      fromMonth: 1,
      toMonth: 12,
      monthlyRemainingInput: '',
      universityFeeInput: '',
      startingBalanceInput: '',
      settings: {
        university_fee: 110000,
        monthly_remaining: 0,
        uses_fixed_payments_remaining: true,
      },
      sources: {
        account_balance: 0,
        fixed_payments_remaining: 0,
        payday_amount: 0,
        monthly_salary: 0,
      },
      startingBalance: 0,
      months: [],
      summary: null,
      monthOptions: MONTH_OPTIONS,
      rangeModeOptions: [
        { title: 'Anual', value: 'year' },
        { title: 'Mensual', value: 'custom' },
      ],
      projectionModeOptions: [
        { title: 'Fija', value: 'fixed' },
        { title: 'Real', value: 'real' },
      ],
      yearOptions: [currentYear - 1, currentYear, currentYear + 1, currentYear + 2],
    }
  },

  computed: {
    remainingHint() {
      if (this.settings.uses_fixed_payments_remaining) {
        return `Tomado de pagos fijos (${this.$formatAmount(this.sources.fixed_payments_remaining)})`
      }

      return 'Monto fijo por mes (con pago U)'
    },

    startingBalanceHint() {
      return `Saldo en cuenta hoy: ${this.$formatAmount(this.sources.account_balance)}`
    },

    periodLabel() {
      if (this.rangeMode === 'year') {
        if (this.projectionMode === 'real' && this.year === new Date().getFullYear()) {
          const from = MONTH_OPTIONS.find(m => m.value === this.fromMonth)?.title || ''

          return `${from} – Diciembre ${this.year}`
        }

        return `Enero – Diciembre ${this.year}`
      }

      const from = MONTH_OPTIONS.find(m => m.value === this.fromMonth)?.title || ''
      const to = MONTH_OPTIONS.find(m => m.value === this.toMonth)?.title || ''

      return `${from} – ${to} ${this.year}`
    },

    summaryCards() {
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
            subtitle: `Partiendo de ${this.$formatAmount(this.startingBalance)}`,
            icon: 'ri-wallet-3-line',
            color: 'primary',
            valueClass: 'text-primary',
          },
          {
            title: 'Salario proyectado',
            value: this.$formatAmount(s.total_salary_in),
            subtitle: `Quincena ${this.$formatAmount(this.sources.payday_amount || 0)}`,
            icon: 'ri-money-dollar-circle-line',
            color: 'info',
            valueClass: '',
          },
          {
            title: 'Gastos proyectados',
            value: this.$formatAmount(s.total_expenses_out),
            subtitle: `${s.free_months_count} mes(es) sin U`,
            icon: 'ri-bill-line',
            color: 'error',
            valueClass: '',
          },
          {
            title: 'Δ total',
            value: this.$formatAmount(s.total_delta),
            subtitle: 'Ingresos − gastos',
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
          icon: 'ri-wallet-3-line',
          color: 'primary',
          valueClass: 'text-primary',
        },
        {
          title: 'Suma de “quedan libres al mes”',
          value: this.$formatAmount(s.total_monthly_remaining),
          subtitle: `${s.payment_months_count + s.free_months_count} meses`,
          icon: 'ri-stack-line',
          color: 'info',
          valueClass: '',
        },
        {
          title: 'Liberado por U',
          value: this.$formatAmount(s.total_university_freed),
          subtitle: `${s.free_months_count} mes(es) sin pago`,
          icon: 'ri-gift-line',
          color: 'success',
          valueClass: 'projection__freed',
        },
        {
          title: 'Total proyectado',
          value: this.$formatAmount(s.total_delta),
          subtitle: 'Queda + liberado U',
          icon: 'ri-line-chart-line',
          color: 'warning',
          valueClass: 'projection__delta',
        },
      ]
    },
  },

  watch: {
    rangeMode(mode) {
      if (mode === 'year') {
        this.fromMonth = 1
        this.toMonth = 12
      }
    },
    projectionMode() {
      this.startingBalanceInput = ''
      this.loadProjection()
    },
  },

  mounted() {
    this.loadProjection()
  },

  methods: {
    effectiveRange() {
      if (this.rangeMode === 'year') {
        const now = new Date()

        if (this.projectionMode === 'real' && this.year === now.getFullYear()) {
          return { from_month: now.getMonth() + 1, to_month: 12 }
        }

        return { from_month: 1, to_month: 12 }
      }

      return {
        from_month: this.fromMonth,
        to_month: this.toMonth,
      }
    },

    loadProjection() {
      this.loading = true
      this.error = ''

      const range = this.effectiveRange()
      const starting = this.$parseAmount(this.startingBalanceInput)
      const params = {
        mode: this.projectionMode,
        year: this.year,
        from_month: range.from_month,
        to_month: range.to_month,
      }

      if (starting !== '' && !Number.isNaN(starting))
        params.starting_balance = starting

      return axios
        .get('/api/projection', { params })
        .then(response => {
          this.applyResponse(response.data, { preserveStartingInput: starting !== '' && !Number.isNaN(starting) })
        })
        .catch(error => {
          this.error = error.response?.data?.message || 'No se pudo cargar la proyección.'
        })
        .finally(() => {
          this.loading = false
        })
    },

    applyResponse(data, { preserveStartingInput = false } = {}) {
      this.projectionMode = data.mode || this.projectionMode
      this.settings = {
        university_fee: data.settings.university_fee,
        monthly_remaining: data.settings.monthly_remaining ?? this.settings.monthly_remaining,
        uses_fixed_payments_remaining: data.settings.uses_fixed_payments_remaining ?? true,
      }
      this.sources = {
        account_balance: data.sources.account_balance ?? 0,
        fixed_payments_remaining: data.sources.fixed_payments_remaining ?? 0,
        payday_amount: data.sources.payday_amount ?? 0,
        monthly_salary: data.sources.monthly_salary ?? 0,
      }
      this.startingBalance = data.starting_balance
      this.months = data.months || []
      this.summary = data.summary
      this.fromMonth = data.from_month
      this.toMonth = data.to_month

      if (data.settings.monthly_remaining != null)
        this.monthlyRemainingInput = this.$formatAmountValue(data.settings.monthly_remaining)
      else if (data.sources.fixed_payments_remaining != null)
        this.monthlyRemainingInput = this.$formatAmountValue(data.sources.fixed_payments_remaining)

      this.universityFeeInput = this.$formatAmountValue(data.settings.university_fee)

      if (!preserveStartingInput)
        this.startingBalanceInput = this.$formatAmountValue(data.starting_balance)
    },

    useAccountBalance() {
      this.startingBalanceInput = this.$formatAmountValue(this.sources.account_balance)
    },

    useFixedRemaining() {
      this.monthlyRemainingInput = this.$formatAmountValue(this.sources.fixed_payments_remaining)
    },

    saveAndReload({ clearRemainingOverride = false, closeSheet = false } = {}) {
      const universityFee = this.$parseAmount(this.universityFeeInput)
      let monthlyRemaining = this.$parseAmount(this.monthlyRemainingInput)

      if (universityFee === '' || Number.isNaN(universityFee)) {
        this.error = 'Ingresa una cuota de universidad válida.'

        return
      }

      if (this.projectionMode === 'fixed' && (monthlyRemaining === '' || Number.isNaN(monthlyRemaining))) {
        this.error = 'Ingresa un monto de “queda al mes” válido.'

        return
      }

      if (
        this.projectionMode === 'fixed'
        && !clearRemainingOverride
        && Math.abs(monthlyRemaining - Number(this.sources.fixed_payments_remaining || 0)) < 0.005
      ) {
        clearRemainingOverride = true
      }

      this.saving = true
      this.error = ''

      const payload = {
        university_fee: universityFee,
        monthly_remaining: this.projectionMode === 'real'
          ? (this.settings.uses_fixed_payments_remaining ? null : this.settings.monthly_remaining)
          : (clearRemainingOverride ? null : monthlyRemaining),
      }

      axios
        .put('/api/projection/settings', payload)
        .then(() => {
          this.$toast.success('Proyección actualizada', { timeout: 2000, closeOnClick: true })

          if (closeSheet)
            this.amountsSheet = false

          return this.loadProjection()
        })
        .catch(error => {
          this.error = error.response?.data?.message || 'No se pudieron guardar los parámetros.'
        })
        .finally(() => {
          this.saving = false
        })
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

.projection-table__row--free {
  background: color-mix(in srgb, rgb(var(--v-theme-success)) 6%, rgb(var(--v-theme-surface)) 94%);
}

.projection-table :deep(th),
.projection-table :deep(td) {
  white-space: nowrap;
}

.projection-month-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
  padding: 12px 16px 4px;
}

.projection-month-card {
  padding: 14px;
  border: thin solid rgba(var(--v-border-color), var(--v-border-opacity));
  border-radius: 12px;
  background: rgb(var(--v-theme-surface));
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

.projection-form__divider {
  margin-block: 1.25rem;
}

.projection-form__actions {
  margin-top: 1.25rem;
}

.projection-form__actions--mobile {
  display: flex;
  gap: 10px;
}

.projection-form__params {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.projection-form__params-row {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 12px;
}

.projection-form__row {
  margin: -8px;
}

.projection-form__row > .v-col {
  padding: 8px;
}

.projection-form__field :deep(.v-input__details) {
  padding-top: 6px;
  min-height: 28px;
  margin-bottom: 4px;
}

@media (max-width: 959px) {
  .projection-form {
    padding-block: 4px;
  }

  .projection-form__divider {
    margin-block: 1.5rem;
  }

  .projection-form__row {
    margin: -10px;
  }

  .projection-form__row > .v-col {
    padding: 10px;
  }

  .projection-form__field {
    padding-block: 12px !important;
  }

  .projection-form__field :deep(.v-input__details) {
    padding-top: 8px;
    margin-bottom: 8px;
  }

  .projection-form__actions {
    margin-top: 1.5rem;
  }

  .projection-form__submit {
    width: 100%;
  }
}

@media (min-width: 960px) {
  .projection-form__submit {
    width: auto;
  }
}

.monto-with-action :deep(.v-field.v-field--appended) {
  --v-field-padding-end: var(--v-field-padding-start, 16px);
}

.monto-with-action :deep(.v-field__field) {
  align-items: stretch;
}

.monto-with-action :deep(.v-field__append-inner) {
  align-self: stretch;
  align-items: stretch;
  padding-top: 0;
  padding-bottom: 0;
  padding-inline-start: 0;
  margin-inline-end: calc(-1 * var(--v-field-padding-end, 16px));
}

.monto-with-action__btn {
  align-self: stretch;
  min-width: 48px !important;
  height: auto !important;
  min-height: 100%;
  box-shadow: none !important;
  border-inline-start: thin solid rgba(var(--v-border-color), var(--v-border-opacity));
}
</style>
