<?php

namespace Einenlum\CashierPaddleWebhooks\Commands;

use Einenlum\CashierPaddleWebhooks\Exceptions\CashierPaddleWebhooksException;
use Einenlum\CashierPaddleWebhooks\Facades\CashierPaddleWebhooks;
use Illuminate\Console\Command;
use Illuminate\Process\InvokedProcess;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Validator;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\note;
use function Laravel\Prompts\select;

/**
 * This command and this whole package are HEAVILY inspired by
 * https://github.com/lmsqueezy/laravel
 *
 * Thanks to them!
 */
class ListenPaddleWebhooksCommand extends Command
{
    public $signature = 'cashier-paddle-webhooks:listen
                        {--port=8000 : The port on your machine to tunnel to the internet}';

    public $description = 'Listens to Paddle webhooks via tunnelmole.';

    // We keep an arry of services here in case we want to add more in the future.
    protected array $services = [
        'tunnelmole' => [
            'domain' => 'tunnelmole.net',
        ],
    ];

    /**
     * The currently invoked process instance.
     */
    protected InvokedProcess $process;

    /**
     * The currently in-use Paddle webhook ID.
     */
    protected ?string $webhookId = null;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! config('cashier.sandbox')) {
            throw new \Exception('You cannot use cashier-paddle-webhooks:listen in production mode.');
        }

        if (windows_os()) {
            error('paddle:listen is not supported on Windows because it lacks support for signal handling.');

            return static::FAILURE;
        }

        $this->validateArguments();

        $errorCode = $this->handleEnvironment();

        if ($errorCode !== null) {
            return $errorCode;
        }

        $errorCode = $this->handleCleanup();

        if ($errorCode !== null) {
            return $errorCode;
        }

        return $this->handleService();
    }

    protected function validateArguments(): void
    {
        Validator::make($this->arguments() + config('cashier'), [
            'api_key' => [
                'required',
            ],
            'service' => [
                'required',
                'string',
                'in:tunnelmole,test',
            ],
        ], [
            'api_key.required' => 'The PADDLE_API_KEY environment variable is required.',
        ])->validate();
    }

    protected function handleEnvironment(): ?int
    {
        if ($this->argument('service') === 'test') {
            info('cashier-paddle-webhooks:listen is using the test service.');

            return static::SUCCESS;
        }

        if (! App::environment('local')) {
            error('paddle:listen can only be used in local environment.');

            return static::FAILURE;
        }

        return null;
    }

    protected function handleService(): int
    {
        note('Setting up webhooks domain with '.$this->argument('service').'...');

        $this->trap([SIGINT], fn (int $signal) => $this->teardownWebhook());

        return $this->{$this->argument('service')}();
    }

    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'service' => fn () => select(
                label: 'Please choose a service',
                options: [
                    'tunnelmole',
                ],
                default: 'tunnelmole',
                validate: fn ($val) => in_array($val, ['tunnelmole'])
                    ? null
                    : 'Please choose a valid service.',
            ),
        ];
    }

    protected function cleanOutput($output): string
    {
        if (preg_match(
            '/Remaining time:\s+\d{2}:\d{2}:\d{2}\\n/',
            $output,
            $matches,
        )) {
            $output = preg_replace('/Remaining time:\s+\d{2}:\d{2}:\d{2}\\n/', '', $output);
        }

        if ($output) {
            $lines = explode("\n", $output);
            $cleaned_lines = [];

            foreach ($lines as $line) {
                // Trim leading and trailing whitespace
                $line = trim($line);
                // Replace multiple spaces with a single space
                $line = preg_replace('/\s+/', ' ', $line);

                if (! empty($line)) {
                    $cleaned_lines[] = $line;
                }
            }
            // Join cleaned lines back into a single string
            $output = implode("\n", $cleaned_lines);
        }

        return $output;
    }

    protected function process(array $commands): InvokedProcess
    {
        return $this->process = Process::timeout(120)
            ->start($commands, function (string $type, string $output) {
                if (isset($this->webhookId) || $this->option('verbose')) {
                    $output = $this->cleanOutput($output);
                    if ($output) {
                        note($output);
                    }
                }
            });
    }

    protected function tunnelmole(): int
    {
        $tunnel = null;

        $this->process([
            'tmole',
            $this->option('port'),
        ]);

        $regex = '/https:\/\/[^\s]+\.tunnelmole\.net/';

        while ($this->process->running()) {

            if ($tunnel !== null) {
                sleep(1);

                continue;
            }

            $latestOutput = $this->process->latestOutput();

            if (preg_match($regex, $latestOutput, $matches)) {
                $tunnel = $matches[0];

                $errorCode = $this->setupWebhook($tunnel);

                if ($errorCode !== null) {
                    return $errorCode;
                }
            }

            sleep(1);
        }

        return static::SUCCESS;
    }

    protected function setupWebhook(string $tunnel): ?int
    {
        note("Found webhook endpoint: {$tunnel}");
        note('Sending webhook to Paddle...');

        try {
            $paddleWebhook = CashierPaddleWebhooks::setupWebhook($tunnel, $this->argument('service'));
        } catch (CashierPaddleWebhooksException $e) {
            error($e->getMessage());

            return static::FAILURE;
        }

        $this->webhookId = $paddleWebhook->id;
        CashierPaddleWebhooks::setSecret($paddleWebhook->secret);

        info('✅ Webhook setup successfully.');
        note('Listening for webhooks...');

        return null;
    }

    protected function teardownWebhook(): void
    {
        if (! isset($this->webhookId)) {
            return;
        }

        note("\nCleaning up webhook on Paddle...");

        if (CashierPaddleWebhooks::deleteWebhook($this->webhookId)->status() !== 204) {
            error("Failed to remove webhook, use --cleanup to remove all {$this->argument('service')} domains");

            return;
        }

        $this->webhookId = null;
        CashierPaddleWebhooks::forgetSecret();

        info('✅ Webhook removed successfully.');
    }
}
