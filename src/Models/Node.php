<?php

namespace Knowfox\Drupal7\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Node extends Model
{

    protected $connection = 'drupal';
    protected $table = 'node';
    protected $primaryKey = 'nid';
    public $timestamps = false;

    /*
     * Relations with drupal entities
     */
    public function terms()
    {
        return $this->belongsToMany(ImportedTerm::class, 'taxonomy_index', 'nid', 'tid');
    }
    public function files()
    {
        return $this->belongsToMany(ImportedFile::class, 'file_usage', 'id', 'fid');
    }
    /*
     * Relation with my custom Booking model
     */
    public function dates()
    {
        return $this->hasMany(Booking::class, 'object_id', 'nid');
    }
    /*
     * Getting drupal alias
     */
    public function getUrlAttribute()
    {
        $alias = DB::connection('drupal')
            ->table('url_alias')
            ->where('source', 'node/' . $this->nid)
            ->where('language', $this->language)
            ->first();
        return $alias->alias;
    }
    /*
     * Getting Body
     * !! Atention with multilanguage !!
     */
    public function getBodyAttribute()
    {
        $body = DB::connection('drupal')
            ->table('field_data_body')
            ->where('entity_id', $this->nid)
            ->first();
        return $body;
    }
    
    /*
     * Getting my custom field: field_characteristics
     */
    public function getDescriptionAttribute()
    {
        $body = DB::connection('drupal')
            ->table('field_data_field_characteristics')
            ->where('entity_id', $this->nid)
            ->where('language', $this->language)
            ->pluck('field_characteristics_value')
            ->first();
        return $body;
    }

    /*
     * Getting first image of files relation 
     */
    public function getCoverAttribute()
    {
        $uri = substr($this->files->first()->uri, 9);
        return "DRUPAL_FOLDER/sites/default/files/" . $uri;

    }

}
