<?php

namespace App\Models;

use App\Services\CredentialCodec;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Credential extends Model
{
    public const TYPES = ['Diploma', 'Transcript of Records', 'Certificate of Graduation'];

    protected $fillable = [
        'type', 'student_name', 'student_id', 'program', 'honors', 'gwa', 'date_conferred',
        'payload', 'hash', 'tx_hash', 'block_number', 'issued_by',
        'revoked_at', 'revoke_reason', 'revoke_tx_hash',
    ];

    protected function casts(): array
    {
        return [
            'date_conferred' => 'date',
            'revoked_at' => 'datetime',
        ];
    }

    /** The exact credential object that was hashed and given to the student. */
    public function credential(): array
    {
        return json_decode($this->payload, true);
    }

    public function urlToken(): string
    {
        return CredentialCodec::toUrlToken($this->credential());
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }
}
