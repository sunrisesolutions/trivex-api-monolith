<?php

namespace App\Controller;
use App\Message\MessageFactory;
use Symfony\Component\Routing\Annotation\Route;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/bean", condition="'%kernel.environment%' === 'dev'")
 */
class BeanPlaygroundController
{
    private $mf;

    public function __construct(MessageFactory $factory)
    {
        $this->mf = $factory;

    }

    /**
     * @Route("/hello", methods="GET")
     */
    public function publishMessage(Request $request): Response
    {
//        $content = json_decode($request->getContent(), true);
//        $m = $this->mf->getMessage('Organisation',1);

        return new JsonResponse(['hello honey ',$m]);
    }
}
