<?php
// api/src/Filter/RegexpFilter.php

namespace App\Filter\Messaging;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\AbstractContextAwareFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\PropertyHelperTrait;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use App\Entity\Delivery;
use Doctrine\ORM\QueryBuilder;

final class GroupByFilter extends AbstractContextAwareFilter
{
    use PropertyHelperTrait;

    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null)
    {
        $expr = $queryBuilder->expr();
        $rootAlias = $queryBuilder->getRootAliases()[0];
        if ($resourceClass === Delivery::class) {
            if ($property === 'groupByMessage' && !empty($value)) {
//                $alias = 'messageSender';
//                $queryBuilder->join('message.sender', 'messageSender');
                $queryBuilder->groupBy($rootAlias.'.message');;
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
        if ($resourceClass === Delivery::class) {
            $description["groupByMessage"] = [
                'property' => 'groupByMessage',
                'type' => 'string',
                'required' => false,
                'swagger' => [
                    'description' => 'groupByMessage.',
                    'name' => 'groupByMessage',
                    'type' => 'Will appear below the name in the Swagger documentation',
                ],
            ];
        }

        if (!$this->properties) {
            return $description;
//            return [];
        }

        foreach ($this->properties as $property => $strategy) {
            $description["not_like_$property"] = [
                'property' => $property,
                'type' => 'string',
                'required' => false,
                'swagger' => [
                    'description' => 'Filter using a NOT LIKE operator. This will appear in the Swagger documentation!',
                    'name' => 'Not-Like Filter',
                    'type' => 'Will appear below the name in the Swagger documentation',
                ],
            ];
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
