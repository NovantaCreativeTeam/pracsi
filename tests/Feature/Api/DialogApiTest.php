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

class DialogApiTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $corpus;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');

        // Setup roles and permissions
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

    public function test_can_create_dialog_with_minimal_imdi_data()
    {
        $eafPath = public_path('elan/IT_PSPR_PN29.eaf');
        if (!File::exists($eafPath)) {
            $this->markTestSkipped("EAF file not found at $eafPath");
        }

        $eafFile = new UploadedFile($eafPath, 'IT_PSPR_PN29.eaf', 'application/xml', null, true);

        // IMDI file content without customer_n or speaking_customer_n
        $imdiContent = '<?xml version="1.0" encoding="UTF-8"?>
        <METATRANSCRIPT>
            <Session>
                <Title>IMDI Test</Title>
                <Date>2024-01-01</Date>
                <MDGroup>
                    <Location><Continent>Europe</Continent><Country>Italy</Country><Region>Calabria</Region></Location>
                    <Project><Name>Test</Name></Project>
                    <Keys></Keys>
                    <Content><Genre>Conversation</Genre><SubGenre>Interazione</SubGenre></Content>
                    <Actors></Actors>
                </MDGroup>
            </Session>
        </METATRANSCRIPT>';

        $imdiFile = UploadedFile::fake()->createWithContent('test.imdi', $imdiContent);

        $response = $this->actingAs($this->user)
            ->postJson('/api/dialogs', [
                'corpus_id' => $this->corpus->id,
                'reference' => 'IMDI_REF',
                'eaf_file' => $eafFile,
                'imdi_file' => $imdiFile,
            ]);

        $response->assertStatus(201);

        // Verify default values are applied
        $this->assertDatabaseHas('dialogs', [
            'title' => 'IMDI Test',
            'customer_n' => 1,
            'speaking_customer_n' => 0
        ]);
    }

    public function test_can_create_dialog_and_import_eaf()
    {
        $eafPath = public_path('elan/IT_PSPR_PN29.eaf');

        if (!File::exists($eafPath)) {
            $this->markTestSkipped("Il file EAF non è presente in $eafPath");
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
                'title' => 'New Import Dialog',
                'reference' => 'IT_PSPR_PN29',
                'eaf_file' => $file,
                'wav_file' => UploadedFile::fake()->create('test.wav', 100, 'audio/wav'),
                'customer_n' => 1,
                'speaking_customer_n' => 1
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'title',
                    'participants',
                    'tasks',
                    'interactional_segments',
                    'transactions',
                    'notes'
                ]
            ]);

        $this->assertDatabaseHas('dialogs', [
            'title' => 'New Import Dialog',
            'corpus_id' => $this->corpus->id
        ]);

        $dialog = Dialog::where('title', 'New Import Dialog')->first();
        $this->assertNotNull($dialog->audio_path);
        $this->assertNotNull($dialog->eaf_path);
        Storage::disk('public')->assertExists(str_replace('/storage/', '', $dialog->audio_path));
        Storage::disk('public')->assertExists(str_replace('/storage/', '', $dialog->eaf_path));

        // Verifichiamo che i dati siano stati importati
        $this->assertGreaterThan(0, Task::count(), 'I Task dovrebbero essere stati importati');
        $this->assertGreaterThan(0, Move::count(), 'I Move dovrebbero essere stati importati');

        $dialogId = $response->json('data.id');
        $this->assertEquals(Task::first()->dialog_id, $dialogId);
    }

    public function test_cannot_create_dialog_without_file()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/dialogs', [
                'corpus_id' => $this->corpus->id,
                'title' => 'Missing File',
                'reference' => 'REF001'
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['eaf_file']);
    }

    public function test_requires_authentication()
    {
        $response = $this->postJson('/api/dialogs', [
            'corpus_id' => $this->corpus->id,
            'title' => 'Unauthenticated',
            'reference' => 'REF001'
        ]);

        $response->assertStatus(401);
    }

    public function test_can_list_dialogs()
    {
        Dialog::create([
            'corpus_id' => $this->corpus->id,
            'title' => 'Dialog 1',
            'reference' => 'REF001',
            'customer_n' => 1,
            'speaking_customer_n' => 1
        ]);

        Dialog::create([
            'corpus_id' => $this->corpus->id,
            'title' => 'Dialog 2',
            'reference' => 'REF002',
            'customer_n' => 1,
            'speaking_customer_n' => 1
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/dialogs');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'reference', 'title', 'corpus_id', 'corpus']
                ]
            ]);
    }

    public function test_can_delete_dialog()
    {
        $dialog = Dialog::create([
            'corpus_id' => $this->corpus->id,
            'title' => 'To Delete',
            'reference' => 'DEL001',
            'customer_n' => 1,
            'speaking_customer_n' => 1
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/dialogs/{$dialog->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Dialog deleted successfully']);

        $this->assertDatabaseMissing('dialogs', ['id' => $dialog->id]);
    }

    public function test_can_get_dialog_details_with_pauses()
    {
        $dialog = Dialog::create([
            'corpus_id' => $this->corpus->id,
            'title' => 'Detailed Dialog with Pauses',
            'reference' => 'DET001',
            'customer_n' => 1,
            'speaking_customer_n' => 1
        ]);

        $taskType = \App\Models\TaskType::create(['name' => 'Test Task Type']);
        $task = Task::create([
            'dialog_id' => $dialog->id,
            'type_id' => $taskType->id,
            'begin' => 0,
            'end' => 10000
        ]);

        $microTaskType = \App\Models\MicroTaskType::create(['name' => 'Test MicroTask Type']);
        $microTask = \App\Models\MicroTask::create([
            'task_id' => $task->id,
            'type_id' => $microTaskType->id,
            'begin' => 0,
            'end' => 10000
        ]);

        // Mossa con partecipante
        $participant = \App\Models\Participant::create([
            'dialog_id' => $dialog->id,
            'code' => 'P1'
        ]);

        Move::create([
            'dialog_id' => $dialog->id,
            'micro_task_id' => $microTask->id,
            'participant_id' => $participant->id,
            'begin' => 0,
            'end' => 5000,
            'annotation' => 'Hello'
        ]);

        // Pausa (senza partecipante)
        Move::create([
            'dialog_id' => $dialog->id,
            'micro_task_id' => $microTask->id,
            'participant_id' => null,
            'begin' => 5000,
            'end' => 10000,
            'annotation' => '(5.00)'
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/dialogs/{$dialog->id}");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'moves');

        $moves = $response->json('moves');
        $this->assertNotNull($moves[0]['participant_id']);
        $this->assertNull($moves[1]['participant_id']);
    }
}
