<script setup lang="ts">
const { current, focusTarget, dismiss } = useFeedback()
const dialog = ref<HTMLDialogElement | null>(null)
const ok = ref<HTMLButtonElement | null>(null)
let previousFocus: HTMLElement | null = null
let previousOverflow = ''
watch(current, async feedback => {
  await nextTick()
  if (!dialog.value) return
  if (feedback) {
    if (!dialog.value.open) {
      previousFocus = focusTarget() || (document.activeElement instanceof HTMLElement ? document.activeElement : null)
      previousOverflow = document.body.style.overflow
      document.body.style.overflow = 'hidden'
      dialog.value.showModal()
    }
    ok.value?.focus()
  } else {
    dialog.value.close()
    document.body.style.overflow = previousOverflow
    if (previousFocus?.isConnected) previousFocus.focus()
  }
}, { flush: 'post', immediate: true })
onBeforeUnmount(() => { document.body.style.overflow = previousOverflow })
</script>

<template>
  <Teleport to="body">
    <dialog ref="dialog" aria-modal="true" aria-labelledby="feedback-title" aria-describedby="feedback-message" class="card fixed inset-0 m-auto max-h-[85dvh] w-[calc(100%_-_2rem)] max-w-md overflow-y-auto p-7 text-center text-slate-800 shadow-2xl backdrop:bg-slate-950/60 dark:text-slate-100" @cancel.prevent="dismiss">
      <template v-if="current">
        <div aria-hidden="true" class="mx-auto mb-4 grid size-12 place-items-center rounded-full text-2xl" :class="current.kind === 'success' ? 'bg-green-100 text-green-700 dark:bg-green-950 dark:text-green-300' : 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300'">{{ current.kind === 'success' ? '✓' : '!' }}</div>
        <h2 id="feedback-title" class="text-xl font-bold">{{ current.title }}</h2>
        <p id="feedback-message" class="muted mt-3 whitespace-pre-line break-words">{{ current.message }}</p>
        <button ref="ok" type="button" class="btn mt-6 min-w-24" autofocus @click="dismiss">OK</button>
      </template>
    </dialog>
  </Teleport>
</template>
