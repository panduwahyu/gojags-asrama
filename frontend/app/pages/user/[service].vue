<script setup lang="ts">
definePageMeta({ role: 'user' })
const route = useRoute()
if (!['laundry', 'catering'].includes(String(route.params.service))) throw createError({ statusCode: 404, statusMessage: 'Halaman tidak ditemukan' })
useHead({ title: () => `${route.params.service === 'laundry' ? 'Laundry' : 'Catering'} · Gojags Asrama` })
</script>

<template><div /></template>
