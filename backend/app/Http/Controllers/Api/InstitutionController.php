<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\InstitutionResource;
use App\Models\Institution;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class InstitutionController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return InstitutionResource::collection(
            Institution::query()->orderBy('type')->orderBy('name')->get()
        );
    }
}
