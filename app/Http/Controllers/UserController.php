<?php

namespace App\Http\Controllers;

use App\Models\TechnicalGroup;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::with('technicalGroup')->orderByDesc('id')->paginate(10);

        return view('users.index', ['users' => $users]);
    }

    public function create(): View
    {
        return view('users.create', ['groups' => $this->groups()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:6'],
            'role' => ['required', Rule::in([User::ROLE_SUPERADMIN, User::ROLE_TEKNIS])],
            'technical_group_id' => ['nullable', 'exists:technical_groups,id', 'required_if:role,teknis'],
        ]);

        if ($data['role'] === User::ROLE_SUPERADMIN) {
            $data['technical_group_id'] = null;
        }

        User::create($data);

        return redirect()->route('admin.users.index')
            ->with('success', 'User berhasil ditambahkan.');
    }

    public function edit(User $user): View
    {
        return view('users.edit', [
            'user' => $user,
            'groups' => $this->groups(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:6'],
            'role' => ['required', Rule::in([User::ROLE_SUPERADMIN, User::ROLE_TEKNIS])],
            'technical_group_id' => ['nullable', 'exists:technical_groups,id'],
        ]);

        if ($user->is(Auth::user())) {
            $data['role'] = User::ROLE_SUPERADMIN;
            $data['technical_group_id'] = null;
        }

        if ($data['role'] === User::ROLE_SUPERADMIN) {
            $data['technical_group_id'] = null;
        }

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'technical_group_id' => $data['technical_group_id'],
        ]);

        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }

        $user->save();

        return redirect()->route('admin.users.index')
            ->with('success', 'User berhasil diperbarui.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is(Auth::user())) {
            abort(403, 'Tidak dapat menghapus akun yang sedang digunakan.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'User berhasil dihapus.');
    }

    private function groups()
    {
        return TechnicalGroup::orderBy('nama')->get();
    }
}
