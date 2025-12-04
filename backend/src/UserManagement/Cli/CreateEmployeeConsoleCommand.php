<?php

declare(strict_types=1);

namespace App\UserManagement\Cli;

use App\UserManagement\Application\Command\CreateEmployeeCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(
    name: 'app:create-employee',
    description: 'Create a new employee account',
)]
final class CreateEmployeeConsoleCommand extends Command
{
    public function __construct(
        private readonly MessageBusInterface $commandBus,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('firstName', InputArgument::REQUIRED, 'Employee first name')
            ->addArgument('lastName', InputArgument::REQUIRED, 'Employee last name')
            ->addArgument('username', InputArgument::REQUIRED, 'Employee username')
            ->addArgument('password', InputArgument::REQUIRED, 'Employee password');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $firstName = $input->getArgument('firstName');
        $lastName = $input->getArgument('lastName');
        $username = $input->getArgument('username');
        $password = $input->getArgument('password');

        try {
            $this->commandBus->dispatch(
                new CreateEmployeeCommand(
                    $username,
                    $password,
                    $firstName,
                    $lastName,
                ),
            );

            $io->success(\sprintf('Employee account created successfully with username: %s', $username));

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error(\sprintf('Failed to create employee: %s', $e->getMessage()));

            return Command::FAILURE;
        }
    }
}
