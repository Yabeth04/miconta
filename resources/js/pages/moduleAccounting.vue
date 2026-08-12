<template>
  <!-- Escritorio: formulario inline (por defecto) -->
  <VForm v-if="mdAndUp">
    <VContainer>
      <!-- Inputs -->
      <VRow
        align="start"
        dense
      >
        <!-- Fecha -->
        <VCol
          cols="12"
          md="4"
        >
          <VDateInput
            v-model="date"
            label="Fecha"
            variant="outlined"
            rounded="lg"
            prepend-icon=""
            append-inner-icon="ri-calendar-line"
            :error-messages="errors(v$.date)"
            hide-details="auto"
            show-adjacent-months
          />
        </VCol>

        <!-- Descripción -->
        <VCol
          cols="12"
          md="4"
        >
          <VTextField
            v-model="description"
            type="text"
            label="Descripción"
            variant="outlined"
            rounded="lg"
            hide-details="auto"
          />
        </VCol>

        <!-- Tipo de movimiento -->
        <VCol
          cols="12"
          md="4"
        >
          <VSelect
            v-model="selectedMovementType"
            label="Tipo de movimiento"
            :items="movementTypes"
            variant="outlined"
            rounded="lg"
            :error-messages="errors(v$.selectedMovementType)"
            hide-details="auto"
          />
        </VCol>

        <!-- Tipo de pago -->
        <VCol
          cols="12"
          md="4"
        >
          <VSelect
            v-model="selectedPaymentType"
            label="Tipo de pago"
            :items="paymentTypes"
            variant="outlined"
            rounded="lg"
            :error-messages="errors(v$.selectedPaymentType)"
            hide-details="auto"
          />
        </VCol>

        <!-- Monto -->
        <VCol
          cols="12"
          md="4"
        >
          <VTextField
            v-model="v$.amount.$model"
            class="monto-with-action"
            type="number"
            label="Monto"
            variant="outlined"
            rounded="lg"
            hide-spin-buttons
            hide-details="auto"
            :error-messages="errors(v$.amount)"
            @keyup.enter="storeAccounting"
          >
            <template #append-inner>
              <VBtn
                color="primary"
                variant="flat"
                class="monto-with-action__btn rounded-s-0 rounded-e-lg"
                aria-label="Contabilizar"
                tabindex="-1"
                @click.stop="storeAccounting"
              >
                <VIcon
                  icon="ri-arrow-right-line"
                  size="22"
                />
              </VBtn>
            </template>
          </VTextField>
        </VCol>
      </VRow>
    </VContainer>
  </VForm>

  <!-- Móvil: formulario en bottom sheet -->
  <AccountingMobileFormSheet
    v-if="mdAndDown"
    :movement-types="movementTypes"
    :payment-types="paymentTypes"
    @saved="refreshAccounting"
  />

  <VContainer :class="mdAndDown ? 'pa-0 mt-4' : 'pa-0 mt-6'">
    <VCard
      variant="outlined"
      rounded="lg"
      class="accounting-table-card overflow-hidden"
    >
      <VDivider />

      <!-- Escritorio / tablet ancha: tabla -->
      <VTable
        v-if="mdAndUp"
        class="accounting-table"
        density="comfortable"
        fixed-header
        hover
      >
        <thead>
          <tr>
            <th
              class="accounting-table__th text-start"
              width="120px"
            >
              Fecha
            </th>
            <th class="accounting-table__th text-start">
              Descripción
            </th>
            <th class="accounting-table__th text-end accounting-table__th--amount">
              Debe / Gasto
            </th>
            <th class="accounting-table__th text-end accounting-table__th--amount">
              Haber / Ingreso
            </th>
            <th class="accounting-table__th text-start accounting-table__th--narrow">
              Tipo de pago
            </th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="item in accounting"
            :key="item.id"
          >
            <td class="text-body-2 text-medium-emphasis">
              {{ item.date }}
            </td>
            <td class="text-body-2">
              {{ item.description || '—' }}
            </td>
            <td
              class="text-end accounting-table__num accounting-table__amount"
              style="color: red;"
            >
              {{ item.movement_type === 'debe' ? $formatAmount(item.amount) : '—' }}
            </td>
            <td
              class="text-end accounting-table__num accounting-table__amount"
              style="color: green;"
            >
              {{ item.movement_type === 'haber' ? $formatAmount(item.amount) : '—' }}
            </td>
            <td>
              <VChip
                size="small"
                variant="tonal"
                color="primary"
                class="text-caption font-weight-medium"
              >
                {{ paymentTypeLabel(item.payment_type) }}
              </VChip>
            </td>
          </tr>

          <!-- Carga más movimientos -->
          <tr>
            <td colspan="5">
              <VInfiniteScroll
                :key="scrollKey"
                side="end"
                @load="showAccounting"
              >
                <template #empty>
                  <div
                    v-if="accounting.length"
                    class="text-body-2 text-medium-emphasis text-center py-4"
                  >
                    No hay más movimientos.
                  </div>
                </template>
              </VInfiniteScroll>
            </td>
          </tr>
        </tbody>
      </VTable>

      <!-- Móvil / tablet estrecha: lista de tarjetas + scroll infinito -->
      <VInfiniteScroll
        v-if="mdAndDown"
        :key="scrollKey"
        side="end"
        class="accounting-mobile-list"
        @load="showAccounting"
      >
        <div class="pa-3">
          <template v-if="accounting.length">
            <VCard
              v-for="item in accounting"
              :key="item.id"
              variant="outlined"
              rounded="lg"
              class="accounting-mobile-card mb-3"
            >
              <VCardText class="pa-4">
                <div class="d-flex justify-space-between align-center flex-wrap gap-2 mb-2">
                  <span class="text-caption text-medium-emphasis">{{ item.date }}</span>
                  <VChip
                    size="small"
                    variant="tonal"
                    color="primary"
                    class="text-caption font-weight-medium"
                  >
                    {{ paymentTypeLabel(item.payment_type) }}
                  </VChip>
                </div>
                <p class="text-body-2 mb-3">
                  {{ item.description || '—' }}
                </p>
                <div class="d-flex justify-space-between gap-4 text-body-2">
                  <div>
                    <span class="text-medium-emphasis text-caption d-block mb-1">Debe</span>
                    <span
                      class="accounting-table__num font-weight-medium"
                      style="color: red;"
                    >
                      {{ item.movement_type === 'debe' ? $formatAmount(item.amount) : '—' }}
                    </span>
                  </div>
                  <div class="text-end">
                    <span class="text-medium-emphasis text-caption d-block mb-1">Haber</span>
                    <span
                      class="accounting-table__num font-weight-medium"
                      style="color: green;"
                    >
                      {{ item.movement_type === 'haber' ? $formatAmount(item.amount) : '—' }}
                    </span>
                  </div>
                </div>
              </VCardText>
            </VCard>
          </template>
          <p
            v-else
            class="text-body-2 text-medium-emphasis text-center py-8"
          >
            No hay movimientos todavía.
          </p>
        </div>

        <!-- Solo si ya hay items y se acabó la paginación (evita el "No more" por defecto) -->
        <template #empty>
          <div
            v-if="accounting.length"
            class="text-body-2 text-medium-emphasis text-center py-4"
          >
            No hay más movimientos.
          </div>
        </template>
      </VInfiniteScroll>

      <VDivider />

      <!-- Totales pc -->
      <div
        v-if="mdAndUp"
        class="accounting-totals accounting-totals--desktop px-4 py-3"
      >
        <div class="accounting-totals__desktop-grid">
          <div class="accounting-totals__desktop-icon-wrap d-flex align-center justify-center">
            <VIcon
              class="accounting-totals__desktop-icon text-medium-emphasis"
              icon="ri-funds-line"
              size="18"
            />
          </div>
          <div class="accounting-totals__desktop-heading d-flex flex-column justify-center min-w-0">
            <span class="text-body-1 font-weight-semibold text-high-emphasis">
              Totales
            </span>
            <span class="text-caption text-medium-emphasis">
              Todos los movimientos
            </span>
          </div>
          <div class="accounting-totals__desktop-metric text-end">
            <span class="accounting-totals__col-label d-block accounting-totals__col-label--tight">
              Debe / Gasto
            </span>
            <span
              class="accounting-totals__desktop-value accounting-table__num text-body-1 font-weight-semibold"
              style="color: red;"
            >
              {{ $formatAmount(totalDebe) }}
            </span>
          </div>
          <div class="accounting-totals__desktop-metric text-end">
            <span class="accounting-totals__col-label d-block accounting-totals__col-label--tight">
              Haber / Ingreso
            </span>
            <span
              class="accounting-totals__desktop-value accounting-table__num text-body-1 font-weight-semibold"
              style="color: green;"
            >
              {{ $formatAmount(totalHaber) }}
            </span>
          </div>
          <div class="accounting-totals__desktop-tail d-flex align-center text-medium-emphasis text-caption" />
        </div>
      </div>

      <!-- Totales mobile -->
      <div
        v-if="mdAndDown"
        class="accounting-totals accounting-totals--mobile pa-4"
      >
        <p class="accounting-totals__mobile-title text-body-2 font-weight-semibold mb-3">
          Totales
        </p>
        <div class="d-flex justify-space-between align-center gap-4">
          <div>
            <span class="text-medium-emphasis text-caption d-block mb-1">Debe / Gasto</span>
            <span
              class="accounting-table__num text-body-2 font-weight-medium"
              style="color: red;"
            >
              {{ $formatAmount(totalDebe) }}
            </span>
          </div>
          <div class="text-end">
            <span class="text-medium-emphasis text-caption d-block mb-1">Haber / Ingreso</span>
            <span
              class="accounting-table__num text-body-2 font-weight-medium"
              style="color: green;"
            >
              {{ $formatAmount(totalHaber) }}
            </span>
          </div>
        </div>
      </div>
    </VCard>
  </VContainer>
