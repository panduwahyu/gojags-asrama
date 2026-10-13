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
    overline: 'KAMAR',
    linkLabel: 'Lihat kamar',
    title: 'Kamar yang nyaman',
    description: 'Informasi kamar, ketersediaan, dan status hunian asrama.',
    label: 'Total kamar',
    color: 'bg-orange-50 dark:bg-orange-950/40 text-orange-400',
  },
  {
    key: 'laundry_orders' as const,
    icon: 'laundry',
    to: '/admin/laundry',
    overline: 'LAUNDRY',
    linkLabel: 'Lihat laundry',
    title: 'Pakaian bersih, tanpa repot',
    description: 'Catat kebutuhan laundry dan pantau proses pencucian Anda.',
    label: 'Pesanan laundry',
    color: 'bg-blue-50 dark:bg-blue-950/50 text-blue-400',
  },
  {
    key: 'catering_menus' as const,
    icon: 'catering',
    to: '/admin/catering',
    overline: 'CATERING',
    linkLabel: 'Lihat catering',
    title: 'Hidangan untuk setiap hari',
    description: 'Temukan jadwal catering dan informasi menu harian asrama.',
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
      class="mt-6 grid gap-4 md:grid-cols-3"
      aria-label="Ringkasan layanan"
    >
      <NuxtLink
        v-for="item in services"
        :key="item.key"
        :to="item.to"
        class="card flex items-center gap-4 p-5"
      >
        <span
          :class="item.color"
          class="grid size-12 place-items-center rounded-xl"
        >
          <AppIcon :name="item.icon" />
        </span>
        <div>
          <p class="text-xs text-slate-500 dark:text-slate-400">
            {{ item.label }}
          </p>
          <strong class="mt-1 block text-3xl tracking-tight">
            {{ status === 'pending' ? '…' : (data?.data[item.key] ?? '—') }}
            <span class="ml-2 text-[10px] font-normal tracking-normal text-slate-400">
              Data tercatat
            </span>
          </strong>
        </div>
        <span class="ml-auto self-start text-slate-300 dark:text-slate-500">↗</span>
      </NuxtLink>
    </section>

    <div class="mb-6 mt-10">
      <p class="overline">
        LAYANAN ASRAMA
      </p>
      <h2 class="mt-2 text-2xl font-bold tracking-tight">
        Semua kebutuhan, satu tempat
      </h2>
      <p class="muted mt-2">
        Layanan yang mendukung kenyamanan Anda selama di asrama.
      </p>
    </div>

    <section class="grid gap-5 md:grid-cols-3">
      <DashboardServiceCard
        v-for="(item, index) in services"
        :key="item.key"
        :to="item.to"
        :icon="item.icon"
        :overline="item.overline"
        :title="item.title"
        :description="item.description"
        :link-label="item.linkLabel"
        :color="item.color"
        :index="index"
      />
    </section>
  </div>
</template>
