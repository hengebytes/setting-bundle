<?php

namespace Hengebytes\SettingBundle\Interfaces;

interface SettingHandlerInterface
{
    public function get(string $name, ?string $default = null): ?string;

    public function set(string $name, string $value, bool $isSensitive): void;

    public function isProductionEnvironment(): bool;

    public function getGrouped(): array;

    public function remove(string $name): void;

    public function setRunTime(string $name, ?string $runtimeValue);
}
