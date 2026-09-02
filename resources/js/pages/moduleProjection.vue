<template>
  <div>
    <div class="d-flex flex-wrap align-center justify-space-between gap-3 mb-4">
      <div>
        <h1 class="text-h4 font-weight-medium mb-1">
          Proyección
        </h1>
        <p class="text-body-2 text-medium-emphasis mb-0">
          Saldo estimado según lo que te queda al mes y los meses sin pago de universidad
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

        <VDivider class="projection-form__divider" />

        <VRow class="projection-form__row">
          <VCol
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
              hint="Se suma en meses sin pago U"
              persistent-hint
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
      </VCardText>
    </VCard>

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
            <th class="text-right">
              Queda
            </th>
            <th class="text-right">
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
            <td class="text-right projection__num">
              {{ $formatAmount(row.monthly_remaining) }}
            </td>
            <td class="text-right projection__num">
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
        En meses con pago U solo suma lo que te queda.
        En meses sin pago U también suma la cuota ({{ $formatAmount(settings.university_fee) }}),
        porque esa plata se queda en la cuenta.
      </VCardText>
    </VCard>
  </div>
</template>

<script>
import { axios } from '@/plugins/axios'

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

  data() {
    const currentYear = new Date().getFullYear()

    return {
      loading: true,
      saving: false,
      error: '',
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
      },
      startingBalance: 0,
      months: [],
      summary: null,
      monthOptions: MONTH_OPTIONS,
      rangeModeOptions: [
        { title: 'Anual', value: 'year' },
        { title: 'Mensual', value: 'custom' },
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
      if (this.rangeMode === 'year')
        return `Enero – Diciembre ${this.year}`

      const from = MONTH_OPTIONS.find(m => m.value === this.fromMonth)?.title || ''
      const to = MONTH_OPTIONS.find(m => m.value === this.toMonth)?.title || ''

      return `${from} – ${to} ${this.year}`
    },

    summaryCards() {
      const s = this.summary || {
        total_monthly_remaining: 0,
        total_university_freed: 0,
        total_delta: 0,
        ending_balance: 0,
        free_months_count: 0,
        payment_months_count: 0,
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
          title: 'Suma de “queda”',
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
  },

  mounted() {
    this.loadProjection()
  },

  methods: {
    effectiveRange() {
      if (this.rangeMode === 'year')
        return { from_month: 1, to_month: 12 }

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
      this.settings = data.settings
      this.sources = data.sources
      this.startingBalance = data.starting_balance
      this.months = data.months || []
      this.summary = data.summary

      this.monthlyRemainingInput = this.$formatAmountValue(data.settings.monthly_remaining)
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

    saveAndReload({ clearRemainingOverride = false } = {}) {
      const universityFee = this.$parseAmount(this.universityFeeInput)
      let monthlyRemaining = this.$parseAmount(this.monthlyRemainingInput)

      if (universityFee === '' || Number.isNaN(universityFee)) {
        this.error = 'Ingresa una cuota de universidad válida.'

        return
      }

      if (monthlyRemaining === '' || Number.isNaN(monthlyRemaining)) {
        this.error = 'Ingresa un monto de “queda al mes” válido.'

        return
      }

      // Si el monto coincide con pagos fijos, guardar null para seguir sincronizado
      if (
        !clearRemainingOverride
        && Math.abs(monthlyRemaining - Number(this.sources.fixed_payments_remaining || 0)) < 0.005
      ) {
        clearRemainingOverride = true
      }

      this.saving = true
      this.error = ''

      const payload = {
        university_fee: universityFee,
        monthly_remaining: clearRemainingOverride ? null : monthlyRemaining,
      }

      axios
        .put('/api/projection/settings', payload)
        .then(() => {
          this.$toast.success('Proyección actualizada', { timeout: 2000, closeOnClick: true })

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

.projection-form__divider {
  margin-block: 1.25rem;
}

.projection-form__actions {
  margin-top: 1.25rem;
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
