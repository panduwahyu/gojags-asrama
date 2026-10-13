# Backend (Laravel 10, PHP 8.2)

## Isi

1. Rute · 2. Enum · 3. Model · 4. FormRequest · 5. Resource · 6. Controller · 7. TableQuery ·
8. Action · 9. Kamar: status turunan · 10. Waktu · 11. Konfigurasi · 12. Migrasi, factory, seeder ·
13. Test · 14. Gaya kode

Contoh berjalan di berkas ini adalah fitur **izin keluar**; fitur lain mengikuti bentuk yang sama.

## 1. Rute

Semua endpoint ada di `routes/api.php`, satu baris per endpoint, dikelompokkan menurut siapa yang boleh
memanggil. `routes/web.php` hanya berisi `/up`. Tidak ada closure berisi logika di berkas rute, dan tidak
ada parameter rute yang menjadi nama tabel.

`RouteServiceProvider` memuat `routes/api.php` dengan grup middleware **`web`** (bukan `api`) supaya sesi
cookie dan CSRF aktif, ditambah `cache.headers:no_store`:

```php
Route::middleware(['web', 'cache.headers:no_store'])
    ->prefix('api')
    ->group(base_path('routes/api.php'));
```

Satu URL per sumber daya untuk kedua peran. Daftar (`index`) dibaca admin dan penghuni lewat URL yang
sama; penyaringannya dilakukan scope `visibleTo()` di model. Ini menghapus pasangan rute `user/...`
lama dan membuat komponen frontend tidak perlu memilih endpoint menurut peran.

```php
Route::get('session', [SessionController::class, 'show']);
Route::post('login', [SessionController::class, 'store'])->middleware('throttle:5,1');

Route::middleware('auth')->group(function () {
    Route::post('logout', [SessionController::class, 'destroy']);

    // Dibaca kedua peran; isinya disaring oleh scope visibleTo().
    Route::get('leave-permits', [LeavePermitController::class, 'index']);
    Route::get('complaints', [ComplaintController::class, 'index']);
    Route::get('laundry-orders', [LaundryOrderController::class, 'index']);
    Route::get('catering-menus', [CateringMenuController::class, 'index']);

    Route::middleware('role:resident')->group(function () {
        Route::get('my-room', [MyRoomController::class, 'show']);
        Route::post('my-room/check-in', [MyRoomController::class, 'checkIn']);
        Route::post('my-room/check-out', [MyRoomController::class, 'checkOut']);
        Route::post('leave-permits', [LeavePermitController::class, 'store']);
        Route::patch('leave-permits/{permit}', [LeavePermitController::class, 'update']);
        Route::post('complaints', [ComplaintController::class, 'store']);
        Route::post('laundry-orders', [LaundryOrderController::class, 'store']);
        Route::put('catering-menus/{menu}/rating', [CateringRatingController::class, 'update']);
    });

    Route::middleware('role:admin')->group(function () {
        Route::get('dashboard', DashboardController::class);
        Route::get('rooms', [RoomController::class, 'index']);
        Route::post('rooms/allocate', RoomAllocationController::class);
        Route::get('residents', [ResidentController::class, 'index']);
        Route::patch('residents/{resident}', [ResidentController::class, 'update']);
        Route::patch('leave-permits/{permit}/review', [LeavePermitController::class, 'review']);
        Route::patch('complaints/{complaint}/resolve', [ComplaintController::class, 'resolve']);
        Route::patch('laundry-orders/{order}', [LaundryOrderController::class, 'update']);
        Route::post('catering-menus', [CateringMenuController::class, 'store']);
        Route::patch('catering-menus/{menu}', [CateringMenuController::class, 'update']);
    });
});
```

Bentuk respons yang seragam:

| Jenis | Bentuk |
|---|---|
| Daftar berpaginasi | `{ data: [...], meta: { current_page, last_page, total, … } }` (bawaan `Resource::collection($paginator)`) |
| Satu objek | `{ data: {...} }` |
| Aksi tulis | `{ message: "…" }` — 201 untuk pembuatan, 200 selainnya |
| Gagal validasi | 422 `{ message, errors: { field: ["…"] } }` (bawaan Laravel) |

