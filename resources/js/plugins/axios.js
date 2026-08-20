import axios from 'axios'

axios.defaults.withCredentials = true
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'
axios.defaults.headers.common['Accept'] = 'application/json'

export const csrfCookie = () => axios.get('/sanctum/csrf-cookie')

export { axios }

export default function (app) {
  app.config.globalProperties.$axios = axios
}
