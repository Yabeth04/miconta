<script setup>
import { useAuthStore } from '@/stores/auth'
import { storeToRefs } from 'pinia'
import { useRouter } from 'vue-router'

const router = useRouter()
const auth = useAuthStore()
const { isSysAdmin } = storeToRefs(auth)

const open = ref(false)
const query = ref('')
const activeIndex = ref(0)
const searchInput = ref(null)

const allGroups = computed(() => {
  const groups = [
    {
      id: 'inicio',
      title: 'Inicio',
      icon: 'ri-home-4-line',
      items: [
        { title: 'Inicio', icon: 'ri-home-4-line', to: '/dashboard' },
      ],
    },
    {
      id: 'contabilidad',
      title: 'Contabilidad',
      icon: 'ri-calculator-line',
      items: [
        { title: 'Movimientos', icon: 'ri-list-check-2', to: '/contabilidad' },
        { title: 'Conceptos', icon: 'ri-price-tag-3-line', to: '/contabilidad/conceptos' },
        { title: 'Pagos fijos', icon: 'ri-calendar-check-line', to: '/pagos-fijos' },
        { title: 'Proyección', icon: 'ri-line-chart-line', to: '/proyeccion' },
        { title: 'Cierres', icon: 'ri-lock-2-line', to: '/cierres' },
      ],
    },
    {
      id: 'entrenamiento',
      title: 'Entrenamiento',
      icon: 'ri-heart-pulse-line',
      items: [
        { title: 'Entrenamiento', icon: 'ri-heart-pulse-line', to: '/entrenamiento' },
      ],
    },
  ]

  if (isSysAdmin.value) {
    groups.push(
      {
        id: 'usuarios',
        title: 'Usuarios',
        icon: 'ri-group-line',
        items: [
          { title: 'Usuarios', icon: 'ri-group-line', to: '/usuarios' },
        ],
      },
      {
        id: 'plan',
        title: 'Plan de estudios',
        icon: 'ri-book-open-line',
        items: [
          { title: 'Plan de estudios', icon: 'ri-book-open-line', to: '/plan-estudios' },
        ],
      },
    )
  }

  return groups
})

const filteredGroups = computed(() => {
  const q = String(query.value || '').trim().toLowerCase()

  return allGroups.value
    .map(group => {
      const hasMany = group.items.length > 1

      if (!q) {
        return {
          ...group,
          isSection: hasMany,
        }
      }

      const groupMatch = group.title.toLowerCase().includes(q)
      const matchedItems = group.items.filter(item =>
        `${item.title} ${group.title}`.toLowerCase().includes(q),
      )

      if (!groupMatch && !matchedItems.length)
        return null

      const items = groupMatch && matchedItems.length === 0
        ? group.items
        : (matchedItems.length ? matchedItems : group.items)

      return {
        ...group,
        items,
        isSection: items.length > 1 || (hasMany && groupMatch),
      }
    })
    .filter(Boolean)
})

const flatItems = computed(() =>
  filteredGroups.value.flatMap(group => group.items),
)

watch(flatItems, list => {
  activeIndex.value = list.length ? 0 : -1
})

watch(open, value => {
  if (!value) {
    query.value = ''
    activeIndex.value = 0

    return
  }

  nextTick(() => {
    searchInput.value?.focus?.()
  })
})

const openSearch = () => {
  open.value = true
}

const goTo = item => {
  if (!item?.to)
    return

  open.value = false
  router.push(item.to)
}

const itemGlobalIndex = (group, itemIndex) => {
  let index = 0
  for (const current of filteredGroups.value) {
    if (current.id === group.id)
      return index + itemIndex
    index += current.items.length
  }

  return itemIndex
}

const onKeydown = event => {
  if (!open.value)
    return

  const list = flatItems.value
  if (event.key === 'ArrowDown') {
    event.preventDefault()
    if (!list.length)
      return
    activeIndex.value = (activeIndex.value + 1) % list.length
  }
  else if (event.key === 'ArrowUp') {
    event.preventDefault()
    if (!list.length)
      return
    activeIndex.value = (activeIndex.value - 1 + list.length) % list.length
  }
  else if (event.key === 'Enter') {
    event.preventDefault()
    const item = list[activeIndex.value]
    if (item)
      goTo(item)
  }
}

const onGlobalKeydown = event => {
  const isModK = (event.metaKey || event.ctrlKey) && String(event.key || '').toLowerCase() === 'k'
  if (!isModK)
    return

  event.preventDefault()
  open.value = !open.value
}

onMounted(() => {
  window.addEventListener('keydown', onGlobalKeydown)
})

onBeforeUnmount(() => {
  window.removeEventListener('keydown', onGlobalKeydown)
})

