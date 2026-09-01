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
            <p class="text-caption text-medium-emphasis mb-2">
              Salario del mes
            </p>
            <div class="d-flex flex-wrap align-end gap-2">
              <VTextField
                v-currency-live
                v-model="salaryInput"
                type="text"
                inputmode="decimal"
                autocomplete="off"
                label="Monto"
                variant="outlined"
                rounded="lg"
                hide-details="auto"
                class="flex-grow-1"
                :disabled="savingSalary"
                @blur="onSalaryBlur"
                @keydown.enter.prevent="onSalaryBlur"
              />
              <VBtn
                variant="tonal"
                rounded="lg"
                :loading="savingSalary"
                @click="saveSalary"
              >
                Guardar
              </VBtn>
            </div>
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
          :color="totals.remaining >= 0 ? 'success' : 'error'"
          variant="tonal"
          class="h-100"
        >
          <VCardText class="d-flex flex-column justify-center h-100">
            <p class="text-caption mb-1">
              Quedan libres al mes
            </p>
            <p class="text-h4 font-weight-semibold mb-1 fixed-payments__num">
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
              <template #prepend>
                <VChip
                  size="x-small"
                  variant="tonal"
                  label
                  class="me-2"
                >
                  {{ item.due_label }}
                </VChip>
              </template>

              <VListItemTitle class="font-weight-medium">
                {{ item.description }}
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
      variant="tonal"
      color="secondary"
      class="mt-4"
    >
      <VCardText class="d-flex flex-wrap justify-space-between align-center gap-3 py-4">
        <span class="text-body-2 font-weight-medium">
          Total gastos fijos
        </span>
        <span class="text-h6 font-weight-semibold fixed-payments__num">
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
    return {
      loading: true,
      error: '',
      settings: { monthly_salary: 0 },
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
        },
        {
          key: 'segundo',
          totalKey: 'segundo',
          title: 'Segundo pago',
          headerClass: 'fixed-payments__header--segundo',
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

  mounted() {
    this.loadPlan()
  },

  methods: {
    loadPlan() {
      this.loading = true
      this.error = ''

      return axios
        .get('/api/fixed-payments')
        .then(response => {
          this.settings = response.data.settings
          this.groups = response.data.groups
          this.totals = response.data.totals
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
      const amount = this.$parseAmount(this.salaryInput)

      if (amount === '' || Number.isNaN(amount) || this.savingSalary)
        return

      this.savingSalary = true

      axios
        .put('/api/fixed-payments/settings', { monthly_salary: amount })
        .then(response => {
          this.settings.monthly_salary = response.data.monthly_salary
          this.salaryInput = this.$formatAmountValue(response.data.monthly_salary)
          this.totals.remaining = this.settings.monthly_salary - this.totals.expenses
          this.$toast.success('Salario guardado', { timeout: 2000, closeOnClick: true })
        })
        .catch(() => {
          this.error = 'No se pudo guardar el salario.'
        })
        .finally(() => {
          this.savingSalary = false
        })
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

    onSalaryBlur() {
      this.normalizeSalary()
      this.saveSalary()
    },

    normalizeSalary() {
      const n = this.$parseAmount(this.salaryInput)

      this.salaryInput = n === '' ? '' : this.$formatAmountValue(n)
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
.fixed-payments__num {
  font-variant-numeric: tabular-nums;
  font-feature-settings: 'tnum';
}

.fixed-payments__header--primero {
  background: rgba(var(--v-theme-info), 0.12);
}

.fixed-payments__header--segundo {
  background: rgba(var(--v-theme-primary), 0.1);
}

.fixed-payments__subtotal {
  background: rgba(var(--v-theme-on-surface), 0.03);
}

.fixed-payments__group-card :deep(.v-list-item) {
  border-bottom: 1px solid rgba(var(--v-theme-on-surface), 0.06);
}

.fixed-payments__group-card :deep(.v-list-item:last-child) {
  border-bottom: none;
}
</style>
