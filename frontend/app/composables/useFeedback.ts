const focusTargets = new Map<number, HTMLElement>()
let nextId = 0

export function useFeedback() {
  const queue = useState<{ id: number; message: string; kind: 'success' | 'error'; title: string }[]>('asrama-feedback', () => [])
  function show(message: string, kind: 'success' | 'error' = 'success', title?: string, focusTarget?: HTMLElement | null) {
    const id = ++nextId
    if (focusTarget) focusTargets.set(id, focusTarget)
    queue.value.push({ id, message, kind, title: title || (kind === 'success' ? 'Berhasil' : 'Gagal') })
  }
  function dismiss() {
    const item = queue.value.shift()
    if (item) focusTargets.delete(item.id)
  }
  return { current: computed(() => queue.value[0] ?? null), focusTarget: () => queue.value[0] ? focusTargets.get(queue.value[0].id) : undefined, show, dismiss }
}
