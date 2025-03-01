<?php

namespace App\Modules\Auth\Services;


use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;


class SocialiteService {
    public static function loginOrRegister($driver, $payload){
        $user = User::where('email', $payload->email)->first();

        if(!$payload->email){
            return redirect('/login');
        }

        if(!$user){

            $user = new User();
            $user->name = $payload->name;
            $user->email = $payload->email;
            $user->password = Hash::make(Str::password(16));
            $user->email_verified_at = Carbon::now()->format('Y-m-d H:i:s');
            $user->google_id = $payload->id;
            $user->save();

        }

        if($user) {
            self::updateAvatarFromSocial($user, $payload);
        }

        auth()->login($user, true);

        return redirect()->to('/');
    }

    public static function updateAvatarFromSocial($user, $payload)
    {
        $filename = 'avatar.jpg';
        $url = $payload->getAvatar();
        if ($url) {
            $url = str_replace('type=normal','type=large', $url);
            $contents = file_get_contents($url);
            Storage::put('public/users/'.$user->id.'/photos/'.$filename, $contents);

            $user->avatar = $filename;
            $user->save();

        }


    }

}
