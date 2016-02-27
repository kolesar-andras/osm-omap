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
        $this->processFeatures($this->data['features']);
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

        $symbol = Symbol::getSymbol($tags);
        if (is_array($symbol)) {
            $flags = sprintf(' flags="%d"', $symbol[1]);
            $symbol = $symbol[0];
        } else {
            $flags = '';
        }
        $coords = [];
        foreach ($feature['geometry']['coordinates'] as $coordinates) {
            $transformed = $this->transformCoordinates($coordinates);
            $coords[] = sprintf('                       <coord x="%d" y="%d"%s/>',
                $transformed[0],
                $transformed[1],
                $flags
            );
        }
        $tagsXml = [];
        foreach ($tags as $key => $value) {
            if ($value == '') continue;
            $tagsXml[] = sprintf('                        <t k="%s">%s</t>',
                htmlspecialchars($key, ENT_XML1, 'UTF-8'),
                htmlspecialchars($value, ENT_XML1, 'UTF-8')
            );
        }
        $this->objects[] = sprintf('                <object type="%d" symbol="%d">
                    <tags>
%s
                    </tags>
                    <coords count="%d">
%s
                    </coords>
                    <pattern rotation="0">
                        <coord x="0" y="0"/>
                    </pattern>
                </object>',
            1, $symbol, implode("\n", $tagsXml), count($coords), implode("\n", $coords)
        );
    }
}
