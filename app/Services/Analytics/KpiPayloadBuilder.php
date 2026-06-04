<?php

namespace App\Services\Analytics;

class KpiPayloadBuilder
{
    /**
     * Build the full JSON payload expected by the Analytics Engine.
     *
     * @param string $calculationType
     * @param array $ast
     * @param array $scope
     * @return array
     */
    public static function build(string $calculationType, array $ast, array $scope): array
    {
        return [
            'ast' => $ast,
            'filters' => [
                'startDate' => $scope['start_date'],
                'endDate' => $scope['end_date'],
                'groupBy' => [$scope['granularity']],
            ],
            $calculationType => true,
        ];
    }
}
