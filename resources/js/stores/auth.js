import { axios, csrfCookie } from '@/plugins/axios'
import { defineStore } from 'pinia'

let initializePromise = null

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    initialized: false,
    loading: false,
  }),

  getters: {
    isAuthenticated: state => !!state.user,
    roleName: state => state.user?.role?.name ?? null,
    roleLabel: state => state.user?.role?.label ?? state.user?.role?.name ?? null,
    isSysAdmin: state => state.user?.role?.name === 'sysAdmin',
  },

  actions: {
    async initialize() {
      if (this.initialized) {
        return
      }

      if (initializePromise) {
        return initializePromise
      }

      initializePromise = (async () => {
        try {
          await this.fetchUser()
        } catch {
          this.user = null
        } finally {
          this.initialized = true
          initializePromise = null
        }
      })()

      return initializePromise
    },

    async fetchUser() {
      const { data } = await axios.get('/api/user')
      this.user = data.user

      return this.user
    },

    async login({ login, password, remember = false }) {
      this.loading = true

      try {
        await csrfCookie()
        const { data } = await axios.post('/login', {
          login,
          password,
          remember,
        })
        this.user = data.user
        this.initialized = true

        return this.user
      } finally {
        this.loading = false
      }
    },

    async logout() {
      try {
        await axios.post('/logout')
      } finally {
        this.user = null
      }
    },
  },
})
