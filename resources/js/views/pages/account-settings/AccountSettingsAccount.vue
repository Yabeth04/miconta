<script>
import defaultAvatar from '@images/avatars/avatar-1.png'
import { axios } from '@/plugins/axios'
import { useAuthStore } from '@/stores/auth'

export default {
  setup() {
    const auth = useAuthStore()

    return { auth }
  },

  data() {
    return {
      form: {
        name: '',
      },
      pendingAvatarFile: null,
      removeAvatarOnSave: false,
      avatarPreview: null,
      saving: false,
      error: null,
      fieldErrors: {},
      usernameDialog: false,
      emailDialog: false,
      usernameForm: {
        username: '',
        current_password: '',
      },
      emailForm: {
        email: '',
        current_password: '',
      },
      savingUsername: false,
      savingEmail: false,
      usernameDialogError: null,
      emailDialogError: null,
      usernameFieldErrors: {},
      emailFieldErrors: {},
      isUsernamePasswordVisible: false,
      isEmailPasswordVisible: false,
    }
  },

  computed: {
    avatarSrc() {
      if (this.avatarPreview) {
        return this.avatarPreview
      }

      if (this.removeAvatarOnSave || !this.auth.user?.avatar_url) {
        return defaultAvatar
      }

      return this.auth.user.avatar_url
    },

    hasCustomAvatar() {
      if (this.pendingAvatarFile) {
        return true
      }

      return !!(this.auth.user?.avatar_url && !this.removeAvatarOnSave)
    },

    hasPendingChanges() {
      const user = this.auth.user
      if (!user) {
        return false
      }

      return this.form.name !== (user.name || '')
        || !!this.pendingAvatarFile
        || this.removeAvatarOnSave
    },
  },

  created() {
    this.resetForm()
  },

  methods: {
    resetForm() {
      const user = this.auth.user
      if (!user) {
        return
      }

      this.form = {
        name: user.name || '',
      }
      this.pendingAvatarFile = null
      this.removeAvatarOnSave = false
      this.avatarPreview = null
      this.error = null
      this.fieldErrors = {}
    },

    async saveProfile() {
      if (!this.hasPendingChanges) {
        return
      }

      this.saving = true
      this.error = null
      this.fieldErrors = {}

      try {
        let user = this.auth.user
        const nameChanged = this.form.name !== (user.name || '')

        if (nameChanged) {
          const { data } = await axios.put('/api/user/profile', {
            name: this.form.name,
            username: user.username,
            email: user.email,
          })
          user = data.user
          this.auth.user = user
        }

        if (this.removeAvatarOnSave && !this.pendingAvatarFile) {
          const { data } = await axios.delete('/api/user/avatar')
          user = data.user
          this.auth.user = user
        } else if (this.pendingAvatarFile) {
          const formData = new FormData()
          formData.append('avatar', this.pendingAvatarFile)
          const { data } = await axios.post('/api/user/avatar', formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
          })
          user = data.user
          this.auth.user = user
        }

        this.pendingAvatarFile = null
        this.removeAvatarOnSave = false
        this.avatarPreview = null
        this.$toast.success('Cambios guardados', { timeout: 2000, closeOnClick: true })
      } catch (error) {
        this.error = error.response?.data?.message || 'No se pudieron guardar los cambios.'
        this.fieldErrors = error.response?.data?.errors || {}
      } finally {
        this.saving = false
      }
    },

    openUsernameDialog() {
      this.usernameForm = {
        username: '',
        current_password: '',
      }
      this.usernameDialogError = null
      this.usernameFieldErrors = {}
      this.isUsernamePasswordVisible = false
      this.usernameDialog = true
    },

    openEmailDialog() {
      this.emailForm = {
        email: '',
        current_password: '',
      }
      this.emailDialogError = null
      this.emailFieldErrors = {}
      this.isEmailPasswordVisible = false
      this.emailDialog = true
    },

    async saveUsername() {
      this.savingUsername = true
      this.usernameDialogError = null
      this.usernameFieldErrors = {}

      try {
        const user = this.auth.user
        const { data } = await axios.put('/api/user/profile', {
          name: user.name,
          username: this.usernameForm.username,
          email: user.email,
          current_password: this.usernameForm.current_password,
        })
        this.auth.user = data.user
        this.usernameDialog = false
        this.$toast.success('Usuario actualizado', { timeout: 2000, closeOnClick: true })
      } catch (error) {
        this.usernameDialogError = error.response?.data?.message || 'No se pudo actualizar el usuario.'
        this.usernameFieldErrors = error.response?.data?.errors || {}
      } finally {
        this.savingUsername = false
      }
    },

    async saveEmail() {
      this.savingEmail = true
      this.emailDialogError = null
      this.emailFieldErrors = {}

      try {
        const user = this.auth.user
        const { data } = await axios.put('/api/user/profile', {
          name: user.name,
          username: user.username,
          email: this.emailForm.email,
          current_password: this.emailForm.current_password,
        })
        this.auth.user = data.user
        this.emailDialog = false
        this.$toast.success('Correo actualizado', { timeout: 2000, closeOnClick: true })
      } catch (error) {
        this.emailDialogError = error.response?.data?.message || 'No se pudo actualizar el correo.'
        this.emailFieldErrors = error.response?.data?.errors || {}
      } finally {
        this.savingEmail = false
      }
    },

    onAvatarSelected(event) {
      const file = event.target.files?.[0]
      event.target.value = ''

      if (!file) {
        return
      }

      this.error = null
      this.pendingAvatarFile = file
      this.removeAvatarOnSave = false

      const reader = new FileReader()
      reader.onload = () => {
        if (typeof reader.result === 'string') {
          this.avatarPreview = reader.result
        }
      }
      reader.readAsDataURL(file)
    },

    removeAvatar() {
      if (!this.auth.user?.avatar_url && !this.pendingAvatarFile) {
        return
      }

      this.error = null
      this.pendingAvatarFile = null
      this.avatarPreview = null
      this.removeAvatarOnSave = true
    },

    fieldError(field) {
      return this.fieldErrors[field]?.[0] || null
    },

    usernameFieldError(field) {
      return this.usernameFieldErrors[field]?.[0] || null
    },

    emailFieldError(field) {
      return this.emailFieldErrors[field]?.[0] || null
    },
  },
}
</script>

