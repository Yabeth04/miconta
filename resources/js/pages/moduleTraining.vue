<template>
  <div class="training-page">
    <div class="d-flex flex-wrap align-center justify-space-between gap-3 mb-3">
      <div class="d-flex align-center gap-2">
        <h1 class="text-h4 font-weight-medium mb-0">
          Entrenamiento
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

    <VTabs
      v-model="activeTab"
      class="mb-4 training-tabs"
      color="primary"
      density="default"
      grow
      fixed-tabs
      height="72"
    >
      <VTab
        value="hoy"
        stacked
      >
        <VIcon icon="ri-flashlight-line" />
        Hoy
      </VTab>
      <VTab
        value="semana"
        stacked
      >
        <VIcon icon="ri-calendar-schedule-line" />
        Semana
      </VTab>
      <VTab
        value="historial"
        stacked
      >
        <VIcon icon="ri-history-line" />
        Historial
      </VTab>
      <VTab
        value="biblioteca"
        stacked
      >
        <VIcon icon="ri-book-open-line" />
        Biblioteca
      </VTab>
    </VTabs>

    <VWindow
      v-model="activeTab"
      :touch="false"
    >
      <!-- Hoy: lo que entrenás AHORA -->
      <VWindowItem value="hoy">
        <div
          v-if="loading && !activeDay"
          class="training-empty"
        >
          Cargando…
        </div>

        <template v-else-if="activeDay">
          <header class="training-hoy__header mb-3">
            <p class="training-hoy__eyebrow mb-1">
              Hoy · {{ calendarDayLabel }}
            </p>
            <h2 class="training-hoy__focus mb-0">
              {{ activeDay.is_rest
                ? (activeDaySummary || 'Descanso / correr')
                : (activeDaySummary || 'Todavía sin plan') }}
            </h2>
            <p
              v-if="isSwappedRoutine"
              class="training-hoy__swap-note mb-0 mt-2"
            >
              Hoy toca {{ todayDaySummary || todayDay?.label }}, pero estás haciendo la de {{ activeDay.label }}.
            </p>
          </header>

          <div class="training-hoy__pick mb-4">
            <p class="training-hoy__pick-label mb-2">
              ¿Qué rutina hago?
            </p>
            <div class="training-day-chips">
              <button
                v-for="day in days"
                :key="`hoy-${day.id}`"
                type="button"
                class="training-day-chip"
                :class="{
                  'training-day-chip--active': activeDayId === day.id,
                  'training-day-chip--today': day.weekday === todayWeekday,
                }"
                @click="activeDayId = day.id"
              >
                {{ shortDay(day.label) }}
              </button>
            </div>
            <div
              v-if="isSwappedRoutine"
              class="training-hoy__swap-actions mt-2"
            >
              <VBtn
                size="small"
                variant="text"
                rounded="lg"
                class="px-1"
                @click="resetToTodayRoutine"
              >
                Volver a la de hoy
              </VBtn>
            </div>
          </div>

          <div
            v-if="activeDay.is_rest"
            class="training-hoy__rest"
          >
            <div
              v-if="!activeDay.exercises?.length"
              class="training-hoy__empty"
            >
              <p class="mb-3">
                Día libre / correr. Agregá una actividad con kilómetros para ir midiendo.
              </p>
              <VBtn
                color="primary"
                variant="tonal"
                rounded="lg"
                @click="goEditDay(activeDay)"
              >
                Armar cardio
              </VBtn>
            </div>

            <template v-else>
              <div class="training-hoy__list">
                <div
                  v-for="item in activeDay.exercises"
                  :key="item.id"
                  class="training-hoy__ex"
                >
                  <div class="min-w-0 flex-grow-1">
                    <p class="training-hoy__ex-name mb-1">
                      {{ item.name }}
                    </p>
                    <p class="training-hoy__ex-rx mb-0">
                      {{ formatLoad(item) }}
                    </p>
                    <p
                      v-if="item.notes"
                      class="training-hoy__ex-note mb-0"
                    >
                      {{ item.notes }}
                    </p>
                  </div>
                  <VBtn
                    icon
                    variant="text"
                    size="small"
                    aria-label="Ajustar kilómetros"
                    @click="openQuickEdit(item)"
                  >
                    <VIcon
                      icon="ri-pencil-line"
                      size="18"
                    />
                  </VBtn>
                </div>
              </div>

              <div class="training-hoy__actions">
                <VBtn
                  color="primary"
                  rounded="lg"
                  size="large"
                  block
                  prepend-icon="ri-checkbox-circle-line"
                  @click="openSessionFromDay(activeDay)"
                >
                  Terminé · registrar
                </VBtn>
                <VBtn
                  variant="text"
                  rounded="lg"
                  block
                  class="mt-1"
                  @click="goEditDay(activeDay)"
                >
                  Editar estas actividades
                </VBtn>
              </div>
            </template>
          </div>

          <template v-else>
            <div
              v-if="!activeDay.exercises?.length"
              class="training-hoy__empty"
            >
              <p class="mb-3">
                No hay ejercicios armados en esta rutina.
              </p>
              <VBtn
                color="primary"
                variant="tonal"
                rounded="lg"
                @click="goEditDay(activeDay)"
              >
                Armar esta rutina
              </VBtn>
            </div>

            <div
              v-else
              class="training-hoy__list"
            >
              <div
                v-for="group in activeGroupedExercises"
                :key="group.name"
                class="training-hoy__group"
              >
                <p class="training-hoy__group-title">
                  <MuscleGroupIcon
                    v-if="hasMuscleIcon(group.name)"
                    :group="group.name"
                  />
                  {{ group.name }}
                </p>
                <div
                  v-for="item in group.items"
                  :key="item.id"
                  class="training-hoy__ex"
                >
                  <div class="min-w-0 flex-grow-1">
                    <p class="training-hoy__ex-name mb-1">
                      {{ item.name }}
                    </p>
                    <p class="training-hoy__ex-rx mb-0">
                      {{ item.reps }}×{{ item.sets }}
                      <span class="training-hoy__ex-dot">·</span>
                      {{ formatLoad(item) }}
                    </p>
                    <p
                      v-if="item.notes"
                      class="training-hoy__ex-note mb-0"
                    >
                      {{ item.notes }}
                    </p>
                  </div>
                  <VBtn
                    icon
                    variant="text"
                    size="small"
                    aria-label="Ajustar reps o nivel"
                    @click="openQuickEdit(item)"
                  >
                    <VIcon
                      icon="ri-pencil-line"
                      size="18"
                    />
                  </VBtn>
                </div>
              </div>
            </div>

            <div class="training-hoy__actions">
              <VBtn
                v-if="activeDay.exercises?.length"
                color="primary"
                rounded="lg"
                size="large"
                block
                prepend-icon="ri-checkbox-circle-line"
                @click="openSessionFromDay(activeDay)"
              >
                Terminé · registrar
              </VBtn>
              <VBtn
                variant="text"
                rounded="lg"
                block
                class="mt-1"
                @click="goEditDay(activeDay)"
              >
                Editar estos ejercicios
              </VBtn>
            </div>
          </template>
        </template>
      </VWindowItem>

      <!-- Semana: plan + mover grupos + editar día -->
      <VWindowItem value="semana">
        <p class="text-body-2 text-medium-emphasis mb-3">
          Arrastrá un grupo a otro día. Tocá un día para editarlo.
        </p>

        <div
          class="training-board mb-4"
          :class="{ 'training-board--busy': dragLocked }"
        >
          <div
            v-for="column in weekBoard"
            :key="column.id"
            class="training-board__day"
            :class="{
              'training-board__day--today': column.weekday === todayWeekday,
              'training-board__day--rest': column.is_rest,
              'training-board__day--selected': editPanelOpen && selectedDayId === column.id,
            }"
          >
            <button
              type="button"
              class="training-board__head"
              @click="toggleEditDay(days.find(d => d.id === column.id))"
            >
              <span class="training-board__label">
                {{ column.label }}
                <span
                  v-if="column.weekday === todayWeekday"
                  class="training-board__hoy"
                >hoy</span>
              </span>
              <span class="training-board__focus">
                {{ column.is_rest
                  ? (column.focus || 'Descanso / correr')
                  : (column.focus || 'Sin definir') }}
              </span>
            </button>

            <VueDraggable
              v-model="column.groups"
              :group="{ name: 'training-muscle-groups', pull: true, put: true }"
              :animation="180"
              handle=".training-board__handle"
              filter="input, textarea, select, .v-field, .v-btn, button:not(.training-board__handle)"
              :prevent-on-filter="false"
              class="training-board__drop"
              :disabled="dragLocked || column.is_rest"
              @add="onGroupMoved($event, column)"
              @update="onGroupReordered(column)"
            >
              <div
                v-for="group in column.groups"
                :key="`${column.id}-${group.key}`"
                class="training-board__group"
              >
                <button
                  type="button"
                  class="training-board__handle"
                  aria-label="Mover grupo"
                >
                  <VIcon
                    icon="ri-draggable"
                    size="18"
                  />
                </button>
                <div class="min-w-0 d-flex align-center gap-2">
                  <MuscleGroupIcon
                    v-if="hasMuscleIcon(group.name)"
                    :group="group.name"
                    size="lg"
                  />
                  <div class="min-w-0">
                    <p class="training-board__group-name mb-0">
                      {{ group.name }}
                    </p>
                    <p class="training-board__group-meta mb-0">
                      {{ group.count }} ejercicio{{ group.count === 1 ? '' : 's' }}
                    </p>
                  </div>
                </div>
              </div>
            </VueDraggable>

            <p
              v-if="!column.groups.length"
              class="training-board__empty"
            >
              {{ column.is_rest ? 'Descanso / correr' : 'Sin grupos' }}
            </p>
          </div>
        </div>

        <VDialog
          v-model="editPanelOpen"
          max-width="560"
          scrollable
        >
          <VCard
            v-if="selectedDay"
            rounded="lg"
            :loading="loading"
            class="training-day-editor"
          >
            <VCardTitle class="d-flex align-center justify-space-between gap-2 pe-2">
              <span>{{ selectedDay.label }}</span>
              <VBtn
                icon
                variant="text"
                size="small"
                aria-label="Cerrar"
                @click="editPanelOpen = false"
              >
                <VIcon icon="ri-close-line" />
              </VBtn>
            </VCardTitle>
            <VCardText class="d-flex flex-column gap-4 pt-2">
              <div class="d-flex flex-wrap align-center justify-space-between gap-2">
                <div class="min-w-0">
                  <p class="text-body-2 text-medium-emphasis mb-1">
                    Resumen automático
                  </p>
                  <p class="text-subtitle-1 font-weight-medium mb-0">
                    {{ selectedDay.is_rest
                      ? (selectedDaySummary || 'Descanso / correr')
                      : (selectedDaySummary || 'Agregá ejercicios con grupo muscular') }}
                  </p>
                </div>
                <VSwitch
                  v-model="selectedDay.is_rest"
                  color="primary"
                  hide-details
                  label="Descanso"
                  density="compact"
                  @update:model-value="saveDay"
                />
              </div>

              <div
                v-if="selectedDay.is_rest"
                class="d-flex flex-column gap-4"
              >
                <div class="d-flex align-center justify-space-between gap-2">
                  <p class="text-subtitle-2 font-weight-medium mb-0">
                    Actividades (km)
                  </p>
                  <VBtn
                    size="small"
                    color="primary"
                    variant="tonal"
                    rounded="lg"
                    prepend-icon="ri-add-line"
                    @click="openCardioExercise()"
                  >
                    Agregar
                  </VBtn>
                </div>

                <div
                  v-if="!selectedDay.exercises?.length"
                  class="training-empty py-6"
                >
                  Ej. Correr · 5 km. Vas midiendo distancia, sin series ni nivel.
                </div>

                <div
                  v-else
                  class="training-drag-list"
                >
                  <div
                    v-for="item in selectedDay.exercises"
                    :key="item.id"
                    class="training-exercise"
                  >
                    <div class="min-w-0 flex-grow-1">
                      <p class="font-weight-medium mb-0">
                        {{ item.name }}
                      </p>
                      <p class="text-body-2 text-medium-emphasis mb-0">
                        {{ formatLoad(item) }}
                      </p>
                    </div>
                    <div class="d-flex gap-1">
                      <VBtn
                        icon
                        variant="text"
                        size="small"
                        @click="openExerciseFromDay(selectedDay, item)"
                      >
                        <VIcon icon="ri-pencil-line" />
                      </VBtn>
                      <VBtn
                        icon
                        variant="text"
                        size="small"
                        @click="deleteExercise(item)"
                      >
                        <VIcon icon="ri-delete-bin-line" />
                      </VBtn>
                    </div>
                  </div>
                </div>
              </div>

              <template v-else>
                <div class="d-flex align-center justify-space-between gap-2">
                  <p class="text-subtitle-2 font-weight-medium mb-0">
                    Ejercicios
                  </p>
                  <VBtn
                    size="small"
                    color="primary"
                    variant="tonal"
                    rounded="lg"
                    prepend-icon="ri-add-line"
                    @click="openPickExercise()"
                  >
                    Agregar
                  </VBtn>
                </div>

                <div
                  v-if="!selectedDay.exercises?.length"
                  class="training-empty py-6"
                >
                  Agregá ejercicios con reps, series y nivel. El grupo muscular arma el resumen del día (ej. Pecho + Tríceps).
                </div>

                <template v-else>
                  <p class="text-caption text-medium-emphasis mb-0">
                    Arrastrá el ícono para cambiar el orden.
                  </p>
                  <VueDraggable
                    v-model="selectedDay.exercises"
                    :animation="180"
                    handle=".training-drag-handle"
                    filter="input, textarea, select, .v-field, .v-btn, button:not(.training-drag-handle)"
                    :prevent-on-filter="false"
                    class="training-drag-list"
                    :disabled="dragLocked"
                    @update="saveExerciseOrder"
                  >
                    <div
                      v-for="item in selectedDay.exercises"
                      :key="item.id"
                      class="training-exercise"
                    >
                      <button
                        type="button"
                        class="training-drag-handle"
                        aria-label="Reordenar"
                      >
                        <VIcon
                          icon="ri-draggable"
                          size="20"
                        />
                      </button>
                      <div class="min-w-0 flex-grow-1">
                        <p class="font-weight-medium mb-0">
                          {{ item.name }}
                        </p>
                        <p class="text-body-2 text-medium-emphasis mb-0">
                          <span v-if="item.muscle_group">{{ item.muscle_group }} · </span>
                          {{ item.reps }}×{{ item.sets }} · {{ formatLoad(item) }}
                        </p>
                      </div>
                      <div class="d-flex gap-1">
                        <VBtn
                          icon
                          variant="text"
                          size="small"
                          @click="openExerciseFromDay(selectedDay, item)"
                        >
                          <VIcon icon="ri-pencil-line" />
                        </VBtn>
                        <VBtn
                          icon
                          variant="text"
                          size="small"
                          @click="deleteExercise(item)"
                        >
                          <VIcon icon="ri-delete-bin-line" />
                        </VBtn>
                      </div>
                    </div>
                  </VueDraggable>
                </template>
              </template>
            </VCardText>
            <VCardActions class="px-4 pb-4">
              <VSpacer />
              <VBtn
                color="primary"
                rounded="lg"
                @click="editPanelOpen = false"
              >
                Listo
              </VBtn>
            </VCardActions>
          </VCard>
        </VDialog>
      </VWindowItem>

      <!-- Historial -->
      <VWindowItem value="historial">
        <div class="d-flex flex-wrap align-center justify-space-between gap-2 mb-3">
          <p class="text-body-2 text-medium-emphasis mb-0">
            Esta semana: {{ summary.week_sessions }} sesión(es)
            <span v-if="summary.week_minutes"> · {{ formatDuration(summary.week_minutes) }}</span>
          </p>
          <VBtn
            color="primary"
            rounded="lg"
            size="small"
            prepend-icon="ri-add-line"
            @click="openSessionFromDay(todayDay)"
          >
            Registrar
          </VBtn>
        </div>

        <VCard
          rounded="lg"
          class="training-history overflow-hidden"
          :loading="loading"
        >
          <div
            v-if="!loading && sessions.length === 0"
            class="training-empty"
          >
            Acá aparece lo que ya entrenaste.
          </div>

          <div
            v-else
            class="training-history__list"
          >
            <div
              v-for="session in sessions"
              :key="session.id"
              class="training-history__item"
            >
              <div class="min-w-0">
                <p class="font-weight-medium mb-0">
                  {{ formatSessionDate(session.date) }}
                  <span
                    v-if="session.weekday_label"
                    class="text-medium-emphasis font-weight-regular"
                  >
                    · {{ session.weekday_label }}
                  </span>
                </p>
                <p class="text-caption text-medium-emphasis mb-0">
                  {{ session.focus || muscleSummary(session) }}
                  <span v-if="session.duration_minutes"> · {{ formatDuration(session.duration_minutes) }}</span>
                  <span v-if="session.calories"> · {{ session.calories }} kcal</span>
                </p>
              </div>
              <div class="d-flex gap-1">
                <VBtn
                  icon
                  variant="text"
                  size="small"
                  @click="openSession(session)"
                >
                  <VIcon icon="ri-pencil-line" />
                </VBtn>
                <VBtn
                  icon
                  variant="text"
                  size="small"
                  @click="deleteSession(session)"
                >
                  <VIcon icon="ri-delete-bin-line" />
                </VBtn>
              </div>
            </div>
          </div>
        </VCard>
      </VWindowItem>

      <!-- Biblioteca -->
      <VWindowItem value="biblioteca">
        <div class="d-flex flex-wrap align-center justify-space-between gap-2 mb-3">
          <p class="text-body-2 text-medium-emphasis mb-0">
            Solo nombre y grupo. Series y nivel se ajustan en cada día.
          </p>
          <VBtn
            color="primary"
            rounded="lg"
            size="small"
            prepend-icon="ri-add-line"
            @click="openLibraryExercise()"
          >
            Nuevo
          </VBtn>
        </div>

        <VTextField
          v-model="libraryQuery"
          class="mb-3"
          label="Buscar"
          prepend-inner-icon="ri-search-line"
          variant="outlined"
          rounded="lg"
          hide-details
          clearable
          density="comfortable"
        />

        <div
          v-if="loading && !library.length"
          class="training-empty"
        >
          Cargando…
        </div>

        <div
          v-else-if="!filteredLibrary.length"
          class="training-empty"
        >
          {{ libraryQuery ? 'Nada coincide con la búsqueda.' : 'Todavía no hay ejercicios. Creá el primero.' }}
        </div>

        <div
          v-else
          class="d-flex flex-column gap-4"
        >
          <section
            v-for="group in groupedLibrary"
            :key="group.name"
          >
            <p class="text-subtitle-2 font-weight-medium mb-2 d-flex align-center gap-2">
              <MuscleGroupIcon
                v-if="hasMuscleIcon(group.name)"
                :group="group.name"
              />
              {{ group.name }}
            </p>
            <div class="training-drag-list">
              <div
                v-for="item in group.items"
                :key="item.id"
                class="training-exercise"
              >
                <div class="min-w-0 flex-grow-1">
                  <p class="font-weight-medium mb-0">
                    {{ item.name }}
                  </p>
                </div>
                <div class="d-flex gap-1">
                  <VBtn
                    icon
                    variant="text"
                    size="small"
                    @click="openLibraryExercise(item)"
                  >
                    <VIcon icon="ri-pencil-line" />
                  </VBtn>
                  <VBtn
                    icon
                    variant="text"
                    size="small"
                    @click="deleteLibraryExercise(item)"
                  >
                    <VIcon icon="ri-delete-bin-line" />
                  </VBtn>
                </div>
              </div>
            </div>
          </section>
        </div>
      </VWindowItem>
    </VWindow>

    <VDialog
      v-model="helpDialog"
      max-width="480"
    >
      <VCard rounded="lg">
        <VCardTitle class="text-h6">
          ¿Cómo se usa?
        </VCardTitle>
        <VCardText>
          <p class="mb-3">
            <strong>Hoy:</strong> lo que mirás en el gym. Con el lápiz ajustás reps/nivel o km si es cardio. Podés elegir otra rutina solo por hoy. Para armar el plan, usá Semana.
          </p>
          <p class="mb-3">
            <strong>Semana:</strong> arrastrá grupos (Abdomen, Bíceps…) entre días. Tocá el día para editar ejercicios.
          </p>
          <p class="mb-3">
            <strong>Historial:</strong> lo que realmente hiciste al terminar.
          </p>
          <p class="mb-0">
            <strong>Biblioteca:</strong> solo nombre y grupo muscular. Series, reps y nivel se definen en cada día.
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

    <VDialog
      v-model="quickEditDialog"
      max-width="420"
    >
      <VCard rounded="lg">
        <VCardTitle class="text-h6">
          {{ quickEditForm.name || 'Ajustar' }}
        </VCardTitle>
        <VCardText>
          <p class="text-caption text-medium-emphasis mb-4">
            Cambio en el plan (queda para las próximas veces).
          </p>

          <div class="training-quick">
            <template v-if="quickEditForm.load_type === 'km'">
              <div class="training-quick__row">
                <span class="training-quick__label">Km</span>
                <div class="training-quick__ctrl">
                  <VBtn
                    icon
                    variant="tonal"
                    size="small"
                    rounded="lg"
                    @click="bumpQuick('load_value', -0.5)"
                  >
                    <VIcon icon="ri-subtract-line" />
                  </VBtn>
                  <span class="training-quick__value">{{ quickEditForm.load_value ?? 0 }}</span>
                  <VBtn
                    icon
                    variant="tonal"
                    size="small"
                    rounded="lg"
                    @click="bumpQuick('load_value', 0.5)"
                  >
                    <VIcon icon="ri-add-line" />
                  </VBtn>
                </div>
              </div>
            </template>

            <template v-else>
              <div class="training-quick__row">
                <span class="training-quick__label">Reps</span>
                <div class="training-quick__ctrl">
                  <VBtn
                    icon
                    variant="tonal"
                    size="small"
                    rounded="lg"
                    @click="bumpQuick('reps', -1)"
                  >
                    <VIcon icon="ri-subtract-line" />
                  </VBtn>
                  <span class="training-quick__value">{{ quickEditForm.reps }}</span>
                  <VBtn
                    icon
                    variant="tonal"
                    size="small"
                    rounded="lg"
                    @click="bumpQuick('reps', 1)"
                  >
                    <VIcon icon="ri-add-line" />
                  </VBtn>
                </div>
              </div>

              <div class="training-quick__row">
                <span class="training-quick__label">Series</span>
                <div class="training-quick__ctrl">
                  <VBtn
                    icon
                    variant="tonal"
                    size="small"
                    rounded="lg"
                    @click="bumpQuick('sets', -1)"
                  >
                    <VIcon icon="ri-subtract-line" />
                  </VBtn>
                  <span class="training-quick__value">{{ quickEditForm.sets }}</span>
                  <VBtn
                    icon
                    variant="tonal"
                    size="small"
                    rounded="lg"
                    @click="bumpQuick('sets', 1)"
                  >
                    <VIcon icon="ri-add-line" />
                  </VBtn>
                </div>
              </div>

              <div
                v-if="quickEditForm.load_type !== 'bodyweight'"
                class="training-quick__row"
              >
                <span class="training-quick__label">
                  {{ quickEditForm.load_type === 'level' ? 'Nivel' : 'Kg' }}
                </span>
                <div class="training-quick__ctrl">
                  <VBtn
                    icon
                    variant="tonal"
                    size="small"
                    rounded="lg"
                    @click="bumpQuick('load_value', quickEditForm.load_type === 'level' ? -1 : -0.5)"
                  >
                    <VIcon icon="ri-subtract-line" />
                  </VBtn>
                  <span class="training-quick__value">{{ quickEditForm.load_value ?? 0 }}</span>
                  <VBtn
                    icon
                    variant="tonal"
                    size="small"
                    rounded="lg"
                    @click="bumpQuick('load_value', quickEditForm.load_type === 'level' ? 1 : 0.5)"
                  >
                    <VIcon icon="ri-add-line" />
                  </VBtn>
                </div>
              </div>
            </template>
          </div>
        </VCardText>
        <VCardActions>
          <VSpacer />
          <VBtn
            variant="text"
            rounded="lg"
            @click="quickEditDialog = false"
          >
            Cancelar
          </VBtn>
          <VBtn
            color="primary"
            rounded="lg"
            :loading="saving"
            @click="saveQuickEdit"
          >
            Guardar
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <VDialog
      v-model="pickExerciseDialog"
      max-width="480"
    >
      <VCard rounded="lg">
        <VCardTitle class="text-h6">
          Agregar ejercicio
        </VCardTitle>
        <VCardText class="d-flex flex-column gap-3 pt-4">
          <VTextField
            v-model="pickQuery"
            label="Buscar en biblioteca"
            prepend-inner-icon="ri-search-line"
            variant="outlined"
            rounded="lg"
            hide-details
            clearable
            density="comfortable"
            autofocus
          />

          <VBtn
            color="primary"
            variant="tonal"
            rounded="lg"
            block
            prepend-icon="ri-add-line"
            @click="createExerciseFromPick"
          >
            Crear nuevo
          </VBtn>

          <div
            v-if="!pickFilteredLibrary.length"
            class="training-empty py-4"
          >
            {{ pickEmptyMessage }}
          </div>

          <div
            v-else
            class="training-drag-list"
            style="max-height: 320px; overflow: auto;"
          >
            <button
              v-for="item in pickFilteredLibrary"
              :key="`pick-${item.id}`"
              type="button"
              class="training-exercise training-exercise--pick"
              :disabled="saving"
              @click="attachFromLibrary(item)"
            >
              <div class="min-w-0 flex-grow-1 text-start">
                <p class="font-weight-medium mb-0">
                  {{ item.name }}
                </p>
                <p
                  v-if="item.muscle_group"
                  class="text-body-2 text-medium-emphasis mb-0"
                >
                  {{ item.muscle_group }}
                </p>
              </div>
              <VIcon
                icon="ri-add-circle-line"
                size="22"
              />
            </button>
          </div>
        </VCardText>
        <VCardActions>
          <VSpacer />
          <VBtn
            variant="text"
            rounded="lg"
            @click="pickExerciseDialog = false"
          >
            Cancelar
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <VDialog
      v-model="exerciseDialog"
      max-width="480"
    >
      <VCard rounded="lg">
        <VCardTitle class="text-h6">
          {{ exerciseDialogTitle }}
        </VCardTitle>
        <VCardText class="d-flex flex-column gap-4 pt-4">
          <VTextField
            v-model="exerciseForm.name"
            :label="isCardioForm ? 'Actividad' : 'Ejercicio'"
            variant="outlined"
            rounded="lg"
            hide-details="auto"
            autofocus
          />
          <VSelect
            v-if="!isCardioForm"
            v-model="exerciseForm.muscle_group"
            :items="muscleOptions"
            label="Grupo muscular"
            variant="outlined"
            rounded="lg"
            hide-details="auto"
            clearable
          >
            <template #selection="{ item }">
              <span class="d-inline-flex align-center gap-2">
                <MuscleGroupIcon
                  v-if="hasMuscleIcon(item.value)"
                  :group="item.value"
                />
                {{ item.title }}
              </span>
            </template>
            <template #item="{ props: itemProps, item }">
              <VListItem v-bind="itemProps">
                <template #prepend>
                  <MuscleGroupIcon
                    v-if="hasMuscleIcon(item.value)"
                    :group="item.value"
                    class="me-1"
                  />
                </template>
              </VListItem>
            </template>
          </VSelect>
          <template v-if="exerciseMode === 'day' && isCardioForm">
            <VTextField
              v-model.number="exerciseForm.load_value"
              type="number"
              inputmode="decimal"
              label="Kilómetros"
              variant="outlined"
              rounded="lg"
              hide-details="auto"
            />
            <VTextField
              v-model="exerciseForm.notes"
              label="Nota (opcional)"
              variant="outlined"
              rounded="lg"
              hide-details="auto"
            />
          </template>
          <template v-else-if="exerciseMode === 'day'">
            <div class="d-flex gap-3">
              <VTextField
                v-model.number="exerciseForm.reps"
                type="number"
                inputmode="numeric"
                label="Reps"
                variant="outlined"
                rounded="lg"
                hide-details="auto"
              />
              <VTextField
                v-model.number="exerciseForm.sets"
                type="number"
                inputmode="numeric"
                label="Series"
                variant="outlined"
                rounded="lg"
                hide-details="auto"
              />
            </div>
            <div class="d-flex gap-3">
              <VSelect
                v-model="exerciseForm.load_type"
                :items="loadTypeOptions"
                item-title="title"
                item-value="value"
                label="Carga"
                variant="outlined"
                rounded="lg"
                hide-details="auto"
              />
              <VTextField
                v-if="exerciseForm.load_type !== 'bodyweight'"
                v-model.number="exerciseForm.load_value"
                type="number"
                inputmode="decimal"
                :label="exerciseForm.load_type === 'level' ? 'Nivel' : 'Kg'"
                variant="outlined"
                rounded="lg"
                hide-details="auto"
              />
            </div>
            <VTextField
              v-model="exerciseForm.notes"
              label="Nota (opcional)"
              variant="outlined"
              rounded="lg"
              hide-details="auto"
            />
          </template>
        </VCardText>
        <VCardActions>
          <VSpacer />
          <VBtn
            variant="text"
            rounded="lg"
            @click="exerciseDialog = false"
          >
            Cancelar
          </VBtn>
          <VBtn
            color="primary"
            rounded="lg"
            :loading="saving"
            @click="saveExercise"
          >
            Guardar
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <VDialog
      v-model="sessionDialog"
      max-width="480"
    >
      <VCard rounded="lg">
        <VCardTitle class="text-h6">
          {{ sessionForm.id ? 'Editar sesión' : 'Registrar sesión' }}
        </VCardTitle>
        <VCardText class="d-flex flex-column gap-4 pt-4">
          <VTextField
            v-model="sessionForm.date"
            type="date"
            label="Fecha"
            variant="outlined"
            rounded="lg"
            hide-details="auto"
          />
          <div class="d-flex flex-wrap gap-3">
            <VTextField
              v-model.number="sessionForm.duration_hours"
              type="number"
              inputmode="numeric"
              min="0"
              max="12"
              label="Horas"
              variant="outlined"
              rounded="lg"
              hide-details="auto"
              class="training-meta-num"
            />
            <VTextField
              v-model.number="sessionForm.duration_mins"
              type="number"
              inputmode="numeric"
              min="0"
              max="59"
              label="Minutos"
              variant="outlined"
              rounded="lg"
              hide-details="auto"
              class="training-meta-num"
            />
            <VTextField
              v-model.number="sessionForm.calories"
              type="number"
              inputmode="numeric"
              min="0"
              label="Calorías"
              variant="outlined"
              rounded="lg"
              hide-details="auto"
              class="training-meta-num"
            />
          </div>
          <VTextField
            v-model="sessionForm.notes"
            label="Notas"
            variant="outlined"
            rounded="lg"
            hide-details="auto"
          />
        </VCardText>
        <VCardActions>
          <VSpacer />
          <VBtn
            variant="text"
            rounded="lg"
            @click="sessionDialog = false"
          >
            Cancelar
          </VBtn>
          <VBtn
            color="primary"
            rounded="lg"
            :loading="saving"
            @click="saveSession"
          >
            Guardar
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <VDialog
      v-model="deleteDialog"
      max-width="400"
    >
      <VCard rounded="lg">
        <VCardTitle class="text-h6">
          {{ deleteDialogTitle }}
        </VCardTitle>
        <VCardText class="text-body-2 pt-2">
          <template v-if="deleteKind === 'session'">
            ¿Eliminar esta sesión? No se puede deshacer.
          </template>
          <template v-else-if="deleteKind === 'library'">
            ¿Sacar “{{ deleteTarget?.name }}” de la biblioteca? También se quita de los días donde esté.
          </template>
          <template v-else>
            ¿Eliminar “{{ deleteTarget?.name }}” de este día?
          </template>
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
  </div>
