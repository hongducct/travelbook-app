<?php

   namespace App\Console\Commands;

   use App\Models\ChatConversation;
   use Illuminate\Console\Command;
   use Illuminate\Support\Str;

   class FixConversationMetadata extends Command
   {
       protected $signature = 'fix:conversation-metadata';
       protected $description = 'Fix conversations with null or invalid metadata';

       public function handle()
       {
           $conversations = ChatConversation::whereNull('metadata')
               ->orWhere('metadata', '')
               ->orWhere('metadata', '[]')
               ->get();

           $this->info("Found {$conversations->count()} conversations to fix.");

           foreach ($conversations as $conversation) {
               $metadata = json_decode($conversation->metadata, true) ?? [];
               if (empty($metadata['temp_user_id']) && !$conversation->user_id) {
                   $metadata['temp_user_id'] = 'unknown-' . Str::random(8);
                   $conversation->metadata = json_encode($metadata);
                   $conversation->save();
                   $this->info("Fixed conversation ID {$conversation->id} with temp_user_id: {$metadata['temp_user_id']}");
               }
           }

           $this->info('Metadata fix completed.');
       }
   }