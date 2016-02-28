<?php

/**
 * Asssign ISOM symbol to features having OpenStreetMap tags
 * @author Kolesár András <kolesar@openstreetmap.hu>
 */

class Symbol {

    /**
     * Get symbol for a feature
     *
     * It responds id number used in template file instead of ISOM numbers.
     * This way there is no need to parse template XML for codes.
     * ISOM numbers and names are displayed in comments.
     *
     * @param $tags as associative array
     * @return id of symbol
     */
    static function getSymbol($tags) {

        if (in_array(@$tags['highway'], ['track', 'unsurfaced', 'bridleway', 'cycleway']))
            return 93; // code="505" name="Vehicle track"

        if (in_array(@$tags['highway'], ['footway', 'steps']) ||
            in_array(@$tags['man_made'], ['pier']))
            return 94; // code="506" name="Footpath"

        if (in_array(@$tags['highway'], ['path']))
            return 95; // code="507" name="Small path"

        if (in_array(@$tags['highway'], ['tertiary']))
            return 90; // code="503" name="Minor road"

        if (in_array(@$tags['highway'], ['unclassified', 'residential', 'service', 'living_street', 'pedestrian']))
            return 92; // code="504" name="Road"

        if (in_array(@$tags['highway'], ['primary', 'secondary']))
            return 88; // code="502" name="Major road, minimum width"

        if (in_array(@$tags['waterway'], ['stream', 'ditch', 'canal']))
            return 50; // code="305" name="Crossable watercourse"

        if (in_array(@$tags['railway'], ['rail']))
            return 99; // code="515" name="Railway"

        if (in_array(@$tags['power'], ['minor_line']))
            return [100, 32]; // code="516" name="Power line"

        if (in_array(@$tags['power'], ['line']))
            return [101, 32]; // code="517" name="Major power line"

        if (in_array(@$tags['barrier'], ['fence']))
            return 109; // code="524" name="High fence"

        if (in_array(@$tags['barrier'], ['retaining_wall', 'city_wall']))
            return 106; // code="521" name="High stone wall"

        if (in_array(@$tags['natural'], ['water']))
            return 45; // code="301" name="Lake"

        if (in_array(@$tags['natural'], ['wetland']))
            return 59; // code="311" name="Indistinct marsh"

        if (in_array(@$tags['natural'], ['grassland']) ||
            in_array(@$tags['landuse'], ['meadow', 'farmland']))
            return 64; // code="401" name="Open land"

        if (in_array(@$tags['natural'], ['heath']))
            return [66, 0, 80]; // code="403" name="Rough open land"
            // code="414" name="Distinct cultivation boundary"

        if (in_array(@$tags['landuse'], ['residential', 'allotments']))
            return 113; // code="527" name="Settlement"

        if (in_array(@$tags['landuse'], ['vineyard']))
            return [79, 0, 80]; // code="413" name="Vineyard"
            // code="414" name="Distinct cultivation boundary"

        if (in_array(@$tags['landuse'], ['forest']))
            return [69, 0, 82]; // code="406" name="Forest: slow running"
            // code="416" name="Distinct vegetation boundary"

        if (isset($tags['building']) && $tags['building'] != 'no')
            return 111; // code="526" name="Building"

        return -3; // magic number for unknown symbol

    }

    /**
     * Get tags of feature
     *
     * GDAL OGR driver handles OSM tags in a special way.
     * Uses a config file named osmconf.ini for mapping tags.
     * Default configuration creates columns for some keys,
     * other tags are placed to other_tags column.
     *
     * Can be configured to put all tags to all_tags column.
     *
     * These fields are filled with key-value pairs
     * encoded with PostgreSQL hstore syntax:
     * "key" => "value", [...]
     *
     * This method finds these fields and decodes tags.
     *
     * @param $tags tags of feature
     * @return associative array of tags
     */
    static function getTags($tags) {
        foreach (['other_tags', 'all_tags'] as $column) {
            if (!isset($tags[$column])) continue;
            $array = static::decodeHstore($tags[$column]);
            unset($tags[$column]);
            if (!is_array($array)) continue;
            $tags = array_merge($tags, $array);
        }
        $response = [];
        foreach ($tags as $key => $value) {
            if ($value == '') continue;
            $response[$key] = $value;
        }
        return $response;
    }

    /**
     * Convert PostgreSQL hstore key-value pairs to associative array
     * by simply converting to JSON object
     */
    static function decodeHstore($hstore) {
        return json_decode('{' . str_replace('"=>"', '":"', $hstore) . '}', true);
    }
}
