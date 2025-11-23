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

    /**
     * Get user by ID (for patients, doctors, general users)
     */
    public function getUserById($id)
    {
        try {
            $response = Http::withHeaders([
                'X-Internal-Token' => env('INTERNAL_API_TOKEN'),
                'Accept' => 'application/json',
            ])->get($this->baseUrl . "/internal/users/{$id}");

            if ($response->successful() && isset($response['data'])) {
                return (object) $response['data'];
            }
            return null;
        } catch (\Exception $e) {
            logger()->error("UserClient getUserById error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get patient information by ID
     * Since patients are users, this uses the same getUserById method
     */
    public function getPatientById($id)
    {
        return $this->getUserById($id);
    }

    /**
     * Update patient type (for changing patient from 'New' to 'FollowUp')
     */
    public function updatePatientType($patientId, $type)
    {
        try {
            $response = Http::withHeaders([
                'X-Internal-Token' => env('INTERNAL_API_TOKEN'),
                'Accept' => 'application/json',
            ])->post($this->baseUrl . "/internal/update-patient-type/{$patientId}", [
                'type' => $type
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            logger()->error("UserClient updatePatientType error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get doctor by ID
     */
    public function getDoctorById($id)
    {
        try {
            $response = Http::withHeaders([
                'X-Internal-Token' => env('INTERNAL_API_TOKEN'),
                'Accept' => 'application/json',
            ])->get($this->baseUrl . "/internal/doctor/show-doctor/{$id}");

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
            logger()->error("UserClient getDoctorById error: " . $e->getMessage());
            return [
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get multiple users by IDs
     */
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
                return collect();
            }
        } catch (\Exception $e) {
            logger()->error("UserClient getUsersByIds error: " . $e->getMessage());
            return collect();
        }
    }

    /**
     * Get multiple doctors by IDs
     */
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
                return [
                    'error' => $response->json('message') ?? 'Failed to fetch doctors',
                    'status' => $response->status(),
                    'body' => $response->json()
                ];
            }
        } catch (\Exception $e) {
            logger()->error("UserClient getDoctors error: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
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
            logger()->error("UserClient hasPermission error: " . $e->getMessage());
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
                return (object) $response["data"];
            } else {
                return null;
            }
        } catch (\Exception $e) {
            logger()->error("UserClient authUser error: " . $e->getMessage());
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
            logger()->error("UserClient getUsersRoles error: " . $e->getMessage());
            return $e->getMessage();
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
            logger()->error("UserClient deletePatientsByClinicId error: " . $e->getMessage());
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
            logger()->error("UserClient deleteUsersByClinicId error: " . $e->getMessage());
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
                return collect();
            }
        } catch (\Exception $e) {
            logger()->error("UserClient getUsersByClinicId error: " . $e->getMessage());
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
                return [];
            }
        } catch (\Exception $e) {
            logger()->error("UserClient getUsersCountByClinicIds error: " . $e->getMessage());
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
            logger()->error("UserClient getStaffsByClinicIds error: " . $e->getMessage());
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
            logger()->error("UserClient getDepartmentManagerByClinicId error: " . $e->getMessage());
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
                return null;
            }
        } catch (\Exception $e) {
            logger()->error("UserClient getUserbyUuid error: " . $e->getMessage());
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
            logger()->error("UserClient updatePatientDoctor error: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }


       public function getUsersByRole($roleName)
    {
        try {
            $response = Http::withHeaders([
                'X-Internal-Token' => env('INTERNAL_API_TOKEN'),
                'Accept' => 'application/json',
            ])->post($this->baseUrl . "/internal/users/by-role", [
                'role' => $roleName
            ]);

            if ($response->successful() && isset($response['data'])) {
                return collect($response['data'])->map(function ($item) {
                    return (object) $item;
                });
            }
            return collect();
        } catch (\Exception $e) {
            logger()->error("UserClient getUsersByRole error: " . $e->getMessage());
            return collect();
        }
    }
}