<script setup lang="ts">
import type { Category, PageData } from '~/composables/useAsrama'
const route = useRoute()
const category = computed(() => route.params.category as Category)
if (!['kamar', 'laundry', 'catering'].includes(category.value)) throw createError({ statusCode: 404, statusMessage: 'Halaman tidak ditemukan' })
definePageMeta({ key: route => route.path, role: 'admin' })
const title = computed(() => category.value.charAt(0).toUpperCase() + category.value.slice(1))
useHead({ title: () => `${title.value} · Gojags Asrama` })
const { user, mutate, errorMessage } = useAsrama()
const descriptions = { kamar: 'Kelola informasi dan ketersediaan kamar asrama.', laundry: 'Catat pesanan dan pantau status laundry.', catering: 'Atur menu dan jadwal catering asrama.' }
const { search, query, page, perPage, parameters, find, sortBy } = useTableSearch()
const { data, status, error: loadError, refresh } = await useFetch<PageData>(() => `/backend/api/asrama/${category.value}`, { query: parameters })
const form = reactive({ name: '', detail: '', status: '', date: new Date().toLocaleDateString('en-CA') })
const statuses = computed(() => data.value?.statuses ?? [])
watch(statuses, (values) => { if (!form.status) form.status = values[0] || '' }, { immediate: true })
const error = ref('')
const success = ref('')
const busy = ref(false)
async function create() {
  busy.value = true; error.value = ''; success.value = ''
  try { await mutate(category.value, { ...form }); form.name = ''; form.detail = ''; success.value = 'Data berhasil ditambahkan.'; await refresh() }
  catch (e) { error.value = errorMessage(e) }
  finally { busy.value = false }
}
async function update(id: number, event: Event) {
  const value = (event.target as HTMLSelectElement).value
  busy.value = true; error.value = ''; success.value = ''
  try { await mutate(`${category.value}/${id}`, { status: value }, 'PATCH'); success.value = 'Status berhasil diperbarui.' }
  catch (e) { error.value = errorMessage(e) }
  finally { await refresh(); busy.value = false }
}
</script>

<template>
  <div><div class="mb-7 flex items-center justify-between"><div><p class="overline">LAYANAN ASRAMA</p><h1 class="mt-3 text-3xl font-bold tracking-tight">{{ title }}</h1><p class="muted mt-2">{{ descriptions[category] }}</p></div><span class="hidden rounded-full border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-4 py-2 text-xs text-slate-500 dark:text-slate-400 sm:block">{{ data?.counts[category] ?? '—' }} data tercatat</span></div>
    <p v-if="error || loadError" role="alert" class="mb-5 rounded-lg bg-red-50 dark:bg-red-950/50 p-4 text-sm text-red-700 dark:text-red-300">{{ error || 'Data tidak dapat dimuat. Pastikan backend Laravel berjalan.' }} <button v-if="loadError" class="underline" @click="refresh()">Coba lagi</button></p><p v-if="success" role="status" class="mb-5 rounded-lg bg-green-50 dark:bg-green-950/50 p-4 text-sm text-green-700 dark:text-green-300">{{ success }}</p>
    <details v-if="user && category !== 'kamar'" class="card mb-6 p-5"><summary class="text-sm font-semibold text-orange-600 dark:text-orange-400">＋ Tambah {{ category }}</summary><form class="mt-5" @submit.prevent="create"><div class="grid gap-5 sm:grid-cols-2"><label>Nama pesanan<input v-model="form.name" required maxlength="100"></label><label>{{ category === 'catering' ? 'Menu / jumlah porsi' : 'Detail / keterangan' }}<input v-model="form.detail" required maxlength="255"></label><label>Status<select v-model="form.status" required><option v-for="value in statuses" :key="value">{{ value }}</option></select></label><label>Tanggal<input v-model="form.date" type="date" required></label></div><button class="btn mt-5" :disabled="busy">{{ busy ? 'Menyimpan…' : 'Simpan data' }}</button></form></details>
    <p v-else class="mb-6 rounded-lg bg-orange-50 dark:bg-orange-950/40 p-4 text-sm text-orange-800 dark:text-orange-300">Silakan <NuxtLink to="/login" class="underline">login</NuxtLink> untuk menambahkan data dan memperbarui status.</p>
    <section class="card p-4 md:p-6"><form class="mb-6 flex gap-3" @submit.prevent="find"><input v-model="search" :placeholder="`Cari ${category}…`" :aria-label="`Cari ${category}`"><button class="btn" :disabled="status === 'pending'">Cari</button><button v-if="query" type="button" class="text-xs text-slate-500 dark:text-slate-400" @click="search = ''; find()">Reset</button></form><p v-if="status === 'pending'" role="status" class="py-10 text-center text-sm text-slate-500 dark:text-slate-400">Memuat data…</p><div v-if="data" class="overflow-x-auto"><table class="w-full whitespace-nowrap text-left text-sm"><thead class="bg-slate-50 dark:bg-slate-800 text-xs text-slate-500 dark:text-slate-400"><tr><SortableHeader column="name" label="Nama" :sort="parameters.sort" :direction="parameters.direction" :loading="status === 'pending'" @sort="sortBy" /><SortableHeader column="detail" label="Keterangan" :sort="parameters.sort" :direction="parameters.direction" :loading="status === 'pending'" @sort="sortBy" /><SortableHeader column="date" label="Tanggal" :sort="parameters.sort" :direction="parameters.direction" :loading="status === 'pending'" @sort="sortBy" /><SortableHeader column="status" label="Status" :sort="parameters.sort" :direction="parameters.direction" :loading="status === 'pending'" @sort="sortBy" /></tr></thead><tbody><tr v-for="record in data?.records.data" :key="record.id" class="border-b border-slate-100 dark:border-slate-800"><td class="p-3 font-semibold">{{ record.name }}</td><td class="p-3 text-slate-500 dark:text-slate-400">{{ record.detail }}</td><td class="p-3 text-slate-500 dark:text-slate-400">{{ record.date }}</td><td class="p-3"><select v-if="user" :value="record.status" :disabled="busy" :aria-label="`Status ${record.name}`" @change="update(record.id, $event)"><option v-for="value in statuses" :key="value">{{ value }}</option></select><span v-else class="rounded-full bg-orange-50 dark:bg-orange-950/40 px-3 py-1 text-xs text-orange-700 dark:text-orange-300">{{ record.status }}</span></td></tr><tr v-if="!data?.records.data.length"><td colspan="4" class="py-14 text-center"><AsramaIcon :name="category" class="mx-auto size-12! text-orange-300" /><h2 class="mt-4 font-semibold">{{ query ? 'Data tidak ditemukan' : `Belum ada data ${category}` }}</h2><p class="mt-2 text-xs text-slate-400 dark:text-slate-400">{{ query ? 'Coba gunakan kata pencarian lain.' : 'Data yang ditambahkan pengelola akan tampil di sini.' }}</p></td></tr></tbody></table></div><TablePagination v-if="data" v-model:page="page" v-model:per-page="perPage" :current="data.records.current_page" :last="data.records.last_page" :loading="status === 'pending'" /></section>
  </div>
</template>
