const WIB = 'Asia/Jakarta'
const WIB_OFFSET_MS = 7 * 60 * 60 * 1000

/** "13 Okt 2026, 14.30 WIB" */
export function formatWib(iso: string, timeStyle: 'short' | 'medium' = 'short'): string {
  const formatter = new Intl.DateTimeFormat('id-ID', {
    dateStyle: 'medium',
    timeStyle,
    timeZone: WIB,
  })
  return `${formatter.format(new Date(iso))} WIB`
}

/** Nilai <input type="datetime-local"> (jam WIB) -> ISO UTC untuk API. */
export function wibInputToIso(local: string): string {
  return new Date(`${local}:00+07:00`).toISOString()
}

/** ISO UTC dari API -> nilai <input type="datetime-local"> dalam WIB. */
export function isoToWibInput(iso: string): string {
  return new Date(new Date(iso).getTime() + WIB_OFFSET_MS).toISOString().slice(0, 16)
}
