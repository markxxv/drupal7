<?php

namespace Knowfox\Drupal7\Models;

use Illuminate\Database\Eloquent\Model;

class ImportedTerm extends Model
{
    protected $connection = 'd7';
    protected $table = 'taxonomy_term_data';
    protected $primaryKey = 'tid';
    public $timestamps = false;
}
