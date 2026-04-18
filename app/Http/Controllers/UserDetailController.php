<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\UserDetail;
use App\Http\Requests\UserDetailRequest;
use App\Http\Resources\UserDetailResource;
use Illuminate\Http\Request;

class UserDetailController extends Controller
{
    public function index()
    {
        return UserDetailResource::collection(UserDetail::all());
    }

    public function store(UserDetailRequest $request)
    {
        $userDetail = UserDetail::create($request->validated());
        return new UserDetailResource($userDetail);
    }

    public function show(UserDetail $userDetail)
    {
        return new UserDetailResource($userDetail);
    }

    public function update(UserDetailRequest $request, UserDetail $userDetail)
    {
        $userDetail->update($request->validated());
        return new UserDetailResource($userDetail);
    }

    public function destroy(UserDetail $userDetail)
    {
        $userDetail->delete();
        return response()->noContent();
    }
}
