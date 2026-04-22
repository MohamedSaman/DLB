<?php

namespace App\Livewire;

use App\Models\UserLocation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.guest')]
class CustomLogin extends Component
{
    public $email = '';
    public $password = '';
    public $remember = false;
    public $latitude = null;
    public $longitude = null;
    public $accuracy = null;

    public function render()
    {
        return view('livewire.custom-login');
    }

    public function login()
    {
        $this->validate([
            'email' => 'required|email',
            'password' => 'required',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'accuracy' => 'nullable|numeric|min:0',
        ]);

        if (Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            session()->regenerate();

            $user = Auth::user();

            if ($user && $this->latitude !== null && $this->longitude !== null) {
                try {
                    UserLocation::create([
                        'user_id' => $user->id,
                        'latitude' => $this->latitude,
                        'longitude' => $this->longitude,
                        'accuracy' => $this->accuracy,
                        'recorded_at' => now(),
                    ]);
                } catch (\Throwable $e) {
                    Log::warning('Failed to store web login location', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            if ($user && $user->role === 'admin') {
                return redirect()->route('admin.dashboard');
            } else if ($user && $user->role === 'staff') {
                // Let the main dashboard route handle staff type redirects
                return redirect()->route('dashboard');
            } else {
                return redirect()->route('dashboard');
            }
        }

        // When authentication fails, add an error to Livewire's error bag
        $this->addError('email', 'These credentials do not match our records.');
        // Clear the password field for security and UX
        $this->password = '';
    }

    /**
     * Clear validation/error for a property when it is updated.
     * This helps remove the red border / shaking animation as soon as user starts typing.
     */
}
