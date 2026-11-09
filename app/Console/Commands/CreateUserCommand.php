<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateUserCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:create
                            {--name= : Nama lengkap pengguna}
                            {--email= : Alamat email pengguna}
                            {--role= : Peran pengguna (admin, operator, viewer)}
                            {--password= : Kata sandi (minimal 8 karakter)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Buat akun pengguna baru dengan password ter-hash secara interaktif';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->components->info('Pembuatan Akun Pengguna Solar Tracker');

        // 1. Nama
        $name = (string) ($this->option('name') ?: $this->ask('Nama lengkap'));
        $name = trim($name);

        if ($name === '') {
            $this->components->error('Nama lengkap tidak boleh kosong.');

            return Command::FAILURE;
        }

        // 2. Email
        $email = (string) ($this->option('email') ?: $this->ask('Alamat email'));
        $email = strtolower(trim($email));

        $emailValidator = Validator::make(
            ['email' => $email],
            ['email' => ['required', 'string', 'email', 'unique:users,email']]
        );

        if ($emailValidator->fails()) {
            foreach ($emailValidator->errors()->all() as $error) {
                $this->components->error($error);
            }

            return Command::FAILURE;
        }

        // 3. Peran (Role)
        $role = $this->option('role');
        $validRoles = ['admin', 'operator', 'viewer'];

        if (! in_array($role, $validRoles, true)) {
            $role = $this->choice(
                'Pilih peran pengguna',
                $validRoles,
                0
            );
        }

        // 4. Password (input tersembunyi + konfirmasi)
        $password = (string) $this->option('password');

        if ($password === '') {
            $password = (string) $this->secret('Kata sandi (minimal 8 karakter)');
            $confirmation = (string) $this->secret('Konfirmasi kata sandi');

            if ($password !== $confirmation) {
                $this->components->error('Konfirmasi kata sandi tidak cocok.');

                return Command::FAILURE;
            }
        }

        if (strlen($password) < 8) {
            $this->components->error('Kata sandi harus minimal 8 karakter.');

            return Command::FAILURE;
        }

        // 5. Simpan ke database dengan password ter-hash
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'role' => $role,
            'password' => Hash::make($password),
            'is_active' => true,
            'force_password_change' => false,
        ]);

        $this->components->info("Pengguna {$user->name} ({$user->email}) dengan peran [{$role}] berhasil dibuat.");

        return Command::SUCCESS;
    }
}

