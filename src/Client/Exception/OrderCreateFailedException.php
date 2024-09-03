<?php

declare(strict_types=1);

namespace Webgriffe\SyliusPausePayPlugin\Client\Exception;

use RuntimeException;

final class OrderCreateFailedException extends RuntimeException implements ExceptionInterface
{
}
