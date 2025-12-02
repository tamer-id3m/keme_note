<?php

namespace App\Enums;

enum RoleType: string
{
    case Admin = 'admin';
    case SystemWide = 'system-wide';
    case ClinicSpecific = 'clinic-specific';

    /**
     * Get all the enum values as an array.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }


    /**
     * get the value of a specific enum case.
     */
    public static function getCaseValue(string $case): ?string
    {
        try {
          
            return constant(self::class . '::' . $case)->value;
        } catch (\Error) {
            throw new \InvalidArgumentException("Invalid RoleType case: {$case}");
        }
    }
}