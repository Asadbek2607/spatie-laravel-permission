<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB as FacadesDB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    function __construct()
    {
         $this->middleware('permission:user-list|user-create|user-edit|user-delete', ['only' => ['index','show']]);
         $this->middleware('permission:user-create', ['only' => ['create','store']]);
         $this->middleware('permission:user-edit', ['only' => ['edit','update']]);
         $this->middleware('permission:user-delete', ['only' => ['destroy']]);
    }

    // show all users
    public function index(Request $request): View
    {
        $data = User::latest()->paginate(5);

        return view('users.index',compact('data'))
            ->with('i', ($request->input('page', 1) - 1) * 5);
    }

    // create the user
    public function create(): View
    {
        // get all roles from db
        $roles = Role::pluck('name','name')->all();

        // set default role
        $defaultRoles = ['Default'];
        return view('users.create',compact('roles', 'defaultRoles'));
    }

    // store the user in the database
    public function store(Request $request): RedirectResponse
    {
        $this->validate($request, [
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|same:confirm-password',
        ]);

        $input = $request->all();
        $input['password'] = Hash::make($input['password']);

        $user = User::create($input);
        $defaultRole = Role::where('name', 'Default')->first();
        $user->assignRole($defaultRole);
        return redirect()->route('users.index')
                        ->with('success','User created successfully');
    }

    // show the user
    public function show(User $user): View
    {
        $user = User::find($user->id);
        return view('users.show',compact('user'));
    }

    // edit the user
    public function edit(User $user): View
    {
        $user = User::find($user->id);
        $roles = Role::pluck('name','name')->all();
        $userRole = $user->roles->pluck('name','name')->all();

        return view('users.edit',compact('user','roles','userRole'));
    }

    // update the user
    public function update(Request $request, $id): RedirectResponse
    {
        $this->validate($request, [
            'name' => 'required',
            'email' => 'required|email|unique:users,email,'.$id,
            'password' => 'sometimes|same:confirm-password', // Use 'sometimes' to allow optional password update
            'roles' => 'required'
        ]);

        $input = $request->all();

        $user = User::find($id);

        // Hash the password if it's included in the request
        if (!empty($input['password'])) {
            $input['password'] = Hash::make($input['password']);
        } else {
            // If the password field is empty, remove it from the input to avoid overwriting with a null value
            unset($input['password']);
        }

        $user->update($input);

        // Remove existing role assignments
        FacadesDB::table('model_has_roles')->where('model_id', $id)->delete();

        // Assign new roles based on the request input
        $user->assignRole($request->input('roles'));

        return redirect()->route('users.index')
            ->with('success', 'User updated successfully');
    }

    // delete the user from the database.
    public function destroy($id): RedirectResponse
    {
        User::find($id)->delete();
        return redirect()->route('users.index')
                        ->with('success','User deleted successfully');
    }
}
