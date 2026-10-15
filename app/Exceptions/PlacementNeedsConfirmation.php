<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

/**
 * Penempatan menyimpang dari yang ideal. Klien menampilkan peringatan dan mengirim ulang
 * permintaan dengan confirm=true bila petugas setuju.
 */
class PlacementNeedsConfirmation extends RuntimeException
{
    /** @param  list<string>  $warnings */
    public function __construct(public readonly array $warnings)
    {
        parent::__construct('Penempatan perlu dikonfirmasi.');
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => 'Penempatan perlu dikonfirmasi.',
            'warnings' => $this->warnings,
        ], 409);
    }
}
