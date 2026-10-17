<?php

use App\Models\Ticket;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Enkripsi seluruh PIN tiket lama yang masih berupa teks polos menjadi Bcrypt Hash ter-salt.
     */
    public function up(): void
    {
        $tickets = Ticket::whereNotNull('pin')
            ->whereRaw('length(pin) < 60')
            ->get();

        foreach ($tickets as $ticket) {
            $rawPin = trim((string) $ticket->pin);
            if (!empty($rawPin) && !str_starts_with($rawPin, '$2y$') && !str_starts_with($rawPin, '$2a$')) {
                $ticket->pin = Hash::make($rawPin);
                $ticket->saveQuietly();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Enkripsi hash satu arah (one-way) tidak dapat dan tidak boleh dibalikkan ke teks polos.
    }
};