## 2. Enum

Backed enum string dengan `label()` Indonesia. Nilai yang tersimpan dan dikirim ke klien adalah `value`;
label hanya untuk tampilan.

```php
namespace App\Enums;

enum PermitStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Diajukan',
            self::Approved => 'Disetujui',
            self::Rejected => 'Ditolak',
        };
    }
}
```

## 3. Model

Model memuat: `$fillable`, `$casts`, relasi, scope query, dan aturan kecil yang melekat pada satu baris
data. Tidak memuat logika HTTP.

```php
namespace App\Models;

class LeavePermit extends Model
{
    use HasFactory;

    protected $fillable = ['leaves_at', 'returns_at', 'reason'];

    protected $casts = [
        'leaves_at' => 'datetime',
        'returns_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'status' => PermitStatus::class,
    ];

    protected $attributes = ['status' => PermitStatus::Pending];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /** Penghuni hanya melihat miliknya; admin melihat semuanya. */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        if ($user->role === Role::Resident) {
            $query->whereBelongsTo($user);
        }
    }

    public function isPending(): bool
    {
        return $this->status === PermitStatus::Pending;
    }
}
```

`$fillable` hanya berisi kolom yang datang dari form pengguna. Kolom yang ditetapkan sistem (`user_id`,
`status`, `reviewed_by`, `reviewed_at`) diisi eksplisit di controller, sehingga terlihat jelas siapa yang
boleh mengubahnya. `scopeVisibleTo` yang sama ada di `Complaint` dan `LaundryOrder`.

`User` meng-cast `role` ke `Role` dan `gender` ke `Gender`, serta punya relasi `leavePermits()`,
`complaints()`, `laundryOrders()`, `slot()`, `stays()`.

## 4. FormRequest

Satu kelas per bentuk input. Aturan, pesan khusus, dan nama atribut Indonesia ada di sini — tidak ada
`$request->validate()` di controller. `authorize()` mengembalikan `true`; otorisasi peran sudah
ditangani middleware rute.

```php
namespace App\Http\Requests;

class SaveLeavePermitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'leaves_at' => ['required', DateFormat::UTC],
            'returns_at' => ['required', DateFormat::UTC, 'after:leaves_at'],
            'reason' => ['required', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return ['returns_at.after' => 'Waktu kembali harus setelah waktu keluar.'];
    }
}
```

```php
class ReviewLeavePermitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in([PermitStatus::Approved->value, PermitStatus::Rejected->value])],
        ];
    }
}
```

Bila simpan dan ubah memakai aturan yang sama, pakai satu kelas `Save…Request`; jangan menyalin.

## 5. Resource

Satu-satunya tempat bentuk JSON sebuah model ditentukan. Status dikirim sebagai `status` (nilai enum)
dan `status_label`. Tanggal cukup dikembalikan sebagai Carbon — Laravel menserialisasikannya ke ISO-8601
UTC, jadi tidak ada `Carbon::parse(...)->toISOString()` manual.

```php
namespace App\Http\Resources;

class LeavePermitResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_name' => $this->user->name,
            'leaves_at' => $this->leaves_at,
            'returns_at' => $this->returns_at,
            'reason' => $this->reason,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
        ];
    }
}
```

Relasi yang dipakai Resource selalu di-*eager load* di controller (`->with('user')`) agar tidak terjadi
N+1 query.

## 6. Controller

