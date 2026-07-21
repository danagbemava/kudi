import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import StatusBadge from './StatusBadge.vue'

describe('StatusBadge', () => {
  it.each([
    ['successful', 'text-green-700'],
    ['pending', 'text-amber-700'],
    ['failed', 'text-red-700'],
    ['active', 'text-green-700'],
    ['inactive', 'text-slate-600'],
  ] as const)('renders %s with its tone', (status, toneClass) => {
    const wrapper = mount(StatusBadge, { props: { status } })

    expect(wrapper.text()).toContain(status)
    expect(wrapper.classes().join(' ')).toContain(toneClass)
  })

  it('pulses the dot only while pending', () => {
    const pending = mount(StatusBadge, { props: { status: 'pending' } })
    const done = mount(StatusBadge, { props: { status: 'successful' } })

    expect(pending.find('span span').classes()).toContain('animate-pulse')
    expect(done.find('span span').classes()).not.toContain('animate-pulse')
  })
})
