<?php

namespace App\Http\Middleware;

use App\Models\ExamSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserCanAccessPlanning
{
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (!auth()->check()) {
            return redirect('/admin/login')->with('error', 'Please login to access this page.');
        }

        $user = auth()->user();

        // Define allowed roles
        $allowedRoles = [
            \App\Models\User::ROLE_SUPER_ADMIN,
            \App\Models\User::ROLE_EXAM_ADMIN,
            \App\Models\User::ROLE_DEPARTMENT_HEAD,
            \App\Models\User::ROLE_DEAN,
            \App\Models\User::ROLE_PROFESSOR,
            \App\Models\User::ROLE_STUDENT,
        ];

        // Check if user has permission
        if (!in_array($user->role, $allowedRoles)) {
            abort(403, 'Unauthorized access.');
        }

        // Extract exam session ID from route
        $sessionId = $this->getSessionIdFromRequest($request);

        // If there's a session ID, validate it for students and professors only
        if ($sessionId && $this->requiresApprovalCheck($user)) {
            return $this->validateSessionApproval($sessionId, $user, $next, $request);
        }

        return $next($request);
    }

    /**
     * Check if user role requires approval validation
     */
    private function requiresApprovalCheck($user): bool
    {
        return in_array($user->role, [
            \App\Models\User::ROLE_PROFESSOR,
            \App\Models\User::ROLE_STUDENT,
        ]);
    }

    /**
     * Extract session ID from request
     */
    private function getSessionIdFromRequest(Request $request)
    {
        // Try to get from route parameters
        $sessionId = $request->route('session')
            ?? $request->route('exam_session')
            ?? $request->route('examSession');

        // If it's a model instance, get the ID
        if ($sessionId instanceof ExamSession) {
            return $sessionId->id;
        }

        // Try to get from query string
        if (!$sessionId) {
            $sessionId = $request->input('exam_session_id')
                ?? $request->input('session_id');
        }

        return $sessionId;
    }

    /**
     * Validate if exam session is approved
     */
    private function validateSessionApproval($sessionId, $user, Closure $next, Request $request): Response
    {
        $session = ExamSession::find($sessionId);

        if (!$session) {
            return $this->denyAccess(
                'Exam session not found.',
                'The requested exam session does not exist or has been deleted.',
                $request
            );
        }

        // Check approval status
        if (!$session->isFullyApproved()) {
            return $this->denyAccess(
                $this->getErrorTitle($session, $user),
                $this->getErrorMessage($session, $user),
                $request
            );
        }

        return $next($request);
    }

    /**
     * Generate error title based on approval status
     */
    private function getErrorTitle(ExamSession $session, $user): string
    {
        if ($session->isDeptRejected()) {
            return 'Schedule Rejected by Department';
        }

        if ($session->isRejected()) {
            return 'Schedule Rejected by Dean';
        }

        if ($user->role === \App\Models\User::ROLE_STUDENT) {
            return 'Exam Schedule Not Available Yet';
        }

        return 'Schedule Pending Approval';
    }

    /**
     * Generate detailed error message based on status and user role
     */
    private function getErrorMessage(ExamSession $session, $user): string
    {
        $isStudent = $user->role === \App\Models\User::ROLE_STUDENT;
        $isProfessor = $user->role === \App\Models\User::ROLE_PROFESSOR;

        // Department rejection
        if ($session->isDeptRejected()) {
            $baseMessage = 'This exam schedule was rejected by the Department Head.';

            if ($session->dept_rejection_reason) {
                $baseMessage .= "\n\nReason: " . $session->dept_rejection_reason;
            }

            if ($isStudent) {
                $baseMessage .= "\n\nPlease contact your department for more information.";
            } elseif ($isProfessor) {
                $baseMessage .= "\n\nA new schedule will be created and you will be notified.";
            }

            return $baseMessage;
        }

        // Dean rejection
        if ($session->isRejected()) {
            $baseMessage = 'This exam schedule was rejected by the Dean.';

            if ($session->rejection_reason) {
                $baseMessage .= "\n\nReason: " . $session->rejection_reason;
            }

            if ($isStudent) {
                $baseMessage .= "\n\nA revised schedule will be published soon.";
            } elseif ($isProfessor) {
                $baseMessage .= "\n\nPlease wait for the revised schedule to be published.";
            }

            return $baseMessage;
        }

        // Pending department approval
        if ($session->isDeptPending()) {
            if ($isStudent) {
                return 'Your exam schedule is currently being reviewed by the Department Head. You will be notified once it is approved and available.';
            } elseif ($isProfessor) {
                return 'This schedule is pending approval from the Department Head. Your surveillance assignments will be available once approved.';
            }

            return 'This schedule is pending approval from the Department Head.';
        }

        // Pending dean approval (already approved by department)
        if ($session->isPending() && $session->isDeptApproved()) {
            if ($isStudent) {
                return 'Your exam schedule has been approved by the Department Head and is awaiting final approval from the Dean. This is the last step before it becomes available.';
            } elseif ($isProfessor) {
                return 'This schedule has been approved by the Department Head and is pending final approval from the Dean. Almost ready!';
            }

            return 'This schedule is pending final approval from the Dean.';
        }

        // Default message
        if ($isStudent) {
            return 'Your exam schedule is not yet available. Please check back later or contact your department for more information.';
        } elseif ($isProfessor) {
            return 'This exam schedule is not yet available. You will be notified once surveillance assignments are published.';
        }

        return 'This schedule is not yet approved and available.';
    }

    /**
     * Deny access with appropriate response
     */
    private function denyAccess(string $title, string $message, Request $request): Response
    {
        // For API/JSON requests
        if ($request->expectsJson()) {
            return response()->json([
                'error' => $title,
                'message' => $message,
                'status' => 'not_approved'
            ], 403);
        }

        // For web requests
        return redirect()->back()->withErrors([
            'title' => $title,
            'message' => $message
        ])->with([
                    'alert_type' => 'warning',
                    'alert_title' => $title,
                    'alert_message' => $message
                ]);
    }
}
