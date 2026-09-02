<template>
  <div>
    <div class="d-flex flex-wrap align-center justify-space-between gap-3 mb-4">
      <div>
        <h1 class="text-h4 font-weight-medium mb-1">
          Historial de cierres
        </h1>
        <p class="text-body-2 text-medium-emphasis mb-0">
          Congela el saldo de cada mes. Los meses cerrados no se pueden editar.
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
      :loading="loadingPreview"
    >
      <VCardText>
        <p class="text-caption text-medium-emphasis mb-3">
          Cerrar un mes
        </p>

        <div class="month-closes__close-row">
          <VSelect
            v-model="closeYear"
            class="month-closes__year"
            :items="yearOptions"
            label="Año"
            variant="outlined"
            rounded="lg"
            hide-details
          />
          <VSelect
            v-model="closeMonth"
            class="month-closes__month"
            :items="monthOptions"
            item-title="title"
            item-value="value"
            label="Mes"
            variant="outlined"
            rounded="lg"
            hide-details
          />
          <VBtn
            class="month-closes__action"
            color="primary"
            rounded="lg"
            prepend-icon="ri-lock-2-line"
            :loading="closing"
            :disabled="!canCloseSelected"
            @click="closeMonthAction"
          >
            Cerrar mes
          </VBtn>
        </div>

        <div
          v-if="preview"
          class="month-closes__preview mt-4"
        >
          <VAlert
            v-if="preview.closed"
            type="warning"
            variant="tonal"
            rounded="lg"
            class="mb-0"
            density="compact"
          >
            {{ preview.label }} ya está cerrado. Reabrilo para cambiar el saldo o los movimientos.
          </VAlert>

          <div
            v-else
            class="month-closes__preview-grid"
          >
            <div class="month-closes__balance-field">
              <VTextField
                v-currency-live
                v-model="closingBalanceInput"
                type="text"
                inputmode="decimal"
                autocomplete="off"
                label="Saldo al cierre"
                variant="outlined"
                rounded="lg"
                hide-details="auto"
                @blur="normalizeClosingBalance"
              />
              <p class="text-caption text-medium-emphasis mb-0 mt-1">
                Calculado: {{ $formatAmount(preview.live.closing_balance) }}
                <button
                  type="button"
                  class="month-closes__link"
                  @click="useCalculatedBalance"
                >
                  Usar
                </button>
              </p>
            </div>
            <div>
              <p class="text-caption text-medium-emphasis mb-1">
                Ingresos
              </p>
              <p class="text-body-1 font-weight-medium mb-0 text-success">
                {{ $formatAmount(preview.live.total_haber) }}
              </p>
            </div>
            <div>
              <p class="text-caption text-medium-emphasis mb-1">
                Gastos
              </p>
              <p class="text-body-1 font-weight-medium mb-0 text-error">
                {{ $formatAmount(preview.live.total_debe) }}
              </p>
            </div>
            <div>
              <p class="text-caption text-medium-emphasis mb-1">
                Movimientos
              </p>
              <p class="text-body-1 font-weight-medium mb-0">
                {{ preview.live.movements_count }}
              </p>
            </div>
          </div>
        </div>
      </VCardText>
    </VCard>

    <VCard
      rounded="lg"
      class="month-closes-table-card overflow-hidden"
      :loading="loading"
    >
      <VCardText class="pb-2">
        <p class="text-subtitle-2 font-weight-medium mb-0">
          Meses cerrados
        </p>
      </VCardText>

      <div
        v-if="!loading && closes.length === 0"
        class="text-center py-12 text-medium-emphasis"
      >
        Todavía no hay cierres. Elegí un mes y pulsá “Cerrar mes”.
      </div>

      <template v-else-if="!loading">
        <div
          class="month-closes__scroll d-none d-md-block"
          :class="{ 'month-closes__scroll--more': desktopHasMore }"
        >
          <VTable
            ref="desktopTable"
            class="month-closes__table"
          >
            <thead>
              <tr>
                <th>Mes</th>
                <th class="text-end">
                  Ingresos
                </th>
                <th class="text-end">
                  Gastos
                </th>
                <th class="text-end">
                  Saldo cierre
                </th>
                <th class="text-end">
                  Movs.
                </th>
                <th />
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="item in closes"
                :key="item.id"
              >
                <td>
                  <div class="font-weight-medium">
                    {{ item.label }}
                  </div>
                  <div class="text-caption text-medium-emphasis">
                    Cerrado {{ formatClosedAt(item.closed_at) }}
                  </div>
                </td>
                <td class="text-end text-success">
                  {{ $formatAmount(item.total_haber) }}
                </td>
                <td class="text-end text-error">
                  {{ $formatAmount(item.total_debe) }}
                </td>
                <td class="text-end font-weight-semibold">
                  {{ $formatAmount(item.closing_balance) }}
                </td>
                <td class="text-end">
                  {{ item.movements_count }}
                </td>
                <td class="text-end">
                  <VBtn
                    size="small"
                    variant="tonal"
                    color="warning"
                    rounded="lg"
                    prepend-icon="ri-lock-unlock-line"
                    :loading="reopeningId === item.id"
                    @click="reopen(item)"
                  >
                    Reabrir
                  </VBtn>
                </td>
              </tr>
            </tbody>
          </VTable>
          <div
            v-if="desktopHasMore"
            class="month-closes__more-hint"
          >
            <VIcon
              icon="ri-arrow-down-line"
              size="18"
            />
            Deslizá para ver más
          </div>
        </div>

        <div
          class="month-closes__scroll d-md-none"
          :class="{ 'month-closes__scroll--more': mobileHasMore }"
        >
          <div
            ref="mobileList"
            class="month-closes__list"
            @scroll.passive="updateMobileScrollHint"
          >
          <div
            v-for="item in closes"
            :key="`m-${item.id}`"
            class="month-closes__card"
          >
            <div class="d-flex align-start justify-space-between gap-2 mb-2">
              <div>
                <p class="font-weight-medium mb-0">
                  {{ item.label }}
                </p>
                <p class="text-caption text-medium-emphasis mb-0">
                  Cerrado {{ formatClosedAt(item.closed_at) }}
                </p>
              </div>
              <VBtn
                size="small"
                variant="tonal"
                color="warning"
                rounded="lg"
                prepend-icon="ri-lock-unlock-line"
                :loading="reopeningId === item.id"
                @click="reopen(item)"
              >
                Reabrir
              </VBtn>
            </div>
            <div class="month-closes__card-grid">
              <div class="month-closes__card-cell">
                <p class="text-caption text-medium-emphasis mb-0">
                  Ingresos
                </p>
                <p class="text-body-2 text-success mb-0">
                  {{ $formatAmount(item.total_haber) }}
                </p>
              </div>
              <div class="month-closes__card-cell month-closes__card-cell--end">
                <p class="text-caption text-medium-emphasis mb-0">
                  Gastos
                </p>
                <p class="text-body-2 text-error mb-0">
                  {{ $formatAmount(item.total_debe) }}
                </p>
              </div>
              <div class="month-closes__card-cell">
                <p class="text-caption text-medium-emphasis mb-0">
                  Saldo cierre
                </p>
                <p class="text-body-2 font-weight-semibold mb-0">
                  {{ $formatAmount(item.closing_balance) }}
                </p>
              </div>
              <div class="month-closes__card-cell month-closes__card-cell--end">
                <p class="text-caption text-medium-emphasis mb-0">
                  Movs.
                </p>
                <p class="text-body-2 mb-0">
                  {{ item.movements_count }}
                </p>
              </div>
            </div>
          </div>
          </div>
          <div
            v-if="mobileHasMore"
            class="month-closes__more-hint"
          >
            <VIcon
              icon="ri-arrow-down-line"
              size="18"
            />
            Deslizá para ver más
          </div>
        </div>
      </template>
    </VCard>

    <VDialog
      v-model="reopenDialog"
      max-width="440"
    >
      <VCard rounded="lg">
        <VCardTitle class="text-h6">
          ¿Reabrir {{ reopenTarget?.label }}?
        </VCardTitle>
        <VCardText>
          Podrás editar movimientos y volver a cerrar con el saldo que quieras. Si hay cierres posteriores, también se reabrirán.
        </VCardText>
        <VCardActions>
          <VSpacer />
          <VBtn
            variant="text"
            rounded="lg"
            @click="reopenDialog = false"
          >
            Cancelar
          </VBtn>
          <VBtn
            color="warning"
            rounded="lg"
            prepend-icon="ri-lock-unlock-line"
            :loading="reopeningId !== null"
            @click="confirmReopen"
          >
            Reabrir
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </div>
</template>

