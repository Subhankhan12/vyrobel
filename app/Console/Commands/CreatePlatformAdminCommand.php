<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\PlatformAdmin;
use App\Support\Platform\PlatformAuditLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

/**
 * SUPER ADMIN · GATE 0 — the ONLY way to create a platform admin. There is NO public sign-up route: platform
 * admins are provisioned out-of-band by an operator with server access. The command prompts for the password
 * (hidden input) so it is never passed on the command line or stored in shell history; the new admin still has
 * NO 2FA, so their first /admin login forces mandatory enrollment before anything is reachable.
 */
class CreatePlatformAdminCommand extends Command
{
    protected $signature = 'admin:create {--name= : The admin display name} {--email= : The admin login email}';

    protected $description = 'Create a Vyrobel platform (super) admin — the founder\'s console operator. No public sign-up exists.';

    public function handle(PlatformAuditLogger $audit): int
    {
        $name = (string) ($this->option('name') ?: $this->ask('Full name'));
        $email = Str::lower(trim((string) ($this->option('email') ?: $this->ask('Login email'))));
        $password = (string) $this->secret('Password (min 12 chars, mixed case + a number)');
        $confirm = (string) $this->secret('Confirm password');

        $validator = Validator::make(
            ['name' => $name, 'email' => $email, 'password' => $password, 'password_confirmation' => $confirm],
            [
                'name' => ['required', 'string', 'max:120'],
                'email' => ['required', 'email', 'max:190', 'unique:platform_admins,email'],
                'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()],
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $admin = PlatformAdmin::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'status' => 'active',
        ]);

        $audit->record('platform.admin_created', ['email' => $email], actor: $admin);

        $this->newLine();
        $this->info("Platform admin created: {$email}");
        $this->line('They must enrol in 2FA on first sign-in at /admin/login before the console unlocks.');

        return self::SUCCESS;
    }
}
