export type Role = 'admin' | 'resident'

export type User = {
  id: number
  name: string
  email: string
  role: Role
}

export type Session = {
  csrfToken: string
  user: User | null
}
