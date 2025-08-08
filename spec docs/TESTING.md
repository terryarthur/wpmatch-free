# Testing Guide for WP Match Free

## Overview

WP Match Free includes comprehensive PHPUnit tests covering:
- Profile CRUD operations
- REST API endpoints and authentication
- Rate limiting functionality
- Access controls and blocking system
- Word filtering and content moderation

## Prerequisites

### System Requirements
- PHP 8.1 or higher
- MySQL/MariaDB
- Composer
- WordPress test suite

### Dependencies Installation

```bash
# Install PHP dependencies
composer install

# Install WordPress test suite
composer run test:install

# Or manually with custom database settings:
./bin/install-wp-tests.sh <db-name> <db-user> <db-pass> [db-host] [wp-version]
```

## Test Configuration

### Environment Variables

Set these environment variables for custom test configuration:

```bash
export WP_TESTS_DIR=/tmp/wordpress-tests-lib
export WP_CORE_DIR=/tmp/wordpress/
export WP_TESTS_PHPUNIT_POLYFILLS_PATH=vendor/yoast/phpunit-polyfills
```

### Database Setup

The test suite requires a separate MySQL database:

```bash
# Create test database
mysql -u root -p -e "CREATE DATABASE wordpress_test;"

# Install WordPress test suite with database
./bin/install-wp-tests.sh wordpress_test root 'your_password' localhost latest
```

## Running Tests

### All Tests
```bash
# Run complete test suite
composer run test

# Or directly with PHPUnit
vendor/bin/phpunit
```

### Specific Test Suites
```bash
# Run only unit tests
composer run test:unit

# Run specific test class
vendor/bin/phpunit tests/ProfilesTest.php

# Run specific test method
vendor/bin/phpunit tests/RestApiTest.php::test_profiles_endpoint_returns_data
```

### Test Coverage
```bash
# Generate HTML coverage report
composer run test:coverage

# View coverage report
open coverage-html/index.html
```

## Test Structure

### Test Classes

1. **ProfilesTest.php** - Profile CRUD operations
   - Profile creation and validation
   - Profile updates and retrieval
   - User profile associations

2. **RestApiTest.php** - REST API functionality
   - Endpoint responses and data structure
   - Authentication requirements
   - Filtering and pagination
   - Access control enforcement

3. **RateLimitsTest.php** - Rate limiting and moderation
   - Message rate limiting (20/day default)
   - Like rate limiting (50/day default) 
   - Word filter functionality
   - Per-user limit enforcement

### Test Data

Tests use WordPress's built-in factory system:
- `$this->factory->user->create()` - Creates test users
- `wpmf_profile_create()` - Creates dating profiles
- Test database is reset between test methods

## Continuous Integration

### GitHub Actions

The repository includes automated testing via GitHub Actions:

```yaml
# .github/workflows/test.yml
name: Tests
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    strategy:
      matrix:
        php: [8.1, 8.2, 8.3]
        wordpress: [6.5, latest]
```

### Local Pre-commit Hooks

Set up pre-commit testing:

```bash
# Create pre-commit hook
cat > .git/hooks/pre-commit << 'EOF'
#!/bin/sh
echo "Running tests..."
composer run test
if [ $? -ne 0 ]; then
    echo "Tests failed. Commit aborted."
    exit 1
fi
EOF

chmod +x .git/hooks/pre-commit
```

## Writing New Tests

### Test Class Template

```php
<?php
/**
 * Test new functionality.
 */
class NewFeatureTest extends WP_UnitTestCase {
    
    protected static $user_id;
    
    public static function wpSetUpBeforeClass( $factory ) {
        self::$user_id = $factory->user->create();
    }
    
    public function test_new_feature() {
        // Arrange
        $expected = 'expected_value';
        
        // Act
        $result = new_feature_function();
        
        // Assert
        $this->assertEquals( $expected, $result );
    }
}
```

### Best Practices

1. **Use descriptive test names** - `test_profiles_endpoint_excludes_blocked_users`
2. **Follow AAA pattern** - Arrange, Act, Assert
3. **Clean up after tests** - Delete created data in tearDown methods
4. **Test edge cases** - Empty inputs, boundary conditions, error states
5. **Mock external dependencies** - Don't rely on external APIs or services

## Debugging Tests

### Verbose Output
```bash
# Run tests with verbose output
vendor/bin/phpunit --verbose

# Debug specific test
vendor/bin/phpunit --debug tests/ProfilesTest.php::test_profile_creation
```

### Test Database Inspection
```bash
# Connect to test database
mysql -u root -p wordpress_test

# View test tables
SHOW TABLES LIKE 'wp_wpmf_%';
```

### WordPress Debug Mode
```php
// Add to tests/bootstrap.php for debugging
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
```

## Performance Testing

### Database Query Analysis
```php
// Add to test methods for query debugging
$queries_before = get_num_queries();
// ... test code ...
$queries_after = get_num_queries();
$this->assertLessThan( 10, $queries_after - $queries_before );
```

### Memory Usage Testing
```php
public function test_memory_usage() {
    $memory_start = memory_get_usage();
    // ... test large operations ...
    $memory_end = memory_get_usage();
    $this->assertLessThan( 1048576, $memory_end - $memory_start ); // < 1MB
}
```

## Common Issues and Solutions

### Test Database Connection Errors
- Verify MySQL is running
- Check database credentials in wp-tests-config.php
- Ensure test database exists and is accessible

### WordPress Core Not Found
- Run `composer run test:install` to download WordPress core
- Check `WP_TESTS_DIR` and `WP_CORE_DIR` environment variables

### Plugin Dependencies Missing
- Ensure all plugin files are loaded in bootstrap.php
- Check that required functions exist before testing

### Memory Limit Issues
```bash
# Increase PHP memory limit for tests
php -d memory_limit=256M vendor/bin/phpunit
```

## Integration with IDE

### PHPStorm Configuration
1. Go to Settings > PHP > Test Frameworks
2. Add PHPUnit by Remote Interpreter
3. Set configuration file to `/path/to/phpunit.xml`
4. Set bootstrap file to `/path/to/tests/bootstrap.php`

### VS Code Configuration
```json
// .vscode/settings.json
{
    "php.validate.executablePath": "/usr/bin/php",
    "phpunit.php": "/usr/bin/php",
    "phpunit.phpunit": "vendor/bin/phpunit",
    "phpunit.args": ["--configuration", "phpunit.xml"]
}
```

---

For more information about WordPress testing, see:
- [WordPress Plugin Handbook - Automated Testing](https://developer.wordpress.org/plugins/plugin-basics/automated-testing/)
- [WP-CLI Testing Guide](https://make.wordpress.org/cli/handbook/plugin-unit-tests/)