</template>

<script>
import { VueDraggable } from 'vue-draggable-plus'
import { axios } from '@/plugins/axios'
import MuscleGroupIcon from '@/views/pages/training/MuscleGroupIcon.vue'

const MUSCLE_OPTIONS = [
  'Pecho', 'Hombros', 'Tríceps', 'Espalda', 'Bíceps', 'Antebrazo',
  'Piernas', 'Abdomen', 'Cardio', 'Otro',
]

function emptyExercise() {
  return {
    id: null,
    library_exercise_id: null,
    name: '',
    muscle_group: null,
    sets: 4,
    reps: 11,
    load_type: 'level',
    load_value: null,
    notes: '',
  }
}

function todayIso() {
  const now = new Date()
  const month = String(now.getMonth() + 1).padStart(2, '0')
  const day = String(now.getDate()).padStart(2, '0')

  return `${now.getFullYear()}-${month}-${day}`
}

function emptySession() {
  return {
    id: null,
    workout_day_id: null,
    date: todayIso(),
    duration_hours: null,
    duration_mins: null,
    calories: null,
    notes: '',
    exercises: [],
  }
}

function splitDuration(totalMinutes) {
  if (totalMinutes == null || totalMinutes === '')
    return { hours: null, mins: null }

  const total = Math.max(0, Number(totalMinutes) || 0)

  return {
    hours: Math.floor(total / 60) || null,
    mins: (total % 60) || null,
  }
}

