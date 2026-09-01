import axios from 'axios'

const api = axios.create({
  baseURL: '/api',
  withCredentials: true,
  withXSRFToken: true,
  headers: { 'X-Requested-With': 'XMLHttpRequest' },
})

api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      window.location.href = '/login'
    }
    return Promise.reject(error)
  }
)

export async function obterCsrfCookie() {
  await axios.get('/sanctum/csrf-cookie', { baseURL: '/', withCredentials: true })
}

export default api
