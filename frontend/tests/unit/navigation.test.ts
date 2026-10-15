import { describe, expect, it } from 'vitest'
import { homePathFor, menusFor, requiredRole } from '../../app/utils/navigation'

describe('navigation utils', () => {
  describe('requiredRole', () => {
    it('mengembalikan admin untuk rute /admin dan subrutenya', () => {
      expect(requiredRole('/admin')).toBe('admin')
      expect(requiredRole('/admin/')).toBe('admin')
      expect(requiredRole('/admin/rooms')).toBe('admin')
      expect(requiredRole('/admin/laundry')).toBe('admin')
      expect(requiredRole('/admin/catering')).toBe('admin')
    })

    it('mengembalikan resident untuk rute /resident dan subrutenya', () => {
      expect(requiredRole('/resident')).toBe('resident')
      expect(requiredRole('/resident/')).toBe('resident')
      expect(requiredRole('/resident/room')).toBe('resident')
      expect(requiredRole('/resident/laundry')).toBe('resident')
      expect(requiredRole('/resident/catering')).toBe('resident')
    })

    it('mengembalikan null untuk rute publik atau rute yang tidak dikenal', () => {
      expect(requiredRole('/')).toBeNull()
      expect(requiredRole('/login')).toBeNull()
      expect(requiredRole('/administrator')).toBeNull()
      expect(requiredRole('/residential')).toBeNull()
    })
  })

  describe('menusFor', () => {
    it('mengembalikan menu admin saat role admin', () => {
      const menus = menusFor('admin')
      expect(menus.map(m => m.to)).toEqual([
        '/admin',
        '/admin/rooms',
        '/admin/laundry',
        '/admin/catering',
      ])
    })

    it('mengembalikan menu resident saat role resident atau undefined', () => {
      const residentMenus = menusFor('resident')
      expect(residentMenus.map(m => m.to)).toEqual([
        '/resident',
        '/resident/room',
        '/resident/laundry',
        '/resident/catering',
      ])

      const defaultMenus = menusFor(undefined)
      expect(defaultMenus).toEqual(residentMenus)
    })
  })

  describe('homePathFor', () => {
    it('mengembalikan /admin untuk role admin', () => {
      expect(homePathFor('admin')).toBe('/admin')
    })

    it('mengembalikan /resident untuk role resident', () => {
      expect(homePathFor('resident')).toBe('/resident')
    })

    it('mengembalikan /resident saat role undefined', () => {
      expect(homePathFor(undefined)).toBe('/resident')
    })
  })
})
