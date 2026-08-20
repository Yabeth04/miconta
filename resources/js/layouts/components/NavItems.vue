<script setup>
import VerticalNavLink from '@layouts/components/VerticalNavLink.vue'
import { storeToRefs } from 'pinia'
import { useAuthStore } from '@/stores/auth'

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
  <VerticalNavLink
    :item="{
      title: 'Contabilidad',
      icon: 'ri-calculator-line',
      to: '/contabilidad',
    }"
  />
  <VerticalNavLink
    :item="{
      title: 'Conceptos',
      icon: 'ri-price-tag-3-line',
      to: '/contabilidad/conceptos',
    }"
  />
  <VerticalNavLink
    v-if="isSysAdmin"
    :item="{
      title: 'Plan de estudios',
      icon: 'ri-book-open-line',
      to: '/plan-estudios',
    }"
  />
</template>
