<?php

namespace App\Http\Controllers;

use App\Models\CateringMenu;
use App\Models\LaundryOrder;
use App\Models\Room;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'data' => [
                'rooms' => Room::count(),
                'laundry_orders' => LaundryOrder::count(),
                'catering_menus' => CateringMenu::count(),
            ],
        ]);
    }
}
