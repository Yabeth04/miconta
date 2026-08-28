<script setup>
const props = defineProps({
  item: {
    type: Object,
    required: true,
  },
})

const route = useRoute()
const isOpen = ref(false)

const routePrefix = computed(() => {
  if (props.item.routePrefix)
    return props.item.routePrefix

  if (typeof props.item.to === 'string')
    return props.item.to

  return null
})

const isActive = computed(() => {
  const prefix = routePrefix.value

  if (!prefix)
    return false

  return route.path === prefix || route.path.startsWith(`${prefix}/`)
})

watch(isActive, value => {
  if (value)
    isOpen.value = true
}, { immediate: true })

function toggle() {
  isOpen.value = !isOpen.value
}
</script>

<template>
  <li
    class="nav-group nav-group--tail"
    :class="{
      open: isOpen,
      active: isActive,
    }"
  >
    <div
      class="nav-group-label"
      @click="toggle"
    >
      <VIcon
        :icon="item.icon || 'ri-checkbox-blank-circle-line'"
        class="nav-item-icon"
      />
      <span class="nav-item-title">
        {{ item.title }}
      </span>
      <VIcon
        icon="ri-arrow-down-s-line"
        class="nav-group-arrow"
      />
    </div>

    <VExpandTransition>
      <ul
        v-show="isOpen"
        class="nav-group-children"
      >
        <slot />
      </ul>
    </VExpandTransition>
  </li>
</template>

<style lang="scss">
.layout-vertical-nav {
  .nav-group--tail {
    margin-block-end: 0.125rem;

    > .nav-group-label {
      display: flex;
      align-items: center;
      cursor: pointer;
      border-radius: 0.5rem;
      transition: background-color 0.2s ease, color 0.2s ease;

      &::before {
        display: none !important;
      }
    }

    &.open > .nav-group-label {
      background: rgba(var(--v-theme-primary), 0.1) !important;
      box-shadow: none !important;

      .nav-item-title {
        color: rgb(var(--v-theme-primary));
        font-weight: 500;
      }

      .nav-item-icon,
      .nav-group-arrow {
        color: rgb(var(--v-theme-primary));
      }
    }

    &:not(.open) > .nav-group-label {
      .nav-item-title {
        color: rgba(var(--v-theme-on-surface), var(--v-high-emphasis-opacity));
      }
    }

    .nav-group-arrow {
      font-size: 1.125rem;
      margin-inline-start: auto;
      opacity: 0.85;
      transform-origin: center;
      transition: transform 0.2s ease;
    }

    &.open > .nav-group-label .nav-group-arrow {
      transform: rotate(180deg);
    }

    .nav-group-children {
      margin: 0.25rem 0 0;
      padding: 0;
      list-style: none;
      overflow: hidden;
    }
  }
}
</style>
