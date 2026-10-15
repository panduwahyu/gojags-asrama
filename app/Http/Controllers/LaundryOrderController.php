<?php

namespace App\Http\Controllers;

use App\Enums\LaundryStatus;
use App\Http\Requests\StoreLaundryOrderRequest;
use App\Http\Requests\UpdateLaundryOrderRequest;
use App\Http\Resources\LaundryOrderResource;
use App\Models\LaundryOrder;
use App\Models\User;
use App\Support\TableQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class LaundryOrderController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $orders = TableQuery::for(LaundryOrder::visibleTo($request->user())->with('user'))
            ->search(['items', 'user.name'])
            ->status('status', LaundryStatus::class)
            ->sort([
                'items',
                'set_count',
                'created_at',
                'cost',
                'user_name' => User::nameOf('laundry_orders'),
            ])
            ->paginate($request);

        return LaundryOrderResource::collection($orders);
    }

    public function store(StoreLaundryOrderRequest $request): JsonResponse
    {
        $request->user()->laundryOrders()->create($request->validated());

        return $this->message('Pengajuan laundry berhasil dikirim.', 201);
    }

    public function update(UpdateLaundryOrderRequest $request, LaundryOrder $order): JsonResponse
    {
        // Mengunci baris agar dua admin yang memperbarui bersamaan tidak saling menimpa.
        DB::transaction(function () use ($request, $order) {
            LaundryOrder::lockForUpdate()->findOrFail($order->id)
                ->forceFill($request->validated())
                ->save();
        }, 3);

        return $this->message('Status berhasil diperbarui.');
    }
}
