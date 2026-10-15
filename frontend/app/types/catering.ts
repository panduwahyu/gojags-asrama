export type MealTime = 'breakfast' | 'lunch' | 'dinner'

export type CateringMenu = {
  id: number
  menu_number: number
  dish_name: string
  meal_time: MealTime
  meal_time_label: string
  provider: string
  served_at: string
  rating_average: number | null
  rating_count: number
  my_rating: number | null
  can_rate: boolean
}

export type CateringMenuInput = {
  menu_number: number
  dish_name: string
  meal_time: MealTime
  provider: string
  served_at: string
}
