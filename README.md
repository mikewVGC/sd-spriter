
# Showdown Spriter

Quick and dirty script that builds a mini-sprites sprite sheet for all Pokemon and items. These are the little icons you see on your a list of teams in the team builder.

I use this for [Reportworm Standings](https://github.com/mikewVGC/vgc-standings) as I was previously dependent on the [pkmn/ps repo](https://github.com/pkmn/ps) for sprites, but didn't want to wait for updates when new sprites or items were added. Note that the sheets produced by this repo are not backwards compatible with Showdown.

## Dependencies

1. You'll need at least PHP 8 with the GD2 extension. I've tested with 8.3.7.
2. Check out the [Smogon sprites project](https://github.com/smogon/sprites).

## Config

Create `config.json` in the root directory. These defaults should work fine, but adjust as needed.

```json
{
    "sprites_repo": "../sprites",
    "species_columns": 12,
    "item_columns": 16,
    "output_location": "./output"
}

```

## Run It!

```
php ./src/run.php
```

This will output three files:

```
output/pokemonicons-sheet.png
output/itemicons-sheet.png
output/map-coords.json
```

The two PNG files are the sprite sheets. The JSON file contains Showdown compatible codes ("Flutter Mane" -> `fluttermane`) along with the coordinates for them on the sheet. You can use the coordinates to display a Pokemon or item icon.

## License

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