function joinDuration(hours, mins) {
  const h = Number(hours) || 0
  const m = Number(mins) || 0
  const total = (h * 60) + m

  return total > 0 ? total : null
}

function groupExercises(exercises) {
  const groups = []
  const map = {}

  ;(exercises || []).forEach(item => {
    const name = item.muscle_group || 'Sin grupo'
    if (!map[name]) {
      map[name] = { name, items: [] }
      groups.push(map[name])
    }
    map[name].items.push(item)
  })

  return groups
}

export default {
  name: 'ModuleTraining',

  components: {
    VueDraggable,
    MuscleGroupIcon,
  },

  data() {
    return {
      loading: false,
      saving: false,
      error: '',
      activeTab: 'hoy',
      days: [],
      sessions: [],
      library: [],
      libraryQuery: '',
      pickQuery: '',
      summary: { week_sessions: 0, week_minutes: 0, week_calories: 0 },
      todayWeekday: new Date().getDay() === 0 ? 7 : new Date().getDay(),
      activeDayId: null,
      selectedDayId: null,
      editPanelOpen: false,
      movingGroup: false,
      weekBoard: [],
      helpDialog: false,
      quickEditDialog: false,
      pickExerciseDialog: false,
      exerciseDialog: false,
      exerciseMode: 'day',
      sessionDialog: false,
      deleteDialog: false,
      deleteKind: null,
      deleteTarget: null,
      deleting: false,
      muscleOptions: MUSCLE_OPTIONS,
      loadTypeOptions: [
        { title: 'Nivel (máquina)', value: 'level' },
        { title: 'Kilogramos', value: 'kg' },
        { title: 'Peso corporal', value: 'bodyweight' },
      ],
      exerciseForm: emptyExercise(),
      quickEditForm: emptyExercise(),
      sessionForm: emptySession(),
    }
  },

  computed: {
    todayDay() {
      return this.days.find(day => day.weekday === this.todayWeekday) || null
    },

    activeDay() {
      return this.days.find(day => day.id === this.activeDayId) || this.todayDay
    },

    activeDaySummary() {
      if (!this.activeDay)
        return null

      if (this.activeDay.is_rest) {
        const names = (this.activeDay.exercises || []).map(item => item.name).filter(Boolean)

        return names.length ? names.join(' + ') : null
      }

      return this.focusFromGroups(this.groupsFromDay(this.activeDay))
    },

    todayDaySummary() {
      if (!this.todayDay)
        return null

      if (this.todayDay.is_rest) {
        const names = (this.todayDay.exercises || []).map(item => item.name).filter(Boolean)

        return names.length ? names.join(' + ') : null
      }

      return this.focusFromGroups(this.groupsFromDay(this.todayDay))
    },

    calendarDayLabel() {
      return this.todayDay?.label || ''
    },

    isSwappedRoutine() {
      return Boolean(this.activeDay && this.todayDay && this.activeDay.id !== this.todayDay.id)
    },

    dragLocked() {
      return this.movingGroup
        || this.editPanelOpen
        || this.pickExerciseDialog
        || this.quickEditDialog
        || this.exerciseDialog
        || this.sessionDialog
        || this.deleteDialog
        || this.helpDialog
    },

    selectedDay() {
      return this.days.find(day => day.id === this.selectedDayId) || null
    },

    selectedDaySummary() {
      if (!this.selectedDay)
        return null

      if (this.selectedDay.is_rest) {
        const names = (this.selectedDay.exercises || []).map(item => item.name).filter(Boolean)

        return names.length ? names.join(' + ') : null
      }

      return this.focusFromGroups(this.groupsFromDay(this.selectedDay))
    },

    activeGroupedExercises() {
      return groupExercises(this.activeDay?.exercises)
    },

    groupedExercises() {
      return groupExercises(this.selectedDay?.exercises)
    },

    isCardioForm() {
      return this.exerciseMode === 'day'
        && (this.exerciseForm.load_type === 'km' || Boolean(this.selectedDay?.is_rest))
    },

    exerciseDialogTitle() {
      if (this.exerciseMode === 'library')
        return this.exerciseForm.id ? 'Editar en biblioteca' : 'Nuevo en biblioteca'

      if (this.isCardioForm)
        return this.exerciseForm.id ? 'Editar actividad' : 'Nueva actividad'

      return this.exerciseForm.id ? 'Editar ejercicio' : 'Nuevo ejercicio'
    },

    deleteDialogTitle() {
      if (this.deleteKind === 'session')
        return 'Eliminar sesión'
      if (this.deleteKind === 'library')
        return 'Quitar de biblioteca'

      return 'Eliminar ejercicio'
    },

    filteredLibrary() {
      const q = String(this.libraryQuery || '').trim().toLowerCase()
      if (!q)
        return this.library

      return this.library.filter(item => {
        const hay = `${item.name} ${item.muscle_group || ''}`.toLowerCase()

        return hay.includes(q)
      })
    },

    pickFilteredLibrary() {
      const usedIds = new Set(
        (this.selectedDay?.exercises || [])
          .map(item => item.library_exercise_id)
          .filter(Boolean),
      )
      const usedNames = new Set(
        (this.selectedDay?.exercises || [])
          .map(item => String(item.name || '').trim().toLowerCase())
          .filter(Boolean),
      )

      const available = this.library.filter(item => {
        if (usedIds.has(item.id))
          return false

        const name = String(item.name || '').trim().toLowerCase()

        return !name || !usedNames.has(name)
      })

      const q = String(this.pickQuery || '').trim().toLowerCase()
      if (!q)
        return available

      return available.filter(item => {
        const hay = `${item.name} ${item.muscle_group || ''}`.toLowerCase()

        return hay.includes(q)
      })
    },

    pickEmptyMessage() {
      if (!this.library.length)
        return 'Biblioteca vacía: creá el primero.'

      const q = String(this.pickQuery || '').trim()
      if (q)
        return 'Nada coincide.'

      const dayHasExercises = Boolean(this.selectedDay?.exercises?.length)
      if (dayHasExercises && this.pickFilteredLibrary.length === 0)
        return 'Ya están todos en este día.'

      return 'Nada coincide.'
    },

    groupedLibrary() {
      return groupExercises(this.filteredLibrary)
    },
  },

  created() {
    this.load()
  },

  methods: {
    emptyExercise,
    emptySession,
    todayIso,

    load() {
      this.loading = true
      this.error = ''

      return axios.get('/api/training')
        .then(response => {
          this.days = response.data.days || []
          this.sessions = response.data.sessions || []
          this.library = response.data.library || []
          this.summary = response.data.summary || this.summary
          this.todayWeekday = response.data.today_weekday ?? this.todayWeekday

          const fallbackId = this.todayDay?.id || this.days[0]?.id || null
          if (!this.activeDayId || !this.days.some(day => day.id === this.activeDayId)) {
            this.activeDayId = fallbackId
          }
          if (!this.selectedDayId) {
            this.selectedDayId = fallbackId
          }

          this.rebuildWeekBoard()
        })
        .catch(error => {
          this.error = error.response?.data?.message || 'No se pudo cargar el entrenamiento.'
        })
        .finally(() => {
          this.loading = false
        })
    },

    rebuildWeekBoard() {
      this.weekBoard = this.days.map(day => {
        const groups = this.groupsFromDay(day)

        return {
          id: day.id,
          label: day.label,
          weekday: day.weekday,
          is_rest: day.is_rest,
          focus: day.is_rest
            ? ((day.exercises || []).map(item => item.name).filter(Boolean).join(' + ') || null)
            : this.focusFromGroups(groups),
          groups,
        }
      })
    },

    focusFromGroups(groups) {
      return (groups || [])
        .map(group => group.name)
        .filter(name => name && name !== 'Sin grupo')
        .join(' + ') || null
    },

    groupsFromDay(day) {
      const map = {}
      const groups = []

      ;(day.exercises || []).forEach(item => {
        const name = item.muscle_group || 'Sin grupo'
        const key = name

        if (!map[key]) {
          map[key] = {
            key,
            name,
            source_day_id: day.id,
            muscle_group: item.muscle_group || null,
            count: 0,
          }
          groups.push(map[key])
        }

        map[key].count += 1
      })

      return groups
    },

    onGroupMoved(event, targetColumn) {
      const group = targetColumn.groups[event.newIndex]
      if (!group || this.movingGroup)
        return

      if (group.source_day_id === targetColumn.id) {
        this.rebuildWeekBoard()

        return
      }

      this.movingGroup = true
      axios.post('/api/training/move-group', {
        source_day_id: group.source_day_id,
        target_day_id: targetColumn.id,
        muscle_group: group.muscle_group,
      })
        .then(() => this.load())
        .catch(error => {
          this.error = error.response?.data?.message || 'No se pudo mover el grupo.'
          this.rebuildWeekBoard()
        })
        .finally(() => {
          this.movingGroup = false
        })
    },

    onGroupReordered(column) {
      if (this.movingGroup || !column?.groups?.length)
        return

      // Si hay un grupo de otro día, lo maneja onGroupMoved (@add).
      if (column.groups.some(group => group.source_day_id !== column.id))
        return

      this.movingGroup = true
      axios.put(`/api/training/days/${column.id}/groups/reorder`, {
        groups: column.groups.map(group => group.muscle_group),
      })
        .then(() => this.load())
        .catch(error => {
          this.error = error.response?.data?.message || 'No se pudo guardar el orden.'
          this.rebuildWeekBoard()
        })
        .finally(() => {
          this.movingGroup = false
        })
    },

    saveDay() {
      if (!this.selectedDay)
        return

      this.saveDayById(this.selectedDay)
    },

    saveDayById(day) {
      if (!day)
        return

      axios.put(`/api/training/days/${day.id}`, {
        focus: day.focus,
        is_rest: day.is_rest,
      }).then(() => {
        this.rebuildWeekBoard()
      }).catch(error => {
        this.error = error.response?.data?.message || 'No se pudo guardar el día.'
      })
    },

    toggleEditDay(day) {
      if (!day)
        return

      this.selectedDayId = day.id
      this.editPanelOpen = true
    },

    resetToTodayRoutine() {
      this.activeDayId = this.todayDay?.id || this.activeDayId
    },

    goEditDay(day) {
      this.selectedDayId = day.id
      this.editPanelOpen = true
      this.activeTab = 'semana'
    },

    shortDay(label) {
      return String(label || '').slice(0, 3)
    },

    hasMuscleIcon(name) {
      return Boolean(name)
        && !['Sin grupo', 'Cardio', 'Otro'].includes(name)
    },

    openCardioExercise(item = null) {
      this.exerciseMode = 'day'
      this.exerciseForm = item
        ? { ...this.emptyExercise(), ...item, load_type: 'km', muscle_group: item.muscle_group || 'Cardio' }
        : {
            ...this.emptyExercise(),
            name: 'Correr',
            muscle_group: 'Cardio',
            sets: 1,
            reps: 1,
            load_type: 'km',
            load_value: 5,
          }
      this.exerciseDialog = true
    },

    openPickExercise() {
      this.pickQuery = ''
      this.pickExerciseDialog = true
    },

    createExerciseFromPick() {
      this.pickExerciseDialog = false
      this.openExercise()
    },

    attachFromLibrary(item) {
      if (!item?.id)
        return

      this.pickExerciseDialog = false
      this.exerciseMode = 'day'
      this.exerciseForm = {
        ...this.emptyExercise(),
        library_exercise_id: item.id,
        name: item.name,
        muscle_group: item.muscle_group || null,
      }
      this.exerciseDialog = true
    },

    openQuickEdit(item) {
      this.quickEditForm = { ...this.emptyExercise(), ...item }
      this.quickEditDialog = true
    },

    bumpQuick(field, delta) {
      const current = Number(this.quickEditForm[field])
      const base = Number.isFinite(current) ? current : 0
      let next = base + delta

      if (field === 'reps')
        next = Math.min(100, Math.max(1, next))
      else if (field === 'sets')
        next = Math.min(30, Math.max(1, next))
      else if (field === 'load_value')
        next = Math.max(0, Math.round(next * 100) / 100)

      this.quickEditForm[field] = next
    },

    saveQuickEdit() {
      if (!this.quickEditForm.id || this.saving)
        return

      this.saving = true
      const payload = {
        name: this.quickEditForm.name,
        muscle_group: this.quickEditForm.muscle_group,
        sets: this.quickEditForm.sets,
        reps: this.quickEditForm.reps,
        load_type: this.quickEditForm.load_type,
        load_value: this.quickEditForm.load_type === 'bodyweight' ? null : this.quickEditForm.load_value,
        notes: this.quickEditForm.notes,
      }

      axios.put(`/api/training/exercises/${this.quickEditForm.id}`, payload)
        .then(() => {
          this.quickEditDialog = false
          this.$toast.success('Actualizado', { timeout: 1500, closeOnClick: true })
          this.load()
        })
        .catch(error => {
          this.error = error.response?.data?.message || 'No se pudo guardar.'
        })
        .finally(() => {
          this.saving = false
        })
    },

    openExerciseFromDay(day, item = null) {
      if (day?.id)
        this.selectedDayId = day.id

      if (day?.is_rest || item?.load_type === 'km') {
        this.openCardioExercise(item)

        return
      }

      this.openExercise(item)
    },

    openExercise(item = null) {
      if (this.selectedDay?.is_rest && !item) {
        this.openCardioExercise()

        return
      }

      this.exerciseMode = 'day'
      this.exerciseForm = item
        ? { ...this.emptyExercise(), ...item }
        : this.emptyExercise()
      this.exerciseDialog = true
    },

    openLibraryExercise(item = null) {
      this.exerciseMode = 'library'
      this.exerciseForm = item
        ? { ...this.emptyExercise(), ...item }
        : this.emptyExercise()
      this.exerciseDialog = true
    },

    saveExercise() {
      if (this.saving)
        return

      if (this.exerciseMode === 'day' && !this.exerciseForm.id && !this.selectedDay)
        return

      const name = String(this.exerciseForm.name || '').trim()
      if (!name) {
        this.error = this.isCardioForm ? 'Indicá el nombre de la actividad.' : 'Indicá el nombre del ejercicio.'

        return
      }

      this.saving = true

      const dayPayload = this.isCardioForm
        ? {
            name,
            muscle_group: 'Cardio',
            sets: 1,
            reps: 1,
            load_type: 'km',
            load_value: this.exerciseForm.load_value,
            notes: this.exerciseForm.notes,
          }
        : {
            name,
            muscle_group: this.exerciseForm.muscle_group,
            sets: this.exerciseForm.sets,
            reps: this.exerciseForm.reps,
            load_type: this.exerciseForm.load_type,
            load_value: this.exerciseForm.load_type === 'bodyweight' ? null : this.exerciseForm.load_value,
            notes: this.exerciseForm.notes,
          }

      let request
      if (this.exerciseMode === 'library') {
        const payload = {
          name,
          muscle_group: this.exerciseForm.muscle_group,
        }
        request = this.exerciseForm.id
          ? axios.put(`/api/training/library/${this.exerciseForm.id}`, payload)
          : axios.post('/api/training/library', payload)
      }
      else if (this.exerciseForm.id) {
        request = axios.put(`/api/training/exercises/${this.exerciseForm.id}`, dayPayload)
      }
      else if (this.exerciseForm.library_exercise_id) {
        request = axios.post(`/api/training/days/${this.selectedDay.id}/exercises/attach`, {
          library_exercise_id: this.exerciseForm.library_exercise_id,
          sets: dayPayload.sets,
          reps: dayPayload.reps,
          load_type: dayPayload.load_type,
          load_value: dayPayload.load_value,
          notes: dayPayload.notes,
        })
      }
      else {
        request = axios.post(`/api/training/days/${this.selectedDay.id}/exercises`, dayPayload)
      }

      request
        .then(() => {
          this.exerciseDialog = false
          this.$toast.success('Guardado', { timeout: 2000, closeOnClick: true })
          this.load()
        })
        .catch(error => {
          this.error = error.response?.data?.message || 'No se pudo guardar.'
        })
        .finally(() => {
          this.saving = false
        })
    },

    deleteExercise(item) {
      this.deleteKind = 'exercise'
      this.deleteTarget = item
      this.deleteDialog = true
    },

    deleteLibraryExercise(item) {
      this.deleteKind = 'library'
      this.deleteTarget = item
      this.deleteDialog = true
    },

    deleteSession(session) {
      this.deleteKind = 'session'
      this.deleteTarget = session
      this.deleteDialog = true
    },

    confirmDelete() {
      if (!this.deleteTarget || this.deleting)
        return

      this.deleting = true

      let request
      if (this.deleteKind === 'session')
        request = axios.delete(`/api/training/sessions/${this.deleteTarget.id}`)
      else if (this.deleteKind === 'library')
        request = axios.delete(`/api/training/library/${this.deleteTarget.id}`)
      else
        request = axios.delete(`/api/training/exercises/${this.deleteTarget.id}`)

      request
        .then(() => {
          const kind = this.deleteKind
          this.deleteDialog = false
          this.deleteTarget = null
          this.deleteKind = null
          if (kind === 'library')
            this.$toast.success('Eliminado de biblioteca y rutina', { timeout: 2000, closeOnClick: true })
          this.load()
        })
        .catch(error => {
          this.deleteDialog = false
          const message = error.response?.data?.message || 'No se pudo eliminar.'
          this.error = message
          this.$toast.error(message, { timeout: 3500, closeOnClick: true })
        })
        .finally(() => {
          this.deleting = false
        })
    },

    saveExerciseOrder() {
      if (!this.selectedDay?.exercises?.length)
        return

      const exerciseIds = this.selectedDay.exercises.map(item => item.id)

      axios.put(`/api/training/days/${this.selectedDay.id}/exercises/reorder`, {
        exercise_ids: exerciseIds,
      }).catch(error => {
        this.error = error.response?.data?.message || 'No se pudo guardar el orden.'
        this.load()
      })
    },

    openSessionFromDay(day = null) {
      const target = day || this.todayDay || this.selectedDay || this.days[0]
      if (!target) {
        this.sessionForm = this.emptySession()
        this.sessionDialog = true

        return
      }

      this.selectedDayId = target.id
      this.sessionForm = {
        ...this.emptySession(),
        workout_day_id: target.id,
        exercises: (target.exercises || []).map(item => ({
          name: item.name,
          muscle_group: item.muscle_group,
          sets: item.sets,
          reps: item.reps,
          load_type: item.load_type,
          load_value: item.load_value,
          notes: item.notes,
        })),
      }
      this.sessionDialog = true
    },

    openSession(session) {
      const duration = splitDuration(session.duration_minutes)
      this.sessionForm = {
        id: session.id,
        workout_day_id: session.workout_day_id,
        date: session.date,
        duration_hours: duration.hours,
        duration_mins: duration.mins,
        calories: session.calories,
        notes: session.notes || '',
        exercises: (session.exercises || []).map(item => ({ ...item })),
      }
      this.sessionDialog = true
    },

    saveSession() {
      if (this.saving)
        return

      this.saving = true
      const payload = {
        date: this.sessionForm.date,
        workout_day_id: this.sessionForm.workout_day_id,
        duration_minutes: joinDuration(this.sessionForm.duration_hours, this.sessionForm.duration_mins),
        calories: this.sessionForm.calories || null,
        notes: this.sessionForm.notes,
        exercises: this.sessionForm.exercises,
      }

      const request = this.sessionForm.id
        ? axios.put(`/api/training/sessions/${this.sessionForm.id}`, payload)
        : axios.post('/api/training/sessions', payload)

      request
        .then(() => {
          this.sessionDialog = false
          this.activeTab = 'historial'
          this.$toast.success('Sesión guardada', { timeout: 2000, closeOnClick: true })
          this.load()
        })
        .catch(error => {
          this.error = error.response?.data?.message || 'No se pudo guardar la sesión.'
        })
        .finally(() => {
          this.saving = false
        })
    },

    formatDuration(totalMinutes) {
      if (!totalMinutes)
        return ''

      const hours = Math.floor(totalMinutes / 60)
      const mins = totalMinutes % 60

      if (hours && mins)
        return `${hours} h ${mins} min`
      if (hours)
        return `${hours} h`

      return `${mins} min`
    },

    formatLoad(item) {
      if (item.load_type === 'km')
        return item.load_value != null ? `${item.load_value} km` : 'Sin km'
      if (item.load_type === 'bodyweight')
        return 'Peso corporal'
      if (item.load_type === 'level')
        return item.load_value != null ? `Niv ${item.load_value}` : 'Sin nivel'
      if (item.load_value == null)
        return 'Sin peso'

      return `${item.load_value} kg`
    },

    formatSessionDate(value) {
      if (!value)
        return ''

      const date = new Date(`${value}T00:00:00`)

      return date.toLocaleDateString('es-CR', {
        weekday: 'short',
        day: '2-digit',
        month: 'short',
      })
    },

    muscleSummary(session) {
      const groups = [...new Set((session.exercises || []).map(item => item.muscle_group).filter(Boolean))]

      return groups.length ? groups.join(' + ') : `${(session.exercises || []).length} ejercicios`
    },
  },
}
</script>

