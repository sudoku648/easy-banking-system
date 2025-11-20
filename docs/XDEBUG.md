# Xdebug Configuration

Xdebug is pre-configured and enabled in both **dev** and **test** environments for debugging and code coverage.

## Configuration Details

- **Xdebug Version**: 3.4.7
- **Mode**: `debug,coverage`
- **Client Host**: `host.docker.internal`
- **Client Port**: `9003`
- **IDE Key**: `PHPSTORM`
- **Start With Request**: `yes`

## IDE Configuration

### PhpStorm / IntelliJ IDEA

1. **Configure PHP Server**:
   - Go to `Settings` → `PHP` → `Servers`
   - Add a new server:
     - **Name**: `ebs` (for dev) or `ebs-test` (for test)
     - **Host**: `localhost`
     - **Port**: `8080` (for dev) or `80` (for test)
     - **Debugger**: `Xdebug`
     - **Use path mappings**: Check this option
     - Map your project root to `/app`

2. **Start Listening**:
   - Click the "Start Listening for PHP Debug Connections" button (phone icon) in the toolbar
   - Or use menu: `Run` → `Start Listening for PHP Debug Connections`

3. **Set Breakpoints**:
   - Click in the gutter next to the line numbers to set breakpoints
   - Execute your code (tests, web requests, CLI commands)
   - PhpStorm will pause at your breakpoints

### VS Code

1. **Install PHP Debug Extension**:
   - Install "PHP Debug" by Felix Becker

2. **Configure Launch Settings** (`.vscode/launch.json`):
   ```json
   {
       "version": "0.2.0",
       "configurations": [
           {
               "name": "Listen for Xdebug (Dev)",
               "type": "php",
               "request": "launch",
               "port": 9003,
               "pathMappings": {
                   "/app": "${workspaceFolder}"
               }
           }
       ]
   }
   ```

3. **Start Debugging**:
   - Press F5 or click "Start Debugging"
   - Set breakpoints by clicking in the gutter
   - Execute your code

## Usage Examples

### Debugging Tests

```bash
# Test environment (already has Xdebug enabled)
docker compose exec -u 1000:1000 ebs php vendor/bin/phpunit tests/Unit/Shared
```

Just start listening in your IDE and run the tests - the debugger will connect automatically.

### Debugging Web Requests

1. Start listening for debug connections in your IDE
2. Navigate to http://localhost:8080 in your browser
3. Your IDE will pause at breakpoints

### Debugging Console Commands

```bash
# Dev environment
docker compose -f docker-compose.dev.yaml exec ebs php bin/console your:command
```

### Code Coverage

Xdebug is configured with coverage mode enabled. To generate code coverage:

```bash
# Run tests with coverage
docker compose exec -u 1000:1000 ebs php vendor/bin/phpunit --coverage-html var/coverage
```

## Troubleshooting

### Xdebug Not Connecting

1. **Verify Xdebug is loaded**:
   ```bash
   docker compose exec ebs php -v
   ```
   Should show "with Xdebug v3.4.7"

2. **Check Xdebug configuration**:
   ```bash
   docker compose exec ebs php -i | grep xdebug
   ```

3. **Verify host connectivity**:
   - Ensure port 9003 is not blocked by firewall
   - On Linux, `host.docker.internal` is mapped via `extra_hosts` in docker-compose

4. **Check IDE settings**:
   - Verify server name matches `PHP_IDE_CONFIG` (`ebs` or `ebs-test`)
   - Verify port 9003 is configured
   - Verify path mappings are correct (`/app` → project root)

### Performance Impact

Xdebug can slow down execution. If you don't need debugging:

1. **Temporarily disable Xdebug**:
   ```bash
   # Run PHP without Xdebug loading
   docker compose exec ebs php -d xdebug.mode=off your-command
   ```

2. **For production**: The production Docker stage doesn't include Xdebug

## Environment Variables

You can override Xdebug settings using environment variables in docker-compose files:

```yaml
environment:
  XDEBUG_MODE: debug,coverage
  XDEBUG_CONFIG: client_host=host.docker.internal client_port=9003
```

## Additional Resources

- [Xdebug Documentation](https://xdebug.org/docs/)
- [PhpStorm Xdebug Guide](https://www.jetbrains.com/help/phpstorm/configuring-xdebug.html)
- [VS Code PHP Debug](https://marketplace.visualstudio.com/items?itemName=xdebug.php-debug)
