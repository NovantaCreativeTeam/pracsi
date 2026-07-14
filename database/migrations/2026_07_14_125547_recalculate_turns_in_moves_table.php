<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $dialogs = \App\Models\Dialog::all();

        foreach ($dialogs as $dialog) {
            $moves = \App\Models\Move::where('dialog_id', $dialog->id)
                ->orderBy('begin')
                ->orderBy('end')
                ->get();

            $turnCounter = 1;
            foreach ($moves as $move) {
                if ($move->participant_id !== null) {
                    $move->turn = $turnCounter++;
                } else {
                    $move->turn = null;
                }
                $move->save();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Difficile ripristinare esattamente il calcolo precedente se non rifacendolo
        // ma in genere non è necessario un ripristino perfetto per dati calcolati.
        // Possiamo lasciare così o azzerare.
    }
};
