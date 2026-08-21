<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

#[Signature('check')]
#[Description('Run all quality checks and produce a formatted report')]
class Check extends Command
{
    private array $results = [];

    private int $totalDuration = 0;

    // Test environment variables matching phpunit.xml
    private array $testEnv = [
        'APP_ENV' => 'testing',
        'APP_MAINTENANCE_DRIVER' => 'file',
        'BCRYPT_ROUNDS' => '4',
        'BROADCAST_CONNECTION' => 'null',
        'CACHE_STORE' => 'array',
        'DB_CONNECTION' => 'sqlite',
        'DB_DATABASE' => ':memory:',
        'MAIL_MAILER' => 'array',
        'QUEUE_CONNECTION' => 'sync',
        'SESSION_DRIVER' => 'array',
    ];

    public function handle(): int
    {
        $this->info('Running quality checks...');
        $this->newLine();

        $checks = [
            ['route:list', 'Route List Verification', fn () => $this->runRouteList()],
            ['analyse', 'Static Analysis (Larastan)', fn () => $this->runAnalyse()],
            ['test', 'Tests (PHPUnit)', fn () => $this->runTests()],
            ['pint', 'Code Style (Pint)', fn () => $this->runPint()],
            ['validate', 'Composer Validate', fn () => $this->runValidate()],
            ['platform-reqs', 'Platform Requirements', fn () => $this->runPlatformReqs()],
            ['build', 'Frontend Build', fn () => $this->runBuild()],
            ['migrations', 'Clean Migrations', fn () => $this->runMigrations()],
        ];

        foreach ($checks as [$name, $label, $callback]) {
            $this->runCheck($name, $label, $callback);
        }

        $this->printReport();

        $failed = collect($this->results)->where('status', 'FAIL')->count();

        return $failed > 0 ? 1 : 0;
    }

    /**
     * @param  callable(): array  $callback
     */
    private function runCheck(string $name, string $label, callable $callback): void
    {
        $this->line("  <comment>{$label}...</>");
        $start = microtime(true);
        $result = $callback();
        $duration = (int) round((microtime(true) - $start) * 1000);

        $this->results[$name] = [
            'label' => $label,
            'status' => $result['success'] ? 'PASS' : 'FAIL',
            'output' => $result['output'] ?? '',
            'duration' => $duration,
        ];
        $this->totalDuration += $duration;

        $statusColor = $result['success'] ? 'green' : 'red';
        $this->line("  <{$statusColor}>{$this->results[$name]['status']}</> ({$duration}ms)");
    }

    private function runRouteList(): array
    {
        try {
            $result = Process::run('php artisan route:list');

            return [
                'success' => $result->successful(),
                'output' => $result->output(),
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'output' => $e->getMessage()];
        }
    }

    private function runAnalyse(): array
    {
        try {
            $result = Process::env($this->testEnv)->run('composer analyse');

            return [
                'success' => $result->successful(),
                'output' => $result->output(),
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'output' => $e->getMessage()];
        }
    }

    private function runTests(): array
    {
        try {
            // Clear config cache first to ensure fresh state
            Process::run('php artisan config:clear');

            // Use a file-based SQLite database that persists across processes
            // The in-memory database doesn't work because each process gets a fresh one
            $testEnvWithFileDb = array_merge($this->testEnv, [
                'DB_DATABASE' => base_path('database/testing.sqlite'),
            ]);

            // Run migrations for the test database
            Process::env($testEnvWithFileDb)->run('php artisan migrate:fresh --force');

            // Run tests with the file-based database
            $result = Process::env($testEnvWithFileDb)->run('composer test');
            $output = $result->output();
            $tests = 0;
            $assertions = 0;

            if (preg_match('/"tests":(\d+)/', $output, $m)) {
                $tests = (int) $m[1];
            }
            if (preg_match('/"assertions":(\d+)/', $output, $m)) {
                $assertions = (int) $m[1];
            }

            // Clean up test database file
            @unlink(base_path('database/testing.sqlite'));

            return [
                'success' => $result->successful(),
                'output' => $output,
                'tests' => $tests,
                'assertions' => $assertions,
            ];
        } catch (\Throwable $e) {
            // Clean up on error
            @unlink(base_path('database/testing.sqlite'));

            return ['success' => false, 'output' => $e->getMessage()];
        }
    }

    private function runPint(): array
    {
        try {
            $pintPath = PHP_OS_FAMILY === 'Windows' ? 'php vendor/bin/pint' : './vendor/bin/pint';
            $result = Process::run("$pintPath --test");

            return [
                'success' => $result->successful(),
                'output' => $result->output(),
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'output' => $e->getMessage()];
        }
    }

    private function runValidate(): array
    {
        try {
            $result = Process::run('composer validate --strict');

            return [
                'success' => $result->successful(),
                'output' => $result->output(),
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'output' => $e->getMessage()];
        }
    }

    private function runPlatformReqs(): array
    {
        try {
            $result = Process::run('composer check-platform-reqs');

            return [
                'success' => $result->successful(),
                'output' => $result->output(),
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'output' => $e->getMessage()];
        }
    }

    private function runBuild(): array
    {
        try {
            $result = Process::run('npm run build');

            return [
                'success' => $result->successful(),
                'output' => $result->output(),
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'output' => $e->getMessage()];
        }
    }

    private function runMigrations(): array
    {
        try {
            $result = Process::run('php artisan migrate:fresh --force');

            return [
                'success' => $result->successful(),
                'output' => $result->output(),
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'output' => $e->getMessage()];
        }
    }

    private function printReport(): void
    {
        $this->newLine();
        $this->line(str_repeat('=', 60));
        $this->line('<options=bold>QUALITY CHECK REPORT</>');
        $this->line(str_repeat('=', 60));
        $this->newLine();

        foreach ($this->results as $name => $result) {
            $status = $result['status'];
            $color = $status === 'PASS' ? 'green' : 'red';
            $this->line("<{$color}>[{$status}]</> {$result['label']} ({$result['duration']}ms)");

            if ($name === 'test' && isset($result['tests'])) {
                $this->line("       Tests: {$result['tests']} | Assertions: {$result['assertions']}");
            }
        }

        $this->newLine();
        $this->line(str_repeat('-', 60));

        $passed = collect($this->results)->where('status', 'PASS')->count();
        $total = count($this->results);

        $this->line("Total: {$passed}/{$total} checks passed");
        $this->line("Duration: {$this->totalDuration}ms");

        if ($passed === $total) {
            $this->newLine();
            $this->line('<green><options=bold>All checks passed!</></>');
        } else {
            $this->newLine();
            $this->line('<red><options=bold>Some checks failed.</></>');
        }
    }
}
