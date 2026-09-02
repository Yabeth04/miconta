<template>
  <div>
    <div class="d-flex flex-wrap align-center justify-space-between gap-3 mb-4">
      <div>
        <h1 class="text-h4 font-weight-medium mb-1">
          Pagos fijos
        </h1>
        <p class="text-body-2 text-medium-emphasis mb-0">
          Compromisos obligatorios del mes
        </p>
      </div>
      <VBtn
        color="primary"
        rounded="lg"
        prepend-icon="ri-add-line"
        @click="openNewItem()"
      >
        Agregar pago
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

    <VRow class="mb-4">
      <VCol
        cols="12"
        md="6"
      >
        <VCard
          rounded="lg"
          :loading="loading"
        >
          <VCardText>
            <p class="text-caption text-medium-emphasis mb-3">
              Salario del mes
            </p>

            <div class="fixed-payments__salary-row">
              <VSelect
                v-model="salaryYear"
                class="fixed-payments__salary-year"
                :items="salaryYearOptions"
                label="Año"
                variant="outlined"
                rounded="lg"
                hide-details
              />
              <VTextField
                v-currency-live
                v-model="salaryInput"
                class="fixed-payments__salary-amount"
                type="text"
                inputmode="decimal"
                autocomplete="off"
                label="Al mes"
                variant="outlined"
                rounded="lg"
                hide-details
                :disabled="savingSalary"
                @blur="normalizeSalary"
                @keydown.enter.prevent="saveSalary"
              />
              <VBtn
                class="fixed-payments__salary-save"
                variant="tonal"
                rounded="lg"
                :loading="savingSalary"
                @click="saveSalary"
              >
                Guardar
              </VBtn>
            </div>

            <p class="text-caption text-medium-emphasis mb-0 mt-2">
              Quincena (1 y 15): {{ $formatAmount(paydayPreview) }}
            </p>
          </VCardText>
        </VCard>
      </VCol>

      <VCol
        cols="12"
        md="6"
      >
        <VCard
          rounded="lg"
          :loading="loading"
          variant="outlined"
          class="h-100 fixed-payments__remaining-card"
        >
          <VCardText class="d-flex flex-column justify-center h-100">
            <p class="text-caption text-medium-emphasis mb-1">
              Quedan libres al mes
            </p>
            <p
              class="text-h4 font-weight-semibold mb-1 fixed-payments__num"
              :class="totals.remaining >= 0 ? 'fixed-payments__amount--positive' : 'fixed-payments__amount--negative'"
            >
              {{ $formatAmount(totals.remaining) }}
            </p>
            <p class="text-caption text-medium-emphasis mb-0">
              Salario {{ $formatAmount(settings.monthly_salary) }}
              − gastos {{ $formatAmount(totals.expenses) }}
            </p>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <div
      v-if="loading"
      class="text-center py-12 text-medium-emphasis"
    >
      Cargando…
    </div>

    <VRow v-else>
      <VCol
        v-for="group in paymentGroups"
        :key="group.key"
        cols="12"
        lg="6"
      >
        <VCard
          rounded="lg"
          variant="outlined"
          class="h-100 fixed-payments__group-card"
        >
          <VCardTitle
            class="text-body-1 font-weight-semibold py-3 px-4"
            :class="group.headerClass"
          >
            {{ group.title }}
          </VCardTitle>

          <VDivider />

          <div
            v-if="!groups[group.key]?.length"
            class="text-center py-8 text-medium-emphasis text-body-2"
          >
            Sin pagos en este grupo
          </div>

          <VList
            v-else
            class="py-2"
            lines="two"
          >
            <VListItem
              v-for="item in groups[group.key]"
              :key="item.id"
              class="px-4"
            >
              <VListItemTitle class="d-flex flex-wrap align-center gap-2 font-weight-medium">
                <span>{{ item.description }}</span>
                <VChip
                  size="x-small"
                  :color="group.chipColor"
                  variant="tonal"
                  label
                >
                  {{ item.due_label }}
                </VChip>
              </VListItemTitle>
              <VListItemSubtitle class="fixed-payments__num">
                {{ $formatAmount(item.amount) }}
              </VListItemSubtitle>

              <template #append>
                <VBtn
                  icon
                  variant="text"
                  size="small"
                  aria-label="Editar"
                  @click="openEditItem(item)"
                >
                  <VIcon icon="ri-pencil-line" />
                </VBtn>
                <VBtn
                  icon
                  variant="text"
                  size="small"
                  aria-label="Eliminar"
                  :loading="deletingId === item.id"
                  @click="confirmDelete(item)"
                >
                  <VIcon icon="ri-delete-bin-line" />
                </VBtn>
              </template>
            </VListItem>
          </VList>

          <VDivider />

          <div class="d-flex justify-space-between align-center px-4 py-3 fixed-payments__subtotal">
            <span class="text-body-2 font-weight-medium">
              Subtotal
            </span>
            <span class="text-body-1 font-weight-semibold fixed-payments__num">
              {{ $formatAmount(totals[group.totalKey]) }}
            </span>
          </div>
        </VCard>
      </VCol>
    </VRow>

    <VCard
      v-if="!loading"
      rounded="lg"
      variant="outlined"
      class="mt-4 fixed-payments__total-card"
    >
      <VCardText class="d-flex flex-wrap justify-space-between align-center gap-3 py-4">
        <span class="text-body-2 font-weight-medium">
          Total gastos fijos
        </span>
        <span class="text-h6 font-weight-semibold fixed-payments__num fixed-payments__total-value">
          {{ $formatAmount(totals.expenses) }}
        </span>
      </VCardText>
    </VCard>

    <!-- Agregar / editar -->
    <VDialog
      v-model="itemDialog"
      max-width="480"
    >
      <VCard rounded="lg">
        <VCardTitle class="text-h6 px-5 pt-5 pb-3">
          {{ editingItem ? 'Editar pago' : 'Nuevo pago' }}
        </VCardTitle>

        <VDivider />

        <VCardText class="pa-5 d-flex flex-column gap-4">
          <VTextField
            v-model="form.description"
            label="Descripción"
            variant="outlined"
            rounded="lg"
            hide-details="auto"
            maxlength="255"
          />

          <VTextField
            v-currency-live
            v-model="form.amount"
            type="text"
            inputmode="decimal"
            autocomplete="off"
            label="Monto"
            variant="outlined"
            rounded="lg"
            hide-details="auto"
            @blur="normalizeFormAmount"
          />

          <VSelect
            v-model="form.payment_group"
            :items="groupOptions"
            item-title="title"
            item-value="value"
            label="Grupo de pago"
            variant="outlined"
            rounded="lg"
            hide-details="auto"
          />

          <VSelect
            v-model="form.due_label"
            :items="dueLabelOptions"
            item-title="title"
            item-value="value"
            label="Cuándo se paga"
            variant="outlined"
            rounded="lg"
            hide-details="auto"
          />
        </VCardText>

        <VCardActions class="px-5 pb-5">
          <VSpacer />
          <VBtn
            variant="text"
            rounded="lg"
            @click="itemDialog = false"
          >
            Cancelar
          </VBtn>
          <VBtn
            color="primary"
            variant="flat"
            rounded="lg"
            :loading="savingItem"
            @click="saveItem"
          >
            Guardar
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- Eliminar -->
    <VDialog
      v-model="deleteDialog"
      max-width="400"
    >
      <VCard rounded="lg">
        <VCardTitle class="text-h6">
          Eliminar pago
        </VCardTitle>
        <VCardText class="text-body-2">
          ¿Eliminar “{{ deleteTarget?.description }}”?
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
            :loading="deletingId !== null"
            @click="destroyItem"
          >
            Eliminar
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </div>
</template>

