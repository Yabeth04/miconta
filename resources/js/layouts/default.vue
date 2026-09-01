<script setup>
import DefaultLayoutWithVerticalNav from './components/DefaultLayoutWithVerticalNav.vue'

const cachedPages = [
  'Dashboard',
  'ModuleAccounting',
  'ModuleAccountingConcepts',
  'ModuleFixedPayments',
  'ModuleStudyPlan',
]
</script>

<template>
  <DefaultLayoutWithVerticalNav>
    <RouterView v-slot="{ Component }">
      <KeepAlive :include="cachedPages">
        <component :is="Component" />
      </KeepAlive>
    </RouterView>
  </DefaultLayoutWithVerticalNav>
</template>

<style lang="scss">
// As we are using `layouts` plugin we need its styles to be imported
@use "@layouts/styles/default-layout";
</style>
