import axios from 'axios'

type LaravelError = {
  message?: string
  errors?: Record<string, string[]>
}

export function requestErrorMessage(
  error: unknown,
  fallback = 'Something went wrong. Please try again.',
) {
  if (!axios.isAxiosError<LaravelError>(error)) return fallback

  const data = error.response?.data
  const firstError = data?.errors
    ? Object.values(data.errors).flat()[0]
    : undefined

  if (firstError) return firstError
  if (data?.message) return data.message

  if (!error.response) {
    return 'Unable to reach Fouskee. Check your connection and try again.'
  }

  return fallback
}
