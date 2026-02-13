<?php

namespace App\Livewire\Integrations;

use Illuminate\View\View;
use Livewire\Attributes\Validate;
use Livewire\Component;

class ApiTokenManager extends Component
{
    #[Validate('required|string|max:100')]
    public string $tokenName = '';

    public ?string $plainTextToken = null;

    public function createToken(): void
    {
        $this->validate();

        $token = auth()->user()->createToken($this->tokenName);
        $this->plainTextToken = $token->plainTextToken;

        $this->reset('tokenName');
    }

    public function revokeToken(int $tokenId): void
    {
        auth()->user()->tokens()->where('id', $tokenId)->delete();
    }

    public function render(): View
    {
        return view('livewire.integrations.api-token-manager', [
            'tokens' => auth()->user()->tokens,
        ]);
    }
}
