<template>
  <!-- Móvil: registrar + filtros + buscador -->
  <template v-if="mdAndDown">
    <AccountingMobileFormSheet
      ref="mobileForm"
      :movement-types="movementTypes"
      :payment-types="paymentTypes"
      @saved="refreshAccounting"
    />

    <VContainer class="pb-0">
      <div class="d-flex align-center gap-2">
        <VBtn
          color="primary"
          rounded="lg"
          class="flex-grow-1"
          prepend-icon="ri-add-line"
          @click="openMobileForm"
        >
          Registrar movimiento
        </VBtn>
        <VBadge
          :model-value="hasSheetFilters"
          color="primary"
          dot
          location="top end"
          offset-x="4"
          offset-y="4"
        >
          <VBtn
            variant="tonal"
            rounded="lg"
            class="px-4"
            aria-label="Filtros"
            @click="filterSheet = true"
          >
            Filtros
          </VBtn>
        </VBadge>
      </div>

      <!-- Buscador móvil -->
      <VTextField
        v-model="filterDescription"
        class="mt-3"
        type="search"
        label="Buscar descripción"
        variant="outlined"
        rounded="lg"
        prepend-inner-icon="ri-search-line"
        hide-details="auto"
        clearable
        @update:model-value="onFilterDescriptionInput"
      />
    </VContainer>

    <!-- Filtros móvil -->
    <AccountingMobileFiltersSheet
      v-model="filterSheet"
      v-model:date-range="filterDateRange"
      v-model:selected-movement-types="filterMovementTypes"
      v-model:selected-payment-types="filterPaymentTypes"
      :movement-types="movementTypes"
      :payment-types="paymentTypes"
      @clear="clearSheetFilters"
    />
    <AccountingMobileEditSheet
      ref="mobileEdit"
      :movement-types="movementTypes"
      :payment-types="paymentTypes"
      @saved="refreshAccounting"
    />
  </template>

  <!-- Dialog de edición -->
  <AccountingEditDialog
    v-if="mdAndUp && editDialog"
    v-model="editDialog"
    :movement="editMovement"
    :movement-types="movementTypes"
    :payment-types="paymentTypes"
    @saved="refreshAccounting"
  />

  <VDialog
    v-model="deleteDialog"
    max-width="400"
  >
    <VCard rounded="lg">
      <VCardTitle class="text-h6">
        Eliminar movimiento
      </VCardTitle>
      <VCardText class="text-body-2">
        ¿Eliminar este movimiento? No se puede deshacer.
      </VCardText>
      <VCardActions class="px-4 pb-4">
        <VSpacer />
        <VBtn
          variant="text"
          rounded="lg"
          @click="deleteDialog = false"
        >
          Cancelar
        </VBtn>
        <VBtn
          color="error"
          variant="flat"
          rounded="lg"
          :loading="deleting"
          @click="confirmDelete"
        >
          Eliminar
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>

  <VContainer :class="mdAndDown ? 'pa-0 mt-4' : ''">
    <!-- Escritorio: alta en una sola fila -->
    <VForm
      v-if="mdAndUp"
      class="mb-4"
    >
      <VCard
        variant="outlined"
        rounded="lg"
        class="accounting-form-card"
      >
        <div class="px-4 pt-3 pb-1">
          <span class="text-body-2 font-weight-semibold">
            Nuevo movimiento
          </span>
        </div>
        <div class="px-4 pb-3 accounting-form-grid">
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
          <VTextField
            v-model="description"
            type="text"
            label="Descripción"
            variant="outlined"
            rounded="lg"
            hide-details="auto"
          />
          <VSelect
            v-model="selectedMovementType"
            label="Tipo"
            :items="movementTypes"
            variant="outlined"
            rounded="lg"
            :error-messages="errors(v$.selectedMovementType)"
            hide-details="auto"
          />
          <VSelect
            v-model="selectedPaymentType"
            label="Pago"
            :items="paymentTypes"
            variant="outlined"
            rounded="lg"
            :error-messages="errors(v$.selectedPaymentType)"
            hide-details="auto"
          />
          <VTextField
            v-currency-live
            v-model="amount"
            class="monto-with-action"
            type="text"
            inputmode="decimal"
            autocomplete="off"
            label="Monto"
            variant="outlined"
            rounded="lg"
            hide-details="auto"
            :error-messages="errors(v$.amount)"
            @blur="normalizeAmount"
            @keydown.enter.prevent="storeAccounting"
          >
            <template #append-inner>
              <VBtn
                color="primary"
                variant="flat"
                class="monto-with-action__btn rounded-s-0 rounded-e-lg"
                aria-label="Contabilizar"
                type="button"
                tabindex="-1"
                @click="storeAccounting"
              >
                <VIcon
                  icon="ri-arrow-right-line"
                  size="22"
                />
              </VBtn>
            </template>
          </VTextField>
        </div>
      </VCard>
    </VForm>

    <VCard
      variant="outlined"
      rounded="lg"
      class="accounting-table-card overflow-hidden"
    >
      <div
        v-if="mdAndUp"
        class="px-4 py-3"
      >
        <div class="d-flex align-center gap-2">
          <span class="text-body-2 font-weight-semibold flex-shrink-0 me-1">
            Movimientos
          </span>
          <VTextField
            v-model="filterDescription"
            class="flex-grow-1"
            type="search"
            label="Buscar por descripción"
            variant="outlined"
            rounded="lg"
            density="compact"
            prepend-inner-icon="ri-search-line"
            hide-details="auto"
            clearable
            @update:model-value="onFilterDescriptionInput"
          />
          <VBadge
            :model-value="hasSheetFilters"
            color="primary"
            dot
            location="top end"
            offset-x="4"
            offset-y="4"
          >
            <VBtn
              :variant="filtersExpanded ? 'flat' : 'tonal'"
              :color="filtersExpanded ? 'primary' : 'default'"
              rounded="lg"
              class="px-3"
              aria-label="Filtros"
              @click="filtersExpanded = !filtersExpanded"
            >
              Filtros
              <VIcon
                :icon="filtersExpanded ? 'ri-arrow-up-s-line' : 'ri-arrow-down-s-line'"
                size="18"
                class="ms-1"
              />
            </VBtn>
          </VBadge>
          <VBtn
            v-if="hasActiveFilters"
            variant="text"
            color="default"
            rounded="lg"
            class="px-2 flex-shrink-0"
            aria-label="Limpiar filtros"
            @click="clearFilters"
          >
            <VIcon
              icon="ri-filter-off-line"
              size="18"
            />
          </VBtn>
        </div>

        <VExpandTransition>
          <div
            v-show="filtersExpanded"
            class="accounting-filters-grid mt-3"
          >
            <VDateInput
              v-model="filterDateRange"
              label="Fechas"
              placeholder="Desde — hasta"
              multiple="range"
              variant="outlined"
              rounded="lg"
              density="compact"
              prepend-icon=""
              append-inner-icon="ri-calendar-line"
              hide-details="auto"
              clearable
              show-adjacent-months
            />
            <VSelect
              v-model="filterMovementTypes"
              label="Tipo"
              :items="movementTypes"
              variant="outlined"
              rounded="lg"
              density="compact"
              multiple
              clearable
              hide-details="auto"
            />
            <VSelect
              v-model="filterPaymentTypes"
              label="Pago"
              :items="paymentTypes"
              variant="outlined"
              rounded="lg"
              density="compact"
              multiple
              clearable
              hide-details="auto"
            />
          </div>
        </VExpandTransition>
      </div>

      <VDivider v-if="mdAndUp" />

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
            <th class="accounting-table__th accounting-table__th--actions" />
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="item in accounting"
            :key="item.id"
            class="accounting-table__row"
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
            <td class="accounting-table__actions text-end">
              <AccountingMovementMenu
                @edit="openEdit(item)"
                @delete="openDelete(item)"
              />
            </td>
          </tr>

          <tr v-if="!accounting.length && !hasMore && !loading">
            <td
              colspan="6"
              class="text-body-2 text-medium-emphasis text-center py-8"
            >
              {{ emptyListMessage }}
            </td>
          </tr>

          <!-- Carga más movimientos -->
          <tr>
            <td colspan="6">
              <VInfiniteScroll
                :key="scrollKey"
                side="end"
                @load="showAccounting"
              >
                <template #empty>
                  <div
                    v-if="accounting.length"
                    class="text-body-2 text-medium-emphasis text-center py-1"
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
                <div class="d-flex justify-space-between align-start gap-2 mb-2">
                  <span class="text-caption text-medium-emphasis">{{ item.date }}</span>
                  <div class="d-flex align-center gap-1 flex-shrink-0">
                    <VChip
                      size="small"
                      variant="tonal"
                      color="primary"
                      class="text-caption font-weight-medium"
                    >
                      {{ paymentTypeLabel(item.payment_type) }}
                    </VChip>
                    <AccountingMovementMenu
                      @edit="openEdit(item)"
                      @delete="openDelete(item)"
                    />
                  </div>
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
            {{ emptyListMessage }}
          </p>
        </div>

        <!-- Solo si ya hay items y se acabó la paginación (evita el "No more" por defecto) -->
        <template #empty>
          <div
            v-if="accounting.length"
            class="text-body-2 text-medium-emphasis text-center py-1"
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
              {{ totalCount ? `${totalCount} movimientos` : 'Sin movimientos' }}
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
        <div class="d-flex justify-space-between align-baseline gap-3 mb-3">
          <p class="accounting-totals__mobile-title text-body-2 font-weight-semibold mb-0">
            Totales
          </p>
          <span class="text-caption text-medium-emphasis">
            {{ totalCount ? `${totalCount} movimientos` : 'Sin movimientos' }}
          </span>
        </div>
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
import AccountingEditDialog from '@/views/pages/accounting/AccountingEditDialog.vue'
import AccountingMobileEditSheet from '@/views/pages/accounting/AccountingMobileEditSheet.vue'
import AccountingMobileFiltersSheet from '@/views/pages/accounting/AccountingMobileFiltersSheet.vue'
import AccountingMobileFormSheet from '@/views/pages/accounting/AccountingMobileFormSheet.vue'
import AccountingMovementMenu from '@/views/pages/accounting/AccountingMovementMenu.vue'
import { parseAmount } from '@core/utils/formatters'
import { useVuelidate } from '@vuelidate/core'
import { helpers, required } from '@vuelidate/validators'
import axios from 'axios'
import { useDisplay } from 'vuetify'

