<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    protected $table = 'part2s';

    protected $fillable = [
        'part1_id',
        'surname',
        'first_name',
        'midle_name',
        'place_of_birth',
        'date_of_birth',
        'age',
        'sex_at_birth',
        'cellular_no',
    ];
}
