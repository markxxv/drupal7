<?php

namespace Knowfox\Drupal7\Models;

use Illuminate\Database\Eloquent\Model;

class ImportedNode extends Model
{
    protected $connection = 'd7';
    protected $table = 'node';
    protected $primaryKey = 'nid';
    public $timestamps = false;

    public function revision()
    {
        return $this->hasOne(ImportedRevision::class, 'nid')
            ->orderBy('vid', 'desc');
    }

    public function terms()
    {
        return $this->belongsToMany(ImportedTerm::class, 'taxonomy_index', 'nid', 'tid');
    }
}
