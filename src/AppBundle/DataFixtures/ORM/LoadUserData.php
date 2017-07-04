<?php

namespace AppBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Ds\Component\Migration\Fixture\ORM\ResourceFixture;

/**
 * Class LoadUserData
 */
class LoadUserData extends ResourceFixture implements OrderedFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $userManager = $this->container->get('fos_user.user_manager');
        $users = $this->parse(__DIR__.'/../../Resources/data/{server}/users.yml');

        foreach ($users as $user) {
            $entity = $userManager->createUser();
            $entity
                ->setUuid($user['uuid'])
                ->setUsername($user['username'])
                ->setEmail($user['email'])
                ->setPlainPassword($user['password'])
                ->setRoles($user['roles'])
                ->setOwner($user['owner'])
                ->setOwnerUuid($user['owner_uuid'])
                ->setIdentity($user['identity'])
                ->setIdentityUuid($user['identity_uuid'])
                ->setEnabled($user['enabled']);
            $userManager->updateUser($entity);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 10;
    }
}