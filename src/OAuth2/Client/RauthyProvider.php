<?php

namespace App\OAuth2\Client;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides supports for OAuth2 Authorization Code flows with Rauthy instances.
 *
 * HOW-TO USE
 * 1. Add a .png image in `public/oauth2/providers` dir to show the provider's logo in the login page
 * 2. Add a new provider under `clients` key in `config/packages/knpu_oauth2_client.yaml`:
 * ```yaml
 *  rauthy_<instance name>:
 *    type: generic
 *    provider_class: App\OAuth2\Client\RauthyProvider
 *    provider_options:
 *      url: <instance web address>
 *    client_id: "%env(<instance's OAuth2 Client id>)%"
 *    client_secret: "%env(<instance's OAuth2 Client secret>)%"
 *    redirect_route: oauth_providers_callback
 *    redirect_params:
 *      provider: rauthy_<instance name>
 * ```
 */
class RauthyProvider extends AbstractProvider
{
    private string $providerUri;

    private string $providerClient;

    /**
     * @param array{url: string, clientId: string, clientSecret: string, redirectUri: string} $options
     */
    public function __construct(
        array $options = [],
        array $collaborators = [],
    ) {
        if (!\array_key_exists('url', $options)) {
            throw new \Exception("Key 'url' must be given in the options array");
        }

        $this->providerUri = $options['url'];
        $this->providerClient = $options['clientId'];

        return parent::__construct($options, $collaborators);
    }

    public function getBaseAuthorizationUrl(): string
    {
        return \sprintf('%s/auth/v1/oidc/authorize', $this->providerUri);
    }

    public function getBaseAccessTokenUrl(array $params): string
    {
        return \sprintf('%s/auth/v1/oidc/token', $this->providerUri);
    }

    public function getResourceOwnerDetailsUrl(AccessToken $token): string
    {
        return \sprintf('%s/auth/v1/oidc/userinfo', $this->providerUri);
    }

    public function getDefaultScopes(): array
    {
        return [
            'email openid profile groups',
        ];
    }

    protected function checkResponse(ResponseInterface $response, $data): void
    {
        $code = $response->getStatusCode();
        if ($code === Response::HTTP_OK) {
            return;
        }

        throw new IdentityProviderException('The instance returned a non-OK HTTP response', $code, $response);
    }

    protected function fetchResourceOwnerDetails(AccessToken $token): mixed
    {
        $accessToken = $token->getToken();

        $url = $this->getResourceOwnerDetailsUrl($token);

        $request = $this->getRequest(Request::METHOD_GET, $url, [
            'headers' => [
                'Authorization' => "Bearer $accessToken",
                'X-Jwt-Aud' => $this->providerClient,
                'Accept' => 'application/json',
            ],
        ]);

        return $this->getParsedResponse($request);
    }

    protected function createResourceOwner(array $response, AccessToken $token): ResourceOwnerInterface
    {
        return new TokenOwner(
            $response['email'],
            $response['given_name'],
            $response['family_name']
        );
    }
}
