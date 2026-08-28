<script setup>
const props = defineProps({
  item: {
    type: null,
    required: true,
  },
})
</script>

<template>
  <li
    class="nav-link"
    :class="{
      disabled: item.disable,
      'nav-link--child': item.child,
    }"
  >
    <Component
      :is="item.to ? 'RouterLink' : 'a'"
      :to="item.to"
      :href="item.href"
      :target="item.target"
    >
      <VIcon
        v-if="item.icon"
        :icon="item.icon"
        class="nav-item-icon"
      />
      <span class="nav-item-title">
        {{ item.title }}
      </span>
      <span
        v-if="item.badgeContent"
        class="nav-item-badge"
        :class="item.badgeClass"
      >
        {{ item.badgeContent }}
      </span>
    </Component>
  </li>
</template>

<style lang="scss">
@use "@configured-variables" as variables;

.layout-vertical-nav {
  .nav-link a {
    display: flex;
    align-items: center;
    cursor: pointer;
  }

  .nav-group--tail .nav-link--child {
    > a {
      display: flex;
      align-items: center;
      width: auto;
      block-size: auto;
      min-block-size: 2.5rem;
      margin-block-end: 0.125rem !important;
      // Derecha alineada con el ítem padre; izquierda inset (estilo TailAdmin)
      margin-inline-start: calc(
        #{variables.$vertical-nav-horizontal-padding-start}
        + #{variables.$vertical-nav-items-icon-size}
        + 0.5rem
      ) !important;
      margin-inline-end: 1rem !important;
      padding-block: 0.5rem;
      padding-inline: 0.75rem 0 !important;
      border-radius: 0.5rem;
      transition: background-color 0.15s ease, color 0.15s ease;

      &::before {
        display: none !important;
      }
    }

    .nav-item-title {
      margin-inline-start: 0;
    }

    &:last-child > a {
      margin-block-end: 0;
    }

    .nav-item-icon {
      font-size: 1.125rem;
      margin-inline-end: 0.5rem;
    }

    > .router-link-exact-active {
      background: rgba(var(--v-theme-primary), 0.1) !important;
      box-shadow: none !important;

      .nav-item-icon,
      i {
        color: rgb(var(--v-theme-primary)) !important;
      }

      .nav-item-title {
        color: rgb(var(--v-theme-primary)) !important;
        font-weight: 500;
      }
    }

    > a:not(.router-link-exact-active) {
      .nav-item-icon,
      i,
      .nav-item-title {
        color: rgba(var(--v-theme-on-surface), var(--v-high-emphasis-opacity)) !important;
      }

      .nav-item-title {
        font-weight: 400;
      }

      &:hover {
        background: rgba(var(--v-theme-on-surface), 0.06);
      }
    }
  }
}
</style>
