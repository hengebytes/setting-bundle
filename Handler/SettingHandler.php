<?php

namespace Hengebytes\SettingBundle\Handler;

use Hengebytes\SettingBundle\Entity\Setting;
use Hengebytes\SettingBundle\Interfaces\SettingHandlerInterface;
use Hengebytes\SettingBundle\Service\CryptoService;
use Doctrine\Persistence\{ManagerRegistry, ObjectManager, ObjectRepository};

class SettingHandler implements SettingHandlerInterface
{
    protected const string SETTING_NOT_DEFINED_INDICATOR = "\n!\t";
    /**  setting with this prefix will be first priority */
    protected string $overridePrefix = '';

    protected ObjectManager $em;
    protected ObjectRepository $repository;
    protected CryptoService $cryptoService;
    private array $runTimeStorage = [];

    public function __construct(ManagerRegistry $objectManager, protected string $entityClass, CryptoService $cryptoService)
    {
        $this->em = $objectManager->getManager();
        $this->repository = $this->em->getRepository($entityClass);
        $this->cryptoService = $cryptoService;
    }

    public function set(string $name, string $value, bool $isSensitive = false): void
    {
        $originalValue = $value;
        if ($isSensitive) {
            $value = $this->cryptoService->encrypt($value);
        }

        /** @var Setting $setting */
        $setting = $this->repository->findOneBy(['name' => $name]);

        if ($setting !== null) {
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
        $settingValue = $this->innerGet($this->overridePrefix . $name) ?? $this->innerGet($name);

        return $settingValue ?? $default;
    }

    protected function innerGet(string $name): ?string
    {
        $runTimeValue = $this->getRunTime($name);
        if ($runTimeValue !== null) {
            return $runTimeValue !== self::SETTING_NOT_DEFINED_INDICATOR ? $runTimeValue : null;
        }

        /** @var Setting $setting */
        $setting = $this->repository->findOneBy(['name' => $name]);
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

    public function getRunTime(string $name): ?string
    {
        return $this->runTimeStorage[$name] ?? null;
    }

    public function getGrouped(): array
    {
        return $this->group(
            $this->repository->findAll()
        );
    }

    private function group(array $settings): array
    {
        $return = [];
        /** @var Setting $setting */
        foreach ($settings as $setting) {
            $groupName = 0;
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
        $setting = $this->repository->findOneBy(['name' => $name]);
        if ($setting !== null) {
            $this->em->remove($setting);
            $this->em->flush();
            $this->setRunTime($name, self::SETTING_NOT_DEFINED_INDICATOR);
        }
    }

    public function setOverridePrefix(string $prefix): void
    {
        $this->overridePrefix = $prefix;
    }
}
