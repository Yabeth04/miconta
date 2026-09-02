<template>
  <div>
    <div class="d-flex flex-wrap align-center justify-space-between gap-3 mb-4">
      <div>
        <h1 class="text-h4 font-weight-medium mb-1">
          Usuarios
        </h1>
        <p class="text-body-2 text-medium-emphasis mb-0">
          Crear y administrar cuentas de acceso
        </p>
      </div>
      <VBtn
        color="primary"
        rounded="lg"
        prepend-icon="ri-add-line"
        @click="openCreateDialog"
      >
        Nuevo usuario
      </VBtn>
    </div>

    <VAlert
      v-if="error"
      type="error"
      variant="tonal"
      rounded="lg"
      class="mb-4"
    >
      {{ error }}
    </VAlert>

    <VCard
      rounded="lg"
      :loading="loading"
    >
      <VDataTable
        :headers="headers"
        :items="items"
        :items-per-page="10"
        class="text-no-wrap"
      >
        <template #item.name="{ item }">
          <div class="font-weight-medium">
            {{ item.name }}
          </div>
          <div class="text-caption text-medium-emphasis">
            @{{ item.username }}
          </div>
        </template>

        <template #item.role="{ item }">
          <VChip
            size="small"
            :color="item.role?.name === 'sysAdmin' ? 'primary' : 'default'"
            variant="tonal"
          >
            {{ item.role?.label || '—' }}
          </VChip>
        </template>

        <template #item.created_at="{ item }">
          {{ item.created_at ? $formatDate(item.created_at) : '—' }}
        </template>

        <template #item.actions="{ item }">
          <VBtn
            icon
            variant="text"
            size="small"
            aria-label="Editar"
            @click="openEditDialog(item)"
          >
            <VIcon icon="ri-edit-line" />
          </VBtn>
          <VBtn
            icon
            variant="text"
            size="small"
            color="error"
            aria-label="Eliminar"
            :disabled="item.id === auth.user?.id"
            @click="confirmDelete(item)"
          >
            <VIcon icon="ri-delete-bin-line" />
          </VBtn>
        </template>

        <template #no-data>
          <div class="text-center py-8 text-medium-emphasis">
            No hay usuarios registrados.
          </div>
        </template>
      </VDataTable>
    </VCard>

    <VDialog
      v-model="formDialog"
      max-width="520"
    >
      <VCard rounded="lg">
        <VCardTitle class="text-h6 px-5 pt-5 pb-3">
          {{ editingItem ? 'Editar usuario' : 'Nuevo usuario' }}
        </VCardTitle>

        <VDivider />

        <VCardText class="pa-5 d-flex flex-column gap-4">
          <VAlert
            v-if="formError"
            type="error"
            variant="tonal"
            rounded="lg"
          >
            {{ formError }}
          </VAlert>

          <VTextField
            v-model="form.name"
            label="Nombre completo"
            variant="outlined"
            rounded="lg"
            hide-details="auto"
            :error-messages="fieldError('name')"
          />

          <VTextField
            v-model="form.username"
            label="Usuario"
            variant="outlined"
            rounded="lg"
            hide-details="auto"
            :error-messages="fieldError('username')"
          />

          <VTextField
            v-model="form.email"
            label="Correo electrónico"
            type="email"
            variant="outlined"
            rounded="lg"
            hide-details="auto"
            :error-messages="fieldError('email')"
          />

          <VSelect
            v-model="form.role_id"
            :items="roles"
            item-title="label"
            item-value="id"
            label="Rol"
            variant="outlined"
            rounded="lg"
            hide-details="auto"
            :disabled="editingItem?.id === auth.user?.id"
            :error-messages="fieldError('role_id')"
          />

          <VTextField
            v-model="form.password"
            :label="editingItem ? 'Nueva contraseña' : 'Contraseña'"
            :placeholder="editingItem ? 'Dejar vacío para no cambiar' : ''"
            :type="isPasswordVisible ? 'text' : 'password'"
            :append-inner-icon="isPasswordVisible ? 'ri-eye-off-line' : 'ri-eye-line'"
            variant="outlined"
            rounded="lg"
            hide-details="auto"
            :error-messages="fieldError('password')"
            @click:append-inner="isPasswordVisible = !isPasswordVisible"
          />
        </VCardText>

        <VCardActions class="px-5 pb-5">
          <VSpacer />
          <VBtn
            variant="text"
            rounded="lg"
            @click="formDialog = false"
          >
            Cancelar
          </VBtn>
          <VBtn
            color="primary"
            variant="flat"
            rounded="lg"
            :loading="saving"
            @click="saveUser"
          >
            Guardar
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <VDialog
      v-model="deleteDialog"
      max-width="440"
    >
      <VCard rounded="lg">
        <VCardTitle class="text-h6">
          Eliminar usuario
        </VCardTitle>
        <VCardText class="text-body-2">
          ¿Eliminar a <strong>{{ deleteTarget?.name }}</strong> (@{{ deleteTarget?.username }})?
          Se borrarán también sus movimientos, conceptos y pagos fijos.
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
            :loading="deleting"
            @click="destroyUser"
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
import { useAuthStore } from '@/stores/auth'

