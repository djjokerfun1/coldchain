<?php

declare(strict_types=1);

namespace App\Domain\Auditing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Append-only: compliance records that could be edited after the fact are
 * not compliance records. There is no update route and none should exist.
 */
class AuditEntry extends Model
{
    /** @use HasFactory<\Database\Factories\Domain\Auditing\AuditEntryFactory> */
    use HasFactory;

    const UPDATED_AT = null;

    protected $fillable = ['auditable_type', 'auditable_id', 'action', 'data', 'occurred_at'];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }
}
