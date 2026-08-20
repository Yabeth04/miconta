<template>
  <VMenu
    location="bottom end"
    :close-on-content-click="true"
  >
    <template #activator="{ props: menuProps }">
      <VBtn
        icon
        variant="text"
        size="small"
        class="accounting-movement-menu__btn"
        aria-label="Acciones"
        v-bind="menuProps"
        @click.stop
      >
        <VIcon
          icon="ri-more-2-fill"
          size="18"
        />
      </VBtn>
    </template>

    <VList
      density="compact"
      min-width="160"
    >
      <VListItem
        prepend-icon="ri-pencil-line"
        title="Editar"
        @click="$emit('edit')"
      />
      <VListItem
        prepend-icon="ri-delete-bin-line"
        title="Eliminar"
        base-color="error"
        @click="$emit('delete')"
      />
    </VList>
  </VMenu>
</template>

<script>
export default {
  name: 'AccountingMovementMenu',
  emits: ['edit', 'delete'],
}
</script>

<style scoped>
.accounting-movement-menu__btn {
  color: rgba(var(--v-theme-on-surface), 0.55);
}
</style>
