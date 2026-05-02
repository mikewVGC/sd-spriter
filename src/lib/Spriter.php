<?php
//
// A Very Simple PHP Pokemon Showdown Sprite Builder Class
//
// Copyright (C) 2026, Mike W.
//
// Permission is hereby granted, free of charge, to any person obtaining a copy of this
// software and associated documentation files (the "Software"), to deal in the Software
// without restriction, including without limitation the rights to use, copy, modify, merge,
// publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons
// to whom the Software is furnished to do so.
//
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
// INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
// PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE
// FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE,
// ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
// SOFTWARE.
//
class Spriter
{
    const DTYPE_SPECIES = 'species';
    const DTYPE_ITEMS = 'items';

    const DATA_TYPES = [
        self::DTYPE_SPECIES,
        self::DTYPE_ITEMS,
    ];

    const MINISPRITES_DIR = "src/minisprites";

    protected $data = [];

    public function __construct(
        protected string $sprites_repo_location,
        protected int $species_columns,
        protected int $item_columns,
        protected string $output_location
    ) {
        $this->sprites_repo_location = realpath($this->sprites_repo_location);

        if (!file_exists($this->sprites_repo_location)) {
            throw new Exception("Sprites repo '{$this->sprites_repo_location}' not found.");
        }

        if (!file_exists($this->output_location)) {
            mkdir($this->output_location);
        }

        $this->fetchData();
    }

    public function createSprites()
    {
        $coordinates = $this->createSprite(
            "{$this->output_location}/pokemonicons-sheet.png",
            $this->compileSpeciesSprites(),
            $this->species_columns,
            40,
            30
        );

        $coordinates = array_merge(
            $coordinates,
            $this->createSprite(
                "{$this->output_location}/itemicons-sheet.png",
                $this->compileItemsSprites(),
                $this->item_columns,
                24,
                24
            )
        );

        ksort($coordinates);
        file_put_contents("{$this->output_location}/map-coords.json", json_encode($coordinates));
        echo "Wrote to {$this->output_location}/map-coords.json\n";
    }

    protected function setFound($data_type, $key)
    {
        $this->data[$data_type][$key]['found'] = true;
    }

    protected function fetchData()
    {
        foreach (self::DATA_TYPES as $data_type) {
            $this->data[$data_type] = json_decode(
                file_get_contents("{$this->sprites_repo_location}/data/{$data_type}.json"),
                true
            );
        }
    }

    protected function compileSpeciesSprites() : array
    {
        $minisprites_dir = "{$this->sprites_repo_location}/" . self::MINISPRITES_DIR;

        $scanned_sprite_files = $this->getSpriteFiles(
            "{$minisprites_dir}/pokemon/gen6",
            self::DTYPE_SPECIES
        );

        $mon_sprite_files = [];

        foreach ($scanned_sprite_files as $mon_sprite_file) {
            $info = explode('-', $mon_sprite_file['code']);

            $main_code = $info[0];
            $sub_code = '';
            if (count($info) > 1) {
                $sub_code = $info[1];
            }

            $species = $this->data[self::DTYPE_SPECIES][$main_code];
            $mon_code = str_replace(
                [ '-', ' ', '.', "'" ],
                [ '', '', '', '' ],
                strtolower("{$species['base']}{$species['forme']}")
            );

            if (!empty($species['found'])) {
                continue;
            }

            if ($species['num'] < 0) {
                continue;
            }

            if ($species['forme'] == 'totem') {
                continue;
            }

            $this->data[self::DTYPE_SPECIES][$main_code]['found'] = true;

            $sprite_file_base = "{$mon_sprite_file['path']}/{$species['sid']}";
            foreach ([ '', '-a', '-vsmogon', '-f' ] as $append) {
                $filepath = "{$sprite_file_base}{$append}.png";
                if (file_exists($filepath)) {
                    $mon_sprite_files[] = [
                        'code' => $mon_code,
                        'num'  => $species['num'],
                        'path' => $filepath,
                    ];
                    break;
                }
            }
        }

        // put them in dex-ish order for aesthetics, I guess?
        usort($mon_sprite_files, function($a, $b) {
            return $a['num'] > $b['num'] ? 1 : -1;
        });

        return $mon_sprite_files;
    }

    protected function compileItemsSprites()
    {
        $minisprites_dir = "{$this->sprites_repo_location}/" . self::MINISPRITES_DIR;

        $scanned_sprite_files = $this->getSpriteFiles(
            "{$minisprites_dir}/items",
            self::DTYPE_ITEMS
        );

        $item_sprite_files = [];

        foreach ($scanned_sprite_files as $item_sprite_file) {
            $code = $item_sprite_file['code'];
            $item = $this->data[self::DTYPE_ITEMS][$code];

            $filepath = "{$item_sprite_file['path']}/{$item_sprite_file['file']}";
            $item_sprite_files[] = [
                'code' => str_replace(
                    [ '-', ' ', "'" ],
                    [ '', '', '' ],
                    strtolower($item['names'][0]),
                ),
                'path' => $filepath,
            ];
        }

        return $item_sprite_files;
    }

    protected function getSpriteFiles(string $dir, string $data_type)
    {
        $list = scandir($dir);

        $sprite_files = [];

        foreach ($list as $file) {
            if (is_dir("{$dir}/{$file}")) {
                continue;
            }

            $code = str_replace('.png', '', $file);
            $code_parts = explode('-', $code);

            if (empty($this->data[$data_type][$code_parts[0]])) {
                continue;
            }

            $value = $this->data[$data_type][$code_parts[0]];

            $sprite_files[] = [
                'code' => $code_parts[0],
                'file' => $file,
                'path' => $dir,
            ];
        }

        return $sprite_files;
    }

    protected function createSprite(
        string $output,
        array $sprite_files,
        int $columns,
        int $icon_width,
        int $icon_height,
    ) {
        $rows = ceil(count($sprite_files) / $columns);

        $sprite_main = Image::create($icon_width * $columns, $icon_height * $rows);

        $x = 1;
        $y = 0;

        $coordinates = [];

        foreach ($sprite_files as $sprite_file) {
            $sprite = Image::load($sprite_file['path'], 'png');

            if (!$sprite) {
                continue;
            }

            $x_pos = $x * $icon_width;
            if ($sprite->getWidth() < $icon_width) {
                $x_pos += floor(($icon_width - $sprite->getWidth()) / 2);
            }

            $y_pos = $y * $icon_height;
            if ($sprite->getHeight() < $icon_height) {
                $y_pos += floor(($icon_height - $sprite->getHeight()) / 2);
            }

            $sprite_main->insert($sprite, $x_pos, $y_pos);

            $coordinates[$sprite_file['code']] = [ $x * $icon_width, $y * $icon_height ];

            $x++;
            if ($x > $columns - 1) {
                $x = 0;
                $y++;
            }
        }

        $sprite_main->write($output, 'png');

        echo "Wrote to {$output}\n";

        return $coordinates;
    }

}
