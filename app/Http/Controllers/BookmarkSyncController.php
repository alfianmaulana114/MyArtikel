<?php

namespace App\Http\Controllers;

use App\Models\BookmarkSync;
use App\Models\Bookmark;
use App\Models\BookmarkCategory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class BookmarkSyncController extends Controller
{
    /**
     * Get all registered devices for the user.
     */
    public function devices(Request $request): JsonResponse
    {
        $devices = BookmarkSync::where('user_id', auth()->id())
            ->orderBy('last_sync_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'devices' => $devices,
                'current_device' => $this->getCurrentDevice($request)
            ]
        ]);
    }

    /**
     * Register a new device for sync.
     */
    public function registerDevice(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'device_name' => 'required|string|max:100',
            'device_type' => 'nullable|string|in:mobile,desktop,tablet,other',
            'device_id' => 'required|string|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $device = BookmarkSync::updateOrCreate(
            [
                'user_id' => auth()->id(),
                'device_id' => $request->device_id
            ],
            [
                'device_name' => $request->device_name,
                'device_type' => $request->device_type ?? 'other',
                'sync_token' => Str::random(64),
                'is_active' => true,
                'last_sync_at' => now()
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Device registered successfully',
            'data' => $device
        ], 201);
    }

    /**
     * Unregister a device.
     */
    public function unregisterDevice(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $device = BookmarkSync::where('user_id', auth()->id())
            ->where('device_id', $request->device_id)
            ->first();

        if (!$device) {
            return response()->json([
                'success' => false,
                'message' => 'Device not found'
            ], 404);
        }

        $device->delete();

        return response()->json([
            'success' => true,
            'message' => 'Device unregistered successfully'
        ]);
    }

    /**
     * Get sync data for the current device.
     */
    public function sync(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string|max:100',
            'last_sync_at' => 'nullable|date',
            'sync_token' => 'required|string|size:64'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Verify device and sync token
        $device = $this->verifyDevice($request->device_id, $request->sync_token);
        if (!$device) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid device or sync token'
            ], 401);
        }

        $lastSyncAt = $request->last_sync_at ? 
            \Carbon\Carbon::parse($request->last_sync_at) : null;

        // Get changes since last sync
        $changes = $this->getChangesSinceLastSync(auth()->id(), $lastSyncAt);

        // Update last sync time
        $device->update(['last_sync_at' => now()]);

        return response()->json([
            'success' => true,
            'data' => [
                'changes' => $changes,
                'sync_timestamp' => now()->toISOString(),
                'server_timestamp' => now()->toISOString()
            ]
        ]);
    }

    /**
     * Process sync data from client.
     */
    public function processSync(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string|max:100',
            'sync_token' => 'required|string|size:64',
            'changes' => 'required|array',
            'changes.bookmarks' => 'nullable|array',
            'changes.categories' => 'nullable|array',
            'changes.deletions' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Verify device and sync token
        $device = $this->verifyDevice($request->device_id, $request->sync_token);
        if (!$device) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid device or sync token'
            ], 401);
        }

        $changes = $request->changes;
        $conflicts = [];
        $processed = [];

        DB::transaction(function () use ($changes, &$conflicts, &$processed) {
            // Process categories
            if (!empty($changes['categories'])) {
                foreach ($changes['categories'] as $categoryData) {
                    $result = $this->processCategoryChange($categoryData);
                    if ($result['conflict']) {
                        $conflicts[] = $result;
                    } else {
                        $processed[] = $result;
                    }
                }
            }

            // Process bookmarks
            if (!empty($changes['bookmarks'])) {
                foreach ($changes['bookmarks'] as $bookmarkData) {
                    $result = $this->processBookmarkChange($bookmarkData);
                    if ($result['conflict']) {
                        $conflicts[] = $result;
                    } else {
                        $processed[] = $result;
                    }
                }
            }

            // Process deletions
            if (!empty($changes['deletions'])) {
                foreach ($changes['deletions'] as $deletionData) {
                    $result = $this->processDeletion($deletionData);
                    if ($result['conflict']) {
                        $conflicts[] = $result;
                    } else {
                        $processed[] = $result;
                    }
                }
            }
        });

        // Update last sync time
        $device->update(['last_sync_at' => now()]);

        return response()->json([
            'success' => true,
            'data' => [
                'processed' => $processed,
                'conflicts' => $conflicts,
                'sync_timestamp' => now()->toISOString()
            ]
        ]);
    }

    /**
     * Get sync conflicts.
     */
    public function conflicts(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string|max:100',
            'sync_token' => 'required|string|size:64'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Verify device and sync token
        $device = $this->verifyDevice($request->device_id, $request->sync_token);
        if (!$device) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid device or sync token'
            ], 401);
        }

        // Get unresolved conflicts for the user
        $conflicts = $this->getUnresolvedConflicts(auth()->id());

        return response()->json([
            'success' => true,
            'data' => [
                'conflicts' => $conflicts
            ]
        ]);
    }

    /**
     * Resolve a sync conflict.
     */
    public function resolveConflict(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string|max:100',
            'sync_token' => 'required|string|size:64',
            'conflict_id' => 'required|string',
            'resolution' => 'required|in:local,remote,merge',
            'merged_data' => 'required_if:resolution,merge|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Verify device and sync token
        $device = $this->verifyDevice($request->device_id, $request->sync_token);
        if (!$device) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid device or sync token'
            ], 401);
        }

        $resolution = $this->resolveConflictById(
            $request->conflict_id, 
            $request->resolution, 
            $request->merged_data ?? []
        );

        return response()->json([
            'success' => true,
            'message' => 'Conflict resolved successfully',
            'data' => $resolution
        ]);
    }

    /**
     * Verify device and sync token.
     */
    private function verifyDevice(string $deviceId, string $syncToken): ?BookmarkSync
    {
        return BookmarkSync::where('user_id', auth()->id())
            ->where('device_id', $deviceId)
            ->where('sync_token', $syncToken)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Get current device information.
     */
    private function getCurrentDevice(Request $request): array
    {
        $deviceId = $request->header('X-Device-ID') ?? $request->ip();
        
        return [
            'device_id' => $deviceId,
            'device_name' => $this->getDeviceName($request),
            'device_type' => $this->getDeviceType($request)
        ];
    }

    /**
     * Get device name from request.
     */
    private function getDeviceName(Request $request): string
    {
        $userAgent = $request->header('User-Agent', 'Unknown');
        
        // Parse user agent to get device name
        if (strpos($userAgent, 'Windows') !== false) {
            return 'Windows Device';
        } elseif (strpos($userAgent, 'Mac') !== false) {
            return 'Mac Device';
        } elseif (strpos($userAgent, 'Linux') !== false) {
            return 'Linux Device';
        } elseif (strpos($userAgent, 'Android') !== false) {
            return 'Android Device';
        } elseif (strpos($userAgent, 'iPhone') !== false || strpos($userAgent, 'iPad') !== false) {
            return 'iOS Device';
        }
        
        return 'Unknown Device';
    }

    /**
     * Get device type from request.
     */
    private function getDeviceType(Request $request): string
    {
        $userAgent = $request->header('User-Agent', '');
        
        if (strpos($userAgent, 'Mobile') !== false) {
            return 'mobile';
        } elseif (strpos($userAgent, 'Tablet') !== false || strpos($userAgent, 'iPad') !== false) {
            return 'tablet';
        } else {
            return 'desktop';
        }
    }

    /**
     * Get changes since last sync.
     */
    private function getChangesSinceLastSync(int $userId, ?\Carbon\Carbon $lastSyncAt): array
    {
        $bookmarks = Bookmark::forUser($userId)
            ->when($lastSyncAt, function ($query) use ($lastSyncAt) {
                return $query->where('updated_at', '>', $lastSyncAt);
            })
            ->with(['category'])
            ->get();

        $categories = BookmarkCategory::forUser($userId)
            ->when($lastSyncAt, function ($query) use ($lastSyncAt) {
                return $query->where('updated_at', '>', $lastSyncAt);
            })
            ->get();

        return [
            'bookmarks' => $bookmarks,
            'categories' => $categories,
            'timestamp' => now()->toISOString()
        ];
    }

    /**
     * Process category change from sync.
     */
    private function processCategoryChange(array $categoryData): array
    {
        // Implementation for processing category changes
        // This would handle conflicts and return appropriate result
        return ['conflict' => false, 'processed' => true];
    }

    /**
     * Process bookmark change from sync.
     */
    private function processBookmarkChange(array $bookmarkData): array
    {
        // Implementation for processing bookmark changes
        // This would handle conflicts and return appropriate result
        return ['conflict' => false, 'processed' => true];
    }

    /**
     * Process deletion from sync.
     */
    private function processDeletion(array $deletionData): array
    {
        // Implementation for processing deletions
        // This would handle conflicts and return appropriate result
        return ['conflict' => false, 'processed' => true];
    }

    /**
     * Get unresolved conflicts.
     */
    private function getUnresolvedConflicts(int $userId): array
    {
        // Implementation for getting unresolved conflicts
        return [];
    }

    /**
     * Resolve conflict by ID.
     */
    private function resolveConflictById(string $conflictId, string $resolution, array $mergedData): array
    {
        // Implementation for resolving conflicts
        return ['resolved' => true];
    }
}