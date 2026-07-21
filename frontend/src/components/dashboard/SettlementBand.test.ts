import { mount } from '@vue/test-utils'
import { RouterLinkStub } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import SettlementBand from './SettlementBand.vue'
import { formatMoney } from '../../utils/money'

function holders(count: number, balance = 1_000) {
  return Array.from({ length: count }, (_, i) => ({
    id: i + 1,
    business_name: `Merchant ${i + 1}`,
    wallet_balance: balance,
  }))
}

function mountBand(merchants: ReturnType<typeof holders>, total?: number) {
  return mount(SettlementBand, {
    props: {
      merchants,
      total: total ?? merchants.reduce((sum, m) => sum + m.wallet_balance, 0),
    },
    global: { stubs: { RouterLink: RouterLinkStub } },
  })
}

const strips = (wrapper: ReturnType<typeof mountBand>) => wrapper.findAllComponents(RouterLinkStub)

describe('SettlementBand', () => {
  it('renders nothing when no float is waiting', () => {
    expect(mountBand([], 0).find('a').exists()).toBe(false)
    expect(mountBand(holders(0)).text()).toBe('')
  })

  it('ignores merchants holding nothing', () => {
    const wrapper = mountBand([
      { id: 1, business_name: 'Holds float', wallet_balance: 5_000 },
      { id: 2, business_name: 'Settled out', wallet_balance: 0 },
    ])

    expect(strips(wrapper)).toHaveLength(1)
    expect(wrapper.text()).not.toContain('Settled out')
  })

  it('orders strips by balance, largest first', () => {
    const wrapper = mountBand([
      { id: 1, business_name: 'Small', wallet_balance: 1_000 },
      { id: 2, business_name: 'Large', wallet_balance: 9_000 },
    ])

    expect(strips(wrapper)[0]!.attributes('aria-label')).toContain('Large')
  })

  it('sizes each strip by its share of the float', () => {
    const wrapper = mountBand([
      { id: 1, business_name: 'Three quarters', wallet_balance: 7_500 },
      { id: 2, business_name: 'One quarter', wallet_balance: 2_500 },
    ])

    const widths = strips(wrapper).map((s) => s.attributes('style'))
    expect(widths[0]).toContain('75%')
    expect(widths[1]).toContain('25%')
  })

  it('names each strip with merchant, amount and share for assistive tech', () => {
    const wrapper = mountBand([{ id: 7, business_name: 'Makola Textiles', wallet_balance: 10_000 }])

    expect(strips(wrapper)[0]!.attributes('aria-label')).toBe(
      `Makola Textiles, ${formatMoney(10_000)}, 100% of the float`,
    )
  })

  it('links a strip to its merchant', () => {
    const wrapper = mountBand([{ id: 42, business_name: 'Ho Craft Works', wallet_balance: 500 }])

    expect(strips(wrapper)[0]!.props('to')).toBe('/merchants/42')
  })

  describe('grouping the tail', () => {
    // Past a handful the strips are a few pixels wide: impossible to aim at,
    // and each would add a tab stop to the dashboard for nothing.
    it('caps individual strips and folds the rest into one', () => {
      const wrapper = mountBand(holders(12))
      const rendered = strips(wrapper)

      expect(rendered).toHaveLength(9) // 8 named + 1 aggregate
      expect(rendered[8]!.attributes('aria-label')).toContain('4 other merchants')
      expect(rendered[8]!.props('to')).toBe('/merchants')
    })

    it('the aggregate strip carries the combined balance and share', () => {
      const wrapper = mountBand(holders(10, 1_000)) // 10 × GHS 10 = GHS 100
      const aggregate = strips(wrapper)[8]!

      // The two merchants past the cap hold GHS 20 between them — 20%.
      expect(aggregate.attributes('aria-label')).toBe(
        `2 other merchants, ${formatMoney(2_000)}, 20% of the float`,
      )
    })

    it('adds no aggregate strip when everyone fits', () => {
      const rendered = strips(mountBand(holders(8)))

      expect(rendered).toHaveLength(8)
      expect(rendered.at(-1)!.attributes('aria-label')).not.toContain('other merchants')
    })
  })
})
