<?php

namespace App\Http\Controllers;

use App\Enums\CateringStatus;
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
            ->search(['menu_number', 'provider'])
            ->sort([
                'menu_number',
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
        $menu->forceFill([
            'status' => CateringStatus::from($request->validated('status')),
        ])->save();

        return $this->message('Status berhasil diperbarui.');
    }
}
