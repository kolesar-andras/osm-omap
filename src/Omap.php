<?php

/**
 * Create .omap file for OpenOrienteering mapper
 * @author Kolesár András <kolesar@openstreetmap.hu>
 */

require_once('Symbol.php');

class Omap {

    public $data;
    public $template;
    public $bbox;
    public $center;
    public $objects = [];
    public $scale = 15000;
    public $proj;
    public $epsg;

    /**
     * @param $data GeoJSON file parsed as associative array
     */
    public function __construct($data) {
        $this->data = $data;
        $this->setBoundingBox($this->data['bbox']);
        $this->setProjection($this->data['crs']['properties']['name']);
        $this->setCenter();
    }

    public function setBoundingBox($bbox) {
        $this->bbox = $bbox;
    }

    public function setProjection($projection) {
        if (preg_match('/EPSG:+([0-9]+)/i', $projection, $regs)) {
            $this->epsg = $regs[1];
            $this->proj = sprintf('+init=epsg:%d', $this->epsg);
        }
    }

    function setCenter() {
        $this->center = [
            ($this->bbox[0] + $this->bbox[2])/2,
            ($this->bbox[1] + $this->bbox[3])/2,
        ];
    }

    public function processFeatures() {
        foreach ($this->data['features'] as $feature) {
            $this->createObjects($feature);
        }
    }

    public function setTemplate($template) {
        $this->template = $template;
    }

    public function getOmap() {
        $template = $this->template;
        $template = str_replace('<!-- {georeference} -->', $this->getGeoreference(), $template);
        $template = str_replace('<!-- {objects} -->', $this->getObjects(), $template);
        return $template;
    }

    public function getGeoreference() {
        return sprintf('    <georeferencing scale="%d">
        <projected_crs id="EPSG">
            <spec language="PROJ.4">%s</spec>
            <parameter>%d</parameter>
            <ref_point x="%f" y="%f"/>
        </projected_crs>
    </georeferencing>',
            $this->scale,
            $this->proj,
            $this->epsg,
            $this->center[0],
            $this->center[1]
        );
    }

    public function getObjects() {
        return sprintf('            <objects count="%d">
%s
            </objects>',
            count($this->objects), implode("\n", $this->objects)
        );
    }

    public function transformCoordinates($coordinates) {
        $multiplier = 1000000 / $this->scale;
        return [
            ($coordinates[0]-$this->center[0]) * $multiplier,
            ($coordinates[1]-$this->center[1]) * $multiplier * -1
        ];
    }

    public function createObjects($feature) {
        $tags = Symbol::getTags($feature['properties']);
        if (isset($tags['osm_id']) && count($tags) == 1) return;
        $symbol = Symbol::getSymbol($tags);
        if (is_array($symbol)) {
            $distinct = @$symbol[2];
            $flags = @$symbol[1];
            $symbol = $symbol[0];
        } else {
            $distinct = false;
            $flags = 0;
        }
        $coordsXml = $this->getCoordsXml($feature, $flags);
        $tagsXml = $this->getTagsXml($tags);
        $direction = @$tags['direction'];
        $this->addObject($symbol, $tagsXml, $coordsXml, $direction);
        if ($distinct && !(@$tags['indistinct'] == 'yes')) {
            $this->addObject($distinct, $tagsXml, $coordsXml);
        }
    }

    public function addObject($symbol, $tagsXml, $coordsXml, $direction=0) {
        $this->objects[] = sprintf('                <object type="%d" symbol="%d">
                    <tags>
%s
                    </tags>
                    <coords count="%d">
%s
                    </coords>
                    <pattern rotation="%s">
                        <coord x="0" y="0"/>
                    </pattern>
                </object>',
            count($coordsXml) == 1 ? 0 : 1,
            $symbol,
            implode("\n", $tagsXml),
            count($coordsXml),
            implode("\n", $coordsXml),
            $direction == 0 ? '0' : sprintf("%1.3f", -$direction / 180 * PI())
        );
    }

    public function getTagsXml($tags) {
        $tagsXml = [];
        foreach ($tags as $key => $value) {
            if ($value == '') continue;
            $tagsXml[] = sprintf('                        <t k="%s">%s</t>',
                htmlspecialchars($key, ENT_XML1, 'UTF-8'),
                htmlspecialchars($value, ENT_XML1, 'UTF-8')
            );
        }
        return $tagsXml;
    }

    public function getCoordsXml($feature, $flags) {
        $coordsXml = [];
        switch ($feature['geometry']['type']) {
            case 'Point':
                $coordsXml[] = $this->getCoord($feature['geometry']['coordinates'], $flags);
                break;

            case 'LineString':
                foreach ($feature['geometry']['coordinates'] as $coordinates) {
                    $coordsXml[] = $this->getCoord($coordinates, $flags);
                }
                break;

            case 'MultiPolygon':
                foreach ($feature['geometry']['coordinates'] as $rings) {
                    foreach ($rings as $ring) {
                        $count = 0;
                        $total = count($ring);
                        foreach ($ring as $coordinates) {
                            $flag = (++$count == $total) ? 18 : 0;
                            $coordsXml[] = $this->getCoord($coordinates, $flags | $flag);
                        }
                    }
                }
                break;
        }
        return $coordsXml;
    }

    public function getCoord($coordinates, $flags) {
        $transformed = $this->transformCoordinates($coordinates);
        return sprintf('                       <coord x="%d" y="%d"%s/>',
            $transformed[0],
            $transformed[1],
            $flags ? sprintf(' flags="%d"', $flags) : ''
        );
    }
}
