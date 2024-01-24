<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TestAccountsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('accounts')->insert( [
            [
                'username' => 'mikethicke',
                'acct' => 'mikethicke',
                'display_name' => 'Mike Thicke',
                'note' => '<p>Mike is the technical lead for Humanities Commons and a social epistemologist of science.</p>',
                'url' => 'https://kcommons.org/@mikethicke',
            ],
            [
                'username' => 'BatsInLavender',
                'acct' => 'BatsInLavender',
                'display_name' => 'Bonnie (she/her)',
                'note' => '<p>Librarian. Product Manager, Humanities Commons / Project Manager, Mesh Research at Michigan State University. Admin hcommons.social. Loves <a href=\"https://hcommons.social/tags/cats\" class=\"mention hashtag\" rel=\"tag\">#<span>cats</span></a>, <a href=\"https://hcommons.social/tags/bats\" class=\"mention hashtag\" rel=\"tag\">#<span>bats</span></a>, <a href=\"https://hcommons.social/tags/dinosaurs\" class=\"mention hashtag\" rel=\"tag\">#<span>dinosaurs</span></a>, &amp; <a href=\"https://hcommons.social/tags/Godzilla\" class=\"mention hashtag\" rel=\"tag\">#<span>Godzilla</span></a>. <a href=\"https://hcommons.social/tags/Whovian\" class=\"mention hashtag\" rel=\"tag\">#<span>Whovian</span></a>. Knows where her towel is. Currently obsessed with the controversies surrounding <a href=\"https://hcommons.social/tags/AI\" class=\"mention hashtag\" rel=\"tag\">#<span>AI</span></a> and <a href=\"https://hcommons.social/tags/AI\" class=\"mention hashtag\" rel=\"tag\">#<span>AI</span></a>-hype.</p><p>Interested in <a href=\"https://hcommons.social/tags/openaccess\" class=\"mention hashtag\" rel=\"tag\">#<span>openaccess</span></a> <a href=\"https://hcommons.social/tags/publishing\" class=\"mention hashtag\" rel=\"tag\">#<span>publishing</span></a> <a href=\"https://hcommons.social/tags/science\" class=\"mention hashtag\" rel=\"tag\">#<span>science</span></a> <a href=\"https://hcommons.social/tags/medievalism\" class=\"mention hashtag\" rel=\"tag\">#<span>medievalism</span></a> <a href=\"https://hcommons.social/tags/sciencefiction\" class=\"mention hashtag\" rel=\"tag\">#<span>sciencefiction</span></a> <a href=\"https://hcommons.social/tags/folklore\" class=\"mention hashtag\" rel=\"tag\">#<span>folklore</span></a> <a href=\"https://hcommons.social/tags/gothichorror\" class=\"mention hashtag\" rel=\"tag\">#<span>gothichorror</span></a> <a href=\"https://hcommons.social/tags/space\" class=\"mention hashtag\" rel=\"tag\">#<span>space</span></a> <a href=\"https://hcommons.social/tags/astrophysics\" class=\"mention hashtag\" rel=\"tag\">#<span>astrophysics</span></a> <a href=\"https://hcommons.social/tags/metadata\" class=\"mention hashtag\" rel=\"tag\">#<span>metadata</span></a> <a href=\"https://hcommons.social/tags/kaiju\" class=\"mention hashtag\" rel=\"tag\">#<span>kaiju</span></a> <a href=\"https://hcommons.social/tags/knitting\" class=\"mention hashtag\" rel=\"tag\">#<span>knitting</span></a> <a href=\"https://hcommons.social/tags/UX\" class=\"mention hashtag\" rel=\"tag\">#<span>UX</span></a> <a href=\"https://hcommons.social/tags/accessibility\" class=\"mention hashtag\" rel=\"tag\">#<span>accessibility</span></a></p>',
                'url' => 'https://kcommons.org/@BatsInLavender',
            ],
            [
                'username' => 'kfitz',
                'acct' => 'kfitz',
                'display_name' => 'Kathleen Fitzpatrick',
                'note' => '<p>Director, DH@MSU. Director, Mesh Research. Director, Humanities Commons. Seeker of open infrastructure, open governance, and open scholarship.</p>',
                'url' => 'https://kcommons.org/@kfitz',
            ]
        ] );
    }
}
