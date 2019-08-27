<?php

declare(strict_types=1);

namespace App\Message;

use App\Util\BaseUtil;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManagerInterface;

abstract class Message
{
    const OPERATION_DELETE = 'DELETE';
    const OPERATION_POST = 'POST';
    const OPERATION_PUT = 'PUT';

    public $version;

    public $data;

    protected abstract function getSupportedType(): string;

    protected function prePersist($obj, $entity)
    {

    }

    protected function getEntity(EntityManagerInterface $manager, ObjectRepository $repo, $obj)
    {
        return $entity = $repo->findOneBy(['uuid' => $obj->uuid]);
    }

    public function updateEntity(EntityManagerInterface $manager)
    {
        $props = get_object_vars($this->data);
        foreach ($props as $prop => $obj) {
//            echo 'prop is ' . $prop . '  ';
            $supportedType = $this->getSupportedType();
            if (defined("$supportedType::$prop")) {
//                echo '///////////////////////////';

                $className = constant("$supportedType::$prop");
                $repo = $manager->getRepository($className);
                $entity = $this->getEntity($manager, $repo, $obj);

                if (!empty($entity)) {
//                    echo '#################### '.$obj->uuid.' ################';
//                    echo get_class($entity);
//                    var_dump($entity);
                    if ($obj->_SYSTEM_OPERATION === self::OPERATION_DELETE) {
                        $manager->remove($entity);
                        break;
                    }
                } else {
//                    echo 'new entity from Message.php' . $className;
                    $entity = new $className();
                }

//                echo '$obj->givenName is '.$obj->givenName;

                $nonScalarProps = BaseUtil::copyObjectScalarProperties($obj, $entity);
//echo 'entity->givename is '.$entity->getGivenName();
                foreach ($nonScalarProps as $_prop => $_obj) {
                    if (defined("$supportedType::$_prop")) {
                        $_className = constant("$supportedType::$_prop");
                        $_repo = $manager->getRepository($_className);
                        if (!empty($_obj)) {
                            $_entity = $_repo->findOneBy(['uuid' => $_obj->uuid]);

//                        echo '_prop is ' . $_prop . ' uuid: ' . $_obj->uuid . '  ' . $_className . ' ' . empty($_entity) . '  ';
                            if (empty($_entity)) {
                                $_entity = new $_className;
                                $_entity->setUuid($_obj->uuid);
                            }
                            $setter = 'set' . ucfirst(strtolower($_prop));
                            $entity->{$setter}($_entity);
                            $manager->persist($_entity);
                        }
                    }
                }
                $this->prePersist($obj, $entity);
//                echo 'prePERSSTTT';
                $manager->persist($entity);
            } else {
//                echo $supportedType.'::'.$prop.' NOT DEFINED';
            }
        }
        $manager->flush();
    }

    public $url;

    public $id;

    public $body;

    public $receiptHandle;
}
