import { setFeeBasisPoints } from '../utils/money'
import { api } from './index'

/**
 * Adopt the server's copy of anything the interface also has to know.
 *
 * Right now that is the processing fee rate, which the payment simulator
 * previews live as you type. Keeping a second hardcoded copy in the frontend
 * meant the preview and the charge agreed only while the default was in force.
 *
 * Best-effort and idempotent: `/api/config` sits behind auth, so a signed-out
 * visitor gets nothing and the built-in default stands until a session exists.
 * Lives in its own module so both the entry point and the auth store can call
 * it without importing each other.
 */
export function syncServerConfig(): void {
  void api
    .config()
    .then((config) => setFeeBasisPoints(config.fee_basis_points))
    .catch(() => {
      /* the default rate is still the right thing to show */
    })
}
