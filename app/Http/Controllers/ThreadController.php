<?php

namespace App\Http\Controllers;

use App\Thread;
use App\Http\Requests\{
    ThreadStoreRequestForm, ThreadUpdateRequestForm
};
use Illuminate\Support\Facades\App;
use Tymon\JWTAuth\JWTAuth;

/**
 * @property  authUser
 */
class ThreadController extends Controller
{
    protected $authUser;

    /**
     * ThreadController constructor.
     * @param JWTAuth $auth
     */
    public function __construct(JWTAuth $auth)
    {
        // to avoid "token could not be parsed from the request" error when user is logged out and run cli commands.
        if (!App::runningInConsole()) {
            $auth->parseToken();
            $this->authUser = $auth->toUser();
        }

    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
//        dd($this->authUser);
        $currentUser = $this->authUser;
        $threads = Thread::where('user_id', $currentUser->id)->with('comments')->get();

        return $threads->toArray();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\ThreadStoreRequestForm $request
     * @return \Illuminate\Http\Response
     */
    public function store(ThreadStoreRequestForm $request)
    {
        $currentUser = $this->authUser;
        $thread = Thread::create([
            'title' => $request->title,
            'user_id' => $currentUser->id,
        ]);

        if (!$thread) {
            return response()->json([
                'success' => false,
                'message' => 'Sorry, product could not be added'
            ], 500);
        }

        return response()->json([
            'success' => true,
            'thread' => $thread
        ], 200);
    }

    /**
     * Display the specified resource.
     *
     * @param $id
     * @return \Illuminate\Http\Response
     * @internal param Thread $thread
     */
    public function show($id)
    {
        $thread = Thread::find($id);

        if (!$thread) {
            return response()->json([
                'success' => false,
                'message' => 'Sorry, thread with id ' . $id . ' cannot be found'
            ], 404);
        }
        return $thread;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param $id
     * @param ThreadUpdateRequestForm $request
     * @return \Illuminate\Http\Response
     * @internal param Thread $thread
     */
    public function update($id, ThreadUpdateRequestForm $request)
    {
        $currentUser = $this->authUser;
        $thread = $this->show($id);

        if (!$thread) {
            return response()->json([
                'success' => false,
                'message' => 'Sorry, thread with id ' . $id . ' cannot be found'
            ], 404);
        }

        if ($thread->isEditable() == false) {
            return response()->json([
                'success' => false,
                'message' => 'Sorry, thread	cannot be edited 6h	after creation'
            ], 500);
        }

        $isAuthor = $thread->isThreadAuthor($currentUser->id);
         if ($isAuthor == false) {
             return response()->json([
                 'success' => false,
                 'message' => 'Only user who created the thread can make updates'
             ], 500);
         }

        $updated = $thread->fill($request->all())
            ->save();

        if (!$updated) {
            return response()->json([
                'success' => false,
                'message' => 'Sorry, thread could not be updated'
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Thread successfully updated'
        ], 204);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param $id
     * @return \Illuminate\Http\Response
     * @internal param Thread $thread
     */
    public function destroy($id)
    {
        $thread = $this->show($id); //TODO: delete with comments in relatioin

        if (!$thread) {
            return response()->json([
                'success' => false,
                'message' => 'Sorry, thread with id ' . $id . ' cannot be found'
            ], 404);
        }

        if (!$thread->delete()) {
            return response()->json([
                'success' => false,
                'message' => 'Thread could not be deleted'
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Thread is deleted.'
        ], 200);
    }
}
