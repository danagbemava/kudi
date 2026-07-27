<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ApiValidationError, toApiError } from '../api/client'
import BaseButton from '../components/ui/BaseButton.vue'
import BaseInput from '../components/ui/BaseInput.vue'
import KudiLogo from '../components/ui/KudiLogo.vue'
import { useAuthStore } from '../stores/auth'
import { safeRedirect } from '../utils/redirect'

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()

const expired = computed(() => route.query.reason === 'expired')

const email = ref('admin@kudi.test')
const password = ref('password')
const submitting = ref(false)
const fieldErrors = ref<Record<string, string | undefined>>({})
const formError = ref<string | null>(null)

/**
 * The same violet ramp the settlement band uses on the dashboard. Here it is
 * purely decorative — it carries no figures, because nothing real is known
 * before sign-in and inventing numbers on a login screen would misrepresent
 * the account behind it.
 */
const STRIPS = [15, 14, 12, 11, 9, 8, 8, 7, 16].map((width, index, all) => {
  const t = index / (all.length - 1)
  const channel = (from: number, to: number) => Math.round(from + (to - from) * t)
  return {
    width,
    colour: `rgb(${channel(18, 176)} ${channel(1, 169)} ${channel(61, 205)})`,
  }
})

const CAPABILITIES = [
  ['Wallets', 'Every successful payment credits a merchant wallet, net of the processing fee.'],
  ['Settlements', 'One run clears every positive balance into a dated, referenced batch.'],
  ['Reporting', 'Filter the full ledger by date and status, down to a single reference.'],
]

async function submit(): Promise<void> {
  submitting.value = true
  fieldErrors.value = {}
  formError.value = null

  try {
    await auth.login(email.value, password.value)
    router.push(safeRedirect(route.query.redirect))
  } catch (error) {
    const normalized = toApiError(error)
    if (normalized instanceof ApiValidationError) {
      fieldErrors.value = {
        email: normalized.first('email'),
        password: normalized.first('password'),
      }
    } else {
      formError.value = normalized.message
    }
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <main class="grid min-h-screen lg:grid-cols-2">
    <!-- Sign-in -->
    <div class="flex items-center justify-center px-6 py-14 sm:px-10">
      <div class="w-full max-w-sm">
        <KudiLogo class="text-xl" />

        <h1 class="font-display mt-10 text-2xl font-semibold text-ink">Sign in</h1>
        <p class="mt-1.5 text-sm text-muted">Merchant Wallet &amp; Settlement Dashboard</p>

        <form class="mt-8 space-y-4" novalidate @submit.prevent="submit">
          <p
            v-if="expired"
            class="rounded-lg border border-warn/25 bg-warn-wash px-3 py-2 text-sm text-ink-soft"
            role="status"
          >
            Your session timed out. Sign in to pick up where you left off.
          </p>

          <BaseInput
            v-model="email"
            label="Email"
            type="email"
            placeholder="you@kudi.test"
            required
            :error="fieldErrors.email"
          />
          <BaseInput
            v-model="password"
            label="Password"
            type="password"
            required
            :error="fieldErrors.password"
          />

          <p v-if="formError" class="text-sm text-bad" role="alert">{{ formError }}</p>

          <BaseButton type="submit" class="w-full" :loading="submitting">Sign in</BaseButton>
        </form>

        <p class="mt-6 border-t border-line pt-4 text-xs text-muted">
          Demo credentials are prefilled — just sign in.
        </p>
      </div>
    </div>

    <!-- What is behind the door. Hidden on small screens, where the form is the
         only thing worth the space. -->
    <aside class="hidden border-l border-line px-12 py-14 lg:flex lg:items-center">
      <div class="max-w-md">
        <p class="font-display text-2xl leading-snug font-semibold text-balance text-ink">
          Payments in, payouts out, and the float in between.
        </p>

        <div class="mt-7 flex h-2 gap-px overflow-hidden rounded-full" aria-hidden="true">
          <span
            v-for="(strip, index) in STRIPS"
            :key="index"
            :style="{ width: `${strip.width}%`, background: strip.colour }"
          />
        </div>

        <dl class="mt-9 space-y-px">
          <div v-for="[term, detail] in CAPABILITIES" :key="term" class="border-t border-line py-4">
            <dt class="text-sm font-semibold text-ink">{{ term }}</dt>
            <dd class="mt-1 text-sm leading-relaxed text-muted">{{ detail }}</dd>
          </div>
        </dl>
      </div>
    </aside>
  </main>
</template>
