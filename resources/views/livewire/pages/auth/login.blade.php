<?php

use App\Livewire\Forms\LoginForm;
use App\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Session;

use function Livewire\Volt\form;
use function Livewire\Volt\layout;

layout('layouts.guest');

form(LoginForm::class);

$login = function () {
    $this->validate();

    $this->form->authenticate();

    Session::regenerate();

    $this->redirectIntended(default: RouteServiceProvider::HOME, navigate: true);
};

?>

<div class="login-wrapper">
    <style>
        :root {
            --bg-color: #FFFBF1;
            --primary-color: #91D06C;
            --accent-color: #346739;
        }

        .login-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--bg-color);
            font-family: 'Outfit', sans-serif;
        }

        .login-card {
            background: white;
            padding: 3rem;
            border-radius: 40px;
            box-shadow: 0 20px 60px rgba(52, 103, 57, 0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
            border: 1px solid rgba(145, 208, 108, 0.2);
        }

        .login-logo {
            width: 70px;
            height: 70px;
            background: var(--primary-color);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            color: var(--accent-color);
            font-size: 1.8rem;
            box-shadow: 0 10px 20px rgba(145, 208, 108, 0.3);
        }

        h2 { color: var(--accent-color); font-weight: 800; margin-bottom: 0.5rem; }
        p { color: #888; margin-bottom: 2rem; font-size: 0.9rem; }

        .form-group { text-align: left; margin-bottom: 1.2rem; }
        label { display: block; font-size: 0.8rem; font-weight: 700; color: var(--accent-color); margin-bottom: 5px; margin-left: 5px; }

        input[type="email"], input[type="password"] {
            width: 100%;
            padding: 12px 20px;
            border-radius: 12px;
            border: 2px solid #f0f0f0;
            font-family: 'Outfit', sans-serif;
            box-sizing: border-box;
            transition: all 0.3s ease;
        }

        input:focus { outline: none; border-color: var(--primary-color); background: #f9fff9; }

        .btn-login {
            width: 100%;
            padding: 14px;
            border-radius: 12px;
            border: none;
            background: var(--accent-color);
            color: white;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 1rem;
            box-shadow: 0 10px 20px rgba(52, 103, 57, 0.2);
        }

        .btn-login:hover { transform: translateY(-2px); background: #284d2b; }

        .back-link { display: inline-block; margin-top: 1.5rem; color: var(--primary-color); text-decoration: none; font-weight: 600; font-size: 0.85rem; }
    </style>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800&display=swap" rel="stylesheet">

    <div class="login-card">
        <div class="login-logo">
            <i class="fas fa-user-lock"></i>
        </div>
        <h2>Akses Masuk</h2>
        <p>Gunakan akun Admin atau Staff Anda</p>

        <x-auth-session-status class="mb-4" :status="session('status')" />

        <form wire:submit="login">
            <div class="form-group">
                <label>EMAIL</label>
                <input wire:model="form.email" type="email" required autofocus placeholder="nama@email.com">
                <x-input-error :messages="$errors->get('form.email')" class="mt-1" />
            </div>

            <div class="form-group">
                <label>PASSWORD</label>
                <input wire:model="form.password" type="password" required placeholder="••••••••">
                <x-input-error :messages="$errors->get('form.password')" class="mt-1" />
            </div>

            <div style="text-align: left; margin-bottom: 1rem; display: flex; align-items: center; gap: 8px;">
                <input wire:model="form.remember" id="remember" type="checkbox" style="width: auto;">
                <label for="remember" style="margin: 0; color: #888; font-weight: 400;">Ingat saya</label>
            </div>

            <button type="submit" class="btn-login">
                MASUK SEKARANG
            </button>
        </form>

        <a href="{{ url('/kiosk') }}" class="back-link">
            <i class="fas fa-arrow-left"></i> Kembali ke Kiosk
        </a>
    </div>
</div>
