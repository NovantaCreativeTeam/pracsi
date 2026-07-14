<?php

use App\Models\Dialog;
use App\Models\Move;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $dialogs = Dialog::all();

        foreach ($dialogs as $dialog) {
            $moves = Move::where('dialog_id', $dialog->id)
                ->orderBy('begin')
                ->orderBy('end')
                ->get();

            foreach ($moves as $index => $move) {
                $move->turn = $index + 1;
                $move->save();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('moves')->update(['turn' => null]);
    }
};
