<?php

namespace App\Http\Controllers;

use App\Comment;
use Illuminate\Http\Request;
use App\Http\Requests\{
    CommentsStoreRequestForm, CommentsUpdateRequestForm
};
use Illuminate\Http\Response;
use Illuminate\Support\Facades\App;
use Tymon\JWTAuth\JWTAuth;

/**
 * @property  authUser
 */
class CommentsController extends Controller
{
    protected $authUser;
    /**
     * CommentsController constructor.
     * @param JWTAuth $auth
     */
    public function __construct(JWTAuth $auth)
    {
        // to avoid "token could not be parsed from the request" error when user is logged out and run cli commands.
        if(!App::runningInConsole()){
            $auth->parseToken();
            $this->authUser = $auth->toUser();
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param CommentsStoreRequestForm|Request $request
     * @return Response
     * @internal param $thread
     * @internal param $thread_id
     */
    public function store(Request $request)
    {
        $currentUser = $this->authUser;
        $comment = Comment::create([
            'title' => $request->title,
            'user_id' => $currentUser->id,
            'thread_id' => $request->thread
        ]);

        if (!$comment) {
            return response()->json([
                'success' => false,
                'message' => 'Sorry, comment could not be added'
            ], 500);
        }

        return response()->json([
            'success' => true,
            'thread' => $comment
        ], 200);
    }

    public function reply(Request $request, $id)
    {
        $currentUser = $this->authUser;
        $parent_comment = $this->show($id);
        $comment = Comment::create([
            'title' => $request->title,
            'thread_id' => $parent_comment->thread_id,
            'parent_id' => $id,
            'user_id' => $currentUser->id
        ]);

        if (!$comment) {
            return response()->json([
                'success' => false,
                'message' => 'Sorry, comment could not be added'
            ], 500);
        }

        return response()->json([
            'success' => true,
            'thread' => $comment
        ], 200);
    }

    /**
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     * Author of thread can approve comment so it will be visible.
     */
    public function approveComment(Request $request, $id)
    {
        $currentUser = $this->authUser;
        $comment = $this->show($id);
        $isAuthor = $comment->thread->isThreadAuthor($currentUser->id);

        if ($isAuthor == false) {
            return response()->json([
                'success' => false,
                'message' => 'Only user who created the thread can make comments visible'
            ], 500);
        }

        if (!$comment) {
            return response()->json([
                'success' => false,
                'message' => 'Sorry, comment could not be added'
            ], 500);
        }

        if ($comment->approveComment()) {
            return response()->json([
                'success' => true,
                'thread' => $comment
            ], 204);
        }

        return response()->json([
            'success' => false,
            'message' => 'Sorry, comment could not set visible.'
        ], 500);

    }

    /**
     * @param $id
     * Upvote comment.
     * @return \Illuminate\Http\JsonResponse
     */
    public function upvote($id)
    {
        $comment = $this->show($id);

        if (!$comment) {
            return response()->json([
                'success' => false,
                'message' => 'Sorry, comment could not be found'
            ], 404);
        }

        if ($comment->upvote()) {
            return response()->json([
                'success' => true,
                'thread' => $comment
            ], 204);
        }

        return response()->json([
            'success' => false,
            'message' => 'Sorry, comment could not upvote'
        ], 500);
    }

    /**
     * Display the specified resource.
     *
     * @param $id
     * @return Response
     * @internal param Comment $comments
     */
    public function show($id)
    {
        $comment = Comment::find($id);

        if (!$comment) {
            return response()->json([
                'success' => false,
                'message' => 'Sorry, comment with id ' . $id . ' cannot be found'
            ], 400);
        }

        return $comment;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param $id
     * @param CommentsUpdateRequestForm $request
     * @return Response
     * @internal param Comment $comments
     */
    public function update($id, CommentsUpdateRequestForm $request)
    {
        $comment = $this->show($id);

        if (!$comment) {
            return response()->json([
                'success' => false,
                'message' => 'Sorry, comment with id ' . $id . ' cannot be found'
            ], 400);
        }

        $updated = $comment->fill($request->all())
            ->save();

        if (!$updated) {
            return response()->json([
                'success' => false,
                'message' => 'Sorry, thread could not be updated'
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Comment successfully updated'
        ], 204);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Comment $comments
     * @return Response
     */
    public function destroy($id)
    {
        $comment = $this->show($id);

        if (!$comment) {
            return response()->json([
                'success' => false,
                'message' => 'Sorry, comment with id ' . $id . ' cannot be found'
            ], 400);
        }

        if (!$comment->delete()) {
            return response()->json([
                'success' => false,
                'message' => 'Thread could not be deleted'
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Comment successfully deleted'
        ], 200);

    }
}