</template>

<script>
import submittedVuelidateForm from '@/mixins/submittedVuelidateForm'
import AccountingMobileFormSheet from '@/views/pages/accounting/AccountingMobileFormSheet.vue'
import { useVuelidate } from '@vuelidate/core'
import { decimal, helpers, required } from '@vuelidate/validators'
import axios from 'axios'
import { useDisplay } from 'vuetify'

export default {
  name: 'ModuleAccounting',
  components: {
    AccountingMobileFormSheet,
  },
  mixins: [submittedVuelidateForm],

  setup() {
    const { mdAndUp, mdAndDown } = useDisplay()

    return {
      v$: useVuelidate(),
      mdAndUp, // para usar en el campo monto (pantallas md y superiores)
      mdAndDown, // para usar en el formulario móvil (pantallas md y menores)
    }
  },

  data() {
    return {
      movementTypes: [
        { title: 'Ingreso', value: 'haber' },
        { title: 'Gasto', value: 'debe' },
      ],
      paymentTypes: [
        { title: 'Sinpe', value: 'sinpe' },
        { title: 'Efectivo', value: 'efectivo' },
        { title: 'Tarjeta', value: 'tarjeta' },
        { title: 'Transferencia', value: 'transferencia' },
        { title: 'Otros', value: 'otros' },
      ],
      date: new Date(),
      selectedPaymentType: null,
      selectedMovementType: null,
      amount: '',
      description: '',
      accounting: [],
      page: 1,
      hasMore: true,
      loading: false,
      scrollKey: 0,
      totalDebe: 0,
      totalHaber: 0,
    }
  },
  validations() {
    return {
      date: {
        required: helpers.withMessage('Fecha requerida', required),
      },
      selectedMovementType: {
        required: helpers.withMessage('Tipo de movimiento requerido', required),
      },
      amount: {
        required: helpers.withMessage('Monto requerido', required),
        decimal: helpers.withMessage('Ingresa un monto válido', decimal),
      },
      selectedPaymentType: {
        required: helpers.withMessage('Tipo de pago requerido', required),
      },
    }
  },
  methods: {
    async storeAccounting() {
      this.submitted = true
      const isValid = await this.v$.$validate()

      if (!isValid) {
        return
      }

      axios
        .post('/api/accounting', {
          date: this.$formatDate(this.date),
          'movement_type': this.selectedMovementType,
          'payment_type': this.selectedPaymentType,
          amount: this.amount,
          description: this.description,
        })
        .then(() => {
          this.resetForm()
          this.refreshAccounting()
          this.$toast.success('Guardado correctamente', {
            timeout: 2000,
            closeOnClick: true,
            // pauseOnHover: true, hace que se pause el hover
            // draggable: true, hace que se pueda arrastrar el toast
            // maxToasts: 3, limita que se pueda mostrar un maximo de 3 toasts
            // newestOnTop: true, hace que se muestre el toast mas nuevo al principio
          })
        })
        .catch(error => {
          console.log(error)
        })
    },
    refreshAccounting() {
      this.page = 1
      this.accounting = []
      this.hasMore = true
      this.loading = false
      this.scrollKey += 1
    },
    async showAccounting({ done } = {}) {
      if (this.loading) {
        if (done)
          done('ok')

        return
      }

      if (!this.hasMore) {
        if (done)
          done('empty')

        return
      }

      this.loading = true
      const page = this.page

      try {
        const response = await axios.get('/api/accounting', {
          params: { page },
        })

        const rows = response.data.data ?? []

        this.accounting = [...this.accounting, ...rows]

        // totales solo vienen en la página 1 (SUM en backend)
        if (response.data.totals) {
          this.totalDebe = response.data.totals.debe ?? 0
          this.totalHaber = response.data.totals.haber ?? 0
        }

        const next = response.data.next_page_url

        if (next) {
          this.page = page + 1
          this.hasMore = true
          if (done)
            done('ok')
        } else {
          this.hasMore = false
          if (done)
            done('empty')
        }
      } catch (error) {
        console.log(error)
        if (done)
          done('error')
      } finally {
        this.loading = false
      }
    },
    resetForm() {
      this.date = new Date()
      this.selectedMovementType = null
      this.selectedPaymentType = null
      this.amount = ''
      this.description = ''
      this.submitted = false
      this.v$.$reset()
    },
    paymentTypeLabel(value) {
      const found = this.paymentTypes.find(p => p.value === value)

      return found ? found.title : value
    },
  },
}
</script>

