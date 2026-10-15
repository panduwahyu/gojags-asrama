<script setup lang="ts">
import type { Tone } from '~/types/api'

defineProps<{
  tone: Tone
}>()

const TONE_CLASSES: Record<Tone, string> = {
  info: [
    'border border-sky-200 bg-sky-50 text-sky-700',
    'dark:border-sky-800/60 dark:bg-sky-950/40 dark:text-sky-300',
  ].join(' '),
  success: [
    'border border-emerald-200 bg-emerald-50 text-emerald-700',
    'dark:border-emerald-800/60 dark:bg-emerald-950/40 dark:text-emerald-300',
  ].join(' '),
  warning: [
    'border border-amber-200 bg-amber-50 text-amber-700',
    'dark:border-amber-800/60 dark:bg-amber-950/40 dark:text-amber-300',
  ].join(' '),
  danger: [
    'border border-rose-200 bg-rose-50 text-rose-700',
    'dark:border-rose-800/60 dark:bg-rose-950/40 dark:text-rose-300',
  ].join(' '),
  neutral: [
    'border border-slate-200 bg-slate-50 text-slate-700',
    'dark:border-slate-700/60 dark:bg-slate-800/40 dark:text-slate-300',
  ].join(' '),
}
</script>

<template>
  <span
    class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold tracking-wide"
    :class="TONE_CLASSES[tone]"
  >
    <span class="size-1.5 rounded-full bg-current opacity-75" />
    <slot />
  </span>
</template>
