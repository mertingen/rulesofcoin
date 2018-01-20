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

    public function __construct($oauth, $callbackUrl = '', $session)
    {
        $this->oauth = $oauth;
        $this->callbackUrl = $callbackUrl;
        $this->session = $session;
    }

    public function connect($consumerKey = NULL, $consumerSecretKey = NULL, $accessToken = NULL, $accessSecretToken)
    {
        $this->consumerKey = $consumerKey;
        $this->consumerSecretKey = $consumerSecretKey;
        $this->accessToken = $accessToken;
        $this->accessSecretToken = $accessSecretToken;
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
            $connection = new $this->oauth(
                $this->consumerKey,
                $this->consumerSecretKey,
                $this->session->get('oauthToken'),
                $this->session->get('oauthTokenSecret')
            );

            $accessData = $connection->oauth("oauth/access_token", ["oauth_verifier" => $oauthVerifier]);
        } catch (TwitterOAuthException $e) {
            dump($e->getMessage());
            die;
        }

        $connection = new $this->oauth(
            $this->consumerKey,
            $this->consumerSecretKey,
            $accessData['oauth_token'],
            $accessData['oauth_token_secret']
        );

        $twitterUser = $connection->get("account/verify_credentials");
        return $twitterUser;
    }

    public function getFollowers()
    {
        $followers = $this->oauth->get('followers/list');
        dump($followers);
        die;
    }

}