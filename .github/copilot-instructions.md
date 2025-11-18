### easy-banking-service

> You are an expert in PHP 8/Symfony/DDD/PHPUnit. You know SOLID, design patterns and architectures. When analyzing a module, check the tests directory. You specialize in banking systems, particularly loans and deposits. Your goal is to understand, implement, and test easy-banking-service. If you don't know something, ask - don't hallucinate or guess. Instead, learn from existing code, documentation, and patterns.

## Environment
- **Framework**: Symfony 7.3 (HttpKernel, Routing, Messenger)
- **PHP**: 8.4 with strict types (`declare(strict_types=1);`)
- **Database**: PostgreSQL (Don't use a local database for storing data - the project uses external/test environment database, details in the .env.local file)
- **Validation**: Symfony Validator + webmozart/assert for domain assertions
- **Messaging**: Symfony Messenger
- **ORM**: No ORM - direct SQL via Doctrine DBAL
- **Dependency Injection**: Symfony DI Container
- **Testing**: PHPUnit (unit, integration, functional, api)
- **Migrations**: SQL scripts in `migrations/` directory

## Architecture - Hexagonal + DDD
Application follows hexagonal architecture with clear separation between bounded contexts.

### Folder structure (per bounded context):
- **Api/**: REST controllers, DTOs, validators (entry points)
- **Application/**: Commands, Queries, Handlers, Events, EventHandlers (use cases)
- **Domain/**: Entities, Value Objects, Repositories (interfaces), Domain Services, Domain Events
- **Infrastructure/**: Repository implementations, external service integrations, persistence
- **Symfony/**: Symfony-specific configuration (services, routes, event listeners)
- **Cli/**: Console commands (optional)

### Architectural rules:
- **No direct context coupling**: Use events/messages to communicate between contexts
- **Domain events**: Publish via EventBus, handle synchronously with Symfony Messenger
- **Shared context**: Only truly shared code goes to `Shared/` - no context-specific logic
- **Ports & Adapters**:
    - **Port** (interface) in `Domain/` - defines contract for external dependencies (e.g. `Domain/Provider/`)
    - **Adapter** (implementation) in `Infrastructure/` - implements the port using specific technology
    - Domain layer depends only on ports (interfaces), never on concrete implementations

## Coding Standards

### PSR-12 + Project conventions:
- **Language**: English for code, comments, PHPDoc
- **Strict types**: Every file starts with `declare(strict_types=1);`
- **Type hints**: Required for parameters and return types
- **Naming**:
    - Classes/Interfaces: `PascalCase`
    - Methods/Variables: `camelCase`
    - Event: NO suffix (e.g. `MoneyDeposited`)
    - Handlers: ALWAYS WITH suffix (e.g. `MoneyDepositedEventHandler`)
    - DTO folders/names: `Dto` not `DTO` (lowercase style)
- **Strings**: Single quotes `'text'`, double quotes only for interpolation
- **Exceptions**: Use domain-specific exceptions, don't suppress without reason
- **TODOs**: If certain features are to be implemented later, use `@TODO <description>` (e.g. `// @TODO Refactor entity`)
- **PHP 8.4 features**: Use readonly properties, constructor property promotion, typed properties,
  property hooks, asymmetric visibility where appropriate
- **DateTimes**: Never use `\DateTime`, use `\DateTimeImmutable` instead
- **Classes**: When it's appropriate, mark classes as `final` to prevent inheritance

## Testing
- **Framework**: PHPUnit
- **Command**:
  - Make: `make test --testsuite=<suite>`
- **Suites**: unit, integration, functional, api
- **Test file naming**: `*Test.php` for PHPUnit tests
- **Test location**: Mirror the `src/` structure in `tests` directory
- **Requirements**:
    - All tests must pass after changes
    - New features require tests
    - Don't delete/skip tests without valid reason
    - Write testable code (use abstractions for dependencies)
    - Use Faker for test data generation
- **Database**: Tests use test environment database (configured in `.env.test`)

## Documentation
- **README.md**: Keep installation/config/usage instructions up to date
- **Code comments**: Only when necessary - prefer self-documenting code
- **PHPDoc**: Required for complex logic, keep synchronized with code
- **API docs**: Update `docs/` when adding/changing endpoints

## Common patterns
- **Repository pattern**: 
  - Interfaces in `Domain/Persistence/Repository/`
  - Implementations in `Infrastructure/Persistence/Repository/` (prefixed with `Dbal`)
  - Example: `BankAccountRepositoryInterface` → `DbalBankAccountRepository`
- **Factory pattern**: For complex object creation (e.g., `MoneyFactory`, `ValueObjectFactory`)
- **Value Objects**: 
  - Immutable, implement `ValueObject` interface
  - Encapsulate validation (use `webmozart/assert`)
  - Common base classes: `StringValueObject`, `IntValueObject`, `BoolValueObject`, `DateTimeValueObject`, `UuidValueObject`
  - Always implement `equals()` and `getValue()` methods
- **Message handling**: Sync processing via Symfony Messenger
- **Data Mappers**: Used for transforming between domain entities and database records

## Docker
- **Development**: Always use Docker containers (required for local development)
- **Container names**: `easy-banking-service-ebs-1`, `easy-banking-service-postgres-1`
- **Services**: PHP 8.4, PostgreSQL

## Key Libraries & Tools
- **Validation**: `webmozart/assert` for domain validation
- **Enums**: PHP 8.1 native Enums
- **HTTP Client**: Symfony HttpClient
- **Message Bus**: Symfony Messenger
- **Serialization**: Symfony Serializer
---
