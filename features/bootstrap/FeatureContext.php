<?php

use Assert\Assertion;
use Behat\Behat\Context\Context;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\SchemaTool;
use Imbo\BehatApiExtension\Context\ApiContext;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;

/**
 * Defines application features from the specific context.
 */
class FeatureContext extends ApiContext implements Context
{
    /**
     * @var ManagerRegistry
     */
    private $doctrine;

    /**
     * @var JWTEncoderInterface
     */
    private $jwtEncoder;

    /**
     * @var \Doctrine\Common\Persistence\ObjectManager
     */
    private $manager;

    /**
     * @var SchemaTool
     */
    private $schemaTool;

    /**
     * @var array
     */
    private $classes;

    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     */
    public function __construct(ManagerRegistry $doctrine, JWTEncoderInterface $jwtEncoder)
    {
        $this->doctrine = $doctrine;
        $this->jwtEncoder = $jwtEncoder;
        $this->manager = $doctrine->getManager();
        $this->schemaTool = new SchemaTool($this->manager);
        $this->classes = $this->manager->getMetadataFactory()->getAllMetadata();
    }

    /**
     * @BeforeScenario @createSchema
     */
    public function createDatabase()
    {
        $this->schemaTool->createSchema($this->classes);
    }

    /**
     * @AfterScenario @dropSchema
     */
    public function dropDatabase()
    {
        $this->schemaTool->dropSchema($this->classes);
    }

    /**
     * Check whether a JWT token, with a given name, contains the provided
     * property and it's value.
     *
     * @Then the response body contains JWT token named :tokenName with :property property as :value
     */
    public function theResponseBodyContainsJwtTokenNamedWithPropertyAsValue($tokenName, $property, $value)
    {
        $bodyStr = $this->response->getBody()->getContents();
        $body = json_decode((string) $bodyStr, true);

        Assertion::nullOrNotInArray(
            $tokenName,
            $body,
            'stdClass',
            'The response body is not a valid JSON object.'
        );

        $decodedToken = $this->jwtEncoder->decode($body[$tokenName]);

        Assertion::eq($decodedToken[$property], $value);
    }
}
