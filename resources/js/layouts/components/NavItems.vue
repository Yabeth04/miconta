<script setup>
import VerticalNavLink from '@layouts/components/VerticalNavLink.vue';
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
      title: 'Plan de estudios',
      icon: 'ri-book-open-line',
      to: '/plan-estudios',
    }"
  />
</template>
