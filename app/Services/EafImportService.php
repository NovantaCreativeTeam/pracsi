<?php

namespace App\Services;

use App\Models\Dialog;
use App\Models\InteractionalSegment;
use App\Models\MicroTask;
use App\Models\MicroTaskType;
use App\Models\Move;
use App\Models\MoveLevel1;
use App\Models\MoveLevel2;
use App\Models\MoveLevel3;
use App\Models\Participant;
use App\Models\Sequence;
use App\Models\SequenceType;
use App\Models\Task;
use App\Models\TaskType;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use SimpleXMLElement;

class EafImportService
{
    /**
     * Importa un file EAF nel database, associandolo a un Dialog esistente.
     *
     * @param string $filePath Percorso del file .eaf
     * @param Dialog $dialog Istanza del Dialog a cui associare i dati
     * @return void
     * @throws \Exception
     */
    public function import(string $filePath, Dialog $dialog): void
    {
        if (!file_exists($filePath)) {
            throw new \Exception("File non trovato: $filePath");
        }

        $xml = simplexml_load_file($filePath);
        if ($xml === false) {
            throw new \Exception("Errore nel caricamento del file XML.");
        }

        // 1. Parsing dei Time Slots
        $timeslots = [];
        foreach ($xml->TIME_ORDER->TIME_SLOT as $ts) {
            $id = (string) $ts['TIME_SLOT_ID'];
            $value = (int) $ts['TIME_VALUE'];
            $timeslots[$id] = $value;
        }

        // 2. Lettura dei TIER e delle loro annotazioni
        $tiers = [];
        foreach ($xml->TIER as $tier) {
            $tierId = (string) $tier['TIER_ID'];
            $participant = (string) $tier['PARTICIPANT'];
            $lingType = (string) $tier['LINGUISTIC_TYPE_REF'];

            $annotations = [];

            // Gestione ALIGNABLE_ANNOTATION
            foreach ($tier->ANNOTATION as $annotationNode) {
                if (isset($annotationNode->ALIGNABLE_ANNOTATION)) {
                    $ann = $annotationNode->ALIGNABLE_ANNOTATION;
                    $beginTs = (string) $ann['TIME_SLOT_REF1'];
                    $endTs = (string) $ann['TIME_SLOT_REF2'];
                    $annotations[] = [
                        'id' => (string) $ann['ANNOTATION_ID'],
                        'begin' => $timeslots[$beginTs] ?? null,
                        'end' => $timeslots[$endTs] ?? null,
                        'value' => trim((string) $ann->ANNOTATION_VALUE),
                    ];
                }

                // Gestione REF_ANNOTATION (le annotazioni referenziate ereditano i tempi)
                if (isset($annotationNode->REF_ANNOTATION)) {
                    $ann = $annotationNode->REF_ANNOTATION;
                    $refId = (string) $ann['ANNOTATION_REF'];
                    $annotations[] = [
                        'id' => (string) $ann['ANNOTATION_ID'],
                        'ref' => $refId,
                        'value' => trim((string) $ann->ANNOTATION_VALUE),
                        // begin ed end verranno risolti in seguito se necessario
                    ];
                }
            }

            $tiers[$tierId] = [
                'tier_id' => $tierId,
                'participant' => $participant,
                'linguistic_type' => $lingType,
                'annotations' => $annotations,
            ];
        }

        // Risoluzione tempi per REF_ANNOTATION
        foreach ($tiers as $tierId => &$tierData) {
            foreach ($tierData['annotations'] as &$ann) {
                if (isset($ann['ref']) && !isset($ann['begin'])) {
                    $refAnn = $this->findAnnotationById($tiers, $ann['ref']);
                    if ($refAnn) {
                        $ann['begin'] = $refAnn['begin'];
                        $ann['end'] = $refAnn['end'];
                    }
                }
            }
        }

        // 3. Salvataggio nel database
        DB::transaction(function () use ($dialog, $tiers) {
            // Pulizia dati esistenti (opzionale, dipende dal requisito)
            // $dialog->tasks()->delete();
            // $dialog->interactionalSegments()->delete();
            // ...

            // Import delle Participant prima delle Move per poterle usare nelle Transaction
            $participantsMap = [];
            foreach ($tiers as $tier) {
                if ($tier['linguistic_type'] === 'Parlante') {
                    $participantName = $tier['participant'];
                    $participant = Participant::firstOrCreate([
                        'dialog_id' => $dialog->id,
                        'code' => $participantName,
                    ], [
                        'full_name' => $participantName,
                    ]);
                    $participantsMap[$participantName] = $participant->id;
                }
            }

            // Mappatura entità trasversali per recupero veloce
            $tasks = $this->importTransversalTier($tiers, 'Task', Task::class, ['dialog_id' => $dialog->id], 'type');
            $intSegments = $this->importTransversalTier($tiers, 'Interactional segment', InteractionalSegment::class, ['dialog_id' => $dialog->id]);
            $sequences = $this->importTransversalTier($tiers, 'Sequence', Sequence::class, [], 'type', 'interactional_segment_id', $intSegments);
            $microTasks = $this->importTransversalTier($tiers, 'Micro task', MicroTask::class, [], 'type', 'task_id', $tasks);
            $transactions = $this->importTransactions($tiers, $dialog, $participantsMap);

            // Import delle Note (opzionale, ma presente in app.py)
            $this->importNotes($tiers, $dialog);

            // 4. Calcolo delle pause (basato su app.py)
            $allParlanteAnns = [];
            foreach ($tiers as $tier) {
                if ($tier['linguistic_type'] === 'Parlante') {
                    foreach ($tier['annotations'] as $ann) {
                        if ($ann['begin'] !== null && $ann['end'] !== null) {
                            $allParlanteAnns[] = $ann;
                        }
                    }
                }
            }

            // Ordina per tempo di inizio
            usort($allParlanteAnns, function ($a, $b) {
                return $a['begin'] <=> $b['begin'];
            });

            // Genera pause dai gap tra annotazioni
            $prevEnd = 0;
            foreach ($allParlanteAnns as $ann) {
                if ($ann['begin'] > $prevEnd) {
                    $durationSec = ($ann['begin'] - $prevEnd) / 1000;

                    // Considera la pausa solo se è più lunga di 0.2s
                    if ($durationSec >= 0.2) {
                        $pauseMove = new Move();
                        $pauseMove->begin = $prevEnd;
                        $pauseMove->end = $ann['begin'];
                        $pauseMove->dialog_id = $dialog->id;
                        $pauseMove->annotation = sprintf('(%.2f)', $durationSec);
                        $pauseMove->participant_id = null; // Le pause non hanno partecipante

                        // Associazione con entità trasversali
                        $pauseMove->micro_task_id = $this->findIdByTime($microTasks, $prevEnd, $ann['begin']);
                        $pauseMove->sequence_id = $this->findIdByTime($sequences, $prevEnd, $ann['begin']);
                        $pauseMove->transaction_id = $this->findIdByTime($transactions, $prevEnd, $ann['begin']);

                        $pauseMove->save();
                    }
                }
                $prevEnd = max($prevEnd, $ann['end']);
            }

            // Import delle Move (basate su linguistic_type "Parlante")
            foreach ($tiers as $tier) {
                if ($tier['linguistic_type'] === 'Parlante') {
                    $participantName = $tier['participant'];
                    $participantId = $participantsMap[$participantName];

                    foreach ($tier['annotations'] as $ann) {
                        if ($ann['begin'] === null || $ann['end'] === null) continue;

                        $move = new Move();
                        $move->begin = $ann['begin'];
                        $move->end = $ann['end'];
                        $move->dialog_id = $dialog->id;
                        $move->annotation = $ann['value'];
                        $move->participant_id = $participantId;

                        // Associazione con entità trasversali in base al tempo
                        $move->micro_task_id = $this->findIdByTime($microTasks, $ann['begin'], $ann['end']);
                        $move->sequence_id = $this->findIdByTime($sequences, $ann['begin'], $ann['end']);
                        $move->transaction_id = $this->findIdByTime($transactions, $ann['begin'], $ann['end']);

                        // Move Level 1, 2, 3
                        $move->move_level_1_id = $this->findMoveLevelId($tiers, 'MoveLev1', $ann['begin'], $ann['end'], $participantName, MoveLevel1::class);
                        $move->move_level_2_id = $this->findMoveLevelId($tiers, 'MoveLev2', $ann['begin'], $ann['end'], $participantName, MoveLevel2::class);
                        $move->move_level_3_id = $this->findMoveLevelId($tiers, 'MoveLev3', $ann['begin'], $ann['end'], $participantName, MoveLevel3::class);

                        $move->save();
                    }
                }
            }
        });
    }

