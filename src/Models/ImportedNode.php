<?php

namespace Knowfox\Drupal7\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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

    public function files()
    {
        return $this->belongsToMany(ImportedFile::class, 'file_usage', 'id', 'fid');
    }

    public function getUrlAttribute()
    {
        $alias = DB::connection('d7')
            ->table('url_alias')
            ->where('source', 'node/' . $this->nid)
            ->first();
        return $alias->alias;
    }
}
