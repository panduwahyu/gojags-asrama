<?php

namespace App\Http\Controllers;

use App\Enums\MealTime;
use App\Http\Requests\ExportRequest;
use App\Http\Requests\StoreCateringMenuRequest;
use App\Http\Requests\UpdateCateringMenuRequest;
use App\Http\Resources\CateringMenuResource;
use App\Models\CateringMenu;
use App\Support\TableExport;
use App\Support\TableQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CateringMenuController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $menus = $this->tableQuery($request)->paginate($request);

        return CateringMenuResource::collection($menus);
    }

    public function store(StoreCateringMenuRequest $request): JsonResponse
    {
        CateringMenu::create($request->validated());

        return $this->message('Menu catering berhasil ditambahkan.', 201);
    }

    public function update(UpdateCateringMenuRequest $request, CateringMenu $menu): JsonResponse
    {
        $menu->fill($request->validated())->save();

        return $this->message('Menu catering berhasil diperbarui.');
    }

    public function destroy(CateringMenu $menu): JsonResponse
    {
        $menu->delete();

        return $this->message('Menu catering berhasil dihapus.');
    }

    private function tableQuery(Request $request): TableQuery
    {
        return TableQuery::for(CateringMenu::withRatingsFor($request->user()))
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
            ]);
    }

    public function export(ExportRequest $request): StreamedResponse
    {
        $rows = $this->tableQuery($request)->export($request)->map(fn (CateringMenu $menu) => [
            $menu->menu_number,
            $menu->dish_name,
            $menu->meal_time->label(),
            $menu->provider,
            TableExport::wib($menu->served_at),
            $menu->rating_average !== null ? round((float) $menu->rating_average, 2) : '',
            (int) $menu->rating_count,
        ]);

        return TableExport::download($request->validated('format'), 'Catering',
            ['No. menu', 'Nama makanan', 'Waktu makan', 'Penyedia', 'Dihidangkan', 'Rata-rata rating', 'Jumlah penilai'], $rows->all());
    }
}