export default {
  name: 'ModuleAccounting',
  components: {
    AccountingEditDialog,
    AccountingMobileEditSheet,
    AccountingMobileFiltersSheet,
    AccountingMobileFormSheet,
    AccountingMovementMenu,
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
      saving: false,
      scrollKey: 0,
      totalDebe: 0,
      totalHaber: 0,
      totalCount: 0,
      filterDateRange: null,
      filterMovementTypes: [],
      filterPaymentTypes: [],
      filterDescription: '',
      descriptionFilterTimer: null,
      skipFilterRefresh: false,
      filterSheet: false,
      filtersExpanded: false,
      editDialog: false,
      editMovement: null,
      deleteDialog: false,
      deleteTarget: null,
      deleting: false,
    }
  },
  computed: {
    hasSheetFilters() {
      const range = this.filterDateRange

      return Boolean(
        (Array.isArray(range) && range.length)
        || this.filterMovementTypes.length
        || this.filterPaymentTypes.length,
      )
    },
    hasActiveFilters() {
      const range = this.filterDateRange

      return Boolean(
        (Array.isArray(range) && range.length)
        || this.filterMovementTypes.length
        || this.filterPaymentTypes.length
        || String(this.filterDescription).trim(),
      )
    },
    emptyListMessage() {
      return this.hasActiveFilters
        ? 'Ningún movimiento coincide con los filtros.'
        : 'No hay movimientos todavía.'
    },
  },
  watch: {
    filterDateRange() {
      if (this.skipFilterRefresh)
        return

      const range = this.filterDateRange
      if (Array.isArray(range) && range.length === 1)
        return

      this.refreshAccounting()
    },
    filterMovementTypes: {
      deep: true,
      handler() {
        if (!this.skipFilterRefresh)
          this.refreshAccounting()
      },
    },
    filterPaymentTypes: {
      deep: true,
      handler() {
        if (!this.skipFilterRefresh)
          this.refreshAccounting()
      },
    },
  },
  mounted() {
    window.addEventListener('accounting:imported', this.refreshAccounting)
  },
  beforeUnmount() {
    clearTimeout(this.descriptionFilterTimer)
    window.removeEventListener('accounting:imported', this.refreshAccounting)
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
        required: helpers.withMessage('Monto requerido', v => parseAmount(v) !== ''),
        valid: helpers.withMessage('Ingresa un monto válido', v => {
          const n = parseAmount(v)

          return n !== '' && n >= 0
        }),
      },
      selectedPaymentType: {
        required: helpers.withMessage('Tipo de pago requerido', required),
      },
    }
  },
  methods: {
    async storeAccounting() {
      if (this.saving)
        return

      this.submitted = true
      const isValid = await this.v$.$validate()

      if (!isValid) {
        return
      }

      this.saving = true

      try {
        await axios.post('/api/accounting', {
          date: this.$formatDate(this.date),
          'movement_type': this.selectedMovementType,
          'payment_type': this.selectedPaymentType,
          amount: this.$parseAmount(this.amount),
          description: this.description,
        })

        this.resetForm()
        this.refreshAccounting()
        this.$toast.success('Guardado correctamente', {
          timeout: 2000,
          closeOnClick: true,
        })
      } catch (error) {
        console.log(error)
      } finally {
        this.saving = false
      }
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
          params: {
            page,
            ...this.listFilterParams(),
          },
        })

        const rows = response.data.data ?? []

        this.accounting = [...this.accounting, ...rows]

        // totales solo vienen en la página 1 (SUM en backend)
        if (response.data.totals) {
          this.totalDebe = response.data.totals.debe ?? 0
          this.totalHaber = response.data.totals.haber ?? 0
          this.totalCount = response.data.totals.count ?? 0
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
    normalizeAmount() {
      const n = this.$parseAmount(this.amount)

      this.amount = n === '' ? '' : this.$formatAmountValue(n)
    },
    paymentTypeLabel(value) {
      const found = this.paymentTypes.find(p => p.value === value)

      return found ? found.title : value
    },
    listFilterParams() {
      const params = {}
      const range = Array.isArray(this.filterDateRange) ? this.filterDateRange.filter(Boolean) : []

      if (range.length >= 1) {
        const start = range[0]
        const end = range[1] ?? range[0]

        params.date_from = this.$formatDate(start)
        params.date_to = this.$formatDate(end)
      }

      if (this.filterMovementTypes.length)
        params.movement_type = this.filterMovementTypes

      if (this.filterPaymentTypes.length)
        params.payment_type = this.filterPaymentTypes

      const description = String(this.filterDescription).trim()
      if (description)
        params.description = description

      return params
    },
    onFilterDescriptionInput() {
      clearTimeout(this.descriptionFilterTimer)
      this.descriptionFilterTimer = setTimeout(() => {
        this.refreshAccounting()
      }, 400)
    },
    openMobileForm() {
      this.$refs.mobileForm?.openFormSheet()
    },
    clearSheetFilters() {
      this.skipFilterRefresh = true
      this.filterDateRange = null
      this.filterMovementTypes = []
      this.filterPaymentTypes = []
      this.skipFilterRefresh = false
      this.refreshAccounting()
    },
    clearFilters() {
      clearTimeout(this.descriptionFilterTimer)
      this.skipFilterRefresh = true
      this.filterDateRange = null
      this.filterMovementTypes = []
      this.filterPaymentTypes = []
      this.filterDescription = ''
      this.skipFilterRefresh = false
      this.refreshAccounting()
    },
    openEdit(item) {
      if (this.mdAndUp) {
        this.editMovement = item
        this.editDialog = true
      } else {
        this.$refs.mobileEdit?.open(item)
      }
    },
    openDelete(item) {
      this.deleteTarget = item
      this.deleteDialog = true
    },
    async confirmDelete() {
      if (this.deleting || !this.deleteTarget?.id)
        return

      this.deleting = true

      try {
        await axios.delete(`/api/accounting/${this.deleteTarget.id}`)
        this.deleteDialog = false
        this.deleteTarget = null
        this.editMovement = null
        this.refreshAccounting()
        this.$toast.success('Movimiento eliminado', { timeout: 2000, closeOnClick: true })
      } catch (error) {
        console.log(error)
      } finally {
        this.deleting = false
      }
    },
  },
}
</script>