defineExpose({ openSearch })
</script>

<template>
  <div
    class="d-flex align-center cursor-pointer"
    style="user-select: none;"
    role="button"
    tabindex="0"
    aria-label="Ir a módulo"
    @click="openSearch"
    @keydown.enter.prevent="openSearch"
  >
    <IconBtn>
      <VIcon icon="ri-search-line" />
    </IconBtn>

    <span class="d-none d-md-flex align-center text-disabled">
      <span class="me-3">Ir a…</span>
      <span class="meta-key">Ctrl K</span>
    </span>
  </div>

  <VDialog
    v-model="open"
    max-width="460"
    scrollable
  >
    <VCard
      rounded="lg"
      @keydown="onKeydown"
    >
      <VCardText class="pb-2 pt-4">
        <VTextField
          ref="searchInput"
          v-model="query"
          label="Ir a…"
          placeholder="Movimientos, Conceptos, Entrenamiento…"
          prepend-inner-icon="ri-search-line"
          variant="outlined"
          rounded="lg"
          hide-details
          autofocus
          clearable
        />
      </VCardText>

      <div class="nav-search-list pb-2">
        <template
          v-for="group in filteredGroups"
          :key="group.id"
        >
          <template v-if="group.isSection">
            <p class="nav-search-section">
              <VIcon
                :icon="group.icon"
                size="16"
                class="me-1"
              />
              {{ group.title }}
            </p>
            <button
              v-for="(item, itemIndex) in group.items"
              :key="item.to"
              type="button"
              class="nav-search-item nav-search-item--child"
              :class="{ 'nav-search-item--active': itemGlobalIndex(group, itemIndex) === activeIndex }"
              @click="goTo(item)"
              @mouseenter="activeIndex = itemGlobalIndex(group, itemIndex)"
            >
              <VIcon
                :icon="item.icon"
                size="18"
                class="nav-search-item__icon"
              />
              <span class="nav-search-item__title">{{ item.title }}</span>
            </button>
          </template>

          <template v-else>
            <button
              v-for="(item, itemIndex) in group.items"
              :key="item.to"
              type="button"
              class="nav-search-item"
              :class="{ 'nav-search-item--active': itemGlobalIndex(group, itemIndex) === activeIndex }"
              @click="goTo(item)"
              @mouseenter="activeIndex = itemGlobalIndex(group, itemIndex)"
            >
              <VIcon
                :icon="item.icon"
                size="18"
                class="nav-search-item__icon"
              />
              <span class="nav-search-item__title">{{ item.title }}</span>
            </button>
          </template>
        </template>

        <p
          v-if="!filteredGroups.length"
          class="nav-search-empty"
        >
          Nada coincide. Probá con otro nombre.
        </p>
      </div>
    </VCard>
  </VDialog>
</template>

<style scoped>
.meta-key {
  border: thin solid rgba(var(--v-border-color), var(--v-border-opacity));
  border-radius: 6px;
  block-size: 1.5625rem;
  line-height: 1.3125rem;
  padding-block: 0.125rem;
  padding-inline: 0.25rem;
}

.nav-search-list {
  max-block-size: min(52vh, 420px);
  overflow-x: hidden;
  overflow-y: auto;
  padding-inline: 0.5rem;
}

.nav-search-section {
  display: flex;
  align-items: center;
  margin: 0.65rem 0.5rem 0.35rem;
  font-size: 0.6875rem;
  font-weight: 700;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: rgba(var(--v-theme-on-surface), 0.5);
}

.nav-search-item {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  width: 100%;
  max-width: 100%;
  box-sizing: border-box;
  border: 0;
  border-radius: 10px;
  background: transparent;
  color: inherit;
  font: inherit;
  text-align: left;
  cursor: pointer;
  padding: 0.65rem 0.75rem;
}

.nav-search-item--child {
  width: calc(100% - 0.5rem);
  max-width: calc(100% - 0.5rem);
  margin-inline-start: 0.5rem;
  padding-inline-start: 1.1rem;
  border-inline-start: 2px solid rgba(var(--v-theme-primary), 0.35);
  border-radius: 0 10px 10px 0;
}

.nav-search-item--active {
  background: color-mix(in srgb, rgb(var(--v-theme-primary)) 14%, transparent);
}

.nav-search-item__icon {
  color: rgba(var(--v-theme-on-surface), 0.65);
  flex-shrink: 0;
}

.nav-search-item__title {
  flex: 1 1 auto;
  min-width: 0;
  font-weight: 600;
}

.nav-search-empty {
  margin: 0;
  padding: 1.25rem 0.75rem;
  text-align: center;
  color: rgba(var(--v-theme-on-surface), 0.55);
  font-size: 0.875rem;
}
</style>
