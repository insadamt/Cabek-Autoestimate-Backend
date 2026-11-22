<?php

/** Configure the local Cabek API client without logging credentials.
 * Depends on migrated tables and canonical seeders; rejects pricing ID drift.
 */
namespace App\Console\Commands;

use App\Models\ApiClient;
use App\Models\Part;
use Database\Seeders\PartSeeder;
use Illuminate\Console\Command;

class ConfigureManager extends Command
{
    protected $signature = 'manager:configure';
    protected $description = 'Provision Cabek Manager credentials from JSON on standard input';

    public function handle(): int
    {
        try {
            $input = json_decode(stream_get_contents($this->input->getStream() ?? STDIN), true, 16, JSON_THROW_ON_ERROR);
            if (!is_string($input['name'] ?? null) || !is_string($input['token'] ?? null)
                || strlen($input['token']) < 32 || strlen($input['name']) > 100) {
                $this->error('Supply a client name and token of at least 32 characters.');
                return self::FAILURE;
            }
            foreach (PartSeeder::PARTS as $index => $label) {
                $id = $index + 1;
                if (Part::find($id)?->libelle !== $label) {
                    $this->error('Canonical part IDs do not match Cabek pricing mappings.');
                    return self::FAILURE;
                }
            }
            ApiClient::updateOrCreate(['name' => $input['name']], [
                'token_hash' => hash('sha256', $input['token']), 'is_active' => true,
            ]);
            $this->info('Cabek credentials configured and canonical IDs verified.');
            return self::SUCCESS;
        } catch (\JsonException $exception) {
            $this->error('Supply JSON credentials on standard input.');
            return self::FAILURE;
        }
    }
}
