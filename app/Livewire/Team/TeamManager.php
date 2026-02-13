<?php

namespace App\Livewire\Team;

use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Spatie\Permission\PermissionRegistrar;

#[Layout('layouts::app')]
#[Title('Team')]
class TeamManager extends Component
{
    use AuthorizesRequests;

    #[Validate('required|email|max:255')]
    public string $inviteEmail = '';

    #[Validate('required|in:tenant_admin,tenant_member')]
    public string $inviteRole = 'tenant_member';

    public bool $showInviteForm = false;

    public function invite(): void
    {
        $this->authorize('team.invite');
        $this->validate();

        $tenant = auth()->user()->currentTenant;

        $user = User::where('email', $this->inviteEmail)->first();

        if (! $user) {
            $user = User::create([
                'name' => explode('@', $this->inviteEmail)[0],
                'email' => $this->inviteEmail,
                'password' => Hash::make(str()->random(32)),
            ]);
        }

        if ($tenant->users()->where('user_id', $user->id)->exists()) {
            $this->addError('inviteEmail', __('This user is already a member of this team.'));

            return;
        }

        $tenant->users()->attach($user->id, [
            'role' => $this->inviteRole,
            'is_default' => ! $user->tenants()->exists(),
        ]);

        if (! $user->current_tenant_id) {
            $user->update(['current_tenant_id' => $tenant->id]);
        }

        app()[PermissionRegistrar::class]->setPermissionsTeamId($tenant->id);
        $user->assignRole($this->inviteRole);

        $this->reset(['inviteEmail', 'inviteRole', 'showInviteForm']);
        $this->inviteRole = 'tenant_member';

        session()->flash('message', __('Team member invited successfully.'));
    }

    public function changeRole(int $userId, string $newRole): void
    {
        $this->authorize('team.invite');

        if (! in_array($newRole, ['tenant_admin', 'tenant_member'])) {
            return;
        }

        $tenant = auth()->user()->currentTenant;
        $user = User::findOrFail($userId);

        $tenant->users()->updateExistingPivot($user->id, ['role' => $newRole]);

        app()[PermissionRegistrar::class]->setPermissionsTeamId($tenant->id);
        $user->syncRoles([$newRole]);
    }

    public function removeMember(int $userId): void
    {
        $this->authorize('team.remove');

        if ($userId === auth()->id()) {
            return;
        }

        $tenant = auth()->user()->currentTenant;
        $user = User::findOrFail($userId);

        app()[PermissionRegistrar::class]->setPermissionsTeamId($tenant->id);
        $user->roles()->detach();

        $tenant->users()->detach($user->id);

        if ($user->current_tenant_id === $tenant->id) {
            $nextTenant = $user->tenants()->first();
            $user->update(['current_tenant_id' => $nextTenant?->id]);
        }

        session()->flash('message', __('Team member removed.'));
    }

    public function render(): View
    {
        $this->authorize('team.view');

        $tenant = auth()->user()->currentTenant;
        $members = $tenant->users()->withPivot('role', 'created_at')->get();

        return view('livewire.team.team-manager', [
            'members' => $members,
        ]);
    }
}
