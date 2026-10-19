<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Модель карточки (Trello Card)
 *
 * @property int $card_id
 * @property int $board_id
 * @property int $column_id
 * @property int $sort_index
 * @property string $title
 * @property string|null $content
 * @property string|null $color
 * @property bool $is_archived
 * @property string $created_datetime
 * @property string|null $updated_datetime
 */
final class Card extends Model
{
    protected $table = 'cards';
    protected $primaryKey = 'card_id';

    public const CREATED_AT = 'created_datetime';
    public const UPDATED_AT = 'updated_datetime';

    protected $fillable = [
        'board_id',
        'column_id',
        'sort_index',
        'title',
        'content',
        'color',
        'is_archived',
    ];

    protected $casts = [
        'board_id' => 'integer',
        'column_id' => 'integer',
        'sort_index' => 'integer',
        'is_archived' => 'boolean',
    ];

    /**
     * Доска, к которой относится карточка
     */
    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class, 'board_id', 'board_id');
    }

    /**
     * Колонка (статус), в которой находится карточка
     */
    public function column(): BelongsTo
    {
        return $this->belongsTo(BoardColumn::class, 'column_id', 'column_id');
    }

    /**
     * Scope: только активные карточки (не в архиве)
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_archived', false);
    }

    /**
     * Scope: только архивные карточки
     */
    public function scopeArchived(Builder $query): Builder
    {
        return $query->where('is_archived', true);
    }
}