<style scoped>
.accounting-form-card,
.accounting-table-card {
  border-color: rgba(var(--v-theme-on-surface), 0.08);
}

.accounting-form-grid {
  display: grid;
  grid-template-columns: minmax(140px, 0.9fr) minmax(180px, 1.4fr) minmax(120px, 0.9fr) minmax(120px, 0.9fr) minmax(160px, 1fr);
  gap: 0.75rem;
  align-items: start;
}

.accounting-filters-grid {
  display: grid;
  grid-template-columns: minmax(180px, 1.4fr) minmax(140px, 1fr) minmax(140px, 1fr);
  gap: 0.75rem;
  align-items: start;
}

/* Crece con el contenido; solo hace scroll al llegar al tope (evita el hueco vacío) */
.accounting-table :deep(.v-table__wrapper) {
  max-height: min(570px, 55vh);
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
  font-size: 14px;
  padding-inline: 0.75rem !important;
}

.accounting-table__th--amount + .accounting-table__th--amount,
.accounting-table__amount + .accounting-table__amount {
  padding-inline-start: 0.5rem !important;
}

.accounting-table :deep(tbody tr:nth-child(even)) {
  background: rgba(var(--v-theme-on-surface), 0.02);
}

.accounting-table__row:hover {
  background: rgba(var(--v-theme-on-surface), 0.04);
}

.accounting-table__th--actions,
.accounting-table__actions {
  width: 1%;
  white-space: nowrap;
  padding-inline: 0.25rem !important;
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
