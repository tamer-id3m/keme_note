<?php

namespace App\Http\Clients;

use Illuminate\Support\Facades\Http;

class UserClient
{
    private $baseUrl = 'http://keme_user.test';
    private $authCookieHelper;

    public function __construct()
    {
        $this->authCookieHelper = new AuthCookieHelper($this->baseUrl);
    }


    public function hasPermission($permission)
    {
        try {
            $response = Http::withHeaders([
                'X-Internal-Token' => env('INTERNAL_API_TOKEN'),
                'Accept' => 'application/json',
                'user_id' => optional(auth()->user())->id,
            ])->post($this->baseUrl . "/internal/permissions/check-permission", [
                'permission_name' => $permission
            ]);
            if ($response->successful() && $response['has_permission']) {
                return true;
            }
            return false;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }


    public function authUser()
    {
        try {
            $auth = $this->authCookieHelper->getCurrentAuthCookies();
            $response = Http::withOptions([
                'cookies' => $auth['cookies'],
                'verify' => false // Only if you're in development
            ])->withHeaders([
                'X-Requested-With' => 'XMLHttpRequest',
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'X-XSRF-TOKEN' => $auth['csrf_token'],
                'Origin' => str_replace('/api', '', $this->baseUrl),
                'Referer' => str_replace('/api', '', $this->baseUrl) . '/',
            ])->get(
                $this->baseUrl . "/api/auth-user"
            );
            if ($response->successful() && isset($response['data'])) {
                return  (object) $response["data"];
            } else {
                return null;
            }
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function getUsersRoles($staffIds)
    {

        try {
            $response = Http::withHeaders([
                'X-Internal-Token' => env('INTERNAL_API_TOKEN'),
                'Accept' => 'application/json',
            ])->post($this->baseUrl . "/internal/roles/users-roles", ['staff_ids' => $staffIds]);
            if ($response->successful() && isset($response['data'])) {
                return $response['data'];
            }
            return null;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
    public function getUsersByIds($ids)
    {
        try {
            $response = Http::withHeaders([
                'X-Internal-Token' => env('INTERNAL_API_TOKEN'),
                'Accept' => 'application/json',
            ])->post(
                $this->baseUrl . "/internal/users-by-ids",
                ['ids' => $ids]
            );
            if ($response->successful() && isset($response['data'])) {
                return collect($response['data'])->map(function ($item) {
                    return (object) $item;
                });
            } else {
            }
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    public function deletePatientsByClinicId($clinicId)
    {
        try {
            $response = Http::withHeaders([
                'X-Internal-Token' => env('INTERNAL_API_TOKEN'),
                'Accept' => 'application/json',
            ])->delete($this->baseUrl . "/internal/patients-by-clinic/$clinicId");
            if ($response->successful() && isset($response['data'])) {
                return $response['data'];
            }
            return [];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    public function deleteUsersByClinicId($clinicId)
    {
                
        try {
            $response = Http::withHeaders([
                'X-Internal-Token' => env('INTERNAL_API_TOKEN'),
                'Accept' => 'application/json',
            ])->delete($this->baseUrl . "/internal/delete-users-by-clinic/$clinicId");
            if ($response->successful() && isset($response['data'])) {
                return collect($response['data'])->map(function ($item) {
                    return (object) $item;
                });
            }
            return collect([]);
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    public function getUsersByClinicId($clinicId)
    {
        try {
            $response = Http::withHeaders([
                'X-Internal-Token' => env('INTERNAL_API_TOKEN'),
                'Accept' => 'application/json',
            ])->get($this->baseUrl . "/internal/users-by-clinic/$clinicId");
            if ($response->successful() && isset($response['data'])) {
                return collect($response['data'])->map(function ($item) {
                    return (object) $item;
                });
            } else {
            }
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    public function getUsersCountByClinicIds($clinicIds)
    {
        try {
            $response = Http::withHeaders([
                'X-Internal-Token' => env('INTERNAL_API_TOKEN'),
                'Accept' => 'application/json',
            ])->post(
                $this->baseUrl . "/internal/users-clinics-count",
                ['clinic_ids' => $clinicIds]
            );
            if ($response->successful() && isset($response['data'])) {
                return $response['data'];
            } else {
            }
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    public function getStaffsByClinicIds($clinicIds)
    {
        try {
            $response = Http::withHeaders([
                'X-Internal-Token' => env('INTERNAL_API_TOKEN'),
                'Accept' => 'application/json',
            ])->post(
                $this->baseUrl . "/internal/staff/get-staffs-by-clinic-ids",
                ['clinic_ids' => $clinicIds]
            );
            if ($response->successful() && isset($response['data'])) {
                return collect($response['data'])->map(function ($item) {
                    return (object) $item;
                });
            } else {
                return collect();
            }
        } catch (\Exception $e) {
            return collect();
        }
    }
    public function getDepartmentManagerByClinicId($clinicId)
    {
        try {
            $response = Http::withHeaders([
                'X-Internal-Token' => env('INTERNAL_API_TOKEN'),
                'Accept' => 'application/json',
            ])->get(
                $this->baseUrl . "/internal/staff/department-manager/$clinicId",
            );
            if ($response->successful() && isset($response['data'])) {
                return collect($response['data'])->map(function ($item) {
                    return (object) $item;
                });
            } else {
                return collect();
            }
        } catch (\Exception $e) {
            return collect();
        }
    }

    public function getUserbyUuid($uuid)
    {
        try {
            $response = Http::withHeaders([
                'X-Internal-Token' => env('INTERNAL_API_TOKEN'),
                'Accept' => 'application/json',
            ])->get($this->baseUrl . "/internal/doctor/show/$uuid");
            if ($response->successful() && isset($response['data'])) {
                return (object) $response['data'];
            } else {
            }
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }
    }
    public function updatePatientDoctor($patientId, $doctorId)
    {
        try {
            $response = Http::withHeaders([
                'X-Internal-Token' => env('INTERNAL_API_TOKEN'),
                'Accept' => 'application/json',
            ])->post(
                $this->baseUrl . "/internal/update-patient-doctor",
                ['doctor_id' => $doctorId, "patient_id" => $patientId]
            );
            return $response;
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    public function getDoctorById($id)
    {
        try {
            $response = Http::withHeaders([
                'X-Internal-Token' => env('INTERNAL_API_TOKEN'),
                'Accept' => 'application/json',
            ])->get($this->baseUrl . "/internal/doctor/show-doctor/$id");
            if ($response->successful() && isset($response['data'])) {
                return (object) $response['data'];
            } else {
                return [
                    'error' => $response->json('message') ?? 'Failed to fetch doctor',
                    'status' => $response->status(),
                    'body' => $response->json()
                ];
            }
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }
    }
    public function getDoctors($ids)
    {
        try {
            $response = Http::withHeaders([
                'X-Internal-Token' => env('INTERNAL_API_TOKEN'),
                'Accept' => 'application/json',
            ])->post(
                $this->baseUrl . "/internal/doctor/doctors-by-ids",
                ['ids' => $ids]
            );
            if ($response->successful() && isset($response['data'])) {
                return collect($response['data'])->map(function ($item) {
                    return (object) $item;
                });
            } else {
                // Show error details if available
                return [
                    'error' => $response->json('message') ?? 'Failed to fetch doctors',
                    'status' => $response->status(),
                    'body' => $response->json()
                ];
            }
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}