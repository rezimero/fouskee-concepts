import axios, { AxiosError, type InternalAxiosRequestConfig } from 'axios'

const apiUrl = import.meta.env.VITE_API_URL ?? 'http://localhost:8000'

type RetriableRequest = InternalAxiosRequestConfig & {
  _csrfRetried?: boolean
}

export const http = axios.create({
  baseURL: apiUrl,
  headers: { Accept: 'application/json' },
  withCredentials: true,
  withXSRFToken: true,
})

export async function initializeCsrf() {
  await axios.get(`${apiUrl}/sanctum/csrf-cookie`, {
    headers: { Accept: 'application/json' },
    withCredentials: true,
    withXSRFToken: true,
  })
}

http.interceptors.response.use(
  (response) => response,
  async (error: AxiosError) => {
    const request = error.config as RetriableRequest | undefined

    if (error.response?.status === 419 && request && !request._csrfRetried) {
      request._csrfRetried = true
      await initializeCsrf()
      return http(request)
    }

    if (error.response?.status === 401) {
      window.dispatchEvent(new CustomEvent('fouskee:auth-expired'))
    }

    return Promise.reject(error)
  },
)
