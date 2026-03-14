<?php

/*
 * Project Name: personal-v4
 * File: PaymentCategory.php
 * Created Date: Monday March 9th 2026
 * 
 * Author: Nova Ardiansyah admin@novaardiansyah.id
 * Website: https://novaardiansyah.id
 * MIT License: https://github.com/novaardiansyah/personal-v4/blob/main/LICENSE
 * 
 * Copyright (c) 2026 Nova Ardiansyah, Org
 */

declare(strict_types=1);

namespace App\Models;

use App\Observers\PaymentCategoryObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy(PaymentCategoryObserver::class)]
class PaymentCategory extends Model
{
	use SoftDeletes;

	protected $table = 'payment_categories';

	protected $fillable = ['name', 'user_id', 'code', 'is_default'];

	protected $casts = [
		'is_default' => 'boolean',
	];

	public function user(): BelongsTo
	{
		return $this->belongsTo(User::class, 'user_id');
	}

	public function payments(): HasMany
	{
		return $this->hasMany(Payment::class, 'category_id');
	}
}