<style scoped>
.accounting-table-card {
  border-color: rgba(var(--v-theme-on-surface), 0.08);
}

/* Crece con el contenido; solo hace scroll al llegar al tope (evita el hueco vacío) */
.accounting-table :deep(.v-table__wrapper) {
  max-height: min(400px, 55vh);
  overflow-y: auto;
}

.accounting-table :deep(thead th) {
  position: sticky;
  top: 0;
  z-index: 1;
  box-shadow: 0 1px 0 rgba(var(--v-theme-on-surface), 0.08);
}

.accounting-table__th {
  font-size: 0.75rem !important;
  font-weight: 600 !important;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  color: rgba(var(--v-theme-on-surface), 0.65) !important;
  background: rgb(var(--v-theme-surface)) !important;
  white-space: nowrap;
}

.accounting-table__th--narrow {
  width: 1%;
  white-space: nowrap;
}

/* Montos compactos y juntos a la derecha */
.accounting-table__th--amount,
.accounting-table__amount {
  width: 1%;
  white-space: nowrap;
  padding-inline: 0.75rem !important;
}

.accounting-table__th--amount + .accounting-table__th--amount,
.accounting-table__amount + .accounting-table__amount {
  padding-inline-start: 0.5rem !important;
}

.accounting-table :deep(tbody tr:nth-child(even)) {
  background: rgba(var(--v-theme-on-surface), 0.02);
}

