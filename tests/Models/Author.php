<?php

namespace Zofe\Rapyd\Tests\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Zofe\Rapyd\Traits\SSearch;

class Author extends Model
{
    use HasFactory;
    use SSearch;
    public $table = 'rapyd_demo_users';

    protected $guarded = [];

    public static function ssearchFallback($query)
    {
        return  static::query()
            ->where('firstname', 'like',  $query . '%')
            ->orWhere('lastname', 'like',  $query . '%');
    }

    public function articles()
    {
        return $this->hasMany(Article::class, 'author_id', 'id');
    }
}
