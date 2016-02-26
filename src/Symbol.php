<?php

/**
 * Asssign a ISOM symbol to features having OpenStreetMap tags
 * @author Kolesár András <kolesar@openstreetmap.hu>
 */

class Symbol {
    static function getSymbol($feature) {

        $tags = $feature['properties'];

        if (in_array($tags['highway'], ['track', 'unsurfaced', 'bridleway', 'cycleway']))
            return 93;

        if (in_array($tags['highway'], ['path']))
            return 94;

        if (in_array($tags['highway'], ['tertiary']))
            return 90;

        if (in_array($tags['highway'], ['primary', 'secondary']))
            return 88;

        return null;

    }
}
