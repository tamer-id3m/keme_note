<?php

namespace App\Http\Clients;

use Illuminate\Support\Facades\Http;

class ClinicClient
{
    private $baseUrl;
    private $internalToken;

    public function __construct()
    {
        $this->baseUrl = env('CLINIC_SERVICE_URL', 'http://keme_clinic.test');
        $this->internalToken = env('INTERNAL_API_TOKEN');
    }

    /**
     * Get clinic by ID
     */
    public function getClinicById($id)
    {
        try {
            $response = Http::withHeaders([
                'X-Internal-Token' => $this->internalToken,
                'Accept' => 'application/json',
            ])->get($this->baseUrl . "/internal/clinics/{$id}");

            if ($response->successful() && isset($response['data'])) {
                return (object) $response['data'];
            }
            return null;
        } catch (\Exception $e) {
            logger()->error("ClinicClient getClinicById error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get clinic by user ID (to find which clinic a patient belongs to)
     */
    public function getClinicByUserId($userId)
    {
        try {
            $response = Http::withHeaders([
                'X-Internal-Token' => $this->internalToken,
                'Accept' => 'application/json',
            ])->get($this->baseUrl . "/internal/clinics/user/{$userId}");

            if ($response->successful() && isset($response['data'])) {
                return (object) $response['data'];
            }
            return null;
        } catch (\Exception $e) {
            logger()->error("ClinicClient getClinicByUserId error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get multiple clinics by IDs
     */
    public function getClinicsByIds(array $ids)
    {
        try {
            $response = Http::withHeaders([
                'X-Internal-Token' => $this->internalToken,
                'Accept' => 'application/json',
            ])->post($this->baseUrl . "/internal/clinics-by-ids", [
                'ids' => $ids
            ]);

            if ($response->successful() && isset($response['data'])) {
                return collect($response['data'])->map(function ($item) {
                    return (object) $item;
                });
            }
            return collect();
        } catch (\Exception $e) {
            logger()->error("ClinicClient getClinicsByIds error: " . $e->getMessage());
            return collect();
        }
    }

    /**
     * Get active clinics
     */
    public function getActiveClinics()
    {
        try {
            $response = Http::withHeaders([
                'X-Internal-Token' => $this->internalToken,
                'Accept' => 'application/json',
            ])->post($this->baseUrl . "/internal/active-clinics");

            if ($response->successful() && isset($response['data'])) {
                return collect($response['data'])->map(function ($item) {
                    return (object) $item;
                });
            }
            return collect();
        } catch (\Exception $e) {
            logger()->error("ClinicClient getActiveClinics error: " . $e->getMessage());
            return collect();
        }
    }

    /**
     * Get clinic by UUID
     */
    public function getClinicByUuid($uuid)
    {
        try {
            $response = Http::withHeaders([
                'X-Internal-Token' => $this->internalToken,
                'Accept' => 'application/json',
            ])->get($this->baseUrl . "/internal/clinics/{$uuid}");

            if ($response->successful() && isset($response['data'])) {
                return (object) $response['data'];
            }
            return null;
        } catch (\Exception $e) {
            logger()->error("ClinicClient getClinicByUuid error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get clinic by phone number
     */
    public function getClinicByPhone($phone)
    {
        try {
            $response = Http::withHeaders([
                'X-Internal-Token' => $this->internalToken,
                'Accept' => 'application/json',
            ])->get($this->baseUrl . "/internal/clinics?phone={$phone}");

            if ($response->successful() && isset($response['data'])) {
                return (object) $response['data'];
            }
            return null;
        } catch (\Exception $e) {
            logger()->error("ClinicClient getClinicByPhone error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get clinic IDs only
     */
    public function getClinicIds()
    {
        try {
            $response = Http::withHeaders([
                'X-Internal-Token' => $this->internalToken,
                'Accept' => 'application/json',
            ])->get($this->baseUrl . "/internal/clinics/ids");

            if ($response->successful() && isset($response['data'])) {
                return $response['data'];
            }
            return [];
        } catch (\Exception $e) {
            logger()->error("ClinicClient getClinicIds error: " . $e->getMessage());
            return [];
        }
    }


    public function getClinicTimezone($clinicId)
    {
        try {
            $clinic = $this->getClinicById($clinicId);
            return $clinic->time_zone ?? null;
        } catch (\Exception $e) {
            logger()->error("ClinicClient getClinicTimezone error: " . $e->getMessage());
            return null;
        }
    }
}