<script>
import { axios } from '@/plugins/axios';

export default {
  name: 'ModuleFixedPayments',

  data() {
    const currentYear = new Date().getFullYear()

    return {
      loading: true,
      error: '',
      salaryYear: currentYear,
      salaryYearOptions: [currentYear - 1, currentYear, currentYear + 1, currentYear + 2],
      settings: { year: currentYear, payday_amount: 0, monthly_salary: 0 },
      groups: { primero: [], segundo: [] },
      totals: { primero: 0, segundo: 0, expenses: 0, remaining: 0 },
      salaryInput: '',
      savingSalary: false,
      itemDialog: false,
      editingItem: null,
      savingItem: false,
      form: {
        description: '',
        amount: '',
        payment_group: 'primero',
        due_label: 'Varía',
      },
      deleteDialog: false,
      deleteTarget: null,
      deletingId: null,
      paymentGroups: [
        {
          key: 'primero',
          totalKey: 'primero',
          title: 'Primer pago',
          headerClass: 'fixed-payments__header--primero',
          chipColor: 'info',
        },
        {
          key: 'segundo',
          totalKey: 'segundo',
          title: 'Segundo pago',
          headerClass: 'fixed-payments__header--segundo',
          chipColor: 'primary',
        },
      ],
      groupOptions: [
        { title: 'Primer pago', value: 'primero' },
        { title: 'Segundo pago', value: 'segundo' },
      ],
      dueLabelOptions: [
        { title: 'Varía', value: 'Varía' },
        { title: '1 cada mes', value: '1 cada mes' },
        { title: '15 cada mes', value: '15 cada mes' },
      ],
    }
  },

  computed: {
    paydayPreview() {
      const n = this.$parseAmount(this.salaryInput)

      if (n === '' || Number.isNaN(n))
        return this.settings.payday_amount || 0

      return n / 2
    },
  },

  watch: {
    salaryYear() {
      this.loadPlan()
    },
  },

  mounted() {
    this.loadPlan()
  },

  methods: {
    loadPlan() {
      this.loading = true
      this.error = ''

      return axios
        .get('/api/fixed-payments', { params: { year: this.salaryYear } })
        .then(response => {
          this.settings = response.data.settings
          this.groups = response.data.groups
          this.totals = response.data.totals
          this.salaryYear = response.data.settings.year
          this.salaryInput = this.$formatAmountValue(this.settings.monthly_salary)
        })
        .catch(() => {
          this.error = 'No se pudo cargar los pagos fijos.'
        })
        .finally(() => {
          this.loading = false
        })
    },

    saveSalary() {
      this.normalizeSalary()

      const amount = this.$parseAmount(this.salaryInput)

      if (amount === '' || Number.isNaN(amount) || this.savingSalary)
        return

      this.savingSalary = true

      axios
        .put('/api/fixed-payments/settings', {
          year: this.salaryYear,
          monthly_salary: amount,
        })
        .then(response => {
          this.settings.year = response.data.year
          this.settings.payday_amount = response.data.payday_amount
          this.settings.monthly_salary = response.data.monthly_salary
          this.salaryInput = this.$formatAmountValue(response.data.monthly_salary)
          this.totals.remaining = response.data.monthly_salary - this.totals.expenses
          this.$toast.success('Salario guardado', { timeout: 2000, closeOnClick: true })
        })
        .catch(() => {
          this.error = 'No se pudo guardar el salario.'
        })
        .finally(() => {
          this.savingSalary = false
        })
    },

    normalizeSalary() {
      const n = this.$parseAmount(this.salaryInput)

      this.salaryInput = n === '' ? '' : this.$formatAmountValue(n)
    },

    openNewItem(group = 'primero') {
      this.editingItem = null
      this.form = {
        description: '',
        amount: '',
        payment_group: group,
        due_label: 'Varía',
      }
      this.itemDialog = true
    },

    openEditItem(item) {
      this.editingItem = item
      this.form = {
        description: item.description,
        amount: this.$formatAmountValue(item.amount),
        payment_group: item.payment_group,
        due_label: item.due_label,
      }
      this.itemDialog = true
    },

    normalizeFormAmount() {
      const n = this.$parseAmount(this.form.amount)

      this.form.amount = n === '' ? '' : this.$formatAmountValue(n)
    },

    saveItem() {
      this.normalizeFormAmount()

      const description = String(this.form.description || '').trim()
      const amount = this.$parseAmount(this.form.amount)

      if (!description || amount === '' || Number.isNaN(amount) || this.savingItem)
        return

      this.savingItem = true

      const payload = {
        description,
        amount,
        payment_group: this.form.payment_group,
        due_label: this.form.due_label,
      }

      const request = this.editingItem
        ? axios.put(`/api/fixed-payments/${this.editingItem.id}`, payload)
        : axios.post('/api/fixed-payments', payload)

      request
        .then(() => {
          this.itemDialog = false
          this.$toast.success('Pago guardado', { timeout: 2000, closeOnClick: true })
          return this.loadPlan()
        })
        .catch(error => {
          const msg = error.response?.data?.message || 'No se pudo guardar el pago.'

          this.error = msg
        })
        .finally(() => {
          this.savingItem = false
        })
    },

    confirmDelete(item) {
      this.deleteTarget = item
      this.deleteDialog = true
    },

    destroyItem() {
      if (!this.deleteTarget?.id || this.deletingId !== null)
        return

      this.deletingId = this.deleteTarget.id

      axios
        .delete(`/api/fixed-payments/${this.deleteTarget.id}`)
        .then(() => {
          this.deleteDialog = false
          this.deleteTarget = null
          this.$toast.success('Pago eliminado', { timeout: 2000, closeOnClick: true })
          return this.loadPlan()
        })
        .catch(() => {
          this.error = 'No se pudo eliminar el pago.'
        })
        .finally(() => {
          this.deletingId = null
        })
    },
  },
}
</script>

