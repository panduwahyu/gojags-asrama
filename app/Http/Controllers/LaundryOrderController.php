<?php

namespace App\Http\Controllers;

use App\Enums\LaundryStatus;
use App\Http\Requests\ExportRequest;
use App\Http\Requests\StoreLaundryOrderRequest;
use App\Http\Requests\UpdateLaundryOrderRequest;
use App\Http\Resources\LaundryOrderResource;
use App\Models\LaundryOrder;
use App\Models\User;
use App\Support\TableExport;
use App\Support\TableQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LaundryOrderController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $orders = $this->tableQuery($request)->paginate($request);

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
            $locked = LaundryOrder::lockForUpdate()->findOrFail($order->id);
            $target = LaundryStatus::from($request->validated('status'));

            if ($locked->status === LaundryStatus::Cancelled) {
                throw ValidationException::withMessages([
                    'status' => 'Pesanan yang sudah dibatalkan tidak dapat diubah.',
                ]);
            }

            if ($locked->status === LaundryStatus::Done && $target === LaundryStatus::Pending) {
                throw ValidationException::withMessages([
                    'status' => 'Pesanan yang sudah selesai tidak dapat dikembalikan ke status Diajukan.',
                ]);
            }

            $locked->forceFill($request->validated())->save();
        }, 3);

        return $this->message('Status berhasil diperbarui.');
    }

    public function cancel(Request $request, LaundryOrder $order): JsonResponse
    {
        DB::transaction(function () use ($request, $order) {
            // Milik orang lain -> 404.
            $locked = $request->user()->laundryOrders()->lockForUpdate()->findOrFail($order->id);

            if (! $locked->isPending()) {
                throw ValidationException::withMessages([
                    'status' => 'Hanya pesanan berstatus Diajukan yang dapat dibatalkan.',
                ]);
            }

            $locked->forceFill(['status' => LaundryStatus::Cancelled])->save();
        }, 3);

        return $this->message('Pesanan laundry dibatalkan.');
    }

    private function tableQuery(Request $request): TableQuery
    {
        return TableQuery::for(LaundryOrder::visibleTo($request->user())->with('user'))
            ->search(['items', 'user.name'])
            ->status('status', LaundryStatus::class)
            ->sort([
                'items',
                'set_count',
                'created_at',
                'cost',
                'user_name' => User::nameOf('laundry_orders'),
            ]);
    }

    public function export(ExportRequest $request): StreamedResponse
    {
        $rows = $this->tableQuery($request)->export($request)->map(fn (LaundryOrder $order) => [
            $order->user->name,
            $order->items,
            $order->set_count,
            $order->status->label(),
            $order->cost ?? '',
            TableExport::wib($order->created_at),
        ]);

        return TableExport::download($request->validated('format'), 'Laundry',
            ['Pemesan', 'Pakaian / barang', 'Jumlah stel', 'Status', 'Biaya (Rp)', 'Diajukan'], $rows->all());
    }
}
