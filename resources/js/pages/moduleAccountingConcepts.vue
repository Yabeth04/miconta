<template>
  <div>
    <div
      v-if="mdAndUp"
      class="mb-4"
    >
      <h1 class="text-h4 font-weight-medium mb-1">
        Conceptos
      </h1>
      <p class="text-body-2 text-medium-emphasis mb-0">
        Lista fija para seleccionar al registrar movimientos
      </p>
    </div>

    <VCard
      variant="outlined"
      rounded="lg"
    >
      <div class="pa-4">
        <form
          class="d-flex flex-wrap align-start gap-2"
          @submit.prevent="createItem"
        >
          <VTextField
            v-model="newName"
            class="flex-grow-1"
            label="Nuevo concepto"
            variant="outlined"
            rounded="lg"
            hide-details="auto"
            maxlength="100"
            :disabled="saving"
            @keydown.enter.prevent="createItem"
          />
          <VBtn
            color="primary"
            rounded="lg"
            type="submit"
            :loading="saving"
            prepend-icon="ri-add-line"
          >
            Agregar
          </VBtn>
        </form>

        <VAlert
          v-if="error"
          type="error"
          variant="tonal"
          rounded="lg"
          class="mt-4 mb-0"
        >
          {{ error }}
        </VAlert>
      </div>

      <VDivider />

      <div
        v-if="loading"
        class="text-center py-10 text-medium-emphasis"
      >
        Cargando…
      </div>

      <div
        v-else-if="!items.length"
        class="text-center py-10 text-medium-emphasis"
      >
        Todavía no hay conceptos. Agregá el primero arriba.
      </div>

      <VList
        v-else
        lines="one"
      >
        <VListItem
          v-for="item in items"
          :key="item.id"
        >
          <VListItemTitle class="font-weight-medium">
            {{ item.name }}
          </VListItemTitle>

          <template #append>
            <VBtn
              icon
              variant="text"
              size="small"
              aria-label="Eliminar"
              :loading="deletingId === item.id"
              @click="confirmDelete(item)"
            >
              <VIcon icon="ri-delete-bin-line" />
            </VBtn>
          </template>
        </VListItem>
      </VList>
    </VCard>

    <VDialog
      v-model="deleteDialog"
      max-width="400"
    >
      <VCard rounded="lg">
        <VCardTitle class="text-h6">
          Eliminar concepto
        </VCardTitle>
        <VCardText class="text-body-2">
          ¿Eliminar “{{ deleteTarget?.name }}”? Los movimientos que lo usaban
          quedan con el texto, sin la relación.
        </VCardText>
        <VCardActions class="px-4 pb-4">
          <VSpacer />
          <VBtn
            variant="text"
            rounded="lg"
            @click="deleteDialog = false"
          >
            Cancelar
          </VBtn>
          <VBtn
            color="error"
            variant="flat"
            rounded="lg"
            :loading="deletingId !== null"
            @click="destroyItem"
          >
            Eliminar
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </div>
</template>

<script>
import { axios } from '@/plugins/axios'
import { useDisplay } from 'vuetify'

export default {
  name: 'ModuleAccountingConcepts',

  setup() {
    const { mdAndUp } = useDisplay()

    return { mdAndUp }
  },

  data() {
    return {
      items: [],
      loading: true,
      saving: false,
      newName: '',
      error: '',
      deleteDialog: false,
      deleteTarget: null,
      deletingId: null,
    }
  },

  mounted() {
    this.loadItems()
  },

  methods: {
    loadItems() {
      this.loading = true
      this.error = ''

      axios
        .get('/api/accounting/concepts')
        .then(response => {
          this.items = response.data.data || []
        })
        .catch(() => {
          this.error = 'No se pudieron cargar los conceptos.'
        })
        .finally(() => {
          this.loading = false
        })
    },

    createItem() {
      const name = String(this.newName || '').trim()

      if (!name || this.saving)
        return

      this.saving = true
      this.error = ''

      axios
        .post('/api/accounting/concepts', { name })
        .then(response => {
          this.items = [...this.items, response.data].sort((a, b) =>
            a.name.localeCompare(b.name, 'es', { sensitivity: 'base' }),
          )
          this.newName = ''
          this.$toast.success('Concepto agregado', { timeout: 2000, closeOnClick: true })
        })
        .catch(error => {
          const msg = error.response?.data?.errors?.name?.[0]
            || error.response?.data?.message
            || 'No se pudo guardar.'

          this.error = msg
        })
        .finally(() => {
          this.saving = false
        })
    },

    confirmDelete(item) {
      this.deleteTarget = item
      this.deleteDialog = true
    },

    destroyItem() {
      if (!this.deleteTarget?.id || this.deletingId !== null)
        return

      this.deletingId = this.deleteTarget.id

      axios
        .delete(`/api/accounting/concepts/${this.deleteTarget.id}`)
        .then(() => {
          this.items = this.items.filter(item => item.id !== this.deleteTarget.id)
          this.deleteDialog = false
          this.deleteTarget = null
          this.$toast.success('Concepto eliminado', { timeout: 2000, closeOnClick: true })
        })
        .catch(() => {
          this.error = 'No se pudo eliminar.'
        })
        .finally(() => {
          this.deletingId = null
        })
    },
  },
}
</script>
