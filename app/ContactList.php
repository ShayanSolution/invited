<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Log;
use Illuminate\Database\Eloquent\SoftDeletes;
use Intervention\Image\ImageManagerStatic as Image;


class ContactList extends Model
{
    use SoftDeletes;

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'contactlists';
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'contact_list',
        'list_name',
        'group_image',
    ];

    protected $casts = [
        'contact_list'=>'string',
        'list_name'=> 'string',
    ];

    public function user()
    {
        return $this->belongsTo('App\Models\User', 'user_id');
    }

    private static function cleanPhoneNumber($jsonContactList){
        $contactList = json_decode($jsonContactList);
        foreach($contactList as $key => $contact){
            Log::info("cleaning phone number =>".$contact->phone);
            $contact->phone = preg_replace('/\s+/', '', trim($contact->phone));
//            $contact->phone = preg_replace('/^92|^092|^\+92/', '', trim($contact->phone));
//            $phone = $contact->phone;
//            if($phone[0]!=0){
//                $phone='0'.$phone;
//            }
//            $contact->phone = $phone;
            Log::info("After cleaning phone number =>".$contact->phone);
            $contactList[$key] = $contact;
        }
        return json_encode($contactList);
    }
    
    public static function CreateList($request){
        $request = $request->all();
        $request['contact_list'] = self::cleanPhoneNumber($request['contact_list']);
        return self::create($request)->id;
    }

    public static function CreateContacts($request, $contactListId){
        $request = $request->all();
        $request['contact_list'] = self::cleanPhoneNumber($request['contact_list']);
        $decodedContacts = (array)(json_decode($request['contact_list'])) ;
        foreach ($decodedContacts as $contact){
            $contact = (array)$contact;
            $contact['contact_list_id'] = $contactListId;
            if(key_exists('email', $contact)){
                $contact['name'] = $contact['email'];
                unset($contact['email']);
            }
            Contact::create($contact);
        }
    }

    public static function UpdateList($request){

        $data = [
            "contact_list"=> $request["contact_list"],
            "list_name"=> $request["list_name"],
        ];

        $request = $request->all();
        $request['contact_list'] = self::cleanPhoneNumber($request['contact_list']);

        return self::where('id',$request['list_id'])->update($data);
    }

    public static function updateContact($request){
        Contact::where('contact_list_id', $request['list_id'])->forceDelete();
        $request = $request->all();
        $request['contact_list'] = self::cleanPhoneNumber($request['contact_list']);
        $decodedContacts = (array)(json_decode($request['contact_list'])) ;
        foreach ($decodedContacts as $contact){
            $contact = (array)$contact;
            $contact['contact_list_id'] = $request['list_id'];
            if(key_exists('email', $contact)){
                $contact['name'] = $contact['email'];
                unset($contact['email']);
            }
            Contact::create($contact);
        }
    }

    public static function UpdateListImage($request){
        $data = [];
        if (!empty($request->file("group_image"))) {

            $originalImage= $request->file('group_image');
            $thumbnailImage = Image::make($originalImage);
            $thumbnailPath = storage_path().'/thumbnail/';
            $originalPath = storage_path().'/images/';
            $fileName = time().$originalImage->getClientOriginalName();
            $thumbnailImage->save($originalPath.$fileName);
            $thumbnailImage->resize(150,150);
            $thumbnailImage->save($thumbnailPath.$fileName);
            $path = app("url")->asset("storage/images/");
            $uploadImagePath = app("url")->asset("storage/images/")."/".$fileName;
            $data['group_image'] = $uploadImagePath;

        }

        $request = $request->all();

        return self::where('id',$request['list_id'])->update($data);
    }

    public static function getList($id){
     return  self::where('id',$id)->get();
    }

    public static function getUserList($id){
        return  self::withTrashed()->where('id',$id)->first();
    }

    public static function getUserContactLists($user_id){
        return  self::with(['contact'=>function($q){
            $q->select('name', 'phone', 'contact_list_id');
        }])->where('user_id',$user_id)->orderBy('list_name')->get();
    }

    public static function getUserListCount($user_id){

        $lists = ContactList::where('user_id',$user_id)->get();
        $user_list = [];
        $index = 0;
        foreach ($lists as $list){
            $users = json_decode($list->contact_list);
            foreach ($users as $user){
                $user_list[$index]['phone'] = $user->phone;
                $index++;
            }

        }
        return $user_list;
    }

    public static function deleteList($request){
        $id = $request['list_id'];
        $list = self::find($id);
        self::deleteContact($id);
        if($list){
            return $list->delete();
        }
    }

    public static function deleteContact($id){
        Contact::where('contact_list_id', $id)->delete();
    }

    public static function generateErrorResponse($validator){
        $response = null;
        if ($validator->fails()) {
            $response = $validator->errors()->toArray();
            $response['error'] = $validator->errors()->toArray();
            $response['code'] = 500;
            $response['message'] = 'Error occured';
        }
        else{
            $response['code'] = 200;
            $response['message'] = 'operation completed successfully';
        }
        return $response;
    }

    protected function castAttribute($key, $value)
    {

        if ($this->getCastType($key) == 'array' && is_null($value)) {
            return [];
        }
        if (is_null($value)) {
            return '';
        }

        return parent::castAttribute($key, $value);
    }

    public function contact(){
        return $this->hasMany('App\Contact', 'contact_list_id', 'id');
    }
}
