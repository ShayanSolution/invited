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
        'list_name'
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
    public static function UpdateList($request){

        $data = [
            "contact_list"=> $request["contact_list"],
            "list_name"=> $request["list_name"],
        ];
        if (!empty($request->file("group_image"))) {

            $originalImage= $request->file('group_image');
            $thumbnailImage = Image::make($originalImage);
            $thumbnailPath = storage_path().'/thumbnail/';
            $originalPath = storage_path().'/images/';
            $thumbnailImage->save($originalPath.time().$originalImage->getClientOriginalName());
            $thumbnailImage->resize(150,150);
            $thumbnailImage->save($thumbnailPath.time().$originalImage->getClientOriginalName());
            $path = app("url")->asset("storage/images/");
            $uploadImagePath = app("url")->asset("storage/images/")."/".time().$originalImage->getClientOriginalName();
            $data['group_image'] = $uploadImagePath;

        } else {
            $data['group_image'] = null;
        }
        $request = $request->all();
        $request['contact_list'] = self::cleanPhoneNumber($request['contact_list']);

        return self::where('id',$request['list_id'])->update($data);
    }

    public static function getList($id){
     return  self::where('id',$id)->get();
    }

    public static function getUserList($id){
        return  self::withTrashed()->where('id',$id)->first();
    }

    public static function getUserContactLists($user_id){
        return  self::where('user_id',$user_id)->orderBy('list_name')->get();
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
        if($list){
            return $list->delete();
        }
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
}
