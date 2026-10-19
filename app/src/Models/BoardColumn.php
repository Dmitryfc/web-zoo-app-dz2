<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Модель колонки доски (Trello Column)
 *
 * @property int $column_id
 * @property int $board_id
 * @property int $ordinal_position
 * @property string $title
 * @property string|null $color
 */
final class BoardColumn extends Model
{
    protected $table = 'board_columns';
    protected $primaryKey = 'column_id';

    public $timestamps = false;

    protected $fillable = [
        'board_id',
        'ordinal_position',
        'title',
        'color',
    ];

    protected $casts = [
        'board_id' => 'integer',
        'ordinal_position' => 'integer',
    ];

    /**
     * Доска, которой принадлежит колонка
     */
    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class, 'board_id', 'board_id');
    }

    /**
     * Карточки внутри колонки
     */
    public function cards(): HasMany
    {
        return $this->hasMany(Card::class, 'column_id', 'column_id')->orderBy('sort_index');
    }
}
