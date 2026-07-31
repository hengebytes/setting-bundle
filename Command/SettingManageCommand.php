<?php

namespace Hengebytes\SettingBundle\Command;

use Hengebytes\SettingBundle\Entity\Setting;
use Doctrine\ORM\EntityManagerInterface;
use Hengebytes\SettingBundle\Service\CryptoService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'hb:setting:manage', description: 'Manage Settings')]
class SettingManageCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CryptoService $cryptoService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Start manage');
        $io->writeln('Please select an action to perform');

        while (true) {
            $action = $io->choice('Select action', ['List', 'Create', 'Update', 'Delete', 'Exit']);
            if ($action === 'Exit') {
                break;
            }
            $this->performAction($action, $io);
        }

        return Command::SUCCESS;
    }

    private function performAction(string $action, SymfonyStyle $io): void
    {
        match ($action) {
            'Update' => $this->update($io),
            'Delete' => $this->delete($io),
            'List' => $this->list($io),
            'Create' => $this->create($io),
            default => throw new \InvalidArgumentException('Invalid action'),
        };
    }

    private function list(SymfonyStyle $io): void
    {
        $settings = $this->em->getRepository(Setting::class)->findAll();
        $rows = [];
        foreach ($settings as $setting) {
            try {
                $value = $setting->isSensitive ? $this->cryptoService->decrypt($setting->value) : $setting->value;
            } catch (\SodiumException $e) {
                $value = '<fg=red>Decryption failed</>';
            }
            $rows[] = [
                '<fg=green>' . $setting->id . '</>',
                $setting->name,
                $value,
                $setting->isSensitive ? '<fg=green>Yes</>' : '<fg=red>No</>',
            ];
            $rows[] = new TableSeparator();
        }
        $table = $io->createTable();
        $table->setStyle('box');
        $table
            ->setHeaders(['ID', 'Name', 'Value', 'Is Sensitive'])
            ->setRows($rows);
        $table->render();
    }

    private function delete(SymfonyStyle $io): void
    {
        $io->title('Delete');
        $id = $io->ask('ID');
        if (!$id) {
            $io->error('ID is required');

            return;
        }
        $setting = $this->em->getRepository(Setting::class)->find($id);
        if (!$setting instanceof Setting) {
            $io->error('Setting not found');

            return;
        }

        $this->em->remove($setting);
        $this->em->flush();

        $io->success('Setting is deleted');
    }

    private function create(SymfonyStyle $io): void
    {
        $io->title('Create new Setting');
        $name = $io->ask('Name');
        if (!$name) {
            $io->error('Name is required');

            return;
        }
        $value = $io->ask('Value');
        if (!$value) {
            $io->error('Value is required');

            return;
        }
        $isSensitive = $io->confirm('Is sensitive', false);
        if ($isSensitive) {
            $value = $this->cryptoService->encrypt($value);
        }

        $setting = new Setting();
        $setting->name = $name;
        $setting->value = $value;
        $setting->isSensitive = $isSensitive;

        $this->em->persist($setting);
        $this->em->flush();

        $io->success('Setting created');
    }

    private function update(SymfonyStyle $io): void
    {
        $io->title('Update config');
        $id = $io->ask('ID');
        if (!$id) {
            $io->error('ID is required');

            return;
        }
        $setting = $this->em->getRepository(Setting::class)->find($id);
        if (!$setting) {
            $io->error('Setting not found');

            return;
        }

        $name = $io->ask('Name', $setting->name);
        if ($name) {
            $setting->name = $name;
        }
        $setting->isSensitive = $io->confirm('Is sensitive', $setting->isSensitive);

        $value = $io->ask('Value',
            $setting->isSensitive ? $this->cryptoService->decrypt($setting->value) : $setting->value
        );

        if ($value) {
            $setting->value = $setting->isSensitive ? $this->cryptoService->encrypt($value) : $value;
        }

        $this->em->flush();

        $io->success('Setting updated');
    }
}
