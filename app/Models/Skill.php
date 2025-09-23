<?php

namespace App\Models;

use App\Observers\SkillObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([SkillObserver::class])]
class Skill extends Model
{
  use SoftDeletes;
  protected $guarded = ['id'];
  protected $table = 'skills';
}
