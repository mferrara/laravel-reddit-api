<?php

namespace CodeWizz\RedditAPI;

use CodeWizz\RedditAPI\Exceptions\RedditAuthenticationException;

class RedditOAuth2
{
    private $access_token;
    private $token_type;
    private $expiration;
    private $scope;
    public $username;
    private $password;
    private $app_id;
    private $app_secret;
    private $user_agent;
    private $endpoint;
    private $cache_service;
    private $cache_key;

    public function __construct($username, $password, $app_id, $app_secret, $user_agent, $endpoint, $cache_auth_token = true, $cache_driver = 'file')
    {
        $this->username = $username;
        $this->password = $password;
        $this->app_id = $app_id;
        $this->app_secret = $app_secret;
        $this->user_agent = $user_agent;
        $this->endpoint = $endpoint;
        $this->cache_key = 'reddit_access_token_'.$this->app_id.time();
        $this->cache_service = null;
        if($cache_auth_token)
            $this->cache_service = \Cache::driver($cache_driver);

        // We're already making a call to getAccessToken on every apiCall, so we don't need to do it here
        //$this->requestAccessToken();
    }

    public function getAccessToken()
    {
        // Try to get token from the cache first
        if($this->cache_service){
            $cached_token = $this->cache_service->get($this->cache_key);
            if ($cached_token) {
                $this->access_token = $cached_token['access_token'];
                $this->token_type = $cached_token['token_type'];
                $this->expiration = $cached_token['expiration'];
                $this->scope = $cached_token['scope'];

                return [
                    'access_token' => $this->access_token,
                    'token_type' => $this->token_type,
                ];
            }
        }

        if (!(isset($this->access_token) && isset($this->token_type) && time() < $this->expiration)) {
            $this->requestAccessToken();
        }

        return array(
            'access_token' => $this->access_token,
            'token_type' => $this->token_type
        );
    }

    private function requestAccessToken()
    {
        $url = "{$this->endpoint}/api/v1/access_token";
        $params = array(
            'grant_type' => 'password',
            'username' => $this->username,
            'password' => $this->password
        );
        $options[CURLOPT_USERAGENT] = $this->user_agent;
        $options[CURLOPT_USERPWD] = $this->app_id . ':' . $this->app_secret;
        $options[CURLOPT_RETURNTRANSFER] = true;
        $options[CURLOPT_CONNECTTIMEOUT] = 5;
        $options[CURLOPT_TIMEOUT] = 10;
        $options[CURLOPT_CUSTOMREQUEST] = 'POST';
        $options[CURLOPT_POSTFIELDS] = $params;

        $response = null;
        $got_token = false;
        $max_attempts = 3;
        $attempts = 1;
        while (!$got_token) {
            $ch = curl_init($url);
            curl_setopt_array($ch, $options);
            $response_raw = curl_exec($ch);
            $response = json_decode($response_raw);
            curl_close($ch);
            if (isset($response->access_token)) {
                $got_token = true;
            } else {
                if (isset($response->error)) {
                    if ($response->error === "invalid_grant") {
                        throw new RedditAuthenticationException("Supplied reddit username/password are invalid or the threshold for invalid logins has been exceeded.", 1);
                    } elseif ($response->error === 401) {
                        throw new RedditAuthenticationException("Supplied reddit app ID/secret are invalid.", 2);
                    }
                } else {
                    fwrite(STDERR, "WARNING: Request for reddit access token has failed. Check your connection.\n");
                    sleep(5);
                    $attempts++;
                }
            }

            if( ! $got_token && $attempts >= $max_attempts ) {
                throw new RedditAuthenticationException("Failed to get reddit access token after $max_attempts attempts, check your internet connection and/or check reddit service status.", 3);
            }
        }
        $this->access_token = $response->access_token;
        $this->token_type = $response->token_type;
        $this->expiration = time() + $response->expires_in;
        $this->scope = $response->scope;

        // Cache the token
        if($this->cache_service){
            $this->cache_service->put($this->cache_key, [
                'access_token' => $this->access_token,
                'token_type' => $this->token_type,
                'expiration' => $this->expiration,
                'scope' => $this->scope,
            ], 60 * 60 * 24 * 14); // 14 days
        }
    }
}
