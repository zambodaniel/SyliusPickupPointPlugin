<?php

declare(strict_types=1);

namespace Setono\SyliusPickupPointPlugin\Provider;

class MplPointProvider extends MplProvider
{

    public function getCode(): string
    {
        return self::CODE_POINT;
    }

    public function getName(): string
    {
        return 'MPL Postapont és automata';
    }

}
