<?php

namespace App\Policies;

use App\Models\Feedback;
use App\Models\User;

class FeedbackPolicy
{
    /**
     * Determine if the user can view any feedbacks.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine if the user can view the feedback.
     */
    public function view(User $user, Feedback $feedback): bool
    {
        return $user->isAdmin() || $user->id === $feedback->user_id;
    }

    /**
     * Determine if the user can create feedback.
     */
    public function create(User $user): bool
    {
        return true; // Both admin and member can create feedback
    }

    /**
     * Determine if the user can update the feedback.
     */
    public function update(User $user, Feedback $feedback): bool
    {
        return $user->isAdmin() || ($user->id === $feedback->user_id && $feedback->created_at->diffInMinutes() < 15);
    }

    /**
     * Determine if the user can delete the feedback.
     */
    public function delete(User $user, Feedback $feedback): bool
    {
        return $user->isAdmin() || ($user->id === $feedback->user_id && $feedback->created_at->diffInMinutes() < 15);
    }
}

