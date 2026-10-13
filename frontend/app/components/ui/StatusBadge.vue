<script setup lang="ts">
import type { Tone } from '~/types/api'

defineProps<{
  tone: Tone
}>()

const TONE_CLASSES: Record<Tone, string> = {
  info: 'bg-sky-100 text-sky-700 dark:bg-sky-950 dark:text-sky-300',
  success: 'bg-green-100 text-green-800 dark:bg-green-950 dark:text-green-300',
  warning: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-950 dark:text-yellow-300',
  danger: 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300',
  neutral: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
}
</script>

<template>
  <span
    class="inline-block rounded-full px-3 py-1 text-xs font-semibold"
    :class="TONE_CLASSES[tone]"
  >
    <slot />
  </span>
</template>
