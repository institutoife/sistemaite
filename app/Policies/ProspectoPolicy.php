<?php
namespace App\Policies;
use App\Models\Prospecto;
use App\Models\User;
class ProspectoPolicy
{
    public function viewAny(User $user) { return $user->can('Listar Prospectos'); }
    public function view(User $user, Prospecto $prospecto) { return $this->viewAny($user)&&($prospecto->responsable_id||$user->can('Configurar CRM')); }
    public function create(User $user) { return $user->can('Crear Prospectos'); }
    public function update(User $user, Prospecto $prospecto) { return $this->view($user,$prospecto)&&$user->can('Editar Prospectos'); }
    public function convert(User $user, Prospecto $prospecto) { return $this->view($user,$prospecto)&&$user->can('Convertir Prospectos'); }
    public function delete(User $user, Prospecto $prospecto) { return false; }
}
