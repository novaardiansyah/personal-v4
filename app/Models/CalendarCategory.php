<?php

namespace App\Models;

use App\Observers\CalendarCategoryObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([CalendarCategoryObserver::class])]
class CalendarCategory extends Model
{
    use SoftDeletes;

    protected $table = 'calendar_categories';

    protected $fillable = ['name', 'user_id', 'color', 'is_default', 'code'];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(CalendarEvent::class, 'category_id');
    }
}