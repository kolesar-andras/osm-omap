<?php

/**
 * Asssign a ISOM symbol to features having OpenStreetMap tags
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
     * @param $feature that has properties tag (GeoJSON)
     * @return id of symbol
     */
    static function getSymbol($feature) {

        $tags = $feature['properties'];

        if (in_array($tags['highway'], ['track', 'unsurfaced', 'bridleway', 'cycleway']))
            return 93; // code="505" name="Vehicle track"

        if (in_array($tags['highway'], ['path']))
            return 94; // code="505" name="Vehicle track"

        if (in_array($tags['highway'], ['tertiary']))
            return 90; // code="503" name="Minor road"

        if (in_array($tags['highway'], ['primary', 'secondary']))
            return 88; // code="502" name="Major road, minimum width"

        if (in_array($tags['waterway'], ['stream', 'ditch', 'canal']))
            return 50; // code="305" name="Crossable watercourse"

        return -3; // magic number for unknown symbol

    }
}
