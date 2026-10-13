<script setup lang="ts">
useHead({ title: 'Beranda · Gojags Asrama' })

const { user } = useSession()
</script>

<template>
  <DashboardHero :resident-name="user?.name ?? ''" />
</template>
