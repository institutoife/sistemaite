<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CredencialesPadreNotification extends Notification
{
    use Queueable;

    protected $user;
    protected $persona;
    protected $url;

    public function __construct($user, $plainPassword, $persona, $url = null)
    {
        $this->user = $user;
        $this->persona = $persona;
        $this->url = $url;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        return [
            'titulo' => 'Credenciales creadas (Padre)',
            'mensaje' => 'Se generaron credenciales para el padre del estudiante.',
            'message' => 'Credenciales generadas para padre (ver detalles).',
            'estudiante' => trim($this->persona->nombre.' '.$this->persona->apellidop.' '.$this->persona->apellidom),
            'correo' => $this->user->email,
            'requiere_entrega_segura' => true,
            'url' => $this->url ?: route('tomar.foto.persona', ['persona' => $this->persona->id]),
            'persona_id' => $this->persona->id,
            'usuario_id' => $this->user->id,
        ];
    }
}
