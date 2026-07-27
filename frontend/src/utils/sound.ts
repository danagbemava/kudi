import { ref } from 'vue'

const STORAGE_KEY = 'kudi:toast-sound'

/**
 * Sound is on by default but must be switchable: there is no `prefers-reduced-
 * sound` media query to read, so the only way to respect someone working in a
 * quiet room is to give them a control and remember the answer.
 */
export const soundEnabled = ref(readPreference())

function readPreference(): boolean {
  try {
    return localStorage.getItem(STORAGE_KEY) !== 'off'
  } catch {
    // Private browsing can throw on access rather than returning null.
    return true
  }
}

export function setSoundEnabled(enabled: boolean): void {
  soundEnabled.value = enabled
  try {
    localStorage.setItem(STORAGE_KEY, enabled ? 'on' : 'off')
  } catch {
    // A preference we cannot persist is still worth honouring for this session.
  }
}

// Each note is [frequency in Hz, start offset in seconds].
const NOTES: Record<'success' | 'error' | 'info', [number, number][]> = {
  // Rising pair: resolved, finished.
  success: [
    [660, 0],
    [880, 0.08],
  ],
  // Falling pair, lower: something needs attention.
  error: [
    [420, 0],
    [300, 0.1],
  ],
  // Single soft note, quieter than the other two.
  info: [[560, 0]],
}

let context: AudioContext | null = null
let lastPlayedAt = 0

/**
 * A short synthesised chime rather than an audio file: no asset to ship, no
 * request to make, and the tone can differ per outcome.
 *
 * Playing is best-effort. Browsers block audio until the page has been
 * interacted with, and every toast here follows a click, but a rejected or
 * unavailable AudioContext must never take down the toast itself.
 */
export function playToastSound(tone: 'success' | 'error' | 'info'): void {
  if (!soundEnabled.value) return

  // A burst of toasts should not machine-gun.
  const now = Date.now()
  if (now - lastPlayedAt < 400) return
  lastPlayedAt = now

  try {
    const Ctor =
      window.AudioContext ??
      (window as { webkitAudioContext?: typeof AudioContext }).webkitAudioContext
    if (!Ctor) return
    context ??= new Ctor()
    if (context.state === 'suspended') void context.resume()

    for (const [frequency, offset] of NOTES[tone]) {
      const oscillator = context.createOscillator()
      const gain = context.createGain()
      oscillator.type = 'sine'
      oscillator.frequency.value = frequency

      const start = context.currentTime + offset
      const peak = tone === 'info' ? 0.025 : 0.045
      // Ramped rather than switched, so it reads as a chime and not a click.
      gain.gain.setValueAtTime(0.0001, start)
      gain.gain.exponentialRampToValueAtTime(peak, start + 0.015)
      gain.gain.exponentialRampToValueAtTime(0.0001, start + 0.18)

      oscillator.connect(gain).connect(context.destination)
      oscillator.start(start)
      oscillator.stop(start + 0.2)
    }
  } catch {
    // Audio is a nicety; the toast is the actual feedback.
  }
}
