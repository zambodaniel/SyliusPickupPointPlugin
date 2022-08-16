<?php

declare(strict_types=1);

namespace Setono\SyliusPickupPointPlugin\Model;

final class MplPointFile
{
    private string $filename;

    private string $content;

    private ?string $fullPath = null;

    public function __construct(string $filename, string $content)
    {
        $this->filename = $filename;
        $this->content = $content;
    }

    public function filename(): string
    {
        return $this->filename;
    }

    public function content(): string
    {
        return $this->content;
    }

    public function fullPath(): ?string
    {
        return $this->fullPath;
    }

    public function setFullPath(?string $fullPath): void
    {
        $this->fullPath = $fullPath;
    }
}
