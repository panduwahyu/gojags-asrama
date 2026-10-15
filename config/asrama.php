<?php

return [
    'floors' => [5, 4, 3],          // urutan pengisian: dari atas
    'rooms_per_floor' => 26,
    'room_capacity' => 2,
    'per_page_options' => [10, 50, 100],
    'display_timezone' => 'Asia/Jakarta',
    // Batas percobaan login per IP per menit. Longgar karena satu Wi-Fi asrama (satu IP) dipakai
    // hingga 150 penghuni yang login hampir bersamaan (keputusan stakeholder no. 18).
    'login_attempts_per_ip' => 200,
];
