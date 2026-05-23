<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Corpus;
use App\Models\Dialog;
use App\Models\Task;
use App\Models\MicroTask;
use App\Models\Sequence;
use App\Models\InteractionalSegment;
use App\Models\Transaction;
use App\Models\Move;
use App\Models\Participant;
use App\Services\EafImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

class EafImportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_eaf_file_populates_database_correctly()
    {
        // 1. Setup
        $corpus = Corpus::create([
            'project_reference' => 'TEST_PROJ',
            'title' => 'Test Corpus'
        ]);

        $dialog = Dialog::create([
            'corpus_id' => $corpus->id,
            'title' => 'Test Dialog',
            'reference' => 'IT_PSPR_PN29',
            'customer_n' => 1,
            'speaking_customer_n' => 1
        ]);

        $eafPath = public_path('elan/IT_PSPR_PN29.eaf');

        if (!File::exists($eafPath)) {
            $this->markTestSkipped("Il file EAF non è presente in $eafPath");
        }

        $service = new EafImportService();

        // 2. Execution
        $service->import($eafPath, $dialog);

        // 3. Assertions
        // Verifichiamo che siano stati creati dei record
        $this->assertEquals(1, Task::count(), 'Dovrebbero essere stati importati dei Task');
        $this->assertEquals(1, InteractionalSegment::count(), 'Dovrebbero essere stati importati degli Interactional Segment');
        $this->assertEquals(3, Sequence::count(), 'Dovrebbero essere stati importati delle Sequence');
        $this->assertEquals(4, MicroTask::count(), 'Dovrebbero essere stati importati dei MicroTask');
        $this->assertEquals(4, Transaction::count(), 'Dovrebbero essere stati importati dei Transaction');
        $this->assertEquals(25, Move::count(), 'Dovrebbero essere stati importati dei Move (17 parlante + 8 pause)');
        $this->assertEquals(3, Participant::count(), 'Dovrebbero essere stati importati 3 Participant (Y_PN, A_PN29, B_PN29)');

        // Verifichiamo la presenza di pause
        $pause = Move::whereNull('participant_id')->first();
        $this->assertNotNull($pause, 'Dovrebbe esserci almeno una pausa');
        $this->assertStringStartsWith('(', $pause->annotation);
        $this->assertStringEndsWith(')', $pause->annotation);

        // Verifichiamo che le Move siano associate correttamente
        $move = Move::whereNotNull('participant_id')->first();
        $this->assertNotNull($move->participant_id, 'La Move deve avere un partecipante');
        $this->assertNotNull($move->annotation, 'La Move deve avere un testo/annotazione');

        // Verifichiamo le relazioni (almeno una dovrebbe essere popolata se il file è ben formato)
        $this->assertTrue(
            $move->micro_task_id !== null || $move->sequence_id !== null || $move->transaction_id !== null,
            'La Move dovrebbe essere associata ad almeno una entità trasversale'
        );

        echo "\nImportazione completata con successo:";
        echo "\nTasks: " . Task::count();
        echo "\nInteractional Segments: " . InteractionalSegment::count();
        echo "\nSequences: " . Sequence::count();
        echo "\nMicroTasks: " . MicroTask::count();
        echo "\nTransactions: " . Transaction::count();
        echo "\nMoves: " . Move::count();
        echo "\nParticipants: " . Participant::whereHas('moves')->count() . "\n";
    }

    public function test_import_eaf_without_transactions_is_successful()
    {
        $corpus = Corpus::create([
            'project_reference' => 'TEST_PROJ_2',
            'title' => 'Test Corpus 2'
        ]);

        $dialog = Dialog::create([
            'corpus_id' => $corpus->id,
            'title' => 'Test Dialog 2',
            'reference' => 'NO_TRANS',
            'customer_n' => 1,
            'speaking_customer_n' => 1
        ]);

        // Creiamo un file EAF minimale senza il tier delle transazioni
        $eafContent = '<?xml version="1.0" encoding="UTF-8"?>
<ANNOTATION_DOCUMENT AUTHOR="" DATE="2024-04-13T10:00:00+01:00"
    FORMAT="3.0" VERSION="3.0"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="http://www.mpi.nl/tools/elan/EAFv3.0.xsd">
    <HEADER MEDIA_FILE="" TIME_UNITS="milliseconds">
        <MEDIA_DESCRIPTOR
            MEDIA_URL="file:///Users/test/test.wav"
            MIME_TYPE="audio/x-wav" RELATIVE_MEDIA_URL="./test.wav"/>
        <PROPERTY NAME="lastUsedAnnotationId">0</PROPERTY>
    </HEADER>
    <TIME_ORDER>
        <TIME_SLOT TIME_SLOT_ID="ts1" TIME_VALUE="0"/>
        <TIME_SLOT TIME_SLOT_ID="ts2" TIME_VALUE="1000"/>
    </TIME_ORDER>
    <TIER LINGUISTIC_TYPE_REF="Parlante" PARTICIPANT="P1" TIER_ID="P1">
        <ANNOTATION>
            <ALIGNABLE_ANNOTATION ANNOTATION_ID="a1"
                TIME_SLOT_REF1="ts1" TIME_SLOT_REF2="ts2">
                <ANNOTATION_VALUE>Ciao</ANNOTATION_VALUE>
            </ALIGNABLE_ANNOTATION>
        </ANNOTATION>
    </TIER>
    <LINGUISTIC_TYPE GRAPHIC_REFERENCES="false"
        LINGUISTIC_TYPE_ID="Parlante" TIME_ALIGNABLE="true"/>
    <CONSTRAINT
        DESCRIPTION="Time subdivision of parent annotation\'s time interval, no time gaps allowed within this interval" STEREOTYPE="Time_Subdivision"/>
    <CONSTRAINT
        DESCRIPTION="Symbolic subdivision of a parent annotation. Annotations refering to the same parent are ordered" STEREOTYPE="Symbolic_Subdivision"/>
    <CONSTRAINT DESCRIPTION="1-1 association with a parent annotation" STEREOTYPE="Symbolic_Association"/>
    <CONSTRAINT
        DESCRIPTION="Time alignable annotations within the parent annotation\'s time interval, gaps are allowed" STEREOTYPE="Included_In"/>
</ANNOTATION_DOCUMENT>';

        $tempEaf = tempnam(sys_get_temp_dir(), 'eaf');
        file_put_contents($tempEaf, $eafContent);

        $service = new EafImportService();
        $service->import($tempEaf, $dialog);

        $this->assertEquals(0, Transaction::count(), 'Non dovrebbero esserci transazioni');
        $this->assertEquals(1, Move::count(), 'Dovrebbe esserci una move');
        $this->assertNull(Move::first()->transaction_id, 'La move non dovrebbe avere una transazione');
        $this->assertNull(Move::first()->sequence_id, 'La move non dovrebbe avere una sequenza');

        unlink($tempEaf);
    }

    public function test_import_eaf_with_transaction_without_participant_is_successful()
    {
        $corpus = Corpus::create([
            'project_reference' => 'TEST_PROJ_3',
            'title' => 'Test Corpus 3'
        ]);

        $dialog = Dialog::create([
            'corpus_id' => $corpus->id,
            'title' => 'Test Dialog 3',
            'reference' => 'TRANS_NO_PART',
            'customer_n' => 1,
            'speaking_customer_n' => 1
        ]);

        // File EAF con una transazione in un range temporale dove non ci sono parlanti (o tier senza participant)
        $eafContent = '<?xml version="1.0" encoding="UTF-8"?>
<ANNOTATION_DOCUMENT AUTHOR="" DATE="2024-04-13T10:00:00+01:00"
    FORMAT="3.0" VERSION="3.0"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="http://www.mpi.nl/tools/elan/EAFv3.0.xsd">
    <HEADER MEDIA_FILE="" TIME_UNITS="milliseconds">
        <PROPERTY NAME="lastUsedAnnotationId">0</PROPERTY>
    </HEADER>
    <TIME_ORDER>
        <TIME_SLOT TIME_SLOT_ID="ts1" TIME_VALUE="0"/>
        <TIME_SLOT TIME_SLOT_ID="ts2" TIME_VALUE="1000"/>
    </TIME_ORDER>
    <TIER LINGUISTIC_TYPE_REF="Transaction" TIER_ID="Transaction">
        <ANNOTATION>
            <ALIGNABLE_ANNOTATION ANNOTATION_ID="a1"
                TIME_SLOT_REF1="ts1" TIME_SLOT_REF2="ts2">
                <ANNOTATION_VALUE>Transazione senza partecipante</ANNOTATION_VALUE>
            </ALIGNABLE_ANNOTATION>
        </ANNOTATION>
    </TIER>
    <LINGUISTIC_TYPE GRAPHIC_REFERENCES="false"
        LINGUISTIC_TYPE_ID="Transaction" TIME_ALIGNABLE="true"/>
</ANNOTATION_DOCUMENT>';

        $tempEaf = tempnam(sys_get_temp_dir(), 'eaf');
        file_put_contents($tempEaf, $eafContent);

        $service = new EafImportService();
        $service->import($tempEaf, $dialog);

        $this->assertEquals(1, Transaction::count(), 'Dovrebbe esserci una transazione');
        $this->assertNull(Transaction::first()->participant_id, 'La transazione dovrebbe avere participant_id null');

        unlink($tempEaf);
    }
}
