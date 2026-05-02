<?php

include(__DIR__ . '/lib/Image.php');
include(__DIR__ . '/lib/Spriter.php');

// using a simple array for the config just 'cause
$config = [];
if (file_exists("./config.json")) {
    $config = json_decode(file_get_contents("./config.json"), true);
}

echo "Building...\n";

try {
    $spriter = new Spriter(
        $config['sprites_repo'],
        $config['species_columns'],
        $config['item_columns'],
        $config['output_location']
    );

    $spriter->createSprites();

} catch (Exception $e) {
    echo "There was an error: {$e->getMessage()}\n";
    exit;
}

echo "Done!\n";