```php
namespace App\Http\Controllers;

class LeavePermitController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $permits = TableQuery::for(LeavePermit::visibleTo($request->user())->with('user'))
            ->search(['reason', 'status', 'user.name'])
            ->sort(['leaves_at', 'returns_at', 'reason', 'status', 'user_name' => User::nameOf('leave_permits')])
            ->paginate($request);

        return LeavePermitResource::collection($permits);
    }

    public function store(SaveLeavePermitRequest $request): JsonResponse
    {
        $request->user()->leavePermits()->create($request->validated());

        return $this->message('Izin keluar berhasil diajukan.', 201);
    }

    public function update(SaveLeavePermitRequest $request, LeavePermit $permit): JsonResponse
    {
        DB::transaction(function () use ($request, $permit) {
            // Mengunci baris agar tidak bertabrakan dengan keputusan admin; milik orang lain → 404.
            $permit = $request->user()->leavePermits()->lockForUpdate()->findOrFail($permit->id);
            $this->ensurePending($permit, 'Hanya izin berstatus Diajukan yang dapat diedit.');
            $permit->update($request->validated());
        }, 3);

        return $this->message('Pengajuan izin berhasil diperbarui.');
    }

    public function review(ReviewLeavePermitRequest $request, LeavePermit $permit): JsonResponse
    {
        $status = PermitStatus::from($request->validated('status'));

        DB::transaction(function () use ($request, $permit, $status) {
            $permit = LeavePermit::lockForUpdate()->findOrFail($permit->id);
            $this->ensurePending($permit, 'Pengajuan ini sudah diproses.');
            $permit->forceFill([
                'status' => $status,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ])->save();
        }, 3);

        return $this->message('Izin keluar '.mb_strtolower($status->label()).'.');
    }

    private function ensurePending(LeavePermit $permit, string $message): void
    {
        if (! $permit->isPending()) {
            throw ValidationException::withMessages(['status' => $message]);
        }
    }
}
```

Controller dasar menyediakan satu pembantu:

```php
abstract class Controller extends BaseController
{
    protected function message(string $message, int $status = 200): JsonResponse
    {
        return response()->json(['message' => $message], $status);
    }
}
```

Pedoman:
- Nama method mengikuti konvensi resource (`index`, `store`, `show`, `update`, `destroy`) ditambah kata
  kerja domain bila perlu (`review`, `resolve`, `checkIn`). Controller satu-aksi memakai `__invoke`.
- Controller tidak memanggil controller lain. Yang dipakai bersama pindah ke model (scope) atau `Support/`.
- Lebih dari kira-kira lima method publik atau method lebih dari ±15 baris adalah tanda perlu dipecah
  atau dipindah ke Action.

`User::nameOf('leave_permits')` adalah pembantu kecil di model `User` yang mengembalikan subquery nama
untuk pengurutan: `static::select('name')->whereColumn('users.id', $table.'.user_id')`.

## 7. TableQuery

Semua tabel berpaginasi memakai `App\Support\TableQuery`. Kelas ini satu-satunya tempat parameter
`q / sort / direction / per_page / page` divalidasi dan diterapkan (aturannya di `domain.md` § 4).

```php
namespace App\Support;

final class TableQuery
{
    /** @var list<string> */
    private array $searchable = [];

    /** @var array<string, string|Builder|Closure> */
    private array $sortable = ['id' => 'id'];

    private function __construct(private Builder $query) {}

    public static function for(Builder $query): self
    {
        return new self($query);
    }

    /** Kolom untuk LIKE; "relasi.kolom" mencari di tabel relasi. */
    public function search(array $columns): self
    {
        $this->searchable = $columns;

        return $this;
    }

    /**
     * Kolom yang boleh diurutkan. Entri tanpa kunci = nama kolom; entri berkunci = alias =>
     * kolom, subquery, atau Closure(Builder $query, string $direction).
     */
    public function sort(array $columns): self
    {
        foreach ($columns as $key => $target) {
            $this->sortable[is_int($key) ? $target : $key] = $target;
        }

        return $this;
    }

    public function paginate(Request $request): LengthAwarePaginator
    {
        $input = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'sort' => ['sometimes', Rule::in(array_keys($this->sortable))],
            'direction' => ['sometimes', Rule::in(['asc', 'desc'])],
            'per_page' => ['sometimes', 'integer', Rule::in(config('asrama.per_page_options'))],
            'page' => ['sometimes', 'integer', 'min:1'],
        ]);

        $this->applySearch($input['q'] ?? '');
        $this->applySort($input['sort'] ?? 'id', $input['direction'] ?? 'desc');

        return $this->query->paginate((int) ($input['per_page'] ?? config('asrama.per_page_options')[0]));
    }

    private function applySearch(string $term): void
    {
        if ($term === '') {
            return;
        }

        $this->query->where(function (Builder $query) use ($term) {
            foreach ($this->searchable as $column) {
                if (str_contains($column, '.')) {
                    [$relation, $field] = explode('.', $column, 2);
                    $query->orWhereRelation($relation, $field, 'like', "%{$term}%");
                } else {
                    $query->orWhere($query->qualifyColumn($column), 'like', "%{$term}%");
                }
            }
        });
    }

    private function applySort(string $sort, string $direction): void
    {
        $target = $this->sortable[$sort];

        if ($target instanceof Closure) {
            $target($this->query, $direction);
        } else {
            $this->query->orderBy($target, $direction);
        }

        // Pengurut kedua menjaga urutan tetap stabil antar halaman.
        if ($sort !== 'id') {
            $this->query->orderBy($this->query->qualifyColumn('id'), $direction);
        }
    }
}
```

