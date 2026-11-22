<?php

/** Verify automatic API provisioning against an isolated, seeded database.
 * Depends on in-memory SQLite and Symfony command input streams.
 */
namespace Tests\Feature;

use App\Console\Commands\ConfigureManager;
use App\Models\ApiClient;
use App\Models\Part;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\TestCase;

class ConfigureManagerTest extends TestCase
{
    use RefreshDatabase;

    private function configure(string $token): CommandTester
    {
        $command = new ConfigureManager;
        $command->setLaravel($this->app);
        $tester = new CommandTester($command);
        $tester->setInputs([json_encode(['name' => 'Cabek AI Manager', 'token' => $token])]);
        $tester->execute([]);
        return $tester;
    }

    public function test_repeated_configuration_keeps_one_client_and_never_prints_token(): void
    {
        $this->seed();
        $token = str_repeat('secret-value-', 4);
        foreach ([1, 2] as $attempt) {
            $tester = $this->configure($token);
            $this->assertSame(0, $tester->getStatusCode());
            $this->assertStringNotContainsString($token, $tester->getDisplay());
        }
        $this->assertSame(1, ApiClient::count());
        $this->assertSame(hash('sha256', $token), ApiClient::first()->token_hash);
    }

    public function test_incorrect_canonical_ids_block_provisioning(): void
    {
        $this->seed();
        Part::findOrFail(31)->update(['libelle' => 'Incorrect part']);
        $tester = $this->configure(str_repeat('secret-value-', 4));
        $this->assertSame(1, $tester->getStatusCode());
        $this->assertSame(0, ApiClient::count());
    }

    public function test_short_credentials_are_rejected(): void
    {
        $tester = $this->configure('short');
        $this->assertSame(1, $tester->getStatusCode());
        $this->assertSame(0, ApiClient::count());
    }
}
