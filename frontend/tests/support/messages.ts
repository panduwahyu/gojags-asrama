/**
 * Pesan yang dikirim backend, disalin huruf demi huruf dari kode Laravel (controller, action, request).
 * Spec tidak boleh mengarang pesan sendiri; bila pesan backend berubah, ubah di sini.
 */
export const MESSAGES = {
  // Sesi
  INVALID_CREDENTIALS: 'Email atau kata sandi tidak sesuai.',
  FORBIDDEN: 'Anda tidak memiliki izin untuk halaman ini.',
  SERVER_ERROR: 'Server Error',
  NOT_STAYING: 'Fitur ini hanya untuk penghuni yang sedang menginap (sudah check-in).',

  // Catering
  CATERING_ADDED: 'Menu catering berhasil ditambahkan.',
  CATERING_UPDATED: 'Menu catering berhasil diperbarui.',
  CATERING_DELETED: 'Menu catering berhasil dihapus.',
  CATERING_RATED: 'Rating catering berhasil disimpan.',
  CATERING_RATING_CLOSED: 'Masa penilaian menu ini sudah berakhir.',
  CATERING_DUPLICATE: 'Menu untuk waktu makan ini pada tanggal yang sama sudah ada.',

  // Keluhan
  COMPLAINT_SENT: 'Keluhan asrama berhasil dikirim.',
  COMPLAINT_RESOLVED: 'Keluhan ditandai Resolved.',
  COMPLAINT_IN_PROGRESS: 'Keluhan ditandai Sedang ditangani.',
  COMPLAINT_REOPENED: 'Keluhan dibuka kembali.',

  // Laundry
  LAUNDRY_SENT: 'Pengajuan laundry berhasil dikirim.',
  LAUNDRY_STATUS_UPDATED: 'Status berhasil diperbarui.',
  LAUNDRY_CANCELLED: 'Pesanan laundry dibatalkan.',

  // Izin keluar
  PERMIT_SENT: 'Izin keluar berhasil diajukan.',
  PERMIT_UPDATED: 'Pengajuan izin berhasil diperbarui.',
  PERMIT_APPROVED: 'Izin keluar disetujui.',
  PERMIT_REJECTED: 'Izin keluar ditolak.',
  PERMIT_CANCELLED: 'Pengajuan izin dibatalkan.',
  PERMIT_OVERLAP: 'Waktu izin bertabrakan dengan izin lain yang masih aktif.',

  // Kamar dan riwayat menginap
  CHECK_IN_SUCCESS: 'Check-in berhasil.',
  CHECK_OUT_SUCCESS: 'Check-out berhasil. Slot kamar telah dilepaskan.',
  CHECK_OUT_CANCELLED: 'Check-out dibatalkan. Penghuni kembali ke kamarnya.',
  ALREADY_CHECKED_IN: 'Anda sudah check-in.',
  PROFILE_SAVED: 'Profil alokasi berhasil disimpan.',
  COHORT_FORMAT: 'Angkatan hanya boleh berisi huruf, angka, dan spasi.',
  ROOM_ACTIVATED: 'Kamar diaktifkan.',
  ROOM_DEACTIVATED: 'Kamar dinonaktifkan.',
  ROOM_HAS_RESIDENTS: 'Kamar masih berpenghuni. Cabut alokasi atau pindahkan penghuni terlebih dahulu.',
  PLACEMENT_NEEDS_CONFIRMATION: 'Penempatan perlu dikonfirmasi.',
  UNASSIGNED: 'Alokasi kamar dicabut.',

  // Akun
  RESIDENT_CREATED: 'Akun penghuni berhasil dibuat.',
  PASSWORD_RESET: 'Kata sandi penghuni diatur ulang.',
  PASSWORD_CHANGED: 'Kata sandi berhasil diperbarui.',
  CURRENT_PASSWORD_WRONG: 'Kata sandi saat ini tidak sesuai.',

  // Pesan sisi klien (frontend/app/utils/errors.ts)
  CLIENT_NOT_FOUND: 'Data tidak ditemukan. Mungkin sudah diubah atau dihapus.',
  CLIENT_SERVER_ERROR: 'Terjadi kesalahan di server. Coba kembali sebentar lagi.',
  CLIENT_NETWORK: 'Tidak dapat menghubungi server. Periksa koneksi Anda.',
} as const

export function allocationSummary(allocated: number, incomplete: number, waiting: number): string {
  return `${allocated} user dialokasikan. ${incomplete} profil belum lengkap. ${waiting} user menunggu kamar yang sesuai.`
}

export function importSummary(count: number): string {
  return `${count} akun penghuni berhasil diimpor.`
}
