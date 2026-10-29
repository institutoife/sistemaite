<?php
namespace App\Policies;
use App\Models\Prospecto;
use App\Models\User;
class ProspectoPolicy
{
    public function viewAny(User $user) { return $user->can('Listar Prospectos'); }
    public function view(User $user, Prospecto $prospecto) { return $this->viewAny($user); }
    public function create(User $user) { return $user->can('Crear Prospectos'); }
    public function update(User $user, Prospecto $prospecto) { return $user->can('Editar Prospectos'); }
    public function convert(User $user, Prospecto $prospecto) { return $user->can('Convertir Prospectos'); }
    public function delete(User $user, Prospecto $prospecto) { return false; }
}
