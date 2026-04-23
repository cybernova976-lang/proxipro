<?php

namespace App\Console\Commands;

use App\Mail\AdminTestMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestMailCommand extends Command
{
    protected $signature = 'mail:test {email?}';
    protected $description = 'Test email sending configuration';

    public function handle()
    {
        $this->info('=== Mail Configuration ===');
        $this->table(['Setting', 'Value'], [
            ['MAIL_MAILER', config('mail.default')],
            ['MAIL_HOST', config('mail.mailers.smtp.host')],
            ['MAIL_PORT', config('mail.mailers.smtp.port')],
            ['MAIL_USERNAME', config('mail.mailers.smtp.username') ? '✅ Set' : '❌ MISSING'],
            ['MAIL_PASSWORD', config('mail.mailers.smtp.password') ? '✅ Set' : '❌ MISSING'],
            ['MAIL_SCHEME', config('mail.mailers.smtp.scheme') ?: '(null)'],
            ['MAIL_FROM_ADDRESS', config('mail.from.address')],
            ['MAIL_FROM_NAME', config('mail.from.name')],
            ['MAIL_REPLY_TO_ADDRESS', config('mail.reply_to.address')],
            ['MAIL_REPLY_TO_NAME', config('mail.reply_to.name')],
            ['MAIL_ADMIN_ADDRESS', config('mail.admin_email')],
            ['QUEUE_CONNECTION', config('queue.default')],
        ]);

        // Check common issues
        $fromAddress = config('mail.from.address');
        if ($fromAddress === 'hello@example.com' || empty($fromAddress)) {
            $this->error('⚠️  MAIL_FROM_ADDRESS is not set or defaults to hello@example.com!');
            $this->error('   Brevo will REJECT emails from unverified senders.');
            $this->error('   Set MAIL_FROM_ADDRESS to a verified sender email in Railway.');
        }

        if (!config('mail.mailers.smtp.password')) {
            $this->error('⚠️  MAIL_PASSWORD is not set! Emails cannot be sent.');
            return 1;
        }

        $email = $this->argument('email') ?? config('mail.from.address');
        $this->info("\nSending test email to: {$email}");

        try {
            Mail::to($email)->send(new AdminTestMail([
                'mailer' => config('mail.default'),
                'from_address' => config('mail.from.address'),
                'from_name' => config('mail.from.name'),
                'reply_to_address' => config('mail.reply_to.address'),
                'reply_to_name' => config('mail.reply_to.name'),
                'admin_email' => config('mail.admin_email'),
                'environment' => app()->environment(),
                'sent_at' => now()->format('d/m/Y H:i:s'),
            ]));
            $this->info('✅ Email sent successfully!');
            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Email FAILED: ' . $e->getMessage());
            $this->error('Exception: ' . get_class($e));
            
            if (str_contains($e->getMessage(), 'Unauthorized') || str_contains($e->getMessage(), '401')) {
                $this->warn('→ The MAIL_PASSWORD (SMTP key) may be wrong.');
            }
            if (str_contains($e->getMessage(), 'sender') || str_contains($e->getMessage(), 'from')) {
                $this->warn('→ The MAIL_FROM_ADDRESS may not be verified in Brevo.');
            }
            if (str_contains($e->getMessage(), 'Connection') || str_contains($e->getMessage(), 'timeout')) {
                $this->warn('→ Cannot connect to SMTP server. Check MAIL_HOST and MAIL_PORT.');
            }
            
            return 1;
        }
    }
}
