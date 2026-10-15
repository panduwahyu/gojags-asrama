<?php

namespace App\Http\Controllers;

use App\Http\Requests\RateCateringMenuRequest;
use App\Models\CateringMenu;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CateringRatingController extends Controller
{
    public function update(RateCateringMenuRequest $request, CateringMenu $menu): JsonResponse
    {
        DB::transaction(function () use ($request, $menu) {
            $lockedMenu = CateringMenu::lockForUpdate()->findOrFail($menu->id);

            if (now()->lt($lockedMenu->served_at)) {
                throw ValidationException::withMessages([
                    'rating' => 'Menu belum dihidangkan sehingga belum bisa dinilai.',
                ]);
            }

            if (now()->gt($lockedMenu->ratingClosesAt())) {
                throw ValidationException::withMessages([
                    'rating' => 'Masa penilaian menu ini sudah berakhir.',
                ]);
            }

            $lockedMenu->ratings()->updateOrCreate(
                ['user_id' => $request->user()->id],
                ['rating' => $request->validated('rating')]
            );
        }, 3);

        return $this->message('Rating catering berhasil disimpan.');
    }
}