Ini satu-satunya `validate()` di luar FormRequest, karena aturannya bergantung pada kolom yang
didaftarkan pemanggil.

## 8. Action

Kelas satu-tujuan dengan satu method publik `handle()`, di-*inject* ke controller. Dipakai untuk proses
yang butuh transaksi dan kunci lintas-model: `AllocateRooms`, `CheckIn`, `CheckOut`,
`UpdateResidentProfile`. Aturannya ada di `domain.md` § 6–8.

```php
namespace App\Actions;

class CheckIn
{
    public function handle(User $resident): void
    {
        DB::transaction(function () use ($resident) {
            $slot = RoomLocks::acquireFor($resident);

            if ($slot === null) {
                throw ValidationException::withMessages(['room' => 'Anda belum memiliki alokasi kamar.']);
            }
            if ($slot->checked_in_at !== null) {
                throw ValidationException::withMessages(['room' => 'Anda sudah check-in.']);
            }

            $slot->update(['checked_in_at' => now()]);
            $resident->stays()->create([
                'room_id' => $slot->room->id,
                'user_name' => $resident->name,
                'room_number' => $slot->room->number,
                'floor' => $slot->room->floor,
                'checked_in_at' => $slot->checked_in_at,
            ]);
        }, 3);
    }
}
```

Urutan kunci `rooms` → `users` → `room_slots` ditulis **sekali**, di `App\Support\RoomLocks`, lalu dipakai
keempat Action. Kode lama menyalin tiga baris kunci itu di tiga tempat; bila satu salinan berubah urutan,
muncul *deadlock* yang sulit dilacak.

`AllocateRooms::handle()` mengembalikan array hasil (`allocated`, `waiting`, `incomplete`). Pecah
algoritmenya menjadi method privat bernama sesuai langkah di `domain.md` § 7 — misalnya
`pendingResidents()`, `homeFloorFor()`, `findRoomFor()`, `assign()` — sehingga `handle()` terbaca seperti
ringkasan aturannya. Kode lama menaruh semuanya dalam satu closure 65 baris.

## 9. Kamar: status turunan

Tidak ada kolom `rooms.status` dan tidak ada `sync()`. `Room` menyediakan:

```php
public function scopeWithOccupancy(Builder $query): void
{
    $query->withCount([
        'slots as assigned_count' => fn (Builder $slots) => $slots->whereNotNull('user_id'),
        'slots as checked_in_count' => fn (Builder $slots) => $slots
            ->whereNotNull('user_id')
            ->whereNotNull('checked_in_at'),
    ]);
}

protected function status(): Attribute
{
    return Attribute::get(fn () => RoomStatus::fromCounts($this->assigned_count, $this->checked_in_count));
}
```

`RoomStatus::fromCounts(int $assigned, int $checkedIn): self` memuat tabel keputusan di `domain.md` § 5 —
satu-satunya tempat aturan itu ditulis di PHP.

- **Monitoring**: muat 78 kamar `withOccupancy()` lalu `countBy` status di PHP. Datanya kecil dan tetap;
  tidak perlu SQL agregat.
