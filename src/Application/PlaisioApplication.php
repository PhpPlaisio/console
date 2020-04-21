<?php
declare(strict_types=1);

namespace Plaisio\Console\Application;

use Composer\IO\BufferIO;
use Symfony\Component\Console\Application;

/**
 * The Plaisio application.
 */
class PlaisioApplication extends Application
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * PlaisioApplication constructor.
   */
  public function __construct()
  {
    parent::__construct('Plaisio', '0.2.12');

    $this->setCommandLoader(new CommandLoader(new BufferIO()));
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
