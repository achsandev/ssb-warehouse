<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\User;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with(['userDetails', 'roles'])->get();
        // Tambahkan role_names (array nama role) untuk setiap user
        $users->transform(function ($user) {
            $userArray = $user->toArray();

            return $userArray;
        });

        return $users;
    }

    public function store(UserRequest $request)
    {
        $user = User::create($request->only(['name', 'email', 'password']));

        // Assign role to user
        $roleId = $request->input('role_id');
        $role = \App\Models\Role::find($roleId);
        if ($role) {
            $user->assignRole($role->name);
        }

        if ($request->has('user_detail')) {
            $user->userDetails()->create($request->user_detail);
        }

        return $user->load('userDetails');
    }

    public function show($uid)
    {
        $user = User::where('uid', $uid)
            ->with(['userDetails', 'roles'])
            ->firstOrFail();

        // Sertakan nama role (array of string) di response
        $userArray = $user->toArray();
        $userArray['role_names'] = $user->getRoleNames();

        return $userArray;
    }

    public function update(UserRequest $request, $uid)
    {
        $user = User::where('uid', $uid)->firstOrFail();

        $data = $request->only(['name', 'email']);
        if ($request->filled('password')) {
            $data['password'] = $request->input('password');
        }

        $user->update($data);
        if ($request->has('user_detail')) {
            $user->userDetails()->updateOrCreate([], $request->user_detail);
        }

        return $user->load('userDetails');
    }

    public function destroy($uid)
    {
        $user = User::where('uid', $uid)->firstOrFail();
        $user->delete();

        return response()->noContent();
    }

    public function detail($uid)
    {
        $user = User::where('uid', $uid)->with('userDetails')->firstOrFail();

        return $user->userDetails;
    }
}
