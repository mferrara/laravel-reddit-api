<?php

return [
    'endpoint_standard' => 'https://www.reddit.com',
    'endpoint_oauth' => 'https://oauth.reddit.com',

    'username' => '',
    'password' => '',
    'app_id' => '',
    'app_secret' => '',
    'grant_type' => '', // 'password' or 'authorization_code'
    'user_agent' => '(CodeWizz 0.1)',
    'response_format' => 'STD', // STD | ARRAY

    'scopes' => 'save,modposts,identity,edit,flair,history,modconfig,modflair,modlog,modposts,modwiki,mysubreddits,privatemessages,read,report,submit,subscribe,vote,wikiedit,wikiread',

    'oauth_scopes' => ['identity', 'mysubreddits', 'read'],
    'oauth_app_id' => '',
    'oauth_app_secret' => '',
    'oauth_grant_type' => '', // 'client_credentials' or 'refresh_token'
    'redirect_uri' => '',

    'rate_limited' => true,
    'cache_rate_limiting_headers' => true,

    'cache_auth_token' => true,
    'cache_driver' => 'file',
];