    private function findAnnotationById(array $tiers, string $id): ?array
    {
        foreach ($tiers as $tier) {
            foreach ($tier['annotations'] as $ann) {
                if ($ann['id'] === $id) {
                    return $ann;
                }
            }
        }
        return null;
    }

    private function importTransversalTier(array $tiers, string $tierName, string $modelClass, array $extraData = [], string $typeRelation = null, string $parentField = null, array $parentEntities = []): array
    {
        $imported = [];
        if (!isset($tiers[$tierName])) return [];

        foreach ($tiers[$tierName]['annotations'] as $ann) {
            $data = array_merge($extraData, [
                'begin' => $ann['begin'],
                'end' => $ann['end'],
            ]);

            $entity = new $modelClass($data);

            if ($typeRelation) {
                $typeClass = $modelClass . 'Type';
                $typeName = $ann['value'] ?: 'Default';
                $type = $typeClass::firstOrCreate(['name' => $typeName]);
                $entity->type_id = $type->id;
            }

            if ($parentField && $parentEntities) {
                $parentId = $this->findIdByTime($parentEntities, $ann['begin'], $ann['end']);
                if ($parentId) {
                    $entity->$parentField = $parentId;
                }
            }

            $entity->save();
            $imported[] = [
                'id' => $entity->id,
                'begin' => $ann['begin'],
                'end' => $ann['end'],
            ];
        }

        return $imported;
    }