<script>
import { axios } from '@/plugins/axios';

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
  name: 'ModuleMonthCloses',

  data() {
    const now = new Date()

    return {
      loading: false,
      loadingPreview: false,
      closing: false,
      error: '',
      closes: [],
      currentYear: now.getFullYear(),
      currentMonth: now.getMonth() + 1,
      closeYear: now.getFullYear(),
      closeMonth: now.getMonth() + 1,
      closingBalanceInput: '',
      preview: null,
      monthOptions: MONTH_OPTIONS,
      reopenDialog: false,
      reopenTarget: null,
      reopeningId: null,
      desktopHasMore: false,
      mobileHasMore: false,
    }
  },

  computed: {
    yearOptions() {
      const years = []
      for (let y = this.currentYear; y >= this.currentYear - 5; y -= 1)
        years.push(y)

      return years
    },

    canCloseSelected() {
      if (!this.preview || this.preview.closed)
        return false

      const selected = this.closeYear * 12 + this.closeMonth
      const current = this.currentYear * 12 + this.currentMonth

      return selected <= current
    },
  },

  watch: {
    closeYear() {
      this.loadPreview()
    },
    closeMonth() {
      this.loadPreview()
    },
  },

  created() {
    this.loadCloses()
    this.loadPreview()
  },

  mounted() {
    window.addEventListener('resize', this.refreshScrollHints)
  },

  beforeUnmount() {
    window.removeEventListener('resize', this.refreshScrollHints)
    this.unbindDesktopScroll()
  },

  methods: {
    desktopScrollEl() {
      const table = this.$refs.desktopTable
      const root = table?.$el || table

      return root?.querySelector?.('.v-table__wrapper') || null
    },

    bindDesktopScroll() {
      this.unbindDesktopScroll()
      const el = this.desktopScrollEl()
      if (!el)
        return

      this._desktopScrollEl = el
      this._desktopScrollHandler = () => this.updateDesktopScrollHint()
      el.addEventListener('scroll', this._desktopScrollHandler, { passive: true })
      this.updateDesktopScrollHint()
    },

    unbindDesktopScroll() {
      if (this._desktopScrollEl && this._desktopScrollHandler) {
        this._desktopScrollEl.removeEventListener('scroll', this._desktopScrollHandler)
      }
      this._desktopScrollEl = null
      this._desktopScrollHandler = null
    },

    isElementVisible(el) {
      if (!el)
        return false

      const style = window.getComputedStyle(el)

      return style.display !== 'none' && style.visibility !== 'hidden' && el.clientHeight > 0
    },

    hasMoreBelow(el) {
      if (!this.isElementVisible(el))
        return false

      const overflow = el.scrollHeight - el.clientHeight
      if (overflow <= 8)
        return false

      return el.scrollTop < overflow - 8
    },

    updateDesktopScrollHint() {
      const el = this.desktopScrollEl()
      if (el) {
        this.desktopHasMore = this.hasMoreBelow(el)

        return
      }

      this.desktopHasMore = this.closes.length > 4
        && window.matchMedia('(min-width: 960px)').matches
    },

    updateMobileScrollHint() {
      const el = this.$refs.mobileList
      if (el) {
        this.mobileHasMore = this.hasMoreBelow(el)

        return
      }

      this.mobileHasMore = this.closes.length > 3
        && window.matchMedia('(max-width: 959px)').matches
    },

    refreshScrollHints() {
      this.$nextTick(() => {
        requestAnimationFrame(() => {
          this.bindDesktopScroll()
          this.updateDesktopScrollHint()
          this.updateMobileScrollHint()
        })
      })
    },

    loadCloses() {
      this.loading = true
      this.error = ''

      return axios
        .get('/api/month-closes')
        .then(response => {
          this.closes = response.data.closes || []
          this.currentYear = response.data.current?.year ?? this.currentYear
          this.currentMonth = response.data.current?.month ?? this.currentMonth
          this.refreshScrollHints()
        })
        .catch(error => {
          this.error = error.response?.data?.message
            || error.response?.data?.errors?.month?.[0]
            || 'No se pudo cargar el historial.'
        })
        .finally(() => {
          this.loading = false
          this.refreshScrollHints()
        })
    },

    loadPreview() {
      const selected = this.closeYear * 12 + this.closeMonth
      const current = this.currentYear * 12 + this.currentMonth

      if (selected > current) {
        this.preview = null

        return Promise.resolve()
      }

      this.loadingPreview = true

      return axios
        .get('/api/month-closes/preview', {
          params: { year: this.closeYear, month: this.closeMonth },
        })
        .then(response => {
          this.preview = response.data
          if (!response.data.closed) {
            this.closingBalanceInput = this.$formatAmountValue(response.data.live.closing_balance)
          }
        })
        .catch(error => {
          this.preview = null
          this.error = error.response?.data?.message
            || error.response?.data?.errors?.month?.[0]
            || 'No se pudo cargar la vista previa.'
        })
        .finally(() => {
          this.loadingPreview = false
        })
    },

    useCalculatedBalance() {
      if (!this.preview?.live)
        return

      this.closingBalanceInput = this.$formatAmountValue(this.preview.live.closing_balance)
    },

    normalizeClosingBalance() {
      const n = this.$parseAmount(this.closingBalanceInput)
      this.closingBalanceInput = n === '' ? '' : this.$formatAmountValue(n)
    },

    closeMonthAction() {
      if (!this.canCloseSelected || this.closing)
        return

      const closing = this.$parseAmount(this.closingBalanceInput)
      if (closing === '' || Number.isNaN(closing)) {
        this.error = 'Indicá un saldo al cierre válido.'

        return
      }

      this.closing = true
      this.error = ''

      axios
        .post('/api/month-closes', {
          year: this.closeYear,
          month: this.closeMonth,
          closing_balance: closing,
        })
        .then(response => {
          this.$toast.success(response.data.message || 'Mes cerrado', {
            timeout: 2500,
            closeOnClick: true,
          })

          return Promise.all([this.loadCloses(), this.loadPreview()])
        })
        .catch(error => {
          this.error = error.response?.data?.message
            || error.response?.data?.errors?.month?.[0]
            || error.response?.data?.errors?.closing_balance?.[0]
            || 'No se pudo cerrar el mes.'
        })
        .finally(() => {
          this.closing = false
        })
    },

    reopen(item) {
      this.reopenTarget = item
      this.reopenDialog = true
    },

    confirmReopen() {
      if (!this.reopenTarget || this.reopeningId !== null)
        return

      this.reopeningId = this.reopenTarget.id
      this.error = ''

      axios
        .delete(`/api/month-closes/${this.reopenTarget.id}`)
        .then(response => {
          this.reopenDialog = false
          this.reopenTarget = null
          this.$toast.success(response.data.message || 'Mes reabierto', {
            timeout: 2500,
            closeOnClick: true,
          })

          return Promise.all([this.loadCloses(), this.loadPreview()])
        })
        .catch(error => {
          this.error = error.response?.data?.message
            || 'No se pudo reabrir el mes.'
        })
        .finally(() => {
          this.reopeningId = null
        })
    },

    formatClosedAt(value) {
      if (!value)
        return ''

      const date = new Date(value)
      if (Number.isNaN(date.getTime()))
        return ''

      return date.toLocaleDateString('es-CR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
      })
    },
  },
}
</script>

