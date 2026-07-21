import { onUnmounted, ref } from 'vue'

/**
 * Lets a route replace the shell heading with something specific — "Labadi
 * Beach Supplies" rather than the generic "Merchant" from the route meta.
 */
const override = ref<string | null>(null)

export function usePageTitle() {
  return {
    override,
    set(title: string | null) {
      override.value = title
    },
  }
}

/** For pages: sets the heading and clears it again on navigate away. */
export function useOwnedPageTitle() {
  const { set } = usePageTitle()
  onUnmounted(() => set(null))
  return { set }
}
