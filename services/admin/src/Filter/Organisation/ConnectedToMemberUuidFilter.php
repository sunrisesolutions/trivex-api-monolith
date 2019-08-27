<?php
// api/src/Filter/RegexpFilter.php

namespace App\Filter\Organisation;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\AbstractContextAwareFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\PropertyHelperTrait;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use App\Entity\Delivery;
use App\Entity\IndividualMember;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

final class ConnectedToMemberUuidFilter extends AbstractContextAwareFilter
{
    use PropertyHelperTrait;

    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null)
    {
        $expr = $queryBuilder->expr();
        $rootAlias = $queryBuilder->getRootAliases()[0];
        if ($resourceClass === IndividualMember::class) {
            if ($property === 'connectedToMemberUuid' && !empty($value)) {
                [$fromConnAlias, $fromField, $fromAssociations] = $this->addJoinsForNestedProperty('fromConnections.toMember', $rootAlias, $queryBuilder, $queryNameGenerator, $resourceClass, Join::LEFT_JOIN);
                [$fromAlias, $fromField, $fromAssociations] = $this->addJoinsForNestedProperty('fromConnections.toMember.uuid', $rootAlias, $queryBuilder, $queryNameGenerator, $resourceClass, Join::LEFT_JOIN);
                [$toConnAlias, $toField, $toAssociations] = $this->addJoinsForNestedProperty('toConnections.fromMember', $rootAlias, $queryBuilder, $queryNameGenerator, $resourceClass, Join::LEFT_JOIN);
                [$toAlias, $toField, $toAssociations] = $this->addJoinsForNestedProperty('toConnections.fromMember.uuid', $rootAlias, $queryBuilder, $queryNameGenerator, $resourceClass, Join::LEFT_JOIN);
//                $queryBuilder->leftJoin($join, $alias)
                $queryBuilder->andWhere(
                    $expr->like($rootAlias.'.uuid', $expr->literal($value))
//                    $expr->orX(
//                        $expr->like($fromAlias.'.uuid', $expr->literal($value)),
//                        $expr->like($toAlias.'.uuid', $expr->literal($value))
//                    )
                );
//                $sql = $queryBuilder->getQuery()->getSQL();
//                echo $sql;
//                exit();
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
        if ($resourceClass === IndividualMember::class) {
            $description["connected_to_memberUuid"] = [
                'property' => 'connectedToMemberUuid',
                'type' => 'string',
                'required' => false,
                'swagger' => [
                    'description' => 'Filter Members who are connected to the member with the given UUID',
                    'name' => 'connectedToMemberUuid',
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
