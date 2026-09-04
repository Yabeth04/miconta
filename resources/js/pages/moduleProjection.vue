<template>
  <div>
    <div class="d-flex flex-wrap align-center justify-space-between gap-3 mb-4">
      <div>
        <div class="d-flex align-center gap-2">
          <h1 class="text-h4 font-weight-medium mb-0">
            Proyección
          </h1>
          <VBtn
            icon
            variant="text"
            size="small"
            aria-label="Ayuda"
            @click="helpDialog = true"
          >
            <VIcon
              icon="ri-question-line"
              size="22"
            />
          </VBtn>
        </div>
        <p class="text-body-2 text-medium-emphasis mb-0 mt-1">
          Adelanto de caja: salario y pagos fijos desde hoy
        </p>
      </div>
    </div>

    <VDialog
      v-model="helpDialog"
      max-width="480"
    >
      <VCard rounded="lg">
        <VCardTitle class="text-h6">
          ¿Para qué es este módulo?
        </VCardTitle>
        <VCardText>
          <p class="mb-3">
            Parte del saldo de hoy y, cada 1 y 15, suma el salario y resta solo los pagos fijos (y la U cuando toca). Sirve para ver si cubrís esos compromisos y cuánto te quedaría.
          </p>
          <p class="mb-0">
            No cuenta gastos extras. Lo “libre” del mes se deja como si no lo usaras. Un pantalón u otro gasto se registra en Movimientos; al recargar, arranca otra vez desde el saldo real.
          </p>
        </VCardText>
        <VCardActions>
          <VSpacer />
          <VBtn
            color="primary"
            rounded="lg"
            @click="helpDialog = false"
          >
            Entendido
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <VAlert
      v-if="error || isRangeInvalid"
      type="error"
      variant="tonal"
      rounded="lg"
      class="mb-4"
    >
      {{ rangeInvalidMessage || error }}
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
              v-model="rangeMode"
              :items="rangeModeOptions"
              label="Periodo"
              variant="outlined"
              rounded="lg"
              hide-details
            />
          </VCol>
          <VCol
            v-if="rangeMode === 'year'"
            cols="6"
            md="3"
          >
            <VSelect
              v-model="year"
              :items="fromYearOptions"
              label="Año"
              variant="outlined"
              rounded="lg"
              hide-details
            />
          </VCol>
        </VRow>

        <VRow
          v-if="rangeMode === 'custom'"
          class="projection-form__row projection-form__range mt-3"
          align="center"
        >
          <VCol
            cols="12"
            md
          >
            <VRow class="projection-form__row">
              <VCol
                cols="6"
                md="6"
              >
                <VSelect
                  v-model="fromYear"
                  :items="fromYearOptions"
                  label="Desde año"
                  variant="outlined"
                  rounded="lg"
                  hide-details
                />
              </VCol>
              <VCol
                cols="6"
                md="6"
              >
                <VSelect
                  v-model="fromMonth"
                  :items="fromMonthOptions"
                  label="Mes"
                  variant="outlined"
                  rounded="lg"
                  hide-details
                />
              </VCol>
            </VRow>
          </VCol>

          <VCol
            cols="12"
            md="auto"
            class="d-flex align-center justify-center py-0"
          >
            <VIcon
              :icon="mdAndDown ? 'ri-arrow-down-line' : 'ri-arrow-right-line'"
              size="22"
              class="text-medium-emphasis"
            />
          </VCol>

          <VCol
            cols="12"
            md
          >
            <VRow class="projection-form__row">
              <VCol
                cols="6"
                md="6"
              >
                <VSelect
                  v-model="toYear"
                  :items="toYearOptions"
                  label="Hasta año"
                  variant="outlined"
                  rounded="lg"
                  hide-details
                />
              </VCol>
              <VCol
                cols="6"
                md="6"
              >
                <VSelect
                  v-model="toMonth"
                  :items="toMonthOptions"
                  label="Mes"
                  variant="outlined"
                  rounded="lg"
                  hide-details
                />
              </VCol>
            </VRow>
          </VCol>
        </VRow>

        <p
          v-if="!isRangeInvalid"
          class="text-caption text-medium-emphasis mb-0 mt-3"
        >
          Rango proyectado: {{ periodLabel }}
        </p>

        <!-- Móvil: resumen + acciones -->
        <template v-if="mdAndDown">
          <VDivider class="projection-form__divider" />

          <div class="projection-form__params">
            <div class="projection-form__params-row">
              <span class="text-caption text-medium-emphasis">Salario al mes</span>
              <span class="text-body-2 font-weight-medium projection__num">
                {{ $formatAmount(sources.monthly_salary || 0) }}
              </span>
            </div>
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
              :disabled="isRangeInvalid"
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
                hint="No se descuenta en meses sin pago U"
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
              :disabled="isRangeInvalid"
              @click="saveAndReload"
            >
              Calcular
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
            hint="No se descuenta en meses sin pago U"
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
            :disabled="isRangeInvalid"
            @click="saveAndReload({ closeSheet: true })"
          >
            Calcular
          </VBtn>
        </div>
      </VCard>
    </VBottomSheet>

    <ProjectionSummaryCards
      :loading="loading"
      :summary="summary"
      :sources="sources"
    />

    <ProjectionMonthsDetail
      :loading="loading"
      :months="months"
      :period-label="periodLabel"
      :has-summary="Boolean(summary)"
    />
  </div>
