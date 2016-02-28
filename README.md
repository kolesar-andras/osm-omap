# osm-omap
Transform OpenStreetMap data to OpenOrienteering mapper files.

## Usage

Download OSM data. Go to http://openstreetmap.org/ and press Export button. It will export objects inside the current view by default. You can adjust zoom and set a custom bounding box. You will get an OSM XML file named `map.osm`. You can also use [JOSM](https://josm.openstreetmap.de/) editor for download data.

Input file format of this converter is GeoJSON with projected coordinates and a computed bounding box. You can convert with the following command, customize EPSG code of projection for your area.

    OSM_USE_CUSTOM_INDEXING=NO \
    ogr2ogr \
    -f GeoJSON \
    -t_srs EPSG:23700 \
    -lco COORDINATE_PRECISION=3 \
    -lco WRITE_BBOX=YES \
    -sql "SELECT * FROM lines UNION ALL SELECT * FROM multipolygons" \
    map.geojson \
    map.osm

Resulting GeoJSON file will be input of converter:

    ./osm-omap.php map.geojson template.omap > output.omap

You can open resulting file in [OpenOrienteering mapper](http://www.openorienteering.org/). Note that it is not possible to send modifications made in mapper back to OpenStreetMap. Please edit geometries in OpenStreetMap and convert file again. You can use .osm file saved from JOSM editor just after uploading changes.

## Notes

Currently only lines are transformed and only a few styles are mapped.

Avoid using projections where projection unit is not meter, for example Spherical Mercator EPSG:3857. OpenOrienteering mapper does not handle these (yet).

## Features
* converts points, lines and multipolygons
* copies all OSM tags
* rotates patterns using direction tag

## TODO
* map all ISOM codes to OSM tags
* concatenate lines
* create splines from polylines (depending on style, exclude power lines and similar linear features)
* adjust pattern rotation to true north
