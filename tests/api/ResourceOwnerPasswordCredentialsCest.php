<?php

use app\fixtures\UserFixture;
use app\fixtures\OauthScopesFixture;
use Codeception\Util\HttpCode;
use tecnocen\oauth2server\fixtures\OauthClientsFixture;
use yii\helpers\Json;

/**
 * @author Christopher CM <ccastaneira@tecnoce.com>
 */
class ResourceOwnerPasswordCredentialsCest
{
    static $token;

    public function fixtures(ApiTester $I)
    {
        $I->haveFixtures([
            'user' => UserFixture::class,
            'scopes' => OauthScopesFixture::class,
            'clients' => OauthClientsFixture::class,
        ]);
    }

    /**
     * @depends fixtures
     */
    public function accessTokenRequest(ApiTester $I)
    {
        $I->wantTo('Request a new access token.');
        $I->amHttpAuthenticated('testclient', 'testpass');

        $I->sendPOST('/oauth2/token', [
            'grant_type' => 'password',
            'username' => 'erau',
            'password' => 'password_0',
        ]);

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseMatchesJsonType([
            'access_token' => 'string:regex(/[0-9a-f]{40}/)',
            'refresh_token' => 'string:regex(/[0-9a-f]{40}/)',
        ]);

        self::$token = $I->grabDataFromResponseByJsonPath('$.access_token')[0];
    }

    /**
     * @depends fixtures
     */
    public function accessTokenRequestInvalid(ApiTester $I)
    {
        $I->wantTo('Request a new access token with invalid credentials.');
        $I->amHttpAuthenticated('testclient', 'testpass');

        $I->sendPOST('/oauth2/token', [
            'grant_type' => 'password',
            'username' => 'wrong_user',
            'password' => 'password_0',
        ]);

        $I->seeResponseCodeIs(HttpCode::UNAUTHORIZED);
        $I->seeResponseIsJson();

        $I->seeResponseMatchesJsonType([
            'error' => 'string',
            'error_description' => 'string',
            // 'error_uri' => 'string|null',
        ]);

        $token = Json::decode($I->grabResponse());
        $I->seeResponseContainsJson([
            'error_description' => tecnocen\oauth2server\Module::t(
                'oauth2server', $token['error_description']
            )
        ]);
    }

    /**
     * @depends fixtures
     */
    public function accessTokenRequestWithScopes(ApiTester $I)
    {
        $I->wantTo('Request a new access token with scope.');
        $I->amHttpAuthenticated('testclient', 'testpass');

        $I->sendPOST('/oauth2/token', [
            'grant_type' => 'password',
            'username' => 'erau',
            'password' => 'password_0',
            'scope' => 'user',
        ]);

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseMatchesJsonType([
            'access_token' => 'string:regex(/[0-9a-f]{40}/)',
            'refresh_token' => 'string:regex(/[0-9a-f]{40}/)',
        ]);
    }

    /**
     * @depends accessTokenRequest
     */
    public function requestToResource(ApiTester $I)
    {
        $I->wantTo('Request a resource controller.');
        $I->sendGET('/site/index', [
            'accessToken' => self::$token,
        ]);

         $I->seeResponseCodeIs(HttpCode::OK);
    }

    /**
     * @depends fixtures
     */
    public function requestToResourceIvalid(ApiTester $I)
    {
        $I->wantTo('Request a resource controller with invalid token.');

        $I->sendGET('/site/index', [
            'accessToken' => md5('InvalidToken'),
        ]);

        $I->seeResponseCodeIs(HttpCode::UNAUTHORIZED);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'name' => 'Unauthorized',
            'message' => 'Your request was made with invalid credentials.',
        ]);
    }
}
