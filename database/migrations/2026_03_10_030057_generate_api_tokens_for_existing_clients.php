<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Client;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // Jader api_token faka ache, tader jonno 40 character er secure token toiri korbe
        $clients = Client::whereNull('api_token')->orWhere('api_token', '')->get();
        
        foreach ($clients as $client) {
            $client->api_token = Str::random(40);
            $client->save();
        }
    }

    public function down(): void
    {
        //
    }
};