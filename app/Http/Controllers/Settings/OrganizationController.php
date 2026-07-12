<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\EditOrganizationRequest;
use App\Http\Requests\Settings\UpdateOrganizationRequest;
use App\Support\OrganizationSettings;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationController extends Controller
{
    public function edit(EditOrganizationRequest $request): Response
    {
        return Inertia::render('settings/organization', [
            'organization' => OrganizationSettings::all(),
        ]);
    }

    public function update(UpdateOrganizationRequest $request): RedirectResponse
    {
        OrganizationSettings::save($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Organization settings updated.')]);

        return to_route('organization.edit');
    }
}