<style scoped>
.training-tabs :deep(.v-slide-group) {
  overflow: visible;
}

.training-tabs :deep(.v-slide-group__content) {
  width: 100%;
  gap: 0;
  transform: none !important;
}

.training-tabs :deep(.v-tabs) {
  height: auto;
}

.training-tabs :deep(.v-tab) {
  min-width: 0;
  flex: 1 1 0;
  height: auto !important;
  min-height: 64px;
  padding-top: 0.35rem;
  padding-bottom: 0.55rem;
  padding-inline: 0.15rem;
  font-size: 0.75rem;
  letter-spacing: 0;
  overflow: visible;
}

.training-tabs :deep(.v-btn__content) {
  white-space: nowrap;
}

.training-hoy__eyebrow {
  font-size: 0.8125rem;
  font-weight: 600;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  color: rgb(var(--v-theme-primary));
}

.training-hoy__focus {
  font-size: clamp(1.5rem, 5vw, 2rem);
  font-weight: 700;
  line-height: 1.2;
}

.training-hoy__swap-note {
  font-size: 0.875rem;
  color: rgba(var(--v-theme-on-surface), 0.65);
}

.training-hoy__pick-label {
  font-size: 0.75rem;
  font-weight: 650;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  color: rgba(var(--v-theme-on-surface), 0.5);
}