- **Urut menurut status / penghuni**: lewat `scopeOrderByStatus($direction)` dan
  `scopeOrderByFirstResident($direction)` di `Room`, didaftarkan ke `TableQuery::sort()` sebagai Closure.
  `orderByStatus` boleh memakai satu ekspresi `CASE` mentah karena pengurutan harus terjadi di basis data;
  ini satu-satunya SQL mentah yang diizinkan, dan harus diberi komentar yang merujuk ke `RoomStatus`.

## 10. Waktu

- `config('app.timezone')` = `UTC`. Kolom waktu di-cast `datetime`.
- API hanya menerima waktu berformat `Y-m-d\TH:i:s.v\Z` (persis keluaran `Date.toISOString()` di
  browser). Aturannya didefinisikan sekali:

  ```php
  namespace App\Support;

  final class DateFormat
  {
      /** ISO-8601 UTC berakhiran Z. */
      public const UTC = 'date_format:Y-m-d\TH:i:s.v\Z';
  }
  ```

  Mengapa seketat itu: Eloquent menyimpan Carbon tanpa mengonversi zona waktunya, sehingga string
  ber-offset seperti `+07:00` akan tersimpan sebagai jam yang salah. Mewajibkan `Z` menutup celah itu
  tanpa kode konversi.
- Konversi WIB ↔ UTC hanya terjadi di frontend (`utils/datetime.ts`). Backend tidak mengenal
  `Asia/Jakarta`.

## 11. Konfigurasi

```php
// config/asrama.php
return [
    'floors' => [5, 4, 3],          // urutan pengisian: dari atas
    'rooms_per_floor' => 26,
    'room_capacity' => 2,
    'per_page_options' => [10, 50, 100],
];
```

## 12. Migrasi, factory, seeder

- Migrasi lama (yang mengubah-ubah tabel dan menyisipkan data) diganti satu migrasi `create` bersih per
  tabel sesuai `schema.md`. Migrasi tidak menyisipkan baris.
- Tiap model punya factory dengan *state* untuk kondisi yang sering dipakai tes:
  `User::factory()->resident()->profiled(cohort: '64')`, `LeavePermit::factory()->approved()`.
- `RoomSeeder` membangun inventaris dari `config/asrama.php`; `AccountSeeder` membuat akun demo
  (`domain.md` § 14). Keduanya aman dijalankan ulang.

## 13. Test

Strategi lengkap ada di `testing.md`. Feature test PHPUnit per fitur, terhadap basis data di `phpunit.xml`
dengan `RefreshDatabase`. Tes memanggil API
JSON (`getJson`, `postJson`, …) dan memeriksa status, pesan, serta isi basis data. Nama method
menggambarkan perilaku.

```php
class LeavePermitTest extends TestCase
{
    use RefreshDatabase;

    public function test_resident_can_edit_only_while_pending(): void
    {
        $resident = User::factory()->resident()->create();
        $permit = LeavePermit::factory()->for($resident)->approved()->create();

        $this->actingAs($resident)
            ->patchJson("/api/leave-permits/{$permit->id}", $this->validPayload())
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status' => 'Hanya izin berstatus Diajukan yang dapat diedit.']);
    }
}
```

Tiap fitur setidaknya menguji: jalur berhasil, tiap aturan di `domain.md` beserta pesannya, batas peran
(403), kepemilikan (404), dan validasi input. Tes yang butuh kamar memanggil `$this->seed(RoomSeeder::class)`.
Tes Blade lama (`AsramaTest`, bagian non-JSON) dihapus bersama Blade.

## 14. Gaya kode

- `vendor/bin/pint` (preset Laravel) adalah wasit format. Maksimal 120 karakter per baris.
- Selalu pakai kurung kurawal dan baris baru untuk `if`; tidak ada `if (...) throw ...;` sebaris.
- Tipe parameter dan tipe kembalian ditulis di semua method.
- `use` di atas berkas; tidak ada nama kelas ber-namespace penuh di tengah kode.
- Rantai method panjang dipecah satu pemanggilan per baris.
- Tanpa docblock bawaan Laravel yang hanya mengulang nama method.
