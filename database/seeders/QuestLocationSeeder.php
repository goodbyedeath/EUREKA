<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\QuestLocation;
use App\Models\User;

class QuestLocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the first admin user to assign as creator
        $adminUser = User::where('role', 'admin')->first();
        
        if (!$adminUser) {
            $this->command->warn('No admin user found. Creating locations without creator.');
        }

        $locations = [
            [
                'name' => 'Monumen Nasional (Monas)',
                'description' => 'Monumen kemerdekaan Indonesia yang terletak di pusat Jakarta.',
                'what_to_do' => 'Ambil foto di depan Monas dan scan QR code yang tersedia di area taman.',
                'google_map_embed_url' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3966.666!2d106.8271!3d-6.1754!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e69f5d2e764b12d%3A0x3d2ad6e1e0e9bcc8!2sMonas!5e0!3m2!1sen!2sid!4v1623456789012!5m2!1sen!2sid',
                'latitude' => -6.1754,
                'longitude' => 106.8271,
                'radius' => 50,
                'quest_points' => 10,
                'max_check_ins_per_user' => 1,
                'is_active' => true,
                'created_by' => $adminUser ? $adminUser->id : null,
            ],
            [
                'name' => 'Kota Tua Jakarta',
                'description' => 'Kawasan bersejarah Jakarta dengan arsitektur kolonial Belanda.',
                'what_to_do' => 'Kunjungi Museum Fatahillah dan scan QR code di dalam museum.',
                'google_map_embed_url' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3966.8!2d106.8133!3d-6.1344!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e69f5c2e8c7b123%3A0x4d3e2f1a2b3c4d5e!2sKota%20Tua!5e0!3m2!1sen!2sid!4v1623456789012!5m2!1sen!2sid',
                'latitude' => -6.1344,
                'longitude' => 106.8133,
                'radius' => 75,
                'quest_points' => 15,
                'max_check_ins_per_user' => 1,
                'is_active' => true,
                'created_by' => $adminUser ? $adminUser->id : null,
            ],
            [
                'name' => 'Ancol Dreamland',
                'description' => 'Taman rekreasi terbesar di Jakarta dengan berbagai wahana menarik.',
                'what_to_do' => 'Masuk ke area Pantai Ancol dan scan QR code di pintu masuk utama.',
                'google_map_embed_url' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3966.2!2d106.8420!3d-6.1233!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e69f5e3f1a2b3c4%3A0x5d6e7f8a9b0c1d2e!2sAncol!5e0!3m2!1sen!2sid!4v1623456789012!5m2!1sen!2sid',
                'latitude' => -6.1233,
                'longitude' => 106.8420,
                'radius' => 100,
                'quest_points' => 20,
                'max_check_ins_per_user' => 1,
                'is_active' => true,
                'created_by' => $adminUser ? $adminUser->id : null,
            ],
            [
                'name' => 'Grand Indonesia Mall',
                'description' => 'Pusat perbelanjaan modern di jantung Jakarta.',
                'what_to_do' => 'Pergi ke food court lantai 3 dan scan QR code di meja informasi.',
                'google_map_embed_url' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3966.5!2d106.8209!3d-6.1944!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e69f4389c8d1234%3A0x6e7f8a9b0c1d2e3f!2sGrand%20Indonesia!5e0!3m2!1sen!2sid!4v1623456789012!5m2!1sen!2sid',
                'latitude' => -6.1944,
                'longitude' => 106.8209,
                'radius' => 30,
                'quest_points' => 12,
                'max_check_ins_per_user' => 1,
                'is_active' => true,
                'created_by' => $adminUser ? $adminUser->id : null,
            ]
        ];

        // Insert all locations
        foreach ($locations as $location) {
            QuestLocation::create($location);
        }
        
        $this->command->info('Quest locations seeded successfully!');
    }
}
