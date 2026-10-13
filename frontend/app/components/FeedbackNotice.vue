<script setup lang="ts">
const props = defineProps<{ message: string }>()
const { show } = useFeedback()
watch(() => props.message, message => { if (message) show(message, 'error') }, { immediate: true })
</script>

<template><span hidden /></template>