<style scoped>
.fixed-payments__salary-row {
  display: flex;
  align-items: center;
  gap: 12px;
}

.fixed-payments__salary-year {
  flex: 0 0 6.75rem;
  width: 6.75rem;
}

.fixed-payments__salary-amount {
  flex: 1 1 auto;
  min-width: 0;
}

.fixed-payments__salary-save {
  flex: 0 0 auto;
  height: 56px !important;
}

@media (max-width: 599px) {
  .fixed-payments__salary-row {
    flex-wrap: wrap;
  }

  .fixed-payments__salary-year {
    flex: 0 0 8rem;
    width: 8rem;
    max-width: 8rem;
  }

  .fixed-payments__salary-amount {
    flex: 1 1 calc(100% - 8rem - 12px);
    min-width: 0;
  }

  .fixed-payments__salary-year :deep(.v-select__selection-text) {
    overflow: visible;
    text-overflow: clip;
  }

  .fixed-payments__salary-save {
    width: 100%;
    height: 44px !important;
  }
}

.fixed-payments__num {
  font-variant-numeric: tabular-nums;
  font-feature-settings: 'tnum';
}

.fixed-payments__amount--positive {
  color: color-mix(in srgb, rgb(var(--v-theme-success)) 78%, rgb(var(--v-theme-on-surface)) 22%);
}

