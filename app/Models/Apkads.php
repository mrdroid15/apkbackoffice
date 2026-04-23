<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Apkads extends Model
{
	protected $table = 'apkads';
    protected $fillable = [
        'name',
        'packagename',
        'image',
        'link',
    ];
}
