<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCompanyRequest;
use App\Http\Requests\UpdateCompanyRequest;
use App\Http\Resources\CompanyListResource;
use App\Http\Resources\CompanyResource;
use App\Models\Company;
use App\Traits\ApiResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class CompanyController extends Controller
{
    use ApiResponses;

    public function index(): JsonResponse
    {
        $response = Gate::inspect('viewAny', Company::class);

        if ($response->denied()) {
            return $this->error($response->message(), 403);
        }

        $companies = Company::query()->with('logo')->paginate(15);

        return $this->ok('Companies retrieved', [
            'companies' => CompanyListResource::collection($companies),
            'meta' => [
                'current_page' => $companies->currentPage(),
                'last_page' => $companies->lastPage(),
                'per_page' => $companies->perPage(),
                'total' => $companies->total(),
            ],
        ]);
    }

    public function show(Company $company): JsonResponse
    {
        $response = Gate::inspect('view', $company);

        if ($response->denied()) {
            return $this->error($response->message(), 403);
        }

        $company->load(['logo', 'coverImage']);

        return $this->ok('Company retrieved', [
            'company' => new CompanyResource($company),
        ]);
    }

    public function store(StoreCompanyRequest $request): JsonResponse
    {
        $response = Gate::inspect('create', Company::class);

        if ($response->denied()) {
            return $this->error($response->message(), 403);
        }

        $data = $request->validated();
        $data['slug'] ??= Str::slug($data['name']);

        $company = Company::create($data);
        $company->load(['logo', 'coverImage']);

        return $this->success('Company created successfully', [
            'company' => new CompanyResource($company),
        ], 201);
    }

    public function update(UpdateCompanyRequest $request, Company $company): JsonResponse
    {
        $response = Gate::inspect('update', $company);

        if ($response->denied()) {
            return $this->error($response->message(), 403);
        }

        $company->update($request->validated());
        $company->load(['logo', 'coverImage']);

        return $this->ok('Company updated successfully', [
            'company' => new CompanyResource($company),
        ]);
    }

    public function destroy(Company $company): JsonResponse
    {
        $response = Gate::inspect('delete', $company);

        if ($response->denied()) {
            return $this->error($response->message(), 403);
        }

        $company->delete();

        return $this->ok('Company deleted successfully');
    }
}
