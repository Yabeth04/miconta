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
    class="nav-group"
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
        icon="ri-arrow-right-s-line"
        class="nav-group-arrow"
      />
    </div>

    <Transition name="transition-slide-x">
      <ul v-show="isOpen">
        <slot />
      </ul>
    </Transition>
  </li>
</template>

<style lang="scss" scoped>
.nav-group-label {
  display: flex;
  align-items: center;
  cursor: pointer;
}
</style>
