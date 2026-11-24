<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Support\Media\OperationsPhotos;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * ⚠️ VY-34 (audit of 2026-11-24) — a housekeeping or maintenance photo, for a signed-in member of the back office who can see its task
 * or work order. Registered inside the `/app` group, so the back-office gate (#26), the tenant and the person's properties all apply
 * before {@see OperationsPhotos::find()} looks the photo up. Read-only: a GET that writes nothing.
 */
final class OperationsPhotoController extends Controller
{
    public function __invoke(string $media): BinaryFileResponse
    {
        return OperationsPhotos::response(OperationsPhotos::find($media));
    }
}
