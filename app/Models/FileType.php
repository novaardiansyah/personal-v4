<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FileType extends Model
{
	use SoftDeletes;

	protected $table = 'file_types';
	
	protected $fillable = [
		'name',
	];
}
