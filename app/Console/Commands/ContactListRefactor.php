<?php

namespace App\Console\Commands;

use App\Contact;
use App\ContactList;
use Illuminate\Console\Command;

class ContactListRefactor extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:refactor_contact_list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refactor ContactList';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        Contact::truncate();
        $contactList = ContactList::withTrashed()->get();
        $decodedContacts = [];

        foreach ($contactList as $contact)
        {
            $decodedObjArray = (array)(json_decode($contact->contact_list));

            foreach ($decodedObjArray as $item){
                $item->contact_list_id = $contact->id;
                $item->deleted_at = $contact->deleted_at;
                $decodedContacts[] = (array)$item;
            }

        }

        echo json_encode($decodedContacts);
        foreach ($decodedContacts as $contact){

            if(key_exists('email', $contact)){
                $contact['name'] = $contact['email'];
                unset($contact['email']);
            }

            Contact::create($contact);
        }

    }
}