.training-day-chips {
  display: flex;
  gap: 0.45rem;
  overflow-x: auto;
  padding-bottom: 0.15rem;
}

.training-day-chip {
  flex: 0 0 auto;
  border: thin solid rgba(var(--v-border-color), var(--v-border-opacity));
  background: rgb(var(--v-theme-surface));
  color: inherit;
  border-radius: 999px;
  padding: 0.4rem 0.8rem;
  font-size: 0.8125rem;
  font-weight: 600;
  cursor: pointer;
}

.training-day-chip--today {
  border-color: rgb(var(--v-theme-primary));
}

.training-day-chip--active {
  background: color-mix(in srgb, rgb(var(--v-theme-primary)) 16%, rgb(var(--v-theme-surface)));
  border-color: rgb(var(--v-theme-primary));
}

.training-hoy__rest,
.training-hoy__empty {
  padding: 1.5rem 0.25rem 2rem;
  color: rgba(var(--v-theme-on-surface), 0.7);
}

.training-hoy__group {
  margin-bottom: 1.5rem;
}

.training-hoy__group-title {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: rgba(var(--v-theme-on-surface), 0.5);
  margin: 0 0 0.65rem;
}

.training-hoy__ex {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  padding: 1rem 0;
  border-bottom: thin solid rgba(var(--v-border-color), var(--v-border-opacity));
}

