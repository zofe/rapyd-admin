<?php

namespace Zofe\Rapyd\Tests\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Zofe\Rapyd\Traits\SSearch;

class Article extends Model
{
    use HasFactory;
    use SSearch;
    public $table = 'rapyd_demo_articles';

    protected $attributes = [
        'public' => 0,
    ];

    protected $guarded = [];

    public static function ssearchFallback($query)
    {
        return  static::query()->where(function ($q) use ($query) {
            $q->where('title', 'like', '%'.$query.'%')
                ->orWhere('body', 'like', '%'.$query.'%')
                ->orWhereHas('author', function ($subq) use ($query) {
                    $subq->where('firstname', 'like',  $query . '%')
                        ->orWhere('lastname', 'like',  $query . '%')
                    ;
                })
            ;
        });
    }

    public function author()
    {
        return $this->belongsTo(Author::class, 'author_id', 'id');
    }
}
