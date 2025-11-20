<?php

declare(strict_types=1);

namespace App\Shared\Cli;

use App\Shared\Infrastructure\Fixtures\FixtureLoader;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:fixtures:load',
    description: 'Load fixtures into the database',
)]
final class LoadFixturesConsoleCommand extends Command
{
    public function __construct(
        private readonly FixtureLoader $fixtureLoader,
        private readonly Connection $connection,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'purge',
                null,
                InputOption::VALUE_NONE,
                'Purge all data before loading fixtures',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $purge = $input->getOption('purge');

        if ($purge) {
            if ($input->isInteractive()) {
                $io->warning('This will purge all data from the database!');

                if (!$io->confirm('Are you sure you want to continue?', false)) {
                    $io->info('Fixtures loading cancelled.');

                    return Command::SUCCESS;
                }
            }

            $this->purgeDatabase($io);
        }

        $io->title('Loading fixtures...');

        try {
            $this->fixtureLoader->load();

            $io->success('Fixtures loaded successfully!');

            $io->section('Summary');
            $io->table(
                ['Entity', 'Count'],
                [
                    ['Employees', $this->getCount('"user"', 'role', 'EMPLOYEE')],
                    ['Customers', $this->getCount('"user"', 'role', 'CUSTOMER')],
                    ['Bank Accounts', $this->getCount('bank_account')],
                    ['Transactions', $this->getCount('transaction')],
                ],
            );

            $io->note([
                'Default password for all users: password123',
                'Sample employees:',
                '  - john.smith',
                '  - anna.kowalska',
                '  - michael.brown',
            ]);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error(\sprintf('Failed to load fixtures: %s', $e->getMessage()));

            return Command::FAILURE;
        }
    }

    private function purgeDatabase(SymfonyStyle $io): void
    {
        $io->info('Purging database...');

        $this->connection->executeStatement('TRUNCATE TABLE transaction CASCADE');
        $this->connection->executeStatement('TRUNCATE TABLE bank_account CASCADE');
        $this->connection->executeStatement('TRUNCATE TABLE "user" CASCADE');

        $io->success('Database purged.');
    }

    private function getCount(string $table, ?string $column = null, ?string $value = null): int
    {
        $sql = \sprintf('SELECT COUNT(*) FROM %s', $table);

        if ($column !== null && $value !== null) {
            $sql .= \sprintf(' WHERE %s = :value', $column);

            return (int) $this->connection->fetchOne($sql, ['value' => $value]);
        }

        return (int) $this->connection->fetchOne($sql);
    }
}
