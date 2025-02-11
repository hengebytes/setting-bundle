<?php

namespace Hengebytes\SettingBundle\Handler;

use Doctrine\ORM\EntityManagerInterface;
use Hengebytes\SettingBundle\Entity\Setting;
use Hengebytes\SettingBundle\Interfaces\SettingHandlerInterface;
use Hengebytes\SettingBundle\Service\CryptoService;

class SettingHandler implements SettingHandlerInterface
{
    protected const string SETTING_NOT_DEFINED_INDICATOR = "\n!\t";
    /**  setting with this prefix will be first priority */
    protected ?string $overridePrefix = null;

    private array $runTimeStorage = [];

    public function __construct(
        protected EntityManagerInterface $em, protected string $entityClass, protected CryptoService $cryptoService
    ) {
    }

    public function set(string $name, string $value, bool $isSensitive = false): void
    {
        $originalValue = $value;
        if ($isSensitive) {
            $value = $this->cryptoService->encrypt($value);
        }

        /** @var Setting $setting */
        $setting = $this->em->getRepository($this->entityClass)->findOneBy(['name' => $name]);

        if ($setting) {
            $setting->value = $value;
            $setting->isSensitive = $isSensitive;
        } else {
            $setting = $this->createEntity();
            $setting->name = $name;
            $setting->value = $value;
            $setting->isSensitive = $isSensitive;
            $this->em->persist($setting);
        }

        $this->em->flush();
        $this->setRunTime($name, $originalValue);
    }

    public function createEntity(): Setting
    {
        return new $this->entityClass();
    }

    public function setRunTime(string $name, $runtimeValue): void
    {
        $this->runTimeStorage[$name] = $runtimeValue;
    }

    public function isProductionEnvironment(): bool
    {
        $env = $this->get('general/environment');

        return $env === 'production' || $env === null;
    }

    public function get(string $name, $default = null): ?string
    {
        if ($this->overridePrefix !== null) {
            return $this->innerGet($this->overridePrefix . $name) ?? $this->innerGet($name) ?? $default;
        }

        return $this->innerGet($name) ?? $default;
    }

    protected function innerGet(string $name): ?string
    {
        $runTimeValue = $this->getRunTime($name);
        if ($runTimeValue !== null) {
            return $runTimeValue !== self::SETTING_NOT_DEFINED_INDICATOR ? $runTimeValue : null;
        }

        /** @var Setting $setting */
        $setting = $this->em->getRepository($this->entityClass)->findOneBy(['name' => $name]);
        if ($setting === null) {
            $this->setRunTime($name, self::SETTING_NOT_DEFINED_INDICATOR);

            return null;
        }

        $value = $setting->value;
        if ($setting->isSensitive) {
            $value = $this->cryptoService->decrypt($value);
        }
        $this->setRunTime($name, $value);

        return $value;
    }

    public function getMultiple(array $names): array
    {
        $result = [];
        foreach ($names as $key => $name) {
            $runTimeValue = $this->getRunTime($name);
            if ($runTimeValue === null) {
                continue;
            }
            $result[$name] = $runTimeValue !== self::SETTING_NOT_DEFINED_INDICATOR ? $runTimeValue : null;
            unset($names[$key]);
        }

        if (!$names) {
            return $result;
        }
        $settings = $this->em->getRepository($this->entityClass)->findBy(['name' => $names]);

        foreach ($settings as $setting) {
            $value = $setting->value;
            if ($setting->isSensitive) {
                $value = $this->cryptoService->decrypt($value);
            }
            $result[$setting->name] = $value;

            $this->setRunTime($setting->name, $value);
        }

        return $result;
    }


    private function getRunTime(string $name): ?string
    {
        return $this->runTimeStorage[$name] ?? null;
    }

    public function getGrouped(): array
    {
        return $this->group(
            $this->em->getRepository($this->entityClass)->findAll()
        );
    }

    private function group(array $settings): array
    {
        $return = [];
        /** @var Setting $setting */
        foreach ($settings as $setting) {
            $groupName = 0;
            $setting->value = $setting->isSensitive ? $this->maskSensitiveString($setting->value) : $setting->value;
            $nameArray = explode('/', $setting->name);
            if ($nameArray) {
                $groupName = $nameArray[0];
            }
            $return[$groupName][] = $setting;
        }
        foreach ($return as $group => $unused) {
            uasort($return[$group], function (Setting $a, Setting $b) {
                return $a->name <=> $b->name;
            });
        }

        return $return;
    }

    public function remove(string $name): void
    {
        /** @var Setting $setting */
        $setting = $this->em->getRepository($this->entityClass)->findOneBy(['name' => $name]);
        if ($setting !== null) {
            $this->em->remove($setting);
            $this->em->flush();
            $this->setRunTime($name, self::SETTING_NOT_DEFINED_INDICATOR);
        }
    }

    public function setOverridePrefix(?string $prefix): void
    {
        $this->overridePrefix = $prefix;
    }

    private function maskSensitiveString(string $input): string
    {
        $input = $this->cryptoService->decrypt($input);
        if (strlen($input) < 5) {
            return "****";
        }

        return substr($input, 0, 2)
            . str_repeat('*', 8)
            . substr($input, -2);
    }
}
