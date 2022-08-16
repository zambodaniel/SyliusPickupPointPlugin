<?php

declare(strict_types=1);

namespace Setono\SyliusPickupPointPlugin\Provider;

class MplPostProvider extends MplProvider
{

    public function getCode(): string
    {
        return self::CODE_POST;
    }

    public function getName(): string
    {
        return 'MPL Postára kézbesítés';
    }

}
