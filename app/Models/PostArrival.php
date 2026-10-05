<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One tap, accepted or refused (SCALING-PLAN.md §6).
 *
 * `(node_id, seq)` is unique, and that one constraint does three jobs: a replayed frame cannot
 * become a second arrival, two gateways hearing the same tap collapse into one, and a queue flushed
 * after a gateway outage cannot double-count what it already delivered.
 *
 * Refusals are kept too. A card that does not work is exactly what the crew needs to see, and
 * `outcome` says which kind of not-working it was.
 */
class PostArrival extends Model
{
    public const OPENED = 'opened';
    public const ALREADY_OPEN = 'already_open';
    public const UNKNOWN_TAG = 'unknown_tag';
    public const UNKNOWN_NODE = 'unknown_node';
    public const NODE_UNPLACED = 'node_unplaced';
    public const NODE_INACTIVE = 'node_inactive';
    public const TAG_EXPIRED = 'tag_expired';
    public const TAG_UNASSIGNED = 'tag_unassigned';
    public const NOT_ON_PLAN = 'not_on_plan';
    public const BAD_SIGNATURE = 'bad_signature';
    public const NO_ACCOUNTS = 'no_accounts';
    public const HEARTBEAT = 'heartbeat';

    /** What the crew reads on the admin page, in their own language. */
    public const LABELS = [
        self::OPENED => 'Pos dibuka',
        self::ALREADY_OPEN => 'Sudah terbuka',
        self::UNKNOWN_TAG => 'Kartu tidak dikenal',
        self::TAG_UNASSIGNED => 'Kartu belum punya tim',
        self::TAG_EXPIRED => 'Kartu di luar masa pakai',
        self::UNKNOWN_NODE => 'Reader tidak terdaftar',
        self::NODE_UNPLACED => 'Reader belum ditaruh di pos',
        self::NODE_INACTIVE => 'Reader dimatikan',
        self::NOT_ON_PLAN => 'Pos tidak ada di denah tim itu',
        self::BAD_SIGNATURE => 'Tanda tangan salah — kemungkinan pemalsuan',
        self::NO_ACCOUNTS => 'Tim belum punya akun login',
        self::HEARTBEAT => 'Kabar dari reader',
    ];

    protected $fillable = [
        'node_id', 'seq', 'uid', 'team_id', 'game_location_id', 'outcome',
        'tapped_at', 'received_at', 'gateway', 'rssi', 'snr', 'vbat_mv', 'hmac_ok',
    ];

    protected function casts(): array
    {
        return [
            'node_id' => 'integer',
            'seq' => 'integer',
            'tapped_at' => 'datetime',
            'received_at' => 'datetime',
            'gateway' => 'integer',
            'rssi' => 'integer',
            'snr' => 'float',
            'vbat_mv' => 'integer',
            'hmac_ok' => 'boolean',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function gameLocation(): BelongsTo
    {
        return $this->belongsTo(GameLocation::class);
    }

    public function label(): string
    {
        return self::LABELS[$this->outcome] ?? $this->outcome;
    }

    /** Did this tap do what the team wanted? Both of these mean "walk in". */
    public function worked(): bool
    {
        return in_array($this->outcome, [self::OPENED, self::ALREADY_OPEN], true);
    }
}
