<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\RatingComment;
use Illuminate\Support\Facades\Auth;
use App\Model\User;
use App\Model\Hotel;

class RatingCommentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $columns = [
            'id',
            'user_id',
            'hotel_id',
            'comment',
            'total_rating',
            'created_at'
        ];
        $query = RatingComment::select($columns)
            ->with(['user' => function ($query) {
                $query->select('id', 'username', 'full_name');
            }])
            ->with(['hotel' => function ($query) {
                $query->select('id', 'name');
            }]);
        if (Auth::user()->is_admin ==User::ROLE_HOTELIER) {
            $query = $query->whereHas('hotel', function ($query) {
                $query->where('user_id', Auth::user()->id);
            });
        }
         $ratingComments = $query
            ->orderby('id', 'DESC')->paginate(RatingComment::ROW_LIMIT);
        return view('backend.comments.index', compact('ratingComments'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id of comment
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $result = RatingComment::findOrFail($id)->delete();

        if ($result) {
            flash(__('Deletion successful'))->success();
        } else {
            flash(__('Deletion failed'))->error();
        }
        return redirect()->route('comment.index');
    }
}
