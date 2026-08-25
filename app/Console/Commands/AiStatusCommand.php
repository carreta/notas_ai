<?php

namespace App\Console\Commands;

use App\AI\AiReadiness;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('ai:status')]
#[Description('Verify AI provider configuration readiness (FR-011)')]
class AiStatusCommand extends Command
{
    public function __construct(private AiReadiness $readiness)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $active = $this->readiness->checkActive();
        $all = $this->readiness->checkAll();

        $this->line('<options=bold>AI Provider Readiness (FR-011)</>');
        $this->newLine();

        foreach ($all as $provider => $result) {
            $mark = $result->ready ? '<green>READY</>' : '<red>NOT READY</>';
            $activeTag = $provider === $active->provider ? ' <comment>(active)</>' : '';
            $this->line("  [{$mark}] {$provider}{$activeTag}");

            foreach ($result->issues as $issue) {
                $this->line("      - {$issue}");
            }
        }

        $this->newLine();

        if ($active->ready) {
            $this->info("Active provider '{$active->provider}' is ready (driver: {$active->driver}).");

            return self::SUCCESS;
        }

        $this->error("Active provider '{$active->provider}' is NOT ready (driver: {$active->driver}).");

        return self::FAILURE;
    }
}
