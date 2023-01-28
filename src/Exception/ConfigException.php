<?php
declare(strict_types=1);

namespace Plaisio\Console\Exception;

use SetBased\Exception\FormattedException;

/**
 * Exception thrown when a configuration error is found.
 */
class ConfigException extends \Exception
{
  use FormattedException;
}

//----------------------------------------------------------------------------------------------------------------------
