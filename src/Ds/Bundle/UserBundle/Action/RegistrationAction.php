<?php

namespace Ds\Bundle\UserBundle\Action;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use FOS\UserBundle\Model\UserManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

use GuzzleHttp\Psr7;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\RequestException;

use Ds\Component\Container\Attribute;

/**
 * Class RegistrationAction
 */
class RegistrationAction
{
    /**
     * @var \Symfony\Component\HttpFoundation\RequestStack
     */
    protected $requestStack;

    /**
     * @var \FOS\UserBundle\Model\UserManagerInterface
     */
    protected $userManager;

    /**
     * Constructor
     *
     * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
     * @param \FOS\UserBundle\Model\UserManagerInterface $userManager
     * @param \Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface $tokenManager
     */
    public function __construct(RequestStack $requestStack, UserManagerInterface $userManager, JWTTokenManagerInterface $tokenManager)
    {
        $this->requestStack = $requestStack;
        $this->userManager = $userManager;
        $this->tokenManager = $tokenManager;
    }

    /**
     * Registration
     *
     * @Route(path="/registration")
     * @Method("POST")
     */
    public function __invoke()
    {
        $request = $this->requestStack->getCurrentRequest();
        $username = $request->get('username');
        $password = $request->get('password');

        $exists = $this->userManager->findUserByUsernameOrEmail($username);

        if ($exists) {
            return new JsonResponse([ 'error' => 'Username is already taken.' ], Response::HTTP_BAD_REQUEST);
        }

        $individualUuid = $this->createIndividual();

        if ($individualUuid) {
            $user = $this->userManager->createUser();
            $user
                ->setUsername($username)
                ->setEmail($username)
                ->setPlainPassword($password)
                ->setRoles(['ROLE_INDIVIDUAL'])
                ->setIdentity('Individual')
                ->setIdentityUuid($individualUuid)
                ->setEnabled(true);

            $this->userManager->updateUser($user);
            return new JsonResponse([ 'uuid' => $user->getUuid() ], Response::HTTP_CREATED);
        }
        else {
            return new JsonResponse([ 'error' => 'Unable to create an Individual identity' ], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Create a new Individual identity and attach it to a new IndividualPersona.
     *
     * @return bool|string Upon success return the UUID of the newly created individual; otherwise, return FALSE.
     */
    protected function createIndividual() {
        // @todo For the Microservice URI to work in Docker, the Identities MS must be added as a `link` in docker-compose.yml
        $client = new HttpClient();
        $microservice_uri = 'http://localhost:8054/app_dev.php';

        // Generates a JWT token for the `identities` system user
         $identitiesUser = $this->userManager->findUserByUsername('identities');
         $identitiesUserToken = $this->tokenManager->create($identitiesUser);

        try {
            $headers = [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $identitiesUserToken
                // @note Enabling `client`-based token verification in the Identities MS will cause it to return a `Invalid JWT token` error
            ];

            $individualCreationResponse = $client->request('POST', $microservice_uri . '/individuals', [
                'body' => \GuzzleHttp\json_encode([
                  'owner' => 'BusinessUnit',
                  'ownerUuid' => '8bca60bb-11ac-420a-bf7b-23c698ab9244',
                  'title' => ['en' => 'Registered Individual from the Authentication MS']
                ]),
                'headers'  => $headers,
            ]);

            $individualJson = \GuzzleHttp\json_decode($individualCreationResponse->getBody()->getContents());

            if ($individualJson) {
                $individualPresonaCreationResponse = $client->request('POST', $microservice_uri . '/individual-personas', [
                    'body' => \GuzzleHttp\json_encode([
                        'owner' => 'BusinessUnit',
                        'ownerUuid' => '8bca60bb-11ac-420a-bf7b-23c698ab9244',
                        'title' => ['en' => 'Default individual persona'],
                        'individual' => '/individuals/' . $individualJson->uuid
                    ]),
                    'headers'  => $headers,
                ]);

                return $individualJson->uuid;
            }

        } catch (\Exception $e) {
            // if (($e instanceof RequestException) && $e->hasResponse()) {}
        }

        return false;
    }
}