<style scoped>
.month-closes__close-row {
  display: grid;
  grid-template-columns: minmax(0, 0.9fr) minmax(0, 1.3fr);
  gap: 0.75rem;
  align-items: center;
}

.month-closes__year,
.month-closes__month {
  width: 100%;
  min-width: 0;
}

.month-closes__action {
  grid-column: 1 / -1;
  width: 100%;
}

@media (min-width: 960px) {
  .month-closes__close-row {
    display: flex;
    flex-wrap: wrap;
  }

  .month-closes__year {
    width: 7.5rem;
    flex: 0 0 auto;
  }

  .month-closes__month {
    width: 11rem;
    flex: 0 0 auto;
  }

  .month-closes__action {
    grid-column: auto;
    width: auto;
    flex: 0 0 auto;
  }
}

.month-closes__preview-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1rem;
}

@media (min-width: 960px) {
  .month-closes__preview-grid {
    grid-template-columns: repeat(4, minmax(0, 1fr));
  }
}

.month-closes__balance-field {
  grid-column: 1 / -1;
}

@media (min-width: 960px) {
  .month-closes__balance-field {
    grid-column: auto;
  }
}

.month-closes__link {
  border: 0;
  background: transparent;
  color: rgb(var(--v-theme-primary));
  cursor: pointer;
  font: inherit;
  padding: 0;
  text-decoration: underline;
}

