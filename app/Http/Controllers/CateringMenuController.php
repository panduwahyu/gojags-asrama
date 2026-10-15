<?php

namespace App\Http\Controllers;

use App\Enums\MealTime;
use App\Http\Requests\StoreCateringMenuRequest;
use App\Http\Requests\UpdateCateringMenuRequest;
use App\Http\Resources\CateringMenuResource;
use App\Models\CateringMenu;
use App\Support\TableQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CateringMenuController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $menus = TableQuery::for(CateringMenu::withRatingsFor($request->user()))
            ->search(['menu_number', 'dish_name', 'provider'])
            ->status('meal_time', MealTime::class)
            ->sort([
                'menu_number',
                'dish_name',
                'provider',
                'served_at',
                'rating_average',
                'rating_count',
                'my_rating',
            ])
            ->paginate($request);

        return CateringMenuResource::collection($menus);
    }

    public function store(StoreCateringMenuRequest $request): JsonResponse
    {
        CateringMenu::create($request->validated());

        return $this->message('Menu catering berhasil ditambahkan.', 201);
    }

    public function update(UpdateCateringMenuRequest $request, CateringMenu $menu): JsonResponse
    {
        $validated = $request->validated();
        $menu->forceFill($validated)->save();

        $isStatusOnly = array_key_exists('status', $validated) && count($validated) === 1;

        return $this->message($isStatusOnly ? 'Status berhasil diperbarui.' : 'Menu catering berhasil diperbarui.');
    }

    public function destroy(CateringMenu $menu): JsonResponse
    {
        $menu->delete();

        return $this->message('Menu catering berhasil dihapus.');
    }
}
