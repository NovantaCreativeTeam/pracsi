<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Corpus;
use App\Models\Dialog;
use App\Models\Move;
use App\Models\Note;
use App\Services\EafImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

class Dialog121ImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_dialog_121_and_check_pause_associations()
    {
        // 1. Setup
        $corpus = Corpus::create([
            'project_reference' => 'DIALOG_121_TEST',
            'title' => 'Dialog 121 Test'
        ]);

        $dialog = Dialog::create([
            'corpus_id' => $corpus->id,
            'title' => 'Dialog 121',
            'reference' => 'IT_PSOR_PN41',
            'customer_n' => 2,
            'speaking_customer_n' => 2
        ]);

        $eafPath = base_path('.junie/dialog_121.eaf');

        if (!File::exists($eafPath)) {
            $this->markTestSkipped("Il file EAF non è presente in $eafPath");
        }

        $service = new EafImportService();

        // 2. Execution
        $service->import($eafPath, $dialog);

        // 3. Assertions

        // Cerchiamo le note corrispondenti al testo incriminato
        $noteText = "La registrazione riprende quando il cameriere Z torna al tavolo per raccogliere la comanda.";
        $notes = Note::where('content', 'like', "%$noteText%")
                    ->where('end', '>=', 5000)
                    ->get();

        $this->assertGreaterThan(0, $notes->count(), "Dovrebbero esserci note con quel testo");

        foreach ($notes as $note) {
            // Cerchiamo se esiste una Move (pausa) che si sovrappone a questa nota
            $moves = Move::where('begin', '<=', $note->end)
                         ->where('end', '>=', $note->begin)
                         ->get();

            $pauseFound = false;
            foreach ($moves as $move) {
                if ($move->participant_id === null) { // È una pausa
                    $pauseFound = true;

                    // Verifichiamo se fallisce (nel senso che NON ha associazioni)
                    $this->assertNull($move->sequence_id, "La pausa a {$move->begin}ms NON dovrebbe avere una sequence");
                    $this->assertNull($move->micro_task_id, "La pausa a {$move->begin}ms NON dovrebbe avere un microtask");
                    $this->assertNull($move->transaction_id, "La pausa a {$move->begin}ms NON dovrebbe avere un microtask");
                }
            }
            $this->assertTrue($pauseFound, "Dovrebbe essere stata trovata una pausa in corrispondenza della nota");
        }
    }

    public function test_import_dialog_121_and_check_pause()
    {
        // 1. Setup
        $corpus = Corpus::create([
            'project_reference' => 'DIALOG_121_TEST',
            'title' => 'Dialog 121 Test'
        ]);

        $dialog = Dialog::create([
            'corpus_id' => $corpus->id,
            'title' => 'Dialog 121',
            'reference' => 'IT_PSOR_PN41',
            'customer_n' => 2,
            'speaking_customer_n' => 2
        ]);

        $eafPath = base_path('.junie/dialog_121.eaf');

        if (!File::exists($eafPath)) {
            $this->markTestSkipped("Il file EAF non è presente in $eafPath");
        }

        $service = new EafImportService();

        // 2. Execution
        $service->import($eafPath, $dialog);

        // 3. Assertions
        $moves = Move::where('begin', '=', 5530)
            ->where('end', '=', 8380)
            ->get();


        foreach ($moves as $move) {
                // Verifichiamo se fallisce (nel senso che NON ha associazioni)
                $this->assertNull($move->participant_id, "La mossaa a {$move->begin}ms NON dovrebbe avere un partecipante");
                $this->assertNull($move->turn, "La pausa a {$move->begin}ms NON dovrebbe avere un turno");
                $this->assertNull($move->sequence_id, "La pausa a {$move->begin}ms NON dovrebbe avere una sequence");
                $this->assertNull($move->micro_task_id, "La pausa a {$move->begin}ms NON dovrebbe avere un microtask");
                $this->assertNull($move->transaction_id, "La pausa a {$move->begin}ms NON dovrebbe avere un microtask");
        }

    }
}
