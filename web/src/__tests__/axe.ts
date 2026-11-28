import type { VueWrapper } from '@vue/test-utils'
import axe from 'axe-core'

export async function axeViolations(wrapper: VueWrapper): Promise<string[]> {
  const root = wrapper.element.parentElement ?? document.body
  const detached = !root.isConnected

  if (detached) {
    document.body.appendChild(root)
  }

  try {
    const results = await axe.run(root, {
      rules: { 'color-contrast': { enabled: false }, region: { enabled: false } },
    })

    return results.violations.map((violation) => `${violation.id}: ${violation.nodes.map((node) => node.target.join(' ')).join(', ')}`)
  } finally {
    if (detached) {
      root.remove()
    }
  }
}
