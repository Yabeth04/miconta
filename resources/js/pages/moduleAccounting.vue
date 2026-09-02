<template>
  <!-- Móvil: registrar + filtros + buscador -->
  <template v-if="mdAndDown">
    <AccountingMobileFormSheet
      ref="mobileForm"
      :movement-types="movementTypes"
      :payment-types="paymentTypes"
      :concepts="fixedConcepts"
      @saved="refreshAccounting"
    />

    <VContainer class="accounting-mobile-toolbar pb-0 px-3">
      <div
        v-if="!selectionMode"
        class="accounting-mobile-toolbar__row"
      >
        <VBtn
          color="primary"
          rounded="lg"
          class="accounting-mobile-toolbar__register"
          prepend-icon="ri-add-line"
          @click="openMobileForm"
        >
          Registrar movimiento
        </VBtn>

        <VMenu
          location="bottom end"
          :close-on-content-click="true"
        >
          <template #activator="{ props: menuProps }">
            <VBadge
              :model-value="hasMobileToolsActive"
              color="primary"
              dot
              location="top end"
              offset-x="2"
              offset-y="2"
            >
              <VBtn
                v-bind="menuProps"
                icon
                variant="tonal"
                rounded="lg"
                size="default"
                aria-label="Más opciones"
              >
                <VIcon
                  icon="ri-more-2-fill"
                  size="20"
                />
              </VBtn>
            </VBadge>
          </template>

          <VList
            density="comfortable"
            min-width="220"
          >
            <VListItem
              prepend-icon="ri-search-line"
              :title="mobileSearchOpen ? 'Ocultar búsqueda' : 'Buscar'"
              @click="toggleMobileSearch"
            />
            <VListItem
              prepend-icon="ri-filter-3-line"
              title="Filtros"
              @click="filterSheet = true"
            >
              <template
                v-if="hasSheetFilters"
                #append
              >
                <VIcon
                  icon="ri-checkbox-blank-circle-fill"
                  size="10"
                  color="primary"
                />
              </template>
            </VListItem>
            <VListItem
              prepend-icon="ri-checkbox-multiple-line"
              title="Seleccionar"
              @click="toggleSelectionMode"
            />
            <VDivider class="my-1" />
            <VListItem
              prepend-icon="ri-delete-bin-7-line"
              title="Limpiar movimientos"
              base-color="error"
              :disabled="!totalCount"
              @click="openClearAllDialog"
            />
          </VList>
        </VMenu>
      </div>

      <div
        v-else
        class="accounting-mobile-selection-toolbar"
      >
        <div class="d-flex align-center justify-space-between gap-2 mb-3">
          <div class="min-w-0">
            <span class="text-subtitle-2 font-weight-semibold d-block">
              {{ selectedCount ? `${selectedCount} seleccionado${selectedCount === 1 ? '' : 's'}` : 'Seleccioná movimientos' }}
            </span>
            <span class="text-caption text-medium-emphasis">
              Tocá las tarjetas para marcarlas
            </span>
          </div>
          <VBtn
            variant="text"
            size="small"
            rounded="lg"
            class="flex-shrink-0"
            @click="toggleSelectionMode"
          >
            Listo
          </VBtn>
        </div>
        <div class="accounting-mobile-selection-toolbar__actions">
          <VBtn
            variant="outlined"
            rounded="lg"
            size="small"
            @click="toggleSelectAllVisible"
          >
            {{ allVisibleSelected ? 'Ninguno' : 'Todos' }}
          </VBtn>
          <VBtn
            variant="tonal"
            rounded="lg"
            size="small"
            :disabled="!selectedCount"
            prepend-icon="ri-pencil-line"
            @click="openBulkEdit"
          >
            Editar
          </VBtn>
          <VBtn
            variant="tonal"
            color="error"
            rounded="lg"
            size="small"
            :disabled="!selectedCount"
            prepend-icon="ri-delete-bin-line"
            @click="openBulkDelete"
          >
            Eliminar
          </VBtn>
        </div>
      </div>

      <VExpandTransition>
        <VTextField
          v-if="mobileSearchOpen && !selectionMode"
          v-model="filterQuery"
          class="mt-3"
          type="search"
          label="Buscar concepto o detalle"
          variant="outlined"
          rounded="lg"
          prepend-inner-icon="ri-search-line"
          hide-details="auto"
          clearable
          autofocus
          @update:model-value="onFilterQueryInput"
          @click:clear="onMobileSearchClear"
        />
      </VExpandTransition>
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
      :concepts="fixedConcepts"
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
    :concepts="fixedConcepts"
    @saved="refreshAccounting"
  />

  <AccountingBulkEditDialog
    v-model="bulkEditDialog"
    :ids="selectedIds"
    :count="selectedCount"
    :movement-types="movementTypes"
    :payment-types="paymentTypes"
    :concepts="fixedConcepts"
    @saved="onBulkSaved"
  />

  <AccountingOpeningBalanceDialog
    v-model="openingBalanceDialog"
    :opening-balance="openingBalance"
    @saved="refreshAccounting"
  />

  <VDialog
    v-model="deleteDialog"
    max-width="400"
  >
    <VCard rounded="lg">
      <VCardTitle class="text-h6">
        {{ deleteDialogTitle }}
      </VCardTitle>
      <VCardText class="text-body-2">
        {{ deleteDialogMessage }}
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

  <VDialog
    v-model="clearAllDialog"
    max-width="480"
    persistent
  >
    <VCard rounded="lg">
      <VCardTitle class="text-h6 px-5 pt-5 pb-3">
        Limpiar todos los movimientos
      </VCardTitle>

      <VDivider />

      <VCardText class="pa-5 d-flex flex-column gap-4">
        <VAlert
          type="warning"
          variant="tonal"
          rounded="lg"
        >
          Se eliminarán <strong>{{ totalCount }}</strong> movimiento{{ totalCount === 1 ? '' : 's' }}.
          Esta acción no se puede deshacer. El saldo inicial se mantiene.
        </VAlert>

        <VAlert
          v-if="clearAllError"
          type="error"
          variant="tonal"
          rounded="lg"
        >
          {{ clearAllError }}
        </VAlert>

        <VCheckbox
          v-model="clearAllAcknowledged"
          hide-details
          label="Entiendo que se borrarán todos mis movimientos"
        />

        <VTextField
          v-model="clearAllConfirmation"
          label='Escribí "ELIMINAR" para confirmar'
          placeholder="ELIMINAR"
          variant="outlined"
          rounded="lg"
          hide-details="auto"
          :error-messages="clearAllFieldError('confirmation')"
        />

        <VTextField
          v-model="clearAllPassword"
          label="Contraseña actual"
          placeholder="············"
          :type="clearAllPasswordVisible ? 'text' : 'password'"
          :append-inner-icon="clearAllPasswordVisible ? 'ri-eye-off-line' : 'ri-eye-line'"
          autocomplete="current-password"
          variant="outlined"
          rounded="lg"
          hide-details="auto"
          :error-messages="clearAllFieldError('current_password')"
          @click:append-inner="clearAllPasswordVisible = !clearAllPasswordVisible"
        />
      </VCardText>

      <VCardActions class="px-5 pb-5">
        <VSpacer />
        <VBtn
          variant="text"
          rounded="lg"
          :disabled="clearingAll"
          @click="closeClearAllDialog"
        >
          Cancelar
        </VBtn>
        <VBtn
          color="error"
          variant="flat"
          rounded="lg"
          :loading="clearingAll"
          :disabled="!canConfirmClearAll"
          @click="confirmClearAll"
        >
          Eliminar todo
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>

  <VContainer
    fluid
    class="accounting-module"
    :class="mdAndDown ? 'pa-0 mt-4' : ''"
  >
    <div
      v-if="mdAndUp"
      class="d-flex flex-wrap align-center justify-space-between gap-3 mb-4"
    >
      <div>
        <h1 class="text-h4 font-weight-medium mb-1">
          Contabilidad
        </h1>
        <p class="text-body-2 text-medium-emphasis mb-0">
          Registrá y consultá tus movimientos
        </p>
      </div>
      <div class="d-flex flex-wrap gap-2">
        <VBtn
          variant="tonal"
          rounded="lg"
          prepend-icon="ri-price-tag-3-line"
          to="/contabilidad/conceptos"
        >
          Conceptos
        </VBtn>
        <VBtn
          variant="outlined"
          color="error"
          rounded="lg"
          prepend-icon="ri-delete-bin-7-line"
          :disabled="!totalCount"
          @click="openClearAllDialog"
        >
          Limpiar movimientos
        </VBtn>
      </div>
    </div>

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
          <AccountingConceptCombobox
            v-model="concept"
            :concepts="fixedConcepts"
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
        <div class="px-4 pb-3">
          <VTextField
            v-model="detail"
            type="text"
            label="Detalle"
            placeholder="Opcional, ej. 10 litros"
            variant="outlined"
            rounded="lg"
            hide-details="auto"
          />
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
            v-model="filterQuery"
            class="flex-grow-1"
            type="search"
            label="Buscar concepto o detalle"
            variant="outlined"
            rounded="lg"
            density="compact"
            prepend-inner-icon="ri-search-line"
            hide-details="auto"
            clearable
            @update:model-value="onFilterQueryInput"
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
          <VBtn
            :variant="selectionMode ? 'flat' : 'tonal'"
            :color="selectionMode ? 'primary' : 'default'"
            rounded="lg"
            class="px-3 flex-shrink-0"
            aria-label="Seleccionar movimientos"
            @click="toggleSelectionMode"
          >
            <VIcon
              icon="ri-checkbox-multiple-line"
              size="18"
            />
          </VBtn>
        </div>

        <VExpandTransition>
          <div
            v-if="selectionMode"
            class="accounting-selection-bar d-flex flex-wrap align-center gap-2 mt-3"
          >
            <span
              v-if="selectedCount"
              class="text-body-2 font-weight-medium"
            >
              {{ selectedCount }} seleccionado{{ selectedCount === 1 ? '' : 's' }}
            </span>
            <span
              v-else
              class="text-body-2 text-medium-emphasis"
            >
              Seleccioná movimientos de la lista
            </span>
            <VSpacer />
            <VBtn
              v-if="selectedCount"
              variant="tonal"
              rounded="lg"
              size="small"
              prepend-icon="ri-pencil-line"
              @click="openBulkEdit"
            >
              Editar
            </VBtn>
            <VBtn
              v-if="selectedCount"
              variant="tonal"
              color="error"
              rounded="lg"
              size="small"
              prepend-icon="ri-delete-bin-line"
              @click="openBulkDelete"
            >
              Eliminar
            </VBtn>
            <VBtn
              v-if="selectedCount"
              variant="text"
              rounded="lg"
              size="small"
              @click="clearSelection"
            >
              Limpiar
            </VBtn>
            <VBtn
              variant="text"
              rounded="lg"
              size="small"
              @click="toggleSelectionMode"
            >
              Cancelar
            </VBtn>
          </div>
        </VExpandTransition>

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
              v-if="selectionMode"
              class="accounting-table__th accounting-table__th--select"
            >
              <VCheckbox
                :model-value="allVisibleSelected"
                :indeterminate="someVisibleSelected && !allVisibleSelected"
                hide-details
                density="compact"
                @update:model-value="toggleSelectAllVisible"
              />
            </th>
            <th
              class="accounting-table__th text-start accounting-table__th--date"
            >
              Fecha
            </th>
            <th class="accounting-table__th text-start accounting-table__th--concept">
              Concepto
            </th>
            <th class="accounting-table__th text-start accounting-table__th--detail">
              Detalle
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
            :class="{
              'accounting-table__row--selected': isSelected(item.id),
              'accounting-table__row--selectable': selectionMode,
            }"
            @click="onRowClick(item)"
          >
            <td
              v-if="selectionMode"
              class="accounting-table__select"
              @click.stop="toggleSelectItem(item.id)"
            >
              <VCheckbox
                :model-value="isSelected(item.id)"
                hide-details
                density="compact"
                readonly
              />
            </td>
            <td class="text-body-2 text-medium-emphasis">
              {{ item.date }}
            </td>
            <td class="text-body-2 accounting-table__concept">
              <div class="d-flex align-center gap-1 min-w-0">
                <span
                  v-if="item.accounting_concept_id"
                  class="d-inline-flex flex-shrink-0"
                  title="Concepto de la lista"
                >
                  <VIcon
                    icon="ri-price-tag-3-fill"
                    size="16"
                    class="text-primary"
                  />
                </span>
                <span
                  class="text-truncate"
                  :title="item.concept || undefined"
                >{{ item.concept || '—' }}</span>
              </div>
            </td>
            <td
              class="text-body-2 text-medium-emphasis accounting-table__detail"
              :title="item.detail || undefined"
            >
              {{ item.detail || '—' }}
            </td>
            <td
              class="text-end accounting-table__num accounting-table__amount"
              :class="{ 'accounting-amount--debe': item.movement_type === 'debe' }"
            >
              {{ item.movement_type === 'debe' ? $formatAmount(item.amount) : '—' }}
            </td>
            <td
              class="text-end accounting-table__num accounting-table__amount"
              :class="{ 'accounting-amount--haber': item.movement_type === 'haber' }"
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
                v-if="!selectionMode"
                @edit="openEdit(item)"
                @delete="openDelete(item)"
              />
            </td>
          </tr>

          <tr v-if="!accounting.length && !hasMore && !loading">
            <td
              :colspan="selectionMode ? 8 : 7"
              class="text-body-2 text-medium-emphasis text-center py-8"
            >
              {{ emptyListMessage }}
            </td>
          </tr>

          <!-- Carga más movimientos -->
          <tr>
            <td :colspan="selectionMode ? 8 : 7">
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
        :class="{ 'accounting-mobile-list--selection': selectionMode }"
        @load="showAccounting"
      >
        <div class="pa-3">
          <p
            v-if="!accounting.length"
            class="text-body-2 text-medium-emphasis text-center py-8"
          >
            {{ emptyListMessage }}
          </p>
          <template v-else>
            <VCard
              v-for="item in accounting"
              :key="item.id"
              variant="outlined"
              rounded="lg"
              class="accounting-mobile-card mb-3"
              :class="{
                'accounting-mobile-card--selected': isSelected(item.id),
                'accounting-mobile-card--selectable': selectionMode,
              }"
              @click="onRowClick(item)"
            >
              <VCardText class="pa-4 accounting-mobile-card__body">
                <div class="d-flex align-start gap-3">
                  <div
                    v-if="selectionMode"
                    class="accounting-mobile-card__checkbox flex-shrink-0"
                  >
                    <VCheckbox
                      :model-value="isSelected(item.id)"
                      hide-details
                      density="comfortable"
                      readonly
                    />
                  </div>

                  <div class="flex-grow-1 min-w-0">
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
                      v-if="!selectionMode"
                      @edit="openEdit(item)"
                      @delete="openDelete(item)"
                    />
                  </div>
                </div>
                <div class="text-body-2 mb-3">
                  <div class="d-flex align-center gap-1">
                    <span
                      v-if="item.accounting_concept_id"
                      class="d-inline-flex flex-shrink-0"
                      title="Concepto de la lista"
                    >
                      <VIcon
                        icon="ri-price-tag-3-fill"
                        size="16"
                        class="text-primary"
                      />
                    </span>
                    <span>{{ item.concept || '—' }}</span>
                  </div>
                  <div
                    v-if="item.detail"
                    class="text-caption text-medium-emphasis text-truncate"
                    :title="item.detail"
                  >
                    {{ item.detail }}
                  </div>
                </div>
                <div class="d-flex justify-space-between gap-4 text-body-2">
                  <div>
                    <span class="text-medium-emphasis text-caption d-block mb-1">Debe</span>
                    <span
                      class="accounting-table__num font-weight-medium"
                      :class="{ 'accounting-amount--debe': item.movement_type === 'debe' }"
                    >
                      {{ item.movement_type === 'debe' ? $formatAmount(item.amount) : '—' }}
                    </span>
                  </div>
                  <div class="text-end">
                    <span class="text-medium-emphasis text-caption d-block mb-1">Haber</span>
                    <span
                      class="accounting-table__num font-weight-medium"
                      :class="{ 'accounting-amount--haber': item.movement_type === 'haber' }"
                    >
                      {{ item.movement_type === 'haber' ? $formatAmount(item.amount) : '—' }}
                    </span>
                  </div>
                </div>
                  </div>
                </div>
              </VCardText>
            </VCard>
          </template>
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
              class="accounting-totals__desktop-value accounting-table__num text-body-1 font-weight-semibold accounting-amount--debe"
            >
              {{ $formatAmount(totalDebe) }}
            </span>
          </div>
          <div class="accounting-totals__desktop-metric text-end">
            <span class="accounting-totals__col-label d-block accounting-totals__col-label--tight">
              Haber / Ingreso
            </span>
            <span
              class="accounting-totals__desktop-value accounting-table__num text-body-1 font-weight-semibold accounting-amount--haber"
            >
              {{ $formatAmount(totalHaber) }}
            </span>
          </div>
          <div class="accounting-totals__desktop-metric accounting-totals__desktop-metric--balance text-end">
            <div class="d-flex align-center justify-end gap-1 mb-1">
              <span class="accounting-totals__col-label accounting-totals__col-label--tight mb-0">
                Monto en cuenta
              </span>
              <VBtn
                icon
                variant="text"
                size="x-small"
                aria-label="Editar saldo inicial"
                title="Editar saldo inicial"
                @click="openingBalanceDialog = true"
              >
                <VIcon
                  icon="ri-settings-3-line"
                  size="14"
                />
              </VBtn>
            </div>
            <span class="accounting-totals__desktop-value accounting-table__num text-body-1 font-weight-semibold text-primary">
              {{ $formatAmount(accountBalance) }}
            </span>
          </div>
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
        <div class="d-flex justify-space-between align-center gap-4 mb-4">
          <div>
            <span class="text-medium-emphasis text-caption d-block mb-1">Debe / Gasto</span>
            <span
              class="accounting-table__num text-body-2 font-weight-medium accounting-amount--debe"
            >
              {{ $formatAmount(totalDebe) }}
            </span>
          </div>
          <div class="text-end">
            <span class="text-medium-emphasis text-caption d-block mb-1">Haber / Ingreso</span>
            <span
              class="accounting-table__num text-body-2 font-weight-medium accounting-amount--haber"
            >
              {{ $formatAmount(totalHaber) }}
            </span>
          </div>
        </div>
        <div class="accounting-totals__mobile-balance d-flex justify-space-between align-center">
          <div>
            <span class="text-medium-emphasis text-caption d-block mb-1">Monto en cuenta</span>
            <span class="accounting-table__num text-body-1 font-weight-semibold text-primary">
              {{ $formatAmount(accountBalance) }}
            </span>
          </div>
          <VBtn
            icon
            variant="text"
            aria-label="Editar saldo inicial"
            @click="openingBalanceDialog = true"
          >
            <VIcon icon="ri-settings-3-line" />
          </VBtn>
        </div>
      </div>
    </VCard>
  </VContainer>

