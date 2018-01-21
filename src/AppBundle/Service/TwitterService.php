<?php
/**
 * Created by IntelliJ IDEA.
 * User: mert
 * Date: 1/20/18
 * Time: 6:51 PM
 */

namespace AppBundle\Service;


use Abraham\TwitterOAuth\TwitterOAuth;
use Abraham\TwitterOAuth\TwitterOAuthException;
use Symfony\Component\HttpFoundation\Session\Session;

class TwitterService
{
    /**
     * @var TwitterOAuth $oauth
     */
    private $oauth;
    private $consumerKey;
    private $consumerSecretKey;
    private $accessToken;
    private $accessSecretToken;
    private $callbackUrl;
    /**
     * @var Session $session
     */
    private $session;

    public function __construct($oauth, $twitterData = array(), $session)
    {
        $this->oauth = $oauth;
        $this->callbackUrl = $twitterData['callback_url'];
        $this->session = $session;
    }

    public function connect($twitterData = array())
    {
        $this->consumerKey = $twitterData['consumer_key'];
        $this->consumerSecretKey = $twitterData['consumer_secret_key'];
        $this->accessToken = $twitterData['access_token'];
        $this->accessSecretToken = $twitterData['access_secret_token'];
        $this->oauth = new $this->oauth(
            $this->consumerKey,
            $this->consumerSecretKey,
            $this->accessToken,
            $this->accessSecretToken
        );
    }

    public function getUrl()
    {
        try {
            $requestToken = $this->oauth->oauth('oauth/request_token', array('oauth_callback' => $this->callbackUrl));
        } catch (TwitterOAuthException $e) {
            dump($e->getMessage());
            die;
        }
        $oauthToken = $requestToken['oauth_token'];
        $oauthSecretToken = $requestToken['oauth_token_secret'];

        $this->session->remove('oauthToken');
        $this->session->set('oauthToken', $oauthToken);
        $this->session->set('oauthTokenSecret', $oauthSecretToken);

        return $this->oauth->url('oauth/authorize', array('oauth_token' => $requestToken['oauth_token']));

    }

    public function getUser($oauthVerifier = '')
    {
        try {
            $twitterData = array(
                'consumer_key' => $this->consumerKey,
                'consumer_secret_key' => $this->consumerSecretKey,
                'access_token' => $this->session->get('oauthToken'),
                'access_secret_token' => $this->session->get('oauthTokenSecret')
            );
            $this->connect($twitterData);

            $accessData = $this->oauth->oauth("oauth/access_token", ["oauth_verifier" => $oauthVerifier]);
        } catch (TwitterOAuthException $e) {
            dump($e->getMessage());
            die;
        }

        $twitterData = array(
            'consumer_key' => $this->consumerKey,
            'consumer_secret_key' => $this->consumerSecretKey,
            'access_token' => $accessData['oauth_token'],
            'access_secret_token' => $accessData['oauth_token_secret']
        );

        $this->connect($twitterData);
        $twitterUser = $this->oauth->get("account/verify_credentials");
        return $twitterUser;
    }

    /**
     * @param string $screeName
     * @param string $message
     */
    public function sendMessage($screeName = '', $message = '')
    {
        $options = array("screen_name" => $screeName, "text" => $message);
        $result = $this->oauth->post('direct_messages/new', $options);
        if ($result->errors){
            print_r($result->errors);
        }
    }

}