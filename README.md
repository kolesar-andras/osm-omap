# osm-omap
Transform OpenStreetMap data to OpenOrienteering mapper files.

## Usage

Input file file needs to be projected and have a bbox. Example command to create:

    ogr2ogr \
    -f GeoJSON \
    -t_srs EPSG:23700 \
    -lco COORDINATE_PRECISION=3 \
    -lco WRITE_BBOX=YES \
    map.lines.geojson \
    map.osm \
    lines

Resulting GeoJSON file will be input of converter:

    ./osm-omap.php map.lines.geojson template.omap > output.omap

You can open resulting file in [OpenOrienteering mapper](http://www.openorienteering.org/). Note that it is not possible to send modifications made in mapper back to OpenStreetMap. Please edit geometries in OpenStreetMap and convert file again. You can use .osm file saved from [JOSM](https://josm.openstreetmap.de/) editor just after uploading changes.

## Notes

Currently only lines are transformed and only a few styles are mapped.

Avoid using projections where projection unit is not meter, for example Spherical Mercator EPSG:3857. OpenOrienteering mapper does not handle these (yet).

## TODO
* handle points and polygons
* map all ISOM codes to OSM tags
* write tags to output
* concatenate lines
* create splines from polylines (depending on style, exclude power lines and similar linear features)
