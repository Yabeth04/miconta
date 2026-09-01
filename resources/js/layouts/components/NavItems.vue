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
        icon: 'ri-list-check-2',
        to: '/contabilidad',
        child: true,
      }"
    />
    <VerticalNavLink
      :item="{
        title: 'Conceptos',
        icon: 'ri-price-tag-3-line',
        to: '/contabilidad/conceptos',
        child: true,
      }"
    />
    <VerticalNavLink
      :item="{
        title: 'Pagos fijos',
        icon: 'ri-calendar-check-line',
        to: '/pagos-fijos',
        child: true,
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
