<?php

namespace Knowfox\Drupal7\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ImportedRevision extends Model
{
    protected $connection = 'd7';
    protected $table = 'node_revision';
    protected $primaryKey = 'vid';
    public $timestamps = false;

    public function body()
    {
        return DB::connection('d7')
            ->table('field_data_body')
            ->where('revision_id', $this->vid)
            ->orderBy('delta', 'desc')
            ->first();
    }
}
