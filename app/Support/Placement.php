<?php

namespace App\Support;

use App\Models\Room;
use App\Models\User;

/**
 * Aturan penempatan penghuni ke kamar, dipakai alokasi otomatis dan penempatan manual.
 *
 * - Penolakan mutlak (violation): melanggar logika fisik asrama; tidak bisa dilanjutkan.
 * - Peringatan (warnings): menyimpang dari yang ideal; petugas tetap dapat melanjutkan.
 */
final class Placement
{
    /** Alasan penolakan mutlak, atau null bila penempatan diperbolehkan. */
    public static function violation(User $resident, Room $room, RoomOccupancy $occupancy): ?string
    {
        if (! $room->is_active) {
            return 'Kamar tidak aktif.';
        }

        if ($resident->gender === null || $resident->religion === null || (string) $resident->cohort === '') {
            return 'Profil penghuni belum lengkap (angkatan, jenis kelamin, dan agama).';
        }

        if (! $occupancy->hasFreeSlot($room)) {
            return 'Kamar sudah penuh.';
        }

        foreach ($occupancy->occupantsOf($room) as $mate) {
            if ($mate->gender !== $resident->gender) {
                return 'Laki-laki dan perempuan tidak boleh sekamar.';
            }
        }

        return null;
    }

    /**
     * Peringatan untuk menempatkan $resident di $room (teman sekamar tidak termasuk dirinya).
     *
     * @return list<string>
     */
    public static function warnings(User $resident, Room $room, RoomOccupancy $occupancy): array
    {
        $warnings = self::roommateMismatches($resident, $occupancy->occupantsOf($room));

        $cohort = User::normalizeProfileText($resident->cohort);
        $cohortFloors = $occupancy->floorsOfCohort($cohort, $resident->id);
        if ($cohortFloors !== [] && ! in_array($room->floor, $cohortFloors, true)) {
            $warnings[] = "Penempatan di luar lantai rombongan angkatan {$cohort}.";
        }

        return $warnings;
    }

    /**
     * Perbedaan profil dengan teman sekamar. Jenis kelamin hanya dilaporkan bila diminta
     * (saat penempatan, beda jenis kelamin sudah ditolak mutlak).
     *
     * @param  iterable<User>  $mates
     * @return list<string>
     */
    public static function roommateMismatches(User $resident, iterable $mates, bool $includeGender = false): array
    {
        $genderDiffers = $religionDiffers = $cohortDiffers = false;

        foreach ($mates as $mate) {
            $genderDiffers = $genderDiffers || $mate->gender !== $resident->gender;
            $religionDiffers = $religionDiffers || $mate->religion !== $resident->religion;
            $cohortDiffers = $cohortDiffers || User::normalizeProfileText($mate->cohort)
                !== User::normalizeProfileText($resident->cohort);
        }

        return array_values(array_filter([
            $includeGender && $genderDiffers ? 'Jenis kelamin berbeda dengan teman sekamar.' : null,
            $religionDiffers ? 'Beda agama dengan teman sekamar.' : null,
            $cohortDiffers ? 'Beda angkatan dengan teman sekamar.' : null,
        ]));
    }
}
