<?php

namespace App\Http\Controllers;

use App\Http\Requests\RateCateringMenuRequest;
use App\Models\CateringMenu;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class CateringRatingController extends Controller
{
    public function update(RateCateringMenuRequest $request, CateringMenu $menu): JsonResponse
    {
        DB::transaction(function () use ($request, $menu) {
            $lockedMenu = CateringMenu::lockForUpdate()->findOrFail($menu->id);

            $lockedMenu->ratings()->updateOrCreate(
                ['user_id' => $request->user()->id],
                ['rating' => $request->validated('rating')]
            );
        }, 3);

        return $this->message('Rating catering berhasil disimpan.');
    }
}
