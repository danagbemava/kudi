import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it } from 'vitest'
import MerchantFormModal from './MerchantFormModal.vue'

function mountModal(open: boolean) {
  return mount(MerchantFormModal, {
    props: { open },
    global: { stubs: { teleport: true } },
  })
}

describe('MerchantFormModal', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('renders nothing while closed, so the form never sits inline on the page', () => {
    const wrapper = mountModal(false)

    expect(wrapper.text()).not.toContain('Business name')
    expect(wrapper.find('form').exists()).toBe(false)
  })

  it('renders the registration form inside a dialog when opened', () => {
    const wrapper = mountModal(true)

    expect(wrapper.find('[role="dialog"]').exists()).toBe(true)
    expect(wrapper.find('[role="dialog"]').text()).toContain('Business name')
  })
})
