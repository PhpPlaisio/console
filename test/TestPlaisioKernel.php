<?php
declare(strict_types=1);

namespace Plaisio\Console\Test;

use Plaisio\PlaisioKernel;

/**
 * A commandline kernel for testing purposes.
 */
class TestPlaisioKernel extends PlaisioKernel
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns an instance of this kernel.
   *
   * @param string $name Ignored.
   *
   * @return static
   */
  public static function create(string $name): self
  {
    unset($name);

    return new self();
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
