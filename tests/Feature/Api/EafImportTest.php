<?php

namespace Tests\Feature\Api;

use App\Models\Corpus;
use App\Models\Dialog;
use App\Models\User;
use App\Models\Task;
use App\Models\Move;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EafImportTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $corpus;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');

        $adminRole = Role::create(['name' => 'Amministratore']);
        Permission::create(['name' => 'view-dialogs']);
        Permission::create(['name' => 'manage-dialogs']);
        $adminRole->givePermissionTo(['view-dialogs', 'manage-dialogs']);

        $this->user = User::factory()->create();
        $this->user->assignRole($adminRole);

        $this->corpus = Corpus::create([
            'project_reference' => 'TEST_PROJ',
            'title' => 'Test Corpus'
        ]);
    }

    public function test_import_eaf_creates_moves_and_levels()
    {
        $eafPath = base_path('.junie/IT_PSPR_PN29.eaf');

        if (!File::exists($eafPath)) {
            $this->markTestSkipped("File EAF non trovato in $eafPath");
        }

        $file = new UploadedFile(
            $eafPath,
            'IT_PSPR_PN29.eaf',
            'application/xml',
            null,
            true
        );

        $response = $this->actingAs($this->user)
            ->postJson('/api/dialogs', [
                'corpus_id' => $this->corpus->id,
                'title' => 'Test EAF Import',
                'reference' => 'IT_PSPR_PN29',
                'eaf_file' => $file,
                'customer_n' => 1,
                'speaking_customer_n' => 1
            ]);

        $response->assertStatus(201);

        $dialogId = $response->json('data.id');
        $dialog = Dialog::with(['moves.moveLevel1', 'moves.moveLevel2', 'moves.moveLevel3', 'moves.participant'])->find($dialogId);

        $this->assertNotNull($dialog);

        // Verifichiamo il numero di moves
        // Nel file EAF ci sono 10 annotazioni per Y, 1 per A, 6 per B. Totale 17 parlati.
        // Più le pause.
        $movesCount = Move::where('dialog_id', $dialogId)->whereNotNull('participant_id')->count();
        $this->assertEquals(17, $movesCount, "Dovrebbero esserci 17 moves parlate");

        $movesWithLevel1 = Move::where('dialog_id', $dialogId)
            ->whereNotNull('participant_id')
            ->whereNotNull('move_level_1_id')
            ->count();
        $this->assertGreaterThan(0, $movesWithLevel1, "Dovrebbero esserci mosse con Move Level 1");

        $movesWithLevel2 = Move::where('dialog_id', $dialogId)
            ->whereNotNull('participant_id')
            ->whereNotNull('move_level_2_id')
            ->count();
        $this->assertGreaterThan(0, $movesWithLevel2, "Dovrebbero esserci mosse con Move Level 2");

        $movesWithNonVerbal = Move::where('dialog_id', $dialogId)
            ->whereNotNull('non_verbal_action_id')
            ->count();
        $this->assertGreaterThan(0, $movesWithNonVerbal, "Dovrebbero esserci mosse con Non Verbal Action");

        $movesWithMicroTask = Move::where('dialog_id', $dialogId)
            ->whereNotNull('micro_task_id')
            ->count();
        $this->assertGreaterThan(0, $movesWithMicroTask, "Dovrebbero esserci mosse con MicroTask");
    }
}
