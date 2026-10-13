const rupiahFormatter = new Intl.NumberFormat('id-ID', {
  style: 'currency',
  currency: 'IDR',
  maximumFractionDigits: 2,
})

export function formatRupiah(value: string | null): string {
  if (value === null || value === '') {
    return 'Belum ditentukan'
  }
  return rupiahFormatter.format(Number(value))
}
