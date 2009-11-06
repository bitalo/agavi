<?php

/**
 * Data file for timezone "Antarctica/Davis".
 * Compiled from olson file "antarctica", version 8.5.
 *
 * @package    agavi
 * @subpackage translation
 *
 * @copyright  Authors
 * @copyright  The Agavi Project
 *
 * @since      0.11.0
 *
 * @version    $Id$
 */

return array (
  'types' => 
  array (
    0 => 
    array (
      'rawOffset' => 25200,
      'dstOffset' => 0,
      'name' => 'DAVT',
    ),
    1 => 
    array (
      'rawOffset' => 0,
      'dstOffset' => 0,
      'name' => 'zzz',
    ),
  ),
  'rules' => 
  array (
    0 => 
    array (
      'time' => -409190400,
      'type' => 0,
    ),
    1 => 
    array (
      'time' => -163062000,
      'type' => 1,
    ),
    2 => 
    array (
      'time' => -28857600,
      'type' => 0,
    ),
  ),
  'finalRule' => 
  array (
    'type' => 'static',
    'name' => 'DAVT',
    'offset' => 25200,
    'startYear' => 1970,
  ),
  'source' => 'antarctica',
  'version' => '8.5',
  'name' => 'Antarctica/Davis',
);

?>