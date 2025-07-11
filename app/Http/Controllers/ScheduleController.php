<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Throwable;
use App\Models\Schedule;
use App\Models\User;
use App\Http\Controllers\Controller;

class ScheduleController extends Controller
{
    private const NOT_FOUND_MSG = 'User not found or does not have Counselor role';
    public static function getSchedule()
    {
        try {
            $users = User::where('role', 'Counselor')->with([
                'expertises',
                'schedules' => function ($schedule) {
                    $schedule->whereDate('available_date', '>=', now())->where('status', '=', 'Available')->orderBy('available_date');
                }
            ])->select('id', 'name')->get();

            $schedules = $users->map(function ($user) {
                return [
                    'counselor_id' => $user->id,
                    'name' => $user ? $user->name : 'Unknown',
                    'expertise' => $user->expertises->isNotEmpty()
                        ? $user->expertises->pluck('type')->toArray()
                        : ['None'],

                    'schedules' => $user->schedules->groupBy(function ($schedule) {
                        return $schedule->available_date->format('Y-m-d');
                    })->map(function ($dateSchedules, $date) {
                        return [
                            'date' => $date,
                            'schedulesByDate' => $dateSchedules->map(function ($schedule) {
                                return [
                                    'schedule_id' => $schedule->schedule_id,
                                    'start_time' => $schedule->start_time,
                                    'end_time' => $schedule->end_time,
                                    'status' => $schedule->status,
                                ];
                            })->values(),
                        ];
                    })->values(),
                ];
            })->values();

            return response()->json([
                'status' => true,
                'message' => 'Data Schedule grouped by counselor ID',
                'data' => $schedules,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public static function getAvailableSchedule()
    {
        try {
            $users = User::where('role', 'Counselor')->with([
                'expertises',
                'schedules' => function ($schedule) {
                    $schedule
                        ->whereDate('available_date', '>=', now())

                        ->whereBetween('available_date', [Carbon::tomorrow(), Carbon::tomorrow()->addDays(6)])
                        ->where('status', '=', 'Available')

                        ->orderBy('available_date');
                }
            ])->get();

            $availableSchedules = $users->map(function ($user) {
                return [
                    'counselor_id' => $user->id,
                    'name' => $user ? $user->name : 'Unknown',
                    'profile_url' => $user->profile_url,
                    'expertise' => $user->expertises->isNotEmpty()
                        ? $user->expertises->pluck('type')->toArray()
                        : ['None'],
                    'schedules' => $user->schedules->groupBy(function ($schedule) {
                        return $schedule->available_date;
                    })->map(function ($dateSchedules, $date) {
                        return [
                            'date' => $date,
                            'schedulesByDate' => $dateSchedules->map(function ($schedule) {
                                return [
                                    'schedule_id' => $schedule->schedule_id,
                                    'start_time' => $schedule->start_time,
                                    'end_time' => $schedule->end_time,
                                    'status' => $schedule->status,
                                ];
                            })->values(),
                        ];
                    })->values(),
                ];
            })->values();

            return response()->json([
                'status' => true,
                'message' => 'Available schedules grouped by counselor ID',
                'data' => $availableSchedules,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public static function getScheduleByID($id)
    {
        try {

            $user = User::where('role', 'Counselor')->where('id', $id)->with([
                'expertises',
                'schedules'
            ])->select('id', 'name')->first();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => self::NOT_FOUND_MSG,
                ], 404);
            }

            $schedules = [
                'counselor_id' => $user->id,
                'name' => $user->name ?? 'Unknown',
                'expertise' => $user->expertises->isNotEmpty()
                    ? $user->expertises->pluck('type')->toArray()
                    : ['None'],
                'schedules' => $user->relationLoaded('schedules') && $user->schedules->isNotEmpty()
                    ? $user->schedules->groupBy(fn($schedule) => $schedule->available_date)
                        ->map(fn($dateSchedules, $date) => [
                            'date' => $date,
                            'schedulesByDate' => $dateSchedules->map(fn($schedule) => [
                                'schedule_id' => $schedule->schedule_id,
                                'start_time' => $schedule->start_time,
                                'end_time' => $schedule->end_time,
                                'status' => $schedule->status,
                            ])->values(),
                        ])->values()
                    : [],
            ];
            return response()->json([
                'status' => true,
                'message' => 'Data Schedule for user',
                'data' => $schedules
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public static function getAvailableScheduleByID($id)
    {
        try {

            $user = User::where('role', 'Counselor')->where('id', $id)->with([
                'expertises',
                'schedules' => function ($schedule) {
                    $schedule->whereDate('available_date', '>=', now())->where('status', '=', 'Available')->orderBy('available_date');
                }
            ])->select('id', 'name')->first();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => self::NOT_FOUND_MSG,
                ], 404);
            }

            $schedules = [
                'counselor_id' => $user->id,
                'name' => $user->name ?? 'Unknown',
                'expertise' => $user->expertises->isNotEmpty()
                    ? $user->expertises->pluck('type')->toArray()
                    : ['None'],
                'profile_url' => $user->profile_url,
                'schedules' => $user->relationLoaded('schedules') && $user->schedules->isNotEmpty()
                    ? $user->schedules->groupBy(fn($schedule) => $schedule->available_date->format('Y-m-d'))
                        ->map(fn($dateSchedules, $date) => [
                            'date' => $date,
                            'schedulesByDate' => $dateSchedules->map(fn($schedule) => [
                                'schedule_id' => $schedule->schedule_id,
                                'start_time' => $schedule->start_time,
                                'end_time' => $schedule->end_time,
                                'status' => $schedule->status,
                            ])->values(),
                        ])->values()
                    : [],
            ];
            return response()->json([
                'status' => true,
                'message' => 'Data Schedule for user',
                'data' => $schedules
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public static function createSchedule(Request $request)
    {
        try {
            $validated = $request->validate([
                'available_date' => 'required|date',
                'start_time' => 'required|string',
                'end_time' => 'required|string',
                'status' => 'required|in:Available,Booked,Done',
                'counselor_id' => 'required|exists:users,id',
            ]);

            $counselorId = $request['counselor_id'];
            $user = User::where('role', 'Counselor')->find($counselorId);

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => self::NOT_FOUND_MSG,
                ], 404);
            }

            $schedule = Schedule::create($validated);

            return response()->json([
                'status' => true,
                'message' => 'Schedule created successfully',
                'data' => $schedule,
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public static function updateScheduleStatus($id)
    {
        try {
            $schedule = Schedule::find($id);

            if (!$schedule) {
                return response()->json([
                    'status' => false,
                    'message' => 'Schedule not found',
                ], 404);
            }

            $schedule->update([
                'status' => 'Booked'
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Schedule status updated successfully to booked',
                'data' => $schedule,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
