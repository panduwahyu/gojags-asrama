<?php

namespace App\Http\Requests;

use App\Enums\MealTime;
use App\Models\CateringMenu;
use App\Support\DateFormat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $menu = $this->route('menu');
            $servedAt = $this->input('served_at', $menu?->served_at);
            $mealTime = $this->input('meal_time', $menu?->meal_time?->value);

            $duplicate = CateringMenu::query()
                ->where('served_on', CateringMenu::servedOnFor($servedAt))
                ->where('meal_time', $mealTime)
                ->when($menu, fn ($query) => $query->whereKeyNot($menu->id))
                ->exists();

            if ($duplicate) {
                $validator->errors()->add('meal_time', 'Menu untuk waktu makan ini pada tanggal yang sama sudah ada.');
            }
        });
    }
}
