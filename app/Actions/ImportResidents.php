<?php

namespace App\Actions;

use App\Enums\Gender;
use App\Enums\Religion;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Mendaftarkan banyak penghuni dari berkas Excel/CSV (keputusan pengelola no. 6).
 * Semua atau tidak sama sekali: satu baris tidak valid membatalkan seluruh impor.
 */
class ImportResidents
{
    public const MAX_ROWS = 500;

    /** Nama kolom (huruf kecil) yang dikenali -> kunci data. */
    private const HEADERS = [
        'nama' => 'name', 'name' => 'name',
        'email' => 'email',
        'angkatan' => 'cohort', 'cohort' => 'cohort',
        'jenis kelamin' => 'gender', 'gender' => 'gender', 'jk' => 'gender',
        'agama' => 'religion', 'religion' => 'religion',
    ];

    /** @return list<array{name: string, email: string, password: string}> akun baru beserta sandi awal */
    public function handle(UploadedFile $file): array
    {
        $rows = $this->readRows($file);
        $this->validateRows($rows);

        return DB::transaction(function () use ($rows) {
            $created = [];

            foreach ($rows as $row) {
                $password = Str::random(10);
                User::create([
                    'name' => $row['name'],
                    'email' => $row['email'],
                    'password' => $password,
                    'role' => Role::Resident,
                    'cohort' => User::normalizeProfileText($row['cohort']),
                    'gender' => $this->gender($row['gender']),
                    'religion' => Religion::from(User::normalizeProfileText($row['religion'])),
                ]);
                $created[] = ['name' => $row['name'], 'email' => $row['email'], 'password' => $password];
            }

            return $created;
        }, 3);
    }

    /** @return list<array<string, string>> baris data dengan nomor baris pada berkas sebagai kunci tersirat */
    private function readRows(UploadedFile $file): array
    {
        $reader = match (strtolower($file->getClientOriginalExtension())) {
            'xlsx' => 'Xlsx',
            'xls' => 'Xls',
            default => 'Csv',
        };

        try {
            $sheet = IOFactory::createReader($reader)->load($file->getRealPath())->getActiveSheet();
            $table = $sheet->toArray(null, true, true, false);
        } catch (\Throwable) {
            throw ValidationException::withMessages(['file' => 'Berkas tidak dapat dibaca. Periksa formatnya.']);
        }

        $header = array_map(fn ($cell) => mb_strtolower(trim((string) $cell)), array_shift($table) ?? []);
        $columns = [];
        foreach ($header as $index => $name) {
            if (isset(self::HEADERS[$name])) {
                $columns[self::HEADERS[$name]] = $index;
            }
        }

        $missing = array_diff(['name', 'email', 'cohort', 'gender', 'religion'], array_keys($columns));
        if ($missing !== []) {
            throw ValidationException::withMessages([
                'file' => 'Kolom wajib tidak ditemukan. Baris pertama harus berisi: Nama, Email, Angkatan, Jenis Kelamin, Agama.',
            ]);
        }

        $rows = [];
        foreach ($table as $offset => $cells) {
            $row = [];
            foreach ($columns as $key => $index) {
                $row[$key] = trim((string) ($cells[$index] ?? ''));
            }
            if (implode('', $row) === '') {
                continue; // baris kosong
            }
            $rows[$offset + 2] = $row; // +2: nomor baris Excel (baris 1 = judul kolom)
        }

        if ($rows === []) {
            throw ValidationException::withMessages(['file' => 'Berkas tidak berisi data penghuni.']);
        }

        if (count($rows) > self::MAX_ROWS) {
            throw ValidationException::withMessages([
                'file' => 'Jumlah baris melebihi batas '.self::MAX_ROWS.' penghuni per impor.',
            ]);
        }

        return $rows;
    }

    /** @param  array<int, array<string, string>>  $rows */
    private function validateRows(array $rows): void
    {
        $errors = [];
        $seen = [];

        foreach ($rows as $number => $row) {
            $row['gender_enum'] = $this->gender($row['gender'])?->value;
            $row['religion'] = User::normalizeProfileText($row['religion']);

            $validator = Validator::make($row, [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
                'cohort' => ['required', 'string', 'max:100', 'regex:/^[\pL\pN\s]+$/u'],
                'gender_enum' => ['required'],
                'religion' => ['required', 'in:'.implode(',', array_column(Religion::cases(), 'value'))],
            ], [
                'email.unique' => 'Email sudah terdaftar.',
                'cohort.regex' => 'Angkatan hanya boleh berisi huruf, angka, dan spasi.',
                'gender_enum.required' => 'Jenis kelamin harus L/P (atau Laki-laki/Perempuan).',
                'religion.in' => 'Agama harus salah satu dari: Islam, Kristen, Katolik, Hindu, Buddha, Konghucu.',
            ]);

            $messages = $validator->errors()->all();
            $email = mb_strtolower($row['email']);
            if (isset($seen[$email])) {
                $messages[] = "Email sama dengan baris {$seen[$email]}.";
            }
            $seen[$email] ??= $number;

            foreach ($messages as $message) {
                $errors[] = "Baris {$number}: {$message}";
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages(['file' => $errors]);
        }
    }

    private function gender(string $value): ?Gender
    {
        return match (mb_strtolower(trim($value))) {
            'l', 'laki-laki', 'laki laki', 'pria', 'male', 'm' => Gender::Male,
            'p', 'perempuan', 'wanita', 'female', 'f' => Gender::Female,
            default => null,
        };
    }
}
