<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Flat;
use App\Models\TenantFlatRentRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class TenantFlatRentRequestController extends Controller
{
    public function tenantRequestStore(Request $request)
    {
        if (! $this->isTenant($request->user())) {
            return response()->error('Only tenant can request a flat for rent', null, 403);
        }

        $validator = Validator::make($request->all(), [
            'flat_id' => 'required|integer|exists:flats,id',
            'request_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->error('The given data was invalid', $validator->errors(), 422);
        }

        $flat = Flat::query()
            ->with('building:id,building_owner,is_active')
            ->where('is_active', 1)
            ->find($request->flat_id);

        if (! $flat) {
            return response()->error('Flat not found', null, 404);
        }

        if (! $flat->building || ! $flat->building->is_active) {
            return response()->error('Building not available', null, 422);
        }

        if ($flat->status !== 'vacant') {
            return response()->error('Flat is not available for rent request', null, 422);
        }

        $alreadyPending = TenantFlatRentRequest::query()
            ->where('tenant_id', $request->user()->id)
            ->where('flat_id', $flat->id)
            ->where('status', 'pending')
            ->exists();

        if ($alreadyPending) {
            return response()->error('You already have a pending request for this flat', null, 422);
        }

        $rentRequest = TenantFlatRentRequest::query()->create([
            'tenant_id' => $request->user()->id,
            'flat_id' => $flat->id,
            'building_id' => $flat->building_id,
            'request_date' => $request->input('request_date', now()->toDateString()),
            'status' => 'pending',
        ]);

        $rentRequest->load([
            'flat:id,building_id,flat_number,floor_no,house_rent,status',
            'building:id,name,address_line',
        ]);

        return response()->success($rentRequest, 'Flat rent request submitted successfully', 201);
    }

    public function tenantRequestList(Request $request)
    {
        if (! $this->isTenant($request->user())) {
            return response()->error('Only tenant can view tenant request list', null, 403);
        }

        $requests = TenantFlatRentRequest::query()
            ->where('tenant_id', $request->user()->id)
            ->with([
                'flat:id,building_id,flat_number,floor_no,house_rent,status,tenant_id',
                'building:id,name,address_line',
            ])
            ->latest('id')
            ->get();

        return response()->success($requests, 'Tenant rent request list found', 200);
    }

    public function landownerRequestList(Request $request)
    {
        if (! $this->isLandowner($request->user())) {
            return response()->error('Only landowner can view this request list', null, 403);
        }

        $validator = Validator::make($request->all(), [
            'status' => ['nullable', Rule::in(['pending', 'approved', 'rejected', 'cancelled'])],
            'building_id' => 'nullable|integer|exists:buildings,id',
        ]);

        if ($validator->fails()) {
            return response()->error('The given data was invalid', $validator->errors(), 422);
        }

        $requests = TenantFlatRentRequest::query()
            ->with([
                'tenant:id,name,phone_number,photo',
                'flat:id,building_id,flat_number,floor_no,house_rent,status,tenant_id',
                'building:id,name,address_line,building_owner',
            ])
            ->whereHas('building', function ($query) use ($request) {
                $query->where('building_owner', $request->user()->id);
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('building_id'), fn ($q) => $q->where('building_id', $request->building_id))
            ->latest('id')
            ->get();

        return response()->success($requests, 'Landowner rent request list found', 200);
    }

    public function landownerRequestAction(Request $request, $id)
    {
        if (! $this->isLandowner($request->user())) {
            return response()->error('Only landowner can approve or reject request', null, 403);
        }

        $validator = Validator::make($request->all(), [
            'action' => ['required', Rule::in(['accept', 'reject'])],
        ]);

        if ($validator->fails()) {
            return response()->error('The given data was invalid', $validator->errors(), 422);
        }

        $rentRequest = TenantFlatRentRequest::query()
            ->with(['flat:id,building_id,status,tenant_id', 'building:id,building_owner'])
            ->find($id);

        if (! $rentRequest) {
            return response()->error('Rent request not found', null, 404);
        }

        if ((int) data_get($rentRequest, 'building.building_owner') !== (int) $request->user()->id) {
            return response()->error('You are not authorized for this request', null, 403);
        }

        if ($rentRequest->status !== 'pending') {
            return response()->error('Only pending request can be processed', null, 422);
        }

        $action = $request->input('action');

        if ($action === 'accept' && (! $rentRequest->flat || $rentRequest->flat->status !== 'vacant')) {
            return response()->error('Flat is not vacant now', null, 422);
        }

        DB::beginTransaction();
        try {
            if ($action === 'accept') {
                $rentRequest->update([
                    'status' => 'approved',
                ]);

                $rentRequest->flat->update([
                    'tenant_id' => $rentRequest->tenant_id,
                    'status' => 'rent',
                    'vacant_date' => null,
                ]);

                TenantFlatRentRequest::query()
                    ->where('flat_id', $rentRequest->flat_id)
                    ->where('id', '!=', $rentRequest->id)
                    ->where('status', 'pending')
                    ->update([
                        'status' => 'rejected',
                        'updated_at' => now(),
                    ]);
            } else {
                $rentRequest->update([
                    'status' => 'rejected',
                ]);
            }

            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();

            return response()->error($th->getMessage(), null, 500);
        }

        $rentRequest->load([
            'tenant:id,name,phone_number',
            'flat:id,building_id,flat_number,status,tenant_id',
            'building:id,name,address_line',
        ]);

        return response()->success($rentRequest, 'Rent request processed successfully', 200);
    }

    private function isTenant($user): bool
    {
        $slug = strtolower((string) data_get($user, 'user_type.slug', ''));

        return $slug === 'tenant';
    }

    private function isLandowner($user): bool
    {
        $slug = strtolower((string) data_get($user, 'user_type.slug', ''));

        return in_array($slug, ['landowner', 'lanowner'], true);
    }
}
