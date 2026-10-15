<?php

namespace App\Http\Requests;

use App\Enums\CateringStatus;
use App\Enums\MealTime;
use App\Support\DateFormat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCateringMenuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'menu_number' => ['sometimes', 'integer', 'min:1', 'max:10000'],
            'dish_name' => ['sometimes', 'string', 'max:255'],
            'meal_time' => ['sometimes', Rule::enum(MealTime::class)],
            'provider' => ['sometimes', 'string', 'max:255'],
            'served_at' => ['sometimes', DateFormat::UTC],
            'status' => ['sometimes', Rule::enum(CateringStatus::class)],
        ];
    }
}
