<?php

namespace Knowfox\Drupal7\Commands;

use Illuminate\Console\Command;
use Knowfox\Drupal7\Models\ImportedNode;
use Knowfox\Models\Concept;

class ImportDrupal7 extends Command
{
    const ROOT_ID = 3372;
    const USER_ID = 1;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'drupal7:import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import Drupal7 nodes from a MySQL database';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        $this->info("Importing from database " . env('DB_D7_DATABASE') . '...');

        $nodes = ImportedNode::with('revision')->orderBy('nid')->get();
        foreach ($nodes as $node) {
            $this->info(' - importing #' . $node->nid . ' - ' . $node->title);

            $created_at = strftime('%Y-%m-%d %H:%M:%S', $node->created);
            $updated_at = strftime('%Y-%m-%d %H:%M:%S', $node->changed);
            $concept = Concept::firstOrNew([
                'title' => $node->title,
                'created_at' => $created_at,
            ]);

            $latest = $node->revision()->first();

            $concept->parent_id = self::ROOT_ID;
            $concept->owner_id = self::USER_ID;

            $concept->created_at = $created_at;
            $concept->updated_at = $updated_at;
            $concept->language = $node->language == 'und' ? 'de' : $node->language;
            $body = $latest->body();
            $concept->body = $body->body_value;
            $concept->summary = $body->body_summary;

            $concept->config = [
                'node_type' => $node->type,
            ];

            $concept->disableVersioning();
            $concept->save();

            $concept->retag($node->terms->map(function ($term) { return $term->name; })->toArray());
        }

        $this->info('Done.');
    }
}
