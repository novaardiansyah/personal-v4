<?php

namespace App\Models;

use App\Observers\EmailTemplateObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([EmailTemplateObserver::class])]
class EmailTemplate extends Model
{
  use SoftDeletes;
  protected $table = 'email_templates';
  protected $fillable = ['code', 'alias', 'notes', 'subject', 'message', 'placeholders', 'is_protected'];
  protected $casts = [
    'placeholders' => 'array',
    'is_protected' => 'boolean',
  ];

  public function emails(): HasMany
  {
    return $this->hasMany(Email::class);
  }
}
