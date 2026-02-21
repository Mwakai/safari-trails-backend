<?php

namespace App\Http\Controllers;

use App\Http\Resources\CompanyResource;
use App\Models\Company;
use App\Traits\ApiResponses;
use Illuminate\Http\JsonResponse;

class PublicCompanyController extends Controller
{
    use ApiResponses;

    public function show(string $slug): JsonResponse
    {
        $company = Company::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $company->load(['logo', 'coverImage']);

        return $this->ok('Company retrieved', [
            'company' => new CompanyResource($company),
        ]);
    }
}
