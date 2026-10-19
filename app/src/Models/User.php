<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Модель пользователя системы (Trello User)
 *
 * @property int $user_id
 * @property string $email
 * @property string $password_hash
 * @property string $name
 * @property string $created_datetime
 * @property string|null $updated_datetime
 */
final class User extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'user_id';

    public const CREATED_AT = 'created_datetime';
    public const UPDATED_AT = 'updated_datetime';

    protected $fillable = [
        'email',
        'password_hash',
        'name',
    ];

    protected $hidden = [
        'password_hash',
    ];

    /**
     * Доски, созданные пользователем
     */
    public function boards(): HasMany
    {
        return $this->hasMany(Board::class, 'owner_user_id', 'user_id');
    }
}
