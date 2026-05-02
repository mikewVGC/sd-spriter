<?php

include("Image.php");

$config = json_decode(file_get_contents("config.json"), true);

$sprites_location_base= "{$config['sprites_repo']}/src/minisprites";

$mon_sprite_files = [];

$species_data = json_decode(
    file_get_contents("{$config['sprites_repo']}/data/species.json"),
    true
);

// collect all pokemon
foreach ([ 'gen6', 'gen8' ] as $gen) {
    $list = scandir("{$sprites_location_base}/pokemon/{$gen}");

    foreach ($list as $file) {
        if (is_dir("{$sprites_location_base}/pokemon/{$gen}/{$file}")) {
            continue;
        }

        $code = str_replace('.png', '', $file);
        $info = explode('-', $code);

        $main_code = $info[0];
        $sub_code = '';
        if (count($info) > 1) {
            $sub_code = $info[1];
        }

        if (empty($species_data[$main_code])) {
            continue;
        }

        $species = $species_data[$main_code];
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

        $species_data[$main_code]['found'] = true;

        $sprite_file_base = "{$sprites_location_base}/pokemon/{$gen}/{$species['sid']}";
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
}

// put them in dex-ish order for aesthetics, I guess?
usort($mon_sprite_files, function($a, $b) {
    return $a['num'] > $b['num'] ? 1 : -1;
});

$item_data = json_decode(
    file_get_contents("{$config['sprites_repo']}/data/items.json"),
    true
);

// now get all items
$list = scandir("{$sprites_location_base}/items");

$item_sprite_files = [];

foreach ($list as $file) {
    if (is_dir("{$sprites_location_base}/items/{$gen}/{$file}")) {
        continue;
    }

    $code = str_replace('.png', '', $file);

    if (empty($item_data[$code])) {
        continue;
    }

    $item = $item_data[$code];

    $filepath = "{$sprites_location_base}/items/{$item['sid']}.png";

    $item_sprite_files[] = [
        'code' => str_replace(
            [ '-', ' ', "'" ],
            [ '', '', '' ],
            strtolower($item['names'][0]),
        ),
        'path' => $filepath,
    ];
}

$coordinates = [];

$mon_rows = ceil(count($mon_sprite_files) / 12);

$mons_sprite_main = Image::create(40 * 12, 30 * $mon_rows);

$x = 1;
$y = 0;

foreach ($mon_sprite_files as $sprite_file) {
    $sprite = Image::load($sprite_file['path'], 'png');

    if (!$sprite) {
        continue;
    }

    $x_pos = $x * 40;
    if ($sprite->getWidth() < 40) {
        $x_pos += floor((40 - $sprite->getWidth()) / 2);
    }

    $y_pos = $y * 30;
    if ($sprite->getHeight() < 30) {
        $y_pos += floor((30 - $sprite->getHeight()) / 2);
    }

    $mons_sprite_main->insert($sprite, $x_pos, $y_pos);

    $coordinates[$sprite_file['code']] = [ $x * 40, $y * 30 ];

    $x++;
    if ($x > 11) {
        $x = 0;
        $y++;
    }
}

$mons_sprite_main->write("pokemonicons-sheet.png", 'png');

$item_rows = ceil(count($item_sprite_files) / 16);

$items_sprite_main = Image::create(24 * 16, 24 * $item_rows);

$x = 1;
$y = 0;

foreach ($item_sprite_files as $item_file) {
    $sprite = Image::load($item_file['path'], 'png');

    if (!$sprite) {
        continue;
    }

    $x_pos = $x * 24;
    if ($sprite->getWidth() < 24) {
        $x_pos += floor((24 - $sprite->getWidth()) / 2);
    }

    $y_pos = $y * 24;
    if ($sprite->getHeight() < 24) {
        $y_pos += floor((24 - $sprite->getHeight()) / 2);
    }

    $items_sprite_main->insert($sprite, $x_pos, $y_pos);

    $coordinates[$item_file['code']] = [ $x * 24, $y * 24 ];

    $x++;
    if ($x > 15) {
        $x = 0;
        $y++;
    }
}

$items_sprite_main->write("itemicons-sheet.png", 'png');

ksort($coordinates);

file_put_contents("map-coords.json", json_encode($coordinates));