export default {
  name: 'ModuleUsers',

  setup() {
    const auth = useAuthStore()

    return { auth }
  },

  data() {
    return {
      items: [],
      roles: [],
      loading: true,
      saving: false,
      deleting: false,
      error: '',
      formDialog: false,
      deleteDialog: false,
      editingItem: null,
      deleteTarget: null,
      formError: '',
      fieldErrors: {},
      isPasswordVisible: false,
      form: this.emptyForm(),
      headers: [
        { title: 'Usuario', key: 'name' },
        { title: 'Correo', key: 'email' },
        { title: 'Rol', key: 'role' },
        { title: 'Creado', key: 'created_at' },
        { title: 'Acciones', key: 'actions', sortable: false, align: 'end' },
      ],
    }
  },

  mounted() {
    this.loadUsers()
  },

  methods: {
    emptyForm() {
      return {
        name: '',
        username: '',
        email: '',
        password: '',
        role_id: null,
      }
    },

    loadUsers() {
      this.loading = true
      this.error = ''

      axios
        .get('/api/users')
        .then(response => {
          this.items = response.data.data || []
          this.roles = response.data.roles || []
        })
        .catch(() => {
          this.error = 'No se pudieron cargar los usuarios.'
        })
        .finally(() => {
          this.loading = false
        })
    },

    openCreateDialog() {
      this.editingItem = null
      this.form = this.emptyForm()
      this.form.role_id = this.roles.find(role => role.name === 'user')?.id ?? null
      this.formError = ''
      this.fieldErrors = {}
      this.isPasswordVisible = false
      this.formDialog = true
    },

    openEditDialog(item) {
      this.editingItem = item
      this.form = {
        name: item.name || '',
        username: item.username || '',
        email: item.email || '',
        password: '',
        role_id: item.role?.id ?? null,
      }
      this.formError = ''
      this.fieldErrors = {}
      this.isPasswordVisible = false
      this.formDialog = true
    },

    saveUser() {
      if (this.saving) {
        return
      }

      this.saving = true
      this.formError = ''
      this.fieldErrors = {}

      const payload = {
        name: this.form.name,
        username: this.form.username,
        email: this.form.email,
        role_id: this.form.role_id,
      }

      if (this.form.password) {
        payload.password = this.form.password
      }

      const request = this.editingItem
        ? axios.put(`/api/users/${this.editingItem.id}`, payload)
        : axios.post('/api/users', {
          ...payload,
          password: this.form.password,
        })

      request
        .then(response => {
          if (this.editingItem) {
            this.items = this.items.map(item =>
              item.id === response.data.id ? response.data : item,
            )
          } else {
            this.items = [...this.items, response.data].sort((a, b) =>
              a.name.localeCompare(b.name, 'es', { sensitivity: 'base' }),
            )
          }

          this.formDialog = false
          this.$toast.success(
            this.editingItem ? 'Usuario actualizado' : 'Usuario creado',
            { timeout: 2000, closeOnClick: true },
          )
        })
        .catch(error => {
          this.formError = error.response?.data?.message || 'No se pudo guardar el usuario.'
          this.fieldErrors = error.response?.data?.errors || {}
        })
        .finally(() => {
          this.saving = false
        })
    },

    confirmDelete(item) {
      this.deleteTarget = item
      this.deleteDialog = true
    },

    destroyUser() {
      if (!this.deleteTarget?.id || this.deleting) {
        return
      }

      this.deleting = true

      axios
        .delete(`/api/users/${this.deleteTarget.id}`)
        .then(() => {
          this.items = this.items.filter(item => item.id !== this.deleteTarget.id)
          this.deleteDialog = false
          this.deleteTarget = null
          this.$toast.success('Usuario eliminado', { timeout: 2000, closeOnClick: true })
        })
        .catch(error => {
          this.error = error.response?.data?.message || 'No se pudo eliminar el usuario.'
          this.deleteDialog = false
        })
        .finally(() => {
          this.deleting = false
        })
    },

    fieldError(field) {
      return this.fieldErrors[field]?.[0] || null
    },
  },
}
</script>
