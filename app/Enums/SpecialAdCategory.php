<?php

namespace App\Enums;

enum SpecialAdCategory: string
{
    case Credit = 'CREDIT';
    case Employment = 'EMPLOYMENT';
    case Housing = 'HOUSING';
    case IssuesElectionsPolitics = 'ISSUES_ELECTIONS_POLITICS';

    public function label(): string
    {
        return match ($this) {
            self::Credit => 'Credit',
            self::Employment => 'Employment',
            self::Housing => 'Housing',
            self::IssuesElectionsPolitics => 'Social issues, elections or politics',
        };
    }
}