</template>

<script>
import { axios } from '@/plugins/axios'
import ProjectionMonthsDetail from '@/views/pages/projection/ProjectionMonthsDetail.vue'
import ProjectionSummaryCards from '@/views/pages/projection/ProjectionSummaryCards.vue'
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

  components: {
    ProjectionMonthsDetail,
    ProjectionSummaryCards,
  },

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
      helpDialog: false,
      year: currentYear,
      fromYear: currentYear,
      toYear: currentYear,
      rangeMode: 'year',
      fromMonth: new Date().getMonth() + 1,
      toMonth: 12,
      universityFeeInput: '',
      startingBalanceInput: '',
      settings: {
        university_fee: 110000,
      },
      sources: {
        account_balance: 0,
        prior_month_balance: 0,
        prior_month_label: '',
        anchor_balance: 0,
        payday_amount: 0,
        monthly_salary: 0,
      },
      startingBalance: 0,
      months: [],
      summary: null,
      monthOptions: MONTH_OPTIONS,
      rangeModeOptions: [
        { title: 'Anual', value: 'year' },
        { title: 'Rango', value: 'custom' },
      ],
      yearOptions: [currentYear - 1, currentYear, currentYear + 1, currentYear + 2, currentYear + 3],
    }
  },

  computed: {
    startingBalanceHint() {
      return `Hoy: ${this.$formatAmount(this.sources.account_balance)} · base del mes para proyectar completo`
    },

    periodLabel() {
      if (this.rangeMode === 'year') {
        if (this.year === new Date().getFullYear()) {
          const from = MONTH_OPTIONS.find(m => m.value === this.fromMonth)?.title || ''

          return `${from} – Diciembre ${this.year}`
        }

        return `Enero – Diciembre ${this.year}`
      }

      const from = MONTH_OPTIONS.find(m => m.value === this.fromMonth)?.title || ''
      const to = MONTH_OPTIONS.find(m => m.value === this.toMonth)?.title || ''

      if (this.fromYear === this.toYear)
        return `${from} – ${to} ${this.fromYear}`

      return `${from} ${this.fromYear} – ${to} ${this.toYear}`
    },

    isRangeInvalid() {
      if (this.rangeMode !== 'custom')
        return false

      const from = this.fromYear * 12 + this.fromMonth
      const to = this.toYear * 12 + this.toMonth
      const min = this.minFromYear * 12 + this.minFromMonth

      return from > to || from < min
    },

    rangeInvalidMessage() {
      if (this.rangeMode !== 'custom')
        return ''

      const from = this.fromYear * 12 + this.fromMonth
      const to = this.toYear * 12 + this.toMonth
      const min = this.minFromYear * 12 + this.minFromMonth

      if (from < min)
        return 'El periodo inicial no puede ser anterior al mes actual.'

      if (from > to)
        return 'El periodo inicial no puede ser posterior al final.'

      return ''
    },

    minFromYear() {
      return new Date().getFullYear()
    },

    minFromMonth() {
      return new Date().getMonth() + 1
    },

    fromYearOptions() {
      return this.yearOptions.filter(year => year >= this.minFromYear)
    },

    fromMonthOptions() {
      if (this.fromYear > this.minFromYear)
        return this.monthOptions

      return this.monthOptions.filter(month => month.value >= this.minFromMonth)
    },

    toYearOptions() {
      return this.yearOptions.filter(year => year >= this.fromYear)
    },

    toMonthOptions() {
      if (this.toYear > this.fromYear)
        return this.monthOptions

      return this.monthOptions.filter(month => month.value >= this.fromMonth)
    },

  },

  watch: {
    fromYear(year) {
      if (year < this.minFromYear)
        this.fromYear = this.minFromYear

      if (this.fromYear === this.minFromYear && this.fromMonth < this.minFromMonth)
        this.fromMonth = this.minFromMonth

      this.clampToPeriod()
    },
    fromMonth(month) {
      if (this.fromYear === this.minFromYear && month < this.minFromMonth)
        this.fromMonth = this.minFromMonth

      this.clampToPeriod()
    },
    toYear(year) {
      if (year < this.fromYear)
        this.toYear = this.fromYear

      if (this.toYear === this.fromYear && this.toMonth < this.fromMonth)
        this.toMonth = this.fromMonth
    },
    toMonth(month) {
      if (this.toYear === this.fromYear && month < this.fromMonth)
        this.toMonth = this.fromMonth
    },
    year(value) {
      if (value < this.minFromYear)
        this.year = this.minFromYear

      if (this.rangeMode === 'year' && this.year === this.minFromYear) {
        this.fromMonth = this.minFromMonth
        this.fromYear = this.minFromYear
      }
    },
    rangeMode(mode) {
      if (mode === 'year') {
        this.fromMonth = 1
        this.toMonth = 12
        this.fromYear = this.year
        this.toYear = this.year
      } else {
        const now = new Date()

        this.fromMonth = now.getMonth() + 1
        this.fromYear = now.getFullYear()
        this.toMonth = 12
        this.toYear = now.getFullYear()
      }
    },
  },

  mounted() {
    this.loadProjection()
  },

  methods: {
    clampToPeriod() {
      if (this.toYear < this.fromYear)
        this.toYear = this.fromYear

      if (this.toYear === this.fromYear && this.toMonth < this.fromMonth)
        this.toMonth = this.fromMonth
    },

    effectiveRange() {
      if (this.rangeMode === 'year') {
        const now = new Date()
        let fromMonth = 1
        const toMonth = 12
        const year = this.year

        if (year === now.getFullYear())
          fromMonth = now.getMonth() + 1

        return {
          from_year: year,
          from_month: fromMonth,
          to_year: year,
          to_month: toMonth,
        }
      }

      return {
        from_year: this.fromYear,
        from_month: this.fromMonth,
        to_year: this.toYear,
        to_month: this.toMonth,
      }
    },

    loadProjection() {
      this.loading = true
      this.error = ''

      const range = this.effectiveRange()
      const starting = this.$parseAmount(this.startingBalanceInput)
      const params = {
        year: range.from_year,
        from_year: range.from_year,
        from_month: range.from_month,
        to_year: range.to_year,
        to_month: range.to_month,
      }

      if (starting !== '' && !Number.isNaN(starting))
        params.starting_balance = starting

      return axios
        .get('/api/projection', { params })
        .then(response => {
          this.applyResponse(response.data, {
            preserveStartingInput: starting !== '' && !Number.isNaN(starting),
          })
        })
        .catch(error => {
          this.error = error.response?.data?.message || 'No se pudo cargar la proyección.'
        })
        .finally(() => {
          this.loading = false
        })
    },

    applyResponse(data, { preserveStartingInput = false } = {}) {
      this.settings = {
        university_fee: data.settings.university_fee,
      }
      this.sources = {
        account_balance: data.sources.account_balance ?? 0,
        anchor_balance: data.sources.anchor_balance ?? data.sources.account_balance ?? 0,
        prior_month_balance: data.sources.prior_month_balance ?? data.sources.account_balance ?? 0,
        prior_month_label: data.sources.prior_month_label ?? '',
        payday_amount: data.sources.payday_amount ?? 0,
        monthly_salary: data.sources.monthly_salary ?? 0,
      }
      this.startingBalance = data.starting_balance
      this.months = data.months || []
      this.summary = data.summary
      this.fromMonth = data.from_month
      this.toMonth = data.to_month
      this.fromYear = data.from_year ?? data.year
      this.toYear = data.to_year ?? data.year
      if (this.rangeMode === 'year')
        this.year = data.from_year ?? data.year

      this.universityFeeInput = this.$formatAmountValue(data.settings.university_fee)

      if (!preserveStartingInput) {
        const anchor = data.sources.anchor_balance ?? data.sources.account_balance ?? data.starting_balance
        this.startingBalanceInput = this.$formatAmountValue(anchor)
      }
    },

    useAccountBalance() {
      this.startingBalanceInput = this.$formatAmountValue(this.sources.account_balance)
    },

    saveAndReload({ closeSheet = false } = {}) {
      if (this.isRangeInvalid)
        return

      const universityFee = this.$parseAmount(this.universityFeeInput)

      if (universityFee === '' || Number.isNaN(universityFee)) {
        this.error = 'Ingresa una cuota de universidad válida.'

        return
      }

      this.saving = true
      this.error = ''

      axios
        .put('/api/projection/settings', {
          university_fee: universityFee,
          monthly_remaining: null,
        })
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
