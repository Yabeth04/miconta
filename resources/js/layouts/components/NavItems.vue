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
      title: 'Configuración',
      icon: 'ri-user-settings-line',
      to: '/account-settings',
    }"
  />
</template>
