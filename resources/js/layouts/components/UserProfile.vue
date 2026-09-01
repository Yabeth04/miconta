<script>
import defaultAvatar from '@images/avatars/avatar-1.png'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'

export default {
  setup() {
    const auth = useAuthStore()
    const router = useRouter()

    return { auth, router }
  },

  data() {
    return {
      loggingOut: false,
    }
  },

  computed: {
    displayName() {
      return this.auth.user?.name || 'Usuario'
    },

    displayRole() {
      return this.auth.roleLabel || 'Usuario'
    },

    avatarSrc() {
      return this.auth.user?.avatar_url || defaultAvatar
    },

    initials() {
      const name = this.auth.user?.name?.trim()
      if (!name) {
        return 'U'
      }

      return name
        .split(/\s+/)
        .slice(0, 2)
        .map(part => part[0]?.toUpperCase() || '')
        .join('')
    },
  },

  methods: {
    async onLogout() {
      this.loggingOut = true

      try {
        await this.auth.logout()
        await this.router.push('/login')
      } finally {
        this.loggingOut = false
      }
    },
  },
}
</script>

<template>
  <VBadge
    dot
    location="bottom right"
    offset-x="3"
    offset-y="3"
    color="success"
    bordered
  >
    <VAvatar
      class="cursor-pointer"
      color="primary"
      variant="tonal"
    >
      <VImg
        v-if="auth.user?.avatar_url"
        :src="avatarSrc"
      />
      <span
        v-else
        class="text-h6"
      >{{ initials }}</span>

      <VMenu
        activator="parent"
        width="230"
        location="bottom end"
        offset="14px"
      >
        <VList>
          <VListItem>
            <template #prepend>
              <VListItemAction start>
                <VBadge
                  dot
                  location="bottom right"
                  offset-x="3"
                  offset-y="3"
                  color="success"
                >
                  <VAvatar
                    color="primary"
                    variant="tonal"
                  >
                    <VImg
                      v-if="auth.user?.avatar_url"
                      :src="avatarSrc"
                    />
                    <span
                      v-else
                      class="text-body-1"
                    >{{ initials }}</span>
                  </VAvatar>
                </VBadge>
              </VListItemAction>
            </template>

            <VListItemTitle class="font-weight-semibold">
              {{ displayName }}
            </VListItemTitle>
            <VListItemSubtitle>{{ displayRole }}</VListItemSubtitle>
          </VListItem>

          <VDivider class="my-2" />

          <VListItem to="/account-settings">
            <template #prepend>
              <VIcon
                class="me-2"
                icon="ri-settings-4-line"
                size="22"
              />
            </template>
            <VListItemTitle>Configuración</VListItemTitle>
          </VListItem>

          <VDivider class="my-2" />

          <VListItem
            :disabled="loggingOut"
            @click="onLogout"
          >
            <template #prepend>
              <VIcon
                class="me-2"
                icon="ri-logout-box-r-line"
                size="22"
              />
            </template>
            <VListItemTitle>Cerrar sesión</VListItemTitle>
          </VListItem>
        </VList>
      </VMenu>
    </VAvatar>
  </VBadge>
</template>
