export type Paginated<T> = {
  data: T[]
  meta: {
    current_page: number
    last_page: number
    total: number
  }
}

export type ApiMessage = {
  message: string
}

export type Tone = 'info' | 'success' | 'warning' | 'danger' | 'neutral'
