<?php

namespace IlBronza\Contacts\Console\Commands;

use IlBronza\Contacts\Helpers\ContactEmailConfirmationHelper;
use IlBronza\Contacts\Jobs\SendContactEmailVerification;
use IlBronza\Contacts\Models\Contact;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Throwable;

class SendEmailVerifications extends Command
{
    protected $signature = 'contacts:send-email-verifications
        {contact? : ID di un singolo contatto}
        {--limit=10 : Numero massimo di contatti da elaborare (1-25)}
        {--queue : Invia tramite la coda Laravel dell\'applicazione}
        {--connection= : Connessione queue (richiede --queue)}
        {--queue-name= : Nome della coda (richiede --queue)}';

    protected $description = 'Invia piccoli gruppi di email di conferma ai contatti non verificati';

    public function handle(ContactEmailConfirmationHelper $helper): int
    {
        $limit = filter_var($this->option('limit'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 25]]);

        if ($limit === false) {
            $this->error('--limit deve essere un intero tra 1 e 25.');

            return self::INVALID;
        }

        $queued = (bool) $this->option('queue');
        if (! $queued && ($this->option('connection') !== null || $this->option('queue-name') !== null)) {
            $this->error('--connection e --queue-name richiedono --queue.');

            return self::INVALID;
        }

        if ($queued) {
            $connection = $this->option('connection') ?? config('queue.default');
            $driver = config('queue.connections.' . $connection . '.driver');
            if (! $driver || $driver === 'null') {
                $this->error('Configurare una connessione queue valida nell\'applicazione.');

                return self::INVALID;
            }
            if ($driver === 'sync')
                $this->warn('La connessione queue usa sync: i job vengono eseguiti subito.');
        }

        $class = Contact::getProjectClassName();
        $query = $class::query();
        if (($id = $this->argument('contact')) !== null) {
            $query->whereKey($id);
            if (! (clone $query)->exists()) {
                $this->error('Contatto non trovato.');

                return self::FAILURE;
            }
        }

        $types = ['email'];
        foreach (config('contacts.contactTypes', []) as $type => $settings) {
            if (($settings['normalizer'] ?? null) === 'email')
                $types[] = $type;
        }

        $query->whereIn('contacttype_slug', array_unique($types))
            ->where('existence_verified', false)
            ->where(function ($query) {
                $query->whereNull('email_verification_token_hash')
                    ->orWhere('email_verification_expires_at', '<', now()->startOfSecond());
            });

        $attempted = $processed = $skipped = $failed = 0;
        foreach ($query->lazyById(100) as $contact) {
            if (! $helper->isEmailContact($contact)) {
                $skipped++;
                continue;
            }

            $attempted++;
            try {
                if ($queued) {
                    $job = (new SendContactEmailVerification($contact))
                        ->onConnection($this->option('connection'))
                        ->onQueue($this->option('queue-name'));
                    Bus::dispatch($job);
                    $processed++;
                } elseif ($helper->sendIfNeeded($contact)) {
                    $processed++;
                } else {
                    $skipped++;
                }
            } catch (Throwable $exception) {
                $this->error('Invio o accodamento fallito per il contatto ' . $contact->getKey() . '.');
                $this->line(OutputFormatter::escape($exception::class . ': ' . $exception->getMessage()));

                try {
                    Log::error('Contacts: invio o accodamento email di verifica fallito.', [
                        'contact_id' => $contact->getKey(),
                        'operation' => $queued ? 'queue' : 'send',
                        'exception' => $exception,
                    ]);
                } catch (Throwable $loggingException) {
                    $this->warn('Impossibile registrare l\'errore nei log dell\'applicazione.');
                    $this->line(OutputFormatter::escape($loggingException::class . ': ' . $loggingException->getMessage()));
                }

                $failed++;
            }

            if ($attempted >= $limit)
                break;
        }

        $this->info(($queued ? 'Job accodati: ' : 'Email inviate: ') . $processed . '. Saltati: ' . $skipped . '. Errori: ' . $failed . '.');

        if ($failed > 0)
            $this->line(OutputFormatter::escape('Canale log: ' . config('logging.default') . '. Configurazione: ' . config_path('logging.php')), null, 'v');

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
