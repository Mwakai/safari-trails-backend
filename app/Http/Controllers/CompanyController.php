<?php

namespace App\Http\Controllers;

use App\Http\Resources\CompanyResource;
use App\Models\Company;
use App\Traits\ApiResponses;
use Illuminate\Http\JsonResponse;

class CompanyController extends Controller
{
    use ApiResponses;

    public function index(): JsonResponse
    {
        $companies = Company::all();

        return $this->ok('Companies retrieved', [
            'companies' => CompanyResource::collection($companies),
        ]);
    }

    public function show(Company $company): JsonResponse
    {
        return $this->ok('Company retrieved', [
            'company' => new CompanyResource($company),
        ]);
    }
}
