<?php

namespace App\Http\Requests;

use App\Enums\MealTime;
use App\Support\DateFormat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCateringMenuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'menu_number' => ['required', 'integer', 'min:1', 'max:10000'],
            'dish_name' => ['required', 'string', 'max:255'],
            'meal_time' => ['required', Rule::enum(MealTime::class)],
            'provider' => ['required', 'string', 'max:255'],
            'served_at' => ['required', DateFormat::UTC],
        ];
    }
}
