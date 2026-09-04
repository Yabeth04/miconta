<script setup>
import AppFooter from '@/layouts/components/AppFooter.vue'
import NavItems from '@/layouts/components/NavItems.vue'
import logo from '@images/logo.svg?raw'
import VerticalNavLayout from '@layouts/components/VerticalNavLayout.vue'
import { useRoute, useRouter } from 'vue-router'

// Components
import NavbarModuleSearch from '@/layouts/components/NavbarModuleSearch.vue'
import NavbarThemeSwitcher from '@/layouts/components/NavbarThemeSwitcher.vue'
import UserProfile from '@/layouts/components/UserProfile.vue'
import AccountingImportDialog from '@/views/pages/accounting/AccountingImportDialog.vue'

const route = useRoute()
const router = useRouter()
const importDialogOpen = ref(false)

const onImported = () => {
  window.dispatchEvent(new CustomEvent('accounting:imported'))

  if (!route.path.includes('contabilidad'))
    router.push('/contabilidad')
}
</script>

<template>
  <VerticalNavLayout>
    <!-- 👉 navbar -->
    <template #navbar="{ toggleVerticalOverlayNavActive }">
      <div class="d-flex h-100 align-center">
        <!-- 👉 Vertical nav toggle in overlay mode -->
        <IconBtn
          class="d-lg-none"
          @click="toggleVerticalOverlayNavActive(true)"
        >
          <VIcon icon="ri-menu-line" />
        </IconBtn>

        <NavbarModuleSearch />

        <VSpacer />

        <IconBtn
          class="me-1"
          aria-label="Importar Excel"
          @click="importDialogOpen = true"
        >
          <VIcon icon="ri-file-excel-2-line" />
          <VTooltip
            activator="parent"
            open-delay="400"
            location="bottom"
          >
            Importar Excel
          </VTooltip>
        </IconBtn>

        <IconBtn>
          <VIcon icon="ri-notification-line" />
        </IconBtn>

        <NavbarThemeSwitcher class="me-2" />

        <UserProfile />
      </div>
    </template>

    <template #vertical-nav-header="{ toggleIsOverlayNavActive }">
      <RouterLink
        to="/"
        class="app-logo app-title-wrapper"
      >
        <!-- eslint-disable vue/no-v-html -->
        <div
          class="d-flex"
          v-html="logo"
        />
        <!-- eslint-enable -->

        <h1 class="font-weight-medium leading-normal text-xl text-uppercase">
          MiConta
        </h1>
      </RouterLink>

      <IconBtn
        class="d-block d-lg-none"
        @click="toggleIsOverlayNavActive(false)"
      >
        <VIcon icon="ri-close-line" />
      </IconBtn>
    </template>

    <template #vertical-nav-content>
      <NavItems />
    </template>

    <!-- 👉 Pages -->
    <slot />

    <AccountingImportDialog
      v-model="importDialogOpen"
      @imported="onImported"
    />

    <!-- 👉 Footer -->
    <template #footer>
      <AppFooter />
    </template>
  </VerticalNavLayout>
</template>

<style lang="scss" scoped>
.app-logo {
  display: flex;
  align-items: center;
  column-gap: 0.75rem;

  .app-logo-title {
    font-size: 1.25rem;
    font-weight: 500;
    line-height: 1.75rem;
    text-transform: uppercase;
  }
}
</style>
