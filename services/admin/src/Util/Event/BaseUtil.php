<?php

namespace App\Util\Event;

use App\Message\Entity\OrganisationSupportedType;
use Doctrine\ORM\EntityManagerInterface;

class BaseUtil
{
    const PROJECT_NAME = 'TRIVEX';

    public static function getFullAppName($name)
    {
        $names = [
            'ORG' => 'Organisation',
            'PERSON' => 'Person',
            'AUTH' => 'Acrole'
        ];
        return $names[$name];
    }

    public static function getRemoteObject($endpoint)
    {
//        $client = new GuzzleHttp\Client();
//        $res = $client->request('GET', 'https://api.github.com/user', [
//            'auth' => ['user', 'pass']
//        ]);
//        echo $res->getStatusCode();
//// "200"
//        echo $res->getHeader('content-type')[0];
//// 'application/json; charset=utf8'
//        echo $res->getBody();
//// {"type":"User"...'
//
//// Send an asynchronous request.
//        $request = new \GuzzleHttp\Psr7\Request('GET', 'http://httpbin.org');
//        $promise = $client->sendAsync($request)->then(function ($response) {
//            echo 'I completed! ' . $response->getBody();
//        });
//        $promise->wait();
    }

    public static function generateUuid($prefix = AppUtil::APP_NAME)
    {
        return sprintf('%s-%s-%s', $prefix, uniqid(), date_format(new \DateTime(), 'HidmY'));
    }

    public static function copyObjectScalarProperties($source, $dest, $nullable = true)
    {
//        $props = get_object_vars($source);
        if (method_exists($source, 'copyScalarProperties')) {
            $source->copyScalarProperties($dest);
            return;
        }

        $reflection = new \ReflectionClass($source);
        $reflectionProps = $reflection->getProperties();
        $nonScalarProps = [];

        /** @var \ReflectionProperty $reflectionProp */
        foreach ($reflectionProps as $reflectionProp) {
            $prop = $reflectionProp->getName();

            if (in_array($prop, ['id', '__initializer__', '__cloner__', '__isInitialized__', 'lazyPropertiesDefaults'])) {
                continue;
            }

            $getter = 'get'.ucfirst(strtolower($prop));
            $val = $source->{$getter}();
            if (is_scalar($val) || $val instanceof \DateTime) {
                if (!$nullable && $val === null) {
                    continue;
                }
                $setter = 'set'.ucfirst(strtolower($prop));
                $dest->{$setter}($val);
            } elseif ($val !== null) {
                $nonScalarProps[$prop] = $val;
            }
        }

        $props = get_object_vars($source);
        foreach ($props as $prop => $val) {
            if (in_array($prop, ['id', '__initializer__', '__cloner__', '__isInitialized__', 'lazyPropertiesDefaults'])) {
                continue;
            }

//            echo 'prop is ' . $prop . '  ';
            if (is_scalar($val)) {
                if (!$nullable && $val === null) {
                    continue;
                }
                $reflectionDest = new \ReflectionClass($dest);
                if ($reflectionDest->hasProperty($prop)) {
                    $setter = 'set'.ucfirst(strtolower($prop));
                    $getter = 'get'.ucfirst(strtolower($prop));

//                    if ($dest->{$getter}() instanceof \DateTime) { //tuan fix
//                        $val = new \DateTime($val);
//                    }

                    $p = $reflectionDest->getMethod($setter)->getParameters()[0];
                    if ($n = $p->getType()) {
                        $n = $p->getType()->getName();
                        if ($n === 'DateTimeInterface' || $n === 'DateTime') {
                            $val = new \DateTime($val);
                        }
                    }

                    $dest->{$setter}($val);
                }
            } else {
                $nonScalarProps[$prop] = $val;
            }
        }


        return $nonScalarProps;
    }
}