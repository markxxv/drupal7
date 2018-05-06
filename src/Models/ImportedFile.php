<?php

namespace Knowfox\Drupal7\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ImportedFile extends Model
{
    protected $connection = 'd7';
    protected $table = 'file_managed';
    protected $primaryKey = 'fid';
    public $timestamps = false;
}
