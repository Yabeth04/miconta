<script setup>
import { useAuthStore } from '@/stores/auth'
import VerticalNavGroup from '@layouts/components/VerticalNavGroup.vue'
import VerticalNavLink from '@layouts/components/VerticalNavLink.vue'
import { storeToRefs } from 'pinia'

const auth = useAuthStore()
const { isSysAdmin } = storeToRefs(auth)
</script>

<template>
  <VerticalNavLink
    :item="{
      title: 'Inicio',
      icon: 'ri-home-4-line',
      to: '/dashboard',
    }"
  />

  <VerticalNavGroup
    :item="{
      title: 'Contabilidad',
      icon: 'ri-calculator-line',
      routePrefix: '/contabilidad',
    }"
  >
    <VerticalNavLink
      :item="{
        title: 'Movimientos',
        to: '/contabilidad',
      }"
    />
    <VerticalNavLink
      :item="{
        title: 'Conceptos',
        to: '/contabilidad/conceptos',
      }"
    />
  </VerticalNavGroup>

  <VerticalNavLink
    v-if="isSysAdmin"
    :item="{
      title: 'Plan de estudios',
      icon: 'ri-book-open-line',
      to: '/plan-estudios',
    }"
  />
</template>
