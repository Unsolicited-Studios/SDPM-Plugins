<?php

declare(strict_types=1);

namespace jojoe77777\FormAPI;

use pocketmine\player\Player;
use pocketmine\form\Form as IForm;

abstract class Form implements IForm
{
    protected array $data = [];

    public function __construct(
        private ?callable $callable
    ) {
    }

    public function getCallable(): ?callable
    {
        return $this->callable;
    }

    public function setCallable(?callable $callable): void
    {
        $this->callable = $callable;
    }

    public function handleResponse(Player $player, $data): void
    {
        $this->processData($data);
        $callable = $this->getCallable();
        if ($callable !== null) {
            $callable($player, $data);
        }
    }

    public function processData(mixed &$data): void
    {
    }

    public function jsonSerialize()
    {
        return $this->data;
    }
}
