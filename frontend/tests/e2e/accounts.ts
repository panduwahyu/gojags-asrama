export const ACCOUNTS = {
  admin: {
    email: 'admin@example.id',
    password: 'admin123',
    home: '/admin',
  },
  resident: {
    email: 'user@example.id',
    password: 'user123',
    home: '/resident',
  },
  user1: {
    email: 'user1@example.id',
    password: 'user123',
    home: '/resident',
  },
} as const

export const STATE: Record<keyof typeof ACCOUNTS, string> = {
  admin: 'tests/e2e/.auth/admin.json',
  resident: 'tests/e2e/.auth/resident.json',
  user1: 'tests/e2e/.auth/user1.json',
}
