<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Corpus;
use Illuminate\Http\Request;

class CorpusController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $this->authorize('view-corpora');

        $query = Corpus::query();

        if (!auth()->user()->can('view-unpublished-corpora')) {
            $query->where('is_published', true);
        }

        $corpora = $query->get();
        return response()->json([
            'data' => $corpora
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $this->authorize('manage-corpora');

        $validated = $request->validate([
            'project_reference' => 'required|string|max:255',
            'subject_language' => 'nullable|string|max:255',
            'working_language' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'region' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'continent' => 'nullable|string|max:255',
            'title' => 'required|string|max:255',
            'depositor' => 'nullable|string|max:255',
            'contact' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_published' => 'nullable|boolean',
        ]);

        $corpus = Corpus::create($validated);

        return response()->json([
            'message' => 'Corpus creato con successo',
            'data' => $corpus
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $this->authorize('view-corpora');
        $corpus = Corpus::findOrFail($id);

        if (!$corpus->is_published && !auth()->user()->can('view-unpublished-corpora')) {
            abort(403, 'Questo corpus non è stato pubblicato.');
        }

        return response()->json([
            'data' => $corpus
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $this->authorize('manage-corpora');

        $corpus = Corpus::findOrFail($id);

        $validated = $request->validate([
            'project_reference' => 'required|string|max:255',
            'subject_language' => 'nullable|string|max:255',
            'working_language' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'region' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'continent' => 'nullable|string|max:255',
            'title' => 'required|string|max:255',
            'depositor' => 'nullable|string|max:255',
            'contact' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_published' => 'nullable|boolean',
        ]);

        $corpus->update($validated);

        return response()->json([
            'message' => 'Corpus aggiornato con successo',
            'data' => $corpus
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $this->authorize('manage-corpora');

        $corpus = Corpus::findOrFail($id);
        $corpus->delete();

        return response()->json([
            'message' => 'Corpus eliminato con successo'
        ]);
    }
}
