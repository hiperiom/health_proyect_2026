<?php

namespace App\Services;

use App\Models\ServiceRequest;

class ServiceRequestService
{
    public function create(array $data): ServiceRequest
    {
        return ServiceRequest::create($data);
    }
}
