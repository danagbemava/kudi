import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it } from 'vitest'
import RunSettlementButton from './RunSettlementButton.vue'
import { useDashboardStore } from '../../stores/dashboard'

function mountButton(walletBalance: number, unsettledCount = 3) {
  const dashboard = useDashboardStore()
  dashboard.data = {
    totals: {
      wallet_balance: walletBalance,
      payment_volume: 0,
      platform_fees: 0,
      settled: 0,
      merchant_count: 12,
      pending_count: 0,
      pending_amount: 0,
    },
    last_settlement: null,
    volume_series: [],
    recent_transactions: [],
    unsettled: Array.from({ length: unsettledCount }, (_, i) => ({
      id: i + 1,
      business_name: `Merchant ${i + 1}`,
      wallet_balance: Math.floor(walletBalance / Math.max(unsettledCount, 1)),
    })),
    // Every holder is named in this fixture, so there is no tail to describe.
    unsettled_meta: {
      holder_count: unsettledCount,
      remainder_count: 0,
      remainder_amount: 0,
    },
  }

  return mount(RunSettlementButton, { global: { stubs: { Teleport: true } } })
}

describe('RunSettlementButton', () => {
  beforeEach(() => setActivePinia(createPinia()))

  it('is disabled when there is nothing to settle', () => {
    // The dashboard used to show an always-enabled "Run settlement" that only
    // navigated, so at zero float it promised an action it could not perform.
    expect(mountButton(0).find('button').attributes('disabled')).toBeDefined()
  })

  it('is enabled once float is waiting', () => {
    expect(mountButton(150_000).find('button').attributes('disabled')).toBeUndefined()
  })

  it('asks for confirmation rather than settling on the first click', async () => {
    const wrapper = mountButton(150_000)

    expect(wrapper.text()).not.toContain('This moves')

    await wrapper.find('button').trigger('click')

    expect(wrapper.text()).toContain('This moves')
  })

  it('states the exact amount and how many wallets it clears', async () => {
    const wrapper = mountButton(150_000, 4)

    await wrapper.find('button').trigger('click')

    expect(wrapper.text()).toContain('GHS')
    expect(wrapper.text()).toContain('1,500.00')
    expect(wrapper.text()).toContain('4 merchant wallets')
  })

  it('uses the singular when only one wallet holds float', async () => {
    const wrapper = mountButton(5_000, 1)

    await wrapper.find('button').trigger('click')

    expect(wrapper.text()).toContain('1 merchant wallet')
    expect(wrapper.text()).not.toContain('merchant wallets')
  })
})
