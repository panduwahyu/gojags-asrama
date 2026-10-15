<script setup lang="ts">
import type { AllocationResult } from '~/types/room'

defineProps<{
  disabled?: boolean
}>()

const emit = defineEmits<{
  allocated: []
}>()

const api = useApi()
const action = useAction()
const result = shallowRef<AllocationResult | null>(null)

function namesOf(people: { name: string }[]): string {
  return people.map(person => person.name).join(', ')
}

async function allocate() {
  result.value = null
  const ok = await action.run(async () => {
    const response = await api.post<AllocationResult>('rooms/allocate')
    result.value = response
    return response
  })
  if (ok) {
    emit('allocated')
  }
}
</script>

<template>
  <section
    aria-label="Alokasi kamar otomatis"
    class="card mt-6 p-5"
  >
    <h2 class="section-title">
      Alokasi kamar otomatis
    </h2>
    <p class="muted mt-1 text-xs">
      Kamar nonaktif dilewati. Agama dilonggarkan bila tidak ada kamar seagama; laki-laki dan perempuan
      tidak pernah sekamar. Penempatan khusus dilakukan manual di halaman Penghuni.
    </p>

    <AlertMessage
      :error="action.error.value"
      :success="action.success.value"
    />

    <button
      type="button"
      class="btn mt-4"
      :disabled="action.busy.value || disabled"
      @click="allocate"
    >
      {{ action.busy.value ? 'Memproses…' : 'Alokasikan kamar otomatis' }}
    </button>

    <div
      v-if="result"
      class="mt-5 space-y-3 text-sm"
    >
      <div
        v-if="result.incomplete.length"
        class="rounded-xl border border-amber-200 bg-amber-50/80 p-3.5 text-amber-900
          dark:border-amber-800/60 dark:bg-amber-950/40 dark:text-amber-200"
      >
        <p><b>Profil belum lengkap:</b> {{ namesOf(result.incomplete) }}</p>
      </div>
      <div
        v-if="result.waiting.length"
        class="rounded-xl border border-sky-200 bg-sky-50/80 p-3.5 text-sky-900
          dark:border-sky-800/60 dark:bg-sky-950/40 dark:text-sky-200"
      >
        <p><b>Menunggu kamar yang sesuai:</b> {{ namesOf(result.waiting) }}</p>
      </div>
      <details
        v-if="result.allocated.length"
        class="rounded-xl border border-emerald-200 bg-emerald-50/80 p-3.5 text-emerald-950
          dark:border-emerald-800/60 dark:bg-emerald-950/40 dark:text-emerald-100"
      >
        <summary class="cursor-pointer font-bold text-emerald-800 dark:text-emerald-300">
          Hasil penempatan ({{ result.allocated.length }})
        </summary>
        <ul class="mt-2 list-inside list-disc space-y-1 text-xs">
          <li
            v-for="user in result.allocated"
            :key="`${user.id}-${user.room}`"
          >
            {{ user.name }} — kamar {{ user.room }}, lantai {{ user.floor }}
            <span
              v-if="user.notes.length"
              class="font-semibold text-amber-700 dark:text-amber-300"
            >
              (perhatian: {{ user.notes.join(' ') }})
            </span>
          </li>
        </ul>
      </details>
    </div>
  </section>
</template>
