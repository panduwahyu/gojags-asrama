export const POLL_INTERVAL_MS = 15_000

type PollingOptions = {
  pausedWhen?: () => boolean
}

export function usePolling(refresh: () => unknown, options: PollingOptions = {}) {
  let timer: ReturnType<typeof setInterval> | undefined

  onMounted(() => {
    timer = setInterval(() => {
      const paused = options.pausedWhen ? options.pausedWhen() : false
      if (!document.hidden && !paused) {
        refresh()
      }
    }, POLL_INTERVAL_MS)
  })

  onBeforeUnmount(() => {
    if (timer !== undefined) {
      clearInterval(timer)
    }
  })
}
