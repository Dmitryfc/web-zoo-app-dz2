<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Модель доски (Trello Board)
 *
 * @property int $board_id
 * @property string $title
 * @property int $owner_user_id
 * @property bool $is_public
 * @property string $created_datetime
 * @property string|null $updated_datetime
 */
final class Board extends Model
{
    protected $table = 'boards';
    protected $primaryKey = 'board_id';

    public const CREATED_AT = 'created_datetime';
    public const UPDATED_AT = 'updated_datetime';

    protected $fillable = [
        'title',
        'owner_user_id',
        'is_public',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'owner_user_id' => 'integer',
    ];

    /**
     * Владелец доски
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id', 'user_id');
    }

    /**
     * Колонки доски
     */
    public function columns(): HasMany
    {
        return $this->hasMany(BoardColumn::class, 'board_id', 'board_id')->orderBy('ordinal_position');
    }

    /**
     * Карточки доски
     */
    public function cards(): HasMany
    {
        return $this->hasMany(Card::class, 'board_id', 'board_id');
    }
}
