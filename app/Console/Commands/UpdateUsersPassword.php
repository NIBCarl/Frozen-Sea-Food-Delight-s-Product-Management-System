<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UpdateUsersPassword extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:update-passwords';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update all test user passwords to password123';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Updating user passwords...');

        $emails = [
            'admin@seafood.com',
            'supplier@seafood.com',
            'customer@seafood.com',
            'customer2@example.com',
            'delivery@seafood.com',
        ];

        $newPassword = 'password123';

        foreach ($emails as $email) {
            $user = User::where('email', $email)->first();
            
            if ($user) {
                $user->password = Hash::make($newPassword);
                $user->save();
                $this->info("✓ Updated password for: {$email}");
            } else {
                $this->warn("✗ User not found: {$email}");
            }
        }

        $this->info('');
        $this->info('All passwords updated to: password123');
        $this->info('You can now login with any of these accounts using password123');

        return Command::SUCCESS;
    }
}
