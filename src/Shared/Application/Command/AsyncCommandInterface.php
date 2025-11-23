<?php

declare(strict_types=1);

namespace App\Shared\Application\Command;

/**
 * Marker interface for commands that should be handled asynchronously.
 * Commands implementing this interface will be routed to async transport.
 */
interface AsyncCommandInterface
{
}
