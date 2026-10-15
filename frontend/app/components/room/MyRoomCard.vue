<script setup lang="ts">
import type { MyRoom } from '~/types/room'

defineProps<{
  room: MyRoom | null
  loaded: boolean
}>()

const emit = defineEmits<{
  changed: []
}>()

const api = useApi()
const action = useAction()

async function transition(kind: 'check-in' | 'check-out') {
  const ok = await action.run(() => api.post(`my-room/${kind}`))
  if (ok) {
    emit('changed')
  }
}
</script>

<template>
  <div>
    <AlertMessage
      :error="action.error.value"
      :success="action.success.value"
    />

    <section
      v-if="room"
      aria-label="Kamar saya"
      class="card relative mt-6 overflow-hidden p-6 md:p-8"
    >
      <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
          <div class="flex items-center gap-3">
            <span
              class="rounded-lg border border-indigo-200 bg-indigo-50 px-2.5 py-1 text-xs font-bold
                text-indigo-700 dark:border-indigo-800 dark:bg-indigo-950/50 dark:text-indigo-300"
            >
              Lantai {{ room.floor }}
            </span>
            <StatusBadge :tone="room.checked_in_at ? 'success' : 'warning'">
              {{ room.checked_in_at ? 'Sudah check-in' : 'Menunggu check-in' }}
            </StatusBadge>
          </div>
          <h2 class="mt-3 text-3xl font-black tracking-tight text-slate-900 dark:text-white">
            Kamar {{ room.number }}
          </h2>
        </div>

        <div class="hidden sm:flex size-16 items-center justify-center rounded-2xl bg-indigo-50 dark:bg-indigo-950/40">
          <AppIcon name="room" class="size-8 text-indigo-600 dark:text-indigo-400" />
        </div>
      </div>

      <div
        v-if="room.checked_in_at"
        class="mt-5 rounded-xl border border-slate-100 bg-slate-50/70 p-4 text-xs text-slate-600
          dark:border-slate-800/80 dark:bg-slate-900/60 dark:text-slate-300"
      >
        <span class="font-semibold text-slate-700 dark:text-slate-200">Waktu check-in: </span>
        <time :datetime="room.checked_in_at">{{ formatWib(room.checked_in_at, 'medium') }}</time>
      </div>

      <div class="mt-6 flex flex-wrap gap-3">
        <button
          type="button"
          class="btn"
          :disabled="action.busy.value || Boolean(room.checked_in_at)"
          @click="transition('check-in')"
        >
          Check-in
        </button>
        <ConfirmButton
          v-if="room.checked_in_at"
          label="Check-out"
          confirm-label="Ya, check-out"
          :busy="action.busy.value"
          described-as="Check-out dari kamar"
          @confirm="transition('check-out')"
        />
        <button
          v-else
          type="button"
          class="btn-secondary"
          disabled
        >
          Check-out
        </button>
      </div>
    </section>

    <section
      v-else-if="loaded"
      aria-label="Kamar saya"
      class="card mt-6 p-8 text-center"
    >
      <div
        class="mx-auto flex size-16 items-center justify-center rounded-2xl bg-slate-100
          text-slate-400 dark:bg-slate-800/60 dark:text-slate-500"
      >
        <AppIcon name="room" class="size-8" />
      </div>
      <h2 class="mt-4 text-lg font-bold text-slate-900 dark:text-white">
        Belum ada alokasi kamar
      </h2>
      <p class="muted mt-2 text-sm">
        Hubungi admin untuk penempatan kamar.
      </p>
    </section>

    <section
      v-else
      aria-label="Memuat kamar"
      class="card mt-6 animate-pulse p-6 md:p-8"
    >
      <div class="h-6 w-32 rounded-lg bg-slate-200 dark:bg-slate-800/80" />
      <div class="mt-4 h-9 w-48 rounded-lg bg-slate-200 dark:bg-slate-800/80" />
      <div class="mt-6 flex gap-3">
        <div class="h-11 w-28 rounded-xl bg-slate-200 dark:bg-slate-800/80" />
        <div class="h-11 w-28 rounded-xl bg-slate-200 dark:bg-slate-800/80" />
      </div>
    </section>
  </div>
</template>