.accounting-table__num {
  font-variant-numeric: tabular-nums;
  font-feature-settings: 'tnum';
}

.accounting-mobile-list {
  max-height: min(520px, 65vh);
}

.accounting-totals {
  background: rgba(var(--v-theme-on-surface), 0.03);
}

.accounting-totals--desktop {
  box-shadow: inset 0 1px 0 rgba(var(--v-theme-on-surface), 0.06);
}

.accounting-totals__desktop-grid {
  display: grid;
  grid-template-columns: 120px 1fr auto auto auto;
  column-gap: 0.5rem;
  align-items: center;
}

.accounting-totals__desktop-icon-wrap {
  align-self: center;
}

.accounting-totals__desktop-heading {
  gap: 0.125rem;
  line-height: 1.25;
}

.accounting-totals__desktop-icon {
  opacity: 0.85;
}

.accounting-totals__col-label {
  font-size: 0.6875rem;
  font-weight: 600;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  color: rgba(var(--v-theme-on-surface), 0.65);
  line-height: 1.15;
}

.accounting-totals__col-label--tight {
  margin-bottom: 2px;
}

.accounting-totals__desktop-metric {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  justify-content: center;
  padding: 0.25rem 0.5rem;
  border-radius: 6px;
  background: rgba(var(--v-theme-on-surface), 0.025);
  border: thin solid rgba(var(--v-theme-on-surface), 0.06);
}

.accounting-totals__desktop-value {
  line-height: 1.2;
}

.accounting-totals__desktop-tail {
  justify-content: flex-start;
  align-self: center;
  white-space: nowrap;
}

.accounting-totals__mobile-title {
  color: rgba(var(--v-theme-on-surface), 0.87);
}

/* Anula el --v-field-padding-end reducido que pone .v-field--appended (suele ser ~6px) */
.monto-with-action :deep(.v-field.v-field--appended) {
  --v-field-padding-end: var(--v-field-padding-start, 16px);
}

/* Integra el botón en el campo sin cambiar variant/outline nativos de Vuetify */
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
