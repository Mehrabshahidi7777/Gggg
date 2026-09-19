<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        return view('admin.users.index', [
            'users' => User::withCount('ads')
                ->latest()
                ->paginate(25),
        ]);
    }

    public function show(User $user)
    {
        return view('admin.users.show', compact('user'));
    }

    public function edit(User $user)
    {
        return view('admin.users.form', compact('user'));
    }

    public function update(Request $r, User $user)
    {
        $d = $r->validate([
            'name' => 'required|max:255',

            'username' => [
                'required',
                'alpha_dash',
                'min:3',
                'max:50',
                'unique:users,username,' . $user->id,
            ],

            'email' => [
                'nullable',
                'email',
                'unique:users,email,' . $user->id,
            ],

            'mobile' => [
                'nullable',
                'string',
                'max:20',
                'unique:users,mobile,' . $user->id,
            ],

            'is_admin' => 'nullable|boolean',
        ]);

        $isAdmin = $r->boolean('is_admin');

        /*
        | یک ادمین نباید بتونه دسترسی ادمین بودن خودش رو از خودش بگیره،
        | چون ممکنه با یه کلیک اشتباه از پنل مدیریت قفل بشه بیرون —
        | همون منطقی که برای جلوگیری از حذف حساب خودش هم هست.
        */
        if ($user->id === auth()->id() && !$isAdmin) {
            return back()->with(
                'error',
                'نمی‌توانید دسترسی مدیریت را از حساب خودتان بگیرید.'
            );
        }

        // is_admin دیگر در $fillable مدل نیست، پس عمداً و جدا از بقیه‌ی
        // فیلدها (که با mass assignment ست می‌شوند) اینجا ست می‌شود.
        unset($d['is_admin']);
        $user->fill($d);
        $user->is_admin = $isAdmin;
        $user->save();

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'کاربر به‌روزرسانی شد.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with(
                'error',
                'نمی‌توانید حساب کاربری خودتان را حذف کنید.'
            );
        }

        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with(
                'success',
                'کاربر با موفقیت حذف شد.'
            );
    }

    public function create()
    {
        return redirect()
            ->route('admin.users.index');
    }

    public function store(Request $r)
    {
        return redirect()
            ->route('admin.users.index');
    }
}
