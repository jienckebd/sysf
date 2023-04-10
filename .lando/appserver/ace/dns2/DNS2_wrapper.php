<?php

/*
 * register the auto-load function
 *
 */
spl_autoload_register('Net_DNS2_wrapper_autoload');

/**
 * Implement a new autoloader that loads Net DNS2 class files relative to this one.
 *
 * @param string $name the name of the class
 *
 * @return void
 * @access public
 *
 */
function Net_DNS2_wrapper_autoload($name)
{
  // only auto-load Net_DNS2 classes
  if (strncmp($name, 'Net_DNS2', 8) == 0) {
    include_once dirname(dirname(__FILE__)) . '/' . str_replace('_', '/', $name) . '.php';
  }

  return;
}

/*
 * actually include the Net DNS2 library
 */
require_once dirname(__FILE__) . "/DNS2.php";
