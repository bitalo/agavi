<?php

/**
 * Data file for Africa/Malabo timezone, compiled from the olson data.
 *
 * Auto-generated by the phing olson task on 06/05/2009 16:44:34
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
      'rawOffset' => 0,
      'dstOffset' => 0,
      'name' => 'GMT',
    ),
    1 => 
    array (
      'rawOffset' => 3600,
      'dstOffset' => 0,
      'name' => 'WAT',
    ),
  ),
  'rules' => 
  array (
    0 => 
    array (
      'time' => -1830386108,
      'type' => 0,
    ),
    1 => 
    array (
      'time' => -190857600,
      'type' => 1,
    ),
  ),
  'finalRule' => 
  array (
    'type' => 'static',
    'name' => 'WAT',
    'offset' => 3600,
    'startYear' => 1964,
  ),
  'name' => 'Africa/Malabo',
);

?>