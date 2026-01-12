<?php

namespace App\Livewire\Pages\Auth;

use App\Livewire\Forms\LoginForm;
use App\Services\LocalizedRoute;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.guest')]
class LoginPage extends Component
{
    public LoginForm $form;

    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        $this->redirectIntended(
            default: app(LocalizedRoute::class)->to('dashboard.index'),
            navigate: true
        );
    }

    public function render()
    {
        return view('livewire.pages.auth.login-page');
    }
}
