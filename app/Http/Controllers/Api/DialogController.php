<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Dialog;
use App\Models\MicroTask;
use App\Models\Move;
use App\Models\Task;
use App\Services\EafImportService;
use App\Services\ImdiImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class DialogController extends Controller
{
    protected $eafImportService;
    protected $imdiImportService;

    public function __construct(EafImportService $eafImportService, ImdiImportService $imdiImportService)
    {
        $this->eafImportService = $eafImportService;
        $this->imdiImportService = $imdiImportService;
    }

    public function index(Request $request)
    {
        $this->authorize('view-dialogs');

        $query = Dialog::with('corpus:id,title,project_reference');

        if ($request->has('corpus_id')) {
            $query->where('corpus_id', $request->corpus_id);
        }

        $dialogs = $query->get();

        return response()->json([
            'data' => $dialogs
        ]);
    }

    public function show($id)
    {
        $this->authorize('view-dialogs');
        $dialog = Dialog::with([
            'corpus',
            'participants',
            'notes',
        ])->findOrFail($id);

        $moves = $dialog->moves()
            ->with([
                'participant',
                'microTask.task.type',
                'microTask.type',
                'sequence.interactionalSegment',
                'sequence.type',
                'transaction',
                'moveLevel1s',
                'moveLevel2s',
                'moveLevel3s',
                'nonVerbalActions',
            ])
            ->orderBy('begin')
            ->get();

        return response()->json([
            'data' => $dialog,
            'moves' => $moves
        ]);
    }

    public function destroy($id)
    {
        $this->authorize('manage-dialogs');
        $dialog = Dialog::findOrFail($id);
        $dialog->delete();

        return response()->json([
            'message' => 'Dialog deleted successfully'
        ]);
    }

    /**
     * Store a newly created dialog and import data from EAF file.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $this->authorize('manage-dialogs');
        $validator = Validator::make($request->all(), [
            'corpus_id' => 'required|exists:corpora,id',
            'title' => 'required_without:imdi_file|string|max:255',
            'reference' => 'required|string|max:255',
            'eaf_file' => 'required|file',
            'imdi_file' => 'nullable|file',
            'wav_file' => 'nullable|file|mimes:wav',
            'date' => 'nullable|string',
            'description' => 'nullable|string',
            'genre' => 'nullable|string',
            'subgenre' => 'nullable|string',
            'topic' => 'nullable|string',
            'subject_languages' => 'nullable|string',
            'working_languages' => 'nullable|string',
            'city' => 'nullable|string',
            'region' => 'nullable|string',
            'country' => 'nullable|string',
            'continent' => 'nullable|string',
            'researcher_involvement' => 'nullable|string',
            'planning_type' => 'nullable|string',
            'social_context' => 'nullable|string',
            'customer_type' => 'nullable|string',
            'customer_profile' => 'nullable|string',
            'customer_n' => 'nullable|integer',
            'speaking_customer_n' => 'nullable|integer',
            'speakers_features' => 'nullable|string',
            'restaurant_title' => 'nullable|string',
            'restaurant_features' => 'nullable|string',
            'menu_type' => 'nullable|string',
            'meal' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $validatedData = $validator->validated();

        // Default values for mandatory numeric fields if they are not provided
        if (!isset($validatedData['customer_n'])) {
            $validatedData['customer_n'] = 1;
        }
        if (!isset($validatedData['speaking_customer_n'])) {
            $validatedData['speaking_customer_n'] = 0;
        }

        $eafFile = $request->file('eaf_file');
        $imdiFile = $request->file('imdi_file');
        $wavFile = $request->file('wav_file');
        unset($validatedData['eaf_file']);
        unset($validatedData['imdi_file']);
        unset($validatedData['wav_file']);

        // Salviamo temporaneamente il file per l'importazione
        $filename = $eafFile->getClientOriginalName();
        $tempPath = $eafFile->storeAs('temp_eaf', $filename, 'local');

        // Se lo storage è faked, il file non esiste fisicamente in storage_path
        // ma l'EafImportService usa fopen/file_exists.
        // Dobbiamo assicurarci che l'EafImportService possa leggerlo.
        $fullPath = Storage::disk('local')->path($tempPath);

        $imdiData = null;
        if ($imdiFile) {
            $imdiTempPath = $imdiFile->storeAs('temp_imdi', $imdiFile->getClientOriginalName(), 'local');
            $imdiFullPath = Storage::disk('local')->path($imdiTempPath);
            $imdiData = $this->imdiImportService->parse($imdiFullPath);

            // Merge IMDI data with validated data (manual fields have priority)
            $validatedData = array_merge($imdiData['dialog'], array_filter($validatedData));

            if (file_exists($imdiFullPath)) {
                unlink($imdiFullPath);
            }
        }

        try {
            $result = DB::transaction(function () use ($validatedData, $fullPath, $eafFile, $wavFile, $imdiData) {
                // Prepariamo i path definitivi
                $dialog = Dialog::create($validatedData);

                // Import participants from IMDI if available
                if ($imdiData && !empty($imdiData['participants'])) {
                    $this->imdiImportService->importParticipants($dialog, $imdiData['participants']);
                }

                // Salvataggio permanente dell'audio se presente
                if ($wavFile) {
                    $audioPath = $wavFile->storeAs('audio', "dialog_{$dialog->id}.wav", 'public');
                    $dialog->audio_path = '/storage/' . $audioPath;
                }

                // Salvataggio permanente dell'EAF per riferimento futuro
                $eafPermanentPath = $eafFile->storeAs('elan', "dialog_{$dialog->id}.eaf", 'public');
                $dialog->eaf_path = '/storage/' . $eafPermanentPath;

                $dialog->save();

                $this->eafImportService->import($fullPath, $dialog);

                return response()->json([
                    'message' => 'Dialog created and data imported successfully',
                    'data' => $dialog->load(['participants', 'tasks', 'interactionalSegments', 'transactions', 'notes'])
                ], 201);
            });

            // Rimuoviamo il file temporaneo dopo l'importazione
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error("Error importing EAF file: " . $e->getMessage(), [
                'exception' => $e,
                'path' => $fullPath
            ]);

            return response()->json([
                'message' => 'An error occurred during import',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
