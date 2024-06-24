<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
Use CodeIgniter\Shield\Models\UserModel;
Use CodeIgniter\Shield\Entities\User;
use Google\Client;
use Google_Service_Oauth2;


class AuthController extends ResourceController
{
  public function googleLogin()
    {
        require_once APPPATH. 'Libraries/vendor/autoload.php';
        
        //$client = new Google_Client();
        $client = new Client();
        $client->setClientId(getenv('google.client_id'));
        $client->setClientSecret(getenv('google.client_secret'));
        $client->setRedirectUri(getenv('google.redirect_uri'));
        $client->addScope("email");
        $client->addScope("profile");

        $authUrl = $client->createAuthUrl();

        return redirect()->to($authUrl);
        // $response = [
        //     "status"    => true,
        //     "message"   => lang('App.login_success'),
        //     "data"      => [
        //         'email' => $authUrl
        //     ]
        // ];
        // return $this->respondCreated($response);

    }

    public function googleCallback()
    {
        $response = [];
        require_once APPPATH. 'Libraries/vendor/autoload.php';

        //$client = new Google_Client();
        $client = new Client();
        $client->setClientId(getenv('google.client_id'));
        $client->setClientSecret(getenv('google.client_secret'));
        $client->setRedirectUri(getenv('google.redirect_uri'));

        $url =  isset($_SERVER['HTTPS']) && 
        $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";   
        $url .= $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];     
        $parse_url = parse_url($url);
        $uri = new \CodeIgniter\HTTP\URI($url);

        $googleString =  $uri->getQuery(['only' => ['code']]);
        parse_str($googleString, $googleData);

        $googleCode = $googleData['code'];
        if (isset($googleCode) && !empty($googleCode)) {
            //$token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
            $token = $client->fetchAccessTokenWithAuthCode($googleCode);
            $client->setAccessToken($token);

            $oauth2 = new Google_Service_Oauth2($client);
            $google_account_info = $oauth2->userinfo->get();

            if($google_account_info){
                $email = $google_account_info->email;
                $first_name = $google_account_info->givenName;
                $last_name = $google_account_info->familyName;
                $oauth_id = $google_account_info->id;
                $picture = $google_account_info->picture;

                $users = auth()->getProvider();
                $email_exist = $users->findByCredentials(['email' => $email]);

                if($email_exist){
                    $response = [
                        "status"    => false,
                        "message"   => lang('App.email_alreadyExist'),
                        "data"      => [
                            "user_data" => $email_exist,
                        ]
                    ];
                } else {

                    $userEntity = [
                        "username"      => $email,
                        "email"         => $email,
                        "first_name"    => $first_name,
                        "last_name"     => $last_name,
                        "role"          => 4,               // player 
                        "oauth_id"      => $oauth_id,       // player 
                        "lang"          => 1,               // english
                        "newsletter"    => 0,
                        "user_domain"   => 5,               // socceryou.co.uk
                    ];
        
                    // Get the User Provider (UserModel by default)
                    $users = auth()->getProvider();
        
                    $user_data = new User($userEntity);
                    $users->save($user_data);

                    // Here, you can handle the user information as needed.
                    // For example, you can create or update a user record in your database.
                    // return $this->response->setJSON([
                    //     'email' => $email,
                    //     'name' => $name
                    // ]);

                    $response = [
                        "status"    => true,
                        "message"   => lang('App.login_success'),
                        "data"      => [
                            "user_data" => $google_account_info,
                        ]
                    ];
                }
            }
            
        } else {
            //return redirect()->to('/login');

            $response = [
                "status"    => true,
                "message"   => lang('App.login_invalidCredentials'),
                "data"      => []
            ];
        }

        return $this->respondCreated($response);
    }
}
