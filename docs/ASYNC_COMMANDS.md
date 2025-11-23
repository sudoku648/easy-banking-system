# Asynchronous Command Handling

This document describes how to implement asynchronous command handling in the application using Symfony Messenger.

## Overview

The application supports both synchronous and asynchronous command execution. Commands that require async processing (e.g., long-running operations, external API calls) can be marked with the `AsyncCommandInterface` marker interface.

## Architecture

### Async Infrastructure

- **Marker Interface**: `App\Shared\Application\Command\AsyncCommandInterface`
- **Transport**: Configured via `MESSENGER_TRANSPORT_DSN` environment variable
- **Default Transport**: Doctrine-based (`doctrine://default`)
- **Test Environment**: Async commands run synchronously in tests for predictability

### Configuration

The async routing is configured in `config/packages/messenger.yaml`:

```yaml
framework:
    messenger:
        transports:
            sync: 'sync://'
            async: '%env(MESSENGER_TRANSPORT_DSN)%'
        
        routing:
            'App\Shared\Application\Command\AsyncCommandInterface': async

when@test:
    framework:
        messenger:
            transports:
                async: 'sync://'  # Run async commands synchronously in tests
```

## How to Make a Command Async

### Step 1: Implement the Marker Interface

Simply add `implements AsyncCommandInterface` to your command class:

```php
<?php

declare(strict_types=1);

namespace App\YourContext\Application\Command;

use App\Shared\Application\Command\AsyncCommandInterface;

final readonly class YourCommand implements AsyncCommandInterface
{
    public function __construct(
        public string $someParameter,
        // ... other parameters
    ) {
    }
}
```

### Step 2: That's It!

No other changes needed. The command handler remains unchanged, and Symfony Messenger will automatically route the command to the async transport.

## Example: TransferMoneyCommand

```php
<?php

declare(strict_types=1);

namespace App\Transaction\Application\Command;

use App\Shared\Application\Command\AsyncCommandInterface;

final readonly class TransferMoneyCommand implements AsyncCommandInterface
{
    public function __construct(
        public string $fromBankAccountId,
        public string $toBankAccountId,
        public int $amount,
        public string $currency,
    ) {
    }
}
```

The corresponding handler (`TransferMoneyCommandHandler`) doesn't need any changes - it remains a regular handler tagged with `messenger.message_handler`.

## Running the Worker

To process async commands in production, run the Messenger worker:

```bash
# Process messages from async transport
php bin/console messenger:consume async

# With options for production
php bin/console messenger:consume async --time-limit=3600 --memory-limit=128M
```

### Supervisor Configuration (Production)

Create `/etc/supervisor/conf.d/messenger-worker.conf`:

```ini
[program:messenger-consume]
command=php /path/to/app/bin/console messenger:consume async --time-limit=3600
user=www-data
numprocs=2
startsecs=0
autostart=true
autorestart=true
process_name=%(program_name)s_%(process_num)02d
```

## Testing

In the test environment, async commands are executed synchronously, so your tests work without changes:

```php
public function testTransferMoney(): void
{
    // Command is dispatched and immediately executed (not queued)
    $this->commandBus->dispatch(new TransferMoneyCommand(
        fromBankAccountId: $fromAccountId,
        toBankAccountId: $toAccountId,
        amount: 10000,
        currency: 'PLN',
    ));
    
    // Can immediately assert results
    $this->assertAccountBalance($fromAccountId, 90000);
}
```

## When to Use Async Commands

Consider making commands async when:

- **Long-running operations**: Operations taking > 1 second
- **External API calls**: Third-party services with unpredictable latency
- **Non-critical operations**: User doesn't need immediate feedback
- **Bulk operations**: Processing large datasets
- **Background tasks**: Scheduled or deferred work

## When NOT to Use Async Commands

Keep commands synchronous when:

- **Immediate feedback required**: User needs instant response
- **Critical path operations**: Core business flows
- **Simple CRUD operations**: Fast database operations
- **Transactional consistency needed**: When you need to return data immediately

## Failure Handling

Symfony Messenger provides automatic retry mechanism. Configure in `messenger.yaml`:

```yaml
framework:
    messenger:
        failure_transport: failed
        
        transports:
            async:
                dsn: '%env(MESSENGER_TRANSPORT_DSN)%'
                retry_strategy:
                    max_retries: 3
                    delay: 1000
                    multiplier: 2
            
            failed: 'doctrine://default?queue_name=failed'
```

View failed messages:

```bash
php bin/console messenger:failed:show
```

Retry failed messages:

```bash
php bin/console messenger:failed:retry
```

## Best Practices

1. **Idempotency**: Async handlers should be idempotent (safe to execute multiple times)
2. **Command Data**: Include all necessary data in the command (don't rely on external state)
3. **Error Handling**: Handle exceptions gracefully, log errors for debugging
4. **Monitoring**: Monitor queue depth and processing times in production
5. **Testing**: Always test both success and failure scenarios

## Environment Variables

Configure in `.env`:

```bash
# Doctrine transport (default)
MESSENGER_TRANSPORT_DSN=doctrine://default?auto_setup=0

# Or use Redis
MESSENGER_TRANSPORT_DSN=redis://localhost:6379/messages

# Or use RabbitMQ
MESSENGER_TRANSPORT_DSN=amqp://guest:guest@localhost:5672/%2f/messages
```

## Troubleshooting

### Messages not being processed

1. Check worker is running: `ps aux | grep messenger:consume`
2. Check transport connection: `php bin/console messenger:stats`
3. Check failed messages: `php bin/console messenger:failed:show`

### Messages processed multiple times

- Ensure handlers are idempotent
- Check for exceptions causing redelivery
- Review retry configuration

### Performance issues

- Increase number of workers
- Use dedicated transport (Redis/RabbitMQ instead of Doctrine)
- Optimize handler execution time
- Consider batching operations
