import { AxiosError, type AxiosResponse, type InternalAxiosRequestConfig } from 'axios'
import { afterEach, describe, expect, it } from 'vitest'

import { setLocale } from '@/i18n'
import { api, token, validationErrors } from '@/lib/api'

function respondWith(status: number, data: unknown = {}) {
  let sent: InternalAxiosRequestConfig | undefined

  api.defaults.adapter = async (config) => {
    sent = config
    const response: AxiosResponse = { data, status, statusText: '', headers: {}, config }

    if (status >= 400) {
      throw new AxiosError('Request failed', 'ERR_BAD_REQUEST', config, null, response)
    }

    return response
  }

  return () => sent
}

describe('api', () => {
  afterEach(() => {
    token.value = null
    setLocale('pl')
  })

  it('sends the interface language and the token', async () => {
    const sent = respondWith(200)
    token.value = 'test-token'
    setLocale('en')

    await api.get('/profile')

    expect(sent()?.headers['Accept-Language']).toBe('en')
    expect(sent()?.headers.Authorization).toBe('Bearer test-token')
  })

  it('sends no token to a guest request', async () => {
    const sent = respondWith(200)

    await api.post('/tokens')

    expect(sent()?.headers.Authorization).toBeUndefined()
  })

  it('forgets the token when the API answers 401', async () => {
    respondWith(401)
    token.value = 'expired-token'

    await expect(api.get('/profile')).rejects.toThrow('Request failed')

    expect(token.value).toBeNull()
  })

  it('reads the first message of each invalid field', async () => {
    respondWith(422, { errors: { email: ['The email is taken.', 'Second message.'] } })

    const error = await api.post('/users').catch((reason: unknown) => reason)

    expect(validationErrors(error)).toEqual({ email: 'The email is taken.' })
  })

  it('gives no field errors for other failures', async () => {
    respondWith(500)

    const error = await api.get('/profile').catch((reason: unknown) => reason)

    expect(validationErrors(error)).toBeNull()
  })
})
