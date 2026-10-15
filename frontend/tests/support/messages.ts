export const MESSAGES = {
  // Catering
  CATERING_ADDED: 'Menu catering berhasil ditambahkan.',
  CATERING_UPDATED: 'Menu catering berhasil diperbarui.',
  CATERING_DELETED: 'Menu catering berhasil dihapus.',
  CATERING_RATED: 'Rating catering berhasil disimpan.',
  CATERING_RATING_CLOSED: 'Masa penilaian menu ini sudah berakhir.',

  // Complaints
  COMPLAINT_SENT: 'Keluhan berhasil dikirim.',
  COMPLAINT_RESOLVED: 'Keluhan berhasil ditandai selesai.',

  // Auth / Session
  INVALID_CREDENTIALS: 'Email atau kata sandi salah.',
  FORBIDDEN: 'Forbidden',
  SERVER_ERROR: 'Server error',

  // Laundry
  LAUNDRY_SENT: 'Pengajuan laundry berhasil dikirim.',
  LAUNDRY_STATUS_UPDATED: 'Status berhasil diperbarui.',

  // Leave Permit
  PERMIT_SENT: 'Izin keluar berhasil diajukan.',
  PERMIT_UPDATED: 'Pengajuan izin keluar berhasil diperbarui.',
  PERMIT_APPROVED: 'Izin keluar berhasil disetujui.',

  // Rooms & Stays
  CHECK_IN_SUCCESS: 'Check-in berhasil.',
  CHECK_OUT_SUCCESS: 'Check-out berhasil. Slot kamar telah dilepaskan.',
  ALREADY_CHECKED_IN: 'Anda sudah melakukan check-in.',
  PROFILE_SAVED: 'Profil berhasil disimpan.',
  ALLOCATION_SUMMARY: 'Alokasi selesai: 1 ditempatkan, 0 menunggu kamar sesuai, 0 profil belum lengkap.',
  ALLOCATED_PROFILE_LOCKED: 'Profil penghuni yang sudah dialokasikan ke kamar tidak dapat diubah.',
} as const
