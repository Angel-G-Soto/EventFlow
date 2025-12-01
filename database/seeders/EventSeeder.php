<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Document;
use App\Models\Event;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    /**
     * Seed a baseline set of events with categories.
     */
    public function run(): void
    {
        // If events already exist we don't need to seed demo data.
        if (Event::exists()) {
            return;
        }

        $categories = Category::factory()->count(5)->create();

        $events = Event::factory()
            ->count(15)
            ->create();

        $events->each(function (Event $event) use ($categories) {
            $count = min(3, $categories->count());
            $event->categories()->sync(
                $categories->random(random_int(1, $count))->pluck('id')->all()
            );

            // Attach sample documents to each event
            Document::factory()
                ->count(random_int(1, 3))
                ->state(fn () => ['event_id' => $event->id])
                ->create();
        });
    }
}
