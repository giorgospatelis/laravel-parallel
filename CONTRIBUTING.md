# Contributing to Laravel Parallel

Thank you for considering contributing to Laravel Parallel! This guide will help you get started.

## Code of Conduct

We expect all contributors to treat each other with respect and professionalism. Please be kind and courteous in all interactions.

## How to Contribute

### Reporting Bugs

If you discover a bug, please create an issue on GitHub with:

- A clear, descriptive title
- Steps to reproduce the issue
- Expected behavior vs. actual behavior
- Your environment (PHP version, Laravel version, OS)
- Any relevant code samples or error messages

### Suggesting Enhancements

We welcome feature suggestions! Please create an issue with:

- A clear description of the proposed feature
- Use cases and benefits
- Any implementation ideas you have
- Examples of how it would be used

### Pull Requests

We actively welcome your pull requests! Follow these steps:

1. **Fork the repository** and create your branch from `develop`
2. **Install dependencies**: `composer install`
3. **Make your changes** following our coding standards
4. **Add tests** for any new functionality
5. **Run the test suite**: `composer test`
6. **Run static analysis**: `composer phpstan`
7. **Format your code**: `composer format`
8. **Commit your changes** with clear, descriptive messages
9. **Push to your fork** and submit a pull request

## Development Setup

### Prerequisites

- PHP 8.2 or higher
- Composer
- Git

### Installation

```bash
# Clone your fork
git clone https://github.com/YOUR_USERNAME/parallel-package.git
cd parallel-package

# Install dependencies
composer install
```

### Running Tests

```bash
# Run all tests
composer test

# Run a specific test file
./vendor/bin/pest tests/Unit/SpecificTest.php

# Run with coverage
composer test:coverage

# Generate HTML coverage report
composer test:coverage-html
```

### Code Quality

```bash
# Run PHPStan (static analysis)
composer phpstan

# Check code style
composer format:test

# Fix code style automatically
composer format
```

## Coding Standards

We follow strict coding standards to maintain code quality:

### Style Guide

- **PSR-12**: We follow PSR-12 coding standards
- **Laravel Pint**: Code style is enforced with Pint
- **PHPStan Level 9**: All code must pass PHPStan at level 9

### Best Practices

1. **Type Everything**: Use strict types and type hints everywhere
   ```php
   declare(strict_types=1);

   public function process(string $data): array
   {
       // ...
   }
   ```

2. **SOLID Principles**: Follow SOLID design principles
   - Single Responsibility: One class, one job
   - Open/Closed: Extend, don't modify
   - Liskov Substitution: Use interfaces
   - Interface Segregation: Small, focused interfaces
   - Dependency Inversion: Depend on abstractions

3. **Immutability**: Prefer readonly properties and immutable objects
   ```php
   final class WorkerConfiguration
   {
       public function __construct(
           public readonly int $workers,
           public readonly float $timeout,
       ) {}
   }
   ```

4. **Final Classes**: Use `final` by default, extend only when necessary

5. **Named Parameters**: Use named parameters for clarity
   ```php
   new WorkerConfiguration(
       workers: 4,
       timeout: 30.0,
   );
   ```

### Testing Requirements

- **All new features** must include tests
- **All bug fixes** should include a test demonstrating the fix
- **Test coverage** should remain above 80%
- **Test naming**: Use descriptive test names that explain what is being tested

Example test structure:

```php
test('executor handles task exceptions gracefully', function () {
    // Arrange
    $task = fn() => throw new RuntimeException('Task failed');

    // Act
    $results = Parallel::run(['task' => $task]);

    // Assert
    expect($results['task']->isFailure())->toBeTrue();
    expect($results['task']->getException())
        ->toBeInstanceOf(RuntimeException::class);
});
```

## Documentation

- **PHPDoc blocks**: Use for all public methods and classes
- **Type hints**: Always include parameter and return types
- **Comments**: Explain "why", not "what" (code should be self-documenting)

Example:

```php
/**
 * Executes tasks in parallel across worker processes.
 *
 * Tasks are distributed evenly across available workers for optimal
 * performance. Each task runs in isolation with its own memory space.
 *
 * @param  array<string, callable>  $tasks  Named tasks to execute
 * @return array<string, ParallelResult>  Results keyed by task name
 * @throws WorkerPoolException  If worker pool cannot be created
 */
public function execute(array $tasks): array
{
    // Implementation
}
```

## Commit Messages

Write clear, descriptive commit messages:

```
Add support for custom task validators

- Implement TaskValidatorContract interface
- Add configuration option for custom validators
- Include tests for validator functionality
- Update documentation with extension example

Fixes #123
```

### Format

- Use present tense ("Add feature" not "Added feature")
- Use imperative mood ("Move cursor to..." not "Moves cursor to...")
- First line should be 50 characters or less
- Reference issues and PRs when applicable

## Branch Naming

Use descriptive branch names with prefixes:

- `feature/add-custom-validators`
- `bugfix/fix-timeout-handling`
- `refactor/simplify-executor`
- `docs/improve-readme`

## Pull Request Process

1. **Update documentation** if you're adding or changing functionality
2. **Update CHANGELOG.md** with a description of your changes
3. **Ensure all tests pass** and code meets quality standards
4. **Request review** from maintainers
5. **Address feedback** promptly and professionally

### Pull Request Template

Your PR description should include:

```markdown
## Description
Brief description of what this PR does

## Motivation
Why is this change needed?

## Changes
- List of specific changes made
- Breaking changes (if any)

## Testing
How was this tested?

## Checklist
- [ ] Tests added/updated
- [ ] Documentation updated
- [ ] CHANGELOG.md updated
- [ ] Code passes PHPStan
- [ ] Code formatted with Pint
```

## Getting Help

If you need help with your contribution:

- **Create an issue**: For questions or clarifications
- **Review existing issues**: Someone may have had the same question
- **Check documentation**: Architecture docs in the repo

## Recognition

All contributors will be recognized in:

- The [README.md](README.md) credits section
- The GitHub contributors page
- Release notes (for significant contributions)

## License

By contributing to Laravel Parallel, you agree that your contributions will be licensed under the MIT License.

## Questions?

If you have any questions about contributing, feel free to create an issue or reach out to the maintainers.

Thank you for contributing to Laravel Parallel!
