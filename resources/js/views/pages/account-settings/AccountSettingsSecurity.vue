<script>
import { axios } from '@/plugins/axios'

export default {
  data() {
    return {
      isCurrentPasswordVisible: false,
      isNewPasswordVisible: false,
      isConfirmPasswordVisible: false,
      form: {
        current_password: '',
        password: '',
        password_confirmation: '',
      },
      saving: false,
      error: null,
      fieldErrors: {},
    }
  },

  methods: {
    resetForm() {
      this.form = {
        current_password: '',
        password: '',
        password_confirmation: '',
      }
      this.error = null
      this.fieldErrors = {}
    },

    async savePassword() {
      this.saving = true
      this.error = null
      this.fieldErrors = {}

      try {
        await axios.put('/api/user/password', this.form)
        this.resetForm()
        this.$toast.success('Contraseña actualizada', { timeout: 2000, closeOnClick: true })
      } catch (error) {
        this.error = error.response?.data?.message || 'No se pudo actualizar la contraseña.'
        this.fieldErrors = error.response?.data?.errors || {}
      } finally {
        this.saving = false
      }
    },

    fieldError(field) {
      return this.fieldErrors[field]?.[0] || null
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

      <VCard title="Cambiar contraseña">
        <VForm @submit.prevent="savePassword">
          <VCardText>
            <VRow class="mb-3">
              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  v-model="form.current_password"
                  :type="isCurrentPasswordVisible ? 'text' : 'password'"
                  :append-inner-icon="isCurrentPasswordVisible ? 'ri-eye-off-line' : 'ri-eye-line'"
                  autocomplete="current-password"
                  label="Contraseña actual"
                  placeholder="············"
                  :error-messages="fieldError('current_password')"
                  @click:append-inner="isCurrentPasswordVisible = !isCurrentPasswordVisible"
                />
              </VCol>
            </VRow>

            <VRow>
              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  v-model="form.password"
                  :type="isNewPasswordVisible ? 'text' : 'password'"
                  :append-inner-icon="isNewPasswordVisible ? 'ri-eye-off-line' : 'ri-eye-line'"
                  autocomplete="new-password"
                  label="Nueva contraseña"
                  placeholder="············"
                  :error-messages="fieldError('password')"
                  @click:append-inner="isNewPasswordVisible = !isNewPasswordVisible"
                />
              </VCol>

              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  v-model="form.password_confirmation"
                  :type="isConfirmPasswordVisible ? 'text' : 'password'"
                  :append-inner-icon="isConfirmPasswordVisible ? 'ri-eye-off-line' : 'ri-eye-line'"
                  autocomplete="new-password"
                  label="Confirmar contraseña"
                  placeholder="············"
                  :error-messages="fieldError('password_confirmation')"
                  @click:append-inner="isConfirmPasswordVisible = !isConfirmPasswordVisible"
                />
              </VCol>
            </VRow>
          </VCardText>

          <VCardText>
            <p class="text-base font-weight-medium mt-2">
              Requisitos de la contraseña:
            </p>

            <ul class="d-flex flex-column gap-y-3">
              <li class="d-flex">
                <VIcon
                  size="7"
                  icon="ri-checkbox-blank-circle-fill"
                  class="me-3 mt-2"
                />
                <span class="font-weight-medium">Mínimo 8 caracteres.</span>
              </li>
            </ul>
          </VCardText>

          <VCardText class="d-flex flex-wrap gap-4">
            <VBtn
              type="submit"
              :loading="saving"
            >
              Guardar cambios
            </VBtn>

            <VBtn
              type="button"
              color="secondary"
              variant="outlined"
              :disabled="saving"
              @click="resetForm"
            >
              Restablecer
            </VBtn>
          </VCardText>
        </VForm>
      </VCard>
    </VCol>
  </VRow>
</template>
