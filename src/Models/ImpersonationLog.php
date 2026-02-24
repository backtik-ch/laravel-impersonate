<?php

namespace Backtik\Impersonate\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImpersonationLog extends Model
{
    protected $table = 'impersonation_logs';

    protected $fillable = [
        'admin_id',
        'impersonated_user_id',
        'started_at',
        'stopped_at',
        'ip_address',
        'user_agent',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'stopped_at' => 'datetime',
        ];
    }

    /**
     * The admin who initiated the impersonation.
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(
            config('auth.providers.users.model', 'App\\Models\\User'),
            'admin_id'
        );
    }

    /**
     * The user who was impersonated.
     */
    public function impersonatedUser(): BelongsTo
    {
        return $this->belongsTo(
            config('auth.providers.users.model', 'App\\Models\\User'),
            'impersonated_user_id'
        );
    }
}
