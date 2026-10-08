<?php

namespace Fujiba\AutoPoster\Models;

use Illuminate\Database\Eloquent\Model;

class AutoPosterPatternKnowledge extends Model
{
    protected $table = 'autoposter_pattern_knowledge';

    protected $guarded = ['id'];

    protected $casts = [
        'images' => 'array', 'closeup' => 'array', 'colors' => 'array', 'motifs' => 'array', 'pattern' => 'array',
        'meanings' => 'array', 'seasons' => 'array', 'occasions' => 'array', 'photo_needs' => 'array', 'hooks' => 'array',
        'analysed_at' => 'datetime',
    ];
}
