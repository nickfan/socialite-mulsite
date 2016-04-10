<?php
namespace SocialiteProviders\Mulsite;

use Illuminate\Http\Request;
use GuzzleHttp\ClientInterface;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\ProviderInterface;
use Laravel\Socialite\Two\User;

class Provider extends AbstractProvider implements ProviderInterface
{
    protected $stateless = false;
    
    /**
     * {@inheritdoc}
     */
    protected $scopes = [];

    /**
     * The options.
     *
     * @var array
     */
    protected $option = [
        'endpoint'=>'http://mul.axiong.me',
        'postfixAuthorize'            => '/oauth/authorize',
        'postfixAccessToken'          => '/oauth/access_token',
        'postfixResourceOwnerDetails' => '/oapi/v1/resource'
    ];

    public function setOption($key='',$value=null)
    {
        if(is_array($key)){
            $this->option = array_merge($this->option,$key);
        }else{
            $this->option[$key] = $value;
        }
    }
    
    /**
     * @param string $key
     * @param null   $default
     *
     * @return mixed
     */
    public function getOption($key = '', $default = null)
    {
        return array_get($this->option, $key, $default);
    }


    /**
     * Create a new provider instance.
     *
     * @param  Request  $request
     * @param  string  $clientId
     * @param  string  $clientSecret
     * @param  string  $redirectUrl
     * @return void
     */
    public function __construct(Request $request, $clientId, $clientSecret, $redirectUrl)
    {
        parent::__construct($request,$clientId,$clientSecret,$redirectUrl);
        $config = config('services.mulsite',[]);
        if(!empty($config)){
            $this->setOption($config);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase($this->getOption('endpoint', 'http://mul.axiong.me').$this->getOption('postfixAuthorize', '/oauth/authorize'), $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return $this->getOption('endpoint', 'http://mul.axiong.me').$this->getOption('postfixAccessToken', '/oauth/access_token');
    }

    /**
     * Get the access token Body By Code
     *
     * @param  string  $code
     * @return string
     */
    public function getAccessTokenBodyByCode($code)
    {
        $postKey = (version_compare(ClientInterface::VERSION, '6') === 1) ? 'form_params' : 'body';
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            'headers' => ['Accept' => 'application/json'],
            $postKey => $this->getTokenFields($code),
        ]);
        return json_decode($response->getBody(), true);
    }

    /**
     * Get the access token Body By Refresh Token
     *
     * @param  string  $code
     * @return string
     */
    public function getAccessTokenBodyByRefreshToken($refresh_token)
    {
        $postKey = (version_compare(ClientInterface::VERSION, '6') === 1) ? 'form_params' : 'body';
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            'headers' => ['Accept' => 'application/json'],
            $postKey => $this->getTokenFieldsByRefreshToken($refresh_token),
        ]);
        return json_decode($response->getBody(), true);
    }

    /**
     * Get the access token Body
     *
     * @param  string  $code
     * @return string
     */
    public function getAccessTokenBody()
    {
        return $this->getAccessTokenBodyByCode($this->getCode());
    }

    /**
     * Get the access token for the given code.
     *
     * @param  string  $code
     * @return string
     */
    public function getAccessToken($code)
    {
        $postKey = (version_compare(ClientInterface::VERSION, '6') === 1) ? 'form_params' : 'body';

        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            'headers' => ['Accept' => 'application/json'],
            $postKey => $this->getTokenFields($code),
        ]);

        return $this->parseAccessToken($response->getBody());
    }


    /**
     * Get the access token for the given code.
     *
     * @param  string  $code
     * @return string
     */
    public function getAccessTokenByRefreshToken($refresh_token)
    {
        $postKey = (version_compare(ClientInterface::VERSION, '6') === 1) ? 'form_params' : 'body';

        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            'headers' => ['Accept' => 'application/json'],
            $postKey => $this->getTokenFieldsByRefreshToken($refresh_token),
        ]);

        return $this->parseAccessToken($response->getBody());
    }

    /**
     * Get the POST fields for the token request.
     *
     * @param  string  $code
     * @return array
     */
    protected function getTokenFieldsByRefreshToken($refresh_token)
    {
        return [
            'client_id' => $this->clientId, 'client_secret' => $this->clientSecret,
            'grant_type' => 'refresh_token',
            'refresh_token' => $refresh_token, 'redirect_uri' => $this->redirectUrl,
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get($this->getOption('endpoint', 'http://mul.axiong.me').$this->getOption('postfixResourceOwnerDetails', '/oapi/v1/resource'), [
            //'query' => ['access_token' => $token],
            'headers' => [
                'Authorization' => 'Bearer '.$token,
            ],
        ]);

        return json_decode($response->getBody(), true);
    }
    
    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id'       => $user['id'],
            'nickname' => array_get($user, 'nickname'),
            'email' => array_get($user, 'email'),
            'name' => array_get($user, 'name'),
            'avatar' => array_get($user, 'avatar'),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        return array_merge(parent::getTokenFields($code), [
            'grant_type' => 'authorization_code'
        ]);
    }
}
