<?php

namespace App\Http\Middleware;

use App\Models\ExamSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureExamSessionApproved
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        // Allow admins, deans, and department heads to bypass
        if ($user && ($user->isSuperAdmin() || $user->isDean() || $user->isDepartmentHead() || $user->isExamAdmin())) {
            return $next($request);
        }

        // Get exam session from route parameter
        $sessionId = $request->route('session') ?? $request->route('exam_session');

        if (!$sessionId) {
            // If no session in route, check if it's in the request
            $sessionId = $request->input('exam_session_id');
        }

        // If session ID is an object (model binding), get its ID
        if ($sessionId instanceof ExamSession) {
            $session = $sessionId;
        } else {
            $session = ExamSession::find($sessionId);
        }

        // If no session found, deny access
        if (!$session) {
            return $this->denyAccess('Exam session not found.');
        }

        // Check if session is fully approved
        if (!$session->isFullyApproved()) {
            return $this->denyAccess(
                'This exam schedule is not yet available.',
                $this->getApprovalMessage($session)
            );
        }

        return $next($request);
    }

    /**
     * Generate approval status message
     */
    private function getApprovalMessage(ExamSession $session): string
    {
        if ($session->isDeptRejected()) {
            return 'This schedule was rejected by the Department Head. Reason: ' . $session->dept_rejection_reason;
        }

        if ($session->isRejected()) {
            return 'This schedule was rejected by the Dean. Reason: ' . $session->rejection_reason;
        }

        if ($session->isDeptPending()) {
            return 'This schedule is pending approval from the Department Head.';
        }

        if ($session->isPending() && $session->isDeptApproved()) {
            return 'This schedule has been approved by the Department Head and is pending final approval from the Dean.';
        }

        return 'This schedule is not yet approved.';
    }

    /**
     * Deny access with custom message
     */
    private function denyAccess(string $title, string $message = null): Response
    {
        if (request()->expectsJson()) {
            return response()->json([
                'error' => $title,
                'message' => $message ?? $title,
                'status' => 'not_approved'
            ], 403);
        }

        return redirect()->back()->with([
            'error' => $title,
            'message' => $message
        ])->setStatusCode(403);
    }
}
