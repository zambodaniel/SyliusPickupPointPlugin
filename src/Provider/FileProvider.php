<?php

namespace Setono\SyliusPickupPointPlugin\Provider;

use League\Flysystem\FileExistsException;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\FilesystemInterface;

abstract class FileProvider extends Provider
{

    private FilesystemInterface $filesystem;

    public function __construct(FilesystemInterface $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    abstract protected function getFileName(): string;

    /**
     * @throws FileNotFoundException
     */
    protected function getFile(): string
    {
        return $this->filesystem->read($this->getFileName());
    }

    /**
     * @param string $data
     * @return void
     * @throws FileExistsException
     * @throws FileNotFoundException
     */
    protected function storeFile(string $data): void
    {
        if ($this->filesystem->has($this->getFileName())) {
            $this->filesystem->update($this->getFileName(), $data);
        } else {
            $this->filesystem->write($this->getFileName(), $data);
        }
    }
}