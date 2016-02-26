#!/usr/bin/env php
<?php

/**
 * transform OpenStreetMap data to OpenOrienteering mapper files
 * see README file for detailed command line usage
 *
 * @author Kolesár András <kolesar@openstreetmap.hu>
 */

require_once('src/Omap.php');

if (@$argc != 3)
    die(sprintf("Usage: %s <geojson> <template>\n", basename($argv[0])));

$contents = file_get_contents($argv[1]);
$template = file_get_contents($argv[2]);

$data = json_decode($contents, true);

$omap = new Omap($data);
$omap->setTemplate($template);
$omap->processFeatures();
echo $omap->getOmap();