.training-hoy__ex:last-child {
  border-bottom: 0;
}

.training-quick {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.training-quick__row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
}

.training-quick__label {
  font-weight: 600;
  min-width: 4rem;
}

.training-quick__ctrl {
  display: flex;
  align-items: center;
  gap: 0.65rem;
}

.training-quick__value {
  min-width: 2.5rem;
  text-align: center;
  font-size: 1.35rem;
  font-weight: 700;
  font-variant-numeric: tabular-nums;
}

.training-hoy__ex-name {
  font-size: 1.125rem;
  font-weight: 650;
  line-height: 1.3;
}

.training-hoy__ex-rx {
  font-size: 1.35rem;
  font-weight: 700;
  font-variant-numeric: tabular-nums;
  letter-spacing: -0.02em;
}

.training-hoy__ex-dot {
  margin: 0 0.15rem;
  opacity: 0.45;
  font-weight: 500;
}

.training-hoy__ex-note {
  margin-top: 0.25rem;
  font-size: 0.8125rem;
  color: rgba(var(--v-theme-on-surface), 0.55);
}

.training-hoy__actions {
  position: sticky;
  bottom: 0.75rem;
  margin-top: 1.5rem;
  padding-top: 0.5rem;
  background: linear-gradient(to top, rgb(var(--v-theme-background)) 70%, transparent);
}