    private function importTransactions(array $tiers, Dialog $dialog, array $participantsMap): array
    {
        $imported = [];
        if (!isset($tiers['Transaction'])) return [];

        foreach ($tiers['Transaction']['annotations'] as $ann) {
            $participantId = null;
            $tierParticipant = $tiers['Transaction']['participant'];

            if ($tierParticipant && isset($participantsMap[$tierParticipant])) {
                $participantId = $participantsMap[$tierParticipant];
            } else {
                // Se il tier non ha un partecipante specifico, cerchiamo tra i parlanti in quel range temporale
                foreach ($tiers as $tier) {
                    if ($tier['linguistic_type'] === 'Parlante') {
                        foreach ($tier['annotations'] as $pAnn) {
                            if (!($pAnn['end'] < $ann['begin'] || $pAnn['begin'] > $ann['end'])) {
                                $participantId = $participantsMap[$tier['participant']] ?? null;
                                if ($participantId) break 2;
                            }
                        }
                    }
                }
            }

            // Se ancora non abbiamo un partecipante, il participant_id rimarrà null
            // dato che ora è opzionale nel database.

            $transaction = Transaction::create([
                'dialog_id' => $dialog->id,
                'participant_id' => $participantId,
                'begin' => $ann['begin'],
                'end' => $ann['end'],
                'content' => $ann['value'] ?: '',
            ]);
            $imported[] = [
                'id' => $transaction->id,
                'begin' => $ann['begin'],
                'end' => $ann['end'],
            ];
        }
        return $imported;
    }

    private function importNotes(array $tiers, Dialog $dialog): void
    {
        if (!isset($tiers['Note'])) return;

        foreach ($tiers['Note']['annotations'] as $ann) {
            \App\Models\Note::create([
                'dialog_id' => $dialog->id,
                'begin' => $ann['begin'],
                'end' => $ann['end'],
                'content' => $ann['value'],
            ]);
        }
    }

    private function findIdByTime(array $entities, int $begin, int $end): ?int
    {
        foreach ($entities as $entity) {
            // Logica di sovrapposizione temporale (almeno parziale)
            if (!($entity['end'] < $begin || $entity['begin'] > $end)) {
                return $entity['id'];
            }
        }
        return null;
    }

    private function findMoveLevelId(array $tiers, string $lingType, int $begin, int $end, string $participant, string $modelClass): ?int
    {
        foreach ($tiers as $tier) {
            if ($tier['linguistic_type'] === $lingType && $tier['participant'] === $participant) {
                foreach ($tier['annotations'] as $ann) {
                    if (!($ann['end'] < $begin || $ann['begin'] > $end)) {
                        $value = $ann['value'] ?: 'Default';
                        $level = $modelClass::firstOrCreate(['name' => $value]);
                        return $level->id;
                    }
                }
            }
        }
        return null;
    }
}
