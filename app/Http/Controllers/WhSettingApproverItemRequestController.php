<?php

namespace App\Http\Controllers;

use App\Http\Requests\SettingApproverItemRequest\StoreRequest;
use App\Http\Requests\SettingApproverItemRequest\UpdateRequest;
use App\Http\Resources\WhSettingApproverItemRequestResource;
use App\Models\WhSettingApproverItemRequest;

class WhSettingApproverItemRequestController extends Controller
{
    public function index()
    {
        return WhSettingApproverItemRequestResource::collection(WhSettingApproverItemRequest::with(['requesterRole', 'approverRole'])->get());
    }

    public function store(StoreRequest $request)
    {
        $setting = WhSettingApproverItemRequest::create($request->validated());

        return new WhSettingApproverItemRequestResource($setting->load(['requesterRole', 'approverRole']));
    }

    // Show by UID
    public function show($uid)
    {
        $setting = WhSettingApproverItemRequest::where('uid', $uid)->with(['requesterRole', 'approverRole'])->firstOrFail();

        return new WhSettingApproverItemRequestResource($setting);
    }

    // Update by UID
    public function update(UpdateRequest $request, $uid)
    {
        $setting = WhSettingApproverItemRequest::where('uid', $uid)->firstOrFail();
        $setting->update($request->validated());

        return new WhSettingApproverItemRequestResource($setting->load(['requesterRole', 'approverRole']));
    }

    // Delete by UID
    public function destroy($uid)
    {
        $setting = WhSettingApproverItemRequest::where('uid', $uid)->firstOrFail();
        $setting->delete();

        return response()->noContent();
    }
}
