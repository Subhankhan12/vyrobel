<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\PlatformAdmin;
use App\Support\Platform\PlatformAdminPasswordPolicy;
use App\Support\Platform\PlatformAdminRole;
use App\Support\Platform\PlatformAuditLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * SUPER ADMIN · GATE 0 — the ONLY way to create a platform admin. There is NO public sign-up route: platform
 * admins are provisioned out-of-band by an operator with server access. The command prompts for the password
 * (hidden input) so it is never passed on the command line or stored in shell history; the new admin still has
 * NO 2FA, so their first /admin login forces mandatory enrollment before anything is reachable.
 */
class CreatePlatformAdminCommand extends Command
{
    protected $signature = 'admin:create {--name= : The admin display name} {--email= : The admin login email} {--role=owner : owner | support | billing | analyst}';

    protected $description = 'Create a Vyrobel platform (super) admin — the founder\'s console operator. No public sign-up exists.';

    public function handle(PlatformAuditLogger $audit): int
    {
        // ⚠️ ADMIN 01 · PHASE 1 — every admin is created with an EXPLICIT role; a null role can do nothing.
        //    Owner by default, because this command is how the platform's first admin is made.
        $role = PlatformAdminRole::tryFrom((string) $this->option('role'));
        if ($role === null) {
            $this->error('Unknown role. Use one of: owner, support, billing, analyst.');

            return self::FAILURE;
        }

        $name = (string) ($this->option('name') ?: $this->ask('Full name'));
        $email = Str::lower(trim((string) ($this->option('email') ?: $this->ask('Login email'))));
        $password = (string) $this->secret('Password (min 12 chars, mixed case, a number and a symbol)');
        $confirm = (string) $this->secret('Confirm password');

        $validator = Validator::make(
            ['name' => $name, 'email' => $email, 'password' => $password, 'password_confirmation' => $confirm],
            [
                'name' => ['required', 'string', 'max:120'],
                'email' => ['required', 'email', 'max:190', 'unique:platform_admins,email'],
                'password' => ['required', 'confirmed', PlatformAdminPasswordPolicy::rule()],
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
            'role' => $role->value,
        ]);

        $audit->record('platform.admin_created', ['email' => $email, 'role' => $role->value], actor: $admin);

        $this->newLine();
        $this->info("Platform admin created: {$email}");
        $this->line('They must enrol in 2FA on first sign-in at /admin/login before the console unlocks.');

        return self::SUCCESS;
    }
}
