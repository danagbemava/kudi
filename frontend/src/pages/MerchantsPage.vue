<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import type { Merchant, MerchantStatus } from '../api/types'
import MerchantFormModal from '../components/merchants/MerchantFormModal.vue'
import BaseButton from '../components/ui/BaseButton.vue'
import DataTable from '../components/ui/DataTable.vue'
import EmptyState from '../components/ui/EmptyState.vue'
import PaginationBar from '../components/ui/PaginationBar.vue'
import StatusBadge from '../components/ui/StatusBadge.vue'
import { useMerchantsStore } from '../stores/merchants'
import { formatMoney } from '../utils/money'

const merchants = useMerchantsStore()

const search = ref('')
const status = ref<MerchantStatus | ''>('')
const page = ref(1)
const formOpen = ref(false)

let searchTimer: ReturnType<typeof setTimeout> | undefined

function load(): void {
  merchants.fetch({ search: search.value, status: status.value, page: page.value })
}

onMounted(load)

watch(search, () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    page.value = 1
    load()
  }, 300)
})

watch(status, () => {
  page.value = 1
  load()
})

watch(page, load)

function onCreated(merchant: Merchant): void {
  void merchant
  page.value = 1
  load()
}

const columns = ['Business', 'Contact', 'Institution', 'Wallet balance', 'Status']
</script>

<template>
  <div>
    <div class="mb-4 flex flex-wrap items-center gap-3">
      <div class="relative min-w-0 flex-1 sm:max-w-xs">
        <svg
          class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400"
          viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"
        >
          <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
        </svg>
        <input
          v-model="search"
          type="search"
          placeholder="Search merchants…"
          aria-label="Search merchants"
          class="w-full rounded-lg border border-line bg-white py-2 pr-3 pl-9 text-sm placeholder:text-slate-500 hover:border-slate-300 focus:outline-2 focus:outline-offset-1 focus:outline-primary-600"
        />
      </div>

      <select
        v-model="status"
        aria-label="Filter by status"
        class="rounded-lg border border-line bg-white px-3 py-2 text-sm hover:border-slate-300 focus:outline-2 focus:outline-offset-1 focus:outline-primary-600"
      >
        <option value="">All statuses</option>
        <option value="active">Active</option>
        <option value="inactive">Inactive</option>
      </select>

      <BaseButton class="ml-auto" @click="formOpen = true">Register merchant</BaseButton>
    </div>

    <DataTable
      :columns="columns"
      :loading="merchants.loading"
      :error="merchants.error"
      :empty="merchants.items.length === 0"
      @retry="load"
    >
      <tr
        v-for="merchant in merchants.items"
        :key="merchant.id"
        class="transition-colors hover:bg-slate-50/70"
      >
        <td class="px-4 py-3">
          <RouterLink
            :to="`/merchants/${merchant.id}`"
            class="font-medium text-ink hover:text-primary-600 focus-visible:outline-2 focus-visible:outline-primary-600"
          >
            {{ merchant.business_name }}
          </RouterLink>
        </td>
        <td class="px-4 py-3">
          <p class="text-ink">{{ merchant.name }}</p>
          <p class="text-xs text-slate-500">{{ merchant.email }}</p>
        </td>
        <td class="px-4 py-3 text-slate-600">{{ merchant.institution?.name }}</td>
        <td class="font-display px-4 py-3 font-semibold text-ink">
          {{ formatMoney(merchant.wallet_balance ?? 0) }}
        </td>
        <td class="px-4 py-3"><StatusBadge :status="merchant.status" /></td>
      </tr>

      <template #empty>
        <EmptyState
          title="No merchants found"
          :message="
            search || status
              ? 'No merchants match the current filters. Adjust the search or status filter.'
              : 'Register your first merchant to start accepting payments.'
          "
        >
          <template #action>
            <BaseButton v-if="!search && !status" @click="formOpen = true">
              Register merchant
            </BaseButton>
          </template>
        </EmptyState>
      </template>
    </DataTable>

    <PaginationBar v-if="merchants.meta" :meta="merchants.meta" @page="page = $event" />

    <MerchantFormModal :open="formOpen" @close="formOpen = false" @created="onCreated" />
  </div>
</template>
