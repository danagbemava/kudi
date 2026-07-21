<script setup lang="ts">
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ApiValidationError, toApiError } from '../api/client'
import BaseButton from '../components/ui/BaseButton.vue'
import BaseInput from '../components/ui/BaseInput.vue'
import KudiLogo from '../components/ui/KudiLogo.vue'
import { useAuthStore } from '../stores/auth'

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()

const email = ref('admin@kudi.test')
const password = ref('password')
const submitting = ref(false)
const fieldErrors = ref<Record<string, string | undefined>>({})
const formError = ref<string | null>(null)

async function submit(): Promise<void> {
  submitting.value = true
  fieldErrors.value = {}
  formError.value = null

  try {
    await auth.login(email.value, password.value)
    router.push((route.query.redirect as string) ?? '/')
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
  <main class="flex min-h-screen items-center justify-center bg-canvas px-4">
    <div class="w-full max-w-sm">
      <div class="mb-8 text-center">
        <h1><KudiLogo class="text-2xl" /></h1>
        <p class="mt-2 text-sm text-slate-500">Merchant Wallet &amp; Settlement Dashboard</p>
      </div>

      <div class="overflow-hidden rounded-2xl border border-line bg-white shadow-sm">
        <div class="kente-thread" aria-hidden="true" />
        <form class="space-y-4 p-6" novalidate @submit.prevent="submit">
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

          <p v-if="formError" class="text-sm text-red-600" role="alert">{{ formError }}</p>

          <BaseButton type="submit" class="w-full" :loading="submitting">Sign in</BaseButton>

          <p class="text-center text-xs text-slate-500">
            Demo credentials are prefilled — just sign in.
          </p>
        </form>
      </div>
    </div>
  </main>
</template>
