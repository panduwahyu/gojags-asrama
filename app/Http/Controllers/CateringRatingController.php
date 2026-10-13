<?php

namespace App\Http\Controllers;

use App\Http\Requests\RateCateringMenuRequest;
use App\Models\CateringMenu;
use Illuminate\Http\JsonResponse;

class CateringRatingController extends Controller
{
    public function update(RateCateringMenuRequest $request, CateringMenu $menu): JsonResponse
    {
        $menu->ratings()->updateOrCreate(
            ['user_id' => $request->user()->id],
            ['rating' => $request->validated('rating')]
        );

        return $this->message('Rating catering berhasil disimpan.');
    }
}
