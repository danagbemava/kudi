import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import { nextTick } from 'vue'
import BaseModal from './BaseModal.vue'

function mountModal() {
  return mount(BaseModal, {
    props: { open: true, title: 'Run daily settlement' },
    slots: { default: '<button id="inner">Confirm</button>' },
    global: { stubs: { teleport: true } },
    attachTo: document.body,
  })
}

describe('BaseModal', () => {
  it('closes on Escape', async () => {
    const wrapper = mountModal()

    window.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }))
    await nextTick()

    expect(wrapper.emitted('close')).toHaveLength(1)
    wrapper.unmount()
  })

  it('still closes on Escape after focus is dropped to the body', async () => {
    // A submit button that disables itself while loading makes every browser
    // reset focus to <body>. A handler bound to the dialog element would stop
    // receiving keys at that point, silently disabling Escape.
    const wrapper = mountModal()
    ;(document.activeElement as HTMLElement | null)?.blur()

    expect(document.activeElement).toBe(document.body)

    window.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }))
    await nextTick()

    expect(wrapper.emitted('close')).toHaveLength(1)
    wrapper.unmount()
  })

  it('labels the dialog from its visible heading rather than a duplicated string', () => {
    const wrapper = mountModal()
    const dialog = wrapper.find('[role="dialog"]')
    const labelledBy = dialog.attributes('aria-labelledby')

    expect(labelledBy).toBeTruthy()
    expect(dialog.attributes('aria-label')).toBeUndefined()
    expect(wrapper.find(`[id="${labelledBy}"]`).text()).toBe('Run daily settlement')
    wrapper.unmount()
  })
})
