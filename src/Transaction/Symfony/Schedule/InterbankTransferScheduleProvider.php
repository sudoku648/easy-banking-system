<?php

declare(strict_types=1);

namespace App\Transaction\Symfony\Schedule;

use App\Transaction\Application\Command\ProcessInterbankTransfersCommand;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;

#[AsSchedule('interbank_transfers')]
final readonly class InterbankTransferScheduleProvider implements ScheduleProviderInterface
{
    public function getSchedule(): Schedule
    {
        return new Schedule()
            ->add(
                // Run at 06:00
                RecurringMessage::cron('0 6 * * *', new ProcessInterbankTransfersCommand(), new \DateTimeZone('Europe/Warsaw')),
            )
            ->add(
                // Run at 09:00
                RecurringMessage::cron('0 9 * * *', new ProcessInterbankTransfersCommand(), new \DateTimeZone('Europe/Warsaw')),
            )
            ->add(
                // Run at 12:00
                RecurringMessage::cron('0 12 * * *', new ProcessInterbankTransfersCommand(), new \DateTimeZone('Europe/Warsaw')),
            )
            ->add(
                // Run at 15:00
                RecurringMessage::cron('0 15 * * *', new ProcessInterbankTransfersCommand(), new \DateTimeZone('Europe/Warsaw')),
            )
            ->add(
                // Run at 18:00
                RecurringMessage::cron('0 18 * * *', new ProcessInterbankTransfersCommand(), new \DateTimeZone('Europe/Warsaw')),
            )
            ->add(
                // Run at 21:00
                RecurringMessage::cron('0 21 * * *', new ProcessInterbankTransfersCommand(), new \DateTimeZone('Europe/Warsaw')),
            );
    }
}