.fixed-payments__amount--negative {
  color: color-mix(in srgb, rgb(var(--v-theme-error)) 78%, rgb(var(--v-theme-on-surface)) 22%);
}

.fixed-payments__remaining-card {
  border-color: rgba(var(--v-border-color), var(--v-border-opacity)) !important;
  background: color-mix(in srgb, rgb(var(--v-theme-primary)) 8%, rgb(var(--v-theme-surface)) 92%);
}

.fixed-payments__header--primero,
.fixed-payments__header--segundo {
  border-inline-start: 4px solid transparent;
  border-bottom: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}

.fixed-payments__header--primero {
  background: color-mix(in srgb, rgb(var(--v-theme-info)) 18%, rgb(var(--v-theme-surface)) 82%);
  border-inline-start-color: rgb(var(--v-theme-info));
  color: color-mix(in srgb, rgb(var(--v-theme-info)) 70%, rgb(var(--v-theme-on-surface)) 30%);
}

.fixed-payments__header--segundo {
  background: color-mix(in srgb, rgb(var(--v-theme-primary)) 18%, rgb(var(--v-theme-surface)) 82%);
  border-inline-start-color: rgb(var(--v-theme-primary));
  color: color-mix(in srgb, rgb(var(--v-theme-primary)) 70%, rgb(var(--v-theme-on-surface)) 30%);
}

.fixed-payments__subtotal {
  background: color-mix(in srgb, rgb(var(--v-theme-on-surface)) 4%, rgb(var(--v-theme-surface)) 96%);
  border-top: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}

.fixed-payments__total-card {
  border-color: rgba(var(--v-border-color), var(--v-border-opacity)) !important;
  background: color-mix(in srgb, rgb(var(--v-theme-primary)) 10%, rgb(var(--v-theme-surface)) 90%);
}

.fixed-payments__total-value {
  color: color-mix(in srgb, rgb(var(--v-theme-primary)) 75%, rgb(var(--v-theme-on-surface)) 25%);
}

.fixed-payments__group-card {
  border-color: rgba(var(--v-border-color), var(--v-border-opacity)) !important;
}

.fixed-payments__group-card :deep(.v-list-item) {
  border-bottom: 1px solid rgba(var(--v-border-color), calc(var(--v-border-opacity) * 0.85));
}

.fixed-payments__group-card :deep(.v-list-item:last-child) {
  border-bottom: none;
}
</style>
