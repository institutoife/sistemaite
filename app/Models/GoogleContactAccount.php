<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoogleContactAccount extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['access_token', 'refresh_token', 'installation_id'];

    protected $casts = [
        'access_token' => 'encrypted',
        'refresh_token' => 'encrypted',
        'expires_at' => 'datetime',
        'disconnected_at' => 'datetime',
        'sync_paused_at' => 'datetime',
        'requires_reconnection' => 'boolean',
    ];

    public function isConnected(): bool
    {
        return !$this->disconnected_at && !$this->requires_reconnection && !empty($this->refresh_token);
    }

    public function isPaused(): bool
    {
        return $this->sync_paused_at !== null;
    }
}
