import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'

import App from '@/App.vue'
import router from '@/router'

describe('App', () => {
  it('renders the home page', async () => {
    await router.push('/')
    await router.isReady()

    const wrapper = mount(App, { global: { plugins: [router] } })

    expect(wrapper.text()).toContain('RTH')
  })
})
