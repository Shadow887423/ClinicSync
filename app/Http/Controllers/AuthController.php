<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return $this->redirectBasedOnRole(Auth::user());
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $user = Auth::user();

            if ($user->status !== 'active') {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'email' => 'Your account has been deactivated. Please contact clinic administration.',
                ]);
            }

            $request->session()->regenerate();

            return $this->redirectBasedOnRole($user);
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function showRegister()
    {
        if (Auth::check()) {
            return $this->redirectBasedOnRole(Auth::user());
        }
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone' => ['required', 'string', 'regex:/^(\+?[0-9]{10,15})$/'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', 'string', 'in:Male,Female,Other'],
            'address' => ['nullable', 'string', 'max:500'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'phone.regex' => 'Please provide a valid mobile number (e.g., +15551234567 or 09171234567).',
            'birth_date.before' => 'Birth date must be a past date.',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'birth_date' => $validated['birth_date'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'address' => $validated['address'] ?? null,
            'role' => User::ROLE_PATIENT,
            'status' => 'active',
            'password' => Hash::make($validated['password']),
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('patient.dashboard')->with('success', 'Welcome to CLINICSYNC! Your patient account has been created successfully.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('info', 'You have been logged out securely.');
    }

    public function showSettings()
    {
        return view('auth.settings', ['user' => Auth::user()]);
    }

    public function updateSettings(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->with('success', 'Your password has been changed successfully.');
    }

    protected function redirectBasedOnRole(User $user)
    {
        if ($user->isStaffOrAdmin()) {
            return redirect()->route('staff.dashboard');
        } elseif ($user->isDentist()) {
            return redirect()->route('dentist.dashboard');
        } else {
            return redirect()->route('patient.dashboard');
        }
    }
}
