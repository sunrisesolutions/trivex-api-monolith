<?php
// api/src/Filter/RegexpFilter.php

namespace App\Filter;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\AbstractContextAwareFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\PropertyHelperTrait;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use App\Entity\Messaging\Delivery;
use App\Entity\Messaging\Message;
use Doctrine\ORM\QueryBuilder;

final class ExpiryFilter extends AbstractContextAwareFilter
{
    use PropertyHelperTrait;

    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null)
    {
        $expr = $queryBuilder->expr();
        $rootAlias = $queryBuilder->getRootAliases()[0];
        if ($resourceClass === Delivery::class) {
            if ($property === 'isExpired' && !empty($value)) {
//                $alias = 'messageSender';
//                $queryBuilder->join('message.sender', 'messageSender');
                $now = new \DateTime();
                $isExpired = $value !== 'false';
                if ($isExpired) {
                    $queryBuilder->andWhere($expr->lte('message.expireAt', $expr->literal($now->format('Y-m-d'))));
                } else {
                    $queryBuilder->andWhere($expr->gte('message.expireAt', $expr->literal($now->format('Y-m-d'))));
                }
            }
        }
        if ($resourceClass === Message::class) {
            if ($property === 'isExpired' && !empty($value)) {
                $now = new \DateTime();
                $isExpired = $value !== 'false';
                if ($isExpired) {
                    $queryBuilder->andWhere($expr->lte($rootAlias.'.expireAt', $expr->literal($now->format('Y-m-d'))));
                } else {
                    $queryBuilder->andWhere($expr->gte($rootAlias.'.expireAt', $expr->literal($now->format('Y-m-d'))));
                }
            }
        }

        // otherwise filter is applied to order and page as well
        if (
            !$this->isPropertyEnabled($property, $resourceClass) ||
            !$this->isPropertyMapped($property, $resourceClass)
        ) {
            return;
        }

//        $parameterName = $queryNameGenerator->generateParameterName($property); // Generate a unique parameter name to avoid collisions with other filters
//        $expr = $queryBuilder->expr();
//        if ($property === 'messageSenderUuid') {
////            $queryBuilder->join('o.message', 'message')->join('message.sender', 'messageSender');
////            $queryBuilder->andWhere($expr->notLike('messageSender.uuid', $expr->literal($value)));
//        } else {
//            $queryBuilder
//                ->andWhere($expr->notLike('o.'.$property, $parameterName))
////            ->andWhere(sprintf('REGEXP(o.%s, :%s) = 1', $property, $parameterName))
//                ->setParameter($parameterName, $value);
//        }
    }

    // This function is only used to hook in documentation generators (supported by Swagger and Hydra)
    public function getDescription(string $resourceClass): array
    {
        $description = [];

        $description["is_expired"] = [
            'property' => 'isExpired',
            'type' => 'bool',
            'required' => false,
            'swagger' => [
                'description' => 'Filter By expiredAt property',
                'name' => 'isExpired',
                'type' => 'boolean',
            ],
        ];


        if (!$this->properties) {
            return $description;
//            return [];
        }
//        $description["not_like_message_sender_uuid"] = [
//            'property' => 'messageSenderUuid',
//            'type' => 'string',
//            'required' => false,
//            'swagger' => [
//                'description' => 'Filter using a NOT LIKE operator. This will appear in the Swagger documentation!',
//                'name' => 'Not-Like Filter',
//                'type' => 'Will appear below the name in the Swagger documentation',
//            ],
//        ];

        return $description;
    }
}
