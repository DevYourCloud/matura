<?php

namespace App\Command\Admin;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:encryption-key')]
class GenerateEncryptionKeyCommand extends Command
{
    protected function configure(): void
    {
        $this->setDescription('Generate a 256-bit encryption key.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $key = base64_encode(openssl_random_pseudo_bytes(32));
        $io = new SymfonyStyle($input, $output);
        $io->title('Generated Key');
        $io->success(sprintf('APP_ENCRYPTION_KEY="%s"', $key));
        $io->writeln('Put this key in your .env!');
        $io->writeln('');

        return Command::SUCCESS;
    }
}