.training-hoy__swap-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.35rem;
  align-items: center;
}

.training-board {
  display: grid;
  gap: 0.75rem;
}

.training-board--busy {
  opacity: 0.7;
  pointer-events: none;
}

.training-board__day {
  border: thin solid rgba(var(--v-border-color), var(--v-border-opacity));
  border-radius: 14px;
  background: rgb(var(--v-theme-surface));
  padding: 0.75rem;
  min-height: 5.5rem;
}

.training-board__day--today {
  border-color: color-mix(in srgb, rgb(var(--v-theme-primary)) 55%, rgba(var(--v-border-color), var(--v-border-opacity)));
}

.training-board__day--selected {
  border-color: rgb(var(--v-theme-primary));
  box-shadow: 0 0 0 1px rgb(var(--v-theme-primary));
  background: color-mix(in srgb, rgb(var(--v-theme-primary)) 8%, rgb(var(--v-theme-surface)));
}

.training-board__day--rest {
  opacity: 0.72;
}

.training-board__head {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
  width: 100%;
  border: 0;
  background: transparent;
  color: inherit;
  text-align: left;
  padding: 0 0 0.55rem;
  margin: 0 0 0.35rem;
  cursor: pointer;
  border-bottom: thin solid rgba(var(--v-border-color), var(--v-border-opacity));
}

