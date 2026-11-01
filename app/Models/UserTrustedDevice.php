<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserTrustedDevice extends Model
{
    protected $guarded = ['id'];
    protected $hidden = ['selector', 'token_hash', 'environment_hash', 'user_agent_hash'];
    protected $casts = ['last_used_at'=>'datetime', 'expires_at'=>'datetime', 'revoked_at'=>'datetime'];

    public function user() { return $this->belongsTo(User::class); }
}
