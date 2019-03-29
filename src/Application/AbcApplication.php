<?php

namespace SetBased\Abc\Console\Application;

use Composer\IO\BufferIO;
use Symfony\Component\Console\Application;

/**
 * The ABC application.
 */
class AbcApplication extends Application
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * AbcApplication constructor.
   */
  public function __construct()
  {
    parent::__construct('ABC', '0.0.0');

    $this->setCommandLoader(new CommandLoader(new BufferIO()));
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
