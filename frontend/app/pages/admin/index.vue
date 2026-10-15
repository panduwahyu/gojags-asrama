<script setup lang="ts">
type DashboardCounts = {
  rooms: number
  laundry_orders: number
  catering_menus: number
}

useHead({ title: 'Beranda · Gojags Asrama' })

const api = useApi()
const { data, status, error, refresh } = await useAsyncData('dashboard', () =>
  api.get<{ data: DashboardCounts }>('dashboard'),
)

const services = [
  {
    key: 'rooms' as const,
    icon: 'kamar',
    to: '/admin/rooms',
    label: 'Total kamar',
    color: 'bg-indigo-50 dark:bg-indigo-950/40 text-indigo-500',
  },
  {
    key: 'laundry_orders' as const,
    icon: 'laundry',
    to: '/admin/laundry',
    label: 'Pesanan laundry',
    color: 'bg-blue-50 dark:bg-blue-950/50 text-blue-400',
  },
  {
    key: 'catering_menus' as const,
    icon: 'catering',
    to: '/admin/catering',
    label: 'Jadwal catering',
    color: 'bg-green-50 dark:bg-green-950/50 text-green-500',
  },
]
</script>

<template>
  <div>
    <DashboardHero />

    <div
      v-if="error"
      role="alert"
      class="mt-5 rounded-lg bg-red-50 p-4 text-sm text-red-700
        dark:bg-red-950/50 dark:text-red-300"
    >
      Data tidak dapat dimuat. Pastikan Laravel berjalan.
      <button
        class="underline"
        @click="refresh()"
      >
        Coba lagi
      </button>
    </div>

    <section
      class="mt-8 grid gap-5 md:grid-cols-3"
      aria-label="Ringkasan layanan"
    >
      <NuxtLink
        v-for="item in services"
        :key="item.key"
        :to="item.to"
        class="card group relative flex items-center gap-5 p-6 transition-all duration-200
          hover:-translate-y-1 hover:border-indigo-300 hover:shadow-lg dark:hover:border-indigo-800"
      >
        <span
          :class="item.color"
          class="grid size-14 place-items-center rounded-2xl shadow-xs transition group-hover:scale-105"
        >
          <AppIcon :name="item.icon" class="size-7" />
        </span>
        <div class="flex-1">
          <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
            {{ item.label }}
          </p>
          <strong class="mt-1.5 block text-3xl font-black tracking-tight text-slate-900 dark:text-white">
            {{ status === 'pending' ? '…' : (data?.data[item.key] ?? '—') }}
          </strong>
        </div>
        <span
          class="text-slate-400 transition group-hover:translate-x-0.5 group-hover:-translate-y-0.5
            group-hover:text-indigo-600 dark:text-slate-500 dark:group-hover:text-indigo-400"
        >
          ↗
        </span>
      </NuxtLink>
    </section>
  </div>
</template>
