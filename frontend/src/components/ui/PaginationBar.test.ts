import { mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import type { PageMeta } from '../../api/types'
import PaginationBar from './PaginationBar.vue'

// The component only reads three fields, but the prop is the shared PageMeta, so
// the fixtures carry the whole shape the API actually returns.
const meta: PageMeta = {
  current_page: 2,
  last_page: 14,
  per_page: 15,
  total: 205,
  from: 16,
  to: 30,
}

describe('PaginationBar', () => {
  beforeEach(() => {
    window.scrollTo = vi.fn()
    window.scrollY = 0
  })

  it('emits the target page', async () => {
    const wrapper = mount(PaginationBar, { props: { meta } })

    await wrapper.get('[data-test="next"]').trigger('click')
    await wrapper.get('[data-test="previous"]').trigger('click')

    expect(wrapper.emitted('page')).toEqual([[3], [1]])
  })

  // Paginating swaps the whole result set. Left alone, the viewport stays where
  // it was — mid-table on a full page, but snapped to the top on a short final
  // page, because the shorter document clamps the scroll. Same click, different
  // landing spot, which reads as the screen moving on its own.
  it('returns the viewport to the start of the results when paginating', async () => {
    window.scrollY = 420
    const wrapper = mount(PaginationBar, { props: { meta } })

    await wrapper.get('[data-test="next"]').trigger('click')

    expect(window.scrollTo).toHaveBeenCalledWith({ top: 0, behavior: 'auto' })
  })

  it('leaves the viewport alone when already at the top', async () => {
    window.scrollY = 0
    const wrapper = mount(PaginationBar, { props: { meta } })

    await wrapper.get('[data-test="next"]').trigger('click')

    expect(window.scrollTo).not.toHaveBeenCalled()
  })

  it('renders nothing when there is only one page', () => {
    const wrapper = mount(PaginationBar, {
      props: { meta: { ...meta, current_page: 1, last_page: 1, total: 4 } },
    })

    expect(wrapper.find('nav').exists()).toBe(false)
  })
})
