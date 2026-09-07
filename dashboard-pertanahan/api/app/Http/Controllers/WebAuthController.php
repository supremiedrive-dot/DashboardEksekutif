<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WebAuthController extends Controller
{
    public function show() { return view('auth.login'); }

    public function login(Request $request)
    {
        $credentials = $request->validate(['email'=>'required|email','password'=>'required|string']);
        if (! Auth::attempt(array_merge($credentials, ['is_active'=>true]))) {
            return back()->withErrors(['email'=>'Email atau password salah.'])->onlyInput('email');
        }
        $request->session()->regenerate();
        return redirect()->intended('/dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
