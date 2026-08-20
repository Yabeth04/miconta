<script setup>
import { useAuthStore } from '@/stores/auth'
import avatar1 from '@images/avatars/avatar-1.png'
import { useRouter } from 'vue-router'

const auth = useAuthStore()
const router = useRouter()
const loggingOut = ref(false)

const displayName = computed(() => auth.user?.name || 'Usuario')
const displayRole = computed(() => auth.roleLabel || 'Usuario')

const onLogout = async () => {
  loggingOut.value = true

  try {
    await auth.logout()
    await router.push('/login')
  } finally {
    loggingOut.value = false
  }
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
      <VImg :src="avatar1" />

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
                    <VImg :src="avatar1" />
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
