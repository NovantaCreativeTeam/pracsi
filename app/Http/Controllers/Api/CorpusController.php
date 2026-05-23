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
        $corpora = Corpus::all(['id', 'title', 'project_reference']);
        return response()->json($corpora);
    }
}
