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
use App\Models\NonVerbalAction;
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

            // Import delle Move (basate su linguistic_type "Parlante")
            $allMoves = [];

            // 1. Prepare pauses
            $prevEnd = 0;
            foreach ($allParlanteAnns as $ann) {
                if ($ann['begin'] > $prevEnd) {
                    $durationSec = ($ann['begin'] - $prevEnd) / 1000;

                    if ($durationSec >= 0.2) {
                        $allMoves[] = [
                            'begin' => $prevEnd,
                            'end' => $ann['begin'],
                            'dialog_id' => $dialog->id,
                            'annotation' => sprintf('(%.2f)', $durationSec),
                            'participant_id' => null,
                            'is_pause' => true,
                        ];
                    }
                }
                $prevEnd = max($prevEnd, $ann['end']);
            }

            // 2. Prepare speaker moves
            foreach ($tiers as $tier) {
                if ($tier['linguistic_type'] === 'Parlante') {
                    $participantName = $tier['participant'];
                    $participantId = $participantsMap[$participantName];

                    foreach ($tier['annotations'] as $ann) {
                        if ($ann['begin'] === null || $ann['end'] === null) continue;

                        $allMoves[] = [
                            'begin' => $ann['begin'],
                            'end' => $ann['end'],
                            'dialog_id' => $dialog->id,
                            'annotation' => $ann['value'],
                            'participant_id' => $participantId,
                            'participant_name' => $participantName,
                            'is_pause' => false,
                        ];
                    }
                }
            }

            // 3. Sort all moves by begin time
            usort($allMoves, function ($a, $b) {
                if ($a['begin'] === $b['begin']) {
                    return $a['end'] <=> $b['end'];
                }
                return $a['begin'] <=> $b['begin'];
            });

            // 4. Save moves with turn number
            $turnCounter = 1;
            foreach ($allMoves as $moveData) {
                $move = new Move();
                $move->begin = $moveData['begin'];
                $move->end = $moveData['end'];
                $move->dialog_id = $moveData['dialog_id'];
                $move->annotation = $moveData['annotation'];
                $move->participant_id = $moveData['participant_id'];

                if (!$moveData['is_pause']) {
                    $move->turn = $turnCounter++;
                    $participantName = $moveData['participant_name'];
                    // Move Level 1, 2, 3
                    $moveLevel1Ids = $this->findMoveLevelIds($tiers, 'MoveLev1', $move->begin, $move->end, $participantName, MoveLevel1::class);
                    $moveLevel2Ids = $this->findMoveLevelIds($tiers, 'MoveLev2', $move->begin, $move->end, $participantName, MoveLevel2::class);
                    $moveLevel3Ids = $this->findMoveLevelIds($tiers, 'MoveLev3', $move->begin, $move->end, $participantName, MoveLevel3::class);
                    $nonVerbalActionIds = $this->findMoveLevelIds($tiers, 'Non verbal action', $move->begin, $move->end, $participantName, NonVerbalAction::class);
                } else {
                    $move->turn = null;
                }

                // Associazione con entità trasversali in base al tempo
                $move->micro_task_id = $this->findIdByTime($microTasks, $move->begin, $move->end);
                $move->sequence_id = $this->findIdByTime($sequences, $move->begin, $move->end);
                $move->transaction_id = $this->findIdByTime($transactions, $move->begin, $move->end);

                $move->save();

                if (!$moveData['is_pause']) {
                    $move->moveLevel1s()->sync($moveLevel1Ids);
                    $move->moveLevel2s()->sync($moveLevel2Ids);
                    $move->moveLevel3s()->sync($moveLevel3Ids);
                    $move->nonVerbalActions()->sync($nonVerbalActionIds);
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

    private function findMoveLevelIds(array $tiers, string $lingType, int $begin, int $end, string $participant, string $modelClass): array
    {
        $ids = [];
        foreach ($tiers as $tier) {
            if ($tier['linguistic_type'] === $lingType && $tier['participant'] === $participant) {
                foreach ($tier['annotations'] as $ann) {
                    if (!($ann['end'] < $begin || $ann['begin'] > $end)) {
                        $value = $ann['value'] ?: 'Default';
                        $level = $modelClass::firstOrCreate(['name' => $value]);
                        $ids[] = $level->id;
                    }
                }
            }
        }
        return array_unique($ids);
    }
}
