<script>
import AccountSettingsAccount from '@/views/pages/account-settings/AccountSettingsAccount.vue'
import AccountSettingsSecurity from '@/views/pages/account-settings/AccountSettingsSecurity.vue'

export default {
  components: {
    AccountSettingsAccount,
    AccountSettingsSecurity,
  },

  data() {
    return {
      activeTab: 'account',
      tabs: [
        {
          title: 'Cuenta',
          icon: 'ri-group-line',
          tab: 'account',
        },
        {
          title: 'Seguridad',
          icon: 'ri-lock-line',
          tab: 'security',
        },
      ],
    }
  },

  created() {
    const tab = this.$route.params.tab
    if (tab && this.tabs.some(item => item.tab === tab)) {
      this.activeTab = tab
    }
  },
}
</script>

<template>
  <div>
    <VTabs
      v-model="activeTab"
      show-arrows
      class="v-tabs-pill"
    >
      <VTab
        v-for="item in tabs"
        :key="item.tab"
        :value="item.tab"
      >
        <VIcon
          size="20"
          start
          :icon="item.icon"
        />
        {{ item.title }}
      </VTab>
    </VTabs>

    <VWindow
      v-model="activeTab"
      class="mt-5 disable-tab-transition"
      :touch="false"
    >
      <VWindowItem value="account">
        <AccountSettingsAccount />
      </VWindowItem>

      <VWindowItem value="security">
        <AccountSettingsSecurity />
      </VWindowItem>
    </VWindow>
  </div>
</template>
