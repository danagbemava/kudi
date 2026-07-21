import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import { createMemoryHistory, createRouter } from 'vue-router'
import SideNav from './SideNav.vue'

const blank = { template: '<div />' }

// Mirrors the real router: detail routes are siblings of their section route,
// not children. RouterLink's own active-class does not treat the section as
// active on a detail page under that shape, which is the bug this guards.
function buildRouter() {
  return createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/', component: blank },
      { path: '/merchants', component: blank },
      { path: '/merchants/:id', component: blank },
      { path: '/transactions', component: blank },
      { path: '/settlements', component: blank },
      { path: '/reports', component: blank },
    ],
  })
}

async function navItemsAt(path: string) {
  const router = buildRouter()
  await router.push(path)
  await router.isReady()

  const wrapper = mount(SideNav, { global: { plugins: [router] } })

  return wrapper
    .findAll('a')
    .filter((a) => a.attributes('aria-current') === 'page')
    .map((a) => a.text().trim())
}

describe('SideNav active state', () => {
  it.each([
    ['/', 'Dashboard'],
    ['/merchants', 'Merchants'],
    ['/transactions', 'Transactions'],
    ['/settlements', 'Settlements'],
    ['/reports', 'Reports'],
  ])('marks %s as %s', async (path, expected) => {
    expect(await navItemsAt(path)).toEqual([expected])
  })

  it('keeps the section highlighted on a detail route', async () => {
    expect(await navItemsAt('/merchants/6')).toEqual(['Merchants'])
  })

  it('does not leave Dashboard highlighted on every route, since / prefixes all paths', async () => {
    expect(await navItemsAt('/transactions')).not.toContain('Dashboard')
  })
})
