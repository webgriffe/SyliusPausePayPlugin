<?php

declare(strict_types=1);

namespace Webgriffe\SyliusPausePayPlugin;

use function dirname;
use Sylius\Bundle\CoreBundle\Application\SyliusPluginTrait;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class WebgriffeSyliusPausePayPlugin extends Bundle
{
    use SyliusPluginTrait;

    public function getPath(): string
    {
        return dirname(__DIR__);
    }
}
