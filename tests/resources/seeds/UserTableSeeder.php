<?php

namespace Tests\Seeds;

use Tests\Models\Profile;
use Tests\Models\Tag;
use Tests\Models\User;
use Illuminate\Database\Seeder;

class UserTableSeeder extends Seeder
{
    public function run()
    {
        factory(User::class, 50)
            ->create()
            ->each(function ($u) {
                $u->profile()->save(factory(Profile::class)->make());
                $u->tags()->saveMany(factory(Tag::class, 5)->make());
            });
    }
}
