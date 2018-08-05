<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use App\Comment;

class Thread extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title', 'user_id', 'password',
    ];

    /**
     * Relation to Comment Model
     *
     * @return object \Illuminate\Database\Eloquent\Relations\hasMany
     */
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * @return bool
     * Thread	cannot	be	edited	6h	after	creation
     */
    public function isEditable()
    {
        $created_at = $this->created_at->format('H:i:s') ;
        $editable = Carbon::parse($created_at)->addHours(6)->format('H:i:s');
        $now = Carbon::now()->format('H:i:s');
        if($now > $editable){
            return false;
        }
        return true;
    }

    /**
     * @param $user_id
     * @return bool
     * @internal param $comment_id
     * Checks if user is creator of Thread.
     */
    public function isThreadAuthor($user_id){
        return $user_id == $this->user_id ? true : false;
    }

}
