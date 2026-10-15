<script setup lang="ts">
useHead({ title: 'Beranda · Gojags Asrama' })

const { user } = useSession()

const residentServices = [
  {
    to: '/resident/room',
    icon: 'kamar',
    overline: 'KAMAR',
    title: 'Kamar & Check-in',
    description: 'Alokasi kamar, check-in, dan perizinan.',
    linkLabel: 'Buka kamar',
    color: 'bg-indigo-50 dark:bg-indigo-950/40 text-indigo-500',
  },
  {
    to: '/resident/laundry',
    icon: 'laundry',
    overline: 'LAUNDRY',
    title: 'Cuci Pakaian',
    description: 'Pengajuan cuci dan pantau status laundry.',
    linkLabel: 'Buka laundry',
    color: 'bg-blue-50 dark:bg-blue-950/50 text-blue-400',
  },
  {
    to: '/resident/catering',
    icon: 'catering',
    overline: 'CATERING',
    title: 'Menu & Rating',
    description: 'Jadwal hidangan dan penilaian makanan.',
    linkLabel: 'Buka catering',
    color: 'bg-green-50 dark:bg-green-950/50 text-green-500',
  },
]
</script>

<template>
  <div>
    <DashboardHero :resident-name="user?.name ?? ''" />

    <section class="mt-8 grid gap-5 md:grid-cols-3">
      <DashboardServiceCard
        v-for="(item, index) in residentServices"
        :key="item.to"
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
