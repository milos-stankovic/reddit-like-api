<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Thread;

class Comment extends Model
{
    protected $fillable = ['title', 'user_id', 'thread_id', 'parent_id', 'is_visible', 'vote'];

    /**
     * Relation to Thread Model
     *
     * @return object \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function thread()
    {
        return $this->belongsTo(Thread::class);
    }

    /**
     * @return int
     * Make comment visible
     */
    public function approveComment()
    {
        return $this->update(['is_visible' => 1]);
    }

    public function isCommentAuthor($user_id, $comment_id){
        return $user_id == $comment_id ? true : false;
    }

    public function isVisible()
    {
        return isset($this->is_visible) && $this->is_visible == 1 ? true : false;
    }

    public function upvote()
    {
        $vote = $this->vote;
        $vote ++;

        return $this->update(['vote' => $vote]);
    }
}