</template>

<script>
import submittedVuelidateForm from '@/mixins/submittedVuelidateForm'
import { axios } from '@/plugins/axios'
import AccountingBulkEditDialog from '@/views/pages/accounting/AccountingBulkEditDialog.vue'
import AccountingConceptCombobox from '@/views/pages/accounting/AccountingConceptCombobox.vue'
import AccountingEditDialog from '@/views/pages/accounting/AccountingEditDialog.vue'
import AccountingMobileEditSheet from '@/views/pages/accounting/AccountingMobileEditSheet.vue'
import AccountingMobileFiltersSheet from '@/views/pages/accounting/AccountingMobileFiltersSheet.vue'
import AccountingMobileFormSheet from '@/views/pages/accounting/AccountingMobileFormSheet.vue'
import AccountingMovementMenu from '@/views/pages/accounting/AccountingMovementMenu.vue'
import AccountingOpeningBalanceDialog from '@/views/pages/accounting/AccountingOpeningBalanceDialog.vue'
import { parseAmount } from '@core/utils/formatters'
import { useVuelidate } from '@vuelidate/core'
import { helpers, required } from '@vuelidate/validators'
import { useDisplay } from 'vuetify'

export default {
  name: 'ModuleAccounting',
  components: {
    AccountingBulkEditDialog,
    AccountingConceptCombobox,
    AccountingEditDialog,
    AccountingMobileEditSheet,
    AccountingMobileFiltersSheet,
    AccountingMobileFormSheet,
    AccountingMovementMenu,
    AccountingOpeningBalanceDialog,
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
      concept: '',
      detail: '',
      fixedConcepts: [],
      accounting: [],
      page: 1,
      hasMore: true,
      loading: false,
      saving: false,
      scrollKey: 0,
      totalDebe: 0,
      totalHaber: 0,
      totalCount: 0,
      openingBalance: 0,
      accountBalance: 0,
      openingBalanceDialog: false,
      filterDateRange: null,
      filterMovementTypes: [],
      filterPaymentTypes: [],
      filterQuery: '',
      filterQueryTimer: null,
      skipFilterRefresh: false,
      filterSheet: false,
      filtersExpanded: false,
      editDialog: false,
      editMovement: null,
      deleteDialog: false,
      deleteTarget: null,
      deleteMode: 'single',
      deleting: false,
      selectionMode: false,
      selectedIds: [],
      bulkEditDialog: false,
      mobileSearchOpen: false,
      clearAllDialog: false,
      clearAllAcknowledged: false,
      clearAllConfirmation: '',
      clearAllPassword: '',
      clearAllPasswordVisible: false,
      clearingAll: false,
      clearAllError: '',
      clearAllFieldErrors: {},
    }
  },
  computed: {
    hasMobileToolsActive() {
      return this.hasSheetFilters
        || this.mobileSearchOpen
        || Boolean(String(this.filterQuery).trim())
    },
    selectedCount() {
      return this.selectedIds.length
    },
    allVisibleSelected() {
      return this.accounting.length > 0
        && this.accounting.every(item => this.isSelected(item.id))
    },
    someVisibleSelected() {
      return this.accounting.some(item => this.isSelected(item.id))
    },
    deleteDialogTitle() {
      return this.deleteMode === 'bulk' ? 'Eliminar movimientos' : 'Eliminar movimiento'
    },
    deleteDialogMessage() {
      if (this.deleteMode === 'bulk') {
        return `¿Eliminar ${this.selectedCount} movimiento${this.selectedCount === 1 ? '' : 's'}? No se puede deshacer.`
      }

      return '¿Eliminar este movimiento? No se puede deshacer.'
    },
    canConfirmClearAll() {
      return this.clearAllAcknowledged
        && this.clearAllConfirmation.trim().toUpperCase() === 'ELIMINAR'
        && Boolean(this.clearAllPassword)
        && this.totalCount > 0
        && !this.clearingAll
    },
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
        || String(this.filterQuery).trim(),
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
    this.loadFixedConcepts()
    window.addEventListener('accounting:imported', this.refreshAccounting)
  },
  activated() {
    this.loadFixedConcepts()
  },
  beforeUnmount() {
    clearTimeout(this.filterQueryTimer)
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
    loadFixedConcepts() {
      axios
        .get('/api/accounting/concepts')
        .then(response => {
          this.fixedConcepts = response.data.data || []
        })
        .catch(() => {
          this.fixedConcepts = []
        })
    },
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
          concept: this.concept,
          detail: this.detail,
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
      this.clearSelection()
      this.selectionMode = false
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
          this.openingBalance = response.data.totals.opening_balance ?? 0
          this.accountBalance = response.data.totals.account_balance ?? 0
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
      this.concept = ''
      this.detail = ''
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

      const q = String(this.filterQuery).trim()
      if (q)
        params.q = q

      return params
    },
    onFilterQueryInput() {
      clearTimeout(this.filterQueryTimer)
      this.filterQueryTimer = setTimeout(() => {
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
      clearTimeout(this.filterQueryTimer)
      this.skipFilterRefresh = true
      this.filterDateRange = null
      this.filterMovementTypes = []
      this.filterPaymentTypes = []
      this.filterQuery = ''
      this.skipFilterRefresh = false
      this.refreshAccounting()
    },
    onRowClick(item) {
      if (!this.selectionMode || item?.id == null)
        return

      this.toggleSelectItem(item.id)
    },
    openEdit(item) {
      if (this.selectionMode)
        return

      if (this.mdAndUp) {
        this.editMovement = item
        this.editDialog = true
      } else {
        this.$refs.mobileEdit?.open(item)
      }
    },
    openDelete(item) {
      this.deleteMode = 'single'
      this.deleteTarget = item
      this.deleteDialog = true
    },
    openBulkDelete() {
      if (!this.selectedIds.length)
        return

      this.deleteMode = 'bulk'
      this.deleteDialog = true
    },
    openClearAllDialog() {
      if (!this.totalCount)
        return

      this.clearAllAcknowledged = false
      this.clearAllConfirmation = ''
      this.clearAllPassword = ''
      this.clearAllPasswordVisible = false
      this.clearAllError = ''
      this.clearAllFieldErrors = {}
      this.clearAllDialog = true
    },
    closeClearAllDialog() {
      if (this.clearingAll)
        return

      this.clearAllDialog = false
    },
    clearAllFieldError(field) {
      return this.clearAllFieldErrors[field]?.[0] || null
    },
    async confirmClearAll() {
      if (!this.canConfirmClearAll || this.clearingAll)
        return

      this.clearingAll = true
      this.clearAllError = ''
      this.clearAllFieldErrors = {}

      try {
        const response = await axios.post('/api/accounting/destroy-all', {
          current_password: this.clearAllPassword,
          confirmation: this.clearAllConfirmation.trim().toUpperCase(),
        })

        this.clearAllDialog = false
        this.selectionMode = false
        this.clearSelection()
        this.refreshAccounting()
        this.$toast.success(
          `Eliminados ${response.data.deleted} movimiento${response.data.deleted === 1 ? '' : 's'}`,
          { timeout: 2500, closeOnClick: true },
        )
      } catch (error) {
        this.clearAllError = error.response?.data?.message || 'No se pudieron eliminar los movimientos.'
        this.clearAllFieldErrors = error.response?.data?.errors || {}
      } finally {
        this.clearingAll = false
      }
    },
    openBulkEdit() {
      if (!this.selectedIds.length)
        return

      this.bulkEditDialog = true
    },
    toggleSelectionMode() {
      this.selectionMode = !this.selectionMode

      if (!this.selectionMode) {
        this.clearSelection()
      } else {
        this.mobileSearchOpen = false
      }
    },
    toggleMobileSearch() {
      this.mobileSearchOpen = !this.mobileSearchOpen
    },
    onMobileSearchClear() {
      clearTimeout(this.filterQueryTimer)
      this.filterQuery = ''
      this.refreshAccounting()
    },
    clearSelection() {
      this.selectedIds = []
    },
    normalizeId(id) {
      return Number(id)
    },
    isSelected(id) {
      const normalizedId = this.normalizeId(id)

      return this.selectedIds.some(itemId => this.normalizeId(itemId) === normalizedId)
    },
    setItemSelected(id, selected) {
      const normalizedId = this.normalizeId(id)

      if (selected) {
        if (!this.isSelected(normalizedId))
          this.selectedIds = [...this.selectedIds, normalizedId]
      } else {
        this.selectedIds = this.selectedIds.filter(itemId => this.normalizeId(itemId) !== normalizedId)
      }
    },
    toggleSelectItem(id) {
      this.setItemSelected(id, !this.isSelected(id))
    },
    toggleSelectAllVisible(selected) {
      const shouldSelect = typeof selected === 'boolean' ? selected : !this.allVisibleSelected

      if (!shouldSelect) {
        const visibleIds = new Set(this.accounting.map(item => this.normalizeId(item.id)))

        this.selectedIds = this.selectedIds.filter(id => !visibleIds.has(this.normalizeId(id)))
      } else {
        const ids = new Set(this.selectedIds)

        this.accounting.forEach(item => {
          if (item?.id != null)
            ids.add(this.normalizeId(item.id))
        })
        this.selectedIds = [...ids]
      }
    },
    onBulkSaved() {
      this.selectionMode = false
      this.clearSelection()
      this.refreshAccounting()
    },
    async confirmDelete() {
      if (this.deleting)
        return

      if (this.deleteMode === 'single' && !this.deleteTarget?.id)
        return

      if (this.deleteMode === 'bulk' && !this.selectedIds.length)
        return

      this.deleting = true

      try {
        if (this.deleteMode === 'bulk') {
          const response = await axios.post('/api/accounting/bulk-destroy', {
            ids: this.selectedIds,
          })

          this.deleteDialog = false
          this.selectionMode = false
          this.clearSelection()
          this.refreshAccounting()
          this.$toast.success(
            `Eliminados ${response.data.deleted} movimiento${response.data.deleted === 1 ? '' : 's'}`,
            { timeout: 2000, closeOnClick: true },
          )
        } else {
          await axios.delete(`/api/accounting/${this.deleteTarget.id}`)
          this.deleteDialog = false
          this.deleteTarget = null
          this.editMovement = null
          this.refreshAccounting()
          this.$toast.success('Movimiento eliminado', { timeout: 2000, closeOnClick: true })
        }
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
.accounting-module {
  width: 100%;
  max-width: 100%;
}

.accounting-form-card,
.accounting-table-card {
  border-color: rgba(var(--v-theme-on-surface), 0.08);
}

.accounting-form-grid {
  display: grid;
  grid-template-columns: minmax(140px, 1fr) minmax(200px, 1.6fr) minmax(120px, 1fr) minmax(120px, 1fr) minmax(160px, 1.1fr);
  gap: 0.75rem;
  align-items: start;
}

.accounting-table {
  width: 100%;
  table-layout: fixed;
}

.accounting-table__th--concept,
.accounting-table__concept {
  width: 22%;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.accounting-table__th--detail,
.accounting-table__detail {
  width: 28%;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.accounting-filters-grid {
  display: grid;
  grid-template-columns: minmax(180px, 1.4fr) minmax(140px, 1fr) minmax(140px, 1fr);
  gap: 0.75rem;
  align-items: start;
}

/* Crece con el contenido; solo hace scroll al llegar al tope (evita el hueco vacío) */
.accounting-table :deep(.v-table__wrapper) {
  max-height: min(410px, 55vh);
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

.accounting-table__th--date {
  width: 9%;
  white-space: nowrap;
}

.accounting-table__th--narrow {
  width: 12%;
  white-space: nowrap;
}

.accounting-table__th--select,
.accounting-table__select {
  width: 3rem;
  min-width: 3rem;
  padding-inline: 0.75rem !important;
}

.accounting-table__row--selectable {
  cursor: pointer;
  user-select: none;
}

.accounting-table__row--selectable:hover {
  background: rgba(var(--v-theme-on-surface), 0.06) !important;
}

.accounting-table__select :deep(.v-selection-control) {
  pointer-events: none;
}

.accounting-table__row--selected {
  background: rgba(var(--v-theme-primary), 0.06) !important;
}

.accounting-selection-bar {
  padding: 0.625rem 0.75rem;
  border-radius: 0.5rem;
  background: rgba(var(--v-theme-primary), 0.08);
  border: thin solid rgba(var(--v-theme-primary), 0.16);
}

.accounting-mobile-card--selected {
  border-color: rgba(var(--v-theme-primary), 0.55) !important;
  background: rgba(var(--v-theme-primary), 0.08);
  box-shadow: inset 0 0 0 1px rgba(var(--v-theme-primary), 0.12);
}

.accounting-mobile-list--selection {
  padding-bottom: 1rem;
}

.accounting-mobile-toolbar__row {
  display: flex;
  align-items: stretch;
  gap: 0.5rem;
}

.accounting-mobile-toolbar__register {
  flex: 1 1 auto;
  min-width: 0;
}

.accounting-mobile-selection-toolbar {
  padding: 0.875rem 1rem;
  border-radius: 0.875rem;
  background: rgba(var(--v-theme-primary), 0.08);
  border: thin solid rgba(var(--v-theme-primary), 0.18);
}

.accounting-mobile-selection-toolbar__actions {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 0.5rem;
}

.accounting-mobile-selection-toolbar__actions :deep(.v-btn) {
  width: 100%;
  min-width: 0;
  padding-inline: 0.5rem;
}

.accounting-mobile-card__checkbox {
  margin-block-start: -0.25rem;
  margin-inline-start: -0.5rem;
}

.accounting-mobile-card__checkbox :deep(.v-selection-control) {
  pointer-events: none;
}

.accounting-mobile-card__body {
  width: 100%;
}

.accounting-mobile-card {
  cursor: default;
}

.accounting-mobile-list--selection .accounting-mobile-card {
  cursor: pointer;
}

.accounting-mobile-card--selectable {
  user-select: none;
  -webkit-tap-highlight-color: transparent;
}

.accounting-mobile-card--selectable:active {
  transform: scale(0.995);
}

.accounting-mobile-card :deep(.v-selection-control) {
  pointer-events: none;
}

/* Montos compactos y juntos a la derecha */
.accounting-table__th--amount,
.accounting-table__amount {
  width: 11%;
  white-space: nowrap;
  font-size: 14px;
  padding-inline: 0.75rem !important;
}

.accounting-table__th--amount + .accounting-table__th--amount,
.accounting-table__amount + .accounting-table__amount {
  padding-inline-start: 0.5rem !important;
}

.accounting-amount--debe {
  color: color-mix(in srgb, rgb(var(--v-theme-error)) 86%, rgb(var(--v-theme-on-surface)) 14%);
}

.accounting-amount--haber {
  color: color-mix(in srgb, rgb(var(--v-theme-success)) 86%, rgb(var(--v-theme-on-surface)) 14%);
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

.accounting-totals__desktop-metric--balance {
  background: rgba(var(--v-theme-primary), 0.06);
  border-color: rgba(var(--v-theme-primary), 0.18);
}

.accounting-totals__mobile-balance {
  padding: 0.75rem;
  border-radius: 8px;
  background: rgba(var(--v-theme-primary), 0.06);
  border: thin solid rgba(var(--v-theme-primary), 0.18);
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
