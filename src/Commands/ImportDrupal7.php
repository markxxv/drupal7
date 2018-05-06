<?php

namespace Knowfox\Drupal7\Commands;

use Illuminate\Console\Command;
use Knowfox\Drupal7\Models\ImportedNode;
use Knowfox\Models\Concept;
use Knowfox\Services\PictureService;

class ImportDrupal7 extends Command
{
    const ROOT_ID = 3372;
    const USER_ID = 1;
    const IMAGE_URL = 'https://olav.net/files/images/';
    const UUID = 'olav.net:';

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

    public function replaceTextileLinks($text)
    {
        return preg_replace_callback('/"([^"]+)":(\S+)/', function ($matches) {
            $last = substr($matches[2], -1, 1);
            if (in_array($last, ['.', ':'])) {
                return '[' . $matches[1] . ']('
                    . substr($matches[2], 0, -1) . ')' . $last;
            }
            else {
                return '[' . $matches[1] . '](' . $matches[2] . ')';
            }
        }, $text);
    }

    public function replaceShortcodes($text)
    {
        $self = $this;

        return preg_replace_callback('/\[([^\]]+)\]/', function ($matches) use ($self) {
            $props = [];

            $body = preg_split('/\s*\|\s*/', $matches[1]);
            if ($body[0] != 'img_assist') {

                if (preg_match('/\[image:(\d+),([^,]+),(\d+)\]/', $matches[0], $m)) {
                    $img = '/uuid/' . self::UUID . $m[1] . '/image';
                    return '<div class="image pull-' . $m[2] . '">'
                        . '<a data-featherlight="' . $img . '">'
                        . '<img src="' . $img . '?style=square' . '"></a></div>';
                }

                if (preg_match('/\[image:(\d+),([^,]+)\]/', $matches[0], $m)) {
                    $img = '/uuid/' . self::UUID . $m[1] . '/image';
                    return '<a data-featherlight="' . $img . '">'
                        . '<img src="' . $img . '?style=square' . '"></a>';
                }

                if (preg_match('/\[([^\|]+)\|([^\]]+)\]/', $matches[0], $m)) {
                    return "[{$m[1]}]($m[2])";
                }

                $self->error('Unsupported shortcode in ' . $matches[0]);
                return $matches[0];
            }

            array_shift($body);

            foreach ($body as $prop) {
                list($name, $value) = preg_split('/\s*=\s*/', $prop, 2);
                $values = preg_split('/,\s*/', $value);
                switch (count($values)) {
                    case 3:
                    case 2:
                        $props[$name] = $values[0];
                        $props['align'] = $values[1];
                        break;

                    default:
                        $props[$name] = $values[0];
                        break;
                }
            }

            if (!isset($props['nid'])) {
                $self->error("{$matches[0]} has no nid");
                return $matches[0];
            }

            $img = '/uuid/' . self::UUID . $props['nid'] . '/image';

            $result = '<a data-featherlight="' . $img . '"><img src="' . $img;
            if (isset($props['width'])) {
                $result .= '?width=' . $props['width'];
            }
            $result .= '"></a>';
            if (isset($props['align'])) {
                $result = '<div class="image pull-' . $props['align'] . '">' . $result . '</div>';
            }
            return $result;
        }, $text);
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(PictureService $picture) {
        $this->info("Importing from database " . env('DB_D7_DATABASE') . '...');

        $nodes = ImportedNode::with('revision')->orderBy('nid')->get();
        foreach ($nodes as $node) {

            try {

                $this->info(' - importing #' . $node->nid . ' - ' . $node->title);

                $created_at = strftime('%Y-%m-%d %H:%M:%S', $node->created);
                $updated_at = strftime('%Y-%m-%d %H:%M:%S', $node->changed);

                $year = strftime('%Y', $node->created);
                $year_concept = Concept::firstOrNew([
                    'owner_id' => self::USER_ID,
                    'parent_id' => self::ROOT_ID,
                    'title' => $year,
                    'type' => 'folder',
                ]);
                $year_concept->config = ['sort' => 'created'];
                $year_concept->save();

                $concept = Concept::firstOrNew([
                    'owner_id' => self::USER_ID,
                    'uuid' => self::UUID . $node->nid,
                ]);

                $latest = $node->revision()->first();

                $concept->parent_id = $year_concept->id;
                $concept->title = $node->title;
                $concept->created_at = $created_at;
                $concept->updated_at = $updated_at;
                $concept->language = $node->language == 'und' ? 'de' : $node->language;
                $concept->source_url = $node->url;

                $body = $latest->body();
                $concept->body = $this->replaceTextileLinks($body->body_value);
                $concept->body = $this->replaceShortcodes($concept->body);
                $concept->summary = $body->body_summary;

                $concept->config = [
                    'node_type' => $node->type,
                    'node_id' => $node->nid,
                ];

                $concept->disableVersioning();
                $concept->save();

                $tags = $node->terms->map(function ($term) { return $term->name; })->toArray();

                if (in_array($node->type, ['story', 'page'])) {
                    $tags[] = 'Post';
                }

                $concept->retag($tags);

                $this->info('   --> ' . $concept->id);

                if (!$node->files->count()) {
                    continue;
                }

                $directory = $picture->imageDirectory($concept->uuid);
                @mkdir($directory, 0755, true);

                foreach ($node->files as $i => $file) {
                    if (preg_match('/\.(thumbnail|preview)$/', $file->filename)) {
                        continue;
                    }
                    $this->info('    . ' . $file->filename . ', ' . $file->uri);
                    $path = parse_url($file->uri, PHP_URL_PATH);
                    $filename = pathinfo($path, PATHINFO_BASENAME);

                    $image = @file_get_contents(self::IMAGE_URL . $path);
                    if (!$image) {
                        $this->error('      ' . self::IMAGE_URL . $path . ' not found');
                        continue;
                    }
                    file_put_contents($directory . '/' . $filename, $image);

                    if (strpos($file->filemime, 'image/') === 0) {

                        $parts = pathinfo($path);

                        if ($i == 0) {
                            $concept->config = ['image' => $parts['basename']] + (array)$concept->config;
                        }
                        else {
                            if (strpos($concept->body, $parts['basename']) === false) {
                                $concept->body .= "\n\n<a data-featherlight=\"{$parts['basename']}\">![{$parts['filename']}]({$parts['basename']}?style=square)</a>\n";
                            }
                        }
                        $concept->save();
                    }

                }
            }
            catch (\Exception $e) {
                $this->error($node->nid . ': ' . $e->getMessage());
            }
        }

        $this->info('Done.');
    }
}
