import type { Role } from '~/types/session'

export type NavItem = {
  label: string
  icon: string
  to: string
}

const ADMIN_MENUS: NavItem[] = [
  { label: 'Beranda', icon: 'home', to: '/admin' },
  { label: 'Kamar', icon: 'room', to: '/admin/rooms' },
  { label: 'Penghuni', icon: 'users', to: '/admin/residents' },
  { label: 'Laundry', icon: 'laundry', to: '/admin/laundry' },
  { label: 'Catering', icon: 'catering', to: '/admin/catering' },
  { label: 'Akun', icon: 'key', to: '/admin/account' },
]

const RESIDENT_MENUS: NavItem[] = [
  { label: 'Beranda', icon: 'home', to: '/resident' },
  { label: 'Kamar', icon: 'room', to: '/resident/room' },
  { label: 'Laundry', icon: 'laundry', to: '/resident/laundry' },
  { label: 'Catering', icon: 'catering', to: '/resident/catering' },
  { label: 'Akun', icon: 'key', to: '/resident/account' },
]

export function menusFor(role?: Role): NavItem[] {
  return role === 'admin' ? ADMIN_MENUS : RESIDENT_MENUS
}

export function homePathFor(role?: Role): string {
  return role === 'admin' ? '/admin' : '/resident'
}

export function requiredRole(path: string): Role | null {
  if (path === '/admin' || path.startsWith('/admin/')) {
    return 'admin'
  }
  if (path === '/resident' || path.startsWith('/resident/')) {
    return 'resident'
  }
  return null
}
