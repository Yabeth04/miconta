<template>
  <div>
    <div class="d-flex flex-wrap align-center justify-space-between gap-3 mb-4">
      <div>
        <h1 class="text-h4 font-weight-medium mb-1">
          Proyección
        </h1>
        <p class="text-body-2 text-medium-emphasis mb-0">
          {{ projectionMode === 'real'
            ? 'Flujo real desde hoy: solo quincenas que faltan, pagos fijos y meses sin U'
            : 'Escenario fijo: lo que te queda al mes y meses sin pago de universidad' }}
        </p>
      </div>
    </div>

    <VAlert
      v-if="error || isRangeInvalid"
      type="error"
      variant="tonal"
      rounded="lg"
      class="mb-4"
    >
      {{ isRangeInvalid ? 'El periodo inicial no puede ser posterior al final.' : error }}
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
              v-model="projectionMode"
              :items="projectionModeOptions"
              label="Modo"
              variant="outlined"
              rounded="lg"
              hide-details
            />
          </VCol>
          <VCol
            v-if="projectionMode === 'real'"
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
            v-if="projectionMode === 'real' && rangeMode === 'year'"
            cols="12"
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
            v-if="projectionMode === 'fixed'"
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
        </VRow>

        <VRow
          v-if="projectionMode === 'real' && rangeMode === 'custom'"
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
                  :items="yearOptions"
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
                  :items="monthOptions"
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
                  :items="yearOptions"
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
                  :items="monthOptions"
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
          v-if="projectionMode === 'real' && !isRangeInvalid"
          class="text-caption text-medium-emphasis mb-0 mt-3"
        >
          Rango proyectado: {{ periodLabel }}
        </p>

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
                  {{ $formatAmount($parseAmount(monthlySalaryInput) || sources.monthly_salary || 0) }}
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
                v-currency-live
                v-model="monthlySalaryInput"
                class="monto-with-action"
                type="text"
                inputmode="decimal"
                autocomplete="off"
                label="Salario al mes"
                variant="outlined"
                rounded="lg"
                hide-details="auto"
                :hint="salaryHint"
                persistent-hint
              >
                <template #append-inner>
                  <VBtn
                    color="primary"
                    variant="flat"
                    class="monto-with-action__btn rounded-s-0 rounded-e-lg"
                    :disabled="loading || saving"
                    aria-label="Usar salario registrado"
                    title="Usar salario registrado"
                    type="button"
                    tabindex="-1"
                    @click="useRegisteredSalary"
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
            v-currency-live
            v-model="monthlySalaryInput"
            class="monto-with-action mb-4"
            type="text"
            inputmode="decimal"
            autocomplete="off"
            label="Salario al mes"
            variant="outlined"
            rounded="lg"
            hide-details="auto"
            :hint="salaryHint"
            persistent-hint
          >
            <template #append-inner>
              <VBtn
                color="primary"
                variant="flat"
                class="monto-with-action__btn rounded-s-0 rounded-e-lg"
                :disabled="loading || saving"
                aria-label="Usar salario registrado"
                title="Usar salario registrado"
                type="button"
                tabindex="-1"
                @click="useRegisteredSalary"
              >
                <VIcon
                  icon="ri-calendar-check-line"
                  size="22"
                />
              </VBtn>
            </template>
          </VTextField>

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
            :disabled="isRangeInvalid"
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
              <p class="text-caption text-medium-emphasis mb-1 d-inline-flex align-center gap-1">
                {{ card.title }}
                <VTooltip
                  v-if="card.tooltip"
                  location="top"
                  max-width="280"
                >
                  <template #activator="{ props: tipProps }">
                    <VIcon
                      v-bind="tipProps"
                      icon="ri-information-line"
                      size="14"
                      class="text-medium-emphasis"
                    />
                  </template>
                  <span>{{ card.tooltip }}</span>
                </VTooltip>
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
            <VChip
              v-if="projectionMode === 'real' && row.partial"
              size="small"
              rounded="lg"
              color="warning"
              variant="tonal"
            >
              Parcial
            </VChip>
          </div>

          <div class="projection-month-card__grid">
            <template v-if="projectionMode === 'real'">
              <div class="projection-month-card__cell">
                <span class="text-caption text-medium-emphasis d-inline-flex align-center gap-1">
                  Salario in
                  <VTooltip
                    location="top"
                    max-width="320"
                  >
                    <template #activator="{ props: tipProps }">
                      <VIcon
                        v-bind="tipProps"
                        icon="ri-information-line"
                        size="14"
                        class="text-medium-emphasis"
                      />
                    </template>
                    <span>{{ salaryInTooltip(row) }}</span>
                  </VTooltip>
                </span>
                <span class="text-body-2 projection__num">
                  {{ $formatAmount(row.salary_in) }}
                </span>
              </div>
              <div class="projection-month-card__cell">
                <span class="text-caption text-medium-emphasis d-inline-flex align-center gap-1">
                  Gastos out
                  <VTooltip
                    location="top"
                    max-width="280"
                  >
                    <template #activator="{ props: tipProps }">
                      <VIcon
                        v-bind="tipProps"
                        icon="ri-information-line"
                        size="14"
                        class="text-medium-emphasis"
                      />
                    </template>
                    <span>{{ expensesOutTooltip(row) }}</span>
                  </VTooltip>
                </span>
                <span class="text-body-2 projection__num">
                  {{ $formatAmount(row.expenses_out) }}
                </span>
              </div>
            </template>
            <template v-else>
              <div class="projection-month-card__cell">
                <span class="text-caption text-medium-emphasis d-inline-flex align-center gap-1">
                  Queda
                  <VTooltip
                    location="top"
                    max-width="280"
                  >
                    <template #activator="{ props: tipProps }">
                      <VIcon
                        v-bind="tipProps"
                        icon="ri-information-line"
                        size="14"
                        class="text-medium-emphasis"
                      />
                    </template>
                    <span>{{ columnTooltips.remaining }}</span>
                  </VTooltip>
                </span>
                <span class="text-body-2 projection__num">
                  {{ $formatAmount(row.monthly_remaining) }}
                </span>
              </div>
              <div class="projection-month-card__cell">
                <span class="text-caption text-medium-emphasis d-inline-flex align-center gap-1">
                  Libre U
                  <VTooltip
                    location="top"
                    max-width="280"
                  >
                    <template #activator="{ props: tipProps }">
                      <VIcon
                        v-bind="tipProps"
                        icon="ri-information-line"
                        size="14"
                        class="text-medium-emphasis"
                      />
                    </template>
                    <span>{{ columnTooltips.universityFreed }}</span>
                  </VTooltip>
                </span>
                <span
                  class="text-body-2 projection__num"
                  :class="{ 'projection__freed': row.university_freed > 0 }"
                >
                  {{ $formatAmount(row.university_freed) }}
                </span>
              </div>
            </template>
            <div class="projection-month-card__cell">
              <span class="text-caption text-medium-emphasis d-inline-flex align-center gap-1">
                Δ mes
                <VTooltip
                  location="top"
                  max-width="280"
                >
                  <template #activator="{ props: tipProps }">
                    <VIcon
                      v-bind="tipProps"
                      icon="ri-information-line"
                      size="14"
                      class="text-medium-emphasis"
                    />
                  </template>
                  <span>{{ columnTooltips.delta }}</span>
                </VTooltip>
              </span>
              <span class="text-body-2 projection__num projection__delta">
                {{ $formatAmount(row.delta) }}
              </span>
            </div>
            <div class="projection-month-card__cell">
              <span class="text-caption text-medium-emphasis d-inline-flex align-center gap-1">
                Saldo
                <VTooltip
                  location="top"
                  max-width="280"
                >
                  <template #activator="{ props: tipProps }">
                    <VIcon
                      v-bind="tipProps"
                      icon="ri-information-line"
                      size="14"
                      class="text-medium-emphasis"
                    />
                  </template>
                  <span>{{ columnTooltips.balance }}</span>
                </VTooltip>
              </span>
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
              <span class="d-inline-flex align-center justify-end gap-1">
                Salario
                <VTooltip
                  location="top"
                  max-width="280"
                >
                  <template #activator="{ props: tipProps }">
                    <VIcon
                      v-bind="tipProps"
                      icon="ri-information-line"
                      size="16"
                      class="text-medium-emphasis"
                    />
                  </template>
                  <span>{{ columnTooltips.salary }}</span>
                </VTooltip>
              </span>
            </th>
            <th
              v-if="projectionMode === 'real'"
              class="text-right"
            >
              <span class="d-inline-flex align-center justify-end gap-1">
                Gastos
                <VTooltip
                  location="top"
                  max-width="280"
                >
                  <template #activator="{ props: tipProps }">
                    <VIcon
                      v-bind="tipProps"
                      icon="ri-information-line"
                      size="16"
                      class="text-medium-emphasis"
                    />
                  </template>
                  <span>{{ columnTooltips.expenses }}</span>
                </VTooltip>
              </span>
            </th>
            <th
              v-if="projectionMode === 'fixed'"
              class="text-right"
            >
              <span class="d-inline-flex align-center justify-end gap-1">
                Queda
                <VTooltip
                  location="top"
                  max-width="280"
                >
                  <template #activator="{ props: tipProps }">
                    <VIcon
                      v-bind="tipProps"
                      icon="ri-information-line"
                      size="16"
                      class="text-medium-emphasis"
                    />
                  </template>
                  <span>{{ columnTooltips.remaining }}</span>
                </VTooltip>
              </span>
            </th>
            <th
              v-if="projectionMode === 'fixed'"
              class="text-right"
            >
              <span class="d-inline-flex align-center justify-end gap-1">
                Libre U
                <VTooltip
                  location="top"
                  max-width="280"
                >
                  <template #activator="{ props: tipProps }">
                    <VIcon
                      v-bind="tipProps"
                      icon="ri-information-line"
                      size="16"
                      class="text-medium-emphasis"
                    />
                  </template>
                  <span>{{ columnTooltips.universityFreed }}</span>
                </VTooltip>
              </span>
            </th>
            <th class="text-right">
              <span class="d-inline-flex align-center justify-end gap-1">
                Δ mes
                <VTooltip
                  location="top"
                  max-width="280"
                >
                  <template #activator="{ props: tipProps }">
                    <VIcon
                      v-bind="tipProps"
                      icon="ri-information-line"
                      size="16"
                      class="text-medium-emphasis"
                    />
                  </template>
                  <span>{{ columnTooltips.delta }}</span>
                </VTooltip>
              </span>
            </th>
            <th class="text-right">
              <span class="d-inline-flex align-center justify-end gap-1">
                Saldo
                <VTooltip
                  location="top"
                  max-width="280"
                >
                  <template #activator="{ props: tipProps }">
                    <VIcon
                      v-bind="tipProps"
                      icon="ri-information-line"
                      size="16"
                      class="text-medium-emphasis"
                    />
                  </template>
                  <span>{{ columnTooltips.balance }}</span>
                </VTooltip>
              </span>
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
              <div class="d-flex flex-wrap align-center gap-2">
                <VChip
                  size="small"
                  rounded="lg"
                  :color="row.pays_university ? 'info' : 'success'"
                  variant="tonal"
                >
                  {{ row.kind_label }}
                </VChip>
                <VChip
                  v-if="projectionMode === 'real' && row.partial"
                  size="small"
                  rounded="lg"
                  color="warning"
                  variant="tonal"
                >
                  Parcial
                </VChip>
              </div>
            </td>
            <td
              v-if="projectionMode === 'real'"
              class="text-right projection__num"
            >
              <span class="d-inline-flex align-center justify-end gap-1">
                <VTooltip
                  location="top"
                  max-width="320"
                >
                  <template #activator="{ props: tipProps }">
                    <VIcon
                      v-bind="tipProps"
                      icon="ri-information-line"
                      size="16"
                      class="text-medium-emphasis"
                    />
                  </template>
                  <span>{{ salaryInTooltip(row) }}</span>
                </VTooltip>
                <span>{{ $formatAmount(row.salary_in) }}</span>
              </span>
            </td>
            <td
              v-if="projectionMode === 'real'"
              class="text-right projection__num"
            >
              <span class="d-inline-flex align-center justify-end gap-1">
                <VTooltip
                  location="top"
                  max-width="280"
                >
                  <template #activator="{ props: tipProps }">
                    <VIcon
                      v-bind="tipProps"
                      icon="ri-information-line"
                      size="16"
                      class="text-medium-emphasis"
                    />
                  </template>
                  <span>{{ expensesOutTooltip(row) }}</span>
                </VTooltip>
                <span>{{ $formatAmount(row.expenses_out) }}</span>
              </span>
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
          Cada mes muestra salario y gastos completos (ambas quincenas; sin U si el mes es libre).
          El saldo parte de hoy: lo ya pasado del mes en curso no se vuelve a sumar.
          Ejemplo sept: 80 + 221 − 100 (casa; U no se paga) ≈ 201. Gastos del mes ≈ 294 (sin los 110 de U).
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
      projectionMode: 'real',
      year: currentYear,
      fromYear: currentYear,
      toYear: currentYear,
      rangeMode: 'year',
      fromMonth: new Date().getMonth() + 1,
      toMonth: 12,
      monthlyRemainingInput: '',
      universityFeeInput: '',
      startingBalanceInput: '',
      monthlySalaryInput: '',
      settings: {
        university_fee: 110000,
        monthly_remaining: 0,
        uses_fixed_payments_remaining: true,
      },
      sources: {
        account_balance: 0,
        prior_month_balance: 0,
        prior_month_label: '',
        fixed_payments_remaining: 0,
        payday_amount: 0,
        monthly_salary: 0,
      },
      startingBalance: 0,
      months: [],
      summary: null,
      monthOptions: MONTH_OPTIONS,
      projectionModeOptions: [
        { title: 'Real', value: 'real' },
        { title: 'Fija', value: 'fixed' },
      ],
      rangeModeOptions: [
        { title: 'Anual', value: 'year' },
        { title: 'Rango', value: 'custom' },
      ],
      yearOptions: [currentYear - 1, currentYear, currentYear + 1, currentYear + 2, currentYear + 3],
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
      if (this.projectionMode === 'real' && this.sources.prior_month_label) {
        return `Saldo hoy: ${this.$formatAmount(this.sources.account_balance)} · cierre ${this.sources.prior_month_label}: ${this.$formatAmount(this.sources.prior_month_balance)}`
      }

      return `Saldo en cuenta hoy: ${this.$formatAmount(this.sources.account_balance)}`
    },

    salaryHint() {
      const payday = this.$parseAmount(this.monthlySalaryInput)
      const quincena = payday === '' || Number.isNaN(payday)
        ? (this.sources.payday_amount || 0)
        : payday / 2

      return `Quincena: ${this.$formatAmount(quincena)} · registrado: ${this.$formatAmount(this.sources.monthly_salary || 0)}`
    },

    periodLabel() {
      if (this.projectionMode === 'fixed' || this.rangeMode === 'year') {
        if (this.projectionMode === 'real' && this.year === new Date().getFullYear()) {
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
      if (this.projectionMode !== 'real' || this.rangeMode !== 'custom')
        return false

      const from = this.fromYear * 12 + this.fromMonth
      const to = this.toYear * 12 + this.toMonth

      return from > to
    },

    columnTooltips() {
      if (this.projectionMode === 'real') {
        return {
          salary: 'Salario del mes completo (quincena del 1 + quincena del 15). Si el mes es parcial, el saldo de hoy ya incluye lo del 1; el Δ solo suma lo que falta.',
          expenses: 'Pagos fijos del mes (primero + segundo). En meses sin U no cuenta la cuota de universidad.',
          delta: 'Lo que aún mueve el saldo desde hoy: quincenas pendientes − sus gastos. En un mes completo coincide con salario − gastos.',
          balance: 'Saldo acumulado al cierre del mes. En el mes en curso: saldo de hoy + Δ pendiente (ej. 80 + 221 − 100 ≈ 201).',
        }
      }

      return {
        remaining: 'Monto “queda al mes” (salario − pagos fijos, o el valor guardado en proyección).',
        universityFreed: 'En meses sin pago U se suma la cuota, porque esa plata no sale de la cuenta.',
        delta: 'Queda al mes + liberado por U (si el mes es libre).',
        balance: 'Saldo acumulado partiendo del saldo inicial + Δ de cada mes.',
      }
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
            tooltip: 'Saldo inicial + salario proyectado − gastos proyectados en el rango.',
            icon: 'ri-wallet-3-line',
            color: 'primary',
            valueClass: 'text-primary',
          },
          {
            title: 'Salario proyectado',
            value: this.$formatAmount(s.total_salary_in),
            subtitle: `Quincena ${this.$formatAmount((this.$parseAmount(this.monthlySalaryInput) || this.sources.monthly_salary || 0) / 2)}`,
            tooltip: 'Suma de lo que aún entra desde hoy (quincenas pendientes). El detalle mes a mes muestra el salario completo del mes.',
            icon: 'ri-money-dollar-circle-line',
            color: 'info',
            valueClass: '',
          },
          {
            title: 'Gastos proyectados',
            value: this.$formatAmount(s.total_expenses_out),
            subtitle: `${s.free_months_count} mes(es) sin U`,
            tooltip: 'Suma de lo que aún sale desde hoy. En cada mes, la columna Gastos muestra el mes completo (sin U si aplica).',
            icon: 'ri-bill-line',
            color: 'error',
            valueClass: '',
          },
          {
            title: 'Δ total',
            value: this.$formatAmount(s.total_delta),
            subtitle: 'Ingresos − gastos',
            tooltip: 'Salario proyectado − gastos proyectados en todo el rango.',
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
          tooltip: 'Saldo inicial + suma de “queda” + liberado por U en el rango.',
          icon: 'ri-wallet-3-line',
          color: 'primary',
          valueClass: 'text-primary',
        },
        {
          title: 'Suma de “quedan libres al mes”',
          value: this.$formatAmount(s.total_monthly_remaining),
          subtitle: `${s.payment_months_count + s.free_months_count} meses`,
          tooltip: '“Queda al mes” × cantidad de meses del rango (salario − fijos, o el monto guardado).',
          icon: 'ri-stack-line',
          color: 'info',
          valueClass: '',
        },
        {
          title: 'Liberado por U',
          value: this.$formatAmount(s.total_university_freed),
          subtitle: `${s.free_months_count} mes(es) sin pago`,
          tooltip: 'Cuota U × meses sin pago. Esa plata se queda en la cuenta.',
          icon: 'ri-gift-line',
          color: 'success',
          valueClass: 'projection__freed',
        },
        {
          title: 'Total proyectado',
          value: this.$formatAmount(s.total_delta),
          subtitle: 'Queda + liberado U',
          tooltip: 'Suma de lo que queda cada mes más lo liberado en meses sin U.',
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
    projectionMode(mode) {
      this.startingBalanceInput = ''
      this.monthlySalaryInput = ''

      if (mode === 'fixed') {
        this.rangeMode = 'year'
        this.fromMonth = 1
        this.toMonth = 12
        this.fromYear = this.year
        this.toYear = this.year
      }
    },
  },

  mounted() {
    this.loadProjection()
  },

  methods: {
    effectiveRange() {
      if (this.projectionMode === 'fixed' || this.rangeMode === 'year') {
        const now = new Date()
        let fromMonth = 1
        const toMonth = 12
        const year = this.year

        if (this.projectionMode === 'real' && year === now.getFullYear())
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
      const monthlySalary = this.$parseAmount(this.monthlySalaryInput)
      const params = {
        mode: this.projectionMode,
        year: range.from_year,
        from_year: range.from_year,
        from_month: range.from_month,
        to_year: range.to_year,
        to_month: range.to_month,
      }

      if (starting !== '' && !Number.isNaN(starting))
        params.starting_balance = starting

      if (this.projectionMode === 'real' && monthlySalary !== '' && !Number.isNaN(monthlySalary))
        params.monthly_salary = monthlySalary

      return axios
        .get('/api/projection', { params })
        .then(response => {
          this.applyResponse(response.data, {
            preserveStartingInput: starting !== '' && !Number.isNaN(starting),
            preserveSalaryInput: monthlySalary !== '' && !Number.isNaN(monthlySalary),
          })
        })
        .catch(error => {
          this.error = error.response?.data?.message || 'No se pudo cargar la proyección.'
        })
        .finally(() => {
          this.loading = false
        })
    },

    applyResponse(data, { preserveStartingInput = false, preserveSalaryInput = false } = {}) {
      this.projectionMode = data.mode || this.projectionMode
      this.settings = {
        university_fee: data.settings.university_fee,
        monthly_remaining: data.settings.monthly_remaining ?? this.settings.monthly_remaining,
        uses_fixed_payments_remaining: data.settings.uses_fixed_payments_remaining ?? true,
      }
      this.sources = {
        account_balance: data.sources.account_balance ?? 0,
        prior_month_balance: data.sources.prior_month_balance ?? data.sources.account_balance ?? 0,
        prior_month_label: data.sources.prior_month_label ?? '',
        fixed_payments_remaining: data.sources.fixed_payments_remaining ?? 0,
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

      if (data.settings.monthly_remaining != null)
        this.monthlyRemainingInput = this.$formatAmountValue(data.settings.monthly_remaining)
      else if (data.sources.fixed_payments_remaining != null)
        this.monthlyRemainingInput = this.$formatAmountValue(data.sources.fixed_payments_remaining)

      this.universityFeeInput = this.$formatAmountValue(data.settings.university_fee)

      if (!preserveStartingInput)
        this.startingBalanceInput = this.$formatAmountValue(data.starting_balance)

      if (!preserveSalaryInput) {
        const salary = data.monthly_salary ?? data.sources.monthly_salary ?? 0
        this.monthlySalaryInput = this.$formatAmountValue(salary)
      }
    },

    useAccountBalance() {
      this.startingBalanceInput = this.$formatAmountValue(this.sources.account_balance)
    },

    useRegisteredSalary() {
      this.monthlySalaryInput = this.$formatAmountValue(this.sources.monthly_salary || 0)
    },

    useFixedRemaining() {
      this.monthlyRemainingInput = this.$formatAmountValue(this.sources.fixed_payments_remaining)
    },

    salaryInTooltip(row) {
      const q = this.$formatAmount(row?.primero?.full_income || row?.segundo?.full_income || 0)
      const full = this.$formatAmount(row.salary_in)
      const pending = this.$formatAmount(row.projected_salary_in ?? 0)

      if (row.partial && row?.primero?.skipped && row?.segundo?.applied) {
        return `Mes completo: ${full} (2 × ${q}). El saldo de hoy ya incluye el 1. Desde hoy solo suma el 15: ${pending}.`
      }

      if (row.partial) {
        return `Mes completo: ${full}. Pendiente desde hoy: ${pending}.`
      }

      return `Salario del mes: ${full} (quincenas del 1 y del 15).`
    },

    expensesOutTooltip(row) {
      const p = this.$formatAmount(row?.primero?.full_expense || 0)
      const s = this.$formatAmount(row?.segundo?.full_expense || 0)
      const full = this.$formatAmount(row.expenses_out)
      const pending = this.$formatAmount(row.projected_expenses_out ?? 0)
      const freed = row.university_freed > 0
        ? ` Sin U este mes (−${this.$formatAmount(row.university_freed)}).`
        : ''

      if (row.partial && row?.primero?.skipped && row?.segundo?.applied) {
        return `Mes completo: ${full} = primero ${p} + segundo ${s}.${freed} El 1 ya está en el saldo de hoy. Desde hoy solo sale el 15: ${pending}. Fin de mes ≈ saldo hoy + ${this.$formatAmount(row.projected_salary_in)} − ${pending}.`
      }

      if (row.partial) {
        return `Mes completo: ${full}.${freed} Pendiente desde hoy: ${pending}.`
      }

      return `Gastos del mes: ${full} = primero ${p} + segundo ${s}.${freed}`
    },

    saveAndReload({ clearRemainingOverride = false, closeSheet = false } = {}) {
      if (this.isRangeInvalid)
        return

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