<template>
  <VRow>
    <VCol cols="12">
      <VAlert
        v-if="error"
        type="error"
        variant="tonal"
        rounded="lg"
        class="mb-4"
      >
        {{ error }}
      </VAlert>

      <VCard title="Datos de la cuenta">
        <VCardText class="d-flex flex-wrap align-center gap-6">
          <VAvatar
            rounded="lg"
            size="100"
            :image="avatarSrc"
          />

          <div class="d-flex flex-column justify-center gap-4">
            <div class="d-flex flex-wrap gap-2">
              <VBtn
                color="primary"
                @click="$refs.avatarInput?.click()"
              >
                <VIcon
                  icon="ri-upload-cloud-line"
                  class="d-sm-none"
                />
                <span class="d-none d-sm-block">Subir foto</span>
              </VBtn>

              <input
                ref="avatarInput"
                type="file"
                accept=".jpeg,.png,.jpg,.gif,.webp"
                hidden
                @change="onAvatarSelected"
              >

              <VBtn
                color="error"
                variant="outlined"
                :disabled="!hasCustomAvatar"
                @click="removeAvatar"
              >
                <span class="d-none d-sm-block">Quitar foto</span>
                <VIcon
                  icon="ri-delete-bin-line"
                  class="d-sm-none"
                />
              </VBtn>
            </div>

            <p class="text-body-2 mb-0">
              JPG, PNG, GIF o WebP. Máximo 800 KB. Los cambios se aplican al guardar.
            </p>
          </div>
        </VCardText>

        <VDivider />

        <VCardText>
          <VForm @submit.prevent="saveProfile">
            <VRow>
              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  v-model="form.name"
                  label="Nombre completo"
                  placeholder="Tu nombre"
                  :error-messages="fieldError('name')"
                />
              </VCol>

              <VCol
                cols="12"
                md="6"
              >
                <div class="d-flex align-start gap-2">
                  <VTextField
                    :model-value="auth.user?.username || ''"
                    label="Usuario"
                    readonly
                    disabled
                    class="flex-grow-1"
                  />
                  <VBtn
                    icon
                    variant="tonal"
                    color="primary"
                    class="mt-1"
                    @click="openUsernameDialog"
                  >
                    <VIcon icon="ri-lock-line" />
                  </VBtn>
                </div>
              </VCol>

              <VCol
                cols="12"
                md="6"
              >
                <div class="d-flex align-start gap-2">
                  <VTextField
                    :model-value="auth.user?.email || ''"
                    label="Correo electrónico"
                    readonly
                    disabled
                    class="flex-grow-1"
                  />
                  <VBtn
                    icon
                    variant="tonal"
                    color="primary"
                    class="mt-1"
                    @click="openEmailDialog"
                  >
                    <VIcon icon="ri-lock-line" />
                  </VBtn>
                </div>
              </VCol>

              <VCol
                cols="12"
                class="d-flex flex-wrap gap-4"
              >
                <VBtn
                  type="submit"
                  :loading="saving"
                  :disabled="!hasPendingChanges || saving"
                >
                  Guardar cambios
                </VBtn>

                <VBtn
                  color="secondary"
                  variant="outlined"
                  type="button"
                  :disabled="saving"
                  @click="resetForm"
                >
                  Restablecer
                </VBtn>
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
      </VCard>
    </VCol>

    <VDialog
      v-model="usernameDialog"
      max-width="480"
    >
      <VCard rounded="lg">
        <VCardTitle class="text-h6 px-5 pt-5 pb-3">
          Cambiar usuario
        </VCardTitle>

        <VDivider />

        <VCardText class="pa-5 d-flex flex-column gap-4">
          <VAlert
            v-if="usernameDialogError"
            type="error"
            variant="tonal"
            rounded="lg"
          >
            {{ usernameDialogError }}
          </VAlert>

          <p class="text-body-2 text-medium-emphasis mb-0">
            Usuario actual: <strong>{{ auth.user?.username }}</strong>
          </p>

          <VTextField
            v-model="usernameForm.username"
            label="Nuevo usuario"
            placeholder="nombre_usuario"
            variant="outlined"
            rounded="lg"
            hide-details="auto"
            :error-messages="usernameFieldError('username')"
          />

          <VTextField
            v-model="usernameForm.current_password"
            :type="isUsernamePasswordVisible ? 'text' : 'password'"
            :append-inner-icon="isUsernamePasswordVisible ? 'ri-eye-off-line' : 'ri-eye-line'"
            autocomplete="current-password"
            label="Contraseña actual"
            placeholder="············"
            variant="outlined"
            rounded="lg"
            hide-details="auto"
            :error-messages="usernameFieldError('current_password')"
            @click:append-inner="isUsernamePasswordVisible = !isUsernamePasswordVisible"
          />
        </VCardText>

        <VCardActions class="px-5 pb-5">
          <VSpacer />
          <VBtn
            variant="text"
            rounded="lg"
            @click="usernameDialog = false"
          >
            Cancelar
          </VBtn>
          <VBtn
            color="primary"
            variant="flat"
            rounded="lg"
            :loading="savingUsername"
            @click="saveUsername"
          >
            Guardar
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <VDialog
      v-model="emailDialog"
      max-width="480"
    >
      <VCard rounded="lg">
        <VCardTitle class="text-h6 px-5 pt-5 pb-3">
          Cambiar correo electrónico
        </VCardTitle>

        <VDivider />

        <VCardText class="pa-5 d-flex flex-column gap-4">
          <VAlert
            v-if="emailDialogError"
            type="error"
            variant="tonal"
            rounded="lg"
          >
            {{ emailDialogError }}
          </VAlert>

          <p class="text-body-2 text-medium-emphasis mb-0">
            Correo actual: <strong>{{ auth.user?.email }}</strong>
          </p>

          <VTextField
            v-model="emailForm.email"
            label="Nuevo correo electrónico"
            placeholder="correo@ejemplo.com"
            type="email"
            variant="outlined"
            rounded="lg"
            hide-details="auto"
            :error-messages="emailFieldError('email')"
          />

          <VTextField
            v-model="emailForm.current_password"
            :type="isEmailPasswordVisible ? 'text' : 'password'"
            :append-inner-icon="isEmailPasswordVisible ? 'ri-eye-off-line' : 'ri-eye-line'"
            autocomplete="current-password"
            label="Contraseña actual"
            placeholder="············"
            variant="outlined"
            rounded="lg"
            hide-details="auto"
            :error-messages="emailFieldError('current_password')"
            @click:append-inner="isEmailPasswordVisible = !isEmailPasswordVisible"
          />
        </VCardText>

        <VCardActions class="px-5 pb-5">
          <VSpacer />
          <VBtn
            variant="text"
            rounded="lg"
            @click="emailDialog = false"
          >
            Cancelar
          </VBtn>
          <VBtn
            color="primary"
            variant="flat"
            rounded="lg"
            :loading="savingEmail"
            @click="saveEmail"
          >
            Guardar
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </VRow>
</template>