.month-closes__table :deep(th),
.month-closes__table :deep(td) {
  white-space: nowrap;
}

/* Ventana con scroll (como movimientos) */
.month-closes__scroll {
  position: relative;
}

.month-closes__scroll::after {
  content: '';
  position: absolute;
  left: 0;
  right: 0;
  bottom: 0;
  height: 52px;
  pointer-events: none;
  opacity: 0;
  transition: opacity 0.2s ease;
  background: linear-gradient(
    to bottom,
    transparent,
    rgb(var(--v-theme-surface))
  );
  z-index: 2;
}

.month-closes__scroll--more::after {
  opacity: 1;
}

.month-closes__more-hint {
  position: absolute;
  left: 50%;
  bottom: 10px;
  z-index: 3;
  transform: translateX(-50%);
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.35rem 0.85rem;
  border-radius: 999px;
  font-size: 0.8125rem;
  font-weight: 600;
  white-space: nowrap;
  pointer-events: none;
  color: rgb(var(--v-theme-on-surface));
  background: color-mix(in srgb, rgb(var(--v-theme-surface)) 88%, rgb(var(--v-theme-primary)) 12%);
  border: thin solid rgba(var(--v-border-color), var(--v-border-opacity));
  box-shadow: 0 4px 14px rgba(0, 0, 0, 0.22);
}

.month-closes__table :deep(.v-table__wrapper) {
  max-height: min(360px, 48vh);
  overflow-y: auto;
}

.month-closes__table :deep(thead th) {
  position: sticky;
  top: 0;
  z-index: 1;
  background: rgb(var(--v-theme-surface));
  box-shadow: 0 1px 0 rgba(var(--v-theme-on-surface), 0.08);
}

.month-closes__list {
  display: flex;
  flex-direction: column;
  gap: 10px;
  padding: 4px 16px 20px;
  max-height: min(460px, 58vh);
  overflow-y: auto;
}

.month-closes__card {
  padding: 14px;
  border: thin solid rgba(var(--v-border-color), var(--v-border-opacity));
  border-radius: 12px;
  background: rgb(var(--v-theme-surface));
  flex-shrink: 0;
}

.month-closes__card-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  column-gap: 1rem;
  row-gap: 0.75rem;
  width: 100%;
}

.month-closes__card-cell {
  min-width: 0;
}

.month-closes__card-cell--end {
  text-align: right;
  justify-self: end;
}
</style>
