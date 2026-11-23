<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        $adminUser = User::where('role', 'admin')->first();
        $hafizhUser = User::where('email', 'hafizh@s2p.co.id')->first() 
            ?? User::where('spesialisasi', 'lapangan')->first() 
            ?? User::where('role', 'teknisi')->first();
        $giskaUser = User::where('email', 'giska@s2p.co.id')->first()
            ?? User::where('spesialisasi', 'aplikasi')->first();
        $itUser = $hafizhUser;
        $rinaUser = User::where('email', 'operasi@s2p.co.id')->first()
            ?? User::where('name', 'like', '%Rina%')->first()
            ?? User::where('role', 'pegawai')->first();

        $demoUsers = User::orderBy('role', 'asc')->orderBy('id', 'asc')->get();

        return view('auth.login', compact('demoUsers', 'adminUser', 'hafizhUser', 'giskaUser', 'itUser', 'rinaUser'));
    }

    public function login(Request $request)
    {
        $loginInput = trim($request->input('login') ?? $request->input('email') ?? '');

        if (empty($loginInput) || empty($request->password)) {
            return back()->withErrors([
                'login' => 'NIP / username dan kata sandi wajib diisi.',
            ])->withInput();
        }

        $isEmail = filter_var($loginInput, FILTER_VALIDATE_EMAIL);

        if ($isEmail) {
            $credentials = ['email' => $loginInput, 'password' => $request->password];
        } else {
            // Cek apakah berupa username sebelum domain @s2p.co.id atau nama pengguna
            $foundUser = User::where('email', $loginInput . '@s2p.co.id')
                ->orWhere('email', 'like', $loginInput . '@%')
                ->orWhere('name', 'like', "%{$loginInput}%")
                ->first();

            $credentials = [
                'email' => $foundUser ? $foundUser->email : $loginInput,
                'password' => $request->password,
            ];
        }

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            $user = Auth::user();

            return redirect()->intended(route('dashboard'))
                ->with('success', 'Berhasil masuk sebagai ' . $user->name . ' (' . ($user->isTeknisi() ? 'Teknisi IT Support' : $user->divisi) . ')');
        }

        return back()->withErrors([
            'login' => 'NIP / username atau kata sandi yang Anda masukkan salah.',
        ])->onlyInput('login');
    }

    public function quickLogin(User $user, Request $request)
    {
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('success', 'Berhasil masuk sebagai ' . $user->name . ' (' . ucfirst($user->role) . ' - ' . $user->divisi . ')');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('info', 'Anda telah berhasil keluar sistem.');
    }
}