.training-board__label {
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 0.4rem;
}

.training-board__hoy {
  font-size: 0.65rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  color: rgb(var(--v-theme-primary));
}

.training-board__focus {
  font-size: 0.8125rem;
  color: rgba(var(--v-theme-on-surface), 0.65);
}

.training-board__drop {
  min-height: 2.5rem;
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.training-board__group {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  padding: 0.55rem 0.6rem;
  border-radius: 10px;
  background: color-mix(in srgb, rgb(var(--v-theme-primary)) 10%, rgb(var(--v-theme-surface)));
  border: thin solid color-mix(in srgb, rgb(var(--v-theme-primary)) 22%, rgba(var(--v-border-color), var(--v-border-opacity)));
}

.training-board__handle {
  display: inline-flex;
  border: 0;
  background: transparent;
  color: rgba(var(--v-theme-on-surface), 0.45);
  cursor: grab;
  padding: 0.15rem;
  touch-action: none;
}

.training-board__handle:active {
  cursor: grabbing;
}

.training-board__group-name {
  font-weight: 650;
  font-size: 0.9rem;
}

.training-board__group-meta {
  font-size: 0.75rem;
  color: rgba(var(--v-theme-on-surface), 0.55);
}

.training-board__empty {
  margin: 0.35rem 0 0;
  font-size: 0.75rem;
  color: rgba(var(--v-theme-on-surface), 0.45);
}

@media (min-width: 900px) {
  .training-board {
    grid-template-columns: repeat(7, minmax(0, 1fr));
    align-items: start;
  }
}

@media (min-width: 600px) and (max-width: 899px) {
  .training-board {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

.training-empty {
  text-align: center;
  padding: 2.5rem 1rem;
  color: rgba(var(--v-theme-on-surface), 0.6);
  font-size: 0.875rem;
}

.training-group__title {
  font-size: 0.7rem;
  font-weight: 700;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: rgba(var(--v-theme-on-surface), 0.55);
  margin: 0 0 0.5rem;
}

.training-exercise {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
  padding: 0.75rem 0.75rem;
  border: thin solid rgba(var(--v-border-color), var(--v-border-opacity));
  border-radius: 12px;
  margin-bottom: 0.5rem;
  background: rgb(var(--v-theme-surface));
}

.training-exercise--pick {
  width: 100%;
  cursor: pointer;
  text-align: left;
  font: inherit;
  color: inherit;
}

.training-exercise--pick:hover {
  border-color: rgba(var(--v-theme-primary), 0.45);
}

.training-exercise--pick:disabled {
  opacity: 0.55;
  cursor: wait;
}

.training-drag-handle {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: 0;
  background: transparent;
  color: rgba(var(--v-theme-on-surface), 0.45);
  cursor: grab;
  padding: 0.25rem;
  touch-action: none;
}

.training-drag-handle:active {
  cursor: grabbing;
}

.training-drag-list .sortable-ghost {
  opacity: 0.45;
}

.training-history__list {
  max-height: min(420px, 55vh);
  overflow-y: auto;
}

.training-history__item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  padding: 0.9rem 1rem;
  border-top: thin solid rgba(var(--v-border-color), var(--v-border-opacity));
}

.training-history__item:first-child {
  border-top: 0;
}

.training-meta-num {
  flex: 1 1 5.5rem;
  min-width: 5rem;
  max-width: 9rem;
}
</style>
