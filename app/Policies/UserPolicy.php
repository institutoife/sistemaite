<?php
namespace App\Policies;
use App\Models\User;
class UserPolicy
{
    public function administer(User $actor): bool { return $actor->hasRole('Admin'); }
    public function view(User $actor,User $user): bool { return $actor->id===$user->id||$actor->can('Listar Usuarios'); }
    public function manageAccess(User $actor,User $user): bool { return $actor->hasRole('Admin'); }
}
