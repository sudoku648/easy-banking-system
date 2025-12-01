<?php

declare(strict_types=1);

namespace App\Transaction\Cli\Command;

use App\Transaction\Application\Command\ProcessInterbankTransfersCommand as ProcessInterbankTransfersApplicationCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(
    name: 'app:interbank-transfers:process',
    description: 'Process pending interbank transfers',
)]
final class ProcessInterbankTransfersConsoleCommand extends Command
{
    public function __construct(
        private readonly MessageBusInterface $commandBus,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Processing pending interbank transfers...');

        $this->commandBus->dispatch(new ProcessInterbankTransfersApplicationCommand());

        $output->writeln('Interbank transfers processed successfully.');

        return Command::SUCCESS;
    }
}
